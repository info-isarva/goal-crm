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
    if ($bt === []) {
        $btRows = [['loan_type' => '', 'loan_amt' => '', 'roi' => '', 'tenor' => '', 'emi' => '']];
    } elseif (isset($bt[0]) && is_array($bt[0])) {
        $btRows = array_values($bt);
    } else {
        $btRows = [is_array($bt) ? $bt : []];
    }
    $loanTypeOpts = [
        '' => 'Select loan type',
        'Home Loan' => 'Home Loan',
        'LAP' => 'LAP',
        'Personal Loan' => 'Personal Loan',
        'Business Loan' => 'Business Loan',
        'Auto Loan' => 'Auto Loan',
        'Education Loan' => 'Education Loan',
        'Other' => 'Other',
    ];
    $loanRowBlank = ['party' => 'applicant', 'type' => '', 'bank' => '', 'sanction' => '', 'outstanding' => '', 'emi' => '', 'paid' => '', 'pending' => '', 'bounces' => ''];
    $loanRows = $loans['rows'] ?? null;
    if (!is_array($loanRows) || $loanRows === []) {
        $loanRows = [
            array_merge($loanRowBlank, ['party' => 'applicant']),
            array_merge($loanRowBlank, ['party' => 'co_applicant']),
        ];
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
    $fmtInr = function ($n) {
        if ($n === null || $n === '') {
            return '—';
        }
        $num = (float) $n;
        $neg = $num < 0;
        $num = abs($num);
        $int = (string) (int) floor($num);
        $dec = round($num - floor($num), 2);
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        if ($rest !== '') {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $int = $rest.','.$last3;
        }
        $out = '₹ '.$int;
        if ($dec > 0) {
            $out .= '.'.str_pad((string) ((int) round($dec * 100)), 2, '0', STR_PAD_LEFT);
        }
        return $neg ? ('-'.$out) : $out;
    };
    $consentLabel = (is_array($consent) && !empty($consent['recorded']))
        ? 'Recorded'
        : ($case->lead?->consent_recorded ? 'Recorded' : 'Not recorded');
@endphp

@push('styles')
<style>
.cw-page{
    --cw-primary:#2563EB;--cw-purple:#7C3AED;--cw-border:#E2E8F0;--cw-text:#0f172a;--cw-muted:#64748b;
    color:var(--cw-text);padding-bottom:96px;background:transparent;
}
.cw-page .cw-actions .dropdown{position:relative;z-index:30}
.cw-page .cw-actions .dropdown-menu{z-index:40;min-width:220px}
.cw-page .cw-table-card .table-responsive,
.cw-page .cw-card{overflow:visible}
.cw-page .pill-pending{
    display:inline-flex;align-items:center;padding:.2rem .65rem;border-radius:999px;
    font-size:.72rem;font-weight:700;background:#FFF7ED;color:#C2410C;border:1px solid #FED7AA;
}
.cw-page .cw-hero{
    display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:start;gap:16px 20px;margin-bottom:1.1rem;
}
.cw-page .cw-back{
    width:40px;height:40px;border-radius:999px;border:1px solid var(--cw-border);background:#fff;
    color:#1e3a8a;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0;
}
.cw-page .cw-title{margin:0;font-size:var(--font-size-page-title,26px);font-weight:var(--font-bold,700);letter-spacing:var(--letter-spacing-tight,-.01em);line-height:1.25;color:#0f172a}
.cw-page .cw-subtitle{margin:.3rem 0 0;font-size:var(--font-size-subtitle,13px);color:#64748b;font-weight:var(--font-regular,400)}
.cw-page .cw-crumb{margin-top:8px;font-size:.78rem;color:#94a3b8}
.cw-page .cw-crumb a{color:#94a3b8;text-decoration:none}
.cw-page .cw-crumb a:hover{color:var(--cw-primary)}
.cw-page .cw-actions{display:flex;gap:10px;flex-wrap:nowrap;align-items:center;justify-content:flex-end}
.cw-page .cw-actions .dropdown{flex-shrink:0}
.cw-page .btn-ghost{
    height:40px;padding:0 14px;border-radius:10px;font-weight:600;font-size:.84rem;white-space:nowrap;
    border:1px solid #Dbe3f0;background:#fff;color:#334155;text-decoration:none;
    display:inline-flex;align-items:center;gap:7px;box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.cw-page .btn-ghost i{color:var(--cw-primary);font-size:.95rem}
.cw-page .btn-primary-cw{
    height:40px;padding:0 16px;border-radius:10px;font-weight:700;font-size:.84rem;border:0;color:#fff;white-space:nowrap;
    background:var(--cw-purple);display:inline-flex;align-items:center;gap:7px;
    box-shadow:0 6px 14px rgba(124,58,237,.28);
}
.cw-page .btn-link-cw{
    border:0;background:transparent;color:var(--cw-primary);font-weight:700;font-size:.82rem;
    display:inline-flex;align-items:center;gap:4px;padding:0;cursor:pointer;text-decoration:none;
}
.cw-page .cw-summary-card{
    background:#fff;border:1px solid rgba(226,232,240,.9);border-radius:16px;
    box-shadow:
        0 1px 2px rgba(15,23,42,.04),
        0 10px 28px rgba(148,163,184,.16),
        0 22px 48px rgba(148,163,184,.10);
    margin-bottom:1rem;overflow:hidden;
}
.cw-page .case-ribbon{
    display:flex;align-items:center;gap:0;padding:16px 14px 14px;
    background:linear-gradient(180deg,#F8FAFF 0%,#FFFFFF 100%);
}
.cw-page .case-ribbon .rb-lead{
    width:46px;height:46px;border-radius:12px;flex-shrink:0;margin-right:10px;
    background:linear-gradient(145deg,#3B82F6,#7C3AED);color:#fff;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.15rem;
    box-shadow:0 8px 16px rgba(79,70,229,.28);
}
.cw-page .case-ribbon .rb-item{
    flex:1 1 0;min-width:0;padding:0 12px;border-right:1px solid #EEF2F7;
    display:flex;align-items:center;gap:10px;
}
.cw-page .case-ribbon .rb-item.rb-status{border-right:0;flex:0 0 auto;padding-right:4px;align-items:flex-start}
.cw-page .case-ribbon .rb-item:last-child{border-right:0}
.cw-page .case-ribbon .rb-ico{
    color:#2E5BFF;font-size:1.15rem;line-height:1;flex-shrink:0;
}
.cw-page .case-ribbon .rb-text{min-width:0;flex:1 1 auto}
.cw-page .case-ribbon .rb-label{
    font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
    color:#6477C7;line-height:1.2;
}
.cw-page .case-ribbon .rb-value{
    font-size:.86rem;font-weight:700;margin-top:4px;display:flex;align-items:center;gap:6px;min-width:0;color:#0f172a;
}
.cw-page .case-ribbon .rb-value > span,.cw-page .case-ribbon .rb-value > a{
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.cw-page .case-ribbon .rb-value a{color:var(--cw-primary);text-decoration:none}
.cw-page .case-ribbon .rb-copy{
    border:0;background:transparent;color:#94a3b8;padding:0;line-height:1;cursor:pointer;flex-shrink:0;
}
.cw-page .case-ribbon .rb-copy:hover{color:var(--cw-primary)}
.cw-page .status-pill{
    display:inline-flex;align-items:center;gap:4px;padding:.18rem .55rem;border-radius:999px;
    font-size:.7rem;font-weight:600;line-height:1.2;border:0;box-shadow:none;
    background:#EFF6FF;color:#1D4ED8;
}
.cw-page .status-pill.draft{background:#F1F5F9;color:#475569}
.cw-page .status-pill.login{background:#DBEAFE;color:#1D4ED8}
.cw-page .status-pill.process{background:#F3E8FF;color:#7C3AED}
.cw-page .status-pill.query{background:#FFF7ED;color:#C2410C}
.cw-page .status-pill.ok{background:#ECFDF5;color:#059669}
.cw-page .status-pill.done{background:#DBEAFE;color:#1E40AF}
.cw-page .status-pill.bad{background:#FEF2F2;color:#DC2626}
.cw-page .doc-badge{
    display:inline-flex;align-items:center;gap:4px;font-size:.7rem;font-weight:600;
    padding:3px 8px;border-radius:6px;background:#eff6ff;color:var(--cw-primary);line-height:1.2;
}
.cw-page .doc-badge.ok{background:#ecfdf5;color:#059669}
.cw-page .doc-badge.warn{background:#fff7ed;color:#c2410c}
.cw-page .doc-badge.bad{background:#fef2f2;color:#dc2626}
.cw-page .lender-chip{
    width:22px;height:22px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;
    font-size:.58rem;font-weight:700;flex-shrink:0;background:#EEF2FF;color:#4338CA;
}
.cw-page .lender-chip.hdfc{background:#FEF2F2;color:#B91C1C}
.cw-page .lender-chip.icici{background:#FFF7ED;color:#C2410C}
.cw-page .lender-chip.sbi{background:#EFF6FF;color:#1D4ED8}
.cw-page .lender-chip.axis{background:#F5F3FF;color:#7C3AED}
.cw-page .doc-type-ico{color:#1E3A8A;font-size:.95rem}
.cw-page .cw-table-card .card-header,
.cw-page .cw-table-card > .sec-head{padding:.85rem 1.15rem .75rem !important}
.cw-page .cw-table-card .cw-table-wrap{padding:.2rem 1.15rem .85rem}
.cw-page .cw-table-card .table-responsive{
    border:1px solid #E2E8F0;border-radius:12px;overflow:auto;background:#fff;
}
.cw-page .cw-table-card .table{
    margin:0;background:#fff;border-collapse:separate;border-spacing:0;width:100%;
}
.cw-page .cw-table-card .table > :not(caption) > * > *{padding:.6rem .7rem;border:0}
.cw-page .cw-table-card .table thead th{
    font-size:.74rem;text-transform:none;letter-spacing:0;color:#475569;font-weight:600;
    background:#fff;border-bottom:1px solid #E2E8F0;white-space:nowrap;
}
.cw-page .cw-table-card .table thead th:first-child{padding-left:1rem}
.cw-page .cw-table-card .table thead th:last-child{padding-right:1rem}
.cw-page .cw-table-card .table tbody td{
    font-size:.84rem;color:#0f172a;vertical-align:middle;background:#fff;
    border-bottom:1px solid #E8EEF5;
}
.cw-page .cw-table-card .table tbody td:first-child{padding-left:1rem}
.cw-page .cw-table-card .table tbody td:last-child{padding-right:1rem}
.cw-page .cw-table-card .table tbody tr:last-child td{border-bottom:0}
.cw-page .cw-table-card .table tbody tr:hover td{background:#F8FAFC}
.cw-page .cw-lender-table{
    table-layout:fixed;width:100%;border-collapse:separate;border-spacing:0;
}
.cw-page .cw-lender-table thead th,
.cw-page .cw-lender-table tbody td{
    padding:.7rem .85rem !important;vertical-align:middle !important;
    text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.cw-page .cw-lender-table thead th.text-end,
.cw-page .cw-lender-table tbody td.text-end{text-align:right !important}
.cw-page .cw-lender-table .cw-lender-name{font-weight:600;color:#0f172a}
.cw-page .cw-lender-table .cw-lender-amt{font-variant-numeric:tabular-nums;font-weight:600}
.cw-page .cw-lender-table .status-pill{display:inline-flex;max-width:100%}
.cw-page .cw-lender-table tbody tr{background:#fff}
.cw-page .cw-lender-table tbody tr:nth-child(even) td{background:#fff}
.cw-page .cw-table-card .cw-table-note{
    padding:.55rem .15rem 0;margin-top:.35rem;font-size:.75rem;color:#94A3B8;font-style:italic;border-top:0;
}
.cw-page .cw-table-card .cw-table-note i{font-style:normal;margin-right:4px;color:#94A3B8}
.cw-page .cw-table-card .btn-action{
    width:28px;height:28px;padding:0;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;
    border:1px solid #CBD5E1;background:#fff;color:#334155;
}
.cw-page .cw-table-card .btn-action:hover{border-color:#93C5FD;color:#1E3A8A;background:#DBEAFE}
.cw-page .cw-table-card .btn-download{
    width:30px;height:30px;padding:0;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;
    border:1px solid #93C5FD;background:#DBEAFE;color:#1E3A8A;
}
.cw-page .cw-table-card .btn-download:hover{background:#BFDBFE;color:#1E3A8A}
.cw-page .cw-progress{
    display:flex;align-items:flex-start;gap:0;padding:8px 10px 18px;
    border-top:1px solid #EEF2F7;
}
.cw-page .cw-step{
    flex:1 1 0;min-width:0;text-align:center;position:relative;padding:0 2px;
    font-size:.7rem;font-weight:700;color:#94a3b8;
}
.cw-page .cw-step .dot{
    width:34px;height:34px;border-radius:999px;margin:0 auto 8px;display:flex;align-items:center;justify-content:center;
    background:#F8FAFC;border:2px solid #E2E8F0;color:#94a3b8;font-size:.9rem;position:relative;z-index:1;
}
.cw-page .cw-step .step-label{color:#0f172a;line-height:1.25;white-space:nowrap}
.cw-page .cw-step .step-at{display:block;margin-top:4px;font-size:.6rem;font-weight:600;color:#94a3b8;line-height:1.2}
.cw-page .cw-step.done .step-label{color:#059669}
.cw-page .cw-step.done .dot{
    background:#10B981;border-color:#10B981;color:#fff;
    box-shadow:0 4px 10px rgba(16,185,129,.28);
}
.cw-page .cw-step.active .step-label{color:#1D4ED8}
.cw-page .cw-step.active .dot{
    background:#2563EB;border-color:#2563EB;color:#fff;
    box-shadow:0 0 0 4px rgba(37,99,235,.16),0 6px 14px rgba(37,99,235,.28);
}
.cw-page .cw-step.todo .step-label{color:#94a3b8}
.cw-page .cw-step.rejected{color:#dc2626}
.cw-page .cw-step.rejected .step-label{color:#dc2626}
.cw-page .cw-step.rejected .dot{background:#EF4444;border-color:#EF4444;color:#fff}
.cw-page .cw-step:not(:last-child)::after{
    content:'';position:absolute;top:17px;left:calc(50% + 20px);right:calc(-50% + 20px);height:2px;background:#E2E8F0;z-index:0;
}
.cw-page .cw-step.done:not(:last-child)::after{background:#10B981}
.cw-page .cw-step.active:not(:last-child)::after{background:#E2E8F0}
.cw-page .cw-tabs{
    display:flex;align-items:center;width:100%;gap:0;overflow-x:auto;
    margin-bottom:1rem;padding:6px;background:#fff;border:1px solid rgba(226,232,240,.85);
    border-radius:999px;
    box-shadow:
        0 1px 2px rgba(15,23,42,.04),
        0 10px 28px rgba(148,163,184,.16);
    scrollbar-width:none;
}
.cw-page .cw-tabs::-webkit-scrollbar{display:none}
.cw-page .cw-tab{
    position:relative;flex:1 1 0;min-width:max-content;justify-content:center;
    border:0;background:transparent;color:#4F46E5;
    border-radius:999px;padding:10px 14px;font-size:.8rem;font-weight:650;
    display:inline-flex;align-items:center;gap:7px;cursor:pointer;white-space:nowrap;
    line-height:1.15;transition:background .15s ease,color .15s ease,box-shadow .15s ease;
}
.cw-page .cw-tab i{font-size:1rem;color:#4F46E5;line-height:1}
.cw-page .cw-tab:not(.active):not(:first-child)::before{
    content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);
    width:1px;height:16px;background:#E2E8F0;pointer-events:none;
}
.cw-page .cw-tab:hover:not(.active){color:#3730A3;background:#F5F3FF}
.cw-page .cw-tab:hover:not(.active) i{color:#3730A3}
.cw-page .cw-tab.active{
    background:#2563EB;color:#fff;
    box-shadow:0 8px 16px rgba(37,99,235,.28);z-index:1;
}
.cw-page .cw-tab.active i{color:#fff}
.cw-page .cw-tab.active + .cw-tab::before,
.cw-page .cw-tab.active::before{display:none}
@media (max-width:1200px){
    .cw-page .cw-hero{grid-template-columns:1fr}
    .cw-page .cw-actions{justify-content:flex-start;flex-wrap:wrap}
    .cw-page .case-ribbon{overflow-x:auto;padding:14px 10px}
    .cw-page .case-ribbon .rb-item{min-width:118px;flex:0 0 auto}
    .cw-page .cw-progress{overflow-x:auto}
    .cw-page .cw-step{min-width:88px;flex:0 0 auto}
}
.cw-page .cw-pane{display:none !important}
.cw-page .cw-pane.active{display:block !important}
.cw-page .sec-card{
    background:#fff;border:1px solid rgba(226,232,240,.85);border-radius:16px;overflow:hidden;
    box-shadow:
        0 1px 2px rgba(15,23,42,.04),
        0 10px 28px rgba(148,163,184,.16),
        0 22px 48px rgba(148,163,184,.10);
    height:100%;
}
.cw-page .overview-grid{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(280px,.95fr);gap:12px;align-items:start}
.cw-page .overview-main,.cw-page .overview-side{display:flex;flex-direction:column;gap:12px;min-width:0}
.cw-page .sec-head{
    display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 16px 0;background:#fff;
}
.cw-page .sec-head.has-line{padding-bottom:10px;border-bottom:1px solid #EEF2F7}
.cw-page .sec-head-left{display:flex;align-items:center;gap:10px}
.cw-page .sec-ico{
    width:34px;height:34px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;
    background:#BFDBFE;color:#1E3A8A;font-size:.95rem;flex-shrink:0;border:1px solid #93C5FD;
}
.cw-page .sec-ico.ico-blue{background:#BFDBFE;color:#1E3A8A;border-color:#93C5FD}
.cw-page .sec-ico.ico-amber{background:#FDE68A;color:#92400E;border-color:#F59E0B}
.cw-page .sec-ico.ico-green{background:#BBF7D0;color:#14532D;border-color:#86EFAC}
.cw-page .sec-ico.ico-teal{background:#99F6E4;color:#115E59;border-color:#5EEAD4}
.cw-page .sec-ico.ico-solid-blue{background:#1D4ED8;color:#fff;border-color:#1D4ED8}
.cw-page .sec-ico.ico-solid-green{background:#15803D;color:#fff;border-color:#15803D}
.cw-page .cw-card{overflow:hidden}
.cw-page .cw-card .card-header{padding:.75rem 1rem !important}
.cw-page .cw-card .card-body{padding:.65rem 1rem .85rem !important}
.cw-page .cw-card .list-group-item{padding:.55rem 1rem !important}
.cw-page .cw-card.query-alert,
.cw-page .sec-card.query-alert{border-left:4px solid #f59e0b !important}
.cw-page .text-theme{color:#1E3A8A !important}
.cw-page .cw-sanc-blue{background:#EFF6FF;border:1px solid #DBEAFE}
.cw-page .cw-sanc-green{background:#ECFDF5;border:1px solid #D1FAE5}
.cw-page .sec-head h6{margin:0;font-size:var(--font-size-card-title,16px);font-weight:var(--font-semibold,600);color:#0f172a;letter-spacing:var(--letter-spacing-tight,-.01em)}
.cw-page .sec-sub{font-size:.78rem;color:#94a3b8;margin-top:2px;font-weight:500}
.cw-page .sec-body{padding:10px 16px 14px}
.cw-page .kv-stack{display:flex;flex-direction:column;gap:14px}
.cw-page .kv-label{font-size:.68rem;font-weight:700;color:var(--cw-muted);text-transform:uppercase;letter-spacing:.04em}
.cw-page .kv-value{font-size:.9rem;font-weight:600;margin-top:3px;word-break:break-word;display:flex;align-items:center;gap:8px}
.cw-page .kv-value i{color:#64748b;font-size:.9rem}
.cw-page .detail-card .sec-body{padding:8px 16px 14px}
.cw-page .detail-grid{
    display:grid;grid-template-columns:1fr 1fr;gap:0;column-gap:0;
    border:0;
}
.cw-page .detail-col{min-width:0;padding:4px 0 0}
.cw-page .detail-col:first-child{padding-right:28px}
.cw-page .detail-col + .detail-col{
    border-left:1px solid #E5E7EB;padding-left:28px;padding-right:0;
}
.cw-page .detail-row{
    display:grid;grid-template-columns:158px 8px minmax(0,1fr);
    align-items:center;column-gap:4px;padding:4px 0;min-height:0;
}
.cw-page .detail-row .d-label{
    font-size:.84rem;font-weight:500;color:#64748b;line-height:1.4;
}
.cw-page .detail-row .d-sep{
    color:#94a3b8;font-weight:500;text-align:left;font-size:.84rem;line-height:1.35;
}
.cw-page .detail-row .d-value{
    font-size:.88rem;font-weight:500;color:#1e293b;display:flex;align-items:center;gap:7px;
    min-width:0;flex-wrap:wrap;line-height:1.35;
}
.cw-page .detail-row .d-value.is-strong{font-weight:700;color:#0f172a}
.cw-page .detail-row .d-value a{color:#2563EB;text-decoration:none;font-weight:600}
.cw-page .detail-row .d-value a:hover{text-decoration:underline}
.cw-page .detail-row .d-value i{color:#1E3A8A;font-size:.9rem;flex-shrink:0}
.cw-page .pill-soft{
    display:inline-flex;align-items:center;gap:5px;padding:.22rem .65rem;border-radius:999px;
    font-size:.75rem;font-weight:650;line-height:1.2;
}
.cw-page .pill-soft.product{background:#F3E8FF;color:#7C3AED}
.cw-page .pill-soft.consent{background:#ECFDF5;color:#059669}
.cw-page .pill-soft.consent.warn{background:#FFF7ED;color:#C2410C}
.cw-page .pill-soft i{font-size:.78rem}
.cw-page .own-list{display:flex;flex-direction:column;gap:2px}
.cw-page .own-row{
    display:grid;grid-template-columns:22px minmax(108px,34%) 8px minmax(0,1fr);
    align-items:center;column-gap:6px;padding:5px 0;border-bottom:0;
}
.cw-page .own-row:last-child{padding-bottom:0}
.cw-page .own-row:first-child{padding-top:0}
.cw-page .own-row .own-ico{
    width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;
    color:#334155;font-size:.92rem;flex-shrink:0;
}
.cw-page .own-row .d-label{font-size:.84rem;font-weight:500;color:#64748b;line-height:1.35}
.cw-page .own-row .d-sep{color:#94a3b8;font-weight:500;font-size:.84rem}
.cw-page .own-row .d-value{
    font-size:.88rem;font-weight:600;color:#0f172a;display:flex;align-items:center;gap:7px;
    min-width:0;flex-wrap:wrap;line-height:1.35;
}
.cw-page .own-row .d-value i{color:#64748b;font-size:.9rem;flex-shrink:0}
.cw-page .own-row .d-value .val-ico{color:#1E3A8A}
.cw-page .query-block{display:flex;flex-direction:column;gap:12px}
.cw-page .query-block .q-label{font-size:.84rem;font-weight:500;color:#64748b;margin-bottom:4px}
.cw-page .query-block .q-value{
    font-size:.9rem;font-weight:600;color:#0f172a;display:flex;align-items:center;gap:8px;line-height:1.4;
}
.cw-page .query-block .q-value i{color:#64748b;font-size:.95rem}
.cw-page .query-block .q-summary{font-size:.9rem;font-weight:600;color:#0f172a;line-height:1.45}
.cw-page .pill-soft.pending{background:#FFF7ED;color:#C2410C;border:1px solid #FED7AA}
.cw-page .btn-ghost.query-view{
    height:34px;padding:0 14px;border-radius:10px;border:1px solid #BFDBFE;color:#2563EB;font-weight:600;font-size:.82rem;
}
.cw-page .btn-ghost.query-view:hover{background:#EFF6FF;border-color:#93C5FD;color:#1D4ED8}
.cw-page .split-cols{display:grid;grid-template-columns:1fr 1fr;gap:28px}
.cw-page .split-cols .col-title{font-size:.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;margin-bottom:14px}
.cw-page .split-cols .col-title.cust{color:var(--cw-purple)}
.cw-page .split-cols .col-title.loan{color:var(--cw-primary)}
.cw-page .query-actions{display:flex;justify-content:flex-end;margin-top:14px;gap:8px}
.cw-page .sanc-mini-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cw-page .sanc-mini{
    border-radius:14px;padding:14px;border:1px solid #E2E8F0;display:flex;gap:10px;align-items:flex-start;
}
.cw-page .sanc-mini.violet{background:#F5F3FF;border-color:#E9D5FF}
.cw-page .sanc-mini.green{background:#ECFDF5;border-color:#D1FAE5}
.cw-page .sanc-mini .sm-ico{
    width:34px;height:34px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;
}
.cw-page .sanc-mini.violet .sm-ico{background:#7C3AED;color:#fff}
.cw-page .sanc-mini.green .sm-ico{background:#D1FAE5;color:#059669}
.cw-page .sanc-mini .sm-label{font-size:.78rem;font-weight:500;color:#64748b;letter-spacing:0;text-transform:none;line-height:1.3}
.cw-page .sanc-mini .sm-value{font-size:1.02rem;font-weight:700;color:#0f172a;margin-top:4px;letter-spacing:-.01em}
.cw-page .sanc-mini .sm-note{font-weight:500;color:#64748b}
.cw-page .sanc-meta{
    display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;margin-top:4px;
}
.cw-page .sanc-meta-row{
    display:grid;grid-template-columns:minmax(0,1fr) 8px auto;align-items:center;column-gap:4px;
}
.cw-page .sanc-meta-row .d-label{font-size:.84rem;font-weight:500;color:#64748b}
.cw-page .sanc-meta-row .d-sep{color:#94a3b8;font-size:.84rem}
.cw-page .sanc-meta-row .d-value{font-size:.88rem;font-weight:700;color:#0f172a;text-align:right;white-space:nowrap}
.cw-page .ws-section-title{
    font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#64748b;
    background:#f8fafc;padding:.55rem .85rem;border-bottom:1px solid #e5e7eb;border-top:1px solid #e5e7eb;
}
.cw-page .ws-table{margin:0}
.cw-page .ws-table td:first-child{width:28%;font-weight:600;color:#334155;background:#fafbfc;vertical-align:middle;font-size:.84rem}
.cw-page .form-label{font-size:.76rem;font-weight:700;color:#475569;margin-bottom:6px}
/* Default (md) — do NOT apply to Bootstrap form-sm / Customer tab */
.cw-page .form-control:not(.form-control-sm),
.cw-page .form-select:not(.form-select-sm){
    min-height:42px;border-radius:10px;border:1px solid var(--cw-border);background:#f8fafc;font-size:.88rem;
}
.cw-page .form-control:focus,.cw-page .form-select:focus{border-color:#93c5fd;background:#fff;box-shadow:0 0 0 3px rgba(36,91,235,.12)}
.cw-page .form-control:disabled,.cw-page .form-control[readonly]{background:#eef4ff;color:#64748b}
/* Bootstrap 5 form-sm — restore compact sizing */
.cw-page .form-control-sm,
.cw-page .form-select-sm{
    min-height:31px !important;height:31px !important;
    padding:.25rem .5rem !important;font-size:.875rem !important;border-radius:.375rem !important;
    background:#fff !important;border:1px solid #E2E8F0 !important;
}
.cw-page .input-group-sm > .form-control,
.cw-page .input-group-sm > .form-select,
.cw-page .input-group-sm > .input-group-text{
    min-height:31px !important;height:31px !important;
}
.cw-page .table thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;font-weight:700;border-bottom-color:#E2E8F0}
.cw-page .table td{padding:.45rem .85rem;border-color:#F1F5F9}
.cw-page .cw-table-card .table thead th{text-transform:none;letter-spacing:0;font-weight:600}
.cw-page .kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:10px;margin-bottom:1rem}
.cw-page .kpi{background:#fff;border:1px solid var(--cw-border);border-radius:12px;padding:12px 14px}
.cw-page .kpi .n{font-size:1.25rem;font-weight:800}
.cw-page .kpi .l{font-size:.72rem;color:var(--cw-muted);font-weight:600}
.cw-page .empty-state{text-align:center;padding:16px 12px;color:var(--cw-muted)}
.cw-page .empty-state i{font-size:1.5rem;opacity:.45;display:block;margin-bottom:6px}
.cw-page .timeline{list-style:none;margin:0;padding:0}
.cw-page .timeline li{
    display:flex;align-items:center;gap:12px;padding:10px 0;position:relative;border-bottom:0;
}
.cw-page .timeline li:last-child{padding-bottom:0}
.cw-page .timeline li:first-child{padding-top:0}
.cw-page .timeline li:not(:last-child)::before{
    content:"";position:absolute;left:15px;top:36px;bottom:-2px;width:2px;background:#E2E8F0;
}
.cw-page .tl-dot{
    width:32px;height:32px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;
    flex-shrink:0;background:#1D4ED8;color:#fff;font-size:.85rem;border:0;position:relative;z-index:1;
}
.cw-page .tl-dot.green{background:#15803D;color:#fff}
.cw-page .tl-dot.blue{background:#1D4ED8;color:#fff}
.cw-page .tl-dot.teal{background:#0F766E;color:#fff}
.cw-page .tl-dot.amber{background:#B45309;color:#fff}
.cw-page .tl-body{
    flex:1;min-width:0;display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.cw-page .tl-title{font-size:.88rem;font-weight:600;color:#0f172a;line-height:1.35}
.cw-page .tl-time{font-size:.78rem;font-weight:500;color:#64748B;white-space:nowrap;flex-shrink:0}
.cw-page .query-alert{border-left:4px solid #f59e0b}
.cw-page .cw-pane.active .cw-footer{
    position:sticky;bottom:58px;z-index:30;width:100%;
    margin:8px 0;border:1px solid var(--cw-border);border-radius:16px;overflow:hidden;
    background:#fff;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;
    box-shadow:
        0 1px 2px rgba(15,23,42,.04),
        0 10px 28px rgba(148,163,184,.16),
        0 22px 48px rgba(148,163,184,.10);
}
.cw-page .cw-footer{display:none}
.cw-page .btn-save{
    height:42px;padding:0 18px;border-radius:10px;font-weight:700;font-size:.86rem;border:0;color:#fff;
    background:#1D4ED8;display:inline-flex;align-items:center;gap:6px;
}
.cw-page .btn-save:hover{background:#1E40AF;color:#fff}
.cw-page .cust-layout .cust-card{
    border-radius:16px !important;border:1px solid #E8EEF5 !important;
    box-shadow:
        0 1px 2px rgba(15,23,42,.04),
        0 10px 28px rgba(148,163,184,.16),
        0 22px 48px rgba(148,163,184,.10) !important;
    overflow:visible !important; /* allow Select2; dropdown itself is on body */
}
.cw-page .cust-layout{overflow-x:clip;max-width:100%}
.cw-page .cust-layout .cust-card .card-header{
    padding:.7rem 1.15rem !important;background:#fff !important;
    border-bottom:1px solid #EEF2F7 !important;
}
.cw-page .cust-layout .cust-card .card-body{padding:1rem 1.15rem 1.1rem !important}
.cw-page .cust-layout .cust-card .card-header h6{
    font-size:.95rem;font-weight:700;color:#0f172a;letter-spacing:-.01em;
}
.cw-page .cust-layout .row.g-cust{--bs-gutter-x:1rem;--bs-gutter-y:.85rem}
/* Bootstrap form-sm baseline — consistent sizes */
.cw-page .cust-layout .form-label{
    font-size:.875rem;font-weight:600;color:#334155;margin-bottom:4px;display:block;
}
.cw-page .cust-layout .form-control,
.cw-page .cust-layout .form-control-sm{
    min-height:31px !important;height:31px !important;
    padding:.25rem .5rem !important;font-size:.875rem !important;
    border-radius:.375rem !important;border:1px solid #E2E8F0 !important;
    background-color:#fff !important;color:#0f172a;
}
.cw-page .cust-layout .form-control::placeholder{color:#94a3b8}
.cw-page .cust-layout .form-control:focus{
    border-color:#93C5FD !important;background-color:#fff !important;box-shadow:0 0 0 3px rgba(37,99,235,.12);
}
.cw-page .cust-layout .input-group-sm > .form-control,
.cw-page .cust-layout .input-group-sm > .input-group-text{
    min-height:31px !important;height:31px !important;
}
/* Select2 (sm) inside Customer tab — beat global select2-theme 38px */
.cw-page .cust-layout .select2-container{width:100%!important;font-size:.875rem}
.cw-page .cust-layout .select2-container--default .select2-selection--single{
    height:31px !important;min-height:31px !important;
    border:1px solid #E2E8F0 !important;border-radius:.375rem !important;background-color:#fff;
}
.cw-page .cust-layout .select2-container--default .select2-selection--single .select2-selection__rendered{
    color:#0f172a;line-height:29px !important;padding-left:.5rem;padding-right:1.75rem;
    font-size:.875rem !important;font-weight:500;
}
.cw-page .cust-layout .select2-container--default .select2-selection--single .select2-selection__placeholder{color:#94a3b8}
.cw-page .cust-layout .select2-container--default .select2-selection--single .select2-selection__arrow{
    height:29px !important;width:26px;top:0;right:2px;
}
.cw-page .cust-layout .select2-container--default.select2-container--focus .select2-selection--single,
.cw-page .cust-layout .select2-container--default.select2-container--open .select2-selection--single{
    border-color:#93C5FD;box-shadow:0 0 0 3px rgba(37,99,235,.12);
}
/* Relationship Self — blue face, dropdown options stay readable */
.cw-page .cust-layout select.cust-rel-self + .select2-container .select2-selection--single{
    background-color:#EFF6FF;border-color:#BFDBFE;
}
.cw-page .cust-layout select.cust-rel-self + .select2-container .select2-selection__rendered{
    color:#2563EB !important;font-weight:600;
}
.cw-page .cust-layout select.cust-rel-self + .select2-container .select2-selection__arrow b{
    border-color:#2563EB transparent transparent transparent;
}
.cw-page .cust-layout select.cust-rel-self + .select2-container.select2-container--open .select2-selection__arrow b{
    border-color:transparent transparent #2563EB transparent;
}
/* Seamless icon-inside-input */
.cw-page .cust-layout .input-group{
    border:1px solid #E2E8F0;border-radius:.375rem;background:#fff;overflow:hidden;
    transition:border-color .15s,box-shadow .15s;
}
.cw-page .cust-layout .input-group:focus-within{
    border-color:#93C5FD;box-shadow:0 0 0 3px rgba(37,99,235,.12);
}
.cw-page .cust-layout .input-group .input-group-text{
    background:transparent;border:0;color:#64748b;min-width:2rem;padding-left:.5rem;
    justify-content:center;border-radius:0 !important;
}
.cw-page .cust-layout .input-group .input-group-text .bi{font-size:.875rem;color:#64748b}
.cw-page .cust-layout .input-group .form-control{
    border:0 !important;box-shadow:none !important;background:transparent !important;
    border-radius:0 !important;
}
.cw-page .cust-layout .input-group .form-control:focus{box-shadow:none !important}
.cw-page .cust-layout .input-group .input-group-text:not(:has(.bi)){
    font-weight:600;color:#1E3A8A;padding-left:.75rem;
}
.cw-page .cust-profile-btn{
    border-radius:999px;font-weight:600;font-size:.8rem;padding:.45rem 1.05rem;
    display:inline-flex;align-items:center;gap:6px;border-width:1.5px;
}
.cw-page .cust-party-toggle{
    background:#F1F5F9;border-radius:999px;padding:3px;border:1px solid #E2E8F0;
}
.cw-page .cust-party-btn{
    border:0 !important;background:transparent;color:#0f172a;font-weight:600;font-size:.72rem;
    border-radius:999px !important;padding:.28rem .85rem;min-width:100px;
}
.cw-page .cust-party-btn.active{
    background:#2563EB !important;color:#fff !important;box-shadow:0 4px 12px rgba(37,99,235,.28);
}
.cw-page .cust-party-panel{display:none}
.cw-page .cust-party-panel.is-active{display:block}
/* Side-by-side Applicant | Co-Applicant identity */
.cw-page .cust-layout .cust-identity-head,
.cw-page .cust-layout .cust-section-head{
    background:#fff !important;padding:.65rem 1rem .4rem !important;
    border-bottom:0 !important;
}
.cw-page .cust-layout .cust-identity-head h6,
.cw-page .cust-layout .cust-section-head h6{
    color:#0f172a;font-size:.95rem;font-weight:700;
}
.cw-page .cust-layout .cust-years-block{
    background:#F8FAFC;border:1px solid #E8EEF5;border-radius:10px;padding:.75rem .9rem;
}
.cw-page .cust-layout .cust-mini-label{
    font-size:.75rem !important;color:#64748B !important;margin-bottom:4px !important;
}
/* Employment comparison grid: label | Applicant | Co-Applicant */
.cw-page .cust-emp-grid{display:flex;flex-direction:column;gap:.55rem}
.cw-page .cust-emp-head-row,
.cw-page .cust-emp-row{
    display:grid;grid-template-columns:minmax(140px,1.1fr) 1fr 1fr;gap:.65rem;align-items:center;
}
.cw-page .cust-emp-row-span{
    grid-template-columns:minmax(140px,1.1fr) minmax(0,2fr);align-items:center;
}
.cw-page .cust-emp-label-col .form-label{margin:0;font-size:.875rem}
.cw-page .cust-emp-span-fields{display:flex;gap:.5rem;align-items:center;min-width:0}
.cw-page .cust-emp-span-fields .cust-pin-sm{max-width:120px;flex:0 0 120px}
.cw-page .cust-emp-head-row .cust-party-col-head{margin-bottom:0}
@media (max-width:991.98px){
    .cw-page .cust-emp-head-row,
    .cw-page .cust-emp-row,
    .cw-page .cust-emp-row-span{
        grid-template-columns:1fr;gap:.35rem;
    }
    .cw-page .cust-emp-span-fields{flex-wrap:wrap}
    .cw-page .cust-emp-span-fields .cust-pin-sm{max-width:100%;flex:1 1 100%}
}
.cw-page .cust-layout .cust-ico-identity{
    background:#EDE9FE;color:#5B21B6;border-color:#DDD6FE;
}
.cw-page .cust-identity-split{
    display:grid;grid-template-columns:1fr 1fr;gap:0;min-width:0;
    padding:0 .85rem 1rem;
}
.cw-page .cust-party-col{min-width:0;display:flex;flex-direction:column;padding:0 .4rem}
.cw-page .cust-party-col + .cust-party-col{
    border-left:1px solid #E8EEF5;padding-left:.85rem;
}
.cw-page .cust-party-col-head{
    display:inline-flex;align-items:center;gap:8px;
    padding:.4rem .8rem;margin-bottom:.65rem;
    font-size:.8rem;font-weight:700;border-radius:8px;width:100%;
}
.cw-page .cust-party-col-head.is-applicant{
    background:#E8F0FE;color:#1D4ED8;
}
.cw-page .cust-party-col-head.is-co{
    background:#F3E8FF;color:#6D28D9;
}
.cw-page .cust-party-col-head i{font-size:.9rem}
.cw-page .cust-party-col-body{padding:0 .1rem .2rem}
/* Horizontal label | input — Bootstrap form-sm sizes */
.cw-page .cust-party-col-body .cust-field{
    display:grid;grid-template-columns:120px minmax(0,1fr);
    align-items:center;gap:10px;margin-bottom:.5rem;
}
.cw-page .cust-party-col-body .cust-field:last-child{margin-bottom:0}
.cw-page .cust-party-col-body .cust-field .form-label{
    margin:0;font-size:.875rem;font-weight:600;color:#334155;line-height:1.25;
}
.cw-page .cust-party-col-body .cust-field .form-control{font-size:.875rem}
.cw-page .cust-party-col-body .cust-field .select2-container{width:100%!important}
@media (max-width:991.98px){
    .cw-page .cust-party-col-body .cust-field{
        grid-template-columns:1fr;gap:3px;align-items:stretch;
    }
}
@media (max-width:767.98px){
    .cw-page .cust-identity-split{grid-template-columns:1fr;padding:0 .65rem .85rem}
    .cw-page .cust-party-col + .cust-party-col{
        border-left:0;border-top:1px solid #E8EEF5;margin-top:.65rem;padding:.65rem 0 0;
    }
}
.cw-page .cust-ref-block{
    background:#F8FAFC;border:1px solid #E8EEF5;border-radius:12px;padding:12px;
}
.cw-page .cust-ref-title{
    font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748B;margin-bottom:10px;
}
.cw-page .cust-product-input{background:#EFF6FF !important;border-color:#BFDBFE !important;font-weight:600;color:#1E3A8A}
.cw-page .cust-consent{
    min-height:34px;border-radius:999px;display:inline-flex;align-items:center;gap:8px;
    padding:0 12px;font-size:.78rem;font-weight:600;width:100%;
}
.cw-page .cust-consent.ok{background:#ECFDF5;color:#047857;border:1px solid #A7F3D0}
.cw-page .cust-consent.warn{background:#FFF7ED;color:#C2410C;border:1px solid #FED7AA}
.cw-page .hint{font-size:.78rem;color:var(--cw-muted);margin-top:6px}
.cw-page .hint.err{color:#b91c1c}
.cw-page .cw-extra-section{margin-top:1rem}
.cw-page .table-sm td,.cw-page .table-sm th{font-size:.82rem;vertical-align:middle}
@media (max-width:1199.98px){
    .cw-page .overview-grid{grid-template-columns:1fr}
}
@media (max-width:991.98px){
    .cw-page .split-cols{grid-template-columns:1fr}
    .cw-page .detail-grid{grid-template-columns:1fr}
    .cw-page .detail-col:first-child{padding-right:0}
    .cw-page .detail-col + .detail-col{border-left:0;padding-left:0;border-top:1px solid #E5E7EB;padding-top:10px;margin-top:6px}
    .cw-page .detail-row{grid-template-columns:140px 10px minmax(0,1fr)}
    .cw-page .sanc-mini-grid{grid-template-columns:1fr}
}
@media (max-width:767.98px){.cw-page .cw-step{min-width:72px}}
</style>
@endpush

@section('content')
<div class="cw-page w-full max-w-full px-4 py-4" id="cwPage" data-active-tab="{{ $activeTab }}">
    {{-- Header --}}
    <div class="cw-hero">
        <div class="flex items-start gap-3">
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
                <button type="button" class="btn-ghost" data-bs-toggle="modal" data-bs-target="#cwReassignModal"><i class="bi bi-person"></i> Reassign</button>
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

    {{-- Case summary + progress (single card) --}}
    <div class="cw-summary-card">
        <div class="case-ribbon">
            <div class="rb-lead" aria-hidden="true"><i class="bi bi-briefcase-fill"></i></div>
            <div class="rb-item">
                <div class="rb-text">
                    <div class="rb-label">Case Reference</div>
                    <div class="rb-value">
                        <span id="cwCaseRef">{{ $case->case_no }}</span>
                        <button type="button" class="rb-copy" data-copy="#cwCaseRef" title="Copy case reference"><i class="bi bi-copy"></i></button>
                    </div>
                </div>
            </div>
            <div class="rb-item">
                <i class="bi bi-person rb-ico" aria-hidden="true"></i>
                <div class="rb-text">
                    <div class="rb-label">Customer</div>
                    <div class="rb-value"><span>{{ $case->customer_name }}</span></div>
                </div>
            </div>
            <div class="rb-item">
                <i class="bi bi-layers rb-ico" aria-hidden="true"></i>
                <div class="rb-text">
                    <div class="rb-label">Product</div>
                    <div class="rb-value"><span>{{ $case->product ?: '—' }}</span></div>
                </div>
            </div>
            <div class="rb-item">
                <i class="bi bi-currency-rupee rb-ico" aria-hidden="true"></i>
                <div class="rb-text">
                    <div class="rb-label">Requested Amount</div>
                    <div class="rb-value"><span>{{ $fmtInr($case->loan_amount) }}</span></div>
                </div>
            </div>
            <div class="rb-item">
                <i class="bi bi-bank rb-ico" aria-hidden="true"></i>
                <div class="rb-text">
                    <div class="rb-label">Branch</div>
                    <div class="rb-value"><span>{{ $case->orgUnit?->name ?? '—' }}</span></div>
                </div>
            </div>
            <div class="rb-item rb-status">
                <div class="rb-text">
                    <div class="rb-label">Status</div>
                    <div class="rb-value">
                        <span class="status-pill {{ $statusClass }}">{{ $case->status }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="cw-progress" aria-label="Case progress">
            @foreach($progress as $step)
                <div class="cw-step {{ $step['state'] }}" aria-disabled="true">
                    <div class="dot">
                        @if($step['state'] === 'done')
                            <i class="bi bi-check-lg"></i>
                        @elseif($step['state'] === 'rejected')
                            <i class="bi bi-x-lg"></i>
                        @else
                            <i class="bi {{ $step['icon'] ?? 'bi-circle' }}"></i>
                        @endif
                    </div>
                    <div class="step-label">{{ $step['label'] }}</div>
                    @if(!empty($step['at_label']) && in_array($step['state'], ['done', 'active'], true))
                        <span class="step-at">{{ $step['at_label'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>
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
            'overview' => ['Overview', 'bi-grid'],
            'customer' => ['Customer', 'bi-person'],
            'recommendation' => ['BM Recommendation', 'bi-lightbulb'],
            'documents' => ['Documents', 'bi-file-earmark'],
            'lenders' => ['Lender Applications', 'bi-layers'],
            'queries' => ['Bank Queries', 'bi-chat-dots'],
            'sanction' => ['Sanction', 'bi-award'],
            'disbursement' => ['Disbursement', 'bi-credit-card'],
            'activity' => ['Activity History', 'bi-clock-history'],
        ] as $key => $meta)
            <button type="button" class="cw-tab {{ $activeTab === $key ? 'active' : '' }}" data-cw-tab="{{ $key }}" role="tab" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}">
                <i class="bi {{ $meta[1] }}"></i><span>{{ $meta[0] }}</span>
            </button>
        @endforeach
    </div>

    {{-- ===================== OVERVIEW ===================== --}}
    @php
        $em = $app['email'] ?? ($cd['email'] ?? null);
        $ten = data_get($cd, 'tenure', data_get($cd, 'loan.tenure'));
        $prefContact = $cd['preferred_contact_time'] ?? null;
        $mobileDigits = preg_replace('/\D+/', '', (string) ($case->mobile ?? ''));
        $mobileDisplay = strlen($mobileDigits) === 10
            ? ('+91 '.substr($mobileDigits, 0, 5).' '.substr($mobileDigits, 5))
            : ($case->mobile ? ('+91 '.$case->mobile) : null);
    @endphp
    <div class="cw-pane {{ $activeTab === 'overview' ? 'active' : '' }}" data-pane="overview">
        <div class="overview-grid">
            <div class="overview-main">
                <div class="sec-card detail-card">
                    <div class="sec-head">
                        <div class="sec-head-left">
                            <span class="sec-ico"><i class="bi bi-person"></i></span>
                            <div>
                                <h6>Customer &amp; Loan Details</h6>
                            </div>
                        </div>
                        @if($canEdit)
                            <button type="button" class="btn-link-cw" data-cw-tab-jump="customer"><i class="bi bi-pencil"></i> Edit</button>
                        @endif
                    </div>
                    <div class="sec-body">
                        <div class="detail-grid">
                            <div class="detail-col">
                                <div class="detail-row">
                                    <div class="d-label">Customer Reference</div><div class="d-sep">:</div>
                                    <div class="d-value">{{ data_get($cd, 'customer_ref', 'CUS-'.str_pad((string)$case->id, 6, '0', STR_PAD_LEFT)) }}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Customer Name</div><div class="d-sep">:</div>
                                    <div class="d-value is-strong">{{ $case->customer_name }}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Mobile Number</div><div class="d-sep">:</div>
                                    <div class="d-value">
                                        @if($mobileDisplay)
                                            <i class="bi bi-telephone"></i><a href="tel:+91{{ $mobileDigits }}">{{ $mobileDisplay }}</a>
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Email</div><div class="d-sep">:</div>
                                    <div class="d-value">
                                        @if($em)
                                            <i class="bi bi-envelope"></i><a href="mailto:{{ $em }}">{{ $em }}</a>
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Preferred Contact Time</div><div class="d-sep">:</div>
                                    <div class="d-value">
                                        @if($prefContact)<i class="bi bi-clock"></i><span>{{ $prefContact }}</span>@else — @endif
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Consent</div><div class="d-sep">:</div>
                                    <div class="d-value">
                                        <span class="pill-soft consent {{ $consentLabel === 'Recorded' ? '' : 'warn' }}">
                                            @if($consentLabel === 'Recorded')<i class="bi bi-check-lg"></i>@endif
                                            {{ $consentLabel }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-col">
                                <div class="detail-row">
                                    <div class="d-label">Product</div><div class="d-sep">:</div>
                                    <div class="d-value">
                                        @if($case->product)
                                            <span class="pill-soft product">{{ $case->product }}</span>
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Requested Amount</div><div class="d-sep">:</div>
                                    <div class="d-value is-strong">{{ $fmtInr($case->loan_amount) }}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Purpose</div><div class="d-sep">:</div>
                                    <div class="d-value">{{ $cd['purpose'] ?? data_get($cd, 'loan.purpose', '—') }}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Tenure</div><div class="d-sep">:</div>
                                    <div class="d-value">{{ $ten ? $ten.' months' : '—' }}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Employment Type</div><div class="d-sep">:</div>
                                    <div class="d-value">{{ $emp['type_applicant'] ?? ($cd['employment_type'] ?? '—') }}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Monthly Income</div><div class="d-sep">:</div>
                                    <div class="d-value is-strong">{{ $fmtInr($emp['income_applicant'] ?? ($cd['monthly_income'] ?? null)) }}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="d-label">Existing Obligations</div><div class="d-sep">:</div>
                                    <div class="d-value is-strong">{{ $fmtInr($cd['obligations'] ?? null) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sec-card cw-table-card">
                    <div class="sec-head has-line">
                        <div class="sec-head-left">
                            <span class="sec-ico"><i class="bi bi-layers"></i></span>
                            <div><h6>Lender Applications</h6></div>
                        </div>
                        @if($canEdit)
                            <button type="button" class="btn-link-cw" data-bs-toggle="modal" data-bs-target="#cwAddLenderModal"><i class="bi bi-plus-lg"></i> Add Application</button>
                        @endif
                    </div>
                    <div class="cw-table-wrap">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead>
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
                                        @php
                                            $baStatusClass = match($ba->status){
                                                'Processing' => 'process',
                                                'Bank Query' => 'query',
                                                'Sanctioned' => 'ok',
                                                'Disbursed' => 'done',
                                                'Rejected' => 'bad',
                                                default => 'login',
                                            };
                                            $lenderName = (string) ($ba->lender_name ?? '');
                                            $lenderKey = strtolower($lenderName);
                                            $chipClass = match(true){
                                                str_contains($lenderKey, 'hdfc') => 'hdfc',
                                                str_contains($lenderKey, 'icici') => 'icici',
                                                str_contains($lenderKey, 'sbi') || str_contains($lenderKey, 'state bank') => 'sbi',
                                                str_contains($lenderKey, 'axis') => 'axis',
                                                default => '',
                                            };
                                            $chipLabel = collect(preg_split('/\s+/', trim($lenderName)))
                                                ->filter()
                                                ->take(2)
                                                ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))
                                                ->implode('') ?: 'BK';
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="lender-chip {{ $chipClass }}">{{ $chipLabel }}</span>
                                                    <span class="fw-semibold">{{ $ba->lender_name }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $ba->application_ref ?: '—' }}</td>
                                            <td>{{ optional($ba->login_date)->format('d M Y') ?: '—' }}</td>
                                            <td><span class="status-pill {{ $baStatusClass }}">{{ $ba->status }}</span></td>
                                            <td>{{ optional($ba->follow_up_at)->format('d M Y') ?: '—' }}</td>
                                            <td class="text-end">
                                                <button type="button" class="btn-action" data-cw-tab-jump="lenders" title="Open"><i class="bi bi-three-dots-vertical"></i></button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-3">No lender applications yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="cw-table-note"><i class="bi bi-info-circle"></i>A case can have multiple lender applications.</div>
                    </div>
                </div>

                <div class="sec-card cw-table-card">
                    <div class="sec-head has-line">
                        <div class="sec-head-left">
                            <span class="sec-ico"><i class="bi bi-file-earmark-text"></i></span>
                            <div><h6>Documents</h6></div>
                        </div>
                        <button type="button" class="btn-link-cw" data-cw-tab-jump="documents">View All →</button>
                    </div>
                    <div class="cw-table-wrap">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Uploaded</th>
                                        <th>Verification</th>
                                        <th>Reviewer</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(collect($docs)->take(5) as $doc)
                                        @php
                                            $st = $doc['status'] ?? 'Pending';
                                            $badge = match($st){ 'Verified'=>'ok', 'Rejected'=>'bad', default=>'warn' };
                                            $path = $doc['path'] ?? ($doc['storage_path'] ?? null);
                                            $label = $doc['label'] ?? $doc['key'] ?? 'Document';
                                            $docIcon = match(true){
                                                str_contains(strtolower((string)$label), 'income') => 'bi-cash-stack',
                                                str_contains(strtolower((string)$label), 'address') => 'bi-geo-alt',
                                                str_contains(strtolower((string)$label), 'identity') || str_contains(strtolower((string)$label), 'id proof') => 'bi-person-vcard',
                                                default => 'bi-file-earmark-text',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi {{ $docIcon }} doc-type-ico"></i>
                                                    <span class="fw-semibold">{{ $label }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $doc['uploaded_at'] ?? '—' }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="doc-badge {{ $badge }}">
                                                        @if($st === 'Verified')<i class="bi bi-check-lg"></i>
                                                        @elseif($st === 'Rejected')<i class="bi bi-x-lg"></i>
                                                        @else<i class="bi bi-clock"></i>@endif
                                                        {{ $st }}
                                                    </span>
                                                    @if($st === 'Rejected' && !empty($doc['rejection_reason']))
                                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" title="{{ $doc['rejection_reason'] }}">Reason available</button>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $doc['reviewer'] ?? '—' }}</td>
                                            <td class="text-end">
                                                @if($path)
                                                    <a class="btn-download" href="{{ asset('storage/'.$path) }}" target="_blank" title="Download"><i class="bi bi-download"></i></a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">No documents uploaded.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overview-side">
                @php
                    $leadSourceRaw = $case->lead?->source ?? data_get($cd, 'lead_source');
                    $leadSourceMap = [
                        'vrm' => 'VRM Calling',
                        'vrm_calling' => 'VRM Calling',
                        'walk_in' => 'Walk-in',
                        'referral' => 'Referral',
                        'partner' => 'Partner',
                        'digital' => 'Digital',
                    ];
                    $leadSourceLabel = $leadSourceRaw
                        ? ($leadSourceMap[strtolower((string) $leadSourceRaw)] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $leadSourceRaw)))
                        : '—';
                    $ownerDisplay = $case->owner ? ($roleLabel($case->owner).' - '.$case->owner->name) : '—';
                    $bmDisplay = $branchManager ? ($roleLabel($branchManager).' - '.$branchManager->name) : '—';
                    $queryOwner = $openQuery?->responsible
                        ? ($roleLabel($openQuery->responsible).' - '.$openQuery->responsible->name)
                        : ($openQuery?->responsible?->name ?? '—');
                @endphp
                <div class="sec-card">
                    <div class="sec-head has-line">
                        <div class="sec-head-left">
                            <span class="sec-ico"><i class="bi bi-people"></i></span>
                            <div><h6>Case Ownership</h6></div>
                        </div>
                    </div>
                    <div class="sec-body">
                        <div class="own-list">
                            @foreach([
                                ['ico' => 'bi-building', 'label' => 'Branch', 'value' => $case->orgUnit?->name ?? '—', 'valIco' => null],
                                ['ico' => 'bi-person', 'label' => 'SM/JSM Owner', 'value' => $ownerDisplay, 'valIco' => $case->owner ? 'bi-person-circle' : null],
                                ['ico' => 'bi-person-vcard', 'label' => 'Branch Manager', 'value' => $bmDisplay, 'valIco' => $branchManager ? 'bi-person-circle' : null],
                                ['ico' => 'bi-diagram-3', 'label' => 'Lead Source', 'value' => $leadSourceLabel, 'valIco' => $leadSourceRaw ? 'bi-telephone' : null],
                            ] as $row)
                                <div class="own-row">
                                    <span class="own-ico"><i class="bi {{ $row['ico'] }}"></i></span>
                                    <span class="d-label">{{ $row['label'] }}</span>
                                    <span class="d-sep">:</span>
                                    <span class="d-value">
                                        @if($row['valIco'])<i class="bi {{ $row['valIco'] }} val-ico"></i>@endif
                                        <span>{{ $row['value'] }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="sec-card {{ $openQuery ? 'query-alert' : '' }}">
                    <div class="sec-head has-line">
                        <div class="sec-head-left">
                            <span class="sec-ico {{ $openQuery ? 'ico-amber' : '' }}"><i class="bi {{ $openQuery ? 'bi-exclamation-triangle' : 'bi-patch-check' }}"></i></span>
                            <div><h6>Pending Bank Query</h6></div>
                        </div>
                        @if($openQuery)
                            <span class="pill-pending">Pending</span>
                        @endif
                    </div>
                    <div class="sec-body">
                        @if($openQuery)
                            <div class="query-block">
                                <div>
                                    <div class="q-label">Query Summary</div>
                                    <div class="q-summary">{{ \Illuminate\Support\Str::limit($openQuery->details, 140) }}</div>
                                </div>
                                <div>
                                    <div class="q-label">Responsible Employee</div>
                                    <div class="q-value"><i class="bi bi-person-circle"></i>{{ $queryOwner }}</div>
                                </div>
                                <div>
                                    <div class="q-label">Due Date</div>
                                    <div class="q-value"><i class="bi bi-calendar3"></i>{{ optional($openQuery->due_at)->format('d M Y') ?: '—' }}</div>
                                </div>
                            </div>
                            <div class="query-actions">
                                <button type="button" class="btn-ghost query-view" data-cw-tab-jump="queries">View Query</button>
                            </div>
                        @else
                            <div class="text-center text-muted py-2">
                                <i class="bi bi-check2-circle d-block fs-4 mb-1 opacity-50"></i>
                                No open bank queries.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="sec-card">
                    <div class="sec-head has-line">
                        <div class="sec-head-left">
                            <span class="sec-ico"><i class="bi bi-layers"></i></span>
                            <div><h6>Sanction &amp; Disbursement</h6></div>
                        </div>
                    </div>
                    <div class="sec-body">
                        @php
                            $sanctionAmt = $activeSanction?->sanction_amount ?? $case->sanction_amount;
                            $remainingAmt = $sanctionCap > 0
                                ? max(0, $sanctionCap - $verifiedDisbTotal - $pendingDisbTotal)
                                : null;
                            $sanctionDateLabel = optional($activeSanction?->sanction_date ?? $case->sanction_date)->format('d M Y') ?: '—';
                        @endphp
                        @if($activeSanction || $verifiedDisbTotal > 0 || $case->sanction_amount)
                            <div class="sanc-mini-grid">
                                <div class="sanc-mini violet">
                                    <span class="sm-ico"><i class="bi bi-currency-rupee"></i></span>
                                    <div>
                                        <div class="sm-label">Sanctioned Amount</div>
                                        <div class="sm-value">{{ $fmtInr($sanctionAmt) }}</div>
                                    </div>
                                </div>
                                <div class="sanc-mini green">
                                    <span class="sm-ico"><i class="bi bi-file-earmark-check"></i></span>
                                    <div>
                                        <div class="sm-label">Total Disbursed <span class="sm-note">(Verified)</span></div>
                                        <div class="sm-value">{{ $fmtInr($verifiedDisbTotal) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="sanc-meta">
                                <div class="sanc-meta-row">
                                    <span class="d-label">Remaining Amount</span><span class="d-sep">:</span>
                                    <span class="d-value">{{ $remainingAmt !== null ? $fmtInr($remainingAmt) : '—' }}</span>
                                </div>
                                <div class="sanc-meta-row">
                                    <span class="d-label">Sanction Date</span><span class="d-sep">:</span>
                                    <span class="d-value">{{ $sanctionDateLabel }}</span>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-inbox d-block fs-4 mb-1 opacity-50"></i>
                                No sanction or disbursement recorded yet.<br>
                                <span class="small">These details become available as processing progresses.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="sec-card">
                    <div class="sec-head has-line">
                        <div class="sec-head-left">
                            <span class="sec-ico ico-blue"><i class="bi bi-clock-history"></i></span>
                            <div><h6>Recent Activity</h6></div>
                        </div>
                        <button type="button" class="btn-link-cw" data-cw-tab-jump="activity">View Activity History →</button>
                    </div>
                    <div class="sec-body" style="padding-top:6px">
                        <ul class="timeline">
                            @forelse($case->events->take(5) as $ev)
                                @php
                                    $dot = match($ev->event_type){
                                        'created','status' => 'green',
                                        'document' => 'teal',
                                        'application','disbursement' => 'blue',
                                        'query','assignment' => 'amber',
                                        default => 'blue',
                                    };
                                    $ico = match($ev->event_type){
                                        'created' => 'bi-plus-lg',
                                        'document' => 'bi-cloud-upload',
                                        'application' => 'bi-layers',
                                        'disbursement' => 'bi-cash-coin',
                                        'query' => 'bi-chat-left-text',
                                        'assignment' => 'bi-person-check',
                                        'status' => 'bi-arrow-right-circle',
                                        default => 'bi-dot',
                                    };
                                @endphp
                                <li>
                                    <span class="tl-dot {{ $dot }}"><i class="bi {{ $ico }}"></i></span>
                                    <div class="tl-body">
                                        <span class="tl-title">{{ $ev->title }}</span>
                                        <span class="tl-time">{{ $ev->created_at?->format('d M Y, h:i a') }}</span>
                                    </div>
                                </li>
                            @empty
                                <li>
                                    <div class="text-center text-muted py-3 w-100">
                                        <i class="bi bi-clock-history d-block fs-4 mb-1 opacity-50"></i>
                                        No activity yet.
                                    </div>
                                </li>
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
            <input type="hidden" name="cw_tab" value="{{ $activeTab }}">
            @include('goal.cases.partials.customer_fields')
            @if($canEdit)
            <div class="cw-footer">
                <div class="hint mb-0">Saves customer details for this case.</div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('goal.cases.show', ['case' => $case, 'tab' => 'overview']) }}" class="btn btn-outline-secondary" style="height:42px;border-radius:10px;display:inline-flex;align-items:center;">Cancel</a>
                    <button type="submit" class="btn-save"><i class="bi bi-check2"></i> Save Case</button>
                </div>
            </div>
            @endif
        </form>
    </div>

    {{-- ===================== BM RECOMMENDATION ===================== --}}
    <div class="cw-pane {{ $activeTab === 'recommendation' ? 'active' : '' }}" data-pane="recommendation">
        <form method="POST" action="{{ route('goal.cases.workspace', $case) }}" id="cwRecommendationForm" novalidate>
            @csrf
            <input type="hidden" name="cw_tab" value="recommendation">
            <input type="hidden" name="customer_name" value="{{ old('customer_name', $case->customer_name) }}">
            <input type="hidden" name="mobile" value="{{ old('mobile', $case->mobile) }}">
            @include('goal.cases.partials.customer_extras')
            @if($canEdit)
            <div class="cw-footer">
                <div class="hint mb-0">Saves BM recommendation, existing loans and collateral.</div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('goal.cases.show', ['case' => $case, 'tab' => 'overview']) }}" class="btn btn-outline-secondary" style="height:42px;border-radius:10px;display:inline-flex;align-items:center;">Cancel</a>
                    <button type="submit" class="btn-save"><i class="bi bi-check2"></i> Save Recommendation</button>
                </div>
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
    function activateTab(key) {
        page.querySelectorAll('.cw-tab').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-cw-tab') === key);
        });
        page.querySelectorAll('.cw-pane').forEach(function (pane) {
            pane.classList.toggle('active', pane.getAttribute('data-pane') === key);
        });
        page.querySelectorAll('input[name="cw_tab"]').forEach(function (inp) {
            inp.value = key;
        });
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

    var custLayout = document.getElementById('cwCustomerLayout');
    if (custLayout) {
        function setCustomerParty(party) {
            custLayout.setAttribute('data-party', party);
            custLayout.querySelectorAll('.cust-party-btn').forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-party') === party);
            });
            custLayout.querySelectorAll('.cust-party-panel').forEach(function (panel) {
                panel.classList.toggle('is-active', panel.getAttribute('data-party-panel') === party);
            });
            custLayout.querySelectorAll('.cust-party-field').forEach(function (field) {
                var match = field.getAttribute('data-party-field') === party;
                field.classList.toggle('d-none', !match);
            });
            if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.init === 'function') {
                window.IsarvaSelect2.init(custLayout);
            }
        }
        custLayout.querySelectorAll('.cust-party-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setCustomerParty(btn.getAttribute('data-party'));
            });
        });
        setCustomerParty(custLayout.getAttribute('data-party') || 'applicant');
        if (window.IsarvaSelect2 && typeof window.IsarvaSelect2.init === 'function') {
            window.IsarvaSelect2.init(custLayout);
        }
    }

    page.querySelectorAll('.js-mobile').forEach(function (el) {
        el.addEventListener('input', function () {
            el.value = String(el.value || '').replace(/\D+/g, '').slice(0, 10);
        });
    });

    (function initBtSourceRows() {
        var list = document.getElementById('cwBtSourceList');
        var tpl = document.getElementById('cwBtRowTpl');
        var addBtn = document.getElementById('cwAddBtRow');
        if (!list || !tpl) return;

        function reindexBt() {
            list.querySelectorAll('[data-bt-row]').forEach(function (row, i) {
                row.querySelectorAll('[name]').forEach(function (el) {
                    el.name = String(el.name || '').replace(/existing_loans\[bt_source\]\[\d+\]/, 'existing_loans[bt_source][' + i + ']');
                });
            });
        }

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var idx = list.querySelectorAll('[data-bt-row]').length;
                var html = tpl.innerHTML.replace(/__i__/g, String(idx));
                var wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                var row = wrap.firstElementChild;
                if (!row) return;
                list.appendChild(row);
            });
        }

        list.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-remove-bt]');
            if (!btn) return;
            var rows = list.querySelectorAll('[data-bt-row]');
            if (rows.length <= 1) {
                var only = rows[0];
                if (!only) return;
                only.querySelectorAll('input, select').forEach(function (el) {
                    if (el.tagName === 'SELECT') el.selectedIndex = 0;
                    else el.value = '';
                });
                return;
            }
            var row = btn.closest('[data-bt-row]');
            if (row) row.remove();
            reindexBt();
        });
    })();

    (function initLoanDetailRows() {
        var body = document.getElementById('cwLoanRowsBody');
        var tpl = document.getElementById('cwLoanRowTpl');
        var addBtn = document.getElementById('cwAddLoanRow');
        if (!body || !tpl) return;

        function reindexLoan() {
            body.querySelectorAll('[data-loan-row]').forEach(function (tr, i) {
                var idx = tr.querySelector('.cw-loan-idx');
                if (idx) idx.textContent = String(i + 1);
                tr.querySelectorAll('[name]').forEach(function (el) {
                    el.name = String(el.name || '').replace(/existing_loans\[rows\]\[\d+\]/, 'existing_loans[rows][' + i + ']');
                });
            });
        }

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var idx = body.querySelectorAll('[data-loan-row]').length;
                var html = tpl.innerHTML
                    .replace(/__i__/g, String(idx))
                    .replace(/__n__/g, String(idx + 1));
                var wrap = document.createElement('tbody');
                wrap.innerHTML = html.trim();
                var row = wrap.firstElementChild;
                if (!row) return;
                body.appendChild(row);
                reindexLoan();
            });
        }

        body.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-remove-loan]');
            if (!btn) return;
            var rows = body.querySelectorAll('[data-loan-row]');
            if (rows.length <= 1) {
                var only = rows[0];
                if (!only) return;
                only.querySelectorAll('input, select').forEach(function (el) {
                    if (el.tagName === 'SELECT') {
                        el.selectedIndex = 0;
                        if (el.classList.contains('cw-party-chip')) {
                            el.classList.add('is-app');
                            el.classList.remove('is-co');
                        }
                    } else {
                        el.value = '';
                    }
                });
                return;
            }
            var row = btn.closest('[data-loan-row]');
            if (row) row.remove();
            reindexLoan();
        });
    })();

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

    function syncCwPreferredContactTime() {
        var fromEl = document.getElementById('cw_preferred_contact_from');
        var toEl = document.getElementById('cw_preferred_contact_to');
        var hidden = document.getElementById('cw_preferred_contact_time');
        if (!hidden) return;
        var from = (fromEl && fromEl.value ? fromEl.value : '').trim();
        var to = (toEl && toEl.value ? toEl.value : '').trim();
        if (from && to) hidden.value = from + ' - ' + to;
        else if (from) hidden.value = from;
        else if (to) hidden.value = to;
        else hidden.value = '';
    }
    ['cw_preferred_contact_from', 'cw_preferred_contact_to'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', syncCwPreferredContactTime);
        el.addEventListener('input', syncCwPreferredContactTime);
    });
    syncCwPreferredContactTime();

    activateTab(page.getAttribute('data-active-tab') || 'overview');

    page.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var sel = btn.getAttribute('data-copy');
            var el = sel ? document.querySelector(sel) : null;
            var text = el ? (el.textContent || '').trim() : '';
            if (!text || !navigator.clipboard) return;
            navigator.clipboard.writeText(text).then(function () {
                btn.setAttribute('title', 'Copied');
                btn.innerHTML = '<i class="bi bi-check2"></i>';
                setTimeout(function () {
                    btn.setAttribute('title', 'Copy case reference');
                    btn.innerHTML = '<i class="bi bi-copy"></i>';
                }, 1200);
            }).catch(function () {});
        });
    });
})();
</script>
@endpush
