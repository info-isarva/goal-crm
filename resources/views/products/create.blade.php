@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{asset('css/leads.css') }}">
<div class="container-fluid p-4">
    <div class="row ">
        <div class="col-12">
            <div class="card mt-0">
                <div class="card-header">
                    <h4>Add Product</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('products.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" value="{{ old('product_name') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="product_code" class="form-label">Product Code<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="product_code" name="product_code" value="{{ old('product_code') }}" required>
                                    @error('product_code')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="product_type" class="form-label">Type<span class="text-danger">*</span></label>
                                    <select class="form-select" id="product_type" name="product_type" required>
                                        <option value="default" {{ old('product_type', 'default') === 'default' ? 'selected' : '' }}>Default</option>
                                        <option value="product" {{ old('product_type', 'default') === 'product' ? 'selected' : '' }}>Product</option>
                                    </select>
                                    @error('product_type')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="product_category_id" class="form-label">Product Category<span class="text-danger">*</span></label>
                                    <select class="form-select" id="product_category_id" name="product_category_id" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('product_category_id') == $category->id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="product_owner_id" class="form-label">Product Owner<span class="text-danger">*</span></label>
                                    <select class="form-select" id="product_owner_id" name="product_owner_id" required>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ (old('product_owner_id', $currentUser->id ?? null) == $user->id) ? 'selected' : '' }}>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="product_status" name="product_status" value="1" {{ old('product_status', 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="product_status">Active</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                
                                <div class="mb-3">
                                    <label for="unit_price" class="form-label">Unit Price<span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="unit_price" name="unit_price" value="{{ old('unit_price') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="commission_rate" class="form-label">Commission Rate</label>
                                    <input type="number" class="form-control" id="commission_rate" name="commission_rate" value="{{ old('commission_rate') }}">
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="tax_status" name="tax_status" value="1" {{ old('tax_status', 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tax_status">Taxable</label>
                                </div>
                                <div class="mb-3" id="tax_rate_wrap" style="{{ old('tax_status', 1) ? '' : 'display:none;' }}">
                                    <label for="tax_rate_id" class="form-label">Tax</label>
                                    <select class="form-select" id="tax_rate_id" name="tax_rate_id">
                                        <option value="">Select Tax</option>
                                        @foreach($taxRates as $tax)
                                            <option value="{{ $tax->id }}" {{ old('tax_rate_id') == $tax->id ? 'selected' : '' }}>{{ $tax->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="product_description" class="form-label">Description</label>
                            <textarea class="form-control" id="product_description" name="product_description">{{ old('product_description') }}</textarea>
                        </div>
                        @include('products.partials.project_detail_editor', [
                            'projectDetailValue' => old('project_detail'),
                        ])
                        <button type="submit" class="btn btn-custom">Create Product</button>
                        <a href="{{ route('products.index') }}" class="btn btn-secondary btn-padding">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var taxable = document.getElementById('tax_status');
    var wrap = document.getElementById('tax_rate_wrap');
    var taxSelect = document.getElementById('tax_rate_id');
    if (!taxable || !wrap) return;
    function syncTaxField() {
        if (taxable.checked) {
            wrap.style.display = '';
        } else {
            wrap.style.display = 'none';
            if (taxSelect) taxSelect.value = '';
        }
    }
    taxable.addEventListener('change', syncTaxField);
    syncTaxField();
})();
</script>
@endsection
