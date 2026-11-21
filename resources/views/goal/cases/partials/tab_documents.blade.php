@php
    $DocCat = \App\Services\Goal\GoalDocumentCatalog::class;
    $statusClass = fn ($label) => match ($label) {
        'Verified' => 'verified',
        'Rejected' => 'rejected',
        'Pending Verification' => 'pending',
        default => 'idle',
    };
    $statusShort = fn ($label) => match ($label) {
        'Pending Verification' => 'Pending',
        'Not recorded' => 'Not recorded',
        default => $label,
    };
    $docsCollection = collect($docs)->values();
    $missingRequired = $docsCollection->filter(function ($d) use ($DocCat) {
        $row = is_array($d) ? $d : [];
        return ! empty($row['mandatory'])
            && $DocCat::normalizeStatus($row) !== 'Verified'
            && ! $DocCat::isReceived($row);
    })->count();

    $docsPerPage = (int) request('docs_per_page', 10);
    if (! in_array($docsPerPage, [5, 10, 25, 50], true)) {
        $docsPerPage = 10;
    }
    $docsPage = max(1, (int) request('docs_page', 1));
    $docsPaged = new \Illuminate\Pagination\LengthAwarePaginator(
        $docsCollection->forPage($docsPage, $docsPerPage)->values(),
        $docsCollection->count(),
        $docsPerPage,
        $docsPage,
        [
            'path' => request()->url(),
            'pageName' => 'docs_page',
            'query' => array_merge(
                request()->except('docs_page'),
                ['tab' => 'documents']
            ),
        ]
    );
@endphp

