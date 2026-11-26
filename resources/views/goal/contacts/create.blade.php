@extends('layouts.app')

@push('styles')
<style>
.cg-page{
  --g:#16a34a;--gd:#15803d;--ink:#0f172a;--muted:#64748b;--line:#e8eef2;--soft:#f8faf9;
  background:#f4f7f5;padding:1rem .85rem 1.5rem;width:100%;max-width:100%;box-sizing:border-box;
}
.cg-page *,.cg-page *::before,.cg-page *::after{box-sizing:border-box}
.cg-page .crumbs{display:flex;flex-wrap:wrap;gap:6px;align-items:center;list-style:none;margin:0 0 12px;padding:0;font-size:.8rem;color:var(--muted)}
.cg-page .crumbs a{color:var(--muted);text-decoration:none;font-weight:600}
.cg-page .crumbs a:hover{color:var(--gd)}
.cg-page .crumbs .cur{color:var(--ink);font-weight:700}
.cg-page .title-row{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:1rem}
.cg-page .title-row h1{margin:0;font-size:1.35rem;font-weight:800;color:var(--ink);line-height:1.25}
.cg-page .title-row p{margin:4px 0 0;font-size:.85rem;color:var(--muted);line-height:1.4;max-width:40rem}
.cg-card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 2px 10px rgba(15,23,42,.04);overflow:hidden}
.cg-btn{min-height:36px;padding:0 14px;border-radius:8px;font-weight:800;font-size:.84rem;border:1px solid #e2e8f0;background:#fff;color:#475569;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;white-space:nowrap}
.cg-btn-primary{background:var(--g);border-color:var(--g);color:#fff}
.cg-btn-primary:hover{background:var(--gd);color:#fff}
.cg-btn-primary:disabled{opacity:.55;cursor:not-allowed;background:#94a3b8;border-color:#94a3b8}
.cg-page .alert{padding:.6rem .85rem;font-size:.875rem;margin-bottom:12px;border-radius:8px}

.cg-flow{display:flex;flex-wrap:wrap;gap:6px 8px;align-items:center;padding:10px 14px;border-bottom:1px solid var(--line);background:#fbfcfc}
.cg-flow-item{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;font-size:.74rem;font-weight:700;color:#64748b}
.cg-flow-item i{font-size:.85rem;line-height:1}
.cg-flow-item.on{border-color:#86efac;background:#f0fdf4;color:#166534}
.cg-flow-sep{color:#cbd5e1;font-size:.75rem}

.cg-grid{display:grid;grid-template-columns:1fr;gap:0;margin:0}
.cg-block{padding:14px 16px;border-bottom:1px solid var(--line);min-width:0;display:flex;flex-direction:column}
.cg-block h3{margin:0 0 10px;font-size:.92rem;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:8px}
.cg-sec-ico{width:28px;height:28px;border-radius:8px;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;font-size:.9rem;background:#e2e8f0;color:#475569}
.cg-sec-ico.upload{background:#dbeafe;color:#1d4ed8}
.cg-sec-ico.details{background:#dcfce7;color:#15803d}
.cg-sec-ico.assign{background:#ffedd5;color:#c2410c}
.cg-sec-ico.summary{background:#ede9fe;color:#6d28d9}

.cg-mid-section{padding-bottom:12px;margin-bottom:12px;border-bottom:1px solid var(--line)}
.cg-mid-section:last-child{padding-bottom:0;margin-bottom:0;border-bottom:0}

.cg-drop{border:1.5px dashed #cbd5e1;border-radius:10px;padding:18px 12px;text-align:center;background:#fcfdfd;cursor:pointer;transition:.15s}
.cg-drop:hover,.cg-drop.is-drag{border-color:#86efac;background:#f0fdf4}
.cg-drop.has-file{border-style:solid;border-color:#86efac}
.cg-drop .ico{width:42px;height:42px;border-radius:12px;margin:0 auto 6px;display:inline-flex;align-items:center;justify-content:center;background:#dcfce7;color:var(--g);font-size:1.3rem}
.cg-drop p{margin:0;font-size:.84rem;color:#475569;font-weight:600}
.cg-drop .pills{display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin:8px 0}
.cg-drop .pill{font-size:.7rem;font-weight:700;padding:3px 8px;border-radius:999px;background:#ecfdf3;color:#166534}
.cg-btn-choose{display:inline-flex;align-items:center;gap:6px;min-height:34px;padding:0 14px;border-radius:8px;border:0;background:var(--g);color:#fff;font-weight:800;font-size:.8rem;cursor:pointer}
.cg-btn-choose input{display:none}
.cg-file{margin-top:6px;font-size:.75rem;color:#64748b;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cg-file.has-file{color:#166534}
.cg-tpl{margin-top:auto;padding:12px 2px 2px;font-size:.78rem;color:#64748b;display:flex;flex-wrap:wrap;align-items:center;gap:8px;border-top:1px solid #eef3f0;padding-top:12px}
.cg-tpl .cg-tpl-label{font-weight:700}
.cg-tpl .cg-tpl-links{display:inline-flex;flex-wrap:wrap;gap:8px}
.cg-tpl a{display:inline-flex;align-items:center;gap:5px;min-height:32px;padding:0 12px;border-radius:8px;font-weight:800;font-size:.75rem;text-decoration:none;border:1px solid transparent}
.cg-btn-csv{background:#eff6ff;border-color:#93c5fd!important;color:#1d4ed8}
.cg-btn-csv:hover{background:#dbeafe;color:#1e40af}
.cg-btn-xlsx{background:#ecfdf3;border-color:#86efac!important;color:#15803d}
.cg-btn-xlsx:hover{background:#dcfce7;color:#166534}
.cg-title-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;flex-shrink:0}
.cg-btn-tpl{background:#0f766e;border-color:#0f766e;color:#fff}
.cg-btn-tpl:hover{background:#0d9488;border-color:#0d9488;color:#fff}
.cg-dd{position:relative}
.cg-dd > summary{list-style:none;cursor:pointer}
.cg-dd > summary::-webkit-details-marker{display:none}
.cg-dd > summary::after{content:"";width:.45rem;height:.45rem;margin-left:2px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg) translateY(-2px);opacity:.7}
.cg-dd[open] > summary::after{transform:rotate(-135deg) translateY(-1px)}
.cg-dd-menu{position:absolute;right:0;top:calc(100% + 6px);z-index:30;min-width:180px;padding:6px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;box-shadow:0 10px 28px rgba(15,23,42,.12)}
.cg-dd-menu a{display:flex;align-items:center;gap:8px;padding:9px 11px;border-radius:8px;font-weight:800;font-size:.82rem;text-decoration:none;border:1px solid transparent;margin-bottom:4px}
.cg-dd-menu a:last-child{margin-bottom:0}
.cg-dd-menu a i{font-size:1rem}

.cg-field{margin-bottom:10px}
.cg-field label{display:block;font-size:.75rem;font-weight:700;color:#475569;margin-bottom:4px}
.cg-field .req{color:#dc2626}
.cg-field input[type=text],.cg-field textarea,.cg-field select{
  width:100%;max-width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:.875rem;color:var(--ink);background:#fff;min-height:38px
}
.cg-field textarea{min-height:64px;height:auto;resize:vertical}
.cg-row2{display:grid;grid-template-columns:1fr;gap:8px}
.cg-assign-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
.cg-assign-tabs label{display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:.8rem;font-weight:700;color:#64748b;cursor:pointer;background:#fff}
.cg-assign-tabs label.on{border-color:#86efac;background:#f0fdf4;color:#166534}
.cg-assign-tabs input{accent-color:var(--g)}
.cg-alloc{display:flex;flex-wrap:wrap;gap:8px 12px;margin:8px 0 4px}
.cg-alloc label{font-size:.78rem;font-weight:700;color:#475569;display:inline-flex;align-items:center;gap:5px}
.cg-split{display:none;margin-top:8px;border:1px solid #eef3f0;border-radius:10px;overflow:hidden}
.cg-split.show{display:block}
.cg-split-head{display:flex;justify-content:space-between;gap:8px;align-items:center;padding:8px 12px;background:var(--soft);font-size:.78rem;font-weight:800;color:#334155}
.cg-split-head .js-split-total{font-weight:700;color:#64748b;font-size:.72rem}
.cg-split-list{max-height:220px;overflow:auto}
.cg-split-table{width:100%;border-collapse:collapse;font-size:.8rem}
.cg-split-table th{text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.03em;color:#64748b;font-weight:800;padding:8px 10px;background:#f8fafc;border-bottom:1px solid #e8eef2;white-space:nowrap}
.cg-split-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#334155}
.cg-split-table .col-check{width:36px;text-align:center}
.cg-split-table .col-pct{width:88px}
.cg-split-table .col-count{width:110px;font-weight:800}
.cg-split-table .nm{font-weight:600;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cg-split-table input[type=number]{width:72px;border:1px solid #e2e8f0;border-radius:6px;padding:4px 6px;min-height:30px;font-size:.78rem}
.cg-split-table .js-pct.hidden,.cg-split-table .js-cnt.hidden{display:none!important}
.cg-split:not(.mode-pct) .col-pct{display:none}
.cg-split.mode-equal .js-cnt{display:none!important}
.cg-split.mode-pct .js-cnt{display:none!important}
.cg-split.mode-counts .js-split-count{display:none!important}
.cg-split-table .js-split-count{display:inline-block;min-width:2rem;font-variant-numeric:tabular-nums;color:#0f172a}
.cg-meter{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;padding:8px 12px;font-size:.78rem;font-weight:700;background:#fffbeb;color:#92400e}
.cg-meter.ok{background:#ecfdf3;color:#166534}
.cg-meter.bad{background:#fef2f2;color:#b91c1c}

.cg-summary{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.cg-sum-card{display:flex;align-items:center;gap:8px;padding:10px;border-radius:10px;border:1px solid #e8eef2;background:#fff;border-left:3px solid var(--sum-accent,#94a3b8)}
.cg-sum-card .sum-ico{width:34px;height:34px;border-radius:8px;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;background:var(--sum-soft,#f1f5f9);color:var(--sum-ink,#475569);font-size:.95rem}
.cg-sum-card .l{margin:0;font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.cg-sum-card .v{margin:2px 0 0;font-size:1.15rem;font-weight:800;color:var(--sum-ink,#0f172a);line-height:1.1}
.cg-sum-card.tone-total{--sum-accent:#475569;--sum-ink:#334155;--sum-soft:#e2e8f0}
.cg-sum-card.tone-ok{--sum-accent:#16a34a;--sum-ink:#15803d;--sum-soft:#dcfce7}
.cg-sum-card.tone-warn{--sum-accent:#ea580c;--sum-ink:#c2410c;--sum-soft:#ffedd5}
.cg-sum-card.tone-bad{--sum-accent:#dc2626;--sum-ink:#b91c1c;--sum-soft:#fee2e2}
.cg-sum-note{margin:8px 0 0;font-size:.72rem;color:#94a3b8;line-height:1.35}
.cg-block-sum{background:#f8faf9}

.cg-actions{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px 16px;border-top:1px solid var(--line);background:#fbfcfc;margin:0;flex-wrap:wrap}
.cg-actions-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-left:auto}

@media (min-width:576px){
  .cg-row2{grid-template-columns:1fr 1fr}
}
@media (min-width:768px){
  .cg-page{padding:1rem 1rem 1.75rem}
  .cg-grid{grid-template-columns:0.9fr 1.4fr}
  .cg-block{border-right:1px solid var(--line);border-bottom:1px solid var(--line)}
  .cg-block:nth-child(2){border-right:0}
  .cg-block-sum{grid-column:1 / -1;border-right:0;border-bottom:0;background:#fff}
  .cg-summary{grid-template-columns:repeat(4,minmax(0,1fr))}
}
@media (min-width:1100px){
  .cg-page{padding:1rem 1.25rem 2rem}
  .cg-grid{grid-template-columns:minmax(220px,.9fr) minmax(420px,1.55fr) minmax(200px,.7fr)}
  .cg-block{border-bottom:0!important;border-right:1px solid var(--line)}
  .cg-block:last-child,.cg-block-sum{border-right:0}
  .cg-block-sum{grid-column:auto;background:#f8faf9}
  .cg-summary{grid-template-columns:1fr}
}
@media (max-width:575px){
  .cg-actions{justify-content:stretch}
  .cg-actions-right{width:100%;justify-content:stretch}
  .cg-actions .cg-btn{flex:1 1 auto;justify-content:center}
}
</style>
@endpush

@section('content')
@php
    $canPickBranch = (bool) ($canPickBranch ?? false);
    $canAssignRegion = (bool) ($canAssignRegion ?? false);
    $handoffKey = (string) (($handoff['key'] ?? '') ?: '');
    $canAssignCluster = $canPickBranch && ! in_array($handoffKey, ['cluster', 'bm'], true);
    $canAssignBranch = $canPickBranch && $handoffKey !== 'bm';
    $defaultMethod = $canAssignRegion ? 'region' : ($canAssignCluster ? 'cluster' : ($canAssignBranch ? 'branch' : 'vrm'));
@endphp
<div class="cg-page">
    <ul class="crumbs">
        <li><a href="{{ url('/') }}">Home</a></li>
        <li>›</li>
        <li><a href="{{ route('goal.leads.index') }}">Lead Management</a></li>
        <li>›</li>
        <li><a href="{{ route('goal.contacts.import') }}">Campaigns</a></li>
        <li>›</li>
        <li class="cur">Add Campaign</li>
    </ul>

    <div class="title-row">
        <div style="min-width:0;flex:1">
            <h1>Add Campaign</h1>
            <p>Import contacts, set campaign details, assign to Cluster / Branch / VRM, then track from the list.</p>
        </div>
        <div class="cg-title-actions">
            <details class="cg-dd">
                <summary class="cg-btn cg-btn-tpl"><i class="bi bi-download"></i> Template</summary>
                <div class="cg-dd-menu" role="menu">
                    <a class="cg-btn-csv" href="{{ route('goal.contacts.import.template', ['format' => 'csv']) }}" role="menuitem">
                        <i class="bi bi-filetype-csv"></i> Download CSV
                    </a>
                    <a class="cg-btn-xlsx" href="{{ route('goal.contacts.import.template', ['format' => 'xlsx']) }}" role="menuitem">
                        <i class="bi bi-file-earmark-excel"></i> Download Excel
                    </a>
                </div>
            </details>
            <a class="cg-btn" href="{{ route('goal.contacts.import') }}"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('goal.contacts.import.store') }}" enctype="multipart/form-data" id="cgCampaignForm" class="cg-card">
        @csrf
        <div class="cg-flow" aria-label="Campaign create flow">
            <div class="cg-flow-item on"><i class="bi bi-cloud-arrow-up"></i> Upload</div>
            <span class="cg-flow-sep">→</span>
            <div class="cg-flow-item on"><i class="bi bi-card-text"></i> Details</div>
            <span class="cg-flow-sep">→</span>
            <div class="cg-flow-item on"><i class="bi bi-diagram-3"></i> Assign</div>
            <span class="cg-flow-sep">→</span>
            <div class="cg-flow-item"><i class="bi bi-graph-up"></i> Track later</div>
        </div>

        <div class="cg-grid">
            <div class="cg-block">
                <h3>
                    <span class="cg-sec-ico upload"><i class="bi bi-cloud-arrow-up"></i></span>
                    Upload List
                    <span style="font-weight:600;color:#94a3b8;font-size:.68rem;margin-left:auto">max 10 MB</span>
                </h3>
                <div class="cg-drop" id="cgDrop" tabindex="0">
                    <div class="ico"><i class="bi bi-file-earmark-arrow-up"></i></div>
                    <p>Drop file or browse</p>
                    <div class="pills">
                        <span class="pill">CSV</span>
                        <span class="pill">XLSX</span>
                    </div>
                    <label class="cg-btn-choose" id="cgChooseBtn">
                        <i class="bi bi-folder2-open"></i> Choose File
                        <input type="file" name="import_file" id="import_file" accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                    </label>
                    <div class="cg-file" id="importFileName">No file chosen</div>
                </div>
                <div class="cg-tpl">
                    <span class="cg-tpl-label">Template</span>
                    <div class="cg-tpl-links">
                        <a class="cg-btn-csv" href="{{ route('goal.contacts.import.template', ['format' => 'csv']) }}"><i class="bi bi-filetype-csv"></i> CSV</a>
                        <a class="cg-btn-xlsx" href="{{ route('goal.contacts.import.template', ['format' => 'xlsx']) }}"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                    </div>
                </div>
            </div>

            <div class="cg-block cg-block-mid">
                <div class="cg-mid-section">
                    <h3>
                        <span class="cg-sec-ico details"><i class="bi bi-megaphone"></i></span>
                        Campaign Details
                    </h3>
                    <div class="cg-field">
                        <label>Campaign Name <span class="req">*</span></label>
                        <input type="text" name="campaign_name" id="cgCampaignName" value="{{ old('campaign_name') }}" placeholder="e.g. Personal Loan – Digi Campaign" required>
                    </div>
                    <div class="cg-row2">
                        <div class="cg-field">
                            <label>Lead Source</label>
                            <select name="lead_source">
                                <option value="Imported List" @selected(old('lead_source', 'Imported List') === 'Imported List')>Imported List</option>
                                <option value="Digital" @selected(old('lead_source') === 'Digital')>Digital</option>
                                <option value="Partner" @selected(old('lead_source') === 'Partner')>Partner</option>
                                <option value="Referral" @selected(old('lead_source') === 'Referral')>Referral</option>
                                <option value="Other" @selected(old('lead_source') === 'Other')>Other</option>
                            </select>
                        </div>
                        <div class="cg-field">
                            <label>Lead Status</label>
                            <select name="lead_status">
                                <option value="Future Prospect" @selected(old('lead_status', 'Future Prospect') === 'Future Prospect')>Future Prospect (dialer)</option>
                                <option value="Pending BM Allocation" @selected(old('lead_status') === 'Pending BM Allocation')>Pending BM Allocation</option>
                            </select>
                        </div>
                    </div>
                    <div class="cg-field" style="margin-bottom:0">
                        <label>Description</label>
                        <textarea name="description" rows="2" placeholder="Short note…">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="cg-mid-section">
                    <h3>
                        <span class="cg-sec-ico assign"><i class="bi bi-people"></i></span>
                        Assign To
                    </h3>
                                    @include('goal.contacts.partials.assign-form', [
                                        'formId' => null,
                                        'formAction' => null,
                                        'submitLabel' => null,
                                        'showAllMethods' => $canPickBranch,
                                        'canAssignRegion' => $canAssignRegion,
                                        'canAssignCluster' => $canAssignCluster,
                                        'canAssignBranch' => $canAssignBranch,
                                        'handoffKey' => $handoffKey,
                                        'defaultMethod' => $defaultMethod,
                                        'validCount' => 0,
                                        'embedInNew' => true,
                                    ])
                </div>
            </div>

            <div class="cg-block cg-block-sum">
                <h3>
                    <span class="cg-sec-ico summary"><i class="bi bi-bar-chart-line"></i></span>
                    Summary
                </h3>
                <div class="cg-summary">
                    <div class="cg-sum-card tone-total">
                        <span class="sum-ico"><i class="bi bi-people-fill"></i></span>
                        <div class="sum-meta">
                            <p class="l">Total</p>
                            <p class="v" id="sumTotal">0</p>
                        </div>
                    </div>
                    <div class="cg-sum-card tone-ok">
                        <span class="sum-ico"><i class="bi bi-check2-circle"></i></span>
                        <div class="sum-meta">
                            <p class="l">Valid</p>
                            <p class="v" id="sumValid">0</p>
                        </div>
                    </div>
                    <div class="cg-sum-card tone-warn">
                        <span class="sum-ico"><i class="bi bi-files"></i></span>
                        <div class="sum-meta">
                            <p class="l">Duplicates</p>
                            <p class="v" id="sumDup">0</p>
                        </div>
                    </div>
                    <div class="cg-sum-card tone-bad">
                        <span class="sum-ico"><i class="bi bi-x-octagon"></i></span>
                        <div class="sum-meta">
                            <p class="l">Invalid</p>
                            <p class="v" id="sumInvalid">0</p>
                        </div>
                    </div>
                </div>
                <p class="cg-sum-note">Preview estimates. Final after Import &amp; Assign.</p>
            </div>
        </div>

        <div class="cg-actions">
            <a class="cg-btn" href="{{ route('goal.contacts.import') }}">Cancel</a>
            <div class="cg-actions-right">
                <button type="reset" class="cg-btn" id="cgResetBtn"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                <button type="submit" class="cg-btn cg-btn-primary" id="cgSubmitBtn" disabled>
                    <i class="bi bi-cloud-upload"></i> <span id="cgSubmitLabel">Import &amp; Assign</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function notify(type, message, title) {
        if (window.toastr && typeof toastr[type] === 'function') { toastr[type](message); return; }
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: type === 'error' ? 'error' : 'success', title: title || 'Campaigns', text: message, confirmButtonColor: '#16a34a' });
            return;
        }
        alert(message);
    }
    @if($errors->any())
    notify('error', @json($errors->first()), 'Error');
    @endif

    var input = document.getElementById('import_file');
    var nameEl = document.getElementById('importFileName');
    var drop = document.getElementById('cgDrop');
    var chooseBtn = document.getElementById('cgChooseBtn');
    var submitBtn = document.getElementById('cgSubmitBtn');
    var submitLabel = document.getElementById('cgSubmitLabel');
    var form = document.getElementById('cgCampaignForm');
    var allowed = ['csv', 'xlsx', 'xls'];

    function applyPreviewCounts(total, valid, dup, invalid) {
        setSummary(total, valid, dup, invalid);
        window.__cgValidEstimate = valid;
        document.querySelectorAll('[data-valid-total]').forEach(function (el) {
            el.setAttribute('data-valid-total', String(valid));
            el.max = String(valid);
        });
        var meter = document.getElementById('asSplitMeter');
        if (meter) meter.setAttribute('data-total', String(valid));
        if (typeof window.cgRefreshSplitHints === 'function') window.cgRefreshSplitHints(valid);
    }

    function setSummary(total, valid, dup, invalid) {
        var t = document.getElementById('sumTotal');
        var v = document.getElementById('sumValid');
        var d = document.getElementById('sumDup');
        var i = document.getElementById('sumInvalid');
        if (t) t.textContent = String(total);
        if (v) v.textContent = String(valid);
        if (d) d.textContent = String(dup);
        if (i) i.textContent = String(invalid);
        var flowItems = document.querySelectorAll('.cg-flow-item');
        if (flowItems.length >= 4) {
            flowItems[0].classList.add('on');
            flowItems[1].classList.add('on');
            flowItems[2].classList.add('on');
            flowItems[3].classList.toggle('on', Number(total) > 0);
        }
    }

    function mobileColIndex(headerRow) {
        var aliases = ['mobile', 'mobile number', 'phone', 'phone number', 'contact'];
        for (var i = 0; i < headerRow.length; i++) {
            var key = String(headerRow[i] == null ? '' : headerRow[i]).trim().toLowerCase().replace(/[_-]+/g, ' ');
            if (aliases.indexOf(key) !== -1) return i;
        }
        return headerRow.length > 1 ? 1 : 0;
    }

    function summarizeRows(rows) {
        if (!rows || !rows.length) {
            applyPreviewCounts(0, 0, 0, 0);
            return;
        }
        var header = rows[0] || [];
        var mobileIdx = mobileColIndex(header);
        var mobiles = {};
        var total = 0, dup = 0, invalid = 0;
        for (var i = 1; i < rows.length; i++) {
            var row = rows[i] || [];
            var hasData = false;
            for (var c = 0; c < row.length; c++) {
                if (String(row[c] == null ? '' : row[c]).trim() !== '') { hasData = true; break; }
            }
            if (!hasData) continue;
            total++;
            var mobile = String(row[mobileIdx] == null ? '' : row[mobileIdx]).replace(/[^0-9]/g, '');
            if (!mobile || mobile.length < 8) { invalid++; continue; }
            if (mobiles[mobile]) dup++; else mobiles[mobile] = 1;
        }
        applyPreviewCounts(total, Math.max(0, total - dup - invalid), dup, invalid);
    }

    function previewCsv(file) {
        var reader = new FileReader();
        reader.onload = function () {
            var text = String(reader.result || '');
            var lines = text.split(/\r?\n/).filter(function (l) { return l.trim() !== ''; });
            var rows = lines.map(function (line) {
                return line.split(',').map(function (cell) { return cell.replace(/^"|"$/g, '').trim(); });
            });
            summarizeRows(rows);
        };
        reader.readAsText(file.slice(0, 2 * 1024 * 1024));
    }

    function loadXlsxLib(done) {
        if (window.XLSX) { done(); return; }
        if (window.__cgXlsxLoading) {
            window.__cgXlsxLoading.push(done);
            return;
        }
        window.__cgXlsxLoading = [done];
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
        s.onload = function () {
            var q = window.__cgXlsxLoading || [];
            window.__cgXlsxLoading = null;
            q.forEach(function (fn) { try { fn(); } catch (e) {} });
        };
        s.onerror = function () {
            window.__cgXlsxLoading = null;
            notify('error', 'Could not load Excel preview. Summary will show after Import.', 'Preview');
            setSummary('—', '—', '—', '—');
        };
        document.head.appendChild(s);
    }

    function previewExcel(file) {
        setSummary('…', '…', '…', '…');
        loadXlsxLib(function () {
            var reader = new FileReader();
            reader.onload = function () {
                try {
                    var wb = window.XLSX.read(reader.result, { type: 'array', dense: true });
                    var sheet = wb.Sheets[wb.SheetNames[0]];
                    var rows = window.XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '', raw: false });
                    summarizeRows(rows);
                } catch (e) {
                    notify('error', 'Could not read Excel file for preview.', 'Preview');
                    setSummary('—', '—', '—', '—');
                }
            };
            reader.onerror = function () {
                setSummary('—', '—', '—', '—');
            };
            reader.readAsArrayBuffer(file);
        });
    }

    function setFile(file) {
        if (!file) return;
        var ext = (file.name.split('.').pop() || '').toLowerCase();
        if (allowed.indexOf(ext) === -1) {
            notify('error', 'Please choose a .csv, .xlsx, or .xls file.', 'Invalid file');
            input.value = '';
            return;
        }
        try {
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        } catch (e) {}
        nameEl.textContent = file.name + ' · ' + Math.max(1, Math.round(file.size / 1024)) + ' KB';
        nameEl.classList.add('has-file');
        drop.classList.add('has-file');
        if (submitBtn) submitBtn.disabled = false;
        if (submitLabel) submitLabel.textContent = 'Import & Assign';
        notify('success', 'File selected: ' + file.name, 'File ready');
        if (ext === 'csv') previewCsv(file);
        else previewExcel(file);
    }

    if (input && drop && nameEl) {
        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            if (f) setFile(f);
            else {
                nameEl.textContent = 'No file chosen';
                nameEl.classList.remove('has-file');
                drop.classList.remove('has-file');
                if (submitBtn) submitBtn.disabled = true;
                setSummary(0, 0, 0, 0);
            }
        });
        drop.addEventListener('click', function (e) {
            if (chooseBtn && (chooseBtn === e.target || chooseBtn.contains(e.target))) return;
            input.click();
        });
        ['dragenter', 'dragover'].forEach(function (evt) {
            drop.addEventListener(evt, function (e) { e.preventDefault(); drop.classList.add('is-drag'); });
        });
        ['dragleave', 'dragend', 'drop'].forEach(function (evt) {
            drop.addEventListener(evt, function (e) {
                e.preventDefault();
                drop.classList.remove('is-drag');
                if (evt === 'drop' && e.dataTransfer && e.dataTransfer.files[0]) setFile(e.dataTransfer.files[0]);
            });
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            function stop(msg) {
                e.preventDefault();
                notify('error', msg, 'Almost there');
                if (submitBtn) submitBtn.disabled = false;
                if (submitLabel) submitLabel.textContent = 'Import & Assign';
            }
            if (!input.files || !input.files[0]) {
                stop('Please choose a file first.');
                return;
            }
            var name = document.getElementById('cgCampaignName');
            if (name && !name.value.trim()) {
                stop('Enter a campaign name.');
                if (name.focus) name.focus();
                return;
            }
            var sumValidEl = document.getElementById('sumValid');
            var sumTxt = sumValidEl ? String(sumValidEl.textContent || '').trim() : '';
            var previewReady = sumTxt !== '' && sumTxt !== '—' && sumTxt !== '…';
            var validEst = typeof window.__cgValidEstimate === 'number'
                ? window.__cgValidEstimate
                : (previewReady ? (parseInt(sumTxt, 10) || 0) : null);
            if (validEst === 0) {
                stop('No valid contacts in the file. Fix the list or choose another file.');
                return;
            }
            if (typeof window.cgValidateAssign === 'function' && !window.cgValidateAssign(e, true)) {
                if (submitBtn) submitBtn.disabled = false;
                if (submitLabel) submitLabel.textContent = 'Import & Assign';
                return;
            }
            if (submitBtn) { submitBtn.disabled = true; }
            if (submitLabel) submitLabel.textContent = 'Importing…';
        });
        form.addEventListener('reset', function () {
            setTimeout(function () {
                nameEl.textContent = 'No file chosen';
                nameEl.classList.remove('has-file');
                drop.classList.remove('has-file');
                if (submitBtn) submitBtn.disabled = true;
                if (submitLabel) submitLabel.textContent = 'Import & Assign';
                setSummary(0, 0, 0, 0);
            }, 0);
        });
    }
})();
</script>
@endpush
