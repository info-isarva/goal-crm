@extends('layouts.app')

@section('content')
@php
    $selectedFyId = session('selected_financial_year', null);
    $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
    $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
    $linkedOrganizations = $person->allOrganizations();
    $linkedOrgIds = $linkedOrganizations->pluck('id');
@endphp
<div class="container-fluid p-4">
    <div class="card mt-0">

        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="mb-0">{{ $person->first_name }} {{ $person->last_name }}</h3>
            <div class="d-flex gap-2 justify-content-end align-items-center ms-auto flex-nowrap" style="white-space:nowrap;">
                <a href="{{ route('people.index') }}" class="btn btn-light btn-sm">&laquo; Back</a>
                @if(!$isHistorical)
                <a href="{{ route('people.edit', $person->id) }}" class="btn btn-outline-secondary btn-sm @if(!auth()->user()->hasCrmPermission('edit_crm_contact_person_guard')) disabled @endif"><i class="bi bi-pencil"></i></a>
                <form method="POST" action="{{ route('people.delete', $person->id) }}" style="display:inline; margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="delete-person-btn btn btn-outline-danger btn-sm @if(!auth()->user()->hasCrmPermission('delete_crm_contact_person_guard')) disabled @endif" data-person-name="{{ $person->first_name }}" style="margin-left:0"><i class="bi bi-trash"></i></button>
                </form>
                @endif
            </div>
        </div>
        <div class="card-body row">
            <div class="col-md-5 border-end">
                <h5 class="mb-3">DETAILS</h5>
                <div class="mb-4">
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">First Name</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->first_name }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Last Name</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->last_name }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Gender</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->gender }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Date of Birth</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->dob }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Email</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->email }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Land Number</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->phone }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Mobile</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->mobile }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Designation</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->job_title }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Lead Source</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->lead_source }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Address</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->address }}</div>
                    </div>
                    <div class="row mb-2 align-items-center">
                        <div class="col-5 col-sm-4 fw-bold details-label text-start text-sm-end">Notes</div>
                        <div class="col-7 col-sm-8 details-value text-end text-sm-start">{{ $person->notes }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card mb-3">
                    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-building me-2"></i>Company</span>
                        @if(!$isHistorical)
                            <button class="btn btn-outline-secondary btn-sm @if(!auth()->user()->hasCrmPermission('edit_crm_contact_person_guard')) disabled @endif" data-bs-toggle="modal" data-bs-target="#addOrganizationModal"><i class="bi bi-plus"></i> Add Organization</button>
                        @endif
                    </div>
                    <div class="card-body py-2 px-3" id="person-organizations-list">
                        @if ($linkedOrganizations->isNotEmpty())
                        <ul class="list-group" id="person-org-ul">
                            @foreach($linkedOrganizations as $org)
                            <li class="list-group-item d-flex justify-content-between align-items-center" data-org-id="{{ $org->id }}">
                                <span><i class="bi bi-buildings"></i> <a class="text-decoration-none" href="{{ route('organizations.show', $org->id) }}">{{ $org->name }}</a></span>
                                @if(!$isHistorical)
                                <button type="button" class="btn btn-sm btn-outline-danger remove-org-btn" title="Remove organization" data-org-id="{{ $org->id }}" data-org-name="{{ $org->name }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                        @else
                        <div class="text-center text-muted" id="person-org-empty">No Company linked.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Organization Modal -->
<div class="modal fade" id="addOrganizationModal" tabindex="-1" aria-labelledby="addOrganizationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addOrganizationForm" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="addOrganizationModalLabel">Add Organization</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
                    <div class="mb-3">
                        <label for="organization_id_modal" class="form-label">Organization</label>
                        <select class="form-select" id="organization_id_modal" name="organization_id" style="width:100%">
                            <option value="">Select organization</option>
                            @foreach($organizations as $org)
                                @if(!$linkedOrgIds->contains($org->id))
                                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endif
                            @endforeach
                            <option value="__new__">+ New Organization</option>
                        </select>
                        <div id="orgModalFeedback" class="text-danger small mt-2"></div>
                    </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="saveOrganizationBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- New Organization Modal (from dropdown) -->
<div class="modal fade" id="orgNewModal" tabindex="-1" aria-labelledby="orgNewModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="orgNewForm" autocomplete="off">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="orgNewModalLabel">Add New Organization</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="org_name" class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" placeholder="Company Name" class="form-control" id="org_name" name="org_name">
            <div id="org_name_error" class="text-danger small mt-1" style="display:none;"></div>
          </div>
          <div class="mb-3">
            <label for="org_address" class="form-label">Address <span class="text-danger">*</span></label>
            <input type="text" placeholder="123 Main St" class="form-control" id="org_address" name="org_address">
            <div id="org_address_error" class="text-danger small mt-1" style="display:none;"></div>
          </div>
          <div class="mb-3">
            <label for="org_phone" class="form-label">Phone</label>
            <input type="text" placeholder="+91 9876234569" class="form-control" id="org_phone" name="org_phone">
            <div id="org_phone_error" class="text-danger small mt-1" style="display:none;"></div>
          </div>
          <div class="mb-3">
            <label for="org_website" class="form-label">Website URL</label>
            <input type="text" placeholder="http://example.com/" class="form-control" id="org_website" name="org_website">
            <div id="org_website_error" class="text-danger small mt-1" style="display:none;"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="orgNewSubmitBtn">Create & Assign</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@include('partials.select2')
@push('scripts')
<script>
$(document).ready(function() {
    function formatOrgOption(option) {
        if (!option.id) {
            return option.text;
        }
        if (option.id === '__new__') {
            return $('<span class="text-primary fw-semibold">+ New Organization</span>');
        }
        return option.text;
    }

    $('#organization_id_modal').select2({
        dropdownParent: $('#addOrganizationModal'),
        placeholder: 'Select organization',
        allowClear: true,
        width: '100%',
        templateResult: formatOrgOption,
        templateSelection: function(option) {
            if (option.id === '__new__') {
                return 'New Organization';
            }
            return option.text;
        }
    });

    $('#organization_id_modal').on('select2:select', function(e) {
        if (e.params.data.id === '__new__') {
            $(this).val(null).trigger('change');
            var addModalEl = document.getElementById('addOrganizationModal');
            var addModal = bootstrap.Modal.getInstance(addModalEl);
            if (addModal) {
                addModal.hide();
            }
            setTimeout(function() {
                document.getElementById('orgNewForm').reset();
                ['org_name_error', 'org_address_error', 'org_phone_error', 'org_website_error'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) { el.style.display = 'none'; el.textContent = ''; }
                });
                new bootstrap.Modal(document.getElementById('orgNewModal')).show();
            }, 250);
        }
    });

    function assignOrganizationToPerson(orgId, orgName) {
        return $.ajax({
            url: '{{ route('people.addOrganization', $person->id) }}',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: JSON.stringify({ organization_id: orgId, organization_name: orgName || '' })
        });
    }

    $('#saveOrganizationBtn').on('click', function(e) {
        e.preventDefault();
        $('#orgModalFeedback').text('');
        var orgId = $('#organization_id_modal').val();
        var orgName = $('#organization_id_modal option:selected').text();
        if (!orgId || orgId === '__new__') {
            $('#orgModalFeedback').text('Please select an organization.');
            return;
        }
        assignOrganizationToPerson(orgId, orgName)
            .done(function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    $('#orgModalFeedback').text(data.message || 'Error saving organization.');
                }
            })
            .fail(function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error saving organization.';
                $('#orgModalFeedback').text(msg);
            });
    });

    $('#orgNewForm').on('submit', function(e) {
        e.preventDefault();
        ['org_name_error', 'org_address_error', 'org_phone_error', 'org_website_error'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) { el.style.display = 'none'; el.textContent = ''; }
        });

        var formData = new FormData(this);
        formData.append('_token', '{{ csrf_token() }}');
        var $submitBtn = $('#orgNewSubmitBtn');
        $submitBtn.prop('disabled', true);

        fetch('/organizations/ajax-create', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: formData
        })
        .then(async function(res) {
            if (res.status === 422) {
                var data = await res.json();
                if (data.errors) {
                    Object.keys(data.errors).forEach(function(key) {
                        var errorDiv = document.getElementById(key + '_error');
                        if (errorDiv) {
                            errorDiv.textContent = data.errors[key][0];
                            errorDiv.style.display = 'block';
                        }
                    });
                }
                throw new Error('Validation error');
            }
            if (!res.ok) {
                throw new Error('Create failed');
            }
            return res.json();
        })
        .then(function(org) {
            return assignOrganizationToPerson(org.id, org.name).then(function() {
                location.reload();
            });
        })
        .catch(function() {
            // validation errors already shown
        })
        .finally(function() {
            $submitBtn.prop('disabled', false);
        });
    });

    $(document).on('click', '.remove-org-btn', function() {
        var orgId = $(this).data('org-id');
        var orgName = $(this).data('org-name') || 'this organization';
        if (!confirm('Remove link to "' + orgName + '"?')) {
            return;
        }
        $.ajax({
            url: '/people/{{ $person->id }}/organizations/' + orgId,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Unable to remove organization.');
                }
            },
            error: function() {
                alert('Unable to remove organization.');
            }
        });
    });

    var buttons = document.querySelectorAll('.delete-person-btn');
    var name = 'data-person-name';
    attachDeleteHandlers(buttons, name);
});
</script>
@endpush
