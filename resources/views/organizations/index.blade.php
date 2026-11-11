@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/organization-main.css') }}">

@php
    $selectedFyId = session('selected_financial_year', null);
    $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
    $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
@endphp
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'organizationsPageSkeleton',
        'columns' => 7,
        'rows' => 10,
        'filterFields' => 3,
    ])

    <div class="card mt-0">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap org-header">
                <div class="d-flex align-items-center me-2 flex-grow-1 org-left" style="gap:12px;">
                    <h4 class="mb-4 mb-md-0">Company List</h4>
                </div>

                <div class="d-flex gap-2 align-items-center flex-wrap">

                    @if(!$isHistorical)
                        <a href="{{ route('organizations.excel', request()->except('page')) }}" class="btn btn-success btn-padding d-flex align-items-center gap-2 ms-1"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                        <a href="{{ route('organizations.create') }}" class="btn btn-custom btn-sm d-flex align-items-center gap-2 add-btn @if(!auth()->user()->hasCrmPermission('create_crm_organization_guard')) disabled @endif"><i class="bi bi-plus"></i> Add Company</a>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif -->

            <div id="orgFilters" class="mt-3 d-block d-md-block mb-3">

                <div class="d-flex justify-content-start align-items-center flex-wrap gap-3">
                    <div >
