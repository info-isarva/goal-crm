{{-- Reassign --}}
@if($canReassign)
<div class="modal fade" id="cwReassignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.cases.reassign', $case) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reassign Case</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.cases.bank-applications.store', $case) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Lender Application</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
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

{{-- Add Document (checklist item, no file) --}}
<style>
#cwAddDocModal .cw-add-doc-meta{
    display:flex;flex-wrap:wrap;gap:8px 14px;margin:0 0 1rem;padding:.7rem .85rem;
    border-radius:10px;background:#f8fafc;border:1px solid #eef2f7;
}
#cwAddDocModal .cw-add-doc-meta .item{min-width:0}
#cwAddDocModal .cw-add-doc-meta .k{
    display:block;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:#94a3b8;margin-bottom:2px;
}
#cwAddDocModal .cw-add-doc-meta .v{
    font-size:.84rem;font-weight:700;color:#0f172a;line-height:1.25;
}
#cwAddDocModal .cw-add-doc-grid{
    display:grid;grid-template-columns:minmax(0,1.4fr) minmax(140px,.7fr);gap:12px;align-items:end;
}
#cwAddDocModal .cw-add-doc-grid .full{grid-column:1 / -1}
#cwAddDocModal .cw-req-row{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    margin:0;padding:.85rem .9rem;border-radius:10px;border:1px solid #e2e8f0;background:#fff;
}
#cwAddDocModal .cw-req-row .txt{min-width:0}
#cwAddDocModal .cw-req-row .ttl{display:block;font-size:.84rem;font-weight:700;color:#0f172a}
#cwAddDocModal .cw-req-row .sub{display:block;font-size:.72rem;color:#64748b;margin-top:2px}
#cwAddDocModal .cw-docs-switch{
    display:inline-flex;align-items:center;gap:8px;margin:0;cursor:pointer;user-select:none;flex-shrink:0;
}
#cwAddDocModal .cw-docs-switch input{position:absolute;opacity:0;width:0;height:0;pointer-events:none}
#cwAddDocModal .cw-docs-switch .track{
    position:relative;width:40px;height:22px;border-radius:999px;background:#cbd5e1;flex-shrink:0;transition:background .15s ease;
}
#cwAddDocModal .cw-docs-switch .track::after{
    content:"";position:absolute;top:2px;left:2px;width:18px;height:18px;border-radius:50%;
    background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.2);transition:transform .15s ease;
}
#cwAddDocModal .cw-docs-switch input:checked + .track{background:#0d9488}
#cwAddDocModal .cw-docs-switch input:checked + .track::after{transform:translateX(18px)}
#cwAddDocModal .cw-docs-switch .lbl{font-size:.78rem;font-weight:700;color:#64748b;min-width:1.75rem}
#cwAddDocModal .cw-docs-switch input:checked ~ .lbl{color:#0f766e}
#cwAddDocModal .modal-footer{
    display:flex;justify-content:flex-end;align-items:center;
    padding:.85rem 1.1rem;border-top:1px solid #eef2f7;background:#fafbfc;
}
#cwAddDocModal .cw-add-doc-actions{
    display:inline-grid;grid-template-columns:1fr 1fr;gap:10px;margin-left:auto;
}
#cwAddDocModal .btn-add-cancel,
#cwAddDocModal .btn-add-submit{
    height:40px;padding:0 14px;border-radius:10px;font-size:.88rem;font-weight:700;
    display:inline-flex;align-items:center;justify-content:center;gap:6px;cursor:pointer;
    width:100%;box-sizing:border-box;white-space:nowrap;
}
#cwAddDocModal .btn-add-cancel{
    border:1px solid #cbd5e1;background:#fff;color:#475569;
}
#cwAddDocModal .btn-add-cancel:hover{background:#f8fafc;border-color:#94a3b8;color:#0f172a}
#cwAddDocModal .btn-add-submit{
    border:1px solid transparent;background:var(--theme-primary,#0d9488);color:#fff;
    box-shadow:0 6px 14px rgba(13,148,136,.25);
}
#cwAddDocModal .btn-add-submit:hover{
    background:var(--theme-primary-hover,#0f766e);color:#fff;
}
@media (max-width:575.98px){
    #cwAddDocModal .cw-add-doc-grid{grid-template-columns:1fr}
    #cwAddDocModal .cw-add-doc-actions{display:grid;width:100%;margin-left:0}
}
</style>
<div class="modal fade" id="cwAddDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('goal.cases.documents.store', $case) }}" class="modal-content" id="cwAddDocForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-1"></i> Add document</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="cw-add-doc-meta">
                    <div class="item">
                        <span class="k">Case</span>
                        <span class="v">{{ $case->case_no }}</span>
                    </div>
                    <div class="item" style="flex:1">
                        <span class="k">Customer</span>
                        <span class="v">{{ $case->customer_name ?: '—' }}</span>
                    </div>
                    @if($case->product)
                        <div class="item">
                            <span class="k">Product</span>
                            <span class="v">{{ $case->product }}</span>
                        </div>
                    @endif
                </div>

                <div class="cw-add-doc-grid">
                    <div>
                        <label class="form-label" for="cwAddDocLabel">Document name <span class="text-danger">*</span></label>
                        <input type="text" name="label" id="cwAddDocLabel" class="form-control" required maxlength="120"
                               placeholder="e.g. PAN Card, Aadhaar, Bank Statement" autocomplete="off">
                    </div>
                    <div>
                        <label class="form-label" for="cwAddDocParty">Party</label>
                        <select name="party" id="cwAddDocParty" class="form-select">
                            <option value="Applicant">Applicant</option>
                            <option value="Co-Applicant">Co-Applicant</option>
                        </select>
                    </div>
                    <div class="full">
                        <div class="cw-req-row">
                            <div class="txt">
                                <span class="ttl">Required for this case</span>
                                <span class="sub">Turn on if this must be verified before Ready for Login</span>
                            </div>
                            <label class="cw-docs-switch" for="cwAddDocRequired">
                                <input type="checkbox" name="mandatory" value="1" id="cwAddDocRequired">
                                <span class="track" aria-hidden="true"></span>
                                <span class="lbl" id="cwAddDocReqLbl">No</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="cw-add-doc-actions">
                    <button type="button" class="btn-add-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-add-submit"><i class="bi bi-plus-lg"></i> Add document</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Reject Document --}}
