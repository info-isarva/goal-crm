@php
    $dynamic = $dynamic ?? false;
    $emailTemplates = $emailTemplates ?? collect();
    $defaultEmailTemplateId = $defaultEmailTemplateId ?? optional($emailTemplates->firstWhere('is_default', true))->id ?? optional($emailTemplates->first())->id;
    $emailTemplatePayloads = $emailTemplatePayloads ?? [];
    $verifiedFrom = trim((string) (optional($emailSetting)->mail_from_address ?? config('mail.from.address')));
    $userEmail = trim((string) (auth()->user()->email ?? ''));
    $userName = trim((string) (auth()->user()->name ?? '')) ?: config('app.name');
    $fromAddress = $userEmail ?: $verifiedFrom;
    $fromName = $userName;
    $showTransportNote = $userEmail !== '' && $verifiedFrom !== '' && strcasecmp($userEmail, $verifiedFrom) !== 0
        && !str_ends_with(strtolower($userEmail), '@' . strtolower((string) substr(strrchr($verifiedFrom, '@') ?: '', 1)));

    if (!$dynamic && isset($quotation)) {
        $defaultToEmail = optional($quotation->people)->email ?? '';
        $defaultToName = trim(
            (optional($quotation->people)->first_name ?? '') . ' ' .
            (optional($quotation->people)->last_name ?? '')
        );
        $pdfFilename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $quotation->quote_no ?? ('quote_' . $quotation->id)) . '.pdf';
        if ($defaultEmailTemplateId && !empty($emailTemplatePayloads[$defaultEmailTemplateId])) {
            $defaultSubject = $emailTemplatePayloads[$defaultEmailTemplateId]['subject'];
            $defaultBody = $emailTemplatePayloads[$defaultEmailTemplateId]['body'];
        } else {
            $defaultSubject = 'Quotation' . (!empty($quotation->subject) ? ' — ' . $quotation->subject : ' — ' . $quotation->quote_no);
            $senderName = optional($quotation->owner)->name ?? optional(auth()->user())->name ?? ($fromName ?: 'CRM');
            $contactLabel = $defaultToName !== '' ? $defaultToName : ($defaultToEmail ?: 'Customer');
            $defaultBody = '<p>Dear ' . e($contactLabel) . ',</p>'
                . '<p>Greetings from <strong>' . e($company->name ?? config('app.name')) . '</strong>.</p>'
                . '<p>Please find attached Quotation No. <strong>' . e($quotation->quote_no) . '</strong>'
                . (optional($quotation->organization)->name ? ' for <strong>' . e(optional($quotation->organization)->name) . '</strong>' : '')
                . '. Total amount: <strong>' . e(format_smart_money($quotation->grand_total ?? 0)) . '</strong>.</p>'
                . '<p>Kindly review and share your confirmation at your earliest convenience.</p>'
                . '<p>Best regards,<br>' . e($senderName) . '</p>';
        }
        $composeSettings = app(\App\Services\EmailComposeSettingsService::class);
        $defaultBody = $composeSettings->prepareQuotationBody(auth()->user(), $defaultBody);
        $composeEditorStyle = $composeSettings->editorContentStyle(auth()->user());
        $modalTitle = $quotation->quote_no;
        $sendEmailUrl = route('quotations.send_email', $quotation->id);
    } else {
        $defaultToEmail = '';
        $defaultToName = '';
        $pdfFilename = 'quotation.pdf';
        $defaultSubject = '';
        $defaultBody = '';
        $composeEditorStyle = app(\App\Services\EmailComposeSettingsService::class)->editorContentStyle(auth()->user());
        $modalTitle = 'Quotation';
        $sendEmailUrl = '';
    }
@endphp

