@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Create User</h4>
                    <a href="{{ route('users.index') }}" class="btn btn-light">&laquo; Back to users</a>
                </div>
                <div class="card-body">
                    @if(!empty($maxUsers))
                        <div class="alert alert-light border py-2 small mb-3">
                            User seats: <strong>{{ $userCount ?? 0 }}</strong> / <strong>{{ $maxUsers }}</strong>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name   <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" >
                                    @error('name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email   <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" >
                                    @error('email')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="crm_role_type" class="form-label">CRM Role  <span class="text-danger">*</span></label>
                                    <select class="form-select" id="crm_role_type" name="crm_role_type" >
                                        <option value="">Select CRM Role</option>
                                        @foreach($crmRoles as $role)
                                            <option value="{{ $role->id }}" {{ old('crm_role_type') == $role->id ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                                        @endforeach
                                    </select>
                                    @error('crm_role_type')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                 <div class="mb-3">
                                    <label for="password" class="form-label">Password   <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" >
                                    @error('password')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm Password   <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" >
                                    @error('password_confirmation')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                               <div class="mb-3">
                                    <label for="sales_target" class="form-label">Sales Target (<?php echo config('app.currency_symbol', '₹'); ?>)</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('sales_target') is-invalid @enderror" id="sales_target" name="sales_target" value="{{ old('sales_target', 0) }}">
                                    @error('sales_target')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                            <div class="col-12 mb-3" id="assign-manager-container" style="display: none;">
                                <label for="assign_manager" class="form-label">Assign Manager</label>
                                <select class="form-select" id="assign_manager" name="assign_manager">
                                    <option value="">Select Manager</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                    @endforeach
                                </select>
                                @error('assign_manager')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0 fw-semibold">Permissions</label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="selectAllPerms">Select All</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="clearAllPerms">Clear All</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" id="applyRoleDefaults">Role Defaults</button>
                                    </div>
                                </div>
                                <div id="permissions-list" data-role-permissions='@json($rolePermissionsMap)'>
                                    <div id="permission-message" class="text-muted mb-2">Select a CRM Role to view permissions.</div>
                                    <div class="row g-3 permission-grid">
                                    @foreach($groupedPermissions as $parentName => $group)
                                        <div class="col-12 col-md-4">
                                            <div class="permission-card h-100 p-3 border-1 rounded-3 bg-white">
                                                <div class="fw-bold text-primary mb-2">{{ $parentName }}</div>
                                                <div class="row">
                                                    @foreach($group as $permission)
                                                        <div class="col-12 col-sm-6">
                                                            <div class="form-check" data-guard-name="{{ $permission->guard_name }}">
                                                                <input class="form-check-input" type="checkbox" name="crm_page_right[]" value="{{ $permission->guard_name }}" id="perm_{{ $permission->id }}" data-guard-name="{{ $permission->guard_name }}" {{ in_array($permission->guard_name, old('crm_page_right', [])) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-custom mt-3">Create User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Show/hide related permissions when CRM role is selected
    const crmRoleSelect = document.getElementById('crm_role_type');
    const permissionsList = document.getElementById('permissions-list');
    const permissionMessage = document.getElementById('permission-message');
    var rolePermissions = {};
    try {
        rolePermissions = JSON.parse(permissionsList.getAttribute('data-role-permissions') || '{}');
    } catch (e) {
        rolePermissions = {};
    }

    function updatePermissionsChecked() {
        const selectedRoleId = crmRoleSelect.value;
        const perms = (rolePermissions[selectedRoleId] || []).map(p => String(p).trim());
        permissionsList.querySelectorAll('.form-check input[type="checkbox"]').forEach(checkbox => {
            const guardName = String(checkbox.getAttribute('data-guard-name') || '').trim();
            checkbox.checked = perms.includes(guardName);
        });
    }
    crmRoleSelect.addEventListener('change', updatePermissionsChecked);
    // On page load, set checked state if role already selected or default
    if (crmRoleSelect.value) {
        updatePermissionsChecked();
    }

    const selectAllBtn = document.getElementById('selectAllPerms');
    const clearAllBtn = document.getElementById('clearAllPerms');
    const roleDefaultsBtn = document.getElementById('applyRoleDefaults');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            permissionsList.querySelectorAll('.form-check input[type="checkbox"]').forEach(cb => cb.checked = true);
        });
    }
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function () {
            permissionsList.querySelectorAll('.form-check input[type="checkbox"]').forEach(cb => cb.checked = false);
        });
    }
    if (roleDefaultsBtn) {
        roleDefaultsBtn.addEventListener('click', updatePermissionsChecked);
    }

    document.getElementById('crm_role_type').addEventListener('change', function() {
        const assignManagerContainer = document.getElementById('assign-manager-container');
        const selectedRole = parseInt(this.value, 10);
        if (selectedRole === 3) {
            assignManagerContainer.style.display = 'block';
        } else {
            assignManagerContainer.style.display = 'none';
        }
    });

    const assignManagerContainer = document.getElementById('assign-manager-container');
    const initialRole = parseInt(crmRoleSelect.value, 10);
    if (initialRole === 3) {
        assignManagerContainer.style.display = 'block';
    }
</script>
<style>
    .border-1 {
        border: 1px solid #cdcbcb;
        padding: 10px;

    }
    .permission-card {
        box-shadow: 0 6px 18px rgba(31,38,135,0.04);
        min-height: 120px;
    }
    /* Below 1400px, show two columns instead of three */
    @media (max-width: 1460px) {
        .permission-grid > .col-md-4 { flex: 0 0 50%; max-width: 50%; }
    }
    /* Below 850px, show single column */
    @media (max-width: 850px) {
        .permission-grid > .col-md-4 { flex: 0 0 100%; max-width: 100%; }
    }


    </style>
@endsection
