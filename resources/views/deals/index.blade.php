
@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/deals-index-custom.css?v=2">
<div class="container-fluid px-4 hub-page-wrap hub-page-wrap--crm">
    @php
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
    @endphp
    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="mb-0">Deals</h4>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <!-- Filter Dropdown -->
                <div class="dropdown d-inline-flex align-items-center">
                    <button class="btn btn-light border dropdown-toggle d-inline-flex align-items-center justify-content-center px-3 text-dark fw-semibold" type="button" id="dealViewDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="height: 38px; background:#ffffff !important; color:#1e293b !important; border:1px solid #cbd5e1 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.06); font-size:14px; border-radius: 6px;">
                        <i class="bi bi-funnel me-1 text-primary"></i> {{ request('view', in_array((int)$user->crm_role_type, [0, 1, 2], true) ? 'All Deals': 'My Deals') }}
                    </button>
                    <ul class="dropdown-menu shadow" aria-labelledby="dealViewDropdown" style="min-width: 260px;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" placeholder="Search" id="dealViewSearchInput">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header text-uppercase fw-bold small text-muted">Public Views</li>
                        <li><a class="dropdown-item @if(request('view') == 'All Deals' || !request('view')) active @endif" href="{{ route('deals.index', ['view' => 'All Deals']) }}">All Deals</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Closed Won') active @endif" href="{{ route('deals.index', ['view' => 'Closed Won']) }}">Closed Won</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Closed Lost') active @endif" href="{{ route('deals.index', ['view' => 'Closed Lost']) }}">Closed Lost</a></li>
                        
                        <li><a class="dropdown-item @if(request('view') == 'Open Deals') active @endif" href="{{ route('deals.index', ['view' => 'Open Deals']) }}">Open Deals</a></li>
                        
                        <li><a class="dropdown-item @if(request('view') == 'My Deals') active @endif" href="{{ route('deals.index', ['view' => 'My Deals']) }}">My Deals</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Recently Created Deals') active @endif" href="{{ route('deals.index', ['view' => 'Recently Created Deals']) }}">Recently Created Deals</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Recently Modified Deals') active @endif" href="{{ route('deals.index', ['view' => 'Recently Modified Deals']) }}">Recently Modified Deals</a></li>
                    </ul>
                </div>
                @if($isHistorical)
                    <a class="btn btn-custom d-inline-flex align-items-center justify-content-center px-3 gap-2 fw-semibold disabled" href="javascript:void(0)" title="Creating deals is disabled for historical years" style="height: 38px; padding: 0 16px; font-size: 14px; border-radius: 6px;"><i class="bi bi-plus-lg"></i> Add deals</a>
                @else
                    <a href="{{ route('deals.create') }}" class="btn btn-custom d-inline-flex align-items-center justify-content-center px-3 gap-2 fw-semibold @if(!auth()->user()->hasCrmPermission('create_crm_deals_guard')) disabled @endif" style="height: 38px; padding: 0 16px; font-size: 14px; border-radius: 6px;"><i class="bi bi-plus-lg"></i> Add deals</a>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <!-- Search/Filter Bar -->
            <form method="GET" action="{{ route('deals.index') }}" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="title" class="form-label mb-1">Search</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ request('title') }}" placeholder="Search Title, Contact, Company">
                    </div>
                    <div class="col-md-2">
                        <label for="label" class="form-label mb-1">Priority</label>
                        <select name="label" id="label" class="form-select">
                            <option value="">All</option>
                            @foreach($priorities ?? [] as $priority)
                                <option value="{{ $priority['value'] }}" @if(request('label') == $priority['value']) selected @endif>{{ $priority['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="lead_source" class="form-label mb-1">Lead Source</label>
                        <select name="lead_source" id="lead_source" class="form-select">
                            <option value="">All</option>
                            @foreach($leadSources ?? [] as $source)
                                <option value="{{ $source->id }}" @if(request('lead_source') == $source->id) selected @endif>{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="stage" class="form-label mb-1">Deal Stage</label>
                        <select name="stage" id="stage" class="form-select">
                            <option value="">All</option>
                            @foreach($stages ?? [] as $stage)
                                <option value="{{ $stage->id }}" @if((string) request('stage') === (string) $stage->id || request('stage') == $stage->name) selected @endif>{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label for="start_date" class="form-label mb-1">From</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-1">
                        <label for="end_date" class="form-label mb-1">To</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="overflow:visible;">
                    <thead class="custom-display d-none d-md-table-row-group">
                        <tr>
                            <th></th>
                            <th>Created</th>
                            <th>Title</th>
                            <th>Priority</th>
                            <th>Amount</th>
                            <th>Company</th>
                            <th>Contact Name</th>
                            <th>Deal Stage</th>
                            <th>Owner</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deals as $deal)
                            <tr class="d-none d-md-table-row">
                                <td style="overflow:visible; position:relative;">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button" id="dealActionsDropdown{{ $deal->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2" aria-labelledby="dealActionsDropdown{{ $deal->id }}" style="min-width:180px;">
                                            <li>
                                                <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('view_crm_deals_guard')) disabled @endif" href="{{ route('deals.show', $deal->id) }}">View</a>
                                            </li>
                                            <li>
                                                @if($isHistorical)
                                                    <button class="dropdown-item px-4 py-2 fs-6 disabled" type="button" disabled title="Editing disabled for historical years">Edit</button>
                                                @else
                                                    <a class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('edit_crm_deals_guard')) disabled @endif" href="{{ route('deals.edit', $deal->id) }}">Edit</a>
                                                @endif
                                            </li>
                                            <li>
                                                @if($isHistorical)
                                                    <button class="dropdown-item px-4 py-2 fs-6 text-danger disabled" type="button" disabled title="Deleting disabled for historical years">Delete</button>
                                                @else
                                                    <form action="{{ route('deals.destroy', $deal->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_deals_guard')) disabled @endif">Delete</button>
                                                    </form>
                                                @endif
                                            </li>
                                            @if(strtolower($deal->status) !== 'closed won' && strtolower($deal->status) !== 'closed lost')
                                                <li>
                                                    <form action="{{ route('deals.won', $deal->id) }}" method="POST" onsubmit="return confirm('Mark as Won?')">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('won_crm_deals_guard')) disabled @endif">Mark as Won</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('deals.lost', $deal->id) }}" method="POST" onsubmit="return confirm('Mark as Lost?')">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('lost_crm_deals_guard')) disabled @endif">Mark as Lost</button>
                                                    </form>
                                                </li>
                                            @else
                                                <li>
                                                    <form action="{{ route('deals.reopen', $deal->id) }}" method="POST" onsubmit="return confirm('Reopen this deal?')">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item px-4 py-2 fs-6 @if(!auth()->user()->hasCrmPermission('reopen_crm_deals_guard')) disabled @endif">Reopen</button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($deal->created_at)->diffForHumans() }}</td>
                                <td><a class="text-decoration-none" href="{{ route('deals.show', $deal->id) }}">{{ $deal->title }}</a></td>
                                <td>
                                    @if($deal->label === 'high')
                                        <span class="badge bg-success">High</span>
                                    @elseif($deal->label === 'normal')
                                        <span class="badge bg-info text-white">Normal</span>
                                    @elseif($deal->label === 'low')
                                        <span class="badge bg-warning text-dark">Low</span>
                                    @else
                                        <span class="badge bg-light text-dark">-</span>
                                    @endif
                                </td>
                                <td>	{{ \App\Helpers\MoneyFormatter::format($deal->amount ?? 0) }}</td>
                                <td>{{ optional($deal->organization)->name ?? ($deal->organization_name ?? '-') }}</td>
                                <td>
                                    @if(optional($deal->person)->first_name)
                                        {{ optional($deal->person)->first_name }} {{ optional($deal->person)->last_name }}
                                    @elseif($deal->person_name)
                                        {{ $deal->person_name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $deal->stage_name ?? '-' }}</td>
                                <td>{{ optional($deal->owner)->name ?? '-' }}</td>
                            </tr>
                            <!-- Mobile Card -->
                            <tr class="d-md-none">
                                <td colspan="10">
                                    <div class="card mb-2 shadow-sm">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold">{{ $deal->title }}</span>
                                                <span>
                                                    @if($deal->label === 'high')
                                                        <span class="badge bg-success">High</span>
                                                    @elseif($deal->label === 'normal')
                                                        <span class="badge bg-info text-white">Normal</span>
                                                    @elseif($deal->label === 'low')
                                                        <span class="badge bg-warning text-dark">Low</span>
                                                    @else
                                                        <span class="badge bg-light text-dark">-</span>
                                                    @endif
                                                </span>
                                                <div class="dropdown">
                                                    <button class="btn btn-link p-0 text-dark" type="button" id="dealActionsDropdownMobile{{ $deal->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots fs-5"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dealActionsDropdownMobile{{ $deal->id }}">
                                                        <li>
                                                            <a class="dropdown-item @if(!auth()->user()->hasCrmPermission('view_crm_deals_guard')) disabled @endif" href="{{ route('deals.show', $deal->id) }}">View</a>
                                                        </li>
                                                        <li>
                                                            @if($isHistorical)
                                                                <button class="dropdown-item disabled" type="button" disabled title="Editing disabled for historical years">Edit</button>
                                                            @else
                                                                <a class="dropdown-item @if(!auth()->user()->hasCrmPermission('edit_crm_deals_guard')) disabled @endif" href="{{ route('deals.edit', $deal->id) }}">Edit</a>
                                                            @endif
                                                        </li>
                                                        <li>
                                                            @if($isHistorical)
                                                                <button class="dropdown-item text-muted disabled" type="button" disabled title="Deleting disabled for historical years">Delete</button>
                                                            @else
                                                                <form action="{{ route('deals.destroy', $deal->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_deals_guard')) disabled @endif">Delete</button>
                                                                </form>
                                                            @endif
                                                        </li>
                                                        @if(strtolower($deal->status) !== 'closed won' && strtolower($deal->status) !== 'closed lost')
                                                            <li>
                                                                <form action="{{ route('deals.won', $deal->id) }}" method="POST" onsubmit="return confirm('Mark as Won?')">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item @if(!auth()->user()->hasCrmPermission('won_crm_leads_guard')) disabled @endif">Mark as Won</button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form action="{{ route('deals.lost', $deal->id) }}" method="POST" onsubmit="return confirm('Mark as Lost?')">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item @if(!auth()->user()->hasCrmPermission('lost_crm_leads_guard')) disabled @endif">Mark as Lost</button>
                                                                </form>
                                                            </li>
                                                        @else
                                                            <li>
                                                                <form action="{{ route('deals.reopen', $deal->id) }}" method="POST" onsubmit="return confirm('Reopen this deal?')">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item @if(!auth()->user()->hasCrmPermission('reopen_crm_leads_guard')) disabled @endif">Reopen</button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="mb-1"><span class="text-muted">Created:</span> {{ \Carbon\Carbon::parse($deal->created_at)->diffForHumans() }}</div>
                                            <div class="mb-1"><span class="text-muted">Value:</span> 	{{ \App\Helpers\MoneyFormatter::format($deal->amount ?? 0) }}</div>
                                            <div class="mb-1"><span class="text-muted">Organization:</span> {{ optional($deal->organization)->name ?? ($deal->organization_name ?? '-') }}</div>
                                            <div class="mb-1"><span class="text-muted">Contact:</span>
                                                @if(optional($deal->person)->first_name)
                                                    {{ optional($deal->person)->first_name }} {{ optional($deal->person)->last_name }}
                                                @elseif($deal->person_name)
                                                    {{ $deal->person_name }}
                                                @else
                                                    -
                                                @endif
                                            </div>
                                            <div class="mb-1"><span class="text-muted">Stage:</span> {{ $deal->stage_name ?? '-' }}</div>
                                            <div class="mb-1"><span class="text-muted">Owner:</span> {{ optional($deal->owner)->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">No deals found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $deals->links() }}
            </div>
        </div>
    </div>

    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'dealsPageSkeleton',
        'columns' => 9,
        'rows' => 10,
        'filterFields' => 6,
    ])
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('dealViewSearchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                var filter = searchInput.value.toLowerCase();
                var dropdown = searchInput.closest('ul');
                var items = dropdown.querySelectorAll('a.dropdown-item');
                items.forEach(function(item) {
                    var text = item.textContent || item.innerText;
                    if (text.toLowerCase().indexOf(filter) > -1) {
                        item.parentElement.style.display = '';
                    } else {
                        item.parentElement.style.display = 'none';
                    }
                });
            });
        }

        // Auto-submit behavior similar to leads index
        var form = document.querySelector('form[action="{{ route('deals.index') }}"]');
        if (!form) return;
        function debounce(fn, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() { fn.apply(context, args); }, wait);
            };
        }
        var submitDebounced = debounce(function() { form.submit(); }, 450);
        var fields = ['title', 'label', 'lead_source', 'stage', 'start_date', 'end_date'];
        fields.forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            var ev = (el.tagName.toLowerCase() === 'input' && el.type === 'date') ? 'change' : (el.tagName.toLowerCase() === 'input' ? 'input' : 'change');
            el.addEventListener(ev, submitDebounced);
        });

        // Date bounds logic: require end > start (end min = start + 1 day)
        var startEl = document.getElementById('start_date');
        var endEl = document.getElementById('end_date');
        function formatDate(d) {
            var y = d.getFullYear();
            var m = ('0' + (d.getMonth() + 1)).slice(-2);
            var day = ('0' + d.getDate()).slice(-2);
            return y + '-' + m + '-' + day;
        }
        function dateFromString(s) {
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
                var sDate = dateFromString(s);
                var sPlus = new Date(sDate.getTime());
                sPlus.setDate(sPlus.getDate() + 1);
                var sPlusStr = formatDate(sPlus);
                endEl.min = sPlusStr;
                if (!e || e < sPlusStr) { endEl.value = sPlusStr; e = sPlusStr; }
            } else {
                endEl.removeAttribute('min');
            }
            if (e) {
                var eDate = dateFromString(e);
                var eMinus = new Date(eDate.getTime());
                eMinus.setDate(eMinus.getDate() - 1);
                var eMinusStr = formatDate(eMinus);
                startEl.max = eMinusStr;
                if (!s || s > eMinusStr) { startEl.value = eMinusStr; s = eMinusStr; }
            } else {
                startEl.removeAttribute('max');
            }
        }
        adjustDateBounds();
        if (startEl) startEl.addEventListener('change', function() { adjustDateBounds(); submitDebounced(); });
        if (endEl) endEl.addEventListener('change', function() { adjustDateBounds(); submitDebounced(); });
    });
</script>
@endpush
