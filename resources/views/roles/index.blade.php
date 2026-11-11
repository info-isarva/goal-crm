@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body, .card, .table, .btn, .form-control, .form-select {
    font-family: 'Inter', Arial, sans-serif !important;
}
.card {
    border-radius: 1.5rem !important;
    box-shadow: 0 2px 16px 0 rgba(60,72,88,0.07);
    border: none !important;
    background: #fff;
    transition: box-shadow 0.2s, transform 0.15s;
}
.card-header {
    border-radius: 1.5rem 1.5rem 0 0 !important;
    background: #f9fafb;
    border-bottom: 1px solid #f3f4f6;
}
.card-body {
    background: #fff;
    border-radius: 0 0 1.5rem 1.5rem !important;
}
.table {
    border-radius: 1rem;
    overflow: hidden;
    background: #f9fafb;
    margin-bottom: 0;
}
.table th, .table td {
    border: none !important;
    background: transparent !important;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    color: #222;
}
.table thead {
    background: #f3f4f6;
}
.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #f6f8fa;
}
.table-responsive {
    border-radius: 1rem;
    overflow: hidden;
}
.btn-primary, .btn-success, .btn-warning, .btn-danger, .btn-info {
    border-radius: 2rem !important;
    font-weight: 600;
    letter-spacing: 0.02em;
    box-shadow: 0 2px 8px 0 rgba(249,199,79,0.10);
    transition: background 0.2s, box-shadow 0.2s;
}
.btn-primary:hover, .btn-primary:focus {
    background: #f9844a !important;
    color: #fff !important;
    box-shadow: 0 4px 16px 0 rgba(249,199,79,0.18);
}
.dropdown-menu {
    border-radius: 1rem;
    box-shadow: 0 2px 16px 0 rgba(60,72,88,0.07);
}
.badge {
    border-radius: 1rem;
    font-size: 0.95rem;
    padding: 0.4em 0.8em;
}
.form-control, .form-select {
    border-radius: 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    font-size: 1rem;
    padding: 0.7rem 1rem;
}
.form-control:focus, .form-select:focus {
    border-color: #f9c74f;
    box-shadow: 0 0 0 2px #f9c74f33;
}
@media (max-width: 767.98px) {
    .card {
        padding: 1.2rem !important;
    }
    .table {
        font-size: 0.98rem;
    }
    .card-header, .card-body {
        padding: 1rem !important;
    }
}
</style>
<style>
/* Responsive table styles: show header label left and value right on small screens */
@media (max-width: 767.98px) {
    .table-responsive table thead { display: none; }
    .table-responsive table, .table-responsive tbody, .table-responsive tr, .table-responsive td { display: block; width: 100%; }
    .table-responsive tbody tr { padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.05); margin-bottom: 8px; }
    .table-responsive td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        white-space: normal !important;
    }
    .table-responsive td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6b7280;
        margin-right: 12px;
        flex: 0 0 auto;
    }
    .table-responsive td[rowspan] { display: none; }
}
</style>
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'rolesPageSkeleton',
        'columns' => 5,
        'rows' => 10,
        'filterFields' => 1,
    ])

    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="mb-0">Roles</h4>
            <a href="{{ route('roles.create') }}" class="btn btn-custom btn-sm d-flex align-items-center gap-2 @if(!auth()->user()->hasCrmPermission('create_crm_role_guard')) disabled @endif"><i class="bi bi-plus"></i> Add Role</a>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="overflow:visible;">
                    <thead class="custom-display d-none d-md-table-row-group">
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rolesList as $role)
                            <tr>
                                <td data-label="Actions" style="overflow:visible; position:relative;">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button" id="roleActionsDropdown{{ $role->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2" aria-labelledby="roleActionsDropdown{{ $role->id }}" style="min-width:180px;">
                                            <li>
                                                <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_role_guard')) disabled @endif" href="{{ route('roles.edit', $role->id) }}">Edit</a>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('roles.destroy', $role->id) }}" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_role_guard')) disabled @endif">Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td data-label="Name"><a href="{{ route('roles.edit', $role->id) }}" class="text-decoration-none @if(!auth()->user()->hasCrmPermission('edit_crm_role_guard')) disabled @endif">{{ $role->name }}</a></td>
                                <td data-label="Description">{{ $role->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center">No roles found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
