@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.org-page .org-filters{
    display:grid;grid-template-columns:minmax(160px,1.6fr) repeat(2,minmax(120px,1fr)) auto;
    gap:10px;align-items:end;margin-bottom:.85rem;padding:.85rem 1rem;
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
    box-shadow:0 6px 16px rgba(148,163,184,.08);
}
.pd-page.org-page .org-filters label{
    display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 4px;
}
.pd-page.org-page .org-filters .form-control,
.pd-page.org-page .org-filters .form-select{
    min-height:38px;height:38px;border-radius:9px;border:1px solid #d1d5db;font-size:.84rem;background:#fff;
}
.pd-page.org-page .org-filter-actions{display:flex;gap:8px;flex-wrap:wrap}
.pd-page.org-page .org-name{
    display:flex;align-items:center;gap:.45rem;font-weight:700;color:#0f172a;
}
.pd-page.org-page .org-indent{
    display:inline-block;width:calc(var(--depth,0) * 1.1rem);flex:0 0 auto;
}
.pd-page.org-page .org-code{
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
    font-size:.78rem;color:#475569;background:#f1f5f9;padding:.15rem .4rem;border-radius:6px;
}
.pd-page.org-page .org-path{font-size:.72rem;color:#94a3b8;margin-top:2px}
@media (max-width:991.98px){
    .pd-page.org-page .org-filters{grid-template-columns:1fr 1fr}
    .pd-page.org-page .org-filter-actions{grid-column:1 / -1}
}
@media (max-width:767.98px){
    .pd-page.org-page .org-filters{grid-template-columns:1fr;padding:.75rem}
    .pd-page.org-page .org-indent{display:none}
}
</style>
@endpush

@section('content')
@php
    $typePill = fn (?string $t) => match ((string) $t) {
        'region' => 'info',
        'cluster' => 'warn',
        'branch' => 'ok',
        default => 'slate',
    };
    $activeFilter = $filters['active'] ?? '';
@endphp
<div class="container-fluid p-4 pd-page org-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Admin</li>
                <li class="sep">›</li>
                <li>Org Hierarchy</li>
            </ul>
            <h1 class="pd-title">Org Hierarchy</h1>
            <p class="pd-sub">Region → Cluster → Branch structure used for allocation, MIS, and targets.</p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.org-units.export', request()->query()) }}" class="pd-link-btn">
                <i class="bi bi-download"></i> Export CSV
            </a>
            <a href="{{ route('goal.org-units.create') }}" class="pd-link-btn primary">
                <i class="bi bi-plus-lg"></i> Add unit
            </a>
        </div>
    </div>

    <div class="pd-kpi-row">
        <div class="pd-kpi tone-slate">
            <p class="pd-kpi-label">Total units</p>
            <p class="pd-kpi-value">{{ number_format($kpis['total'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-blue">
            <p class="pd-kpi-label">Regions</p>
            <p class="pd-kpi-value">{{ number_format($kpis['regions'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-amber">
            <p class="pd-kpi-label">Clusters</p>
            <p class="pd-kpi-value">{{ number_format($kpis['clusters'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-green">
            <p class="pd-kpi-label">Branches</p>
            <p class="pd-kpi-value">{{ number_format($kpis['branches'] ?? 0) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('goal.org-units.index') }}" class="org-filters">
        <div>
            <label for="org-q">Search</label>
            <input id="org-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name, code, or parent">
        </div>
        <div>
            <label for="org-type">Type</label>
            <select id="org-type" name="type" class="form-select">
                <option value="">All types</option>
                @foreach(['region','cluster','branch'] as $t)
                    <option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="org-active">Status</label>
            <select id="org-active" name="active" class="form-select">
                <option value="">All</option>
                <option value="1" @selected($activeFilter === '1' || $activeFilter === 'yes')>Active</option>
                <option value="0" @selected($activeFilter === '0' || $activeFilter === 'no')>Inactive</option>
            </select>
        </div>
        <div class="org-filter-actions">
            <button type="submit" class="pd-btn"><i class="bi bi-funnel"></i> Filter</button>
            <a href="{{ route('goal.org-units.index') }}" class="pd-btn-ghost">Reset</a>
        </div>
    </form>

    <div class="pd-card">
        <div class="pd-card-head">
            <h5>Units</h5>
            <span class="pd-badge" style="background:#eff6ff;color:#1d4ed8">{{ $units->count() }} shown · {{ $kpis['active'] ?? 0 }} active</span>
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Parent</th>
                        <th>Children</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($units as $unit)
                    @php $depth = (int) ($unit->tree_depth ?? 0); @endphp
                    <tr>
                        <td data-label="Name">
                            <div class="org-name">
                                <span class="org-indent" style="--depth:{{ $depth }}"></span>
                                <span>{{ $unit->name }}</span>
                            </div>
                            @if($unit->parent)
                                <div class="org-path">Under {{ $unit->parent->name }}</div>
                            @endif
                        </td>
                        <td data-label="Code">
                            @if($unit->code)
                                <span class="org-code">{{ $unit->code }}</span>
                            @else
                                <span class="pd-muted">—</span>
                            @endif
                        </td>
                        <td data-label="Type">
                            <span class="pd-pill {{ $typePill($unit->type) }}">{{ ucfirst($unit->type) }}</span>
                        </td>
                        <td data-label="Parent">{{ $unit->parent?->name ?? '—' }}</td>
                        <td data-label="Children">{{ number_format($unit->children_count ?? $unit->children->count()) }}</td>
                        <td data-label="Status">
                            <span class="pd-pill {{ $unit->is_active ? 'ok' : 'slate' }}">
                                {{ $unit->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="pd-col-actions" data-label="Actions">
                            <a href="{{ route('goal.org-units.edit', $unit) }}" class="pd-btn-ghost" title="Edit unit">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-diagram-2"></i>
                                No org units match these filters.
                                <div class="mt-2">
                                    <a href="{{ route('goal.org-units.create') }}" class="pd-link-btn primary">Add first unit</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pd-foot">
            <span>Tree order: Region → Cluster → Branch</span>
            <span>{{ $units->count() }} row{{ $units->count() === 1 ? '' : 's' }}</span>
        </div>
    </div>
</div>
@endsection
