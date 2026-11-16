@extends('layouts.app')

@php
    $cd = old('customer_details', $case->customer_details ?? []) ?: [];
    $br = old('bm_reco', $case->bm_reco ?? []) ?: [];
    $col = old('collateral', $case->collateral ?? []) ?: [];
    $loans = old('existing_loans', $case->existing_loans ?? []) ?: [];
    $mis = old('branch_mis', $case->branch_mis ?? []) ?: [];
    $docs = $cd['documents'] ?? [];
    $app = $cd['applicant'] ?? [];
    $co = $cd['co_applicant'] ?? [];
    $res = $cd['residence'] ?? [];
    $emp = $cd['employment'] ?? [];
    $prop = $cd['property'] ?? [];
    $refs = $cd['references'] ?? [];
    $bt = $loans['bt_source'] ?? [];
    $loanRows = $loans['rows'] ?? [['type' => '', 'bank' => '', 'sanction' => '', 'outstanding' => '', 'emi' => '', 'paid' => '', 'pending' => '', 'bounces' => '']];
    if ($loanRows === []) {
        $loanRows = [['type' => '', 'bank' => '', 'sanction' => '', 'outstanding' => '', 'emi' => '', 'paid' => '', 'pending' => '', 'bounces' => '']];
    }
    $consent = $cd['consent'] ?? null;
    $canEdit = $canEdit ?? false;
    $canDisburse = $canDisburse ?? false;
    $canReassign = $canReassign ?? false;
    $canVerifyDocs = $canVerifyDocs ?? false;
    $canPms = $canPms ?? false;
    $ro = $canEdit ? '' : 'readonly';
    $dis = $canEdit ? '' : 'disabled';
    $roleLabel = function ($user) {
        if (!$user) return null;
        return match ((int) ($user->crm_role_type ?? -1)) {
            8 => 'BM', 10 => 'JSM', 11 => 'SM', 12 => 'SSM',
            default => strtoupper((string) ($user->job_role ?: 'User')),
        };
    };
    $statusClass = match ($case->status) {
        'Draft' => 'draft',
        'Ready for Login', 'Bank Login' => 'login',
        'Bank Query', 'Processing' => 'process',
        'Sanctioned' => 'ok',
        'Disbursed', 'PMS' => 'done',
        'Rejected', 'Closed' => 'bad',
        default => 'draft',
    };
    $fmtInr = fn ($n) => $n !== null && $n !== '' ? ('₹ '.number_format((float) $n, 0)) : '—';
    $consentLabel = (is_array($consent) && !empty($consent['recorded']))
        ? 'Recorded'
        : ($case->lead?->consent_recorded ? 'Recorded' : 'Not recorded');
@endphp

