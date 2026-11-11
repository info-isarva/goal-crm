<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Permission;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\UserWelcomeMail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
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

    public function index()
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_user_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $currentUser = auth()->user();
        $isSuperAdmin = $this->isSuperAdmin($currentUser);
        if ($isSuperAdmin) {
            $users = User::orderBy('name')->paginate(10);
        } elseif ((int) $currentUser->crm_role_type === 1) {
            $users = User::whereIn('crm_role_type', [1, 2, 3])
                ->where('status', 1)
                ->orderBy('name')
                ->paginate(10);
        } elseif ((int) $currentUser->crm_role_type === 2) {
            $users = User::where('assign_manager', $currentUser->id)
                ->where('status', 1)
                ->orderBy('name')
                ->paginate(10);
        } else {
            $users = User::where('status', 1)->orderBy('name')->paginate(10);
        }
        $roles = \App\Models\Role::pluck('name', 'id');
        $allPermissions = \App\Models\Permission::orderBy('name')->pluck('name')->toArray();
        $company = Company::current();
        $userCount = User::countNonSuperAdmins();
        $maxUsers = $company->maxUsersLimit();

        return view('users.index', compact('users', 'roles', 'allPermissions', 'userCount', 'maxUsers'));
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
        $rolePermissionsMap = [];
        $allowedGuards = $permissions->pluck('guard_name')->all();
        foreach ($crmRoles as $role) {
            $rolePerms = $role->permissions()->pluck('guard_name')->toArray();
            $rolePermissionsMap[$role->id] = array_values(array_intersect($rolePerms, $allowedGuards));
        }
        $selectedRoleId = old('crm_role_type', $crmRoles[0]->id ?? null);
        $defaultPermissions = isset($rolePermissionsMap[$selectedRoleId]) ? $rolePermissionsMap[$selectedRoleId] : [];
        $groupedPermissions = $permissions->groupBy(function ($item) {
            return $item->parentPermission ? $item->parentPermission->name : 'No Parent';
        });
        $managers = User::where('crm_role_type', '2')->get();
        $company = Company::current();
        $userCount = User::countNonSuperAdmins();
        $maxUsers = $company->maxUsersLimit();

        return view('users.create', compact(
            'crmRoles',
            'permissions',
            'rolePermissionsMap',
            'defaultPermissions',
            'groupedPermissions',
            'managers',
            'userCount',
            'maxUsers'
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
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'email.regex' => 'Please enter a valid email address.',
            'assign_manager' => 'Assign Manager is required when the role is Employee.',
        ]);

        $plainPassword = $validated['password'];
        $pageRights = $this->filterAssignableGuards($validated['crm_page_right'] ?? []);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = $plainPassword;
        $user->crm_role_type = $validated['crm_role_type'];
        $user->sales_target = $validated['sales_target'] ?? 0;
        $user->crm_page_right = json_encode($pageRights);
        $user->assign_manager = $validated['assign_manager'] ?? null;
        $user->status = $validated['status'] ?? 1;
        $user->created_by = auth()->id();
        $user->save();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        Mail::to($user->email)->send(new UserWelcomeMail($user, $plainPassword, $verificationUrl));

        return redirect()->route('users.index')->with('success', 'User created successfully! Verification email sent.');
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
        $rolePermissionsMap = [];
        $allowedGuards = $permissions->pluck('guard_name')->all();
        foreach ($crmRoles as $role) {
            $rolePerms = $role->permissions()->pluck('guard_name')->toArray();
            $rolePermissionsMap[$role->id] = array_values(array_intersect($rolePerms, $allowedGuards));
        }
        $selectedRoleId = old('crm_role_type', $user->crm_role_type);
        $defaultPermissions = isset($rolePermissionsMap[$selectedRoleId]) ? $rolePermissionsMap[$selectedRoleId] : [];
        $groupedPermissions = $permissions->groupBy(function ($item) {
            return $item->parentPermission ? $item->parentPermission->name : 'No Parent';
        });
        $managers = User::where('crm_role_type', 2)->get();

        return view('users.edit', compact(
            'user',
            'crmRoles',
            'permissions',
            'rolePermissionsMap',
            'defaultPermissions',
            'groupedPermissions',
            'managers'
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
            'password' => 'nullable|string|min:8|confirmed',
            'crm_role_type' => $this->roleTypeRules($user),
            'crm_page_right' => 'array',
            'assign_manager' => [
                Rule::requiredIf($request->crm_role_type == 3),
                'nullable',
                'exists:users,id',
            ],
            'status' => 'integer|in:0,1',
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'email.regex' => 'Please enter a valid email address.',
            'assign_manager' => 'Assign Manager is required when the role is Employee.',
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
        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }
        $user->sales_target = $validated['sales_target'] ?? ($user->sales_target ?? 0);
        $user->crm_role_type = $validated['crm_role_type'];
        $user->crm_page_right = json_encode($pageRights);
        if ($validated['crm_role_type'] == 3) {
            $user->assign_manager = $validated['assign_manager'] ?? $user->assign_manager;
        } else {
            $user->assign_manager = null;
        }
        $user->status = $validated['status'] ?? $user->status;
        $user->updated_by = auth()->id();
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
}
