@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card mt-3">
                <div class="card-header">
                    <h4>Edit Tax Rate</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tax_rates.update', $taxRate->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="name" class="form-label">Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $taxRate->name) }}" >
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="rate" class="form-label">Rate (%)<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="rate" name="rate" value="{{ old('rate', $taxRate->rate) }}" >
                            @error('rate')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" >
                                <option value="percentage" {{ old('type', $taxRate->type) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                <option value="fixed" {{ old('type', $taxRate->type) == 'fixed' ? 'selected' : '' }}>Fixed</option>
                            </select>
                            @error('type')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="country" class="form-label">Country</label>
                            <select class="form-select" id="country" name="country">
                                <option value="">Select Country</option>
                                @foreach(["India", "United States", "United Kingdom", "Canada", "Australia", "Germany", "France", "Singapore", "UAE", "China", "Japan"] as $country)
                                    <option value="{{ $country }}" {{ old('country', $taxRate->country) == $country ? 'selected' : '' }}>{{ $country }}</option>
                                @endforeach
                            </select>
                            @error('country')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{ route('tax_rates.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
