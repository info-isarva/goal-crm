{{-- Edit Call Log — same design/fields as Log Call create popup --}}
<div class="modal fade vrm-call-modal" id="editVrmCallModal" tabindex="-1" aria-labelledby="editVrmCallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" class="vrm-log-form" id="editVrmCallForm" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div class="modal-title-wrap">
                        <span class="modal-icon"><i class="bi bi-pencil-square"></i></span>
                        <div>
                            <h5 class="modal-title" id="editVrmCallModalLabel">Edit Call Log</h5>
                            <div class="modal-sub">Update customer call details and linked lead information.</div>
                        </div>
                    </div>
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-section">
                        <div class="section-bar"><i class="bi bi-person"></i> Customer Details</div>
                        <div class="field-grid">
                            <div class="field">
                                <label class="form-label" for="edit_customer_name">Customer <span class="req">*</span></label>
                                <div class="input-icon">
                                    <i class="bi bi-search"></i>
                                    <input type="text" name="customer_name" id="edit_customer_name"
                                           class="form-control" placeholder="Search customer (name, mobile, or ID)..."
                                           required maxlength="150" autocomplete="off"
                                           data-required-message="Please enter customer name.">
                                </div>
                            </div>
                            <div class="field">
                                <label class="form-label" for="edit_mobile">Mobile <span class="req">*</span></label>
                                <div class="input-icon">
                                    <i class="bi bi-telephone"></i>
                                    <input type="text" name="mobile" id="edit_mobile"
                                           class="form-control" placeholder="10-digit mobile" maxlength="10"
                                           inputmode="numeric" pattern="[0-9]{10}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-bar"><i class="bi bi-telephone"></i> Call Details</div>
                        <div class="field-grid">
                            <div class="field">
                                <label class="form-label" for="edit_disposition">Disposition <span class="req">*</span></label>
                                <div class="goal-disposition-with-add product-with-add">
                                    <select name="disposition" id="edit_disposition"
                                            class="form-select js-select2"
                                            data-placeholder="Select disposition" required
                                            data-required-message="Please select disposition.">
                                        @foreach(($dispositions ?? []) as $d)
                                            <option value="{{ $d->name }}"
                                                    data-creates-lead="{{ $d->creates_lead ? '1' : '0' }}"
                                                    data-maps-to-lead-type="{{ $d->maps_to_lead_type }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn-quick-add-disposition" data-bs-toggle="modal"
                                            data-bs-target="#quickAddDispositionModal" data-disposition-select="#edit_disposition"
                                            title="Quick add disposition" aria-label="Quick add disposition">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="field">
                                <label class="form-label" for="edit_callback_at">Callback</label>
                                <div class="input-icon">
                                    <i class="bi bi-calendar3"></i>
                                    <input type="text" name="callback_at" id="edit_callback_at"
                                           class="form-control js-datetimepicker"
                                           placeholder="dd-mm-yyyy --:--" autocomplete="off">
                                </div>
                            </div>
                            <div class="field">
                                <label class="form-label" for="edit_goal_org_unit_id">Branch</label>
                                <div class="input-icon">
                                    <i class="bi bi-building"></i>
                                    <select name="goal_org_unit_id" id="edit_goal_org_unit_id"
                                            class="form-select js-select2"
                                            data-placeholder="Select branch" data-allow-clear="true">
                                        <option value="">Select branch</option>
                                        @foreach($branches ?? [] as $b)
                                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="field check-align">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_has_lead" disabled>
                                    <label class="form-check-label" for="edit_has_lead">Lead already linked to this call</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-bar"><i class="bi bi-bullseye"></i> Lead Information</div>
                        <div class="field-grid">
                            <div class="field">
                                <label class="form-label" for="edit_product">Product</label>
                                <div class="input-icon">
                                    <i class="bi bi-layers"></i>
                                    <select name="product" id="edit_product"
                                            class="form-select js-select2"
                                            data-placeholder="Select product" data-allow-clear="true">
                                        <option value=""></option>
                                        @foreach($products ?? [] as $product)
                                            <option value="{{ $product->product_name }}">{{ $product->product_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-bar"><i class="bi bi-cash-coin"></i> Loan Details</div>
                        <div class="field-grid">
                            <div class="field">
                                <label class="form-label" for="edit_loan_amount">Loan Amount</label>
                                <div class="input-icon">
                                    <span class="rupee">₹</span>
                                    <input type="text" inputmode="decimal" data-decimals="2" name="loan_amount" id="edit_loan_amount"
                                           class="form-control js-decimal" placeholder="Enter loan amount">
                                </div>
                            </div>
                            <div class="field">
                                <label class="form-label" for="edit_notes">Notes</label>
                                <div class="input-icon textarea-icon">
                                    <i class="bi bi-file-text"></i>
                                    <textarea name="notes" id="edit_notes" class="form-control" rows="3"
                                              placeholder="Add call notes, customer response, or remarks..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <div class="footer-actions" style="width:100%;justify-content:flex-end;">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-save-call">
                            <i class="bi bi-floppy"></i> Save changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    if (window.__vrmCallEditBound) return;
    window.__vrmCallEditBound = true;

    function setSelectValue(id, value) {
        var el = document.getElementById(id);
        if (!el) return;
        var v = value == null ? '' : String(value);
        if (v && !Array.from(el.options).some(function (o) { return o.value === v; })) {
            el.add(new Option(v, v, true, true));
        }
        el.value = v;
        if (window.jQuery && jQuery.fn.select2 && jQuery(el).hasClass('js-select2')) {
            jQuery(el).val(v || null).trigger('change');
        }
    }

    function fillEditForm(data) {
        var form = document.getElementById('editVrmCallForm');
        if (!form) return;
        form.action = data.update_url || '';
        document.getElementById('edit_customer_name').value = data.customer_name || '';
        document.getElementById('edit_mobile').value = data.mobile || '';
        setSelectValue('edit_disposition', data.disposition || 'Interested');
        document.getElementById('edit_callback_at').value = data.callback_at || data.callback_display || '';
        setSelectValue('edit_goal_org_unit_id', data.goal_org_unit_id || '');
        setSelectValue('edit_product', data.product || '');
        document.getElementById('edit_loan_amount').value = data.loan_amount || '';
        document.getElementById('edit_notes').value = data.notes || '';
        var hasLead = document.getElementById('edit_has_lead');
        if (hasLead) hasLead.checked = !!(data.has_lead || data.lead_id);
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-edit-call');
        if (!btn) return;
        e.preventDefault();

        var editModalEl = document.getElementById('editVrmCallModal');
        if (!editModalEl || typeof bootstrap === 'undefined') return;

        fillEditForm({
            update_url: btn.getAttribute('data-update-url') || '',
            customer_name: btn.getAttribute('data-customer') || '',
            mobile: btn.getAttribute('data-mobile') || '',
            disposition: btn.getAttribute('data-disposition') || 'Interested',
            notes: btn.getAttribute('data-notes') || '',
            callback_at: btn.getAttribute('data-callback') || '',
            goal_org_unit_id: btn.getAttribute('data-branch-id') || '',
            product: btn.getAttribute('data-product') || '',
            loan_amount: btn.getAttribute('data-loan-amount') || '',
            has_lead: btn.getAttribute('data-has-lead') === '1',
            lead_id: btn.getAttribute('data-lead-id') || ''
        });

        bootstrap.Modal.getOrCreateInstance(editModalEl).show();
    });

    window.__vrmFillEditCallForm = fillEditForm;
})();
</script>
@endpush
@endonce