<style>
.qt-mail-modal .modal-dialog {
    max-width: var(--qt-mail-dialog-width, 920px);
    width: calc(100% - 2rem);
    height: var(--qt-mail-dialog-height, min(82vh, 860px));
    max-height: calc(100vh - 1rem);
    margin: 0.5rem auto;
    display: flex;
    flex-direction: column;
    transition: max-width .2s ease, max-height .2s ease, width .2s ease, height .2s ease;
}
.qt-mail-modal .modal-content {
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
    max-height: 100%;
    display: flex;
    flex-direction: column;
}
.qt-mail-modal.qt-mail-size-sm {
    --qt-mail-dialog-width: 720px;
    --qt-mail-dialog-height: min(72vh, 680px);
    --qt-mail-editor-min-height: 200px;
}
.qt-mail-modal.qt-mail-size-md {
    --qt-mail-dialog-width: 920px;
    --qt-mail-dialog-height: min(82vh, 860px);
    --qt-mail-editor-min-height: 280px;
}
.qt-mail-modal.qt-mail-size-lg {
    --qt-mail-dialog-width: 1140px;
    --qt-mail-dialog-height: min(90vh, 960px);
    --qt-mail-editor-min-height: 360px;
}
.qt-mail-modal.qt-mail-size-xl {
    --qt-mail-dialog-width: calc(100vw - 0.75rem);
    --qt-mail-dialog-height: calc(100vh - 0.75rem);
    --qt-mail-editor-min-height: 320px;
}
.qt-mail-modal.qt-mail-size-xl .modal-dialog {
    width: var(--qt-mail-dialog-width);
    margin: 0.375rem auto;
}
.qt-mail-modal.qt-mail-size-xl .modal-dialog.modal-dialog-centered {
    min-height: calc(100% - 0.75rem);
}
.qt-mail-composer {
    border: 1px solid #d8dee9;
    border-radius: 4px;
    overflow: hidden;
    background: #fff;
    font-size: 13px;
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}
.qt-mail-body-scroll {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.qt-mail-compose-fields {
    flex: 0 0 auto;
    overflow-y: auto;
    max-height: 42vh;
}
.qt-mail-modal.qt-mail-size-xl .qt-mail-compose-fields {
    max-height: 30vh;
}
.qt-mail-titlebar,
.qt-mail-footer {
    flex-shrink: 0;
}
.qt-mail-titlebar {
    background: linear-gradient(180deg, #1f4f82 0%, #1a3f6b 100%);
    color: #fff;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.qt-mail-titlebar .title {
    font-weight: 600;
    font-size: 14px;
}
.qt-mail-titlebar .window-actions {
    display: flex;
    align-items: center;
    gap: 2px;
}
.qt-mail-titlebar .window-actions button {
    border: 0;
    background: transparent;
    color: rgba(255,255,255,.85);
    width: 28px;
    height: 28px;
    border-radius: 4px;
    line-height: 1;
}
.qt-mail-titlebar .window-actions button:disabled {
    opacity: .45;
    cursor: not-allowed;
}
.qt-mail-titlebar .window-actions button:hover:not(:disabled) {
    background: rgba(255,255,255,.12);
    color: #fff;
}
.qt-mail-toolbar {
    padding: 10px 14px;
    border-bottom: 1px solid #e8edf3;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    background: #fafbfc;
    flex-wrap: wrap;
}
.qt-mail-from {
    color: #4b5563;
    font-size: 12px;
    flex: 1;
    min-width: 200px;
}
.qt-mail-template-pick {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    background: #fff;
    border: 1px solid #dbe3ee;
    border-radius: 8px;
    padding: 6px 10px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
}
.qt-mail-template-label {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin: 0;
    white-space: nowrap;
}
.qt-mail-template-select {
    border: 0;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #1e3a8a;
    min-width: 180px;
    max-width: 260px;
    padding: 2px 24px 2px 4px;
    cursor: pointer;
    outline: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M4.5 6L8 9.5 11.5 6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 2px center;
}
.qt-mail-template-select:focus {
    box-shadow: none;
}
.qt-mail-template-manage {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    color: #64748b;
    text-decoration: none;
    flex-shrink: 0;
}
.qt-mail-template-manage:hover {
    background: #f1f5f9;
    color: #2563eb;
}
.qt-mail-from strong {
    color: #111827;
}
.qt-mail-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 14px;
    border-bottom: 1px solid #eef2f6;
}
.qt-mail-row .label {
    width: 42px;
    color: #6b7280;
    font-weight: 500;
    padding-top: 7px;
    flex-shrink: 0;
}
.qt-mail-row .field {
    flex: 1;
    min-width: 0;
}
.qt-mail-row .extra-links {
    padding-top: 7px;
    flex-shrink: 0;
}
.qt-mail-row .extra-links a {
    color: #2563eb;
    text-decoration: none;
    font-size: 12px;
    margin-left: 8px;
}
.qt-mail-row .extra-links a:hover {
    text-decoration: underline;
}
.qt-mail-chip-input {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    min-height: 36px;
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: #fff;
}
.qt-mail-chip-input:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59,130,246,.15);
}
.qt-mail-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eef2ff;
    color: #1e3a8a;
    border-radius: 999px;
    padding: 2px 10px;
    font-size: 12px;
}
.qt-mail-chip button {
    border: 0;
    background: transparent;
    color: #64748b;
    line-height: 1;
    padding: 0;
}
.qt-mail-chip-input input {
    border: 0;
    outline: 0;
    flex: 1;
    min-width: 140px;
    font-size: 13px;
    padding: 4px 2px;
}
.qt-mail-subject-input {
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 7px 10px;
    width: 100%;
    font-size: 13px;
}
.qt-mail-editor-wrap {
    border-top: 1px solid #eef2f6;
    flex: 1 1 auto;
    min-height: var(--qt-mail-editor-min-height, 280px);
    display: flex;
    flex-direction: column;
    overflow: auto;
}
.qt-mail-editor-wrap .crm-quill-wrap {
    border: 0;
    border-radius: 0;
    flex: 1 1 auto;
    min-height: var(--qt-mail-editor-min-height, 280px);
}
.qt-mail-editor-wrap .crm-quill-wrap .ql-editor {
    min-height: 240px;
}
.qt-mail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor table {
    width: 100% !important;
    max-width: 100% !important;
    table-layout: auto !important;
    border-collapse: collapse;
}
.qt-mail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor td,
.qt-mail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor th {
    white-space: normal !important;
    word-break: normal !important;
    min-width: 120px;
    vertical-align: top;
}
.qt-mail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor img {
    max-width: 100%;
    height: auto;
}
.qt-mail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor.ql-blank::before {
    display: none !important;
}
.qt-mail-attachments {
    flex: 0 0 auto;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
    padding: 10px 14px 12px;
}
.qt-mail-attachments-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 12px;
}
.qt-mail-attachment-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 8px 12px;
    max-width: 280px;
}
.qt-mail-attachment-item .pdf-icon {
    width: 28px;
    height: 28px;
    border-radius: 4px;
    background: #fee2e2;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
}
.qt-mail-attachment-item .name {
    font-size: 12px;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.qt-mail-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-top: 1px solid #e5e7eb;
    background: #fff;
    position: sticky;
    bottom: 0;
    z-index: 3;
    box-shadow: 0 -4px 12px rgba(15, 23, 42, .06);
}
.qt-mail-footer .attach-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #dbeafe;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 600;
}
.qt-mail-notice {
    background: #fffbeb;
    border-bottom: 1px solid #fde68a;
    color: #92400e;
    font-size: 12px;
    padding: 8px 14px;
}
</style>

