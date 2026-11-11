@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'last_week';
    $exportUrl = route('reports.weekly_activity_excel', request()->only('period', 'start_date', 'end_date', 'type', 'user_id'));
@endphp

<x-report-shell
    title="Weekly Activity Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :export-url="$exportUrl"
    :form-action="route('reports.weekly_activity')"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="type">Type</label>
            <select name="type" id="type" class="form-select">
                <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Leads & Deals</option>
                <option value="lead" {{ $type === 'lead' ? 'selected' : '' }}>Leads Only</option>
                <option value="deal" {{ $type === 'deal' ? 'selected' : '' }}>Deals Only</option>
            </select>
        </div>
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="">All Users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string) $user_id === (string) $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <style>
        .activity-block { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-top: 8px; }
        .activity-block h6 { font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #374151; }
        .entity-badge-lead { background: #dbeafe; color: #1e40af; }
        .entity-badge-deal { background: #dcfce7; color: #166534; }
    </style>

    @if($records->isEmpty())
        <p class="text-muted mb-0 px-3">No lead/deal activities found for the selected period.</p>
    @else
        <div class="accordion px-3 pb-3" id="weeklyActivityAccordion">
            @foreach($records as $index => $row)
                @php
                    $collapseId = 'activity-' . $row->entity_type . '-' . $row->id;
                    $notesCount = $row->notes->count();
                    $meetingsCount = $row->meetings->count();
                    $tasksCount = $row->tasks->count();
                    $callsCount = isset($row->calls) ? $row->calls->count() : 0;
                    $totalActivities = $notesCount + $meetingsCount + $tasksCount + $callsCount;
                @endphp
                <div class="accordion-item mb-2 border rounded">
                    <h2 class="accordion-header" id="heading-{{ $collapseId }}">
                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                            <div class="w-100 d-flex flex-wrap gap-3 align-items-center pe-3">
                                <span class="badge {{ $row->entity_type === 'lead' ? 'entity-badge-lead' : 'entity-badge-deal' }}">
                                    {{ $row->entity_type === 'lead' ? 'Lead' : 'Deal' }}
                                </span>
                                <strong>
                                    @if($row->entity_type === 'lead')
                                        <a href="{{ url('/leads/' . $row->id) }}" target="_blank" onclick="event.stopPropagation();">{{ $row->title ?: '-' }}</a>
                                    @else
                                        <a href="{{ url('/deals/' . $row->id) }}" target="_blank" onclick="event.stopPropagation();">{{ $row->title ?: '-' }}</a>
                                    @endif
                                </strong>
                                <span class="text-muted">Status: <strong>{{ $row->status ?: '-' }}</strong></span>
                                @if($row->stage)
                                    <span class="text-muted">Stage: <strong>{{ $row->stage }}</strong></span>
                                @endif
                                <span class="text-muted">{{ $row->entity_type === 'lead' ? 'Lead Owner' : 'Deal Owner' }}: <strong>{{ $row->owner_name ?: '-' }}</strong></span>
                                <span class="text-muted">Amount:
                                    <strong>
                                        @if(is_numeric($row->amount))
                                            @money($row->amount)
                                        @else
                                            {{ $row->amount ?: '-' }}
                                        @endif
                                    </strong>
                                </span>
                                <span class="badge bg-secondary ms-auto">{{ $totalActivities }} activities</span>
                            </div>
                        </button>
                    </h2>
                    <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading-{{ $collapseId }}" data-bs-parent="#weeklyActivityAccordion">
                        <div class="accordion-body">
                            <div class="activity-block">
                                <h6>Notes ({{ $notesCount }})</h6>
                                @if($notesCount)
                                    <div class="report-table-wrap">
                                        <table class="report-table">
                                            <thead>
                                                <tr><th>Note</th><th>Created By</th><th>Created At</th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach($row->notes as $note)
                                                    <tr>
                                                        <td data-label="Note">{{ \Illuminate\Support\Str::limit(strip_tags($note->content), 200) }}</td>
                                                        <td data-label="Created By">{{ $userNames[$note->created_by] ?? '-' }}</td>
                                                        <td data-label="Created At">{{ $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d-m-Y h:i A') : '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">No notes in this period.</p>
                                @endif
                            </div>

                            <div class="activity-block">
                                <h6>Meetings ({{ $meetingsCount }})</h6>
                                @if($meetingsCount)
                                    <div class="report-table-wrap">
                                        <table class="report-table">
                                            <thead>
                                                <tr><th>Subject</th><th>Start</th><th>End</th><th>Venue</th><th>Owner</th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach($row->meetings as $meeting)
                                                    <tr>
                                                        <td data-label="Subject">{{ $meeting->name ?: '-' }}</td>
                                                        <td data-label="Start">{{ $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('d-m-Y h:i A') : '-' }}</td>
                                                        <td data-label="End">{{ $meeting->finish_at ? \Carbon\Carbon::parse($meeting->finish_at)->format('d-m-Y h:i A') : '-' }}</td>
                                                        <td data-label="Venue">{{ $meeting->venue ?: ($meeting->location ?? '-') }}</td>
                                                        <td data-label="Owner">{{ $userNames[$meeting->user_owner_id] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">No meetings in this period.</p>
                                @endif
                            </div>

                            <div class="activity-block">
                                <h6>Tasks ({{ $tasksCount }})</h6>
                                @if($tasksCount)
                                    <div class="report-table-wrap">
                                        <table class="report-table">
                                            <thead>
                                                <tr><th>Task</th><th>Status</th><th>Priority</th><th>Due Date</th><th>Owner</th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach($row->tasks as $task)
                                                    @php
                                                        $status = $task->status ?? '-';
                                                        $stageColors = [
                                                            'In Progress' => 'bg-primary',
                                                            'Completed' => 'bg-success-subtle text-success',
                                                            'Not Started' => 'bg-danger-subtle text-danger',
                                                            'Waiting for input' => 'bg-warning-subtle text-warning',
                                                            'Deferred' => 'bg-secondary',
                                                        ];
                                                        $color = $stageColors[$status] ?? 'bg-secondary';
                                                    @endphp
                                                    <tr>
                                                        <td data-label="Task">{{ $task->name ?: '-' }}</td>
                                                        <td data-label="Status"><span class="badge {{ $color }}">{{ $status }}</span></td>
                                                        <td data-label="Priority">{{ $task->priority ?: '-' }}</td>
                                                        <td data-label="Due Date">{{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('d-m-Y h:i A') : '-' }}</td>
                                                        <td data-label="Owner">{{ $userNames[$task->user_owner_id] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">No tasks in this period.</p>
                                @endif
                            </div>

                            <div class="activity-block">
                                <h6>Calls ({{ $callsCount }})</h6>
                                @if($callsCount)
                                    <div class="report-table-wrap">
                                        <table class="report-table">
                                            <thead>
                                                <tr><th>Subject</th><th>Start</th><th>End</th><th>Location</th><th>Owner</th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach($row->calls as $call)
                                                    <tr>
                                                        <td data-label="Subject">{{ $call->name ?: '-' }}</td>
                                                        <td data-label="Start">{{ $call->start_at ? \Carbon\Carbon::parse($call->start_at)->format('d-m-Y h:i A') : '-' }}</td>
                                                        <td data-label="End">{{ $call->finish_at ? \Carbon\Carbon::parse($call->finish_at)->format('d-m-Y h:i A') : '-' }}</td>
                                                        <td data-label="Location">{{ $call->location ?: '-' }}</td>
                                                        <td data-label="Owner">{{ $userNames[$call->user_owner_id] ?? ($userNames[$call->created_by] ?? '-') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">No calls in this period.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-report-shell>
@endsection
