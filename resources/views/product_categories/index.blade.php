
@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{asset('css/leads.css') }}">
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'productCategoriesPageSkeleton',
        'columns' => 5,
        'rows' => 10,
        'filterFields' => 1,
    ])

    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h4 class="mb-0">Product Categories</h4>
            <div class="d-flex gap-2 flex-wrap">
                <!-- <button id="toggleFiltersButton" class="btn btn-outline-secondary btn-sm">Show Filters</button> -->
                @if(auth()->user()->hasCrmPermission('manage_crm_product_category_guard') || (int)(auth()->user()->crm_role_type ?? -1) === 0)
                    <a href="{{ route('product_category_types.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                        Manage Types
                    </a>
                @endif
                <a href="{{ route('product_categories.create') }}" class="btn btn-custom btn-sm d-flex align-items-center gap-2 @if(!auth()->user()->hasCrmPermission('create_crm_product_category_guard')) disabled @endif">
                    <i class="bi bi-plus"></i> Add Category
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('product_categories.index') }}" class="mb-3" id="filtersForm">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label for="search" class="form-label mb-1">Search</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Search by name, description, or type">
                    </div>
                    <div class="col-xl-2 col-md-3">
                        <label for="status" class="form-label mb-1">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All</option>
                            <option value="1" @selected(request('status') === '1')>Active</option>
                            <option value="0" @selected(request('status') === '0')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-3">
                        <label for="type" class="form-label mb-1">Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">All</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-12 d-flex gap-2 align-items-stretch">
                        <button type="submit" class="btn btn-custom px-3 d-inline-flex align-items-center justify-content-center">Apply</button>
                        <a href="{{ route('product_categories.index') }}" class="btn btn-outline-secondary btn-padding px-3 d-inline-flex align-items-center justify-content-center">Reset</a>
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
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td style="overflow:visible; position:relative;" data-label="Actions">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button" id="productCategoryActionsDropdown{{ $category->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2" aria-labelledby="productCategoryActionsDropdown{{ $category->id }}">
                                            <li>
                                                <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_product_category_guard')) disabled @endif" href="{{ route('product_categories.edit', $category->id) }}">Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('product_categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_product_category_guard')) disabled @endif">Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td data-label="Name">
                                    <a class="text-decoration-none @if(!auth()->user()->hasCrmPermission('edit_crm_product_category_guard')) disabled @endif" href='{{ route('product_categories.edit', $category->id) }}'>{{ $category->category_name }}</a>
                                </td>
                                <td data-label="Description">{{ $category->description }}</td>
                                <td data-label="Status">{{ $category->status ? 'Active' : 'Inactive' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center">No categories found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
                <div class="small text-muted">Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} categories</div>
                <div class="pagination-custom text-center my-3">
                    <nav aria-label="Product categories pagination">
                        <ul class="pagination justify-content-center gap-3 mb-0">
                            <li class="page-item {{ $categories->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $categories->previousPageUrl() ?: '#' }}" tabindex="-1" aria-disabled="{{ $categories->onFirstPage() ? 'true' : 'false' }}">&laquo; Previous</a>
                            </li>
                            <li class="page-item {{ $categories->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $categories->nextPageUrl() ?: '#' }}" aria-disabled="{{ $categories->hasMorePages() ? 'false' : 'true' }}">Next &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
