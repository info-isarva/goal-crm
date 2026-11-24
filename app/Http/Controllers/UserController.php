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
use App\Support\PhoneNumber;
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
     * Main users who may see/assign the full permission catalog (within company rules).
     * Super Admin, Admin, MD, CEO.
     */
    protected function isMainPermissionUser(?User $actor = null): bool
    {
        $actor = $actor ?: auth()->user();
        $role = (int) ($actor->crm_role_type ?? -1);

        return $this->isSuperAdmin($actor) || in_array($role, [1, 4, 5], true);
    }

    /**
     * Guard names the actor is allowed to grant on create/edit.
     * Main users: full (SA) or Admin whitelist. Others: only their own crm_page_right.
     */
    protected function actorAssignableGuards(?User $actor = null): array
    {
        $actor = $actor ?: auth()->user();
        $company = Company::current();
        $all = Permission::query()->orderBy('name')->pluck('guard_name')->map(fn ($g) => (string) $g)->all();

        // Goal Loan CRM: only Goal modules. Classic CRM: hide Goal modules.
        if ($this->usesGoalTargets($actor)) {
            $all = array_values(array_filter($all, fn ($g) => Permission::isGoalGuard($g)));
        } else {
            $all = array_values(array_filter($all, fn ($g) => !Permission::isGoalGuard($g)));
        }

        if (!$company->isWebFormsEnabled()) {
            $all = array_values(array_filter($all, fn ($g) => !Permission::isWebFormsGuard($g)));
        }
        if (!$company->isSocialAdsEnabled()) {
            $all = array_values(array_filter($all, fn ($g) => !Permission::isSocialAdsGuard($g)));
        }

        if ($this->isSuperAdmin($actor)) {
            return $all;
        }

        $role = (int) ($actor->crm_role_type ?? -1);
        $rights = json_decode($actor->crm_page_right ?? '[]', true);
        if (!is_array($rights)) {
            $rights = [];
        }

        // Admin: company Super-Admin whitelist
        if ($role === 1) {
            $allowed = $company->adminAssignableGuards();

            return array_values(array_intersect($all, $allowed));
        }

        // MD / CEO: full enabled catalog (or explicit rights if set without "all")
        if (in_array($role, [4, 5], true)) {
            if ($rights === [] || in_array('all', $rights, true)) {
                return $all;
            }

            return array_values(array_intersect($all, $rights));
        }

        // BM / Cluster / RD / sales / etc.: only permissions they themselves have
        if (in_array('all', $rights, true)) {
            return $all;
        }

        return array_values(array_intersect($all, $rights));
    }

    /**
     * Permissions the current user is allowed to assign on create/edit.
     * Main users (SA/Admin/MD/CEO): full set. Others: only their own permissions.
     */
    protected function assignablePermissions()
    {
        $allowedGuards = $this->actorAssignableGuards();

        return Permission::with('parentPermission')
            ->orderBy('name')
            ->get()
            ->filter(fn ($permission) => in_array((string) $permission->guard_name, $allowedGuards, true))
            ->values();
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
     * Numeric rank for Goal hierarchy (lower = higher authority).
     */
    protected function roleHierarchyRankMap(): array
    {
        return [
            1 => 10,  // Admin
            4 => 40,  // MD
            5 => 50,  // CEO
            6 => 60,  // RD
            7 => 70,  // Cluster Head
            8 => 80,  // BM
            // Level 6 peers under BM — many of each allowed (VRM team + sales team)
            9 => 90,  // VRM
            10 => 90, // JSM
            11 => 90, // SM
            12 => 90, // SSM
            13 => 130, // PMS
            14 => 140, // Accounts
        ];
    }

    /**
     * CRM roles ordered by org hierarchy (not alphabetically).
     */
    protected function crmRolesByHierarchy(?array $onlyIds = null)
    {
        $rank = $this->roleHierarchyRankMap();

        return \App\Models\Role::query()
            ->when($onlyIds !== null, fn ($q) => $q->whereIn('id', $onlyIds))
            ->get()
            ->sortBy(fn ($role) => $rank[(int) $role->id] ?? (1000 + (int) $role->id))
            ->values();
    }

    /**
     * Classic Employee (3) still uses the Assign Manager field.
     * Goal roles derive manager from Goal Org Unit (one BM per branch, etc.).
     */
    protected function roleRequiresManager(?int $roleId): bool
    {
        return (int) $roleId === 3;
    }

    /**
     * Resolve reports_to from org unit: branch staff → that branch's BM,
     * BM → Cluster Head, CH → RD, RD → CEO, CEO → MD, PMS/Accounts → CEO.
     */
    protected function resolveManagerFromOrgUnit(int $roleId, $orgUnitId, ?int $excludeUserId = null): ?int
    {
        if (!$orgUnitId) {
            return null;
        }

        $orgUnitId = (int) $orgUnitId;
        $unit = \App\Models\GoalOrgUnit::query()->find($orgUnitId);

        $findInUnits = function (int $managerRole, array $orgIds) use ($excludeUserId): ?int {
            if ($orgIds === []) {
                return null;
            }
            $id = User::query()
                ->where('crm_role_type', $managerRole)
                ->whereIn('goal_org_unit_id', $orgIds)
                ->where('status', 1)
                ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
                ->orderBy('id')
                ->value('id');

            return $id ? (int) $id : null;
        };

        $findAny = function (int $managerRole) use ($excludeUserId): ?int {
            $id = User::query()
                ->where('crm_role_type', $managerRole)
                ->where('status', 1)
                ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
                ->orderBy('id')
                ->value('id');

            return $id ? (int) $id : null;
        };

        // VRM / JSM / SM / SSM → BM of the same branch
        if (in_array($roleId, [9, 10, 11, 12], true)) {
            return $findInUnits(8, [$orgUnitId]);
        }

        // BM → Cluster Head on parent cluster (fallback RD)
        if ($roleId === 8) {
            $parentId = $unit?->parent_id ? (int) $unit->parent_id : null;
            if ($parentId) {
                return $findInUnits(7, [$parentId])
                    ?? $findInUnits(6, array_filter([(int) optional($unit?->parent)->parent_id]))
                    ?? $findAny(6);
            }

            return $findAny(7) ?? $findAny(6);
        }

        // Cluster Head → RD on parent region
        if ($roleId === 7) {
            $parentId = $unit?->parent_id ? (int) $unit->parent_id : null;
            if ($parentId) {
                return $findInUnits(6, [$parentId]) ?? $findAny(6);
            }

            return $findAny(6);
        }

        // RD → CEO
        if ($roleId === 6) {
            return $findAny(5) ?? $findAny(4);
        }

        // CEO → MD
        if ($roleId === 5) {
            return $findAny(4);
        }

        // PMS / Accounts → CEO (then MD)
        if (in_array($roleId, [13, 14], true)) {
            return $findAny(5) ?? $findAny(4);
        }

        return null;
    }

    /**
     * Roles the logged-in user may assign when creating/editing users.
     * BM → VRM/JSM/SM/SSM only; Cluster → BM + sales; etc.
     */
    protected function creatableRoleIdsFor(?User $actor = null): array
    {
        $actor = $actor ?: auth()->user();
        $role = (int) ($actor->crm_role_type ?? -1);

        if ($this->isSuperAdmin($actor) || in_array($role, [1, 4, 5], true)) {
            // Super Admin / Admin / MD / CEO: full Goal ladder (+ Admin for SA/Admin)
            $ids = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
            if (in_array($role, [0, 1], true)) {
                $ids = array_merge([1], $ids);
            }

            return $ids;
        }

        return match ($role) {
            6 => [7, 8, 9, 10, 11, 12],          // RD → Cluster + BM + sales
            7 => [8, 9, 10, 11, 12],             // Cluster → BM + sales
            8 => [9, 10, 11, 12],                // BM → many VRM + many JSM/SM/SSM
            9 => [10, 11, 12],                   // VRM may also lead sales reports
            10 => [11, 12],                      // JSM may lead SM/SSM reports
            11 => [12],                          // SM may lead SSM reports
            default => [],
        };
    }

    /**
     * Org unit types allowed for a target CRM role.
     * BM / sales → branches; Cluster Head → clusters; RD/MD/CEO → regions.
     */
    protected function orgUnitTypesForRole(int $roleId): array
    {
        return match ($roleId) {
            4, 5, 6 => [\App\Models\GoalOrgUnit::TYPE_REGION],
            7 => [\App\Models\GoalOrgUnit::TYPE_CLUSTER],
            8, 9, 10, 11, 12 => [\App\Models\GoalOrgUnit::TYPE_BRANCH],
            13, 14 => [
                \App\Models\GoalOrgUnit::TYPE_REGION,
                \App\Models\GoalOrgUnit::TYPE_CLUSTER,
                \App\Models\GoalOrgUnit::TYPE_BRANCH,
            ],
            default => [
                \App\Models\GoalOrgUnit::TYPE_REGION,
                \App\Models\GoalOrgUnit::TYPE_CLUSTER,
                \App\Models\GoalOrgUnit::TYPE_BRANCH,
            ],
        };
    }

    /**
     * Org units the actor may assign, optionally filtered by target role type.
     */
    protected function assignableGoalOrgUnits(?User $actor = null, ?int $forRoleId = null)
    {
        $actor = $actor ?: auth()->user();
        $query = \App\Models\GoalOrgUnit::query()->active()->orderBy('type')->orderBy('name');

        if (!$this->isSuperAdmin($actor) && !in_array((int) ($actor->crm_role_type ?? -1), [1, 4, 5], true)) {
            $scopeIds = $actor->goalScopedOrgUnitIds();
            if ($scopeIds === []) {
                return collect();
            }
            $query->whereIn('id', $scopeIds);
        }

        if ($forRoleId !== null) {
            $types = $this->orgUnitTypesForRole($forRoleId);
            $query->whereIn('type', $types);
        }

        return $query->get();
    }

    /**
     * Role id → allowed org unit types (for frontend filtering).
     */
    protected function orgUnitTypesByRoleMap(): array
    {
        $map = [];
        foreach (array_keys($this->roleHierarchyRankMap()) as $roleId) {
            $map[(string) $roleId] = $this->orgUnitTypesForRole((int) $roleId);
        }

        return $map;
    }

    protected function assertAssignableRoleAndOrg(int $roleId, $orgUnitId): void
    {
        $allowedRoles = $this->creatableRoleIdsFor();
        if (!in_array($roleId, $allowedRoles, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'crm_role_type' => 'You cannot assign that CRM Role.',
            ]);
        }

        if ($orgUnitId === null || $orgUnitId === '') {
            return;
        }

        $orgUnitId = (int) $orgUnitId;
        $allowedUnits = $this->assignableGoalOrgUnits(null, $roleId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (!in_array($orgUnitId, $allowedUnits, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'goal_org_unit_id' => 'Selected Goal Org Unit is not valid for this role / your scope.',
            ]);
        }
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

        return array_values(array_intersect($guards, $this->actorAssignableGuards()));
    }

    /**
     * Validation rules for crm_role_type: must be an existing role id. Super Admin (0) is
     * never assignable from the form (only kept when a Super Admin edits a Super Admin),
     * and only Super Admin / Admin may grant the Admin role.
     */
    protected function roleTypeRules(?User $target = null): array
    {
        $allowed = array_map('strval', $this->creatableRoleIdsFor());

        // Keep current role selectable when editing (even if not creatable for peers).
        if ($target && (int) $target->crm_role_type > 0) {
            $allowed[] = (string) $target->crm_role_type;
            $allowed = array_values(array_unique($allowed));
        }

        if ($target && (int) $target->crm_role_type === 0 && $this->isSuperAdmin()) {
            $allowed[] = '0';
        }

        return ['required', Rule::in($allowed)];
    }

    /**
     * Super Admin / Admin / MD / CEO: company-wide (except SA for non-SA).
     * Others: self + reporting tree + lower-ranked staff in their org scope.
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
        if (in_array($role, [1, 4, 5], true)) {
            return;
        }
        if (! in_array((int) $target->id, $this->manageableUserIds($current), true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * User IDs the actor may list/manage (self + people under them).
     */
    protected function manageableUserIds(?User $actor = null): array
    {
        $actor = $actor ?: auth()->user();
        $actorId = (int) $actor->id;
        $ids = [$actorId];
        $seen = [$actorId => true];
        $frontier = [$actorId];

        // Walk reporting tree (reports_to + legacy assign_manager).
        while ($frontier !== []) {
            $children = User::query()
                ->where(function ($q) use ($frontier) {
                    $q->whereIn('reports_to_user_id', $frontier)
                        ->orWhereIn('assign_manager', $frontier);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $frontier = [];
            foreach ($children as $childId) {
                if (isset($seen[$childId])) {
                    continue;
                }
                $seen[$childId] = true;
                $ids[] = $childId;
                $frontier[] = $childId;
            }
        }

        // Org unit scope: include lower-ranked staff in the actor's unit subtree.
        $orgIds = $actor->goalScopedOrgUnitIds();
        if ($orgIds !== []) {
            $actorRank = $this->roleHierarchyRankMap()[(int) ($actor->crm_role_type ?? -1)] ?? 999;
            $orgUsers = User::query()
                ->whereIn('goal_org_unit_id', $orgIds)
                ->where('crm_role_type', '!=', 0)
                ->get(['id', 'crm_role_type']);

            foreach ($orgUsers as $orgUser) {
                $rank = $this->roleHierarchyRankMap()[(int) $orgUser->crm_role_type] ?? 999;
                if ($rank > $actorRank) {
                    $ids[] = (int) $orgUser->id;
                }
            }
        }

        return array_values(array_unique($ids));
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
        [$query] = $this->applyUserListFilters($this->manageableUsersQuery()->with(['orgUnit']), $request);
        $rows = $query->get();
        $roles = $this->crmRolesByHierarchy()->pluck('name', 'id');
        $includeSalesTarget = !$this->usesGoalTargets();

        $header = ['#', 'Name', 'Email', 'Mobile', 'Branch', 'CRM Role', 'Status', '2FA'];
        if ($includeSalesTarget) {
            $header[] = 'Sales Target';
        }
        $lines = [$header];
        foreach ($rows as $i => $u) {
            $role = ((int) $u->crm_role_type === 0)
                ? 'Super Admin'
                : ($roles[$u->crm_role_type] ?? 'Unknown');
            $line = [
                $i + 1,
                $u->name,
                $u->email,
                $u->mobile ?? '',
                optional($u->orgUnit)->name ?? '',
                $role,
                (int) $u->status === 1 ? 'Active' : 'Inactive',
                $u->{"2fa_enabled"} ? 'Enabled' : 'Disabled',
            ];
            if ($includeSalesTarget) {
                $line[] = number_format((float) ($u->sales_target ?? 0), 2, '.', '');
            }
            $lines[] = $line;
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
     * Filters (search / role / status / 2FA) are applied on top of this scope.
     */
    protected function manageableUsersQuery(?User $currentUser = null)
    {
        $currentUser = $currentUser ?: auth()->user();
        $role = (int) ($currentUser->crm_role_type ?? -1);
        $query = User::query();

        // Company-wide viewers
        if ($this->isSuperAdmin($currentUser)) {
            return $query;
        }
        if (in_array($role, [1, 4, 5], true)) {
            return $query->where('crm_role_type', '!=', 0);
        }

        // Everyone else: only self + people under them (reporting / org scope)
        $ids = $this->manageableUserIds($currentUser);
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', $ids);
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

        if ($request->filled('goal_org_unit_id') && $request->goal_org_unit_id !== '') {
            $query->where('goal_org_unit_id', (int) $request->goal_org_unit_id);
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

        $roles = $this->crmRolesByHierarchy()->pluck('name', 'id');
        $allPermissions = \App\Models\Permission::orderBy('name')->pluck('name')->toArray();
        $company = Company::current();
        $userCount = User::countNonSuperAdmins();
        $maxUsers = $company->maxUsersLimit();

        $filters = [
            'q' => $request->get('q', ''),
            'crm_role_type' => $request->get('crm_role_type', ''),
            'goal_org_unit_id' => $request->get('goal_org_unit_id', ''),
            'status' => $request->get('status', ''),
            'two_fa' => $request->get('two_fa', ''),
            'per_page' => $perPage,
            'sort' => $sort,
            'dir' => $dir,
        ];

        $branchOptions = $this->assignableGoalOrgUnits($currentUser)
            ->where('type', \App\Models\GoalOrgUnit::TYPE_BRANCH)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Hierarchy view: Goal Loan roles only (MD→CEO→RD→CH→BM→VRM/Sales + PMS/Accounts).
        $goalHierarchyRoles = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
        $hierarchyQuery = User::query()
            ->with(['orgUnit'])
            ->withCount('directReports')
            ->whereIn('crm_role_type', $goalHierarchyRoles)
            ->where('status', 1)
            ->orderBy('name');
        // Same people-scope as List View (except company-wide SA/Admin/MD/CEO).
        if (! $isSuperAdmin && ! in_array((int) ($currentUser->crm_role_type ?? -1), [1, 4, 5], true)) {
            $hierarchyQuery->whereIn('id', $this->manageableUserIds($currentUser));
        } elseif (! $isSuperAdmin) {
            $hierarchyQuery->where('crm_role_type', '!=', 0);
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
            'branchOptions',
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

        $actor = auth()->user();
        $creatableRoleIds = $this->creatableRoleIdsFor($actor);
        $crmRoles = $this->crmRolesByHierarchy($creatableRoleIds);
        $permissions = $this->assignablePermissions();
        $rolePermissionsMap = $this->buildRolePermissionsMap($crmRoles, $permissions);
        $selectedRoleId = old('crm_role_type');
        $defaultPermissions = ($selectedRoleId !== null && isset($rolePermissionsMap[$selectedRoleId]))
            ? $rolePermissionsMap[$selectedRoleId]
            : [];
        $groupedPermissions = $permissions->groupBy(function ($item) {
            return $item->parentPermission ? $item->parentPermission->name : 'No Parent';
        });
        $managers = $this->reportingUsersQuery()->with(['orgUnit'])->get();
        if (!$this->isSuperAdmin($actor) && !in_array((int) ($actor->crm_role_type ?? -1), [1, 4, 5], true)) {
            $scopeIds = $actor->goalScopedOrgUnitIds();
            $managers = $managers->filter(function ($m) use ($scopeIds, $actor) {
                return (int) $m->id === (int) $actor->id
                    || ($m->goal_org_unit_id && in_array((int) $m->goal_org_unit_id, $scopeIds, true));
            })->values();
            if ($managers->where('id', $actor->id)->isEmpty()) {
                $managers = $managers->prepend($actor);
            }
        }
        $managerRoleLabels = $this->crmRolesByHierarchy()->pluck('name', 'id');
        $company = Company::current();
        $userCount = User::countNonSuperAdmins();
        $maxUsers = $company->maxUsersLimit();
        $goalOrgUnits = $this->assignableGoalOrgUnits($actor);
        $orgUnitTypesByRole = $this->orgUnitTypesByRoleMap();
        $defaultOrgUnitId = null;
        $orgUnitLocked = false;
        if (!$this->isSuperAdmin($actor) && in_array((int) ($actor->crm_role_type ?? -1), [8, 9, 10, 11, 12], true) && $actor->goal_org_unit_id) {
            $defaultOrgUnitId = (int) $actor->goal_org_unit_id;
            $orgUnitLocked = $goalOrgUnits->count() <= 1;
        }

        return view('users.create', compact(
            'crmRoles',
            'permissions',
            'rolePermissionsMap',
            'defaultPermissions',
            'groupedPermissions',
            'managers',
            'managerRoleLabels',
            'userCount',
            'maxUsers',
            'goalOrgUnits',
            'orgUnitTypesByRole',
            'defaultOrgUnitId',
            'orgUnitLocked'
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
            'mobile' => PhoneNumber::mobileRules(true),
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required_with:password|same:password',
            'crm_role_type' => $this->roleTypeRules(),
            'assign_manager' => [
                Rule::requiredIf(fn () => $this->roleRequiresManager((int) $request->crm_role_type)),
                'nullable',
                'exists:users,id',
            ],
            'crm_page_right' => 'array',
            'status' => 'integer|in:0,1',
            'goal_org_unit_id' => 'required|exists:goal_org_units,id',
            'reports_to_user_id' => 'nullable|exists:users,id',
            'job_role' => 'nullable|string|max:30',
            'avatar' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,gif'],
        ], array_merge([
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'email.regex' => 'Please enter a valid email address.',
            'assign_manager.required' => 'Assign Manager is required when the role is Employee.',
            'assign_manager' => 'Assign Manager is required when the role is Employee.',
            'goal_org_unit_id.required' => 'Goal Org Unit is required.',
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar size may not exceed 2MB.',
            'avatar.mimes' => 'The avatar must be a file of type: jpg, jpeg, png, gif.',
        ], PhoneNumber::messages('mobile')));

        $this->assertAssignableRoleAndOrg(
            (int) $validated['crm_role_type'],
            $validated['goal_org_unit_id'] ?? null
        );

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
        $user->goal_org_unit_id = $validated['goal_org_unit_id'] ?? null;
        // Goal roles: manager comes from Goal Org Unit (branch → BM). Classic Employee uses Assign Manager.
        $actor = auth()->user();
        $actorRole = (int) ($actor->crm_role_type ?? -1);
        $roleId = (int) $validated['crm_role_type'];
        if ($this->roleRequiresManager($roleId)) {
            $managerId = !empty($validated['assign_manager']) ? (int) $validated['assign_manager'] : null;
        } else {
            $managerId = $this->resolveManagerFromOrgUnit($roleId, $validated['goal_org_unit_id'] ?? null);
            if (!$managerId && !$this->isSuperAdmin($actor) && !in_array($actorRole, [1, 4], true)) {
                $managerId = (int) $actor->id;
            }
        }
        $user->assign_manager = $this->roleRequiresManager($roleId) ? $managerId : null;
        $user->reports_to_user_id = $managerId;
        // Job role is derived from CRM Role (single role field in UI).
        $user->job_role = $this->jobRoleFromCrmRole((int) $validated['crm_role_type']);
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

        $actor = auth()->user();
        $creatableRoleIds = $this->creatableRoleIdsFor($actor);
        // Ensure the user's current role remains listed while editing.
        if ((int) $user->crm_role_type > 0) {
            $creatableRoleIds[] = (int) $user->crm_role_type;
            $creatableRoleIds = array_values(array_unique($creatableRoleIds));
        }
        $crmRoles = $this->crmRolesByHierarchy($creatableRoleIds);
        $permissions = $this->assignablePermissions();
        $rolePermissionsMap = $this->buildRolePermissionsMap($crmRoles, $permissions);
        $selectedRoleId = old('crm_role_type', $user->crm_role_type);
        $defaultPermissions = isset($rolePermissionsMap[$selectedRoleId]) ? $rolePermissionsMap[$selectedRoleId] : [];
        $groupedPermissions = $permissions->groupBy(function ($item) {
            return $item->parentPermission ? $item->parentPermission->name : 'No Parent';
        });
        $managers = $this->reportingUsersQuery($user->id)->with(['orgUnit'])->get();
        if (!$this->isSuperAdmin($actor) && !in_array((int) ($actor->crm_role_type ?? -1), [1, 4, 5], true)) {
            $scopeIds = $actor->goalScopedOrgUnitIds();
            $managers = $managers->filter(function ($m) use ($scopeIds, $actor) {
                return (int) $m->id === (int) $actor->id
                    || ($m->goal_org_unit_id && in_array((int) $m->goal_org_unit_id, $scopeIds, true));
            })->values();
        }
        $managerRoleLabels = $this->crmRolesByHierarchy()->pluck('name', 'id');
        $goalOrgUnits = $this->assignableGoalOrgUnits($actor);
        $orgUnitTypesByRole = $this->orgUnitTypesByRoleMap();
        $defaultOrgUnitId = (int) ($user->goal_org_unit_id ?: 0) ?: null;
        $orgUnitLocked = false;
        if (!$this->isSuperAdmin($actor) && in_array((int) ($actor->crm_role_type ?? -1), [8, 9, 10, 11, 12], true) && $actor->goal_org_unit_id) {
            $orgUnitLocked = $goalOrgUnits->count() <= 1;
            $defaultOrgUnitId = $defaultOrgUnitId ?: (int) $actor->goal_org_unit_id;
        }

        return view('users.edit', compact(
            'user',
            'crmRoles',
            'permissions',
            'rolePermissionsMap',
            'defaultPermissions',
            'groupedPermissions',
            'managers',
            'managerRoleLabels',
            'goalOrgUnits',
            'orgUnitTypesByRole',
            'defaultOrgUnitId',
            'orgUnitLocked'
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
            'mobile' => PhoneNumber::mobileRules(true),
            'password' => 'nullable|string|min:8|confirmed',
            'crm_role_type' => $this->roleTypeRules($user),
            'crm_page_right' => 'array',
            'assign_manager' => [
                Rule::requiredIf(fn () => $this->roleRequiresManager((int) $request->crm_role_type)),
                'nullable',
                'exists:users,id',
            ],
            'status' => 'integer|in:0,1',
            'goal_org_unit_id' => 'required|exists:goal_org_units,id',
            'reports_to_user_id' => 'nullable|exists:users,id',
            'job_role' => 'nullable|string|max:30',
            'avatar' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,gif'],
        ], array_merge([
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'email.regex' => 'Please enter a valid email address.',
            'assign_manager.required' => 'Assign Manager is required when the role is Employee.',
            'assign_manager' => 'Assign Manager is required when the role is Employee.',
            'goal_org_unit_id.required' => 'Goal Org Unit is required.',
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar size may not exceed 2MB.',
            'avatar.mimes' => 'The avatar must be a file of type: jpg, jpeg, png, gif.',
        ], PhoneNumber::messages('mobile')));

        $this->assertAssignableRoleAndOrg(
            (int) $validated['crm_role_type'],
            $validated['goal_org_unit_id'] ?? null
        );

        $pageRights = $this->filterAssignableGuards($validated['crm_page_right'] ?? []);
        // Preserve target rights the actor cannot assign (outside their permission set)
        if (!$this->isSuperAdmin()) {
            $existing = json_decode($user->crm_page_right ?? '[]', true) ?: [];
            if (!is_array($existing)) {
                $existing = [];
            }
            $actorCanAssign = $this->actorAssignableGuards();
            $untouchable = array_values(array_diff($existing, $actorCanAssign));
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
        $user->goal_org_unit_id = $validated['goal_org_unit_id'] ?? null;
        $roleId = (int) $validated['crm_role_type'];
        if ($this->roleRequiresManager($roleId)) {
            $managerId = !empty($validated['assign_manager']) ? (int) $validated['assign_manager'] : null;
            $user->assign_manager = $managerId;
            if ($managerId) {
                $user->reports_to_user_id = $managerId;
            }
        } else {
            // Goal ladder: reports_to from Goal Org Unit (e.g. branch → that branch's BM)
            $managerId = $this->resolveManagerFromOrgUnit($roleId, $validated['goal_org_unit_id'] ?? null, (int) $user->id);
            $user->assign_manager = null;
            $user->reports_to_user_id = $managerId;
        }
        // Job role is derived from CRM Role (single role field in UI).
        $user->job_role = $this->jobRoleFromCrmRole((int) $validated['crm_role_type']);
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
