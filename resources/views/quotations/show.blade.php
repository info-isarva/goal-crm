@extends('layouts.app')

@section('content')
@php
    $selectedFyId = session('selected_financial_year', null);
    $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
    $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;

    $billing = $quotation->addresses->where('address_type', 'billing')->first();
    $shipping = $quotation->addresses->where('address_type', 'shipping')->first();
    $hasShippingOnView = $shipping && (
        trim((string) ($shipping->address_line1 ?? '')) !== ''
        || trim((string) ($shipping->city ?? '')) !== ''
        || trim((string) ($shipping->state ?? '')) !== ''
    );

    $hasRichContent = function ($html) {
        $text = trim(html_entity_decode(strip_tags((string) ($html ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\x{00A0}/u', ' ', $text);
        return trim((string) $text) !== '';
    };
    $hasProjectDetail = $hasRichContent($quotation->project_detail ?? '');
    $hasDescription = $hasRichContent($quotation->description ?? '');
    $hasTerms = $hasRichContent($quotation->terms_conditions ?? '');
    $company = $company ?? null;
    $hasBankDetails = (bool) ($quotation->show_bank_details ?? false)
        && $company
        && (
            trim((string) ($company->bank_account_number ?? '')) !== ''
            || trim((string) ($company->bank_name ?? '')) !== ''
            || trim((string) ($company->bank_branch ?? '')) !== ''
            || trim((string) ($company->bank_address ?? '')) !== ''
            || trim((string) ($company->bank_ifsc ?? '')) !== ''
        );

    $showItemTaxColumn = $quotation->items->contains(function ($item) {
        return (bool) optional($item->product)->tax_status;
    });
    $showOverallTax = (float) ($quotation->tax ?? 0) > 0;
    $showAdjustment = (float) ($quotation->adjustment ?? 0) != 0;
    $overallDiscPct = $quotation->discount_percent !== null
        ? (float) $quotation->discount_percent
        : ((float) ($quotation->subtotal ?? 0) > 0 ? round(((float) ($quotation->discount ?? 0) / (float) $quotation->subtotal) * 100, 2) : 0);
    $currencySymbol = $quotation->currency_symbol ?? '₹';

    $statusClass = match ((string) $quotation->status) {
        'Draft' => 'secondary',
        'Negotiation' => 'info',
        'Delivered' => 'primary',
        'On Hold' => 'warning',
        'Confirmed' => 'success',
        'Closed Won' => 'success',
        'Closed Lost' => 'danger',
        default => 'secondary',
    };

    $contactName = trim(
        (optional($quotation->people)->first_name ?? '') . ' ' .
        (optional($quotation->people)->last_name ?? '')
    );
    $validUntil = $quotation->valid_until
        ? \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y')
        : '—';

    $formatAddress = function ($addr) {
        if (!$addr) return '—';
        $parts = array_filter([
            $addr->flat_no ?? null,
            $addr->address_line1 ?? null,
            $addr->city ?? null,
            $addr->state ?? null,
            $addr->pincode ?? null,
            $addr->country ?? null,
        ], fn ($v) => trim((string) $v) !== '');
        return $parts ? implode(', ', $parts) : '—';
    };
@endphp
<style>
    .qt-view-hero {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
    }
    .qt-view-hero .qt-quote-no {
        font-size: 1.35rem;
        font-weight: 700;
        color: #111827;
    }
    .qt-view-hero .qt-grand {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1d4ed8;
    }
    .qt-view-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 0;
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .qt-view-section .qt-acc-header {
        font-weight: 700;
        font-size: 0.95rem;
        color: #111827;
        margin: 0;
        padding: 1rem 1.25rem;
        border: 0;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        user-select: none;
        background: #fff;
        width: 100%;
        text-align: left;
    }
    .qt-view-section .qt-acc-header:hover {
        background: #f8fafc;
    }
    .qt-view-section .qt-acc-header i.qt-acc-icon {
        color: #3b82f6;
    }
    .qt-view-section .qt-acc-chevron {
        margin-left: auto;
        color: #6b7280;
        transition: transform 0.2s ease;
        font-size: 1rem;
    }
    .qt-view-section.is-collapsed .qt-acc-chevron {
        transform: rotate(-90deg);
    }
    .qt-view-section.is-collapsed .qt-acc-header {
        border-bottom: 0;
    }
    .qt-view-section .qt-acc-body {
        padding: 1.1rem 1.5rem 1.25rem;
    }
    .qt-view-section .qt-acc-body .row-line {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        margin-bottom: 0.65rem;
        font-size: 0.9rem;
        color: #4b5563;
        line-height: 1.45;
    }
    .qt-view-section .qt-acc-body .row-line:last-child {
        margin-bottom: 0;
    }
    .qt-view-section .qt-acc-body .row-line .qt-info-icon {
        flex: 0 0 1.125rem;
        width: 1.125rem;
        text-align: center;
        color: #3b82f6;
        line-height: 1.45;
        font-size: 0.95rem;
    }
    .qt-view-section .qt-acc-body .row-line .qt-info-text {
        flex: 1;
        min-width: 0;
        word-break: break-word;
        color: #374151;
    }
    .qt-view-section .qt-acc-body .row-line .qt-info-text a {
        color: #2563eb;
        text-decoration: none;
    }
    .qt-view-section .qt-acc-body .row-line .qt-info-text a:hover {
        text-decoration: underline;
    }
    .qt-related-cards .qt-view-section {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .qt-related-cards .qt-acc-body {
        flex: 1;
    }
    .qt-view-section.is-collapsed .qt-acc-body {
        display: none;
    }
    .qt-kv {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 0.35rem 0.75rem;
        margin-bottom: 0.65rem;
        font-size: 0.925rem;
    }
    .qt-kv .k {
        color: #6b7280;
        font-weight: 500;
    }
    .qt-kv .v {
        color: #111827;
        font-weight: 500;
        word-break: break-word;
    }
    .qt-addr-box {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        height: 100%;
    }
    .qt-addr-box h6 {
        border: 0;
        padding: 0;
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #111827;
    }
    .qt-addr-box p {
        margin: 0;
        color: #374151;
        line-height: 1.55;
        font-size: 0.925rem;
    }
    .qt-rich-content {
        line-height: 1.55;
        word-break: break-word;
        color: #1f2937;
    }
    .qt-rich-content h1,
    .qt-rich-content h2,
    .qt-rich-content h3,
    .qt-rich-content h4,
    .qt-rich-content h5,
    .qt-rich-content h6 {
        margin: 0.75rem 0 0.4rem;
        font-weight: 600;
        line-height: 1.3;
    }
    .qt-rich-content p { margin: 0 0 0.5rem; }
    .qt-rich-content ol,
    .qt-rich-content ul {
        margin: 0 0 0.75rem;
        padding-left: 1.25rem;
    }
    .qt-rich-content li { margin-bottom: 0.25rem; }
    .qt-rich-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 0.75rem 0;
    }
    .qt-rich-content td,
    .qt-rich-content th {
        border: 1px solid #cbd5e1;
        padding: 6px 10px;
        vertical-align: top;
    }
    .qt-items-table thead th {
        background: #f8fafc;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .qt-items-table td,
    .qt-items-table th {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .qt-totals-table {
        max-width: 360px;
        margin-left: auto;
    }
    .qt-totals-table th {
        background: #f8fafc;
        font-weight: 600;
        width: 55%;
    }
    .qt-totals-table .grand th,
    .qt-totals-table .grand td {
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 700;
        font-size: 1rem;
    }
    .qt-side-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.1rem 1.25rem;
        height: 100%;
    }
    .qt-side-card .title {
        font-size: 0.8rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6b7280;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }
    .qt-side-card .row-line {
        display: flex;
        gap: 0.55rem;
        align-items: flex-start;
        color: #4b5563;
        font-size: 0.9rem;
        margin-bottom: 0.55rem;
    }
    .qt-side-card .row-line i {
        color: #3b82f6;
        margin-top: 0.15rem;
    }
    .qt-title-banner {
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        color: #1d4ed8;
        font-weight: 600;
        margin-bottom: 1rem;
    }
    .qt-actions-dropdown .btn-actions-toggle {
        width: 36px;
        height: 36px;
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
</style>

<div class="container-fluid p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="mb-0">Quotation Details</h5>
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <a href="{{ route('quotations.index') }}" class="btn btn-light btn-sm border">&laquo; Back</a>
            @if(!$isHistorical)
                <div class="dropdown qt-actions-dropdown">
                    <button class="btn btn-light btn-sm border btn-actions-toggle" type="button"
                            id="quotationActionsMenuBtn" data-bs-toggle="dropdown" aria-expanded="false"
                            title="Actions">
                        <i class="bi bi-three-dots-vertical fs-5 text-secondary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="quotationActionsMenuBtn">
                        @if(auth()->user()->canSendQuotationEmail())
                        <li>
                            <button type="button" class="dropdown-item" onclick="closeQuotationActionsMenu(); handleQuotationEmailClick();">
                                <i class="bi bi-envelope text-primary"></i> Send Mail
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item" onclick="closeQuotationActionsMenu(); handleQuotationGmailClick();">
                                <i class="bi bi-google text-danger"></i> Send via Gmail
                            </button>
                        </li>
                        @endif
                        @if(auth()->user()->canSendQuotationWhatsApp())
                        <li>
                            <button type="button" class="dropdown-item" onclick="closeQuotationActionsMenu(); handleQuotationWhatsappClick();">
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
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('quotations.edit', $quotation->id) }}">
                                <i class="bi bi-pencil text-secondary"></i> Edit
                            </a>
                        </li>
                        <li>
                            <form id="quotationDeleteForm" action="{{ route('quotations.destroy', $quotation->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                                <button type="button" id="quotationDeleteBtn"
                                    class="dropdown-item text-danger delete-quotation-btn w-100 text-start @if(!auth()->user()->hasCrmPermission('delete_crm_quotations_guard')) disabled @endif"
                                    data-quote-id="{{ $quotation->quote_no }}">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                </form>
                        </li>
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- Hero summary --}}
    <div class="qt-view-hero">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <div class="qt-quote-no">{{ $quotation->quote_no }}</div>
                    <span class="badge bg-{{ $statusClass }}">{{ $quotation->status }}</span>
                    @if($quotation->proposal_version)
                        <span class="badge bg-light text-dark border">v{{ (int) $quotation->proposal_version }}</span>
                    @endif
                </div>
                <div class="text-muted mb-1">{{ $quotation->subject ?: '—' }}</div>
                <div class="small text-secondary">
                    Valid until <strong>{{ $validUntil }}</strong>
                    · Owned by <strong>{{ optional($quotation->owner)->name ?? '—' }}</strong>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="small text-secondary mb-1">Grand Total</div>
                <div class="qt-grand">{{ format_smart_money($quotation->grand_total ?? 0, $quotation->currency_symbol ?? '₹') }}</div>
            </div>
        </div>
                </div>

    {{-- Quotation Information --}}
    <div class="qt-view-section">
        <button type="button" class="qt-acc-header" aria-expanded="true">
            <i class="bi bi-file-earmark-text qt-acc-icon"></i>
            <span>Quotation Information</span>
            <i class="bi bi-chevron-down qt-acc-chevron"></i>
        </button>
        <div class="qt-acc-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="qt-kv"><div class="k">Subject</div><div class="v">{{ $quotation->subject ?: '—' }}</div></div>
                    <div class="qt-kv"><div class="k">Owner</div><div class="v">{{ optional($quotation->owner)->name ?? '—' }}</div></div>
                    <div class="qt-kv"><div class="k">Stage</div><div class="v"><span class="badge bg-{{ $statusClass }}">{{ $quotation->status }}</span></div></div>
                    <div class="qt-kv"><div class="k">Valid Until</div><div class="v">{{ $validUntil }}</div></div>
                    <div class="qt-kv">
                        <div class="k">Created</div>
                        <div class="v">
                            {{ optional($quotation->createdBy)->name ?? '—' }}
                            <div class="small text-muted">{{ optional($quotation->created_at)->format('d M Y, h:i A') }}</div>
                </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="qt-kv"><div class="k">Quote No.</div><div class="v">{{ $quotation->quote_no }}</div></div>
                    <div class="qt-kv"><div class="k">Company</div><div class="v">{{ optional($quotation->organization)->name ?? '—' }}</div></div>
                    <div class="qt-kv"><div class="k">Contact</div><div class="v">{{ $contactName !== '' ? $contactName : '—' }}</div></div>
                    <div class="qt-kv"><div class="k">Deal</div><div class="v">{{ optional($quotation->deal)->title ?? '—' }}</div></div>
                    <div class="qt-kv">
                        <div class="k">Modified</div>
                        <div class="v">
                            {{ optional($quotation->updatedBy)->name ?? '—' }}
                            <div class="small text-muted">{{ optional($quotation->updated_at)->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>
                </div>
            </div>
                                    </div>
                                </div>

    {{-- Address --}}
    <div class="qt-view-section">
        <button type="button" class="qt-acc-header" aria-expanded="true">
            <i class="bi bi-geo-alt qt-acc-icon"></i>
            <span>Address Information</span>
            <i class="bi bi-chevron-down qt-acc-chevron"></i>
        </button>
        <div class="qt-acc-body">
            <div class="row g-3">
                <div class="col-md-{{ $hasShippingOnView ? '6' : '12' }}">
                    <div class="qt-addr-box">
                        <h6>Billing Address</h6>
                        <p>{{ $formatAddress($billing) }}</p>
                                        </div>
                                        </div>
                @if($hasShippingOnView)
                                                <div class="col-md-6">
                    <div class="qt-addr-box">
                        <h6>Shipping Address</h6>
                        <p>{{ $formatAddress($shipping) }}</p>
                                                </div>
                                            </div>
                @endif
                                    </div>
                                </div>
                            </div>

    {{-- Project Detail --}}
    @if($hasProjectDetail)
    <div class="qt-view-section">
        <button type="button" class="qt-acc-header" aria-expanded="true">
            <i class="bi bi-clipboard-check qt-acc-icon"></i>
            <span>Project Details</span>
            <i class="bi bi-chevron-down qt-acc-chevron"></i>
        </button>
        <div class="qt-acc-body">
            <div class="qt-rich-content">
                {!! $quotation->project_detail !!}
            </div>
        </div>
                        </div>
    @endif

    {{-- Products & Summary --}}
    <div class="qt-view-section">
        <button type="button" class="qt-acc-header" aria-expanded="true">
            <i class="bi bi-box-seam qt-acc-icon"></i>
            <span>Products &amp; Summary</span>
            <i class="bi bi-chevron-down qt-acc-chevron"></i>
        </button>
        <div class="qt-acc-body">
            @if(!empty($quotation->quotation_title))
                <div class="qt-title-banner">{{ $quotation->quotation_title }}</div>
            @endif

                @if($quotation->items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered qt-items-table mb-3">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Discount</th>
                                @if($showItemTaxColumn)
                                <th class="text-end">Tax</th>
                                @endif
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quotation->items as $index => $item)
                                @php
                                    $lineAmount = (float) ($item->amount ?? 0);
                                    if ($lineAmount <= 0) {
                                        $lineAmount = ((float) ($item->quantity ?? 0)) * ((float) ($item->price ?? 0));
                                    }
                                    $discountAmt = (float) ($item->discount ?? 0);
                                    $discountPct = $item->discount_percent !== null
                                        ? (float) $item->discount_percent
                                        : ($lineAmount > 0 ? round(($discountAmt / $lineAmount) * 100, 2) : 0);
                                    $isTaxable = (bool) optional($item->product)->tax_status;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product->product_name ?? '—' }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ format_smart_money($item->price, $currencySymbol) }}</td>
                                    <td class="text-end">{{ format_smart_money($item->amount, $currencySymbol) }}</td>
                                    <td class="text-end">
                                        @if($discountAmt > 0)
                                            {{ format_smart_money($discountAmt, $currencySymbol) }}
                                            <span class="text-muted small">({{ format_smart_number($discountPct) }}%)</span>
                                        @else
                                            {{ format_smart_money(0, $currencySymbol) }}
                                        @endif
                                    </td>
                                    @if($showItemTaxColumn)
                                    <td class="text-end">
                                        @if($isTaxable && (float) ($item->tax ?? 0) > 0)
                                            {{ format_smart_number($item->tax) }}%
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td class="text-end fw-semibold">{{ format_smart_money($item->total, $currencySymbol) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <table class="table table-bordered qt-totals-table mb-0">
                    <tr>
                        <th>Subtotal</th>
                        <td class="text-end">{{ format_smart_money($quotation->subtotal, $currencySymbol) }}</td>
                    </tr>
                    @if((float) ($quotation->discount ?? 0) > 0 || $overallDiscPct > 0)
                    <tr>
                        <th>Discount</th>
                        <td class="text-end">
                            {{ format_smart_money($quotation->discount, $currencySymbol) }}
                            @if($overallDiscPct > 0)
                                <span class="text-muted small">({{ format_smart_number($overallDiscPct) }}%)</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                    @if($showOverallTax)
                    <tr>
                        <th>Tax</th>
                        <td class="text-end">{{ format_smart_number($quotation->tax) }}%</td>
                    </tr>
                    @endif
                    @if($showAdjustment)
                    <tr>
                        <th>Adjustment</th>
                        <td class="text-end">{{ format_smart_money($quotation->adjustment, $currencySymbol) }}</td>
                    </tr>
                    @endif
                    <tr class="grand">
                        <th>Grand Total</th>
                        <td class="text-end">{{ format_smart_money($quotation->grand_total, $currencySymbol) }}</td>
                    </tr>
                </table>
            @else
                <div class="text-muted">No products added.</div>
            @endif
        </div>
    </div>

    {{-- Notes: Description / Bank / Terms --}}
    @if($hasDescription || $hasBankDetails || $hasTerms)
    <div class="qt-view-section">
        <button type="button" class="qt-acc-header" aria-expanded="true">
            <i class="bi bi-journal-text qt-acc-icon"></i>
            <span>Notes</span>
            <i class="bi bi-chevron-down qt-acc-chevron"></i>
        </button>
        <div class="qt-acc-body">
            @if($hasDescription)
                <div class="mb-3">
                    <div class="fw-semibold mb-2">Description / Remarks</div>
                    <div class="qt-rich-content">{!! $quotation->description !!}</div>
                </div>
            @endif

            @if($hasBankDetails)
                <div class="mb-3">
                    <div class="fw-semibold mb-2">Bank Details</div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-2">
                            <thead>
                                <tr>
                                    <th>Account Number</th>
                                    <th>Bank Name</th>
                                    <th>Branch</th>
                                    <th>Bank Address</th>
                                    <th>IFSC/NEFT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $company->bank_account_number ?: '—' }}</td>
                                    <td>{{ $company->bank_name ?: '—' }}</td>
                                    <td>{{ $company->bank_branch ?: '—' }}</td>
                                    <td>{{ $company->bank_address ?: '—' }}</td>
                                    <td>{{ $company->bank_ifsc ?: '—' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($company->email))
                        <div class="text-secondary small">
                            For any queries, contact
                            <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                        </div>
                    @endif
                </div>
            @endif

            @if($hasTerms)
                <div>
                    <div class="fw-semibold mb-2">Terms and Conditions</div>
                    <div class="qt-rich-content">{!! $quotation->terms_conditions !!}</div>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- WhatsApp Send Logs --}}
    <div class="qt-view-section">
        <button type="button" class="qt-acc-header" aria-expanded="true">
            <i class="bi bi-whatsapp qt-acc-icon text-success"></i>
            <span>WhatsApp Send Logs</span>
            <span class="badge bg-light text-dark border ms-2">{{ $quotation->whatsappSendLogs->count() }}</span>
            <i class="bi bi-chevron-down qt-acc-chevron"></i>
        </button>
        <div class="qt-acc-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="small text-muted">WhatsApp delivery history for this quotation</div>
                <a href="{{ route('quotations.whatsapp_send_logs', ['search' => $quotation->quote_no]) }}" class="btn btn-sm btn-light border">View all WhatsApp logs</a>
            </div>
            @if($quotation->whatsappSendLogs->isEmpty())
                <div class="text-muted small py-2">No WhatsApp sends recorded yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Number</th>
                                <th>Message</th>
                                <th>Sent By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quotation->whatsappSendLogs as $log)
                                <tr>
                                    <td class="small text-nowrap">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                    <td class="small">{{ $log->recipient ?: '—' }}</td>
                                    <td class="small">
                                        @if($log->message_preview)<div class="text-muted">{{ Str::limit($log->message_preview, 100) }}</div>@endif
                                        @if($log->error_message)<div class="text-danger">{{ Str::limit($log->error_message, 100) }}</div>@endif
                                    </td>
                                    <td class="small">{{ optional($log->sender)->name ?? '—' }}</td>
                                    <td>
                                        @if($log->isSuccess())
                                            <span class="badge bg-success">Sent</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Email Send Logs --}}
    <div class="qt-view-section">
        <button type="button" class="qt-acc-header" aria-expanded="true">
            <i class="bi bi-envelope qt-acc-icon text-primary"></i>
            <span>Email Send Logs</span>
            <span class="badge bg-light text-dark border ms-2">{{ $quotation->emailSendLogs->count() }}</span>
            <i class="bi bi-chevron-down qt-acc-chevron"></i>
        </button>
        <div class="qt-acc-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="small text-muted">Email delivery history for this quotation</div>
                <a href="{{ route('quotations.email_send_logs', ['search' => $quotation->quote_no]) }}" class="btn btn-sm btn-light border">View all email logs</a>
            </div>
            @if($quotation->emailSendLogs->isEmpty())
                <div class="text-muted small py-2">No email sends recorded yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>To</th>
                                <th>Subject / Preview</th>
                                <th>Sent By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quotation->emailSendLogs as $log)
                                <tr>
                                    <td class="small text-nowrap">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                    <td class="small">
                                        {{ $log->recipient ?: '—' }}
                                        @if($log->cc)<div class="text-muted">Cc: {{ $log->cc }}</div>@endif
                                    </td>
                                    <td class="small">
                                        @if($log->subject)<div class="fw-semibold">{{ $log->subject }}</div>@endif
                                        @if($log->from_address)<div class="text-muted">From: {{ $log->from_address }}</div>@endif
                                        @if($log->message_preview)<div class="text-muted">{{ Str::limit($log->message_preview, 100) }}</div>@endif
                                        @if(!empty($log->meta['email_template_name']))
                                            <div class="text-muted">Template: {{ $log->meta['email_template_name'] }}</div>
                                        @endif
                                        @if($log->error_message)<div class="text-danger">{{ Str::limit($log->error_message, 100) }}</div>@endif
                                    </td>
                                    <td class="small">{{ optional($log->sender)->name ?? '—' }}</td>
                                    <td>
                                        @if($log->isSuccess())
                                            <span class="badge bg-success">Sent</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                            </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                @endif
            </div>
    </div>

    {{-- Related Company / Deal --}}
    <div class="row g-3 qt-related-cards">
        <div class="col-md-6 d-flex">
            <div class="qt-view-section mb-0 flex-fill">
                <button type="button" class="qt-acc-header" aria-expanded="true">
                    <i class="bi bi-buildings qt-acc-icon"></i>
                    <span>Company</span>
                    <i class="bi bi-chevron-down qt-acc-chevron"></i>
                </button>
                <div class="qt-acc-body">
                    <div class="row-line">
                        <i class="bi bi-buildings qt-info-icon"></i>
                        <span class="qt-info-text">{{ optional($quotation->organization)->name ?? '—' }}</span>
                    </div>
                    <div class="row-line">
                        <i class="bi bi-geo-alt qt-info-icon"></i>
                        <span class="qt-info-text">
                            @if($quotation->organization)
                                @php
                                    $orgLocation = array_filter([
                                        $quotation->organization->address ?? null,
                                        $quotation->organization->city ?? null,
                                        $quotation->organization->state ?? null,
                                        $quotation->organization->country ?? null,
                                    ], fn ($v) => trim((string) $v) !== '');
                                @endphp
                                {{ $orgLocation ? implode(', ', $orgLocation) : '—' }}
                            @else
                                —
                            @endif
                        </span>
                        </div>
                    <div class="row-line">
                        <i class="bi bi-globe qt-info-icon"></i>
                        <span class="qt-info-text">
                            @if($quotation->organization && $quotation->organization->website)
                                <a href="{{ $quotation->organization->website }}" target="_blank" rel="noopener">{{ $quotation->organization->website }}</a>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 d-flex">
            <div class="qt-view-section mb-0 flex-fill">
                <button type="button" class="qt-acc-header" aria-expanded="true">
                    <i class="bi bi-briefcase qt-acc-icon"></i>
                    <span>Deal</span>
                    <i class="bi bi-chevron-down qt-acc-chevron"></i>
                </button>
                <div class="qt-acc-body">
                    <div class="row-line">
                        <i class="bi bi-briefcase qt-info-icon"></i>
                        <span class="qt-info-text">{{ optional($quotation->deal)->title ?? '—' }}</span>
                    </div>
                    <div class="row-line">
                        <i class="bi bi-bar-chart-steps qt-info-icon"></i>
                        <span class="qt-info-text">{{ optional($quotation->deal)->stage ?? '—' }}</span>
                    </div>
                    <div class="row-line">
                        <i class="bi bi-currency-rupee qt-info-icon"></i>
                        <span class="qt-info-text">{{ optional($quotation->deal)->amount ? format_smart_number($quotation->deal->amount) : '—' }}</span>
                        </div>
                    <div class="row-line">
                        <i class="bi bi-calendar-check qt-info-icon"></i>
                        <span class="qt-info-text">
                            @if(optional($quotation->deal)->close_date)
                                {{ \Carbon\Carbon::parse($quotation->deal->close_date)->format('d M Y') }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $defaultWaNumber = optional($quotation->people)->mobile
        ?: (optional($quotation->people)->phone ?? '');
@endphp

@include('quotations.partials.send_email_composer', [
    'quotation' => $quotation,
    'company' => $company,
    'emailEnabled' => $emailEnabled ?? false,
    'emailSetting' => $emailSetting ?? null,
    'emailTemplates' => $emailTemplates ?? collect(),
    'defaultEmailTemplateId' => optional($defaultEmailTemplate ?? null)->id,
    'emailTemplatePayloads' => $emailTemplatePayloads ?? [],
    'sendEmailUrl' => route('quotations.send_email', $quotation->id),
])

@include('quotations.partials.send_gmail_composer', [
    'quotation' => $quotation,
    'company' => $company,
    'gmailEnabled' => auth()->user()->canSendQuotationEmail(),
    'emailTemplates' => $emailTemplates ?? collect(),
    'defaultEmailTemplateId' => optional($defaultEmailTemplate ?? null)->id,
    'emailTemplatePayloads' => $emailTemplatePayloads ?? [],
])

{{-- Send Quotation WhatsApp Modal --}}
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
                    Sends the approved short professional quotation template with PDF attachment
                    <strong>({{ $quotation->quote_no }})</strong> to the number below.
                </p>
                <div class="mb-3">
                    <label for="quotation_whatsapp_number" class="form-label fw-semibold">WhatsApp Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="text" class="form-control" id="quotation_whatsapp_number"
                            value="{{ $defaultWaNumber }}"
                            placeholder="e.g. +919876543210" required>
                    </div>
                    <div class="form-text">Include country code (e.g. +91).</div>
                </div>
                <div class="alert alert-info border-0 small mb-2 py-2 px-3">
                    <div class="fw-semibold mb-1"><i class="bi bi-check-circle me-1"></i> Currently applied format</div>
                    <div class="font-monospace text-dark">{{ $whatsappSetting->quotation_message_format ?? \App\Models\WhatsappSetting::defaultQuotationMessageFormat() }}</div>
                </div>
                <div class="alert alert-light border small mb-0">
                    <strong>Preview for this quotation:</strong><br>
                    {{ \App\Models\WhatsappSetting::renderQuotationMessageFormat(
                        $whatsappSetting->quotation_message_format ?? '',
                        \App\Models\WhatsappSetting::quotationMessageVariables($quotation, $company)['variables']
                    ) }}
                </div>
                        </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="confirmSendQuotationWhatsappBtn" onclick="submitQuotationWhatsapp()">
                    <span id="quotationWaBtnText"><i class="bi bi-send me-1"></i> Send</span>
                    <span id="quotationWaSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                    </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.qt-view-section .qt-acc-header').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var section = btn.closest('.qt-view-section');
            if (!section) return;
            var collapsed = section.classList.toggle('is-collapsed');
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
    });

    const deleteBtn = document.getElementById('quotationDeleteBtn');
    if (deleteBtn && typeof Swal !== 'undefined') {
        deleteBtn.addEventListener('click', function (event) {
            event.preventDefault();
            if (deleteBtn.classList.contains('disabled')) return;
            const form = document.getElementById('quotationDeleteForm');
            if (!form) return;
            const name = deleteBtn.getAttribute('data-quote-id') || '';
            const message = name
                ? 'Are you sure you want to delete "' + name + '"? This action cannot be undone.'
                : 'Are you sure you want to delete this quotation? This action cannot be undone.';
            Swal.fire({
                title: 'Delete confirmation',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    }
});

const quotationWhatsappEnabled = @json($whatsappEnabled ?? false);

function closeQuotationActionsMenu() {
    const btn = document.getElementById('quotationActionsMenuBtn');
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

function handleQuotationWhatsappClick() {
    if (!quotationWhatsappEnabled) {
        showWhatsappDisabledSwal();
        return;
    }

    const modalEl = document.getElementById('sendQuotationWhatsappModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function submitQuotationWhatsapp() {
    if (!quotationWhatsappEnabled) {
        const modalEl = document.getElementById('sendQuotationWhatsappModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        showWhatsappDisabledSwal();
        return;
    }

    const phoneInput = document.getElementById('quotation_whatsapp_number');
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

    btn.disabled = true;
    btnText.classList.add('d-none');
    spinner.classList.remove('d-none');

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('whatsapp_number', phone);

    fetch('{{ route("quotations.send_whatsapp", $quotation->id) }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async (response) => {
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
</script>
@endsection
