@extends('layouts.app')

@section('content')
@php
$selectedFyId = session('selected_financial_year', null);
$activeFy = \App\Models\FinancialYear::where('active', 1)->first();
$isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
@endphp
<div class="container-fluid p-4">
    <div class="card mt-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">{{ $customer->name }}</h3>
            <div class="d-flex gap-1">
                <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back to Company Owners</a>
                @if(!$isHistorical)
                <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-outline-secondary btn-sm @if(!auth()->user()->hasCrmPermission('edit_crm_customer_guard')) disabled @endif"><i class="bi bi-pencil"></i></a>
                <form id="custDeleteForm" method="POST" action="{{ route('customers.destroy', $customer->id) }}" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="button" id="custDeleteBtn" class="delete-person-btn btn btn-outline-danger btn-sm @if(!auth()->user()->hasCrmPermission('delete_crm_customer_guard')) disabled @endif" data-person-name="{{ $customer->name }}"><i class="bi bi-trash"></i></button>
                </form>
                @endif
            </div>
        </div>
        <div class="card-body row">
            <div class="col-md-5 border-end">
                <h5 class="mb-3">DETAILS</h5>
                <div class="mb-4">
                    <div class="row mb-2">
                        <div class="col-sm-4 text-end fw-bold">Name</div>
                        <div class="col-sm-8">{{ $customer->name }}</div>
                    </div>
                    {{-- <div class="row mb-2">
                        <div class="col-sm-4 text-end fw-bold">Organization</div>
                        <div class="col-sm-8">{{ optional($customer->organization)->name ?? '-' }}
                </div>
            </div> --}}

            <div class="row mb-2">
                <div class="col-sm-4 text-end fw-bold">Phone</div>
                <div class="col-sm-8">{{ $customer->phone ?? '-' }}</div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-4 text-end fw-bold">Email</div>
                <div class="col-sm-8">{{ $customer->email ?? '-' }}</div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-4 text-end fw-bold">Owner</div>
                <div class="col-sm-8">{{ optional($customer->owner)->name ?? '-' }}</div>
            </div>
        </div>
        {{-- <div class="card mb-3">
                    <div class="card-header py-2 px-3"><i class="bi bi-person-circle me-2"></i>OWNER</div>
                    <div class="card-body py-2 px-3">
                        <div class="row align-items-center">
                            <div class="col-4 text-end fw-bold">Name</div>
                            <div class="col-8"><a href="#">{{ optional($customer->owner)->name ?? '-' }}</a>
    </div>
</div>
</div>
</div> --}}

<div class="card mb-3">
    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-building me-2"></i>Company <span class="badge bg-secondary">1</span></span>
        <!-- <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus"></i></button> -->
    </div>
    <div class="card-body py-2 px-3">
        <ul class="list-group">
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="bi bi-buildings"></i> <a class="text-decoration-none" href="{{ route('organizations.show', optional($customer->organization)->id) }}">{{ optional($customer->organization)->name ?? '-' }}</a></span>
                <!-- <button class="btn btn-sm btn-danger"><i class="bi bi-x"></i></button> -->
            </li>
        </ul>
    </div>
</div>
</div>
<!-- <div class="col-md-7"> -->
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
<!-- </div> -->
</div>
</div>
</div>

<!-- Add Person Modal -->
<div class="modal fade" id="addPersonModal_1" tabindex="-1" aria-labelledby="addPersonModalLabel_1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addPersonForm" method="POST" action="{{ route('people.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addPersonModalLabel_1">Add Person</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="person_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="person_name" name="person_name" required placeholder="First Last">
                    </div>
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    <input type="hidden" name="organization_id" value="{{ $customer->organization_id }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Person</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
<!-- Ensure Bootstrap JS is loaded for modal functionality -->
@push('scripts')

<script>
    function refreshPeopleList() {
        fetch('/customers/{{ $customer->id }}/people')
            .then(response => response.json())
            .then(data => {
                var peopleList = document.getElementById('peopleList');
                // If you have a badge for people count, update it here (optional)
                peopleList.innerHTML = '';
                data.forEach(function(person) {
                    var li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center';
                    li.innerHTML = `<span>${person.first_name}</span>
                    <button class='btn btn-sm btn-danger delete-person' data-id='${person.id}'><i class='bi bi-trash'></i></button>`;
                    peopleList.appendChild(li);
                });

                // Optionally update people count badge if needed
                // document.querySelector('.badge.bg-secondary').textContent = data.length;
            });
    }

    // Wire customer delete button in header to SweetAlert2 confirmation
    document.addEventListener('DOMContentLoaded', function() {
        //Swal Alert for delete confirmation
        var buttons = document.querySelectorAll('.delete-person-btn');
        var name = 'data-person-name';
        attachDeleteHandlers(buttons, name); 
    });
</script>
@endpush