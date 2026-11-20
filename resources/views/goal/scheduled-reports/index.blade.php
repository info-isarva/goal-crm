@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
<style>
.sr-page .sr-hero{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem}
.sr-page .sr-hero-left{display:flex;align-items:flex-start;gap:12px;min-width:0}
.sr-page .sr-ico{
    width:44px;height:44px;border-radius:12px;background:var(--theme-primary);color:#fff;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.2rem;flex:0 0 auto
}
.sr-page .sr-title{margin:0;font-size:1.4rem;font-weight:800;color:#0f172a}
.sr-page .sr-sub{margin:.3rem 0 0;color:#64748b;font-size:.86rem}
.sr-page .sr-actions{display:flex;flex-wrap:wrap;gap:8px}
.sr-page .sr-card{
    background:#fff;border:1px solid #e8edf5;border-radius:14px;
    box-shadow:0 4px 14px rgba(148,163,184,.08);overflow:hidden
}
.sr-page .sr-table{width:100%;margin:0;border-collapse:separate;border-spacing:0}
.sr-page .sr-table th{
    font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.03em;
    background:#f8fafc;padding:.7rem .85rem;border-bottom:1px solid #eef2f7;white-space:nowrap
}
.sr-page .sr-table td{
    padding:.75rem .85rem;border-bottom:1px solid #f1f5f9;font-size:.84rem;vertical-align:middle;color:#0f172a
}
.sr-page .sr-table tr:last-child td{border-bottom:0}
.sr-page .sr-name{font-weight:750}
.sr-page .sr-muted{color:#64748b;font-size:.75rem;margin-top:2px}
.sr-page .sr-pill{
    display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:999px;
    font-size:.7rem;font-weight:800
}
.sr-page .sr-pill.active{background:#ecfdf5;color:#047857}
.sr-page .sr-pill.paused{background:#f1f5f9;color:#64748b}
.sr-page .sr-pill.failed{background:#fef2f2;color:#dc2626}
.sr-page .sr-act{display:flex;flex-wrap:wrap;gap:6px}
.sr-page .sr-act .btn{height:32px;padding:0 10px;font-size:.75rem;font-weight:700;border-radius:8px}
.sr-page .sr-empty{padding:2.5rem 1rem;text-align:center;color:#94a3b8}
@media (max-width:991.98px){
    .sr-page .sr-table-wrap{overflow-x:auto}
    .sr-page .sr-table{min-width:920px}
}
</style>
@endpush

@section('content')
<div class="container-fluid p-4 pd-page sr-page">
    <div class="sr-hero">
        <div class="sr-hero-left">
            <span class="sr-ico"><i class="bi bi-calendar2-check"></i></span>
            <div>
                <h1 class="sr-title">Scheduled Reports</h1>
                <p class="sr-sub">Manage automated analytics report delivery. Each schedule uses your role-based data permissions.</p>
            </div>
        </div>
        <div class="sr-actions">
            <a href="{{ route('goal.analytics') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-bar-chart"></i> Analytics Dashboard
            </a>
            <a href="{{ route('goal.analytics') }}#schedule" class="btn btn-success btn-sm"
               onclick="sessionStorage.setItem('gaOpenSchedule','1')">
                <i class="bi bi-plus-lg"></i> New Schedule
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
            @if(str_contains(strtolower(session('error')), 'smtp') || str_contains(strtolower(session('error')), 'email'))
                <div class="mt-2">
                    <a href="{{ route('email.settings.edit') }}" class="alert-link">Open Email Settings</a>
                </div>
            @endif
        </div>
    @endif

    @php
        $hasMailFail = $schedules->contains(fn ($s) => $s->last_error && (
            str_contains(strtolower($s->last_error), 'smtp')
            || str_contains(strtolower($s->last_error), 'authentication')
            || str_contains(strtolower($s->last_error), '535')
        ));
    @endphp
    @if($hasMailFail && !session('error'))
        <div class="alert alert-warning">
            One or more schedules failed to send email due to SMTP authentication.
            Update credentials in <a href="{{ route('email.settings.edit') }}" class="alert-link">Email Settings</a>,
            then Resume and click Run Now.
        </div>
    @endif

    <div class="sr-card">
        <div class="sr-table-wrap">
            <table class="sr-table">
                <thead>
                    <tr>
                        <th>Schedule Name</th>
                        <th>Report Type</th>
                        <th>Frequency</th>
                        <th>Next Run</th>
                        <th>Recipients</th>
                        <th>Last Run</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $s)
                        <tr>
                            <td>
                                <div class="sr-name">{{ $s->name }}</div>
                                <div class="sr-muted">{{ $s->formatLabel() }} · {{ $s->runs_count }} run(s)</div>
                            </td>
                            <td>{{ $s->typeLabel() }}</td>
                            <td>{{ $s->frequencyLabel() }} @ {{ \Carbon\Carbon::createFromFormat('H:i', $s->run_time)->format('h:i A') }}</td>
                            <td>{{ $s->next_run_at ? $s->next_run_at->format('d M Y, h:i A') : '—' }}</td>
                            <td>
                                <div>{{ count($s->recipientsList()) }} email(s)</div>
                                <div class="sr-muted">{{ \Illuminate\Support\Str::limit(implode(', ', $s->recipientsList()), 42) }}</div>
                            </td>
                            <td>
                                {{ $s->last_run_at ? $s->last_run_at->format('d M Y, h:i A') : '—' }}
                                @if($s->last_error)
                                    <div class="sr-muted text-danger" title="{{ $s->last_error }}">
                                        {{ \Illuminate\Support\Str::limit($s->last_error, 72) }}
                                    </div>
                                @endif
                            </td>
                            <td><span class="sr-pill {{ $s->status }}">{{ $s->statusLabel() }}</span></td>
                            <td>
                                <div class="sr-act">
                                    @if(in_array($s->status, ['paused', 'failed'], true))
                                        <form method="POST" action="{{ route('goal.scheduled-reports.resume', $s) }}">
                                            @csrf
                                            <button class="btn btn-outline-success" type="submit">Resume</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('goal.scheduled-reports.pause', $s) }}">
                                            @csrf
                                            <button class="btn btn-outline-secondary" type="submit">Pause</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('goal.scheduled-reports.run', $s) }}">
                                        @csrf
                                        <button class="btn btn-outline-primary" type="submit">Run Now</button>
                                    </form>
                                    <form method="POST" action="{{ route('goal.scheduled-reports.destroy', $s) }}"
                                          onsubmit="return confirm('Delete this schedule?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="sr-empty">
                                    <i class="bi bi-calendar2-x" style="font-size:1.6rem;display:block;margin-bottom:.5rem"></i>
                                    No scheduled reports yet. Create one from the Analytics Dashboard.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($schedules->hasPages())
            <div class="p-3">{{ $schedules->links() }}</div>
        @endif
    </div>
</div>
@endsection
