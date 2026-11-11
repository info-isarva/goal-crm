@php
    $dynamic = $dynamic ?? false;
    $gmailEnabled = $gmailEnabled ?? auth()->user()->canSendQuotationEmail();
    $emailTemplates = $emailTemplates ?? collect();
    $defaultEmailTemplateId = $defaultEmailTemplateId ?? optional($emailTemplates->firstWhere('is_default', true))->id ?? optional($emailTemplates->first())->id;
    $emailTemplatePayloads = $emailTemplatePayloads ?? [];
    $userEmail = trim((string) (auth()->user()->email ?? ''));
    $userName = trim((string) (auth()->user()->name ?? '')) ?: config('app.name');
    $connectUrl = (!$dynamic && isset($quotation))
        ? route('google.gmail.connect', ['return' => route('quotations.show', $quotation->id) . '?open_gmail=1'])
        : route('google.gmail.connect', ['return' => url()->current()]);

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
            $contactLabel = $defaultToName !== '' ? $defaultToName : ($defaultToEmail ?: 'Customer');
            $senderName = optional($quotation->owner)->name ?? optional(auth()->user())->name ?? $userName;
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
        $sendGmailUrl = route('quotations.send_gmail', $quotation->id);
        $gmailComposeUrl = route('quotations.gmail_compose', $quotation->id);
    } else {
        $defaultToEmail = '';
        $defaultToName = '';
        $pdfFilename = 'quotation.pdf';
        $defaultSubject = '';
        $defaultBody = '';
        $composeEditorStyle = app(\App\Services\EmailComposeSettingsService::class)->editorContentStyle(auth()->user());
        $modalTitle = 'Quotation';
        $sendGmailUrl = '';
        $gmailComposeUrl = '';
    }
@endphp

