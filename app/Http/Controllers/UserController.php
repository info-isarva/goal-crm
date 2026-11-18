<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Permission;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use App\Mail\UserWelcomeMail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Support\SafeUpload;
use App\Services\Goal\GoalDashboardService;

class UserController extends Controller
{
    protected function usesGoalTargets(?User $user = null): bool
    {
        return GoalDashboardService::userUsesGoalDashboard($user ?: auth()->user());
    }

    protected function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        return (int) ($user->crm_role_type ?? -1) === 0;
    }

    /**
     * Permissions the current user is allowed to assign on create/edit.
     * Super Admin: all. Admin/others: Super-Admin whitelist only.
     */
    protected function assignablePermissions()
    {
        $permissions = Permission::with('parentPermission')->orderBy('name')->get();
        $company = Company::current();

        if (!$company->isWebFormsEnabled()) {
            $permissions = $permissions->reject(fn ($p) => Permission::isWebFormsGuard($p->guard_name));
        }

        if (!$company->isSocialAdsEnabled()) {
            $permissions = $permissions->reject(fn ($p) => Permission::isSocialAdsGuard($p->guard_name));
        }

        if ($this->isSuperAdmin()) {
            return $permissions->values();
        }

        $allowed = $company->adminAssignableGuards();
        if (empty($allowed)) {
            return $permissions->filter(fn () => false)->values();
        }

        return $permissions->filter(function ($permission) use ($allowed) {
            return in_array($permission->guard_name, $allowed, true);
        })->values();
    }

    /**
     * Users available in Reports To / Assign Manager dropdowns.
     * Active users only; excludes Super Admin and optional self (edit).
     */
    protected function reportingUsersQuery(?int $excludeUserId = null)
    {
        return User::query()
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('crm_role_type')
                    ->orWhere('crm_role_type', '!=', 0);
            })
            ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->orderBy('name');
    }

    /**
     * Role → default permission guards for the Add/Edit User UI.
     * MD (4) and CEO (5) always receive every assignable module permission.
     */
    protected function buildRolePermissionsMap($crmRoles, $permissions): array
    {
        $allowedGuards = $permissions->pluck('guard_name')->map(fn ($g) => (string) $g)->values()->all();
        $fullAccessRoleIds = [4, 5]; // MD, CEO
        $map = [];

        foreach ($crmRoles as $role) {
            $roleId = (int) $role->id;
            if (in_array($roleId, $fullAccessRoleIds, true)) {
                $map[$roleId] = $allowedGuards;
                continue;
            }

            $rolePerms = $role->permissions()->pluck('guard_name')->map(fn ($g) => (string) $g)->all();
            $map[$roleId] = array_values(array_intersect($rolePerms, $allowedGuards));
        }

        return $map;
    }

    protected function filterAssignableGuards(array $guards): array
    {
        $guards = array_values(array_filter(array_map('strval', $guards)));
        $company = Company::current();

        if (!$company->isWebFormsEnabled()) {
            $guards = array_values(array_filter($guards, fn ($g) => !Permission::isWebFormsGuard($g)));
        }

        if (!$company->isSocialAdsEnabled()) {
            $guards = array_values(array_filter($guards, fn ($g) => !Permission::isSocialAdsGuard($g)));
        }

        if ($this->isSuperAdmin()) {
            return $guards;
        }

        $allowed = $company->adminAssignableGuards();

        return array_values(array_intersect($guards, $allowed));
    }

    /**
     * Validation rules for crm_role_type: must be an existing role id. Super Admin (0) is
     * never assignable from the form (only kept when a Super Admin edits a Super Admin),
     * and only Super Admin / Admin may grant the Admin role.
     */
    protected function roleTypeRules(?User $target = null): array
    {
        $current = auth()->user();
        $allowed = \App\Models\Role::pluck('id')->map(fn ($id) => (string) $id)->all();

        if (!in_array((int) ($current->crm_role_type ?? -1), [0, 1], true)) {
            $allowed = array_values(array_diff($allowed, ['1']));
        }

        if ($target && (int) $target->crm_role_type === 0 && $this->isSuperAdmin()) {
            $allowed[] = '0';
        }

        return ['required', Rule::in($allowed)];
    }

    /**
     * Super Admin: everyone. Admin: everyone except Super Admin.
     * Others: only users assigned to them as manager.
     */
    protected function ensureCanManageUser(User $target): void
    {
        $current = auth()->user();
        $role = (int) ($current->crm_role_type ?? -1);

        if ($role === 0) {
            return;
        }
        if ((int) $target->crm_role_type === 0) {
            abort(403, 'You cannot manage Super Admin.');
        }
        if ($role === 1) {
            return;
        }
        if ((int) $target->assign_manager !== (int) $current->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function assertCanCreateUser()
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        $company = Company::current();
        $currentCount = User::countNonSuperAdmins();
        if (!$company->canCreateMoreUsers($currentCount)) {
            $limit = $company->maxUsersLimit();

            return redirect()
                ->route('users.index')
                ->with('error', "User create limit reached ({$currentCount}/{$limit}). Ask Super Admin to increase Access Control limit.");
        }

        return null;
    }

    /**
     * Admin: Update 2FA status for any user
     */
    public function update2FA(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $user = User::findOrFail($id);
        $this->ensureCanManageUser($user);
        $enabled = $request->has('2fa_enabled');
        $user->{"2fa_enabled"} = $enabled;
        $user->save();
        if (auth()->check() && auth()->id() == $user->id) {
            if ($enabled) {
                $request->session()->put('2fa:passed', true);
            } else {
                $request->session()->forget('2fa:passed');
            }
        }

        return redirect()->route('users.index')->with('success', '2FA status updated for user.');
    }

    /**
     * Activate / deactivate a user account.
     */
    public function updateStatus(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $user = User::findOrFail($id);
        $this->ensureCanManageUser($user);
        if ((int) $user->crm_role_type === 0) {
            return redirect()->route('users.index')->with('error', 'Super Admin status cannot be changed here.');
        }
        if ((int) $user->id === (int) auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot deactivate your own account.');
        }

        $status = (int) $request->input('status', $user->status ? 0 : 1) === 1 ? 1 : 0;
        $user->status = $status;
        $user->updated_by = auth()->id();
        $user->save();

        return redirect()->route('users.index')->with(
            'success',
            $status === 1 ? 'User activated successfully.' : 'User deactivated successfully.'
        );
    }

    /**
     * Send password reset link to the user (existing Laravel password broker).
     */
    public function sendPasswordReset(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $user = User::findOrFail($id);
        $this->ensureCanManageUser($user);

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->route('users.index')->with('success', 'Password reset link sent to '.$user->email);
        }

        return redirect()->route('users.index')->with('error', __($status));
    }

    /**
     * Export filtered users as CSV (Excel-friendly) or PDF.
     */
    public function export(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $format = strtolower((string) $request->get('format', 'csv'));
        [$query] = $this->applyUserListFilters($this->manageableUsersQuery(), $request);
        $rows = $query->get();
        $roles = \App\Models\Role::pluck('name', 'id');

        $lines = [['#', 'Name', 'Email', 'Mobile', 'CRM Role', 'Status', '2FA', 'Sales Target']];
        foreach ($rows as $i => $u) {
            $role = ((int) $u->crm_role_type === 0)
                ? 'Super Admin'
                : ($roles[$u->crm_role_type] ?? 'Unknown');
            $lines[] = [
                $i + 1,
                $u->name,
                $u->email,
                $u->mobile ?? '',
                $role,
                (int) $u->status === 1 ? 'Active' : 'Inactive',
                $u->{"2fa_enabled"} ? 'Enabled' : 'Disabled',
                number_format((float) ($u->sales_target ?? 0), 2, '.', ''),
            ];
        }

        if ($format === 'pdf') {
            $html = '<h2 style="font-family:DejaVu Sans,sans-serif;">Users Export</h2>';
            $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse:collapse;font-family:DejaVu Sans,sans-serif;font-size:11px;">';
            foreach ($lines as $ri => $line) {
                $html .= '<tr>';
                foreach ($line as $cell) {
                    $tag = $ri === 0 ? 'th' : 'td';
                    $html .= '<'.$tag.'>'.e((string) $cell).'</'.$tag.'>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';

            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L']);
            $mpdf->WriteHTML($html);

            return response($mpdf->Output('users-export.pdf', 'S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="users-export.pdf"',
            ]);
        }

        $csv = collect($lines)->map(function ($r) {
            return collect($r)->map(function ($c) {
                return '"'.str_replace('"', '""', (string) $c).'"';
            })->implode(',');
        })->implode("\n");

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users-export.csv"',
        ]);
    }

    /**
     * Base query for users the current actor may manage/list.
     */
    protected function manageableUsersQuery(?User $currentUser = null)
    {
        $currentUser = $currentUser ?: auth()->user();
        $isSuperAdmin = $this->isSuperAdmin($currentUser);
        $query = User::query();

        if ($isSuperAdmin) {
            return $query;
        }
        if ((int) $currentUser->crm_role_type === 1) {
            return $query->where('crm_role_type', '!=', 0);
        }
        if ((int) $currentUser->crm_role_type === 2) {
            return $query->where('assign_manager', $currentUser->id);
        }

        return $query->where('status', 1);
    }

    protected function applyUserListFilters($query, Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($request->filled('crm_role_type') && $request->crm_role_type !== '') {
            $query->where('crm_role_type', (int) $request->crm_role_type);
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('two_fa') && $request->two_fa !== '') {
            $query->where('2fa_enabled', (int) $request->two_fa === 1 ? 1 : 0);
        }

        $sort = in_array($request->get('sort'), ['name', 'email', 'crm_role_type', 'status', 'sales_target', 'created_at'], true)
            ? $request->get('sort')
            : 'name';
        $dir = strtolower((string) $request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $dir);

        return [$query, $sort, $dir];
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $currentUser = auth()->user();
        $isSuperAdmin = $this->isSuperAdmin($currentUser);

        $perPage = (int) $request->get('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $baseQuery = $this->manageableUsersQuery($currentUser)->with(['orgUnit']);
        [$listQuery, $sort, $dir] = $this->applyUserListFilters(clone $baseQuery, $request);
        $users = $listQuery->paginate($perPage)->withQueryString();

        // KPI cards from manageable set (unfiltered by search/role/status/2fa)
        $kpiBase = $this->manageableUsersQuery($currentUser);
        $listStats = [
            'total' => (clone $kpiBase)->count(),
            'active' => (clone $kpiBase)->where('status', 1)->count(),
            'inactive' => (clone $kpiBase)->where('status', 0)->count(),
            'two_fa' => (clone $kpiBase)->where('2fa_enabled', 1)->count(),
        ];

        $roles = \App\Models\Role::pluck('name', 'id');
        $allPermissions = \App\Models\Permission::orderBy('name')->pluck('name')->toArray();
        $company = Company::current();
        $userCount = User::countNonSuperAdmins();
        $maxUsers = $company->maxUsersLimit();

        $filters = [
            'q' => $request->get('q', ''),
            'crm_role_type' => $request->get('crm_role_type', ''),
            'status' => $request->get('status', ''),
            'two_fa' => $request->get('two_fa', ''),
            'per_page' => $perPage,
            'sort' => $sort,
            'dir' => $dir,
        ];

        // Hierarchy view: Goal Loan roles only (MD→CEO→RD→CH→BM→VRM/Sales + PMS/Accounts).
        $goalHierarchyRoles = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
        $hierarchyQuery = User::query()
            ->with(['orgUnit'])
            ->withCount('directReports')
            ->whereIn('crm_role_type', $goalHierarchyRoles)
            ->where('status', 1)
            ->orderBy('name');
        if (!$isSuperAdmin && (int) $currentUser->crm_role_type === 2) {
            $hierarchyQuery->where('assign_manager', $currentUser->id);
        }
        $hierarchyUsers = $hierarchyQuery->get();
        $hierarchyRoles = \App\Models\Role::query()
            ->whereIn('id', $goalHierarchyRoles)
            ->orderBy('id')
            ->pluck('name', 'id');

        $orgUnits = \App\Models\GoalOrgUnit::query()
            ->active()
            ->with([
                'children' => fn ($q) => $q->active()->orderBy('name'),
                'children.children' => fn ($q) => $q->active()->orderBy('name'),
                'children.children.children' => fn ($q) => $q->active()->orderBy('name'),
            ])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $usersByOrgUnit = $hierarchyUsers->groupBy('goal_org_unit_id');
        $topLevelUsers = $hierarchyUsers->filter(fn ($u) => empty($u->reports_to_user_id))->values();
        $managersCount = $hierarchyUsers->filter(fn ($u) => (int) $u->direct_reports_count > 0)->count();
        $maxDepth = $this->hierarchyMaxDepth($hierarchyUsers);
        $hierarchyStats = [
            'total' => $hierarchyUsers->count(),
            'top_level' => $topLevelUsers->count(),
            'managers' => $managersCount,
            'levels' => max(1, $maxDepth),
        ];

        return view('users.index', compact(
            'users',
            'roles',
            'allPermissions',
            'userCount',
            'maxUsers',
            'listStats',
            'filters',
            'hierarchyUsers',
            'hierarchyRoles',
            'orgUnits',
            'usersByOrgUnit',
            'topLevelUsers',
            'hierarchyStats',
            'company'
        ));
    }

    /** Depth of reporting tree (1 = only roots / no reports). */
    protected function hierarchyMaxDepth($users): int
    {
        if ($users->isEmpty()) {
            return 1;
        }
        $byManager = $users->groupBy(fn ($u) => (int) ($u->reports_to_user_id ?? 0));
        $walk = function ($parentId, $depth) use (&$walk, $byManager) {
            $children = $byManager->get($parentId, collect());
            if ($children->isEmpty()) {
                return $depth;
            }
            $max = $depth;
            foreach ($children as $child) {
                $max = max($max, $walk((int) $child->id, $depth + 1));
            }

            return $max;
        };

        // Virtual root at depth 0 so MD→CEO counts as 2 levels.
        return max(1, $walk(0, 0));
    }

    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        if ($redirect = $this->assertCanCreateUser()) {
            return $redirect;
        }

        $crmRoles = \App\Models\Role::orderBy('name')->get();
        $permissions = $this->assignablePermissions();
        $rolePermissionsMap = $this->buildRolePermissionsMap($crmRoles, $permissions);
        $selectedRoleId = old('crm_role_type', $crmRoles[0]->id ?? null);
        $defaultPermissions = isset($rolePermissionsMap[$selectedRoleId]) ? $rolePermissionsMap[$selectedRoleId] : [];
        $groupedPermissions = $permissions->groupBy(function ($item) {
            return $item->parentPermission ? $item->parentPermission->name : 'No Parent';
        });
        $managers = $this->reportingUsersQuery()->get();
        $company = Company::current();
        $userCount = User::countNonSuperAdmins();
        $maxUsers = $company->maxUsersLimit();
        $goalOrgUnits = \App\Models\GoalOrgUnit::query()->active()->orderBy('type')->orderBy('name')->get();
        $jobRoles = ['md','ceo','rd','cluster','bm','vrm','jsm','sm','ssm','pms','accounts'];

        return view('users.create', compact(
            'crmRoles',
            'permissions',
            'rolePermissionsMap',
            'defaultPermissions',
            'groupedPermissions',
            'managers',
            'userCount',
            'maxUsers',
            'goalOrgUnits',
            'jobRoles'
        ));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        if ($redirect = $this->assertCanCreateUser()) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'sales_target' => ['nullable', 'numeric', 'min:0'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            ],
            'mobile' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required_with:password|same:password',
            'crm_role_type' => $this->roleTypeRules(),
            'assign_manager' => [
                Rule::requiredIf($request->crm_role_type == 3),
                'nullable',
                'exists:users,id',
            ],
            'crm_page_right' => 'array',
            'status' => 'integer|in:0,1',
            'goal_org_unit_id' => 'nullable|exists:goal_org_units,id',
            'reports_to_user_id' => 'nullable|exists:users,id',
            'job_role' => 'nullable|string|max:30',
            'avatar' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,gif'],
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'email.regex' => 'Please enter a valid email address.',
            'mobile.regex' => 'Please enter a valid mobile / phone number.',
            'assign_manager' => 'Assign Manager is required when the role is Employee.',
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar size may not exceed 2MB.',
            'avatar.mimes' => 'The avatar must be a file of type: jpg, jpeg, png, gif.',
        ]);

        $plainPassword = $validated['password'];
        $pageRights = $this->filterAssignableGuards($validated['crm_page_right'] ?? []);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->mobile = $validated['mobile'] ?? null;
        $user->password = $plainPassword;
        $user->crm_role_type = $validated['crm_role_type'];
        // Goal tenants manage targets in Performance & Finance → Targets (not on user form)
        $user->sales_target = $this->usesGoalTargets()
            ? 0
            : ($validated['sales_target'] ?? 0);
        $user->crm_page_right = json_encode($pageRights);
        $user->assign_manager = $validated['assign_manager'] ?? null;
        $user->goal_org_unit_id = $validated['goal_org_unit_id'] ?? null;
        $user->reports_to_user_id = $validated['reports_to_user_id'] ?? ($validated['assign_manager'] ?? null);
        $user->job_role = $validated['job_role'] ?? $this->jobRoleFromCrmRole((int) $validated['crm_role_type']);
        $user->status = $validated['status'] ?? 1;
        $user->created_by = auth()->id();
        $user->save();

        if ($request->hasFile('avatar')) {
            $avatarPath = $this->storeUserAvatar($request->file('avatar'), $user);
            if ($avatarPath === null) {
                return back()->withErrors(['avatar' => 'The avatar must be a file of type: jpg, jpeg, png, gif.'])->withInput();
            }
            $user->avatar = $avatarPath;
            $user->save();
        }

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $mailSent = true;
        $mailError = null;
        try {
            Mail::to($user->email)->send(new UserWelcomeMail($user, $plainPassword, $verificationUrl));
        } catch (\Throwable $e) {
            $mailSent = false;
            $mailError = $e->getMessage();
            report($e);
        }

        if ($mailSent) {
            return redirect()->route('users.index')
                ->with('success', 'User created successfully! Verification email sent.');
        }

        return redirect()->route('users.index')->with(
            'success',
            'User created successfully, but the welcome email could not be sent. Please check mail settings.'
        )->with('warning', $mailError ? 'Mail error: SMTP authentication failed.' : null);
    }

    public function edit($id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $user = User::findOrFail($id);
        if (!$this->isSuperAdmin() && (int) $user->crm_role_type === 0) {
            abort(403, 'You cannot edit Super Admin.');
        }
        $this->ensureCanManageUser($user);

        $crmRoles = \App\Models\Role::orderBy('name')->get();
        $permissions = $this->assignablePermissions();
        $rolePermissionsMap = $this->buildRolePermissionsMap($crmRoles, $permissions);
        $selectedRoleId = old('crm_role_type', $user->crm_role_type);
        $defaultPermissions = isset($rolePermissionsMap[$selectedRoleId]) ? $rolePermissionsMap[$selectedRoleId] : [];
        $groupedPermissions = $permissions->groupBy(function ($item) {
            return $item->parentPermission ? $item->parentPermission->name : 'No Parent';
        });
        $managers = $this->reportingUsersQuery($user->id)->get();
        $goalOrgUnits = \App\Models\GoalOrgUnit::query()->active()->orderBy('type')->orderBy('name')->get();
        $jobRoles = ['md','ceo','rd','cluster','bm','vrm','jsm','sm','ssm','pms','accounts'];

        return view('users.edit', compact(
            'user',
            'crmRoles',
            'permissions',
            'rolePermissionsMap',
            'defaultPermissions',
            'groupedPermissions',
            'managers',
            'goalOrgUnits',
            'jobRoles'
        ));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $user = User::findOrFail($id);
        if (!$this->isSuperAdmin() && (int) $user->crm_role_type === 0) {
            abort(403, 'You cannot edit Super Admin.');
        }
        $this->ensureCanManageUser($user);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'sales_target' => ['nullable', 'numeric', 'min:0'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            ],
            'mobile' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'password' => 'nullable|string|min:8|confirmed',
            'crm_role_type' => $this->roleTypeRules($user),
            'crm_page_right' => 'array',
            'assign_manager' => [
                Rule::requiredIf($request->crm_role_type == 3),
                'nullable',
                'exists:users,id',
            ],
            'status' => 'integer|in:0,1',
            'goal_org_unit_id' => 'nullable|exists:goal_org_units,id',
            'reports_to_user_id' => 'nullable|exists:users,id',
            'job_role' => 'nullable|string|max:30',
            'avatar' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,gif'],
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'email.regex' => 'Please enter a valid email address.',
            'mobile.regex' => 'Please enter a valid mobile / phone number.',
            'assign_manager' => 'Assign Manager is required when the role is Employee.',
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar size may not exceed 2MB.',
            'avatar.mimes' => 'The avatar must be a file of type: jpg, jpeg, png, gif.',
        ]);

        $pageRights = $this->filterAssignableGuards($validated['crm_page_right'] ?? []);
        // Preserve existing rights Admin is not allowed to touch
        if (!$this->isSuperAdmin()) {
            $existing = json_decode($user->crm_page_right ?? '[]', true) ?: [];
            $allowed = Company::current()->adminAssignableGuards();
            $untouchable = array_values(array_diff($existing, $allowed));
            $pageRights = array_values(array_unique(array_merge($untouchable, $pageRights)));
        }

        $company = Company::current();
        if (!$company->isWebFormsEnabled()) {
            $pageRights = array_values(array_filter($pageRights, fn ($g) => !Permission::isWebFormsGuard($g)));
        }
        if (!$company->isSocialAdsEnabled()) {
            $pageRights = array_values(array_filter($pageRights, fn ($g) => !Permission::isSocialAdsGuard($g)));
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->mobile = $validated['mobile'] ?? null;
        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }
        // Goal: keep synced value from Targets module; classic CRM still edits sales_target here
        if (!$this->usesGoalTargets()) {
            $user->sales_target = $validated['sales_target'] ?? ($user->sales_target ?? 0);
        }
        $user->crm_role_type = $validated['crm_role_type'];
        $user->crm_page_right = json_encode($pageRights);
        if ($validated['crm_role_type'] == 3) {
            $user->assign_manager = $validated['assign_manager'] ?? $user->assign_manager;
        } else {
            $user->assign_manager = null;
        }
        $user->goal_org_unit_id = $validated['goal_org_unit_id'] ?? null;
        $user->reports_to_user_id = $validated['reports_to_user_id'] ?? ($validated['assign_manager'] ?? $user->reports_to_user_id);
        $user->job_role = $validated['job_role'] ?? $this->jobRoleFromCrmRole((int) $validated['crm_role_type']);
        $user->status = $validated['status'] ?? $user->status;
        $user->updated_by = auth()->id();

        if ($request->hasFile('avatar')) {
            $avatarPath = $this->storeUserAvatar($request->file('avatar'), $user, true);
            if ($avatarPath === null) {
                return back()->withErrors(['avatar' => 'The avatar must be a file of type: jpg, jpeg, png, gif.'])->withInput();
            }
            $user->avatar = $avatarPath;
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $user = User::findOrFail($id);
        if ((int) $user->crm_role_type === 0) {
            return redirect()->route('users.index')->with('error', 'Super Admin cannot be deleted.');
        }
        $this->ensureCanManageUser($user);
        if ((int) $user->id === (int) auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account here.');
        }
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    /**
     * Store avatar under public/assets/employee_profile_image/avatars/{user_id}.
     * Returns relative path stored on users.avatar, or null if file type is invalid.
     */
    protected function storeUserAvatar($file, User $user, bool $replaceExisting = false): ?string
    {
        $extension = SafeUpload::imageExtension($file);
        if ($extension === null) {
            return null;
        }

        if ($replaceExisting && $user->avatar) {
            $oldPath = public_path('assets/employee_profile_image/' . $user->avatar);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $folder = 'avatars/' . $user->id;
        $filename = Str::random(32) . '.' . $extension;
        $relativeDir = 'assets/employee_profile_image/' . $folder;
        $destination = public_path($relativeDir);
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);

        return $folder . '/' . $filename;
    }

    protected function jobRoleFromCrmRole(int $roleId): ?string
    {
        return [
            4 => 'md',
            5 => 'ceo',
            6 => 'rd',
            7 => 'cluster',
            8 => 'bm',
            9 => 'vrm',
            10 => 'jsm',
            11 => 'sm',
            12 => 'ssm',
            13 => 'pms',
            14 => 'accounts',
        ][$roleId] ?? null;
    }
}
