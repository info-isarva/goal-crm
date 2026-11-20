{{-- Shared Quick Add Disposition modal + script (include once per page) --}}
@once
<style>
.goal-disposition-with-add {
    display: flex;
    align-items: stretch;
    gap: 8px;
    width: 100%;
}
.goal-disposition-with-add > select,
.goal-disposition-with-add .select2-container {
    flex: 1 1 auto;
    min-width: 0;
}
.btn-goal-quick-add-disposition,
.btn-quick-add-disposition {
    flex: 0 0 38px;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    border: 1px solid #99f6e4;
    background: #f0fdfa;
    color: #0f766e;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    line-height: 1;
}
.btn-goal-quick-add-disposition:hover,
.btn-quick-add-disposition:hover {
    background: #ccfbf1;
    color: #0f766e;
}
#quickAddDispositionModal.modal.show { z-index: 10670 !important; }
#quickAddDispositionModal .modal-content {
    border: none;
    border-radius: 14px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
}
#quickAddDispositionModal .modal-header {
    border-bottom: 1px solid #e8edf5;
    padding: 14px 16px;
}
#quickAddDispositionModal .modal-title {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}
#quickAddDispositionModal .modal-body { padding: 14px 16px; }
#quickAddDispositionModal .modal-footer {
    border-top: 1px solid #e8edf5;
    padding: 12px 16px;
}
#quickAddDispositionModal .field-error {
    display: none;
    margin-top: 6px;
    font-size: 0.75rem;
    color: #dc2626;
}
#quickAddDispositionModal .field-error.show { display: block; }
#quickAddDispositionModal .btn-save-quick {
    background: linear-gradient(90deg, #0d9488, #0f766e);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
#quickAddDispositionModal .btn-save-quick:hover { filter: brightness(1.05); color: #fff; }
#quickAddDispositionModal .btn-save-quick:disabled { opacity: 0.65; }
</style>

<div class="modal fade" id="quickAddDispositionModal" tabindex="-1" aria-labelledby="quickAddDispositionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddDispositionModalLabel">Quick Add Disposition</h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="quick_disposition_name">Disposition Name <span class="text-danger">*</span></label>
                    <input type="text" id="quick_disposition_name" class="form-control"
                           placeholder="e.g. Follow-up Later" maxlength="100" autocomplete="off">
                    <div class="field-error" id="quickDispositionError"></div>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="quick_disposition_creates_lead" value="1">
                    <label class="form-check-label" for="quick_disposition_creates_lead">Can create lead</label>
                </div>
                <div>
                    <label class="form-label" for="quick_disposition_lead_type">Maps to Lead Type</label>
                    <select id="quick_disposition_lead_type" class="form-select">
                        <option value="">— Auto —</option>
                        <option value="immediate">Immediate</option>
                        <option value="future">Future</option>
                    </select>
                    <div class="form-text" style="font-size:.72rem;">Used when Create Lead is checked on Log Call.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-save-quick" id="btnSaveQuickDisposition">
                    <i class="bi bi-check-lg"></i> Add
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const quickModalEl = document.getElementById('quickAddDispositionModal');
    const nameInput = document.getElementById('quick_disposition_name');
    const createsLeadEl = document.getElementById('quick_disposition_creates_lead');
    const leadTypeEl = document.getElementById('quick_disposition_lead_type');
    const errEl = document.getElementById('quickDispositionError');
    const saveBtn = document.getElementById('btnSaveQuickDisposition');
    if (!quickModalEl || !nameInput || !saveBtn) return;

    let targetSelect = null;
    const quickModal = bootstrap.Modal.getOrCreateInstance(quickModalEl);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    window.__vrmDispositionMeta = window.__vrmDispositionMeta || {};

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-goal-quick-add-disposition, .btn-quick-add-disposition');
        if (!btn) return;
        const sel = btn.getAttribute('data-disposition-select') || '#disposition';
        targetSelect = document.querySelector(sel);
    });

    function showQuickError(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('show', !!msg);
        nameInput.classList.toggle('is-invalid', !!msg);
    }

    function selectDisposition(payload) {
        const dispositionSelect = targetSelect || document.getElementById('disposition');
        if (!dispositionSelect) return;
        const name = payload.name;
        let opt = Array.from(dispositionSelect.options).find(function (o) { return o.value === name; });
        if (!opt) {
            opt = new Option(name, name, true, true);
            opt.dataset.createsLead = payload.creates_lead ? '1' : '0';
            opt.dataset.mapsToLeadType = payload.maps_to_lead_type || '';
            dispositionSelect.add(opt);
        } else {
            opt.dataset.createsLead = payload.creates_lead ? '1' : '0';
            opt.dataset.mapsToLeadType = payload.maps_to_lead_type || '';
        }
        window.__vrmDispositionMeta[name] = {
            creates_lead: !!payload.creates_lead,
            maps_to_lead_type: payload.maps_to_lead_type || null,
        };
        dispositionSelect.value = name;
        if (window.jQuery && window.jQuery(dispositionSelect).hasClass('select2-hidden-accessible')) {
            window.jQuery(dispositionSelect).val(name).trigger('change');
        } else {
            dispositionSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    async function saveQuickDisposition() {
        const name = (nameInput.value || '').trim();
        if (!name) {
            showQuickError('Please enter disposition name.');
            nameInput.focus();
            return;
        }
        showQuickError('');
        saveBtn.disabled = true;
        const original = saveBtn.innerHTML;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        try {
            const res = await fetch(@json(route('goal.vrm.dispositions.quick')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    name: name,
                    creates_lead: !!(createsLeadEl && createsLeadEl.checked),
                    maps_to_lead_type: leadTypeEl ? (leadTypeEl.value || null) : null,
                }),
            });
            const data = await res.json().catch(function () { return {}; });
            if (!res.ok) {
                showQuickError(data?.errors?.name?.[0] || data?.message || 'Unable to add disposition.');
                return;
            }
            selectDisposition(data);
            nameInput.value = '';
            if (createsLeadEl) createsLeadEl.checked = false;
            if (leadTypeEl) leadTypeEl.value = '';
            quickModal.hide();
        } catch (e) {
            showQuickError('Unable to add disposition. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = original;
        }
    }

    quickModalEl.addEventListener('shown.bs.modal', function () {
        showQuickError('');
        nameInput.value = '';
        if (createsLeadEl) createsLeadEl.checked = false;
        if (leadTypeEl) leadTypeEl.value = '';
        nameInput.focus();
    });
    quickModalEl.addEventListener('hidden.bs.modal', function () {
        if (document.querySelector('.modal.show')) {
            document.body.classList.add('modal-open');
        }
    });
    nameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); saveQuickDisposition(); }
    });
    nameInput.addEventListener('input', function () { showQuickError(''); });
    saveBtn.addEventListener('click', saveQuickDisposition);
})();
</script>
@endpush
@endonce
