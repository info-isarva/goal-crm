@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card mt-0">
                <div class="card-header"><h4 class="mb-0">Edit Outreach Type</h4></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('outreach_types.update', $outreachType->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Name<span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $outreachType->name) }}" required>
                            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control">{{ old('description', $outreachType->description) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $outreachType->sort_order) }}" min="0">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $outreachType->is_active))>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-custom">Update</button>
                        <a href="{{ route('outreach_types.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
