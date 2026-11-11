<ul class="nav nav-tabs mb-3" id="leadTabs" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" id="activity-tab"
                                    data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab"
                                    aria-controls="activity" aria-selected="true">Activity</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="notes-tab"
                                    data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab"
                                    aria-controls="notes" aria-selected="false">Notes</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="tasks-tab"
                                    data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab"
                                    aria-controls="tasks" aria-selected="false">Tasks</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="calls-tab"
                                    data-bs-toggle="tab" data-bs-target="#calls" type="button" role="tab"
                                    aria-controls="calls" aria-selected="false">Calls</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="meetings-tab"
                                    data-bs-toggle="tab" data-bs-target="#meetings" type="button" role="tab"
                                    aria-controls="meetings" aria-selected="false">Meetings</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="files-tab"
                                    data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab"
                                    aria-controls="files" aria-selected="false">Files</button></li>
                        </ul>
                        <div class="tab-content" id="leadTabsContent">
                            <div class="tab-pane fade show active" id="activity" role="tabpanel"
                                aria-labelledby="activity-tab">
                                <h6>All Activity</h6>
                                <div class="row g-3">
                                    @foreach(\App\Models\Note::where('related_type', $type)->where('related_id', $model->id)->orderByDesc('noted_at')->get() as $note)
                                        <div class="col-12">
                                            <div class="card shadow-sm border rounded mb-2">
                                                <div class="card-body position-relative">
                                                    <span class="badge bg-info mb-2">Note</span>
                                                    <div class="fw-bold mb-1">{{ $note->created_at->diffForHumans() }} -
                                                        {{ $note->created_by ? optional(\App\Models\User::find($note->created_by))->name : 'Unknown' }}
                                                    </div>
                                                    <div class="mb-2">{{ $note->content }}</div>
                                                    <span
                                                        class="badge bg-secondary">{{ $note->noted_at ? \Carbon\Carbon::parse($note->noted_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                                    <div class="position-absolute top-0 end-0 p-2">
                                                        <div class="dropdown">
                                                            <button class="btn btn-link text-dark p-0" type="button"
                                                                id="noteMenu{{ $note->id }}" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <span style="font-size:1.5rem;"><i
                                                                        class="bi bi-three-dots"></i></span>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end"
                                                                aria-labelledby="noteMenu{{ $note->id }}">
                                                                <li><a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                                        data-bs-target="#editNoteModal{{ $note->id }}">Edit</a>
                                                                </li>
                                                                <li>
                                                                    @if(!$note->pinned)
                                                                        <form action="{{ route('notes.pin', $note->id) }}"
                                                                            method="POST">@csrf<button type="submit"
                                                                                class="dropdown-item">Pin this note</button></form>
                                                                    @else
                                                                        <form action="{{ route('notes.unpin', $note->id) }}"
                                                                            method="POST">@csrf<button type="submit"
                                                                                class="dropdown-item">Unpin</button></form>
                                                                    @endif
                                                                </li>
                                                                <li>
                                                                    <form action="{{ route('notes.destroy', $note->id) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Delete this note?');">@csrf
                                                                        @method('DELETE')<button type="submit"
                                                                            class="dropdown-item text-danger">Delete</button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @foreach(\App\Models\Task::where('related_type', $type)->where('related_id', $model->id)->orderByDesc('due_at')->get() as $task)
                                        <div class="col-12">
                                            <div class="card shadow-sm border rounded mb-2">
                                                <div class="card-body position-relative">
                                                    <span class="badge bg-warning mb-2">Task</span>
                                                    <div class="fw-bold mb-1">{{ $task->name }}</div>
                                                    <div class="mb-2">{{ $task->description }}</div>
                                                    <span class="badge bg-secondary">Due:
                                                        {{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                                    @if($task->completed_at)
                                                        <span class="badge bg-success ms-2">Completed</span>
                                                    @endif
                                                    <div class="position-absolute top-0 end-0 p-2">
                                                        <div class="dropdown">
                                                            <button class="btn btn-link text-dark p-0" type="button"
                                                                id="taskMenu{{ $task->id }}" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <span style="font-size:1.5rem;"><i
                                                                        class="bi bi-three-dots"></i></span>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end"
                                                                aria-labelledby="taskMenu{{ $task->id }}">
                                                                <li><a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                                        data-bs-target="#editTaskModal{{ $task->id }}">Edit</a>
                                                                </li>
                                                                <li>
                                                                    @if(!$task->completed_at)
                                                                        <form action="{{ route('tasks.complete', $task->id) }}"
                                                                            method="POST">@csrf<button type="submit"
                                                                                class="dropdown-item">Mark Complete</button></form>
                                                                    @endif
                                                                </li>
                                                                <li>
                                                                    <form action="{{ route('tasks.destroy', $task->id) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Delete this task?');">@csrf
                                                                        @method('DELETE')<button type="submit"
                                                                            class="dropdown-item text-danger">Delete</button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @foreach(\App\Models\Call::where('related_type', $type)->where('related_id', $model->id)->orderByDesc('start_at')->get() as $call)
                                        <div class="col-12">
                                            <div class="card shadow-sm border rounded mb-2">
                                                <div class="card-body position-relative">
                                                    <span class="badge bg-primary mb-2">Call</span>
                                                    <div class="fw-bold mb-1">{{ $call->name }}</div>
                                                    <div class="mb-2">{{ $call->description }}</div>
                                                    <span
                                                        class="badge bg-secondary">{{ $call->start_at ? \Carbon\Carbon::parse($call->start_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                                    <span class="badge bg-light text-dark ms-2">Location:
                                                        {{ $call->location ?? '-' }}</span>
                                                    @if($call->user_restored_id)
                                                        <div class="mt-2">
                                                            <span class="fw-bold">Participants:</span>
                                                            @if($call->user_restored_id)
                                                                @php
                                                                    $participantIds = json_decode($call->user_restored_id, true);
                                                                    $names = collect($participantIds)->map(function ($id) {
                                                                        $person = \App\Models\Person::find($id);
                                                                        return $person ? $person->first_name . ' ' . $person->last_name : null;
                                                                    })->filter()->toArray();
                                                                @endphp
                                                                <span class="badge bg-secondary me-1">{{ implode(', ', $names) }}</span>
                                                            @else
                                                                <span class="badge bg-secondary me-1">-</span>
                                                            @endif
                                                           
                                                        </div>
                                                    @endif
                                                    <div class="position-absolute top-0 end-0 p-2">
                                                        <div class="dropdown">
                                                            <button class="btn btn-link text-dark p-0" type="button"
                                                                id="callMenu{{ $call->id }}" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <span style="font-size:1.5rem;"><i
                                                                        class="bi bi-three-dots"></i></span>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end"
                                                                aria-labelledby="callMenu{{ $call->id }}">
                                                                <li><a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                                        data-bs-target="#editCallModal{{ $call->id }}">Edit</a>
                                                                </li>
                                                                <li>
                                                                    <form action="{{ route('calls.destroy', $call->id) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Delete this call?');">@csrf
                                                                        @method('DELETE')<button type="submit"
                                                                            class="dropdown-item text-danger">Delete</button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @foreach(\App\Models\Meeting::where('related_type', $type)->where('related_id', $model->id)->orderByDesc('start_at')->get() as $meeting)
                                        <div class="col-12">
                                            <div class="card shadow-sm border rounded mb-2">
                                                <div class="card-body position-relative">
                                                    <span class="badge bg-success mb-2">Meeting</span>
                                                    <div class="fw-bold mb-1">{{ $meeting->name }}</div>
                                                    <div class="mb-2">{{ $meeting->description }}</div>
                                                    <span
                                                        class="badge bg-secondary">{{ $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                                    <span class="badge bg-light text-dark ms-2">Location:
                                                        {{ $meeting->location ?? '-' }}</span>
                                                    @if($meeting->user_restored_id)
                                                        <div class="mt-2">
                                                            <span class="fw-bold">Participants:</span>
                                                            @if($meeting->user_restored_id)
                                                                @php
                                                                    $participantIds = json_decode($meeting->user_restored_id, true);
                                                                    $names = collect($participantIds)->map(function ($id) {
                                                                        $person = \App\Models\Person::find($id);
                                                                        return $person ? $person->first_name . ' ' . $person->last_name : null;
                                                                    })->filter()->toArray();
                                                                @endphp
                                                                <span class="badge bg-secondary me-1">{{ implode(', ', $names) }}</span>
                                                            @else
                                                                <span class="badge bg-secondary me-1">-</span>
                                                            @endif
                                                            
                                                        </div>
                                                    @endif
                                                    <div class="position-absolute top-0 end-0 p-2">
                                                        <div class="dropdown">
                                                            <button class="btn btn-link text-dark p-0" type="button"
                                                                id="meetingMenu{{ $meeting->id }}" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <span style="font-size:1.5rem;"><i
                                                                        class="bi bi-three-dots"></i></span>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end"
                                                                aria-labelledby="meetingMenu{{ $meeting->id }}">
                                                                <li><a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                                        data-bs-target="#editMeetingModal{{ $meeting->id }}">Edit</a>
                                                                </li>
                                                                <li>
                                                                    <form action="{{ route('meetings.destroy', $meeting->id) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Delete this meeting?');">@csrf
                                                                        @method('DELETE')<button type="submit"
                                                                            class="dropdown-item text-danger">Delete</button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <form action="{{ route('notes.store') }}" method="POST" class="mb-3">
                                    @csrf
                                    <input type="hidden" name="related_type" value="{{$type}}">
                                    <input type="hidden" name="related_id" value="{{ $model->id }}">
                                    <div class="mb-2">
                                        <label for="note_content" class="form-label">Note</label>
                                        <textarea name="content" id="note_content" class="form-control" rows="2"
                                            required></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label for="noted_at" class="form-label">Note at</label>
                                        <input type="datetime-local" name="noted_at" id="noted_at" class="form-control"
                                            required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                </form>
                                <hr>
                                <h6>Notes</h6>
                                @php
                                    $notes = \App\Models\Note::where('related_type', $type)->where('related_id', $model->id)->orderByDesc('noted_at')->get();
                                @endphp
                                @forelse($notes as $note)
                                    <div class="mb-3 p-3 border rounded bg-light position-relative">
                                        <div class="fw-bold">{{ $note->created_at->diffForHumans() }} -
                                            {{ $note->created_by ? optional(\App\Models\User::find($note->created_by))->name : 'Unknown' }}
                                        </div>
                                        <div>{{ $note->content }}</div>
                                        <span class="badge bg-secondary">Noted at
                                            {{ $note->noted_at ? \Carbon\Carbon::parse($note->noted_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                        <!-- Three-dot dropdown -->
                                        <div class="position-absolute top-0 end-0 p-2">
                                            <div class="dropdown">
                                                <button class="btn btn-link text-dark p-0" type="button"
                                                    id="noteMenu{{ $note->id }}" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <span style="font-size:1.5rem;"><i class="bi bi-three-dots"></i></span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end"
                                                    aria-labelledby="noteMenu{{ $note->id }}">
                                                    <li>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                            data-bs-target="#editNoteModal{{ $note->id }}">Edit</a>
                                                    </li>
                                                    <li>
                                                        @if(!$note->pinned)
                                                            <form action="{{ route('notes.pin', $note->id) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Pin this note</button>
                                                            </form>
                                                        @else
                                                            <form action="{{ route('notes.unpin', $note->id) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Unpin</button>
                                                            </form>
                                                        @endif
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('notes.destroy', $note->id) }}" method="POST"
                                                            onsubmit="return confirm('Delete this note?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="dropdown-item text-danger">Delete</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                    </div>
                                @empty
                                    <div class="text-muted">No notes found.</div>
                                @endforelse
                            </div>
                            <div class="tab-pane fade" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <form action="{{ route('tasks.store') }}" method="POST" class="mb-3">
                                    @csrf
                                    <input type="hidden" name="related_type" value="{{$type}}">
                                    <input type="hidden" name="related_id" value="{{ $model->id }}">
                                    <div class="mb-2">
                                        <label for="task_name" class="form-label">Task Name</label>
                                        <input type="text" name="name" id="task_name" class="form-control" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="task_description" class="form-label">Description</label>
                                        <textarea name="description" id="task_description" class="form-control"
                                            rows="2"></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label for="task_due_at" class="form-label">Due Date</label>
                                        <input type="datetime-local" name="due_at" id="task_due_at" class="form-control"
                                            required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                </form>
                                <hr>
                                <h6>Tasks</h6>
                                @php
                                    $tasks = \App\Models\Task::where('related_type', $type)->where('related_id', $model->id)->orderByDesc('due_at')->get();
                                @endphp
                                @forelse($tasks as $task)
                                    <div class="mb-3 p-3 border rounded bg-light position-relative">
                                        <div class="fw-bold">{{ $task->name }}</div>
                                        <div>{{ $task->description }}</div>
                                        <span class="badge bg-secondary">Due
                                            {{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                        @if($task->completed_at)
                                            <span class="badge bg-success">Completed</span>
                                        @endif
                                        <!-- Three-dot dropdown -->
                                        <div class="position-absolute top-0 end-0 p-2">
                                            <div class="dropdown">
                                                <button class="btn btn-link text-dark p-0" type="button"
                                                    id="taskMenu{{ $task->id }}" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <span style="font-size:1.5rem;"><i class="bi bi-three-dots"></i></span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end"
                                                    aria-labelledby="taskMenu{{ $task->id }}">
                                                    <li>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                            data-bs-target="#editTaskModal{{ $task->id }}">Edit</a>
                                                    </li>
                                                    <li>
                                                        @if(!$task->completed_at)
                                                            <form action="{{ route('tasks.complete', $task->id) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Mark as
                                                                    Completed</button>
                                                            </form>
                                                        @endif
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('tasks.destroy', $task->id) }}" method="POST"
                                                            onsubmit="return confirm('Delete this task?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="dropdown-item text-danger">Delete</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                    </div>
                                @empty
                                    <div class="text-muted">No tasks found.</div>
                                @endforelse
                            </div>
                            <div class="tab-pane fade" id="calls" role="tabpanel" aria-labelledby="calls-tab">
                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <form action="{{ route('calls.store') }}" method="POST" class="mb-3">
                                    @csrf
                                    <input type="hidden" name="related_type" value="{{$type}}">
                                    <input type="hidden" name="related_id" value="{{ $model->id }}">
                                    <div class="mb-2">
                                        <label for="call_name" class="form-label">Title</label>
                                        <input type="text" name="name" id="call_name" class="form-control" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="call_start_at" class="form-label">From Date</label>
                                        <input type="datetime-local" name="start_at" id="call_start_at" class="form-control"
                                            required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="call_finish_at" class="form-label">To Date</label>
                                        <input type="datetime-local" name="finish_at" id="call_finish_at"
                                            class="form-control" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="call_user_participant_id" class="form-label">Participants</label>
                                        <select name="user_call_participant_id[]" id="call_user_participant_id"
                                            class="form-control" multiple>
                                            @foreach(\App\Models\Person::where('organization_id', $model->organization_id)->get() as $person)
                                                <option value="{{ $person->id }}">{{ $person->first_name }}
                                                    {{ $person->last_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                       
                                    </div>
                                    <div class="mb-2">
                                        <label for="call_location" class="form-label">Location</label>
                                        <input type="text" name="location" id="call_location" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label for="call_description" class="form-label">Description</label>
                                        <textarea name="description" id="call_description" class="form-control"
                                            rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                </form>
                                <hr>
                                <h6>Calls</h6>
                                @php
                                    $calls = \App\Models\Call::where('related_type', $type)->where('related_id', $model->id)->orderByDesc('start_at')->get();
                                @endphp
                                @forelse($calls as $call)
                                    <div class="mb-3 p-3 border rounded bg-light position-relative">
                                        <div class="fw-bold">{{ $call->name }}</div>
                                        <div>{{ $call->description }}</div>
                                        <span class="badge bg-secondary">From
                                            {{ $call->start_at ? \Carbon\Carbon::parse($call->start_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                        <span class="badge bg-secondary">To
                                            {{ $call->finish_at ? \Carbon\Carbon::parse($call->finish_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                        <span class="badge bg-light text-dark border">Location:
                                            {{ $call->location ?? '-' }}</span>
                                        <span class="badge bg-info">Participants:
                                            @if($call->user_restored_id)
                                                @php
                                                    $participantIds = json_decode($call->user_restored_id, true);
                                                    $names = collect($participantIds)->map(function ($id) {
                                                        $person = \App\Models\Person::find($id);
                                                        return $person ? $person->first_name . ' ' . $person->last_name : null;
                                                    })->filter()->toArray();
                                                @endphp
                                                {{ implode(', ', $names) }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                        <!-- Three-dot dropdown -->
                                        <div class="position-absolute top-0 end-0 p-2">
                                            <div class="dropdown">
                                                <button class="btn btn-link text-dark p-0" type="button"
                                                    id="callMenu{{ $call->id }}" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <span style="font-size:1.5rem;"><i class="bi bi-three-dots"></i></span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end"
                                                    aria-labelledby="callMenu{{ $call->id }}">
                                                    <li>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                            data-bs-target="#editCallModal{{ $call->id }}">Edit</a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('calls.destroy', $call->id) }}" method="POST"
                                                            onsubmit="return confirm('Delete this call?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="dropdown-item text-danger">Delete</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                    </div>
                                @empty
                                    <div class="text-muted">No calls found.</div>
                                @endforelse
                            </div>
                            <div class="tab-pane fade" id="meetings" role="tabpanel" aria-labelledby="meetings-tab">
                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <form action="{{ route('meetings.store') }}" method="POST" class="mb-3">
                                    @csrf
                                    <input type="hidden" name="related_type" value="{{$type}}">
                                    <input type="hidden" name="related_id" value="{{ $model->id }}">
                                    <div class="mb-2">
                                        <label for="meeting_name" class="form-label">Title</label>
                                        <input type="text" name="name" id="meeting_name" class="form-control" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="meeting_start_at" class="form-label">From Date</label>
                                        <input type="datetime-local" name="start_at" id="meeting_start_at"
                                            class="form-control" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="meeting_finish_at" class="form-label">To Date</label>
                                        <input type="datetime-local" name="finish_at" id="meeting_finish_at"
                                            class="form-control" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="meeting_user_owner_id" class="form-label">Host</label>
                                        <select name="user_owner_id" id="meeting_user_owner_id" class="form-control">
                                            <option value="">Select User</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" @if(old('user_owner_id', $model->owner ? $model->owner->id : null) == $user->id) selected @endif>{{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label for="meeting_user_participant_id" class="form-label">Participants</label>
                                        <select name="user_participant_id[]" id="meeting_user_participant_id"
                                            class="form-control" multiple>
                                            @foreach(\App\Models\Person::where('organization_id', $model->organization_id)->get() as $person)
                                                <option value="{{ $person->id }}">{{ $person->first_name }}
                                                    {{ $person->last_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label for="meeting_location" class="form-label">Location</label>
                                        <input type="text" name="location" id="meeting_location" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label for="meeting_description" class="form-label">Description</label>
                                        <textarea name="description" id="meeting_description" class="form-control"
                                            rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                </form>
                                <hr>
                                <h6>Meetings</h6>
                                @php
                                    $meetings = \App\Models\Meeting::where('related_type', $type)->where('related_id', $model->id)->orderByDesc('start_at')->get();
                                @endphp
                                @forelse($meetings as $meeting)
                                    <div class="mb-3 p-3 border rounded bg-light position-relative">
                                        <div class="fw-bold">{{ $meeting->name }}</div>
                                        <div>{{ $meeting->description }}</div>
                                        <span class="badge bg-secondary">From
                                            {{ $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                        <span class="badge bg-secondary">To
                                            {{ $meeting->finish_at ? \Carbon\Carbon::parse($meeting->finish_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                        <span class="badge bg-light text-dark border">Location:
                                            {{ $meeting->location ?? '-' }}</span>
                                        <span class="badge bg-info">Participants:
                                            @if($meeting->user_restored_id)
                                                @php
                                                    $participantIds = json_decode($meeting->user_restored_id, true);
                                                    $names = collect($participantIds)->map(function ($id) {
                                                        $person = \App\Models\Person::find($id);
                                                        return $person ? $person->first_name . ' ' . $person->last_name : null;
                                                    })->filter()->toArray();
                                                @endphp
                                                {{ implode(', ', $names) }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                        <!-- Three-dot dropdown -->
                                        <div class="position-absolute top-0 end-0 p-2">
                                            <div class="dropdown">
                                                <button class="btn btn-link text-dark p-0" type="button"
                                                    id="meetingMenu{{ $meeting->id }}" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <span style="font-size:1.5rem;"><i class="bi bi-three-dots"></i></span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end"
                                                    aria-labelledby="meetingMenu{{ $meeting->id }}">
                                                    <li>
                                                        <a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                            data-bs-target="#editMeetingModal{{ $meeting->id }}">Edit</a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('meetings.destroy', $meeting->id) }}"
                                                            method="POST" onsubmit="return confirm('Delete this meeting?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="dropdown-item text-danger">Delete</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                    </div>
                                @empty
                                    <div class="text-muted">No meetings found.</div>
                                @endforelse
                            </div>
                            <div class="tab-pane fade" id="files" role="tabpanel" aria-labelledby="files-tab">Files content
                                here...</div>
                        </div>


                           <!-- Edit Note Modal -->
    <div class="modal fade" id="editNoteModal{{ $note->id }}" tabindex="-1"
        aria-labelledby="editNoteModalLabel{{ $note->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('notes.update', $note->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editNoteModalLabel{{ $note->id }}">Edit
                            Note</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="edit_note_content{{ $note->id }}" class="form-label">Note</label>
                            <textarea name="content" id="edit_note_content{{ $note->id }}" class="form-control" rows="2"
                                required>{{ $note->content }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label for="edit_noted_at{{ $note->id }}" class="form-label">Note at</label>
                            <input type="datetime-local" name="noted_at" id="edit_noted_at{{ $note->id }}"
                                class="form-control"
                                value="{{ $note->noted_at ? \Carbon\Carbon::parse($note->noted_at)->format('Y-m-d\TH:i') : '' }}"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div class="modal fade" id="editTaskModal{{ $task->id }}" tabindex="-1"
        aria-labelledby="editTaskModalLabel{{ $task->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('tasks.update', $task->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTaskModalLabel{{ $task->id }}">Edit
                            Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="edit_task_name{{ $task->id }}" class="form-label">Task Name</label>
                            <input type="text" name="name" id="edit_task_name{{ $task->id }}" class="form-control"
                                value="{{ $task->name }}" required>
                        </div>
                        <div class="mb-2">
                            <label for="edit_task_description{{ $task->id }}" class="form-label">Description</label>
                            <textarea name="description" id="edit_task_description{{ $task->id }}" class="form-control"
                                rows="2">{{ $task->description }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label for="edit_task_due_at{{ $task->id }}" class="form-label">Due Date</label>
                            <input type="datetime-local" name="due_at" id="edit_task_due_at{{ $task->id }}"
                                class="form-control"
                                value="{{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('Y-m-d\TH:i') : '' }}"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Edit Call Modal -->
    <div class="modal fade" id="editCallModal{{ $call->id }}" tabindex="-1"
        aria-labelledby="editCallModalLabel{{ $call->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('calls.update', $call->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCallModalLabel{{ $call->id }}">Edit
                            Call</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="edit_call_name{{ $call->id }}" class="form-label">Title</label>
                            <input type="text" name="name" id="edit_call_name{{ $call->id }}" class="form-control"
                                value="{{ $call->name }}" required>
                        </div>
                        <div class="mb-2">
                            <label for="edit_call_start_at{{ $call->id }}" class="form-label">From Date</label>
                            <input type="datetime-local" name="start_at" id="edit_call_start_at{{ $call->id }}"
                                class="form-control"
                                value="{{ $call->start_at ? \Carbon\Carbon::parse($call->start_at)->format('Y-m-d\TH:i') : '' }}"
                                required>
                        </div>
                        <div class="mb-2">
                            <label for="edit_call_finish_at{{ $call->id }}" class="form-label">To Date</label>
                            <input type="datetime-local" name="finish_at" id="edit_call_finish_at{{ $call->id }}"
                                class="form-control"
                                value="{{ $call->finish_at ? \Carbon\Carbon::parse($call->finish_at)->format('Y-m-d\TH:i') : '' }}"
                                required>
                        </div>
                        <div class="mb-2">
                            <label for="edit_call_user_participant_id{{ $call->id }}"
                                class="form-label">Participants</label>
                            <select name="user_call_participant_id[]" id="edit_call_user_participant_id{{ $call->id }}"
                                class="form-control" multiple>
                                @foreach(\App\Models\Person::where('organization_id', $lead->organization_id)->get() as $person)
                                    <option value="{{ $person->id }}" @if(in_array($person->id, json_decode($call->user_restored_id ?? '[]'))) selected @endif>{{ $person->first_name }}
                                        {{ $person->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            <!-- <label for="edit_call_user_owner_id{{ $call->id }}" class="form-label">Contact Person</label>
                                                                        <select name="user_owner_id" id="edit_call_user_owner_id{{ $call->id }}" class="form-control">
                                                                            <option value="">Select Contact</option>
                                                                            @foreach(\App\Models\Person::where('organization_id', $lead->organization_id)->get() as $person)
                                                                                <option value="{{ $person->id }}" @if($call->user_owner_id == $person->id) selected @endif>{{ $person->first_name }} {{ $person->last_name }}</option>
                                                                            @endforeach
                                                                        </select> -->
                        </div>
                        <div class="mb-2">
                            <label for="edit_call_location{{ $call->id }}" class="form-label">Location</label>
                            <input type="text" name="location" id="edit_call_location{{ $call->id }}" class="form-control"
                                value="{{ $call->location }}">
                        </div>
                        <div class="mb-2">
                            <label for="edit_call_description{{ $call->id }}" class="form-label">Description</label>
                            <textarea name="description" id="edit_call_description{{ $call->id }}" class="form-control"
                                rows="2">{{ $call->description }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Meeting Modal -->
    <div class="modal fade" id="editMeetingModal{{ $meeting->id }}" tabindex="-1"
        aria-labelledby="editMeetingModalLabel{{ $meeting->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('meetings.update', $meeting->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMeetingModalLabel{{ $meeting->id }}">Edit Meeting</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="edit_meeting_name{{ $meeting->id }}" class="form-label">Title</label>
                            <input type="text" name="name" id="edit_meeting_name{{ $meeting->id }}" class="form-control"
                                value="{{ $meeting->name }}" required>
                        </div>
                        <div class="mb-2">
                            <label for="edit_meeting_start_at{{ $meeting->id }}" class="form-label">From Date</label>
                            <input type="datetime-local" name="start_at" id="edit_meeting_start_at{{ $meeting->id }}"
                                class="form-control"
                                value="{{ $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('Y-m-d\TH:i') : '' }}"
                                required>
                        </div>
                        <div class="mb-2">
                            <label for="edit_meeting_finish_at{{ $meeting->id }}" class="form-label">To Date</label>
                            <input type="datetime-local" name="finish_at" id="edit_meeting_finish_at{{ $meeting->id }}"
                                class="form-control"
                                value="{{ $meeting->finish_at ? \Carbon\Carbon::parse($meeting->finish_at)->format('Y-m-d\TH:i') : '' }}"
                                required>
                        </div>
                        <div class="mb-2">
                            <label for="edit_meeting_user_owner_id{{ $meeting->id }}" class="form-label">Host</label>
                            <select name="user_owner_id" id="edit_meeting_user_owner_id{{ $meeting->id }}"
                                class="form-control">
                                <option value="">Select User</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @if($meeting->user_owner_id == $user->id) selected @endif>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="edit_meeting_user_participant_id{{ $meeting->id }}"
                                class="form-label">Participants</label>
                            <select name="user_participant_id[]" id="edit_meeting_user_participant_id{{ $meeting->id }}"
                                class="form-control" multiple>
                                @foreach(\App\Models\Person::where('organization_id', $lead->organization_id)->get() as $person)
                                    <option value="{{ $person->id }}" @if(in_array($person->id, json_decode($meeting->user_restored_id ?? '[]'))) selected @endif>
                                        {{ $person->first_name }} {{ $person->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="edit_meeting_location{{ $meeting->id }}" class="form-label">Location</label>
                            <input type="text" name="location" id="edit_meeting_location{{ $meeting->id }}"
                                class="form-control" value="{{ $meeting->location }}">
                        </div>
                        <div class="mb-2">
                            <label for="edit_meeting_description{{ $meeting->id }}" class="form-label">Description</label>
                            <textarea name="description" id="edit_meeting_description{{ $meeting->id }}"
                                class="form-control" rows="2">{{ $meeting->description }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Select2 CSS/JS for participants -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#meeting_user_participant_id').select2({
                placeholder: 'Select participants',
                allowClear: true,
                width: '100%'
            });
        });
    </script>