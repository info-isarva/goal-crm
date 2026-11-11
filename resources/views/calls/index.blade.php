@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">

<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'callsPageSkeleton',
        'columns' => 8,
        'rows' => 10,
        'filterFields' => 4,
    ])

    @php
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
    @endphp

     @if(Auth::user()->hasCrmPermission('import_crm_call_logs_guard'))
    <div style="display: flex; align-items: center; justify-content: space-between;" class="flex-wrap">
        <h2>Create Daily Call Logs</h2>
       
         @if($isHistorical)
             <button class="btn btn-success disabled" type="button" disabled title="Importing call logs is disabled for historical years">Import Call Logs</button>
         @else
             <a href="{{ route('calllogs.import.page') }}" class="btn btn-success btn-padding">Import Call Logs</a>
         @endif
       
    </div>
     @endif
    @if(Auth::user()->hasCrmPermission('create_crm_call_logs_guard') && !$isHistorical)
    <div class="mt-4">
    <!-- @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif -->
    <form action="{{ route('calllogs.store') }}" method="POST" class="mb-4">
        @csrf
        <div id="calllog-entries">
            @php
                $oldLogs = old('logs', []);
                if (empty($oldLogs)) {
                    $oldLogs = [ [ ] ];
                }
            @endphp
            @foreach($oldLogs as $i => $row)
            <div class="calllog-entry card mb-3 p-3 border border-primary bg-light">
                <div class="row mb-2">
                    <div class="col-md-4">
                        <input type="text" name="logs[{{ $i }}][name]" class="form-control" placeholder="Name" value="{{ old('logs.'.$i.'.name', ($row['name'] ?? '')) }}">
                        @if($errors->has('logs.'.$i.'.name'))
                            <div class="text-danger small mt-1">{{ $errors->first('logs.'.$i.'.name') }}</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="logs[{{ $i }}][company_name]" class="form-control" placeholder="Company Name" value="{{ old('logs.'.$i.'.company_name', ($row['company_name'] ?? '')) }}">
                        @if($errors->has('logs.'.$i.'.company_name'))
                            <div class="text-danger small mt-1">{{ $errors->first('logs.'.$i.'.company_name') }}</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="logs[{{ $i }}][mobile_number]" class="form-control" placeholder="Mobile Number" value="{{ old('logs.'.$i.'.mobile_number', ($row['mobile_number'] ?? '')) }}">
                        @if($errors->has('logs.'.$i.'.mobile_number'))
                            <div class="text-danger small mt-1">{{ $errors->first('logs.'.$i.'.mobile_number') }}</div>
                        @endif
                    </div>
                   
                </div>
                <div class="row mb-2">
                     <div class="col-md-4">
                        <input type="email" name="logs[{{ $i }}][email]" class="form-control" placeholder="Email" value="{{ old('logs.'.$i.'.email', ($row['email'] ?? '')) }}">
                        @if($errors->has('logs.'.$i.'.email'))
                            <div class="text-danger small mt-1">{{ $errors->first('logs.'.$i.'.email') }}</div>
                        @endif
                    </div>
                    
                    <div class="col-md-4">
                        <select name="logs[{{ $i }}][call_status]" class="form-select form-select-sm">
                            <option value="">Select Status</option>
                            @php $callStatus = old('logs.'.$i.'.call_status', ($row['call_status'] ?? '')); @endphp
                            <option value="Answered" @if($callStatus=='Answered') selected @endif>Answered</option>
                            <option value="Not Answered" @if($callStatus=='Not Answered') selected @endif>Not Answered</option>
                            <option value="Busy" @if($callStatus=='Busy') selected @endif>Busy</option>
                            <option value="Switch Off" @if($callStatus=='Switch Off') selected @endif>Switch Off</option>
                            <option value="Not Exist" @if($callStatus=='Not Exist') selected @endif>Not Exist</option>
                            <option value="Not reachable" @if($callStatus=='Not reachable') selected @endif>Not reachable</option>
                            <option value="Wrong number" @if($callStatus=='Wrong number') selected @endif>Wrong number</option>
                        </select>
                        @if($errors->has('logs.'.$i.'.call_status'))
                            <div class="text-danger small mt-1">{{ $errors->first('logs.'.$i.'.call_status') }}</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        @php $leadStatus = old('logs.'.$i.'.lead_status', ($row['lead_status'] ?? '')); @endphp
                        <select name="logs[{{ $i }}][lead_status]" class="form-select form-select-sm">
                            <option value="">Select Lead Status (Optional)</option>
                            <option value="Interested" @if($leadStatus=='Interested') selected @endif>Interested</option>
                            <option value="Not Interested" @if($leadStatus=='Not Interested') selected @endif>Not Interested</option>
                            <option value="Follow Up" @if($leadStatus=='Follow Up') selected @endif>Follow Up</option>
                            <option value="Call Later" @if($leadStatus=='Call Later') selected @endif>Call Later</option>
                            <option value="Share the Details" @if($leadStatus=='Share the Details') selected @endif>Share the Details</option>
                            <option value="Closed" @if($leadStatus=='Closed') selected @endif>Closed</option>
                        </select>
                        @if($errors->has('logs.'.$i.'.lead_status'))
                            <div class="text-danger small mt-1">{{ $errors->first('logs.'.$i.'.lead_status') }}</div>
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-12">
                        <textarea name="logs[{{ $i }}][requirement]" class="form-control" placeholder="Requirement">{{ old('logs.'.$i.'.requirement', ($row['requirement'] ?? '')) }}</textarea>
                        @if($errors->has('logs.'.$i.'.requirement'))
                            <div class="text-danger small mt-1">{{ $errors->first('logs.'.$i.'.requirement') }}</div>
                        @endif
                    </div>
                </div>
                <div class="mb-2 text-end">
                    <button type="button" class="btn btn-danger btn-sm remove-entry" style="display:{{ $i==0 ? 'none' : 'inline-block' }};">Remove</button>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mb-3">
            <button type="button" class="btn btn-info" id="add-entry"><i class="fa fa-plus"></i> Add Another Log</button>
        </div>
        <button type="submit" class="btn btn-custom">Submit Logs</button>
        
    </form>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize entryIndex from existing rendered rows
            let container = document.getElementById('calllog-entries');
            let existing = container.querySelectorAll('.calllog-entry');
            let entryIndex = existing.length;

            document.getElementById('add-entry').onclick = function() {
                const first = container.querySelector('.calllog-entry');
                const clone = first.cloneNode(true);
                // Clear values and error messages
                clone.querySelectorAll('input, textarea, select').forEach(function(el) {
                    const name = el.getAttribute('name');
                    if (name) {
                        el.setAttribute('name', name.replace(/logs\[\d+\]/, 'logs['+entryIndex+']'));
                        el.value = '';
                    }
                });
                // remove any inline error messages inside clone
                clone.querySelectorAll('.text-danger.small').forEach(function(err) { err.remove(); });
                // wire remove button
                const rem = clone.querySelector('.remove-entry');
                if (rem) {
                    rem.style.display = 'inline-block';
                    rem.onclick = function() { clone.remove(); };
                }
                container.appendChild(clone);
                entryIndex++;
            };
            // wire existing remove buttons
            container.querySelectorAll('.remove-entry').forEach(function(btn) {
                btn.onclick = function() { this.closest('.calllog-entry').remove(); };
            });
        });
        </script>
    <hr>
    @endif
    
    <h4>My Recent Call Logs</h4>
    <div class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="calllogs-search-form" action="{{ route('calllogs.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-auto" style="flex:1 1 300px; max-width:300px;">
                        <input type="search" name="q" id="calllogs-search-q" class="form-control" placeholder="Search name or mobile" value="{{ request('q') }}">
                    </div>
                    <div class="col-auto">
                        <!-- <button type="submit" class="btn btn-custom">Search</button> -->
                        <a href="{{ route('calllogs.index') }}" class="btn btn-outline-secondary btn-padding ms-1">Clear</a>
                        <a href="{{ route('calllogs.excel', request()->only('q')) }}" class="btn btn-success btn-padding ms-1">Export Excel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Call Log Modal -->
