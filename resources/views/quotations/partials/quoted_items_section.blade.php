@php
    $qtItems = $qtItems ?? old('items', [['product_id' => '', 'quantity' => 1, 'price' => 0, 'amount' => 0, 'discount' => 0, 'tax' => '', 'total' => 0]]);
    $qtProductType = old('quotation_product_type', $quotationProductType ?? 'default');
    $firstItem = $qtItems[0] ?? [];
    $selectedProduct = $products->firstWhere('id', $firstItem['product_id'] ?? null);
    $productLineAmount = (float) ($firstItem['amount'] ?? 0);
    if ($productLineAmount <= 0) {
        $productLineAmount = ((float) ($firstItem['quantity'] ?? 0)) * ((float) ($firstItem['price'] ?? 0));
    }
    $productDiscountAmt = (float) ($firstItem['discount'] ?? 0);
    $productDiscountPct = (float) ($firstItem['discount_percent'] ?? 0);
    if ($productDiscountPct <= 0 && $productDiscountAmt > 0 && $productLineAmount > 0) {
        $productDiscountPct = round(($productDiscountAmt / $productLineAmount) * 100, 2);
    }

    $selectedCurrencyCode = old('currency_code', $quotation->currency_code ?? 'INR');
    $selectedCurrencySymbol = old('currency_symbol', $quotation->currency_symbol ?? '₹');
    $currenciesList = [
        ['code' => 'INR', 'symbol' => '₹', 'rate' => 1.0, 'name' => 'Indian Rupee'],
        ['code' => 'USD', 'symbol' => '$', 'rate' => 0.011976, 'name' => 'US Dollar'],
        ['code' => 'EUR', 'symbol' => '€', 'rate' => 0.011050, 'name' => 'Euro'],
        ['code' => 'GBP', 'symbol' => '£', 'rate' => 0.009434, 'name' => 'British Pound'],
        ['code' => 'AED', 'symbol' => 'AED', 'rate' => 0.043975, 'name' => 'UAE Dirham'],
        ['code' => 'SAR', 'symbol' => 'SAR', 'rate' => 0.044924, 'name' => 'Saudi Riyal'],
        ['code' => 'AUD', 'symbol' => 'A$', 'rate' => 0.018018, 'name' => 'Australian Dollar'],
        ['code' => 'CAD', 'symbol' => 'C$', 'rate' => 0.016340, 'name' => 'Canadian Dollar'],
        ['code' => 'SGD', 'symbol' => 'S$', 'rate' => 0.016129, 'name' => 'Singapore Dollar'],
        ['code' => 'JPY', 'symbol' => '¥', 'rate' => 1.851852, 'name' => 'Japanese Yen'],
        ['code' => 'CNY', 'symbol' => '¥', 'rate' => 0.086957, 'name' => 'Chinese Yuan'],
        ['code' => 'CHF', 'symbol' => 'CHF', 'rate' => 0.010695, 'name' => 'Swiss Franc'],
        ['code' => 'MYR', 'symbol' => 'RM', 'rate' => 0.056497, 'name' => 'Malaysian Ringgit'],
        ['code' => 'NZD', 'symbol' => 'NZ$', 'rate' => 0.019608, 'name' => 'New Zealand Dollar'],
        ['code' => 'ZAR', 'symbol' => 'R', 'rate' => 0.217391, 'name' => 'South African Rand'],
    ];
@endphp