<style>
.qt-gmail-modal .modal-dialog {
    max-width: 920px;
    width: calc(100% - 2rem);
}
.qt-gmail-modal .modal-content {
    border: 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .18);
}
.qt-gmail-header {
    background: linear-gradient(135deg, #ea4335 0%, #c5221f 100%);
    color: #fff;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.qt-gmail-header .title {
    font-weight: 600;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.qt-gmail-header button {
    border: 0;
    background: rgba(255,255,255,.15);
    color: #fff;
    width: 30px;
    height: 30px;
    border-radius: 50%;
}
.qt-gmail-body {
    background: #fff;
    max-height: min(78vh, 760px);
    overflow-y: auto;
}
.qt-gmail-connect {
    padding: 28px 24px;
    text-align: center;
}
.qt-gmail-connect .icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #fce8e6;
    color: #ea4335;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 12px;
}
.qt-gmail-toolbar {
    padding: 10px 16px;
    border-bottom: 1px solid #eef2f6;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    background: #fafbfc;
    flex-wrap: wrap;
}
.qt-gmail-from {
    font-size: 13px;
    color: #4b5563;
    flex: 1;
    min-width: 180px;
}
.qt-gmail-from strong { color: #111827; }
.qt-gmail-template-pick {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    background: #fff;
    border: 1px solid #dbe3ee;
    border-radius: 8px;
    padding: 6px 10px;
}
.qt-gmail-template-label {
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
.qt-gmail-template-select {
    border: 0;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #1e3a8a;
    min-width: 180px;
    max-width: 280px;
    padding: 2px 4px;
    cursor: pointer;
    outline: none;
}
.qt-gmail-template-manage {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    color: #64748b;
    text-decoration: none;
}
.qt-gmail-template-manage:hover {
    background: #f1f5f9;
    color: #ea4335;
}
.qt-gmail-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 16px;
    border-bottom: 1px solid #eef2f6;
}
.qt-gmail-row .label {
    width: 52px;
    color: #6b7280;
    font-size: 13px;
    padding-top: 8px;
    flex-shrink: 0;
}
.qt-gmail-row .field { flex: 1; min-width: 0; }
.qt-gmail-chip-input {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    min-height: 38px;
    border: 1px solid #dbe3ee;
    border-radius: 8px;
    padding: 4px 8px;
    background: #fff;
}
.qt-gmail-chip-input input {
    border: 0;
    outline: 0;
    flex: 1;
    min-width: 140px;
    font-size: 13px;
    padding: 4px;
}
.qt-gmail-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 2px 8px 2px 10px;
    font-size: 12px;
}
.qt-gmail-chip-remove {
    border: 0;
    background: transparent;
    color: #64748b;
    line-height: 1;
    font-size: 16px;
    padding: 0;
}
.qt-gmail-input {
    width: 100%;
    border: 1px solid #dbe3ee;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
}
.qt-gmail-editor-wrap {
    min-height: 240px;
    padding: 0 8px 8px;
}
.qt-gmail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor table {
    width: 100% !important;
    max-width: 100% !important;
    table-layout: auto !important;
    border-collapse: collapse;
}
.qt-gmail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor td,
.qt-gmail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor th {
    white-space: normal !important;
    word-break: normal !important;
    min-width: 120px;
    vertical-align: top;
}
.qt-gmail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor img {
    max-width: 100%;
    height: auto;
}
.qt-gmail-editor-wrap .crm-quill-wrap.qet-email-editor .ql-editor.ql-blank::before {
    display: none !important;
}
.qt-gmail-attachment {
    margin: 8px 16px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8fafc;
}
.qt-gmail-attachment .pdf {
    background: #fee2e2;
    color: #b91c1c;
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
    padding: 8px 10px;
}
.qt-gmail-footer {
    padding: 12px 16px;
    border-top: 1px solid #eef2f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    background: #fff;
}
.qt-gmail-extra a {
    color: #2563eb;
    text-decoration: none;
    font-size: 12px;
    margin-left: 8px;
}
</style>

<div class="modal fade qt-gmail-modal" id="sendQuotationGmailModal" tabindex="-1" aria-labelledby="sendQuotationGmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="qt-gmail-header">
                <div class="title" id="sendQuotationGmailModalLabel">
                    <i class="bi bi-google"></i> Send via Gmail — <span id="qtGmailModalQuote">{{ $modalTitle }}</span>
                </div>
                <button type="button" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="qt-gmail-body">
                <div id="qtGmailConnectPanel" class="qt-gmail-connect d-none">
                    <div class="icon"><i class="bi bi-envelope-at"></i></div>
                    <h6 class="fw-semibold mb-2">Connect your Gmail account</h6>
                    <p class="text-muted small mb-2">Authorize Gmail once to send quotation PDFs directly from your Gmail account inside CRM.</p>
                    @if(!empty($userEmail))
                        <p class="text-muted small mb-3" id="qtGmailConnectEmailHint">
                            You can connect only the Gmail account that matches your CRM login email
                            <strong>{{ $userEmail }}</strong>.
                        </p>
                    @else
                        <p class="text-muted small mb-3" id="qtGmailConnectEmailHint"></p>
                    @endif
                    <div id="qtGmailConnectAlert" class="mb-3"></div>
                    <button type="button" class="btn btn-danger btn-sm px-4" id="qtGmailConnectBtn"
                            data-connect-url="{{ $connectUrl }}" data-crm-email="{{ $userEmail }}">
                        <i class="bi bi-google me-1"></i> Connect Gmail
                    </button>
                </div>

                <div id="qtGmailComposePanel">
                    <div class="qt-gmail-toolbar">
                        <div class="qt-gmail-from">
                            <span class="text-muted">From</span>
                            <strong id="qtGmailFromName">{{ $userName }}</strong>
                            <span class="text-muted" id="qtGmailFromAddress">@if($userEmail)&lt;{{ $userEmail }}&gt;@endif</span>
                        </div>
                        <div class="qt-gmail-template-pick @if($dynamic && $emailTemplates->isEmpty()) d-none @endif" id="qtGmailTemplateRow">
                            <label for="quotationGmailTemplateSelect" class="qt-gmail-template-label">
                                <i class="bi bi-layout-text-window-reverse"></i> Template
                            </label>
                            <select id="quotationGmailTemplateSelect" class="qt-gmail-template-select" title="Choose email design template">
                                @foreach($emailTemplates as $emailTemplate)
                                    <option value="{{ $emailTemplate->id }}" @selected((int) $defaultEmailTemplateId === (int) $emailTemplate->id)>
                                        {{ $emailTemplate->name }}@if($emailTemplate->is_default) (Default)@endif
                                    </option>
                                @endforeach
                            </select>
                            @if(auth()->user()->crm_role_type === 0)
                                <a href="{{ route('quotation_email_templates.index') }}" target="_blank" class="qt-gmail-template-manage" title="Manage templates">
                                    <i class="bi bi-gear"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div id="qt-gmail-alert" class="px-3 pt-2"></div>
                    <input type="hidden" id="quotation_gmail_send_url" value="{{ $sendGmailUrl }}">
                    <input type="hidden" id="quotation_gmail_compose_url" value="{{ $gmailComposeUrl }}">

                    <div class="qt-gmail-row">
                        <div class="label">To</div>
                        <div class="field">
                            <div class="qt-gmail-chip-input" id="qtGmailToWrap">
                                @if($defaultToEmail)
                                <span class="qt-gmail-chip" data-email="{{ $defaultToEmail }}">
                                    <span>{{ $defaultToName !== '' ? $defaultToName . ' (' . $defaultToEmail . ')' : $defaultToEmail }}</span>
                                    <button type="button" class="qt-gmail-chip-remove" aria-label="Remove">&times;</button>
                                </span>
                                @endif
                                <input type="text" id="qtGmailToInput" placeholder="Add recipient and press Enter">
                            </div>
                        </div>
                        <div class="qt-gmail-extra">
                            <a href="#" id="qtGmailToggleCc">Cc</a>
                            <a href="#" id="qtGmailToggleBcc">Bcc</a>
                        </div>
                    </div>

                    <div class="qt-gmail-row d-none" id="qtGmailCcRow">
                        <div class="label">Cc</div>
                        <div class="field">
                            <input type="text" class="qt-gmail-input" id="qtGmailCcInput" placeholder="Cc recipients (comma separated)">
                        </div>
                    </div>

                    <div class="qt-gmail-row d-none" id="qtGmailBccRow">
                        <div class="label">Bcc</div>
                        <div class="field">
                            <input type="text" class="qt-gmail-input" id="qtGmailBccInput" placeholder="Bcc recipients (comma separated)">
                        </div>
                    </div>

                    <div class="qt-gmail-row">
                        <div class="label">Subject</div>
                        <div class="field">
                            <input type="text" class="qt-gmail-input" id="quotation_gmail_subject" value="{{ $defaultSubject }}">
                        </div>
                    </div>

                    <div class="qt-gmail-editor-wrap">
                        <textarea id="quotation_gmail_body">{!! $defaultBody !!}</textarea>
                    </div>

                    <div class="qt-gmail-attachment" id="qtGmailAttachmentItem" title="{{ $pdfFilename }}">
                        <div class="pdf">PDF</div>
                        <div class="small fw-semibold text-truncate" id="qtGmailAttachmentName">{{ $pdfFilename }}</div>
                    </div>
                </div>
            </div>

            <div class="qt-gmail-footer">
                <span class="small text-muted"><i class="bi bi-paperclip"></i> 1 attachment</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm px-4" id="confirmSendQuotationGmailBtn" onclick="submitQuotationGmail()">
                        <span id="quotationGmailBtnText"><i class="bi bi-send me-1"></i> Send via Gmail</span>
                        <span id="quotationGmailSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('partials.quill_editor_assets')
<script>
(function () {
    const quotationGmailEnabled = @json($gmailEnabled ?? false);
    const quotationGmailDynamic = @json($dynamic ?? false);
    const quotationGmailTemplatePayloads = @json($emailTemplatePayloads ?? []);
    const quotationGmailLoginEmail = @json($userEmail ?? '');
    const gmailConnectSessionError = @json(session('error'));
    let quotationGmailCurrentId = @json(!$dynamic && isset($quotation) ? $quotation->id : null);
    let qtGmailEditorReady = false;
    let qtGmailPendingBody = @json($defaultBody ?? '');
    const qtGmailComposeStyle = @json($composeEditorStyle ?? 'body { font-family: Verdana, sans-serif; font-size: 14px; }');

    function qtGmailAlert(html) {
        document.getElementById('qt-gmail-alert').innerHTML = html || '';
    }

    function qtGmailConnectAlert(html) {
        const el = document.getElementById('qtGmailConnectAlert');
        if (el) el.innerHTML = html || '';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showGmailConnectError(message) {
        if (!message) return;
        qtGmailConnectAlert('<div class="alert alert-danger py-2 small mb-0">' + escapeHtml(message) + '</div>');
    }

    function bindGmailConnectButton() {
        const btn = document.getElementById('qtGmailConnectBtn');
        if (!btn || btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';

        btn.addEventListener('click', function () {
            const connectUrl = btn.getAttribute('data-connect-url') || '';
            const crmEmail = btn.getAttribute('data-crm-email') || quotationGmailLoginEmail || '';

            if (!connectUrl) return;

            if (!crmEmail) {
                showGmailConnectError('Your CRM account does not have an email address. Update your profile before connecting Gmail.');
                return;
            }

            const htmlMessage = 'You can connect only the Gmail account that matches your CRM login email:<br><strong>'
                + escapeHtml(crmEmail) + '</strong><br><br>Continue to Google sign-in?';

            const fire = (typeof Swal !== 'undefined' && Swal.fire)
                ? Swal.fire({
                    title: 'Connect Gmail',
                    html: htmlMessage,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Continue',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#ea4335',
                    reverseButtons: true
                })
                : Promise.resolve({ isConfirmed: window.confirm('Connect Gmail using your CRM login email: ' + crmEmail + '?') });

            fire.then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = connectUrl;
                }
            });
        });
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function addGmailToChip(email, label) {
        const wrap = document.getElementById('qtGmailToWrap');
        const input = document.getElementById('qtGmailToInput');
        if (!wrap || !email || !isValidEmail(email)) return;
        if (wrap.querySelector('.qt-gmail-chip[data-email="' + email + '"]')) return;
        const chip = document.createElement('span');
        chip.className = 'qt-gmail-chip';
        chip.dataset.email = email;
        chip.innerHTML = '<span>' + (label || email) + '</span><button type="button" class="qt-gmail-chip-remove" aria-label="Remove">&times;</button>';
        wrap.insertBefore(chip, input);
    }

    function resetGmailToChips() {
        document.querySelectorAll('#qtGmailToWrap .qt-gmail-chip').forEach(function (chip) { chip.remove(); });
    }

    function collectGmailToEmails() {
        return Array.from(document.querySelectorAll('#qtGmailToWrap .qt-gmail-chip')).map(function (chip) {
            return chip.dataset.email || '';
        }).filter(Boolean);
    }

    function bindGmailChipInput() {
        const input = document.getElementById('qtGmailToInput');
        const wrap = document.getElementById('qtGmailToWrap');
        if (!input || !wrap || input.dataset.bound === '1') return;
        input.dataset.bound = '1';

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const value = input.value.trim().replace(/[,;]+$/, '');
                if (value) {
                    addGmailToChip(value, value);
                    input.value = '';
                }
            }
        });

        wrap.addEventListener('click', function (e) {
            if (e.target.classList.contains('qt-gmail-chip-remove')) {
                e.target.closest('.qt-gmail-chip')?.remove();
            }
        });
    }

    function setGmailConnectState(connected, connectUrl, crmEmail) {
        const connectPanel = document.getElementById('qtGmailConnectPanel');
        const composePanel = document.getElementById('qtGmailComposePanel');
        const sendBtn = document.getElementById('confirmSendQuotationGmailBtn');
        const connectBtn = document.getElementById('qtGmailConnectBtn');
        const emailHint = document.getElementById('qtGmailConnectEmailHint');

        if (connectPanel) connectPanel.classList.toggle('d-none', connected);
        if (composePanel) composePanel.classList.toggle('d-none', !connected);
        if (sendBtn) sendBtn.disabled = !connected;
        if (connectBtn && connectUrl) connectBtn.setAttribute('data-connect-url', connectUrl);
        if (connectBtn && crmEmail) connectBtn.setAttribute('data-crm-email', crmEmail);
        if (emailHint && crmEmail) {
            emailHint.innerHTML = 'You can connect only the Gmail account that matches your CRM login email <strong>'
                + escapeHtml(crmEmail) + '</strong>.';
        }
        if (connected) qtGmailConnectAlert('');
    }

    function setQuotationGmailBody(content) {
        const html = content || '';
        qtGmailPendingBody = html;
        const ta = document.getElementById('quotation_gmail_body');
        if (ta) ta.value = html;
        if (window.CrmQuill && CrmQuill.get('quotation_gmail_body')) {
            CrmQuill.setHtml('quotation_gmail_body', html);
        }
    }

    function getQuotationGmailBody() {
        if (window.CrmQuill && CrmQuill.get('quotation_gmail_body')) {
            return CrmQuill.getHtml('quotation_gmail_body');
        }
        return document.getElementById('quotation_gmail_body')?.value || '';
    }

    function initQuotationGmailEditor() {
        if (!window.CrmQuill) return;
        const seed = qtGmailPendingBody || document.getElementById('quotation_gmail_body')?.value || '';
        CrmQuill.create('quotation_gmail_body', {
            sizeClass: 'crm-quill-medium',
            wrapClass: 'qet-email-editor',
            disableTables: true,
            emailLayout: true,
            html: seed,
            contentStyle: qtGmailComposeStyle,
            placeholder: 'Write your quotation email...'
        });
        qtGmailEditorReady = true;
        if (seed) {
            setQuotationGmailBody(seed);
        }
    }

    function applyQuotationGmailTemplate(templateId) {
        if (quotationGmailDynamic && quotationGmailCurrentId) {
            const composeUrl = @json(url('/quotations')) + '/' + quotationGmailCurrentId + '/gmail-compose?template_id=' + encodeURIComponent(templateId || '');
            qtGmailAlert('<div class="alert alert-light border py-2 small mb-0"><span class="spinner-border spinner-border-sm me-2"></span>Loading template...</div>');
            fetch(composeUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        qtGmailAlert('<div class="alert alert-danger py-2 small mb-0">' + (data.error || 'Unable to load template.') + '</div>');
                        return;
                    }
                    document.getElementById('quotation_gmail_subject').value = data.subject || '';
                    setQuotationGmailBody(data.body || '');
                    qtGmailAlert('');
                })
                .catch(() => {
                    qtGmailAlert('<div class="alert alert-danger py-2 small mb-0">Network error while loading template.</div>');
                });
            return;
        }

        const payload = quotationGmailTemplatePayloads[templateId] || quotationGmailTemplatePayloads[String(templateId)];
        if (!payload) return;
        document.getElementById('quotation_gmail_subject').value = payload.subject || '';
        setQuotationGmailBody(payload.body || '');
    }

    function populateQuotationGmailComposer(data) {
        quotationGmailCurrentId = data.quotation_id || quotationGmailCurrentId;
        const templateRow = document.getElementById('qtGmailTemplateRow');
        if (data.templates && Array.isArray(data.templates)) {
            if (templateRow) {
                templateRow.classList.toggle('d-none', data.templates.length === 0);
            }
            const select = document.getElementById('quotationGmailTemplateSelect');
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
            const select = document.getElementById('quotationGmailTemplateSelect');
            if (select) select.value = data.template_id;
        }

        document.getElementById('quotation_gmail_send_url').value = data.send_url || '';
        document.getElementById('quotation_gmail_compose_url').value = data.compose_url || '';
        document.getElementById('qtGmailModalQuote').textContent = data.modal_title || data.pdf_filename || 'Quotation';
        document.getElementById('quotation_gmail_subject').value = data.subject || '';
        document.getElementById('qtGmailAttachmentName').textContent = data.pdf_filename || 'quotation.pdf';
        document.getElementById('qtGmailFromName').textContent = data.from_name || '';
        const fromAddr = data.from_address || '';
        const fromAddrEl = document.getElementById('qtGmailFromAddress');
        if (fromAddrEl) fromAddrEl.textContent = fromAddr ? '<' + fromAddr + '>' : '';
        document.getElementById('qtGmailCcInput').value = '';
        document.getElementById('qtGmailBccInput').value = '';
        document.getElementById('qtGmailCcRow')?.classList.add('d-none');
        document.getElementById('qtGmailBccRow')?.classList.add('d-none');
        qtGmailAlert('');
        resetGmailToChips();
        if (data.to_email) addGmailToChip(data.to_email, data.to_label || data.to_email);
        setQuotationGmailBody(data.body || '');
        setGmailConnectState(!!data.gmail_connected, data.connect_url, data.from_address || quotationGmailLoginEmail);
        bindGmailConnectButton();
        if (window.qtGmailPendingConnectError) {
            showGmailConnectError(window.qtGmailPendingConnectError);
            window.qtGmailPendingConnectError = null;
        }
        initQuotationGmailEditor();
    }

    window.handleQuotationGmailClick = function () {
        if (!quotationGmailEnabled) return;
        const modalEl = document.getElementById('sendQuotationGmailModal');
        if (!modalEl) return;
        bindGmailChipInput();
        initQuotationGmailEditor();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        const composeUrl = document.getElementById('quotation_gmail_compose_url')?.value
            || (quotationGmailCurrentId ? @json(url('/quotations')) + '/' + quotationGmailCurrentId + '/gmail-compose' : '');
        if (!composeUrl) return;

        const templateId = document.getElementById('quotationGmailTemplateSelect')?.value || '';
        const url = composeUrl + (composeUrl.indexOf('?') >= 0 ? '&' : '?') + 'template_id=' + encodeURIComponent(templateId);

        qtGmailAlert('<div class="alert alert-light border py-2 small mb-0"><span class="spinner-border spinner-border-sm me-2"></span>Loading Gmail composer...</div>');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    qtGmailAlert('<div class="alert alert-danger py-2 small mb-0">' + (data.error || 'Unable to load Gmail composer.') + '</div>');
                    return;
                }
                populateQuotationGmailComposer(data);
            })
            .catch(() => {
                qtGmailAlert('<div class="alert alert-danger py-2 small mb-0">Network error while loading Gmail composer.</div>');
            });
    };

    window.handleIndexQuotationGmailClick = function (btn) {
        if (!quotationGmailEnabled) return;
        const quotationId = btn.getAttribute('data-quotation-id');
        if (!quotationId) return;
        quotationGmailCurrentId = quotationId;

        const composeUrl = @json(url('/quotations')) + '/' + quotationId + '/gmail-compose';
        const modalEl = document.getElementById('sendQuotationGmailModal');
        if (!modalEl) return;

        qtGmailAlert('<div class="alert alert-light border py-2 small mb-0"><span class="spinner-border spinner-border-sm me-2"></span>Loading Gmail composer...</div>');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        bindGmailChipInput();
        initQuotationGmailEditor();

        fetch(composeUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    qtGmailAlert('<div class="alert alert-danger py-2 small mb-0">' + (data.error || 'Unable to load Gmail composer.') + '</div>');
                    return;
                }
                populateQuotationGmailComposer(data);
            })
            .catch(() => {
                qtGmailAlert('<div class="alert alert-danger py-2 small mb-0">Network error while loading Gmail composer.</div>');
            });
    };

    window.submitQuotationGmail = function () {
        if (!quotationGmailEnabled) return;

        const alertBox = document.getElementById('qt-gmail-alert');
        const btn = document.getElementById('confirmSendQuotationGmailBtn');
        const btnText = document.getElementById('quotationGmailBtnText');
        const spinner = document.getElementById('quotationGmailSpinner');
        const toEmails = collectGmailToEmails();
        const subject = (document.getElementById('quotation_gmail_subject')?.value || '').trim();
        const body = (getQuotationGmailBody() || '').trim();
        const templateId = document.getElementById('quotationGmailTemplateSelect')?.value || '';

        alertBox.innerHTML = '';
        if (!toEmails.length) {
            alertBox.innerHTML = '<div class="alert alert-warning py-2 small mb-0">Please add at least one recipient.</div>';
            return;
        }
        if (!subject || !body || body === '<p></p>' || body === '<p><br></p>') {
            alertBox.innerHTML = '<div class="alert alert-warning py-2 small mb-0">Subject and message are required.</div>';
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('to', toEmails.join(', '));
        formData.append('cc', document.getElementById('qtGmailCcInput')?.value || '');
        formData.append('bcc', document.getElementById('qtGmailBccInput')?.value || '');
        formData.append('subject', subject);
        formData.append('body', body);
        if (templateId) formData.append('template_id', templateId);

        const sendUrl = document.getElementById('quotation_gmail_send_url')?.value
            || (@json($sendGmailUrl) || (@json(url('/quotations')) + '/' + quotationGmailCurrentId + '/send-gmail'));

        btn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');

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

            if (data.needs_connect && data.connect_url) {
                setGmailConnectState(false, data.connect_url, quotationGmailLoginEmail);
                bindGmailConnectButton();
                alertBox.innerHTML = '<div class="alert alert-warning py-2 small mb-0">' + (data.error || 'Connect Gmail to continue.') + '</div>';
                return;
            }

            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('sendQuotationGmailModal'))?.hide();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Sent via Gmail', text: data.message || 'Quotation sent.', confirmButtonColor: '#ea4335' });
                }
                return;
            }

            alertBox.innerHTML = '<div class="alert alert-danger py-2 small mb-0">' + (data.error || 'Failed to send via Gmail.') + '</div>';
        })
        .catch(() => {
            btn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');
            alertBox.innerHTML = '<div class="alert alert-danger py-2 small mb-0">Network error while sending via Gmail.</div>';
        });
    };

    document.getElementById('qtGmailToggleCc')?.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('qtGmailCcRow')?.classList.toggle('d-none');
    });
    document.getElementById('qtGmailToggleBcc')?.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('qtGmailBccRow')?.classList.toggle('d-none');
    });
    document.getElementById('quotationGmailTemplateSelect')?.addEventListener('change', function () {
        applyQuotationGmailTemplate(this.value);
    });

    document.addEventListener('DOMContentLoaded', function () {
        bindGmailChipInput();
        bindGmailConnectButton();
        initQuotationGmailEditor();

        if (gmailConnectSessionError) {
            window.qtGmailPendingConnectError = gmailConnectSessionError;
        }

        const shouldOpenGmail = @json(
            session('open_gmail')
            || request()->boolean('open_gmail')
            || request()->query('open_gmail') === '1'
        );

        if (shouldOpenGmail && typeof handleQuotationGmailClick === 'function') {
            setTimeout(function () { handleQuotationGmailClick(); }, 300);
        }
    });
})();
</script>
