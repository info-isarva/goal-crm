@extends('layouts.app')

@push('styles')
<style>
.al-show{--al-primary:var(--theme-primary,#009B80);--al-border:#e2e8f0;color:#0f172a}
.al-show .page-hero{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.1rem}
.al-show .breadcrumb-al{list-style:none;display:flex;flex-wrap:wrap;gap:.35rem;align-items:center;margin:0 0 .35rem;padding:0;font-size:.78rem;color:#94a3b8}
.al-show .breadcrumb-al a{color:#64748b;text-decoration:none;font-weight:600}
.al-show .breadcrumb-al .sep{color:#cbd5e1}
.al-show .page-title{margin:0;font-size:1.45rem;font-weight:800;letter-spacing:-.02em}
.al-show .page-sub{margin:.25rem 0 0;font-size:.86rem;color:#64748b}
.al-show .btn-back{
    height:36px;padding:0 14px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#334155;
    font-size:.84rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;
}
.al-show .btn-back:hover{background:#f8fafc;color:var(--al-primary)}
.al-show .card-grid{display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:.85rem}
.al-show .al-card{
    background:#fff;border:1px solid var(--al-border);border-radius:16px;padding:1rem 1.1rem;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 24px rgba(148,163,184,.1);
}
.al-show .al-card h3{
    margin:0 0 .85rem;font-size:.92rem;font-weight:800;display:flex;align-items:center;gap:.45rem;
}
.al-show .al-card h3 i{color:var(--al-primary)}
.al-show .kv{display:flex;justify-content:space-between;gap:1rem;padding:.55rem .7rem;border-radius:10px;background:#f8fafc;border:1px solid #eef2f7;margin-bottom:.45rem}
.al-show .kv:last-child{margin-bottom:0}
.al-show .kv .k{font-size:.78rem;font-weight:700;color:#64748b}
.al-show .kv .v{font-size:.84rem;font-weight:700;color:#0f172a;text-align:right;word-break:break-word}
.al-show .user-row{display:flex;align-items:center;gap:.75rem;margin-bottom:.65rem}
.al-show .avatar{
    width:44px;height:44px;border-radius:12px;background:#ecfdf5;color:var(--al-primary);
    border:1px solid #a7f3d0;display:inline-flex;align-items:center;justify-content:center;font-weight:800;
}
.al-show .user-name{font-weight:800}
.al-show .user-email{font-size:.78rem;color:#94a3b8}
.al-show .details-card{margin-bottom:1rem}
.al-show .meta-row{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.75rem}
.al-show .chip{
    display:inline-flex;align-items:center;gap:5px;padding:.28rem .65rem;border-radius:999px;
    font-size:.74rem;font-weight:700;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;
}
.al-show pre{
    margin:0;padding:.85rem 1rem;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;
    font-size:.8rem;color:#0f172a;overflow:auto;max-height:420px;
}
.al-show .muted{color:#94a3b8;font-size:.86rem}
@media (max-width:900px){.al-show .card-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
    $json = null;
    if (! empty($activityLog->details)) {
        try {
            $json = json_decode($activityLog->details, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $json = null;
        }
    }
@endphp
<div class="container-fluid p-4 al-show">
    <div class="page-hero">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb-al">
                    <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="sep">/</li>
                    <li><a href="{{ route('activity-logs.index') }}">Audit Logs</a></li>
                    <li class="sep">/</li>
                    <li>#{{ $activityLog->id }}</li>
                </ol>
            </nav>
            <h1 class="page-title">Audit detail</h1>
            <p class="page-sub">{{ ucfirst((string) $activityLog->action) }} · {{ $activityLog->module ?: 'General' }}</p>
        </div>
        <a href="{{ route('activity-logs.index') }}" class="btn-back"><i class="bi bi-arrow-left"></i> Back to logs</a>
    </div>

    <div class="card-grid">
        <div class="al-card">
            <h3><i class="bi bi-info-circle"></i> Event</h3>
            <div class="kv"><span class="k">Event type</span><span class="v">{{ ucfirst((string) $activityLog->action) ?: '—' }}</span></div>
            <div class="kv"><span class="k">Category</span><span class="v">{{ $activityLog->module ?: '—' }}</span></div>
            <div class="kv"><span class="k">Subject type</span><span class="v">{{ $activityLog->subject_type ?: '—' }}</span></div>
            <div class="kv"><span class="k">Subject ID</span><span class="v">{{ $activityLog->subject_id ? '#'.$activityLog->subject_id : '—' }}</span></div>
            <div class="kv"><span class="k">IP address</span><span class="v">{{ $activityLog->ip_address ?: '—' }}</span></div>
            <div class="kv"><span class="k">Device</span><span class="v">{{ $activityLog->device ?: '—' }}</span></div>
        </div>
        <div class="al-card">
            <h3><i class="bi bi-person"></i> User</h3>
            <div class="user-row">
                <div class="avatar">{{ $activityLog->user ? strtoupper(substr($activityLog->user->name, 0, 1)) : '?' }}</div>
                <div>
                    <div class="user-name">{{ $activityLog->user?->name ?: 'Unknown' }}</div>
                    <div class="user-email">{{ $activityLog->user?->email ?: '' }}</div>
                </div>
            </div>
            <div class="kv"><span class="k">User ID</span><span class="v">{{ $activityLog->user?->id ? '#'.$activityLog->user->id : '—' }}</span></div>
            <div class="kv"><span class="k">Logged at</span><span class="v">{{ optional($activityLog->created_at)->format('d M Y, H:i:s') ?: '—' }}</span></div>
        </div>
    </div>

    <div class="al-card details-card">
        <h3><i class="bi bi-code-slash"></i> Details</h3>
        <div class="meta-row">
            <span class="chip"><i class="bi bi-calendar-plus"></i> Created {{ optional($activityLog->created_at)->format('d M Y H:i') ?: '—' }}</span>
            <span class="chip"><i class="bi bi-clock-history"></i> {{ optional($activityLog->created_at)->diffForHumans() ?: '' }}</span>
        </div>
        @if($activityLog->details)
            <pre>{{ $json ? json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : $activityLog->details }}</pre>
        @else
            <div class="muted">No detail payload recorded for this event.</div>
        @endif
    </div>
</div>
@endsection
