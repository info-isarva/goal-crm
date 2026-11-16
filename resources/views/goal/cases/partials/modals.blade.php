{{-- Reassign --}}
@if($canReassign)
<div class="modal fade" id="cwReassignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('goal.cases.reassign', $case) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reassign Case</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Eligible employee</label>
                    <select name="owner_id" class="form-select" required>
                        <option value="">Select…</option>
                        @foreach($reassignUsers as $u)
                            <option value="{{ $u->id }}" @selected((int)$case->owner_id === (int)$u->id)>{{ $u->name }} ({{ strtoupper($u->job_role ?: 'SM') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary-cw">Reassign</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Add Lender Application --}}
@if($canEdit)
<div class="modal fade" id="cwAddLenderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('goal.cases.bank-applications.store', $case) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Lender Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Lender <span class="text-danger">*</span></label>
                        <input type="text" name="lender_name" class="form-control" required maxlength="150" list="cwLenderList" placeholder="e.g. HDFC Bank">
                        <datalist id="cwLenderList">
                            <option value="HDFC Bank"><option value="ICICI Bank"><option value="SBI"><option value="Axis Bank">
                            <option value="Kotak Mahindra"><option value="Bajaj Finserv"><option value="Tata Capital">
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Application Reference</label>
                        <input type="text" name="application_ref" class="form-control" maxlength="100" placeholder="Pending allocation if blank">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Login Date</label>
                        <input type="date" name="login_date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Bank Login" selected>Bank Login</option>
                            <option value="Processing">Processing</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Follow-up</label>
                        <input type="date" name="follow_up_at" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Requested Amount</label>
                        <input type="number" step="0.01" name="requested_amount" class="form-control" value="{{ $case->loan_amount }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Supporting Evidence</label>
                        <input type="file" name="evidence" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary-cw">Save Application</button>
            </div>
        </form>
    </div>
</div>

{{-- Upload Document --}}
<div class="modal fade" id="cwUploadDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('goal.cases.documents.store', $case) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Document Key</label>
                    <input type="text" name="key" class="form-control" required maxlength="60" placeholder="e.g. identity">
                </div>
                <div class="mb-3">
                    <label class="form-label">Document Type / Label</label>
                    <input type="text" name="label" class="form-control" required maxlength="120" placeholder="e.g. Identity Proof">
                </div>
                <div class="mb-3">
                    <label class="form-label">Party</label>
                    <select name="party" class="form-select">
                        <option value="Applicant">Applicant</option>
                        <option value="Co-Applicant">Co-Applicant</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label">File</label>
                    <input type="file" name="file" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.webp">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary-cw">Upload</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Document --}}
<div class="modal fade" id="cwRejectDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('goal.cases.documents.verify', $case) }}" class="modal-content" id="cwRejectDocForm">
            @csrf
            <input type="hidden" name="key" id="cwRejectDocKey">
            <input type="hidden" name="party" id="cwRejectDocParty" value="Applicant">
            <input type="hidden" name="action" value="Rejected">
            <div class="modal-header">
                <h5 class="modal-title">Reject Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Rejection reason <span class="text-danger">*</span></label>
                <textarea name="rejection_reason" class="form-control" rows="3" required maxlength="500"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary-cw" style="background:#dc2626">Reject</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Query --}}
<div class="modal fade" id="cwAddQueryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="" id="cwAddQueryForm" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Bank Query</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Lender Application</label>
                    <select name="application_id" id="cwQueryAppSelect" class="form-select" required>
                        @foreach($case->bankApplications as $ba)
                            <option value="{{ $ba->id }}" data-action="{{ route('goal.bank-applications.queries.store', $ba) }}">
                                {{ $ba->lender_name }} — {{ $ba->application_ref ?: ('#'.$ba->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Query Details <span class="text-danger">*</span></label>
                    <textarea name="details" class="form-control" rows="3" required maxlength="2000"></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Responsible Employee</label>
                        <select name="responsible_user_id" class="form-select" required>
                            <option value="{{ $case->owner_id }}">{{ $case->owner?->name ?? 'Owner' }}</option>
                            @foreach(($reassignUsers ?? collect()) as $u)
                                @if((int)$u->id !== (int)$case->owner_id)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_at" class="form-control" required value="{{ now()->addDays(4)->toDateString() }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Attachment</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="blocks_processing" value="1" id="cwBlocksProc" checked>
                            <label class="form-check-label" for="cwBlocksProc">Blocks progression to Processing</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary-cw">Save Query</button>
            </div>
        </form>
    </div>
</div>

{{-- Resolve Query --}}
<div class="modal fade" id="cwResolveQueryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="" id="cwResolveQueryForm" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Resolve Bank Query</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Resolution Remarks <span class="text-danger">*</span></label>
                    <textarea name="resolution_remarks" class="form-control" rows="3" required maxlength="2000"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Resolution Evidence</label>
                    <input type="file" name="evidence" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="mark_non_blocking" value="1" id="cwNonBlock">
                    <label class="form-check-label" for="cwNonBlock">Mark as no longer blocking</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary-cw">Resolve</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Sanction --}}
<div class="modal fade" id="cwAddSanctionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="" id="cwAddSanctionForm" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Record Sanction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Lender Application</label>
                    <select name="application_id" id="cwSanctionAppSelect" class="form-select" required>
                        @foreach($case->bankApplications as $ba)
                            <option value="{{ $ba->id }}" data-action="{{ route('goal.bank-applications.sanctions.store', $ba) }}">
                                {{ $ba->lender_name }} — {{ $ba->application_ref ?: ('#'.$ba->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Sanctioned Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="sanction_amount" class="form-control" required min="0.01">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sanction Date</label>
                        <input type="date" name="sanction_date" class="form-control" required value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Validity</label>
                        <input type="date" name="validity_until" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Terms</label>
                        <textarea name="terms" class="form-control" rows="2" maxlength="2000"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Change Reason (for revisions)</label>
                        <input type="text" name="change_reason" class="form-control" maxlength="500">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Supporting Evidence <span class="text-danger">*</span></label>
                        <input type="file" name="evidence" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary-cw">Save Sanction</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Disbursement --}}
<div class="modal fade" id="cwAddDisbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('goal.cases.disbursements.store', $case) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Record Disbursement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Lender Application</label>
                        <select name="goal_bank_application_id" class="form-select">
                            <option value="">—</option>
                            @foreach($case->bankApplications as $ba)
                                <option value="{{ $ba->id }}">{{ $ba->lender_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sanction</label>
                        <select name="goal_sanction_id" class="form-select">
                            <option value="">Active sanction</option>
                            @foreach($case->sanctions->where('status', 'Active') as $s)
                                <option value="{{ $s->id }}">Rev {{ $s->revision }} — ₹ {{ number_format((float)$s->sanction_amount, 0) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" required min="0.01">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="disbursed_on" class="form-control" required value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lender Reference</label>
                        <input type="text" name="bank_reference" class="form-control" required maxlength="100">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <input type="text" name="evidence_note" class="form-control" maxlength="500">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Supporting Evidence <span class="text-danger">*</span></label>
                        <input type="file" name="evidence" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                </div>
                <div class="hint">Saved as Pending Verification. Revenue is recognized only after an authorized reviewer verifies.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary-cw">Save Disbursement</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
(function () {
    var rejectModal = document.getElementById('cwRejectDocModal');
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn) return;
            document.getElementById('cwRejectDocKey').value = btn.getAttribute('data-doc-key') || '';
            document.getElementById('cwRejectDocParty').value = btn.getAttribute('data-doc-party') || 'Applicant';
        });
    }

    function bindAppSelect(selectId, formId) {
        var sel = document.getElementById(selectId);
        var form = document.getElementById(formId);
        if (!sel || !form) return;
        var sync = function () {
            var opt = sel.options[sel.selectedIndex];
            if (opt && opt.getAttribute('data-action')) form.action = opt.getAttribute('data-action');
        };
        sel.addEventListener('change', sync);
        sync();
    }
    bindAppSelect('cwQueryAppSelect', 'cwAddQueryForm');
    bindAppSelect('cwSanctionAppSelect', 'cwAddSanctionForm');

    var resolveModal = document.getElementById('cwResolveQueryModal');
    if (resolveModal) {
        resolveModal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            var form = document.getElementById('cwResolveQueryForm');
            if (btn && form) form.action = btn.getAttribute('data-query-action') || '';
        });
    }

    document.querySelectorAll('[data-bs-target="#cwAddQueryModal"], [data-bs-target="#cwAddSanctionModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var appId = btn.getAttribute('data-app-id');
            if (!appId) return;
            ['cwQueryAppSelect', 'cwSanctionAppSelect'].forEach(function (id) {
                var sel = document.getElementById(id);
                if (sel) {
                    sel.value = appId;
                    sel.dispatchEvent(new Event('change'));
                }
            });
        });
    });
})();
</script>
@endpush
