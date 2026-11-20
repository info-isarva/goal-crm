@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.masters-page .masters-filters{
    display:grid;grid-template-columns:minmax(160px,1.6fr) minmax(120px,1fr) auto;
    gap:10px;align-items:end;margin-bottom:.85rem;padding:.85rem 1rem;
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
    box-shadow:0 6px 16px rgba(148,163,184,.08);
}
.pd-page.masters-page .masters-filters label{
    display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 4px;
}
.pd-page.masters-page .masters-filters .form-control,
.pd-page.masters-page .masters-filters .form-select{
    min-height:38px;height:38px;border-radius:9px;border:1px solid #d1d5db;font-size:.84rem;background:#fff;
}
.pd-page.masters-page .masters-filter-actions{display:flex;gap:8px;flex-wrap:wrap}
.pd-page.masters-page .edit-form{display:grid;grid-template-columns:1.2fr 1.6fr .7fr auto;gap:8px;align-items:center}
.pd-page.masters-page .edit-form .form-control,
.pd-page.masters-page .edit-form .form-select{min-height:34px;height:34px;font-size:.82rem;border-radius:8px}
.pd-page.masters-page .check-inline{display:flex;align-items:center;gap:6px;font-size:.78rem;color:#475569;margin:0}
@media (max-width:991.98px){
    .pd-page.masters-page .masters-filters{grid-template-columns:1fr 1fr}
    .pd-page.masters-page .masters-filter-actions{grid-column:1 / -1}
    .pd-page.masters-page .edit-form{grid-template-columns:1fr 1fr}
}
@media (max-width:767.98px){
    .pd-page.masters-page .masters-filters,
    .pd-page.masters-page .edit-form{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
@php $trashedFilter = $filters['trashed'] ?? ''; @endphp
<div class="container-fluid p-4 pd-page masters-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Masters</li>
                <li class="sep">›</li>
                <li>Sources</li>
            </ul>
            <h1 class="pd-title">Sources</h1>
            <p class="pd-sub">Lead source options used on Create Lead, filters and VRM-created leads.</p>
        </div>
        <div class="pd-hero-actions">
            <button type="button" class="pd-link-btn primary" data-bs-toggle="modal" data-bs-target="#addSourceModal">
                <i class="bi bi-plus-lg"></i> Add source
            </button>
        </div>
    </div>

    <div class="pd-kpi-row">
        <div class="pd-kpi tone-slate">
            <p class="pd-kpi-label">Total</p>
            <p class="pd-kpi-value">{{ number_format($kpis['total'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-green">
            <p class="pd-kpi-label">Active</p>
            <p class="pd-kpi-value">{{ number_format($kpis['active'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-blue">
            <p class="pd-kpi-label">Inactive</p>
            <p class="pd-kpi-value">{{ number_format($kpis['inactive'] ?? 0) }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('goal.masters.sources.index') }}" class="masters-filters">
        <div>
            <label for="src-q">Search</label>
            <input id="src-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Source name">
        </div>
        <div>
            <label for="src-trashed">Status</label>
            <select id="src-trashed" name="trashed" class="form-select">
                <option value="0" @selected($trashedFilter !== '1')>Active</option>
                <option value="1" @selected($trashedFilter === '1')>Inactive</option>
            </select>
        </div>
        <div class="masters-filter-actions">
            <button type="submit" class="pd-btn"><i class="bi bi-funnel"></i> Filter</button>
            <a href="{{ route('goal.masters.sources.index') }}" class="pd-btn-ghost">Reset</a>
        </div>
    </form>

    <div class="pd-card">
        <div class="pd-card-head">
            <h5>Source master</h5>
            <span class="pd-badge" style="background:#eff6ff;color:#1d4ed8">{{ $sources->total() }} total</span>
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($sources as $s)
                    <tr>
                        <td data-label="Name" colspan="4" class="p-2">
                            @if($s->trashed())
                                <div class="edit-form">
                                    <div class="fw-semibold">{{ $s->name }}</div>
                                    <div class="text-muted small">{{ $s->description ?: '—' }}</div>
                                    <span class="badge bg-secondary">Inactive</span>
                                    <form method="POST" action="{{ route('goal.masters.sources.restore', $s->id) }}">
                                        @csrf
                                        <button type="submit" class="pd-btn-ghost" title="Restore">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restore
                                        </button>
                                    </form>
                                </div>
                            @else
                                <form method="POST" action="{{ route('goal.masters.sources.update', $s) }}" class="edit-form">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" class="form-control" value="{{ old('name_'.$s->id, $s->name) }}" required maxlength="100">
                                    <input type="text" name="description" class="form-control" value="{{ old('description_'.$s->id, $s->description) }}" maxlength="255" placeholder="Optional description">
                                    <label class="check-inline">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active_'.$s->id, true))>
                                        Active
                                    </label>
                                    <button type="submit" class="pd-btn-ghost" title="Save">
                                        <i class="bi bi-check-lg"></i> Save
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-signpost-2"></i>
                                No sources found.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pd-foot">
            <span>Used on Create Lead · Loan Leads filters</span>
            <span>{{ $sources->links() }}</span>
        </div>
    </div>
</div>

<div class="modal fade" id="addSourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('goal.masters.sources.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add source</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="add_src_name">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="add_src_name" class="form-control" required maxlength="100" placeholder="e.g. Digital Ads">
                </div>
                <div>
                    <label class="form-label" for="add_src_desc">Description</label>
                    <input type="text" name="description" id="add_src_desc" class="form-control" maxlength="255" placeholder="Optional">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