<style>
    .qt-pricing-card { border: 1px solid #9cc3c3; border-collapse: collapse; width: 100%; margin-bottom: 0; }
    .qt-pricing-card td { border: 1px solid #9cc3c3; padding: 10px 12px; vertical-align: middle; }
    .qt-pricing-card .qt-pricing-head td { background: #2aa3a3; color: #fff; font-weight: 700; font-size: 1.05rem; }
    .qt-pricing-card .qt-pricing-discount td { color: #dc3545; font-weight: 600; }
    .qt-product-items-table { border: 1px solid #9cc3c3; border-collapse: collapse; width: 100%; margin-top: -1px; }
    .qt-product-items-table th,
    .qt-product-items-table td { border: 1px solid #9cc3c3; padding: 8px 10px; vertical-align: middle; }
    .qt-product-items-table thead th { background: #2E7D32; color: #fff; font-weight: 700; text-align: center; }
    .qt-product-items-table tfoot td { font-weight: 700; background: #f8fafc; }
    .qt-product-items-table .form-control { min-width: 90px; }
    #qt-items-product-layout .qt-product-mode-toolbar { display: flex; flex-wrap: wrap; gap: 1rem; align-items: end; margin-bottom: 1rem; }
</style>

<div class="mb-3">
    <label for="quotation_product_type" class="form-label"><b>Type</b></label>
    <select class="form-select" id="quotation_product_type" name="quotation_product_type" style="max-width: 280px;">
        <option value="default" @selected($qtProductType === 'default')>Default</option>
        <option value="product" @selected($qtProductType === 'product')>Product</option>
    </select>
    <div class="form-text">Default and Product lists show only matching product records in the dropdown.</div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
    <label class="form-label mb-0"><b>Quoted Items</b></label>
    <div class="d-flex align-items-center gap-2">
        <label for="currency_code" class="form-label mb-0 fw-bold small text-secondary">Currency:</label>
        <select class="form-select form-select-sm" id="currency_code" name="currency_code" style="min-width: 180px; max-width: 260px;">
            @foreach($currenciesList as $curr)
                <option value="{{ $curr['code'] }}"
                        data-symbol="{{ $curr['symbol'] }}"
                        data-rate="{{ $curr['rate'] }}"
                        @selected($selectedCurrencyCode === $curr['code'])>
                    {{ $curr['code'] }} ({{ $curr['symbol'] }}) - {{ $curr['name'] }}
                </option>
            @endforeach
        </select>
        <input type="hidden" id="currency_symbol" name="currency_symbol" value="{{ $selectedCurrencySymbol }}">
    </div>
</div>

<div id="qt-items-default-layout" @if($qtProductType === 'product') style="display:none;" @endif>
    <div class="mb-3">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Amount</th>
                        <th style="min-width:140px;">Discount</th>
                        <th>Tax</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="quotation-items">
                    @foreach($qtItems as $index => $item)
                        @php
                            $selectedProduct = $products->firstWhere('id', $item['product_id'] ?? null);
                            $isTaxable = $selectedProduct ? (bool) $selectedProduct->tax_status : false;
                            $lineAmount = (float) ($item['amount'] ?? 0);
                            if ($lineAmount <= 0) {
                                $lineAmount = ((float) ($item['quantity'] ?? 0)) * ((float) ($item['price'] ?? 0));
                            }
                            $discountAmt = (float) ($item['discount'] ?? 0);
                            $discountPct = (float) ($item['discount_percent'] ?? 0);
                            if ($discountPct <= 0 && $discountAmt > 0 && $lineAmount > 0) {
                                $discountPct = round(($discountAmt / $lineAmount) * 100, 2);
                            }
                        @endphp
                        <tr>
                            <td>
                                <select class="form-select qt-product-select" name="items[{{ $index }}][product_id]" required>
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}"
                                            data-price="{{ $product->unit_price }}"
                                            data-taxable="{{ $product->tax_status ? '1' : '0' }}"
                                            data-tax-rate="{{ optional($product->taxRate->first())->rate }}"
                                            data-product-type="{{ $product->product_type ?? 'default' }}"
                                            {{ ($item['product_id'] ?? '') == $product->id ? 'selected' : '' }}>
                                            {{ $product->product_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" class="form-control quantity" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}" required></td>
                            <td><input type="number" class="form-control price" name="items[{{ $index }}][price]" value="{{ format_smart_number($item['price'] ?? 0, 2, false) }}" step="any" readonly></td>
                            <td><input type="number" class="form-control amount" name="items[{{ $index }}][amount]" value="{{ format_smart_number($item['amount'] ?? 0, 2, false) }}" step="any" min="0" readonly></td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control item-discount-percent" name="items[{{ $index }}][discount_percent]" value="{{ format_smart_number($discountPct, 2, false) }}" step="any" min="0" max="100" placeholder="0">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control item-discount" name="items[{{ $index }}][discount]" value="{{ format_smart_number($item['discount'] ?? 0, 2, false) }}" step="any" min="0" placeholder="0">
                                        <span class="input-group-text">Amt</span>
                                    </div>
                                </div>
                            </td>
                            <td class="item-tax-cell">
                                <select class="form-select item-tax" name="items[{{ $index }}][tax]" style="{{ $isTaxable ? '' : 'display:none;' }}">
                                    <option value="">Select Tax</option>
                                    @foreach($taxRates as $taxRate)
                                        <option value="{{ $taxRate->rate }}" {{ ($item['tax'] ?? '') == $taxRate->rate ? 'selected' : '' }}>{{ $taxRate->name }} ({{ $taxRate->rate }}%)</option>
                                    @endforeach
                                </select>
                                <span class="item-tax-na text-muted small" style="{{ $isTaxable ? 'display:none;' : '' }}">N/A</span>
                            </td>
                            <td><input type="number" class="form-control total" name="items[{{ $index }}][total]" value="{{ format_smart_number($item['total'] ?? 0, 2, false) }}" step="any" readonly></td>
                            <td><button type="button" class="btn btn-danger remove-item">Remove</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-primary" id="add-item">Add Item</button>
    </div>
</div>

<div id="qt-items-product-layout" @if($qtProductType !== 'product') style="display:none;" @endif>
    <div class="mb-3">
        <div class="qt-product-mode-toolbar">
            <div style="min-width:280px; flex:1;">
                <label for="qt-product-mode-select" class="form-label">Product</label>
                <select class="form-select qt-product-select" id="qt-product-mode-select">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                            data-price="{{ $product->unit_price }}"
                            data-taxable="{{ $product->tax_status ? '1' : '0' }}"
                            data-tax-rate="{{ optional($product->taxRate->first())->rate }}"
                            data-product-type="{{ $product->product_type ?? 'default' }}"
                            data-project-detail="{{ e($product->project_detail ?? '') }}"
                            {{ ($firstItem['product_id'] ?? '') == $product->id ? 'selected' : '' }}>
                            {{ $product->product_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:160px;">
                <label for="qt-product-discount-percent" class="form-label">Discount %</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="qt-product-discount-percent" value="{{ format_smart_number($productDiscountPct, 2, false) }}" min="0" max="100" step="any">
                    <span class="input-group-text">%</span>
                </div>
            </div>
        </div>

        <table class="qt-pricing-card">
            <tr class="qt-pricing-head">
                <td id="qt-pricing-product-name">{{ $selectedProduct->product_name ?? 'Select a product' }}</td>
                <td style="width:220px; text-align:center;">Pricing</td>
            </tr>
            <tr>
                <td>Actual Price Employee - 1 User</td>
                <td style="text-align:center;"><span id="qt-actual-unit-price">0</span> Per User</td>
            </tr>
            <tr class="qt-pricing-discount">
                <td>Discounted Price - 1 User (<span id="qt-discount-pct-label">{{ format_smart_number($productDiscountPct, 2, false) }}</span>% Discount)</td>
                <td style="text-align:center;"><span id="qt-discounted-unit-price">0</span> Per User</td>
            </tr>
        </table>

        <table class="qt-product-items-table">
            <thead>
                <tr>
                    <th style="width:70px;">Sl. No</th>
                    <th>Description</th>
                    <th style="width:120px;">Price</th>
                    <th style="width:140px;">No of Employee</th>
                    <th style="width:150px;" id="qt-product-total-header">Total ({{ $selectedCurrencyCode }})</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td id="qt-line-description">{{ $selectedProduct->product_name ?? '—' }}</td>
                    <td><input type="number" class="form-control price" id="qt-product-line-price" value="{{ format_smart_number($firstItem['price'] ?? 0, 2, false) }}" step="any" readonly></td>
                    <td><input type="number" class="form-control quantity" id="qt-product-line-qty" value="{{ $firstItem['quantity'] ?? 1 }}" min="1" required></td>
                    <td><input type="number" class="form-control total" id="qt-product-line-total" value="{{ format_smart_number($firstItem['total'] ?? 0, 2, false) }}" step="any" readonly></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end">Grand Total</td>
                    <td class="text-center" id="qt-product-grand-total">0</td>
                </tr>
            </tfoot>
        </table>

        <input type="hidden" id="qt-product-hidden-product-id" name="items[0][product_id]" value="{{ $firstItem['product_id'] ?? '' }}">
        <input type="hidden" id="qt-product-hidden-quantity" name="items[0][quantity]" value="{{ $firstItem['quantity'] ?? 1 }}">
        <input type="hidden" id="qt-product-hidden-price" name="items[0][price]" value="{{ format_smart_number($firstItem['price'] ?? 0, 2, false) }}">
        <input type="hidden" id="qt-product-hidden-amount" name="items[0][amount]" value="{{ format_smart_number($firstItem['amount'] ?? 0, 2, false) }}">
        <input type="hidden" id="qt-product-hidden-discount" name="items[0][discount]" value="{{ format_smart_number($firstItem['discount'] ?? 0, 2, false) }}">
        <input type="hidden" id="qt-product-hidden-discount-percent" name="items[0][discount_percent]" value="{{ format_smart_number($productDiscountPct, 2, false) }}">
        <input type="hidden" id="qt-product-hidden-tax" name="items[0][tax]" value="{{ $firstItem['tax'] ?? '' }}">
        <input type="hidden" id="qt-product-hidden-total" name="items[0][total]" value="{{ format_smart_number($firstItem['total'] ?? 0, 2, false) }}">
    </div>
</div>

@php
    $qtProductsPayload = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->product_name,
            'unit_price' => (float) $product->unit_price,
            'product_type' => $product->product_type ?? 'default',
            'project_detail' => $product->project_detail,
            'tax_status' => (bool) $product->tax_status,
            'tax_rate' => optional($product->taxRate->first())->rate,
        ];
    })->values();
@endphp

<script>
window.QtQuotationItemsConfig = {
    itemIndex: {{ count($qtItems) }},
    productType: @json($qtProductType),
    products: @json($qtProductsPayload),
    taxRates: @json($taxRates->map(fn ($t) => ['rate' => $t->rate, 'name' => $t->name])->values()),
    productMetaUrl: @json(route('quotations.product_meta', ['product' => '__ID__'])),
    currencies: @json($currenciesList),
    currentCurrency: @json($selectedCurrencyCode),
    currentSymbol: @json($selectedCurrencySymbol)
};
</script>
<script src="{{ asset('js/quotation-items.js') }}?v=6"></script>
