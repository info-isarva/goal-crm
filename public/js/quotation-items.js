(function () {
    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        var cfg = window.QtQuotationItemsConfig || {};
        var typeSelect = document.getElementById('quotation_product_type');
        var defaultLayout = document.getElementById('qt-items-default-layout');
        var productLayout = document.getElementById('qt-items-product-layout');
        var itemsContainer = document.getElementById('quotation-items');
        var subtotalField = document.getElementById('subtotal');
        var grandTotalField = document.getElementById('grand-total');
        var enableOverallTax = document.getElementById('enable-overall-tax');
        var overallTaxWrap = document.getElementById('overall-tax-wrap');
        var overallTaxSelect = document.getElementById('overall-tax');
        var enableAdjustment = document.getElementById('enable-adjustment');
        var adjustmentWrap = document.getElementById('adjustment-wrap');
        var adjustmentInput = document.getElementById('adjustment');
        var productModeSelect = document.getElementById('qt-product-mode-select');
        var productDiscountPercent = document.getElementById('qt-product-discount-percent');
        var itemIndex = cfg.itemIndex || 1;

        var currencySelect = document.getElementById('currency_code');
        var currencySymbolInput = document.getElementById('currency_symbol');
        var productTotalHeader = document.getElementById('qt-product-total-header');

        var activeCurrencyCode = (currencySelect && currencySelect.value) || cfg.currentCurrency || 'INR';
        var selectedCurrencyOpt = currencySelect && currencySelect.options[currencySelect.selectedIndex];
        var activeRate = (selectedCurrencyOpt && parseFloat(selectedCurrencyOpt.getAttribute('data-rate'))) || 1.0;
        var activeSymbol = (selectedCurrencyOpt && selectedCurrencyOpt.getAttribute('data-symbol')) || cfg.currentSymbol || '₹';

        if (!typeSelect || !itemsContainer) {
            return;
        }

        function qtFormatNum(val) {
            var n = parseFloat(val);
            if (!isFinite(n)) n = 0;
            n = Math.round(n * 100) / 100;
            if (Math.abs(n - Math.round(n)) < 1e-9) {
                return String(Math.round(n));
            }
            return n.toFixed(2);
        }

        function formatCurrency(val) {
            var n = parseFloat(val) || 0;
            return activeSymbol + ' ' + n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function currentType() {
            return typeSelect.value === 'product' ? 'product' : 'default';
        }

        function setLayoutDisabled(layout, disabled) {
            if (!layout) return;
            layout.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                if (el.id === 'add-item' || el.classList.contains('remove-item')) {
                    el.disabled = disabled;
                    return;
                }
                el.disabled = disabled;
            });
        }

        function filterProductOptions(selectEl, type) {
            if (!selectEl) return;
            Array.prototype.forEach.call(selectEl.options, function (opt) {
                if (!opt.value) {
                    opt.hidden = false;
                    return;
                }
                var pType = opt.getAttribute('data-product-type') || 'default';
                opt.hidden = pType !== type;
            });
            if (selectEl.selectedOptions.length && selectEl.selectedOptions[0].hidden) {
                selectEl.value = '';
            }
        }

        function filterAllProductSelects() {
            var type = currentType();
            document.querySelectorAll('.qt-product-select').forEach(function (sel) {
                filterProductOptions(sel, type);
            });
        }

        function applyProjectDetail(html) {
            if (!html) return;
            if (window.QuotationFormHelpers && typeof window.QuotationFormHelpers.applyProjectDetailContent === 'function') {
                window.QuotationFormHelpers.applyProjectDetailContent(html);
                return;
            }
            window.__qtPendingProjectDetail = html;
            var jsonEl = document.getElementById('project_detail_json');
            if (jsonEl) {
                jsonEl.textContent = JSON.stringify(html);
            }
            var panel = document.getElementById('projectDetailPanel');
            var toggleBtn = document.getElementById('btnToggleProjectDetail');
            if (panel) {
                panel.style.display = 'block';
            }
            if (toggleBtn) {
                toggleBtn.classList.add('is-open');
            }
        }

        function fetchProjectDetail(productId) {
            if (!productId || currentType() !== 'product') return;
            var product = (cfg.products || []).find(function (p) {
                return String(p.id) === String(productId);
            });
            if (product && product.project_detail) {
                applyProjectDetail(product.project_detail);
                return;
            }
            var url = (cfg.productMetaUrl || '').replace('__ID__', productId);
            if (!url) return;
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && data.project_detail) {
                        applyProjectDetail(data.project_detail);
                    }
                })
                .catch(function () {});
        }

        function applyProductTaxVisibility(row, selectedOption, autoFillTax) {
            var taxCell = row.querySelector('.item-tax-cell');
            var taxSelect = row.querySelector('.item-tax');
            var taxNa = row.querySelector('.item-tax-na');
            if (!taxCell || !taxSelect) return;

            var taxable = selectedOption && selectedOption.value && selectedOption.getAttribute('data-taxable') === '1';
            if (taxable) {
                taxSelect.style.display = '';
                if (taxNa) taxNa.style.display = 'none';
                if (autoFillTax) {
                    var productTaxRate = selectedOption.getAttribute('data-tax-rate') || '';
                    if (productTaxRate !== '') {
                        taxSelect.value = productTaxRate;
                    }
                }
            } else {
                taxSelect.style.display = 'none';
                if (taxNa) taxNa.style.display = '';
                taxSelect.value = '';
            }
        }

        function updateRowTotal(row, source) {
            var quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            var priceInput = row.querySelector('.price');
            var amountInput = row.querySelector('.amount');
            var discountInput = row.querySelector('.item-discount');
            var percentInput = row.querySelector('.item-discount-percent');
            var taxSelect = row.querySelector('.item-tax');
            var totalInput = row.querySelector('.total');

            // Determine base INR price
            var baseInrPrice = 0;
            if (source === 'userPrice') {
                var userPrice = parseFloat(priceInput.value) || 0;
                baseInrPrice = activeRate > 0 ? (userPrice / activeRate) : userPrice;
                priceInput.dataset.baseInrPrice = String(baseInrPrice);
            } else if (priceInput.dataset.baseInrPrice !== undefined && priceInput.dataset.baseInrPrice !== '') {
                baseInrPrice = parseFloat(priceInput.dataset.baseInrPrice) || 0;
            } else {
                var currentPrice = parseFloat(priceInput.value) || 0;
                baseInrPrice = activeRate > 0 ? (currentPrice / activeRate) : currentPrice;
                priceInput.dataset.baseInrPrice = String(baseInrPrice);
            }

            var price = baseInrPrice * activeRate;
            priceInput.value = qtFormatNum(price);

            var baseAmount = quantity * price;
            if (amountInput) amountInput.value = qtFormatNum(baseAmount);

            var discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
            var percent = parseFloat(percentInput ? percentInput.value : 0) || 0;

            if (source === 'percent' && percentInput) {
                percent = parseFloat(percentInput.value) || 0;
                discount = (baseAmount * percent) / 100;
                if (discount > baseAmount) discount = baseAmount;
                if (discountInput) {
                    discountInput.value = qtFormatNum(discount);
                    discountInput.dataset.baseInrDiscount = String(activeRate > 0 ? discount / activeRate : discount);
                }
                percentInput.dataset.lockedPercent = String(percent);
            } else if (source === 'discount' && discountInput && percentInput) {
                discount = parseFloat(discountInput.value) || 0;
                if (discount > baseAmount) {
                    discount = baseAmount;
                    discountInput.value = qtFormatNum(discount);
                }
                discountInput.dataset.baseInrDiscount = String(activeRate > 0 ? discount / activeRate : discount);
                percent = baseAmount > 0 ? (discount / baseAmount) * 100 : 0;
                percentInput.value = qtFormatNum(percent);
                percentInput.dataset.lockedPercent = String(percent);
            } else if (source === 'init' && discountInput && percentInput) {
                if (percent <= 0 && discount > 0 && baseAmount > 0) {
                    percent = (discount / baseAmount) * 100;
                    percentInput.value = qtFormatNum(percent);
                } else {
                    discount = (baseAmount * percent) / 100;
                    if (discount > baseAmount) discount = baseAmount;
                    discountInput.value = qtFormatNum(discount);
                }
                discountInput.dataset.baseInrDiscount = String(activeRate > 0 ? discount / activeRate : discount);
                percentInput.dataset.lockedPercent = String(parseFloat(percentInput.value) || 0);
            } else if (source === 'currencyChange' && discountInput && percentInput) {
                var baseInrDisc = parseFloat(discountInput.dataset.baseInrDiscount) || 0;
                if (baseInrDisc > 0) {
                    discount = baseInrDisc * activeRate;
                    if (discount > baseAmount) discount = baseAmount;
                    discountInput.value = qtFormatNum(discount);
                } else {
                    var locked = percentInput.dataset.lockedPercent;
                    percent = locked !== undefined && locked !== '' ? (parseFloat(locked) || 0) : (parseFloat(percentInput.value) || 0);
                    discount = (baseAmount * percent) / 100;
                    if (discount > baseAmount) discount = baseAmount;
                    discountInput.value = qtFormatNum(discount);
                    discountInput.dataset.baseInrDiscount = String(activeRate > 0 ? discount / activeRate : discount);
                }
            } else if (percentInput && discountInput) {
                var locked = percentInput.dataset.lockedPercent;
                percent = locked !== undefined && locked !== ''
                    ? (parseFloat(locked) || 0)
                    : (parseFloat(percentInput.value) || 0);
                percentInput.value = qtFormatNum(percent);
                percentInput.dataset.lockedPercent = String(percent);
                discount = (baseAmount * percent) / 100;
                if (discount > baseAmount) discount = baseAmount;
                discountInput.value = qtFormatNum(discount);
                discountInput.dataset.baseInrDiscount = String(activeRate > 0 ? discount / activeRate : discount);
            }

            discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
            var taxVisible = taxSelect && taxSelect.style.display !== 'none';
            var tax = taxVisible ? (parseFloat(taxSelect.value) || 0) : 0;
            if (taxSelect && !taxVisible) taxSelect.value = '';

            var total = baseAmount - discount;
            total += (total * tax) / 100;
            if (totalInput) totalInput.value = qtFormatNum(total);
        }

        function calculateTotals(source) {
            var subtotal = 0;
            if (currentType() === 'product') {
                subtotal = parseFloat(document.getElementById('qt-product-hidden-total')?.value || 0) || 0;
            } else {
                itemsContainer.querySelectorAll('tr').forEach(function (row) {
                    subtotal += parseFloat(row.querySelector('.total').value) || 0;
                });
            }

            if (subtotalField) subtotalField.value = qtFormatNum(subtotal);

            var overallDiscountInput = document.getElementById('overall-discount');
            var overallDiscountPercentInput = document.getElementById('overall-discount-percent');
            var overallDiscount = parseFloat(overallDiscountInput?.value || 0) || 0;
            var overallDiscountPercent = parseFloat(overallDiscountPercentInput?.value || 0) || 0;

            if (source === 'overall-percent') {
                overallDiscount = (subtotal * overallDiscountPercent) / 100;
                if (overallDiscount > subtotal) overallDiscount = subtotal;
                if (overallDiscountInput) overallDiscountInput.value = qtFormatNum(overallDiscount);
            } else if (source === 'overall-amount') {
                if (overallDiscount > subtotal && overallDiscountInput) {
                    overallDiscount = subtotal;
                    overallDiscountInput.value = qtFormatNum(overallDiscount);
                }
                overallDiscountPercent = subtotal > 0 ? (overallDiscount / subtotal) * 100 : 0;
                if (overallDiscountPercentInput) overallDiscountPercentInput.value = qtFormatNum(overallDiscountPercent);
            } else if (source === 'init') {
                if (overallDiscountPercent <= 0 && overallDiscount > 0 && subtotal > 0) {
                    overallDiscountPercent = (overallDiscount / subtotal) * 100;
                    if (overallDiscountPercentInput) overallDiscountPercentInput.value = qtFormatNum(overallDiscountPercent);
                } else if (overallDiscountInput) {
                    overallDiscount = (subtotal * overallDiscountPercent) / 100;
                    if (overallDiscount > subtotal) overallDiscount = subtotal;
                    overallDiscountInput.value = qtFormatNum(overallDiscount);
                }
            } else if (overallDiscountPercentInput && overallDiscountInput) {
                overallDiscountPercent = parseFloat(overallDiscountPercentInput.value) || 0;
                overallDiscount = (subtotal * overallDiscountPercent) / 100;
                if (overallDiscount > subtotal) overallDiscount = subtotal;
                overallDiscountInput.value = qtFormatNum(overallDiscount);
            }

            overallDiscount = parseFloat(overallDiscountInput?.value || 0) || 0;
            var overallTax = (enableOverallTax && enableOverallTax.checked)
                ? (parseFloat(overallTaxSelect?.value || 0) || 0)
                : 0;

            var adjustment = 0;
            if (enableAdjustment && enableAdjustment.checked && adjustmentInput) {
                if (source === 'userAdjustment') {
                    var userAdj = parseFloat(adjustmentInput.value) || 0;
                    adjustmentInput.dataset.baseInrAdjustment = String(activeRate > 0 ? userAdj / activeRate : userAdj);
                    adjustment = userAdj;
                } else if (adjustmentInput.dataset.baseInrAdjustment !== undefined && adjustmentInput.dataset.baseInrAdjustment !== '') {
                    var baseInrAdj = parseFloat(adjustmentInput.dataset.baseInrAdjustment) || 0;
                    adjustment = baseInrAdj * activeRate;
                    adjustmentInput.value = qtFormatNum(adjustment);
                } else {
                    adjustment = parseFloat(adjustmentInput.value) || 0;
                    adjustmentInput.dataset.baseInrAdjustment = String(activeRate > 0 ? adjustment / activeRate : adjustment);
                }
            }

            var grandTotal = subtotal - overallDiscount;
            grandTotal += (grandTotal * overallTax) / 100;
            grandTotal -= adjustment;

            if (grandTotalField) grandTotalField.value = qtFormatNum(grandTotal);
        }

        function syncProductModeHiddenFields() {
            var select = productModeSelect;
            var opt = select && select.selectedOptions[0];
            var baseUnitPriceInINR = opt && opt.value ? (parseFloat(opt.getAttribute('data-price')) || 0) : 0;
            var unitPrice = baseUnitPriceInINR * activeRate;
            var qty = parseFloat(document.getElementById('qt-product-line-qty')?.value || 0) || 0;
            var percent = parseFloat(productDiscountPercent?.value || 0) || 0;
            var baseAmount = unitPrice * qty;
            var discount = (baseAmount * percent) / 100;
            if (discount > baseAmount) discount = baseAmount;
            var discountedUnit = unitPrice - (unitPrice * percent / 100);
            var lineTotal = baseAmount - discount;

            document.getElementById('qt-pricing-product-name').textContent = opt && opt.value ? opt.textContent.trim() : 'Select a product';
            document.getElementById('qt-line-description').textContent = opt && opt.value ? opt.textContent.trim() : '—';
            document.getElementById('qt-actual-unit-price').textContent = formatCurrency(unitPrice);
            document.getElementById('qt-discounted-unit-price').textContent = formatCurrency(discountedUnit);
            document.getElementById('qt-discount-pct-label').textContent = qtFormatNum(percent);
            document.getElementById('qt-product-line-price').value = qtFormatNum(discountedUnit);
            document.getElementById('qt-product-line-total').value = qtFormatNum(lineTotal);
            document.getElementById('qt-product-grand-total').textContent = formatCurrency(lineTotal);

            document.getElementById('qt-product-hidden-product-id').value = opt ? opt.value : '';
            document.getElementById('qt-product-hidden-quantity').value = String(qty || 1);
            document.getElementById('qt-product-hidden-price').value = qtFormatNum(discountedUnit);
            document.getElementById('qt-product-hidden-amount').value = qtFormatNum(baseAmount);
            document.getElementById('qt-product-hidden-discount').value = qtFormatNum(discount);
            document.getElementById('qt-product-hidden-discount-percent').value = qtFormatNum(percent);
            document.getElementById('qt-product-hidden-tax').value = '';
            document.getElementById('qt-product-hidden-total').value = qtFormatNum(lineTotal);
        }

        function toggleLayouts() {
            var isProduct = currentType() === 'product';
            if (defaultLayout) defaultLayout.style.display = isProduct ? 'none' : '';
            if (productLayout) productLayout.style.display = isProduct ? '' : 'none';
            setLayoutDisabled(defaultLayout, isProduct);
            setLayoutDisabled(productLayout, !isProduct);
            filterAllProductSelects();
            if (isProduct) {
                syncProductModeHiddenFields();
            }
            calculateTotals('init');
        }

        function buildProductOption(product) {
            return '<option value="' + product.id + '" data-price="' + product.unit_price + '" data-taxable="' + (product.tax_status ? '1' : '0') + '" data-tax-rate="' + (product.tax_rate || '') + '" data-product-type="' + (product.product_type || 'default') + '" data-project-detail="">' + product.name.replace(/</g, '&lt;') + '</option>';
        }

        function addDefaultRow() {
            var tableBody = itemsContainer;
            var newRow = document.createElement('tr');
            var options = '<option value="">Select Product</option>';
            (cfg.products || []).forEach(function (product) {
                options += buildProductOption(product);
            });
            var taxOptions = '<option value="">Select Tax</option>';
            (cfg.taxRates || []).forEach(function (tax) {
                taxOptions += '<option value="' + tax.rate + '">' + tax.name + ' (' + tax.rate + '%)</option>';
            });

            newRow.innerHTML = '<td><select class="form-select qt-product-select" name="items[' + itemIndex + '][product_id]" required>' + options + '</select></td>'
                + '<td><input type="number" class="form-control quantity" name="items[' + itemIndex + '][quantity]" value="1" required></td>'
                + '<td><input type="number" class="form-control price" name="items[' + itemIndex + '][price]" readonly></td>'
                + '<td><input type="number" class="form-control amount" name="items[' + itemIndex + '][amount]" value="0" readonly></td>'
                + '<td><div class="d-flex flex-column gap-1"><div class="input-group input-group-sm"><input type="number" class="form-control item-discount-percent" name="items[' + itemIndex + '][discount_percent]" value="0" step="0.01" min="0" max="100"><span class="input-group-text">%</span></div><div class="input-group input-group-sm"><input type="number" class="form-control item-discount" name="items[' + itemIndex + '][discount]" value="0" step="0.01" min="0"><span class="input-group-text">Amt</span></div></div></td>'
                + '<td class="item-tax-cell"><select class="form-select item-tax" name="items[' + itemIndex + '][tax]" style="display:none;">' + taxOptions + '</select><span class="item-tax-na text-muted small">N/A</span></td>'
                + '<td><input type="number" class="form-control total" name="items[' + itemIndex + '][total]" readonly></td>'
                + '<td><button type="button" class="btn btn-danger remove-item">Remove</button></td>';

            tableBody.appendChild(newRow);
            filterProductOptions(newRow.querySelector('.qt-product-select'), currentType());
            itemIndex += 1;
            updateRowTotal(newRow, 'init');
            calculateTotals();
        }

        if (currencySelect) {
            currencySelect.addEventListener('change', function () {
                var newOpt = currencySelect.options[currencySelect.selectedIndex];
                if (!newOpt) return;
                var newRate = parseFloat(newOpt.getAttribute('data-rate')) || 1.0;
                var newSymbol = newOpt.getAttribute('data-symbol') || '₹';
                var newCode = currencySelect.value || 'INR';

                if (newRate === activeRate) return;

                // Update active currency variables before conversion
                activeRate = newRate;
                activeSymbol = newSymbol;
                activeCurrencyCode = newCode;

                // Convert all default layout rows from their stored base INR values
                itemsContainer.querySelectorAll('tr').forEach(function (row) {
                    var pInput = row.querySelector('.price');
                    if (pInput) {
                        var baseInrP = parseFloat(pInput.dataset.baseInrPrice);
                        if (isNaN(baseInrP)) {
                            baseInrP = (parseFloat(pInput.value) || 0) / (activeRate > 0 ? activeRate : 1);
                            pInput.dataset.baseInrPrice = String(baseInrP);
                        }
                        pInput.value = qtFormatNum(baseInrP * activeRate);
                    }
                    updateRowTotal(row, 'currencyChange');
                });

                if (currencySymbolInput) {
                    currencySymbolInput.value = newSymbol;
                }

                if (productTotalHeader) {
                    productTotalHeader.textContent = 'Total (' + newCode + ')';
                }

                if (currentType() === 'product') {
                    syncProductModeHiddenFields();
                }

                calculateTotals('currencyChange');
            });
        }

        typeSelect.addEventListener('change', function () {
            toggleLayouts();
        });

        if (productModeSelect) {
            productModeSelect.addEventListener('change', function () {
                syncProductModeHiddenFields();
                calculateTotals();
                if (productModeSelect.value) {
                    fetchProjectDetail(productModeSelect.value);
                }
            });
        }

        if (productDiscountPercent) {
            productDiscountPercent.addEventListener('input', function () {
                syncProductModeHiddenFields();
                calculateTotals();
            });
        }

        var productQty = document.getElementById('qt-product-line-qty');
        if (productQty) {
            productQty.addEventListener('input', function () {
                syncProductModeHiddenFields();
                calculateTotals();
            });
        }

        itemsContainer.addEventListener('change', function (e) {
            if (!e.target.matches('select[name$="[product_id]"]')) return;
            var selectedOption = e.target.options[e.target.selectedIndex];
            var row = e.target.closest('tr');
            var priceInput = row.querySelector('.price');
            var totalInput = row.querySelector('.total');
            var priceInINR = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            
            // Set base INR price directly from selected product's base price
            priceInput.dataset.baseInrPrice = String(priceInINR);
            var price = priceInINR * activeRate;
            priceInput.value = qtFormatNum(price);

            if (selectedOption.value) {
                priceInput.removeAttribute('readonly');
                totalInput.removeAttribute('readonly');
            } else {
                priceInput.setAttribute('readonly', true);
                totalInput.setAttribute('readonly', true);
            }
            applyProductTaxVisibility(row, selectedOption, true);
            updateRowTotal(row, 'fields');
            calculateTotals();
        });

        itemsContainer.addEventListener('input', function (e) {
            var row = e.target.closest('tr');
            if (!row) return;
            if (e.target.matches('.item-discount-percent')) {
                updateRowTotal(row, 'percent');
                calculateTotals();
            } else if (e.target.matches('.item-discount')) {
                updateRowTotal(row, 'discount');
                calculateTotals();
            } else if (e.target.matches('.price')) {
                updateRowTotal(row, 'userPrice');
                calculateTotals();
            } else if (e.target.matches('.quantity, .item-tax')) {
                updateRowTotal(row, 'fields');
                calculateTotals();
            }
        });

        itemsContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-item')) {
                e.target.closest('tr').remove();
                calculateTotals();
            }
        });

        var addItemBtn = document.getElementById('add-item');
        if (addItemBtn) {
            addItemBtn.addEventListener('click', addDefaultRow);
        }

        if (enableOverallTax) enableOverallTax.addEventListener('change', function () {
            var enabled = enableOverallTax.checked;
            if (overallTaxWrap) overallTaxWrap.style.display = enabled ? '' : 'none';
            if (overallTaxSelect) overallTaxSelect.disabled = !enabled;
            if (!enabled && overallTaxSelect) overallTaxSelect.value = '';
            calculateTotals();
        });
        if (enableAdjustment) enableAdjustment.addEventListener('change', function () {
            var enabled = enableAdjustment.checked;
            if (adjustmentWrap) adjustmentWrap.style.display = enabled ? '' : 'none';
            if (adjustmentInput) {
                adjustmentInput.disabled = !enabled;
                if (!enabled) adjustmentInput.value = 0;
            }
            calculateTotals();
        });

        var overallDiscountPercentInput = document.getElementById('overall-discount-percent');
        var overallDiscountInput = document.getElementById('overall-discount');
        if (overallDiscountPercentInput) overallDiscountPercentInput.addEventListener('input', function () { calculateTotals('overall-percent'); });
        if (overallDiscountInput) overallDiscountInput.addEventListener('input', function () { calculateTotals('overall-amount'); });
        if (overallTaxSelect) overallTaxSelect.addEventListener('change', calculateTotals);
        if (adjustmentInput) {
            adjustmentInput.addEventListener('input', function () { calculateTotals('userAdjustment'); });
            adjustmentInput.addEventListener('change', function () { calculateTotals('userAdjustment'); });
        }

        itemsContainer.querySelectorAll('tr').forEach(function (row) {
            var productSelect = row.querySelector('select[name$="[product_id]"]');
            if (productSelect) {
                applyProductTaxVisibility(row, productSelect.options[productSelect.selectedIndex], false);
            }
            updateRowTotal(row, 'init');
        });

        window.QtQuotationItems = {
            validateProductsStep: function () {
                if (currentType() === 'product') {
                    var productId = document.getElementById('qt-product-hidden-product-id')?.value || '';
                    var qty = parseFloat(document.getElementById('qt-product-hidden-quantity')?.value || 0) || 0;
                    if (!productId) {
                        alert('Please select a product before continuing.');
                        return false;
                    }
                    if (qty < 1) {
                        alert('Please enter number of employees (minimum 1).');
                        return false;
                    }
                    syncProductModeHiddenFields();
                    return true;
                }
                var rows = itemsContainer.querySelectorAll('tr');
                if (!rows.length) {
                    alert('Add at least one product before continuing.');
                    return false;
                }
                var invalid = false;
                rows.forEach(function (row) {
                    var product = row.querySelector('select[name*="[product_id]"]');
                    var qty = row.querySelector('.quantity');
                    var price = row.querySelector('.price');
                    if (product && !product.value) invalid = true;
                    if (qty && (!qty.value || Number(qty.value) < 1)) invalid = true;
                    if (price && price.value === '') invalid = true;
                });
                if (invalid) {
                    alert('Please complete all quoted item fields before continuing.');
                    return false;
                }
                return true;
            }
        };

        toggleLayouts();
        if (currentType() === 'product' && productModeSelect && productModeSelect.value) {
            syncProductModeHiddenFields();
            setTimeout(function () {
                fetchProjectDetail(productModeSelect.value);
            }, 0);
        }
        calculateTotals('init');
    });
})();