@push('styles')
<style>
.cw-page{
    --cw-primary:#245BEB;--cw-purple:#7542F5;--cw-border:#E3EAF5;--cw-text:#0f172a;--cw-muted:#64748b;
    color:var(--cw-text);padding-bottom:96px;
}
.cw-page .cw-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:nowrap;margin-bottom:1rem}
.cw-page .cw-back{
    width:42px;height:42px;border-radius:999px;border:1px solid var(--cw-border);background:#fff;
    color:#1e3a8a;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0;
}
.cw-page .cw-title{margin:0;font-size:1.45rem;font-weight:800;letter-spacing:-.02em;line-height:1.2}
.cw-page .cw-subtitle{margin:.35rem 0 0;font-size:.86rem;color:var(--cw-muted);font-weight:500}
.cw-page .cw-crumb{margin-top:6px;font-size:.78rem;color:var(--cw-muted)}
.cw-page .cw-crumb a{color:var(--cw-muted);text-decoration:none}
.cw-page .cw-actions{display:flex;gap:8px;flex-wrap:nowrap;align-items:center;justify-content:flex-end;flex-shrink:0}
.cw-page .cw-actions .dropdown{flex-shrink:0}
.cw-page .btn-ghost{
    height:40px;padding:0 14px;border-radius:10px;font-weight:700;font-size:.84rem;white-space:nowrap;
    border:1px solid var(--cw-border);background:#fff;color:#334155;text-decoration:none;
    display:inline-flex;align-items:center;gap:6px;
}
.cw-page .btn-primary-cw{
    height:40px;padding:0 16px;border-radius:10px;font-weight:700;font-size:.84rem;border:0;color:#fff;white-space:nowrap;
    background:var(--cw-primary);display:inline-flex;align-items:center;gap:6px;
}
.cw-page .btn-link-cw{
    border:0;background:transparent;color:var(--cw-primary);font-weight:700;font-size:.82rem;
    display:inline-flex;align-items:center;gap:4px;padding:0;cursor:pointer;text-decoration:none;
}
.cw-page .case-ribbon{
    display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:12px 0;
    background:#fff;border:1px solid var(--cw-border);border-radius:16px;padding:16px 8px;margin-bottom:1rem;
    box-shadow:0 8px 24px rgba(148,163,184,.08);
}
.cw-page .case-ribbon > div{
    padding:0 14px;border-right:1px solid #eef2f7;min-width:0;
}
.cw-page .case-ribbon > div:nth-child(8n){border-right:0}
.cw-page .case-ribbon .rb-value{flex-wrap:wrap}
@media (max-width:1399.98px){
    .cw-page .case-ribbon{grid-template-columns:repeat(4,minmax(0,1fr));row-gap:14px}
    .cw-page .case-ribbon > div:nth-child(4n){border-right:0}
    .cw-page .case-ribbon > div:nth-child(8n){border-right:0}
}
@media (max-width:991.98px){
    .cw-page .cw-hero{flex-wrap:wrap}
    .cw-page .cw-actions{flex-wrap:wrap;width:100%;justify-content:flex-start}
    .cw-page .case-ribbon{grid-template-columns:repeat(2,minmax(0,1fr))}
    .cw-page .case-ribbon > div{border-right:0;border-bottom:1px solid #eef2f7;padding-bottom:10px}
    .cw-page .case-ribbon > div:nth-last-child(-n+2){border-bottom:0}
}
.cw-page .case-ribbon .rb-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--cw-muted)}
.cw-page .case-ribbon .rb-value{font-size:.9rem;font-weight:700;margin-top:2px;display:flex;align-items:center;gap:6px}
.cw-page .status-pill{
    display:inline-flex;align-items:center;padding:.28rem .7rem;border-radius:999px;font-size:.74rem;font-weight:700;background:#f1f5f9;color:#334155;
}
.cw-page .status-pill.draft{background:#f1f5f9;color:#475569}
.cw-page .status-pill.login{background:#eff6ff;color:#1d4ed8}
.cw-page .status-pill.process{background:#fff7ed;color:#c2410c}
.cw-page .status-pill.ok{background:#ecfdf5;color:#059669}
.cw-page .status-pill.done{background:#dbeafe;color:#1e40af}
.cw-page .status-pill.bad{background:#fef2f2;color:#dc2626}
.cw-page .cw-progress{
    display:flex;align-items:center;gap:0;margin-bottom:1rem;padding:16px 18px;
    background:#fff;border:1px solid var(--cw-border);border-radius:16px;overflow-x:auto;
}
.cw-page .cw-step{
    flex:1;min-width:88px;text-align:center;position:relative;padding:0 4px;
    font-size:.72rem;font-weight:700;color:#94a3b8;
}
.cw-page .cw-step .dot{
    width:28px;height:28px;border-radius:999px;margin:0 auto 6px;display:flex;align-items:center;justify-content:center;
    background:#f1f5f9;border:2px solid #e2e8f0;color:#94a3b8;font-size:.75rem;
}
.cw-page .cw-step.done{color:#059669}
.cw-page .cw-step.done .dot{background:#ecfdf5;border-color:#34d399;color:#059669}
.cw-page .cw-step.active{color:#1d4ed8}
.cw-page .cw-step.active .dot{background:#eff6ff;border-color:#3b82f6;color:#1d4ed8;box-shadow:0 0 0 4px rgba(59,130,246,.15)}
.cw-page .cw-step.rejected{color:#dc2626}
.cw-page .cw-step.rejected .dot{background:#fef2f2;border-color:#f87171;color:#dc2626}
.cw-page .cw-step:not(:last-child)::after{
    content:'';position:absolute;top:14px;left:calc(50% + 18px);right:calc(-50% + 18px);height:2px;background:#e2e8f0;z-index:0;
}
.cw-page .cw-step.done:not(:last-child)::after{background:#34d399}
.cw-page .cw-tabs{display:flex;gap:6px;overflow-x:auto;padding-bottom:2px;margin-bottom:1rem;scrollbar-width:thin}
.cw-page .cw-tab{
    flex:0 0 auto;border:1px solid var(--cw-border);background:#fff;color:#334155;
    border-radius:999px;padding:8px 14px;font-size:.8rem;font-weight:700;
    display:inline-flex;align-items:center;gap:6px;cursor:pointer;
}
.cw-page .cw-tab.active{background:var(--cw-primary);color:#fff;border-color:transparent}
.cw-page .cw-pane{display:none !important}
.cw-page .cw-pane.active{display:block !important}
.cw-page .sec-card{
    background:#fff;border:1px solid var(--cw-border);border-radius:16px;overflow:hidden;
    box-shadow:0 8px 24px rgba(148,163,184,.10);margin-bottom:1rem;
}
.cw-page .sec-head{
    display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;
    border-bottom:1px solid #eef2f7;background:#fff;
}
.cw-page .sec-head-left{display:flex;align-items:center;gap:12px}
.cw-page .sec-ico{
    width:38px;height:38px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;
    background:#ede9fe;color:var(--cw-purple);font-size:1rem;flex-shrink:0;
}
.cw-page .sec-ico.ico-blue{background:#dbeafe;color:var(--cw-primary)}
.cw-page .sec-ico.ico-amber{background:#fef3c7;color:#d97706}
.cw-page .sec-ico.ico-green{background:#dcfce7;color:#16a34a}
.cw-page .sec-ico.ico-teal{background:#ccfbf1;color:#0d9488}
.cw-page .sec-head h6{margin:0;font-size:.98rem;font-weight:800}
.cw-page .sec-sub{font-size:.78rem;color:var(--cw-muted)}
.cw-page .sec-body{padding:16px 18px 18px}
.cw-page .kv-stack{display:flex;flex-direction:column;gap:14px}
.cw-page .kv-label{font-size:.68rem;font-weight:700;color:var(--cw-muted);text-transform:uppercase;letter-spacing:.04em}
.cw-page .kv-value{font-size:.9rem;font-weight:600;margin-top:3px;word-break:break-word;display:flex;align-items:center;gap:8px}
.cw-page .kv-value i{color:#64748b;font-size:.9rem}
.cw-page .own-row{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px}
.cw-page .own-row:last-child{margin-bottom:0}
.cw-page .own-ico{
    width:34px;height:34px;border-radius:10px;background:#f1f5f9;color:#475569;
    display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.95rem;
}
.cw-page .split-cols{display:grid;grid-template-columns:1fr 1fr;gap:28px}
.cw-page .split-cols .col-title{font-size:.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;margin-bottom:14px}
.cw-page .split-cols .col-title.cust{color:var(--cw-purple)}
.cw-page .split-cols .col-title.loan{color:var(--cw-primary)}
.cw-page .query-actions{display:flex;justify-content:flex-end;margin-top:12px}
.cw-page .ws-section-title{
    font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;
    background:#f8fafc;padding:.55rem .85rem;border-bottom:1px solid #e5e7eb;border-top:1px solid #e5e7eb;
}
.cw-page .ws-table{margin:0}
.cw-page .ws-table td:first-child{width:28%;font-weight:600;color:#334155;background:#fafbfc;vertical-align:middle;font-size:.84rem}
.cw-page .form-label{font-size:.76rem;font-weight:700;color:#475569;margin-bottom:6px}
.cw-page .form-control,.cw-page .form-select{
    min-height:42px;border-radius:10px;border:1px solid var(--cw-border);background:#f8fafc;font-size:.88rem;
}
.cw-page .form-control:focus,.cw-page .form-select:focus{border-color:#93c5fd;background:#fff;box-shadow:0 0 0 3px rgba(36,91,235,.12)}
.cw-page .form-control:disabled,.cw-page .form-control[readonly]{background:#eef4ff;color:#64748b}
.cw-page .doc-badge{display:inline-block;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:999px;background:#eff6ff;color:var(--cw-primary)}
.cw-page .doc-badge.ok{background:#ecfdf5;color:#059669}
.cw-page .doc-badge.warn{background:#fff7ed;color:#c2410c}
.cw-page .doc-badge.bad{background:#fef2f2;color:#dc2626}
.cw-page .kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:10px;margin-bottom:1rem}
.cw-page .kpi{background:#fff;border:1px solid var(--cw-border);border-radius:12px;padding:12px 14px}
.cw-page .kpi .n{font-size:1.25rem;font-weight:800}
.cw-page .kpi .l{font-size:.72rem;color:var(--cw-muted);font-weight:600}
.cw-page .empty-state{text-align:center;padding:28px 16px;color:var(--cw-muted)}
.cw-page .empty-state i{font-size:2rem;opacity:.45;display:block;margin-bottom:8px}
.cw-page .timeline{list-style:none;margin:0;padding:0}
.cw-page .timeline li{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #eef2f7}
.cw-page .timeline li:last-child{border-bottom:0}
.cw-page .tl-dot{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#ede9fe;color:var(--cw-purple)}
.cw-page .tl-dot.green{background:#dcfce7;color:#16a34a}
.cw-page .tl-dot.blue{background:#dbeafe;color:var(--cw-primary)}
.cw-page .tl-dot.amber{background:#fef3c7;color:#d97706}
.cw-page .query-alert{border-left:4px solid #f59e0b}
.cw-page .cw-pane.active .cw-footer{
    position:sticky;bottom:58px;z-index:30;width:100%;
    margin:8px 0;border:1px solid var(--cw-border);border-radius:16px;overflow:hidden;
    background:#fff;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;
    box-shadow:0 8px 22px rgba(148,163,184,.14);
}
.cw-page .cw-footer{display:none}
.cw-page .btn-save{
    height:42px;padding:0 18px;border-radius:10px;font-weight:700;font-size:.86rem;border:0;color:#fff;
    background:linear-gradient(90deg,var(--cw-primary),var(--cw-purple));
    display:inline-flex;align-items:center;gap:6px;
}
.cw-page .hint{font-size:.78rem;color:var(--cw-muted);margin-top:6px}
.cw-page .hint.err{color:#b91c1c}
.cw-page .cw-extra-section{margin-top:1rem}
.cw-page .table-sm td,.cw-page .table-sm th{font-size:.82rem;vertical-align:middle}
@media (max-width:991.98px){.cw-page .split-cols{grid-template-columns:1fr}}
@media (max-width:767.98px){.cw-page .cw-step{min-width:72px}}
</style>
@endpush

@section('content')
<div class="container-fluid p-4 cw-page" id="cwPage" data-active-tab="{{ $activeTab }}">
    {{-- Header --}}
    <div class="cw-hero">
        <div class="d-flex align-items-start gap-3">
            <a href="{{ route('goal.cases.index') }}" class="cw-back" title="Back"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h1 class="cw-title">Loan Case Workspace</h1>
                <p class="cw-subtitle">Manage customer information, documents and loan processing milestones.</p>
                <div class="cw-crumb">
                    <a href="{{ url('/') }}">Home</a> &gt;
                    <a href="{{ route('goal.cases.index') }}">Loan Cases</a> &gt;
                    Case Details
                </div>
            </div>
        </div>
        <div class="cw-actions">
            @if($canEdit)
                <button type="button" class="btn-ghost" data-cw-tab-jump="customer"><i class="bi bi-pencil"></i> Edit Case</button>
            @endif
            @if($canReassign)
                <button type="button" class="btn-ghost" data-bs-toggle="modal" data-bs-target="#cwReassignModal"><i class="bi bi-people"></i> Reassign</button>
            @endif
            @if($canEdit)
                <button type="button" class="btn-primary-cw" data-bs-toggle="modal" data-bs-target="#cwAddLenderModal"><i class="bi bi-plus-lg"></i> Add Lender Application</button>
            @endif
            <div class="dropdown">
                <button class="btn-ghost dropdown-toggle" type="button" data-bs-toggle="dropdown">Actions</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @if($case->lead)
                        <li><a class="dropdown-item" href="{{ route('goal.leads.show', $case->lead) }}"><i class="bi bi-link-45deg me-1"></i> View Lead</a></li>
                    @endif
                    <li><a class="dropdown-item" href="#" data-cw-tab-jump="activity" data-cw-filter="assignment"><i class="bi bi-clock-history me-1"></i> Assignment History</a></li>
                    <li><a class="dropdown-item" href="#" data-cw-tab-jump="activity"><i class="bi bi-journal-text me-1"></i> Audit History</a></li>
                    @if($canEdit && $next !== [])
                        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#cwAdvanceStatusModal"><i class="bi bi-arrow-right-circle me-1"></i> Advance Status</button></li>
                    @endif
                    @if($canPms && $case->status === 'Disbursed')
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('goal.cases.pms-transfer', $case) }}" onsubmit="return confirm('Transfer eligible case to PMS?')">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-1"></i> Transfer to PMS</button>
                            </form>
                        </li>
                    @endif
                    @if($canEdit && !in_array($case->status, ['Closed', 'Rejected'], true))
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('goal.cases.transition', $case) }}" onsubmit="return confirm('Close this case?')">
                                @csrf
                                <input type="hidden" name="to_status" value="Closed">
                                <input type="hidden" name="rejection_reason" value="Closed via Actions">
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle me-1"></i> Close Case</button>
                            </form>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- Case summary ribbon --}}
    <div class="case-ribbon">
        <div>
            <div class="rb-label">Case Reference</div>
            <div class="rb-value"><i class="bi bi-briefcase text-primary"></i> {{ $case->case_no }}</div>
        </div>
        <div>
            <div class="rb-label">Customer</div>
            <div class="rb-value"><i class="bi bi-person text-muted"></i> {{ $case->customer_name }}</div>
        </div>
        <div>
            <div class="rb-label">Product</div>
            <div class="rb-value"><i class="bi bi-box-seam text-muted"></i> {{ $case->product ?: '—' }}</div>
        </div>
        <div>
            <div class="rb-label">Requested Amount</div>
            <div class="rb-value"><i class="bi bi-currency-rupee text-muted"></i> {{ $fmtInr($case->loan_amount) }}</div>
        </div>
        <div>
            <div class="rb-label">Branch</div>
            <div class="rb-value"><i class="bi bi-building text-muted"></i> {{ $case->orgUnit?->name ?? '—' }}</div>
        </div>
        <div>
            <div class="rb-label">Owner</div>
            <div class="rb-value"><i class="bi bi-person-badge text-muted"></i> {{ $roleLabel($case->owner) }} — {{ $case->owner?->name ?? '—' }}</div>
        </div>
        <div>
            <div class="rb-label">Lead Reference</div>
            <div class="rb-value">
                <i class="bi bi-link-45deg text-muted"></i>
                @if($case->lead)
                    <a href="{{ route('goal.leads.show', $case->lead) }}">{{ $case->lead->ref_no }}</a>
                @else — @endif
            </div>
        </div>
        <div>
            <div class="rb-label">Status</div>
            <div class="rb-value"><span class="status-pill {{ $statusClass }}">{{ $case->status }}</span></div>
        </div>
    </div>

    {{-- Progress tracker (informational only) --}}
    <div class="cw-progress" aria-label="Case progress">
        @foreach($progress as $step)
            <div class="cw-step {{ $step['state'] }}" aria-disabled="true">
                <div class="dot">
                    @if($step['state'] === 'done')
                        <i class="bi bi-check-lg"></i>
                    @elseif($step['state'] === 'active')
                        <i class="bi bi-bullseye"></i>
                    @elseif($step['state'] === 'rejected')
                        <i class="bi bi-x-lg"></i>
                    @else
                        <i class="bi bi-circle"></i>
                    @endif
                </div>
                {{ $step['label'] }}
            </div>
        @endforeach
    </div>

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger py-2 small">
            <strong>Please fix:</strong>
            <ul class="mb-0 mt-1">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif

    {{-- Tabs --}}
    <div class="cw-tabs" role="tablist">
        @foreach([
            'overview' => ['Overview', 'bi-briefcase'],
            'customer' => ['Customer', 'bi-person-vcard'],
            'documents' => ['Documents', 'bi-file-earmark-text'],
            'lenders' => ['Lender Applications', 'bi-bank'],
            'queries' => ['Bank Queries', 'bi-chat-left-dots'],
            'sanction' => ['Sanction', 'bi-award'],
            'disbursement' => ['Disbursement', 'bi-cash-stack'],
            'activity' => ['Activity History', 'bi-clock-history'],
        ] as $key => $meta)
            <button type="button" class="cw-tab {{ $activeTab === $key ? 'active' : '' }}" data-cw-tab="{{ $key }}">
                <i class="bi {{ $meta[1] }}"></i> {{ $meta[0] }}
            </button>
        @endforeach
    </div>

    {{-- ===================== OVERVIEW ===================== --}}
    @php $em = $app['email'] ?? ($cd['email'] ?? null); @endphp
    <div class="cw-pane {{ $activeTab === 'overview' ? 'active' : '' }}" data-pane="overview">
        <div class="row g-3">
            <div class="col-xl-8 col-lg-7">
                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico"><i class="bi bi-person-vcard"></i></span>
                            <div>
                                <h6>Customer &amp; Loan Details</h6>
                                <div class="sec-sub">Summary from the customer record</div>
                            </div>
                        </div>
                        @if($canEdit)
                            <button type="button" class="btn-link-cw" data-cw-tab-jump="customer"><i class="bi bi-pencil"></i> Edit</button>
                        @endif
                    </div>
                    <div class="sec-body">
                        <div class="split-cols">
                            <div>
                                <div class="col-title cust">Customer</div>
                                <div class="kv-stack">
                                    <div>
                                        <div class="kv-label">Customer Reference</div>
                                        <div class="kv-value">{{ data_get($cd, 'customer_ref', 'CUS-'.str_pad((string)$case->id, 6, '0', STR_PAD_LEFT)) }}</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Name</div>
                                        <div class="kv-value">{{ $case->customer_name }}</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Mobile</div>
                                        <div class="kv-value">@if($case->mobile)<i class="bi bi-telephone"></i> +91 {{ $case->mobile }}@else — @endif</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Email</div>
                                        <div class="kv-value">@if($em)<i class="bi bi-envelope"></i> <a href="mailto:{{ $em }}">{{ $em }}</a>@else — @endif</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Preferred Contact Time</div>
                                        <div class="kv-value">{{ $cd['preferred_contact_time'] ?? '—' }}</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Consent</div>
                                        <div class="kv-value"><span class="doc-badge {{ $consentLabel === 'Recorded' ? 'ok' : 'warn' }}">{{ $consentLabel }}</span></div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="col-title loan">Loan</div>
                                <div class="kv-stack">
                                    <div>
                                        <div class="kv-label">Product</div>
                                        <div class="kv-value"><span class="doc-badge">{{ $case->product ?: '—' }}</span></div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Requested Amount</div>
                                        <div class="kv-value">{{ $fmtInr($case->loan_amount) }}</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Purpose</div>
                                        <div class="kv-value">{{ $cd['purpose'] ?? data_get($cd, 'loan.purpose', '—') }}</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Tenure</div>
                                        <div class="kv-value">@php $ten = data_get($cd, 'tenure', data_get($cd, 'loan.tenure')); @endphp{{ $ten ? $ten.' months' : '—' }}</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Employment Type</div>
                                        <div class="kv-value">{{ $emp['type_applicant'] ?? ($cd['employment_type'] ?? '—') }}</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Monthly Income</div>
                                        <div class="kv-value">{{ $fmtInr($emp['income_applicant'] ?? ($cd['monthly_income'] ?? null)) }}</div>
                                    </div>
                                    <div>
                                        <div class="kv-label">Existing Obligations</div>
                                        <div class="kv-value">{{ $fmtInr($cd['obligations'] ?? null) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico ico-blue"><i class="bi bi-bank"></i></span>
                            <div>
                                <h6>Lender Applications</h6>
                            </div>
                        </div>
                        @if($canEdit)
                            <button type="button" class="btn-link-cw" data-bs-toggle="modal" data-bs-target="#cwAddLenderModal"><i class="bi bi-plus-lg"></i> Add Application</button>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Lender</th>
                                    <th>Application Reference</th>
                                    <th>Login Date</th>
                                    <th>Status</th>
                                    <th>Assigned Follow-up</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($case->bankApplications->take(5) as $ba)
                                    <tr>
                                        <td class="fw-semibold"><i class="bi bi-bank2 text-primary me-1"></i>{{ $ba->lender_name }}</td>
                                        <td>{{ $ba->application_ref ?: '—' }}</td>
                                        <td>{{ optional($ba->login_date)->format('d M Y') ?: '—' }}</td>
                                        <td><span class="status-pill login">{{ $ba->status }}</span></td>
                                        <td>{{ optional($ba->follow_up_at)->format('d M Y') ?: '—' }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn-ghost" style="height:30px;padding:0 8px" data-cw-tab-jump="lenders" title="Open"><i class="bi bi-three-dots-vertical"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No lender applications yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="sec-body pt-2 pb-3"><div class="hint mb-0">A case can have multiple lender applications.</div></div>
                </div>

                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico"><i class="bi bi-file-earmark-check"></i></span>
                            <div>
                                <h6>Documents</h6>
                            </div>
                        </div>
                        <button type="button" class="btn-link-cw" data-cw-tab-jump="documents">View All →</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Document Type</th>
                                    <th>Uploaded</th>
                                    <th>Verification Status</th>
                                    <th>Reviewer</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(collect($docs)->take(5) as $doc)
                                    @php
                                        $st = $doc['status'] ?? 'Pending';
                                        $badge = match($st){ 'Verified'=>'ok', 'Rejected'=>'bad', default=>'warn' };
                                        $path = $doc['path'] ?? ($doc['storage_path'] ?? null);
                                    @endphp
                                    <tr>
                                        <td>{{ $doc['label'] ?? $doc['key'] ?? 'Document' }}</td>
                                        <td>{{ $doc['uploaded_at'] ?? '—' }}</td>
                                        <td>
                                            <span class="doc-badge {{ $badge }}">{{ $st }}</span>
                                            @if($st === 'Rejected' && !empty($doc['rejection_reason']))
                                                <div class="hint">Reason available</div>
                                            @endif
                                        </td>
                                        <td>{{ $doc['reviewer'] ?? '—' }}</td>
                                        <td class="text-end">
                                            @if($path)
                                                <a class="btn-ghost" style="height:30px;padding:0 8px" href="{{ asset('storage/'.$path) }}" target="_blank" title="Download"><i class="bi bi-download"></i></a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No documents uploaded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5">
                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico ico-teal"><i class="bi bi-diagram-3"></i></span>
                            <h6>Case Ownership</h6>
                        </div>
                    </div>
                    <div class="sec-body">
                        <div class="own-row">
                            <span class="own-ico"><i class="bi bi-building"></i></span>
                            <div><div class="kv-label">Branch</div><div class="kv-value">{{ $case->orgUnit?->name ?? '—' }}</div></div>
                        </div>
                        <div class="own-row">
                            <span class="own-ico"><i class="bi bi-person"></i></span>
                            <div><div class="kv-label">SM / JSM Owner</div><div class="kv-value">{{ $case->owner ? ($roleLabel($case->owner).' — '.$case->owner->name) : '—' }}</div></div>
                        </div>
                        <div class="own-row">
                            <span class="own-ico"><i class="bi bi-person-badge"></i></span>
                            <div><div class="kv-label">Branch Manager</div><div class="kv-value">{{ $branchManager ? ($roleLabel($branchManager).' — '.$branchManager->name) : '—' }}</div></div>
                        </div>
                        <div class="own-row">
                            <span class="own-ico"><i class="bi bi-broadcast"></i></span>
                            <div><div class="kv-label">Lead Source</div><div class="kv-value">{{ $case->lead?->source ?? data_get($cd, 'lead_source', '—') }}</div></div>
                        </div>
                    </div>
                </div>

                <div class="sec-card query-alert">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico ico-amber"><i class="bi bi-exclamation-triangle"></i></span>
                            <h6>Pending Bank Query</h6>
                        </div>
                        @if($openQuery)
                            <span class="doc-badge warn">Pending</span>
                        @endif
                    </div>
                    <div class="sec-body">
                        @if($openQuery)
                            <div class="mb-3">
                                <div class="kv-label">Query Summary</div>
                                <div class="kv-value">{{ \Illuminate\Support\Str::limit($openQuery->details, 140) }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="kv-label">Responsible Employee</div>
                                <div class="kv-value">{{ $openQuery->responsible?->name ?? '—' }}</div>
                            </div>
                            <div class="mb-2">
                                <div class="kv-label">Due Date</div>
                                <div class="kv-value">{{ optional($openQuery->due_at)->format('d M Y') }}</div>
                            </div>
                            <div class="query-actions">
                                <button type="button" class="btn-ghost" data-cw-tab-jump="queries">View Query</button>
                            </div>
                        @else
                            <div class="empty-state py-3"><i class="bi bi-check2-circle"></i>No open bank queries.</div>
                        @endif
                    </div>
                </div>

                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico ico-green"><i class="bi bi-award"></i></span>
                            <h6>Sanction &amp; Disbursement</h6>
                        </div>
                    </div>
                    <div class="sec-body">
                        @if($activeSanction || $verifiedDisbTotal > 0 || $case->sanction_amount)
                            <div class="kv-stack">
                                <div><div class="kv-label">Sanctioned Amount</div><div class="kv-value">{{ $fmtInr($activeSanction?->sanction_amount ?? $case->sanction_amount) }}</div></div>
                                <div><div class="kv-label">Sanction Date</div><div class="kv-value">{{ optional($activeSanction?->sanction_date ?? $case->sanction_date)->format('d M Y') ?: '—' }}</div></div>
                                <div><div class="kv-label">Status</div><div class="kv-value">{{ $activeSanction?->status ?? ($case->sanction_amount ? 'Recorded' : '—') }}</div></div>
                                <div><div class="kv-label">Verified Disbursement</div><div class="kv-value">{{ $fmtInr($verifiedDisbTotal) }}</div></div>
                                <div><div class="kv-label">Remaining</div><div class="kv-value">{{ $sanctionCap > 0 ? $fmtInr(max(0, $sanctionCap - $verifiedDisbTotal - $pendingDisbTotal)) : '—' }}</div></div>
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                No sanction or disbursement recorded yet.<br>
                                <span style="font-size:.78rem">These details become available as processing progresses.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="sec-card">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico"><i class="bi bi-activity"></i></span>
                            <h6>Recent Activity</h6>
                        </div>
                        <button type="button" class="btn-link-cw" data-cw-tab-jump="activity">View Activity History →</button>
                    </div>
                    <div class="sec-body">
                        <ul class="timeline">
                            @forelse($case->events->take(5) as $ev)
                                @php
                                    $dot = match($ev->event_type){
                                        'created','status' => 'green',
                                        'document' => '',
                                        'application','disbursement' => 'blue',
                                        'query','assignment' => 'amber',
                                        default => 'blue',
                                    };
                                    $ico = match($ev->event_type){
                                        'created' => 'bi-plus-lg',
                                        'document' => 'bi-file-earmark',
                                        'application' => 'bi-bank',
                                        'disbursement' => 'bi-cash',
                                        'query' => 'bi-chat-left',
                                        default => 'bi-dot',
                                    };
                                @endphp
                                <li>
                                    <div class="tl-dot {{ $dot }}"><i class="bi {{ $ico }}"></i></div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.86rem">{{ $ev->title }}</div>
                                        <div class="hint mb-0">{{ $ev->created_at?->format('d M Y, h:i a') }}</div>
                                    </div>
                                </li>
                            @empty
                                <li><div class="empty-state py-2 w-100"><i class="bi bi-clock-history"></i>No activity yet.</div></li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== CUSTOMER ===================== --}}
    <div class="cw-pane {{ $activeTab === 'customer' ? 'active' : '' }}" data-pane="customer">
        <form method="POST" action="{{ route('goal.cases.workspace', $case) }}" id="cwWorkspaceForm" novalidate>
            @csrf
            <input type="hidden" name="cw_tab" id="cw_tab" value="{{ $activeTab }}">
            @include('goal.cases.partials.customer_fields')
            <div class="accordion mb-3" id="cwCustomerExtras">
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed rounded-3 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#cwExtraPanels">
                            BM Recommendation · Existing Loans · Collateral
                        </button>
                    </h2>
                    <div id="cwExtraPanels" class="accordion-collapse collapse" data-bs-parent="#cwCustomerExtras">
                        <div class="accordion-body px-0">
                            @include('goal.cases.partials.customer_extras')
                        </div>
                    </div>
                </div>
            </div>
            @if($canEdit)
            <div class="cw-footer">
                <div class="hint mb-0">Saves customer, BM reco, loans and collateral for this case.</div>
                <button type="submit" class="btn-save"><i class="bi bi-check2"></i> Save Case</button>
            </div>
            @endif
        </form>
    </div>

    {{-- ===================== DOCUMENTS ===================== --}}
    <div class="cw-pane {{ $activeTab === 'documents' ? 'active' : '' }}" data-pane="documents">
        @include('goal.cases.partials.tab_documents')
    </div>

    {{-- ===================== LENDERS ===================== --}}
    <div class="cw-pane {{ $activeTab === 'lenders' ? 'active' : '' }}" data-pane="lenders">
        @include('goal.cases.partials.tab_lenders')
    </div>

    {{-- ===================== QUERIES ===================== --}}
    <div class="cw-pane {{ $activeTab === 'queries' ? 'active' : '' }}" data-pane="queries">
        @include('goal.cases.partials.tab_queries')
    </div>

    {{-- ===================== SANCTION ===================== --}}
    <div class="cw-pane {{ $activeTab === 'sanction' ? 'active' : '' }}" data-pane="sanction">
        @include('goal.cases.partials.tab_sanction')
    </div>

    {{-- ===================== DISBURSEMENT ===================== --}}
    <div class="cw-pane {{ $activeTab === 'disbursement' ? 'active' : '' }}" data-pane="disbursement">
        @include('goal.cases.partials.tab_disbursement')
    </div>

    {{-- ===================== ACTIVITY ===================== --}}
    <div class="cw-pane {{ $activeTab === 'activity' ? 'active' : '' }}" data-pane="activity">
        @include('goal.cases.partials.tab_activity')
    </div>

    @include('goal.cases.partials.modals')

    @if($canEdit && $next !== [])
    <div class="modal fade" id="cwAdvanceStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('goal.cases.transition', $case) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Advance Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Next status</label>
                    <select name="to_status" class="form-select mb-2" id="cwToStatus" required>
                        <option value="">Select…</option>
                        @foreach($next as $st)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                    <div id="cwQueryFields" class="d-none mb-2">
                        <textarea name="bank_query" id="cwQueryText" class="form-control mb-2" rows="2" placeholder="Query details"></textarea>
                        <input type="date" name="bank_query_due" id="cwQueryDue" class="form-control">
                    </div>
                    <div id="cwSancFields" class="d-none mb-2">
                        <input type="number" step="0.01" name="sanction_amount" id="cwSancAmt" class="form-control mb-2" placeholder="Sanction amount">
                        <input type="date" name="sanction_date" id="cwSancDate" class="form-control">
                    </div>
                    <div id="cwRejectFields" class="d-none mb-2">
                        <input type="text" name="rejection_reason" id="cwRejectReason" class="form-control" placeholder="Rejection reason">
                    </div>
                    <div class="cw-req-lender d-none mb-2">
                        <input type="text" name="lender_name" id="cwLender" class="form-control" placeholder="Lender name" value="{{ $case->lender_name }}">
                    </div>
                    <div class="hint mb-0">Validated on the server. Prefer lender / query / sanction / disbursement actions for evidence-backed steps.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-cw">Apply Transition</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var page = document.getElementById('cwPage');
    if (!page) return;
    var tabInput = document.getElementById('cw_tab');

    function activateTab(key) {
        page.querySelectorAll('.cw-tab').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-cw-tab') === key);
        });
        page.querySelectorAll('.cw-pane').forEach(function (pane) {
            pane.classList.toggle('active', pane.getAttribute('data-pane') === key);
        });
        if (tabInput) tabInput.value = key;
        page.setAttribute('data-active-tab', key);
        try { history.replaceState(null, '', '?tab=' + encodeURIComponent(key)); } catch (e) {}
    }

    page.querySelectorAll('.cw-tab').forEach(function (btn) {
        btn.addEventListener('click', function () { activateTab(btn.getAttribute('data-cw-tab')); });
    });
    page.querySelectorAll('[data-cw-tab-jump]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            activateTab(el.getAttribute('data-cw-tab-jump'));
            var f = el.getAttribute('data-cw-filter');
            var sel = document.getElementById('cwActivityFilter');
            if (f && sel) { sel.value = f; sel.dispatchEvent(new Event('change')); }
        });
    });

    var wsForm = document.getElementById('cwWorkspaceForm');
    if (wsForm) {
        wsForm.addEventListener('submit', function () {
            var nameEl = wsForm.querySelector('[name="customer_details[applicant][name]"]');
            var mobEl = wsForm.querySelector('[name="customer_details[applicant][mobile]"]');
            var hiddenName = document.getElementById('cw_customer_name');
            var hiddenMob = document.getElementById('cw_mobile');
            if (nameEl && hiddenName && nameEl.value) hiddenName.value = nameEl.value;
            if (mobEl && hiddenMob && mobEl.value) hiddenMob.value = mobEl.value;
        });
    }

    page.querySelectorAll('.js-mobile').forEach(function (el) {
        el.addEventListener('input', function () {
            el.value = String(el.value || '').replace(/\D+/g, '').slice(0, 10);
        });
    });

    var toSel = document.getElementById('cwToStatus');
    function syncTransitionFields() {
        var to = toSel ? toSel.value : '';
        var q = document.getElementById('cwQueryFields');
        var s = document.getElementById('cwSancFields');
        var r = document.getElementById('cwRejectFields');
        var lenderBox = document.querySelector('.cw-req-lender');
        if (q) q.classList.toggle('d-none', to !== 'Bank Query');
        if (s) s.classList.toggle('d-none', to !== 'Sanctioned');
        if (r) r.classList.toggle('d-none', to !== 'Rejected' && to !== 'Closed');
        if (lenderBox) lenderBox.classList.toggle('d-none', to !== 'Bank Login');
    }
    if (toSel) { toSel.addEventListener('change', syncTransitionFields); syncTransitionFields(); }

    var actFilter = document.getElementById('cwActivityFilter');
    if (actFilter) {
        actFilter.addEventListener('change', function () {
            var v = actFilter.value;
            page.querySelectorAll('[data-activity-type]').forEach(function (row) {
                var t = row.getAttribute('data-activity-type');
                row.style.display = (!v || v === 'all' || t === v) ? '' : 'none';
            });
        });
    }

    if (window.IsarvaDatepicker) { window.IsarvaDatepicker.init(page); }
    activateTab(page.getAttribute('data-active-tab') || 'overview');
})();
</script>
@endpush