<div class="modal fade" id="editCallLogModal" tabindex="-1" aria-labelledby="editCallLogModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCallLogForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editCallLogModalLabel">Edit Call Log</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="calllog_id" id="editCallLogId">
                    <div class="mb-2">
                        <input type="text" name="name" id="editName" class="form-control" placeholder="Name" >
                            @if($errors->has('name'))
                                <div class="text-danger small mt-1">{{ $errors->first('name') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <input type="text" name="company_name" id="editCompanyName" class="form-control" placeholder="Company Name">
                            @if($errors->has('company_name'))
                                <div class="text-danger small mt-1">{{ $errors->first('company_name') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <input type="text" name="address" id="editAddress" class="form-control" placeholder="Address">
                            @if($errors->has('address'))
                                <div class="text-danger small mt-1">{{ $errors->first('address') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <input type="text" name="mobile_number" id="editMobileNumber" class="form-control" placeholder="Mobile Number">
                            @if($errors->has('mobile_number'))
                                <div class="text-danger small mt-1">{{ $errors->first('mobile_number') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <input type="text" name="email" id="editEmail" class="form-control" placeholder="Email">
                            @if($errors->has('email'))
                                <div class="text-danger small mt-1">{{ $errors->first('email') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <textarea name="requirement" id="editRequirement" class="form-control" placeholder="Requirement"></textarea>
                            @if($errors->has('requirement'))
                                <div class="text-danger small mt-1">{{ $errors->first('requirement') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <input type="text" name="estimated_budget" id="editEstimatedBudget" class="form-control" placeholder="Estimated Budget">
                            @if($errors->has('estimated_budget'))
                                <div class="text-danger small mt-1">{{ $errors->first('estimated_budget') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <select name="call_status" id="editCallStatus" class="form-select form-select-sm">
                            <option value="">Select Status</option>
                            <option value="Answered">Answered</option>
                            <option value="Not Answered">Not Answered</option>
                            <option value="Busy">Busy</option>
                            <option value="Switch Off">Switch Off</option>
                            <option value="Not Exist">Not Exist</option>
                            <option value="Not reachable">Not reachable</option>
                            <option value="Wrong number">Wrong number</option>
                        </select>
                            @if($errors->has('call_status'))
                                <div class="text-danger small mt-1">{{ $errors->first('call_status') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <select name="lead_status" id="editLeadStatus" class="form-select form-select-sm">
                            <option value="">Select Lead Status (Optional)</option>
                            <option value="Interested">Interested</option>
                            <option value="Not Interested">Not Interested</option>
                            <option value="Follow Up">Follow Up</option>
                            <option value="Call Later">Call Later</option>
                            <option value="Share the Details">Share the Details</option>
                            <option value="Closed">Closed</option>
                        </select>
                            @if($errors->has('lead_status'))
                                <div class="text-danger small mt-1">{{ $errors->first('lead_status') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <input type="date" name="next_follow_up_date" id="editNextFollowUpDate" class="form-control" placeholder="Next Follow Up Date">
                            @if($errors->has('next_follow_up_date'))
                                <div class="text-danger small mt-1">{{ $errors->first('next_follow_up_date') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <textarea name="next_action" id="editNextAction" class="form-control" placeholder="Next Action"></textarea>
                            @if($errors->has('next_action'))
                                <div class="text-danger small mt-1">{{ $errors->first('next_action') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <textarea name="remarks" id="editRemarks" class="form-control" placeholder="Remarks"></textarea>
                            @if($errors->has('remarks'))
                                <div class="text-danger small mt-1">{{ $errors->first('remarks') }}</div>
                            @endif
                    </div>
                    <div class="mb-2">
                        <input type="text" name="source" id="editSource" class="form-control" placeholder="Source">
                            @if($errors->has('source'))
                                <div class="text-danger small mt-1">{{ $errors->first('source') }}</div>
                            @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-custom">Update</button>
                    <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="width: 2%;"></th>
                <th>Name</th>
                <th>Mobile Number</th>
                <th>Status</th>
                <th>Sales Person</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($calls as $call)
            <tr>
                <td><!-- Dropdown action moved inside Name cell but will be positioned via CSS on small screens -->
                    <div class="dropdown calllog-action-dropdown">
                        <button class="btn btn-link p-0 text-dark calllog-sidebar-action" type="button" id="callLogActionsDropdown{{ $call->id }}" data-bs-toggle="dropdown" aria-expanded="false" data-calllog-id="{{ $call->id }}">
                            <i class="bi bi-three-dots-vertical fs-5 desktop-dots"></i>
                            <i class="bi bi-three-dots fs-5 mobile-dots" style="display:none;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow rounded-3 py-2 mt-2" aria-labelledby="callLogActionsDropdown{{ $call->id }}" style="min-width:160px; transform:translate(139px, 10px) !important;z-index:1050;">
                            <li>
                                @if($isHistorical)
                                    <button class="dropdown-item px-4 py-2 fs-6 disabled" type="button" disabled title="Editing disabled for historical years">Edit</button>
                                @else
                                    @if(Auth::user()->hasCrmPermission('edit_crm_call_logs_guard'))
                                        <a class="dropdown-item px-4 py-2 fs-6 edit-calllog-btn" href="#" data-calllog='@json($call)'>Edit</a>
                                    @else
                                        <a class="dropdown-item px-4 py-2 fs-6 text-muted disabled" href="#" tabindex="-1" aria-disabled="true">Edit (No Permission)</a>
                                    @endif
                                @endif
                            </li>
                            <li>
                                @if($isHistorical)
                                    <button class="dropdown-item px-4 py-2 fs-6 text-muted disabled" type="button" disabled title="Deleting disabled for historical years">Delete</button>
                                @else
                                    @if(Auth::user()->hasCrmPermission('delete_crm_call_logs_guard'))
                                        <form action="{{ route('calllogs.destroy', $call->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this call log?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="delete-calllogs-btn dropdown-item px-4 py-2 fs-6 text-danger" data-log-name="{{ $call->name }}">Delete</button>
                                        </form>
                                    @else
                                        <button type="button" class="dropdown-item px-4 py-2 fs-6 text-muted disabled" tabindex="-1" aria-disabled="true">Delete (No Permission)</button>
                                    @endif
                                @endif
                            </li>
                        </ul>
                    </div>
                </td>
                <td data-label="Name" >
                    <!-- <div class="d-flex align-items-center gap-2"> -->
                        <span>{{ $call->name }}</span>
                    <!-- </div> -->
                    
                </td>
                <td data-label="Mobile Number"><a href="{{ route('calllogs.show', $call->id) }}">{{ $call->mobile_number }}</a></td>
                <td data-label="Status">{{ $call->call_status }}</td>
                
                <td>{{ $call->creator?->name ?? 'N/A' }}</td>
                <td data-label="Date">{{ $call->created_at->format('d M Y') }}</td>
            </tr>

            @empty
            <tr><td colspan="4" class="text-center">No call logs found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
     <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
        <div class="small text-muted">Showing {{ $calls->firstItem() ?? 0 }} to {{ $calls->lastItem() ?? 0 }} of {{ $calls->total() }} call logs</div>
        <div class="pagination-custom text-center my-3">
            <nav aria-label="Calls pagination">
                <ul class="pagination justify-content-center gap-3 mb-0">
                    <li class="page-item {{ $calls->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $calls->previousPageUrl() ?: '#' }}" tabindex="-1" aria-disabled="{{ $calls->onFirstPage() ? 'true' : 'false' }}">&laquo; Previous</a>
                    </li>
                    <li class="page-item {{ $calls->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $calls->nextPageUrl() ?: '#' }}" aria-disabled="{{ $calls->hasMorePages() ? 'false' : 'true' }}">Next &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    
</div>
<script>
// Wire customer delete button in header to SweetAlert2 confirmation
document.addEventListener('DOMContentLoaded', function() {
    //Swal Alert for delete confirmation
    var buttons = document.querySelectorAll('.delete-calllogs-btn');
    var name = 'data-log-name';
    attachDeleteHandlers(buttons, name); 
});
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.calllog-sidebar-action').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            // If this button is a Bootstrap dropdown toggle (three-dots), allow the dropdown to open
            // and do not trigger the sidebar opening. This prevents mobile/tablet from opening the
            // main menu instead of the edit/delete menu.
            var isDropdownToggle = btn.getAttribute('data-bs-toggle') === 'dropdown' || btn.classList.contains('dropdown-toggle');
            if (isDropdownToggle) {
                // Let Bootstrap handle the dropdown; do not preventDefault or open sidebar
                return;
            }
            e.preventDefault();
            var sidebarBtn = document.querySelector('button[aria-controls="sidebarOffcanvas"]');
            if (sidebarBtn) {
                if (!document.querySelector('.mobile-sidebar.open')) {
                    sidebarBtn.click();
                }
            }
            setTimeout(function() {
                var actionsMenu = document.querySelector('.nav-link[href*="calllogs.create"]');
                if (actionsMenu) {
                    actionsMenu.classList.add('active', 'bg-warning', 'text-dark');
                    actionsMenu.scrollIntoView({behavior: 'smooth', block: 'center'});
                    setTimeout(function() {
                        actionsMenu.classList.remove('bg-warning', 'text-dark');
                    }, 2000);
                }
            }, 350);
        });
    });

    // Edit modal logic
    var editModalEl = document.getElementById('editCallLogModal');
    var editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    document.querySelectorAll('.edit-calllog-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var call = JSON.parse(this.getAttribute('data-calllog'));
            document.getElementById('editCallLogId').value = call.id;
            document.getElementById('editName').value = call.name || '';
            document.getElementById('editCompanyName').value = call.company_name || '';
            document.getElementById('editAddress').value = call.address || '';
            document.getElementById('editMobileNumber').value = call.mobile_number || '';
            document.getElementById('editEmail').value = call.email || '';
            document.getElementById('editRequirement').value = call.requirement || '';
            document.getElementById('editEstimatedBudget').value = call.estimated_budget || '';
            document.getElementById('editCallStatus').value = call.call_status || '';
            document.getElementById('editLeadStatus').value = call.lead_status || '';
            document.getElementById('editNextFollowUpDate').value = call.next_follow_up_date ? call.next_follow_up_date.split(' ')[0] : '';
            document.getElementById('editNextAction').value = call.next_action || '';
            document.getElementById('editRemarks').value = call.remarks || '';
            document.getElementById('editSource').value = call.source || '';
            if (editModal) {
                editModal.show();
            }
            document.getElementById('editCallLogForm').action = '/calllogs/' + call.id;
        });
    });

    // Fix modal backdrop issue on Cancel
    var cancelBtn = document.querySelector('#editCallLogModal .btn-secondary');
    if (cancelBtn && editModal) {
        cancelBtn.addEventListener('click', function() {
            editModal.hide();
            setTimeout(function() {
                document.body.classList.remove('modal-open');
                var backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(bd) { bd.remove(); });
            }, 300);
        });
    }

    // Debounced auto-search for single query input 'q' (searches name OR mobile)
    (function() {
        var form = document.getElementById('calllogs-search-form');
        if (!form) return;
        var qInput = document.getElementById('calllogs-search-q');
        var timer = null;
        var delay = 450; // ms
        var submitWithPreservedParams = function() { form.submit(); };
        var schedule = function() {
            if (timer) clearTimeout(timer);
            timer = setTimeout(submitWithPreservedParams, delay);
        };
        qInput && qInput.addEventListener('input', schedule);
        // Submit on Enter immediately
        qInput && qInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (timer) clearTimeout(timer);
                submitWithPreservedParams();
            }
        });
    })();
});
</script>
@if(old('calllog_id'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    var id = {!! json_encode(old('calllog_id')) !!};
    try {
        document.getElementById('editCallLogId').value = id;
        document.getElementById('editCallLogForm').action = '/calllogs/' + id;
        document.getElementById('editName').value = {!! json_encode(old('name', '')) !!};
        document.getElementById('editCompanyName').value = {!! json_encode(old('company_name', '')) !!};
        document.getElementById('editAddress').value = {!! json_encode(old('address', '')) !!};
        document.getElementById('editMobileNumber').value = {!! json_encode(old('mobile_number', '')) !!};
        document.getElementById('editEmail').value = {!! json_encode(old('email', '')) !!};
        document.getElementById('editRequirement').value = {!! json_encode(old('requirement', '')) !!};
        document.getElementById('editEstimatedBudget').value = {!! json_encode(old('estimated_budget', '')) !!};
        document.getElementById('editCallStatus').value = {!! json_encode(old('call_status', '')) !!};
        document.getElementById('editLeadStatus').value = {!! json_encode(old('lead_status', '')) !!};
        document.getElementById('editNextFollowUpDate').value = {!! json_encode(old('next_follow_up_date', '')) !!};
        document.getElementById('editNextAction').value = {!! json_encode(old('next_action', '')) !!};
        document.getElementById('editRemarks').value = {!! json_encode(old('remarks', '')) !!};
        document.getElementById('editSource').value = {!! json_encode(old('source', '')) !!};
        var modalEl = document.getElementById('editCallLogModal');
        var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        if (modal) modal.show();
    } catch (e) {
        // ignore
    }
});
</script>
@endif
@endsection
