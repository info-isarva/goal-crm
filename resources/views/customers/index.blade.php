@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">

@php
    $selectedFyId = session('selected_financial_year', null);
    $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
    $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
@endphp
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'customersPageSkeleton',
        'columns' => 6,
        'rows' => 10,
        'filterFields' => 2,
    ])

    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="mb-0">Company Owner List</h4>
            <div class="d-flex align-items-center gap-2">
                @if(!$isHistorical)
                    <a href="{{ route('customers.excel', request()->except('page')) }}" class="btn btn-success btn-sm d-flex align-items-center gap-2"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2 @if(!auth()->user()->hasCrmPermission('create_crm_customer_guard')) disabled @endif"><i class="bi bi-plus"></i> Add Company Owner</a>
                @endif

                
            </div>
        </div>
        <div class="card-body">
            <!-- @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif -->
              <div class="mt-3 mb-3" id="customerFilters">
                    <!-- Inline search / owner filter -->
                    <form id="customers-search-form" method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-center">
                        <div class="col-12 col-sm-6 col-md-4">    
                            <input type="text" name="q" id="customers-search-q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name or company" autocomplete="off">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <select name="owner_id" id="customers-search-owner" class="form-select form-select-sm" >
                                <option value="">All owners</option>
                                @foreach(\App\Models\User::whereNotIn('crm_role_type', [0,1])->orderBy('name')->get() as $o)
                                    <option value="{{ $o->id }}" @if((string)request('owner_id') === (string)$o->id) selected @endif>{{ $o->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                    </form>
                </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="overflow:visible;">
                    <thead class="custom-display d-none d-md-table-row-group">
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Owner</th>
                            <th>Phone</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr class="d-none d-md-table-row">
                                <td style="overflow:visible; position:relative;">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button" id="customerActionsDropdown{{ $customer->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2" aria-labelledby="customerActionsDropdown{{ $customer->id }}" style="min-width:180px;">
                                            <li>
                                                <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('view_crm_customer_guard')) disabled @endif" href="{{ route('customers.show', $customer->id) }}">View</a>
                                            </li>
                                            @if(!$isHistorical)
                                            <li>
                                                <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_customer_guard')) disabled @endif" href="{{ route('customers.edit', $customer->id) }}">Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="delete-customer-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="dropdown-item px-4 py-2 fs-6 text-danger delete-customer-btn @if(!auth()->user()->hasCrmPermission('delete_crm_customer_guard')) disabled @endif" data-customer-name="{{ $customer->name }}">Delete</button>
                                                </form>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td><a href="{{ route('customers.show', $customer->id) }}" class="text-decoration-none">{{ $customer->name }}</a></td>
                                <td>{{ optional($customer->organization)->name ?? '-' }}</td>
                                <td>{{ optional($customer->owner)->name ?? '-' }}</td>
                                <td>{{ $customer->phone }}</td>
                                <td>{{ $customer->email }}</td>
                            </tr>
                            <!-- Mobile Card -->
                            <tr class="d-md-none">
                                <td colspan="10">
                                    <div class="card mb-2 shadow-sm">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold">{{ $customer->name }}</span>
                                                <div class="dropdown">
                                                    <button class="btn btn-link p-0 text-dark" type="button" id="customerActionsDropdownMobile{{ $customer->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots fs-5"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="customerActionsDropdownMobile{{ $customer->id }}">
                                                        <li>
                                                            <a class="dropdown-item @if(!auth()->user()->hasCrmPermission('view_crm_customer_guard')) disabled @endif" href="{{ route('customers.show', $customer->id) }}">View</a>
                                                        </li>
                                                        @if(!$isHistorical)
                                                        <li>
                                                            <a class="dropdown-item @if(!auth()->user()->hasCrmPermission('edit_crm_customer_guard')) disabled @endif" href="{{ route('customers.edit', $customer->id) }}">Edit1</a>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="delete-customer-form">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="button" class="dropdown-item text-danger delete-customer-btn @if(!auth()->user()->hasCrmPermission('delete_crm_customer_guard')) disabled @endif" data-customer-name="{{ $customer->name }}">Delete</button>
                                                            </form>
                                                        </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="mb-1"><span class="text-muted">Organization:</span> {{ optional($customer->organization)->name ?? '-' }}</div>
                                            <div class="mb-1"><span class="text-muted">Owner:</span> {{ optional($customer->owner)->name ?? '-' }}</div>
                                            <div class="mb-1"><span class="text-muted">Phone:</span> {{ $customer->phone }}</div>
                                            <div class="mb-1"><span class="text-muted">Email:</span> {{ $customer->email }}</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center">No Company Owner found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small text-muted">Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} owners</div>
                <div class="pagination-custom text-center my-3">
                    <nav aria-label="Customers pagination">
                        <ul class="pagination justify-content-center gap-3 mb-0">
                            <li class="page-item {{ $customers->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $customers->previousPageUrl() ?: '#' }}" tabindex="-1" aria-disabled="{{ $customers->onFirstPage() ? 'true' : 'false' }}">&laquo; Previous</a>
                            </li>
                            <li class="page-item {{ $customers->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $customers->nextPageUrl() ?: '#' }}" aria-disabled="{{ $customers->hasMorePages() ? 'false' : 'true' }}">Next &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var form = document.getElementById('customers-search-form');
                    var qInput = document.getElementById('customers-search-q');
                    var ownerSelect = document.getElementById('customers-search-owner');
                    var clearBtn = document.getElementById('customers-search-clear');
                    if (!form) return;

                    var timeout = null;
                    if (qInput) {
                        qInput.addEventListener('input', function () {
                            if (timeout) clearTimeout(timeout);
                            timeout = setTimeout(function () {
                                form.submit();
                            }, 450);
                        });

                        qInput.addEventListener('keydown', function (e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                if (timeout) clearTimeout(timeout);
                                form.submit();
                            }
                        });
                    }

                    if (ownerSelect) {
                        ownerSelect.addEventListener('change', function () { form.submit(); });
                    }

                    if (clearBtn) {
                        clearBtn.addEventListener('click', function () {
                            if (qInput) qInput.value = '';
                            if (ownerSelect) ownerSelect.selectedIndex = 0;
                            form.submit();
                        });
                    }

                   
                    //Swal Alert for delete confirmation
                    var buttons = document.querySelectorAll('.delete-customer-btn');
                    var name = 'data-customer-name';
                    attachDeleteHandlers(buttons, name); 
                });
            </script>
        </div>
    </div>
</div>
@endsection
