@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card mt-0">
                <div class="card-header">
                    <h4>Edit Lead Source</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('lead_sources.update', $leadSource->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="name" class="form-label">Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $leadSource->name) }}">
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description">{{ old('description', $leadSource->description) }}</textarea>
                            @error('description')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-custom">Update</button>
                        <a href="{{ route('lead_sources.index') }}" class="btn btn-secondary btn-padding">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
