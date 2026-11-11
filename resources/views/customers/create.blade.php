@extends('layouts.app')

@section('content')
@include('partials.select2')
<link href="{{ asset('css/customer-custom.css') }}" rel="stylesheet" />
<div class="container-fluid p-4">
    <form method="POST" action="{{ route('customers.store') }}">
        @csrf
        <div class="card mt-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Add Company Owner </h3>
                <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm">&laquo; Back to Company Owners</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Company Owner Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}">
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="organization_id" class="form-label">Company <span class="text-danger">*</span></label>
                            <select class="form-select" id="organization_id" name="organization_id">
                                <option value="">Select organization</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" @if(old('organization_id') == $org->id) selected @endif>{{ $org->name }}</option>
                                @endforeach
                            </select>
                            @error('organization_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="owner_id" class="form-label">Assign Owner <span class="text-danger">*</span></label>
                            <select class="form-select" id="owner_id" name="owner_id" >
                                <option value="">Select owner</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @if($owner->id == $currentUserId) selected @endif>{{ $owner->name }}</option>
                                @endforeach
                            </select>
                            @error('owner_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control " id="email" name="email" value="{{ old('email') }}">
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-start">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </form>
</div>
 
@push('scripts')
<script>
$(document).ready(function() {
    $('#organization_id').select2({
        placeholder: 'Select organization',
        allowClear: true,
        width: '100%'
    });
    $('#owner_id').select2({
        placeholder: 'Select owner',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endpush
@endsection
