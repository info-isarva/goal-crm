@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card mt-3">
        <form method="POST" action="{{ route('tax_rates.store') }}">
            @csrf
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Add Tax Rate</h4>
                <a href="{{ route('tax_rates.index') }}" class="btn btn-light btn-sm">&laquo; Back to Tax Rates</a>
            </div>
            <div class="card-body">
                <!-- <div class="row">
                    <div class="col-md-6"> -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" >
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="rate" class="form-label">Rate (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="rate" name="rate" value="{{ old('rate') }}" >
                            @error('rate')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    <!-- </div>
                    <div class="col-md-6"> -->
                        <div class="mb-3">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" >
                                <option value="">Select type</option>
                                <option value="percentage" @if(old('type')=='percentage') selected @endif>Percentage</option>
                                <option value="fixed" @if(old('type')=='fixed') selected @endif>Fixed</option>
                            </select>
                            @error('type')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="country" class="form-label">Country</label>
                            <select class="form-select" id="country" name="country">
                                <option value="">Select country</option>
                                @foreach(["India", "United States", "United Kingdom", "Canada", "Australia", "Germany", "France", "Singapore", "UAE", "China", "Japan"] as $country)
                                    <option value="{{ $country }}"
                                        @if(old('country', null) !== null)
                                            {{ old('country') == $country ? 'selected' : '' }}
                                        @else
                                            {{ $country == 'India' ? 'selected' : '' }}
                                        @endif
                                    >{{ $country }}</option>
                                @endforeach
                            </select>
                            @error('country')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    <!-- </div>
                </div> -->
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Save Tax Rate</button>
                </div>
            </div>
        </form>
    </div>
        </div>
    </div>

</div>
@endsection
