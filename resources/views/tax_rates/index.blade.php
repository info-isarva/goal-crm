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
<div class="container-fluid hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'taxRatesPageSkeleton',
        'columns' => 5,
        'rows' => 10,
        'filterFields' => 1,
    ])

    <div class="card mt-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="mb-0">Tax Rates</h4>
            <a href="{{ route('tax_rates.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2 @if(!auth()->user()->hasCrmPermission('create_crm_tax_guard')) disabled @endif"><i class="bi bi-plus"></i> Add tax rate</a>
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
                            <th>Rate (%)</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($taxRates as $taxRate)
                            <tr class="d-none d-md-table-row">
                                <td class="dropdown-parent-fix" style="overflow:visible; position:relative;">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button" id="taxRateActionsDropdown{{ $taxRate->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2" aria-labelledby="taxRateActionsDropdown{{ $taxRate->id }}" style="min-width:180px;">
                                            <li>
                                                <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_tax_guard')) disabled @endif" href="{{ route('tax_rates.edit', $taxRate->id) }}">Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('tax_rates.destroy', $taxRate->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tax rate?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_tax_guard')) disabled @endif">Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td><a href="{{ route('tax_rates.edit', $taxRate->id) }}" class="text-decoration-none @if(!auth()->user()->hasCrmPermission('edit_crm_tax_guard')) disabled @endif">{{ $taxRate->name }}</a></td>
                                <td>{{ $taxRate->rate }}</td>
                                <td>{{ $taxRate->type }}</td>
                            </tr>
                            <!-- Mobile Card -->
                            <tr class="d-md-none">
                                <td colspan="10">
                                    <div class="card mb-2 shadow-sm">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold">{{ $taxRate->name }}</span>
                                                <div class="dropdown">
                                                    <button class="btn btn-link p-0 text-dark" type="button" id="taxRateActionsDropdownMobile{{ $taxRate->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots fs-5"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="taxRateActionsDropdownMobile{{ $taxRate->id }}">
                                                        <li>
                                                            <a class="dropdown-item @if(!auth()->user()->hasCrmPermission('edit_crm_tax_guard')) disabled @endif" href="{{ route('tax_rates.edit', $taxRate->id) }}">Edit</a>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('tax_rates.destroy', $taxRate->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tax rate?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_tax_guard')) disabled @endif">Delete</button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="mb-1"><span class="text-muted">Rate:</span> {{ $taxRate->rate }}</div>
                                            <div class="mb-1"><span class="text-muted">Type:</span> {{ $taxRate->type }}</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center">No tax rates found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $taxRates->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
