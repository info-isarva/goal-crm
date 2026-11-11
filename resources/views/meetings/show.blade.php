@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="mb-0">Meeting Information</h3>
            <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center ms-auto">
                <a href="{{ route('meetings.index') }}" class="btn btn-light btn-sm">&laquo; Back</a>
            </div>
        </div>
        <div class="card-body row">
            <div class="col-lg-6 d-flex flex-column mb-3 mb-lg-0 border-end">

                <div class="mb-4">
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Title</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->name ?? '-' }}</div>
                    </div>
                     <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Meeting Venue</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->venue ?? '-' }}</div>
                    </div>
                     <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Location</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->location ?? '-' }}</div>
                    </div>


                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Start Time</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('d/m/Y h:i A') : '-' }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">End Time</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->finish_at ? \Carbon\Carbon::parse($meeting->finish_at)->format('d/m/Y h:i A') : '-' }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Description</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->description ?? '-' }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Host</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->owner->name ?? '-' }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Reminder</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->reminder ?? '-' }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Participants Reminder</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->participants_reminder ?? '-' }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Related To</div>
                        <div class="col-7 col-sm-8 details-value">
                            @if ($meeting->related_type === 'lead' && $meeting->lead && $meeting->lead->person)
                                <a href="{{ route('leads.show', $meeting->related_id) }}">{{ ucfirst($meeting->lead->person->first_name) ?? 'N/A' }} - {{ '( Leads )' }}</a>
                            @elseif ($meeting->related_type === 'deal' && $meeting->deal && $meeting->deal->person)
                                <a href="{{ route('deals.show', $meeting->related_id) }}">{{ ucfirst($meeting->deal->person->first_name) ?? 'N/A' }} - {{ '( Deals )' }}</a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Created By</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->createdBy->name ?? '-' }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Created At</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->created_at ? $meeting->created_at->format('D, d M Y h:i A') : '-' }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Modified By</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->modifiedBy->name ?? '-' }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label">Modified At</div>
                        <div class="col-7 col-sm-8 details-value">{{ $meeting->updated_at ? $meeting->updated_at->format('D, d M Y h:i A') : '-' }}</div>
                    </div>

                </div>
            </div>
            <div class="col-lg-6 d-flex flex-column mb-3 mb-lg-0">
                <div class="card mb-2">
                    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people me-2"></i>Participants <span class="badge bg-secondary" id="peopleCount">{{ $meeting->participants->count() }}</span></span>
                        <button class="btn btn-outline-secondary btn-sm" id="addParticipantButton" data-bs-toggle="modal" data-bs-target="#participantsModal"><i class="bi bi-plus"></i></button>
                    </div>
                    <div class="card-body py-2 px-3">
                        @if ($meeting->participants->isEmpty())
                            <div class="text-center text-muted">No participants found.</div>
                        @else
                            <ul class="list-group" id="peopleList">
                                @foreach($meeting->participants as $participant)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        @if ($participant->type === 'user')
                                            <span>{{ $participant->user->name ?? '-' }} <small class="text-muted">(User)</small></span>
                                        @elseif ($participant->type === 'contact')
                                            <span>{{ $participant->contact->first_name ?? '-' }} {{ $participant->contact->last_name ?? '' }} <small class="text-muted">(Contact)</small></span>
                                        @endif
                                        <button class="btn btn-sm btn-danger deleteParticipantButton" data-id="{{ $participant->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        
                    </div>
                </div>
                @if ($meeting->meet_link)
                    <div class="card">
                        <!-- meeting links section -->
                        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-link-45deg me-2"></i>Meeting Links</span>
                        </div>
                        <div class="card-body py-2 px-3">
                            <ul class="list-group" id="linksList">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ $meeting->meet_link }}" target="_blank">{{ $meeting->meet_link }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="participantsModal" tabindex="-1" aria-labelledby="participantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="participantsModalLabel">Add Participants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="participantsForm">
            <div class="modal-body">

                    @csrf
                    <input type="hidden" id="meeting_id" name="meeting_id" value="{{ $meeting->id }}">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <label for="participantType" class="form-label mb-0">Select Type</label>
                        <select class="form-select" id="participantType">
                            <option value="contacts">Contacts</option>
                            <!-- <option value="leads">Leads</option> -->
                            <option value="users">Users</option>
                        </select>
                    </div>
                    <div class="flex-grow-1">
                        <label for="searchParticipant" class="form-label mb-0">Search by Name</label>
                        <input type="text" class="form-control" id="searchParticipant" placeholder="Type to search...">
                    </div>
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
                                        <input type="checkbox" name="selectedContacts" value="{{ $contact->id }}" data-name="{{ $contact->first_name }}">
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
                                        <input type="checkbox" name="selectedLeads" value="{{ $lead->id }}" data-name="{{ $lead->person->first_name }}">
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
                                        <input type="checkbox" name="selectedUsers" value="{{ $user->id }}" data-name="{{ $user->name }}">
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
            </form>
        </div>
    </div>
