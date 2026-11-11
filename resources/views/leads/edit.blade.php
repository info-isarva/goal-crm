<!-- Modern Leads Edit Styles to Match Dashboard -->

@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{asset('css/leads.css') }}">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid p-4">
    <div class="card mt-0">
        <form method="POST" action="{{ route('leads.update', $lead->id) }}">
            @csrf
            @method('PUT')
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Edit Lead</h4>
                <div class="text-end">
                    <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-light btn-sm">&laquo; Back to Details</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="organization_id" class="form-label">Company Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-buildings"></i></span>
                                <input type="text" class="form-control" id="organization_id" name="organization_id" value="{{ old('organization_id', $lead->organization ? $lead->organization->name : '') }}" placeholder="Search or add organization" autocomplete="off" readonly>
                                <div class="dropdown-menu w-100 p-0" id="org-autocomplete-list" style="top:38px; min-width:100%;">
                                    <div class="p-2 border-bottom bg-white sticky-top" style="z-index:3;">
                                        <input type="text" id="org-autocomplete-search" class="form-control form-control-sm" placeholder="Search organization..." autocomplete="off">
                                    </div>
                                    <div id="org-autocomplete-scroll" style="max-height:200px; overflow-y:auto;"></div>
                                    <div id="org-autocomplete-new" style="border-top:1px solid #eee; background:#fff; position:sticky; bottom:0; z-index:2;"></div>
                                </div>
                            </div>
                            @error('organization_id')
                            <div id="organization_id_error" class="text-danger small mt-1" style="display:none;"></div>
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="people_id" class="form-label">Company Contact Person <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="people_id" name="people_id" value="{{ old('people_id', $lead->person ? $lead->person->first_name . ' ' . ($lead->person->last_name ? $lead->person->last_name : '') : '') }}" placeholder="Search or add contact person" autocomplete="off" readonly>
                                <span class="input-group-text bg-light" id="person-new-label" style="display:none;"><span class="badge bg-success">New</span></span>
                                <div class="dropdown-menu w-100 p-0" id="person-autocomplete-list" style="top:38px; min-width:100%;">
                                    <div class="p-2 border-bottom bg-white sticky-top" style="z-index:3;">
                                        <input type="text" id="person-autocomplete-search" class="form-control form-control-sm" placeholder="Search contact person..." autocomplete="off">
                                    </div>
                                    <div id="person-autocomplete-scroll" style="max-height:200px; overflow-y:auto;"></div>
                                    <div id="person-autocomplete-new" style="border-top:1px solid #eee; background:#fff; position:sticky; bottom:0; z-index:2;"></div>
                                </div>
                            </div>
                            @error('people_id')
                            <div id="people_id_error" class="text-danger small mt-1" style="display:none;"></div>
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Company Owner</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                <input type="text" class="form-control" id="customer_id" name="customer_id" value="{{ old('customer_id', $lead->customer ? $lead->customer->name : '') }}" placeholder="Search or add customer" autocomplete="off" readonly>
                                <div class="dropdown-menu w-100 p-0" id="customer-autocomplete-list" style="top:38px; min-width:100%;">
                                    <div class="p-2 border-bottom bg-white sticky-top" style="z-index:3;">
                                        <input type="text" id="customer-autocomplete-search" class="form-control form-control-sm" placeholder="Search customer..." autocomplete="off">
                                    </div>
                                    <div id="customer-autocomplete-scroll" style="max-height:200px; overflow-y:auto;"></div>
                                    <div id="customer-autocomplete-new" style="border-top:1px solid #eee; background:#fff; position:sticky; bottom:0; z-index:2;"></div>
                                </div>
                            </div>
                            @error('customer_id')
                            <div id="customer_id_error" class="text-danger small mt-1" style="display:none;"></div>
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $lead->title) }}">
                            @error('title')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="250">{{ old('description', $lead->description) }}</textarea>
                            @error('description')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="amount" name="amount" value="{{ old('amount', $lead->amount) }}">
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="lead_source" class="form-label">Lead Source <span class="text-danger">*</span></label>
                            <select class="form-select" id="lead_source" name="lead_source">
                                <option value="">None</option>
                                @foreach($leadSources as $source)
                                    <option value="{{ $source->id }}" data-name="{{ $source->name }}" @if(old('lead_source', $lead->lead_source)==$source->id) selected @endif>{{ $source->name }}</option>
                                @endforeach
                            </select>
                            @error('lead_source')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        @include('partials.referral_name_field', ['referralNameValue' => $lead->referral_name ?? ''])
                        <div class="mb-3">
                            <label for="status" class="form-label">Lead Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" onchange="toggleReasonForLossLead()">
                                <option value="">None</option>
                                @foreach($leadStatuses as $status)
                                    <option value="{{ $status->name }}" @if(old('status') == $status->name || (isset($lead) && $lead->status == $status->name)) selected @endif>{{ $status->name }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" id="reason_for_loss_lead_container" style="display: {{ old('status', $lead->status) == 'Closed Lost' ? 'block' : 'none' }};">
                            <label for="reason_for_loss" class="form-label">Reason for Loss <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reason_for_loss" name="reason_for_loss" rows="2" placeholder="Please specify the reason">{{ old('reason_for_loss', $lead->reason_for_loss) }}</textarea>
                            @error('reason_for_loss')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="label" class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" id="label" name="label">
                                <option value="">None</option>
                                <option value="high" @if(old('label', $lead->label)=='high') selected @endif>High</option>
                                <option value="normal" @if(old('label', $lead->label)=='normal') selected @endif>Normal</option>
                                <option value="low" @if(old('label', $lead->label)=='low') selected @endif>Low</option>
                            </select>
                            @error('label')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                            <div class="mb-3">
                            <label for="industry_id" class="form-label">Industries <span class="text-danger">*</span></label>
                            <select class="form-select" id="industry_id" name="industry_id">
                                <option value="">None</option>
                                @foreach($industries as $industry)
                                    <option value="{{ $industry->id }}" @if((string) old('industry_id', $lead->industry_id) === (string) $industry->id) selected @endif>{{ $industry->name }}</option>
                                @endforeach
                            </select>
                            @error('industry_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            </div>
                        <div class="mb-3">
                            <label for="categories" class="form-label">Product/Services <span class="text-danger">*</span></label>
                            @if($categories->isNotEmpty())
                                @php
                                $selectedCategories = old('categories', $lead->category);
                                if (is_null($selectedCategories)) {
                                    $selectedCategories = [];
                                } elseif (is_string($selectedCategories)) {
                                    $selectedCategories = explode(',', $selectedCategories);
                                }
                            @endphp
                            <select class="form-control @error('categories') is-invalid @enderror" id="category" name="categories[]" multiple>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ in_array($category->id, $selectedCategories) ? 'selected' : '' }}>{{ $category->category_name }}</option>
                                @endforeach
                            </select>
                            @else
                                <p class="text-muted">No categories available.</p>
                            @endif
                            @error('categories')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="user_owner_id" class="form-label">Owner</label>
                            <select class="form-select" id="user_owner_id" name="user_owner_id">
                                <option value="">Select owner</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @if(old('user_owner_id', $lead->owner ? $lead->owner->id : null)==$user->id) selected @endif>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('user_owner_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-start">
                <button type="submit" class="btn btn-custom">Update Leads</button>
            </div>
        </form>
    </div>
</div>

<!-- Organization New Modal -->
<div class="modal fade" id="orgNewModal" tabindex="-1" aria-labelledby="orgNewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="orgNewForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orgNewModalLabel">Add New Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="org_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" placeholder="Enter company name" class="form-control" id="org_name" name="org_name">
                        <div id="org_name_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="org_address" class="form-label">Address <span class="text-danger">*</span></label>
                        <input type="text" placeholder="Enter company address" class="form-control" id="org_address" name="org_address">
                        <div id="org_address_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    @include('organizations.partials.ajax_org_location_fields')
                    <div class="mb-3">
                        <label for="org_phone" class="form-label">Phone</label>
                        <input type="text" placeholder="+91 9457834901" class="form-control" id="org_phone" name="org_phone">
                        <div id="org_phone_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="org_website" class="form-label">Website</label>
                        <input type="text" placeholder="https://example.com" class="form-control" id="org_website" name="org_website">
                        <div id="org_website_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-custom">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Customer New Modal -->
<div class="modal fade" id="customerNewModal" tabindex="-1" aria-labelledby="customerNewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="customerNewForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerNewModalLabel">Add New Company Owner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cust_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" placeholder="Enter company owner name" class="form-control" id="cust_name" name="cust_name">
                        <div id="cust_name_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="cust_org_id" class="form-label">Company <span class="text-danger">*</span></label>
                        <select class="form-select" id="cust_org_id" name="cust_org_id">
                            <option value="">Select company</option>
                            @foreach ($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        <div id="cust_org_id_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-custom">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Contact Person New Modal -->
<div class="modal fade" id="personNewModal" tabindex="-1" aria-labelledby="personNewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="personNewForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="personNewModalLabel">Add New Contact Person</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-3">
                            <label for="person_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="person_first_name" name="person_first_name">
                            <div id="person_first_name_error" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                        <div class="col mb-3">
                            <label for="person_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="person_last_name" name="person_last_name">
                            <div id="person_last_name_error" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="person_org_id" class="form-label">Company <span class="text-danger">*</span></label>
                        <select class="form-select" id="person_org_id" name="person_org_id">
                            <option value="">Select company</option>
                            @foreach ($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        <div id="person_org_id_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="person_designation" class="form-label">Designation</label>
                        <input type="text" placeholder="e.g. Manager, Director" class="form-control" id="person_designation" name="person_designation">
                        <div id="person_designation_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="person_email" class="form-label">Email</label>
                        <input type="email" placeholder="Enter email address" class="form-control" id="person_email" name="person_email">
                        <div id="person_email_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="person_phone" class="form-label">Mobile <span class="text-danger">*</span></label>
                        <input type="text" placeholder="+91 9457834901" class="form-control" id="person_mobile" name="person_phone">
                        <div id="person_phone_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="person_land_number" class="form-label">Land Number</label>
                        <input type="text" placeholder="+91 80 12345678" class="form-control" id="person_land_number" name="person_land_number">
                        <div id="person_land_number_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-custom">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

@include('partials.industry_new_modal')

<!-- Bootstrap JS Bundle -->
<script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
<script>
function toggleReasonForLossLead() {
    var status = document.getElementById('status').value;
    var reasonContainer = document.getElementById('reason_for_loss_lead_container');
    if (status === 'Closed Lost' || status === 'Lost Lead') {
        reasonContainer.style.display = 'block';
    } else {
        reasonContainer.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('status').addEventListener('change', toggleReasonForLossLead);
    toggleReasonForLossLead();

    // ─── Shared state: tracks the last created/selected organization ───────────
    // This is the single source of truth used by both person and customer modals.
    let lastCreatedOrg = null; // { id, name } — set after a new org is created via modal

    const CSRF_TOKEN = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Fetch the latest organizations from the server and populate a <select> by id.
     * After populating, if `autoSelectOrg` ({ id, name }) is provided,
     * that org will be automatically selected.
     */
    function loadLatestOrganizations(selectId, autoSelectOrg) {
        return fetch('/organizations/autocomplete?q=')
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById(selectId);
                if (!select) return;
                select.innerHTML = '<option value="">Select company</option>';
                data.forEach(item => {
                    const id   = item.id   || item.value || item;
                    const name = item.name || item.label || item;
                    const option = document.createElement('option');
                    option.value = id;
                    option.textContent = name;
                    select.appendChild(option);
                });
                // Auto-select if a target org is provided
                if (autoSelectOrg && autoSelectOrg.id) {
                    select.value = autoSelectOrg.id;
                }
            });
    }

    /**
     * Apply the "readonly display" pattern to a modal's org select.
     * If the currently selected org (from orgInput) matches an option in the
     * given select, hide the select, show a readonly text input, and inject a
     * hidden input so FormData still carries the correct org id.
     *
     * @param {string} selectId       — id of the <select> inside the modal
     * @param {string} readonlyId     — id to use for the injected readonly <input>
     * @param {string} hiddenId       — id to use for the injected <input type="hidden">
     * @param {string} orgName        — org name to match / display (from orgInput or lastCreatedOrg)
     * @param {string|number} orgId   — org id to pre-select (optional; derived from select if omitted)
     */
    function applyReadonlyOrgField(selectId, readonlyId, hiddenId, orgName, orgId) {
        const orgSelect = document.getElementById(selectId);
        if (!orgSelect) return;

        // Find the option whose text matches orgName
        let foundIndex = -1;
        let foundId    = orgId || '';
        for (let i = 0; i < orgSelect.options.length; i++) {
            if (orgSelect.options[i].text === orgName) {
                foundIndex = i;
                if (!foundId) foundId = orgSelect.options[i].value;
                break;
            }
        }

        if (foundIndex !== -1 && orgName) {
            // Select the matching option
            orgSelect.selectedIndex = foundIndex;
            orgSelect.style.display = 'none';

            // Show readonly text input
            let readonlyInput = document.getElementById(readonlyId);
            if (!readonlyInput) {
                readonlyInput = document.createElement('input');
                readonlyInput.type      = 'text';
                readonlyInput.className = 'form-control';
                readonlyInput.id        = readonlyId;
                readonlyInput.readOnly  = true;
                orgSelect.parentNode.insertBefore(readonlyInput, orgSelect);
            }
            readonlyInput.value = orgName;
            readonlyInput.style.display = '';

            // Inject hidden input to carry org id reliably through FormData
            let hidInput = document.getElementById(hiddenId);
            if (!hidInput) {
                hidInput = document.createElement('input');
                hidInput.type = 'hidden';
                hidInput.id   = hiddenId;
                hidInput.name = hiddenId;
                orgSelect.parentNode.insertBefore(hidInput, orgSelect);
            }
            hidInput.value = foundId;

        } else {
            // No match — show the normal select, remove any readonly remnants
            orgSelect.style.display = '';
            const readonlyInput = document.getElementById(readonlyId);
            if (readonlyInput) readonlyInput.remove();
            const hidInput = document.getElementById(hiddenId);
            if (hidInput) hidInput.remove();
        }
    }

    // ─── Organization autocomplete ────────────────────────────────────────────

    const orgInput    = document.getElementById('organization_id');
    const orgDropdown = document.getElementById('org-autocomplete-list');
    let orgListData   = [];

    function renderOrgList(filter = '') {
        const scrollDiv = document.getElementById('org-autocomplete-scroll');
        const newDiv    = document.getElementById('org-autocomplete-new');
        scrollDiv.innerHTML = '';

        orgListData
            .filter(org => org.name.toLowerCase().includes(filter.toLowerCase()))
            .forEach(org => {
                const item = document.createElement('button');
                item.type      = 'button';
                item.className = 'dropdown-item';
                item.textContent = org.name;
                item.onclick = function () {
                    orgInput.value = org.name;
                    // When selecting an existing org, clear lastCreatedOrg so modals
                    // show the regular select (user can pick any org).
                    lastCreatedOrg = null;
                    orgDropdown.style.display = 'none';

                    // Fetch org details and autofill dependent fields
                    fetch('/organizations/details?name=' + encodeURIComponent(org.name))
                        .then(res => res.json())
                        .then(data => {
                            if (Array.isArray(data.customers) && data.customers.length > 0) {
                                const custEl = document.getElementById('customer_id');
                                if (custEl) custEl.value = data.customers[0].name;
                            } else {
                                const custEl = document.getElementById('customer_id');
                                if (custEl) custEl.value = '';
                            }
                            if (Array.isArray(data.people) && data.people.length > 0) {
                                const personEl = document.getElementById('people_id');
                                if (personEl) personEl.value = data.people[0].full_name;
                            } else {
                                const personEl = document.getElementById('people_id');
                                if (personEl) personEl.value = '';
                            }
                            if (data.owner) {
                                const ownerSelect = document.getElementById('user_owner_id');
                                if (ownerSelect && data.owner.id) {
                                    ownerSelect.value = data.owner.id;
                                    if (typeof $ !== 'undefined') $(ownerSelect).val(data.owner.id).trigger('change');
                                }
                            }
                            if (data.industry_id || data.industry_type) {
                                const industrySelect = document.getElementById('industry_id');
                                const industryVal = data.industry_id || data.industry_type;
                                if (industrySelect && industryVal) {
                                    if (typeof $ !== 'undefined') {
                                        $(industrySelect).val(String(industryVal)).trigger('change');
                                    } else {
                                        industrySelect.value = String(industryVal);
                                    }
                                }
                            }
                        })
                        .catch(() => {
                            const custEl   = document.getElementById('customer_id');   if (custEl)   custEl.value   = '';
                            const personEl = document.getElementById('people_id');     if (personEl) personEl.value = '';
                        });
                };
                scrollDiv.appendChild(item);
            });

        // "New Company" link — always at the bottom
        newDiv.innerHTML = '';
        const newBtn = document.createElement('a');
        newBtn.href      = '#';
        newBtn.className = 'dropdown-item text-primary';
        newBtn.textContent = 'New Company';
        newBtn.onclick = function (e) {
            e.preventDefault();
            orgDropdown.style.display = 'none';
            // Reset the form before showing
            document.getElementById('orgNewForm').reset();
            ['org_name_error','org_address_error','org_country_error','org_state_error','org_city_error','org_phone_error','org_website_error'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.style.display = 'none'; el.textContent = ''; }
            });
            setTimeout(() => new bootstrap.Modal(document.getElementById('orgNewModal')).show(), 100);
        };
        newDiv.appendChild(newBtn);
    }

    orgInput.addEventListener('focus', function () {
        fetch('/organizations/autocomplete?q=')
            .then(res => res.json())
            .then(data => {
                orgListData = data;
                renderOrgList();
                orgDropdown.style.display = 'block';
                document.getElementById('org-autocomplete-search').value = '';
            });
    });

    document.getElementById('org-autocomplete-search').addEventListener('input', function (e) {
        renderOrgList(e.target.value);
    });

    document.addEventListener('mousedown', function (e) {
        if (orgDropdown.style.display === 'block' && !orgDropdown.contains(e.target) && e.target !== orgInput) {
            orgDropdown.style.display = 'none';
        }
    });

    // ─── New Organization modal submit ────────────────────────────────────────

    document.getElementById('orgNewForm').addEventListener('submit', function (e) {
        e.preventDefault();
        ['org_name_error','org_address_error','org_country_error','org_state_error','org_city_error','org_phone_error','org_website_error'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.style.display = 'none'; el.textContent = ''; }
        });

        const formData = new FormData(this);
        if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
        const selectedIndustry = document.getElementById('industry_id');
        if (selectedIndustry && selectedIndustry.value) {
            formData.append('industry_type', selectedIndustry.value);
        }

        fetch('/organizations/ajax-create', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: formData
        })
        .then(async res => {
            if (res.status === 422) {
                const data = await res.json();
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        const el = document.getElementById(key + '_error');
                        if (el) { el.textContent = data.errors[key][0]; el.style.display = 'block'; }
                    });
                }
                throw new Error('Validation error');
            }
            return res.json();
        })
        .then(org => {
            // ── KEY CHANGE: store the newly created org so modals can auto-select it ──
            lastCreatedOrg = { id: org.id, name: org.name };

            // Set the main org input
            orgInput.value = org.name;

            // Clear dependent fields since this is a brand-new org
            const custEl   = document.getElementById('customer_id');   if (custEl)   custEl.value   = '';
            const personEl = document.getElementById('people_id');     if (personEl) personEl.value = '';

            if (org.industry_type || org.industry_id) {
                const industrySelect = document.getElementById('industry_id');
                const industryVal = org.industry_type || org.industry_id;
                if (industrySelect && industryVal) {
                    if (typeof $ !== 'undefined') {
                        $(industrySelect).val(String(industryVal)).trigger('change');
                    } else {
                        industrySelect.value = String(industryVal);
                    }
                }
            }

            // Dismiss the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('orgNewModal'));
            if (modal) modal.hide();
        })
        .catch(() => {});
    });

    // ─── Customer autocomplete ────────────────────────────────────────────────

    const custInput    = document.getElementById('customer_id');
    const custDropdown = document.getElementById('customer-autocomplete-list');
    let customerListData = [];

    function renderCustomerList(filter = '') {
        const scrollDiv = document.getElementById('customer-autocomplete-scroll');
        const newDiv    = document.getElementById('customer-autocomplete-new');
        scrollDiv.innerHTML = '';

        customerListData
            .filter(cust => (cust.name || cust).toLowerCase().includes(filter.toLowerCase()))
            .forEach(cust => {
                const item = document.createElement('button');
                item.type      = 'button';
                item.className = 'dropdown-item';
                item.textContent = cust.name || cust;
                item.onclick = function () {
                    custInput.value = cust.name || cust;
                    custDropdown.style.display = 'none';
                };
                scrollDiv.appendChild(item);
            });

        newDiv.innerHTML = '';
        const newBtn = document.createElement('a');
        newBtn.href      = '#';
        newBtn.className = 'dropdown-item text-primary';
        newBtn.textContent = 'New Company Owner';
        newBtn.onclick = function (e) {
            e.preventDefault();
            custDropdown.style.display = 'none';
            setTimeout(() => new bootstrap.Modal(document.getElementById('customerNewModal')).show(), 100);
        };
        newDiv.appendChild(newBtn);
    }

    custInput.addEventListener('focus', function () {
        const orgName = orgInput.value;
        let url = '/customers/autocomplete?q=';
        if (orgName) url += '&organization=' + encodeURIComponent(orgName);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                customerListData = data;
                renderCustomerList();
                custDropdown.style.display = 'block';
                document.getElementById('customer-autocomplete-search').value = '';
            });

        document.getElementById('customer-autocomplete-search').oninput = function (e) {
            renderCustomerList(e.target.value);
        };
    });

    document.addEventListener('mousedown', function (e) {
        if (custDropdown.style.display === 'block' && !custDropdown.contains(e.target) && e.target !== custInput) {
            custDropdown.style.display = 'none';
        }
    });

    // ─── Contact Person autocomplete ──────────────────────────────────────────

    const personInput    = document.getElementById('people_id');
    const personDropdown = document.getElementById('person-autocomplete-list');
    let personListData   = [];

    function renderPersonList(filter = '') {
        const scrollDiv = document.getElementById('person-autocomplete-scroll');
        const newDiv    = document.getElementById('person-autocomplete-new');
        scrollDiv.innerHTML = '';

        const filtered = personListData.filter(person => {
            const full = ((person.full_name || ((person.first_name || '') + ' ' + (person.last_name || ''))).toLowerCase());
            const hay = full + ' ' + String(person.email || '').toLowerCase() + ' ' + String(person.mobile || '') + ' ' + String(person.phone || '');
            return String(filter || '').toLowerCase().trim().split(/\s+/).filter(Boolean).every(t => hay.includes(t));
        });
        const linked = filtered.filter(p => p.linked);
        const others = filtered.filter(p => !p.linked);

        function appendPersonItem(person) {
            const full = person.full_name || ((person.first_name || '') + (person.last_name ? ' ' + person.last_name : ''));
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'dropdown-item d-flex justify-content-between align-items-center';
            const nameSpan = document.createElement('span');
            nameSpan.textContent = full;
            item.appendChild(nameSpan);
            if (!person.linked) {
                const badge = document.createElement('small');
                badge.className = 'text-muted ms-2';
                badge.textContent = 'other company';
                item.appendChild(badge);
            }
            item.onclick = function () {
                personInput.value = full;
                personInput.dataset.personId = person.id || '';
                personDropdown.style.display = 'none';
            };
            scrollDiv.appendChild(item);
        }

        if (linked.length) {
            const hdr = document.createElement('div');
            hdr.className = 'dropdown-header py-1';
            hdr.textContent = 'Company contacts';
            scrollDiv.appendChild(hdr);
            linked.forEach(appendPersonItem);
        }
        if (others.length) {
            const hdr = document.createElement('div');
            hdr.className = 'dropdown-header py-1';
            hdr.textContent = linked.length ? 'Other contacts' : 'Existing contacts';
            scrollDiv.appendChild(hdr);
            others.forEach(appendPersonItem);
        }
        if (!filtered.length) {
            const empty = document.createElement('div');
            empty.className = 'p-2 text-muted';
            empty.textContent = 'No matching contacts. Use New Contact Person below.';
            scrollDiv.appendChild(empty);
        }

        newDiv.innerHTML = '';
        const newBtn = document.createElement('a');
        newBtn.href      = '#';
        newBtn.className = 'dropdown-item text-primary';
        newBtn.textContent = 'New Contact Person';
        newBtn.onclick = function (e) {
            e.preventDefault();
            personDropdown.style.display = 'none';
            setTimeout(() => new bootstrap.Modal(document.getElementById('personNewModal')).show(), 100);
        };
        newDiv.appendChild(newBtn);
    }

    personInput.addEventListener('focus', function () {
        const orgName = (orgInput.value || '').trim();
        if (!orgName) {
            document.getElementById('person-autocomplete-scroll').innerHTML =
                '<div class="p-2 text-muted">Please select a Company first to list contacts.</div>';
            document.getElementById('person-autocomplete-new').innerHTML = '';
            personDropdown.style.display = 'block';
            return;
        }
        let url = '/people/autocomplete?q=&organization=' + encodeURIComponent(orgName);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                personListData = data;
                renderPersonList();
                personDropdown.style.display = 'block';
                document.getElementById('person-autocomplete-search').value = '';
            });

        let personSearchTimer = null;
        let personSearchSeq = 0;
        document.getElementById('person-autocomplete-search').oninput = function (e) {
            const term = e.target.value;
            renderPersonList(term);
            clearTimeout(personSearchTimer);
            personSearchTimer = setTimeout(function () {
                const seq = ++personSearchSeq;
                fetch('/people/autocomplete?q=' + encodeURIComponent(term.trim()) + '&organization=' + encodeURIComponent(orgName))
                    .then(res => res.json())
                    .then(data => {
                        if (seq !== personSearchSeq) return;
                        personListData = Array.isArray(data) ? data : [];
                        renderPersonList(term);
                    })
                    .catch(() => {});
            }, 250);
        };
    });

    document.addEventListener('mousedown', function (e) {
        if (personDropdown.style.display === 'block' && !personDropdown.contains(e.target) && e.target !== personInput) {
            personDropdown.style.display = 'none';
        }
    });

    // ─── Customer modal: show.bs.modal ────────────────────────────────────────
    // Determine which org to pre-select:
    //   1. If a new org was just created via the org modal → use lastCreatedOrg
    //   2. Otherwise → use whatever is typed in orgInput (existing org)
    // In both cases: reload the org list first so the newly created org appears,
    // then apply the readonly pattern.

    document.getElementById('customerNewModal').addEventListener('show.bs.modal', function () {
        // Reset form & errors
        document.getElementById('customerNewForm').reset();
        ['cust_name_error','cust_org_id_error'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.style.display = 'none'; el.textContent = ''; }
        });

        const targetOrg = lastCreatedOrg
            ? lastCreatedOrg          // newly created org takes priority
            : { name: orgInput.value, id: null };   // fall back to typed org name

        // Reload org list so newly created org is present, then auto-select
        loadLatestOrganizations('cust_org_id', lastCreatedOrg)
            .then(() => {
                // After list is loaded, apply readonly display for the target org
                applyReadonlyOrgField(
                    'cust_org_id',
                    'cust_org_readonly',
                    'cust_org_id_hidden',
                    targetOrg.name,
                    targetOrg.id || null
                );
            });
    });

    // ─── Person modal: show.bs.modal ──────────────────────────────────────────

    document.getElementById('personNewModal').addEventListener('show.bs.modal', function () {
        // Reset form & errors
        document.getElementById('personNewForm').reset();
        ['person_first_name_error','person_last_name_error','person_org_id_error','person_email_error','person_phone_error','person_designation_error','person_land_number_error'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.style.display = 'none'; el.textContent = ''; }
        });

        const targetOrg = lastCreatedOrg
            ? lastCreatedOrg
            : { name: orgInput.value, id: null };

        // Reload org list so newly created org is present, then auto-select
        loadLatestOrganizations('person_org_id', lastCreatedOrg)
            .then(() => {
                applyReadonlyOrgField(
                    'person_org_id',
                    'person_org_readonly',
                    'person_org_id_hidden',
                    targetOrg.name,
                    targetOrg.id || null
                );
            });
    });

    // ─── New Customer modal submit ────────────────────────────────────────────

    document.getElementById('customerNewForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formEl = this;

        ['cust_name_error','cust_org_id_error'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.style.display = 'none'; el.textContent = ''; }
        });

        const custName  = (document.getElementById('cust_name').value || '').trim();
        const orgSelect = document.getElementById('cust_org_id');
        const orgName   = orgSelect && orgSelect.options[orgSelect.selectedIndex] ? orgSelect.options[orgSelect.selectedIndex].text : '';

        function doCreateCustomer() {
            const formData = new FormData(formEl);
            const custHidden = document.getElementById('cust_org_id_hidden');
            if (custHidden) formData.set('cust_org_id', custHidden.value);
            if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);

            fetch('/customers/ajax-create', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: formData
            })
            .then(async res => {
                if (res.status === 422) {
                    const data = await res.json();
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const el = document.getElementById(key + '_error');
                            if (el) { el.textContent = data.errors[key][0]; el.style.display = 'block'; }
                        });
                    }
                    throw new Error('Validation error');
                }
                return res.json();
            })
            .then(cust => {
                if (cust && cust.duplicate) {
                    const el = document.getElementById('cust_name_error');
                    if (el) { el.textContent = 'A company owner named "' + (cust.name || '') + '" already exists for the selected organization.'; el.style.display = 'block'; }
                    const nameInput = document.getElementById('cust_name'); if (nameInput) nameInput.focus();
                    return;
                }
                custInput.value = cust.name || '';
                try { const modal = bootstrap.Modal.getInstance(document.getElementById('customerNewModal')); if (modal) modal.hide(); } catch (_) {}
            })
            .catch(() => {});
        }

        if (custName && orgSelect && orgSelect.value) {
            const checkUrl = '/customers/autocomplete?q=' + encodeURIComponent(custName) + '&organization=' + encodeURIComponent(orgName);
            fetch(checkUrl)
                .then(res => res.json())
                .then(matches => {
                    const duplicate = (Array.isArray(matches) ? matches : []).find(item => (item.name || item).toLowerCase() === custName.toLowerCase());
                    if (duplicate) {
                        const el = document.getElementById('cust_name_error');
                        if (el) { el.textContent = 'A company owner named "' + custName + '" already exists for "' + orgName + '".'; el.style.display = 'block'; }
                        const nameInput = document.getElementById('cust_name'); if (nameInput) nameInput.focus();
                        return;
                    }
                    doCreateCustomer();
                })
                .catch(() => doCreateCustomer());
            return;
        }

        doCreateCustomer();
    });

    // ─── New Contact Person modal submit ──────────────────────────────────────

    document.getElementById('personNewForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formEl = this;

        ['person_first_name_error','person_last_name_error','person_org_id_error','person_email_error','person_phone_error','person_designation_error','person_land_number_error'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.style.display = 'none'; el.textContent = ''; }
        });

        const firstName = document.getElementById('person_first_name').value.trim();
        const lastName  = document.getElementById('person_last_name').value.trim();
        const singleWordRegex = /^\w+$/;
        let errorMsg = '';
        if (firstName && !singleWordRegex.test(firstName)) errorMsg += 'First name must be a single word.\n';
        if (lastName  && !singleWordRegex.test(lastName))  errorMsg += 'Last name must be a single word.';
        if (errorMsg) {
            const el = document.getElementById('person_first_name_error');
            el.textContent = errorMsg; el.style.display = 'block';
            return;
        }

        const orgSelect = document.getElementById('person_org_id');
        const orgName   = orgSelect && orgSelect.options[orgSelect.selectedIndex] ? orgSelect.options[orgSelect.selectedIndex].text : '';
        const q         = firstName + (lastName ? ' ' + lastName : '');

        function doCreatePerson() {
            const formData = new FormData(formEl);
            const personHidden = document.getElementById('person_org_id_hidden');
            if (personHidden) formData.set('person_org_id', personHidden.value);
            if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);

            fetch('/people/ajax-create', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: formData
            })
            .then(async res => {
                if (res.status === 422) {
                    const data = await res.json();
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const el = document.getElementById(key + '_error');
                            if (el) { el.textContent = data.errors[key][0]; el.style.display = 'block'; }
                        });
                    }
                    throw new Error('Validation error');
                }
                return res.json();
            })
            .then(person => {
                if (person && person.duplicate) {
                    const el  = document.getElementById('person_first_name_error');
                    const msg = 'A contact person named "' + ((person.first_name || '') + (person.last_name ? ' ' + person.last_name : '')).trim() + '" already exists for the selected organization.';
                    if (el) { el.textContent = msg; el.style.display = 'block'; }
                    const firstInput = document.getElementById('person_first_name'); if (firstInput) firstInput.focus();
                    return;
                }
                personInput.value = (person.first_name || '') + (person.last_name ? ' ' + person.last_name : '');
                const modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                if (modal) modal.hide();
            })
            .catch(() => {});
        }

        if (firstName) {
            const checkUrl = '/people/autocomplete?q=' + encodeURIComponent(q) + (orgName ? '&organization=' + encodeURIComponent(orgName) : '');
            fetch(checkUrl)
                .then(res => res.json())
                .then(matches => {
                    const duplicate = (Array.isArray(matches) ? matches : []).find(p => ((p.first_name || '') + ' ' + (p.last_name || '')).trim().toLowerCase() === q.trim().toLowerCase());
                    if (duplicate) {
                        const el  = document.getElementById('person_first_name_error');
                        const msg = 'A contact person named "' + q + '" already exists for "' + (orgName || 'the selected organization') + '".';
                        if (el) { el.textContent = msg; el.style.display = 'block'; }
                        const firstInput = document.getElementById('person_first_name'); if (firstInput) firstInput.focus();
                        return;
                    }
                    doCreatePerson();
                })
                .catch(() => doCreatePerson());
            return;
        }

        doCreatePerson();
    });

    // ─── Select2 initialisation ───────────────────────────────────────────────

    $('#user_owner_id').select2({ placeholder: 'Select owner',             allowClear: true, width: '100%' });
    $('#lead_source').select2(  { placeholder: 'Select lead source',        allowClear: true, width: '100%' });
    $('#status').select2(       { placeholder: 'Select status',             allowClear: true, width: '100%' });
    $('#label').select2(        { placeholder: 'Select priority',           allowClear: true, width: '100%' });
    $('#industry_id').select2({ placeholder: 'Select Industries',   allowClear: true, width: '100%' });
    $('#category').select2(     { placeholder: 'Select Product/Services',   allowClear: true, width: '100%', closeOnSelect: false });
    // Compact when empty, grows with the typed text so the full search word is visible
    function shrinkCategorySearch() {
        $('#category').next('.select2-container').find('.select2-search__field').each(function () {
            var len = (this.value || '').length;
            var w = len ? 'calc(' + (len + 2) + 'ch)' : '0.75em';
            this.style.setProperty('width', w, 'important');
            this.style.setProperty('min-width', w, 'important');
            this.style.setProperty('max-width', '100%', 'important');
            this.style.setProperty('height', '28px', 'important');
            this.style.setProperty('min-height', '28px', 'important');
        });
    }
    shrinkCategorySearch();
    $('#category').on('select2:select select2:unselect select2:clear select2:open select2:close change', function () {
        setTimeout(shrinkCategorySearch, 0);
    });
    $('#category').next('.select2-container').on('input keyup', '.select2-search__field', shrinkCategorySearch);

    // Re-trigger toggleReasonForLossLead when Select2 changes the status field
    $('#status').on('change', function () { toggleReasonForLossLead(); });
});
</script>
@include('organizations.partials.ajax_org_location_scripts')
@include('partials.referral_name_script')
@include('partials.industry_new_script')
@endsection