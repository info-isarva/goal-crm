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
.pd-page.masters-page .edit-form{display:grid;grid-template-columns:1.4fr .9fr .7fr .6fr auto;gap:8px;align-items:center}
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
@php $activeFilter = $filters['active'] ?? ''; @endphp
<div class="container-fluid p-4 pd-page masters-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li>Masters</li>
                <li class="sep">›</li>
                <li>Dispositions</li>
            </ul>
            <h1 class="pd-title">Dispositions</h1>
            <p class="pd-sub">Call dispositions used on VRM. Lead type is derived from each disposition when a lead is created.</p>
        </div>
        <div class="pd-hero-actions">
            <button type="button" class="pd-link-btn primary" data-bs-toggle="modal" data-bs-target="#addDispositionModal">
                <i class="bi bi-plus-lg"></i> Add disposition
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
            <p class="pd-kpi-label">Can create lead</p>
            <p class="pd-kpi-value">{{ number_format($kpis['creates_lead'] ?? 0) }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('goal.masters.dispositions.index') }}" class="masters-filters">
        <div>
            <label for="disp-q">Search</label>
            <input id="disp-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Disposition name">
        </div>
        <div>
            <label for="disp-active">Status</label>
            <select id="disp-active" name="active" class="form-select">
                <option value="">All</option>
                <option value="1" @selected($activeFilter === '1')>Active</option>
                <option value="0" @selected($activeFilter === '0')>Inactive</option>
            </select>
        </div>
        <div class="masters-filter-actions">
            <button type="submit" class="pd-btn"><i class="bi bi-funnel"></i> Filter</button>
            <a href="{{ route('goal.masters.dispositions.index') }}" class="pd-btn-ghost">Reset</a>
        </div>
    </form>

    <div class="pd-card">
        <div class="pd-card-head">
            <h5>Disposition master</h5>
            <span class="pd-badge" style="background:#eff6ff;color:#1d4ed8">{{ $dispositions->total() }} total</span>
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Creates lead</th>
                        <th>Lead type</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($dispositions as $d)
                    <tr>
                        <td data-label="Name" colspan="6" class="p-2">
                            <form method="POST" action="{{ route('goal.masters.dispositions.update', $d) }}" class="edit-form">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" class="form-control" value="{{ old('name_'.$d->id, $d->name) }}" required maxlength="100">
                                <label class="check-inline">
                                    <input type="checkbox" name="creates_lead" value="1" @checked(old('creates_lead_'.$d->id, $d->creates_lead))>
                                    Creates lead
                                </label>
                                <select name="maps_to_lead_type" class="form-select">
                                    <option value="">— Auto —</option>
                                    <option value="immediate" @selected(old('maps_to_lead_type_'.$d->id, $d->maps_to_lead_type) === 'immediate')>Immediate</option>
                                    <option value="future" @selected(old('maps_to_lead_type_'.$d->id, $d->maps_to_lead_type) === 'future')>Future</option>
                                </select>
                                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order_'.$d->id, $d->sort_order) }}" min="0" max="9999">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <label class="check-inline">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active_'.$d->id, $d->is_active))>
                                        Active
                                    </label>
                                    <button type="submit" class="pd-btn-ghost" title="Save">
                                        <i class="bi bi-check-lg"></i> Save
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-list-check"></i>
                                No dispositions found.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pd-foot">
            <span>Used on VRM Log Call · Lead Type auto-mapped</span>
            <span>{{ $dispositions->links() }}</span>
        </div>
    </div>
</div>

<div class="modal fade" id="addDispositionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('goal.masters.dispositions.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add disposition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="add_disp_name">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="add_disp_name" class="form-control" required maxlength="100" placeholder="e.g. Follow-up Later">
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="creates_lead" value="1" id="add_disp_creates_lead">
                    <label class="form-check-label" for="add_disp_creates_lead">Can create lead</label>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="add_disp_lead_type">Maps to Lead Type</label>
                    <select name="maps_to_lead_type" id="add_disp_lead_type" class="form-select">
                        <option value="">— Auto —</option>
                        <option value="immediate">Immediate</option>
                        <option value="future">Future</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="add_disp_sort">Sort order</label>
                    <input type="number" name="sort_order" id="add_disp_sort" class="form-control" min="0" max="9999" placeholder="Optional">
                </div>
                <input type="hidden" name="is_active" value="1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
