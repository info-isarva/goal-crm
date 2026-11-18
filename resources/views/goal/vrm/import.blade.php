@extends('layouts.app')

@push('styles')
<style>
.vrm-import {
    --vi-ink:#0f172a; --vi-muted:#64748b; --vi-line:#e8edf5;
    --vi-blue:var(--theme-primary); --vi-purple:#7c3aed; --vi-green:#16a34a;
}
.vrm-import .page-hero {
    display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
    gap:1rem; margin-bottom:1.25rem;
}
.vrm-import .page-hero-left { display:flex; align-items:center; gap:14px; min-width:0; }
.vrm-import .page-hero-icon {
    width:52px; height:52px; border-radius:14px; flex-shrink:0;
    background:linear-gradient(135deg,#8b5cf6 0%,#6366f1 100%);
    color:#fff; display:inline-flex; align-items:center; justify-content:center;
    font-size:1.35rem; box-shadow:0 10px 20px rgba(99,102,241,.28);
}
.vrm-import .vi-crumbs {
    display:flex; flex-wrap:wrap; align-items:center; gap:6px;
    margin:0 0 8px; padding:0; list-style:none; font-size:.78rem; color:#64748b;
}
.vrm-import .vi-crumbs a { color:var(--theme-primary); text-decoration:none; font-weight:600; }
.vrm-import .vi-crumbs a:hover { text-decoration:underline; }
.vrm-import .vi-crumbs .sep { opacity:.5; }
.vrm-import .vi-title { font-size:1.55rem; font-weight:800; color:var(--vi-ink); margin:0; line-height:1.2; }
.vrm-import .vi-sub { color:var(--vi-muted); font-size:.9rem; margin-top:4px; line-height:1.35; }
.vrm-import .vi-note {
    margin:0 0 1rem; padding:.7rem .9rem; border-radius:12px; font-size:.82rem; color:#475569; line-height:1.45;
    background:#f8fafc; border:1px dashed #cbd5e1;
}
@media (max-width:767.98px) {
    .vrm-import.container-fluid { padding:.75rem .7rem 1.1rem !important; }
    .vrm-import .vi-title { font-size:1.2rem; }
    .vrm-import .page-hero { flex-direction:column; align-items:stretch; }
    .vrm-import .btn-back { width:100%; justify-content:center; }
}
.vrm-import .btn-back {
    height:40px; padding:0 1rem; border-radius:10px; font-weight:700; font-size:.88rem;
    display:inline-flex; align-items:center; gap:.4rem;
    border:1px solid #e2e8f0; background:#fff; color:#334155;
    text-decoration:none; box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.vrm-import .btn-back:hover { background:#f8fafc; color:var(--vi-ink); border-color:#cbd5e1; }

.vrm-import .vi-grid {
    display:grid; grid-template-columns:minmax(0,1.15fr) minmax(0,.95fr); gap:16px; align-items:start;
}
@media (max-width:992px) {
    .vrm-import .vi-grid { grid-template-columns:1fr; }
}

.vrm-import .vi-card {
    background:#fff; border:1px solid var(--vi-line); border-radius:16px;
    box-shadow:0 8px 24px rgba(148,163,184,.12); overflow:hidden;
}
.vrm-import .vi-card-head {
    display:flex; align-items:center; gap:12px;
    padding:16px 18px; border-bottom:1px solid #f1f5f9;
}
.vrm-import .vi-card-ico {
    width:38px; height:38px; border-radius:12px; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center; font-size:1.05rem;
}
.vrm-import .vi-card-ico.upload { background:#ede9fe; color:var(--vi-purple); }
.vrm-import .vi-card-ico.guide { background:var(--theme-100); color:var(--vi-blue); }
.vrm-import .vi-card-head h2 {
    margin:0; font-size:1.05rem; font-weight:800; color:var(--vi-ink); line-height:1.25;
}
.vrm-import .vi-card-head p {
    margin:2px 0 0; font-size:.8rem; color:var(--vi-muted); line-height:1.35;
}
.vrm-import .vi-card-body { padding:18px; }

.vrm-import .vi-sample {
    display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    padding:14px 16px; border-radius:14px; margin-bottom:18px;
    background:linear-gradient(120deg,var(--theme-50) 0%,#f5f3ff 100%);
    border:1px solid #e0e7ff;
}
.vrm-import .vi-sample-left { display:flex; align-items:center; gap:12px; min-width:0; }
.vrm-import .vi-sample-ico {
    width:40px; height:40px; border-radius:12px; flex-shrink:0;
    background:#fff; color:var(--vi-blue);
    display:inline-flex; align-items:center; justify-content:center; font-size:1.15rem;
    box-shadow:0 4px 12px var(--theme-primary-ring);
}
.vrm-import .vi-sample strong { display:block; font-size:.9rem; color:var(--vi-ink); font-weight:800; }
.vrm-import .vi-sample span { display:block; font-size:.78rem; color:var(--vi-muted); margin-top:2px; }
.vrm-import .btn-sample {
    height:38px; padding:0 14px; border-radius:10px; font-weight:700; font-size:.84rem;
    display:inline-flex; align-items:center; gap:.4rem; text-decoration:none; white-space:nowrap;
    background:var(--theme-primary); color:#fff; border:0;
    box-shadow:0 6px 14px var(--theme-primary-ring);
}
.vrm-import .btn-sample:hover { background:var(--theme-primary-hover); color:#fff; }

.vrm-import .vi-drop {
    position:relative; border:1.5px dashed #c7d2fe; border-radius:14px;
    background:#f8fafc; padding:22px 18px; text-align:center; transition:.15s ease;
}
.vrm-import .vi-drop:hover,
.vrm-import .vi-drop.is-drag { border-color:#818cf8; background:#eef2ff; }
.vrm-import .vi-drop-ico {
    width:52px; height:52px; margin:0 auto 12px; border-radius:16px;
    background:#fff; color:var(--vi-purple);
    display:inline-flex; align-items:center; justify-content:center; font-size:1.4rem;
    box-shadow:0 6px 16px rgba(124,58,237,.14);
}
.vrm-import .vi-drop h3 { margin:0 0 4px; font-size:.98rem; font-weight:800; color:var(--vi-ink); }
.vrm-import .vi-drop p { margin:0 0 14px; font-size:.82rem; color:var(--vi-muted); }
.vrm-import .vi-file-row {
    display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap;
}
.vrm-import .vi-file-btn {
    position:relative; overflow:hidden; cursor:pointer;
    height:40px; padding:0 16px; border-radius:10px; font-weight:700; font-size:.86rem;
    display:inline-flex; align-items:center; gap:.45rem;
    background:#fff; color:#334155; border:1px solid #e2e8f0;
}
.vrm-import .vi-file-btn:hover { background:#f8fafc; border-color:#cbd5e1; }
.vrm-import .vi-file-btn input {
    position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%;
}
.vrm-import .vi-file-name {
    font-size:.84rem; color:var(--vi-muted); font-weight:600; max-width:220px;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.vrm-import .vi-file-name.has-file { color:var(--vi-ink); }

.vrm-import .vi-actions {
    display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:16px;
}
.vrm-import .btn-import {
    height:42px; padding:0 18px; border-radius:10px; font-weight:700; font-size:.9rem;
    display:inline-flex; align-items:center; gap:.45rem; border:0; color:#fff;
    background:linear-gradient(90deg,#16a34a,#15803d);
    box-shadow:0 8px 16px rgba(22,163,74,.25);
}
.vrm-import .btn-import:hover { filter:brightness(1.05); color:#fff; }
.vrm-import .vi-hint {
    font-size:.78rem; color:var(--vi-muted); display:inline-flex; align-items:center; gap:6px;
}

.vrm-import .col-list { list-style:none; margin:0; padding:0; display:grid; gap:10px; }
.vrm-import .col-item {
    display:flex; align-items:flex-start; gap:12px;
    padding:12px 14px; border-radius:12px; background:#f8fafc; border:1px solid #f1f5f9;
}
.vrm-import .col-item .ci {
    width:34px; height:34px; border-radius:10px; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center; font-size:.95rem;
}
.vrm-import .col-item .ci.req { background:#fee2e2; color:#dc2626; }
.vrm-import .col-item .ci.opt { background:#e0e7ff; color:#4f46e5; }
.vrm-import .col-item .ci.date { background:#ffedd5; color:#ea580c; }
.vrm-import .col-meta { min-width:0; flex:1; }
.vrm-import .col-meta .name {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    font-size:.88rem; font-weight:800; color:var(--vi-ink); line-height:1.3;
}
.vrm-import .badge-req {
    font-size:.65rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase;
    padding:.18rem .5rem; border-radius:999px; background:#fee2e2; color:#b91c1c;
}
.vrm-import .badge-opt {
    font-size:.65rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase;
    padding:.18rem .5rem; border-radius:999px; background:#e0e7ff; color:#4338ca;
}
.vrm-import .col-meta .desc { margin:4px 0 0; font-size:.78rem; color:var(--vi-muted); line-height:1.4; }
</style>
@endpush

@section('content')
<div class="container-fluid p-4 vrm-import">
    <div class="page-hero">
        <div class="page-hero-left">
            <span class="page-hero-icon"><i class="bi bi-cloud-upload"></i></span>
            <div>
                <ul class="vi-crumbs">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li><a href="{{ route('goal.vrm.index') }}">VRM Calling</a></li>
                    <li class="sep">›</li>
                    <li>Import</li>
                </ul>
                <h1 class="vi-title">Import VRM Call Logs</h1>
                <div class="vi-sub">Upload Excel/CSV into Goal VRM Calling with the sample column format.</div>
            </div>
        </div>
        <a href="{{ route('goal.vrm.index') }}" class="btn-back">
            <i class="bi bi-arrow-left"></i> Back to VRM Calling
        </a>
    </div>

    <p class="vi-note"><i class="bi bi-bullseye me-1"></i>Imported calls count toward the <strong>200 logged calls / working day</strong> target shown on VRM Calling.</p>

    <div class="vi-grid">
        <div class="vi-card">
            <div class="vi-card-head">
                <span class="vi-card-ico upload"><i class="bi bi-file-earmark-arrow-up"></i></span>
                <div>
                    <h2>Upload file</h2>
                    <p>Accepted formats: .xlsx, .xls, .csv (max 10 MB)</p>
                </div>
            </div>
            <div class="vi-card-body">
                <div class="vi-sample">
                    <div class="vi-sample-left">
                        <span class="vi-sample-ico"><i class="bi bi-filetype-csv"></i></span>
                        <div>
                            <strong>Need the template?</strong>
                            <span>Download the sample CSV and keep the header row intact.</span>
                        </div>
                    </div>
                    <a href="{{ asset('files/sample_vrm_call_logs.csv') }}" class="btn-sample">
                        <i class="bi bi-download"></i> Download Sample
                    </a>
                </div>

                <form action="{{ route('goal.vrm.calls.import.store') }}" method="POST" enctype="multipart/form-data" id="vrmImportForm">
                    @csrf
                    <div class="vi-drop" id="vrmImportDrop">
                        <div class="vi-drop-ico"><i class="bi bi-cloud-arrow-up"></i></div>
                        <h3>Select Excel / CSV file</h3>
                        <p>Choose a file from your computer to start the import.</p>
                        <div class="vi-file-row">
                            <label class="vi-file-btn">
                                <i class="bi bi-folder2-open"></i> Choose File
                                <input type="file" name="import_file" id="import_file" accept=".xlsx,.xls,.csv" required>
                            </label>
                            <span class="vi-file-name" id="importFileName">No file chosen</span>
                        </div>
                    </div>

                    <div class="vi-actions">
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Upload &amp; Import
                        </button>
                        <span class="vi-hint"><i class="bi bi-info-circle"></i> First row must contain column headers.</span>
                    </div>
                </form>
            </div>
        </div>

        <div class="vi-card">
            <div class="vi-card-head">
                <span class="vi-card-ico guide"><i class="bi bi-list-check"></i></span>
                <div>
                    <h2>Column guide</h2>
                    <p>Required and optional fields for a successful import</p>
                </div>
            </div>
            <div class="vi-card-body">
                <ul class="col-list">
                    <li class="col-item">
                        <span class="ci req"><i class="bi bi-person"></i></span>
                        <div class="col-meta">
                            <div class="name">Customer Name <span class="badge-req">Required</span></div>
                            <p class="desc">Full customer name as captured during the call.</p>
                        </div>
                    </li>
                    <li class="col-item">
                        <span class="ci req"><i class="bi bi-chat-left-dots"></i></span>
                        <div class="col-meta">
                            <div class="name">Disposition <span class="badge-req">Required</span></div>
                            <p class="desc">Interested, Not Interested, No Answer, Busy, Callback, Invalid Number</p>
                        </div>
                    </li>
                    <li class="col-item">
                        <span class="ci opt"><i class="bi bi-telephone"></i></span>
                        <div class="col-meta">
                            <div class="name">Mobile <span class="badge-opt">Optional</span></div>
                            <p class="desc">Customer mobile number with country code if available.</p>
                        </div>
                    </li>
                    <li class="col-item">
                        <span class="ci opt"><i class="bi bi-journal-text"></i></span>
                        <div class="col-meta">
                            <div class="name">Notes <span class="badge-opt">Optional</span></div>
                            <p class="desc">Call remarks, customer response, or follow-up comments.</p>
                        </div>
                    </li>
                    <li class="col-item">
                        <span class="ci date"><i class="bi bi-calendar-event"></i></span>
                        <div class="col-meta">
                            <div class="name">Callback At <span class="badge-opt">Optional</span></div>
                            <p class="desc">Scheduled callback date/time (e.g. 14-11-2026 10:30).</p>
                        </div>
                    </li>
                    <li class="col-item">
                        <span class="ci date"><i class="bi bi-clock-history"></i></span>
                        <div class="col-meta">
                            <div class="name">Called At <span class="badge-opt">Optional</span></div>
                            <p class="desc">When the call was made. Defaults to import time if blank.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('import_file');
    var nameEl = document.getElementById('importFileName');
    var drop = document.getElementById('vrmImportDrop');
    if (!input || !nameEl) return;

    function showName() {
        var file = input.files && input.files[0];
        if (file) {
            nameEl.textContent = file.name;
            nameEl.classList.add('has-file');
        } else {
            nameEl.textContent = 'No file chosen';
            nameEl.classList.remove('has-file');
        }
    }

    input.addEventListener('change', showName);

    if (drop) {
        ['dragenter', 'dragover'].forEach(function (evt) {
            drop.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                drop.classList.add('is-drag');
            });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            drop.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                drop.classList.remove('is-drag');
            });
        });
        drop.addEventListener('drop', function (e) {
            var files = e.dataTransfer && e.dataTransfer.files;
            if (!files || !files.length) return;
            input.files = files;
            showName();
        });
    }
})();
</script>
@endpush
