@extends('layouts.app')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/leads.css') }}">
    <style>
        /* Custom green checkbox with checkmark */
        .custom-checkbox {
            position: relative;
            width: 22px;
            height: 22px;
        }

        .custom-checkbox input[type="checkbox"] {
            opacity: 0;
            width: 22px;
            height: 22px;
            margin: 0;
            cursor: pointer;
            position: absolute;
            left: 0;
            top: 0;
        }

        .custom-checkbox,
        .custom-checkbox .checkmark {
            cursor: pointer;
        }

        .custom-checkbox .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 22px;
            width: 22px;
            background-color: #fff;
            border: 2px solid #28a745;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .custom-checkbox input[type="checkbox"]:checked~.checkmark {
            background-color: #28a745;
        }

        .custom-checkbox .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .custom-checkbox input[type="checkbox"]:checked~.checkmark:after {
            display: block;
        }

        /* Always show checkmark, color depends on checked state */
        .custom-checkbox .checkmark:after {
            left: 6px;
            top: 2px;
            width: 6px;
            height: 12px;
            border: solid #bbb;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg);
            content: "";
            display: block;
            transition: border-color 0.2s;
        }

        .custom-checkbox input[type="checkbox"]:checked~.checkmark:after {
            border-color: #fff;
        }
    </style>
    <div class="container-fluid p-4 px-md-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'tasksPageSkeleton',
        'columns' => 7,
        'rows' => 10,
        'filterFields' => 3,
    ])

        <div class="card mt-0 shadow-sm">
            <div class="card-header">
                <div class="row g-2 align-items-center org-header">
                    <div class="col-12 col-md-4 d-flex align-items-center org-left" style="gap:12px;">
                        <h4 class="mb-2 mb-md-0">Tasks</h4>
                    </div>
                    <div class="col-12 col-md-8">
                        <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                            <div class="dropdown" style="min-width:140px;">
                                <select class="form-select" id="filterDropdown" onchange="location.href=this.value">
                                    <option
                                        value="{{ route('tasks.index', array_merge(request()->except(['assigned_to']), ['filter' => 'all'])) }}"
                                        {{ request('filter') === 'all' ? 'selected' : '' }}>All Tasks</option>
                                    <option
                                        value="{{ route('tasks.index', array_merge(request()->except(['assigned_to']), ['filter' => 'deals'])) }}"
                                        {{ request('filter') === 'deals' ? 'selected' : '' }}>Deals Tasks</option>
                                    <option
                                        value="{{ route('tasks.index', array_merge(request()->except(['assigned_to']), ['filter' => 'leads'])) }}"
                                        {{ request('filter') === 'leads' ? 'selected' : '' }}>Leads Tasks</option>
                                </select>
                            </div>
                            <div class="dropdown" style="min-width:180px;">
                                <select class="form-select" id="assignedToFilter" onchange="onAssignedToFilterChange()">
                                    <option value="">-- Assigned To --</option>
                                    @foreach (\App\Models\User::all() as $user)
                                        <option value="{{ $user->id }}"
                                            {{ request('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <script>
                                function onAssignedToFilterChange() {
                                    const assignedTo = document.getElementById('assignedToFilter').value;
                                    const url = new URL(window.location.href);
                                    if (assignedTo) {
                                        url.searchParams.set('assigned_to', assignedTo);
                                    } else {
                                        url.searchParams.delete('assigned_to');
                                    }
                                    window.location.href = url.toString();
                                }
                            </script>
                            <button type="button" class="btn btn-custom" data-bs-toggle="modal"
                                data-bs-target="#createTaskModal">
                                Add Task
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Tabs navigation -->
                <ul class="nav nav-tabs mb-3" id="taskTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overdue-tab" data-bs-toggle="tab" data-bs-target="#overdue"
                            type="button" role="tab" aria-controls="overdue" aria-selected="true">Overdue
                            ({{ $totalCounts['overdue'] }})</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="today-tab" data-bs-toggle="tab" data-bs-target="#today" type="button"
                            role="tab" aria-controls="today" aria-selected="false">Today
                            ({{ $totalCounts['today'] }})</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming"
                            type="button" role="tab" aria-controls="upcoming" aria-selected="false">Upcoming
                            ({{ $totalCounts['upcoming'] }})</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed"
                            type="button" role="tab" aria-controls="completed" aria-selected="false">Completed
                            ({{ $totalCounts['completed'] }})</button>
                    </li>
                </ul>
                <div class="tab-content" id="taskTabContent">
                    <!-- Overdue Tab -->
                    <div class="tab-pane fade show active" id="overdue" role="tabpanel" aria-labelledby="overdue-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th>Task</th>
                                        <th>Company</th>
                                        <th>Related</th>
                                        <th>Created By</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($tasks['overdue']->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center">No overdue tasks.</td>
                                        </tr>
                                    @else
                                        @foreach ($tasks['overdue'] as $task)
                                            <tr data-task-id="{{ $task['id'] }}">
                                                <td data-label="Status">
                                                    <label class="custom-checkbox">
                                                        <input type="checkbox" class="mark-complete-checkbox"
                                                            data-task-id="{{ $task['id'] }}" name="overdue_task_select[]"
                                                            value="{{ $task['id'] }}">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td data-label="Task">
                                                    <a href="#" class="open-task-modal"
                                                        data-task-id="{{ $task['id'] }}"
                                                        style="font-weight:600;text-decoration:underline;color:#2c2c2c;">{{ $task['name'] }}</a>
                                                </td>
                                                <td data-label="Company">
                                                    @if($task['related_type'] === 'deal')
                                                        <a href="{{ route('deals.show', $task['related_id']) }}" class="text-decoration-none">
                                                            {{ $task['organization_name'] ?? '-' }}
                                                        </a>
                                                    @elseif($task['related_type'] === 'lead')
                                                        <a href="{{ route('leads.show', $task['related_id']) }}" class="text-decoration-none">
                                                            {{ $task['organization_name'] ?? '-' }}
                                                        </a>
                                                    @else
                                                        {{ $task['organization_name'] ?? '-' }}
                                                    @endif
                                                </td>
                                                <td data-label="Related">{{ $task['related_type'] ?? '' }}</td>
                                                <td data-label="Created By">
                                                    {{ \App\Models\User::find($task['user_owner_id'])->name ?? 'Unknown' }}
                                                </td>
                                                <td data-label="Assigned To">
                                                    {{ \App\Models\User::find($task['user_assigned_id'])->name ?? 'Unknown' }}
                                                </td>
                                                <td data-label="Due Date">
                                                    {{ trim(\Carbon\Carbon::parse($task['due_at'])->format('d-m-Y h:i A')) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
                                <div class="small text-muted">Showing {{ $tasks['overdue']->firstItem() ?? 0 }} to
                                    {{ $tasks['overdue']->lastItem() ?? 0 }} of {{ $tasks['overdue']->total() }} tasks
                                </div>
                                <div class="pagination-custom text-center my-3">
                                    <nav aria-label="Tasks pagination">
                                        <ul class="pagination justify-content-center gap-3 mb-0">
                                            <li
                                                class="page-item {{ $tasks['overdue']->onFirstPage() ? 'disabled' : '' }}">
                                                <a class="page-link"
                                                    href="{{ $tasks['overdue']->previousPageUrl() ?: '#' }}"
                                                    tabindex="-1"
                                                    aria-disabled="{{ $tasks['overdue']->onFirstPage() ? 'true' : 'false' }}">&laquo;
                                                    Previous</a>
                                            </li>
                                            <li
                                                class="page-item {{ $tasks['overdue']->hasMorePages() ? '' : 'disabled' }}">
                                                <a class="page-link" href="{{ $tasks['overdue']->nextPageUrl() ?: '#' }}"
                                                    aria-disabled="{{ $tasks['overdue']->hasMorePages() ? 'false' : 'true' }}">Next
                                                    &raquo;</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Today Tab -->
                    <div class="tab-pane fade" id="today" role="tabpanel" aria-labelledby="today-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th>Task</th>
                                        <th>Company</th>
                                        <th>Related</th>
                                        <th>Created By</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($tasks['today']->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center">No tasks for today.</td>
                                        </tr>
                                    @else
                                        @foreach ($tasks['today'] as $task)
                                            <tr data-task-id="{{ $task['id'] }}">
                                                <td data-label="Status">
                                                    <label class="custom-checkbox">
                                                        <input type="checkbox" class="mark-complete-checkbox"
                                                            data-task-id="{{ $task['id'] }}" name="today_task_select[]"
                                                            value="{{ $task['id'] }}">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td data-label="Task">
                                                    <a href="#" class="open-task-modal"
                                                        data-task-id="{{ $task['id'] }}"
                                                        style="font-weight:600;text-decoration:underline;color:#2c2c2c;">{{ $task['name'] }}</a>
                                                </td>
                                                <td data-label="Company">
                                                    @if($task['related_type'] === 'deal')
                                                        <a href="{{ route('deals.show', $task['related_id']) }}" class="text-decoration-none">
                                                            {{ $task['organization_name'] ?? '-' }}
                                                        </a>
                                                    @elseif($task['related_type'] === 'lead')
                                                        <a href="{{ route('leads.show', $task['related_id']) }}" class="text-decoration-none">
                                                            {{ $task['organization_name'] ?? '-' }}
                                                        </a>
                                                    @else
                                                        {{ $task['organization_name'] ?? '-' }}
                                                    @endif
                                                </td>
                                                <td data-label="Related">{{ $task['related_type'] ?? '' }}</td>
                                                <td data-label="Created By">
                                                    {{ \App\Models\User::find($task['user_owner_id'])->name ?? 'Unknown' }}
                                                </td>
                                                <td data-label="Assigned To">
                                                    {{ \App\Models\User::find($task['user_assigned_id'])->name ?? 'Unknown' }}
                                                </td>
                                                <td data-label="Due Date">{{ $task['due_at']->format('d-m-Y h:i A') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
                                <div class="small text-muted">Showing {{ $tasks['today']->firstItem() ?? 0 }} to
                                    {{ $tasks['today']->lastItem() ?? 0 }} of {{ $tasks['today']->total() }} tasks
                                </div>
                                <div class="pagination-custom text-center my-3">
                                    <nav aria-label="Tasks pagination">
                                        <ul class="pagination justify-content-center gap-3 mb-0">
                                            <li
                                                class="page-item {{ $tasks['today']->onFirstPage() ? 'disabled' : '' }}">
                                                <a class="page-link"
                                                    href="{{ $tasks['today']->previousPageUrl() ?: '#' }}"
                                                    tabindex="-1"
                                                    aria-disabled="{{ $tasks['today']->onFirstPage() ? 'true' : 'false' }}">&laquo;
                                                    Previous</a>
                                            </li>
                                            <li
                                                class="page-item {{ $tasks['today']->hasMorePages() ? '' : 'disabled' }}">
                                                <a class="page-link" href="{{ $tasks['today']->nextPageUrl() ?: '#' }}"
                                                    aria-disabled="{{ $tasks['today']->hasMorePages() ? 'false' : 'true' }}">Next
                                                    &raquo;</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Upcoming Tab -->
                    <div class="tab-pane fade" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th>Task</th>
                                        <th>Company</th>
                                        <th>Related</th>
                                        <th>Created By</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($tasks['upcoming']->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center">No upcoming tasks.</td>
                                        </tr>
                                    @else
                                        @foreach ($tasks['upcoming'] as $task)
                                            <tr data-task-id="{{ $task['id'] }}">
                                                <td data-label="Status">
                                                    <label class="custom-checkbox">
                                                        <input type="checkbox" class="mark-complete-checkbox"
                                                            data-task-id="{{ $task['id'] }}"
                                                            name="upcoming_task_select[]" value="{{ $task['id'] }}">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td data-label="Task">
                                                    <a href="#" class="open-task-modal"
                                                        data-task-id="{{ $task['id'] }}"
                                                        style="font-weight:600;text-decoration:underline;color:#2c2c2c;">{{ $task['name'] }}</a>
                                                </td>
                                                <td data-label="Company">
                                                    @if($task['related_type'] === 'deal')
                                                        <a href="{{ route('deals.show', $task['related_id']) }}" class="text-decoration-none">
                                                            {{ $task['organization_name'] }}
                                                        </a>
                                                    @elseif($task['related_type'] === 'lead')
                                                        <a href="{{ route('leads.show', $task['related_id']) }}" class="text-decoration-none">
                                                            {{ $task['organization_name'] }}
                                                        </a>
                                                    @else
                                                        {{ $task['organization_name'] ?? '-' }}
                                                    @endif
                                                </td>
                                                <td data-label="Related">{{ $task['related_type'] ?? '' }}</td>
                                                <td data-label="Created By">
                                                    {{ \App\Models\User::find($task['user_owner_id'])->name ?? 'Unknown' }}
                                                </td>
                                                <td data-label="Assigned To">
                                                    {{ \App\Models\User::find($task['user_assigned_id'])->name ?? 'Unknown' }}
                                                </td>
                                                <td data-label="Due Date">{{ $task['due_at']->format('d-m-Y h:i A') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
                                <div class="small text-muted">Showing {{ $tasks['upcoming']->firstItem() ?? 0 }} to
                                    {{ $tasks['upcoming']->lastItem() ?? 0 }} of {{ $tasks['upcoming']->total() }} tasks
                                </div>
                                <div class="pagination-custom text-center my-3">
                                    <nav aria-label="Tasks pagination">
                                        <ul class="pagination justify-content-center gap-3 mb-0">
                                            <li
                                                class="page-item {{ $tasks['upcoming']->onFirstPage() ? 'disabled' : '' }}">
                                                <a class="page-link"
                                                    href="{{ $tasks['upcoming']->previousPageUrl() ?: '#' }}"
                                                    tabindex="-1"
                                                    aria-disabled="{{ $tasks['upcoming']->onFirstPage() ? 'true' : 'false' }}">&laquo;
                                                    Previous</a>
                                            </li>
                                            <li
                                                class="page-item {{ $tasks['upcoming']->hasMorePages() ? '' : 'disabled' }}">
                                                <a class="page-link" href="{{ $tasks['upcoming']->nextPageUrl() ?: '#' }}"
                                                    aria-disabled="{{ $tasks['upcoming']->hasMorePages() ? 'false' : 'true' }}">Next
                                                    &raquo;</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Completed Tab -->
                    <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th>Task</th>
                                        <th>Company</th>
                                        <th>Related</th>

                                        <th>Created By</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                        <th>Completed At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (empty($tasks['completed']) || count($tasks['completed']) === 0)
                                        <tr>
                                            <td colspan="8" class="text-center">No completed tasks.</td>
                                        </tr>
                                    @else
                                        @foreach ($tasks['completed'] as $task)
                                            <tr>
                                                <td class="text-center" data-label="Status">
                                                    <label class="custom-checkbox">
                                                        <input type="checkbox" class="completed-checkbox"
                                                            data-task-id="{{ $task['id'] }}" title="Mark as incomplete"
                                                            checked>
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>

                                                <td data-label="Task">
                                                    <a href="#" class="open-task-modal"
                                                        data-task-id="{{ $task['id'] }}"
                                                        style="font-weight:600;text-decoration:underline;color:#2c2c2c;">
                                                        <span
                                                            style="text-decoration: line-through;">{{ $task['name'] }}</span>
                                                    </a>
                                                </td>
                                                <td data-label="Company">
                                                    @if($task['related_type'] === 'deal')
                                                        <a href="{{ route('deals.show', $task['related_id']) }}" class="text-decoration-none">
                                                            {{ $task['organization_name'] }}
                                                        </a>
                                                    @elseif($task['related_type'] === 'lead')
                                                        <a href="{{ route('leads.show', $task['related_id']) }}" class="text-decoration-none">
                                                            {{ $task['organization_name'] }}
                                                        </a>
                                                    @else
                                                        {{ $task['organization_name'] ?? '-' }}
                                                    @endif
                                                </td>
                                                <td data-label="Related">{{ $task['related_type'] ?? '--' }}</td>
                                                <td data-label="Created By">
                                                    {{ \App\Models\User::find($task['user_owner_id'])->name ?? 'Unknown' }}
                                                </td>
                                                <td data-label="Assigned To">
                                                    {{ \App\Models\User::find($task['user_assigned_id'])->name ?? 'Unknown' }}
                                                </td>
                                                <td data-label="Due Date">{{ $task['due_at']->format('d-m-Y h:i A') }}
                                                </td>
                                                <td data-label="Completed At">
                                                    {{ $task['completed_at'] ? (is_object($task['completed_at']) ? $task['completed_at']->format('d-m-Y h:i A') : \Carbon\Carbon::parse($task['completed_at'])->format('d-m-Y h:i A')) : '' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pagination-info">
                                <div class="small text-muted">Showing {{ $tasks['completed']->firstItem() ?? 0 }} to
                                    {{ $tasks['completed']->lastItem() ?? 0 }} of {{ $tasks['completed']->total() }} tasks
                                </div>
                                <div class="pagination-custom text-center my-3">
                                    <nav aria-label="Tasks pagination">
                                        <ul class="pagination justify-content-center gap-3 mb-0">
                                            <li
                                                class="page-item {{ $tasks['completed']->onFirstPage() ? 'disabled' : '' }}">
                                                <a class="page-link"
                                                    href="{{ $tasks['completed']->previousPageUrl() ?: '#' }}"
                                                    tabindex="-1"
                                                    aria-disabled="{{ $tasks['completed']->onFirstPage() ? 'true' : 'false' }}">&laquo;
                                                    Previous</a>
                                            </li>
                                            <li
                                                class="page-item {{ $tasks['completed']->hasMorePages() ? '' : 'disabled' }}">
                                                <a class="page-link" href="{{ $tasks['completed']->nextPageUrl() ?: '#' }}"
                                                    aria-disabled="{{ $tasks['completed']->hasMorePages() ? 'false' : 'true' }}">Next
                                                    &raquo;</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTaskModalLabel">Create Tasks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('tasks.store') }}">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Task Title</label>
                                <div class="dropdown">
                                    <input type="text" class="form-control" id="taskName" name="name"
                                        placeholder="Enter task name" required autocomplete="off">
                                    @if ($errors->has('name'))
                                        <div class="text-danger mt-1">{{ $errors->first('name') }}</div>
                                    @endif
                                </div>
                            </div>

                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority" id="priority" required>
                                    <option value="high">High</option>
                                    <option value="higest">Highest</option>
                                    <option value="low">Low</option>
                                    <option value="lowest">Lowest</option>
                                    <option value="normal">Normal</option>
                                </select>

                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>

                                <select class="form-select" name="status" id="status" required>
                                    <option value="Not Started">Not Started</option>
                                    <option value="Deferred">Deferred</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Waiting for input">Waiting for input</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Link To</label>
                                <select class="form-select" name="related_type" id="linkTo"
                                    onchange="updateAutoCompleteList()" required>
                                    <option value="deal">Deal</option>
                                    <option value="lead">Lead</option>
                                </select>

                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Deal</label>

                                <select class="form-select mt-2" name="related_id" id="linkName" style="width: 100%;"
                                    required>
                                    <!-- Options will be dynamically populated -->
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Assigned To</label>
                                <select class="form-select" name="user_assigned_id" id="assignedTo"
                                    onchange="updateAssignedUserName()" required>
                                    @foreach (\App\Models\User::all() as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <p id="selectedUserName" class="mt-2"></p>

                                <script>
                                    function updateAssignedUserName() {
                                        const selectElement = document.getElementById('assignedTo');
                                        const selectedUserName = selectElement.options[selectElement.selectedIndex].text;
                                        document.getElementById('selectedUserName').textContent = `Selected User: ${selectedUserName}`;
                                    }
                                </script>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Due Date & Time</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                    <input type="datetime-local" name="due_at" class="form-control" id="dueDatePicker"
                                        placeholder="Select date & time" value="{{ now()->format('Y-m-d\TH:i') }}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check">
                                    <input type="hidden" name="reminder_notifications_enabled" value="0">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="reminderEnabledCreate" name="reminder_notifications_enabled" checked>
                                    <label class="form-check-label" for="reminderEnabledCreate">Enable Reminder
                                        Notification</label>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center" id="reminderOffsetWrapperCreate"
                                style="visibility:hidden; height:0; align-items:center; gap:10px;">
                                <label class="form-label mb-0">Reminder Time</label>
                                <select class="form-select" name="reminder_offset" id="reminderOffsetCreate"
                                    style="width:160px;">
                                    <option value="5">5 minutes before</option>
                                    <option value="15">15 minutes before</option>
                                    <option value="30" selected>30 minutes before</option>
                                    <option value="60">1 hour before</option>
                                    <option value="120">2 hours before</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                            <button type="submit" class="btn btn-custom">Save Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form method="POST" action="{{ isset($task) ? '/tasks/' . $task->id : '#' }}">

                        @csrf
                        @method('PUT')
                        <input type="hidden" name="task_id">

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Task Title</label>
                                <input type="text" class="form-control" name="name" placeholder="Enter task name">
                                @if ($errors->has('name'))
                                    <div class="text-danger mt-1">{{ $errors->first('name') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                                @if ($errors->has('description'))
                                    <div class="text-danger mt-1">{{ $errors->first('description') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority">
                                    <option value="high">High</option>
                                    <option value="highest">Highest</option>
                                    <option value="low">Low</option>
                                    <option value="lowest">Lowest</option>
                                    <option value="normal">Normal</option>
                                </select>
                                @if ($errors->has('priority'))
                                    <div class="text-danger mt-1">{{ $errors->first('priority') }}</div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="Not Started">Not Started</option>
                                    <option value="Deferred">Deferred</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Waiting for input">Waiting for input</option>
                                </select>
                                @if ($errors->has('status'))
                                    <div class="text-danger mt-1">{{ $errors->first('status') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Link To</label>
                                <select class="form-select" name="related_type" id="editLinkTo">
                                    <option value="deal">Deal</option>
                                    <option value="lead">Lead</option>
                                </select>
                                @if ($errors->has('related_type'))
                                    <div class="text-danger mt-1">{{ $errors->first('related_type') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Deal/Lead</label>
                                <select class="form-select" name="related_id" id="editLinkName">
                                    <!-- Options will be dynamically populated -->
                                </select>
                                @if ($errors->has('related_id'))
                                    <div class="text-danger mt-1">{{ $errors->first('related_id') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Assigned To</label>
                                <select class="form-select" name="user_assigned_id" id="editAssignedTo">
                                    <!-- error message will be shown below if needed -->
                                    @foreach (\App\Models\User::all() as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <div id="editAssignedToError" class="text-danger mb-2" style="display:none;"></div>
                                @if ($errors->has('user_assigned_id'))
                                    <div class="text-danger mt-1">{{ $errors->first('user_assigned_id') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Due Date & Time</label>
                                <input type="datetime-local" class="form-control" name="due_at" id="editDueDate">
                                @if ($errors->has('due_at'))
                                    <div class="text-danger mt-1">{{ $errors->first('due_at') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check">
                                    <input type="hidden" name="reminder_notifications_enabled" value="0">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="reminderEnabledEdit" name="reminder_notifications_enabled">
                                    <label class="form-check-label" for="reminderEnabledEdit">Enable Reminder
                                        Notification</label>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center" id="reminderOffsetWrapperEdit"
                                style="visibility:hidden; height:0; align-items:center; gap:10px;">
                                <label class="form-label mb-0">Reminder Time</label>
                                <select class="form-select" name="reminder_offset" id="reminderOffsetEdit"
                                    style="width:160px;">
                                    <option value="5">5 minutes before</option>
                                    <option value="15">15 minutes before</option>
                                    <option value="30">30 minutes before</option>
                                    <option value="60">1 hour before</option>
                                    <option value="120">2 hours before</option>
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                            <button type="submit" class="btn btn-custom">Update Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .task-item span {
            cursor: pointer;
        }

        .accordion-button {
            text-align: left;
        }

        @media (max-width: 1024px) {

            .org-header .col-md-4,
            .org-header .col-md-8 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            @media (max-width: 1299.98px) {
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .task-item {
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            .task-item>div {
                width: 100% !important;
                max-width: 100% !important;
            }

            .card-header .btn,
            .card-header .form-select,
            .card-header input[type="text"] {
                width: 100% !important;
                margin-bottom: 8px;
            }

            .card-header .d-flex.flex-wrap>* {
                flex: 1 1 100%;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ...existing code...

            // Completed tab: mark as incomplete using checkbox
            document.querySelectorAll('.completed-checkbox').forEach(function(el) {
                el.addEventListener('change', function() {
                    const taskId = this.getAttribute('data-task-id');
                    if (!taskId) return;
                    fetch(`/tasks/${taskId}/incomplete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Failed to mark incomplete');
                            // Remove the row from completed tab
                            const row = this.closest('tr');
                            if (row) {
                                // Get task name and org from completed row
                                const cells = row.querySelectorAll('td');
                                const taskName = cells[1].innerText.replace(/\n|\r/g, '')
                            .trim();
                                const orgName = cells[2] ? cells[2].innerText : '';
                                const type = cells[3] ? cells[3].innerText : '';
                                const crtName = cells[4] ? cells[4].innerText : '';
                                const assgndName = cells[5] ? cells[5].innerText : '';
                                const dueDate = cells[6] ? cells[6].innerText : '';
                                // For demo: assign to Overdue tab (or you can use due date via AJAX for real logic)
                                const overdueTable = document.querySelector(
                                    '#overdue table tbody');
                                if (overdueTable) {
                                    const overdueRow = document.createElement('tr');
                                    overdueRow.setAttribute('data-task-id', taskId);
                                    overdueRow.innerHTML = `
                                <td><label class="custom-checkbox">
                                            <input type="checkbox" class="mark-complete-checkbox" data-task-id="${taskId}" name="today_task_select[]" value="${taskId}">
                                            <span class="checkmark"></span>
                                        </label>
                                </td>
                                <td><a href="#" class="open-task-modal" data-task-id="${taskId}" style="font-weight:600;text-decoration:underline;color:#2c2c2c;">${taskName}</a></td>
                                <td>${orgName}</td>
                                <td>${type}</td>
                                <td>${crtName}</td>
                                <td>${assgndName}</td>
                                <td>${dueDate}</td>
                            `;
                                    overdueTable.prepend(overdueRow);
                                    // Update overdue count
                                    const overdueTabBtn = document.getElementById(
                                    'overdue-tab');
                                    if (overdueTabBtn) {
                                        const match = overdueTabBtn.innerText.match(
                                            /Overdue \((\d+)\)/);
                                        if (match) {
                                            const newCount = parseInt(match[1], 10) + 1;
                                            overdueTabBtn.innerText = `Overdue (${newCount})`;
                                        }
                                    }
                                }
                                // Update completed count
                                const completedTabBtn = document.getElementById(
                                'completed-tab');
                                if (completedTabBtn) {
                                    const match = completedTabBtn.innerText.match(
                                        /Completed \((\d+)\)/);
                                    if (match) {
                                        const newCount = Math.max(0, parseInt(match[1], 10) -
                                        1);
                                        completedTabBtn.innerText = `Completed (${newCount})`;
                                    }
                                }
                                // Animate and remove from completed
                                row.style.transition = 'opacity 0.5s';
                                row.style.opacity = 0.3;
                                setTimeout(() => row.remove(), 600);
                            }
                        })
                        .catch(() => {
                            this.checked = true;
                        });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Only open modal when clicking the task name (not the row or other cells)
            document.querySelectorAll('.open-task-modal').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const taskId = this.getAttribute('data-task-id');
                    if (!taskId) return;
                    // Fetch and populate task data via AJAX, then show modal
                    $('#editTaskModal input[name="task_id"]').val(taskId);
                    $('#editTaskModal form').attr('action', `/tasks/${taskId}`);
                    $.ajax({
                        url: `/tasks/${taskId}/edit`,
                        method: 'GET',
                        success: function(data) {
                            if (!data || !data.id) {
                                alert('Task details not found or invalid.');
                                return;
                            }
                            // Populate modal fields with task data
                            $('#editTaskModal input[name="name"]').val(data.name || '');
                            $('#editTaskModal textarea[name="description"]').val(data
                                .description || '');
                            $('#editTaskModal select[name="priority"]').val(data
                                .priority || 'normal');
                            $('#editTaskModal select[name="status"]').val(data.status ||
                                'Not Started');
                            // Format due_at for datetime-local input (YYYY-MM-DDTHH:MM)
                            let dueAtVal = '';
                            if (data.due_at) {
                                const dt = new Date(data.due_at);
                                if (!isNaN(dt.getTime())) {
                                    const pad = n => n.toString().padStart(2, '0');
                                    dueAtVal = dt.getFullYear() + '-' + pad(dt
                                        .getMonth() + 1) + '-' + pad(dt.getDate()) +
                                        'T' + pad(dt.getHours()) + ':' + pad(dt
                                            .getMinutes());
                                } else {
                                    dueAtVal = data.due_at;
                                }
                            }
                            $('#editTaskModal input[name="due_at"]').val(dueAtVal);
                            $('#editTaskModal select[name="related_type"]').val(data
                                .related_type || 'deal');
                            // Populate related options and set selected
                            if (data.related_options) {
                                const relatedOptions = (data.related_options || []).map(
                                    option => ({
                                        id: option.id,
                                        text: option.text
                                    }));
                                $('#editLinkName').empty().select2({
                                    placeholder: 'Select Deal/Lead',
                                    allowClear: true,
                                    width: '100%',
                                    dropdownParent: $('#editTaskModal'),
                                    data: relatedOptions
                                });
                                $('#editLinkName').val(data.related_id || '').trigger(
                                    'change');
                            } else {
                                $('#editLinkName').val(data.related_id || '').trigger(
                                    'change');
                            }
                            $('#editTaskModal select[name="user_assigned_id"]').val(data
                                .user_assigned_id || '');
                            $('#reminderEnabledEdit').trigger('change');
                            setTimeout(function() {
                                if (typeof toggleEditReminderOffset ===
                                    'function') toggleEditReminderOffset();
                            }, 50);
                            $('#editTaskModal').modal('show');
                        },
                        error: function() {
                            alert('Failed to fetch task details. Please try again.');
                        }
                    });
                });
            });
            // Mark as complete when checkbox is checked in Overdue, Today, Upcoming
            document.querySelectorAll('.mark-complete-checkbox').forEach(function(el) {

                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    const taskId = this.getAttribute('data-task-id');
                    if (!taskId || !this.checked) return;
                    fetch(`/tasks/${taskId}/complete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Failed to mark complete');
                            // Get the row and its data
                            const row = this.closest('tr');
                            if (row) {
                                // Clone the row data for completed tab
                                const cells = row.querySelectorAll('td');
                                // Build new row for completed tab
                                const completedTable = document.querySelector(
                                    '#completed table tbody');
                                if (completedTable) {
                                    // Compose completed row HTML (adjust columns as needed)
                                    const completedAt = new Date().toLocaleString('en-GB', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        hour12: true
                                    });
                                    const completedRow = document.createElement('tr');
                                    completedRow.innerHTML = `
                                <td class="text-center">
                                <label class="custom-checkbox">
                                            <input type="checkbox" class="completed-checkbox" data-task-id="${taskId}" title="Mark as incomplete" checked>
                                            <span class="checkmark"></span>
                                        </label>

                                </td>
                                <td>
                                    <a href="#" class="open-task-modal" data-task-id="${taskId}" style="font-weight:600;text-decoration:underline;color:#2c2c2c;">
                                        <span style="text-decoration: line-through;">${cells[1].innerText}</span>
                                    </a>
                                </td>
                                <td>${cells[2] ? cells[2].innerText : '--'}</td>
                                <td>${cells[3] ? cells[3].innerText : '--'}</td>

                                <td>${cells[4] ? cells[4].innerText : '--'}</td>
                                <td>${cells[5] ? cells[5].innerText : '--'}</td>
                                <td>${cells[6] ? cells[6].innerText : '--'}</td>
                                <td>${completedAt}</td>
                            `;
                                    completedTable.prepend(completedRow);
                                    // Update completed count in tab
                                    const completedTabBtn = document.getElementById(
                                        'completed-tab');
                                    if (completedTabBtn) {
                                        const match = completedTabBtn.innerText.match(
                                            /Completed \((\d+)\)/);
                                        if (match) {
                                            const newCount = parseInt(match[1], 10) + 1;
                                            completedTabBtn.innerText =
                                                `Completed (${newCount})`;
                                        }
                                    }


                                }
                                // Animate and remove from overdue
                                row.style.transition = 'opacity 0.5s';
                                row.style.opacity = 0.3;
                                setTimeout(() => row.remove(), 600);
                            }
                        })
                        .catch(() => {
                            this.checked = false;
                        });
                });
            });
        });

        //Create Modal Script
        $(document).ready(function() {
            // Add client-side validation for Assigned To in edit modal
            $('#editTaskModal form').on('submit', function(e) {
                var assignedTo = $('#editAssignedTo').val();
                if (!assignedTo) {
                    $('#editAssignedToError').text('Assigned To is required.').show();
                    $('#editAssignedTo').addClass('is-invalid');
                    e.preventDefault();
                    return false;
                } else {
                    $('#editAssignedToError').hide();
                    $('#editAssignedTo').removeClass('is-invalid');
                }
            });
            // Hide error on change
            $('#editAssignedTo').on('change', function() {
                if ($(this).val()) {
                    $('#editAssignedToError').hide();
                    $(this).removeClass('is-invalid');
                }
            });
            // Reset edit task modal fields on close
            $('#editTaskModal').on('hidden.bs.modal', function() {
                const $form = $(this).find('form');
                $form[0].reset();
                // Reset Select2 dropdowns if used
                $form.find('select').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).val('').trigger('change');
                    }
                });
                // Reset reminder UI
                $('#reminderEnabledEdit').prop('checked', false);
                $('#reminderOffsetEdit').val('30');
                toggleEditReminderOffset();
                // Clear error messages
                $form.find('.text-danger').remove();
                // Reset assigned user name display if present
                // (add similar logic if you display assigned user elsewhere)
                // Reset date/time picker to blank
                $('#editDueDate').val('');
            });
            // Reset create task modal fields on close
            $('#createTaskModal').on('hidden.bs.modal', function() {
                const $form = $(this).find('form');
                $form[0].reset();
                // Reset Select2 dropdowns if used
                $form.find('select').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).val('').trigger('change');
                    }
                });
                // Reset reminder UI
                $('#reminderEnabledCreate').prop('checked', true);
                $('#reminderOffsetCreate').val('30');
                toggleCreateReminderOffset();
                // Clear error messages
                $form.find('.text-danger').remove();
                // Reset assigned user name display
                $('#selectedUserName').text('');
                // Reset date/time picker to default
                $('#dueDatePicker').val($('#dueDatePicker').attr('value'));
            });
            const data = {
                deal: <?php echo json_encode(
                    \App\Models\Deal::with('organization:id,name')
                        ->select('id', 'organization_id', 'title')
                        ->get()
                        ->map(function ($deal) {
                            return [
                                'id' => $deal->id,
                                'text' => ($deal->organization->name ?? 'Unknown') . ' - ' . $deal->title,
                            ];
                        }),
                ); ?>,

                lead: <?php echo json_encode(
                    \App\Models\Lead::with('organization:id,name')
                        ->select('id', 'organization_id', 'title')
                        ->get()
                        ->map(function ($lead) {
                            return [
                                'id' => $lead->id,
                                'text' => ($lead->organization->name ?? 'Unknown') . ' - ' . $lead->title,
                            ];
                        }),
                ); ?>
            };

            $('#linkName').select2({
                placeholder: 'Enter name',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#createTaskModal'),
                data: []
            });

            $('#linkTo').on('change', function() {
                const selectedType = $(this).val();
                const options = data[selectedType];
                $('#linkName').empty().select2({
                    placeholder: 'Enter name',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#createTaskModal'),
                    data: options
                });
            });

            // Trigger initial population
            $('#linkTo').trigger('change');
        });
        //Create Script #createTaskModal



        //Edit Script
        $(document).ready(function() {
            // Initialize Select2 for Deal/Lead dropdown
            $('#editLinkName').select2({
                placeholder: 'Select Deal/Lead',
                allowClear: true,
                width: '100%'
            });

            // Handle change event for Link To dropdown in edit modal
            $('#editLinkTo').on('change', function() {
                const selectedType = $(this).val();
                console.log('Link To changed:', selectedType); // Debug statement

                // Fetch related options dynamically
                const relatedOptions = selectedType === 'deal' ?
                    <?php echo json_encode(
                        \App\Models\Deal::with('organization:id,name')
                            ->select('id', 'organization_id', 'title')
                            ->get()
                            ->map(function ($deal) {
                                return [
                                    'id' => $deal->id,
                                    'text' => ($deal->organization->name ?? 'Unknown') . ' - ' . $deal->title,
                                ];
                            }),
                    ); ?> :
                    <?php echo json_encode(
                        \App\Models\Lead::with('organization:id,name')
                            ->select('id', 'organization_id', 'title')
                            ->get()
                            ->map(function ($lead) {
                                return [
                                    'id' => $lead->id,
                                    'text' => ($lead->organization->name ?? 'Unknown') . ' - ' . $lead->title,
                                ];
                            }),
                    ); ?>;
                // Update the related dropdown
                $('#editLinkName').empty().select2({
                    placeholder: 'Select Deal/Lead',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#editTaskModal'), // Ensure dropdown is within the modal
                    data: relatedOptions
                });
            });

            // Populate edit modal with existing task data
            $('.task-item').on('click', function() {
                const taskId = $(this).data('task-id');
                if (!taskId) {
                    alert('Task not found. Please try again.');
                    return;
                }

                // Proceed with fetching task details
                $('#editTaskModal input[name="task_id"]').val(taskId);
                $('#editTaskModal form').attr('action', `/tasks/${taskId}`);

                $.ajax({
                    url: `/tasks/${taskId}/edit`,
                    method: 'GET',
                    success: function(data) {
                        if (!data || !data.id) {
                            alert('Task details not found or invalid.');
                            return;
                        }

                        // Populate modal fields with task data
                        $('#editTaskModal input[name="name"]').val(data.name || '');
                        $('#editTaskModal textarea[name="description"]').val(data.description ||
                            '');
                        $('#editTaskModal select[name="priority"]').val(data.priority ||
                            'normal');
                        $('#editTaskModal select[name="status"]').val(data.status ||
                            'Not Started');
                        // Format due_at for datetime-local input (YYYY-MM-DDTHH:MM)
                        let dueAtVal = '';
                        if (data.due_at) {
                            // Try to parse and format
                            const dt = new Date(data.due_at);
                            if (!isNaN(dt.getTime())) {
                                const pad = n => n.toString().padStart(2, '0');
                                dueAtVal = dt.getFullYear() + '-' + pad(dt.getMonth() + 1) +
                                    '-' + pad(dt.getDate()) + 'T' + pad(dt.getHours()) + ':' +
                                    pad(dt.getMinutes());
                            } else {
                                dueAtVal = data.due_at;
                            }
                        }
                        $('#editTaskModal input[name="due_at"]').val(dueAtVal);
                        $('#editTaskModal select[name="related_type"]').val(data.related_type ||
                            'deal');

                        const relatedOptions = (data.related_options || []).map(option => ({
                            id: option.id,
                            text: option.text
                        }));

                        $('#editLinkName').empty().select2({
                            placeholder: 'Select Deal/Lead',
                            allowClear: true,
                            width: '100%',
                            dropdownParent: $('#editTaskModal'),
                            data: relatedOptions
                        });

                        $('#editLinkName').val(data.related_id || '').trigger('change');
                        $('#editTaskModal select[name="user_assigned_id"]').val(data
                            .user_assigned_id || '');
                        // populate reminder checkbox and offset, then trigger change to ensure wrapper visibility
                        // $('#reminderEnabledEdit').prop('checked', !!data.reminder_notifications_enabled);
                        // $('#reminderOffsetEdit').val(data.reminder_offset || 30);
                        $('#reminderEnabledEdit').trigger('change');
                        setTimeout(toggleEditReminderOffset, 50);

                        $('#editTaskModal').modal('show');
                    },
                    error: function() {
                        alert('Failed to fetch task details. Please try again.');
                    }
                });
            });

            // Keep the modal open if there are validation errors
            @if ($errors->any())

                $('#editTaskModal').modal('show');
            @endif

            // Show/hide reminder offset in create modal based on checkbox
            function toggleCreateReminderOffset() {
                const $checkbox = $('#reminderEnabledCreate');
                const $wrapper = $('#reminderOffsetWrapperCreate');
                if (!$checkbox.length || !$wrapper.length) {
                    console.debug('toggleCreateReminderOffset: missing elements', $checkbox.length, $wrapper
                    .length);
                    return;
                }
                const checked = $checkbox.is(':checked');
                console.debug('toggleCreateReminderOffset:', checked);
                if (checked) {
                    $wrapper.css({
                        'visibility': 'visible',
                        'height': 'auto'
                    });
                } else {
                    $wrapper.css({
                        'visibility': 'hidden',
                        'height': '0'
                    });
                }
            }
            $('#reminderEnabledCreate').on('change', toggleCreateReminderOffset);
            // ensure correct state when modal is shown
            // run on both show and shown to avoid timing races; trigger the checkbox change handler
            $('#createTaskModal').on('show.bs.modal shown.bs.modal', function() {
                console.debug('createTaskModal show event');
                $('#reminderEnabledCreate').trigger('change');
                setTimeout(toggleCreateReminderOffset, 50);
            });
            toggleCreateReminderOffset();

            // Show/hide reminder offset in edit modal based on checkbox
            function toggleEditReminderOffset() {
                const $checkbox = $('#reminderEnabledEdit');
                const $wrapper = $('#reminderOffsetWrapperEdit');
                if (!$checkbox.length || !$wrapper.length) {
                    console.debug('toggleEditReminderOffset: missing elements', $checkbox.length, $wrapper.length);
                    return;
                }
                const checked = $checkbox.is(':checked');
                console.debug('toggleEditReminderOffset:', checked);
                if (checked) {
                    $wrapper.css({
                        'visibility': 'visible',
                        'height': 'auto'
                    });
                } else {
                    $wrapper.css({
                        'visibility': 'hidden',
                        'height': '0'
                    });
                }
            }
            $(document).on('change', '#reminderEnabledEdit', toggleEditReminderOffset);
            // ensure correct state when modal is shown (after AJAX population)
            $('#editTaskModal').on('show.bs.modal shown.bs.modal', function() {
                console.debug('editTaskModal show event');
                toggleEditReminderOffset();
            });
            toggleEditReminderOffset();
        });
    </script>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Activate the correct tab based on the 'tab' query parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                const tabButton = document.getElementById(tab + '-tab');
                if (tabButton) {
                    tabButton.click();
                }
            }

            // Ensure pagination links retain the current tab
            document.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function(event) {
                    const currentTab = document.querySelector('.nav-tabs .active').id.replace('-tab', '');
                    const url = new URL(this.href);
                    url.searchParams.set('tab', currentTab);
                    this.href = url.toString();
                });
            });
        });
    </script>
@endpush
