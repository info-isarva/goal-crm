@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
@endphp

<x-report-shell
    title="Task Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :form-action="route('reports.task')"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="filter">Status</label>
            <select name="filter" id="filter" class="form-select">
                <option value="overdue" {{ request('filter', 'overdue') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                <option value="today" {{ request('filter') == 'today' ? 'selected' : '' }}>Today</option>
                <option value="upcoming" {{ request('filter') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                <option value="completed" {{ request('filter') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
        <div class="toolbar-field">
            <label for="assigned_to">Assigned To</label>
            <select name="assigned_to" id="assigned_to" class="form-select">
                <option value="">All Users</option>
                @foreach($assignUsers ?? [] as $user)
                    <option value="{{ $user->id }}" {{ (string) ($assignedTo ?? '') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    @if(isset($tasks) && !$tasks->isEmpty())
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Task Name</th>
                        <th>Organization</th>
                        <th>Type</th>
                        <th>Created By</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                        <tr>
                            <td data-label="Task Name">{{ $task->name }}</td>
                            <td data-label="Organization">{{ $task->organization_name }}</td>
                            <td data-label="Type">{{ ($task->related_type === 'deal') ? 'Deals' : (($task->related_type === 'lead') ? 'Leads' : 'Other') }}</td>
                            <td data-label="Created By">{{ \App\Models\User::find($task->user_owner_id)->name ?? 'Unknown' }}</td>
                            <td data-label="Assigned To">{{ \App\Models\User::find($task->user_assigned_id)->name ?? 'Unknown' }}</td>
                            <td data-label="Due Date">{{ \Carbon\Carbon::parse($task->due_at)->format('d-m-Y') }}</td>
                            <td data-label="Status">
                                @php
                                    $status = $task->status ?? '-';
                                    $stageColors = [
                                        'In Progress' => 'bg-primary',
                                        'Completed' => 'bg-success-subtle text-success',
                                        'Not Started' => 'bg-danger-subtle text-danger',
                                        'Waiting for input' => 'bg-warning-subtle text-warning',
                                    ];
                                    $color = $stageColors[$status] ?? 'bg-secondary';
                                @endphp
                                <span class="badge {{ $color }}">{{ ucfirst($status) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('reports.partials.pagination', ['paginator' => $tasks, 'noun' => 'tasks'])
    @else
        <p class="report-empty px-3">No tasks found for the selected criteria.</p>
    @endif
</x-report-shell>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.report-table tbody tr').forEach(function(row) {
        row.querySelectorAll('td').forEach(function(cell, index) {
            var header = document.querySelector('.report-table thead th:nth-child(' + (index + 1) + ')');
            if (header) {
                cell.setAttribute('data-label', header.textContent.trim());
            }
        });
    });
});
</script>
@endpush