<style>
.cw-docs-kpi{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;margin-bottom:.85rem}
.cw-docs-kpi .tile{
    background:#fff;border:1px solid #e8edf5;border-radius:12px;padding:.55rem .75rem;
    display:flex;align-items:center;justify-content:space-between;gap:8px;
    box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.cw-docs-kpi .tile .l{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
.cw-docs-kpi .tile .n{font-size:1.15rem;font-weight:800;color:#0f172a;line-height:1}
.cw-docs-hint{font-size:.82rem;color:#64748b;margin:0 0 .75rem}

.cw-docs-wrap{overflow:hidden}
.cw-docs-table{margin:0}
.cw-docs-table > table{
    width:100%;
    table-layout:fixed;
    border-collapse:collapse;
    margin:0;
}
.cw-docs-table > table thead th{
    background:#f8fafc !important;
    color:#64748b !important;
    font-size:.68rem !important;
    font-weight:700 !important;
    text-transform:uppercase !important;
    letter-spacing:.04em !important;
    text-align:left !important;
    vertical-align:middle !important;
    white-space:nowrap;
    height:42px;
    padding:0 14px !important;
    border-bottom:1px solid #e2e8f0 !important;
}
.cw-docs-table > table tbody td{
    text-align:left !important;
    vertical-align:middle !important;
    height:56px;
    padding:10px 14px !important;
    border-bottom:1px solid #f1f5f9 !important;
    color:#334155;
    font-size:.84rem;
    background:#fff;
}
.cw-docs-table > table tbody tr:hover td{background:#f8fafc}
.cw-docs-table > table tbody tr:last-child td{border-bottom:0 !important}

.cw-docs-table col.c-doc{width:20%}
.cw-docs-table col.c-req{width:11%}
.cw-docs-table col.c-party{width:11%}
.cw-docs-table col.c-status{width:12%}
.cw-docs-table col.c-recorded{width:13%}
.cw-docs-table col.c-reviewer{width:11%}
.cw-docs-table col.c-actions{width:22%}

.cw-docs-table th.c-actions,
.cw-docs-table td.c-actions{text-align:right !important}
.cw-docs-table thead th,
.cw-docs-table tbody td{box-sizing:border-box}

.cw-docs-table .doc-name{
    font-weight:600;color:#0f172a;line-height:1.3;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.cw-docs-table .doc-note{
    margin-top:2px;font-size:.72rem;color:#dc2626;line-height:1.2;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}

.cw-docs-switch{
    display:inline-flex;align-items:center;gap:8px;margin:0;cursor:pointer;user-select:none;
}
.cw-docs-switch input{
    position:absolute;opacity:0;width:0;height:0;pointer-events:none;
}
.cw-docs-switch .track{
    position:relative;width:36px;height:20px;border-radius:999px;
    background:#cbd5e1;flex-shrink:0;transition:background .15s ease;
}
.cw-docs-switch .track::after{
    content:"";position:absolute;top:2px;left:2px;width:16px;height:16px;
    border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.2);
    transition:transform .15s ease;
}
.cw-docs-switch input:checked + .track{background:#0d9488}
.cw-docs-switch input:checked + .track::after{transform:translateX(16px)}
.cw-docs-switch input:focus-visible + .track{outline:2px solid rgba(13,148,136,.35);outline-offset:2px}
.cw-docs-switch .lbl{
    font-size:.72rem;font-weight:700;color:#64748b;min-width:3.5rem;
}
.cw-docs-switch input:checked ~ .lbl{color:#0f766e}
.cw-docs-switch.is-ro{cursor:default}
.cw-docs-switch.is-ro .track{opacity:.85}

.cw-docs-table .party-txt{color:#475569;font-weight:500;white-space:nowrap}
.cw-docs-table .muted-dash{color:#94a3b8}

.cw-docs-table .status-pill{
    display:inline-flex;align-items:center;justify-content:center;
    min-height:24px;padding:.2rem .6rem;border-radius:999px;
    font-size:.72rem;font-weight:700;white-space:nowrap;line-height:1;
}
.cw-docs-table .status-pill.idle{background:#f1f5f9;color:#64748b}
.cw-docs-table .status-pill.pending{background:#ffedd5;color:#c2410c}
.cw-docs-table .status-pill.verified{background:#dcfce7;color:#15803d}
.cw-docs-table .status-pill.rejected{background:#fee2e2;color:#b91c1c}

.cw-docs-table .recorded-cell{min-width:0}
.cw-docs-table .recorded-cell .dt{
    font-weight:600;color:#334155;white-space:nowrap;font-size:.82rem;line-height:1.25;
}
.cw-docs-table .recorded-cell .by{
    color:#94a3b8;font-size:.72rem;line-height:1.2;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.cw-docs-table .reviewer-txt{
    display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#475569;
}

.cw-docs-actions{
    display:inline-flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap;
}
.cw-docs-actions form{display:inline;margin:0}
.cw-docs-btn{
    display:inline-flex;align-items:center;justify-content:center;gap:5px;
    height:32px;padding:0 10px;margin:0;
    border-radius:8px;border:1px solid #e2e8f0;background:#fff;
    color:#475569;font-size:.75rem;font-weight:700;line-height:1;
    cursor:pointer;text-decoration:none;white-space:nowrap;
    transition:background .12s,border-color .12s,color .12s;
}
.cw-docs-btn:hover{background:#f8fafc;border-color:#cbd5e1;color:#0f172a;text-decoration:none}
.cw-docs-btn i{font-size:.88rem;line-height:1}
.cw-docs-btn.is-receive{border-color:#bae6fd;background:#f0f9ff;color:#0369a1}
.cw-docs-btn.is-receive:hover{background:#e0f2fe;border-color:#7dd3fc;color:#075985}
.cw-docs-btn.is-verify{border-color:#a7f3d0;background:#ecfdf5;color:#059669}
.cw-docs-btn.is-verify:hover{background:#d1fae5;border-color:#6ee7b7;color:#047857}
.cw-docs-btn.is-reject{border-color:#fecaca;background:#fef2f2;color:#dc2626}
.cw-docs-btn.is-reject:hover{background:#fee2e2;border-color:#fca5a5;color:#b91c1c}

.cw-docs-foot{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;
    padding:.75rem 1rem;border-top:1px solid #eef2f7;background:#fafbfc;
}
.cw-docs-foot .meta{font-size:.8rem;color:#64748b;font-weight:600}
.cw-docs-foot .controls{display:flex;flex-wrap:wrap;align-items:center;gap:10px}
.cw-docs-foot .per-page{
    display:inline-flex;align-items:center;gap:6px;font-size:.78rem;color:#64748b;font-weight:600;
}
.cw-docs-foot .per-page select{
    height:32px;border:1px solid #e2e8f0;border-radius:8px;padding:0 8px;
    background:#fff;color:#334155;font-size:.78rem;font-weight:600;
}
.cw-docs-foot .pagination{margin:0;gap:4px}
.cw-docs-foot .page-link{
    min-width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;
    border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;
    font-size:.78rem;font-weight:700;padding:0 8px;text-decoration:none;
}
.cw-docs-foot .page-item.active .page-link{
    background:var(--theme-primary,#0d9488);border-color:var(--theme-primary,#0d9488);color:#fff;
}
.cw-docs-foot .page-item.disabled .page-link{opacity:.45;pointer-events:none}

@media (max-width:991.98px){
    .cw-docs-kpi{grid-template-columns:repeat(2,minmax(0,1fr))}
    .cw-docs-table > table,
    .cw-docs-table > table thead,
    .cw-docs-table > table tbody,
    .cw-docs-table > table tr,
    .cw-docs-table > table th,
    .cw-docs-table > table td{display:block;width:100%;table-layout:auto}
    .cw-docs-table > table thead{display:none}
    .cw-docs-table > table tbody tr{
        display:block;margin:0 0 10px;padding:12px 14px;
        border:1px solid #e2e8f0;border-radius:12px;background:#fff;height:auto;
    }
    .cw-docs-table > table tbody td{
        display:flex;align-items:center;justify-content:space-between;gap:12px;
        height:auto;padding:.45rem 0 !important;border-bottom:1px dashed #f1f5f9 !important;
        text-align:right !important;
    }
    .cw-docs-table > table tbody td:last-child{border-bottom:0 !important}
    .cw-docs-table > table tbody td::before{
        content:attr(data-label);
        color:#64748b;font-size:.68rem;font-weight:700;text-transform:uppercase;
        letter-spacing:.03em;text-align:left;
    }
    .cw-docs-table > table tbody td.c-doc{
        display:block;text-align:left !important;padding-bottom:.55rem !important;
        border-bottom:1px solid #e2e8f0 !important;margin-bottom:4px;
    }
    .cw-docs-table > table tbody td.c-doc::before{display:none}
    .cw-docs-table > table tbody td.c-actions{
        justify-content:flex-start;padding-top:.65rem !important;
    }
    .cw-docs-table > table tbody td.c-actions::before{display:none}
    .cw-docs-actions{justify-content:flex-start;flex-wrap:wrap}
    .cw-docs-foot{flex-direction:column;align-items:stretch}
}
</style>

<div class="cw-docs-kpi">
    <div class="tile"><span class="l">Required</span><span class="n">{{ $docSummary['required'] ?? 0 }}</span></div>
    <div class="tile"><span class="l">Received</span><span class="n">{{ $docSummary['received'] ?? 0 }}</span></div>
    <div class="tile"><span class="l">Pending verify</span><span class="n">{{ $docSummary['pending'] ?? 0 }}</span></div>
    <div class="tile"><span class="l">Verified</span><span class="n">{{ $docSummary['verified'] ?? 0 }}</span></div>
    <div class="tile"><span class="l">Rejected</span><span class="n">{{ $docSummary['rejected'] ?? 0 }}</span></div>
</div>

<p class="cw-docs-hint">
    Add documents for this case, toggle Required when needed, then mark Received and Verify manually.
    Only required items must be verified before Ready for Login.
</p>

<div class="sec-card cw-docs-wrap">
    <div class="sec-head">
        <div class="sec-head-left">
            <span class="sec-ico"><i class="bi bi-clipboard-check"></i></span>
            <div>
                <h6>Case Documents</h6>
                <div class="sec-sub">
                    Same checklist as the
                    <a href="{{ route('goal.documents.index', ['case_id' => $case->id]) }}">Documents register</a>.
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($canEdit && $missingRequired > 0)
                <form method="POST" action="{{ route('goal.cases.documents.receive', $case) }}" class="js-cw-doc-confirm"
                    data-swal-title="Mark all missing as received?"
                    data-swal-text="{{ $missingRequired }} required document{{ $missingRequired === 1 ? '' : 's' }} will be marked received and pending verification."
                    data-swal-confirm="Yes, mark received"
                    data-swal-icon="question">
                    @csrf
                    <input type="hidden" name="bulk_missing" value="1">
                    <button type="submit" class="btn-ghost">
                        <i class="bi bi-check2-all"></i> Mark all missing received
                    </button>
                </form>
            @endif
            @if($canEdit)
                <button type="button" class="btn-primary-cw" data-bs-toggle="modal" data-bs-target="#cwAddDocModal">
                    <i class="bi bi-plus-lg"></i> Add document
                </button>
            @endif
        </div>
    </div>
    <div class="table-responsive cw-docs-table">
        <table>
            <colgroup>
                <col class="c-doc">
                <col class="c-req">
                <col class="c-party">
                <col class="c-status">
                <col class="c-recorded">
                <col class="c-reviewer">
                <col class="c-actions">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">Document</th>
                    <th scope="col">Required</th>
                    <th scope="col">Party</th>
                    <th scope="col">Status</th>
                    <th scope="col">Recorded</th>
                    <th scope="col">Reviewer</th>
                    <th scope="col" class="c-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($docsPaged as $doc)
                    @php
                        $row = is_array($doc) ? $doc : [];
                        $stNorm = $DocCat::normalizeStatus($row);
                        $stLabel = $DocCat::displayStatus($row);
                        $isReceived = $DocCat::isReceived($row);
                        $key = (string) ($row['key'] ?? '');
                        $party = (string) ($row['party'] ?? 'Applicant');
                        $label = (string) ($row['label'] ?? ($key !== '' ? $key : 'Document'));
                        $isMandatory = ! empty($row['mandatory']);
                        $recordedOn = null;
                        $recordedRaw = $row['uploaded_at'] ?? ($row['received_at'] ?? null);
                        if (! empty($recordedRaw)) {
                            try { $recordedOn = \Carbon\Carbon::parse($recordedRaw)->format('d M Y'); } catch (\Throwable) { $recordedOn = $recordedRaw; }
                        }
                        $recordedBy = $row['uploaded_by'] ?? null;
                        $reviewer = $row['reviewer'] ?? null;
                        $note = trim((string) ($row['rejection_reason'] ?? ''));
                        $canMarkReceived = $canEdit && $stNorm !== 'Verified' && (! $isReceived || $stNorm === 'Rejected');
                        $canReview = $canVerifyDocs && $stNorm === 'Pending' && $isReceived;
                    @endphp
                    <tr>
                        <td class="c-doc" data-label="Document">
                            <div class="doc-name" title="{{ $label }}">{{ $label }}</div>
                            @if($note !== '')
                                <div class="doc-note" title="{{ $note }}">{{ \Illuminate\Support\Str::limit($note, 42) }}</div>
                            @endif
                        </td>
                        <td class="c-req" data-label="Required">
                            @if($canEdit)
                                <form method="POST" action="{{ route('goal.cases.documents.meta', $case) }}">
                                    @csrf
                                    <input type="hidden" name="key" value="{{ $key }}">
                                    <input type="hidden" name="party" value="{{ $party }}">
                                    <label class="cw-docs-switch" title="Toggle required for this case">
                                        <input type="checkbox" name="mandatory" value="1" @checked($isMandatory)
                                            onchange="this.form.submit()">
                                        <span class="track" aria-hidden="true"></span>
                                        <span class="lbl">{{ $isMandatory ? 'Yes' : 'No' }}</span>
                                    </label>
                                </form>
                            @else
                                <span class="status-pill {{ $isMandatory ? 'verified' : 'idle' }}">{{ $isMandatory ? 'Yes' : 'No' }}</span>
                            @endif
                        </td>
                        <td class="c-party" data-label="Party">
                            <span class="party-txt">{{ $party }}</span>
                        </td>
                        <td class="c-status" data-label="Status">
                            <span class="status-pill {{ $statusClass($stLabel) }}" title="{{ $stLabel }}">{{ $statusShort($stLabel) }}</span>
                        </td>
                        <td class="c-recorded" data-label="Recorded">
                            @if($recordedOn)
                                <div class="recorded-cell">
                                    <div class="dt">{{ $recordedOn }}</div>
                                    @if($recordedBy)
                                        <div class="by" title="{{ $recordedBy }}">{{ $recordedBy }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="muted-dash">—</span>
                            @endif
                        </td>
                        <td class="c-reviewer" data-label="Reviewer">
                            @if($reviewer)
                                <span class="reviewer-txt" title="{{ $reviewer }}">{{ $reviewer }}</span>
                            @else
                                <span class="muted-dash">—</span>
                            @endif
                        </td>
                        <td class="c-actions" data-label="Actions">
                            <div class="cw-docs-actions">
                                @if($canMarkReceived)
                                    <form method="POST" action="{{ route('goal.cases.documents.receive', $case) }}" class="js-cw-doc-confirm"
                                        data-swal-title="Mark as received?"
                                        data-swal-text="{{ $label }} will be marked received and pending verification."
                                        data-swal-confirm="Yes, mark received"
                                        data-swal-icon="question">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $key }}">
                                        <input type="hidden" name="label" value="{{ $label }}">
                                        <input type="hidden" name="party" value="{{ $party }}">
                                        <input type="hidden" name="mandatory" value="{{ $isMandatory ? '1' : '0' }}">
                                        <button class="cw-docs-btn is-receive" type="submit">
                                            <i class="bi bi-check2"></i> Received
                                        </button>
                                    </form>
                                @endif
                                @if($canReview)
                                    <form method="POST" action="{{ route('goal.cases.documents.verify', $case) }}" class="js-cw-doc-confirm"
                                        data-swal-title="Verify document?"
                                        data-swal-text="Confirm verification for {{ $label }}."
                                        data-swal-confirm="Yes, verify"
                                        data-swal-icon="question"
                                        data-swal-color="#059669">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $key }}">
                                        <input type="hidden" name="party" value="{{ $party }}">
                                        <input type="hidden" name="action" value="Verified">
                                        <button class="cw-docs-btn is-verify" type="submit">
                                            <i class="bi bi-shield-check"></i> Verify
                                        </button>
                                    </form>
                                    <button type="button" class="cw-docs-btn is-reject js-cw-doc-reject"
                                        data-doc-key="{{ $key }}"
                                        data-doc-party="{{ $party }}"
                                        data-doc-label="{{ $label }}"
                                        data-reject-action="{{ route('goal.cases.documents.verify', $case) }}">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4" style="text-align:center !important;height:auto">
                            No documents yet.
                            @if($canEdit)
                                Use <strong>Add document</strong> to build this case checklist.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($docsPaged->total() > 0)
        <div class="cw-docs-foot">
            <div class="meta">
                Showing {{ $docsPaged->firstItem() }}–{{ $docsPaged->lastItem() }} of {{ $docsPaged->total() }}
            </div>
            <div class="controls">
                <form method="GET" action="{{ request()->url() }}" class="per-page">
                    @foreach(request()->except(['docs_per_page', 'docs_page']) as $k => $v)
                        @if(is_array($v))
                            @foreach($v as $vv)
                                <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <input type="hidden" name="tab" value="documents">
                    <label for="cwDocsPerPage">Rows</label>
                    <select id="cwDocsPerPage" name="docs_per_page" onchange="this.form.submit()">
                        @foreach([5, 10, 25, 50] as $n)
                            <option value="{{ $n }}" @selected($docsPerPage === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </form>
                @if($docsPaged->hasPages())
                    <div>{{ $docsPaged->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    var theme = (getComputedStyle(document.documentElement).getPropertyValue('--theme-primary') || '').trim() || '#009B80';

    function confirmThen(opts, onYes) {
        if (window.Swal && typeof Swal.fire === 'function') {
            Swal.fire({
                icon: opts.icon || 'question',
                title: opts.title || 'Confirm',
                text: opts.text || '',
                showCancelButton: true,
                focusCancel: true,
                confirmButtonColor: opts.color || theme,
                cancelButtonColor: '#94a3b8',
                confirmButtonText: opts.confirmText || 'Yes',
                cancelButtonText: 'Cancel'
            }).then(function (r) {
                if (r.isConfirmed) onYes();
            });
            return;
        }
        if (window.confirm((opts.title || 'Confirm') + (opts.text ? '\n\n' + opts.text : ''))) {
            onYes();
        }
    }

    document.querySelectorAll('form.js-cw-doc-confirm').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            confirmThen({
                title: form.getAttribute('data-swal-title') || 'Confirm?',
                text: form.getAttribute('data-swal-text') || '',
                confirmText: form.getAttribute('data-swal-confirm') || 'Yes',
                icon: form.getAttribute('data-swal-icon') || 'question',
                color: form.getAttribute('data-swal-color') || theme
            }, function () {
                HTMLFormElement.prototype.submit.call(form);
            });
        });
    });

    document.querySelectorAll('.js-cw-doc-reject').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var label = btn.getAttribute('data-doc-label') || 'this document';
            var key = btn.getAttribute('data-doc-key') || '';
            var party = btn.getAttribute('data-doc-party') || 'Applicant';
            var action = btn.getAttribute('data-reject-action') || '';
            if (!action || !key) return;

            var runReject = function (reason) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = action;
                form.style.display = 'none';
                function add(name, value) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                }
                var token = document.querySelector('meta[name="csrf-token"]');
                add('_token', token ? token.getAttribute('content') : '{{ csrf_token() }}');
                add('key', key);
                add('party', party);
                add('action', 'Rejected');
                add('rejection_reason', reason);
                document.body.appendChild(form);
                form.submit();
            };

            if (window.Swal && typeof Swal.fire === 'function') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Reject document?',
                    html: '<div style="text-align:left;font-size:.9rem;color:#475569;margin-bottom:.5rem">Reject <strong>' +
                        String(label).replace(/</g, '&lt;') + '</strong>. Reason is required.</div>',
                    input: 'textarea',
                    inputPlaceholder: 'Enter rejection reason…',
                    inputAttributes: { maxlength: 500, 'aria-label': 'Rejection reason' },
                    showCancelButton: true,
                    focusCancel: true,
                    confirmButtonText: 'Reject',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#94a3b8',
                    preConfirm: function (value) {
                        if (!value || !String(value).trim()) {
                            Swal.showValidationMessage('Rejection reason is required');
                            return false;
                        }
                        return String(value).trim();
                    }
                }).then(function (r) {
                    if (r.isConfirmed) runReject(r.value);
                });
                return;
            }

            var reason = window.prompt('Rejection reason for ' + label + ' (required):', '');
            if (reason && reason.trim()) runReject(reason.trim());
        });
    });
})();
</script>
@endpush
