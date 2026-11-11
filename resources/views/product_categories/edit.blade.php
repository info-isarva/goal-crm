@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card mt-0">
                <div class="card-header">
                    <h4>Edit Product Category</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('product_categories.update', $category->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Product Category<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_name" name="category_name" value="{{ old('category_name', $category->category_name) }}" >
                            @error('category_name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description">{{ old('description', $category->description) }}</textarea>
                            @error('description')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="1" {{ old('status', $category->status) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status', $category->status) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Select Type</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ old('type', $category->type) == $type ? 'selected' : '' }}>
                                        {{ ucwords(str_replace(['_', '-'], ' ', $type)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-custom">Update</button>
                        <a href="{{ route('product_categories.index') }}" class="btn btn-secondary btn-padding">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
