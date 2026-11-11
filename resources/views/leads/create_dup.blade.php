<!-- Modern Leads Create Styles to Match Dashboard -->
@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{asset('css/leads.css') }}">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <div class="container-fluid p-4">
        <div class="card mt-0">
            <form method="POST" action="{{ route('leads.store') }}">
                <div class="card-header d-flex justify-content-between align-items-center">

                    <h4 class="mb-0">Create Lead</h4>
                    <div class="text-end">
                        <a href="{{ route('leads.index') }}" class="btn btn-light btn-sm">&laquo; Back to Leads</a>

                    </div>
                </div>
                <div class="card-body">
                    @csrf
                    {{-- @if ($errors->any())
                        <div class="alert alert-danger">Please fix the errors below.</div>
                    @endif --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="organization_id" class="form-label">Company Name <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-buildings"></i></span>
                                    <input type="text" class="form-control input-text" id="organization_id" name="organization_id"
                                        value="{{ old('organization_id') }}" placeholder="Search or Add Company"
                                        autocomplete="off" readonly>
                                    <div class="dropdown-menu w-100 p-0" id="org-autocomplete-list"
                                        style="top:38px; min-width:100%;">
                                        <div class="p-2 border-bottom bg-white sticky-top" style="z-index:3;">
                                            <input type="text" id="org-autocomplete-search"
                                                class="form-control form-control-sm input-text" placeholder="Search Company..."
                                                autocomplete="off">
                                        </div>
                                        <div id="org-autocomplete-scroll" style="max-height:200px; overflow-y:auto;"></div>
                                        <div id="org-autocomplete-new"
                                            style="border-top:1px solid #eee; background:#fff; position:sticky; bottom:0; z-index:2;">
                                        </div>
                                    </div>
                                </div>
                                @error('organization_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                             <div class="mb-3">
                                <label for="people_id" class="form-label">Company Contact Person <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control input-text" id="people_id" name="people_id"
                                        value="{{ old('people_id') }}" placeholder="Search or Add Contact Person"
                                        autocomplete="off" readonly>
                                    <span class="input-group-text bg-light" id="person-new-label"
                                        style="display:none;"><span class="badge bg-success">New</span></span>
                                    <div class="dropdown-menu w-100 p-0" id="person-autocomplete-list"
                                        style="top:38px; min-width:100%;">
                                        <div class="p-2 border-bottom bg-white sticky-top" style="z-index:3;">
                                            <input type="text" id="person-autocomplete-search"
                                                class="form-control form-control-sm input-text" placeholder="Search Contact Person..."
                                                autocomplete="off">
                                        </div>
                                        <div id="person-autocomplete-scroll" style="max-height:200px; overflow-y:auto;">
                                        </div>
                                        <div id="person-autocomplete-new"
                                            style="border-top:1px solid #eee; background:#fff; position:sticky; bottom:0; z-index:2;">
                                        </div>
                                    </div>
                                </div>
                                @error('people_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Company Owner Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" class="form-control input-text" id="customer_id" name="customer_id"
                                        value="{{ old('customer_id') }}" placeholder="Search or Add Company Owner"
                                        autocomplete="off" readonly>
                                    <div class="dropdown-menu w-100 p-0" id="customer-autocomplete-list"
                                        style="top:38px; min-width:100%;">
                                        <div class="p-2 border-bottom bg-white sticky-top" style="z-index:3;">
                                            <input type="text" id="customer-autocomplete-search"
                                                class="form-control form-control-sm input-text" placeholder="Search Company Owner..."
                                                autocomplete="off">
                                        </div>
                                        <div id="customer-autocomplete-scroll" style="max-height:200px; overflow-y:auto;">
                                        </div>
                                        <div id="customer-autocomplete-new"
                                            style="border-top:1px solid #eee; background:#fff; position:sticky; bottom:0; z-index:2;">
                                        </div>
                                    </div>
                                </div>
                                @error('customer_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>


                            <div class="mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                    id="title" name="title" value="{{ old('title') }}">

                                @error('title')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" maxlength="250">{{ old('description') }}</textarea>

                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="amount" name="amount"
                                    value="{{ old('amount') }}">

                                @error('amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="lead_source" class="form-label">Lead Source<span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="lead_source" name="lead_source">

                                    <option value="" selected>None</option>
                                    @foreach ($leadSources as $source)
                                        <option value="{{ $source->id }}"
                                            @if (old('lead_source') == $source->id) selected @endif>{{ $source->name }}</option>
                                    @endforeach
                                </select>
                                @error('lead_source')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Lead Status <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status">

                                    <option value="">None</option>
                                    @foreach ($leadStatuses as $status)
                                        <option value="{{ $status->name }}"
                                            @if (old('status') == $status->name) selected @endif>{{ $status->name }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="label" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" id="label" name="label">

                                    <option value="">None</option>
                                    <option value="high" @if (old('label') == 'high') selected @endif>High</option>
                                    <option value="normal" @if (old('label') == 'normal') selected @endif>Normal
                                    </option>
                                    <option value="low" @if (old('label') == 'low') selected @endif>Low</option>
                                </select>
                                @error('label')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                                      <div class="mb-3">
                                          <label for="categories" class="form-label">Product/Services <span class="text-danger">*</span></label>
                                <select class="form-select @error('categories') is-invalid @enderror" id="category" name="categories[]" multiple >

                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>{{ $category->category_name }}</option>
                                    @endforeach
                                </select>

                                @error('categories')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="user_owner_id" class="form-label">Owner <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="user_owner_id" name="user_owner_id">
                                    <option value="">Select owner</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}"
                                            @if (isset($currentUser) && $currentUser->id == $user->id) selected @endif>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('user_owner_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- <div class="mb-3">
                                <label for="created_at" class="form-label">Created Date <span
                                        class="text-danger">*</span></label>
                                 <input type="datetime-local" class="form-control" id="created_at" name="created_at"
                                    value="{{ old('created_at') }}" required>

                            </div> -->
                        </div>
                    </div>
                </div>
                <div class="card-footer text-start">
                    <button type="submit" class="btn btn-custom">Create Leads</button>
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
                        <h5 class="modal-title" id="orgNewModalLabel">Add New Comapny</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="org_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" placeholder="Company Name" class="form-control" id="org_name"
                                name="org_name">
                            <div id="org_name_error" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                        <div class="mb-3">
                            <label for="org_address" class="form-label">Address <span
                                    class="text-danger">*</span></label>
                            <input type="text" placeholder="123 Main St" class="form-control" id="org_address"
                                name="org_address">
                            <div id="org_address_error" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                        <div class="mb-3">
                            <label for="org_phone" class="form-label">Phone</label>
                            <input type="text" placeholder="+91 9876234569" class="form-control" id="org_phone"
                                name="org_phone">
                            <div id="org_phone_error" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                        <div class="mb-3">
                            <label for="org_website" class="form-label">Website URL</label>
                            <input type="text" placeholder="http://example.com/" class="form-control"
                                id="org_website" name="org_website">
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
    <div class="modal fade" id="customerNewModal" tabindex="-1" aria-labelledby="customerNewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="customerNewForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="customerNewModalLabel">Add New Company Owner </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cust_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" placeholder="Company Owner name" class="form-control" id="cust_name" name="cust_name">
                            <div id="cust_name_error" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                        <div class="mb-3">
                            <label for="cust_org_id" class="form-label">Company <span
                                    class="text-danger">*</span></label>
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
    <div class="modal fade" id="personNewModal" tabindex="-1" aria-labelledby="personNewModalLabel"
        aria-hidden="true">
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
                                <label for="person_first_name" class="form-label">First Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="person_first_name"
                                    name="person_first_name">
                                <div id="person_first_name_error" class="text-danger small mt-1" style="display:none;">
                                </div>
                            </div>
                            <div class="col mb-3">
                                <label for="person_last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="person_last_name"
                                    name="person_last_name">
                                <div id="person_last_name_error" class="text-danger small mt-1" style="display:none;">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="person_org_id" class="form-label">Company <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="person_org_id" name="person_org_id">
                                <option value="">Select company</option>
                                @foreach ($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endforeach
                            </select>
                            <div id="person_org_id_error" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                        <div class="mb-3">
                            <label for="person_email" class="form-label">Email </label>
                            <input type="text" placeholder="Enter email address" class="form-control" id="person_email" name="person_email">
                            <div id="person_email_error" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                        <div class="mb-3">
                            <label for="person_phone" class="form-label">Mobile <span
                                    class="text-danger">*</span></label>
                            <input type="text" placeholder="+91 9457834901" class="form-control" id="person_mobile" name="person_phone">
                            <div id="person_phone_error" class="text-danger small mt-1" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-custom">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Bootstrap JS Bundle (for modal functionality) -->
    <script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
    <!-- <script src="{{asset('js/leads/create-leads.js')}}"></script> -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
     document.addEventListener('DOMContentLoaded', function() {
         const CSRF_TOKEN = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '';
            // Autocomplete organization list
            const orgInput = document.getElementById('organization_id');
            const orgDropdown = document.getElementById('org-autocomplete-list');

            let orgListData = [];

            function renderOrgList(filter = '') {
                const scrollDiv = document.getElementById('org-autocomplete-scroll');
                const newDiv = document.getElementById('org-autocomplete-new');
                scrollDiv.innerHTML = '';
                (orgListData.filter(org => org.name.toLowerCase().includes(filter.toLowerCase()))).forEach(org => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'dropdown-item';
                    item.textContent = org.name;
                    item.onclick = function() {
                        orgInput.value = org.name;
                        orgDropdown.style.display = 'none';

                        // Fetch organization details (owner and primary contact) and autofill owner/contact fields
                        fetch('/organizations/details?name=' + encodeURIComponent(org.name))
                            .then(res => res.json())
                                .then(data => {
                                    // Helper to render a small selectable list below an input
                                    function renderRelatedList(containerId, items, defaultText, onSelect) {
                                        // remove existing container if present
                                        let existing = document.getElementById(containerId);
                                        if (existing) existing.remove();
                                        if (!items || items.length === 0) return;
                                        const container = document.createElement('div');
                                        container.id = containerId;
                                        container.style.marginTop = '6px';
                                        container.style.padding = '6px';
                                        container.style.border = '1px solid #e9ecef';
                                        container.style.background = '#fff';
                                        container.style.borderRadius = '4px';
                                        container.style.boxShadow = '0 1px 2px rgba(0,0,0,0.04)';
                                        const list = document.createElement('div');
                                        list.style.maxHeight = '160px';
                                        list.style.overflowY = 'auto';
                                        items.forEach((it, idx) => {
                                            const btn = document.createElement('button');
                                            btn.type = 'button';
                                            btn.className = 'btn btn-sm btn-light w-100 text-start mb-1';
                                            btn.style.whiteSpace = 'nowrap';
                                            btn.style.overflow = 'hidden';
                                            btn.style.textOverflow = 'ellipsis';
                                            btn.textContent = it.name || it.full_name || it;
                                            btn.onclick = function(e) {
                                                e.preventDefault();
                                                onSelect(it);
                                                container.remove();
                                            };
                                            list.appendChild(btn);
                                        });
                                        container.appendChild(list);
                                        // Insert after the input element
                                        const anchor = defaultText instanceof Element ? defaultText : document.getElementById(defaultText);
                                        if (anchor && anchor.parentNode) {
                                            anchor.parentNode.insertBefore(container, anchor.nextSibling);
                                        }
                                    }

                                    // Customers (company owners) list
                                    if (Array.isArray(data.customers) && data.customers.length > 0) {
                                        // Default: first customer
                                        const firstCust = data.customers[0];
                                        const custInputEl = document.getElementById('customer_id');
                                        if (custInputEl) custInputEl.value = firstCust.name;

                                        // renderRelatedList('org-related-customers', data.customers, 'customer_id', function(sel) {
                                        //     const custInputEl2 = document.getElementById('customer_id');
                                        //     if (custInputEl2) custInputEl2.value = sel.name;
                                        // });
                                    } else {
                                        const custInputEl = document.getElementById('customer_id');
                                        if (custInputEl) custInputEl.value = '';
                                        const existingCustList = document.getElementById('org-related-customers');
                                        if (existingCustList) existingCustList.remove();
                                    }

                                    // People list (contact persons)
                                    if (Array.isArray(data.people) && data.people.length > 0) {
                                        const firstPerson = data.people[0];
                                        const personInputEl = document.getElementById('people_id');
                                        if (personInputEl) personInputEl.value = firstPerson.full_name;

                                        // transform people array to objects with full_name property for rendering
                                        const peopleForRender = data.people.map(p => ({ full_name: p.full_name, id: p.id }));
                                        // renderRelatedList('org-related-people', peopleForRender, 'people_id', function(sel) {
                                        //     const personInputEl2 = document.getElementById('people_id');
                                        //     if (personInputEl2) personInputEl2.value = sel.full_name;
                                        // });
                                    } else {
                                        const personInputEl = document.getElementById('people_id');
                                        if (personInputEl) personInputEl.value = '';
                                        const existingPersonList = document.getElementById('org-related-people');
                                        if (existingPersonList) existingPersonList.remove();
                                    }

                                    // Owner (user_owner_id) selection if provided
                                    if (data.owner) {
                                        const ownerSelect = document.getElementById('user_owner_id');
                                        if (ownerSelect && data.owner.id) {
                                            try {
                                                ownerSelect.value = data.owner.id;
                                                if (typeof $ !== 'undefined') {
                                                    $(ownerSelect).val(data.owner.id).trigger('change');
                                                }
                                            } catch (e) {}
                                        }
                                    }
                                })
                            .catch(() => {
                                // On error, clear dependent fields
                                document.getElementById('customer_id').value = '';
                                document.getElementById('people_id').value = '';
                            });
                    };
                    scrollDiv.appendChild(item);
                });
                // Add "New Organization" option at the end, always visible and sticky
                newDiv.innerHTML = '';
                const newBtn = document.createElement('a');
                newBtn.href = "#";
                newBtn.className = 'dropdown-item text-primary';
                newBtn.textContent = 'New Company';
                newBtn.onclick = function(e) {
                    e.preventDefault();
                    orgDropdown.style.display = 'none';
                    setTimeout(function() {
                        var modal = new bootstrap.Modal(document.getElementById('orgNewModal'));
                        modal.show();
                    }, 100);
                };
                newDiv.appendChild(newBtn);
            }

            orgInput.addEventListener('focus', function() {
                fetch('/organizations/autocomplete?q=')
                    .then(res => res.json())
                    .then(data => {
                        orgListData = data;
                        renderOrgList();
                        orgDropdown.style.display = 'block';
                        document.getElementById('org-autocomplete-search').value = '';
                    });
            });

            document.getElementById('org-autocomplete-search').addEventListener('input', function(e) {
                renderOrgList(e.target.value);
            });



            // Click outside to close organization dropdown
            document.addEventListener('mousedown', function(e) {
                if (orgDropdown.style.display === 'block') {
                    if (!orgDropdown.contains(e.target) && e.target !== orgInput) {
                        orgDropdown.style.display = 'none';
                    }
                }
            });

            // New organization modal submit
            document.getElementById('orgNewForm').addEventListener('submit', function(e) {

                e.preventDefault();
                // Hide all error messages first
                ['org_name_error', 'org_address_error', 'org_phone_error', 'org_website_error'].forEach(
                    function(id) {
                        document.getElementById(id).style.display = 'none';
                        document.getElementById(id).textContent = '';
                    });
                const formData = new FormData(this);
                // ensure token is present in FormData for fallback and set headers/credentials
                if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
                fetch('/organizations/ajax-create', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: formData
                    })
                    .then(async res => {
                        if (res.status === 422) {
                            const data = await res.json();
                            if (data.errors) {
                                Object.keys(data.errors).forEach(function(key) {
                                    const errorDiv = document.getElementById(key +
                                    '_error');
                                    if (errorDiv) {
                                        errorDiv.textContent = data.errors[key][0];
                                        errorDiv.style.display = 'block';
                                    }
                                });
                            }
                            throw new Error('Validation error');
                        }
                        return res.json();
                    })
                    .then(org => {
                        orgInput.value = org.name;
                        var modal = bootstrap.Modal.getInstance(document.getElementById('orgNewModal'));
                        modal.hide();
                    })
                    .catch(() => {});
            });

            // Autocomplete customer list
            const custInput = document.getElementById('customer_id');
            const custDropdown = document.getElementById('customer-autocomplete-list');

            custInput.addEventListener('focus', function() {
                // Get selected organization name from orgInput
                const orgName = orgInput.value && orgInput.value.trim();
                // If no organization is selected, do not list customers
                if (!orgName) {
                    const scrollDiv = document.getElementById('customer-autocomplete-scroll');
                    const newDiv = document.getElementById('customer-autocomplete-new');
                    scrollDiv.innerHTML = '<div class="p-2 text-muted">Please select a Company first to list its owners.</div>';
                    newDiv.innerHTML = '';
                    custDropdown.style.display = 'block';
                    document.getElementById('customer-autocomplete-search').value = '';
                    return;
                }
                let url = '/customers/autocomplete?q=' + '';
                if (orgName) {
                    url += '&organization=' + encodeURIComponent(orgName);
                }
                let customerListData = [];

                function renderCustomerList(filter = '') {
                    const scrollDiv = document.getElementById('customer-autocomplete-scroll');
                    const newDiv = document.getElementById('customer-autocomplete-new');
                    scrollDiv.innerHTML = '';
                    (customerListData.filter(cust => (cust.name || cust).toLowerCase().includes(filter
                        .toLowerCase()))).forEach(cust => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'dropdown-item';
                        item.textContent = cust.name || cust;
                        item.onclick = function() {
                            custInput.value = cust.name || cust;
                            custDropdown.style.display = 'none';
                        };
                        scrollDiv.appendChild(item);
                    });
                    // Add "New Customer" option at the end, always visible and sticky
                    newDiv.innerHTML = '';
                    const newBtn = document.createElement('a');
                    newBtn.href = "#";
                    newBtn.className = 'dropdown-item text-primary';
                    newBtn.textContent = 'New Company Owner';
                    newBtn.onclick = function(e) {
                        e.preventDefault();
                        custDropdown.style.display = 'none';
                        setTimeout(function() {
                            var modal = new bootstrap.Modal(document.getElementById(
                                'customerNewModal'));
                            modal.show();
                        }, 100);
                    };
                    newDiv.appendChild(newBtn);
                }
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        customerListData = data;
                        renderCustomerList();
                        custDropdown.style.display = 'block';
                        document.getElementById('customer-autocomplete-search').value = '';
                    });
                document.getElementById('customer-autocomplete-search').oninput = function(e) {
                    renderCustomerList(e.target.value);
                };
            });

            // Autocomplete contact person list
            const personInput = document.getElementById('people_id');
            const personDropdown = document.getElementById('person-autocomplete-list');

            personInput.addEventListener('focus', function() {
                // Get selected organization name from orgInput
                const orgName = orgInput.value && orgInput.value.trim();
                // If no organization is selected, do not list contact persons
                if (!orgName) {
                    const scrollDiv = document.getElementById('person-autocomplete-scroll');
                    const newDiv = document.getElementById('person-autocomplete-new');
                    scrollDiv.innerHTML = '<div class="p-2 text-muted">Please select a Company first to list its contacts.</div>';
                    newDiv.innerHTML = '';
                    personDropdown.style.display = 'block';
                    document.getElementById('person-autocomplete-search').value = '';
                    return;
                }
                let url = '/people/autocomplete?q=' + '';
                if (orgName) {
                    url += '&organization=' + encodeURIComponent(orgName);
                }
                let personListData = [];

                function renderPersonList(filter = '') {
                    const scrollDiv = document.getElementById('person-autocomplete-scroll');
                    const newDiv = document.getElementById('person-autocomplete-new');
                    scrollDiv.innerHTML = '';
                    (personListData.filter(person => ((person.first_name ? person.first_name : '') + (person
                        .last_name ? ' ' + person.last_name : '')).toLowerCase().includes(filter
                        .toLowerCase()))).forEach(person => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'dropdown-item';
                        item.textContent = (person.first_name ? person.first_name : '') + (person
                            .last_name ? ' ' + person.last_name : '');
                        item.onclick = function() {
                            personInput.value = (person.first_name ? person.first_name : '') + (
                                person.last_name ? ' ' + person.last_name : '');
                            personDropdown.style.display = 'none';
                        };
                        scrollDiv.appendChild(item);
                    });
                    // Add "New Contact Person" option at the end, always visible and sticky
                    newDiv.innerHTML = '';
                    const newBtn = document.createElement('a');
                    newBtn.href = "#";
                    newBtn.className = 'dropdown-item text-primary';
                    newBtn.textContent = 'New Contact Person';
                    newBtn.onclick = function(e) {
                        e.preventDefault();
                        personDropdown.style.display = 'none';
                        setTimeout(function() {
                            var modal = new bootstrap.Modal(document.getElementById(
                                'personNewModal'));
                            modal.show();
                        }, 100);
                    };
                    newDiv.appendChild(newBtn);
                }
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        personListData = data;
                        renderPersonList();
                        personDropdown.style.display = 'block';
                        document.getElementById('person-autocomplete-search').value = '';
                    });
                document.getElementById('person-autocomplete-search').oninput = function(e) {
                    renderPersonList(e.target.value);
                };
            });

            // Click outside to close contact person dropdown
            document.addEventListener('mousedown', function(e) {
                if (personDropdown.style.display === 'block') {
                    if (!personDropdown.contains(e.target) && e.target !== personInput) {
                        personDropdown.style.display = 'none';
                    }
                }
            });

            // Click outside to close customer dropdown
            document.addEventListener('mousedown', function(e) {
                if (custDropdown.style.display === 'block') {
                    if (!custDropdown.contains(e.target) && e.target !== custInput) {
                        custDropdown.style.display = 'none';
                    }
                }
            });

            // Fill organization in customer modal when opened, make readonly if selected
            document.getElementById('customerNewModal').addEventListener('show.bs.modal', function() {
                const orgName = document.getElementById('organization_id').value;
                const orgSelect = document.getElementById('cust_org_id');
                if (!orgSelect) return;
                let foundIndex = -1;
                for (let i = 0; i < orgSelect.options.length; i++) {
                    if (orgSelect.options[i].text === orgName) {
                        foundIndex = i;
                        break;
                    }
                }
                const readonlyId = 'cust_org_readonly';
                // If organization is already selected, hide the dropdown and show a readonly input instead
                if (foundIndex !== -1 && orgName !== '') {
                    // ensure select has the correct value selected
                    orgSelect.selectedIndex = foundIndex;
                    // visually hide the select but keep it enabled so value submits
                    orgSelect.style.display = 'none';
                    // insert readonly input if not present
                    if (!document.getElementById(readonlyId)) {
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'form-control';
                        input.id = readonlyId;
                        input.value = orgName;
                        input.readOnly = true;
                        orgSelect.parentNode.insertBefore(input, orgSelect);
                    } else {
                        const existing = document.getElementById(readonlyId);
                        existing.value = orgName;
                        existing.style.display = '';
                    }
                    // ensure hidden input for org id exists so AJAX/FormData can reliably include org id
                    const orgId = orgSelect.options[foundIndex] ? orgSelect.options[foundIndex].value : '';
                    if (orgId) {
                        let hid = document.getElementById('cust_org_id_hidden');
                        if (!hid) {
                            hid = document.createElement('input');
                            hid.type = 'hidden';
                            hid.id = 'cust_org_id_hidden';
                            hid.name = 'cust_org_id_hidden';
                            orgSelect.parentNode.insertBefore(hid, orgSelect);
                        }
                        hid.value = orgId;
                    }
                } else {
                    // show select and remove readonly input if exists
                    orgSelect.style.display = '';
                    const existing = document.getElementById(readonlyId);
                    if (existing) existing.remove();
                    const hid = document.getElementById('cust_org_id_hidden');
                    if (hid) hid.remove();
                }
            });
            // New customer modal submit with duplicate check
            document.getElementById('customerNewForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formEl = this;
                // Hide all error messages first
                ['cust_name_error', 'cust_org_id_error'].forEach(function(id) {
                    const el = document.getElementById(id);
                    if (el) { el.style.display = 'none'; el.textContent = ''; }
                });

                const custName = (document.getElementById('cust_name').value || '').trim();
                const orgSelect = document.getElementById('cust_org_id');
                const orgName = orgSelect && orgSelect.options[orgSelect.selectedIndex] ? orgSelect.options[orgSelect.selectedIndex].text : '';

                // If customer name and organization provided, pre-check for existing owner in same org
                    if (custName && orgSelect && orgSelect.value) {
                    const checkUrl = '/customers/autocomplete?q=' + encodeURIComponent(custName) + '&organization=' + encodeURIComponent(orgName);
                    fetch(checkUrl)
                        .then(res => res.json())
                        .then(matches => {
                            const duplicate = (Array.isArray(matches) ? matches : []).find(item => ((item.name || item).toLowerCase() === custName.toLowerCase()));
                            if (duplicate) {
                                // Show validation error under the customer name field instead of prompting
                                const errorDiv = document.getElementById('cust_name_error');
                                if (errorDiv) {
                                    errorDiv.textContent = 'A company owner named "' + custName + '" already exists for "' + orgName + '".';
                                    errorDiv.style.display = 'block';
                                } else {
                                    alert('A company owner named "' + custName + '" already exists for "' + orgName + '".');
                                }
                                // focus the name input
                                const nameInput = document.getElementById('cust_name');
                                if (nameInput) nameInput.focus();
                                return;
                            }

                            // No duplicate or user chose to create new -> proceed with AJAX create
                            const formData = new FormData(formEl);
                            // If we inserted a hidden org-id input, ensure the org id is included as 'cust_org_id'
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
                                            Object.keys(data.errors).forEach(function(key) {
                                                const errorDiv = document.getElementById(key + '_error');
                                                if (errorDiv) {
                                                    errorDiv.textContent = data.errors[key][0];
                                                    errorDiv.style.display = 'block';
                                                }
                                            });
                                        }
                                        throw new Error('Validation error');
                                    }
                                    return res.json();
                                })
                                .then(cust => {
                                    // server may return { duplicate: true, id, name } or the created customer
                                        if (cust && cust.duplicate) {
                                            // show inline validation error and keep modal open
                                            const errorDiv = document.getElementById('cust_name_error');
                                            if (errorDiv) {
                                                errorDiv.textContent = 'A company owner named "' + (cust.name || '') + '" already exists for the selected organization.';
                                                errorDiv.style.display = 'block';
                                            }
                                            const nameInput = document.getElementById('cust_name');
                                            if (nameInput) nameInput.focus();
                                            return;
                                        }
                                        custInput.value = cust.name || '';
                                        try { var modal = bootstrap.Modal.getInstance(document.getElementById('customerNewModal')); if (modal) modal.hide(); } catch (e) {}
                                })
                                .catch(() => {});
                        })
                        .catch(() => {
                            // On error checking duplicates, fall back to submitting normally
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
                                            Object.keys(data.errors).forEach(function(key) {
                                                const errorDiv = document.getElementById(key + '_error');
                                                if (errorDiv) {
                                                    errorDiv.textContent = data.errors[key][0];
                                                    errorDiv.style.display = 'block';
                                                }
                                            });
                                        }
                                        throw new Error('Validation error');
                                    }
                                    return res.json();
                                })
                                .then(cust => {
                                    if (cust && cust.duplicate) {
                                        const errorDiv = document.getElementById('cust_name_error');
                                        if (errorDiv) {
                                            errorDiv.textContent = 'A company owner named "' + (cust.name || '') + '" already exists for the selected organization.';
                                            errorDiv.style.display = 'block';
                                        }
                                        const nameInput = document.getElementById('cust_name');
                                        if (nameInput) nameInput.focus();
                                        return;
                                    }
                                    custInput.value = cust.name || '';
                                    try { var modal = bootstrap.Modal.getInstance(document.getElementById('customerNewModal')); if (modal) modal.hide(); } catch (e) {}
                                })
                                .catch(() => {});
                        });
                    return;
                }

                // fallback: submit normally if name/org not provided
                const formData = new FormData(this);
                const custHidden = document.getElementById('cust_org_id_hidden');
                if (custHidden) formData.set('cust_org_id', custHidden.value);
                fetch('/customers/ajax-create', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                    .then(async res => {
                        if (res.status === 422) {
                            const data = await res.json();
                            if (data.errors) {
                                Object.keys(data.errors).forEach(function(key) {
                                    const errorDiv = document.getElementById(key +
                                    '_error');
                                    if (errorDiv) {
                                        errorDiv.textContent = data.errors[key][0];
                                        errorDiv.style.display = 'block';
                                    }
                                });
                            }
                            throw new Error('Validation error');
                        }
                        return res.json();
                    })
                    .then(cust => {
                        custInput.value = cust.name
                        var modal = bootstrap.Modal.getInstance(document.getElementById(
                            'customerNewModal'));
                        modal.hide();
                    })
                    .catch(() => {});
            });

            // Fill organization in contact person modal when opened, make readonly if selected
            document.getElementById('personNewModal').addEventListener('show.bs.modal', function() {
                const orgName = document.getElementById('organization_id').value;
                const orgSelect = document.getElementById('person_org_id');
                if (!orgSelect) return;
                let foundIndex = -1;
                for (let i = 0; i < orgSelect.options.length; i++) {
                    if (orgSelect.options[i].text === orgName) {
                        foundIndex = i;
                        break;
                    }
                }
                const readonlyId = 'person_org_readonly';
                // If organization is already selected, hide the dropdown and show a readonly input instead
                if (foundIndex !== -1 && orgName !== '') {
                    orgSelect.selectedIndex = foundIndex;
                    orgSelect.style.display = 'none';
                    if (!document.getElementById(readonlyId)) {
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'form-control';
                        input.id = readonlyId;
                        input.value = orgName;
                        input.readOnly = true;
                        orgSelect.parentNode.insertBefore(input, orgSelect);
                    } else {
                        const existing = document.getElementById(readonlyId);
                        existing.value = orgName;
                        existing.style.display = '';
                    }
                    // ensure hidden input for org id exists so AJAX/FormData can reliably include org id
                    const orgId = orgSelect.options[foundIndex] ? orgSelect.options[foundIndex].value : '';
                    if (orgId) {
                        let hid = document.getElementById('person_org_id_hidden');
                        if (!hid) {
                            hid = document.createElement('input');
                            hid.type = 'hidden';
                            hid.id = 'person_org_id_hidden';
                            hid.name = 'person_org_id_hidden';
                            orgSelect.parentNode.insertBefore(hid, orgSelect);
                        }
                        hid.value = orgId;
                    }
                } else {
                    orgSelect.style.display = '';
                    const existing = document.getElementById(readonlyId);
                    if (existing) existing.remove();
                    const hid = document.getElementById('person_org_id_hidden');
                    if (hid) hid.remove();
                }
            });
            // New contact person modal submit with single word validation
            document.getElementById('personNewForm').addEventListener('submit', function(e) {
                e.preventDefault();
                // Hide all error messages first
                ['person_first_name_error', 'person_last_name_error', 'person_org_id_error',
                    'person_email_error', 'person_phone_error'
                ].forEach(function(id) {
                    document.getElementById(id).style.display = 'none';
                    document.getElementById(id).textContent = '';
                });
                const firstName = document.getElementById('person_first_name').value.trim();
                const lastName = document.getElementById('person_last_name').value.trim();
                const singleWordRegex = /^\w+$/;
                let errorMsg = '';
                if (firstName && !singleWordRegex.test(firstName)) {
                    errorMsg += 'First name must be a single word.\n';
                }
                // if (lastName && !singleWordRegex.test(lastName)) {
                //     errorMsg += 'Last name must be a single word.';
                // }
                if (errorMsg) {
                    document.getElementById('person_first_name_error').textContent = errorMsg;
                    document.getElementById('person_first_name_error').style.display = 'block';
                    return;
                }
                const formEl = this;
                const orgSelect = document.getElementById('person_org_id');
                const orgName = orgSelect && orgSelect.options[orgSelect.selectedIndex] ? orgSelect.options[orgSelect.selectedIndex].text : '';

                // Pre-check duplicates via autocomplete (by full name + organization)
                if (firstName) {
                    const q = firstName + (lastName ? ' ' + lastName : '');
                    const checkUrl = '/people/autocomplete?q=' + encodeURIComponent(q) + (orgName ? '&organization=' + encodeURIComponent(orgName) : '');
                    fetch(checkUrl)
                        .then(res => res.json())
                        .then(matches => {
                            const duplicate = (Array.isArray(matches) ? matches : []).find(p => (((p.first_name || '') + ' ' + (p.last_name || '')).trim().toLowerCase() === q.trim().toLowerCase()));
                            if (duplicate) {
                                const firstErr = document.getElementById('person_first_name_error');
                                const lastErr = document.getElementById('person_last_name_error');
                                const msg = 'A contact person named "' + q + '" already exists for "' + (orgName || 'the selected organization') + '".';
                                if (firstErr) { firstErr.textContent = msg; firstErr.style.display = 'block'; }
                                if (lastErr) { lastErr.textContent = msg; lastErr.style.display = 'block'; }
                                const firstInput = document.getElementById('person_first_name'); if (firstInput) firstInput.focus();
                                return;
                            }

                            // No duplicate -> submit
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
                                            Object.keys(data.errors).forEach(function(key) {
                                                const errorDiv = document.getElementById(key + '_error');
                                                if (errorDiv) {
                                                    errorDiv.textContent = data.errors[key][0];
                                                    errorDiv.style.display = 'block';
                                                }
                                            });
                                        }
                                        throw new Error('Validation error');
                                    }
                                    return res.json();
                                })
                                .then(person => {
                                    // server may return duplicate info
                                    if (person && person.duplicate) {
                                        const firstErr = document.getElementById('person_first_name_error');
                                        const lastErr = document.getElementById('person_last_name_error');
                                        const msg = 'A contact person named "' + ((person.first_name || '') + (person.last_name ? ' ' + person.last_name : '')).trim() + '" already exists for the selected organization.';
                                        if (firstErr) { firstErr.textContent = msg; firstErr.style.display = 'block'; }
                                        if (lastErr) { lastErr.textContent = msg; lastErr.style.display = 'block'; }
                                        const firstInput = document.getElementById('person_first_name'); if (firstInput) firstInput.focus();
                                        return;
                                    }
                                    personInput.value = (person.first_name ? person.first_name : '') + (person.last_name ? ' ' + person.last_name : '');
                                    var modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                                    modal.hide();
                                })
                                .catch(() => {});
                        })
                        .catch(() => {
                            // On error checking duplicates, fall back to submitting normally
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
                                            Object.keys(data.errors).forEach(function(key) {
                                                const errorDiv = document.getElementById(key + '_error');
                                                if (errorDiv) {
                                                    errorDiv.textContent = data.errors[key][0];
                                                    errorDiv.style.display = 'block';
                                                }
                                            });
                                        }
                                        throw new Error('Validation error');
                                    }
                                    return res.json();
                                })
                                .then(person => {
                                    if (person && person.duplicate) {
                                        const firstErr = document.getElementById('person_first_name_error');
                                        const lastErr = document.getElementById('person_last_name_error');
                                        const msg = 'A contact person named "' + ((person.first_name || '') + (person.last_name ? ' ' + person.last_name : '')).trim() + '" already exists for the selected organization.';
                                        if (firstErr) { firstErr.textContent = msg; firstErr.style.display = 'block'; }
                                        if (lastErr) { lastErr.textContent = msg; lastErr.style.display = 'block'; }
                                        const firstInput = document.getElementById('person_first_name'); if (firstInput) firstInput.focus();
                                        return;
                                    }
                                    personInput.value = (person.first_name ? person.first_name : '') + (person.last_name ? ' ' + person.last_name : '');
                                    var modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                                    modal.hide();
                                })
                                .catch(() => {});
                        });
                    return;
                }

                // fallback: submit normally if firstName not present
                const formData = new FormData(this);
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
                                Object.keys(data.errors).forEach(function(key) {
                                    const errorDiv = document.getElementById(key + '_error');
                                    if (errorDiv) {
                                        errorDiv.textContent = data.errors[key][0];
                                        errorDiv.style.display = 'block';
                                    }
                                });
                            }
                            throw new Error('Validation error');
                        }
                        return res.json();
                    })
                    .then(person => {
                        personInput.value = (person.first_name ? person.first_name : '') + (person.last_name ? ' ' + person.last_name : '');
                        var modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                        modal.hide();
                    })
                    .catch(() => {});
            });

            // --- Fetch and update organization list for modals ---
            function loadLatestOrganizations(selectId) {
                fetch('/organizations/autocomplete?q=')
                    .then(res => res.json())
                    .then(data => {
                        const select = document.getElementById(selectId);
                        if (select) {
                            select.innerHTML = '<option value="">Select organization</option>';
                            data.forEach(item => {
                                // Use ID as value, name as text
                                const id = item.id || item.value || item;
                                const name = item.name || item.label || item;
                                const option = document.createElement('option');
                                option.value = id;
                                option.textContent = name;
                                select.appendChild(option);
                            });
                        }
                    });
            }

            // Before showing New Customer modal
            const customerNewModal = document.getElementById('customerNewModal');
            if (customerNewModal) {
                customerNewModal.addEventListener('show.bs.modal', function() {
                    loadLatestOrganizations('cust_org_id');
                });
            }

            // Before showing New Contact Person modal
            const personNewModal = document.getElementById('personNewModal');
            if (personNewModal) {
                personNewModal.addEventListener('show.bs.modal', function() {
                    loadLatestOrganizations('person_org_id');
                });
            }

            $('#user_owner_id').select2({
                placeholder: 'Select Owner',
                allowClear: true,
                width: '100%'
            });
            $('#lead_source').select2({
                placeholder: 'Select Lead Source',
                allowClear: true,
                width: '100%'
            });
            $('#status').select2({
                placeholder: 'Select Status',
                allowClear: true,
                width: '100%'
            });
            $('#label').select2({
                placeholder: 'Select Priority',
                allowClear: true,
                width: '100%'
            });
            $(document).ready(function() {
        $('#category').select2({
            placeholder: 'Select Product/Services',
            allowClear: true,
            width: '100%'
        });
    });
        });
</script>

@endsection
