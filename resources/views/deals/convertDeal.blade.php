@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="card">
        <form method="POST" action="{{ route('deals.storeFromLead', $lead->id) }}">
            @csrf
            <!-- Hidden inputs: disabled fields are not submitted, include hidden copies so server receives values -->
            <input type="hidden" name="title" value="{{ $lead->title }}">
            <input type="hidden" name="organization_id" value="{{ $lead->organization_id }}">
            <input type="hidden" name="people_id" value="{{ $lead->people_id }}">
            <input type="hidden" name="lead_source" value="{{ $lead->lead_source }}">
            <input type="hidden" name="user_owner_id" value="{{ $lead->user_owner_id }}">
            <input type="hidden" name="industry_id" value="{{ old('industry_id', $lead->industry_id) }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Convert Lead to Deal</h4>
                <div class="text-end">
                    <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-light btn-sm">&laquo; Back to Lead</a>

                </div>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-6">
                         <div class="mb-3">
                            <label for="organization_id" class="form-label">Company Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-buildings"></i></span>
                                <input type="text" class="form-control" id="organization_id" name="organization_id" placeholder="Search or add organization" autocomplete="off" value="{{ $lead->organization->name ?? '' }}" readonly disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Company Owner Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                <input type="text" class="form-control" id="customer_id" name="customer_id" placeholder="Search or add customer" autocomplete="off" value="{{ $lead->customer->name ?? '' }}" readonly disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="people_id" class="form-label">Company Contact Person <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="people_id" name="people_id" placeholder="Search or add contact person" autocomplete="off" value="{{ optional($lead->person) ? $lead->person->first_name . ' ' . $lead->person->last_name : '-' }}" readonly disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="{{ $lead->title }}" readonly disabled>
                            @error('title')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" readonly disabled>{{ $lead->description }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="label" value="{{ $lead->label }}" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lead Source <span class="text-danger">*</span></label>
                            <select class="form-select" name="lead_source" disabled>
                                @foreach($leadSources as $source)
                                    <option value="{{ $source->id }}" @if($lead->lead_source == $source->id) selected @endif>{{ $source->name }}</option>
                                @endforeach
                            </select>
                            @error('lead_source')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Owner <span class="text-danger">*</span></label>
                            <select class="form-select" name="user_owner_id" disabled>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @if($lead->user_owner_id == $user->id) selected @endif>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('user_owner_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Amount    <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" value="{{ old('amount', $lead->amount) }}" >
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                         <div class="mb-3">
                            <label class="form-label">Stage <span class="text-danger">*</span></label>
                            <select class="form-select" name="stage" id="stage">
                                @foreach($stages as $stage)
                                    <option value="{{ $stage->id }}" data-name="{{ $stage->name }}" data-probability="{{ $stage->probability ?? 10 }}" @if((string) old('stage') === (string) $stage->id || (!old('stage') && strtolower((string) ($lead->stage ?? '')) === strtolower($stage->name))) selected @endif>{{ $stage->name }} ({{ $stage->probability ?? 'N/A' }}%)</option>
                                @endforeach
                            </select>
                            @error('stage')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3" id="reason_for_loss_container" style="display: {{ (string) old('stage') === (string) \App\Models\Stage::closedLostId() ? 'block' : 'none' }};">
                            <label for="reason_for_loss" class="form-label">Reason for Loss <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reason_for_loss" name="reason_for_loss" rows="2" placeholder="Please specify the reason">{{ old('reason_for_loss') }}</textarea>
                            @error('reason_for_loss')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="probability" class="form-label">Probability (%)</label>
                            <input type="number" class="form-control" id="probability" name="probability" min="0" max="100" value="{{ old('probability', 10) }}">
                            @error('probability')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                         <div class="mb-3">
                            <label for="industry_id_display" class="form-label">Industries</label>
                            <select class="form-select" id="industry_id_display" disabled>
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
                            <label for="categories" class="form-label">Categories <span class="text-danger">*</span></label>
                            @if($categories->isNotEmpty())
                                @php
                                $selectedCategories = old('categories', $lead->category);
                                if (is_null($selectedCategories)) {
                                    $selectedCategories = [];
                                } elseif (is_string($selectedCategories)) {
                                    $selectedCategories = explode(',', $selectedCategories);
                                }
                            @endphp
                            <select class="form-control @error('categories') is-invalid @enderror" id="categories" name="categories[]" multiple disabled>
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
                            <label class="form-label">Closing Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="close_date" value="{{ old('close_date') }}">
                            @error('close_date')
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
                <button type="submit" class="btn btn-custom">Convert</button>
            </div>
        </form>
    </div>
</div>
<script>
    // Toggle reason_for_loss visibility and required attribute on convert page
    (function(){
        var stageSelect = document.getElementById('stage');
        var reasonContainer = document.getElementById('reason_for_loss_container');
        var reasonEl = document.getElementById('reason_for_loss');
        var probabilityEl = document.getElementById('probability');

        function handleStageChange() {
            var selected = stageSelect && stageSelect.selectedIndex >= 0 ? stageSelect.options[stageSelect.selectedIndex] : null;
            var val = selected ? (selected.getAttribute('data-name') || '') : '';
            if (val && val.toLowerCase() === 'closed lost') {
                if (reasonContainer) reasonContainer.style.display = 'block';
                // if (reasonEl) reasonEl.setAttribute('required', 'required');
            } else {
                if (reasonContainer) reasonContainer.style.display = 'none';
                if (reasonEl) { reasonEl.removeAttribute('required'); reasonEl.value = ''; }
            }
            // Update probability from selected option's data-probability
            if (stageSelect && probabilityEl) {
                var sel = stageSelect.options[stageSelect.selectedIndex];
                var prob = sel ? sel.getAttribute('data-probability') : null;
                if (prob !== null && prob !== '') {
                    probabilityEl.value = prob;
                }
            }
        }
        if (stageSelect) {
            stageSelect.addEventListener('change', handleStageChange);
            // run on load
            handleStageChange();
        }

        // Duplicate check before submitting conversion
        var form = document.querySelector('form[action^="{{ route('deals.storeFromLead', $lead->id) }}"]') || document.querySelector('form');
        if (form) {
            var dupErr = document.createElement('div');
            dupErr.id = 'convert_deal_duplicate_error';
            dupErr.className = 'text-danger small mt-1';
            dupErr.style.display = 'none';
            // Insert after title field
            var titleEl = form.querySelector('input[name="title"]');
            if (titleEl && titleEl.parentNode) {
                titleEl.parentNode.appendChild(dupErr);
            }
            form.addEventListener('submit', function(e){
                e.preventDefault();
                if (dupErr) { dupErr.style.display = 'none'; dupErr.textContent = ''; }
                var title = (titleEl && titleEl.value) ? titleEl.value.trim() : '';
                var org = (document.getElementById('organization_id') && document.getElementById('organization_id').value) ? document.getElementById('organization_id').value.trim() : '';
                var person = (document.getElementById('people_id') && document.getElementById('people_id').value) ? document.getElementById('people_id').value.trim() : '';
                if (!title || !org || !person) {
                    form.submit();
                    return;
                }
                var params = new URLSearchParams();
                params.append('title', title);
                params.append('organization', org);
                params.append('person', person);
                fetch('/deals/check-duplicate?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function(res){ return res.json(); })
                    .then(function(data){
                        if (data && data.duplicate) {
                            if (dupErr) { dupErr.textContent = 'A deal with the same title, company and contact person already exists.'; dupErr.style.display = 'block'; }
                            return;
                        }
                        form.submit();
                    })
                    .catch(function(){ form.submit(); });
            });
        }
    })();

    document.addEventListener('DOMContentLoaded', function(){
        // Initialize select2 for categories
        $('#categories').select2({
            placeholder: 'Select categories',
            allowClear: true,
            width: '100%'
        });
    });
</script>
@endsection
