{{-- Shared Call Details modal (VRM dashboard + Recent Calls All) --}}
<style>
#callDetailModal .modal-content {
    border: none; border-radius: 16px; overflow: hidden;
    max-height: 90vh; display: flex; flex-direction: column;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
}
#callDetailModal .modal-header {
    border-bottom: 1px solid #e8edf5; padding: 16px 20px;
    align-items: flex-start; flex-shrink: 0; background: #fff; gap: 12px;
}
#callDetailModal .cd-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; width: 100%;
}
#callDetailModal .cd-head-left { display: flex; align-items: flex-start; gap: 12px; min-width: 0; }
#callDetailModal .cd-icon {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #8b5cf6, #6366f1); color: #fff;
    display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem;
}
#callDetailModal .cd-title { margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
#callDetailModal .cd-sub { margin: 3px 0 0; font-size: 0.84rem; color: #64748b; }
#callDetailModal .cd-head-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
#callDetailModal .cd-call-id {
    display: inline-flex; align-items: center; border-radius: 999px;
    background: var(--theme-50); color: var(--theme-primary-hover); border: 1px solid var(--theme-200);
    padding: 0.3rem 0.7rem; font-size: 0.78rem; font-weight: 700; white-space: nowrap;
}
#callDetailModal .modal-body {
    padding: 16px 20px; overflow-y: auto; flex: 1 1 auto; min-height: 0;
    scrollbar-width: thin;
}
#callDetailModal .modal-footer {
    border-top: 1px solid #e8edf5; padding: 12px 20px; flex-shrink: 0; background: #fff;
    display: flex; justify-content: flex-end; gap: 8px;
}
#callDetailModal .cd-grid {
    display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
    gap: 14px; margin-bottom: 14px;
}
#callDetailModal .cd-card {
    border: 1px solid var(--theme-100); border-radius: 14px; background: #fff; padding: 14px 16px;
}
#callDetailModal .cd-card-title {
    font-size: 0.78rem; font-weight: 800; color: var(--theme-primary-hover); text-transform: uppercase;
    letter-spacing: .04em; margin: 0 0 12px;
}
#callDetailModal .cd-customer-top {
    display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;
}
#callDetailModal .cd-avatar {
    width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0;
    background: var(--theme-100); color: var(--theme-primary);
    display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem;
}
#callDetailModal .cd-name { margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; line-height: 1.25; }
#callDetailModal .cd-cust-id { margin: 3px 0 6px; font-size: 0.8rem; color: #64748b; font-weight: 600; }
#callDetailModal .cd-meta-list { display: grid; gap: 8px; }
#callDetailModal .cd-meta-item {
    display: flex; align-items: center; gap: 8px; font-size: 0.88rem; color: #334155; font-weight: 600;
}
#callDetailModal .cd-meta-item i { color: #64748b; width: 16px; text-align: center; }
#callDetailModal .cd-copy {
    border: none; background: transparent; color: #94a3b8; padding: 0; line-height: 1; cursor: pointer;
}
#callDetailModal .cd-copy:hover { color: var(--theme-primary); }
#callDetailModal .cd-summary-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px;
}
#callDetailModal .cd-field-label {
    display: block; font-size: 0.72rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .03em; margin-bottom: 4px;
}
#callDetailModal .cd-field-value { font-size: 0.9rem; font-weight: 700; color: #0f172a; }
#callDetailModal .cd-notes {
    font-size: 0.9rem; color: #334155; line-height: 1.55; font-weight: 500; white-space: pre-wrap;
}
#callDetailModal .cd-history-wrap {
    border: 1px solid #e8edf5; border-radius: 14px; padding: 14px 16px; background: #f8fafc;
}
#callDetailModal .cd-history-title {
    display: flex; align-items: center; gap: 8px; margin: 0 0 14px;
    font-size: 0.95rem; font-weight: 800; color: #0f172a;
}
#callDetailModal .cd-timeline { position: relative; padding-left: 18px; }
#callDetailModal .cd-timeline::before {
    content: ''; position: absolute; left: 5px; top: 4px; bottom: 4px; width: 2px; background: #c4b5fd;
}
#callDetailModal .cd-tl-item { position: relative; padding: 0 0 16px 14px; }
#callDetailModal .cd-tl-item:last-child { padding-bottom: 0; }
#callDetailModal .cd-tl-item::before {
    content: ''; position: absolute; left: -16px; top: 6px;
    width: 10px; height: 10px; border-radius: 50%; background: #7c3aed;
    border: 2px solid #ede9fe; box-shadow: 0 0 0 2px #fff;
}
#callDetailModal .cd-tl-item.is-current::before { background: var(--theme-primary); border-color: var(--theme-100); }
#callDetailModal .cd-tl-top {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 4px;
}
#callDetailModal .cd-tl-date { font-size: 0.84rem; font-weight: 700; color: #0f172a; }
#callDetailModal .cd-tl-notes { font-size: 0.84rem; color: #475569; line-height: 1.45; }
#callDetailModal .disp-pill,
#callDetailModal .lead-badge {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0.22rem 0.65rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700;
}
#callDetailModal .disp-pill.interested { background: #dcfce7; color: #15803d; }
#callDetailModal .disp-pill.callback { background: #fef3c7; color: #b45309; }
#callDetailModal .disp-pill.not-interested { background: var(--theme-100); color: var(--theme-primary-hover); }
#callDetailModal .disp-pill.busy,
#callDetailModal .disp-pill.no-answer,
#callDetailModal .disp-pill.follow-up { background: #ede9fe; color: #6d28d9; }
#callDetailModal .disp-pill.invalid-number { background: #fee2e2; color: #b91c1c; }
#callDetailModal .lead-badge.yes { background: #dcfce7; color: #15803d; }
#callDetailModal .lead-badge.no { background: #f1f5f9; color: #475569; }
#callDetailModal .btn-cd-close {
    border: none; background: #7c3aed; color: #fff; border-radius: 8px;
    height: 36px; padding: 0 16px; font-weight: 700; font-size: 0.875rem;
}
#callDetailModal .btn-cd-close:hover { background: #6d28d9; color: #fff; }
#callDetailModal .btn-cd-lead {
    border: 1px solid var(--theme-300); background: #fff; color: var(--theme-primary); border-radius: 8px;
    height: 36px; padding: 0 14px; font-weight: 700; font-size: 0.875rem; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
}
#callDetailModal .btn-cd-lead:hover { background: var(--theme-50); color: var(--theme-primary-hover); }
#callDetailModal .cd-loading,
#callDetailModal .cd-error { padding: 2rem 1rem; text-align: center; }
#callDetailModal .cd-loading .spinner-border { width: 2rem; height: 2rem; color: #7c3aed; }
#callDetailModal .cd-error { color: #b91c1c; font-weight: 600; }
@media (max-width: 767.98px) {
    #callDetailModal .cd-grid { grid-template-columns: 1fr; }
    #callDetailModal .cd-summary-grid { grid-template-columns: 1fr; }
}
</style>

<div class="modal fade" id="callDetailModal" tabindex="-1" aria-labelledby="callDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="cd-head">
                    <div class="cd-head-left">
                        <span class="cd-icon"><i class="bi bi-telephone-outbound"></i></span>
                        <div>
                            <h5 class="cd-title" id="callDetailModalLabel">Call Details</h5>
                            <div class="cd-sub">View complete call information and history.</div>
                        </div>
                    </div>
                    <div class="cd-head-right">
                        <span class="cd-call-id" id="cdCallId">Call ID: —</span>
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>
            <div class="modal-body" id="callDetailBody">
                <div class="cd-loading">
                    <div class="spinner-border" role="status"></div>
                    <div class="mt-2 text-muted">Loading call details...</div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="callDetailLeadLink" class="btn-cd-lead d-none"><i class="bi bi-box-arrow-up-right"></i> Open Lead</a>
                <button type="button" id="callDetailEditBtn" class="btn btn-outline-primary btn-sm d-none">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <form id="callDetailDeleteForm" method="POST" class="d-none" onsubmit="return confirm('Delete this call log?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </form>
                <button type="button" class="btn-cd-close" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    function bootCallDetailModal() {
        if (window.__vrmCallDetailBound) return;

        const detailModalEl = document.getElementById('callDetailModal');
        if (!detailModalEl) {
            console.warn('VRM Call Details: modal element missing');
            return;
        }
        if (typeof bootstrap === 'undefined') {
            console.warn('VRM Call Details: bootstrap not loaded yet');
            return;
        }

        window.__vrmCallDetailBound = true;

        const detailModal = bootstrap.Modal.getOrCreateInstance(detailModalEl);
        const bodyEl = document.getElementById('callDetailBody');
        const callIdEl = document.getElementById('cdCallId');
        const leadLink = document.getElementById('callDetailLeadLink');
        const editBtn = document.getElementById('callDetailEditBtn');
        const deleteForm = document.getElementById('callDetailDeleteForm');
        let lastCallPayload = null;

        function dispClass(d) {
            return {
                'Interested': 'interested',
                'Callback': 'callback',
                'Not Interested': 'not-interested',
                'Busy': 'busy',
                'No Answer': 'no-answer',
                'Invalid Number': 'invalid-number'
            }[d] || 'follow-up';
        }

        function escapeHtml(str) {
            return String(str == null ? '' : str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function dash(v) {
            return (v === null || v === undefined || String(v).trim() === '') ? '—' : String(v);
        }

        function pill(disposition) {
            const label = dash(disposition);
            if (label === '—') return '—';
            return '<span class="disp-pill ' + dispClass(disposition) + '">' + escapeHtml(label) + '</span>';
        }

        function leadBadge(lead) {
            const yes = String(lead).toLowerCase() === 'yes';
            return '<span class="lead-badge ' + (yes ? 'yes' : 'no') + '">' + (yes ? 'Yes' : 'No') + '</span>';
        }

        function loadingHtml() {
            return '<div class="cd-loading"><div class="spinner-border" role="status"></div><div class="mt-2 text-muted">Loading call details...</div></div>';
        }

        function renderDetails(data) {
            const history = Array.isArray(data.history) ? data.history : [];
            let historyHtml = '';
            if (!history.length) {
                historyHtml = '<div class="text-muted" style="font-size:.875rem;">No previous calls found for this customer.</div>';
            } else {
                historyHtml = '<div class="cd-timeline">' + history.map(function (h) {
                    return '<div class="cd-tl-item' + (h.is_current ? ' is-current' : '') + '">'
                        + '<div class="cd-tl-top">'
                        + '<span class="cd-tl-date">' + escapeHtml(dash(h.called_at)) + '</span>'
                        + pill(h.disposition)
                        + leadBadge(h.lead)
                        + '</div>'
                        + '<div class="cd-tl-notes">' + escapeHtml(dash(h.notes)) + '</div>'
                        + '</div>';
                }).join('') + '</div>';
            }

            const mobile = dash(data.mobile);
            const mobileHtml = mobile === '—'
                ? '—'
                : '<span>' + escapeHtml(mobile) + '</span>'
                    + ' <button type="button" class="cd-copy" title="Copy mobile" data-copy="' + escapeHtml(mobile) + '"><i class="bi bi-clipboard"></i></button>';

            return ''
                + '<div class="cd-grid">'
                +   '<div class="cd-card">'
                +     '<div class="cd-card-title">Customer Information</div>'
                +     '<div class="cd-customer-top">'
                +       '<span class="cd-avatar"><i class="bi bi-person"></i></span>'
                +       '<div>'
                +         '<h6 class="cd-name">' + escapeHtml(dash(data.customer_name)) + '</h6>'
                +         '<div class="cd-cust-id">' + escapeHtml(dash(data.customer_id)) + '</div>'
                +         pill(data.disposition)
                +       '</div>'
                +     '</div>'
                +     '<div class="cd-meta-list">'
                +       '<div class="cd-meta-item"><i class="bi bi-telephone"></i>' + mobileHtml + '</div>'
                +       '<div class="cd-meta-item"><i class="bi bi-building"></i>' + escapeHtml(dash(data.branch)) + '</div>'
                +       '<div class="cd-meta-item"><i class="bi bi-box"></i>' + escapeHtml(dash(data.product)) + '</div>'
                +       '<div class="cd-meta-item"><i class="bi bi-calendar3"></i>Called on: ' + escapeHtml(dash(data.called_at)) + '</div>'
                +     '</div>'
                +   '</div>'
                +   '<div>'
                +     '<div class="cd-card mb-3">'
                +       '<div class="cd-card-title">Call Summary</div>'
                +       '<div class="cd-summary-grid">'
                +         '<div><span class="cd-field-label">Disposition</span><div class="cd-field-value">' + pill(data.disposition) + '</div></div>'
                +         '<div><span class="cd-field-label">Lead</span><div class="cd-field-value">' + leadBadge(data.lead) + '</div></div>'
                +         '<div><span class="cd-field-label">Callback</span><div class="cd-field-value">' + escapeHtml(dash(data.callback_at)) + '</div></div>'
                +         '<div><span class="cd-field-label">Loan Amount</span><div class="cd-field-value">' + escapeHtml(dash(data.loan_amount)) + '</div></div>'
                +         '<div><span class="cd-field-label">Lead Type</span><div class="cd-field-value">' + escapeHtml(dash(data.lead_type)) + '</div></div>'
                +         '<div><span class="cd-field-label">Product</span><div class="cd-field-value">' + escapeHtml(dash(data.product)) + '</div></div>'
                +       '</div>'
                +     '</div>'
                +     '<div class="cd-card">'
                +       '<div class="cd-card-title">Call Notes</div>'
                +       '<div class="cd-notes">' + escapeHtml(dash(data.notes)) + '</div>'
                +     '</div>'
                +   '</div>'
                + '</div>'
                + '<div class="cd-history-wrap">'
                +   '<h6 class="cd-history-title"><i class="bi bi-clock-history text-primary"></i> Call History (' + history.length + ')</h6>'
                +   historyHtml
                + '</div>';
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-view-call');
            if (!btn) return;
            e.preventDefault();

            const url = btn.getAttribute('data-url');
            if (!url) return;

            callIdEl.textContent = 'Call ID: —';
            leadLink.classList.add('d-none');
            leadLink.removeAttribute('href');
            if (editBtn) editBtn.classList.add('d-none');
            if (deleteForm) deleteForm.classList.add('d-none');
            lastCallPayload = null;
            bodyEl.innerHTML = loadingHtml();
            detailModal.show();

            fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) throw new Error('Failed');
                    return r.json();
                })
                .then(function (data) {
                    lastCallPayload = data;
                    callIdEl.textContent = 'Call ID: #' + (data.call_code || data.id || '—');
                    bodyEl.innerHTML = renderDetails(data);
                    if (data.lead_url) {
                        leadLink.href = data.lead_url;
                        leadLink.classList.remove('d-none');
                    }
                    if (editBtn && data.can_edit) {
                        editBtn.classList.remove('d-none');
                    }
                    if (deleteForm && data.can_delete && data.delete_url) {
                        deleteForm.action = data.delete_url;
                        deleteForm.classList.remove('d-none');
                    }
                })
                .catch(function () {
                    bodyEl.innerHTML = '<div class="cd-error"><i class="bi bi-exclamation-triangle me-1"></i> Unable to load call details. Please try again.</div>';
                });
        });

        if (editBtn) {
            editBtn.addEventListener('click', function () {
                if (!lastCallPayload) return;
                const rowBtn = document.querySelector('.btn-edit-call[data-id="' + lastCallPayload.id + '"]');
                if (rowBtn) {
                    rowBtn.click();
                    return;
                }
                const editModalEl = document.getElementById('editVrmCallModal');
                if (!editModalEl || typeof bootstrap === 'undefined') return;
                if (typeof window.__vrmFillEditCallForm === 'function') {
                    window.__vrmFillEditCallForm({
                        update_url: lastCallPayload.update_url || '',
                        customer_name: lastCallPayload.customer_name || '',
                        mobile: lastCallPayload.mobile || '',
                        disposition: lastCallPayload.disposition || 'Interested',
                        notes: lastCallPayload.notes || '',
                        callback_at: lastCallPayload.callback_at_input || '',
                        goal_org_unit_id: lastCallPayload.goal_org_unit_id || '',
                        lead_type: lastCallPayload.lead_type_raw || '',
                        product: lastCallPayload.product_raw || '',
                        loan_amount: lastCallPayload.loan_amount_raw || '',
                        has_lead: !!lastCallPayload.lead_id,
                        lead_id: lastCallPayload.lead_id || ''
                    });
                }
                bootstrap.Modal.getOrCreateInstance(editModalEl).show();
            });
        }

        document.addEventListener('click', function (e) {
            const copyBtn = e.target.closest('#callDetailModal .cd-copy');
            if (!copyBtn || !navigator.clipboard) return;
            const text = copyBtn.getAttribute('data-copy') || '';
            if (text) navigator.clipboard.writeText(text).catch(function () {});
        });
    }

    bootCallDetailModal();
})();
</script>
@endpush
@endonce
