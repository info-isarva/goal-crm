@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.rev-page .pd-hero{align-items:center}
.pd-page.rev-page .pd-kpi-row{grid-template-columns:repeat(4,minmax(0,1fr))}
@media (max-width:1199.98px){.pd-page.rev-page .pd-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:575.98px){.pd-page.rev-page .pd-kpi-row{grid-template-columns:1fr}}
.pd-page.rev-page .pd-kpi-meta{margin:4px 0 0;font-size:.72rem;color:#94a3b8;line-height:1.3}

.pd-page.rev-page .rev-guide{
    display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;
    margin-bottom:.85rem;
}
@media (max-width:991.98px){.pd-page.rev-page .rev-guide{grid-template-columns:1fr}}
.pd-page.rev-page .rev-guide-card{
    background:#fff;border:1px solid #e8edf5;border-radius:14px;padding:14px 16px;
    box-shadow:0 6px 16px rgba(148,163,184,.08);
}
.pd-page.rev-page .rev-guide-card .rev-code{
    display:inline-flex;align-items:center;gap:6px;
    font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;
    padding:4px 10px;border-radius:999px;margin-bottom:8px;
}
.pd-page.rev-page .rev-guide-card.is-rev01 .rev-code{background:var(--theme-100,#f0fdfa);color:var(--theme-primary-hover,#0f766e)}
.pd-page.rev-page .rev-guide-card.is-rev03 .rev-code{background:#fff7ed;color:#c2410c}
.pd-page.rev-page .rev-guide-card.is-flow .rev-code{background:#eff6ff;color:#1d4ed8}
.pd-page.rev-page .rev-guide-card h6{margin:0 0 6px;font-size:.95rem;font-weight:800;color:#0f172a}
.pd-page.rev-page .rev-guide-card p{margin:0;font-size:.82rem;color:#64748b;line-height:1.45}

.pd-page.rev-page .rev-filters{
    display:grid;grid-template-columns:minmax(200px,1.6fr) minmax(140px,1fr) auto;
    gap:10px;align-items:end;margin-bottom:.85rem;padding:.85rem 1rem;
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
    box-shadow:0 6px 16px rgba(148,163,184,.08);
}
.pd-page.rev-page .rev-filters label{
    display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 4px;
}
.pd-page.rev-page .rev-filters .form-control,
.pd-page.rev-page .rev-filters .form-select{
    min-height:38px;height:38px;border-radius:9px;border:1px solid #d1d5db;font-size:.84rem;background:#fff;
}
.pd-page.rev-page .rev-filter-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
@media (max-width:767.98px){.pd-page.rev-page .rev-filters{grid-template-columns:1fr}}

.pd-page.rev-page .rev-adj-form{
    display:grid;
    grid-template-columns:minmax(180px,1.5fr) minmax(120px,.8fr) minmax(120px,.8fr) minmax(160px,1.2fr) auto;
    gap:12px;align-items:end;padding:1rem 1.1rem;
}
@media (max-width:991.98px){
    .pd-page.rev-page .rev-adj-form{grid-template-columns:1fr 1fr}
    .pd-page.rev-page .rev-adj-form .rev-adj-actions{grid-column:1 / -1}
}
@media (max-width:575.98px){.pd-page.rev-page .rev-adj-form{grid-template-columns:1fr}}
.pd-page.rev-page .rev-adj-form label{
    display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 4px;
}
.pd-page.rev-page .rev-adj-form .form-control,
.pd-page.rev-page .rev-adj-form .form-select{
    min-height:38px;height:38px;border-radius:9px;font-size:.84rem;
}
.pd-page.rev-page .rev-adj-hint{
    margin:0;padding:0 1.1rem 1rem;font-size:.78rem;color:#64748b;line-height:1.4;
}
.pd-page.rev-page .rev-adj-locked{
    padding:1rem 1.1rem;font-size:.84rem;color:#64748b;line-height:1.45;
    display:flex;align-items:flex-start;gap:10px;
}
.pd-page.rev-page .rev-adj-locked i{color:#c2410c;font-size:1.1rem;margin-top:2px}

.pd-page.rev-page .rev-type{
    display:inline-flex;align-items:center;gap:5px;
    padding:4px 10px;border-radius:999px;font-size:.72rem;font-weight:700;line-height:1.2;
}
.pd-page.rev-page .rev-type.is-recognized{background:var(--theme-100,#f0fdfa);color:var(--theme-primary-hover,#0f766e)}
.pd-page.rev-page .rev-type.is-adjustment{background:#fff7ed;color:#c2410c}
.pd-page.rev-page .rev-type.is-reversal{background:#fef2f2;color:#b91c1c}
.pd-page.rev-page .pd-amt.is-neg{color:#b91c1c}
.pd-page.rev-page .rev-org{font-size:.75rem;color:#94a3b8;margin-top:2px}
.pd-page.rev-page .rev-note{max-width:280px;color:#475569;font-size:.84rem}
.pd-page.rev-page .rev-rollups{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;padding:1rem 1.1rem}
@media (max-width:991.98px){.pd-page.rev-page .rev-rollups{grid-template-columns:1fr}}
.pd-page.rev-page .rev-rollup-item{
    border:1px solid #eef2f7;border-radius:12px;padding:10px 12px;background:#f8fafc;
}
.pd-page.rev-page .rev-rollup-type{
    font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;margin:0;
}
.pd-page.rev-page .rev-rollup-name{font-size:.88rem;font-weight:700;color:#0f172a;margin:2px 0 4px}
.pd-page.rev-page .rev-rollup-amt{font-size:1rem;font-weight:800;color:#0f172a;font-variant-numeric:tabular-nums}
.pd-page.rev-page .pd-card-sub{font-size:.78rem;color:#94a3b8;font-weight:600}
.pd-page.rev-page .select2-container{width:100%!important}
.pd-page.rev-page .pd-hero-actions .dropdown > .pd-link-btn{
    border:0;cursor:pointer;appearance:none;-webkit-appearance:none;
}
.pd-page.rev-page .pd-hero-actions .dropdown-menu{
    min-width:168px;border:1px solid #e2e8f0;border-radius:12px;padding:6px;
    box-shadow:0 12px 28px rgba(15,23,42,.12);
}
.pd-page.rev-page .pd-hero-actions .dropdown-item{
    border-radius:8px;font-size:.84rem;font-weight:650;padding:8px 10px;display:flex;align-items:center;gap:8px;
}
.pd-page.rev-page .pd-hero-actions .dropdown-item i{font-size:1rem}
.pd-page.rev-page .pd-hero-actions .dropdown-item .text-csv{color:#2563eb}
.pd-page.rev-page .pd-hero-actions .dropdown-item .text-xlsx{color:#15803d}
.pd-page.rev-page .pd-hero-actions .dropdown-item .text-pdf{color:#b91c1c}
</style>
@endpush

@section('content')
@php
    $fmtInr = fn ($n) => $n !== null && $n !== '' ? '₹ '.number_format((float) $n, 2) : '—';
    $fmtInr0 = fn ($n) => $n !== null && $n !== '' ? '₹ '.number_format((float) $n, 0) : '—';
    $canAdjust = $canAdjust ?? false;
    $revenueRatePct = $revenueRatePct ?? round(((float) config('goal.revenue_rate', 0.0035)) * 100, 4);
    $typeLabel = function (?string $type): string {
        $t = strtolower(trim((string) $type));
        return match ($t) {
            'adjustment' => 'Adjustment',
            'reversal' => 'Reversal',
            default => 'Recognized',
        };
    };
    $typeClass = function (?string $type): string {
        $t = strtolower(trim((string) $type));
        return match ($t) {
            'adjustment' => 'is-adjustment',
            'reversal' => 'is-reversal',
            default => 'is-recognized',
        };
    };
@endphp
<div class="container-fluid p-4 pd-page rev-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Performance & Finance</li>
                <li class="sep">›</li>
                <li>Revenue Ledger</li>
            </ul>
            <h1 class="pd-title">Revenue Ledger</h1>
            <p class="pd-sub">
                Company revenue journal — automatic recognition at verified disbursement, plus controlled adjustments.
                @if(!empty($filters['fy_label']))
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">FY {{ $filters['fy_label'] }}</span>
                @endif
            </p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.targets.index') }}" class="pd-link-btn"><i class="bi bi-bullseye"></i> Targets</a>
            <a href="{{ route('goal.branch-mis') }}" class="pd-link-btn"><i class="bi bi-table"></i> Branch MIS</a>
            <div class="dropdown">
                <button class="pd-link-btn primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" data-no-loader href="{{ route('goal.revenue-ledger.export', array_merge(request()->query(), ['format' => 'csv'])) }}" download>
                            <i class="bi bi-filetype-csv text-csv"></i> CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" data-no-loader href="{{ route('goal.revenue-ledger.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" download>
                            <i class="bi bi-file-earmark-excel text-xlsx"></i> Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" data-no-loader href="{{ route('goal.revenue-ledger.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" download>
                            <i class="bi bi-file-earmark-pdf text-pdf"></i> PDF
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="rev-guide">
        <div class="rev-guide-card is-rev01">
            <div class="rev-code"><i class="bi bi-check2-circle"></i> REV-01</div>
            <h6>Auto recognition</h6>
            <p>Created when a disbursement is <strong>verified (PD-01)</strong>. Amount = disbursed × {{ rtrim(rtrim(number_format($revenueRatePct, 4), '0'), '.') }}%.</p>
        </div>
        <div class="rev-guide-card is-rev03">
            <div class="rev-code"><i class="bi bi-pencil-square"></i> REV-03</div>
            <h6>Adjustment / reversal</h6>
            <p>Manual correction posted here. Needs <strong>Revenue Ledger + Manage Targets</strong>. Updates target actuals.</p>
        </div>
        <div class="rev-guide-card is-flow">
            <div class="rev-code"><i class="bi bi-diagram-3"></i> How it fits</div>
            <h6>Ledger → Targets</h6>
            <p><strong>Ledger</strong> = system of record. <strong>Targets</strong> use these amounts as revenue actuals. Branch MIS shows net margin separately.</p>
        </div>
    </div>

    <div class="pd-kpi-row">
        <div class="pd-kpi tone-blue">
            <p class="pd-kpi-label">Entries</p>
            <p class="pd-kpi-value">{{ number_format($kpis['total'] ?? 0) }}</p>
            <p class="pd-kpi-meta">All ledger rows in your scope</p>
        </div>
        <div class="pd-kpi tone-green">
            <p class="pd-kpi-label">Recognized (REV-01)</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr0($kpis['recognized'] ?? 0) }}</p>
            <p class="pd-kpi-meta">{{ number_format($kpis['recognized_count'] ?? 0) }} auto credits</p>
        </div>
        <div class="pd-kpi tone-amber">
            <p class="pd-kpi-label">Adjustments (REV-03)</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr0($kpis['adjustments'] ?? 0) }}</p>
            <p class="pd-kpi-meta">{{ number_format($kpis['adjustment_count'] ?? 0) }} posts · reversals {{ $fmtInr0($kpis['reversals'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-slate">
            <p class="pd-kpi-label">Net ledger</p>
            <p class="pd-kpi-value" style="font-size:1.15rem">{{ $fmtInr0($kpis['amount'] ?? 0) }}</p>
            <p class="pd-kpi-meta">Recognized + adjustments + reversals</p>
        </div>
    </div>

    @if(!empty($rollups))
        <div class="pd-card" style="margin-bottom:.85rem">
            <div class="pd-card-head">
                <div>
                    <h5>Org rollups</h5>
                    <div class="pd-card-sub">Branch amounts rolled up to cluster and region</div>
                </div>
            </div>
            <div class="rev-rollups">
                @foreach($rollups as $r)
                    <div class="rev-rollup-item">
                        <p class="rev-rollup-type">{{ strtoupper($r['type']) }}</p>
                        <div class="rev-rollup-name">{{ $r['name'] }}</div>
                        <div class="rev-rollup-amt">{{ $fmtInr($r['amount']) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('goal.revenue-ledger') }}" class="rev-filters">
        <div>
            <label for="revQ">Search</label>
            <input id="revQ" type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Case no, customer, or note">
        </div>
        <div>
            <label for="revType">Entry type</label>
            <select id="revType" name="entry_type" class="form-select" data-search="false" data-allow-clear="false">
                <option value="">All types</option>
                <option value="recognized" @selected(($filters['entry_type'] ?? '') === 'recognized')>Recognized (REV-01)</option>
                <option value="adjustment" @selected(($filters['entry_type'] ?? '') === 'adjustment')>Adjustment</option>
                <option value="reversal" @selected(($filters['entry_type'] ?? '') === 'reversal')>Reversal</option>
            </select>
        </div>
        <div class="rev-filter-actions">
            <button type="submit" class="pd-btn" style="height:38px"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ route('goal.revenue-ledger') }}" class="pd-btn-ghost" style="height:38px">Reset</a>
        </div>
    </form>

    <div class="pd-card" style="margin-bottom:.85rem">
        <div class="pd-card-head">
            <div>
                <h5>Post REV-03 adjustment / reversal</h5>
                <div class="pd-card-sub">Use for corrections after recognition — not for new disbursements</div>
            </div>
        </div>
        @if($canAdjust)
            <form method="POST" action="{{ route('goal.revenue-ledger.adjust') }}" class="rev-adj-form" id="revAdjForm">
                @csrf
                <div>
                    <label for="adjCase">Case <span class="text-danger">*</span></label>
                    <select id="adjCase" name="goal_loan_case_id" class="form-select" required
                            data-search="true" data-placeholder="Select case…">
                        <option value="">Select case…</option>
                        @foreach(($cases ?? []) as $c)
                            <option value="{{ $c->id }}" @selected((string) old('goal_loan_case_id') === (string) $c->id)>
                                {{ $c->case_no }} · {{ $c->customer_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="adjAmt">Amount <span class="text-danger">*</span></label>
                    <input id="adjAmt" type="number" step="0.01" name="amount" class="form-control"
                           value="{{ old('amount') }}" required placeholder="0.00">
                </div>
                <div>
                    <label for="adjType">Type <span class="text-danger">*</span></label>
                    <select id="adjType" name="entry_type" class="form-select" required data-search="false" data-allow-clear="false">
                        <option value="adjustment" @selected(old('entry_type', 'adjustment') === 'adjustment')>Adjustment (+ / −)</option>
                        <option value="reversal" @selected(old('entry_type') === 'reversal')>Reversal (credit back)</option>
                    </select>
                </div>
                <div>
                    <label for="adjNote">Reason / note <span class="text-danger">*</span></label>
                    <input id="adjNote" type="text" name="note" class="form-control" value="{{ old('note') }}"
                           required maxlength="255" placeholder="Why is this being posted?">
                </div>
                <div class="rev-adj-actions">
                    <button type="submit" class="pd-btn" style="height:38px"><i class="bi bi-plus-lg"></i> Post entry</button>
                </div>
            </form>
            <p class="rev-adj-hint">
                <i class="bi bi-info-circle"></i>
                Reversals are stored as negative amounts. This posts to the ledger and refreshes revenue target actuals for the current period.
            </p>
            @error('amount')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror
            @error('goal_loan_case_id')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror
            @error('note')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror
            @error('entry_type')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror
        @else
            <div class="rev-adj-locked">
                <i class="bi bi-lock-fill"></i>
                <div>
                    <strong>Posting locked for your role.</strong>
                    You can view and export the ledger. REV-03 posting needs Manage Targets permission (separation of duties).
                </div>
            </div>
        @endif
    </div>

    <div class="pd-card">
        <div class="pd-card-head">
            <div>
                <h5>Ledger entries ({{ number_format($entries->total()) }})</h5>
                <div class="pd-card-sub">Newest first · click case to open workspace</div>
            </div>
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Case / customer</th>
                        <th>Type</th>
                        <th>Org unit</th>
                        <th class="text-end">Amount</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($entries as $e)
                    @php
                        $etype = $e->entry_type ?: 'recognized';
                        $amt = (float) $e->amount;
                    @endphp
                    <tr>
                        <td data-label="When">
                            <div>{{ optional($e->created_at)->format('d M Y') ?: '—' }}</div>
                            <div class="pd-muted">{{ optional($e->created_at)->format('H:i') ?: '' }}</div>
                        </td>
                        <td data-label="Case">
                            <a class="pd-case" href="{{ route('goal.cases.show', $e->goal_loan_case_id) }}">
                                {{ $e->loanCase?->case_no ?: '—' }}
                            </a>
                            <div class="pd-cust">{{ $e->loanCase?->customer_name ?: '—' }}</div>
                        </td>
                        <td data-label="Type">
                            <span class="rev-type {{ $typeClass($etype) }}">{{ $typeLabel($etype) }}</span>
                        </td>
                        <td data-label="Org">
                            {{ $e->orgUnit?->name ?: '—' }}
                            @if($e->orgUnit?->type)
                                <div class="rev-org">{{ strtoupper($e->orgUnit->type) }}</div>
                            @endif
                        </td>
                        <td data-label="Amount" class="pd-amt {{ $amt < 0 ? 'is-neg' : '' }}">{{ $fmtInr($amt) }}</td>
                        <td data-label="Note"><div class="rev-note">{{ $e->note ?: '—' }}</div></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-journal-text"></i>
                                <div style="font-weight:700;color:#334155;margin-bottom:4px">No revenue entries yet</div>
                                <div>When a disbursement is verified (PD-01), a REV-01 credit appears here automatically.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="pd-foot">
                <span>Showing {{ $entries->firstItem() }}–{{ $entries->lastItem() }} of {{ $entries->total() }}</span>
                <div>{{ $entries->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
