@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">
@include('reports.partials.styles')
<div class="container-fluid p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold">Daily Reports</h4>
            <small class="text-muted">Submit and review your daily CRM activity</small>
        </div>
        <a href="{{ route('daily_reports.create') }}" class="btn btn-custom btn-sm">
            <i class="bi bi-plus-lg"></i> Enter Daily Report
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" id="reportFilterForm" action="{{ route('daily_reports.index') }}" class="dr-toolbar">
                <div class="dr-field">
                    @include('reports.partials.date_range', [
                        'period' => $period ?? 'this_month',
                        'start' => ($period ?? '') === 'custom' ? ($start ?? '') : '',
                        'end' => ($period ?? '') === 'custom' ? ($end ?? '') : '',
                        'periodKeys' => ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'custom'],
                    ])
                </div>
                @if(!empty($isAdmin))
                    <div class="dr-field dr-field-user">
                        <label for="user_id" class="dr-label">User</label>
                        <select name="user_id" id="user_id" class="form-select dr-control">
                            <option value="">All Users</option>
                            @foreach($users ?? [] as $u)
                                <option value="{{ $u->id }}" {{ (int) ($selectedUserId ?? 0) === (int) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <input type="hidden" name="per_page" value="{{ (int) ($perPage ?? 25) }}">
                <div class="dr-actions">
                    <button class="dr-btn dr-btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('daily_reports.index') }}" class="dr-btn dr-btn-light"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
                <div class="dr-actions dr-actions-export">
                    <a href="{{ route('daily_reports.export_excel', $exportParams ?? []) }}" class="dr-btn dr-btn-excel">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                    <a href="{{ route('daily_reports.export_pdf', $exportParams ?? []) }}" class="dr-btn dr-btn-pdf">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </form>

            <style>
                .dr-toolbar {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: flex-end;
                    gap: 14px;
                    padding: 14px 16px;
                    margin-bottom: 16px;
                    background: #f8fafc;
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                }
                .dr-toolbar .dr-field { display: flex; flex-direction: column; gap: 6px; }
                .dr-toolbar .dr-field-user { min-width: 230px; }
                .dr-toolbar .dr-label,
                .dr-toolbar .drp-wrap .drp-label {
                    margin: 0;
                    font-size: 12px;
                    font-weight: 600;
                    color: #64748b;
                    letter-spacing: 0.02em;
                    line-height: 1.2;
                }
                .dr-toolbar .drp-wrap { gap: 6px; }
                .dr-toolbar .drp-trigger,
                .dr-toolbar .dr-control {
                    height: 38px;
                    min-width: 230px;
                    padding: 0 12px;
                    font-size: 14px;
                    border: 1px solid #d1d5db;
                    border-radius: 8px;
                    background-color: #fff;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
                }
                .dr-toolbar .dr-control { padding-right: 34px; }
                .dr-toolbar .drp-trigger:focus,
                .dr-toolbar .dr-control:focus {
                    border-color: #6366f1;
                    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
                    outline: none;
                }
                .dr-toolbar .dr-actions { display: flex; gap: 8px; }
                .dr-toolbar .dr-actions-export { margin-left: auto; }
                .dr-toolbar .dr-btn {
                    height: 38px;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 0 16px;
                    font-size: 14px;
                    font-weight: 600;
                    border-radius: 8px;
                    border: 1px solid transparent;
                    text-decoration: none;
                    cursor: pointer;
                    white-space: nowrap;
                    transition: background-color .15s, border-color .15s, color .15s;
                }
                .dr-toolbar .dr-btn-primary { background: #5b3cc4; border-color: #5b3cc4; color: #fff; }
                .dr-toolbar .dr-btn-primary:hover { background: #4a2fa8; border-color: #4a2fa8; }
                .dr-toolbar .dr-btn-light { background: #fff; border-color: #d1d5db; color: #374151; }
                .dr-toolbar .dr-btn-light:hover { background: #f3f4f6; }
                .dr-toolbar .dr-btn-excel { background: #fff; border-color: #16a34a; color: #15803d; }
                .dr-toolbar .dr-btn-excel:hover { background: #16a34a; color: #fff; }
                .dr-toolbar .dr-btn-pdf { background: #fff; border-color: #dc2626; color: #b91c1c; }
                .dr-toolbar .dr-btn-pdf:hover { background: #dc2626; color: #fff; }
                @media (max-width: 768px) {
                    .dr-toolbar .dr-field,
                    .dr-toolbar .dr-field-user,
                    .dr-toolbar .drp-wrap,
                    .dr-toolbar .drp-trigger,
                    .dr-toolbar .dr-control { width: 100%; min-width: 0; }
                    .dr-toolbar .dr-actions-export { margin-left: 0; }
                }
            </style>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-nowrap" style="width: 1%;">Date</th>
                            <th>User</th>
                            <th>Outreaches</th>
                            <th>Other activity</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td class="text-nowrap">{{ optional($report->report_date)->format('d M Y') }}</td>
                                <td>{{ optional($report->user)->name }}</td>
                                <td>
                                    @foreach($report->outreaches as $o)
                                        <span class="badge bg-light text-dark border me-1 mb-1">
                                            {{ optional($o->outreachType)->name }}: {{ $o->count }}
                                        </span>
                                    @endforeach
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit(strip_tags((string)$report->other_activity), 60) }}</td>
                                <td>
                                    @if($report->status === 'draft')
                                        <span class="badge bg-warning text-dark">Draft</span>
                                    @else
                                        <span class="badge bg-success">Submitted</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('daily_reports.show', $report->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    @if($report->status === 'draft' && optional($report->report_date)->isSameDay(\Carbon\Carbon::today()))
                                        <a href="{{ route('daily_reports.edit', $report->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No daily reports found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reports->total() > 0)
                @php
                    $current = $reports->currentPage();
                    $last = $reports->lastPage();
                    $window = 2;
                    $pages = [];
                    for ($p = 1; $p <= $last; $p++) {
                        if ($p === 1 || $p === $last || abs($p - $current) <= $window) {
                            $pages[] = $p;
                        } elseif (end($pages) !== '...') {
                            $pages[] = '...';
                        }
                    }
                @endphp
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2 small text-muted">
                            <label for="per_page" class="mb-0">Show</label>
                            <select id="per_page" class="form-select form-select-sm" style="width:auto;">
                                @foreach([25, 50, 100] as $size)
                                    <option value="{{ $size }}" {{ (int) ($perPage ?? 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                            <span>per page</span>
                        </div>
                        <div class="small text-muted">
                            Showing {{ $reports->firstItem() }} to {{ $reports->lastItem() }} of {{ $reports->total() }} results
                        </div>
                    </div>
                    @if($reports->hasPages())
                        <nav aria-label="Daily reports pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item {{ $reports->onFirstPage() ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $reports->previousPageUrl() ?: '#' }}">&laquo; Previous</a>
                                </li>
                                @foreach($pages as $p)
                                    @if($p === '...')
                                        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                    @else
                                        <li class="page-item {{ $p === $current ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $reports->url($p) }}">{{ $p }}</a>
                                        </li>
                                    @endif
                                @endforeach
                                <li class="page-item {{ $reports->hasMorePages() ? '' : 'disabled' }}">
                                    <a class="page-link" href="{{ $reports->nextPageUrl() ?: '#' }}">Next &raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var perPage = document.getElementById('per_page');
    if (!perPage) return;
    perPage.addEventListener('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage.value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });
});
</script>
@push('scripts')
    @include('reports.partials.date_range_js')
@endpush
@endsection
