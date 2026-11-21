@extends('layouts.app')

@push('styles')
<style>
.al-page{
    --al-border:#e2e8f0;--al-text:#0f172a;--al-muted:#64748b;
    --al-primary:var(--theme-primary,#009B80);
    --al-primary-hover:var(--theme-primary-hover,#007a66);
    color:var(--al-text);
}
.al-page .page-hero{
    display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;
    gap:1rem;margin-bottom:1.1rem;
}
.al-page .breadcrumb-al{
    list-style:none;display:flex;flex-wrap:wrap;gap:.35rem;align-items:center;
    margin:0 0 .35rem;padding:0;font-size:.78rem;color:#94a3b8;
}
.al-page .breadcrumb-al a{color:#64748b;text-decoration:none;font-weight:600}
.al-page .breadcrumb-al a:hover{color:var(--al-primary)}
.al-page .breadcrumb-al .sep{color:#cbd5e1}
.al-page .page-title{margin:0;font-size:1.55rem;font-weight:800;letter-spacing:-.02em;color:#0f172a}
.al-page .page-sub{margin:.25rem 0 0;font-size:.86rem;color:var(--al-muted)}

.al-page .filter-card,.al-page .table-card{
    background:#fff;border:1px solid var(--al-border);border-radius:16px;
    box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 24px rgba(148,163,184,.1);
    margin-bottom:.85rem;
}
.al-page .filter-card{padding:1rem 1.1rem 1.05rem}
.al-page .filter-head{
    display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.85rem;
}
.al-page .filter-head h2{
    margin:0;font-size:.95rem;font-weight:800;display:flex;align-items:center;gap:.45rem;
}
.al-page .filter-head h2 i{color:var(--al-primary)}
.al-page .filter-grid{
    display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.65rem .75rem;align-items:end;
}
.al-page .filter-grid .fg{min-width:0}
.al-page .filter-grid label{
    display:block;margin:0 0 .28rem;font-size:.72rem;font-weight:700;color:#64748b;
    text-transform:uppercase;letter-spacing:.03em;
}
.al-page .filter-grid .form-control,
.al-page .filter-grid .form-select{
    height:38px;border-radius:10px;border:1px solid #cbd5e1;font-size:.84rem;
    box-shadow:none;
}
.al-page .filter-grid .form-control:focus,
.al-page .filter-grid .form-select:focus{
    border-color:var(--al-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--al-primary) 18%,transparent);
}
.al-page .filter-actions{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.85rem}
.al-page .btn-apply{
    height:36px;border:0;border-radius:10px;padding:0 14px;font-weight:700;font-size:.84rem;
    background:var(--al-primary);color:#fff;display:inline-flex;align-items:center;gap:6px;
}
.al-page .btn-apply:hover{background:var(--al-primary-hover);color:#fff}
.al-page .btn-reset{
    height:36px;border:1px solid #cbd5e1;border-radius:10px;padding:0 14px;font-weight:650;font-size:.84rem;
    background:#fff;color:#475569;text-decoration:none;display:inline-flex;align-items:center;gap:6px;
}
.al-page .btn-reset:hover{background:#f8fafc;color:#0f172a}

.al-page .table-card-head{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
    gap:.5rem;padding:.85rem 1.1rem;border-bottom:1px solid #eef2f7;
}
.al-page .table-card-head h5{margin:0;font-size:1.02rem;font-weight:800}
.al-page .head-sub{margin:2px 0 0;font-size:.78rem;color:var(--al-muted)}

.al-page .al-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.al-page table.al-grid{
    width:100%;min-width:980px;margin:0;border-collapse:collapse;table-layout:fixed;
}
.al-page table.al-grid thead th{
    background:#f8fafc;color:#475569;font-size:.7rem;font-weight:800;text-transform:uppercase;
    letter-spacing:.04em;padding:.65rem .85rem;border-bottom:1px solid #e2e8f0;white-space:nowrap;
}
.al-page table.al-grid tbody td{
    padding:.7rem .85rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;
    font-size:.84rem;color:#0f172a;
}
.al-page table.al-grid tbody tr:hover td{background:#f8fafc}
.al-page table.al-grid col.c-act{width:22%}
.al-page table.al-grid col.c-user{width:18%}
.al-page table.al-grid col.c-type{width:10%}
.al-page table.al-grid col.c-ip{width:12%}
.al-page table.al-grid col.c-dev{width:16%}
.al-page table.al-grid col.c-time{width:14%}
.al-page table.al-grid col.c-btn{width:8%}

.al-page .act-cell{display:flex;align-items:flex-start;gap:.65rem;min-width:0}
.al-page .act-ico{
    width:32px;height:32px;border-radius:9px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;font-size:.9rem;
    background:#ecfdf5;color:var(--al-primary);border:1px solid #a7f3d0;
}
.al-page .act-ico.is-create{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.al-page .act-ico.is-update{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.al-page .act-ico.is-delete{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.al-page .act-ico.is-login,.al-page .act-ico.is-view{background:#f8fafc;color:#475569;border-color:#e2e8f0}
.al-page .act-title{font-weight:700;line-height:1.25;word-break:break-word}
.al-page .act-sub{font-size:.72rem;color:#94a3b8;margin-top:2px}

.al-page .user-cell{min-width:0}
.al-page .user-name{font-weight:700;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.al-page .user-email{font-size:.72rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.al-page .type-pill{
    display:inline-flex;align-items:center;padding:.18rem .55rem;border-radius:999px;
    font-size:.7rem;font-weight:800;border:1px solid transparent;text-transform:capitalize;
}
.al-page .type-pill.create{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.al-page .type-pill.update{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.al-page .type-pill.delete{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.al-page .type-pill.login,.al-page .type-pill.view,.al-page .type-pill.other{
    background:#f1f5f9;color:#475569;border-color:#e2e8f0;
}

.al-page .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:.78rem;color:#334155}
.al-page .device-txt{
    color:#475569;font-size:.78rem;line-height:1.35;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.al-page .time-main{font-weight:700;font-size:.82rem}
.al-page .time-sub{font-size:.72rem;color:#94a3b8;margin-top:1px}

.al-page .btn-view{
    height:32px;padding:0 10px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;
    color:#334155;font-size:.78rem;font-weight:700;text-decoration:none;
    display:inline-flex;align-items:center;gap:5px;white-space:nowrap;
}
.al-page .btn-view:hover{background:#f8fafc;color:var(--al-primary);border-color:#94a3b8}

.al-page .empty-state{
    text-align:center;padding:2.5rem 1rem;color:#94a3b8;
}
.al-page .empty-state i{font-size:1.8rem;display:block;margin-bottom:.5rem;color:#cbd5e1}
.al-page .empty-state strong{display:block;color:#64748b;font-size:.95rem;margin-bottom:.2rem}

.al-page .table-footer{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
    gap:.65rem;padding:.75rem 1.1rem;border-top:1px solid #eef2f7;
}
.al-page .table-footer .meta{font-size:.78rem;color:var(--al-muted)}
.al-page .foot-right{display:flex;flex-wrap:wrap;align-items:center;gap:.65rem}
.al-page .per-page-wrap{
    display:inline-flex;align-items:center;gap:.4rem;font-size:.78rem;color:#64748b;font-weight:600;
}
.al-page .per-page-wrap select{
    height:32px;border-radius:8px;border:1px solid #cbd5e1;padding:0 8px;font-size:.78rem;background:#fff;
}

@media (max-width:1100px){
    .al-page .filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media (max-width:700px){
    .al-page .filter-grid{grid-template-columns:1fr 1fr}
    .al-page .page-title{font-size:1.3rem}
}
@media (max-width:520px){
    .al-page .filter-grid{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
@php
    $actionClass = function (?string $action): string {
        $a = strtolower(trim((string) $action));
        if (str_contains($a, 'creat') || $a === 'store') return 'create';
        if (str_contains($a, 'updat') || $a === 'edit') return 'update';
        if (str_contains($a, 'delet') || $a === 'destroy') return 'delete';
        if (str_contains($a, 'login')) return 'login';
        if (str_contains($a, 'view') || str_contains($a, 'show')) return 'view';
        return 'other';
    };
    $actionIcon = fn (string $cls) => match ($cls) {
        'create' => 'bi-plus-lg',
        'update' => 'bi-pencil',
        'delete' => 'bi-trash3',
        'login' => 'bi-box-arrow-in-right',
        'view' => 'bi-eye',
        default => 'bi-activity',
    };
@endphp

<div class="container-fluid p-4 al-page">
    <div class="page-hero">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb-al">
                    <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="sep">/</li>
                    <li>System</li>
                    <li class="sep">/</li>
                    <li>Audit Logs</li>
                </ol>
            </nav>
            <h1 class="page-title">Audit Logs</h1>
            <p class="page-sub">Track user activity across Goal CRM — filters apply to the table below.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('activity-logs.index') }}" class="filter-card" id="alFilterForm">
        <div class="filter-head">
            <h2><i class="bi bi-funnel"></i> Filters</h2>
        </div>
        <div class="filter-grid">
            <div class="fg">
                <label for="al_user">User</label>
                <select name="user_id" id="al_user" class="form-select" data-select2-skip="1">
                    <option value="">All users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg">
                <label for="al_module">Category</label>
                <select name="module" id="al_module" class="form-select" data-select2-skip="1">
                    <option value="">All categories</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}" @selected(($filters['module'] ?? '') === $mod)>{{ $mod }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg">
                <label for="al_action">Event type</label>
                <select name="action" id="al_action" class="form-select" data-select2-skip="1">
                    <option value="">All events</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" @selected(($filters['action'] ?? '') === $act)>{{ ucfirst($act) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg">
                <label for="al_from">From</label>
                <input type="date" name="from" id="al_from" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="fg">
                <label for="al_to">To</label>
                <input type="date" name="to" id="al_to" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="fg">
                <label for="al_q">Search</label>
                <input type="text" name="user_name" id="al_q" class="form-control"
                       value="{{ $filters['user_name'] ?? '' }}"
                       placeholder="User, module, IP…">
            </div>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-apply"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ route('activity-logs.index') }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        </div>
    </form>

    <div class="table-card">
        <div class="table-card-head">
            <div>
                <h5>Activity register</h5>
                <p class="head-sub">{{ number_format($logs->total()) }} event{{ $logs->total() === 1 ? '' : 's' }}</p>
            </div>
        </div>

        @if($logs->total() === 0)
            <div class="empty-state">
                <i class="bi bi-clipboard-data"></i>
                <strong>No audit logs found</strong>
                <span>Try widening the date range or clearing filters.</span>
            </div>
        @else
            <div class="al-table-wrap">
                <table class="al-grid">
                    <colgroup>
                        <col class="c-act">
                        <col class="c-user">
                        <col class="c-type">
                        <col class="c-ip">
                        <col class="c-dev">
                        <col class="c-time">
                        <col class="c-btn">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>IP address</th>
                            <th>Device</th>
                            <th>Timestamp</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            @php
                                $cls = $actionClass($log->action);
                                $module = trim((string) ($log->module ?? ''));
                                $action = trim((string) ($log->action ?? ''));
                                $title = trim(($module !== '' ? $module.' · ' : '').ucfirst($action !== '' ? $action : 'event'));
                            @endphp
                            <tr>
                                <td>
                                    <div class="act-cell">
                                        <span class="act-ico is-{{ $cls }}"><i class="bi {{ $actionIcon($cls) }}"></i></span>
                                        <div style="min-width:0">
                                            <div class="act-title" title="{{ $title }}">{{ $title }}</div>
                                            @if($module !== '')
                                                <div class="act-sub">{{ $module }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-name" title="{{ $log->user?->name ?: 'Unknown' }}">{{ $log->user?->name ?: 'Unknown' }}</div>
                                        @if($log->user?->email)
                                            <div class="user-email" title="{{ $log->user->email }}">{{ $log->user->email }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="type-pill {{ $cls }}">{{ $action !== '' ? ucfirst($action) : '—' }}</span>
                                </td>
                                <td>
                                    <span class="mono">{{ $log->ip_address ?: '—' }}</span>
                                </td>
                                <td>
                                    <div class="device-txt" title="{{ $log->device }}">{{ $log->device ? \Illuminate\Support\Str::limit($log->device, 56) : '—' }}</div>
                                </td>
                                <td>
                                    <div class="time-main">{{ optional($log->created_at)->format('d M Y, H:i') }}</div>
                                    <div class="time-sub">{{ optional($log->created_at)->diffForHumans() }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('activity-logs.show', $log) }}" class="btn-view" title="View details">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="meta">
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}
                </div>
                <div class="foot-right">
                    <form method="GET" action="{{ route('activity-logs.index') }}" class="per-page-wrap">
                        @foreach(request()->except('per_page', 'page') as $key => $value)
                            @if(is_array($value))
                                @foreach($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <span>Rows</span>
                        <select name="per_page" data-select2-skip="1" onchange="this.form.submit()" aria-label="Rows per page">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected((int) $perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if($logs->hasPages())
                        <div>{{ $logs->onEachSide(1)->links('vendor.pagination.users') }}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
