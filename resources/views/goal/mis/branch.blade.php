@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.mis-page .mis-filters{
    display:grid;grid-template-columns:repeat(3,minmax(120px,1fr)) repeat(3,minmax(120px,1fr)) minmax(160px,1.4fr) auto;
    gap:10px;align-items:end;margin-bottom:.85rem;padding:.85rem 1rem;
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
    box-shadow:0 6px 16px rgba(148,163,184,.08);
}
.pd-page.mis-page .mis-filters label{
    display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 4px;
}
.pd-page.mis-page .mis-filters .form-control,
.pd-page.mis-page .mis-filters .form-select{
    min-height:38px;height:38px;border-radius:9px;border:1px solid #d1d5db;font-size:.84rem;background:#fff;
}
.pd-page.mis-page .mis-filter-actions{display:flex;gap:8px;flex-wrap:wrap}
.pd-page.mis-page .mis-scroll{
    overflow:auto;max-height:calc(100vh - 340px);-webkit-overflow-scrolling:touch;
}
.pd-page.mis-page .pd-table{font-size:.78rem}
.pd-page.mis-page .pd-table thead th{
    position:sticky;top:0;z-index:2;white-space:nowrap;
    text-transform:uppercase;letter-spacing:.03em;font-size:.68rem;color:#64748b;
}
.pd-page.mis-page .pd-table tbody td{white-space:nowrap;vertical-align:middle}
.pd-page.mis-page .pd-table .sticky-col{
    position:sticky;left:0;z-index:1;background:#fff;
    box-shadow:2px 0 4px rgba(15,23,42,.04);
}
.pd-page.mis-page .pd-table thead .sticky-col{z-index:3;background:#f8fafc}
.pd-page.mis-page .pd-table tbody tr:hover .sticky-col{background:#f8fbff}
.pd-page.mis-page .amt{font-variant-numeric:tabular-nums;font-weight:700;color:#0f172a;white-space:nowrap}
.pd-page.mis-page .net-pos{color:#15803d;font-weight:700}
.pd-page.mis-page .mis-ws-btn{
    display:inline-flex;align-items:center;gap:4px;padding:.25rem .55rem;
    border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;color:#334155;
    font-size:.72rem;font-weight:600;text-decoration:none;white-space:nowrap;
}
.pd-page.mis-page .mis-ws-btn:hover{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
@media (max-width:1199.98px){
    .pd-page.mis-page .mis-filters{grid-template-columns:1fr 1fr 1fr}
}
@media (max-width:767.98px){
    .pd-page.mis-page .mis-filters{grid-template-columns:1fr;padding:.75rem}
    .pd-page.mis-page .mis-filter-actions{grid-column:1 / -1}
    .pd-page.mis-page .mis-scroll{max-height:none;overflow:visible;padding:0 .65rem .65rem}
    .pd-page.mis-page .pd-table .sticky-col{position:static;box-shadow:none}
}
</style>
@endpush

@section('content')
@php
    $mis = function ($m, array $keys, $fallback = '—') {
        foreach ($keys as $k) {
            if (!is_array($m) || !array_key_exists($k, $m)) continue;
            $v = $m[$k];
            if ($v === null || $v === '') continue;
            return is_scalar($v) ? $v : $fallback;
        }
        return $fallback;
    };
    $fmtInr = function ($n) {
        if ($n === null || $n === '' || $n === '—') return '—';
        if (!is_numeric(str_replace([',', '₹', ' '], '', (string) $n))) return $n;
        return '₹ '.number_format((float) str_replace([',', '₹', ' '], '', (string) $n), 0);
    };
    $fmtDate = function ($v) {
        if ($v === null || $v === '' || $v === '—') return '—';
        try {
            return \Illuminate\Support\Carbon::parse($v)->format('d-M-Y');
        } catch (\Throwable $e) {
            return $v;
        }
    };
    $statusPill = function (?string $status) {
        return match ((string) $status) {
            'Disbursed' => 'ok',
            'Sanctioned' => 'info',
            'Rejected', 'Closed' => 'bad',
            'Bank Login', 'Processing', 'Bank Query' => 'warn',
            default => 'slate',
        };
    };
    $otcPill = function (?string $otc) {
        return match ((string) $otc) {
            'PDD Cleared', 'OTC Cleared' => 'ok',
            'Pending PDD' => 'warn',
            default => 'slate',
        };
    };
@endphp
<div class="container-fluid p-4 pd-page mis-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Performance</li>
                <li class="sep">›</li>
                <li>Branch MIS</li>
            </ul>
            <h1 class="pd-title">Branch MIS</h1>
            <p class="pd-sub">Branch-wise case updation and export</p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.revenue-ledger') }}" class="pd-link-btn"><i class="bi bi-currency-rupee"></i> Revenue Ledger</a>
            <a href="{{ route('goal.branch-mis.export', request()->query()) }}" class="pd-link-btn primary">
                <i class="bi bi-download"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="pd-kpi-row">
        <div class="pd-kpi tone-blue">
            <p class="pd-kpi-label">Cases in MIS</p>
            <p class="pd-kpi-value">{{ number_format($kpis['total'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-green">
            <p class="pd-kpi-label">Disbursed amt</p>
            <p class="pd-kpi-value" style="font-size:1.2rem">{{ $fmtInr($kpis['disb_amount'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-amber">
            <p class="pd-kpi-label">Payout amt</p>
            <p class="pd-kpi-value" style="font-size:1.2rem">{{ $fmtInr($kpis['payout_amount'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-slate">
            <p class="pd-kpi-label">Net revenue</p>
            <p class="pd-kpi-value" style="font-size:1.2rem">{{ $fmtInr($kpis['net_revenue'] ?? 0) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('goal.branch-mis') }}" class="mis-filters">
        <div>
            <label for="misMonth">Month</label>
            <select id="misMonth" name="month" class="form-select">
                <option value="">All</option>
                @foreach($months as $mo)
                    <option value="{{ $mo }}" @selected(($filters['month'] ?? '') === $mo)>{{ $mo }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misBranch">Location</label>
            <select id="misBranch" name="branch" class="form-select">
                <option value="">All</option>
                @foreach($branches as $br)
                    <option value="{{ $br->id }}" @selected((int) ($filters['branch'] ?? 0) === (int) $br->id)>{{ $br->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misStatus">Status</label>
            <select id="misStatus" name="status" class="form-select">
                <option value="">All</option>
                @foreach($statuses as $st)
                    <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misBank">Bank</label>
            <select id="misBank" name="bank" class="form-select">
                <option value="">All</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank }}" @selected(($filters['bank'] ?? '') === $bank)>{{ $bank }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misOtc">OTC/PDD</label>
            <select id="misOtc" name="otc_pdd" class="form-select">
                <option value="">All</option>
                @foreach($otcOptions as $otc)
                    <option value="{{ $otc }}" @selected(($filters['otc_pdd'] ?? '') === $otc)>{{ $otc }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misProduct">Product</label>
            <select id="misProduct" name="product" class="form-select">
                <option value="">All</option>
                @foreach($products as $prod)
                    <option value="{{ $prod }}" @selected(($filters['product'] ?? '') === $prod)>{{ $prod }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misQ">Search</label>
            <input id="misQ" type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Ref / LAN / Customer">
        </div>
        <div class="mis-filter-actions">
            <button type="submit" class="pd-btn" style="height:38px"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ route('goal.branch-mis') }}" class="pd-btn-ghost" style="height:38px">Reset</a>
        </div>
    </form>

    <div class="pd-card">
        <div class="pd-card-head">
            <div>
                <h5>Branch case updation</h5>
                <div class="pd-muted" style="margin-top:2px">37 Excel columns · horizontal scroll · Export = filtered sheet (max 5,000)</div>
            </div>
        </div>
        <div class="mis-scroll pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th class="sticky-col">Ref Number</th>
                        <th>LAN Number</th>
                        <th>Customer Name</th>
                        <th>Customer Contact</th>
                        <th>Month</th>
                        <th>Login Date</th>
                        <th>Login Amount</th>
                        <th>Sanction Dt</th>
                        <th>Sanc Amt</th>
                        <th>Disb Date</th>
                        <th>Disb amt</th>
                        <th>Code</th>
                        <th>Product</th>
                        <th>Sub Product</th>
                        <th>Status</th>
                        <th>OTC/PDD Status</th>
                        <th>Location</th>
                        <th>Source</th>
                        <th>Source Name</th>
                        <th>GSM Name</th>
                        <th>Bank Name</th>
                        <th>Banker Name</th>
                        <th>Banker Contact</th>
                        <th>Banker Mail ID</th>
                        <th>PayIn%</th>
                        <th>Payin In Amt</th>
                        <th>Payout %</th>
                        <th>Payout Amount</th>
                        <th>Net Revenue</th>
                        <th>SM Remark</th>
                        <th>Payout Month</th>
                        <th>Ref - 1 Name</th>
                        <th>Ref - 1 Contact</th>
                        <th>Ref - 2 Name</th>
                        <th>Ref - 2 Contact</th>
                        <th>DOB APPLICANT</th>
                        <th>CO-APPLICANT</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($cases as $case)
                    @php
                        $m = is_array($case->branch_mis) ? $case->branch_mis : [];
                        $otc = $mis($m, ['otc_pdd_status', 'otc_pdd']);
                        $net = $mis($m, ['net_revenue']);
                        $netNum = is_numeric(str_replace([',','₹',' '], '', (string) $net))
                            ? (float) str_replace([',','₹',' '], '', (string) $net)
                            : null;
                    @endphp
                    <tr>
                        <td class="sticky-col" data-label="Ref">
                            <a class="pd-case" href="{{ route('goal.cases.show', ['case' => $case, 'tab' => 'mis']) }}">{{ $case->case_no }}</a>
                        </td>
                        <td>{{ $mis($m, ['lan_number', 'lan', 'lan_no']) }}</td>
                        <td><span class="pd-cust">{{ $case->customer_name ?: '—' }}</span></td>
                        <td>{{ $case->mobile ?: '—' }}</td>
                        <td>{{ $mis($m, ['month']) }}</td>
                        <td>{{ $fmtDate($mis($m, ['login_date'])) }}</td>
                        <td class="amt">{{ $fmtInr($mis($m, ['login_amount', 'login_amt'])) }}</td>
                        <td>{{ $case->sanction_date ? $case->sanction_date->format('d-M-Y') : $fmtDate($mis($m, ['sanction_dt', 'sanction_date'])) }}</td>
                        <td class="amt">
                            {{ $case->sanction_amount !== null ? $fmtInr($case->sanction_amount) : $fmtInr($mis($m, ['sanc_amt', 'sanction_amount'])) }}
                        </td>
                        <td>{{ $fmtDate($mis($m, ['disb_date'])) }}</td>
                        <td class="amt">{{ $fmtInr($mis($m, ['disb_amount', 'disb_amt'])) }}</td>
                        <td>{{ $mis($m, ['code']) }}</td>
                        <td>{{ $case->product ?: '—' }}</td>
                        <td>{{ $mis($m, ['sub_product']) }}</td>
                        <td><span class="pd-pill {{ $statusPill($case->status) }}">{{ $case->status }}</span></td>
                        <td>
                            @if($otc !== '—')
                                <span class="pd-pill {{ $otcPill($otc) }}">{{ $otc }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $case->orgUnit?->name ?: $mis($m, ['location']) }}</td>
                        <td>{{ $mis($m, ['source']) }}</td>
                        <td>{{ $mis($m, ['source_name']) }}</td>
                        <td>{{ $mis($m, ['gsm_name']) !== '—' ? $mis($m, ['gsm_name']) : ($case->owner?->name ?: '—') }}</td>
                        <td>{{ $mis($m, ['bank_name']) !== '—' ? $mis($m, ['bank_name']) : ($case->lender_name ?: '—') }}</td>
                        <td>{{ $mis($m, ['banker_name']) }}</td>
                        <td>{{ $mis($m, ['banker_contact']) }}</td>
                        <td>{{ $mis($m, ['banker_mail', 'banker_email']) }}</td>
                        <td>{{ $mis($m, ['payin_pct', 'pay_in_pct']) }}</td>
                        <td class="amt">{{ $fmtInr($mis($m, ['payin_amt', 'pay_in_amt'])) }}</td>
                        <td>{{ $mis($m, ['payout_pct']) }}</td>
                        <td class="amt">{{ $fmtInr($mis($m, ['payout_amount', 'payout_amt'])) }}</td>
                        <td class="amt {{ ($netNum !== null && $netNum > 0) ? 'net-pos' : '' }}">{{ $fmtInr($net) }}</td>
                        <td>{{ $mis($m, ['sm_remark']) }}</td>
                        <td>{{ $mis($m, ['payout_month']) }}</td>
                        <td>{{ $mis($m, ['ref1_name']) }}</td>
                        <td>{{ $mis($m, ['ref1_contact']) }}</td>
                        <td>{{ $mis($m, ['ref2_name']) }}</td>
                        <td>{{ $mis($m, ['ref2_contact']) }}</td>
                        <td>{{ $mis($m, ['dob_applicant']) }}</td>
                        <td>{{ $mis($m, ['co_applicant']) }}</td>
                        <td>
                            <a class="mis-ws-btn" href="{{ route('goal.cases.show', ['case' => $case, 'tab' => 'mis']) }}">
                                <i class="bi bi-box-arrow-up-right"></i> Workspace
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="38" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-table"></i>
                                No case MIS rows for this filter. Open a case → Branch MIS to capture commercials.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($cases->hasPages())
            <div class="pd-foot">
                <span>Showing {{ $cases->firstItem() }}–{{ $cases->lastItem() }} of {{ $cases->total() }} · 37 data columns</span>
                <div>{{ $cases->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
