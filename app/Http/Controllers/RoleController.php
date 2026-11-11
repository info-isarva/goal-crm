<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_role_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $rolesList = Role::orderBy('name')->get();
        return view('roles.index', compact('rolesList'));
    }

    public function create()
    {
         if (!auth()->user()->hasCrmPermission('create_crm_role_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $company = \App\Models\Company::current();
        $permissions = Permission::with('parentPermission')->orderBy('name')->get();
        if (!$company->isWebFormsEnabled()) {
            $permissions = $permissions->reject(fn ($p) => Permission::isWebFormsGuard($p->guard_name));
        }
        if (!$company->isSocialAdsEnabled()) {
            $permissions = $permissions->reject(fn ($p) => Permission::isSocialAdsGuard($p->guard_name));
        }
        $groupedPermissions = $permissions->groupBy(function($item) {
            return $item->parentPermission ? $item->parentPermission->name : 'Other';
        });
        return view('roles.create', compact('groupedPermissions'));
    }

    public function store(Request $request)
    {
         if (!auth()->user()->hasCrmPermission('create_crm_role_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:roles,name',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
        ]);
        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
            if (!empty($validated['permissions'])) {
                foreach ($validated['permissions'] as $permId) {
                    DB::table('role_has_permission')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permId
                    ]);
                }
            }
            DB::commit();
            return redirect()->route('roles.index')->with('success', 'Role created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create role: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
         if (!auth()->user()->hasCrmPermission('edit_crm_role_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $role = Role::findOrFail($id);
        $company = \App\Models\Company::current();
        $permissions = Permission::with('parentPermission')->orderBy('name')->get();
        if (!$company->isWebFormsEnabled()) {
            $permissions = $permissions->reject(fn ($p) => Permission::isWebFormsGuard($p->guard_name));
        }
        if (!$company->isSocialAdsEnabled()) {
            $permissions = $permissions->reject(fn ($p) => Permission::isSocialAdsGuard($p->guard_name));
        }
        $groupedPermissions = $permissions->groupBy(function($item) {
            return $item->parentPermission ? $item->parentPermission->name : 'Other';
        });
        $rolePermissions = DB::table('role_has_permission')->where('role_id', $role->id)->pluck('permission_id')->toArray();
        return view('roles.edit', compact('role', 'groupedPermissions', 'rolePermissions'));
    }

    public function update(Request $request, $id)
    {
         if (!auth()->user()->hasCrmPermission('edit_crm_role_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $role = Role::findOrFail($id);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:roles,name,' . $role->id,
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
        ]);
        DB::beginTransaction();
        try {
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);
            DB::table('role_has_permission')->where('role_id', $role->id)->delete();
            if (!empty($validated['permissions'])) {
                foreach ($validated['permissions'] as $permId) {
                    DB::table('role_has_permission')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permId
                    ]);
                }
            }
            DB::commit();
            return redirect()->route('roles.index')->with('success', 'Role updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update role: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_role_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $role = Role::findOrFail($id);
        DB::table('role_has_permission')->where('role_id', $role->id)->delete();
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully!');
    }
}
