@extends('layouts.app')

@section('content')
@php
    $selectedFyId = session('selected_financial_year', null);
    $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
    $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
@endphp
<div class="container-fluid p-4">
<div class="card mt-0">
    <div id="peopleSuccessMsg" class="alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 2000; min-width: 300px; text-align: center; display: none; opacity: 0; transition: opacity 0.5s;"></div>
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h3 class="mb-0">{{ $organization->name }}</h3>
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center ms-auto">
            <a href="{{ route('organizations.index') }}" class="btn btn-light btn-sm">&laquo; Back</a>
            @if(!$isHistorical)
                <a href="{{ route('organizations.edit', $organization->id) }}" class="btn btn-outline-secondary btn-sm @if(!auth()->user()->hasCrmPermission('edit_crm_organization_guard')) disabled @endif"><i class="bi bi-pencil"></i></a>
                <form id="orgDeleteForm" action="{{ route('organizations.destroy', $organization->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="button" id="orgDeleteBtn" class="delete-company-btn btn btn-outline-danger btn-sm @if(!auth()->user()->hasCrmPermission('delete_crm_organization_guard')) disabled @endif" data-company-name="{{ $organization->name }}"><i class="bi bi-trash"></i> </button>
                </form>
            @endif
        </div>
    </div>
    <div class="card-body row">
        <div class="col-lg-6 d-flex flex-column mb-3 mb-lg-0 border-end">
            <h5 class="mb-3">DETAILS</h5>
            <div class="mb-4">
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-sm-4 fw-bold details-label">Name</div>
                    <div class="col-7 col-sm-8 details-value">{{ $organization->name }}</div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-sm-4 fw-bold details-label">Type</div>
                    <div class="col-7 col-sm-8 details-value">{{ $organizationTypes[$organization->organization_type] ?? '-' }}</div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-sm-4 fw-bold details-label">Industry</div>
                    <div class="col-7 col-sm-8 details-value">{{ optional($organization->industry)->name ?? '-' }}</div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-sm-4 fw-bold details-label">Number of employees</div>
                    <div class="col-7 col-sm-8 details-value">{{ $organization->number_of_employees ?? '-' }}</div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-sm-4 fw-bold details-label">Description</div>
                    <div class="col-7 col-sm-8 details-value">{{ $organization->description }}</div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-sm-4 fw-bold details-label">Address</div>
                    <div class="col-7 col-sm-8 details-value">{{ $organization->address }}{{ $organization->address ? ' (Primary)' : '' }}</div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-sm-4 fw-bold details-label">Website</div>
                    <div class="col-7 col-sm-8 details-value">{{ $organization->website }}</div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header py-2 px-3"><i class="bi bi-person-circle me-2"></i>LEAD OWNER</div>
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center mb-2">
                        <div class="col-4 text-end fw-bold">Name</div>
                        <div class="col-8">{{ optional($organization->owner)->name ?? '-' }}</div>
                    </div>
                    {{-- <div class="row">
                        <div class="col-4 text-end fw-bold">Customers</div>
                        <div class="col-8">
                            @php
                                $orgCustomers = $organization->owner && $organization->owner->customers
                                    ? $organization->owner->customers->where('organization_id', $organization->id)
                                    : collect();
                            @endphp
                            @if($orgCustomers->count())
                                <ul class="list-unstyled mb-0">
                                    @foreach($orgCustomers as $customer)
                                        <li>{{ $customer->name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-muted">No customers found for this organization.</span>
                            @endif
                        </div>
                    </div> --}}
                </div>
            </div>
            <div class="col-12">
                    <div class="card mb-2">
                        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-people me-2"></i>Contact Persons <span class="badge bg-secondary" id="peopleCount">{{ $organization->people->count() }}</span></span>
                            @if(!$isHistorical)
                            <button class="btn btn-outline-secondary btn-sm @if(!auth()->user()->hasCrmPermission('create_crm_contact_person_guard')) disabled @endif" data-bs-toggle="modal" data-bs-target="#addPersonModal"><i class="bi bi-plus"></i></button>
                            @endif
                        </div>
                        <div class="card-body py-2 px-3">
                            @if ($organization->people->isEmpty())
                                <div class="text-center text-muted">No contact persons found.</div>
                                @else

                                <ul class="list-group" id="peopleList">
                                @foreach($organization->people as $person)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><a class="text-decoration-none" href="{{ route('people.show', $person->id) }}">{{ $person->first_name }}</a></span>
                                        @if(!$isHistorical)
                                        <button class="btn btn-sm btn-danger delete-person @if(!auth()->user()->hasCrmPermission('create_crm_contact_person_guard')) disabled @endif" data-id="{{ $person->id }}" data-person-name="{{ $person->first_name }}"><i class="bi bi-trash"></i></button>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            @endif

                        </div>
                    </div>
                </div>

        </div>
        <div class="col-lg-6 d-flex flex-column mb-3 mb-lg-0">
            <div class="row g-2">

                    <div class="col-12">
                    <div class="card mb-2">
                        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-people me-2"></i>Leads</span>
                        </div>
                        <div class="card-body py-2 px-3">
                            @if ($organization->leads->isEmpty())
                                <div class="text-center text-muted">No leads found.</div>
                            @else
                                <ul class="list-group">
                                    @foreach($organization->leads as $lead)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <a href="{{ route('leads.show', $lead->id) }}" class="text-decoration-none">
                                                    <strong>{{ $lead->title }}</strong>
                                                </a>
                                                - Contact: {{ optional($lead->person) ? $lead->person->first_name . ' ' . $lead->person->last_name : '-' }}
                                            </span>
                                            <span class="badge bg-{{ $lead->converted_at ? 'success' : 'secondary' }}">
                                                {{ $lead->converted_at ? 'Converted' : 'Not Converted' }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card mb-2">
                        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-briefcase me-2"></i>Deals</span>
                        </div>
                        <div class="card-body py-2 px-3">
                            @if ($organization->deals->isEmpty())
                                <div class="text-center text-muted">No deals found.</div>
                            @else
                                <ul class="list-group">
                                    @foreach($organization->deals as $deal)
                                        <li class="list-group-item">
                                            <a href="{{ route('deals.show', $deal->id) }}" class="text-decoration-none">
                                                <strong>{{ $deal->title }}</strong>
                                            </a>
                                            - Contact: {{ optional($deal->person)->first_name ? optional($deal->person)->first_name . ' ' . optional($deal->person)->last_name : ($deal->person_name ?? '-') }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
            <!-- <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link active" href="#">Activity</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Notes</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Tasks</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Calls</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Meetings</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Lunches</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Files</a></li>
            </ul> -->
            <!-- <div class="tab-content p-3 border bg-white" style="min-height:300px;"> -->
                <!-- Tab content placeholder -->
            <!-- </div> -->
        </div>
    </div>
</div>


<!-- Add Person Modal -->
<div class="modal fade" id="addPersonModal" tabindex="-1" aria-labelledby="addPersonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addPersonForm">
        <div class="modal-header">
          <h5 class="modal-title" id="addPersonModalLabel">Add Contact Person</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="person_name" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="person_first_name" name="first_name" >
                        <div class="text-danger small" id="error_first_name"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="person_last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="person_last_name" name="last_name">
                        <div class="text-danger small" id="error_last_name"></div>
                    </div>
                </div>
            </div>


          <div class="mb-3">
            <label for="person_mobile" class="form-label">Mobile <span class="text-danger">*</span></label>
            <input type="tel" placeholder="+91 9876543210" class="form-control" id="person_mobile" name="mobile">
            <div class="text-danger small" id="error_mobile"></div>
          </div>
          <div class="mb-3">
            <label for="person_email" class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" placeholder="example@example.com" class="form-control" id="person_email" name="email">
            <div class="text-danger small" id="error_email"></div>
          </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-custom">Save</button>
            <button type="button" class="btn btn-secondary btn-padding" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
</div>

<!-- Ensure Bootstrap JS is loaded for modal functionality -->
@push('scripts')

<script>
var isHistorical = {{ $isHistorical ? 'true' : 'false' }};
var canDeletePerson = @json(auth()->user()->hasCrmPermission('create_crm_contact_person_guard'));

function refreshPeopleList() {
    fetch('/organizations/{{ $organization->id }}/people')
        .then(response => response.json())
        .then(data => {
            var peopleList = document.getElementById('peopleList');
            var peopleCount = document.getElementById('peopleCount');
            peopleList.innerHTML = '';
            data.forEach(function(person) {
                var li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                var inner = `<span><a class="text-decoration-none" href="/people/${person.id}">${person.first_name}</a></span>`;
                if (!isHistorical && canDeletePerson) {
                    inner += ` <button class='btn btn-sm btn-danger delete-person' data-id='${person.id}'><i class='bi bi-trash'></i></button>`;
                }
                li.innerHTML = inner;
                peopleList.appendChild(li);
                var modalEl = document.getElementById('addPersonModal');
                var modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
            });
            peopleCount.textContent = data.length;
        });
}
document.addEventListener('DOMContentLoaded', function() {
    var addPersonForm = document.getElementById('addPersonForm');
    addPersonForm.addEventListener('submit', function(e) {
        e.preventDefault();
        // Clear previous errors
        ['first_name','last_name','mobile','email'].forEach(function(f){
            var err = document.getElementById('error_' + f);
            if (err) err.textContent = '';
        });
        var firstName = document.getElementById('person_first_name').value.trim();
        var lastName = document.getElementById('person_last_name').value.trim();
        var mobile = document.getElementById('person_mobile').value.trim();
        var email = document.getElementById('person_email').value.trim();
        var orgId = {{ $organization->id }};
        let valid = true;
        // First name: required, only letters, min 2 chars
        if (!firstName || !/^([A-Za-z .'-]{3,})$/.test(firstName)) {
            document.getElementById('error_first_name').textContent = 'Enter a valid first name.';
            valid = false;
        }
        // Last name: optional, but if present, only letters, min 2 chars
        if (lastName && !/^([A-Za-z .'-]{1,})$/.test(lastName)) {
            document.getElementById('error_last_name').textContent = 'Enter a valid last name.';
            valid = false;
        }
        // Mobile: required, +, digits, spaces, hyphens, 7-20 chars
        if (!mobile || !/^\+?[0-9\-\s]{7,20}$/.test(mobile)) {
            document.getElementById('error_mobile').textContent = 'Enter a valid mobile number.';
            valid = false;
        }
        // Email: required, basic email regex
        if (!email || !/^([a-zA-Z0-9_\.-]+)@([a-zA-Z0-9\.-]+)\.([a-zA-Z]{2,})$/.test(email)) {
            document.getElementById('error_email').textContent = 'Enter a valid email address.';
            valid = false;
        }
        if (!valid) return;
        fetch('/people', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ first_name: firstName, last_name: lastName, mobile: mobile, email: email, organization_id: orgId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPeopleSuccessMsg('Person added successfully!');
                var modalEl = document.getElementById('addPersonModal');
                var modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                setTimeout(function() {
                    // Remove lingering modal-backdrop if present
                    var backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(function(bd) { bd.parentNode.removeChild(bd); });
                    document.body.classList.remove('modal-open');
                    addPersonForm.reset();
                    ['first_name','last_name','mobile','email'].forEach(function(f){
                        var err = document.getElementById('error_' + f);
                        if (err) err.textContent = '';
                    });
                }, 300);
                refreshPeopleList();
            } else if (data.errors) {
                // Show validation errors under each field
                Object.keys(data.errors).forEach(function(field) {
                    var err = document.getElementById('error_' + field);
                    if (err) err.textContent = data.errors[field][0];
                });
            } else {
                alert('Error: ' + (data.message || 'Could not add person.'));
            }
        })
        .catch(() => alert('Error: Could not add person.'));
    });
});

// Show a temporary success message for add/delete actions
function showPeopleSuccessMsg(msg) {
    var el = document.getElementById('peopleSuccessMsg');
    el.textContent = msg;
    el.style.display = 'block';
    setTimeout(function() {
        el.style.opacity = 1;
    }, 10);
    setTimeout(function() {
        el.style.opacity = 0;
        setTimeout(function() { el.style.display = 'none'; }, 500);
    }, 2000);
}

    // Wire organization header delete to SweetAlert2
    document.addEventListener('DOMContentLoaded', function() {

        //Swal Alert for delete confirmation
        var buttons = document.querySelectorAll('.delete-company-btn');
        var name = 'data-company-name';
        attachDeleteHandlers(buttons, name);


        // Delegate delete clicks anywhere on the document so it works after list refreshes
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.delete-person');
            if (!btn) return;
            var id = btn.getAttribute('data-id');
            if (!id) return;
            var name = btn.getAttribute('data-person-name') || '';
            var message = name ? 'Are you sure you want to delete "' + name + '"? This action cannot be undone.' : 'Are you sure you want to delete this record? This action cannot be undone.';
            // Confirm with SweetAlert2
            Swal.fire({
                title: 'Delete confirmation',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                reverseButtons: false
            }).then(function(result) {
                if (!result.isConfirmed) return;
                btn.disabled = true;
                var formBody = '_method=DELETE';
                fetch('/people/' + id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formBody
                })
                .then(response => response.json().catch(() => ({ success: false, message: 'Could not delete person.' })))
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Deleted', text: 'Person deleted successfully.', timer: 1400, showConfirmButton: false });
                        showPeopleSuccessMsg('Person deleted successfully!');
                        refreshPeopleList();
                    } else {
                        Swal.fire('Error', data.message || 'Could not delete person.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Could not delete person.', 'error'))
                .finally(function() { btn.disabled = false; });
            });
        });
    });
</script>
@endpush


@endsection
