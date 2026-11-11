@extends('layouts.app')

@section('content')
<link href="{{ asset('css/customer-custom.css') }}" rel="stylesheet" />
<div class="container-fluid p-4">
    <form method="POST" action="{{ route('customers.update', $customer->id) }}">
        @csrf
        @method('PUT')
        <div class="card mt-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Edit Company Owner</h3>
                <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm">&laquo; Back to Company Owners</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Company Owner Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $customer->name) }}" >
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="organization_id" class="form-label">Organization <span class="text-danger">*</span></label>
                            <select class="form-select" id="organization_id" name="organization_id" >
                                <option value="">Select organization</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" @if(old('organization_id', $customer->organization_id) == $org->id) selected @endif>{{ $org->name }}</option>
                                @endforeach
                            </select>
                            @error('organization_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="owner_id" class="form-label">Assign Owner <span class="text-danger">*</span></label>
                            <select class="form-select" id="owner_id" name="owner_id">
                                <option value="">Select owner</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @if($owner->id == old('owner_id', $customer->owner_id ?? $customer->user_owner_id)) selected @endif>{{ $owner->name }}</option>
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
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">
                            @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $customer->email) }}">
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-start">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
{{-- <script>
// Organization name autocomplete
const orgInput = document.getElementById('organization_name');
const orgList = document.getElementById('org-autocomplete-list');
let orgSelectedId = null;
orgInput.addEventListener('input', function() {
    const val = this.value;
    if (val.length < 2) {
        orgList.innerHTML = '';
        return;
    }
    fetch('/organizations/autocomplete?q=' + encodeURIComponent(val))
        .then(res => res.json())
        .then(data => {
            orgList.innerHTML = '';
            if (data.length) {
                 data.forEach(function(org) {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = org.name;
                    item.onclick = function() {
                        orgInput.value = org.name;
                        document.getElementById('organization_id').value = org.id;
                        orgList.innerHTML = '';
                    };
                    orgList.appendChild(item);
                });
            }
        });
});
// Hide autocomplete on blur
orgInput.addEventListener('blur', function() {
    setTimeout(() => orgList.innerHTML = '', 200);
});
</script> --}}

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