<div class="modal fade qt-mail-modal qt-mail-size-md" id="sendQuotationEmailModal" tabindex="-1" aria-labelledby="sendQuotationEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent shadow-none">
            <div class="qt-mail-composer">
                <div class="qt-mail-titlebar">
                    <div class="title" id="sendQuotationEmailModalLabel">{{ $modalTitle }}</div>
                    <div class="window-actions">
                        <button type="button" id="qtMailZoomOutBtn" title="Zoom out" aria-label="Zoom out"><i class="bi bi-dash-lg"></i></button>
                        <button type="button" id="qtMailZoomInBtn" title="Zoom in" aria-label="Zoom in"><i class="bi bi-plus-lg"></i></button>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>

                <div class="qt-mail-body-scroll">
                <div class="qt-mail-compose-fields">
                <div class="qt-mail-toolbar">
                    <div class="qt-mail-from">
                        <span class="text-muted">From</span>
                        <strong>{{ $fromName }}</strong>
                        <span class="text-muted">&lt;{{ $fromAddress }}&gt;</span>
                        @if($showTransportNote)
                            <div class="small text-muted mt-1">
                                Delivered via Brevo using <strong>{{ $verifiedFrom }}</strong>. Replies will go to <strong>{{ $fromAddress }}</strong>.
                            </div>
                        @endif
                    </div>
                    <div class="qt-mail-template-pick @if($dynamic && $emailTemplates->isEmpty()) d-none @endif" id="qtMailTemplateRow">
                        <label for="quotationEmailTemplateSelect" class="qt-mail-template-label">
                            <i class="bi bi-layout-text-window-reverse"></i> Template
                        </label>
                        <select id="quotationEmailTemplateSelect" class="qt-mail-template-select" title="Choose email design template">
                            @foreach($emailTemplates as $emailTemplate)
                                <option value="{{ $emailTemplate->id }}" @selected((int) $defaultEmailTemplateId === (int) $emailTemplate->id)>
                                    {{ $emailTemplate->name }}@if($emailTemplate->is_default) (Default)@endif
                                </option>
                            @endforeach
                        </select>
                        @if(auth()->user()->crm_role_type === 0)
                            <a href="{{ route('quotation_email_templates.index') }}" target="_blank" class="qt-mail-template-manage" title="Manage templates">
                                <i class="bi bi-gear"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="qt-mail-notice">
                    <i class="bi bi-info-circle me-1"></i>
                    Quotation PDF will be attached automatically when you click Send.
                </div>

                <div id="quotation-email-alert"></div>
                <input type="hidden" id="quotation_email_send_url" value="{{ $sendEmailUrl }}">

                <div class="qt-mail-row">
                    <div class="label">To</div>
                    <div class="field">
                        <div class="qt-mail-chip-input" id="qtMailToWrap">
                            @if($defaultToEmail)
                            <span class="qt-mail-chip" data-email="{{ $defaultToEmail }}">
                                <span>{{ $defaultToName !== '' ? $defaultToName . ' (' . $defaultToEmail . ')' : $defaultToEmail }}</span>
                                <button type="button" class="qt-mail-chip-remove" aria-label="Remove">&times;</button>
                            </span>
                            @endif
                            <input type="text" id="qtMailToInput" placeholder="Add recipient email and press Enter">
                        </div>
                    </div>
                    <div class="extra-links">
                        <a href="#" id="qtMailToggleCc">Cc</a>
                        <a href="#" id="qtMailToggleBcc">Bcc</a>
                    </div>
                </div>

                <div class="qt-mail-row d-none" id="qtMailCcRow">
                    <div class="label">Cc</div>
                    <div class="field">
                        <input type="text" class="qt-mail-subject-input" id="qtMailCcInput" placeholder="Cc recipients (comma separated)">
                    </div>
                </div>

                <div class="qt-mail-row d-none" id="qtMailBccRow">
                    <div class="label">Bcc</div>
                    <div class="field">
                        <input type="text" class="qt-mail-subject-input" id="qtMailBccInput" placeholder="Bcc recipients (comma separated)">
                    </div>
                </div>

                <div class="qt-mail-row">
                    <div class="label">Subject</div>
                    <div class="field">
                        <input type="text" class="qt-mail-subject-input" id="quotation_email_subject" value="{{ $defaultSubject }}">
                    </div>
                </div>

                </div>

                <div class="qt-mail-editor-wrap">
                    <textarea id="quotation_email_body">{!! $defaultBody !!}</textarea>
                </div>

                <div class="qt-mail-attachments">
                    <div class="qt-mail-attachments-head">
                        <span>Attachments</span>
                        <i class="bi bi-chevron-down text-muted"></i>
                    </div>
                    <div class="qt-mail-attachment-item" title="{{ $pdfFilename }}" id="qtMailAttachmentItem">
                        <div class="pdf-icon">PDF</div>
                        <div class="name" id="qtMailAttachmentName">{{ $pdfFilename }}</div>
                    </div>
                </div>
                </div>

                <div class="qt-mail-footer">
                    <div class="attach-badge">
                        <i class="bi bi-paperclip"></i> 1 Attachments
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary btn-sm px-4" id="confirmSendQuotationEmailBtn" onclick="submitQuotationEmail()">
                            <span id="quotationEmailBtnText"><i class="bi bi-send me-1"></i> Send</span>
                            <span id="quotationEmailSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('partials.quill_editor_assets')
