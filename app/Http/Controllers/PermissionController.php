<?php
namespace App\Http\Controllers;

use Illuminate\Console\Concerns\HasParameters;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\User;

class PermissionController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_permission_guard')) {
            abort(403, 'Unauthorized action.');
        }
    $permissions = Permission::with('parentPermission')->orderBy('id')->get();
    $parentPermissions = \App\Models\ParentPermission::orderBy('name')->get();
    return view('permissions.index', compact('permissions', 'parentPermissions'));
    }

    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_permission_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $users = User::orderBy('name')->get();
        return view('permissions.create', compact('users'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_permission_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:permissions,name',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'guard_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'crm_permission' => 'nullable|boolean',
            'created_by' => 'nullable|integer|exists:users,id',
            'parent_id' => 'required|integer',
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
        ]);
        $validated['crm_permission'] = $request->has('crm_permission') ? 1 : 0;
        $validated['created_at'] = now();
        Permission::create($validated);
        return redirect()->route('permissions.index')->with('success', 'Permission created successfully!');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_permission_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $permission = Permission::findOrFail($id);
        $users = User::orderBy('name')->get();
        return view('permissions.edit', compact('permission', 'users'));
    }

    public function update(Request $request, $id)
    {
         if (!auth()->user()->hasCrmPermission('edit_crm_permission_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $permission = Permission::findOrFail($id);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:permissions,name,' . $permission->id,
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'guard_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'crm_permission' => 'nullable|boolean',
            'updated_by' => 'nullable|integer|exists:users,id',
            'parent_id' => 'required|integer|exists:parent_permissions,id',
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
        ]);
        $validated['crm_permission'] = $request->has('crm_permission') ? 1 : 0;
        $validated['updated_at'] = now();
        $permission->update($validated);
        return redirect()->route('permissions.index')->with('success', 'Permission updated successfully!');
    }

    public function destroy($id)
    {
         if (!auth()->user()->hasCrmPermission('delete_crm_permission_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $permission = Permission::findOrFail($id);
        $permission->deleted_by = auth()->id();
        $permission->deleted_at = now();
        $permission->save();
        $permission->delete();
        return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully!');
    }
}
