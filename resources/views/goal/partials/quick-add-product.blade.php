{{-- Shared Quick Add Product modal + script (include once per page) --}}
@once
<style>
.goal-product-with-add {
    display: flex;
    align-items: stretch;
    gap: 8px;
    width: 100%;
}
.goal-product-with-add > select,
.goal-product-with-add .select2-container {
    flex: 1 1 auto;
    min-width: 0;
}
.btn-goal-quick-add-product {
    flex: 0 0 38px;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    border: 1px solid #c4b5fd;
    background: #f5f3ff;
    color: #7c3aed;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    line-height: 1;
}
.btn-goal-quick-add-product:hover {
    background: #ede9fe;
    color: #6d28d9;
}
#quickAddProductModal { z-index: 1065; }
.modal-backdrop.show + .modal-backdrop { z-index: 1060; }
#quickAddProductModal .modal-content {
    border: none;
    border-radius: 14px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
}
#quickAddProductModal .modal-header {
    border-bottom: 1px solid #e8edf5;
    padding: 14px 16px;
}
#quickAddProductModal .modal-title {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}
#quickAddProductModal .modal-body { padding: 14px 16px; }
#quickAddProductModal .modal-footer {
    border-top: 1px solid #e8edf5;
    padding: 12px 16px;
}
#quickAddProductModal .field-error {
    display: none;
    margin-top: 6px;
    font-size: 0.75rem;
    color: #dc2626;
}
#quickAddProductModal .field-error.show { display: block; }
#quickAddProductModal .btn-save-quick {
    background: linear-gradient(90deg, #7c3aed, #6d28d9);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
#quickAddProductModal .btn-save-quick:hover { filter: brightness(1.05); color: #fff; }
#quickAddProductModal .btn-save-quick:disabled { opacity: 0.65; }
</style>

<div class="modal fade" id="quickAddProductModal" tabindex="-1" aria-labelledby="quickAddProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddProductModalLabel">Quick Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="quick_product_name">Product Name <span class="text-danger">*</span></label>
                <input type="text" id="quick_product_name" class="form-control"
                       placeholder="e.g. Home Loan" maxlength="150" autocomplete="off">
                <div class="field-error" id="quickProductError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-save-quick" id="btnSaveQuickProduct">
                    <i class="bi bi-check-lg"></i> Add
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const quickModalEl = document.getElementById('quickAddProductModal');
    const nameInput = document.getElementById('quick_product_name');
    const errEl = document.getElementById('quickProductError');
    const saveBtn = document.getElementById('btnSaveQuickProduct');
    if (!quickModalEl || !nameInput || !saveBtn) return;

    let targetSelect = null;
    const quickModal = bootstrap.Modal.getOrCreateInstance(quickModalEl);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-goal-quick-add-product, .btn-quick-add-product');
        if (!btn) return;
        const sel = btn.getAttribute('data-product-select') || '#product';
        targetSelect = document.querySelector(sel);
    });

    function showQuickError(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('show', !!msg);
        nameInput.classList.toggle('is-invalid', !!msg);
    }

    function selectProduct(name) {
        const productSelect = targetSelect || document.getElementById('product');
        if (!productSelect) return;
        let opt = Array.from(productSelect.options).find(function (o) { return o.value === name; });
        if (!opt) {
            opt = new Option(name, name, true, true);
            productSelect.add(opt);
        }
        productSelect.value = name;
        if (window.jQuery && window.jQuery(productSelect).hasClass('select2-hidden-accessible')) {
            window.jQuery(productSelect).val(name).trigger('change');
        }
    }

    async function saveQuickProduct() {
        const name = (nameInput.value || '').trim();
        if (!name) {
            showQuickError('Please enter product name.');
            nameInput.focus();
            return;
        }
        showQuickError('');
        saveBtn.disabled = true;
        const original = saveBtn.innerHTML;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        try {
            const res = await fetch(@json(route('goal.vrm.products.quick')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ product_name: name }),
            });
            const data = await res.json().catch(function () { return {}; });
            if (!res.ok) {
                showQuickError(data?.errors?.product_name?.[0] || data?.message || 'Unable to add product.');
                return;
            }
            selectProduct(data.product_name);
            nameInput.value = '';
            quickModal.hide();
        } catch (e) {
            showQuickError('Unable to add product. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = original;
        }
    }

    quickModalEl.addEventListener('shown.bs.modal', function () {
        showQuickError('');
        nameInput.value = '';
        nameInput.focus();
    });
    quickModalEl.addEventListener('hidden.bs.modal', function () {
        if (document.querySelector('.modal.show')) {
            document.body.classList.add('modal-open');
        }
    });
    nameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); saveQuickProduct(); }
    });
    nameInput.addEventListener('input', function () { showQuickError(''); });
    saveBtn.addEventListener('click', saveQuickProduct);
})();
</script>
@endpush
@endonce
