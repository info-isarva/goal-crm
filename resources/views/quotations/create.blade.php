@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{asset('css/leads.css') }}">
@include('quotations.partials.rich_editor_assets')
@include('quotations.partials.wizard_styles')
<div class="container-fluid p-4">
    <div class="row ">
        <div class="col-12 ">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">Create Quotation</h5>
                    <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center ms-auto">
                        <a href="{{ route('quotations.index') }}" class="btn btn-light btn-sm">&laquo; Back</a>
                    </div>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ route('quotations.store') }}" id="quotationWizardForm">
                        @csrf

                        <div class="qt-wizard-stepper" id="qtWizardStepper">
                            <div class="qt-wizard-stepper-item is-active" data-step="0"><div class="qt-wizard-step-num">1</div><div class="qt-wizard-step-label">Quotation</div></div>
                            <div class="qt-wizard-stepper-item" data-step="1"><div class="qt-wizard-step-num">2</div><div class="qt-wizard-step-label">Address</div></div>
                            <div class="qt-wizard-stepper-item" data-step="2"><div class="qt-wizard-step-num">3</div><div class="qt-wizard-step-label">Products</div></div>
                            <div class="qt-wizard-stepper-item" data-step="3"><div class="qt-wizard-step-num">4</div><div class="qt-wizard-step-label">Description</div></div>
                            <div class="qt-wizard-stepper-item" data-step="4"><div class="qt-wizard-step-num">5</div><div class="qt-wizard-step-label">Notes</div></div>
                            <div class="qt-wizard-stepper-item" data-step="5"><div class="qt-wizard-step-num">6</div><div class="qt-wizard-step-label">Review</div></div>
                        </div>

                        <!-- Step 1: Quotation -->
                        <div class="qt-wizard-panel is-active" data-step="0">
                        <div class="qt-wizard-panel-title">Quotation Information</div>
                        <!-- Quote Information -->
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject', optional($prefillDeal ?? null)->title) }}" required>
                                        @error('subject')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="quotation_owner" class="form-label">Quotation Owner <span class="text-danger">*</span></label>
                                        <select class="form-select" id="quotation_owner" name="quotation_owner" required>
                                            <option value="">Select Owner</option>
                                            @foreach($users as $user)
                                            <option value="{{ $user->id }}" @if (isset($currentUser) && $currentUser->id == $user->id) selected @endif>{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('quotation_owner')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Quotation Stage <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Draft" {{ old('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                                            <option value="Negotiation" {{ old('status') == 'Negotiation' ? 'selected' : '' }}>Negotiation</option>
                                            <option value="Delivered" {{ old('status') == 'Delivered' ? 'selected' : '' }}>Delivered</option>
                                            <option value="On Hold" {{ old('status') == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                                            <option value="Confirmed" {{ old('status') == 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                                            <option value="Closed Won" {{ old('status') == 'Closed Won' ? 'selected' : '' }}>Closed Won</option>
                                            <option value="Closed Lost" {{ old('status') == 'Closed Lost' ? 'selected' : '' }}>Closed Lost</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="valid_until" class="form-label">Valid Until <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="valid_until" name="valid_until" value="{{ old('valid_until', now()->addDays(30)->format('Y-m-d')) }}" required>
                                        @error('valid_until')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Quotation Template</label>
                                        <input type="hidden" name="quotation_template_id" id="quotation_template_id"
                                               value="{{ $selectedQuotationTemplateId ?? $defaultQuotationTemplateId ?? '' }}">

                                        <div class="qt-selected-card border rounded p-3 bg-light" id="qtSelectedCard">
                                            <div class="d-flex gap-3 align-items-start">
                                                <div class="qt-thumb-wrap flex-shrink-0" id="qtSelectedThumbWrap">
                                                    @if(!empty($selectedQuotationTemplate))
                                                        <iframe class="qt-thumb-iframe"
                                                            src="{{ route('quotation_templates.picker_preview', $selectedQuotationTemplate) }}"
                                                            title="Selected template preview"
                                                            tabindex="-1"></iframe>
                                                    @else
                                                        <div class="qt-thumb-empty">No template</div>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                        <strong id="qtSelectedName">{{ optional($selectedQuotationTemplate ?? null)->name ?? 'No template' }}</strong>
                                                        <span class="badge bg-warning text-dark" id="qtSelectedDefaultBadge"
                                                              style="{{ !empty($selectedQuotationTemplate) && $selectedQuotationTemplate->is_default ? '' : 'display:none;' }}">Default</span>
                                                    </div>
                                                    <div class="small text-muted mb-2" id="qtSelectedMeta">
                                                        Version {{ optional($selectedQuotationTemplate ?? null)->version ?? '—' }}
                                                        @if(!empty(optional($selectedQuotationTemplate ?? null)->description))
                                                            · {{ \Illuminate\Support\Str::limit($selectedQuotationTemplate->description, 70) }}
                                                        @endif
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnChangeQuotationTemplate"
                                                            @disabled(($quotationTemplates ?? collect())->isEmpty())>
                                                        <i class="bi bi-grid-3x3-gap"></i> Change Template
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @error('quotation_template_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">Default template is pre-selected. Click Change Template to pick another layout.</small>
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="issue_date" class="form-label">Company Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-buildings"></i></span>
                                            <input type="text" class="form-control input-text" id="organization_id" name="organization_id"
                                                value="{{ old('organization_id', optional(optional($prefillDeal ?? null)->organization)->name) }}" placeholder="Search or Add Company"
                                                autocomplete="off" readonly required>
                                            <div class="dropdown-menu w-100 p-0" id="org-autocomplete-list"
                                                style="top:38px; min-width:100%;">
                                                <div class="p-2 border-bottom bg-white sticky-top" style="z-index:3;">
                                                    <input type="text" id="org-autocomplete-search"
                                                        class="form-control form-control-sm input-text" placeholder="Search Company..."
                                                        autocomplete="off">
                                                </div>
                                                <div id="org-autocomplete-scroll" style="max-height:200px; overflow-y:auto;"></div>
                                                <div id="org-autocomplete-new"
                                                    style="border-top:1px solid #eee; background:#fff; position:sticky; bottom:0; z-index:2;">
                                                </div>
                                            </div>
                                        </div>
                                        @error('organization_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="contact_name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control input-text" id="people_id" name="people_id"
                                                value="{{ old('people_id', trim((optional(optional($prefillDeal ?? null)->person)->first_name ?? '') . ' ' . (optional(optional($prefillDeal ?? null)->person)->last_name ?? ''))) }}" placeholder="Search or Add Contact Person"
                                                autocomplete="off" readonly required>
                                            <span class="input-group-text bg-light" id="person-new-label"
                                                style="display:none;"><span class="badge bg-success">New</span></span>
                                            <div class="dropdown-menu w-100 p-0" id="person-autocomplete-list"
                                                style="top:38px; min-width:100%;">
                                                <div class="p-2 border-bottom bg-white sticky-top" style="z-index:3;">
                                                    <input type="text" id="person-autocomplete-search"
                                                        class="form-control form-control-sm input-text" placeholder="Search Contact Person..."
                                                        autocomplete="off">
                                                </div>
                                                <div id="person-autocomplete-scroll" style="max-height:200px; overflow-y:auto;">
                                                </div>
                                                <div id="person-autocomplete-new"
                                                    style="border-top:1px solid #eee; background:#fff; position:sticky; bottom:0; z-index:2;">
                                                </div>
                                            </div>
                                            <input type="hidden" id="person_id" name="person_id" value="{{ old('person_id', optional($prefillDeal ?? null)->people_id) }}"> <!-- Hidden field to store selected person's organization ID -->
                                        </div>
                                        @error('people_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="deal_name" class="form-label">Deal Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                            <input type="text" class="form-control input-text" id="deal_id" name="deal_id"
                                                value="{{ old('deal_id', optional($prefillDeal ?? null)->title) }}" placeholder="Search or Add Deals"
                                                autocomplete="off" readonly required>
                                            <div class="dropdown-menu w-100 p-0" id="deal-autocomplete-list"
                                                style="top:38px; min-width:100%;">
                                                <div class="p-2 border-bottom bg-white sticky-top" style="z-index:3;">
                                                    <input type="text" id="deal-autocomplete-search"
                                                        class="form-control form-control-sm input-text" placeholder="Search Deals..."
                                                        autocomplete="off">
                                                </div>
                                                <div id="deal-autocomplete-scroll" style="max-height:200px; overflow-y:auto;">
                                                </div>
                                                <div id="deal-autocomplete-new"
                                                    style="border-top:1px solid #eee; background:#fff; position:sticky; bottom:0; z-index:2;">
                                                </div>
                                            </div>
                                        </div>
                                        @error('deal_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="proposal_version_display" class="form-label">Proposal Version</label>
                                        <input type="text" class="form-control" id="proposal_version_display"
                                               value="v{{ (int) ($nextProposalVersion ?? 1) }}" readonly>
                                        <input type="hidden" name="proposal_version" id="proposal_version"
                                               value="{{ (int) ($nextProposalVersion ?? 1) }}">
                                        <small class="text-muted">Auto-updates from this deal’s quotation list (v1, v2, …).</small>
                                    </div>

                                </div>
                            </div>
                        </div>
                        </div><!-- /Step 1 -->

                        <!-- Step 2: Address -->
                        <div class="qt-wizard-panel" data-step="1">
                        @php
                            $enableShippingAddress = old('enable_shipping_address', '0') == '1';
                        @endphp
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div class="qt-wizard-panel-title mb-0">Address Information</div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="enable-shipping-address" name="enable_shipping_address" value="1" {{ $enableShippingAddress ? 'checked' : '' }}>
                                <label class="form-check-label" for="enable-shipping-address">Enable Shipping Address</label>
                            </div>
                        </div>
                        <!---- Quote Billing and Shipping Information -->
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="border p-3 rounded">
                                        <h5>Billing Address</h5>
                                        <div class="mb-3">
                                            <label for="billing_country" class="form-label">Country / Region <span class="text-danger">*</span></label>
                                            <select class="form-select" id="billing_country" name="billing_country" required>
                                                <option value="">-None-</option>
                                                <!-- Add country options here -->
                                                @foreach ($countries as $country)
                                                <option value="{{ $country }}"
                                                    @if ($country==old('country', 'India' )) selected @endif>{{ $country }}
                                                </option>
                                                @endforeach
                                            </select>

                                        </div>
                                        <div class="mb-3">
                                            <input type="hidden" name="billing_address_type" value="billing"> <!-- Hidden field to store selected organization's ID -->
                                            <label for="billing_flat" class="form-label">Flat / House No./ Building / Apartment Name</label>
                                            <input type="text" class="form-control" id="billing_flat" name="billing_flat" value="{{ old('billing_flat') }}">
                                            @error('billing_flat')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="billing_street" class="form-label">Street Address <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="billing_street" name="billing_street" value="{{ old('billing_street') }}" required>
                                            @error('billing_street')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="billing_city" class="form-label">City <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="billing_city" name="billing_city" value="{{ old('billing_city') }}" required>
                                            @error('billing_city')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="billing_state" class="form-label">State / Province <span class="text-danger">*</span></label>
                                            <select class="form-select" id="billing_state" name="billing_state" required>
                                                <option value="">-None-</option>
                                                <!-- Add state options here -->
                                            </select>
                                            @error('billing_state')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="billing_zip" class="form-label">Zip / Postal Code</label>
                                            <input type="text" class="form-control" id="billing_zip" name="billing_zip" value="{{ old('billing_zip') }}">
                                        </div>
                                        <!-- <div class="mb-3">
                                            <label for="billing_latitude" class="form-label">Coordinates</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control" id="billing_latitude" name="billing_latitude" placeholder="Latitude">
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control" id="billing_longitude" name="billing_longitude" placeholder="Longitude">
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-secondary">Clear All</button> -->
                                    </div>
                                </div>

                                <div class="col-md-6" id="shipping-address-section" style="{{ $enableShippingAddress ? '' : 'display:none;' }}">
                                    <div class="border p-3 rounded">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">Shipping Address</h5>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" id="sameAsBilling" {{ $enableShippingAddress ? '' : 'disabled' }}>
                                                <label class="form-check-label" for="sameAsBilling">
                                                    Same as Billing Address
                                                </label>
                                            </div>
                                        </div>
                                        <div id="shipping-address-fields">
                                        <div class="mb-3">
                                            <label for="shipping_country" class="form-label">Country / Region <span class="text-danger shipping-required-mark">*</span></label>
                                            <select class="form-select shipping-required-field" id="shipping_country" name="shipping_country" {{ $enableShippingAddress ? 'required' : 'disabled' }}>
                                                <option value="">-None-</option>
                                                <!-- Add country options here -->
                                                @foreach ($countries as $country)
                                                <option value="{{ $country }}"
                                                    @if ($country==old('shipping_country', 'India' )) selected @endif>{{ $country }}
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('shipping_country')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <input type="hidden" name="shipping_address_type" value="shipping" {{ $enableShippingAddress ? '' : 'disabled' }}>
                                            <label for="shipping_flat" class="form-label">Flat / House No./ Building / Apartment Name</label>
                                            <input type="text" class="form-control" id="shipping_flat" name="shipping_flat" value="{{ old('shipping_flat') }}" {{ $enableShippingAddress ? '' : 'disabled' }}>
                                        </div>
                                        <div class="mb-3">
                                            <label for="shipping_street" class="form-label">Street Address <span class="text-danger shipping-required-mark">*</span></label>
                                            <input type="text" class="form-control shipping-required-field" id="shipping_street" name="shipping_street" value="{{ old('shipping_street') }}" {{ $enableShippingAddress ? 'required' : 'disabled' }}>
                                            @error('shipping_street')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror

                                        </div>
                                        <div class="mb-3">
                                            <label for="shipping_city" class="form-label">City <span class="text-danger shipping-required-mark">*</span></label>
                                            <input type="text" class="form-control shipping-required-field" id="shipping_city" name="shipping_city" value="{{ old('shipping_city') }}" {{ $enableShippingAddress ? 'required' : 'disabled' }}>
                                            @error('shipping_city')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="shipping_state" class="form-label">State / Province <span class="text-danger shipping-required-mark">*</span></label>
                                            <select class="form-select shipping-required-field" id="shipping_state" name="shipping_state" {{ $enableShippingAddress ? 'required' : 'disabled' }}>
                                                <option value="">-None-</option>
                                                <!-- Add state options here -->
                                            </select>
                                            @error('shipping_state')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="shipping_zip" class="form-label">Zip / Postal Code</label>
                                            <input type="text" class="form-control" id="shipping_zip" name="shipping_zip" value="{{ old('shipping_zip') }}" {{ $enableShippingAddress ? '' : 'disabled' }}>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        </div><!-- /Step 2 -->

                        <!-- Step 3: Products + Summary -->
                        <div class="qt-wizard-panel" data-step="2">
                        <div class="qt-wizard-panel-title">Products &amp; Summary</div>
                        <!-- Quotation Title (shown above items on PDF) -->
                        <div class="mb-3">
                            <label for="quotation_title" class="form-label"><b>Quotation Title</b></label>
                            <input type="text" class="form-control" id="quotation_title" name="quotation_title"
                                   value="{{ old('quotation_title') }}"
                                   placeholder="e.g. Quotation for Website Redesigning">
                            <small class="text-muted">Shown in blue above the quote table on the PDF.</small>
                            @error('quotation_title')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        @include('quotations.partials.quoted_items_section', [
                            'quotationProductType' => old('quotation_product_type', 'default'),
                        ])

                        <!-- Adding Subtotal, Discount, Tax, Adjustment, and Grand Total fields -->
                        <div class="mt-4 d-flex justify-content-end">
                            <div style="width: 100%; max-width: 500px;" class="border p-3 rounded">
                                <div class="mb-3">
                                    <label for="subtotal" class="form-label">Subtotal</label>
                                    <input type="number" class="form-control" id="subtotal" name="subtotal" readonly step="any" value="{{ format_smart_number(old('subtotal', 0), 2, false) }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Overall Discount</label>
                                    @php
                                        $oldOverallDiscount = (float) old('overall_discount', 0);
                                        $oldOverallDiscountPct = (float) old('overall_discount_percent', 0);
                                        if (!old('overall_discount_percent') && (float) old('subtotal', 0) > 0 && $oldOverallDiscount > 0) {
                                            $oldOverallDiscountPct = round(($oldOverallDiscount / (float) old('subtotal', 0)) * 100, 2);
                                        }
                                    @endphp
                                    <div class="d-flex flex-column gap-1">
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="overall-discount-percent" name="overall_discount_percent"
                                                value="{{ format_smart_number($oldOverallDiscountPct, 2, false) }}"
                                                step="any" min="0" max="100" placeholder="0">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="overall-discount" name="overall_discount"
                                                value="{{ format_smart_number($oldOverallDiscount, 2, false) }}"
                                                step="any" min="0" placeholder="0">
                                            <span class="input-group-text">Amt</span>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $oldOverallTax = old('overall_tax');
                                    $enableOverallTax = old('enable_overall_tax', ($oldOverallTax !== null && $oldOverallTax !== '') ? '1' : '0') == '1';
                                    $oldAdjustment = old('adjustment', 0);
                                    $enableAdjustment = old('enable_adjustment', ((float) $oldAdjustment != 0) ? '1' : '0') == '1';
                                @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="overall-tax" class="form-label mb-0">Overall Tax (%)</label>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="enable-overall-tax" name="enable_overall_tax" value="1" {{ $enableOverallTax ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable-overall-tax">Enable</label>
                                        </div>
                                    </div>
                                    <div id="overall-tax-wrap" style="{{ $enableOverallTax ? '' : 'display:none;' }}">
                                        <select class="form-select" id="overall-tax" name="overall_tax" {{ $enableOverallTax ? '' : 'disabled' }}>
                                            <option value="">Select Tax</option>
                                            @foreach($taxRates as $taxRate)
                                            <option value="{{ $taxRate->rate }}" {{ $oldOverallTax == $taxRate->rate ? 'selected' : '' }}>{{ $taxRate->name }} ({{ $taxRate->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="adjustment" class="form-label mb-0">Adjustment</label>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="enable-adjustment" name="enable_adjustment" value="1" {{ $enableAdjustment ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable-adjustment">Enable</label>
                                        </div>
                                    </div>
                                    <div id="adjustment-wrap" style="{{ $enableAdjustment ? '' : 'display:none;' }}">
                                        <input type="number" class="form-control" id="adjustment" name="adjustment" value="{{ $oldAdjustment }}" {{ $enableAdjustment ? '' : 'disabled' }}>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="grand-total" class="form-label">Grand Total</label>
                                    <input type="number" class="form-control" id="grand-total" name="grand_total" value="{{ format_smart_number(old('grand_total', 0), 2, false) }}" step="any" readonly>
                                </div>
                            </div>
                        </div>


                        </div><!-- /Step 3 -->

                        <!-- Step 4: Description -->
                        <div class="qt-wizard-panel" data-step="3">
                        <div class="qt-wizard-panel-title">Description</div>
                        <!-- Project Detail (optional) -->
                        <div class="mb-3 border rounded p-3">
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleProjectDetail" title="Add project detail">
                                    <i class="bi bi-plus-lg"></i>
                                    <i class="bi bi-dash-lg" style="display:none;"></i>
                                </button>
                                <label class="form-label mb-0"><b>Project Detail</b>
                                    <span class="text-muted small fw-normal">(optional — click + to edit)</span>
                                </label>
                            </div>
                            <div id="projectDetailPanel" style="display:none;">
                                <textarea class="form-control qt-rich-editor" id="project_detail" name="project_detail" rows="8"></textarea>
                                <script type="application/json" id="project_detail_json">@json(old('project_detail') ?? '')</script>
                                @error('project_detail')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        </div><!-- /Step 4 -->

                        <!-- Step 5: Notes -->
                        <div class="qt-wizard-panel" data-step="4">
                        <div class="qt-wizard-panel-title">Notes</div>
                        <div class="mb-3">
                            <div class="qt-rich-label mb-1">
                                <label for="description" class="form-label mb-0">Description / Remarks</label>
                                <span class="qt-rich-hint">Rich text · code · images · tables</span>
                            </div>
                            <textarea class="form-control qt-rich-editor" id="description" name="description" rows="8"></textarea>
                            <script type="application/json" id="description_json">@json(old('description') ?? '')</script>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="show_bank_details" name="show_bank_details" value="1" {{ old('show_bank_details') ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_bank_details">Include bank details on PDF</label>
                            </div>
                            <div class="form-text">When enabled, company bank account details are shown between Remarks and Terms &amp; Conditions.</div>
                        </div>
                        <div class="mb-3">
                            <div class="qt-rich-label mb-1">
                                <label for="terms_conditions" class="form-label mb-0">Terms and Conditions</label>
                                <span class="qt-rich-hint">Use Source code for HTML · Preview before save</span>
                            </div>
                            <textarea class="form-control qt-rich-editor" id="terms_conditions" name="terms_conditions" rows="10"></textarea>
                            <script type="application/json" id="terms_conditions_json">@json(old('terms_conditions', $defaultTermsConditions ?? '') ?? '')</script>
                        </div>
                        </div><!-- /Step 5 -->

                        <!-- Step 6: Review -->
                        <div class="qt-wizard-panel" data-step="5">
                        <div class="qt-wizard-panel-title d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <span>Review</span>
                            <span class="small text-muted fw-normal" id="qtReviewTemplateName">Selected template preview</span>
                        </div>
                        <div class="qt-review-preview-wrap">
                            <div class="qt-review-preview-loading" id="qtReviewPreviewLoading">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Loading template preview…
                            </div>
                            <iframe id="qtReviewTemplatePreview"
                                class="qt-review-preview-iframe"
                                title="Quotation template preview"
                                sandbox="allow-same-origin allow-scripts"></iframe>
                        </div>
                        <p class="text-muted small mb-0 mt-2">This preview uses your selected quotation template and the details entered above. Confirm, then create the quotation.</p>
                        </div><!-- /Step 6 -->

                        <div class="qt-wizard-nav">
                            <button type="button" class="btn btn-outline-secondary" id="qtWizardPrev" style="visibility:hidden;">&laquo; Previous</button>
                            <button type="button" class="btn btn-primary btn-next" id="qtWizardNext">Next &raquo;</button>
                            <button type="submit" class="btn btn-success" id="qtWizardSubmit" style="display:none;">Create Quotation</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('quotations.partials.wizard_scripts')

{{-- Quotation Template Picker Modal --}}
<div class="modal fade" id="quotationTemplatePickerModal" tabindex="-1" aria-labelledby="quotationTemplatePickerLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quotationTemplatePickerLabel">Select Quotation Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Click a template thumbnail to select it. Double-click to apply immediately.</p>
                <div class="row g-3" id="qtPickerGrid">
                    @foreach(($quotationTemplates ?? []) as $qt)
                        <div class="col-md-4 col-sm-6">
                            <button type="button"
                                class="qt-picker-card w-100 text-start border rounded p-2 bg-white {{ (int)($selectedQuotationTemplateId ?? 0) === (int)$qt->id ? 'is-selected' : '' }}"
                                data-id="{{ $qt->id }}"
                                data-name="{{ $qt->name }}"
                                data-version="{{ $qt->version }}"
                                data-default="{{ $qt->is_default ? '1' : '0' }}"
                                data-description="{{ e($qt->description ?? '') }}"
                                data-preview="{{ route('quotation_templates.picker_preview', $qt) }}">
                                <div class="qt-picker-thumb mb-2">
                                    <iframe src="{{ route('quotation_templates.picker_preview', $qt) }}"
                                        title="{{ $qt->name }} preview"
                                        tabindex="-1"
                                        loading="lazy"></iframe>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $qt->name }}</div>
                                        <div class="small text-muted">v{{ $qt->version }}</div>
                                    </div>
                                    @if($qt->is_default)
                                        <span class="badge bg-warning text-dark">Default</span>
                                    @endif
                                </div>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-custom" id="qtConfirmTemplateBtn">Use Selected Template</button>
            </div>
        </div>
    </div>
</div>

<style>
    .qt-thumb-wrap,
    .qt-picker-thumb {
        width: 140px;
        height: 180px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        position: relative;
    }
    .qt-picker-thumb {
        width: 100%;
        height: 220px;
    }
    .qt-thumb-iframe,
    .qt-picker-thumb iframe {
        width: 794px;
        height: 1123px;
        border: 0;
        transform: scale(0.176);
        transform-origin: top left;
        pointer-events: none;
        background: #fff;
    }
    .qt-picker-thumb iframe {
        transform: scale(0.28);
    }
    .qt-thumb-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: #94a3b8;
        font-size: 12px;
    }
    .qt-picker-card {
        transition: box-shadow .15s ease, border-color .15s ease;
        cursor: pointer;
    }
    .qt-picker-card:hover {
        border-color: #d8891c !important;
        box-shadow: 0 4px 16px rgba(216, 137, 28, .15);
    }
    .qt-picker-card.is-selected {
        border-color: #d8891c !important;
        box-shadow: 0 0 0 2px rgba(216, 137, 28, .35);
        background: #fffaf3 !important;
    }
    #qtSelectedCard .btn-outline-primary {
        border-color: #d8891c;
        color: #d8891c;
    }
    #qtSelectedCard .btn-outline-primary:hover {
        background: #d8891c;
        color: #fff;
    }
</style>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const hiddenInput = document.getElementById('quotation_template_id');
        const btnChange = document.getElementById('btnChangeQuotationTemplate');
        const pickerModalEl = document.getElementById('quotationTemplatePickerModal');
        if (!hiddenInput || !btnChange || !pickerModalEl) return;

        const pickerModal = new bootstrap.Modal(pickerModalEl);
        let pendingId = hiddenInput.value;

        function setSelectedUI(card) {
            if (!card) return;
            const id = card.getAttribute('data-id');
            const name = card.getAttribute('data-name') || '';
            const version = card.getAttribute('data-version') || '';
            const isDefault = card.getAttribute('data-default') === '1';
            const description = card.getAttribute('data-description') || '';
            const preview = card.getAttribute('data-preview') || '';

            hiddenInput.value = id;
            pendingId = id;

            document.getElementById('qtSelectedName').textContent = name;
            const badge = document.getElementById('qtSelectedDefaultBadge');
            if (badge) badge.style.display = isDefault ? '' : 'none';

            let meta = 'Version ' + version;
            if (description) meta += ' · ' + (description.length > 70 ? description.slice(0, 70) + '…' : description);
            document.getElementById('qtSelectedMeta').textContent = meta;

            const wrap = document.getElementById('qtSelectedThumbWrap');
            if (wrap && preview) {
                wrap.innerHTML = '<iframe class="qt-thumb-iframe" src="' + preview + '" title="Selected template preview" tabindex="-1"></iframe>';
            }

            document.querySelectorAll('.qt-picker-card').forEach(function (el) {
                el.classList.toggle('is-selected', el.getAttribute('data-id') === String(id));
            });
        }

        btnChange.addEventListener('click', function () {
            pendingId = hiddenInput.value;
            document.querySelectorAll('.qt-picker-card').forEach(function (el) {
                el.classList.toggle('is-selected', el.getAttribute('data-id') === String(pendingId));
            });
            pickerModal.show();
        });

        document.querySelectorAll('.qt-picker-card').forEach(function (card) {
            card.addEventListener('click', function () {
                pendingId = card.getAttribute('data-id');
                document.querySelectorAll('.qt-picker-card').forEach(function (el) {
                    el.classList.toggle('is-selected', el === card);
                });
            });

            card.addEventListener('dblclick', function () {
                setSelectedUI(card);
                pickerModal.hide();
            });
        });

        const confirmBtn = document.getElementById('qtConfirmTemplateBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                const card = document.querySelector('.qt-picker-card[data-id="' + pendingId + '"]');
                if (card) setSelectedUI(card);
                pickerModal.hide();
            });
        }
    });
})();
</script>

<!-- Organization New Modal -->
<div class="modal fade" id="orgNewModal" tabindex="-1" aria-labelledby="orgNewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="orgNewForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orgNewModalLabel">Add New Comapny</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="org_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" placeholder="Company Name" class="form-control" id="org_name"
                            name="org_name">
                        <div id="org_name_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="org_address" class="form-label">Address <span
                                class="text-danger">*</span></label>
                        <input type="text" placeholder="123 Main St" class="form-control" id="org_address"
                            name="org_address">
                        <div id="org_address_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="org_phone" class="form-label">Phone</label>
                        <input type="text" placeholder="+91 9876234569" class="form-control" id="org_phone"
                            name="org_phone">
                        <div id="org_phone_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="org_website" class="form-label">Website URL</label>
                        <input type="text" placeholder="http://example.com/" class="form-control"
                            id="org_website" name="org_website">
                        <div id="org_website_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-custom">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Contact Person New Modal -->
<div class="modal fade" id="personNewModal" tabindex="-1" aria-labelledby="personNewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <form id="personNewForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="personNewModalLabel">Add New Contact Person</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-3">
                            <label for="person_first_name" class="form-label">First Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="person_first_name"
                                name="person_first_name">
                            <div id="person_first_name_error" class="text-danger small mt-1" style="display:none;">
                            </div>
                        </div>
                        <div class="col mb-3">
                            <label for="person_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="person_last_name"
                                name="person_last_name">
                            <div id="person_last_name_error" class="text-danger small mt-1" style="display:none;">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="person_org_id" class="form-label">Company <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="person_org_id" name="person_org_id">
                            <option value="">Select company</option>
                            @foreach ($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        <div id="person_org_id_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="person_email" class="form-label">Email </label>
                        <input type="text" placeholder="Enter email address" class="form-control" id="person_email" name="person_email">
                        <div id="person_email_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="person_phone" class="form-label">Mobile <span
                                class="text-danger">*</span></label>
                        <input type="text" placeholder="+91 9457834901" class="form-control" id="person_mobile" name="person_phone">
                        <div id="person_phone_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-custom">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!--- Deal New Modal -->
<div class="modal fade" id="dealNewModal" tabindex="-1" aria-labelledby="dealNewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <form id="dealNewForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dealNewModalLabel">Add New Deals</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="deal_name" class="form-label">Deal Name <span class="text-danger">*</span></label>
                        <input type="text" placeholder="Deal Name" class="form-control" id="deal_name"
                            name="deal_name">
                        <div id="deal_name_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="deal_org_id" class="form-label">Company <span class="text-danger">*</span></label>
                        <select class="form-select" id="cust_org_id" name="cust_org_id">
                            <option value="">Select company</option>
                            @foreach ($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" id="deal_person_id" name="deal_person_id"> <!-- Hidden field to store selected person's ID -->
                        <div id="deal_org_id_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="deal_date" class="form-label">Closing Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="deal_date" name="deal_date">
                        <div id="deal_date_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="deal_stage" class="form-label">Stage <span class="text-danger">*</span></label>
                        <select class="form-select" id="deal_stage" name="deal_stage">
                            <option value="">Select stage</option>

                            @foreach($stages as $stage)
                            <option value="{{ $stage->id }}" data-name="{{ $stage->name }}" data-probability="{{ $stage->probability ?? 10 }}" @if((string) old('deal_stage') === (string) $stage->id || (string) old('stage') === (string) $stage->id) selected @endif>{{ $stage->name }} ({{ $stage->probability ?? 'N/A' }}%)</option>
                            @endforeach
                        </select>
                        <div id="deal_stage_error" class="text-danger small mt-1" style="display:none;"></div>

                        <input type="hidden" class="form-control" id="probability" name="probability" min="0" max="100" value="{{ old('probability', 10) }}" oninput="if(this.value.length>3)this.value=this.value.slice(0,3);">
                    </div>
                    <div class="mb-3">
                        <label for="deal_amount" class="form-label">Amount</label>
                        <input type="number" placeholder="Enter deal value" class="form-control" id="deal_amount" name="deal_amount">
                        <div id="deal_amount_error" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="deal_owner" class="form-label">Deal Owner</label>
                        <select class="form-select" id="deal_owner" name="deal_owner">
                            <option value="">Select deal owner</option>
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <div id="deal_owner_error" class="text-danger small mt-1" style="display:none;"></div>

                    </div>
                    <div class="mb-3" id="reason_for_loss_container" style="display: none;">
                        <label for="reason_for_loss" class="form-label">Reason for Loss <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason_for_loss" name="reason_for_loss" rows="2" placeholder="Please specify the reason" required></textarea>
                        <div id="reason_for_loss_error" class="text-danger small mt-1" style="display: none;">This field is required.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-custom">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        //Company Module Concepts....
        const CSRF_TOKEN = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '';
        // Autocomplete organization list
        const orgInput = document.getElementById('organization_id');
        const orgDropdown = document.getElementById('org-autocomplete-list');

        let orgListData = [];

        function renderOrgList(filter = '') {
            const scrollDiv = document.getElementById('org-autocomplete-scroll');
            const newDiv = document.getElementById('org-autocomplete-new');
            scrollDiv.innerHTML = '';
            (orgListData.filter(org => org.name.toLowerCase().includes(filter.toLowerCase()))).forEach(org => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'dropdown-item';
                item.textContent = org.name;
                item.onclick = function() {
                    orgInput.value = org.name;
                    orgDropdown.style.display = 'none';

                    // Fetch organization details (owner and primary contact) and autofill owner/contact fields
                    fetch('/organizations/details?name=' + encodeURIComponent(org.name))
                        .then(res => res.json())
                        .then(data => {
                            console.log('Organization details:', data);
                            // Helper to render a small selectable list below an input
                            function renderRelatedList(containerId, items, defaultText, onSelect) {
                                // remove existing container if present
                                let existing = document.getElementById(containerId);
                                if (existing) existing.remove();
                                if (!items || items.length === 0) return;
                                const container = document.createElement('div');
                                container.id = containerId;
                                container.style.marginTop = '6px';
                                container.style.padding = '6px';
                                container.style.border = '1px solid #e9ecef';
                                container.style.background = '#fff';
                                container.style.borderRadius = '4px';
                                container.style.boxShadow = '0 1px 2px rgba(0,0,0,0.04)';
                                const list = document.createElement('div');
                                list.style.maxHeight = '160px';
                                list.style.overflowY = 'auto';
                                items.forEach((it, idx) => {
                                    const btn = document.createElement('button');
                                    btn.type = 'button';
                                    btn.className = 'btn btn-sm btn-light w-100 text-start mb-1';
                                    btn.style.whiteSpace = 'nowrap';
                                    btn.style.overflow = 'hidden';
                                    btn.style.textOverflow = 'ellipsis';
                                    btn.textContent = it.name || it.full_name || it;
                                    btn.onclick = function(e) {
                                        e.preventDefault();
                                        onSelect(it);
                                        container.remove();
                                    };
                                    list.appendChild(btn);
                                });
                                container.appendChild(list);
                                // Insert after the input element
                                const anchor = defaultText instanceof Element ? defaultText : document.getElementById(defaultText);
                                if (anchor && anchor.parentNode) {
                                    anchor.parentNode.insertBefore(container, anchor.nextSibling);
                                }
                            }

                            // Deals for this company — autofill deal + next proposal version
                            function applyOrgDealSelection(deal) {
                                const custInputEl = document.getElementById('deal_id');
                                if (custInputEl) {
                                    custInputEl.value = (deal && deal.name) ? deal.name : '';
                                }
                                const titleInput = document.getElementById('quotation_title');
                                if (titleInput && deal && deal.name) {
                                    const current = (titleInput.value || '').trim();
                                    if (current === '' || /^Quotation for /i.test(current)) {
                                        titleInput.value = 'Quotation for ' + deal.name;
                                    }
                                }
                                if (!window.QuotationFormHelpers) {
                                    return;
                                }
                                if (deal && deal.next_proposal_version) {
                                    window.QuotationFormHelpers.setProposalVersion(deal.next_proposal_version);
                                } else if (deal && deal.name) {
                                    window.QuotationFormHelpers.fetchNextProposalVersion(
                                        deal.name,
                                        org.name || ((document.getElementById('organization_id') || {}).value || '')
                                    );
                                } else {
                                    window.QuotationFormHelpers.setProposalVersion(1);
                                }
                            }

                            if (Array.isArray(data.deals) && data.deals.length > 0) {
                                // Fill only one deal (latest) + proposal version — no multi-deal list UI
                                applyOrgDealSelection(data.deals[0]);
                            } else {
                                applyOrgDealSelection(null);
                            }
                            const existingCustList = document.getElementById('org-related-deals');
                            if (existingCustList) existingCustList.remove();

                            // People list (contact persons)
                            if (Array.isArray(data.people) && data.people.length > 0) {
                                console.log('Related people:', data.people);
                                const firstPerson = data.people[0];
                                const personInputEl = document.getElementById('people_id');
                                const personIdEl = document.getElementById('person_id');
                                if (personInputEl) personInputEl.value = firstPerson.full_name;
                                if (personIdEl) personIdEl.value = firstPerson.id;

                                // transform people array to objects with full_name property for rendering
                                const peopleForRender = data.people.map(p => ({
                                    full_name: p.full_name,
                                    id: p.id
                                }));
                                // renderRelatedList('org-related-people', peopleForRender, 'people_id', function(sel) {
                                //     const personInputEl2 = document.getElementById('people_id');
                                //     if (personInputEl2) personInputEl2.value = sel.full_name;
                                // });
                            } else {
                                const personInputEl = document.getElementById('people_id');
                                const personIdEl = document.getElementById('person_id');
                                if (personInputEl) personInputEl.value = '';
                                if (personIdEl) personIdEl.value = '';
                                const existingPersonList = document.getElementById('org-related-people');
                                if (existingPersonList) existingPersonList.remove();
                            }

                            // Owner (user_owner_id) selection if provided
                            if (data.owner) {
                                const ownerSelect = document.getElementById('user_owner_id');
                                if (ownerSelect && data.owner.id) {
                                    try {
                                        ownerSelect.value = data.owner.id;
                                        if (typeof $ !== 'undefined') {
                                            $(ownerSelect).val(data.owner.id).trigger('change');
                                        }
                                    } catch (e) {}
                                }
                            }
                        })
                        .catch(() => {
                            // On error, clear dependent fields
                            document.getElementById('deal_id').value = '';
                            document.getElementById('people_id').value = '';
                            document.getElementById('person_id').value = '';
                            if (window.QuotationFormHelpers) {
                                window.QuotationFormHelpers.setProposalVersion(1);
                            }
                        });
                };
                scrollDiv.appendChild(item);
            });
            // Add "New Organization" option at the end, always visible and sticky
            newDiv.innerHTML = '';
            const newBtn = document.createElement('a');
            newBtn.href = "#";
            newBtn.className = 'dropdown-item text-primary';
            newBtn.textContent = 'New Company';
            newBtn.onclick = function(e) {
                e.preventDefault();
                orgDropdown.style.display = 'none';
                setTimeout(function() {
                    var modal = new bootstrap.Modal(document.getElementById('orgNewModal'));
                    modal.show();
                }, 100);
            };
            newDiv.appendChild(newBtn);
        }

        orgInput.addEventListener('focus', function() {
            fetch('/organizations/autocomplete?q=')
                .then(res => res.json())
                .then(data => {
                    orgListData = data;
                    renderOrgList();
                    orgDropdown.style.display = 'block';
                    document.getElementById('org-autocomplete-search').value = '';
                });
        });

        document.getElementById('org-autocomplete-search').addEventListener('input', function(e) {
            renderOrgList(e.target.value);
        });



        // Click outside to close organization dropdown
        document.addEventListener('mousedown', function(e) {
            if (orgDropdown.style.display === 'block') {
                if (!orgDropdown.contains(e.target) && e.target !== orgInput) {
                    orgDropdown.style.display = 'none';
                }
            }
        });

        // New organization modal submit
        document.getElementById('orgNewForm').addEventListener('submit', function(e) {

            e.preventDefault();
            // Hide all error messages first
            ['org_name_error', 'org_address_error', 'org_phone_error', 'org_website_error'].forEach(
                function(id) {
                    document.getElementById(id).style.display = 'none';
                    document.getElementById(id).textContent = '';
                });
            const formData = new FormData(this);
            // ensure token is present in FormData for fallback and set headers/credentials
            if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
            fetch('/organizations/ajax-create', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: formData
                })
                .then(async res => {
                    if (res.status === 422) {
                        const data = await res.json();
                        if (data.errors) {
                            Object.keys(data.errors).forEach(function(key) {
                                const errorDiv = document.getElementById(key +
                                    '_error');
                                if (errorDiv) {
                                    errorDiv.textContent = data.errors[key][0];
                                    errorDiv.style.display = 'block';
                                }
                            });
                        }
                        throw new Error('Validation error');
                    }
                    return res.json();
                })
                .then(org => {
                    orgInput.value = org.name;
                    var modal = bootstrap.Modal.getInstance(document.getElementById('orgNewModal'));
                    modal.hide();
                })
                .catch(() => {});
        });

        //End Company Module Concepts.....


        //Start Contact Person Module Concepts....

        // Autocomplete contact person list
        const personInput = document.getElementById('people_id');
        const personDropdown = document.getElementById('person-autocomplete-list');
        const personIdInput = document.getElementById('person_id');

        personInput.addEventListener('focus', function() {
            // Get selected organization name from orgInput
            const orgName = orgInput.value && orgInput.value.trim();
            // If no organization is selected, do not list contact persons
            if (!orgName) {
                const scrollDiv = document.getElementById('person-autocomplete-scroll');
                const newDiv = document.getElementById('person-autocomplete-new');
                scrollDiv.innerHTML = '<div class="p-2 text-muted">Please select a Company first to list its contacts.</div>';
                newDiv.innerHTML = '';
                personDropdown.style.display = 'block';
                document.getElementById('person-autocomplete-search').value = '';
                return;
            }
            let url = '/people/autocomplete?q=' + '';
            if (orgName) {
                url += '&organization=' + encodeURIComponent(orgName);
            }
            let personListData = [];

            function renderPersonList(filter = '') {
                const scrollDiv = document.getElementById('person-autocomplete-scroll');
                const newDiv = document.getElementById('person-autocomplete-new');
                scrollDiv.innerHTML = '';
                (personListData.filter(person => ((person.first_name ? person.first_name : '') + (person
                    .last_name ? ' ' + person.last_name : '')).toLowerCase().includes(filter
                    .toLowerCase()))).forEach(person => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'dropdown-item';
                    item.textContent = (person.first_name ? person.first_name : '') + (person
                        .last_name ? ' ' + person.last_name : '');
                    item.onclick = function() {
                        personInput.value = (person.first_name ? person.first_name : '') + (
                            person.last_name ? ' ' + person.last_name : '');
                        personIdInput.value = person.id;
                        personDropdown.style.display = 'none';
                    };
                    scrollDiv.appendChild(item);
                });
                // Add "New Contact Person" option at the end, always visible and sticky
                newDiv.innerHTML = '';
                const newBtn = document.createElement('a');
                newBtn.href = "#";
                newBtn.className = 'dropdown-item text-primary';
                newBtn.textContent = 'New Contact Person';
                newBtn.onclick = function(e) {
                    e.preventDefault();
                    personDropdown.style.display = 'none';
                    setTimeout(function() {
                        var modal = new bootstrap.Modal(document.getElementById(
                            'personNewModal'));
                        modal.show();
                    }, 100);
                };
                newDiv.appendChild(newBtn);
            }
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    personListData = data;

                    renderPersonList();
                    personDropdown.style.display = 'block';
                    document.getElementById('person-autocomplete-search').value = '';
                });
            document.getElementById('person-autocomplete-search').oninput = function(e) {
                renderPersonList(e.target.value);
            };
        });

        // Click outside to close contact person dropdown
        document.addEventListener('mousedown', function(e) {
            if (personDropdown.style.display === 'block') {
                if (!personDropdown.contains(e.target) && e.target !== personInput) {
                    personDropdown.style.display = 'none';
                }
            }
        });

        // Fill organization in contact person modal when opened, make readonly if selected
        document.getElementById('personNewModal').addEventListener('show.bs.modal', function() {
            const orgName = document.getElementById('organization_id').value;
            const orgSelect = document.getElementById('person_org_id');
            if (!orgSelect) return;
            let foundIndex = -1;
            for (let i = 0; i < orgSelect.options.length; i++) {
                if (orgSelect.options[i].text === orgName) {
                    foundIndex = i;
                    break;
                }
            }
            const readonlyId = 'person_org_readonly';
            // If organization is already selected, hide the dropdown and show a readonly input instead
            if (foundIndex !== -1 && orgName !== '') {
                orgSelect.selectedIndex = foundIndex;
                orgSelect.style.display = 'none';
                if (!document.getElementById(readonlyId)) {
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control';
                    input.id = readonlyId;
                    input.value = orgName;
                    input.readOnly = true;
                    orgSelect.parentNode.insertBefore(input, orgSelect);
                } else {
                    const existing = document.getElementById(readonlyId);
                    existing.value = orgName;
                    existing.style.display = '';
                }
                // ensure hidden input for org id exists so AJAX/FormData can reliably include org id
                const orgId = orgSelect.options[foundIndex] ? orgSelect.options[foundIndex].value : '';
                if (orgId) {
                    let hid = document.getElementById('person_org_id_hidden');
                    if (!hid) {
                        hid = document.createElement('input');
                        hid.type = 'hidden';
                        hid.id = 'person_org_id_hidden';
                        hid.name = 'person_org_id_hidden';
                        orgSelect.parentNode.insertBefore(hid, orgSelect);
                    }
                    hid.value = orgId;
                }
            } else {
                orgSelect.style.display = '';
                const existing = document.getElementById(readonlyId);
                if (existing) existing.remove();
                const hid = document.getElementById('person_org_id_hidden');
                if (hid) hid.remove();
            }
        });

        // New contact person modal submit with single word validation
        document.getElementById('personNewForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Hide all error messages first
            ['person_first_name_error', 'person_last_name_error', 'person_org_id_error',
                'person_email_error', 'person_phone_error'
            ].forEach(function(id) {
                document.getElementById(id).style.display = 'none';
                document.getElementById(id).textContent = '';
            });
            const firstName = document.getElementById('person_first_name').value.trim();
            const lastName = document.getElementById('person_last_name').value.trim();
            const singleWordRegex = /^\w+$/;
            let errorMsg = '';
            if (firstName && !singleWordRegex.test(firstName)) {
                errorMsg += 'First name must be a single word.\n';
            }
            // if (lastName && !singleWordRegex.test(lastName)) {
            //     errorMsg += 'Last name must be a single word.';
            // }
            if (errorMsg) {
                document.getElementById('person_first_name_error').textContent = errorMsg;
                document.getElementById('person_first_name_error').style.display = 'block';
                return;
            }
            const formEl = this;
            const orgSelect = document.getElementById('person_org_id');
            const orgName = orgSelect && orgSelect.options[orgSelect.selectedIndex] ? orgSelect.options[orgSelect.selectedIndex].text : '';

            // Pre-check duplicates via autocomplete (by full name + organization)
            if (firstName) {
                const q = firstName + (lastName ? ' ' + lastName : '');
                const checkUrl = '/people/autocomplete?q=' + encodeURIComponent(q) + (orgName ? '&organization=' + encodeURIComponent(orgName) : '');
                fetch(checkUrl)
                    .then(res => res.json())
                    .then(matches => {
                        const duplicate = (Array.isArray(matches) ? matches : []).find(p => (((p.first_name || '') + ' ' + (p.last_name || '')).trim().toLowerCase() === q.trim().toLowerCase()));
                        if (duplicate) {
                            const firstErr = document.getElementById('person_first_name_error');
                            const lastErr = document.getElementById('person_last_name_error');
                            const msg = 'A contact person named "' + q + '" already exists for "' + (orgName || 'the selected organization') + '".';
                            if (firstErr) {
                                firstErr.textContent = msg;
                                firstErr.style.display = 'block';
                            }
                            if (lastErr) {
                                lastErr.textContent = msg;
                                lastErr.style.display = 'block';
                            }
                            const firstInput = document.getElementById('person_first_name');
                            if (firstInput) firstInput.focus();
                            return;
                        }

                        // No duplicate -> submit
                        const formData = new FormData(formEl);
                        const personHidden = document.getElementById('person_org_id_hidden');
                        if (personHidden) formData.set('person_org_id', personHidden.value);
                        if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
                        fetch('/people/ajax-create', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': CSRF_TOKEN,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                credentials: 'same-origin',
                                body: formData
                            })
                            .then(async res => {
                                if (res.status === 422) {
                                    const data = await res.json();
                                    if (data.errors) {
                                        Object.keys(data.errors).forEach(function(key) {
                                            const errorDiv = document.getElementById(key + '_error');
                                            if (errorDiv) {
                                                errorDiv.textContent = data.errors[key][0];
                                                errorDiv.style.display = 'block';
                                            }
                                        });
                                    }
                                    throw new Error('Validation error');
                                }
                                return res.json();
                            })
                            .then(person => {
                                // server may return duplicate info
                                if (person && person.duplicate) {
                                    const firstErr = document.getElementById('person_first_name_error');
                                    const lastErr = document.getElementById('person_last_name_error');
                                    const msg = 'A contact person named "' + ((person.first_name || '') + (person.last_name ? ' ' + person.last_name : '')).trim() + '" already exists for the selected organization.';
                                    if (firstErr) {
                                        firstErr.textContent = msg;
                                        firstErr.style.display = 'block';
                                    }
                                    if (lastErr) {
                                        lastErr.textContent = msg;
                                        lastErr.style.display = 'block';
                                    }
                                    const firstInput = document.getElementById('person_first_name');
                                    if (firstInput) firstInput.focus();
                                    return;
                                }
                                personInput.value = (person.first_name ? person.first_name : '') + (person.last_name ? ' ' + person.last_name : '');
                                var modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                                modal.hide();
                            })
                            .catch(() => {});
                    })
                    .catch(() => {
                        // On error checking duplicates, fall back to submitting normally
                        const formData = new FormData(formEl);
                        const personHidden = document.getElementById('person_org_id_hidden');
                        if (personHidden) formData.set('person_org_id', personHidden.value);
                        if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
                        fetch('/people/ajax-create', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': CSRF_TOKEN,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                credentials: 'same-origin',
                                body: formData
                            })
                            .then(async res => {
                                if (res.status === 422) {
                                    const data = await res.json();
                                    if (data.errors) {
                                        Object.keys(data.errors).forEach(function(key) {
                                            const errorDiv = document.getElementById(key + '_error');
                                            if (errorDiv) {
                                                errorDiv.textContent = data.errors[key][0];
                                                errorDiv.style.display = 'block';
                                            }
                                        });
                                    }
                                    throw new Error('Validation error');
                                }
                                return res.json();
                            })
                            .then(person => {
                                if (person && person.duplicate) {
                                    const firstErr = document.getElementById('person_first_name_error');
                                    const lastErr = document.getElementById('person_last_name_error');
                                    const msg = 'A contact person named "' + ((person.first_name || '') + (person.last_name ? ' ' + person.last_name : '')).trim() + '" already exists for the selected organization.';
                                    if (firstErr) {
                                        firstErr.textContent = msg;
                                        firstErr.style.display = 'block';
                                    }
                                    if (lastErr) {
                                        lastErr.textContent = msg;
                                        lastErr.style.display = 'block';
                                    }
                                    const firstInput = document.getElementById('person_first_name');
                                    if (firstInput) firstInput.focus();
                                    return;
                                }
                                personInput.value = (person.first_name ? person.first_name : '') + (person.last_name ? ' ' + person.last_name : '');
                                var modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                                modal.hide();
                            })
                            .catch(() => {});
                    });
                return;
            }

            // fallback: submit normally if firstName not present
            const formData = new FormData(this);
            const personHidden = document.getElementById('person_org_id_hidden');
            if (personHidden) formData.set('person_org_id', personHidden.value);
            if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
            fetch('/people/ajax-create', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: formData
                })
                .then(async res => {
                    if (res.status === 422) {
                        const data = await res.json();
                        if (data.errors) {
                            Object.keys(data.errors).forEach(function(key) {
                                const errorDiv = document.getElementById(key + '_error');
                                if (errorDiv) {
                                    errorDiv.textContent = data.errors[key][0];
                                    errorDiv.style.display = 'block';
                                }
                            });
                        }
                        throw new Error('Validation error');
                    }
                    return res.json();
                })
                .then(person => {
                    personInput.value = (person.first_name ? person.first_name : '') + (person.last_name ? ' ' + person.last_name : '');
                    var modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                    modal.hide();
                })
                .catch(() => {});
        });

        // --- Fetch and update organization list for modals ---
        function loadLatestOrganizations(selectId) {
            fetch('/organizations/autocomplete?q=')
                .then(res => res.json())
                .then(data => {
                    const select = document.getElementById(selectId);
                    if (select) {
                        select.innerHTML = '<option value="">Select organization</option>';
                        data.forEach(item => {
                            // Use ID as value, name as text
                            const id = item.id || item.value || item;
                            const name = item.name || item.label || item;
                            const option = document.createElement('option');
                            option.value = id;
                            option.textContent = name;
                            select.appendChild(option);
                        });
                    }
                });
        }
        // Before showing New Contact Person modal
        const personNewModal = document.getElementById('personNewModal');
        if (personNewModal) {
            personNewModal.addEventListener('show.bs.modal', function() {
                loadLatestOrganizations('person_org_id');
            });
        }
        //End Contact Person Module Concepts....

        //------ Start Deals Module Concepts -------
        // Autocomplete customer list
        const custInput = document.getElementById('deal_id');
        const custDropdown = document.getElementById('deal-autocomplete-list');

        custInput.addEventListener('focus', function() {
            // Get selected organization name from orgInput
            const orgName = orgInput.value && orgInput.value.trim();
            // If no organization is selected, do not list customers
            if (!orgName) {
                const scrollDiv = document.getElementById('deal-autocomplete-scroll');
                const newDiv = document.getElementById('deal-autocomplete-new');
                scrollDiv.innerHTML = '<div class="p-2 text-muted">Please select a Deals.</div>';
                newDiv.innerHTML = '';
                custDropdown.style.display = 'block';
                document.getElementById('deal-autocomplete-search').value = '';
                return;
            }
            let url = '/deals/search/autocomplete?q=' + '';
            if (orgName) {
                url += '&organization=' + encodeURIComponent(orgName);
            }
            let customerListData = [];

            function renderCustomerList(filter = '') {
                const scrollDiv = document.getElementById('deal-autocomplete-scroll');
                const newDiv = document.getElementById('deal-autocomplete-new');
                scrollDiv.innerHTML = '';
                (customerListData.filter(cust => (cust.name || cust).toLowerCase().includes(filter
                    .toLowerCase()))).forEach(cust => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'dropdown-item';
                    item.textContent = cust.name || cust;
                    item.onclick = function() {
                        custInput.value = cust.name || cust;
                        custDropdown.style.display = 'none';
                        const titleInput = document.getElementById('quotation_title');
                        if (titleInput && (cust.name || cust)) {
                            const dealName = cust.name || cust;
                            const current = (titleInput.value || '').trim();
                            if (current === '' || /^Quotation for /i.test(current)) {
                                titleInput.value = 'Quotation for ' + dealName;
                            }
                        }
                        if (window.QuotationFormHelpers) {
                            if (cust.next_proposal_version) {
                                window.QuotationFormHelpers.setProposalVersion(cust.next_proposal_version);
                            } else {
                                window.QuotationFormHelpers.fetchNextProposalVersion(
                                    custInput.value,
                                    (document.getElementById('organization_id') || {}).value || ''
                                );
                            }
                        }
                    };
                    scrollDiv.appendChild(item);
                });
                // Add "New Customer" option at the end, always visible and sticky
                newDiv.innerHTML = '';
                const newBtn = document.createElement('a');
                newBtn.href = "#";
                newBtn.className = 'dropdown-item text-primary';
                newBtn.textContent = 'New Deals';
                newBtn.onclick = function(e) {
                    e.preventDefault();
                    custDropdown.style.display = 'none';
                    setTimeout(function() {
                        var modal = new bootstrap.Modal(document.getElementById(
                            'dealNewModal'));
                        modal.show();
                    }, 100);
                };
                newDiv.appendChild(newBtn);
            }
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    customerListData = data;
                    renderCustomerList();
                    custDropdown.style.display = 'block';
                    document.getElementById('deal-autocomplete-search').value = '';
                });
            document.getElementById('deal-autocomplete-search').oninput = function(e) {
                renderCustomerList(e.target.value);
            };
        });

        // Click outside to close customer dropdown
        document.addEventListener('mousedown', function(e) {
            if (custDropdown.style.display === 'block') {
                if (!custDropdown.contains(e.target) && e.target !== custInput) {
                    custDropdown.style.display = 'none';
                }
            }
        });

        // // Fill organization in deals modal when opened, make readonly if selected
        document.getElementById('dealNewModal').addEventListener('show.bs.modal', function() {
            const orgName = document.getElementById('organization_id').value;

            const orgSelect = document.getElementById('cust_org_id');
            const personId = document.getElementById('person_id').value;
            document.getElementById('deal_person_id').value = personId;

            if (!orgSelect) return;
            let foundIndex = -1;
            for (let i = 0; i < orgSelect.options.length; i++) {
                if (orgSelect.options[i].text === orgName) {
                    foundIndex = i;
                    break;
                }
            }
            const readonlyId = 'cust_org_readonly';
            // If organization is already selected, hide the dropdown and show a readonly input instead
            if (foundIndex !== -1 && orgName !== '') {
                // ensure select has the correct value selected
                orgSelect.selectedIndex = foundIndex;
                // visually hide the select but keep it enabled so value submits
                orgSelect.style.display = 'none';
                // insert readonly input if not present
                if (!document.getElementById(readonlyId)) {
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control';
                    input.id = readonlyId;
                    input.value = orgName;
                    input.readOnly = true;
                    orgSelect.parentNode.insertBefore(input, orgSelect);
                } else {
                    const existing = document.getElementById(readonlyId);
                    existing.value = orgName;
                    existing.style.display = '';
                }
                // ensure hidden input for org id exists so AJAX/FormData can reliably include org id
                const orgId = orgSelect.options[foundIndex] ? orgSelect.options[foundIndex].value : '';
                if (orgId) {
                    let hid = document.getElementById('cust_org_id_hidden');
                    if (!hid) {
                        hid = document.createElement('input');
                        hid.type = 'hidden';
                        hid.id = 'cust_org_id_hidden';
                        hid.name = 'cust_org_id_hidden';
                        orgSelect.parentNode.insertBefore(hid, orgSelect);
                    }
                    hid.value = orgId;
                }
            } else {
                // show select and remove readonly input if exists
                orgSelect.style.display = '';
                const existing = document.getElementById(readonlyId);
                if (existing) existing.remove();
                const hid = document.getElementById('cust_org_id_hidden');
                if (hid) hid.remove();
            }
        });

        // Update probability based on deal stage
        const dealStageDropdown = document.getElementById('deal_stage');
        const reasonForLossContainer = document.getElementById('reason_for_loss_container');
        const reasonForLossTextarea = document.getElementById('reason_for_loss');
        const amountInput = document.getElementById('amount');
        const closedDateInput = document.getElementById('closed_date');
        const probabilityInput = document.getElementById('probability');

        if (dealStageDropdown && probabilityInput) {
            dealStageDropdown.addEventListener('change', function() {
                const selectedOpt = dealStageDropdown.options[dealStageDropdown.selectedIndex];
                const stage = selectedOpt ? (selectedOpt.getAttribute('data-name') || '') : '';
                const prob = selectedOpt ? selectedOpt.getAttribute('data-probability') : '';

                console.log('Selected stage:', stage, 'Probability from attribute:', prob);

                probabilityInput.value = prob || '';
                // Handle Reason for Loss visibility and mandatory status
                if (stage === 'Closed Lost') {
                    reasonForLossContainer.style.display = 'block';
                    reasonForLossTextarea.setAttribute('required', 'required');
                } else {
                    reasonForLossContainer.style.display = 'none';
                    reasonForLossTextarea.removeAttribute('required');
                }

                // Handle Amount and Closed Date mandatory status
                if (stage === 'Closed Won') {
                    amountInput.setAttribute('required', 'required');
                    closedDateInput.setAttribute('required', 'required');
                } else {
                    amountInput.removeAttribute('required');
                    closedDateInput.removeAttribute('required');
                }
            });
        }
        // New deal modal submit with title validation
        document.getElementById('dealNewForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const titleEl = document.getElementById('deal_name');
            const orgEl = document.getElementById('cust_org_id');
            const orgId = document.getElementById('cust_org_id_hidden') ? document.getElementById('cust_org_id_hidden').value : (orgEl ? orgEl.value : '');
            const dupErr = document.getElementById('deal_name_error');
            const orgErr = document.getElementById('cust_org_id_error');
            const closingDateEl = document.getElementById('deal_date');
            const stageEl = document.getElementById('deal_stage');
            console.log('Submitting new deal form with values:', {
                title: titleEl ? titleEl.value : null,
                organization: orgId,
                closingDate: closingDateEl ? closingDateEl.value : null,
                stage: stageEl ? stageEl.value : null
            });

            let hasError = false;

            // Clear previous errors
            if (dupErr) {
                dupErr.style.display = 'none';
                dupErr.textContent = '';
            }
            if (orgErr) {
                orgErr.style.display = 'none';
                orgErr.textContent = '';
            }
            const closingDateErr = document.getElementById('deal_date_error') || document.createElement('div');
            const stageErr = document.getElementById('deal_stage_error') || document.createElement('div');

            if (!closingDateEl) {
                closingDateErr.textContent = 'Closing date is required.';
                closingDateErr.style.display = 'block';
                hasError = true;
            }

            if (stageEl) {
                if (!document.getElementById('deal_stage_error')) {
                    stageErr.id = 'deal_stage_error';
                    stageErr.className = 'text-danger small mt-1';
                    stageErr.style.display = 'none';
                    stageEl.parentNode.appendChild(stageErr);
                }
            }

            // Validate required fields
            const title = titleEl ? titleEl.value.trim() : '';
            const org = orgEl ? orgEl.value.trim() : '';
            const closingDate = closingDateEl ? closingDateEl.value.trim() : '';
            const stage = stageEl ? stageEl.value.trim() : '';

            if (!title) {
                dupErr.textContent = 'Deal name is required.';
                dupErr.style.display = 'block';
                hasError = true;
            }

            if (!org) {
                orgErr.textContent = 'Organization is required.';
                orgErr.style.display = 'block';
                hasError = true;
            }

            if (!closingDate) {
                closingDateErr.textContent = 'Closing date is required.';
                closingDateErr.style.display = 'block';
                hasError = true;
            }

            if (!stage) {
                stageErr.textContent = 'Stage is required.';
                stageErr.style.display = 'block';
                hasError = true;
            }

            if (hasError) {
                return; // Stop submission if there are validation errors
            }


            // Check for duplicate deal name via AJAX
            fetch(`/deals/check-duplicate?title=${encodeURIComponent(title)}&organization=${encodeURIComponent(orgId)}  `)
                .then(response => response.json())
                .then(data => {
                    console.log('Duplicate check response:', data);
                    if (data.duplicate) {
                        dupErr.textContent = 'A deal with this name already exists.';
                        dupErr.style.display = 'block';
                    } else {

                        const dealForm = document.getElementById('dealNewForm');
                        // Submit the form data via AJAX
                        const formData = new FormData(dealForm);

                        fetch('/deals/ajax-create', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log('Deal created successfully:', data);
                                    alert('Deal created successfully!');
                                    dealForm.reset(); // Reset the form
                                    dealInput = document.getElementById('deal_id');
                                    dealInput.value = data.deal ? data.deal.title : '';
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('dealNewModal'));
                                    modal.hide(); // Hide the modal
                                } else {
                                    alert('Failed to create deal. Please try again.');
                                }
                            })
                            .catch(error => {
                                console.error('Error creating deal:', error);
                                alert('An error occurred. Please try again.');
                            });
                    }
                })
                .catch(error => {
                    console.error('Error checking duplicate deal name:', error);
                    dupErr.textContent = 'An error occurred while checking the deal name. Please try again.';
                    dupErr.style.display = 'block';
                });
        });

        //  $('#billing_country').select2({
        //         placeholder: 'Select Country',
        //         allowClear: true,
        //         width: '100%'
        //     });
        // $('#shipping_country').select2({
        //         placeholder: 'Select Country',
        //         allowClear: true,
        //         width: '100%'
        //     });
        // $('#billing_state').select2({
        //         placeholder: 'Select State',
        //         allowClear: true,
        //         width: '100%'
        //     });
        //     $('#shipping_state').select2({
        //         placeholder: 'Select State',
        //         allowClear: true,
        //         width: '100%'
        //     });
        $('#quotation_owner').select2({
            placeholder: 'Select Owner',
            allowClear: true,
            width: '100%',
            height: '500px'
        });

        $('#status').select2({
            placeholder: 'Select Status',
            allowClear: true,
            width: '100%'
        });

    });

    (function() {
        var statesByCountry = {
            'India': ['Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Delhi'],
            'United Arab Emirates': ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'],
            'UAE': ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'],
            'United States': ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'],
            'Canada': ['Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador', 'Nova Scotia', 'Ontario', 'Prince Edward Island', 'Quebec', 'Saskatchewan'],
            'Australia': ['New South Wales', 'Queensland', 'South Australia', 'Tasmania', 'Victoria', 'Western Australia', 'Australian Capital Territory', 'Northern Territory'],
            'United Kingdom': ['England', 'Scotland', 'Wales', 'Northern Ireland'],
            'Saudi Arabia': ['Riyadh', 'Makkah', 'Madinah', 'Eastern Province', 'Asir', 'Tabuk', 'Qassim', 'Hail', 'Jizan', 'Najran', 'Northern Borders', 'El-Jauf', 'Al-Baha'],
            'Qatar': ['Doha', 'Al Rayyan', 'Al Wakrah', 'Al Khor', 'Umm Salal', 'Al Daayen', 'Madinat ash Shamal', 'Shahaniya'],
            'Kuwait': ['Al Asimah (Kuwait City)', 'Hawalli', 'Farwaniya', 'Ahmadi', 'Jahra', 'Mubarak Al-Kabeer'],
            'Bahrain': ['Capital Governorate', 'Muharraq Governorate', 'Northern Governorate', 'Southern Governorate'],
            'Oman': ['Muscat', 'Dhofar', 'Musandam', 'Al Buraimi', 'Ad Dakhiliyah', 'North Al Batinah', 'South Al Batinah', 'North Ash Sharqiyah', 'South Ash Sharqiyah', 'Ad Dhahirah', 'Al Wusta'],
            'Germany': ['Baden-Württemberg', 'Bavaria', 'Berlin', 'Brandenburg', 'Bremen', 'Hamburg', 'Hesse', 'Lower Saxony', 'Mecklenburg-Vorpommern', 'North Rhine-Westphalia', 'Rhineland-Palatinate', 'Saarland', 'Saxony', 'Saxony-Anhalt', 'Schleswig-Holstein', 'Thuringia'],
            'France': ['Île-de-France', 'Auvergne-Rhône-Alpes', 'Bourgogne-Franche-Comté', 'Brittany', 'Centre-Val de Loire', 'Corsica', 'Grand Est', 'Hauts-de-France', 'Normandy', 'Nouvelle-Aquitaine', 'Occitanie', 'Pays de la Loire', 'Provence-Alpes-Côte d\'Azur'],
            'Singapore': ['Central Region', 'East Region', 'North Region', 'North-East Region', 'West Region'],
            'Japan': ['Hokkaido', 'Tohoku', 'Kanto (Tokyo)', 'Chubu', 'Kansai (Osaka)', 'Chugoku', 'Shikoku', 'Kyushu & Okinawa'],
            'China': ['Beijing', 'Shanghai', 'Guangdong', 'Zhejiang', 'Jiangsu', 'Sichuan', 'Shandong', 'Hubei', 'Fujian', 'Henan'],
            'Switzerland': ['Zurich', 'Bern', 'Lucerne', 'Uri', 'Schwyz', 'Obwalden', 'Nidwalden', 'Glarus', 'Zug', 'Fribourg', 'Solothurn', 'Basel-Stadt', 'Basel-Landschaft', 'Schaffhausen', 'Appenzell Ausserrhoden', 'Appenzell Innerrhoden', 'St. Gallen', 'Graubünden', 'Aargau', 'Thurgau', 'Ticino', 'Vaud', 'Valais', 'Neuchâtel', 'Geneva', 'Jura'],
            'Malaysia': ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'],
            'New Zealand': ['Auckland', 'Bay of Plenty', 'Canterbury', 'Gisborne', 'Hawke\'s Bay', 'Manawatu-Whanganui', 'Marlborough', 'Nelson', 'Northland', 'Otago', 'Southland', 'Taranaki', 'Tasman', 'Waikato', 'Wellington', 'West Coast'],
            'South Africa': ['Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape']
        };

        var countryToCurrencyMap = {
            'India': 'INR',
            'United Arab Emirates': 'AED',
            'UAE': 'AED',
            'United States': 'USD',
            'US': 'USD',
            'USA': 'USD',
            'United Kingdom': 'GBP',
            'UK': 'GBP',
            'Canada': 'CAD',
            'Australia': 'AUD',
            'Germany': 'EUR',
            'France': 'EUR',
            'Singapore': 'SGD',
            'Japan': 'JPY',
            'China': 'CNY',
            'Saudi Arabia': 'SAR',
            'Qatar': 'QAR',
            'Kuwait': 'KWD',
            'Bahrain': 'BHD',
            'Oman': 'OMR',
            'Switzerland': 'CHF',
            'Malaysia': 'MYR',
            'New Zealand': 'NZD',
            'South Africa': 'ZAR'
        };

        function syncCurrencyWithCountry(countryName) {
            if (!countryName) return;
            var currencySelect = document.getElementById('currency_code');
            if (!currencySelect) return;
            var code = countryToCurrencyMap[countryName.trim()];
            if (code) {
                var option = currencySelect.querySelector('option[value="' + code + '"]');
                if (option && currencySelect.value !== code) {
                    currencySelect.value = code;
                    currencySelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }

        function populateStates(country, stateSelectId, selected) {
            var stateSelect = document.getElementById(stateSelectId);
            if (!stateSelect) return;
            stateSelect.innerHTML = '';
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.text = 'Select state';
            stateSelect.appendChild(placeholder);
            var list = statesByCountry[country] || [];
            if (list.length > 0) {
                list.forEach(function(s) {
                    var opt = document.createElement('option');
                    opt.value = s;
                    opt.text = s;
                    if (selected && selected === s) opt.selected = true;
                    stateSelect.appendChild(opt);
                });
                stateSelect.disabled = false;
            } else {
                var prev = selected || '';
                if (prev) {
                    var opt = document.createElement('option');
                    opt.value = prev;
                    opt.text = prev;
                    opt.selected = true;
                    stateSelect.appendChild(opt);
                } else {
                    stateSelect.querySelector('option').text = 'No states available';
                }
                stateSelect.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var billingCountryEl = document.getElementById('billing_country');
            var shippingCountryEl = document.getElementById('shipping_country');

            var initialBillingCountry = billingCountryEl ? billingCountryEl.value || '{{ old("billing_country", "India") }}' : '{{ old("billing_country", "India") }}';
            var initialBillingState = '{{ old("billing_state") }}';
            populateStates(initialBillingCountry, 'billing_state', initialBillingState);
            if (initialBillingCountry) {
                syncCurrencyWithCountry(initialBillingCountry);
            }

            if (billingCountryEl) {
                billingCountryEl.addEventListener('change', function() {
                    populateStates(this.value, 'billing_state', '');
                    syncCurrencyWithCountry(this.value);
                });
            }

            var initialShippingCountry = shippingCountryEl ? shippingCountryEl.value || '{{ old("shipping_country", "India") }}' : '{{ old("shipping_country", "India") }}';
            var initialShippingState = '{{ old("shipping_state") }}';
            populateStates(initialShippingCountry, 'shipping_state', initialShippingState);

            if (shippingCountryEl) {
                shippingCountryEl.addEventListener('change', function() {
                    populateStates(this.value, 'shipping_state', '');
                    var bVal = billingCountryEl ? billingCountryEl.value : '';
                    if (!bVal) {
                        syncCurrencyWithCountry(this.value);
                    }
                });
            }
        });


                document.addEventListener("DOMContentLoaded", function() {

            const checkbox = document.getElementById('sameAsBilling');
            const enableShippingToggle = document.getElementById('enable-shipping-address');
            const shippingFieldsWrap = document.getElementById('shipping-address-section');

            const billingFields = {
                country: document.getElementById('billing_country'),
                flat: document.getElementById('billing_flat'),
                street: document.getElementById('billing_street'),
                city: document.getElementById('billing_city'),
                state: document.getElementById('billing_state'),
                zip: document.getElementById('billing_zip'),
            };

            const shippingFields = {
                country: document.getElementById('shipping_country'),
                flat: document.getElementById('shipping_flat'),
                street: document.getElementById('shipping_street'),
                city: document.getElementById('shipping_city'),
                state: document.getElementById('shipping_state'),
                zip: document.getElementById('shipping_zip'),
            };

            function clearShipping() {
                if (shippingFields.country) shippingFields.country.value = '';
                if (shippingFields.flat) shippingFields.flat.value = '';
                if (shippingFields.street) shippingFields.street.value = '';
                if (shippingFields.city) shippingFields.city.value = '';
                if (shippingFields.state) shippingFields.state.value = '';
                if (shippingFields.zip) shippingFields.zip.value = '';
            }

            function setShippingEnabled(enabled) {
                if (shippingFieldsWrap) {
                    shippingFieldsWrap.style.display = enabled ? '' : 'none';
                }
                Object.values(shippingFields).forEach(function (field) {
                    if (!field) return;
                    field.disabled = !enabled;
                });
                document.querySelectorAll('.shipping-required-field').forEach(function (field) {
                    if (enabled) {
                        field.setAttribute('required', 'required');
                    } else {
                        field.removeAttribute('required');
                    }
                });
                var typeHidden = document.querySelector('input[name="shipping_address_type"]');
                if (typeHidden) typeHidden.disabled = !enabled;
                if (checkbox) {
                    checkbox.disabled = !enabled;
                    if (!enabled) {
                        checkbox.checked = false;
                        clearShipping();
                    }
                }
            }

            function copyBillingToShipping() {
                shippingFields.country.value = billingFields.country.value;
                populateStates(billingFields.country.value, 'shipping_state', billingFields.state.value);
                shippingFields.flat.value = billingFields.flat.value;
                shippingFields.street.value = billingFields.street.value;
                shippingFields.city.value = billingFields.city.value;
                shippingFields.zip.value = billingFields.zip.value;
            }

            if (enableShippingToggle) {
                setShippingEnabled(enableShippingToggle.checked);
                enableShippingToggle.addEventListener('change', function () {
                    setShippingEnabled(this.checked);
                });
            }

            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        copyBillingToShipping();
                    } else {
                        clearShipping();
                    }
                });
            }

            Object.values(billingFields).forEach(field => {
                if (!field) return;
                field.addEventListener('input', function() {
                    if (checkbox && checkbox.checked) {
                        copyBillingToShipping();
                    }
                });
                field.addEventListener('change', function() {
                    if (checkbox && checkbox.checked) {
                        copyBillingToShipping();
                    }
                });
            });

        });
    })();
</script>
@include('quotations.partials.rich_editor_scripts')
@endsection