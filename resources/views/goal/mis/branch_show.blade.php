@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.mis-show .pd-hero{align-items:center}
.pd-page.mis-show .mis-hero-meta{
    display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;align-items:center;
}
.pd-page.mis-show .mis-kpi-row{
    display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:.85rem;
}
@media (max-width:991.98px){.pd-page.mis-show .mis-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:575.98px){.pd-page.mis-show .mis-kpi-row{grid-template-columns:1fr}}
.pd-page.mis-show .mis-kpi-meta{margin:4px 0 0;font-size:.72rem;color:#94a3b8}
.pd-page.mis-show .mis-section{margin-bottom:.85rem}
.pd-page.mis-show .mis-grid{
    display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 16px;padding:1rem 1.1rem;
}
@media (max-width:991.98px){.pd-page.mis-show .mis-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:575.98px){.pd-page.mis-show .mis-grid{grid-template-columns:1fr}}
.pd-page.mis-show .mis-field label{
    display:block;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;
    color:#94a3b8;margin:0 0 4px;
}
.pd-page.mis-show .mis-field .val{
    font-size:.92rem;font-weight:650;color:#0f172a;line-height:1.35;word-break:break-word;
}
.pd-page.mis-show .mis-field .val.is-muted{color:#94a3b8;font-weight:500}
.pd-page.mis-show .mis-field .val.is-amt{font-variant-numeric:tabular-nums;font-weight:800}
.pd-page.mis-show .mis-field .val.is-net{color:#15803d}
.pd-page.mis-show .mis-comm{
    display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;padding:1rem 1.1rem;
}
@media (max-width:767.98px){.pd-page.mis-show .mis-comm{grid-template-columns:1fr}}
.pd-page.mis-show .mis-comm-card{
    border:1px solid #eef2f7;border-radius:12px;padding:12px 14px;background:#f8fafc;
}
.pd-page.mis-show .mis-comm-card.is-net{background:#f0fdf4;border-color:#bbf7d0}
.pd-page.mis-show .mis-comm-card .lbl{
    font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#64748b;margin:0;
}
.pd-page.mis-show .mis-comm-card .num{
    margin-top:4px;font-size:1.2rem;font-weight:800;color:#0f172a;font-variant-numeric:tabular-nums;
}
.pd-page.mis-show .mis-comm-card.is-net .num{color:#15803d}
.pd-page.mis-show .mis-comm-card .sub{margin-top:2px;font-size:.75rem;color:#94a3b8}
</style>
@endpush

@section('content')
@php
    $v = function (array $keys, $fallback = '—') use ($mis, $case) {
        foreach ($keys as $k) {
            if (str_starts_with($k, 'case.')) {
                $attr = substr($k, 5);
                $val = data_get($case, $attr);
            } else {
                if (!is_array($mis) || !array_key_exists($k, $mis)) continue;
                $val = $mis[$k];
            }
            if ($val === null || $val === '') continue;
            return is_scalar($val) ? $val : $fallback;
        }
        return $fallback;
    };
    $fmtInr = function ($n) {
        if ($n === null || $n === '' || $n === '—') return '—';
        $clean = str_replace([',', '₹', ' '], '', (string) $n);
        if (!is_numeric($clean)) return $n;
        return '₹ '.number_format((float) $clean, 2);
    };
    $fmtDate = function ($raw) {
        if ($raw === null || $raw === '' || $raw === '—') return '—';
        try { return \Illuminate\Support\Carbon::parse($raw)->format('d M Y'); }
        catch (\Throwable $e) { return $raw; }
    };
    $statusPill = match ((string) $case->status) {
        'Disbursed' => 'ok',
        'Sanctioned' => 'info',
        'Rejected', 'Closed' => 'bad',
        'Bank Login', 'Processing', 'Bank Query' => 'warn',
        default => 'slate',
    };
    $otc = $v(['otc_pdd_status', 'otc_pdd']);
    $otcPill = match ((string) $otc) {
        'PDD Cleared', 'OTC Cleared' => 'ok',
        'Pending PDD' => 'warn',
        default => 'slate',
    };
    $disbAmt = $v(['disb_amount', 'disb_amt']);
    $payinAmt = $v(['payin_amt', 'pay_in_amt']);
    $payoutAmt = $v(['payout_amount', 'payout_amt']);
    $net = $v(['net_revenue']);
@endphp
<div class="container-fluid p-4 pd-page mis-show">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li><a href="{{ route('goal.branch-mis') }}">Branch MIS</a></li>
                <li class="sep">›</li>
                <li>{{ $case->case_no }}</li>
            </ul>
            <h1 class="pd-title">{{ $case->customer_name ?: 'Case MIS' }}</h1>
            <p class="pd-sub">Branch commercial sheet for {{ $case->case_no }}</p>
            <div class="mis-hero-meta">
                <a class="pd-case" href="{{ route('goal.cases.show', $case) }}">{{ $case->case_no }}</a>
                <span class="pd-pill {{ $statusPill }}">{{ $case->status ?: '—' }}</span>
                @if($otc !== '—')
                    <span class="pd-pill {{ $otcPill }}">{{ $otc }}</span>
                @endif
                <span class="pd-muted">{{ $case->orgUnit?->name ?: '—' }} · {{ $case->product ?: '—' }}</span>
            </div>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.branch-mis') }}" class="pd-link-btn"><i class="bi bi-arrow-left"></i> Back to MIS</a>
            <a href="{{ route('goal.cases.show', ['case' => $case, 'tab' => 'mis']) }}" class="pd-link-btn primary">
                <i class="bi bi-pencil-square"></i> Edit in workspace
            </a>
        </div>
    </div>

    <div class="mis-kpi-row">
        <div class="pd-kpi tone-blue">
            <p class="pd-kpi-label">Disbursed</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr($disbAmt) }}</p>
            <p class="mis-kpi-meta">{{ $fmtDate($v(['disb_date'])) }}</p>
        </div>
        <div class="pd-kpi tone-green">
            <p class="pd-kpi-label">Payin</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr($payinAmt) }}</p>
            <p class="mis-kpi-meta">{{ $v(['payin_pct', 'pay_in_pct']) }}%</p>
        </div>
        <div class="pd-kpi tone-amber">
            <p class="pd-kpi-label">Payout</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr($payoutAmt) }}</p>
            <p class="mis-kpi-meta">{{ $v(['payout_pct']) }}% · {{ $v(['payout_month']) }}</p>
        </div>
        <div class="pd-kpi tone-green">
            <p class="pd-kpi-label">Net revenue</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr($net) }}</p>
            <p class="mis-kpi-meta">Payin − payout</p>
        </div>
    </div>

    <div class="pd-card mis-section">
        <div class="pd-card-head"><h5>Commercials</h5></div>
        <div class="mis-comm">
            <div class="mis-comm-card">
                <p class="lbl">Payin %</p>
                <div class="num">{{ $v(['payin_pct', 'pay_in_pct']) }}%</div>
                <div class="sub">Amount {{ $fmtInr($payinAmt) }}</div>
            </div>
            <div class="mis-comm-card">
                <p class="lbl">Payout %</p>
                <div class="num">{{ $v(['payout_pct']) }}%</div>
                <div class="sub">Amount {{ $fmtInr($payoutAmt) }}</div>
            </div>
            <div class="mis-comm-card is-net">
                <p class="lbl">Net revenue</p>
                <div class="num">{{ $fmtInr($net) }}</div>
                <div class="sub">Branch margin on this file</div>
            </div>
        </div>
    </div>

    <div class="pd-card mis-section">
        <div class="pd-card-head"><h5>Case & banking</h5></div>
        <div class="mis-grid">
            <div class="mis-field">
                <label>LAN number</label>
                <div class="val {{ $v(['lan_number','lan','lan_no']) === '—' ? 'is-muted' : '' }}">{{ $v(['lan_number','lan','lan_no']) }}</div>
            </div>
            <div class="mis-field">
                <label>Code</label>
                <div class="val {{ $v(['code']) === '—' ? 'is-muted' : '' }}">{{ $v(['code']) }}</div>
            </div>
            <div class="mis-field">
                <label>Month</label>
                <div class="val">{{ $v(['month']) }}</div>
            </div>
            <div class="mis-field">
                <label>Product</label>
                <div class="val">{{ $case->product ?: '—' }}</div>
            </div>
            <div class="mis-field">
                <label>Sub product</label>
                <div class="val {{ $v(['sub_product']) === '—' ? 'is-muted' : '' }}">{{ $v(['sub_product']) }}</div>
            </div>
            <div class="mis-field">
                <label>Bank</label>
                <div class="val">{{ $v(['bank_name']) !== '—' ? $v(['bank_name']) : ($case->lender_name ?: '—') }}</div>
            </div>
            <div class="mis-field">
                <label>Login date</label>
                <div class="val">{{ $fmtDate($v(['login_date'])) }}</div>
            </div>
            <div class="mis-field">
                <label>Login amount</label>
                <div class="val is-amt">{{ $fmtInr($v(['login_amount','login_amt'])) }}</div>
            </div>
            <div class="mis-field">
                <label>Sanction date</label>
                <div class="val">{{ $case->sanction_date ? $case->sanction_date->format('d M Y') : $fmtDate($v(['sanction_dt','sanction_date'])) }}</div>
            </div>
            <div class="mis-field">
                <label>Sanction amount</label>
                <div class="val is-amt">{{ $case->sanction_amount !== null ? $fmtInr($case->sanction_amount) : $fmtInr($v(['sanc_amt','sanction_amount'])) }}</div>
            </div>
            <div class="mis-field">
                <label>Disbursement date</label>
                <div class="val">{{ $fmtDate($v(['disb_date'])) }}</div>
            </div>
            <div class="mis-field">
                <label>Disbursement amount</label>
                <div class="val is-amt">{{ $fmtInr($disbAmt) }}</div>
            </div>
        </div>
    </div>

    <div class="pd-card mis-section">
        <div class="pd-card-head"><h5>Source & people</h5></div>
        <div class="mis-grid">
            <div class="mis-field">
                <label>Location</label>
                <div class="val">{{ $case->orgUnit?->name ?: $v(['location']) }}</div>
            </div>
            <div class="mis-field">
                <label>Source</label>
                <div class="val {{ $v(['source']) === '—' ? 'is-muted' : '' }}">{{ $v(['source']) }}</div>
            </div>
            <div class="mis-field">
                <label>Source name</label>
                <div class="val {{ $v(['source_name']) === '—' ? 'is-muted' : '' }}">{{ $v(['source_name']) }}</div>
            </div>
            <div class="mis-field">
                <label>GSM / owner</label>
                <div class="val">{{ $v(['gsm_name']) !== '—' ? $v(['gsm_name']) : ($case->owner?->name ?: '—') }}</div>
            </div>
            <div class="mis-field">
                <label>Banker name</label>
                <div class="val {{ $v(['banker_name']) === '—' ? 'is-muted' : '' }}">{{ $v(['banker_name']) }}</div>
            </div>
            <div class="mis-field">
                <label>Banker contact</label>
                <div class="val {{ $v(['banker_contact']) === '—' ? 'is-muted' : '' }}">{{ $v(['banker_contact']) }}</div>
            </div>
            <div class="mis-field">
                <label>Banker email</label>
                <div class="val {{ $v(['banker_mail','banker_email']) === '—' ? 'is-muted' : '' }}">{{ $v(['banker_mail','banker_email']) }}</div>
            </div>
            <div class="mis-field">
                <label>Customer contact</label>
                <div class="val">{{ $case->mobile ?: '—' }}</div>
            </div>
            <div class="mis-field">
                <label>SM remark</label>
                <div class="val {{ $v(['sm_remark']) === '—' ? 'is-muted' : '' }}">{{ $v(['sm_remark']) }}</div>
            </div>
        </div>
    </div>

    <div class="pd-card mis-section">
        <div class="pd-card-head"><h5>References & applicant</h5></div>
        <div class="mis-grid">
            <div class="mis-field">
                <label>Ref 1 name</label>
                <div class="val {{ $v(['ref1_name']) === '—' ? 'is-muted' : '' }}">{{ $v(['ref1_name']) }}</div>
            </div>
            <div class="mis-field">
                <label>Ref 1 contact</label>
                <div class="val {{ $v(['ref1_contact']) === '—' ? 'is-muted' : '' }}">{{ $v(['ref1_contact']) }}</div>
            </div>
            <div class="mis-field">
                <label>Ref 2 name</label>
                <div class="val {{ $v(['ref2_name']) === '—' ? 'is-muted' : '' }}">{{ $v(['ref2_name']) }}</div>
            </div>
            <div class="mis-field">
                <label>Ref 2 contact</label>
                <div class="val {{ $v(['ref2_contact']) === '—' ? 'is-muted' : '' }}">{{ $v(['ref2_contact']) }}</div>
            </div>
            <div class="mis-field">
                <label>DOB applicant</label>
                <div class="val {{ $v(['dob_applicant']) === '—' ? 'is-muted' : '' }}">{{ $v(['dob_applicant']) }}</div>
            </div>
            <div class="mis-field">
                <label>Co-applicant</label>
                <div class="val {{ $v(['co_applicant']) === '—' ? 'is-muted' : '' }}">{{ $v(['co_applicant']) }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
