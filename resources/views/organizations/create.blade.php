@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/organization-custom.css') }}">
<div class="container-fluid p-4">
    <form method="POST" action="{{ route('organizations.store') }}" id="orgCreateForm" novalidate>
        @csrf
        <div class="card mt-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Create Company</h3>
                <a href="{{ route('organizations.index') }}" class="btn btn-light btn-sm">&laquo; Back to Company List</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ old('name') }}">
                            @error('name')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="industry_type" class="form-label">Industry<span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="industry_type" name="industry_type">
                                    <option value="">Select industry</option>
                                    @foreach ($industries as $industry)
                                    <option value="{{ $industry->id }}"
                                        @if ($industry->id == old('industry_type')) selected @endif>{{ $industry->name }}
                                    </option>
                                    @endforeach
                                </select>

                                @error('industry_type')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="organization_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="organization_type" name="organization_type">
                                    <option value="">Select type</option>
                                    @foreach ($organizationTypes as $typeId => $typeName)
                                    <option value="{{ $typeId }}" @if ($typeId==old('organization_type')) selected @endif>{{ $typeName }}</option>
                                    @endforeach
                                </select>
                                @error('organization_type')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>

                        <div class="mb-3">
                            <label for="website" class="form-label">Website URL</label>
                            <input type="text" placeholder="https://example.com" class="form-control" id="website"
                                name="website" value="{{ old('website') }}">
                            @error('website')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="number_of_employees" class="form-label">Number of employees</label>
                                <input type="number" placeholder="1" class="form-control" id="number_of_employees"
                                    name="number_of_employees" value="{{ old('number_of_employees') }}">
                                @error('number_of_employees')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="user_owner_id" class="form-label">Owner <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="user_owner_id" name="user_owner_id">
                                    <option value="">Select owner</option>
                                    @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}"
                                        @if ($owner->id == old('user_owner_id', $currentUserId)) selected @endif>{{ $owner->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('user_owner_id')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address" name="address"
                                value="{{ old('address') }}">
                            @error('address')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" placeholder="Enter city" class="form-control" id="city"
                                    name="city" value="{{ old('city') }}">
                                @error('city')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="state" class="form-label">State</label>
                                <select class="form-select" id="state" name="state">
                                    <option value="">Select state</option>
                                </select>
                                @error('state')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="pincode" class="form-label">Pincode</label>
                                <input type="text" placeholder="Enter pincode" class="form-control"
                                    id="pincode" name="pincode" value="{{ old('pincode') }}">
                                @error('pincode')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="">Select country</option>
                                    @foreach ($countries as $country)
                                    <option value="{{ $country }}"
                                        @if ($country==old('country', 'India' )) selected @endif>{{ $country }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('country')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" placeholder="+91 98765 43210" class="form-control"
                                    id="phone" name="phone" value="{{ old('phone') }}"
                                    pattern="^\+?[0-9\-\s]{7,20}$" maxlength="20">
                                @error('phone')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" placeholder="example@example.com" class="form-control"
                                    id="email" name="email" value="{{ old('email') }}">
                                @error('email')
                                <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-start">
                <button type="submit" class="btn btn-custom">Submit</button>
            </div>
        </div>
    </form>

</div>
@endsection
@push('scripts')
<script>
    (function(){
        var statesByCountry = {
            'India': ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Delhi'],
            'United States': ['Alabama','Alaska','Arizona','Arkansas','California','Colorado','Connecticut','Delaware','Florida','Georgia','Hawaii','Idaho','Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota','Mississippi','Missouri','Montana','Nebraska','Nevada','New Hampshire','New Jersey','New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee','Texas','Utah','Vermont','Virginia','Washington','West Virginia','Wisconsin','Wyoming'],
            'Canada': ['Alberta','British Columbia','Manitoba','New Brunswick','Newfoundland and Labrador','Nova Scotia','Ontario','Prince Edward Island','Quebec','Saskatchewan'],
            'Australia': ['New South Wales','Queensland','South Australia','Tasmania','Victoria','Western Australia','Australian Capital Territory','Northern Territory'],
            'United Kingdom': ['England','Scotland','Wales','Northern Ireland']
        };

        function populateStates(country, selected) {
            var stateSelect = document.getElementById('state');
            if (!stateSelect) return;
            stateSelect.innerHTML = '';
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.text = 'Select state';
            stateSelect.appendChild(placeholder);
            var list = statesByCountry[country] || [];
            if (list.length > 0) {
                list.forEach(function(s){
                    var opt = document.createElement('option');
                    opt.value = s;
                    opt.text = s;
                    if (selected && selected === s) opt.selected = true;
                    stateSelect.appendChild(opt);
                });
                stateSelect.disabled = false;
            } else {
                var prev = selected || '';
                if (prev) {
                    var opt = document.createElement('option');
                    opt.value = prev;
                    opt.text = prev;
                    opt.selected = true;
                    stateSelect.appendChild(opt);
                } else {
                    stateSelect.querySelector('option').text = 'No states available';
                }
                stateSelect.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function(){
            var countryEl = document.getElementById('country');
            var initialCountry = countryEl ? countryEl.value || '{{ old("country", "India") }}' : '{{ old("country", "India") }}';
            var initialState = '{{ old("state") }}';
            populateStates(initialCountry, initialState);
            if (countryEl) {
                countryEl.addEventListener('change', function(){ populateStates(this.value, ''); });
            }
        });
    })();
</script>
<script src="{{asset('js/organization/organization-custom.js')}}"></script>
@endpush
