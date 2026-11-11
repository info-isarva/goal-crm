@extends('layouts.app')

@section('content')
<style>
    .activity-table-card {
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 2px 16px 0 rgba(31, 38, 135, 0.07);
        margin-bottom: 2rem;
        padding: 2.5rem 2rem 2rem 2rem;
        border: 1px solid #f3f4f6;
    }
    .activity-table-header {
        display: flex;
        align-items: center;
        font-size: 1.7rem;
        font-weight: 700;
        margin-bottom: 1.2rem;
    }
    .activity-table-header .icon {
        font-size: 2.1rem;
        margin-right: 0.7rem;
        color: #6366f1;
    }
    .activity-table-controls {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }
    .activity-table-controls .form-control {
        border-radius: 999px;
        margin-right: 0.7rem;
    }
    .activity-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 0.7rem;
    }
    .activity-table th {
        background: #f7f8fd;
        color: #222;
        font-weight: 600;
        border: none;
        padding: 1rem 0.7rem;
        font-size: 1.05rem;
        text-align: left;
    }
    .activity-table td {
        background: #fff;
        border: none;
        padding: 1.1rem 0.7rem;
        vertical-align: middle;
        font-size: 1.08rem;
        box-shadow: 0 2px 8px 0 rgba(31, 38, 135, 0.04);
    }
    .activity-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1 0%, #06d6a0 100%);
        color: #fff;
        font-size: 1.2rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.8rem;
    }
    .activity-badge {
        background: #e6f9f0;
        color: #06d6a0;
        border-radius: 999px;
        padding: 0.4rem 1.1rem;
        font-size: 1rem;
        font-weight: 600;
        border: none;
        display: inline-block;
    }
    .activity-badge.created { background: #e6f9f0; color: #06d6a0; }
    .activity-badge.updated { background: #e0e7ff; color: #6366f1; }
    .activity-badge.deleted { background: #ffe6e6; color: #f87171; }
    .activity-network {
        background: #f7f8fd;
        border-radius: 8px;
        padding: 0.3rem 0.9rem;
        font-size: 0.98rem;
        color: #222;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid #e5e7eb;
    }
    .activity-table-action {
        background: linear-gradient(90deg, #6366f1 0%, #a855f7 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1.3rem;
        font-weight: 600;
        font-size: 1rem;
        box-shadow: 0 2px 8px 0 rgba(99,102,241,0.08);
        transition: box-shadow 0.2s;
        text-decoration: none !important;
    }
    .activity-table-action:hover {
        box-shadow: 0 4px 16px 0 rgba(99,102,241,0.18);
        color: #fff;
    }
    .activity-table-row {
        transition: background 0.2s;
    }
    .activity-table-row:hover {
        background: #f7f8fd;
    }
    .activity-table-empty {
        text-align: center;
        color: #b0b3c6;
        padding: 2.5rem 0;
        font-size: 1.2rem;
    }
    @media (max-width: 900px) {
        .activity-table-card { padding: 1.2rem 0.7rem 1rem 0.7rem; }
        .activity-table th, .activity-table td { font-size: 0.98rem; }
    }
</style>
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'activityLogsPageSkeleton',
        'columns' => 6,
        'rows' => 10,
        'filterFields' => 3,
    ])

     <form method="GET" action="" class="mb-4">
            <div class="card shadow-sm p-4 mb-3" style="border-radius: 18px;">
                <div class="mb-3 fw-bold fs-5 d-flex align-items-center gap-2"><i class="bi bi-funnel text-primary"></i> Audit Filters</div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-1"><i class="bi bi-search me-1"></i>Search Query</label>
                        <input type="text" name="user_name" value="{{ request('user_name') }}" class="form-control" placeholder="Search activity/User name/Email/Type/Category/Module...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1"><i class="bi bi-tag me-1"></i>Log Category</label>
                        <select name="module" class="form-select">
                            <option value="">All Categories</option>
                            @foreach(\App\Models\ActivityLog::query()->distinct()->pluck('module')->filter()->sort()->unique() as $cat)
                                <option value="{{ $cat }}" {{ request('module') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1"><i class="bi bi-lightning-charge me-1"></i>Event Type</label>
                        <select name="action" class="form-select">
                            <option value="">All Events</option>
                            @foreach(\App\Models\ActivityLog::query()->distinct()->pluck('action')->filter()->sort()->unique() as $eventType)
                                <option value="{{ $eventType }}" {{ request('action') == $eventType ? 'selected' : '' }}>{{ ucfirst($eventType) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1"><i class="bi bi-person me-1"></i>User</label>
                        <select name="user_id" class="form-select">
                            <option value="">All Users</option>
                            @foreach(\App\Models\User::orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1"><i class="bi bi-calendar me-1"></i>From Date</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="form-control" placeholder="dd-mm-yyyy">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1"><i class="bi bi-calendar me-1"></i>To Date</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="form-control" placeholder="dd-mm-yyyy">
                    </div>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-custom" style="padding: 6px 20px !important;"><i class="bi bi-funnel"></i> Apply Filters</button>
                    <!-- <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary px-4">Clear Filters</a>
                    <button type="button" class="btn btn-success px-4"><i class="bi bi-download"></i> Export CSV</button>
                    <button type="button" class="btn btn-danger px-4" style="box-shadow:0 0 12px 0 #f87171a8;"><i class="bi bi-trash"></i> Cleanup Old Logs</button> -->
                </div>
            </div>
        </form>
    <div class="activity-table-card">
        <div class="activity-table-header mb-3"><span class="icon"><i class="bi bi-list-task"></i></span> Audit Logs</div>
        
        <div class="table-responsive mb-3">
            <table class="activity-table">
                <thead>
                    <tr>
                        <th style="width:18%;"><i class="bi bi-activity me-1"></i> Activity</th>
                        <th style="width:18%;"><i class="bi bi-person-circle me-1"></i> User</th>
                        <th style="width:12%;"><i class="bi bi-tag me-1"></i> Type</th>
                        <th style="width:12%;"><i class="bi bi-diagram-3 me-1"></i> IP Address</th>
                        <th style="width:12%;"><i class="bi bi-device-hdd me-1"></i> Device</th>
                        <th style="width:16%;"><i class="bi bi-clock-history me-1"></i> Timestamp</th>
                        <th style="width:12%;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="activity-table-row">
                            <td data-label="Activity">
                                <div class="d-flex align-items-center">
                                    <span class="activity-avatar me-2"><i class="bi bi-plus-lg"></i></span>
                                    <div>
                                        <div class="fw-semibold">{{ ucfirst($log->module) }} {{ strtolower($log->action) }}</div>
                                        <div class="text-muted small">{{ $log->module }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="User">
                                <div class="d-flex align-items-center">
                                    <span class="activity-avatar" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); font-size:1.1rem;">{{ $log->user ? strtoupper(substr($log->user->name,0,1)) : '?' }}</span>
                                    <div>
                                        <div class="fw-semibold">{{ $log->user ? $log->user->name : 'Unknown' }}</div>
                                        <div class="text-muted small">{{ $log->user ? $log->user->email : '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Type">
                                <span class="activity-badge {{ strtolower($log->action) }}">{{ ucfirst($log->action) }}</span>
                            </td>
                            <td data-label="IP Address">
                                <span class="activity-network"><span class="small"><i class="bi bi-geo-alt text-muted me-1"></i>{{ $log->ip_address }}</span>
                            </span>
                            </td>
                            <td data-label="Device"><span class="activity-network"><span class="small"><i class="bi bi-laptop text-muted me-1"></i>{{ Str::limit($log->device, 40) }}</span></td>
                            <td data-label="Timestamp">
                                <div class="fw-semibold">{{ $log->created_at->format('M d, Y') }}</div>
                                <div class="text-muted small">{{ $log->created_at->format('H:i:s') }}<br>{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td data-label="Action">
                                <a href="{{ route('activity-logs.show', $log->id) }}" class="activity-table-action btn btn-primary btn-sm w-100 w-md-auto mt-2 mt-md-0 d-block d-md-inline-block"><i class="bi bi-search"></i> View Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="activity-table-empty"><i class="bi bi-emoji-frown"></i> No activity logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="small text-muted">Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} logs</div>
            <div>{{ $logs->links('vendor.pagination.arrows-only') }}</div>
        </div>
    </div>
</div>
@endsection
