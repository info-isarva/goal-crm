
@extends('layouts.app')

@section('content')
<link href="{{asset('css/deals-pipeline-custom.css') }}" rel="stylesheet">
<style>
    .deal-card .view-more {
        display: none;
        position: absolute;
        bottom: 0;
        width: 100%;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: #bdbdbdec;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 4px 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s ease, transform 0.3s ease;
        font-size: 0.98rem;
    }
    .deal-card:hover .view-more {
        display: block;
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    .deal-card .view-more a.move-to-link {
        font-weight: 600;
        color: #007bff !important;
        cursor: pointer;
    }
</style>
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--pipeline" style="min-height: 100vh;">
    @include('partials.skeletons.deals_pipeline')
    @php
    $selectedFyId = session('selected_financial_year', null);
    $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
    $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
    @endphp
    <div class="d-flex flex-column mb-3" style="gap:12px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center w-100" style="gap:12px;">
            <h2 class="mb-0">Deals Pipeline</h2>

            <div class="d-flex gap-2 flex-wrap align-items-center">


                <!-- Filter Dropdown (placed before the form to avoid overlap on small screens) -->
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle px-3 py-2 text-dark fw-semibold" type="button" id="dealViewDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="background:#ffffff !important; color:#1e293b !important; border:1px solid #cbd5e1 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.06); font-size:14px;">
                        <i class="bi bi-funnel me-1 text-primary"></i> {{ request('view', in_array((int)$user->crm_role_type, [0, 1, 2], true) ? 'All Deals': 'My Deals') }}
                    </button>
                    <ul class="dropdown-menu shadow" aria-labelledby="dealViewDropdown" style="min-width: 260px;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" placeholder="Search" id="dealViewSearchInput">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header text-uppercase fw-bold small text-muted">Public Views</li>
                        <li><a class="dropdown-item @if(request('view') == 'All Deals' || !request('view')) active @endif" href="{{ request()->fullUrlWithQuery(['view' => 'All Deals']) }}">All Deals</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Closed Won') active @endif" href="{{ request()->fullUrlWithQuery(['view' => 'Closed Won']) }}">Closed Won</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Closed Lost') active @endif" href="{{ request()->fullUrlWithQuery(['view' => 'Closed Lost']) }}">Closed Lost</a></li>

                        <li><a class="dropdown-item @if(request('view') == 'Open Deals') active @endif" href="{{ request()->fullUrlWithQuery(['view' => 'Open Deals']) }}">Open Deals</a></li>

                        <li><a class="dropdown-item @if(request('view') == 'My Deals') active @endif" href="{{ request()->fullUrlWithQuery(['view' => 'My Deals']) }}">My Deals</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Recently Created Deals') active @endif" href="{{ request()->fullUrlWithQuery(['view' => 'Recently Created Deals']) }}">Recently Created Deals</a></li>
                        <li><a class="dropdown-item @if(request('view') == 'Recently Modified Deals') active @endif" href="{{ request()->fullUrlWithQuery(['view' => 'Recently Modified Deals']) }}">Recently Modified Deals</a></li>
                    </ul>
                </div>

                @if(!$isHistorical)
                    <a href="{{ route('deals.create') }}" class="btn btn-custom d-flex align-items-center" style="font-weight: 500; font-size: 1rem; padding: 8px 20px; border-radius: 6px;">
                        <i class="fa fa-plus" style="margin-right: 6px;"></i> Add Deal
                    </a>
                @endif
            </div>
        </div>
        <div class="row g-2">
            <form id="pipeline-search-wrapper" method="GET" action="{{ route('deals.index') }}" class="w-100">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm-6 col-md-3">
                        <input id="pipelineContactSearch" name="title" type="search" class="form-control" placeholder="Search Title, Contact, Company" value="{{ request('title') }}">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <!-- <label for="category" class="form-label mb-1">Product/Services</label> -->
                        <select name="category" id="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Product/Services</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @if(request('category') == $category->id) selected @endif>{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- <div class="col-6 col-sm-4 col-md-2">
                        <select name="label" id="label" class="form-select">
                            <option value="">Priority</option>
                            @foreach($priorities ?? [] as $p)
                                <option value="{{ $p['value'] }}" @if(request('label') == $p['value']) selected @endif>{{ $p['label'] }}</option>
                            @endforeach
                        </select>
                    </div> -->
                    <div class="col-xl-2 col-md-4">
                        <select name="lead_source" id="lead_source" class="form-select">
                            <option value="">All Lead Source</option>
                            @foreach($leadSources ?? [] as $ls)
                                <option value="{{ $ls->id }}" @if(request('lead_source') == $ls->id) selected @endif>{{ $ls->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                        <!-- <small class="text-muted d-block d-md-none">Format: dd-mm-yyyy</small> -->
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                        <!-- <small class="text-muted d-block d-md-none">Format: dd-mm-yyyy</small> -->
                    </div>

                </div>
            </form>
        </div>
    </div>
    <div id="pipeline-board" style="display: flex; overflow-x: auto; gap: 24px; padding-bottom: 16px;">
            <!-- Mobile scroll buttons -->
            <div id="pipeline-scroll-left" class="pipeline-scroll-btn" style="display:none;position:fixed;left:8px;z-index:9999;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.15);width:40px;height:40px;align-items:center;justify-content:center;">
                <i class="bi bi-arrow-left-square" style="font-size:2rem;line-height:1;"></i>
            </div>
            <div id="pipeline-scroll-right" class="pipeline-scroll-btn" style="display:none;position:fixed;right:8px;z-index:9999;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.15);width:40px;height:40px;align-items:center;justify-content:center;">
                <i class="bi bi-arrow-right-square" style="font-size:2rem;line-height:1;"></i>
            </div>
        @foreach($stages as $stage)
        @php
        $stageDeals = $deals->filter(function($deal) use ($stage) {
        return $deal->stage_name == $stage->name;
        });
        // compute colors once to avoid complex Blade expressions in style attributes
        $stageBorderColor = $loop->last ? '#dc3545' : ($loop->index == 0 ? '#007bff' : ($loop->index == 1 ? '#17a2b8' : ($loop->index == 2 ? '#ffc107' : '#28a745')));
        $stageHeaderColor = $loop->last ? '#dc3545' : '#333';
        $stageBadgeBg = $stageBorderColor;
        @endphp
        <div class="pipeline-column" style="min-width: 320px; min-height: 820px; background: #f8f9fa; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; flex-direction: column;">
            <div class="pipeline-header" style="padding: 16px 16px 8px 16px; border-bottom: 2px solid {{ $stageBorderColor }}; background: #fff; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <span style="font-weight: 600; font-size: 1.1rem; color: {{ $stageHeaderColor }};">{{ $stage->name }} ({{$stage->probability}}%)</span>
                <span class="badge" style="float:right; background: {{ $stageBadgeBg }}; color: #fff; font-size: 0.95rem;">{{ $stageDeals->count() }}</span>
            </div>
            <div class="pipeline-body deal-stage" data-stage-id="{{ $stage->id }}" style="padding: 16px; max-height: 750px; overflow-y: auto; flex: 1;">
                @forelse($stageDeals as $deal)
                @php
                $dealBorderColor = $loop->parent->last ? '#dc3545' : ($loop->parent->index == 0 ? '#007bff' : ($loop->parent->index == 1 ? '#17a2b8' : ($loop->parent->index == 2 ? '#ffc107' : '#28a745')));
                @endphp
                <div class="deal-card mb-3 position-relative @if(strtolower($stage->name) === 'closed won') deal-card-locked @endif" data-deal-id="{{ $deal->id }}" data-contact="{{ $deal->contact_name ?? '-' }}" data-organization="{{ optional($deal->organization)->name ?? $deal->organization_name ?? '' }}" data-amount="{{ $deal->amount ?? '' }}" data-close-date="{{ $deal->close_date ? \Carbon\Carbon::parse($deal->close_date)->format('Y-m-d') : '' }}" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.07); padding: 12px 16px; margin-bottom: 12px; border-left: 4px solid {{ $dealBorderColor }}; text-decoration: none; display: block; cursor: pointer;">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('deals.show', $deal->id) }}" style="font-weight: 600; font-size: 1rem; color: #222; text-decoration:none;">{{ $deal->title }}</a>
                        <div class="dropdown">
                            <button class="btn btn-link p-0 text-dark" type="button" id="dealActionsDropdownPipeline{{ $deal->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical fs-5"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow rounded-3 py-2 mt-2" aria-labelledby="dealActionsDropdownPipeline{{ $deal->id }}" style="min-width:160px;">
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
                                    <form action="{{ route('deals.destroy', $deal->id) }}" method="POST" >
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="delete-deals-btn dropdown-item px-4 py-2 fs-6 text-danger @if(!auth()->user()->hasCrmPermission('delete_crm_deals_guard')) disabled @endif" data-deals-name="{{$deal->title}}">Delete</button>
                                    </form>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div style="font-size: 0.95rem; color: #666; margin-top: 2px;">
                        <span class="text-muted">Company:</span>
                        @if(optional($deal->organization)->id)
                            <a href="{{ route('organizations.show', optional($deal->organization)->id) }}" class="text-decoration-none">{{ optional($deal->organization)->name ?? $deal->organization_name ?? '-' }}</a>
                        @else
                            {{ optional($deal->organization)->name ?? $deal->organization_name ?? '-' }}
                        @endif
                        <br>
                        <span class="text-muted">Contact:</span> {{ $deal->contact_name ?? '-' }}<br>
                        <span class="text-muted">Owner:</span> {{ $deal->owner_name ?? '-' }}<br>
                        <span class="text-muted">Amount:</span> <span style="font-weight: 600; color: #007bff;">{{ \App\Helpers\MoneyFormatter::format($deal->amount) ?? '-' }}</span><br>
                        <span class="text-muted">Close Date:</span>
                        @php
                        $closeDateObj = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : null;
                        $isExpired = $closeDateObj && $closeDateObj->lt(\Carbon\Carbon::today());
                        @endphp
                        <span style="font-weight: 600; color: {{ $isExpired ? '#dc3545' : '#28a745' }};">
                            {{ $closeDateObj ? $closeDateObj->format('d M Y') : '-' }}
                        </span>
                    </div>
                    <div class="view-more text-center" >
                        <a href="{{ route('deals.show', $deal->id) }}" class="text-decoration-none">View More</a><br>
                        @if(strtolower($stage->name) !== 'closed won') <a href="#" class="text-decoration-none text-primary move-to-link mt-1 d-inline-block" data-deal-id="{{ $deal->id }}">Move To</a>@endif
                    </div>
                </div>
                @empty
                <span class="text-muted">No deals in this stage.</span>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>



<!-- Modal for reason for loss -->
<div class="modal fade" id="reasonForLossModal" tabindex="-1" aria-labelledby="reasonForLossModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonForLossModalLabel">Reason for Loss</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea id="reasonForLossInput" class="form-control" rows="3" placeholder="Enter reason for loss" required></textarea>
                <div id="reasonForLossError" class="text-danger mt-2" style="display:none;">Reason is required.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="submitReasonForLoss">Submit</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

            </div>
        </div>
    </div>
</div>
<!-- Modal for Closed Won details -->
<div class="modal fade" id="closedWonModal" tabindex="-1" aria-labelledby="closedWonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closedWonModalLabel">Closed Won Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" id="closedWonAmount" class="form-control" placeholder="Enter deal amount">
                    <div id="closedWonAmountError" class="text-danger mt-2" style="display:none;">Amount is required and must be numeric.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Close Date</label>
                    <input type="date" id="closedWonDate" class="form-control">
                    <div id="closedWonDateError" class="text-danger mt-2" style="display:none;">Close date is required.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="submitClosedWon">Submit</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Move To Stage Modal -->
<div class="modal fade" id="moveToStageModal" tabindex="-1" aria-labelledby="moveToStageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moveToStageModalLabel">Move Deal To Stage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="moveToStageForm">
                    <input type="hidden" id="moveDealId" name="deal_id" value="">
                    <div class="mb-3">
                        <label for="moveStageSelect" class="form-label">Select Stage</label>
                        <select class="form-select" id="moveStageSelect" name="stage_id" required>
                            <option value="">Select Stage</option>
                            @foreach($stages as $stage)
                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="moveStageBtn">Move</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let moveDealId = null;
    const moveModal = new bootstrap.Modal(document.getElementById('moveToStageModal'));
    document.querySelectorAll('.move-to-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            moveDealId = this.getAttribute('data-deal-id');
            document.getElementById('moveDealId').value = moveDealId;
            document.getElementById('moveStageSelect').value = '';
            moveModal.show();
        });
    });

    document.getElementById('moveStageBtn').addEventListener('click', function() {
        const stageId = document.getElementById('moveStageSelect').value;
        if (!moveDealId || !stageId) {
            alert('Please select a stage.');
            return;
        }
        // Get stage name for logic
        const stageName = document.getElementById('moveStageSelect').options[document.getElementById('moveStageSelect').selectedIndex].text.trim().toLowerCase();
        if (stageName === 'closed won') {
            // Prompt for amount and close date
            let amount = prompt('Enter amount for Closed Won:');
            if (!amount || isNaN(amount)) {
                alert('Amount is required and must be numeric.');
                return;
            }
            let closeDate = prompt('Enter close date (YYYY-MM-DD):');
            if (!closeDate || !/^\d{4}-\d{2}-\d{2}$/.test(closeDate)) {
                alert('Valid close date is required (YYYY-MM-DD).');
                return;
            }
            fetch("{{ route('deals.updateStage') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    deal_id: moveDealId,
                    stage_id: stageId,
                    amount: amount,
                    close_date: closeDate
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success === true) {
                    moveModal.hide();
                    let msg = document.getElementById('pipeline-success-msg');
                    if (!msg) {
                        msg = document.createElement('div');
                        msg.id = 'pipeline-success-msg';
                        msg.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                        msg.style.zIndex = 9999;
                        document.body.appendChild(msg);
                    }
                    msg.innerText = 'Deal stage changed to Closed Won!';
                    msg.style.display = 'block';
                    setTimeout(function() {
                        msg.style.display = 'none';
                    }, 1200);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                } else {
                    alert(data.message || 'Failed to move deal.');
                }
            })
            .catch(() => alert('Failed to move deal.'));
        } else if (stageName === 'closed lost') {
            // Prompt for reason
            let reason = prompt('Enter reason for Closed Lost:');
            if (!reason) {
                alert('Reason is required.');
                return;
            }
            fetch("{{ route('deals.updateStage') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    deal_id: moveDealId,
                    stage_id: stageId,
                    reason_for_loss: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success === true) {
                    moveModal.hide();
                    let msg = document.getElementById('pipeline-success-msg');
                    if (!msg) {
                        msg = document.createElement('div');
                        msg.id = 'pipeline-success-msg';
                        msg.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                        msg.style.zIndex = 9999;
                        document.body.appendChild(msg);
                    }
                    msg.innerText = 'Deal stage changed to Closed Lost!';
                    msg.style.display = 'block';
                    setTimeout(function() {
                        msg.style.display = 'none';
                    }, 1200);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                } else {
                    alert(data.message || 'Failed to move deal.');
                }
            })
            .catch(() => alert('Failed to move deal.'));
        } else {
            // Normal move
            fetch("{{ route('deals.updateStage') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    deal_id: moveDealId,
                    stage_id: stageId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success === true) {
                    moveModal.hide();
                    let msg = document.getElementById('pipeline-success-msg');
                    if (!msg) {
                        msg = document.createElement('div');
                        msg.id = 'pipeline-success-msg';
                        msg.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                        msg.style.zIndex = 9999;
                        document.body.appendChild(msg);
                    }
                    msg.innerText = 'Deal stage changed!';
                    msg.style.display = 'block';
                    setTimeout(function() {
                        msg.style.display = 'none';
                    }, 1200);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                } else {
                    alert(data.message || 'Failed to move deal.');
                }
            })
            .catch(() => alert('Failed to move deal.'));
        }
    });
});
</script>
@endsection
@push('scripts')
<style>
@media (max-width: 767.98px) {
    .pipeline-scroll-btn { display: flex !important; }
}
</style>
<!-- Include SortableJS or other drag-and-drop library here -->
<script src="{{asset('js/Sortable.min.js')}}"></script>
<script>
    //Quick attach: delegated search handler so live filtering works even if other scripts fail or elements move

    (function() {
        function runPipelineFilter(q) {
            var cards = document.querySelectorAll('.deal-card');
            var shown = 0,
                hidden = 0;
            cards.forEach(function(card) {
                var contact = (card.getAttribute('data-contact') || '').toLowerCase();
                var org = (card.getAttribute('data-organization') || '').toLowerCase();
                var title = (card.querySelector('a') ? card.querySelector('a').innerText : '').toLowerCase();
                if (!q) {
                    card.style.display = '';
                    shown++;
                } else if (contact.indexOf(q) !== -1 || title.indexOf(q) !== -1 || org.indexOf(q) !== -1) {
                    card.style.display = '';
                    shown++;
                } else {
                    card.style.display = 'none';
                    hidden++;
                }
            });
            // debug
            // console.log('pipeline search results - shown:', shown, 'hidden:', hidden);
        }

        var debounceTimer = null;
        // delegated handler - listens for input events on the document
        document.addEventListener('input', function(e) {
            try {
                if (!e.target || e.target.id !== 'pipelineContactSearch') return;
                var val = (e.target.value || '').trim().toLowerCase();
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    runPipelineFilter(val);
                }, 200);
            } catch (err) {
                console.error('pipeline delegated search error', err);
            }
        }, true);

        // run initial filter if input already has value
        document.addEventListener('DOMContentLoaded', function() {
            try {
                var input = document.getElementById('pipelineContactSearch');
                if (input && input.value) {
                    runPipelineFilter((input.value || '').trim().toLowerCase());
                }
            } catch (err) {
                console.error('pipeline initial filter error', err);
            }
        });
    })();


    // Click-and-drag horizontal scrolling for pipeline board
    (function() {
        var board = document.getElementById('pipeline-board');
        var isDragging = false;
        var startX, scrollLeft;
        board.addEventListener('mousedown', function(e) {
            if (e.button !== 0) return; // Only left mouse button
            isDragging = true;
            board.classList.add('dragging');
            startX = e.pageX - board.offsetLeft;
            scrollLeft = board.scrollLeft;
            document.body.style.userSelect = 'none';
        });
        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            var x = e.pageX - board.offsetLeft;
            var walk = (x - startX);
            board.scrollLeft = scrollLeft - walk;
        });
        document.addEventListener('mouseup', function() {
            isDragging = false;
            board.classList.remove('dragging');
            document.body.style.userSelect = '';
        });
    })();
    // Prevent text selection while auto-scrolling
    function setUserSelectNone(enable) {
        document.body.style.userSelect = enable ? 'none' : '';
        document.body.style.webkitUserSelect = enable ? 'none' : '';
        document.body.style.msUserSelect = enable ? 'none' : '';
    }
    // Auto-scroll during drag and mouse move near screen edge
    (function() {
        var board = document.getElementById('pipeline-board');
        var scrollSpeed = 12; // px per frame for smoothness
        var edgeThreshold = 80; // px from edge to start scrolling
        var scrollDirection = null;
        var isScrolling = false;

        // function autoScroll() {
        //     if (!scrollDirection) return;
        //     if (scrollDirection === 'left') {
        //         board.scrollLeft -= scrollSpeed;
        //     } else if (scrollDirection === 'right') {
        //         board.scrollLeft += scrollSpeed;
        //     }
        //     if (isScrolling) {
        //         requestAnimationFrame(autoScroll);
        //     }
        // }

        function startAutoScroll(direction) {
            if (isScrolling && scrollDirection === direction) return;
            scrollDirection = direction;
            isScrolling = true;
            requestAnimationFrame(autoScroll);
        }

        function stopAutoScroll() {
            isScrolling = false;
            scrollDirection = null;
        }
        // Drag auto-scroll
        document.addEventListener('dragover', function(e) {
            var rect = board.getBoundingClientRect();
            if (e.clientX - rect.left < edgeThreshold) {
                startAutoScroll('left');
            } else if (rect.right - e.clientX < edgeThreshold) {
                startAutoScroll('right');
            } else {
                stopAutoScroll();
            }
        });
        document.addEventListener('dragleave', stopAutoScroll);
        document.addEventListener('drop', stopAutoScroll);

        // Mouse move auto-scroll
        document.addEventListener('mousemove', function(e) {
            var windowWidth = window.innerWidth;
            if (e.clientX < edgeThreshold) {
                startAutoScroll('left');
                setUserSelectNone(true);
            } else if (windowWidth - e.clientX < edgeThreshold) {
                startAutoScroll('right');
                setUserSelectNone(true);
            } else {
                stopAutoScroll();
                setUserSelectNone(false);
            }
        });
        document.addEventListener('mouseleave', function() {
            stopAutoScroll();
            setUserSelectNone(false);
        });
    })();

    // Helper to get stage name by id
    function getStageNameById(stageId) {
        var stageMap = {};
        @foreach($stages as $stage)
        stageMap['{{ $stage->id }}'] = @json($stage -> name);
        @endforeach
        return stageMap[stageId];
    }

    var lastDrag = null;
    var pendingRevert = null;
    var pendingClosedWon = null;
    var modal = new bootstrap.Modal(document.getElementById('reasonForLossModal'));
    var submitBtn = document.getElementById('submitReasonForLoss');
    var cancelBtn = document.querySelector('#reasonForLossModal .btn-secondary');
    var reasonInput = document.getElementById('reasonForLossInput');
    var reasonError = document.getElementById('reasonForLossError');

    function handleRevertCard() {
        if (pendingRevert && pendingRevert.dealCard && pendingRevert.lastDrag) {
            var from = pendingRevert.lastDrag.from;
            var oldIndex = pendingRevert.lastDrag.oldIndex;
            from.insertBefore(pendingRevert.dealCard, from.children[oldIndex] || null);
        }
        pendingRevert = null;
    }

    submitBtn.addEventListener('click', function() {
        var reason = reasonInput.value.trim();
        if (!reason) {
            reasonError.style.display = 'block';
            return;
        }
        reasonError.style.display = 'none';
        modal.hide();
        if (pendingRevert) {
            // AJAX call to update deal stage with reason
            fetch("{{ route('deals.updateStage') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        deal_id: pendingRevert.dealId,
                        stage_id: pendingRevert.newStageId,
                        reason_for_loss: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success === true) {
                        let msg = document.getElementById('pipeline-success-msg');
                        if (!msg) {
                            msg = document.createElement('div');
                            msg.id = 'pipeline-success-msg';
                            msg.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                            msg.style.zIndex = 9999;
                            document.body.appendChild(msg);
                        }
                        msg.innerText = 'Deal stage changed from "' + data.old_stage + '" to "' + data.new_stage + '"!';
                        msg.style.display = 'block';
                        setTimeout(function() {
                            msg.style.display = 'none';
                        }, 1200);
                        setTimeout(function() {
                            window.location.reload();
                        }, 1200);
                    } else {
                        alert('Failed to update stage');
                    }
                })
                .catch(() => alert('Error updating stage'));
        }
        pendingRevert = null;
    });
    cancelBtn.addEventListener('click', function() {
        modal.hide();
        handleRevertCard();
    });

    // Closed Won modal submit handling
    var closedWonModalEl = document.getElementById('closedWonModal');
    var closedWonModal = new bootstrap.Modal(closedWonModalEl);
    var submitClosedWonBtn = document.getElementById('submitClosedWon');
    var cancelClosedWonBtn = closedWonModalEl.querySelector('.btn-secondary');
    submitClosedWonBtn && submitClosedWonBtn.addEventListener('click', function() {
        var amt = document.getElementById('closedWonAmount').value.trim();
        var cdate = document.getElementById('closedWonDate').value.trim();
        var amtErr = document.getElementById('closedWonAmountError');
        var dateErr = document.getElementById('closedWonDateError');
        var valid = true;
        if (!amt || isNaN(amt)) {
            amtErr.style.display = 'block';
            valid = false;
        } else {
            amtErr.style.display = 'none';
        }
        if (!cdate) {
            dateErr.style.display = 'block';
            valid = false;
        } else {
            dateErr.style.display = 'none';
        }
        if (!valid) return;
        closedWonModal.hide();
        if (pendingClosedWon) {
            fetch("{{ route('deals.updateStage') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    deal_id: pendingClosedWon.dealId,
                    stage_id: pendingClosedWon.newStageId,
                    amount: amt,
                    close_date: cdate
                })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Failed to update stage');
                }
            }).catch(() => alert('Error updating stage'));
        }
    });

    // Cancel handler: revert card back to its previous container/position
    cancelClosedWonBtn && cancelClosedWonBtn.addEventListener('click', function() {
        closedWonModal.hide();
        if (pendingClosedWon && pendingClosedWon.dealCard && pendingClosedWon.lastDrag) {
            var from = pendingClosedWon.lastDrag.from;
            var oldIndex = pendingClosedWon.lastDrag.oldIndex;
            // insert back to original position
            from.insertBefore(pendingClosedWon.dealCard, from.children[oldIndex] || null);
        }
        pendingClosedWon = null;
    });

    // Only initialize Sortable when viewing the current/active financial year
    var pipelineIsHistorical = @json($isHistorical);
    if (pipelineIsHistorical) {
        // mark pipeline as readonly for styling or hooks
        document.querySelectorAll('.deal-stage').forEach(function(s) {
            s.classList.add('pipeline-readonly');
        });
        // add small read-only badge to board wrapper
        // var board = document.getElementById('pipeline-board');
        // if (board && board.parentElement && !document.getElementById('pipeline-readonly-badge')) {
        //     board.parentElement.style.position = 'relative';
        //     var note = document.createElement('div');
        //     note.id = 'pipeline-readonly-badge';
        //     note.className = 'badge bg-secondary text-white position-absolute';
        //     note.style.zIndex = 9999;
        //     note.style.top = '8px';
        //     note.style.right = '18px';
        //     note.style.fontSize = '0.85rem';
        //     note.innerText = 'Read-only (historical FY)';
        //     board.parentElement.appendChild(note);
        // }
        // do not initialize Sortable => no drag/drop
        // additionally block pointer/mouse/dragstart events on deal cards to be extra-safe
        document.querySelectorAll('.deal-card').forEach(function(card) {
            ['mousedown', 'pointerdown', 'touchstart', 'dragstart'].forEach(function(ev) {
                card.addEventListener(ev, function(e) {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                }, {
                    capture: true
                });
            });
            // make the cursor indicate non-interactive
            card.style.cursor = 'not-allowed';
            card.classList.add('deal-card-locked');
        });
    } else {
        document.querySelectorAll('.deal-stage').forEach(function(el) {
            new Sortable(el, {
                group: 'deals',
                animation: 150,
                onStart: function(evt) {
                    lastDrag = {
                        from: evt.from,
                        oldIndex: evt.oldIndex
                    };
                },
                onMove: function(evt) {
                    // Disable drag for deals already in Closed Won stage
                    var dealCard = evt.dragged;
                    var currentStageName = getStageNameById(evt.from.getAttribute('data-stage-id'));
                    // If the card is currently in Closed Won, block move and show message
                    if (currentStageName && currentStageName.toLowerCase() === 'closed won') {
                        showUnableToMoveMsg();
                        return false;
                    }
                    return true;
                },
                onEnd: function(evt) {
                    var dealCard = evt.item;
                    var dealId = dealCard.getAttribute('data-deal-id');
                    var newStageId = evt.to.getAttribute('data-stage-id');
                    var oldStageId = evt.from.getAttribute('data-stage-id');
                    var oldStageName = getStageNameById(oldStageId);
                    var newStageName = getStageNameById(newStageId);
                    // If the card is currently in Closed Won, block all move logic
                    if (oldStageName && oldStageName.toLowerCase() === 'closed won') {
                        return;
                    }
                    // If moved to Closed Lost, require reason
                    if (newStageName && newStageName.toLowerCase() === 'closed lost') {
                        reasonInput.value = '';
                        reasonError.style.display = 'none';
                        pendingRevert = {
                            dealCard: dealCard,
                            lastDrag: lastDrag,
                            dealId: dealId,
                            newStageId: newStageId
                        };
                        modal.show();
                        return;
                    }
                    // If moved to Closed Won, collect amount and close date
                    if (newStageName && newStageName.toLowerCase() === 'closed won') {
                        // show closed won modal
                        var existingAmount = dealCard.getAttribute('data-amount') || '';
                        var existingCloseDate = dealCard.getAttribute('data-close-date') || '';
                        pendingClosedWon = {
                            dealCard: dealCard,
                            dealId: dealId,
                            newStageId: newStageId,
                            lastDrag: lastDrag
                        };
                        document.getElementById('closedWonAmount').value = existingAmount;
                        document.getElementById('closedWonDate').value = existingCloseDate || new Date().toISOString().slice(0, 10);
                        closedWonModal.show();
                        return;
                    }
                    // Normal AJAX call to update deal stage
                    fetch("{{ route('deals.updateStage') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                deal_id: dealId,
                                stage_id: newStageId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success === true) {
                                let msg = document.getElementById('pipeline-success-msg');
                                if (!msg) {
                                    msg = document.createElement('div');
                                    msg.id = 'pipeline-success-msg';
                                    msg.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                                    msg.style.zIndex = 9999;
                                    document.body.appendChild(msg);
                                }
                                msg.innerText = 'Deal stage changed from "' + data.old_stage + '" to "' + data.new_stage + '"!';
                                msg.style.display = 'block';
                                setTimeout(function() {
                                    msg.style.display = 'none';
                                }, 1200);
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1200);
                            } else {
                                alert('Failed to update stage');
                            }
                        })
                        .catch(() => alert('Error updating stage'));
                }
            });
        });
    }

    // Show unable to move message for Closed Won deals
    function showUnableToMoveMsg() {
        let msg = document.getElementById('pipeline-unable-move-msg');
        if (!msg) {
            msg = document.createElement('div');
            msg.id = 'pipeline-unable-move-msg';
            msg.className = 'alert alert-warning position-fixed top-0 end-0 m-3';
            msg.style.zIndex = 9999;
            document.body.appendChild(msg);
        }
        msg.innerText = 'Unable to move: This deal is already Closed Won.';
        msg.style.display = 'block';
        setTimeout(function() {
            msg.style.display = 'none';
        }, 1800);
    }

    // Contact/person search filtering (debounced) - attach on DOMContentLoaded and guard with try/catch
    document.addEventListener('DOMContentLoaded', function() {
        try {
            var input = document.getElementById('pipelineContactSearch');
            if (!input) return;
            var timeout = null;
            input.addEventListener('input', function() {
                try {
                    clearTimeout(timeout);
                    var q = input.value.trim().toLowerCase();
                    timeout = setTimeout(function() {
                        var cards = document.querySelectorAll('.deal-card');
                        cards.forEach(function(card) {
                            var contact = (card.getAttribute('data-contact') || '').toLowerCase();
                            var org = (card.getAttribute('data-organization') || '').toLowerCase();
                            var title = (card.querySelector('a') ? card.querySelector('a').innerText : '').toLowerCase();
                            if (!q) {
                                card.style.display = '';
                            } else if (contact.indexOf(q) !== -1 || title.indexOf(q) !== -1 || org.indexOf(q) !== -1) {
                                card.style.display = '';
                            } else {
                                card.style.display = 'none';
                            }
                        });
                    }, 200);
                } catch (innerErr) {
                    console.error('Pipeline search inner error', innerErr);
                }
            });
        } catch (err) {
            console.error('Pipeline search init error', err);
        }
    });

    // Auto-submit filters (debounced) and enforce date bounds similar to Leads/Deals index
    (function() {
        var submitTimer = null;
        function submitDebounced() {
            clearTimeout(submitTimer);
            submitTimer = setTimeout(function() {
                try {
                    var form = document.querySelector('#pipeline-search-wrapper form') || document.querySelector('#pipeline-search-wrapper');
                    if (form) form.submit();
                } catch (e) {
                    console.error('pipeline auto-submit error', e);
                }
            }, 450);
        }

        function dateFromString(s) {
            if (!s) return null;
            var parts = s.split('-');
            if (parts.length !== 3) return null;
            return new Date(parts[0], parts[1] - 1, parts[2]);
        }
        function formatDate(d) {
            if (!d) return '';
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var dd = String(d.getDate()).padStart(2, '0');
            return d.getFullYear() + '-' + mm + '-' + dd;
        }

        function adjustDateBounds() {
            try {
                var sEl = document.getElementById('start_date');
                var eEl = document.getElementById('end_date');
                if (!sEl || !eEl) return;
                var s = dateFromString(sEl.value);
                var e = dateFromString(eEl.value);
                if (s) {
                    var minTo = new Date(s.getTime());
                    minTo.setDate(minTo.getDate() + 1); // To must be after From
                    eEl.min = formatDate(minTo);
                    // if current end is before minTo, snap it
                    if (e && e < minTo) {
                        eEl.value = formatDate(minTo);
                        e = minTo;
                    }
                } else {
                    eEl.min = '';
                }
                if (e) {
                    var maxFrom = new Date(e.getTime());
                    maxFrom.setDate(maxFrom.getDate() - 1);
                    sEl.max = formatDate(maxFrom);
                    if (s && s > maxFrom) {
                        sEl.value = formatDate(maxFrom);
                    }
                } else {
                    sEl.max = '';
                }
            } catch (err) {
                console.error('adjustDateBounds error', err);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
                try {
                var form = document.querySelector('#pipeline-search-wrapper form') || document.querySelector('#pipeline-search-wrapper');
                if (!form) return;
                var fields = ['pipelineContactSearch', 'label', 'lead_source', 'start_date', 'end_date'];
                fields.forEach(function(id) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    var ev = (el.tagName.toLowerCase() === 'input') ? 'input' : 'change';
                    el.addEventListener(ev, function() {
                        if (id === 'start_date' || id === 'end_date') {
                            adjustDateBounds();
                        }
                        submitDebounced();
                    });
                });
                // initial bounds adjust
                adjustDateBounds();
            } catch (err) {
                console.error('pipeline auto-filter init error', err);
            }
        });


        //Swal Alert for delete confirmation
        var buttons = document.querySelectorAll('.delete-deals-btn');
        var name = 'data-deals-name';
        attachDeleteHandlers(buttons, name);
    })();

    document.addEventListener('DOMContentLoaded', function () {
        var board = document.getElementById('pipeline-board');
        var btnLeft = document.getElementById('pipeline-scroll-left');
        var btnRight = document.getElementById('pipeline-scroll-right');
        function updateScrollBtns() {
            if (!board) return;
            // Only show on mobile
            if (window.innerWidth <= 767) {
                btnLeft.style.display = 'flex';
                btnRight.style.display = 'flex';
                // Hide left if at start, right if at end
                btnLeft.style.opacity = board.scrollLeft > 10 ? '1' : '0.3';
                btnRight.style.opacity = (board.scrollLeft + board.clientWidth < board.scrollWidth - 10) ? '1' : '0.3';
            } else {
                btnLeft.style.display = 'none';
                btnRight.style.display = 'none';
            }
        }
        if (btnLeft && btnRight && board) {
            function getPipelineColumns() {
                return Array.from(board.querySelectorAll('.pipeline-column'));
            }
            function getCenteredColumnIdx() {
                const columns = getPipelineColumns();
                const boardRect = board.getBoundingClientRect();
                const boardCenter = board.scrollLeft + board.clientWidth / 2;
                let minDist = Infinity, idx = 0;
                columns.forEach((col, i) => {
                    const colRect = col.getBoundingClientRect();
                    // col center relative to board scroll
                    const colCenter = col.offsetLeft + col.offsetWidth / 2;
                    const dist = Math.abs(colCenter - boardCenter);
                    if (dist < minDist) { minDist = dist; idx = i; }
                });
                return idx;
            }
            function scrollToColumn(idx) {
                const columns = getPipelineColumns();
                if (!columns[idx]) return;
                const col = columns[idx];
                const colCenter = col.offsetLeft + col.offsetWidth / 2;
                const scrollTo = colCenter - board.clientWidth / 2;
                board.scrollTo({ left: scrollTo, behavior: 'smooth' });
            }
            btnLeft.addEventListener('click', function() {
                const columns = getPipelineColumns();
                let idx = getCenteredColumnIdx();
                if (idx > 0) idx--;
                scrollToColumn(idx);
            });
            btnRight.addEventListener('click', function() {
                const columns = getPipelineColumns();
                let idx = getCenteredColumnIdx();
                if (idx < columns.length - 1) idx++;
                scrollToColumn(idx);
            });
            board.addEventListener('scroll', updateScrollBtns);
            window.addEventListener('resize', updateScrollBtns);
            updateScrollBtns();
        }
    });
</script>
@endpush
