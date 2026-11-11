@extends('layouts.app')

@section('content')
<link href="{{ asset('css/people.css') }}" rel="stylesheet" />
<div class="container-fluid p-4">
    <form method="POST" action="{{ route('people.save') }}">
        @csrf
        <div class="card mt-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Add Person</h3>
                <a href="{{ route('people.index') }}" class="btn btn-light btn-sm">&laquo; Back to Contact Person List</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}">
                                    @error('first_name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}">
                                    @error('last_name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            @error('gender')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob" min="{{ \Carbon\Carbon::now()->subYears(100)->format('Y-m-d') }}" max="{{ \Carbon\Carbon::now()->endOfYear()->subYears(6)->format('Y-m-d'); }}" value="{{ old('dob') }}">
                            @error('dob')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="job_title" class="form-label">Designation</label>
                            <input type="text" class="form-control" id="job_title" name="job_title">
                            @error('job_title')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="lead_source" class="form-label">Lead Source </label>
                            <select class="form-select" id="lead_source" name="lead_source" >
                                <option value="">Select lead source</option>
                                @foreach($leadSources as $source)
                                    <option value="{{ $source->name }}">{{ $source->name }}</option>
                                @endforeach
                            </select>
                            @error('lead_source')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="owner_id" class="form-label">Owner <span class="text-danger">*</span></label>
                            <select class="form-select" id="owner_id" name="owner_id" >
                                <option value="">Select owner</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @if(old('owner_id', $currentUserId) == $owner->id) selected @endif>{{ $owner->name }}</option>
                                @endforeach
                            </select>
                            @error('owner_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label for="email" class="form-label">Email </label>
                            <input type="text" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile" value="{{ old('mobile') }}">
                            @error('mobile')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Land Number</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            @error('address')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            @error('notes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="organization_ids" class="form-label">Companies</label>
                            <select class="form-select" id="organization_ids" name="organization_ids[]" multiple>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" @if(collect(old('organization_ids', []))->contains($org->id)) selected @endif>{{ $org->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Select one or more companies for this contact.</div>
                            @error('organization_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @error('organization_ids.*')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-custom">Add Person</button>
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function() {
    $('#lead_source').select2({
        placeholder: 'Select lead source',
        allowClear: true,
        width: '100%'
    });
    $('#owner_id').select2({
        placeholder: 'Select owner',
        allowClear: true,
        width: '100%'
    });
    $('#organization_ids').select2({
        placeholder: 'Select companies',
        allowClear: true,
        width: '100%'
    });
});
</script>

@endsection