<script>
(function () {
    const quotationEmailEnabled = @json($emailEnabled ?? false);
    const quotationEmailDynamic = @json($dynamic ?? false);
    const quotationEmailTemplatePayloads = @json($emailTemplatePayloads ?? []);
    let quotationEmailCurrentQuotationId = @json(!$dynamic && isset($quotation) ? $quotation->id : null);
    let qtMailEditorReady = false;
    let qtMailPendingBody = '';
    let qtMailEditorResizeObserver = null;
    const qtMailModalSizes = ['sm', 'md', 'lg', 'xl'];
    let qtMailModalSize = 'md';
    const qtMailComposeStyle = @json($composeEditorStyle ?? 'body { font-family: Verdana, sans-serif; font-size: 14px; color: #111827; margin: 8px; }');

    function resizeMailEditorForModal() {
        const wrap = document.querySelector('#sendQuotationEmailModal .qt-mail-editor-wrap');
        const quill = window.CrmQuill ? CrmQuill.get('quotation_email_body') : null;
        if (!wrap || !quill) return;
        const minHeight = parseInt(getComputedStyle(wrap).getPropertyValue('--qt-mail-editor-min-height'), 10) || 220;
        const height = Math.max(minHeight, wrap.clientHeight - 70);
        quill.root.style.minHeight = height + 'px';
    }

    function bindMailEditorAutoResize() {
        const wrap = document.querySelector('#sendQuotationEmailModal .qt-mail-editor-wrap');
        if (!wrap || qtMailEditorResizeObserver || typeof ResizeObserver === 'undefined') {
            return;
        }

        qtMailEditorResizeObserver = new ResizeObserver(function () {
            window.requestAnimationFrame(resizeMailEditorForModal);
        });
        qtMailEditorResizeObserver.observe(wrap);
    }

    function updateMailZoomButtons(size) {
        const zoomOutBtn = document.getElementById('qtMailZoomOutBtn');
        const zoomInBtn = document.getElementById('qtMailZoomInBtn');
        const index = qtMailModalSizes.indexOf(size);
        if (zoomOutBtn) zoomOutBtn.disabled = index <= 0;
        if (zoomInBtn) zoomInBtn.disabled = index >= qtMailModalSizes.length - 1;
    }

    function applyMailModalSize(size, persist) {
        const modalEl = document.getElementById('sendQuotationEmailModal');
        if (!modalEl) return;
        if (qtMailModalSizes.indexOf(size) === -1) {
            size = 'md';
        }
        qtMailModalSize = size;
        qtMailModalSizes.forEach(function (item) {
            modalEl.classList.toggle('qt-mail-size-' + item, item === size);
        });
        if (persist !== false) {
            try {
                sessionStorage.setItem('qtMailModalSize', size);
            } catch (e) { /* ignore */ }
        }
        updateMailZoomButtons(size);
        window.requestAnimationFrame(function () {
            resizeMailEditorForModal();
            setTimeout(resizeMailEditorForModal, 120);
        });
    }

    function stepMailModalSize(direction) {
        const index = qtMailModalSizes.indexOf(qtMailModalSize);
        const next = index + direction;
        if (next < 0 || next >= qtMailModalSizes.length) return;
        applyMailModalSize(qtMailModalSizes[next]);
    }

    function initMailModalSizeControls() {
        let saved = 'md';
        try {
            saved = sessionStorage.getItem('qtMailModalSize') || 'md';
        } catch (e) { /* ignore */ }
        applyMailModalSize(saved, false);

        document.getElementById('qtMailZoomOutBtn')?.addEventListener('click', function () {
            stepMailModalSize(-1);
        });
        document.getElementById('qtMailZoomInBtn')?.addEventListener('click', function () {
            stepMailModalSize(1);
        });

        const modalEl = document.getElementById('sendQuotationEmailModal');
        if (modalEl && !modalEl.__qtMailResizeBound) {
            modalEl.__qtMailResizeBound = true;
            modalEl.addEventListener('shown.bs.modal', function () {
                bindMailEditorAutoResize();
                resizeMailEditorForModal();
                setTimeout(resizeMailEditorForModal, 150);
            });
            window.addEventListener('resize', function () {
                if (modalEl.classList.contains('show')) {
                    resizeMailEditorForModal();
                }
            });
        }
    }

    function setQuotationEmailBody(content) {
        const html = content || '';
        qtMailPendingBody = html;
        const ta = document.getElementById('quotation_email_body');
        if (ta) ta.value = html;
        if (window.CrmQuill && CrmQuill.get('quotation_email_body')) {
            CrmQuill.setHtml('quotation_email_body', html);
        }
    }

    function resetQuotationEmailToChips() {
        document.querySelectorAll('#qtMailToWrap .qt-mail-chip').forEach(function (chip) {
            chip.remove();
        });
        const toInput = document.getElementById('qtMailToInput');
        if (toInput) toInput.value = '';
    }

    function applyQuotationEmailTemplate(templateId) {
        if (quotationEmailDynamic && quotationEmailCurrentQuotationId) {
            const composeUrl = @json(url('/quotations')) + '/' + quotationEmailCurrentQuotationId + '/email-compose?template_id=' + encodeURIComponent(templateId || '');
            document.getElementById('quotation-email-alert').innerHTML = '<div class="qt-mail-notice"><span class="spinner-border spinner-border-sm me-2"></span>Loading template...</div>';
            fetch(composeUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        document.getElementById('quotation-email-alert').innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">' + (data.error || 'Unable to load template.') + '</div>';
                        return;
                    }
                    document.getElementById('quotation_email_subject').value = data.subject || '';
                    qtMailPendingBody = data.body || '';
                    setQuotationEmailBody(qtMailPendingBody);
                    document.getElementById('quotation-email-alert').innerHTML = '';
                })
                .catch(function () {
                    document.getElementById('quotation-email-alert').innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">Network error while loading template.</div>';
                });
            return;
        }

        const payload = quotationEmailTemplatePayloads[templateId] || quotationEmailTemplatePayloads[String(templateId)];
        if (!payload) return;
        document.getElementById('quotation_email_subject').value = payload.subject || '';
        qtMailPendingBody = payload.body || '';
        setQuotationEmailBody(qtMailPendingBody);
    }

    function populateQuotationEmailComposer(data) {
        quotationEmailCurrentQuotationId = data.quotation_id || quotationEmailCurrentQuotationId;
        const templateRow = document.getElementById('qtMailTemplateRow');
        if (data.templates && Array.isArray(data.templates)) {
            if (templateRow) {
                templateRow.classList.toggle('d-none', data.templates.length === 0);
            }
            const select = document.getElementById('quotationEmailTemplateSelect');
            if (select) {
                select.innerHTML = '';
                data.templates.forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name + (item.is_default ? ' (Default)' : '');
                    if (String(data.template_id || '') === String(item.id)) opt.selected = true;
                    select.appendChild(opt);
                });
            }
        } else if (data.template_id) {
            const select = document.getElementById('quotationEmailTemplateSelect');
            if (select) select.value = data.template_id;
        }
        document.getElementById('quotation_email_send_url').value = data.send_url || '';
        document.getElementById('sendQuotationEmailModalLabel').textContent = data.modal_title || 'Quotation';
        document.getElementById('quotation_email_subject').value = data.subject || '';
        document.getElementById('qtMailAttachmentName').textContent = data.pdf_filename || 'quotation.pdf';
        const attachmentItem = document.getElementById('qtMailAttachmentItem');
        if (attachmentItem) attachmentItem.setAttribute('title', data.pdf_filename || 'quotation.pdf');
        document.getElementById('quotation-email-alert').innerHTML = '';
        document.getElementById('qtMailCcInput').value = '';
        document.getElementById('qtMailBccInput').value = '';
        document.getElementById('qtMailCcRow')?.classList.add('d-none');
        document.getElementById('qtMailBccRow')?.classList.add('d-none');
        resetQuotationEmailToChips();
        if (data.to_email) {
            addToChip(data.to_email, data.to_label || data.to_email);
        }
        qtMailPendingBody = data.body || '';
        setQuotationEmailBody(qtMailPendingBody);
    }

    window.handleIndexQuotationEmailClick = function (btn) {
        if (!quotationEmailEnabled) {
            showEmailDisabledSwal();
            return;
        }

        const quotationId = btn.getAttribute('data-quotation-id');
        if (!quotationId) return;
        quotationEmailCurrentQuotationId = quotationId;

        const composeUrl = @json(url('/quotations')) + '/' + quotationId + '/email-compose';
        const modalEl = document.getElementById('sendQuotationEmailModal');
        if (!modalEl) return;

        document.getElementById('quotation-email-alert').innerHTML = '<div class="qt-mail-notice"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading composer...</div>';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        initQuotationEmailEditor();

        fetch(composeUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (data.disabled) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                showEmailDisabledSwal(data.error);
                return;
            }
            if (!response.ok || !data.success) {
                document.getElementById('quotation-email-alert').innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">' + (data.error || 'Unable to load email composer.') + '</div>';
                return;
            }
            populateQuotationEmailComposer(data);
        })
        .catch(function () {
            document.getElementById('quotation-email-alert').innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">Network error while loading email composer.</div>';
        });
    };

    window.handleQuotationEmailClick = function () {
        if (!quotationEmailEnabled) {
            showEmailDisabledSwal();
            return;
        }
        const modalEl = document.getElementById('sendQuotationEmailModal');
        if (!modalEl) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        initQuotationEmailEditor();
        // Ensure selected template body is visible (editor can open blank after prior load race)
        const select = document.getElementById('quotationEmailTemplateSelect');
        const templateId = select ? select.value : '';
        if (templateId) {
            const payload = quotationEmailTemplatePayloads[templateId] || quotationEmailTemplatePayloads[String(templateId)];
            if (payload && payload.body) {
                setQuotationEmailBody(payload.body);
            } else if (document.getElementById('quotation_email_body')?.value) {
                setQuotationEmailBody(document.getElementById('quotation_email_body').value);
            }
        }
    };

    window.showEmailDisabledSwal = function (msg) {
        const message = msg || 'Email integration is disabled. Configure and enable Email Settings first.';
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Email Disabled', text: message, confirmButtonColor: '#2563eb' });
        } else {
            alert(message);
        }
    };

    function initQuotationEmailEditor() {
        if (!window.CrmQuill) return;
        if (qtMailEditorReady && CrmQuill.get('quotation_email_body')) {
            if (qtMailPendingBody) {
                setQuotationEmailBody(qtMailPendingBody);
            }
            resizeMailEditorForModal();
            return;
        }
        const seed = qtMailPendingBody || document.getElementById('quotation_email_body')?.value || '';
        CrmQuill.create('quotation_email_body', {
            sizeClass: 'crm-quill-medium',
            wrapClass: 'qet-email-editor',
            disableTables: true,
            emailLayout: true,
            html: seed,
            contentStyle: qtMailComposeStyle,
            placeholder: 'Write your quotation email...'
        });
        qtMailEditorReady = true;
        if (seed) {
            setQuotationEmailBody(seed);
        }
        qtMailPendingBody = seed;
        bindMailEditorAutoResize();
        resizeMailEditorForModal();
        setTimeout(resizeMailEditorForModal, 120);
    }

    function collectToEmails() {
        const emails = [];
        document.querySelectorAll('#qtMailToWrap .qt-mail-chip').forEach(function (chip) {
            const email = chip.getAttribute('data-email');
            if (email) emails.push(email);
        });
        return emails.join(', ');
    }

    function addToChip(email, label) {
        email = (email || '').trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return false;
        const existing = collectToEmails().split(',').map(function (e) { return e.trim(); }).filter(Boolean);
        if (existing.indexOf(email) !== -1) return true;

        const chip = document.createElement('span');
        chip.className = 'qt-mail-chip';
        chip.setAttribute('data-email', email);
        chip.innerHTML = '<span>' + (label || email) + '</span><button type="button" class="qt-mail-chip-remove" aria-label="Remove">&times;</button>';
        const input = document.getElementById('qtMailToInput');
        document.getElementById('qtMailToWrap').insertBefore(chip, input);
        return true;
    }

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('qt-mail-chip-remove')) {
            e.target.closest('.qt-mail-chip')?.remove();
        }
    });

    const toInput = document.getElementById('qtMailToInput');
    if (toInput) {
        toInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const val = toInput.value.replace(',', '').trim();
                if (addToChip(val, val)) toInput.value = '';
            }
        });
        toInput.addEventListener('blur', function () {
            const val = toInput.value.trim();
            if (val && addToChip(val, val)) toInput.value = '';
        });
    }

    document.getElementById('qtMailToggleCc')?.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('qtMailCcRow')?.classList.toggle('d-none');
    });
    document.getElementById('qtMailToggleBcc')?.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('qtMailBccRow')?.classList.toggle('d-none');
    });

    document.getElementById('quotationEmailTemplateSelect')?.addEventListener('change', function () {
        applyQuotationEmailTemplate(this.value);
    });

    window.submitQuotationEmail = function () {
        if (!quotationEmailEnabled) {
            showEmailDisabledSwal();
            return;
        }

        const alertBox = document.getElementById('quotation-email-alert');
        const btn = document.getElementById('confirmSendQuotationEmailBtn');
        const btnText = document.getElementById('quotationEmailBtnText');
        const spinner = document.getElementById('quotationEmailSpinner');
        alertBox.innerHTML = '';

        const to = collectToEmails();
        const cc = (document.getElementById('qtMailCcInput')?.value || '').trim();
        const bcc = (document.getElementById('qtMailBccInput')?.value || '').trim();
        const subject = (document.getElementById('quotation_email_subject')?.value || '').trim();
        let body = '';

        if (window.CrmQuill && CrmQuill.get('quotation_email_body')) {
            CrmQuill.sync('quotation_email_body');
            body = CrmQuill.getHtml('quotation_email_body');
        } else {
            body = document.getElementById('quotation_email_body')?.value || '';
        }

        if (!to) {
            alertBox.innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">Please add at least one recipient.</div>';
            return;
        }
        if (!subject) {
            alertBox.innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">Subject is required.</div>';
            return;
        }
        if (!body || body.replace(/<[^>]+>/g, '').trim() === '') {
            alertBox.innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">Please enter email description.</div>';
            return;
        }

        btn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('to', to);
        formData.append('cc', cc);
        formData.append('bcc', bcc);
        formData.append('subject', subject);
        formData.append('body', body);
        const templateId = document.getElementById('quotationEmailTemplateSelect')?.value || '';
        if (templateId) {
            formData.append('template_id', templateId);
        }

        const sendUrl = document.getElementById('quotation_email_send_url')?.value || @json($sendEmailUrl);
        if (!sendUrl) {
            alertBox.innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">Missing send URL.</div>';
            btn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');
            return;
        }

        fetch(sendUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData
        })
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));
            btn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');

            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Sent', text: data.message || 'Quotation email sent.', confirmButtonColor: '#2563eb' });
                }
                const modalEl = document.getElementById('sendQuotationEmailModal');
                bootstrap.Modal.getInstance(modalEl)?.hide();
            } else if (data.disabled) {
                bootstrap.Modal.getInstance(document.getElementById('sendQuotationEmailModal'))?.hide();
                showEmailDisabledSwal(data.error);
            } else {
                alertBox.innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">' + (data.error || 'Failed to send email.') + '</div>';
            }
        })
        .catch(function () {
            btn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');
            alertBox.innerHTML = '<div class="qt-mail-notice" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">Network error while sending email.</div>';
        });
    };

    initMailModalSizeControls();
})();
</script>
