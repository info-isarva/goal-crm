@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.mis-page .pd-hero{align-items:center}
.pd-page.mis-page .pd-kpi-meta{margin:4px 0 0;font-size:.72rem;color:#94a3b8;line-height:1.3}

.pd-page.mis-page .mis-guide{
    display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:.85rem;
}
@media (max-width:991.98px){.pd-page.mis-page .mis-guide{grid-template-columns:1fr}}
.pd-page.mis-page .mis-guide-card{
    background:#fff;border:1px solid #e8edf5;border-radius:14px;padding:14px 16px;
    box-shadow:0 6px 16px rgba(148,163,184,.08);
}
.pd-page.mis-page .mis-guide-card .tag{
    display:inline-flex;align-items:center;gap:6px;font-size:.72rem;font-weight:800;
    letter-spacing:.04em;text-transform:uppercase;padding:4px 10px;border-radius:999px;margin-bottom:8px;
}
.pd-page.mis-page .mis-guide-card.is-what .tag{background:var(--theme-100,#f0fdfa);color:var(--theme-primary-hover,#0f766e)}
.pd-page.mis-page .mis-guide-card.is-vs .tag{background:#eff6ff;color:#1d4ed8}
.pd-page.mis-page .mis-guide-card.is-edit .tag{background:#fff7ed;color:#c2410c}
.pd-page.mis-page .mis-guide-card h6{margin:0 0 6px;font-size:.95rem;font-weight:800;color:#0f172a}
.pd-page.mis-page .mis-guide-card p{margin:0;font-size:.82rem;color:#64748b;line-height:1.45}

.pd-page.mis-page .mis-filters{
    display:grid;
    grid-template-columns:repeat(3,minmax(110px,1fr)) repeat(3,minmax(110px,1fr)) minmax(160px,1.3fr) auto;
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
.pd-page.mis-page .mis-filter-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
@media (max-width:1199.98px){.pd-page.mis-page .mis-filters{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:767.98px){
    .pd-page.mis-page .mis-filters{grid-template-columns:1fr 1fr}
    .pd-page.mis-page .mis-filter-actions{grid-column:1 / -1}
}

.pd-page.mis-page .amt{font-variant-numeric:tabular-nums;font-weight:700;color:#0f172a;white-space:nowrap}
.pd-page.mis-page .net-pos{color:#15803d;font-weight:800}
.pd-page.mis-page .mis-case-cell .pd-cust{margin-top:2px}
.pd-page.mis-page .mis-sub{font-size:.75rem;color:#94a3b8;margin-top:2px}
.pd-page.mis-page .mis-actions{display:inline-flex;gap:6px;flex-wrap:wrap}
.pd-page.mis-page .mis-btn{
    display:inline-flex;align-items:center;gap:5px;height:32px;padding:0 10px;
    border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;
    font-size:.75rem;font-weight:650;text-decoration:none;white-space:nowrap;
}
.pd-page.mis-page .mis-btn:hover{background:#f8fafc;color:#0f172a}
.pd-page.mis-page .mis-btn.primary{
    border-color:var(--theme-primary);background:var(--theme-100,#f0fdfa);color:var(--theme-primary-hover,#0f766e);
}
.pd-page.mis-page .mis-btn.primary:hover{filter:brightness(.98)}
.pd-page.mis-page .pd-card-sub{font-size:.78rem;color:#94a3b8;font-weight:600}
.pd-page.mis-page .pd-table thead th{white-space:nowrap}
.pd-page.mis-page .pd-table tbody td{vertical-align:middle}
.pd-page.mis-page .pd-hero-actions .dropdown > .pd-link-btn{
    border:0;cursor:pointer;appearance:none;-webkit-appearance:none;
}
.pd-page.mis-page .pd-hero-actions .dropdown-menu{
    min-width:168px;border:1px solid #e2e8f0;border-radius:12px;padding:6px;
    box-shadow:0 12px 28px rgba(15,23,42,.12);
}
.pd-page.mis-page .pd-hero-actions .dropdown-item{
    border-radius:8px;font-size:.84rem;font-weight:650;padding:8px 10px;display:flex;align-items:center;gap:8px;
}
.pd-page.mis-page .pd-hero-actions .dropdown-item i{font-size:1rem}
.pd-page.mis-page .pd-hero-actions .dropdown-item .text-csv{color:#2563eb}
.pd-page.mis-page .pd-hero-actions .dropdown-item .text-xlsx{color:#15803d}
.pd-page.mis-page .pd-hero-actions .dropdown-item .text-pdf{color:#b91c1c}
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
        try { return \Illuminate\Support\Carbon::parse($v)->format('d M Y'); }
        catch (\Throwable $e) { return $v; }
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
                <li>Performance & Finance</li>
                <li class="sep">›</li>
                <li>Branch MIS</li>
            </ul>
            <h1 class="pd-title">Branch MIS</h1>
            <p class="pd-sub">Branch commercial sheet per case — disbursement, payin, payout and net revenue.</p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.revenue-ledger') }}" class="pd-link-btn"><i class="bi bi-journal-text"></i> Revenue Ledger</a>
            <a href="{{ route('goal.targets.index') }}" class="pd-link-btn"><i class="bi bi-bullseye"></i> Targets</a>
            <div class="dropdown">
                <button class="pd-link-btn primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('goal.branch-mis.export', array_merge(request()->query(), ['format' => 'csv'])) }}">
                            <i class="bi bi-filetype-csv text-csv"></i> CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('goal.branch-mis.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}">
                            <i class="bi bi-file-earmark-excel text-xlsx"></i> Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('goal.branch-mis.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
                            <i class="bi bi-file-earmark-pdf text-pdf"></i> PDF
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mis-guide">
        <div class="mis-guide-card is-what">
            <div class="tag"><i class="bi bi-clipboard-data"></i> What this is</div>
            <h6>Case commercial sheet</h6>
            <p>One row per loan case with bank, disbursement and margin fields used for branch reporting.</p>
        </div>
        <div class="mis-guide-card is-vs">
            <div class="tag"><i class="bi bi-arrow-left-right"></i> vs Revenue Ledger</div>
            <h6>Net margin here</h6>
            <p><strong>Net revenue</strong> = payin − payout. Revenue Ledger tracks company REV-01 recognition separately.</p>
        </div>
        <div class="mis-guide-card is-edit">
            <div class="tag"><i class="bi bi-pencil-square"></i> How to update</div>
            <h6>Open detail → Edit</h6>
            <p>Use <strong>View detail</strong> to read the full sheet, or <strong>Edit</strong> to update fields in the case workspace MIS tab.</p>
        </div>
    </div>

    <div class="pd-kpi-row">
        <div class="pd-kpi tone-blue">
            <p class="pd-kpi-label">Cases in MIS</p>
            <p class="pd-kpi-value">{{ number_format($kpis['total'] ?? 0) }}</p>
            <p class="pd-kpi-meta">{{ number_format($kpis['disbursed'] ?? 0) }} disbursed · {{ number_format($kpis['sanctioned'] ?? 0) }} sanctioned</p>
        </div>
        <div class="pd-kpi tone-green">
            <p class="pd-kpi-label">Disbursed amt</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr($kpis['disb_amount'] ?? 0) }}</p>
            <p class="pd-kpi-meta">Verified / recorded disbursements</p>
        </div>
        <div class="pd-kpi tone-amber">
            <p class="pd-kpi-label">Payout amt</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr($kpis['payout_amount'] ?? 0) }}</p>
            <p class="pd-kpi-meta">Expected payout on files</p>
        </div>
        <div class="pd-kpi tone-green">
            <p class="pd-kpi-label">Net revenue</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr($kpis['net_revenue'] ?? 0) }}</p>
            <p class="pd-kpi-meta">Payin − payout (branch margin)</p>
        </div>
    </div>

    <form method="GET" action="{{ route('goal.branch-mis') }}" class="mis-filters">
        <div>
            <label for="misMonth">Month</label>
            <select id="misMonth" name="month" class="form-select" data-search="true" data-allow-clear="false">
                <option value="">All months</option>
                @foreach($months as $mo)
                    <option value="{{ $mo }}" @selected(($filters['month'] ?? '') === $mo)>{{ $mo }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misBranch">Location</label>
            <select id="misBranch" name="branch" class="form-select" data-search="true" data-allow-clear="false">
                <option value="">All locations</option>
                @foreach($branches as $br)
                    <option value="{{ $br->id }}" @selected((int) ($filters['branch'] ?? 0) === (int) $br->id)>{{ $br->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misStatus">Status</label>
            <select id="misStatus" name="status" class="form-select" data-search="false" data-allow-clear="false">
                <option value="">All status</option>
                @foreach($statuses as $st)
                    <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misBank">Bank</label>
            <select id="misBank" name="bank" class="form-select" data-search="true" data-allow-clear="false">
                <option value="">All banks</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank }}" @selected(($filters['bank'] ?? '') === $bank)>{{ $bank }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misOtc">OTC/PDD</label>
            <select id="misOtc" name="otc_pdd" class="form-select" data-search="false" data-allow-clear="false">
                <option value="">All</option>
                @foreach($otcOptions as $otc)
                    <option value="{{ $otc }}" @selected(($filters['otc_pdd'] ?? '') === $otc)>{{ $otc }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="misProduct">Product</label>
            <select id="misProduct" name="product" class="form-select" data-search="true" data-allow-clear="false">
                <option value="">All products</option>
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
                <h5>Branch cases ({{ number_format($cases->total()) }})</h5>
                <div class="pd-card-sub">Readable summary · open detail for full sheet · Export as CSV / Excel / PDF</div>
            </div>
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th>Case / customer</th>
                        <th>Month</th>
                        <th>Location</th>
                        <th>Bank</th>
                        <th>Status</th>
                        <th>OTC/PDD</th>
                        <th class="text-end">Disb amt</th>
                        <th class="text-end">Payin</th>
                        <th class="text-end">Payout</th>
                        <th class="text-end">Net revenue</th>
                        <th></th>
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
                        $lan = $mis($m, ['lan_number', 'lan', 'lan_no']);
                    @endphp
                    <tr>
                        <td data-label="Case" class="mis-case-cell">
                            <a class="pd-case" href="{{ route('goal.branch-mis.show', $case) }}">{{ $case->case_no }}</a>
                            <div class="pd-cust">{{ $case->customer_name ?: '—' }}</div>
                            <div class="mis-sub">
                                {{ $case->product ?: '—' }}
                                @if($lan !== '—') · LAN {{ $lan }}@endif
                            </div>
                        </td>
                        <td data-label="Month">{{ $mis($m, ['month']) }}</td>
                        <td data-label="Location">{{ $case->orgUnit?->name ?: $mis($m, ['location']) }}</td>
                        <td data-label="Bank">
                            {{ $mis($m, ['bank_name']) !== '—' ? $mis($m, ['bank_name']) : ($case->lender_name ?: '—') }}
                        </td>
                        <td data-label="Status"><span class="pd-pill {{ $statusPill($case->status) }}">{{ $case->status }}</span></td>
                        <td data-label="OTC/PDD">
                            @if($otc !== '—')
                                <span class="pd-pill {{ $otcPill($otc) }}">{{ $otc }}</span>
                            @else
                                <span class="mis-sub">—</span>
                            @endif
                        </td>
                        <td data-label="Disb" class="amt text-end">{{ $fmtInr($mis($m, ['disb_amount', 'disb_amt'])) }}</td>
                        <td data-label="Payin" class="amt text-end">{{ $fmtInr($mis($m, ['payin_amt', 'pay_in_amt'])) }}</td>
                        <td data-label="Payout" class="amt text-end">{{ $fmtInr($mis($m, ['payout_amount', 'payout_amt'])) }}</td>
                        <td data-label="Net" class="amt text-end {{ ($netNum !== null && $netNum > 0) ? 'net-pos' : '' }}">{{ $fmtInr($net) }}</td>
                        <td data-label="Actions">
                            <div class="mis-actions">
                                <a class="mis-btn primary" href="{{ route('goal.branch-mis.show', $case) }}">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a class="mis-btn" href="{{ route('goal.cases.show', ['case' => $case, 'tab' => 'mis']) }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-clipboard-data"></i>
                                <div style="font-weight:700;color:#334155;margin-bottom:4px">No Branch MIS rows for this filter</div>
                                <div>Open a loan case → Branch MIS tab to capture commercials, or clear filters.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($cases->hasPages())
            <div class="pd-foot">
                <span>Showing {{ $cases->firstItem() }}–{{ $cases->lastItem() }} of {{ $cases->total() }}</span>
                <div>{{ $cases->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
