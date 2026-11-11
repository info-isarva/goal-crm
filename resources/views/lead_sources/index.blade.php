@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{asset('css/leads.css') }}">
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'leadSourcesPageSkeleton',
        'columns' => 4,
        'rows' => 10,
        'filterFields' => 1,
    ])

    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h4 class="mb-0">Lead Sources</h4>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('lead_sources.create') }}" class="btn btn-custom btn-sm d-flex align-items-center gap-2 @if(!auth()->user()->hasCrmPermission('create_crm_product_category_guard')) disabled @endif">
                    <i class="bi bi-plus"></i> Add Lead Source
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('lead_sources.index') }}" class="mb-3" id="leadSourceFiltersForm">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-6 col-md-6">
                        <label for="search" class="form-label mb-1">Search</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Search by name or description">
                    </div>
                    <div class="col-xl-3 col-md-3">
                        <label for="description_filter" class="form-label mb-1">Description</label>
                        <select name="description_filter" id="description_filter" class="form-select">
                            <option value="" @selected(request('description_filter') === '')>All</option>
                            <option value="with" @selected(request('description_filter') === 'with')>With Description</option>
                            <option value="without" @selected(request('description_filter') === 'without')>Without Description</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-3 d-flex gap-2 align-items-stretch">
                        <button type="submit" class="btn btn-custom px-3 d-inline-flex align-items-center justify-content-center">Apply</button>
                        <a href="{{ route('lead_sources.index') }}" class="btn btn-outline-secondary btn-padding px-3 d-inline-flex align-items-center justify-content-center">Reset</a>
                    </div>
                </div>
            </form>

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
                        @forelse($leadSources as $leadSource)
                            <tr>
                                <td style="overflow:visible; position:relative;" data-label="Actions">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button" id="leadSourceActionsDropdown{{ $leadSource->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2" aria-labelledby="leadSourceActionsDropdown{{ $leadSource->id }}">
                                            <li>
                                                <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_product_category_guard')) disabled @endif" href="{{ route('lead_sources.edit', $leadSource->id) }}">Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('lead_sources.destroy', $leadSource->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this lead source?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_product_category_guard')) disabled @endif">Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td data-label="Name">
                                    <a class="text-decoration-none @if(!auth()->user()->hasCrmPermission('edit_crm_product_category_guard')) disabled @endif" href="{{ route('lead_sources.edit', $leadSource->id) }}">{{ $leadSource->name }}</a>
                                </td>
                                <td data-label="Description">{{ $leadSource->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center">No lead sources found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
                <div class="small text-muted">Showing {{ $leadSources->firstItem() ?? 0 }} to {{ $leadSources->lastItem() ?? 0 }} of {{ $leadSources->total() }} lead sources</div>
                <div class="pagination-custom text-center my-3">
                    {{ $leadSources->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
