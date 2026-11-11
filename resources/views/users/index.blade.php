@extends('layouts.app')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="{{asset('css/user.css') }}" rel="stylesheet">
    <style>
        /* Responsive table styles: show header label left and value right on small screens */
        @media (max-width: 767.98px) {
            .table-responsive table thead {
                display: none;
            }

            .table-responsive table,
            .table-responsive tbody,
            .table-responsive tr,
            .table-responsive td {
                display: block;
                width: 100%;
            }

            .table-responsive tbody tr {
                padding: 8px 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                margin-bottom: 8px;
            }

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

            .table-responsive td[rowspan] {
                display: none;
            }
        }
    </style>
    <div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
        @include('partials.skeletons.crm_list', [
            'skeletonId' => 'usersPageSkeleton',
            'columns' => 7,
            'rows' => 10,
            'filterFields' => 2,
        ])

        <div class="card mt-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Users</h4>
                    @if(!empty($maxUsers))
                        <small class="text-muted">Seats: {{ $userCount ?? 0 }} / {{ $maxUsers }}</small>
                    @elseif(isset($userCount))
                        <small class="text-muted">Total users: {{ $userCount }}</small>
                    @endif
                </div>
                <a href="{{ route('users.create') }}"
                    class="btn btn-custom btn-sm d-flex align-items-center gap-2 @if(!auth()->user()->hasCrmPermission('create_crm_user_guard') || (!auth()->user()->isSuperAdmin() && !empty($maxUsers) && ($userCount ?? 0) >= $maxUsers)) disabled @endif"><i
                        class="bi bi-plus"></i> Create User</a>
            </div>
            <div class="card-body">
                <!-- @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" style="overflow:visible;">
                        <thead class="custom-display d-none d-md-table-row-group">
                            <tr>
                                <th></th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>CRM Role</th>
                                <th>Status</th>
                                <!-- <th>Permissions</th> -->
                                <th>2FA</th>
                                <th>Sales Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td data-label="Actions" style="overflow:visible; position:relative;">
                                        @if ($user->crm_role_type != 0)
                                            <div class="dropdown">
                                                <button class="btn btn-link p-0 text-dark" type="button"
                                                    id="userActionsDropdown{{ $user->id }}" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical fs-5"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2"
                                                    aria-labelledby="userActionsDropdown{{ $user->id }}" style="min-width:180px;">
                                                    <li>
                                                        <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_user_guard')) disabled @endif"
                                                            href="{{ route('users.edit', $user->id) }}">Edit</a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button"
                                                                class="delete-user-btn dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_user_guard')) disabled @endif"
                                                                data-user-name="{{ $user->name }}">Delete</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Name">
                                        @if ($user->crm_role_type != 0)
                                            <a href="{{ route('users.edit', $user->id) }}"
                                                class="text-decoration-none @if(!auth()->user()->hasCrmPermission('edit_crm_user_guard')) disabled @endif">
                                                {{ $user->name }}
                                            </a>
                                        @else
                                            {{ $user->name }}
                                        @endif
                                    </td>
                                    <td data-label="Email">{{ $user->email }}</td>
                                    <td data-label="CRM Role">
                                        @if($user->crm_role_type == 0)
                                            <span class="badge bg-dark">Super Admin</span>
                                        @elseif(isset($roles[$user->crm_role_type]))
                                            <span class="badge bg-primary">{{ $roles[$user->crm_role_type] }}</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    </td>
                                    <td data-label="Status">
                                        @if((int) $user->status === 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <!-- <td>
                                            @php
                                                $perms = is_array(json_decode($user->crm_page_right, true)) ? json_decode($user->crm_page_right, true) : [];
                                            @endphp
                                            @if(isset($allPermissions) && is_array($allPermissions) && count($allPermissions))
                                                @foreach($allPermissions as $perm)
                                                    @if(in_array($perm, $perms))
                                                        <span class="badge bg-info text-dark">{{ $perm }}</span>
                                                    @else
                                                        <span class="badge bg-info text-muted border">{{ $perm }}</span>
                                                    @endif
                                                @endforeach
                                            @else
                                                <span class="text-muted">None</span>
                                            @endif
                                        </td> -->
                                    <td data-label="2FA">
                                        <form method="POST" action="{{ route('users.2fa.update', $user->id) }}"
                                            style="display:inline-block;">
                                            @csrf
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="2fa_enabled" value="1"
                                                    onchange="this.form.submit()" {{ $user->{"2fa_enabled"} ? 'checked' : '' }}
                                                    @if(!auth()->user()->hasCrmPermission('edit_crm_user_guard')) disabled
                                                    @endif>
                                                <label class="form-check-label" for="2fa_enabled_{{ $user->id }}">2FA</label>
                                            </div>
                                        </form>
                                    </td>
                                    <td data-label="Sales Target">
                                        {{ $user->sales_target ? number_format($user->sales_target, 2) : '0.00' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function () {
        //Swal Alert for delete confirmation
        var buttons = document.querySelectorAll('.delete-user-btn');
        var name = 'data-user-name';
        attachDeleteHandlers(buttons, name);
    });
</script>