<form id="org-search-form" method="GET" action="{{ route('organizations.index') }}" class="row ">
                        <div class="col-lg-4 col-sm-6 col-md-4">
                            <input type="text" name="name" id="org-search-name" value="{{ request('name') }}" class="form-control form-control-sm" placeholder="Name" autocomplete="off">
                        </div>

                        <div class="col-lg-4 col-sm-6 col-md-4">
                            <select name="industry_type" id="org-search-industry" class="form-select form-select-sm">
                                <option value="">All industries</option>
                                @foreach($industries as $ind)
                                    <option value="{{ $ind->id }}" @if((string)request('industry_type') === (string)$ind->id) selected @endif>{{ $ind->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-sm-6 col-md-4">
                            <select name="organization_type" id="org-search-type" class="form-select form-select-sm">
                                <option value="">All types</option>
                                @foreach($organizationTypes as $id => $label)
                                    <option value="{{ $id }}" @if((string)request('organization_type') === (string)$id) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-sm-6 col-md-4">
                            <select name="owner_id" id="org-search-owner" class="form-select form-select-sm">
                                <option value="">All owners</option>
                                @foreach($owners as $o)
                                    <option value="{{ $o->id }}" @if((string)request('owner_id') === (string)$o->id) selected @endif>{{ $o->name }}</option>
                                @endforeach
                            </select>
                        </div>


                        <div class="col-12 col-sm-6 col-md-1 d-flex gap-2">
                            @if(request()->has('view'))
                                <input type="hidden" name="view" value="{{ request('view') }}">
                            @endif
                            <!-- <button type="button" id="org-search-clear" class="btn btn-sm btn-outline-secondary">Clear</button> -->
                        </div>
                    </form>
                    </div>

                      <div >
                            <form id="org-view-form" method="GET" action="{{ route('organizations.index') }}">
                                @foreach(request()->except('view','page') as $k => $v)
                                    @if(is_array($v))
                                        @foreach($v as $item)
                                            <input type="hidden" name="{{ $k }}[]" value="{{ $item }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                    @endif
                                @endforeach
                                <select name="view" id="orgViewSelect" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="All Accounts" @if(request('view') == 'All Accounts') selected @endif>All Accounts</option>
                                    <option value="My Accounts" @if(request('view') == 'My Accounts') selected @endif>My Accounts</option>
                                    <option value="New Last Week" @if(request('view') == 'New Last Week') selected @endif>New Last Week</option>
                                    <option value="New This Week" @if(request('view') == 'New This Week') selected @endif>New This Week</option>
                                    <option value="Recently Created Accounts" @if(request('view') == 'Recently Created Accounts') selected @endif>Recently Created Accounts</option>
                                    <option value="Recently Modified Accounts" @if(request('view') == 'Recently Modified Accounts' || !request('view')) selected @endif>Recently Modified Accounts</option>
                                </select>
                            </form>
                        </div>
                </div>

            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="overflow:visible;">
                    <thead class="custom-display d-none d-md-table-row-group">
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Industry</th>
                            <th>Type</th>
                            <th>Website</th>
                            <th>City</th>
                            <th>Owner</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($organizations as $org)
                        <tr>
                            <td style="overflow:visible; position:relative;" data-label="Actions">
                                <div class="dropdown">
                                    <button class="btn btn-link p-0 text-dark" type="button" id="orgActionsDropdown{{ $org->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical fs-5"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2" aria-labelledby="orgActionsDropdown{{ $org->id }}" style="min-width:180px;">
                                        <li>
                                            <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('view_crm_organization_guard')) disabled @endif" href="{{ route('organizations.show', $org->id) }}">View</a>
                                        </li>
                                        @if(!$isHistorical)
                                        <li>
                                            <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_organization_guard')) disabled @endif" href="{{ route('organizations.edit', $org->id) }}">Edit</a>
                                        </li>
                                        <li>
                                            <form action="{{ route('organizations.destroy', $org->id) }}" method="POST" >
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="delete-company-btn dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_organization_guard')) disabled @endif" data-company-name="{{ $org->name }}">Delete</button>
                                            </form>

                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                            <td data-label="Name"><a href="{{ route('organizations.show', $org->id) }}" class="text-decoration-none">{{ $org->name }}</a></td>
                            @php $primaryPerson = $org->people->first() ?? null; @endphp
                            <td data-label="Contact Person">
                                @if($primaryPerson)
                                    <a href="{{ route('people.show', $primaryPerson->id) }}" class="text-decoration-none">{{ trim(($primaryPerson->first_name ?? '') . ' ' . ($primaryPerson->last_name ?? '')) ?: '-' }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td data-label="Industry">{{ optional($org->industry)->name ?? '-' }}</td>
                            <td data-label="Type">{{ $organizationTypes[$org->organization_type] ?? '-' }}</td>
                            <td data-label="Website">{{ $org->website }}</td>
                            <td data-label="City">{{ $org->city }}</td>
                            <td data-label="Owner">{{ optional($org->owner)->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No companies found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
                <div class="small text-muted">Showing {{ $organizations->firstItem() ?? 0 }} to {{ $organizations->lastItem() ?? 0 }} of {{ $organizations->total() }} company</div>
                <div class="org-pagination text-center my-3">
                    <nav aria-label="Organizations pagination">
                        <ul class="pagination justify-content-center gap-3 mb-0">
                            <li class="page-item {{ $organizations->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $organizations->previousPageUrl() ?: '#' }}" tabindex="-1" aria-disabled="{{ $organizations->onFirstPage() ? 'true' : 'false' }}">&laquo; Previous</a>
                            </li>
                            <li class="page-item {{ $organizations->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $organizations->nextPageUrl() ?: '#' }}" aria-disabled="{{ $organizations->hasMorePages() ? 'false' : 'true' }}">Next &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

        </div>

    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('org-search-form');
        var nameInput = document.getElementById('org-search-name');
        var industrySelect = document.getElementById('org-search-industry');
        var typeSelect = document.getElementById('org-search-type');
        var ownerSelect = document.getElementById('org-search-owner');
        var clearBtn = document.getElementById('org-search-clear');
        var filtersDiv = document.getElementById('orgFilters');

        // Initialize filters visibility
        if (window.innerWidth >= 768) {
            if (filtersDiv) filtersDiv.style.display = 'block';
        }

        if (!form) return;

        var timeout = null;
        if (nameInput) {
            nameInput.addEventListener('input', function () {
                if (timeout) clearTimeout(timeout);
                timeout = setTimeout(function () {
                    form.submit();
                }, 450);
            });

            nameInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (timeout) clearTimeout(timeout);
                    form.submit();
                }
            });
        }

        [industrySelect, typeSelect, ownerSelect].forEach(function (sel) {
            if (!sel) return;
            sel.addEventListener('change', function () {

                form.submit();
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (nameInput) nameInput.value = '';
                if (industrySelect) industrySelect.selectedIndex = 0;
                if (typeSelect) typeSelect.selectedIndex = 0;
                if (ownerSelect) ownerSelect.selectedIndex = 0;
                form.submit();
            });
        }

        // Handle filters toggle on mobile
        var filterToggleBtn = document.getElementById('filterToggleBtn');
        if (filterToggleBtn && filtersDiv) {
            filterToggleBtn.addEventListener('click', function() {
                if (filtersDiv.style.display === 'none' || !filtersDiv.style.display) {
                    filtersDiv.style.display = 'block';
                    filterToggleBtn.classList.add('active');
                } else {
                    filtersDiv.style.display = 'none';
                    filterToggleBtn.classList.remove('active');
                }
            });
        }

        // Handle resize events
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                if (filtersDiv) filtersDiv.style.display = 'block';
            } else {
                if (filtersDiv && !filterToggleBtn.classList.contains('active')) {
                    filtersDiv.style.display = 'none';
                }
            }
        });


        //Swal Alert for delete confirmation
        var buttons = document.querySelectorAll('.delete-company-btn');
        var name = 'data-company-name';
        attachDeleteHandlers(buttons, name);

        // // SweetAlert2 delete confirmation for customer delete buttons
        // function attachDeleteHandlers() {
        //     var buttons = document.querySelectorAll('.delete-company-btn');
        //     buttons.forEach(function (btn) {
        //         // avoid attaching multiple listeners
        //         if (btn._swalAttached) return;
        //         btn._swalAttached = true;

        //         btn.addEventListener('click', function (e) {
        //             if (btn.classList.contains('disabled')) return;
        //             var form = btn.closest('form');
        //             if (!form) return;
        //             var name = btn.getAttribute('data-company-name') || '';
        //             var message = name ? 'Are you sure you want to delete "' + name + '"? This action cannot be undone.' : 'Are you sure you want to delete this record? This action cannot be undone.';

        //             if (window.Swal && typeof window.Swal.fire === 'function') {
        //                 window.Swal.fire({
        //                     title: 'Delete confirmation',
        //                     text: message,
        //                     icon: 'warning',
        //                     showCancelButton: true,
        //                     confirmButtonColor: '#d33',
        //                     confirmButtonText: 'Yes, delete it!',
        //                     cancelButtonText: 'Cancel'
        //                 }).then(function (result) {
        //                     if (result.isConfirmed) {
        //                         form.submit();
        //                     }
        //                 });
        //             } else {
        //                 // Fallback to native confirm
        //                 if (confirm(message)) {
        //                     form.submit();
        //                 }
        //             }
        //         });
        //     });
        // }

        // Attach now and also after any AJAX content changes (if applicable)
        attachDeleteHandlers();


    });
</script>
@endsection
