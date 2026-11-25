@extends('layouts.app')

@push('styles')
<style>
.ci-split { --ink:#0f172a; --muted:#64748b; --line:#e8edf5; }
.ci-split .page-hero {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
    gap:1rem; margin-bottom:1.25rem;
}
.ci-split .ci-title { font-size:1.4rem; font-weight:800; color:var(--ink); margin:0; }
.ci-split .ci-sub { color:var(--muted); font-size:.88rem; margin-top:4px; }
.ci-split .ci-card {
    background:#fff; border:1px solid var(--line); border-radius:16px;
    box-shadow:0 8px 24px rgba(148,163,184,.12); overflow:hidden; margin-bottom:16px;
}
.ci-split .ci-card-head { padding:16px 18px; border-bottom:1px solid #f1f5f9; }
.ci-split .ci-card-head h2 { margin:0; font-size:1.05rem; font-weight:800; }
.ci-split .ci-card-body { padding:18px; }
.ci-split .mode-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin-bottom:16px; }
@media (max-width:768px) { .ci-split .mode-grid { grid-template-columns:1fr; } }
.ci-split .mode-opt {
    border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; cursor:pointer; background:#f8fafc;
}
.ci-split .mode-opt.active { border-color:#0284c7; background:#e0f2fe; }
.ci-split .mode-opt strong { display:block; font-size:.9rem; color:var(--ink); }
.ci-split .mode-opt span { font-size:.78rem; color:var(--muted); }
.ci-split .vrm-list { display:grid; gap:8px; max-height:320px; overflow:auto; }
.ci-split .vrm-row {
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    padding:10px 12px; border:1px solid #f1f5f9; border-radius:10px; background:#fff;
}
.ci-split .btn-apply {
    height:42px; padding:0 18px; border-radius:10px; font-weight:700; border:0; color:#fff;
    background:linear-gradient(90deg,#0284c7,#0369a1);
}
.ci-split .btn-back {
    height:38px; padding:0 14px; border-radius:10px; font-weight:700; font-size:.84rem;
    display:inline-flex; align-items:center; gap:.35rem; text-decoration:none;
    border:1px solid #e2e8f0; background:#fff; color:#334155;
}
.ci-split .panel { display:none; }
.ci-split .panel.active { display:block; }
</style>
@endpush

@section('content')
<div class="container-fluid p-4 ci-split">
    <div class="page-hero">
        <div>
            <div class="small text-muted mb-1">
                <a href="{{ route('goal.contacts.import.show', $import) }}">Batch #{{ $import->id }}</a> › Split
            </div>
            <h1 class="ci-title">Split contacts</h1>
            <div class="ci-sub">{{ $unassigned->count() }} unassigned contact(s) in this batch.</div>
        </div>
        <a href="{{ route('goal.contacts.import.show', $import) }}" class="btn-back"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($unassigned->isEmpty())
        <div class="alert alert-info">All contacts in this batch are already assigned.</div>
    @else
    <form method="POST" action="{{ route('goal.contacts.import.split.store', $import) }}" id="splitForm">
        @csrf
        <input type="hidden" name="mode" id="splitMode" value="even">

        <div class="ci-card">
            <div class="ci-card-head">
                <h2>Choose how to split</h2>
            </div>
            <div class="ci-card-body">
                <div class="mode-grid">
                    <label class="mode-opt active" data-mode="even">
                        <strong>Even split to VRMs</strong>
                        <span>Distribute unassigned contacts evenly across selected VRMs.</span>
                    </label>
                    <label class="mode-opt" data-mode="custom">
                        <strong>Custom counts</strong>
                        <span>Set how many contacts each VRM receives.</span>
                    </label>
                    @if($canPickBranch)
                    <label class="mode-opt" data-mode="branch">
                        <strong>Assign all to one branch</strong>
                        <span>Stamp branch only — leave unassigned for that BM/VRM pool.</span>
                    </label>
                    @endif
                    <label class="mode-opt" data-mode="vrm">
                        <strong>Assign all to one VRM</strong>
                        <span>Give the full unassigned pool to a single VRM.</span>
                    </label>
                </div>

                <div class="panel active" data-panel="even">
                    <div class="form-label fw-bold mb-2">Select VRMs</div>
                    <div class="vrm-list">
                        @forelse($vrms as $vrm)
                            <label class="vrm-row">
                                <span>
                                    <input type="checkbox" name="vrm_ids[]" value="{{ $vrm->id }}" class="form-check-input me-2">
                                    {{ $vrm->name }}
                                    <span class="small text-muted">· branch #{{ $vrm->goal_org_unit_id }}</span>
                                </span>
                            </label>
                        @empty
                            <div class="text-muted">No VRMs found in your org scope.</div>
                        @endforelse
                    </div>
                </div>

                <div class="panel" data-panel="custom">
                    <div class="form-label fw-bold mb-2">Counts per VRM (total ≤ {{ $unassigned->count() }})</div>
                    <div class="vrm-list">
                        @foreach($vrms as $vrm)
                            <div class="vrm-row">
                                <span>{{ $vrm->name }}</span>
                                <input type="number" min="0" max="{{ $unassigned->count() }}" name="counts[{{ $vrm->id }}]" class="form-control" style="width:100px;" value="0">
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($canPickBranch)
                <div class="panel" data-panel="branch">
                    <label class="form-label fw-bold">Branch</label>
                    <select name="branch_id" class="form-select" style="max-width:420px;">
                        <option value="">— Select branch —</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}{{ $b->code ? ' ('.$b->code.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="panel" data-panel="vrm">
                    <label class="form-label fw-bold">VRM</label>
                    <select name="vrm_id" class="form-select" style="max-width:420px;">
                        <option value="">— Select VRM —</option>
                        @foreach($vrms as $vrm)
                            <option value="{{ $vrm->id }}">{{ $vrm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-apply"><i class="bi bi-check2-circle me-1"></i> Apply split</button>
                </div>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var opts = document.querySelectorAll('.mode-opt');
    var panels = document.querySelectorAll('.panel');
    var modeInput = document.getElementById('splitMode');
    if (!opts.length || !modeInput) return;

    opts.forEach(function (opt) {
        opt.addEventListener('click', function () {
            var mode = opt.getAttribute('data-mode');
            opts.forEach(function (o) { o.classList.remove('active'); });
            opt.classList.add('active');
            modeInput.value = mode;
            panels.forEach(function (p) {
                p.classList.toggle('active', p.getAttribute('data-panel') === mode);
            });
        });
    });
})();
</script>
@endpush
