@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row align-items-stretch" style="min-height: 100%;">
        <div class="col-lg-6 d-flex mb-3 mb-lg-0">
            <div class="card mt-0 h-100 flex-grow-1 w-100" style="max-height: auto;">
                <div class="card-body d-flex flex-column h-100" style="max-height: 100%;">
                    @php
                    $selectedFyId = session('selected_financial_year', null);
                    $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
                    $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
                    $quotes = $quotes ?? collect();
                    $meetings = $meetings ?? collect();
                    $contacts = $contacts ?? collect();
                    @endphp

                    <h3 class="mb-2">{{ $deal->title }}
                        <span class="badge bg-light text-dark border ms-2" style="font-size: 0.8rem; vertical-align: middle;">{{ $deal->status_label ?? ucfirst(str_replace('_', ' ', $deal->status)) }}</span>
                    </h3>
                    <hr>
                    <div class="mb-2"><strong>COMPANY</strong></div>
                    <div class="mb-2 d-flex align-items-center text-secondary"><span class="me-2"><i class="bi bi-buildings"></i></span> {{ optional($deal->organization)->name ?? ($deal->organization_name ?? '-') }}</div>
                    <div class="mb-2 d-flex align-items-center text-secondary"><span class="me-2"><i class="bi bi-geo-alt"></i></span>
                        @if($deal->organization)
                        {{ $deal->organization->address ?? '-' }}
                        {{ $deal->organization->city ? ', ' . $deal->organization->city : '' }}
                        {{ $deal->organization->state ? ', ' . $deal->organization->state : '' }}
                        {{ $deal->organization->country ? ', ' . $deal->organization->country : '' }}
                        @else
                        -
                        @endif
                    </div>
                     <div class="mb-2 d-flex align-items-center text-secondary"><span class="me-2"><i
                                    class="bi bi-geo-alt"></i></span>
                            @if($deal->organization && $deal->organization->website)
                            <a href="{{ optional($deal->organization)->website ?? '#' }}" target="_blank">{{ optional($deal->organization)->website }}</a>
                            @else
                            -
                            @endif
                        </div>

                    <hr>
                    <div class="mb-2"><strong>CONTACT PERSON</strong></div>
                    <div class="mb-2 d-flex align-items-center text-secondary"><span class="me-2"><i class="bi bi-person"></i></span> {{ optional($deal->person)->first_name ? optional($deal->person)->first_name . ' ' . optional($deal->person)->last_name : ($deal->person_name ?? '-') }}</div>
                    @if(!empty(optional($deal->person)->job_title))
                    <div class="mb-2 d-flex align-items-center text-secondary"><span class="me-2"><i class="bi bi-briefcase"></i></span> {{ $deal->person->job_title }}</div>
                    @endif
                    <div class="mb-2 d-flex align-items-center text-secondary"><span class="me-2"><i class="bi bi-phone"></i></span> {{ optional($deal->person)->mobile ?? '-' }}</div>
                    @if(!empty(optional($deal->person)->phone))
                    <div class="mb-2 d-flex align-items-center text-secondary"><span class="me-2"><i class="bi bi-telephone"></i></span> {{ $deal->person->phone }}</div>
                    @endif
                    <div class="mb-2 d-flex align-items-center text-secondary"><span class="me-2"><i class="bi bi-envelope"></i></span> {{ optional($deal->person)->email ?? '-' }}</div>
                    <hr>
                    <div class="mb-2"><strong>COMPANY OWNER</strong></div>
                    <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-credit-card-2-front"></i></span> {{ optional($deal->customer)->name ?? ($deal->customer_name ?? '-') }}</div>

                    <hr>
                    <h6 class="mb-3">DETAILS</h6>
                    <div class="mb-2 d-flex align-items-center"> <span class="me-2"><i class="bi bi-bookmark"></i></span> @if($deal->label === 'high')<span class="badge bg-success">High</span>@elseif($deal->label === 'normal')<span class="badge bg-info text-white">Normal</span>@elseif($deal->label === 'low')<span class="badge bg-warning text-dark">Low</span>@endif</div>
                    <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-currency-rupee"></i></span>
                        <!-- {{ $deal->amount_currency ?? '₹' }} -->
                        {{ \App\Helpers\MoneyFormatter::format($deal->amount) }}
                    </div>
                    <!-- <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-info-circle"></i></span> {{ $deal->description ?? '-' }}</div> -->
                    <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-flag"></i></span> {{ optional($deal->dealSource)->name ?? '-' }}</div>
                    @php
                        $dealSourceName = optional($deal->dealSource)->name ?? '';
                        $showDealReferral = \App\Models\LeadSource::isReferralSourceName($dealSourceName)
                            || \App\Models\LeadSource::isReferralSourceId($deal->lead_source);
                    @endphp
                    @if($showDealReferral && !empty($deal->referral_name))
                    <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-person-check"></i></span> {{ $deal->referral_name }}</div>
                    @endif
                    <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-calendar-check"></i></span> {{ $deal->close_date ? \Carbon\Carbon::parse($deal->close_date)->format('d M Y') : '-' }}</div>
                    <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-bar-chart-steps"></i></span> {{ $deal->stage_name ?? '-'}}</div>
                    <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-percent"></i></span> {{ $deal->probability ? $deal->probability  : '-' }}</div>
                    <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-person-circle"></i></span> {{ $deal->owner->name ?? '-' }}</div>

                    <hr>
                    <div class="mb-2"><strong>DESCRIPTION</strong></div>
                      <div class="mb-2 d-flex align-items-center"><span class="me-2"><i
                                class="bi bi-info-circle"></i></span> {{ $deal->description ?? '-' }}</div>

                    <hr>
                                        <div class="mb-2"><strong>INDUSTRIES</strong></div>
                                        <div class="mb-2 d-flex align-items-center"><span class="me-2"><i class="bi bi-diagram-3"></i></span>
                                                {{ optional($deal->industry)->name ?? 'N/A' }}
                                        </div>

                                        <hr>
                     <div class="mb-2"><strong>PRODUCT/SERVICES</strong></div>
                      <!-- <div class="mb-2 d-flex align-items-center"> -->
                        @if(!empty($deal->category_names))
                            @foreach($deal->category_names as $categoryName)
                                <div>{{ $categoryName }}</div>
                            @endforeach
                        @else
                            N/A
                        @endif
                      <!-- </div> -->
                    <hr>
                    <div class="deal-quotes-section">
                        <div class="deal-quotes-toolbar">
                            <strong class="deal-quotes-title">Quotes</strong>
                            <div class="deal-quotes-actions">
                                @if(!$isHistorical && auth()->user()->hasCrmPermission('create_crm_quotations_guard'))
                                    <a href="{{ route('quotations.create', ['deal_id' => $deal->id]) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Create Quotation">New</a>
                                @endif
                                @if(!$isHistorical && auth()->user()->hasCrmPermission('edit_crm_quotations_guard') && $quotes->count() > 0)
                                    <a href="{{ route('quotations.edit', $quotes->first()->id) }}"
                                       class="btn btn-sm btn-outline-primary deal-quote-edit-btn"
                                       id="dealQuoteEditBtn"
                                       title="Edit selected quotation">Edit</a>
                                @endif
                            </div>
                        </div>

                        @if($quotes->count() > 0)
                            <div class="table-responsive deal-quotes-table-wrap">
                                <table class="table table-bordered deal-quotes-table mb-0">
                                    <thead>
                                        <tr>
                                            @if(!$isHistorical && auth()->user()->hasCrmPermission('edit_crm_quotations_guard'))
                                                <th style="width:36px;"></th>
                                            @endif
                                            <th>Quote No</th>
                                            <th>Subject</th>
                                            <th>Quote Stage</th>
                                            <th>Valid Until</th>
                                            <th>Quote Owner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($quotes as $quote)
                                            @php
                                                $validUntil = $quote->valid_until
                                                    ? \Carbon\Carbon::parse($quote->valid_until)->format('d/m/Y')
                                                    : '—';
                                                $canViewQuote = auth()->user()->hasCrmPermission('view_crm_quotations_guard')
                                                    || auth()->user()->hasCrmPermission('manage_crm_quotations_guard');
                                                $canEditQuote = !$isHistorical && auth()->user()->hasCrmPermission('edit_crm_quotations_guard');
                                                $subjectLabel = $quote->subject ?: $quote->quote_no;
                                            @endphp
                                            <tr class="deal-quote-row @if($loop->first) deal-quote-row--selected @endif"
                                                data-edit-url="{{ $canEditQuote ? route('quotations.edit', $quote->id) : '' }}">
                                                @if($canEditQuote)
                                                <td class="text-center">
                                                    <input type="radio"
                                                           name="deal_quote_select"
                                                           class="form-check-input deal-quote-select"
                                                           value="{{ $quote->id }}"
                                                           data-edit-url="{{ route('quotations.edit', $quote->id) }}"
                                                           @checked($loop->first)>
                                                </td>
                                                @endif
                                                <td>
                                                    @if($canViewQuote)
                                                        <a href="{{ route('quotations.show', $quote->id) }}" class="deal-quote-link">{{ $quote->quote_no }}</a>
                                                    @else
                                                        {{ $quote->quote_no }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($canViewQuote)
                                                        <a href="{{ route('quotations.show', $quote->id) }}" class="deal-quote-link">{{ $subjectLabel }}</a>
                                                    @else
                                                        {{ $subjectLabel }}
                                                    @endif
                                                </td>
                                                <td>{{ $quote->status ?: '—' }}</td>
                                                <td>{{ $validUntil }}</td>
                                                <td>{{ optional($quote->owner)->name ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="deal-quotes-empty text-muted small">N/A</div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
        <div class="col-lg-6 d-flex mb-3 mb-lg-0">
            <div class="card mt-0 h-100 flex-grow-1 w-100" style="max-height: 100%;">
                <div class="card-body d-flex flex-column h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex gap-2">
                            <a href="{{ route('deals.index') }}" class="btn btn-light btn-sm">&laquo; Back to Deals</a>
                        </div>
                        @if(!$isHistorical)

                        <div class="d-flex gap-1" role="group">
                            <a href="{{ route('deals.edit', $deal->id) }}" class="btn btn-outline-secondary btn-sm @if(!auth()->user()->hasCrmPermission('edit_crm_leads_guard')) disabled @endif"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('deals.destroy', $deal->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="delete-leads-btn btn btn-outline-danger btn-sm @if(!auth()->user()->hasCrmPermission('delete_crm_leads_guard')) disabled @endif" data-lead-name="{{ $deal->title }}"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @php
                    $activeTab = session('show_tasks_tab') ? 'tasks' : 'activity';
                    if (session('open_entity_gmail') || request()->boolean('open_entity_gmail') || request()->query('open_entity_gmail') === '1') {
                        $activeTab = 'email';
                    }
                    @endphp
                    <ul class="nav nav-tabs mb-3" id="dealTabs" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link @if($activeTab=='activity') active @endif" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">Activity</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link @if($activeTab=='notes') active @endif" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab">Notes</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link @if($activeTab=='tasks') active @endif" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">Tasks</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link @if($activeTab=='calls') active @endif" id="calls-tab" data-bs-toggle="tab" data-bs-target="#calls" type="button" role="tab">Calls</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link @if($activeTab=='meetings') active @endif" id="meetings-tab" data-bs-toggle="tab" data-bs-target="#meetings" type="button" role="tab">Meetings</button></li>
                        <!-- <li class="nav-item" role="presentation"><button class="nav-link" id="lunches-tab" data-bs-toggle="tab" data-bs-target="#lunches" type="button" role="tab">Lunches</button></li> -->
                        <li class="nav-item" role="presentation"><button class="nav-link @if($activeTab=='files') active @endif" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab">Files</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link @if($activeTab=='email') active @endif" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">Email</button></li>
                    </ul>
                    <div class="tab-content flex-grow-1 crm-entity-tabs-scroll" id="dealTabsContent">
                        <div class="tab-pane fade @if($activeTab=='activity') show active @endif" id="activity" role="tabpanel"
                            aria-labelledby="activity-tab">
                            <h6 class="activity-heading">All Activity</h6>
                            @php
                                $activityNotes = \App\Models\Note::where('related_type', 'deal')->where('related_id', $deal->id)->orderByDesc('noted_at')->get();
                                $activityTasks = \App\Models\Task::where('related_type', 'deal')->where('related_id', $deal->id)->orderByDesc('due_at')->get();
                                $activityCalls = \App\Models\Call::where('related_type', 'deal')->where('related_id', $deal->id)->orderByDesc('start_at')->get();
                                $activityMeetings = \App\Models\Meeting::where('related_type', 'deal')->where('related_id', $deal->id)->orderByDesc('start_at')->get();
                                $hasActivity = $activityNotes->isNotEmpty() || $activityTasks->isNotEmpty() || $activityCalls->isNotEmpty() || $activityMeetings->isNotEmpty();
                            @endphp
                            {{-- mt-0 cancels Bootstrap row gutter negative margin that overlaps the heading when empty --}}
                            <div class="row g-3 mt-0">
                                @foreach($activityNotes as $note)
                                @php $isGmailNote = str_starts_with((string) $note->content, '[Gmail Email]'); @endphp
                                <div class="col-12">
                                    <div id="task-{{ $note->id }}" class="card shadow-sm border  bg-light rounded mb-2">
                                        <div class="card-body position-relative">

                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                @if($isGmailNote)
                                                    <span class="badge bg-danger mb-2"><i class="bi bi-envelope me-1"></i>Email</span>
                                                @else
                                                    <span class="badge bg-info mb-2">Note</span>
                                                @endif
                                                <span class="badge text-dark">{{ $note->created_at->diffForHumans() }} </span>
                                            </div>
                                            <div class="fw-bold mb-1">
                                                {{ $note->created_by ? optional(\App\Models\User::find($note->created_by))->name : 'Unknown' }}
                                            </div>
                                            <div class="mb-2" style="{{ $isGmailNote ? 'white-space:pre-wrap;' : '' }}">{{ $isGmailNote ? preg_replace('/^\[Gmail Email\]\s*/', '', (string) $note->content) : $note->content }}</div>
                                            <span
                                                class="badge bg-secondary">{{ $note->noted_at ? \Carbon\Carbon::parse($note->noted_at)->format('h:i A \o\n M d, Y') : '-' }}</span>

                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                @foreach($activityTasks as $task)
                                <div class="col-12">
                                    <div class="card shadow-sm border  bg-light rounded mb-2">
                                        <div class="card-body position-relative">
                                            <div class="d-flex justify-content-start align-items-center mb-1">
                                                <span class="badge bg-warning mb-2">Task</span>
                                                <span class="badge text-dark mb-2">{{ $task->created_at->diffForHumans() }}</span>
                                            </div>
                                            <div class="fw-bold mb-1">{{ $task->name }}</div>
                                            <div class="mb-2">{{ $task->description }}</div>
                                            <span class="badge bg-info">Priority: {{ ucfirst($task->priority) }}</span>
                                            @if ($task->completed_at)
                                            <span class="badge bg-success ms-2">Status: {{ $task->status }} </span>
                                            @else
                                            <span class="badge bg-primary ms-2">Status: {{ $task->status }}</span>
                                            @endif

                                            <span class="badge bg-secondary ms-2">Due: {{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                            <div class="position-absolute top-0 end-0 p-2">
                                                    <div class="dropdown">
                                                        <button class="btn btn-link text-dark p-0" type="button"
                                                            id="taskMenu{{ $task->id }}" data-bs-toggle="dropdown"
                                                            aria-expanded="false">
                                                            <span style="font-size:1.5rem;"><i class="bi bi-three-dots"></i></span>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end"
                                                            aria-labelledby="taskMenu{{ $task->id }}">
                                                            <li><a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                                    data-bs-target="#editTaskModal{{ $task->id }}">Edit</a>
                                                            </li>
                                                            <li>
                                                                @if (!$task->completed_at)
                                                                <form action="{{ route('tasks.complete', $task->id) }}"
                                                                    method="POST">@csrf<button type="submit"
                                                                        class="dropdown-item">Mark Complete</button></form>
                                                                @endif
                                                            </li>
                                                            <li>
                                                                <form action="{{ route('tasks.destroy', $task->id) }}"
                                                                    method="POST"
                                                                    >@csrf
                                                                    @method('DELETE')<button type="button"
                                                                        class="delete-note-btn dropdown-item text-danger">Delete</button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
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
                                                 <input type="hidden" name="related_type" value="deal">
                                                <input type="hidden" name="related_id" value="{{ $task->related_id }}">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editTaskModalLabel{{ $task->id }}">Edit Task</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-2">
                                                        <label for="edit_task_name{{ $task->id }}" class="form-label">Task Name</label>
                                                        <input type="text" name="name" id="edit_task_name{{ $task->id }}" class="form-control" value="{{ $task->name }}" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="edit_task_description{{ $task->id }}" class="form-label">Description</label>
                                                        <textarea name="description" id="edit_task_description{{ $task->id }}" class="form-control" rows="2">{{ $task->description }}</textarea>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="edit_task_due_at{{ $task->id }}" class="form-label">Due Date</label>
                                                        <input type="datetime-local" name="due_at" id="edit_task_due_at{{ $task->id }}" class="form-control" value="{{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('Y-m-d\\TH:i') : '' }}" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="edit_task_priority{{ $task->id }}" class="form-label">Priority</label>
                                                        <select name="priority" id="edit_task_priority{{ $task->id }}" class="form-select" required>
                                                            <option value="normal" @if($task->priority=='normal') selected @endif>Normal</option>
                                                            <option value="high" @if($task->priority=='high') selected @endif>High</option>
                                                            <option value="highest" @if($task->priority=='highest') selected @endif>Highest</option> <!-- Fixed typo -->
                                                            <option value="low" @if($task->priority=='low') selected @endif>Low</option>
                                                            <option value="lowest" @if($task->priority=='lowest') selected @endif>Lowest</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="edit_task_status{{ $task->id }}" class="form-label">Status</label>
                                                        <select name="status" id="edit_task_status{{ $task->id }}" class="form-select" required>
                                                            <option value="Not Started" @if($task->status=='Not Started') selected @endif>Not Started</option>
                                                            <option value="Deferred" @if($task->status=='Deferred') selected @endif>Deferred</option>
                                                            <option value="In Progress" @if($task->status=='In Progress') selected @endif>In Progress</option>
                                                            <option value="Completed" @if($task->status=='Completed') selected @endif>Completed</option>
                                                            <option value="Waiting for input" @if($task->status=='Waiting for input') selected @endif>Waiting for input</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label for="edit_task_assigned_to{{ $task->id }}" class="form-label">Assign To</label>
                                                        <select name="user_assigned_id" id="edit_task_assigned_to{{ $task->id }}" class="form-select">
                                                            <option value="">-- Select User --</option>
                                                            @foreach (\App\Models\User::all() as $user)
                                                                <option value="{{ $user->id }}" @if($task->user_assigned_id == $user->id) selected @endif>{{ $user->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-md-6 d-flex align-items-center">
                                                            <div class="form-check">
                                                                <input type="hidden" name="reminder_notifications_enabled" value="0">
                                                                <input class="form-check-input" type="checkbox" value="1" id="reminderEnabledEdit1{{ $task->id }}" name="reminder_notifications_enabled" @if($task->reminder_notifications_enabled) checked @endif>
                                                                <label class="form-check-label" for="reminderEnabledEdit1{{ $task->id }}">Enable Reminder Notification</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 d-flex align-items-center" id="reminderOffsetWrapperEdit1{{ $task->id }}" style="align-items:center; gap:10px; @if(!$task->reminder_notifications_enabled) visibility:hidden; height:0; @endif">
                                                        <label class="form-label mb-0">Reminder Time</label>
                                                        <select class="form-select" name="reminder_offset" id="reminderOffsetEdit1{{ $task->id }}" style="width:160px;">
                                                            <option value="5" @if($task->reminder_offset==5) selected @endif>5 minutes before</option>
                                                            <option value="15" @if($task->reminder_offset==15) selected @endif>15 minutes before</option>
                                                            <option value="30" @if($task->reminder_offset==30) selected @endif>30 minutes before</option>
                                                            <option value="60" @if($task->reminder_offset==60) selected @endif>1 hour before</option>
                                                            <option value="120" @if($task->reminder_offset==120) selected @endif>2 hours before</option>
                                                        </select>
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
                                <script>
                                    function toggleEditReminderOffset1{{ $task->id }}() {
                                        const $checkbox = $('#reminderEnabledEdit1{{ $task->id }}');
                                        const $wrapper = $('#reminderOffsetWrapperEdit1{{ $task->id }}');
                                        if (!$checkbox.length || !$wrapper.length) {
                                            console.debug('toggleEditReminderOffset1{{ $task->id }}: missing elements', $checkbox.length, $wrapper.length);
                                            return;
                                        }
                                        const checked = $checkbox.is(':checked');
                                        console.debug('toggleEditReminderOffset1{{ $task->id }}:', checked);
                                        if (checked) {
                                            $wrapper.css({'visibility':'visible','height':'auto'});
                                        } else {
                                            $wrapper.css({'visibility':'hidden','height':'0'});
                                        }
                                    }
                                    $(document).ready(function() {
                                        $('#reminderEnabledEdit1{{ $task->id }}').on('change', toggleEditReminderOffset1{{ $task->id }});
                                        toggleEditReminderOffset1{{ $task->id }}();
                                    });
                                </script>
                                @endforeach
                                @foreach($activityCalls as $call)
                                <div class="col-12">
                                    <div class="card shadow-sm border  bg-light rounded mb-2">
                                        <div class="card-body position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="badge bg-primary mb-2">Call</span>
                                                <span class="badge text-dark">{{ $call->created_at->diffForHumans() }} </span>
                                            </div>
                                            <div class="fw-bold mb-1">{{ $call->name }}</div>
                                            <div class="mb-2">{{ $call->description }}</div>
                                            <span
                                                class="badge bg-secondary">{{ $call->start_at ? \Carbon\Carbon::parse($call->start_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                            <div class="mt-2">
                                                <span class="fw-bold">Location:</span>
                                                <span class="badge bg-light text-dark border">
                                                    {{ $call->location ?? '-' }}</span>
                                            </div>
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

                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                @foreach($activityMeetings as $meeting)
                                <div class="col-12">
                                    <div class="card shadow-sm border  bg-light rounded mb-2">
                                        <div class="card-body position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="badge bg-success mb-2">Meeting</span>
                                                <span class="badge text-dark">{{ $meeting->created_at->diffForHumans() }} </span>
                                            </div>
                                            <div class="fw-bold mb-1">{{ $meeting->name }}</div>
                                            <div class="mb-2">{{ $meeting->description }}</div>
                                            <span
                                                class="badge bg-secondary">{{ $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                            <div class="mt-2">
                                                <span class="fw-bold">Location:</span>
                                                <span class="badge bg-light text-dark ms-2">
                                                    {{ $meeting->location ?? '-' }}</span>
                                            </div>
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

                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @unless($hasActivity)
                                <div class="text-muted small py-3">No activity yet.</div>
                            @endunless
                        </div>
                        <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                            <!-- @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                            @endif -->
                            @if($isHistorical)
                            <div class="alert alert-secondary mb-3">Creating notes is disabled for historical financial years.</div>
                            @else
                            <form action="{{ route('notes.store') }}" method="POST" class="mb-3">
                                @csrf
                                <input type="hidden" name="related_type" value="deal">
                                <input type="hidden" name="related_id" value="{{ $deal->id }}">
                                <div class="mb-2">
                                    <label for="note_content" class="form-label">Note</label>
                                    <textarea name="content" id="note_content" class="form-control" rows="2"
                                        required></textarea>
                                </div>
                                <div class="mb-2">
                                    <label for="noted_at" class="form-label">Note at</label>
                                    <input type="datetime-local" name="noted_at" id="noted_at" class="form-control"
                                        required value="{{ now()->format('Y-m-d\\TH:i') }}">
                                </div>
                                <button type="submit" class="btn btn-custom">Save Notes</button>
                            </form>
                            @endif
                            <hr>
                            <h6>Notes</h6>
                            @php
                            $notes = \App\Models\Note::where('related_type', 'deal')->where('related_id', $deal->id)->orderByDesc('noted_at')->get();
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
                                            @if($isHistorical)
                                            <li><button class="dropdown-item disabled" type="button" title="Editing disabled for historical years">Edit</button></li>
                                            <li><button class="dropdown-item disabled" type="button" title="Pin/unpin disabled for historical years">Pin/Unpin</button></li>
                                            <li><button class="dropdown-item text-danger disabled" type="button" title="Deleting disabled for historical years">Delete</button></li>
                                            @else
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
                                                    >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                        class="delete-note-btn dropdown-item text-danger">Delete</button>
                                                </form>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
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
                                                    <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-custom">Save changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-muted">No notes found.</div>
                            @endforelse
                        </div>

                        <!---Task Section --->
                        <div class="tab-pane fade @if($activeTab=='tasks') show active @endif" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">

                            @if($isHistorical)
                            <div class="alert alert-secondary mb-3">Creating tasks is disabled for historical financial years.</div>
                            @else
                            <form action="{{ route('tasks.store') }}" method="POST" class="mb-3">
                                @csrf
                                <input type="hidden" name="related_type" value="deal">
                                <input type="hidden" name="related_id" value="{{ $deal->id }}">
                                <div class="mb-2">
                                    <label for="task_name" class="form-label">Task Name</label>
                                    <input type="text" name="name" id="task_name" class="form-control" required>
                                </div>
                                <div class="mb-2">
                                    <label for="task_description" class="form-label">Description</label>
                                    <textarea name="description" id="task_description" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="mb-2">
                                    <label for="task_due_at" class="form-label">Due Date</label>
                                    <input type="datetime-local" name="due_at" id="task_due_at" class="form-control" required value="{{ now()->format('Y-m-d\\TH:i') }}">
                                </div>
                                <div class="mb-2">
                                    <label for="task_priority" class="form-label">Priority</label>
                                    <select name="priority" id="task_priority" class="form-select" required>
                                        <option value="normal" selected>Normal</option>
                                        <option value="high">High</option>
                                        <option value="highest">Highest</option>
                                        <option value="low">Low</option>
                                        <option value="lowest">Lowest</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label for="task_status" class="form-label">Status</label>
                                    <select name="status" id="task_status" class="form-select" required>
                                        <option value="Not Started" selected>Not Started</option>
                                        <option value="Deferred">Deferred</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Waiting for input">Waiting for input</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label for="task_assigned_to" class="form-label">Assign To</label>
                                    <select name="user_assigned_id" id="task_assigned_to" class="form-select">
                                        <option value="">-- Select User --</option>
                                       @foreach (\App\Models\User::all() as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6 d-flex align-items-center">
                                        <div class="form-check">
                                            <input type="hidden" name="reminder_notifications_enabled" value="0">
                                            <input class="form-check-input" type="checkbox" value="1" id="reminderEnabledCreate" name="reminder_notifications_enabled" checked>
                                            <label class="form-check-label" for="reminderEnabledCreate">Enable Reminder Notification</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-center" id="reminderOffsetWrapperCreate" style="visibility:hidden; height:0; align-items:center; gap:10px;">
                                        <label class="form-label mb-0">Reminder Time</label>
                                        <select class="form-select" name="reminder_offset" id="reminderOffsetCreate" style="width:160px;">
                                            <option value="5">5 minutes before</option>
                                            <option value="15">15 minutes before</option>
                                            <option value="30" selected>30 minutes before</option>
                                            <option value="60">1 hour before</option>
                                            <option value="120">2 hours before</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-custom">Create Task</button>
                            </form>
                            <script>
                            function toggleCreateReminderOffset() {
                                const $checkbox = $('#reminderEnabledCreate');
                                const $wrapper = $('#reminderOffsetWrapperCreate');
                                if (!$checkbox.length || !$wrapper.length) {
                                    console.debug('toggleCreateReminderOffset: missing elements', $checkbox.length, $wrapper.length);
                                    return;
                                }
                                const checked = $checkbox.is(':checked');
                                console.debug('toggleCreateReminderOffset:', checked);
                                if (checked) {
                                    $wrapper.css({'visibility':'visible','height':'auto'});
                                } else {
                                    $wrapper.css({'visibility':'hidden','height':'0'});
                                }
                            }
                            $(document).ready(function() {
                                $('#reminderEnabledCreate').on('change', toggleCreateReminderOffset);
                                toggleCreateReminderOffset();
                            });
                            </script>
                            @endif
                            <hr>
                            <h6>Tasks</h6>
                            <div class="row g-3">
                                @foreach (\App\Models\Task::where('related_type', 'deal')->where('related_id', $deal->id)->orderByDesc('due_at')->get() as $task)
                                <div class="col-12">
                                    <div id="task-{{ $task->id }}" class="card shadow-sm border  bg-light rounded mb-2">
                                        <div class="card-body position-relative">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="badge text-dark">{{ $task->created_at->diffForHumans() }}</span>
                                                <div class="position-absolute top-0 end-0 p-2">
                                                    <div class="dropdown">
                                                        <button class="btn btn-link text-dark p-0" type="button"
                                                            id="taskMenu{{ $task->id }}" data-bs-toggle="dropdown"
                                                            aria-expanded="false">
                                                            <span style="font-size:1.5rem;"><i class="bi bi-three-dots"></i></span>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end"
                                                            aria-labelledby="taskMenu{{ $task->id }}">
                                                            <li><a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                                    data-bs-target="#editTaskModal1{{ $task->id }}">Edit</a>
                                                            </li>
                                                            <li>
                                                                @if (!$task->completed_at)
                                                                <form action="{{ route('tasks.complete', $task->id) }}"
                                                                    method="POST">@csrf<button type="submit"
                                                                        class="dropdown-item">Mark Complete</button></form>
                                                                @endif
                                                            </li>
                                                            <li>
                                                                <form action="{{ route('tasks.destroy', $task->id) }}"
                                                                    method="POST"
                                                                    >@csrf
                                                                    @method('DELETE')<button type="button"
                                                                        class="delete-note-btn dropdown-item text-danger">Delete</button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="fw-bold mb-1">{{ $task->name }}</div>
                                            <div class="mb-2">{{ $task->description }}</div>
                                            <span class="badge bg-info">Priority: {{ ucfirst($task->priority) }}</span>
                                            @if ($task->completed_at)
                                            <span class="badge bg-success ms-2">Status: {{ $task->status }}</span>
                                            @else
                                            <span class="badge bg-primary ms-2">Status: {{ $task->status }}</span>
                                            @endif
                                            <span class="badge bg-secondary ms-2">Due: {{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                            <!-- Edit Task Modal -->
                                            <div class="modal fade" id="editTaskModal1{{ $task->id }}" tabindex="-1"
                                                aria-labelledby="editTaskModalLabel{{ $task->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('tasks.update', $task->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                             <input type="hidden" name="related_type" value="deal">
                                                            <input type="hidden" name="related_id" value="{{ $task->related_id }}">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editTaskModalLabel{{ $task->id }}">Edit Task</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-2">
                                                                    <label for="edit_task_name{{ $task->id }}" class="form-label">Task Name</label>
                                                                    <input type="text" name="name" id="edit_task_name{{ $task->id }}" class="form-control" value="{{ $task->name }}" required>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label for="edit_task_description{{ $task->id }}" class="form-label">Description</label>
                                                                    <textarea name="description" id="edit_task_description{{ $task->id }}" class="form-control" rows="2">{{ $task->description }}</textarea>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label for="edit_task_due_at{{ $task->id }}" class="form-label">Due Date</label>
                                                                    <input type="datetime-local" name="due_at" id="edit_task_due_at{{ $task->id }}" class="form-control" value="{{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('Y-m-d\\TH:i') : '' }}" required>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label for="edit_task_priority{{ $task->id }}" class="form-label">Priority</label>
                                                                    <select name="priority" id="edit_task_priority{{ $task->id }}" class="form-select" required>
                                                                        <option value="normal" @if($task->priority=='normal') selected @endif>Normal</option>
                                                                        <option value="high" @if($task->priority=='high') selected @endif>High</option>
                                                                        <option value="highest" @if($task->priority=='highest') selected @endif>Highest</option> <!-- Fixed typo -->
                                                                        <option value="low" @if($task->priority=='low') selected @endif>Low</option>
                                                                        <option value="lowest" @if($task->priority=='lowest') selected @endif>Lowest</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label for="edit_task_status{{ $task->id }}" class="form-label">Status</label>
                                                                    <select name="status" id="edit_task_status{{ $task->id }}" class="form-select" required>
                                                                        <option value="Not Started" @if($task->status=='Not Started') selected @endif>Not Started</option>
                                                                        <option value="Deferred" @if($task->status=='Deferred') selected @endif>Deferred</option>
                                                                        <option value="In Progress" @if($task->status=='In Progress') selected @endif>In Progress</option>
                                                                        <option value="Completed" @if($task->status=='Completed') selected @endif>Completed</option>
                                                                        <option value="Waiting for input" @if($task->status=='Waiting for input') selected @endif>Waiting for input</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label for="edit_task_assigned_to{{ $task->id }}" class="form-label">Assign To</label>
                                                                    <select name="user_assigned_id" id="edit_task_assigned_to{{ $task->id }}" class="form-select">
                                                                        <option value="">-- Select User --</option>
                                                                       @foreach (\App\Models\User::all() as $user)
                                                                            <option value="{{ $user->id }}" @if($task->user_assigned_id == $user->id) selected @endif>{{ $user->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6 d-flex align-items-center">
                                                                        <div class="form-check">
                                                                            <input type="hidden" name="reminder_notifications_enabled" value="0">
                                                                            <input class="form-check-input" type="checkbox" value="1" id="reminderEnabledEdit{{ $task->id }}" name="reminder_notifications_enabled" @if($task->reminder_notifications_enabled) checked @endif>
                                                                            <label class="form-check-label" for="reminderEnabledEdit{{ $task->id }}">Enable Reminder Notification</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6 d-flex align-items-center" id="reminderOffsetWrapperEdit{{ $task->id }}" style="align-items:center; gap:10px; @if(!$task->reminder_notifications_enabled) visibility:hidden; height:0; @endif">
                                                                    <label class="form-label mb-0">Reminder Time</label>
                                                                    <select class="form-select" name="reminder_offset" id="reminderOffsetEdit{{ $task->id }}" style="width:160px;">
                                                                        <option value="5" @if($task->reminder_offset==5) selected @endif>5 minutes before</option>
                                                                        <option value="15" @if($task->reminder_offset==15) selected @endif>15 minutes before</option>
                                                                        <option value="30" @if($task->reminder_offset==30) selected @endif>30 minutes before</option>
                                                                        <option value="60" @if($task->reminder_offset==60) selected @endif>1 hour before</option>
                                                                        <option value="120" @if($task->reminder_offset==120) selected @endif>2 hours before</option>
                                                                    </select>
                                                                </div>
                                                                <script>
                                                                function toggleEditReminderOffset{{ $task->id }}() {
                                                                    const $checkbox = $('#reminderEnabledEdit{{ $task->id }}');
                                                                    const $wrapper = $('#reminderOffsetWrapperEdit{{ $task->id }}');
                                                                    if (!$checkbox.length || !$wrapper.length) {
                                                                        console.debug('toggleEditReminderOffset{{ $task->id }}: missing elements', $checkbox.length, $wrapper.length);
                                                                        return;
                                                                    }
                                                                    const checked = $checkbox.is(':checked');
                                                                    console.debug('toggleEditReminderOffset{{ $task->id }}:', checked);
                                                                    if (checked) {
                                                                        $wrapper.css({'visibility':'visible','height':'auto'});
                                                                    } else {
                                                                        $wrapper.css({'visibility':'hidden','height':'0'});
                                                                    }
                                                                }
                                                                $(document).ready(function() {
                                                                    $('#reminderEnabledEdit{{ $task->id }}').on('change', toggleEditReminderOffset{{ $task->id }});
                                                                    toggleEditReminderOffset{{ $task->id }}();
                                                                });
                                                                </script>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-custom">Save changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <!--- Call Tab Section --->
                        <div class="tab-pane fade" id="calls" role="tabpanel" aria-labelledby="calls-tab">
                            <!-- @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                            @endif -->
                            @if($isHistorical)
                            <div class="alert alert-secondary mb-3">Creating Call is disabled for historical financial years.</div>
                            @else
                            <form action="{{ route('calls.store') }}" method="POST" class="mb-3">
                                @csrf
                                <input type="hidden" name="related_type" value="deal">
                                <input type="hidden" name="related_id" value="{{ $deal->id }}">
                                <div class="mb-2">
                                    <label for="call_name" class="form-label">Title</label>
                                    <input type="text" name="name" id="call_name" class="form-control" required>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label for="call_start_at" class="form-label">From Date</label>
                                        <input type="datetime-local" name="start_at" id="call_start_at" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="call_finish_at" class="form-label">To Date</label>
                                        <input type="datetime-local" name="finish_at" id="call_finish_at" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="call_user_participant_id" class="form-label">Participants</label>
                                    <select name="user_call_participant_id" id="call_user_participant_id"
                                        class="form-control">
                                        @foreach(\App\Models\Person::where('organization_id', $deal->organization_id)->get() as $person)
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
                                <button type="submit" class="btn btn-custom">Create Calls</button>
                            </form>
                            @endif
                            <hr>
                            <h6>Calls</h6>
                            @php
                            $calls = \App\Models\Call::where('related_type', 'deal')->where('related_id', $deal->id)->orderByDesc('start_at')->get();
                            @endphp
                            @forelse($calls as $call)
                            <div class="mb-3 p-3 border rounded bg-light position-relative">
                                <div class="mb-3"><span>{{ $call->created_at->diffForHumans() }} </span></div>
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
                                            @if($isHistorical)
                                            <li>
                                                <a href="#" class="dropdown-item disabled" data-bs-toggle="modal"
                                                    data-bs-target="#editCallModal{{ $call->id }}">Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('calls.destroy', $call->id) }}" method="POST"
                                                    >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                        class="delete-note-btn dropdown-item text-danger disabled">Delete</button>
                                                </form>
                                            </li>
                                            @else
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                    data-bs-target="#editCallModal{{ $call->id }}">Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('calls.destroy', $call->id) }}" method="POST"
                                                    >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                        class="delete-note-btn dropdown-item text-danger">Delete</button>
                                                </form>
                                            </li>
                                            @endif
                                        </ul>
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
                                                        <select name="user_call_participant_id"
                                                            id="edit_call_user_participant_id"
                                                            class="form-control" >
                                                            @foreach (\App\Models\Person::where('organization_id', $deal->organization_id)->get() as $person)
                                                                <option value="{{ $person->id }}" @if ($person->id == $call->user_restored_id) selected @endif>
                                                                    {{ $person->first_name }}
                                                                    {{ $person->last_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                       

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
                                                    <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-custom">Save changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-muted">No calls found.</div>
                            @endforelse
                        </div>

                        <!--- Meeting Tab Section --->
                        <div class="tab-pane fade" id="meetings" role="tabpanel" aria-labelledby="meetings-tab">
                            <!-- @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                            @endif -->
                            @if($isHistorical)
                            <div class="alert alert-secondary mb-3">Creating Meeting is disabled for historical financial years.</div>
                            @else
                            <form action="{{ route('meetings.store') }}" method="POST" class="mb-3">
                                        @csrf
                                         <input type="hidden" name="related_type" value="deal">
                                        <input type="hidden" name="related_id" value="{{ $deal->id }}">
                                        <div class="mb-2">
                                            <label for="meeting_name" class="form-label">Title</label>
                                            <input type="text" name="name" id="meeting_name" class="form-control" required>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <label for="meeting_start_at" class="form-label">From </label>
                                                <input type="datetime-local" name="start_at" id="meeting_start_at"
                                                    class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="meeting_finish_at" class="form-label">To </label>
                                                <input type="datetime-local" name="finish_at" id="meeting_finish_at"
                                                    class="form-control" required>
                                            </div>
                                        </div>
                                         <div class="mb-2">
                                            <label for="meeting_location" class="form-label">Venue</label>
                                            <select class="form-select" id="meetingVenue" name="venue" required>
                                                <option value="In-office">In-office</option>
                                                <option value="Client Location">Client Location</option>
                                                <option value="Online">Online</option>
                                            </select>
                                        </div>
                                        <div class="mb-2" id="locationField">
                                            <label for="meeting_location" class="form-label">Location</label>
                                            <input type="text" name="location" id="meeting_location" class="form-control " required>
                                        </div>

                                        <div class="mb-2">
                                            <label for="meeting_user_owner_id" class="form-label">Host</label>
                                            <select name="user_owner_id" id="meeting_user_owner_id" class="form-control">
                                                <option value="">Select User</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}" @if (old('user_owner_id', $deal->owner ? $deal->owner->id : null) == $user->id) selected @endif>{{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label for="meeting_user_participant_id" class="form-label">Participants</label>

                                            <div class="input-group">
                                                <input type="text" class="form-control" id="participantsList" name="participant_id"
                                                    placeholder="Add participants" readonly>
                                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                                    data-bs-target="#participantsModal">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <input type="hidden" id="participantIds" name="user_participant_id">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="hostReminder" class="form-label">Host Reminder</label>
                                            <select class="form-select" id="hostReminder" name="host_reminder">
                                                <option value="">None</option>
                                                <option value="at_time">At time of meeting</option>
                                                <option value="5_minutes">5 minutes before</option>
                                                <option value="10_minutes">10 minutes before</option>
                                                <option value="15_minutes">15 minutes before</option>
                                                <option value="30_minutes">30 minutes before</option>
                                                <option value="1_hour">1 hour before</option>
                                                <option value="2_hours">2 hours before</option>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label for="meeting_description" class="form-label">Description</label>
                                            <textarea name="description" id="meeting_description" class="form-control"
                                                rows="2"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-custom">Create Meeting</button>
                                    </form>

                            @endif
                            <hr>
                            <h6>Meetings</h6>

                            @forelse($meetings as $meeting)
                            <div class="mb-3 p-3 border rounded bg-light position-relative">
                                <div class="mb-3"><span>{{ $meeting->created_at->diffForHumans() }} </span></div>
                                <div class="fw-bold">{{ $meeting->name }}</div>
                                <div>{{ $meeting->description }}</div>
                                <span class="badge bg-secondary">From
                                    {{ $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                <span class="badge bg-secondary">To
                                    {{ $meeting->finish_at ? \Carbon\Carbon::parse($meeting->finish_at)->format('h:i A \o\n M d, Y') : '-' }}</span>
                                <span class="badge bg-light text-dark border">Location:
                                    {{ $meeting->location ?? '-' }}</span>
                                <span class="badge bg-info">Participants:
                                     @if (!$meeting->participants->isEmpty())
                                        @foreach($meeting->participants as $participant)
                                            @if($participant->type === 'user')
                                                @php
                                                    $user = \App\Models\User::find($participant->user_id);
                                                @endphp
                                                {{ $user ? $user->name : 'Unknown User' }}@if (!$loop->last), @endif
                                            @elseif($participant->type === 'contact')
                                                @php
                                                    $person = \App\Models\Person::find($participant->person_id);
                                                @endphp
                                                {{ $person ? $person->first_name . ' ' . $person->last_name : 'Unknown Contact' }}@if (!$loop->last), @endif
                                            @endif
                                        @endforeach
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
                                            @if($isHistorical)
                                            <li>
                                                <a href="#" class="dropdown-item disabled" data-bs-toggle="modal"
                                                    data-bs-target="#editMeetingModal{{ $meeting->id }}">Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('meetings.destroy', $meeting->id) }}"
                                                    method="POST" >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                        class="delete-note-btn dropdown-item text-danger disabled">Delete</button>
                                                </form>
                                            </li>
                                            @else
                                            <li>
                                                <!-- <a href="#" class="dropdown-item" data-bs-toggle="modal"
                                                    data-bs-target="#editMeetingModal{{ $meeting->id }}">Edit</a> -->
                                                    <a class="dropdown-item px-4 py-2 fs-6 edit-meeting" href="#"
                                                               data-meeting='@json($meeting)'>Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('meetings.destroy', $meeting->id) }}"
                                                    method="POST" onsubmit="return confirm('Delete this meeting?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                        class="delete-note-btn dropdown-item text-danger">Delete</button>
                                                </form>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>

                            </div>
                            @empty
                            <div class="text-muted">No meetings found.</div>
                            @endforelse
                        </div>

                        <div class="tab-pane fade" id="files" role="tabpanel" aria-labelledby="files-tab">
                            <!-- @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                            @endif -->
                            @if($isHistorical)
                                <div class="alert alert-secondary mb-3">Creating File is disabled for historical financial years.</div>
                            @else
                            <form action="{{ route('deals.files.store') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                                @csrf
                                <input type="hidden" name="related_id" value="{{ $deal->id }}">
                                <div class="mb-2">
                                    <label for="file_type" class="form-label">File Type</label>
                                    <select name="file_type" id="file_type" class="form-control" required onchange="toggleFileInput()">
                                        <option value="file upload">File Upload</option>
                                        <option value="file links">File Link</option>
                                    </select>
                                </div>
                                <div class="mb-2" id="file_upload_div">
                                    <label for="file_upload" class="form-label">Upload File (pdf, jpg, png, doc)</label>
                                    <input type="file" name="file_upload" id="file_upload" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                </div>
                                <div class="mb-2 d-none" id="file_link_div">
                                    <label for="file_link" class="form-label">File Link (URL)</label>
                                    <input type="url" name="file_link" id="file_link" class="form-control" placeholder="https://...">
                                </div>
                                <div class="mb-2">
                                    <label for="file_name" class="form-label">File Name</label>
                                    <input type="text" name="file_name" id="file_name" class="form-control" maxlength="150" required>
                                </div>
                                <button type="submit" class="btn btn-custom">Add File</button>
                            </form>
                        @endif
                            <hr>
                            <h6>Files</h6>
                            @php
                            $files = \App\Models\File::where('related_type', 'deal')->where('related_id', $deal->id)->orderByDesc('created_at')->get();
                            @endphp
                            @forelse($files as $file)
                            <div class="mb-3 p-3 border rounded bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between" style="box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                                <div>
                                    <a href="{{ $file->downloadUrl() }}" rel="noopener noreferrer"
                                        target="_blank" class="fw-bold text-primary"
                                        style="font-size:1.1rem; text-decoration:underline;">{{ $file->file_name }}</a>
                                    <div class="text-muted mt-1" style="font-size:0.95rem;">
                                        {{ $file->created_at->format('h:i A \o\n M d, Y') }}
                                        @if ($file->uploader)
                                        | {{ $file->uploader->name }}
                                        @endif
                                        @if ($file->file_type === 'file upload' && $file->file_path)
                                        |
                                        @php
                                            $fullPath = $file->absolutePath();
                                        @endphp

                                        {{ $fullPath
                                            ? number_format(filesize($fullPath) / 1024, 3) . ' kB'
                                            : ''
                                        }}
                                    @endif
                                    </div>
                                </div>
                                <div class="dropdown ms-md-2 mt-2 mt-md-0">
                                    <button class="btn btn-link text-dark p-0" type="button" id="fileMenu{{ $file->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span style="font-size:1.5rem;"><i class="bi bi-three-dots"></i></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="fileMenu{{ $file->id }}">
                                        @if($isHistorical)
                                            @if($file->file_type === 'file upload')
                                            <li>
                                                <a class="dropdown-item disabled" href="{{ route('deal-files.download', $file->id) }}">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            </li>
                                            @endif
                                            <li>
                                                <form action="{{ route('deals.files.destroy', $file->id) }}" method="POST" >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="delete-note-btn dropdown-item text-danger disabled">Delete</button>
                                                </form>
                                            </li>
                                        @else
                                        @if($file->file_type === 'file upload')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('deal-files.download', $file->id) }}">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </li>
                                        @endif
                                        <li>
                                            <form action="{{ route('deals.files.destroy', $file->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="delete-note-btn dropdown-item text-danger">Delete</button>
                                            </form>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            @empty
                            <div class="text-muted">No files found.</div>
                            @endforelse
                            <script>
                                function toggleFileInput() {
                                    var type = document.getElementById('file_type').value;
                                    document.getElementById('file_upload_div').classList.toggle('d-none', type !== 'file upload');
                                    document.getElementById('file_link_div').classList.toggle('d-none', type !== 'file links');
                                }
                                document.addEventListener('DOMContentLoaded', function() {
                                    toggleFileInput();
                                    @if(session('show_files_tab'))
                                    var tab = document.getElementById('files-tab');
                                    if (tab) tab.click();
                                    @endif
                                    @if(session('show_notes_tab'))
                                    var tab = document.getElementById('notes-tab');
                                    if (tab) tab.click();
                                    @endif
                                    @if(session('show_tasks_tab'))
                                    var tab = document.getElementById('tasks-tab');
                                    if (tab) tab.click();
                                    @endif
                                    @if(session('show_meeting_tab'))
                                    var tab = document.getElementById('meetings-tab');
                                    if (tab) tab.click();
                                    @endif
                                });
                            </script>
                        </div>

                        {{-- Email Tab --}}
                        <div class="tab-pane fade @if($activeTab=='email') show active @endif" id="email" role="tabpanel" aria-labelledby="email-tab">
                            @include('partials.entity_gmail_composer', [
                                'entityType' => 'deal',
                                'entity' => $deal,
                                'isHistorical' => $isHistorical,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- Participants Selection Modal (CREATE MEETING)               -->
<!-- ONLY CHANGE: added search input above the participant tables -->
<!-- ========================================================== -->
<div class="modal fade" id="participantsModal" tabindex="-1" aria-labelledby="participantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="participantsModalLabel">Add Participants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="participantType" class="form-label">Select Type</label>
                    <select class="form-select" id="participantType">
                        <option value="contacts">Contacts</option>
                        <!-- <option value="leads">Leads</option> -->
                        <option value="users">Users</option>
                    </select>
                </div>

                {{-- ★ ADDED: search input for create-participants modal --}}
                <div class="mb-3">
                    <input type="text" class="form-control" id="participantSearch"
                           placeholder="Search by name or email...">
                </div>

                <div id="participantsTableContainer">
                    <table class="table table-bordered d-none" id="contactsTable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Contact Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contacts as $contact)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selectedContacts" value="{{ $contact->id }}"
                                            data-name="{{ $contact->first_name }}">
                                    </td>
                                    <td>{{ $contact->first_name }}</td>
                                    <td>{{ $contact->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <table class="table table-bordered d-none" id="leadsTable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Lead Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leads as $lead)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selectedLeads" value="{{ $lead->id }}"
                                            data-name="{{ $lead->person->first_name }}">
                                    </td>
                                    <td>{{ $lead->person->first_name }}</td>
                                    <td>{{ $lead->person->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <table class="table table-bordered d-none" id="usersTable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>User Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selectedUsers" value="{{ $user->id }}"
                                            data-name="{{ $user->name }}">
                                    </td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-custom" id="addParticipantsButton">Done</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Meeting Modal -->
<div class="modal fade" id="editMeetingModal" tabindex="-1" aria-labelledby="editMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMeetingModalLabel">Edit Meeting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editMeetingForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="editMeetingTitle" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="hidden" id="editMeetingId" name="meeting_id">
                        <input type="text" class="form-control" id="editMeetingTitle" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editMeetingVenue" class="form-label">Meeting Venue</label>
                        <select class="form-select" id="editMeetingVenue" name="venue" required>
                            <option value="In-office">In-office</option>
                            <option value="Client Location">Client Location</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                    <div class="mb-3" id="editLocationField">
                        <label for="editMeetingLocation" class="form-label">Location</label>
                        <input type="text" class="form-control" id="editMeetingLocation" name="location">
                    </div>
                    <div class="mb-3">
                        <label for="editMeetingFrom" class="form-label">From <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="editMeetingFrom" name="start_at" required>
                    </div>
                    <div class="mb-3">
                        <label for="editMeetingTo" class="form-label">To <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="editMeetingTo" name="finish_at" required>
                    </div>
                    <div class="mb-3">
                        <label for="editMeetingHost" class="form-label">Host <span class="text-danger">*</span></label>
                        <select class="form-select" id="editMeetingHost" name="user_owner_id" required>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="editRelatedTo" name="related_type">
                        <input type="hidden" id="editRelatedId" name="related_id">
                    </div>
                    <div class="mb-3">
                        <label for="participants" class="form-label">Participants</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="editParticipantsList" name="participant_id"
                                placeholder="Add participants" readonly>
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                data-bs-target="#editParticipantsModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="hostReminder" class="form-label">Host Reminder</label>
                        <select class="form-select" id="editHostReminder" name="host_reminder">
                            <option value="">None</option>
                            <option value="at_time">At time of meeting</option>
                            <option value="5_minutes">5 minutes before</option>
                            <option value="10_minutes">10 minutes before</option>
                            <option value="15_minutes">15 minutes before</option>
                            <option value="30_minutes">30 minutes before</option>
                            <option value="1_hour">1 hour before</option>
                            <option value="2_hours">2 hours before</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                    <input type="hidden" id="editParticipantIds" name="user_participant_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-custom" id="editMeetingButton">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- Edit Participants Selection Modal (EDIT MEETING)            -->
<!-- ONLY CHANGE: added search input above the participant tables -->
<!-- ========================================================== -->
<div class="modal fade" id="editParticipantsModal" tabindex="-1" aria-labelledby="editParticipantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editParticipantsModalLabel">Add Participants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="editParticipantType" class="form-label">Select Type</label>
                    <select class="form-select" id="editParticipantType">
                        <option value="contacts">Contacts</option>
                        <!-- <option value="leads">Leads</option> -->
                        <option value="users">Users</option>
                    </select>
                </div>

                {{-- ★ ADDED: search input for edit-participants modal --}}
                <div class="mb-3">
                    <input type="text" class="form-control" id="editParticipantSearch"
                           placeholder="Search by name or email...">
                </div>

                <div id="editParticipantsTableContainer">
                    <table class="table table-bordered d-none" id="editContactsTable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Contact Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contacts as $contact)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="editSelectedContacts" value="{{ $contact->id }}"
                                            data-name="{{ $contact->first_name }}">
                                    </td>
                                    <td>{{ $contact->first_name }}</td>
                                    <td>{{ $contact->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <table class="table table-bordered d-none" id="editLeadsTable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Lead Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leads as $lead)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="editSelectedLeads" value="{{ $lead->id }}"
                                            data-name="{{ $lead->person->first_name }}">
                                    </td>
                                    <td>{{ $lead->person->first_name }}</td>
                                    <td>{{ $lead->person->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <table class="table table-bordered d-none" id="editUsersTable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>User Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="editSelectedUsers" value="{{ $user->id }}"
                                            data-name="{{ $user->name }}">
                                    </td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-custom" id="editParticipantsButton">Done</button>
            </div>
        </div>
    </div>
</div>

<style>
    .modal-body {
        max-height: 400px;
        overflow-y: auto;
    }

    /* Scroll when activity list is long; don't force empty bottom space */
    .crm-entity-tabs-scroll {
        flex: 1 1 auto !important;
        min-height: 0;
        max-height: calc(100vh - 230px);
        overflow-y: auto;
        overflow-x: hidden;
        padding-top: 0.25rem;
    }

    #activity .activity-heading {
        margin: 0 0 0.75rem;
        line-height: 1.4;
        overflow: visible;
        position: relative;
        z-index: 1;
    }

    /* Taller activity cards */
    #activity .card.shadow-sm {
        min-height: 150px;
    }

    #activity .card.shadow-sm > .card-body {
        padding: 1.35rem 1.5rem 1.5rem;
    }

    #activity .card.shadow-sm .mb-2 {
        margin-bottom: 0.85rem !important;
    }

    .deal-quotes-section {
        margin-top: 0.25rem;
    }

    .deal-quotes-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .deal-quotes-title {
        font-size: 0.95rem;
        color: #0f172a;
    }

    .deal-quotes-actions {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .deal-quotes-actions .btn {
        min-width: 64px;
        font-size: 0.8rem;
        padding: 0.2rem 0.75rem;
    }

    .deal-quotes-table-wrap {
        border: 1px solid #d7dde5;
        border-radius: 4px;
        overflow: hidden;
        background: #fff;
    }

    .deal-quotes-table {
        font-size: 0.84rem;
        margin-bottom: 0;
    }

    .deal-quotes-table thead th {
        background: #f3f4f6;
        color: #334155;
        font-weight: 600;
        border-color: #d7dde5;
        white-space: nowrap;
        vertical-align: middle;
        padding: 0.55rem 0.75rem;
    }

    .deal-quotes-table tbody td {
        color: #334155;
        border-color: #e5e7eb;
        vertical-align: middle;
        padding: 0.55rem 0.75rem;
        background: #fff;
    }

    .deal-quotes-table tbody tr:hover td {
        background: #fafbfc;
    }

    .deal-quote-link {
        color: #2563eb;
        text-decoration: none;
    }

    .deal-quote-link:hover {
        text-decoration: underline;
    }

    .deal-quotes-table tbody tr.deal-quote-row--selected td {
        background: #f8fbff;
    }

    .deal-quotes-empty {
        padding: 0.5rem 0;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editBtn = document.getElementById('dealQuoteEditBtn');
    const selects = document.querySelectorAll('.deal-quote-select');

    function updateDealQuoteSelection(radio) {
        if (!radio) return;
        document.querySelectorAll('.deal-quote-row').forEach(function (row) {
            row.classList.remove('deal-quote-row--selected');
        });
        const row = radio.closest('.deal-quote-row');
        if (row) row.classList.add('deal-quote-row--selected');
        if (editBtn) {
            editBtn.href = radio.getAttribute('data-edit-url') || '#';
        }
    }

    selects.forEach(function (radio) {
        radio.addEventListener('change', function () {
            updateDealQuoteSelection(radio);
        });
    });

    document.querySelectorAll('.deal-quote-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a') || e.target.closest('input')) return;
            const radio = row.querySelector('.deal-quote-select');
            if (!radio) return;
            radio.checked = true;
            updateDealQuoteSelection(radio);
        });
    });

    const checked = document.querySelector('.deal-quote-select:checked');
    if (checked) updateDealQuoteSelection(checked);
});
</script>

 <script src="{{asset('js/leads/show-leads.js')}}"></script>
<script>
     document.addEventListener('DOMContentLoaded', function () {
        const meetingVenue = document.getElementById('meetingVenue');
        const locationField = document.getElementById('locationField');

        meetingVenue.addEventListener('change', function () {
            if (this.value === 'Online') {
                locationField.classList.add('d-none');
                locationField.querySelector('input').required = false;
            } else {
                locationField.classList.remove('d-none');
                locationField.querySelector('input').required = true;
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const participantType = document.getElementById('participantType');
        const contactsTable = document.getElementById('contactsTable');
        const leadsTable = document.getElementById('leadsTable');
        const usersTable = document.getElementById('usersTable');
        const participantsList = document.getElementById('participantsList');

        participantType.addEventListener('change', function () {
            const selectedType = this.value;
            contactsTable.classList.add('d-none');
            leadsTable.classList.add('d-none');
            usersTable.classList.add('d-none');
            if (selectedType === 'contacts') {
                contactsTable.classList.remove('d-none');
            } else if (selectedType === 'leads') {
                leadsTable.classList.remove('d-none');
            } else if (selectedType === 'users') {
                usersTable.classList.remove('d-none');
            }
            // Clear search when switching type
            document.getElementById('participantSearch').value = '';
            filterParticipantRows('participantSearch', 'participantsTableContainer');
        });

        // Default to showing the contacts table
        contactsTable.classList.remove('d-none');
        leadsTable.classList.add('d-none');
        usersTable.classList.add('d-none');

        // ★ ADDED: live search for create-participants modal
        document.getElementById('participantSearch').addEventListener('input', function () {
            filterParticipantRows('participantSearch', 'participantsTableContainer');
        });

        // Clear search when modal opens
        document.getElementById('participantsModal').addEventListener('show.bs.modal', function () {
            document.getElementById('participantSearch').value = '';
            filterParticipantRows('participantSearch', 'participantsTableContainer');
        });

        const addParticipantsButton = document.getElementById('addParticipantsButton');
        addParticipantsButton.addEventListener('click', function () {
            let selectedParticipants = [];

            const contactCheckboxes = document.querySelectorAll('#contactsTable input[name="selectedContacts"]:checked');
            contactCheckboxes.forEach(checkbox => {
                selectedParticipants.push({ id: checkbox.value, type: 'contact', name: checkbox.getAttribute('data-name') });
            });

            const userCheckboxes = document.querySelectorAll('#usersTable input[name="selectedUsers"]:checked');
            userCheckboxes.forEach(checkbox => {
                selectedParticipants.push({ id: checkbox.value, type: 'user', name: checkbox.getAttribute('data-name') });
            });

            const participantVariable = selectedParticipants;
            const participantIdsField = document.getElementById('participantIds');
            participantIdsField.value = JSON.stringify(participantVariable);

            const participantsList = document.getElementById('participantsList');
            participantsList.value = participantVariable.map(participant => `${participant.name}`).join(', ');

            console.log('Selected participants:', participantVariable);

            const participantsModal = bootstrap.Modal.getInstance(document.getElementById('participantsModal'));
            participantsModal.hide();
        });
    });

    // Edit Meeting Modal - Show/hide location field
    document.addEventListener('DOMContentLoaded', function () {
        const editMeetingVenue = document.getElementById('editMeetingVenue');
        const editLocationField = document.getElementById('editLocationField');

        editMeetingVenue.addEventListener('change', function () {
            if (this.value === 'Online') {
                editLocationField.style.display = 'none';
                editLocationField.querySelector('input').required = false;
            } else {
                editLocationField.style.display = 'block';
                editLocationField.querySelector('input').required = true;
            }
        });
     });

     // Edit Meeting - populate modal on edit click
     document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.dropdown-item.edit-meeting');
        const editMeetingModal = new bootstrap.Modal(document.getElementById('editMeetingModal'));
        const editMeetingForm = document.getElementById('editMeetingForm');

        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                const meeting = JSON.parse(this.getAttribute('data-meeting'));
                console.log('Editing meeting:', meeting);

                document.getElementById('editMeetingId').value = meeting.id;
                document.getElementById('editMeetingTitle').value = meeting.name;
                document.getElementById('editMeetingVenue').value = meeting.venue;
                document.getElementById('editMeetingLocation').value = meeting.location;
                document.getElementById('editMeetingFrom').value = meeting.start_at;
                document.getElementById('editMeetingTo').value = meeting.finish_at;
                document.getElementById('editMeetingHost').value = meeting.user_owner_id;
                document.getElementById('editRelatedTo').value = meeting.related_type;
                document.getElementById('editRelatedId').value = meeting.related_id;
                document.getElementById('editParticipantsList').value = meeting.participants_names;
                document.getElementById('editParticipantIds').value = JSON.stringify(meeting.participants_lists);
                document.getElementById('editDescription').value = meeting.description;

                const checkboxes = document.querySelectorAll('#editParticipantsModal input[type="checkbox"]');
                checkboxes.forEach(checkbox => checkbox.checked = false);

                if (meeting.participants_lists) {
                    meeting.participants_lists.forEach(participant => {
                        let selector = '';
                        if (participant.type === 'contact') {
                            selector = `#editContactsTable input[value="${participant.id}"]`;
                        } else if (participant.type === 'user') {
                            selector = `#editUsersTable input[value="${participant.id}"]`;
                        }
                        if (selector) {
                            const checkbox = document.querySelector(selector);
                            if (checkbox) checkbox.checked = true;
                        }
                    });
                }

                editMeetingModal.show();
            });
        });
    });

     // Edit participants modal - type switcher
     document.addEventListener('DOMContentLoaded', function () {
        const participantType = document.getElementById('editParticipantType');
        const contactsTable = document.getElementById('editContactsTable');
        const leadsTable = document.getElementById('editLeadsTable');
        const usersTable = document.getElementById('editUsersTable');

        participantType.addEventListener('change', function () {
            const selectedType = this.value;
            contactsTable.classList.add('d-none');
            leadsTable.classList.add('d-none');
            usersTable.classList.add('d-none');
            if (selectedType === 'contacts') {
                contactsTable.classList.remove('d-none');
            } else if (selectedType === 'leads') {
                leadsTable.classList.remove('d-none');
            } else if (selectedType === 'users') {
                usersTable.classList.remove('d-none');
            }
            // Clear search when switching type
            document.getElementById('editParticipantSearch').value = '';
            filterParticipantRows('editParticipantSearch', 'editParticipantsTableContainer');
        });

        // Default
        contactsTable.classList.remove('d-none');
        leadsTable.classList.add('d-none');
        usersTable.classList.add('d-none');

        // ★ ADDED: live search for edit-participants modal
        document.getElementById('editParticipantSearch').addEventListener('input', function () {
            filterParticipantRows('editParticipantSearch', 'editParticipantsTableContainer');
        });

        // Clear search when modal opens
        document.getElementById('editParticipantsModal').addEventListener('show.bs.modal', function () {
            document.getElementById('editParticipantSearch').value = '';
            filterParticipantRows('editParticipantSearch', 'editParticipantsTableContainer');
        });

        const addParticipantsButton = document.getElementById('editParticipantsButton');
        addParticipantsButton.addEventListener('click', function () {
            let selectedParticipants = [];

            const contactCheckboxes = document.querySelectorAll('#editContactsTable input[name="editSelectedContacts"]:checked');
            contactCheckboxes.forEach(checkbox => {
                selectedParticipants.push({ id: checkbox.value, type: 'contact', name: checkbox.getAttribute('data-name') });
            });

            const userCheckboxes = document.querySelectorAll('#editUsersTable input[name="editSelectedUsers"]:checked');
            userCheckboxes.forEach(checkbox => {
                selectedParticipants.push({ id: checkbox.value, type: 'user', name: checkbox.getAttribute('data-name') });
            });

            const participantVariable = selectedParticipants;
            const participantIdsField = document.getElementById('editParticipantIds');
            participantIdsField.value = JSON.stringify(participantVariable);
            console.log('Updated hidden participantIds field:', participantVariable.map(participant => `${participant.type}: ${participant.id}`).join(', '));

            const participantsList = document.getElementById('editParticipantsList');
            participantsList.value = participantVariable.map(participant => `${participant.name}`).join(', ');

            console.log('Selected participants:', participantsList);

            const participantsModal = bootstrap.Modal.getInstance(document.getElementById('editParticipantsModal'));
            participantsModal.hide();

            const meetingModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editMeetingModal'));
            meetingModal.show();
        });

        const editParticipantsModal = document.getElementById('editParticipantsModal');
        editParticipantsModal.addEventListener('hidden.bs.modal', function () {
            const meetingModal = new bootstrap.Modal(document.getElementById('editMeetingModal'));
            meetingModal.show();
        });
    });

    // ★ ADDED: shared helper function — filters visible rows in the active table
    // inside the given container based on the search input value.
    function filterParticipantRows(searchInputId, containerId) {
        const query = document.getElementById(searchInputId).value.toLowerCase().trim();
        const container = document.getElementById(containerId);
        // Only filter rows of the currently visible table
        const visibleTable = container.querySelector('table:not(.d-none)');
        if (!visibleTable) return;
        const rows = visibleTable.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
            const cells = row.querySelectorAll('td');
            // Columns 1 (name) and 2 (email) — skip column 0 (checkbox)
            const name  = cells[1] ? cells[1].textContent.toLowerCase() : '';
            const email = cells[2] ? cells[2].textContent.toLowerCase() : '';
            row.style.display = (name.includes(query) || email.includes(query)) ? '' : 'none';
        });
    }

    // Edit meeting form submit
    document.addEventListener('DOMContentLoaded', function () {
        const editMeetingButton = document.getElementById('editMeetingButton');
        const editMeetingForm = document.getElementById('editMeetingForm');

        editMeetingButton.addEventListener('click', function (event) {
            event.preventDefault();

            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(el => el.remove());

            let isValid = true;

            const requiredFields = ['editMeetingTitle', 'editMeetingFrom', 'editMeetingTo', 'editMeetingHost', 'editRelatedTo'];
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value) {
                    isValid = false;
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'error-message text-danger';
                    errorMessage.textContent = 'This field is required';
                    field.parentElement.appendChild(errorMessage);
                }
            });

            const fromField = document.getElementById('editMeetingFrom');
            const toField = document.getElementById('editMeetingTo');
            if (fromField.value && toField.value && new Date(fromField.value) >= new Date(toField.value)) {
                isValid = false;
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message text-danger';
                errorMessage.textContent = 'The "To" date must be after the "From" date';
                toField.parentElement.appendChild(errorMessage);
            }

            if (!isValid) return;

            const editFormData = new FormData(editMeetingForm);
            editFormData.delete('_method');
            console.log('Edit form data:', Array.from(editFormData.entries()));

            fetch('{{ route('meetings.meetingUpdate') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: editFormData
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        console.log(data);
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Meeting saved successfully!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save meeting. Please try again.' });
                    }
                })
                .catch(error => {
                    console.error('There was a problem with the fetch operation:', error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while saving the meeting.' });
                });
        });
    });
</script>

@endsection