</div>
@endsection
<style>
    .modal-body {
        max-height: 400px; /* Set a fixed height for all modal bodies */
        overflow-y: auto; /* Enable vertical scrolling */
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchParticipant');
        const participantTypeDropdown = document.getElementById('participantType');

        // Function to filter participants based on search input and selected type
        function filterParticipants() {
            console.log('Filtering participants...');
            const filter = searchInput.value.toLowerCase();
            const selectedType = participantTypeDropdown.value;
            console.log('Selected Type:', selectedType);
            console.log('Search Filter:', filter);
            let rows;

            if (selectedType === 'contacts') {
                rows = document.querySelectorAll('#contactsTable tbody tr');
            } else if (selectedType === 'users') {
                rows = document.querySelectorAll('#usersTable tbody tr');
            } else if (selectedType === 'leads') {
                rows = document.querySelectorAll('#leadsTable tbody tr');
            }

            if (rows) {
                rows.forEach(row => {
                    const nameCell = row.querySelector('td:nth-child(2)');
                    const name = nameCell ? nameCell.textContent.toLowerCase() : '';
                    console.log('Checking row:', name);
                    if (name.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            } else {
                console.log('No rows found for the selected type.');
            }
        }

        // Event listener for search input
        searchInput.addEventListener('input', function () {
            console.log('Search input changed:', searchInput.value);
            filterParticipants();
        });

        // Event listener for participant type dropdown
        participantTypeDropdown.addEventListener('change', function () {
            console.log('Participant type changed:', participantTypeDropdown.value);
            searchInput.value = ''; // Clear search input when type changes
            filterParticipants(); // Reapply filter

            // Show the relevant table based on selected type
            document.getElementById('contactsTable').classList.add('d-none');
            document.getElementById('leadsTable').classList.add('d-none');
            document.getElementById('usersTable').classList.add('d-none');

            if (this.value === 'contacts') {
                document.getElementById('contactsTable').classList.remove('d-none');
            } else if (this.value === 'leads') {
                document.getElementById('leadsTable').classList.remove('d-none');
            } else if (this.value === 'users') {
                document.getElementById('usersTable').classList.remove('d-none');
            }
        });

        // Trigger change event on page load to set the initial state
        participantTypeDropdown.dispatchEvent(new Event('change'));

        const addParticipantsButton = document.getElementById('addParticipantsButton');
        const participantsForm = document.getElementById('participantsForm');

        addParticipantsButton.addEventListener('click', async function () {
            const meetingId = document.getElementById('meeting_id').value; // Ensure meetingId is defined inside the event listener
            const selectedParticipants = [];

            document.querySelectorAll('input[name="selectedUsers"]:checked').forEach(input => {
                selectedParticipants.push({ id: input.value, type: 'user' });
            });

            document.querySelectorAll('input[name="selectedContacts"]:checked').forEach(input => {
                selectedParticipants.push({ id: input.value, type: 'contact' });
            });

            if (selectedParticipants.length === 0) {
                Swal.fire('No Participants Selected', 'Please select at least one participant to add.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Add Participants',
                text: 'Are you sure you want to add the selected participants?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, add them',
                cancelButtonText: 'Cancel',
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('/meetings/add-participants', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                meeting_id: meetingId,
                                participants: selectedParticipants
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }

                        const result = await response.json();
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Participants Added',
                                text: 'Participants added successfully!',
                                timer: 1400,
                                showConfirmButton: false
                            });
                            location.reload();
                        } else {
                            Swal.fire('Error', result.message || 'Failed to add participants.', 'error');
                        }
                    } catch (error) {
                        console.error('Error adding participants:', error);
                        Swal.fire('Error', 'An error occurred while adding participants.', 'error');
                    }
                }
            });
        });

        const deleteParticipantButtons = document.querySelectorAll('.deleteParticipantButton');

        deleteParticipantButtons.forEach(button => {
            button.addEventListener('click', function () {
                const participantId = this.getAttribute('data-id');
                const meetingId = document.getElementById('meeting_id').value; // Ensure meetingId is defined inside the event listener
                Swal.fire({
                    title: 'Delete confirmation',
                    text: 'Are you sure you want to delete this participant? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/meetings/${meetingId}/participants/${participantId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted',
                                    text: 'Participant deleted successfully.',
                                    timer: 1400,
                                    showConfirmButton: false
                                });
                                location.reload();
                            } else {
                                Swal.fire('Error', data.message || 'Failed to delete participant.', 'error');
                            }
                        })
                        .catch(() => Swal.fire('Error', 'An error occurred while deleting the participant.', 'error'));
                    }
                });
            });
        });
    });
</script>


