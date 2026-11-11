@extends('layouts.app')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/leads.css') }}">
    <style>
        .isDisabled {
            color: #333;
            cursor: not-allowed;
            opacity: 0.5;
            text-decoration: none;
        }
        .card-body {
            overflow: visible !important;
        }
        .dropdown-menu {
            position: absolute;
            z-index: 1050;
            will-change: transform;
            top: 100%;
            left: 0;
            transform: translate3d(0px, 0px, 0px) !important;
        }
        .table tbody tr td {
            position: relative;
            overflow: visible;
        }
    </style>
    <div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'meetingsPageSkeleton',
        'columns' => 6,
        'rows' => 10,
        'filterFields' => 2,
    ])

        <div class="card mt-0 shadow-sm">
            <div class="card-header">
                <div class="row g-2 align-items-center org-header">
                    <div class="col-12 col-md-4 d-flex align-items-center org-left" style="gap:12px;">
                        <h4 class="mb-2 mb-md-0">Meetings</h4>
                    </div>
                    <div class="col-12 col-md-8">
                        <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                            <a href="" class="btn btn-custom" data-bs-toggle="modal"
                                data-bs-target="#createMeetingModal">Create Meeting</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'all' ? 'active' : '' }}"
                            href="{{ route('meetings.index', ['tab' => 'all', 'filter_type' => $filterType, 'hosted_by' => $hostedBy]) }}">All
                            Meetings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'today' ? 'active' : '' }}"
                            href="{{ route('meetings.index', ['tab' => 'today', 'filter_type' => $filterType, 'hosted_by' => $hostedBy]) }}">Today
                            Meetings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'upcoming' ? 'active' : '' }}"
                            href="{{ route('meetings.index', ['tab' => 'upcoming', 'filter_type' => $filterType, 'hosted_by' => $hostedBy]) }}">Upcoming
                            Meetings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'completed' ? 'active' : '' }}"
                            href="{{ route('meetings.index', ['tab' => 'completed', 'filter_type' => $filterType, 'hosted_by' => $hostedBy]) }}">Completed
                            Meetings</a>
                    </li>
                </ul>

                <!-- Meetings Table -->
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Title</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Related To</th>
                            <th>Host Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($meetings as $meeting)
                            <tr>
                                <td style="overflow:visible; position:relative;" data-label="Actions">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button"
                                            id="meetingActionsDropdown{{ $meeting->id }}" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2 mt-2"
                                            aria-labelledby="meetingActionsDropdown{{ $meeting->id }}">
                                            <li>
                                                <a class="dropdown-item px-4 py-2 fs-6 edit-meeting {{ $meeting->start_at < now()->toDateString() ? 'isDisabled' : '' }}" href="#"
                                                   @if($meeting->start_at >= now()->toDateString()) data-meeting='@json($meeting)' @endif>Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('meetings.destroy', $meeting->id) }}" method="POST"
                                                    onsubmit="return confirm('Are you sure you want to delete this meeting?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="dropdown-item px-4 py-2 fs-6 text-danger">Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td><a href="{{ route('meetings.show', $meeting->id) }}">{{ $meeting->name }}</a></td>
                                <td>{{ \Carbon\Carbon::parse($meeting->start_at)->format('d-m-Y h:i A') }}</td>
                                <td>{{ \Carbon\Carbon::parse($meeting->finish_at)->format('d-m-Y h:i A') }}</td>
                                <td>
                                    @if ($meeting->related_type === 'lead' && $meeting->lead && $meeting->lead->person)
                                        <a href="{{ route('leads.show', $meeting->related_id) }}">{{ ucfirst($meeting->lead->person->first_name) ?? 'N/A' }}
                                            - {{ '( Leads )' }}</a>
                                    @elseif ($meeting->related_type === 'deal' && $meeting->deal && $meeting->deal->person)
                                        <a href="{{ route('deals.show', $meeting->related_id) }}">{{ ucfirst($meeting->deal->person->first_name) ?? 'N/A' }}
                                            - {{ '( Deals )' }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $meeting->owner ? $meeting->owner->name : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No meetings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Meeting Modal -->
    <div class="modal fade" id="createMeetingModal" tabindex="-1" aria-labelledby="createMeetingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createMeetingModalLabel">Meeting Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createMeetingForm">
                        <div class="mb-3">
                            <label for="meetingTitle" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="meetingTitle" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="meetingVenue" class="form-label">Meeting Venue</label>
                            <select class="form-select" id="meetingVenue" name="venue" required>
                                <option value="In-office">In-office</option>
                                <option value="Client Location">Client Location</option>
                                <option value="Online">Online</option>
                            </select>
                        </div>
                        <div class="mb-3" id="locationField">
                            <label for="meetingLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="meetingLocation" name="location">
                        </div>
                        <div class="mb-3 d-none" id="googleMeetingOption">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="createGoogleMeeting" name="create_google_meeting" value="1">
                                <label class="form-check-label" for="createGoogleMeeting">
                                    Create Google Meeting Link
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="meetingFrom" class="form-label">From <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="meetingFrom" name="start_at"
                                value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="meetingTo" class="form-label">To <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="meetingTo" name="finish_at"
                                value="{{ now()->addHours(1)->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="meetingHost" class="form-label">Host <span class="text-danger">*</span></label>
                            <select class="form-select" id="meetingHost" name="user_owner_id" required>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ $user->id == auth()->id() ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="relatedTo" class="form-label">Related To <span class="text-danger">*</span></label>
                            <select class="form-select" id="relatedTo" name="related_type" required>
                                <option value="">None</option>
                                <option value="lead">Leads</option>
                                <option value="deal">Deals</option>
                                <option value="other">Other</option>
                            </select>
                            <input type="hidden" id="relatedId" name="related_id">
                        </div>
                        <div class="mb-3 d-none" id="leadsField">
                            <label for="selectLead" class="form-label">Lead</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="selectLead" name="lead_name"
                                    placeholder="Select a lead" readonly>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#leadsSearchModal">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3 d-none" id="dealsField">
                            <label for="selectDeal" class="form-label">Deal</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="selectDeal" name="deal_name"
                                    placeholder="Select a deal" readonly>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#dealsSearchModal">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="participants" class="form-label">Participants</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="participantsList" name="participant_id"
                                    placeholder="Add participants" readonly>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#participantsModal">
                                    <i class="fas fa-plus"></i>
                                </button>
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
                        <div class="mb-3">
                            <label for="meetingNote" class="form-label">Description</label>
                            <textarea class="form-control" id="meetingNote" name="note" rows="3"></textarea>
                        </div>
                        <input type="hidden" id="participantIds" name="user_participant_id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom" id="saveMeetingButton">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leads Search Modal -->
    <div class="modal fade" id="leadsSearchModal" tabindex="-1" aria-labelledby="leadsSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="max-width: 1500px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leadsSearchModalLabel">Choose Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="searchLeadInput" placeholder="Search by Lead Name">
                    </div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Lead Name</th>
                                <th>Company</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Lead Source</th>
                                <th>Lead Owner</th>
                                <th>Title</th>
                            </tr>
                        </thead>
                        <tbody id="leadsTableBody">
                            @foreach ($leads as $lead)
                                <tr>
                                    <td>
                                        <input type="radio" name="selectedLead" value="{{ $lead->id }}"
                                            data-name="{{ $lead->name }}">
                                    </td>
                                    <td>{{ $lead->person->first_name }}</td>
                                    <td>{{ optional($lead->organization)->name }}</td>
                                    <td>{{ $lead->person->email }}</td>
                                    <td>{{ $lead->person->mobile }}</td>
                                    <td>{{ $lead->source }}</td>
                                    <td>{{ optional($lead->owner)->name }}</td>
                                    <td>{{ $lead->title }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom" id="selectLeadButton">Select Lead</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Deals Search Modal -->
    <div class="modal fade" id="dealsSearchModal" tabindex="-1" aria-labelledby="dealsSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="max-width: 1500px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dealsSearchModalLabel">Choose Deal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="searchDealInput" placeholder="Search by Deal Name">
                    </div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Deal Name</th>
                                <th>Company</th>
                                <th>Amount</th>
                                <th>Stage</th>
                                <th>Owner</th>
                            </tr>
                        </thead>
                        <tbody id="dealsTableBody">
                            @foreach ($deals as $deal)
                                <tr>
                                    <td>
                                        <input type="radio" name="selectedDeal" value="{{ $deal->id }}"
                                            data-name="{{ $deal->title }}">
                                    </td>
                                    <td>{{ $deal->title }}</td>
                                    <td>{{ optional($deal->organization)->name }}</td>
                                    <td>{{ $deal->amount }}</td>
                                    <td>{{ $deal->stage_name }}</td>
                                    <td>{{ optional($deal->owner)->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom" id="selectDealButton">Select Deal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Participants Selection Modal (Create) -->
    <div class="modal fade" id="participantsModal" tabindex="-1" aria-labelledby="participantsModalLabel"
        aria-hidden="true">
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
                            <option value="users">Users</option>
                        </select>
                    </div>
                    <div id="participantsTableContainer">
                        <div class="mb-3">
                            <input type="text" id="participantSearchInput" class="form-control" placeholder="Search participants...">
                        </div>
                        <table class="table table-bordered" id="contactsTable">
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
                        <!-- Location field - hidden when venue is Online -->
                        <div class="mb-3" id="editLocationField">
                            <label for="editMeetingLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="editMeetingLocation" name="location">
                        </div>
                        <!-- Google Meeting checkbox - visible & checked when venue is Online -->
                        <div class="mb-3 d-none" id="editGoogleMeetingOption">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editCreateGoogleMeeting" name="create_google_meeting" value="1">
                                <label class="form-check-label" for="editCreateGoogleMeeting">
                                    Create Google Meeting Link
                                </label>
                            </div>
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
                        </div>
                        <div class="mb-3">
                            <label for="editRelatedTo" class="form-label">Related To</label>
                            <select class="form-select" id="editRelatedTo" name="related_type">
                                <option value="">None</option>
                                <option value="lead">Leads</option>
                                <option value="deal">Deals</option>
                                <option value="other">Other</option>
                            </select>
                            <input type="hidden" id="editRelatedId" name="related_id">
                        </div>
                        <div class="mb-3 d-none" id="editLeadsField">
                            <label for="editSelectLead" class="form-label">Lead</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="editSelectLead" name="lead_name"
                                    placeholder="Select a lead" readonly>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#editLeadsSearchModal">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3 d-none" id="editDealsField">
                            <label for="editSelectDeal" class="form-label">Deal</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="editSelectDeal" name="deal_name"
                                    placeholder="Select a deal" readonly>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#editDealsSearchModal">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editParticipants" class="form-label">Participants</label>
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
                            <label for="editHostReminder" class="form-label">Host Reminder</label>
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
                    <button type="button" class="btn btn-custom" id="editMeetingButton">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Leads Search Modal -->
    <div class="modal fade" id="editLeadsSearchModal" tabindex="-1" aria-labelledby="editLeadsSearchModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" style="max-width: 1500px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLeadsSearchModalLabel">Choose Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="editSearchLeadInput" placeholder="Search by Lead Name">
                    </div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Lead Name</th>
                                <th>Company</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Lead Source</th>
                                <th>Lead Owner</th>
                                <th>Title</th>
                            </tr>
                        </thead>
                        <tbody id="editLeadsTableBody">
                            @foreach ($leads as $lead)
                                <tr>
                                    <td>
                                        <input type="radio" name="editSelectedLead" value="{{ $lead->id }}"
                                            data-name="{{ $lead->name }}">
                                    </td>
                                    <td>{{ $lead->person->first_name }}</td>
                                    <td>{{ optional($lead->organization)->name }}</td>
                                    <td>{{ $lead->person->email }}</td>
                                    <td>{{ $lead->person->mobile }}</td>
                                    <td>{{ $lead->source }}</td>
                                    <td>{{ optional($lead->owner)->name }}</td>
                                    <td>{{ $lead->title }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom" id="editSelectLeadButton">Select Lead</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Deals Search Modal -->
    <div class="modal fade" id="editDealsSearchModal" tabindex="-1" aria-labelledby="editDealsSearchModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" style="max-width: 1500px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDealsSearchModalLabel">Choose Deal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="editSearchDealInput" placeholder="Search by Deal Name">
                    </div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Deal Name</th>
                                <th>Company</th>
                                <th>Amount</th>
                                <th>Stage</th>
                                <th>Owner</th>
                            </tr>
                        </thead>
                        <tbody id="editDealsTableBody">
                            @foreach ($deals as $deal)
                                <tr>
                                    <td>
                                        <input type="radio" name="editSelectedDeal" value="{{ $deal->id }}"
                                            data-name="{{ $deal->title }}">
                                    </td>
                                    <td>{{ $deal->title }}</td>
                                    <td>{{ optional($deal->organization)->name }}</td>
                                    <td>{{ $deal->amount }}</td>
                                    <td>{{ $deal->stage_name }}</td>
                                    <td>{{ optional($deal->owner)->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom" id="editSelectDealButton">Select Deal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Participants Selection Modal -->
    <div class="modal fade" id="editParticipantsModal" tabindex="-1" aria-labelledby="editParticipantsModalLabel"
        aria-hidden="true">
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
                            <option value="users">Users</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input type="text" id="editParticipantSearchInput" class="form-control" placeholder="Search participants...">
                    </div>
                    <div id="editParticipantsTableContainer">
                        <table class="table table-bordered" id="editContactsTable">
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
    </style>

    <script>
        /* ─────────────────────────────────────────────
           UTILITY
        ───────────────────────────────────────────── */
        function toDatetimeLocal(dt) {
            const pad = (n) => n < 10 ? '0' + n : n;
            return dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate()) +
                'T' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
        }

        /* ─────────────────────────────────────────────
           CREATE MODAL – venue toggle
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const meetingVenue      = document.getElementById('meetingVenue');
            const locationField     = document.getElementById('locationField');
            const googleMeetingOption = document.getElementById('googleMeetingOption');

            meetingVenue.addEventListener('change', function () {
                if (this.value === 'Online') {
                    locationField.classList.add('d-none');
                    googleMeetingOption.classList.remove('d-none');
                } else {
                    locationField.classList.remove('d-none');
                    googleMeetingOption.classList.add('d-none');
                    document.getElementById('createGoogleMeeting').checked = false;
                }
            });

            /* Related-to toggle (create) */
            const relatedTo = document.getElementById('relatedTo');
            const leadsField = document.getElementById('leadsField');
            const dealsField = document.getElementById('dealsField');

            relatedTo.addEventListener('change', function () {
                if (this.value === 'lead') {
                    leadsField.classList.remove('d-none');
                    dealsField.classList.add('d-none');
                    document.getElementById('relatedId').value = '';
                } else if (this.value === 'deal') {
                    dealsField.classList.remove('d-none');
                    leadsField.classList.add('d-none');
                    document.getElementById('relatedId').value = '';
                } else {
                    leadsField.classList.add('d-none');
                    dealsField.classList.add('d-none');
                    document.getElementById('relatedId').value = '0';
                }
            });
        });

        /* ─────────────────────────────────────────────
           EDIT MODAL – venue toggle (shared helper)
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const editMeetingVenue      = document.getElementById('editMeetingVenue');
            const editLocationField     = document.getElementById('editLocationField');
            const editGoogleMeetingOption = document.getElementById('editGoogleMeetingOption');
            const editCreateGoogleMeeting = document.getElementById('editCreateGoogleMeeting');

            /**
             * Apply venue-dependent visibility in the Edit modal.
             * Exposed on window so the edit-button handler can call it after
             * populating the form fields.
             */
            window.applyEditVenueToggle = function (venueValue, meetLink = '') {
                console.log('Applying venue toggle for value:', venueValue);
                console.log('Meeting link:', meetLink);
                if (venueValue === 'Online') {
                    editLocationField.classList.add('d-none');
                    editGoogleMeetingOption.classList.remove('d-none');
                    if(meetLink) {
                        editCreateGoogleMeeting.checked = true;
                    } else {
                        editCreateGoogleMeeting.checked = false;
                    }
                    //editCreateGoogleMeeting.checked = true;
                } else {
                    editLocationField.classList.remove('d-none');
                    editGoogleMeetingOption.classList.add('d-none');
                    editCreateGoogleMeeting.checked = false;
                }
            };

            editMeetingVenue.addEventListener('change', function () {
                window.applyEditVenueToggle(this.value);
            });
        });

        /* ─────────────────────────────────────────────
           EDIT MODAL – related-to toggle
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const editRelatedTo  = document.getElementById('editRelatedTo');
            const editLeadsField = document.getElementById('editLeadsField');
            const editDealsField = document.getElementById('editDealsField');

            editRelatedTo.addEventListener('change', function () {
                if (this.value === 'lead') {
                    editLeadsField.classList.remove('d-none');
                    editDealsField.classList.add('d-none');
                    document.getElementById('editRelatedId').value = '';
                } else if (this.value === 'deal') {
                    editDealsField.classList.remove('d-none');
                    editLeadsField.classList.add('d-none');
                    document.getElementById('editRelatedId').value = '';
                } else {
                    editLeadsField.classList.add('d-none');
                    editDealsField.classList.add('d-none');
                    document.getElementById('editRelatedId').value = '0';
                }
            });
        });

        /* ─────────────────────────────────────────────
           EDIT MODAL – open & populate
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const editButtons      = document.querySelectorAll('.dropdown-item.edit-meeting');
            const editMeetingModal = new bootstrap.Modal(document.getElementById('editMeetingModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const meeting = JSON.parse(this.getAttribute('data-meeting'));
                    console.log('Editing meeting:', meeting);
                    /* Basic fields */
                    document.getElementById('editMeetingId').value       = meeting.id;
                    document.getElementById('editMeetingTitle').value    = meeting.name;
                    document.getElementById('editMeetingVenue').value    = meeting.venue;
                    document.getElementById('editMeetingLocation').value = meeting.location || '';
                    document.getElementById('editMeetingFrom').value     = meeting.start_at;
                    document.getElementById('editMeetingTo').value       = meeting.finish_at;
                    document.getElementById('editMeetingHost').value     = meeting.user_owner_id;
                    document.getElementById('editRelatedTo').value       = meeting.related_type || '';
                    document.getElementById('editRelatedId').value       = meeting.related_id   || '';
                    document.getElementById('editDescription').value     = meeting.description  || '';

                    /* Apply venue toggle immediately after setting venue value */
                    if (window.applyEditVenueToggle) {
                        window.applyEditVenueToggle(meeting.venue, meeting.meet_link);
                    }

                    /* Related-to field visibility */
                    const editLeadsField = document.getElementById('editLeadsField');
                    const editDealsField = document.getElementById('editDealsField');

                    if (meeting.related_type === 'lead') {
                        editLeadsField.classList.remove('d-none');
                        editDealsField.classList.add('d-none');
                        document.getElementById('editSelectLead').value = meeting.related_name || '';
                        document.getElementById('editSelectDeal').value = '';
                    } else if (meeting.related_type === 'deal') {
                        editDealsField.classList.remove('d-none');
                        editLeadsField.classList.add('d-none');
                        document.getElementById('editSelectDeal').value = meeting.related_name || '';
                        document.getElementById('editSelectLead').value = '';
                    } else {
                        editLeadsField.classList.add('d-none');
                        editDealsField.classList.add('d-none');
                        document.getElementById('editSelectLead').value = '';
                        document.getElementById('editSelectDeal').value = '';
                        document.getElementById('editRelatedId').value = '0';
                    }

                    /* Participants */
                    document.getElementById('editParticipantsList').value = meeting.participants_names || '';
                    document.getElementById('editParticipantIds').value   = JSON.stringify(meeting.participants_lists || []);

                    /* Reset all checkboxes in edit participants modal */
                    document.querySelectorAll('#editParticipantsModal input[type="checkbox"]')
                        .forEach(cb => cb.checked = false);

                    /* Pre-select existing participants */
                    if (meeting.participants_lists && Array.isArray(meeting.participants_lists)) {
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

        /* ─────────────────────────────────────────────
           EDIT PARTICIPANTS MODAL – table switching & search & done
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const editParticipantType        = document.getElementById('editParticipantType');
            const editContactsTable          = document.getElementById('editContactsTable');
            const editUsersTable             = document.getElementById('editUsersTable');
            const editParticipantSearchInput = document.getElementById('editParticipantSearchInput');

            /* Show the correct table */
            function showEditParticipantTable(type) {
                editContactsTable.classList.add('d-none');
                editUsersTable.classList.add('d-none');

                if (type === 'contacts') {
                    editContactsTable.classList.remove('d-none');
                } else if (type === 'users') {
                    editUsersTable.classList.remove('d-none');
                }

                /* Reset search */
                editParticipantSearchInput.value = '';
                filterEditParticipants('');
            }

            /* Filter visible table rows */
            function filterEditParticipants(term) {
                const activeTable = [editContactsTable, editUsersTable]
                    .find(t => !t.classList.contains('d-none'));
                if (!activeTable) return;

                activeTable.querySelectorAll('tbody tr').forEach(row => {
                    const name  = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    row.style.display = (name.includes(term) || email.includes(term)) ? '' : 'none';
                });
            }

            /* Default: show contacts */
            showEditParticipantTable('contacts');

            editParticipantType.addEventListener('change', function () {
                showEditParticipantTable(this.value);
            });

            editParticipantSearchInput.addEventListener('input', function () {
                filterEditParticipants(this.value.toLowerCase());
            });

            /* Done button – collect checked items and update edit meeting modal */
            document.getElementById('editParticipantsButton').addEventListener('click', function () {
                const selected = [];

                document.querySelectorAll('#editContactsTable input[name="editSelectedContacts"]:checked')
                    .forEach(cb => selected.push({ id: cb.value, type: 'contact', name: cb.getAttribute('data-name') }));

                document.querySelectorAll('#editUsersTable input[name="editSelectedUsers"]:checked')
                    .forEach(cb => selected.push({ id: cb.value, type: 'user', name: cb.getAttribute('data-name') }));

                document.getElementById('editParticipantIds').value   = JSON.stringify(selected);
                document.getElementById('editParticipantsList').value = selected.map(p => p.name).join(', ');

                bootstrap.Modal.getInstance(document.getElementById('editParticipantsModal')).hide();
            });

            /* Re-open edit meeting modal after participants modal closes */
            document.getElementById('editParticipantsModal').addEventListener('hidden.bs.modal', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editMeetingModal')).show();
            });
        });

        /* ─────────────────────────────────────────────
           CREATE PARTICIPANTS MODAL – table switching & search & done
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const participantType        = document.getElementById('participantType');
            const contactsTable          = document.getElementById('contactsTable');
            const leadsTable             = document.getElementById('leadsTable');
            const usersTable             = document.getElementById('usersTable');
            const participantSearchInput = document.getElementById('participantSearchInput');

            function showCreateParticipantTable(type) {
                contactsTable.classList.add('d-none');
                leadsTable.classList.add('d-none');
                usersTable.classList.add('d-none');

                if (type === 'contacts')  contactsTable.classList.remove('d-none');
                else if (type === 'leads') leadsTable.classList.remove('d-none');
                else if (type === 'users') usersTable.classList.remove('d-none');

                participantSearchInput.value = '';
                filterCreateParticipants('');
            }

            function filterCreateParticipants(term) {
                const activeTable = [contactsTable, leadsTable, usersTable]
                    .find(t => !t.classList.contains('d-none'));
                if (!activeTable) return;

                activeTable.querySelectorAll('tbody tr').forEach(row => {
                    const name  = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    row.style.display = (name.includes(term) || email.includes(term)) ? '' : 'none';
                });
            }

            /* Default: contacts */
            showCreateParticipantTable('contacts');

            participantType.addEventListener('change', function () {
                showCreateParticipantTable(this.value);
            });

            participantSearchInput.addEventListener('input', function () {
                filterCreateParticipants(this.value.toLowerCase());
            });

            document.getElementById('addParticipantsButton').addEventListener('click', function () {
                const selected = [];

                document.querySelectorAll('#contactsTable input[name="selectedContacts"]:checked')
                    .forEach(cb => selected.push({ id: cb.value, type: 'contact', name: cb.getAttribute('data-name') }));

                document.querySelectorAll('#usersTable input[name="selectedUsers"]:checked')
                    .forEach(cb => selected.push({ id: cb.value, type: 'user', name: cb.getAttribute('data-name') }));

                document.getElementById('participantIds').value   = JSON.stringify(selected);
                document.getElementById('participantsList').value = selected.map(p => p.name).join(', ');

                bootstrap.Modal.getInstance(document.getElementById('participantsModal')).hide();
            });

            document.getElementById('participantsModal').addEventListener('hidden.bs.modal', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createMeetingModal')).show();
            });
        });

        /* ─────────────────────────────────────────────
           CREATE MODAL – leads search
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const searchLeadInput  = document.getElementById('searchLeadInput');
            const leadsTableBody   = document.getElementById('leadsTableBody');
            const selectLeadField  = document.getElementById('selectLead');

            searchLeadInput.addEventListener('input', function () {
                const term = this.value.toLowerCase();
                leadsTableBody.querySelectorAll('tr').forEach(row => {
                    row.style.display = row.querySelector('td:nth-child(2)').textContent.toLowerCase().includes(term) ? '' : 'none';
                });
            });

            leadsTableBody.addEventListener('change', function () {
                const selected = document.querySelector('input[name="selectedLead"]:checked');
                if (!selected) return;
                const leadTitle = selected.closest('tr').querySelector('td:nth-child(8)').textContent;
                selectLeadField.value = `${selected.getAttribute('data-name')} (${leadTitle})`;
                document.getElementById('relatedId').value = selected.value;
                bootstrap.Modal.getInstance(document.getElementById('leadsSearchModal')).hide();
            });

            document.getElementById('leadsSearchModal').addEventListener('hidden.bs.modal', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createMeetingModal')).show();
            });
        });

        /* ─────────────────────────────────────────────
           CREATE MODAL – deals search
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const searchDealInput = document.getElementById('searchDealInput');
            const dealsTableBody  = document.getElementById('dealsTableBody');
            const selectDealField = document.getElementById('selectDeal');

            searchDealInput.addEventListener('input', function () {
                const term = this.value.toLowerCase();
                dealsTableBody.querySelectorAll('tr').forEach(row => {
                    row.style.display = row.querySelector('td:nth-child(2)').textContent.toLowerCase().includes(term) ? '' : 'none';
                });
            });

            dealsTableBody.addEventListener('change', function () {
                const selected = document.querySelector('input[name="selectedDeal"]:checked');
                if (!selected) return;
                selectDealField.value = selected.getAttribute('data-name');
                document.getElementById('relatedId').value = selected.value;
                bootstrap.Modal.getInstance(document.getElementById('dealsSearchModal')).hide();
            });

            document.getElementById('dealsSearchModal').addEventListener('hidden.bs.modal', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createMeetingModal')).show();
            });
        });

        /* ─────────────────────────────────────────────
           EDIT MODAL – leads search
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const editSearchLeadInput = document.getElementById('editSearchLeadInput');
            const editLeadsTableBody  = document.getElementById('editLeadsTableBody');
            const editSelectLeadField = document.getElementById('editSelectLead');

            editSearchLeadInput.addEventListener('input', function () {
                const term = this.value.toLowerCase();
                editLeadsTableBody.querySelectorAll('tr').forEach(row => {
                    row.style.display = row.querySelector('td:nth-child(2)').textContent.toLowerCase().includes(term) ? '' : 'none';
                });
            });

            editLeadsTableBody.addEventListener('change', function () {
                const selected = document.querySelector('input[name="editSelectedLead"]:checked');
                if (!selected) return;
                const leadTitle = selected.closest('tr').querySelector('td:nth-child(8)').textContent;
                editSelectLeadField.value = `${selected.getAttribute('data-name')} (${leadTitle})`;
                document.getElementById('editRelatedId').value = selected.value;
                bootstrap.Modal.getInstance(document.getElementById('editLeadsSearchModal')).hide();
            });

            document.getElementById('editLeadsSearchModal').addEventListener('hidden.bs.modal', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editMeetingModal')).show();
            });
        });

        /* ─────────────────────────────────────────────
           EDIT MODAL – deals search
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const editSearchDealInput = document.getElementById('editSearchDealInput');
            const editDealsTableBody  = document.getElementById('editDealsTableBody');
            const editSelectDealField = document.getElementById('editSelectDeal');

            editSearchDealInput.addEventListener('input', function () {
                const term = this.value.toLowerCase();
                editDealsTableBody.querySelectorAll('tr').forEach(row => {
                    row.style.display = row.querySelector('td:nth-child(2)').textContent.toLowerCase().includes(term) ? '' : 'none';
                });
            });

            editDealsTableBody.addEventListener('change', function () {
                const selected = document.querySelector('input[name="editSelectedDeal"]:checked');
                if (!selected) return;
                editSelectDealField.value = selected.getAttribute('data-name');
                document.getElementById('editRelatedId').value = selected.value;
                bootstrap.Modal.getInstance(document.getElementById('editDealsSearchModal')).hide();
            });

            document.getElementById('editDealsSearchModal').addEventListener('hidden.bs.modal', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editMeetingModal')).show();
            });
        });

        /* ─────────────────────────────────────────────
           CREATE MODAL – datetime auto-fill
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            const meetingFromInput = document.getElementById('meetingFrom');
            const meetingToInput   = document.getElementById('meetingTo');

            if (meetingFromInput && meetingToInput) {
                if (!meetingFromInput.value) meetingFromInput.value = toDatetimeLocal(new Date());
                if (!meetingToInput.value) {
                    const to = new Date(meetingFromInput.value);
                    to.setHours(to.getHours() + 1);
                    meetingToInput.value = toDatetimeLocal(to);
                }

                meetingFromInput.addEventListener('change', function () {
                    if (this.value) {
                        meetingToInput.min = this.value;
                        const to = new Date(this.value);
                        to.setHours(to.getHours() + 1);
                        meetingToInput.value = toDatetimeLocal(to);
                    } else {
                        meetingToInput.min = '';
                    }
                });

                meetingToInput.addEventListener('change', function () {
                    if (this.value) {
                        const from = new Date(this.value);
                        from.setHours(from.getHours() - 1);
                        meetingFromInput.value = toDatetimeLocal(from);
                    }
                });

                if (meetingFromInput.value) meetingToInput.min = meetingFromInput.value;
            }
        });

        /* ─────────────────────────────────────────────
           CREATE MEETING – save
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('saveMeetingButton').addEventListener('click', function (event) {
                event.preventDefault();

                document.querySelectorAll('.error-message').forEach(el => el.remove());
                let isValid = true;

                ['meetingTitle', 'meetingFrom', 'meetingTo', 'meetingHost', 'relatedTo'].forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value) {
                        isValid = false;
                        const err = document.createElement('div');
                        err.className = 'error-message text-danger';
                        err.textContent = 'This field is required';
                        field.parentElement.appendChild(err);
                    }
                });

                const fromField = document.getElementById('meetingFrom');
                const toField   = document.getElementById('meetingTo');
                if (fromField.value && toField.value && new Date(fromField.value) >= new Date(toField.value)) {
                    isValid = false;
                    const err = document.createElement('div');
                    err.className = 'error-message text-danger';
                    err.textContent = 'The "To" date must be after the "From" date';
                    toField.parentElement.appendChild(err);
                }

                if (!isValid) return;

                fetch('{{ route('meetings.meetingStore') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: new FormData(document.getElementById('createMeetingForm'))
                })
                .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Success', text: 'Meeting saved successfully!', timer: 1500, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to save meeting.' });
                    }
                });
            });
        });

        /* ─────────────────────────────────────────────
           DELETE – SweetAlert confirmation
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.dropdown-item.text-danger').forEach(button => {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    const form = this.closest('form');
                    Swal.fire({
                        title: 'Delete confirmation',
                        text: 'Are you sure you want to delete this meeting? This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel',
                    }).then(result => { if (result.isConfirmed) form.submit(); });
                });
            });
        });

        /* ─────────────────────────────────────────────
           EDIT MEETING – save
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('editMeetingButton').addEventListener('click', function (event) {
                event.preventDefault();

                document.querySelectorAll('.error-message').forEach(el => el.remove());
                let isValid = true;

                ['editMeetingTitle', 'editMeetingFrom', 'editMeetingTo', 'editMeetingHost', 'editRelatedTo'].forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value) {
                        isValid = false;
                        const err = document.createElement('div');
                        err.className = 'error-message text-danger';
                        err.textContent = 'This field is required';
                        field.parentElement.appendChild(err);
                    }
                });

                const fromField = document.getElementById('editMeetingFrom');
                const toField   = document.getElementById('editMeetingTo');
                if (fromField.value && toField.value && new Date(fromField.value) >= new Date(toField.value)) {
                    isValid = false;
                    const err = document.createElement('div');
                    err.className = 'error-message text-danger';
                    err.textContent = 'The "To" date must be after the "From" date';
                    toField.parentElement.appendChild(err);
                }

                if (!isValid) return;

                const editFormData = new FormData(document.getElementById('editMeetingForm'));
                editFormData.delete('_method');

                fetch('{{ route('meetings.meetingUpdate') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: editFormData
                })
                .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Success', text: 'Meeting saved successfully!', timer: 1500, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save meeting. Please try again.' });
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while saving the meeting.' });
                });
            });
        });

        /* ─────────────────────────────────────────────
           GOOGLE MEETING – validate token on checkbox change (create modal)
        ───────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('createGoogleMeeting').addEventListener('change', async function () {
                if (!this.checked) return;
                try {
                    const response = await fetch('/validate-google-token', {
                        method: 'GET',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    const data = await response.json();
                    if (!data.valid) {
                        alert('Your Google account is not connected or the token is invalid. Please reconnect.');
                        window.open(data.redirect, '_blank', 'width=500,height=600');
                    }
                } catch (error) {
                    console.error('Error validating Google token:', error);
                }
            });
        });
    </script>
@endsection
