@extends('layouts.app')

@push('styles')
<style>
.ci-show { --ink:#0f172a; --muted:#64748b; --line:#e8edf5; }
.ci-show .page-hero {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
    gap:1rem; margin-bottom:1.25rem;
}
.ci-show .ci-title { font-size:1.4rem; font-weight:800; color:var(--ink); margin:0; }
.ci-show .ci-sub { color:var(--muted); font-size:.88rem; margin-top:4px; }
.ci-show .kpi-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
@media (max-width:768px) { .ci-show .kpi-row { grid-template-columns:1fr 1fr; } }
.ci-show .kpi {
    background:#fff; border:1px solid var(--line); border-radius:14px; padding:14px 16px;
    box-shadow:0 6px 16px rgba(148,163,184,.1);
}
.ci-show .kpi .lbl { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
.ci-show .kpi .val { font-size:1.35rem; font-weight:800; color:var(--ink); margin-top:4px; }
.ci-show .ci-card {
    background:#fff; border:1px solid var(--line); border-radius:16px; overflow:hidden;
    box-shadow:0 8px 24px rgba(148,163,184,.12);
}
.ci-show .ci-table { width:100%; border-collapse:collapse; font-size:.86rem; }
.ci-show .ci-table th {
    text-align:left; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em;
    color:#64748b; padding:10px 12px; border-bottom:1px solid #e2e8f0; background:#f8fafc;
}
.ci-show .ci-table td { padding:11px 12px; border-bottom:1px solid #f1f5f9; color:#334155; }
.ci-show .badge-st {
    display:inline-flex; padding:.18rem .5rem; border-radius:999px; font-size:.72rem; font-weight:700;
}
.ci-show .badge-st.ok { background:#dcfce7; color:#166534; }
.ci-show .badge-st.error { background:#fee2e2; color:#b91c1c; }
.ci-show .badge-st.duplicate { background:#ffedd5; color:#c2410c; }
.ci-show .hero-actions { display:flex; flex-wrap:wrap; gap:8px; }
.ci-show .btn-back, .ci-show .btn-act {
    height:38px; padding:0 14px; border-radius:10px; font-weight:700; font-size:.84rem;
    display:inline-flex; align-items:center; gap:.35rem; text-decoration:none;
}
.ci-show .btn-back { border:1px solid #e2e8f0; background:#fff; color:#334155; }
.ci-show .btn-act.primary { background:#0284c7; color:#fff; border:0; }
.ci-show .btn-act.outline { border:1px solid #bae6fd; background:#fff; color:#0369a1; }
</style>
@endpush

@section('content')
<div class="container-fluid p-4 ci-show">
    <div class="page-hero">
        <div>
            <div class="small text-muted mb-1">
                <a href="{{ route('goal.contacts.import') }}">Import Contacts</a> › Batch #{{ $import->id }}
            </div>
            <h1 class="ci-title">{{ $import->filename }}</h1>
            <div class="ci-sub">
                By {{ $import->importer?->name ?? '—' }}
                · {{ $import->orgUnit?->name ?? 'No default org' }}
                · {{ $import->created_at?->format('d M Y, H:i') }}
                · Status <strong>{{ $import->status }}</strong>
            </div>
        </div>
        <div class="hero-actions">
            <a href="{{ route('goal.contacts.import') }}" class="btn-back"><i class="bi bi-arrow-left"></i> Back</a>
            @if($import->failed_count > 0)
                <a href="{{ route('goal.contacts.import.errors', $import) }}" class="btn-act outline">
                    <i class="bi bi-download"></i> Error report
                </a>
            @endif
            @if($canSplit && $unassignedCount > 0)
                <a href="{{ route('goal.contacts.import.split', $import) }}" class="btn-act primary">
                    <i class="bi bi-diagram-3"></i> Split contacts ({{ $unassignedCount }})
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="kpi-row">
        <div class="kpi"><div class="lbl">Total rows</div><div class="val">{{ $import->total_rows }}</div></div>
        <div class="kpi"><div class="lbl">Created</div><div class="val" style="color:#166534;">{{ $import->success_count }}</div></div>
        <div class="kpi"><div class="lbl">Failed / dup</div><div class="val" style="color:#b91c1c;">{{ $import->failed_count }}</div></div>
        <div class="kpi"><div class="lbl">Unassigned</div><div class="val">{{ $unassignedCount }}</div></div>
    </div>

    <div class="ci-card">
        <div class="table-responsive">
            <table class="ci-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Status</th>
                        <th>Name / Mobile</th>
                        <th>Lead</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $p = $row->payload ?? []; @endphp
                        <tr>
                            <td>{{ $row->row_number }}</td>
                            <td><span class="badge-st {{ $row->status }}">{{ $row->status }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $p['name'] ?? ($row->lead?->customer_name ?? '—') }}</div>
                                <div class="small text-muted">{{ $p['mobile'] ?? ($row->lead?->mobile ?? '') }}</div>
                            </td>
                            <td>
                                @if($row->lead)
                                    <a href="{{ route('goal.leads.show', $row->lead) }}">{{ $row->lead->ref_no }}</a>
                                    <div class="small text-muted">{{ $row->lead->status }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small">{{ $row->error_message ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No rows recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="p-3">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
@endsection
