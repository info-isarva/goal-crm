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
.pd-page.masters-page .bank-code{
    display:inline-flex;align-items:center;padding:.15rem .55rem;border-radius:999px;
    background:#eff6ff;color:#1d4ed8;font-size:.75rem;font-weight:700;letter-spacing:.02em;
}
.pd-page.masters-page .bank-status{
    display:inline-flex;align-items:center;padding:.15rem .55rem;border-radius:999px;
    font-size:.72rem;font-weight:700;
}
.pd-page.masters-page .bank-status.is-on{background:#ecfdf5;color:#047857}
.pd-page.masters-page .bank-status.is-off{background:#f1f5f9;color:#64748b}
.pd-page.masters-page .bank-actions{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.pd-page.masters-page .bank-actions .pd-btn-ghost{
    min-width:0;height:32px;padding:0 10px;font-size:.78rem;
}
@media (max-width:991.98px){
    .pd-page.masters-page .masters-filters{grid-template-columns:1fr 1fr}
    .pd-page.masters-page .masters-filter-actions{grid-column:1 / -1}
}
@media (max-width:767.98px){
    .pd-page.masters-page .masters-filters{grid-template-columns:1fr}
    .pd-page.masters-page .bank-actions{justify-content:stretch}
    .pd-page.masters-page .bank-actions .pd-btn-ghost{width:100%;justify-content:center}
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
                <li>Banks</li>
            </ul>
            <h1 class="pd-title">Banks</h1>
            <p class="pd-sub">Bank name and code master used on BM Recommendation (Suggested Bank).</p>
        </div>
        <div class="pd-hero-actions">
            <button type="button" class="pd-link-btn primary" data-bs-toggle="modal" data-bs-target="#bankFormModal" data-bank-mode="add">
                <i class="bi bi-plus-lg"></i> Add bank
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

    <form method="GET" action="{{ route('goal.masters.banks.index') }}" class="masters-filters">
        <div>
            <label for="bank-q">Search</label>
            <input id="bank-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Bank name or code">
        </div>
        <div>
            <label for="bank-active">Status</label>
            <select id="bank-active" name="active" class="form-select">
                <option value="">All</option>
                <option value="1" @selected($activeFilter === '1')>Active</option>
                <option value="0" @selected($activeFilter === '0')>Inactive</option>
            </select>
        </div>
        <div class="masters-filter-actions">
            <button type="submit" class="pd-btn"><i class="bi bi-funnel"></i> Filter</button>
            <a href="{{ route('goal.masters.banks.index') }}" class="pd-btn-ghost">Reset</a>
        </div>
    </form>

    <div class="pd-card">
        <div class="pd-card-head">
            <h5>Bank master</h5>
            <span class="pd-badge" style="background:#eff6ff;color:#1d4ed8">{{ $banks->total() }} total</span>
        </div>
        <div class="pd-table-wrap">
            <table class="pd-table align-middle">
                <thead>
                    <tr>
                        <th style="width:56px">#</th>
                        <th>Bank Name</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th style="width:80px">Sort</th>
                        <th style="width:100px">Status</th>
                        <th class="text-end" style="width:110px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($banks as $b)
                    <tr>
                        <td data-label="#">{{ $banks->firstItem() + $loop->index }}</td>
                        <td data-label="Bank Name">
                            <strong>{{ $b->name }}</strong>
                        </td>
                        <td data-label="Code">
                            <span class="bank-code">{{ $b->code }}</span>
                        </td>
                        <td data-label="Description">{{ $b->description ?: '—' }}</td>
                        <td data-label="Sort">{{ $b->sort_order }}</td>
                        <td data-label="Status">
                            <span class="bank-status {{ $b->is_active ? 'is-on' : 'is-off' }}">
                                {{ $b->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="pd-col-actions text-end" data-label="Actions">
                            <div class="bank-actions">
                                <button
                                    type="button"
                                    class="pd-btn-ghost js-bank-edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#bankFormModal"
                                    data-bank-mode="edit"
                                    data-id="{{ $b->id }}"
                                    data-name="{{ $b->name }}"
                                    data-code="{{ $b->code }}"
                                    data-description="{{ $b->description }}"
                                    data-sort-order="{{ $b->sort_order }}"
                                    data-is-active="{{ $b->is_active ? '1' : '0' }}"
                                    data-update-url="{{ route('goal.masters.banks.update', $b) }}"
                                    title="Edit"
                                >
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="pd-col-empty">
                            <div class="pd-empty">
                                <i class="bi bi-bank"></i>
                                No banks found.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pd-foot">
            <span>Used on BM Recommendation · Suggested Bank</span>
            <span>{{ $banks->links() }}</span>
        </div>
    </div>
</div>

{{-- Shared Add / Edit popup --}}
<div class="modal fade" id="bankFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('goal.masters.banks.store') }}" class="modal-content" id="bankForm">
            @csrf
            <input type="hidden" name="_method" id="bank_form_method" value="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="bankFormTitle">Add bank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="bank_form_name">Bank name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="bank_form_name" class="form-control" required maxlength="150" placeholder="e.g. HDFC Bank">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="bank_form_code">Bank code <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="bank_form_code" class="form-control" required maxlength="40" placeholder="e.g. HDFC">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="bank_form_desc">Description</label>
                    <input type="text" name="description" id="bank_form_desc" class="form-control" maxlength="255" placeholder="Optional">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="bank_form_sort">Sort order</label>
                    <input type="number" name="sort_order" id="bank_form_sort" class="form-control" min="0" max="9999" placeholder="Optional">
                </div>
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="bank_form_active" checked>
                    <label class="form-check-label" for="bank_form_active">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm" id="bankFormSubmit">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('bankFormModal');
    var form = document.getElementById('bankForm');
    if (!modal || !form) return;

    var storeUrl = @json(route('goal.masters.banks.store'));
    var titleEl = document.getElementById('bankFormTitle');
    var methodEl = document.getElementById('bank_form_method');
    var submitEl = document.getElementById('bankFormSubmit');
    var nameEl = document.getElementById('bank_form_name');
    var codeEl = document.getElementById('bank_form_code');
    var descEl = document.getElementById('bank_form_desc');
    var sortEl = document.getElementById('bank_form_sort');
    var activeEl = document.getElementById('bank_form_active');

    function resetAdd() {
        form.setAttribute('action', storeUrl);
        methodEl.value = 'POST';
        titleEl.textContent = 'Add bank';
        submitEl.textContent = 'Save';
        form.reset();
        if (nameEl) nameEl.value = '';
        if (codeEl) codeEl.value = '';
        if (descEl) descEl.value = '';
        if (sortEl) sortEl.value = '';
        if (activeEl) activeEl.checked = true;
    }

    function fillEdit(btn) {
        form.setAttribute('action', btn.getAttribute('data-update-url') || storeUrl);
        methodEl.value = 'PUT';
        titleEl.textContent = 'Edit bank';
        submitEl.textContent = 'Update';
        if (nameEl) nameEl.value = btn.getAttribute('data-name') || '';
        if (codeEl) codeEl.value = btn.getAttribute('data-code') || '';
        if (descEl) descEl.value = btn.getAttribute('data-description') || '';
        if (sortEl) sortEl.value = btn.getAttribute('data-sort-order') || '';
        if (activeEl) activeEl.checked = btn.getAttribute('data-is-active') === '1';
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) {
            resetAdd();
            return;
        }
        var mode = trigger.getAttribute('data-bank-mode') || 'add';
        if (mode === 'edit') {
            fillEdit(trigger);
        } else {
            resetAdd();
        }
    });

    modal.addEventListener('hidden.bs.modal', function () {
        resetAdd();
    });
})();
</script>
@endpush
