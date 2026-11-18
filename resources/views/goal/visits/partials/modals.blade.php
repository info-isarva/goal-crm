{{-- Schedule --}}
<div class="modal fade" id="visScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('goal.visits.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Schedule Customer Visit / Follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Loan Case <span class="text-danger">*</span></label>
                        <select name="goal_loan_case_id" id="vis_case_id" class="form-select" required>
                            <option value="">Search and select loan case…</option>
                            @foreach($cases as $c)
                                <option value="{{ $c->id }}"
                                        data-branch="{{ $c->orgUnit->name ?? '' }}"
                                        data-branch-id="{{ $c->goal_org_unit_id }}"
                                        data-owner="{{ $c->owner_id }}">
                                    {{ $c->case_no }} — {{ $c->customer_name }} ({{ $c->status }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Task Type <span class="text-danger">*</span></label>
                        <select name="task_type" class="form-select" required>
                            <option value="visit">Customer Visit</option>
                            <option value="followup">Follow-up</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Case Branch</label>
                        <input type="text" id="vis_branch_label" class="form-control" readonly placeholder="Auto-filled from loan case" tabindex="-1">
                        <div class="form-text">Read-only · taken from the selected loan case</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                        <input type="text" name="scheduled_date" class="form-control js-datepicker" required placeholder="dd-mm-yyyy" autocomplete="off" data-min-date="today">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Scheduled Time <span class="text-danger">*</span></label>
                        <input type="text" name="scheduled_time" class="form-control js-timepicker" required placeholder="hh:mm" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assigned To <span class="text-danger">*</span></label>
                        <select name="assigned_to" id="vis_assigned_to" class="form-select" required>
                            <option value="">Select employee</option>
                            @foreach($assignees as $u)
                                <option value="{{ $u->id }}" data-branch-id="{{ $u->goal_org_unit_id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text" id="vis_assigned_hint">Select a loan case to list that branch’s staff.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Optional visit location">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Purpose / Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Enter visit purpose or follow-up notes…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2"></i> Save Task</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit — same layout as Schedule (create) --}}
<div class="modal fade" id="visEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="#" id="visEditForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Visit / Follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Loan Case <span class="text-danger">*</span></label>
                        <input type="text" id="vis_edit_case_label" class="form-control" readonly tabindex="-1" placeholder="Loan case">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Task Type <span class="text-danger">*</span></label>
                        <select name="task_type" id="vis_edit_task_type" class="form-select" required>
                            <option value="visit">Customer Visit</option>
                            <option value="followup">Follow-up</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Case Branch</label>
                        <input type="text" id="vis_edit_branch_label" class="form-control" readonly placeholder="Auto-filled from loan case" tabindex="-1">
                        <div class="form-text">Read-only · taken from the loan case</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                        <input type="text" name="scheduled_date" id="vis_edit_scheduled_date" class="form-control js-datepicker" required placeholder="dd-mm-yyyy" autocomplete="off" data-min-date="today">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Scheduled Time <span class="text-danger">*</span></label>
                        <input type="text" name="scheduled_time" id="vis_edit_scheduled_time" class="form-control js-timepicker" required placeholder="hh:mm" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assigned To <span class="text-danger">*</span></label>
                        <select name="assigned_to" id="vis_edit_assigned_to" class="form-select" required>
                            <option value="">Select employee</option>
                            @foreach($assignees as $u)
                                <option value="{{ $u->id }}" data-branch-id="{{ $u->goal_org_unit_id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text" id="vis_edit_assigned_hint">Staff from the case branch</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" id="vis_edit_location" class="form-control" placeholder="Optional visit location">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Purpose / Notes</label>
                        <textarea name="notes" id="vis_edit_notes" class="form-control" rows="3" placeholder="Enter visit purpose or follow-up notes…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2"></i> Update Task</button>
            </div>
        </form>
    </div>
</div>

{{-- Complete --}}
<div class="modal fade" id="visCompleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="#" id="visCompleteForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="visCompleteTitle">Complete task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Outcome <span class="text-danger">*</span></label>
                    <select name="outcome" class="form-select" required>
                        <option value="">Select outcome</option>
                        <option value="Met customer">Met customer</option>
                        <option value="Documents collected">Documents collected</option>
                        <option value="Follow-up required">Follow-up required</option>
                        <option value="Not reachable">Not reachable</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Completion notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2"></i> Mark Complete</button>
            </div>
        </form>
    </div>
</div>
