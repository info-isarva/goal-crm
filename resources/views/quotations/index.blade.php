@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{asset('css/leads.css') }}">
<style>
    .qt-actions-dropdown .btn-actions-toggle {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }
    .qt-actions-dropdown .dropdown-menu {
        min-width: 200px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.35rem 0;
    }
    .qt-actions-dropdown .dropdown-item {
        font-size: 0.875rem;
        padding: 0.55rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .qt-actions-dropdown .dropdown-item i {
        width: 18px;
        text-align: center;
    }
    .quotations-table-wrap.table-responsive {
        overflow: visible;
    }
    .quotations-table-wrap .table {
        overflow: visible;
    }
    .quotations-table-wrap .table td,
    .quotations-table-wrap .table th {
        overflow: visible;
    }
    .quotations-table-wrap tr.qt-row-actions-open {
        position: relative;
        z-index: 5;
    }
    .qt-actions-dropdown .dropdown-menu {
        z-index: 1060;
    }
</style>
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'quotationsPageSkeleton',
        'columns' => 8,
        'rows' => 10,
        'filterFields' => 3,
    ])

    <div class="row justify-content-center">
        <div class="col-12 col-lg-12">
            <div class="card mt-0">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <h4 class="mb-2 mb-md-0">Quotations</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('quotations.create') }}" class="btn btn-custom btn-sm"><i class="bi bi-plus"></i> Add Quotation</a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('quotations.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" name="search" class="form-control" placeholder="Search by Quotation Number" value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="Negotiation" {{ request('status') == 'Negotiation' ? 'selected' : '' }}>Negotiation</option>
                                    <option value="Delivered" {{ request('status') == 'Delivered' ? 'selected' : '' }}>Delivered</option>
                                    <option value="On Hold" {{ request('status') == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                                    <option value="Confirmed" {{ request('status') == 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                                    <option value="Closed Won" {{ request('status') == 'Closed Won' ? 'selected' : '' }}>Closed Won</option>
                                    <option value="Closed Lost" {{ request('status') == 'Closed Lost' ? 'selected' : '' }}>Closed Lost</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-custom" >Filter</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive quotations-table-wrap">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Quote No.</th>
                                    <th>Subject</th>
                                    <th>Deal Name</th>
                                    <th>Company Name</th>
                                    <th>Valid Until</th>
                                    <th>Status</th>
                                    <th>Grand Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Loop through quotations --}}
                               @if($quotations->count() > 0)
                                @forelse($quotations as $quotation)
                                    @php
                                        $waPhone = optional($quotation->people)->mobile
                                            ?: (optional($quotation->people)->phone ?? '');
                                    @endphp
                                    <tr>
                                        <td style="overflow:visible; position:relative;">
                                            <div class="dropdown qt-actions-dropdown">
                                                <button class="btn btn-light btn-sm border btn-actions-toggle" type="button"
                                                        data-bs-toggle="dropdown"
                                                        data-bs-popper-config='{"strategy":"fixed"}'
                                                        aria-expanded="false" title="Actions">
                                                    <i class="bi bi-three-dots-vertical fs-6 text-secondary"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-start shadow-sm">
                                                    @if(auth()->user()->canSendQuotationEmail())
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                            data-quotation-id="{{ $quotation->id }}"
                                                            onclick="closeQtListActionsMenu(this); handleIndexQuotationEmailClick(this);">
                                                            <i class="bi bi-envelope text-primary"></i> Send Mail
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                            data-quotation-id="{{ $quotation->id }}"
                                                            onclick="closeQtListActionsMenu(this); handleIndexQuotationGmailClick(this);">
                                                            <i class="bi bi-google text-danger"></i> Send via Gmail
                                                        </button>
                                                    </li>
                                                    @endif
                                                    @if(auth()->user()->canSendQuotationWhatsApp())
                                                    <li>
                                                        <button type="button" class="dropdown-item quotation-wa-btn"
                                                            data-quotation-id="{{ $quotation->id }}"
                                                            data-quote-no="{{ $quotation->quote_no }}"
                                                            data-phone="{{ $waPhone }}"
                                                            data-contact-name="{{ trim(optional($quotation->people)->full_name ?? optional($quotation->people)->first_name ?? 'Customer') }}"
                                                            data-company-name="{{ optional($company ?? null)->name ?? config('app.name') }}"
                                                            data-client-name="{{ optional($quotation->organization)->name ?: trim(optional($quotation->people)->full_name ?? optional($quotation->people)->first_name ?? 'Customer') }}"
                                                            data-amount="{{ format_smart_money($quotation->grand_total ?? 0, trim((string) ($quotation->currency_symbol ?? '')) ?: '₹') }}"
                                                            data-sender-name="{{ optional($quotation->owner)->name ?? auth()->user()->name ?? config('app.name') }}"
                                                            data-send-url="{{ route('quotations.send_whatsapp', $quotation->id) }}"
                                                            onclick="closeQtListActionsMenu(this); handleIndexQuotationWhatsappClick(this);">
                                                            <i class="bi bi-whatsapp text-success"></i> WhatsApp
                                                        </button>
                                                    </li>
                                                    @endif
                                                    @if(auth()->user()->hasCrmPermission('download_pdf_crm_quotations_guard'))
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('quotations.download', $quotation->id) }}" target="_blank">
                                                            <i class="bi bi-file-earmark-pdf text-danger"></i> PDF
                                                        </a>
                                                    </li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('quotations.show', $quotation->id) }}">
                                                            <i class="bi bi-eye text-primary"></i> View
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('quotations.edit', $quotation->id) }}">
                                                            <i class="bi bi-pencil text-secondary"></i> Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('quotations.destroy', $quotation->id) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button"
                                                                class="dropdown-item text-danger delete-quote-btn w-100 text-start"
                                                                data-quote-id="{{ $quotation->quote_no }}">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td><a href="{{ route('quotations.show', $quotation->id) }}">{{ $quotation->quote_no }}</a></td>
                                        <td>{{ $quotation->subject }}</td>
                                        <td>
                                            @if($quotation->deal)
                                                <a href="{{ route('deals.show', $quotation->deal->id) }}">{{ $quotation->deal->title }}</a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ optional($quotation->organization)->name }}</td>
                                        <td>{{ $quotation->valid_until }}</td>
                                        <td>{{ $quotation->status }}</td>
                                        <td>{{ format_smart_money($quotation->grand_total ?? 0, trim((string) ($quotation->currency_symbol ?? '')) ?: '₹') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No quotations found.</td>
                                    </tr>
                                @endforelse
                                @else
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No quotations found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $quotations->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('quotations.partials.send_email_composer', [
    'dynamic' => true,
    'emailEnabled' => $emailEnabled ?? false,
    'emailSetting' => $emailSetting ?? null,
])

@include('quotations.partials.send_gmail_composer', [
    'dynamic' => true,
    'gmailEnabled' => auth()->user()->canSendQuotationEmail(),
])

{{-- Shared Send Quotation WhatsApp Modal --}}
<div class="modal fade" id="sendQuotationWhatsappModal" tabindex="-1" aria-labelledby="sendQuotationWhatsappModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="sendQuotationWhatsappModalLabel">
                    <i class="bi bi-whatsapp text-success me-2"></i> Send Quotation via WhatsApp
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="quotation-wa-alert"></div>
                <p class="small text-muted mb-3">
                    Sends the approved quotation template with PDF attachment
                    <strong id="quotation-wa-quote-no"></strong> to the number below.
                </p>
                <input type="hidden" id="quotation_wa_send_url" value="">
                <div class="mb-3">
                    <label for="quotation_whatsapp_number" class="form-label fw-semibold">WhatsApp Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="text" class="form-control" id="quotation_whatsapp_number"
                            placeholder="e.g. +919876543210" required>
                    </div>
                    <div class="form-text">Include country code (e.g. +91).</div>
                </div>
                <div class="alert alert-info border-0 small mb-2 py-2 px-3">
                    <div class="fw-semibold mb-1"><i class="bi bi-check-circle me-1"></i> Currently applied format</div>
                    <div class="font-monospace text-dark">{{ $whatsappSetting->quotation_message_format ?? \App\Models\WhatsappSetting::defaultQuotationMessageFormat() }}</div>
                </div>
                <div class="alert alert-light border small mb-0" id="quotation-wa-message-preview">
                    <strong>Preview for this quotation:</strong><br>
                    <span id="quotation-wa-message-preview-text">Select a quotation to see the filled message preview.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="confirmSendQuotationWhatsappBtn" onclick="submitIndexQuotationWhatsapp()">
                    <span id="quotationWaBtnText"><i class="bi bi-send me-1"></i> Send</span>
                    <span id="quotationWaSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const quotationWhatsappEnabled = @json($whatsappEnabled ?? false);
    const quotationWaMessageFormat = @json($whatsappSetting->quotation_message_format ?? \App\Models\WhatsappSetting::defaultQuotationMessageFormat());
    const quotationWaDefaultFormat = @json(\App\Models\WhatsappSetting::defaultQuotationMessageFormat());

    function renderQuotationWaMessagePreview(format, vars) {
        const template = (format || '').trim() || quotationWaDefaultFormat;
        const map = {
            '{contact_name}': vars.contact_name || '',
            '{company_name}': vars.company_name || '',
            '{quote_no}': vars.quote_no || '',
            '{client_name}': vars.client_name || '',
            '{amount}': vars.amount || '',
            '{sender_name}': vars.sender_name || '',
            '[Name]': vars.contact_name || '',
            '[Company]': vars.company_name || '',
            '[Quote]': vars.quote_no || '',
            '[Client]': vars.client_name || '',
            '[Amount]': vars.amount || '',
            '[Sender]': vars.sender_name || '',
            '{{1}}': vars.contact_name || '',
            '{{2}}': vars.company_name || '',
            '{{3}}': vars.quote_no || '',
            '{{4}}': vars.client_name || '',
            '{{5}}': vars.amount || '',
            '{{6}}': vars.sender_name || '',
        };
        let text = template;
        Object.keys(map).forEach(function (key) {
            text = text.split(key).join(map[key]);
        });
        return text;
    }

    function closeQtListActionsMenu(el) {
        const dropdown = el?.closest('.dropdown');
        const btn = dropdown?.querySelector('[data-bs-toggle="dropdown"]');
        if (btn && typeof bootstrap !== 'undefined') {
            const instance = bootstrap.Dropdown.getInstance(btn);
            if (instance) instance.hide();
        }
    }

    function showWhatsappDisabledSwal(msg) {
        const message = msg || "WhatsApp Integration is currently disabled for the selected provider. Please turn on \"Enable Integration for Selected Provider\" and save settings to send messages.";
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Integration Disabled',
                text: message,
                confirmButtonColor: '#198754',
                confirmButtonText: 'OK'
            });
        } else {
            alert(message);
        }
    }

    function handleIndexQuotationWhatsappClick(btn) {
        if (!quotationWhatsappEnabled) {
            showWhatsappDisabledSwal();
            return;
        }

        const quoteNo = btn.getAttribute('data-quote-no') || '';
        const phone = btn.getAttribute('data-phone') || '';
        const sendUrl = btn.getAttribute('data-send-url') || '';

        document.getElementById('quotation-wa-quote-no').textContent = quoteNo ? '(' + quoteNo + ')' : '';
        document.getElementById('quotation_whatsapp_number').value = phone;
        document.getElementById('quotation_wa_send_url').value = sendUrl;
        document.getElementById('quotation-wa-alert').innerHTML = '';
        const previewEl = document.getElementById('quotation-wa-message-preview-text');
        if (previewEl) {
            previewEl.textContent = renderQuotationWaMessagePreview(quotationWaMessageFormat, {
                contact_name: btn.getAttribute('data-contact-name') || '',
                company_name: btn.getAttribute('data-company-name') || '',
                quote_no: quoteNo,
                client_name: btn.getAttribute('data-client-name') || '',
                amount: btn.getAttribute('data-amount') || '',
                sender_name: btn.getAttribute('data-sender-name') || '',
            });
        }

        const modalEl = document.getElementById('sendQuotationWhatsappModal');
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function submitIndexQuotationWhatsapp() {
        if (!quotationWhatsappEnabled) {
            const modalEl = document.getElementById('sendQuotationWhatsappModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            showWhatsappDisabledSwal();
            return;
        }

        const phoneInput = document.getElementById('quotation_whatsapp_number');
        const sendUrl = document.getElementById('quotation_wa_send_url').value;
        const alertBox = document.getElementById('quotation-wa-alert');
        const btn = document.getElementById('confirmSendQuotationWhatsappBtn');
        const btnText = document.getElementById('quotationWaBtnText');
        const spinner = document.getElementById('quotationWaSpinner');

        alertBox.innerHTML = '';
        const phone = (phoneInput.value || '').trim();
        if (!phone) {
            alertBox.innerHTML = '<div class="alert alert-warning py-2 small">Please enter a WhatsApp number.</div>';
            return;
        }
        if (!sendUrl) {
            alertBox.innerHTML = '<div class="alert alert-danger py-2 small">Missing send URL.</div>';
            return;
        }

        btn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('whatsapp_number', phone);

        fetch(sendUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async (response) => {
            const contentType = response.headers.get('content-type') || '';
            // Test mode dd() returns HTML — show helpful note
            if (!contentType.includes('application/json')) {
                const text = await response.text();
                btn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
                alertBox.innerHTML = '<div class="alert alert-info py-2 small">Test dump returned (check Network → Response). WhatsApp was not sent.</div>';
                console.log('WhatsApp test dump HTML length:', text.length);
                return;
            }

            const data = await response.json().catch(() => ({}));
            btn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');

            if (data.success) {
                alertBox.innerHTML = `<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i> ${data.message || 'Sent successfully.'}</div>`;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Sent', text: data.message || 'Quotation sent via WhatsApp.', confirmButtonColor: '#198754' });
                }
                setTimeout(() => {
                    const modalEl = document.getElementById('sendQuotationWhatsappModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }, 1200);
            } else if (data.disabled) {
                const modalEl = document.getElementById('sendQuotationWhatsappModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                showWhatsappDisabledSwal(data.error);
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i> ${data.error || 'Failed to send.'}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btnText.classList.remove('d-none');
            spinner.classList.add('d-none');
            alertBox.innerHTML = '<div class="alert alert-danger py-2 small">Network error while sending WhatsApp message.</div>';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.qt-actions-dropdown').forEach(function (dropdownEl) {
            dropdownEl.addEventListener('show.bs.dropdown', function () {
                dropdownEl.closest('tr')?.classList.add('qt-row-actions-open');
            });
            dropdownEl.addEventListener('hide.bs.dropdown', function () {
                dropdownEl.closest('tr')?.classList.remove('qt-row-actions-open');
            });
        });

        const deleteButtons = document.querySelectorAll('.delete-quote-btn');
        deleteButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const form = this.closest('form');

                if (!form) return;
                var name = button.getAttribute('data-quote-id') || '';
                var message = name ? 'Are you sure you want to delete "' + name + '"? This action cannot be undone.' : 'Are you sure you want to delete this record? This action cannot be undone.';

                Swal.fire({
                    title: 'Delete confirmation',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection
