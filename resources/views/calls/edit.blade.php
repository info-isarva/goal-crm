@extends('layouts.app')

@section('content')
<div class="container-fluid p-4" >
    <h2>Edit Call Log</h2>
    <form action="{{ route('calllogs.update', $call->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row mb-2">
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" value="{{ old('name', $call->name) }}" placeholder="Name" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $call->company_name) }}" placeholder="Company Name">
            </div>
            <div class="col-md-4">
                <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $call->mobile_number) }}" placeholder="Mobile Number" required>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-4">
                <textarea name="requirement" class="form-control" placeholder="Requirement">{{ old('requirement', $call->requirement) }}</textarea>
            </div>
            <div class="col-md-4">
                <select name="call_status" class="form-control" required>
                    <option value="">Select Status</option>
                    <option value="Answered" {{ old('call_status', $call->call_status) == 'Answered' ? 'selected' : '' }}>Answered</option>
                    <option value="Not Answered" {{ old('call_status', $call->call_status) == 'Not Answered' ? 'selected' : '' }}>Not Answered</option>
                    <option value="Busy" {{ old('call_status', $call->call_status) == 'Busy' ? 'selected' : '' }}>Busy</option>
                    <option value="Switch Off" {{ old('call_status', $call->call_status) == 'Switch Off' ? 'selected' : '' }}>Switch Off</option>
                    <option value="Not Exist" {{ old('call_status', $call->call_status) == 'Not Exist' ? 'selected' : '' }}>Not Exist</option>
                    <option value="Not reachable" {{ old('call_status', $call->call_status) == 'Not reachable' ? 'selected' : '' }}>Not reachable</option>
                    <option value="Wrong number" {{ old('call_status', $call->call_status) == 'Wrong number' ? 'selected' : '' }}>Wrong number</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="lead_status" class="form-control">
                    <option value="">Select Lead Status (Optional)</option>
                    <option value="Interested" {{ old('lead_status', $call->lead_status) == 'Interested' ? 'selected' : '' }}>Interested</option>
                    <option value="Not Interested" {{ old('lead_status', $call->lead_status) == 'Not Interested' ? 'selected' : '' }}>Not Interested</option>
                    <option value="Follow Up" {{ old('lead_status', $call->lead_status) == 'Follow Up' ? 'selected' : '' }}>Follow Up</option>
                    <option value="Call Later" {{ old('lead_status', $call->lead_status) == 'Call Later' ? 'selected' : '' }}>Call Later</option>
                    <option value="Share the Details" {{ old('lead_status', $call->lead_status) == 'Share the Details' ? 'selected' : '' }}>Share the Details</option>
                    <option value="Closed" {{ old('lead_status', $call->lead_status) == 'Closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Update Call Log</button>
        <a href="{{ route('calllogs.index') }}" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>
@endsection
