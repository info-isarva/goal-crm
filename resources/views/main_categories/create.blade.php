@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card mt-0">
                <div class="card-header">
                    <h4>Add Main Category</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('main_categories.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_name" name="category_name" value="{{ old('category_name') }}">
                            @error('category_name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="1" {{ (string)old('status', '1') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ (string)old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-custom">Create</button>
                        <a href="{{ route('main_categories.index') }}" class="btn btn-secondary btn-padding">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
