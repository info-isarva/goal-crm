@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.pd-page.notif-page .pd-hero-brand{display:flex;align-items:flex-start;gap:12px;min-width:0}
.pd-page.notif-page .pd-hero-ico{
    width:44px;height:44px;border-radius:12px;flex:0 0 auto;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--theme-primary);color:#fff;font-size:1.2rem;
    box-shadow:0 8px 18px var(--theme-primary-ring);
}
.pd-page.notif-page .pd-kpi-row{grid-template-columns:repeat(4,minmax(0,1fr))}
.pd-page.notif-page .notif-filters{
    display:grid;grid-template-columns:minmax(120px,.9fr) minmax(160px,1.1fr) minmax(180px,1.4fr) auto auto;
    gap:10px;align-items:end;margin-bottom:.85rem;padding:.85rem 1rem;
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
}
.pd-page.notif-page .notif-filters label{display:block;font-size:.72rem;font-weight:700;color:#64748b;margin:0 0 4px}
.pd-page.notif-page .notif-filters .form-control,
.pd-page.notif-page .notif-filters .form-select{min-height:38px;height:38px;border-radius:9px;font-size:.84rem}
.pd-page.notif-page .notif-card{
    background:#fff;border:1px solid #e8edf5;border-radius:16px;overflow:hidden;
    box-shadow:0 8px 24px rgba(15,23,42,.04);
}
.pd-page.notif-page .notif-card-head{
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
    padding:14px 16px;border-bottom:1px solid #f1f5f9;
}
.pd-page.notif-page .notif-card-head h5{margin:0;font-size:.98rem;font-weight:800;color:#0f172a}
.pd-page.notif-page .notif-card-head p{margin:2px 0 0;font-size:.78rem;color:#64748b}
.pd-page.notif-page .notif-row{
    display:flex;gap:12px;align-items:flex-start;padding:14px 16px;
    border-bottom:1px solid #f1f5f9;background:#fff;
}
.pd-page.notif-page .notif-row:last-child{border-bottom:0}
.pd-page.notif-page .notif-row.unread{background:#f8fffb}
.pd-page.notif-page .notif-dot{
    width:10px;height:10px;border-radius:999px;margin-top:6px;flex:0 0 auto;
    background:var(--theme-primary);box-shadow:0 0 0 3px rgba(15,118,110,.12);
}
.pd-page.notif-page .notif-dot.read{background:#cbd5e1;box-shadow:none}
.pd-page.notif-page .notif-title{font-size:.92rem;font-weight:800;color:#0f172a;line-height:1.3}
.pd-page.notif-page .notif-body{font-size:.84rem;color:#475569;margin-top:4px;line-height:1.4}
.pd-page.notif-page .notif-meta{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px}
.pd-page.notif-page .notif-ref{
    display:inline-flex;align-items:center;padding:.18rem .55rem;border-radius:999px;
    background:#f1f5f9;color:#334155;font-size:.7rem;font-weight:700;
}
.pd-page.notif-page .badge.bg-teal{background:#0d9488!important}
.pd-page.notif-page .notif-empty{padding:36px 16px;text-align:center;color:#94a3b8;font-size:.88rem}
@media (max-width:991.98px){
    .pd-page.notif-page .notif-filters{grid-template-columns:1fr 1fr}
}
@media (max-width:767.98px){
    .pd-page.notif-page .pd-kpi-row{grid-template-columns:1fr 1fr}
    .pd-page.notif-page .notif-filters{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
@php
    use App\Services\Goal\GoalNotify;
    $status = $filters['status'] ?? 'unread';
    $type = $filters['type'] ?? 'all';
    $q = $filters['q'] ?? '';
@endphp
<div class="container-fluid p-4 pd-page notif-page">
    <div class="pd-hero">
        <div class="pd-hero-brand">
            <span class="pd-hero-ico" aria-hidden="true"><i class="bi bi-bell"></i></span>
            <div>
                <h1 class="pd-title">Notifications</h1>
                <p class="pd-sub">In-app alerts for leads, visits, bank queries and operational holds.</p>
                <ul class="pd-crumbs" style="margin-top:8px;margin-bottom:0">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li class="sep">›</li>
                    <li>System</li>
                    <li class="sep">›</li>
                    <li>Notifications</li>
                </ul>
            </div>
        </div>
        <div class="pd-hero-actions">
            <button type="button" class="pd-link-btn" id="markAllReadPage">
                <i class="bi bi-check2-all"></i> Mark all read
            </button>
        </div>
    </div>

    <div class="pd-kpi-row">
        <div class="pd-kpi tone-blue">
            <p class="pd-kpi-label">Unread</p>
            <p class="pd-kpi-value">{{ number_format($kpis['unread'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-slate">
            <p class="pd-kpi-label">Today</p>
            <p class="pd-kpi-value">{{ number_format($kpis['today'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-amber">
            <p class="pd-kpi-label">Action required</p>
            <p class="pd-kpi-value">{{ number_format($kpis['action'] ?? 0) }}</p>
        </div>
        <div class="pd-kpi tone-red">
            <p class="pd-kpi-label">Ageing / hold</p>
            <p class="pd-kpi-value">{{ number_format($kpis['ageing'] ?? 0) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('notifications.index') }}" class="notif-filters" id="notifFilterForm">
        <div>
            <label>Status</label>
            <select name="status" class="form-select">
                <option value="all" @selected($status === 'all')>All</option>
                <option value="unread" @selected($status === 'unread')>Unread</option>
                <option value="read" @selected($status === 'read')>Read</option>
            </select>
        </div>
        <div>
            <label>Event type</label>
            <select name="type" class="form-select">
                <option value="all" @selected($type === 'all')>All events</option>
                @foreach($typeOptions as $opt)
                    <option value="{{ $opt }}" @selected($type === $opt)>{{ GoalNotify::typeLabel($opt) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Search</label>
            <input type="search" name="q" class="form-control" value="{{ $q }}" placeholder="Lead, case, customer…" autocomplete="off">
        </div>
        <div>
            <button type="submit" class="pd-btn" style="height:38px"><i class="bi bi-funnel"></i> Apply</button>
        </div>
        <div>
            <a href="{{ route('notifications.index') }}" class="pd-btn-ghost" style="height:38px">Reset</a>
        </div>
    </form>

    <div class="notif-card">
        <div class="notif-card-head">
            <div>
                <h5>Inbox</h5>
                <p>Your role-scoped in-app feed</p>
            </div>
        </div>

        @forelse($notifications as $notification)
            @php
                $data = $notification->data ?? [];
                $link = GoalNotify::linkFromData($data);
                $nType = GoalNotify::inferType($data);
                $title = $data['title'] ?? null;
                $body = $data['message'] ?? ($data['title'] ?? 'Notification');
                $ref = $data['ref'] ?? null;
                $isRead = (bool) $notification->read_at;
            @endphp
            <div class="notif-row {{ $isRead ? '' : 'unread' }}" data-id="{{ $notification->id }}">
                <span class="notif-dot {{ $isRead ? 'read' : '' }}" aria-hidden="true"></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div class="notif-title">{{ $title ?: \Illuminate\Support\Str::limit(strip_tags($body), 80) }}</div>
                        <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                    @if($title)
                        <div class="notif-body">{!! $body !!}</div>
                    @elseif($body !== ($title ?: ''))
                        <div class="notif-body">{!! $body !!}</div>
                    @endif
                    <div class="notif-meta">
                        <span class="badge {{ GoalNotify::typeBadgeClass($nType) }}">{{ GoalNotify::typeLabel($nType) }}</span>
                        @if($ref)
                            <span class="notif-ref">{{ $ref }}</span>
                        @endif
                        @if($link)
                            <a class="btn btn-sm btn-outline-primary" href="{{ $link }}">Open</a>
                        @endif
                        @if(! $isRead)
                            <button type="button" class="btn btn-sm btn-link p-0 mark-read-btn" data-id="{{ $notification->id }}">Mark read</button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="notif-empty">No notifications for this filter.</div>
        @endforelse

        @if($notifications->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function postMark(payload, cb) {
        fetch("{{ route('notifications.markRead') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json(); }).then(function (j) { if (cb) cb(j); })
         .catch(function (e) { console.error('Mark read failed', e); });
    }

    document.querySelectorAll('.mark-read-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var row = this.closest('.notif-row');
            postMark({ id: id }, function (resp) {
                if (!resp || !resp.success || !row) return;
                row.classList.remove('unread');
                var dot = row.querySelector('.notif-dot');
                if (dot) dot.classList.add('read');
                btn.remove();
            });
        });
    });

    var markAll = document.getElementById('markAllReadPage');
    if (markAll) {
        markAll.addEventListener('click', function (e) {
            e.preventDefault();
            postMark({ all: 1 }, function (resp) {
                if (!resp || !resp.success) return;
                document.querySelectorAll('.notif-row.unread').forEach(function (row) {
                    row.classList.remove('unread');
                    var dot = row.querySelector('.notif-dot');
                    if (dot) dot.classList.add('read');
                    var btn = row.querySelector('.mark-read-btn');
                    if (btn) btn.remove();
                });
            });
        });
    }
});
</script>
@endpush
