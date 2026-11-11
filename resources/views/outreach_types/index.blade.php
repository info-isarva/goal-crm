@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">
<div class="container-fluid p-4">
    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-0">Outreach Types</h4>
                <small class="text-muted">Admin master list used in Daily Report outreach dropdown</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('settings.index') }}" class="btn btn-light btn-sm">&laquo; Settings</a>
                @if(auth()->user()->hasCrmPermission('create_crm_outreach_type_guard') || (int)(auth()->user()->crm_role_type ?? -1) === 0)
                    <a href="{{ route('outreach_types.create') }}" class="btn btn-custom btn-sm">
                        <i class="bi bi-plus"></i> Add Outreach Type
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="GET" class="mb-3 row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by name">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-custom" type="submit">Apply</button>
                    <a href="{{ route('outreach_types.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Order</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($outreachTypes as $type)
                            <tr>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu shadow">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('outreach_types.edit', $type->id) }}">Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('outreach_types.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Delete this outreach type?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td>{{ $type->name }}</td>
                                <td>{{ $type->description }}</td>
                                <td>{{ $type->sort_order }}</td>
                                <td>
                                    @if($type->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No outreach types found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $outreachTypes->links() }}</div>
        </div>
    </div>
</div>
@endsection