<div class="modal fade" id="cwRejectDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.cases.documents.verify', $case) }}" class="modal-content" id="cwRejectDocForm">
            @csrf
            <input type="hidden" name="key" id="cwRejectDocKey">
            <input type="hidden" name="party" id="cwRejectDocParty" value="Applicant">
            <input type="hidden" name="action" value="Rejected">
            <div class="modal-header">
                <h5 class="modal-title">Reject Document</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="" id="cwAddQueryForm" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Bank Query</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="" id="cwResolveQueryForm" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Resolve Bank Query</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="" id="cwAddSanctionForm" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Record Sanction</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.cases.disbursements.store', $case) }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Record Disbursement</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
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

    var addDocModal = document.getElementById('cwAddDocModal');
    var addDocReq = document.getElementById('cwAddDocRequired');
    var addDocReqLbl = document.getElementById('cwAddDocReqLbl');
    function syncAddDocReqLabel() {
        if (!addDocReqLbl || !addDocReq) return;
        addDocReqLbl.textContent = addDocReq.checked ? 'Yes' : 'No';
    }
    if (addDocReq) {
        addDocReq.addEventListener('change', syncAddDocReqLabel);
        syncAddDocReqLabel();
    }
    if (addDocModal) {
        addDocModal.addEventListener('show.bs.modal', function () {
            var form = document.getElementById('cwAddDocForm');
            if (form) form.reset();
            if (addDocReq) addDocReq.checked = false;
            syncAddDocReqLabel();
            var label = document.getElementById('cwAddDocLabel');
            if (label) setTimeout(function () { label.focus(); }, 200);
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

    function applyLenderAppId(appId) {
        if (!appId) return;
        ['cwQueryAppSelect', 'cwSanctionAppSelect'].forEach(function (id) {
            var sel = document.getElementById(id);
            if (!sel) return;
            sel.value = String(appId);
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    document.querySelectorAll('[data-bs-target="#cwAddQueryModal"], [data-bs-target="#cwAddSanctionModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyLenderAppId(btn.getAttribute('data-app-id'));
        });
    });

    ['cwAddQueryModal', 'cwAddSanctionModal'].forEach(function (id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (btn) applyLenderAppId(btn.getAttribute('data-app-id'));
        });
    });
})();
</script>
@endpush
