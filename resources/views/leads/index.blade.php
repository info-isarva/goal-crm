@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{asset('css/leads.css') }}">
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @php
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
    @endphp
    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="mb-0">Leads </h4>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                 <!-- Show Filters Button -->
                <button id="toggleFiltersButton" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center px-3 fw-semibold" style="height: 38px; font-size: 14px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">Show Filters</button>
                <!-- Filter Dropdown -->
                <div class="dropdown d-inline-flex align-items-center">
                    <button class="btn btn-light border dropdown-toggle d-inline-flex align-items-center justify-content-center px-3 text-dark fw-semibold" type="button" id="leadViewDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="height: 38px; background:#ffffff !important; color:#1e293b !important; border:1px solid #cbd5e1 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.06); font-size:14px; border-radius: 6px;">
                        <i class="bi bi-funnel me-1 text-primary"></i> {{ request('view', in_array((int)$user->crm_role_type, [0, 1, 2], true) ? 'All Leads' : 'My Leads') }}
                    </button>
                    <ul class="dropdown-menu shadow" aria-labelledby="leadViewDropdown" style="min-width: 260px;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" placeholder="Search" id="leadViewSearchInput">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header text-uppercase fw-bold small text-muted">Public Views</li>
                        <li><a class="dropdown-item @if(request('view') == 'All Leads' || !request('view')) active @endif" href="{{ route('leads.index', ['view' => 'All Leads']) }}">All Leads</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'All Converted Leads' || request('view') == 'Converted Leads') active @endif" href="{{ route('leads.index', ['view' => 'All Converted Leads']) }}">All Converted Leads</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'My Leads') active @endif" href="{{ route('leads.index', ['view' => 'My Leads']) }}">My Leads</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'My Converted Leads') active @endif" href="{{ route('leads.index', ['view' => 'My Converted Leads']) }}">My Converted Leads</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Open Leads') active @endif" href="{{ route('leads.index', ['view' => 'Open Leads']) }}">Open Leads</a></li>
                        <li><a class="dropdown-item @if(request('view') == "Today's Leads") active @endif" href="{{ route('leads.index', ['view' => "Today's Leads"]) }}">Today's Leads</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Junk Leads') active @endif" href="{{ route('leads.index', ['view' => 'Junk Leads']) }}">Junk Leads</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Not Qualified Leads') active @endif" href="{{ route('leads.index', ['view' => 'Not Qualified Leads']) }}">Not Qualified Leads</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Recently Created Leads') active @endif" href="{{ route('leads.index', ['view' => 'Recently Created Leads']) }}">Recently Created Leads</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Recently Modified Leads') active @endif" href="{{ route('leads.index', ['view' => 'Recently Modified Leads']) }}">Recently Modified Leads</a></li>
                    </ul>
                </div>
                @if(!$isHistorical)
                    <!-- Add Lead Button -->
                    <a href="{{ route('leads.create') }}" class="btn btn-custom d-inline-flex align-items-center justify-content-center px-3 gap-2 fw-semibold @if(!auth()->user()->hasCrmPermission('create_crm_leads_guard')) disabled @endif" style="height: 38px; padding: 0 16px; font-size: 14px; border-radius: 6px;">
                        <i class="bi bi-plus-lg"></i> Add Lead
                    </a>
                @endif

               
            </div>
        </div>
        <div class="card-body">
            <!-- @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif -->
            <!-- Search/Filter Bar -->
            

            <form method="GET" action="{{ route('leads.index') }}" class="mb-3" id="filtersForm" style="display: none;">
                <input type="hidden" name="view" value="{{ request('view') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-2 col-md-4">
                        <label for="contact" class="form-label mb-1">Lead Name</label>
                        <input type="text" name="contact" id="contact" class="form-control" value="{{ request('contact') }}" placeholder="Search by Company Name/Lead Name/Title">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="category" class="form-label mb-1">Product/Services</label>
                        <select name="category" id="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Product/Services</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @if(request('category') == $category->id) selected @endif>{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="label" class="form-label mb-1">Priority</label>
                        <select name="label" id="label" class="form-select">
                            <option value="">All</option>
                            @foreach($priorities ?? [] as $priority)
                                <option value="{{ $priority['value'] }}" @if(request('label') == $priority['value']) selected @endif>{{ $priority['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="lead_source" class="form-label mb-1">Lead Source</label>
                        <select name="lead_source" id="lead_source" class="form-select">
                            <option value="">All</option>
                            @foreach($leadSources ?? [] as $source)
                                <option value="{{ $source->id }}" @if(request('lead_source') == $source->id) selected @endif>{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="lead_status" class="form-label mb-1">Lead Status</label>
                        <select name="lead_status" id="lead_status" class="form-select">
                            <option value="">All</option>
                            @foreach($leadStatuses ?? [] as $status)
                                <option value="{{ $status['name'] }}" @if(request('lead_status') == $status['name']) selected @endif>{{ $status['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="owner" class="form-label mb-1">Owner</label>
                        <select name="owner" id="owner" class="form-select">
                            <option value="">All</option>
                            @foreach($owners ?? [] as $o)
                                <option value="{{ $o->id }}" @if((string)request('owner') === (string)$o->id) selected @endif>{{ $o->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="start_date" class="form-label mb-1">From</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label for="end_date" class="form-label mb-1">To</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="overflow:visible;">
                    <thead class="custom-display d-none d-md-table-row-group">
                        <tr>
                            <th></th>
                            
                            <!-- <th>Title</th> -->
                            <th>Lead Name</th>
                            <th>Company</th>
                            
                            <th>Lead Source</th>
                            <th>Lead Status</th>
                            <th>Lead Owner</th>
                            
                            <th>Priority</th>
                            <th>Product/Services</th>
                            <th>
                                <a href="{{ route('leads.index', array_merge(request()->all(), ['sort' => 'created_at', 'order' => request('order') === 'asc' ? 'desc' : 'asc'])) }}">
                                Created
                                 @if(request('sort') === 'created_at')
                                            <i class="bi bi-arrow-{{ request('order') === 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                            </th>
                            <th>
                                <a href="{{ route('leads.index', array_merge(request()->all(), ['sort' => 'updated_at', 'order' => request('order') === 'asc' ? 'desc' : 'asc'])) }}">
                                Updated
                                 @if(request('sort') === 'updated_at')
                                            <i class="bi bi-arrow-{{ request('order') === 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $lead)
                            <tr>
                                <td style="overflow:visible; position:relative;" data-label="Actions">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button" id="leadActionsDropdown{{ $lead->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2" aria-labelledby="leadActionsDropdown{{ $lead->id }}">
                                            @if(!$lead->converted_at)
                                            <li> 
                                                <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('convert_crm_leads_to_deals_guard')) disabled @endif" href="{{ route('leads.convert', $lead->id) }}">Convert</a>
                                            </li>
                                            @endif
                                            <li>
                                                <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('view_crm_leads_guard')) disabled @endif" href="{{ route('leads.show', $lead->id) }}">View</a>
                                            </li>
                                            <li>
                                                @if(!$isHistorical)
                                                   
                                                    <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_leads_guard')) disabled @endif" href="{{ route('leads.edit', $lead->id) }}">Edit</a>
                                                @endif
                                            </li>
                                            <li>
                                                @if(!$isHistorical)
                                                    
                                                    <form action="{{ route('leads.destroy', $lead->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="delete-leads-btn dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_leads_guard')) disabled @endif" data-lead-name="{{ $lead->title }}" >Delete</button>
                                                    </form>
                                                @endif
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td data-label="Lead Name">
                                    
                                    @if($lead->person)
                                    <a href="{{ route('leads.show', ['lead' => $lead->id, 'page' => request('page')]) }}" class="text-decoration-none">
                                        {{ trim($lead->person->first_name . ' ' . $lead->person->last_name) ?: '-' }}
                                    </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td data-label="Company">{{ optional($lead->organization)->name ?? '-' }}</td>

                                <td data-label="Lead Source">{{ optional($lead->leadSource)->name ?? '-' }}</td>
                                <td data-label="Status">{{ ucfirst(str_replace('_', ' ', $lead->status)) }}</td>
                                <td data-label="Owner">{{ optional($lead->owner)->name ?? '-' }}</td>

                                <td data-label="Priority">
                                    @if($lead->label === 'high')
                                        <span class="badge bg-success">High</span>
                                    @elseif($lead->label === 'normal')
                                        <span class="badge bg-info text-white">Normal</span>
                                    @elseif($lead->label === 'low')
                                        <span class="badge bg-warning text-dark">Low</span>
                                    @else
                                        <span class="badge bg-light text-dark">-</span>
                                    @endif
                                </td>
                                <td data-label="Product/Services">
                                    @if(!empty($lead->category_names))
                                        @foreach($lead->category_names as $categoryName)
                                            <div>{{ $categoryName }}</div>
                                        @endforeach
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td data-label="Created">
                                    {{ $lead->created_at->diffForHumans() }}
                                </td>
                                <td data-label="Updated">
                                    {{ $lead->updated_at->diffForHumans() }}
                                </td>

                            </tr>
                            
                        @empty
                            <tr><td colspan="10" class="text-center">No leads found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
                <div class="small text-muted">Showing {{ $leads->firstItem() ?? 0 }} to {{ $leads->lastItem() ?? 0 }} of {{ $leads->total() }} leads</div>
                <div class="pagination-custom text-center my-3">
                    <nav aria-label="Leads pagination">
                        <ul class="pagination justify-content-center gap-3 mb-0">
                            <li class="page-item {{ $leads->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $leads->previousPageUrl() ?: '#' }}" tabindex="-1" aria-disabled="{{ $leads->onFirstPage() ? 'true' : 'false' }}">&laquo; Previous</a>
                            </li>
                            <li class="page-item {{ $leads->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $leads->nextPageUrl() ?: '#' }}" aria-disabled="{{ $leads->hasMorePages() ? 'false' : 'true' }}">Next &raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'leadsPageSkeleton',
        'columns' => 10,
        'rows' => 10,
        'filterFields' => 6,
    ])
</div>
@endsection

<script src="{{asset('js/leads/leads.js')}}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select the existing GET form for leads index
    var form = document.querySelector('form[action="{{ route('leads.index') }}"]');
    if (!form) return;

    function debounce(fn, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() { fn.apply(context, args); }, wait);
        };
    }

    // Debounced submit (adjust delay as needed)
    var submitDebounced = debounce(function() { form.submit(); }, 450);

    // Fields to auto-submit on change/typing
    var fields = ['contact', 'label', 'lead_source', 'owner', 'start_date', 'end_date'];
    fields.forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        // For date inputs use 'change' so it triggers when a date is selected.
        var ev = (el.tagName.toLowerCase() === 'input' && el.type === 'date') ? 'change' : (el.tagName.toLowerCase() === 'input' ? 'input' : 'change');
        el.addEventListener(ev, submitDebounced);
    });

    // Date bounds: ensure end_date can't be earlier than start_date and vice-versa
    var startEl = document.getElementById('start_date');
    var endEl = document.getElementById('end_date');
    function formatDate(d) {
        // d is a Date object
        var y = d.getFullYear();
        var m = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        return y + '-' + m + '-' + day;
    }

    function dateFromString(s) {
        // s in YYYY-MM-DD
        if (!s) return null;
        var parts = s.split('-');
        if (parts.length !== 3) return null;
        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    }

    function adjustDateBounds() {
        if (!startEl || !endEl) return;
        var s = startEl.value;
        var e = endEl.value;

        if (s) {
            // enforce end to be strictly after start (min = start + 1 day)
            var sDate = dateFromString(s);
            var sPlus = new Date(sDate.getTime());
            sPlus.setDate(sPlus.getDate() + 1);
            var sPlusStr = formatDate(sPlus);
            endEl.min = sPlusStr;
            if (!e || e < sPlusStr) {
                // if end is not set or is before strictly-after-start, snap end to start+1
                endEl.value = sPlusStr;
                e = sPlusStr;
            }
        } else {
            endEl.removeAttribute('min');
        }

        // if (e) {
        //     // enforce start to be strictly before end (max = end - 1 day)
        //     var eDate = dateFromString(e);
        //     var eMinus = new Date(eDate.getTime());
        //     eMinus.setDate(eMinus.getDate() - 1);
        //     var eMinusStr = formatDate(eMinus);
        //     startEl.max = eMinusStr;
        //     if (!s || s > eMinusStr) {
        //         // if start not set or after allowed max, snap start to end-1
        //         startEl.value = eMinusStr;
        //         s = eMinusStr;
        //     }
        // } else {
        //     startEl.removeAttribute('max');
        // }
    }

    // Initialize bounds on load
    adjustDateBounds();

    // Keep bounds in sync and trigger search when dates change
    if (startEl) startEl.addEventListener('change', function() { adjustDateBounds(); submitDebounced(); });
    if (endEl) endEl.addEventListener('change', function() { adjustDateBounds(); submitDebounced(); });


    //Swal Alert for delete confirmation
    var buttons = document.querySelectorAll('.delete-leads-btn');
    var name = 'data-lead-name';
    attachDeleteHandlers(buttons, name); 

    // Auto-submit form when Lead Status dropdown changes
    var leadStatusDropdown = document.getElementById('lead_status');
    if (leadStatusDropdown) {
        leadStatusDropdown.addEventListener('change', function() {
            this.form.submit();
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleFiltersButton = document.getElementById('toggleFiltersButton');
    const filtersForm = document.getElementById('filtersForm');

    // Check localStorage to determine the initial state of the filters form
    if (localStorage.getItem('filtersVisible') === 'true') {
        filtersForm.style.display = 'block';
        toggleFiltersButton.textContent = 'Hide Filters';
    } else {
        filtersForm.style.display = 'none';
        toggleFiltersButton.textContent = 'Show Filters';
    }

    toggleFiltersButton.addEventListener('click', function() {
        if (filtersForm.style.display === 'none') {
            filtersForm.style.display = 'block';
            toggleFiltersButton.textContent = 'Hide Filters';
            localStorage.setItem('filtersVisible', 'true'); // Save state to localStorage
        } else {
            filtersForm.style.display = 'none';
            toggleFiltersButton.textContent = 'Show Filters';
            localStorage.setItem('filtersVisible', 'false'); // Save state to localStorage
        }
    });
});
</script>
