{{--
  Shared Product master select (+ optional quick-add).
  Props:
    $products (collection)
    $selected (string|null)
    $name (default: product)
    $id (default: product)
    $required (bool)
    $withQuickAdd (bool, default true)
    $selectClass (extra classes)
--}}
@php
    $name = $name ?? 'product';
    $id = $id ?? 'product';
    $selected = old($name, $selected ?? null);
    $required = !empty($required);
    $withQuickAdd = $withQuickAdd ?? true;
    $selectClass = $selectClass ?? 'form-select js-select2';
    $products = $products ?? collect();
@endphp
<div class="goal-product-with-add">
    <select name="{{ $name }}" id="{{ $id }}"
            class="{{ $selectClass }} {{ $errors->has($name) ? 'is-invalid' : '' }}"
            data-placeholder="Select product" data-allow-clear="true"
            @if($required) required @endif>
        <option value=""></option>
        @php
            $names = $products->pluck('product_name')->all();
            $hasSelected = $selected && in_array($selected, $names, true);
        @endphp
        @if($selected && !$hasSelected)
            <option value="{{ $selected }}" selected>{{ $selected }}</option>
        @endif
        @foreach($products as $product)
            <option value="{{ $product->product_name }}" @selected($selected === $product->product_name)>
                {{ $product->product_name }}
            </option>
        @endforeach
    </select>
    @if($withQuickAdd)
        <button type="button" class="btn-goal-quick-add-product"
                title="Quick add product" aria-label="Quick add product"
                data-bs-toggle="modal" data-bs-target="#quickAddProductModal"
                data-product-select="#{{ $id }}">
            <i class="bi bi-plus-lg"></i>
        </button>
    @endif
</div>
