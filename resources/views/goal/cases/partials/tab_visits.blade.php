@php
    $visitTasks = $case->tasks
        ->whereIn('task_type', ['visit', 'followup'])
        ->sortByDesc(fn ($t) => $t->due_at?->timestamp ?? 0)
        ->values();
    $statusClass = fn ($s) => match ($s) {
        'Completed' => 'ok',
        'Overdue' => 'bad',
        'Upcoming' => 'warn',
        default => 'info',
    };
@endphp

<div class="sec-card">
    <div class="sec-head has-line">
        <div class="sec-head-left">
            <span class="sec-ico ico-teal"><i class="bi bi-geo-alt"></i></span>
            <div>
                <h6>Visits &amp; Follow-ups</h6>
                <div class="sec-sub">Customer interaction history for this case</div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('goal.visits.index', ['q' => $case->case_no]) }}" class="btn-ghost"><i class="bi bi-grid"></i> Visits hub</a>
            @if($canEdit)
                <a href="{{ route('goal.visits.index') }}#schedule" class="btn-primary-cw"
                   onclick="sessionStorage.setItem('visPrefillCase','{{ $case->id }}')">
                    <i class="bi bi-plus-lg"></i> Schedule
                </a>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Scheduled</th>
                    <th>Assigned</th>
                    <th>Status</th>
                    <th>Outcome / Notes</th>
                </tr>
            </thead>
            <tbody>
            @forelse($visitTasks as $task)
                @php $st = $task->displayStatus(); @endphp
                <tr>
                    <td class="fw-semibold">{{ $task->typeLabel() }}</td>
                    <td>{{ optional($task->due_at)->format('d M Y, h:i A') ?: '—' }}</td>
                    <td>{{ $task->assignee?->name ?: '—' }}</td>
                    <td><span class="doc-badge {{ $statusClass($st) }}">{{ $st }}</span></td>
                    <td class="small">
                        @if($task->outcome)<div class="fw-semibold">{{ $task->outcome }}</div>@endif
                        {{ \Illuminate\Support\Str::limit($task->notes, 80) ?: '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        No visits or follow-ups yet.
                        @if($canEdit)
                            <div class="mt-2">
                                <a href="{{ route('goal.visits.index') }}" class="btn-ghost" style="height:34px;padding:0 12px">Schedule from Visits hub</a>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
