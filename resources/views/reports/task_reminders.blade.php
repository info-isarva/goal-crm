@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'today';
@endphp

<x-report-shell
    title="Reminder Reports"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? ($startDate ?? null)"
    :end="$end ?? ($endDate ?? null)"
    :form-action="route('task-reports.index')"
>
    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Company</th>
                    <th>Contact Person</th>
                    <th>Owner</th>
                    <th>Due Date</th>
                    <th>Reminder Time</th>
                    <th>Email Sent</th>
                    <th>Notification Sent</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    @foreach($task->reminders as $reminder)
                        <tr>
                            <td data-label="Task">{{ $task->name }}</td>
                            @php
                                $contact = null;
                                $organization = null;
                                if ($task->related_type === 'lead') {
                                    $lead = \App\Models\Lead::find($task->related_id);
                                    $organization = $lead ? $lead->organization->name : null;
                                    $contact = $lead && $lead->person ? $lead->person->first_name . ' ' . $lead->person->last_name : '';
                                } elseif ($task->related_type === 'deal') {
                                    $deal = \App\Models\Deal::find($task->related_id);
                                    $organization = $deal ? $deal->organization->name : null;
                                    $contact = $deal && $deal->person ? $deal->person->first_name . ' ' . $deal->person->last_name : '';
                                }
                            @endphp
                            <td data-label="Company">{{ $organization ?? 'N/A' }}</td>
                            <td data-label="Contact Person">{{ $contact ?? '' }}</td>
                            <td data-label="Owner">{{ $task->owner->name ?? 'N/A' }}</td>
                            <td data-label="Due Date">{{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('d-m-Y H:i') : 'N/A' }}</td>
                            <td data-label="Reminder Time">{{ $reminder->remind_at ? \Carbon\Carbon::parse($reminder->remind_at)->format('d-m-Y H:i') : 'N/A' }}</td>
                            <td data-label="Email Sent">
                                @if($reminder->email_sent)
                                    <i class="fas fa-check-circle text-success"></i>
                                @else
                                    <i class="fas fa-times-circle text-danger"></i>
                                @endif
                            </td>
                            <td data-label="Notification Sent">
                                @if($reminder->notification_sent)
                                    <i class="fas fa-check-circle text-success"></i>
                                @else
                                    <i class="fas fa-times-circle text-danger"></i>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="report-empty">No reminders found for the selected date range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('reports.partials.pagination', ['paginator' => $tasks ?? null, 'noun' => 'reminders'])
</x-report-shell>
@endsection
