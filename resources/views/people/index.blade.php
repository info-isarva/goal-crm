@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">

<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'peoplePageSkeleton',
        'columns' => 7,
        'rows' => 10,
        'filterFields' => 3,
    ])

@php
    $selectedFyId = session('selected_financial_year', null);
    $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
    $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
@endphp
    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h4 class="mb-0">Contact Person</h4>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <div class="d-flex gap-2 flex-wrap align-items-center ">

                    @if(!$isHistorical)
                    <a href="{{ route('people.excel', request()->except('page')) }}" class="btn btn-success btn-padding d-flex align-items-center gap-2 ms-1"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                    <a href="{{ route('people.create') }}" class="btn btn-custom btn-sm d-flex align-items-center gap-2 @if(!auth()->user()->hasCrmPermission('create_crm_contact_person_guard')) disabled @endif"><i class="bi bi-plus"></i> Add Person</a>
                    @endif
                </div>


            </div>
        </div>
        <div class="card-body">
                <!-- @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                @endif -->
                <div class="mt-3 d-block d-md-block mb-3" id="peopleFilters">
                    <!-- Inline search / owner filter -->
                     <div class="d-flex justify-content-start align-items-center flex-wrap gap-3">
                        <div>
                            <form id="people-search-form" method="GET" action="{{ route('people.index') }}" class="row">
                                <div class="col-lg-7 col-sm-6 col-md-4">
                                    <input type="text" name="q" id="people-search-q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name, email or mobile" autocomplete="off" >
                                </div>
                                <div class="col-lg-5 col-sm-6 col-md-4">
                                    <select name="owner_id" id="people-search-owner" class="form-select form-select-sm" >
                                        <option value="">All owners</option>
                                        @foreach(\App\Models\User::whereNotIn('crm_role_type', [0,1])->orderBy('name')->get() as $o)
                                            <option value="{{ $o->id }}" @if((string)request('owner_id') === (string)$o->id) selected @endif>{{ $o->name }}</option>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="col-12 col-md-auto">
                                    @if(request()->has('view'))
                                        <input type="hidden" name="view" value="{{ request('view') }}">
                                    @endif
                                </div>
                            </form>
                        </div>
                        <div>
                            <!-- Filter Select -->
                            <form id="people-view-form" method="GET" action="{{ route('people.index') }}">
                                @foreach(request()->except('view','page') as $k => $v)
                                    @if(is_array($v))
                                        @foreach($v as $item)
                                            <input type="hidden" name="{{ $k }}[]" value="{{ $item }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                    @endif
                                @endforeach
                                <select name="view" id="peopleViewSelect" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="All Contacts" @if(request('view') == 'All Contacts' || !request('view')) selected @endif>All Contacts</option>
                                    <option value="My Contacts" @if(request('view') == 'My Contacts') selected @endif>My Contacts</option>
                                    <option value="New Last Week" @if(request('view') == 'New Last Week') selected @endif>New Last Week</option>
                                    <option value="New This Week" @if(request('view') == 'New This Week') selected @endif>New This Week</option>
                                    <option value="Recently Created Contacts" @if(request('view') == 'Recently Created Contacts') selected @endif>Recently Created Contacts</option>
                                    <option value="Recently Modified Contacts" @if(request('view') == 'Recently Modified Contacts') selected @endif>Recently Modified Contacts</option>
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
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Owner</th>
                            </tr>
                        </thead>
                        <tbody>
                                @forelse($people as $person)
                                <tr>
                                    <td style="overflow:visible; position:relative;" data-label="Actions">
                                        <div class="dropdown">
                                            <button class="btn btn-link p-0 text-dark" type="button" id="personActionsDropdown{{ $person->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical fs-5"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2" aria-labelledby="personActionsDropdown{{ $person->id }}" style="min-width:180px;">
                                                <li>
                                                    <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('view_crm_contact_person_guard')) disabled @endif" href="{{ route('people.show', $person->id) }}">View</a>
                                                </li>
                                                @if(!$isHistorical)
                                                <li>
                                                    <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_contact_person_guard')) disabled @endif" href="{{ route('people.edit', $person->id) }}">Edit</a>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ route('people.destroy', $person->id) }}" onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="delete-person-btn dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_contact_person_guard')) disabled @endif" data-people-name="{{ $person->first_name }}">Delete</button>
                                                    </form>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                    <td data-label="Name"><a href="{{ route('people.show', $person->id) }}" class="text-decoration-none">{{ $person->first_name }} {{ $person->last_name }}</a></td>
                                    <td data-label="Email">{{ $person->email }}</td>
                                    <td data-label="Phone">{{ $person->mobile }}</td>
                                    <td data-label="Owner">{{ optional($person->owner)->name ?? '-' }}</td>
                                </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center">No contacts found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
                    <div class="small text-muted">Showing {{ $people->firstItem() ?? 0 }} to {{ $people->lastItem() ?? 0 }} of {{ $people->total() }} contact person</div>
                    <div class="pagination-custom text-center my-3">
                        <nav aria-label="People pagination">
                            <ul class="pagination justify-content-center gap-3 mb-0">
                                <li class="page-item {{ $people->onFirstPage() ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $people->previousPageUrl() ?: '#' }}" tabindex="-1" aria-disabled="{{ $people->onFirstPage() ? 'true' : 'false' }}">&laquo; Previous</a>
                                </li>
                                <li class="page-item {{ $people->hasMorePages() ? '' : 'disabled' }}">
                                    <a class="page-link" href="{{ $people->nextPageUrl() ?: '#' }}" aria-disabled="{{ $people->hasMorePages() ? 'false' : 'true' }}">Next &raquo;</a>
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
        var form = document.getElementById('people-search-form');
        var qInput = document.getElementById('people-search-q');
        var ownerSelect = document.getElementById('people-search-owner');
        var clearBtn = document.getElementById('people-search-clear');
        if (!form) return;

        var timeout = null;
        if (qInput) {
            qInput.addEventListener('input', function () {
                if (timeout) clearTimeout(timeout);
                timeout = setTimeout(function () {
                    form.submit();
                }, 1250);
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
        var buttons = document.querySelectorAll('.delete-person-btn');
        var name = 'data-people-name';
        attachDeleteHandlers(buttons, name);
    });
   </script>
    @endsection
