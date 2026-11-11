@extends('layouts.app')

@section('content')
    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4 border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle p-2 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center"
                                style="width: 42px; height: 42px;">
                                <i class="bi bi-whatsapp fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">WhatsApp API Integration</h4>
                                <small class="text-muted">Configure Isarva WhatsApp Gateway or Other Vendor (Direct Meta Cloud API)</small>
                            </div>
                        </div>
                        <div>
                            @if(($setting->is_active ?? true) && !empty($setting->whatsapp_access_token))
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i
                                        class="bi bi-check-circle-fill me-1"></i> Integration Active ({{ ucfirst($setting->provider_type) }})</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill"><i
                                        class="bi bi-exclamation-triangle-fill me-1"></i> Setup Required</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-4">
                        @php $canEdit = $canEdit ?? false; @endphp
                        @unless($canEdit)
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                                <div>Only Super Admin users can access or modify WhatsApp integration settings.</div>
                            </div>
                        @endunless

                        <form method="POST" action="{{ route('whatsapp.settings.update') }}" id="whatsappSettingForm">
                            @csrf

                            <div class="row g-4">
                                <!-- Left Column: Settings Form -->
                                <div class="col-lg-8">
                                    <div class="card border rounded-3 mb-4">
                                        <div class="card-header bg-light fw-semibold text-dark d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-sliders me-2"></i> WhatsApp Provider & Credentials</span>
                                            <span class="badge bg-primary rounded-pill px-3 py-1" id="providerBadge">
                                                {{ ($activeProvider ?? 'isarva') === 'isarva' ? 'Isarva WhatsApp' : 'Other Vendor' }}
                                            </span>
                                        </div>
                                        <div class="card-body p-4">

                                            <!-- Provider Selection Concept -->
                                            <div class="mb-4">
                                                <label class="form-label fw-bold text-dark">Select Integration Provider <span class="text-danger">*</span></label>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="card border p-3 cursor-pointer provider-card {{ old('provider_type', $activeProvider ?? 'isarva') === 'isarva' ? 'border-success bg-success bg-opacity-10' : '' }}" onclick="selectProvider('isarva')">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="provider_type" id="provider_isarva" value="isarva" {{ old('provider_type', $activeProvider ?? 'isarva') === 'isarva' ? 'checked' : '' }} onchange="onProviderChange('isarva')" @if(!$canEdit) disabled @endif>
                                                                <label class="form-check-label fw-bold text-dark cursor-pointer" for="provider_isarva">
                                                                    <i class="bi bi-rocket-takeoff-fill text-success me-1"></i> Isarva WhatsApp
                                                                </label>
                                                                <div class="text-muted small mt-1">Uses IsarvaCRM WACRM Gateway (Token: <code>wacrm_live_...</code>)</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border p-3 cursor-pointer provider-card {{ old('provider_type', $activeProvider ?? 'isarva') === 'other' ? 'border-primary bg-primary bg-opacity-10' : '' }}" onclick="selectProvider('other')">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="provider_type" id="provider_other" value="other" {{ old('provider_type', $activeProvider ?? 'isarva') === 'other' ? 'checked' : '' }} onchange="onProviderChange('other')" @if(!$canEdit) disabled @endif>
                                                                <label class="form-check-label fw-bold text-dark cursor-pointer" for="provider_other">
                                                                    <i class="bi bi-diagram-3-fill text-primary me-1"></i> Other Vendor / Direct Meta
                                                                </label>
                                                                <div class="text-muted small mt-1">Direct Meta Cloud API (Token: <code>EAAG...</code>)</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @error('provider_type')
                                                <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>

                                            <hr class="my-4">

                                            <!-- Active Status Toggle -->
                                            <div class="form-check form-switch mb-4 p-3 bg-light rounded border d-flex justify-content-between align-items-center">
                                                <div>
                                                    <label class="form-check-label fw-bold text-dark mb-0" for="is_active">Enable Integration for Selected Provider</label>
                                                    <div class="text-muted small" id="activeStatusHelpText">Turn on/off automated WhatsApp notifications for this provider</div>
                                                </div>
                                                <input class="form-check-input ms-0 fs-4" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $setting->is_active ?? true) ? 'checked' : '' }} @if(!$canEdit) disabled @endif>
                                            </div>

                                            <!-- WhatsApp Access Token / API Key -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold" for="whatsapp_access_token">
                                                    <span id="tokenLabel">IsarvaCRM API Key</span> <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white"><i class="bi bi-key-fill text-muted"></i></span>
                                                    <input type="password" name="whatsapp_access_token" id="whatsapp_access_token"
                                                        class="form-control @error('whatsapp_access_token') is-invalid @enderror"
                                                        value="{{ old('whatsapp_access_token', $setting->whatsapp_access_token ?? '') }}"
                                                        placeholder="wacrm_live_..." @if(!$canEdit) disabled @endif required>
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleTokenBtn" onclick="toggleTokenVisibility()">
                                                        <i class="bi bi-eye" id="toggleTokenIcon"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text" id="tokenHelpText">IsarvaCRM API key (Authorization: Bearer <code>wacrm_live_...</code>).</div>
                                                @error('whatsapp_access_token')
                                                <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>

                                            <!-- WhatsApp Phone Number ID -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold" for="whatsapp_phone_number_id">
                                                    Meta Phone Number ID <span class="text-muted">(Optional for Isarva WhatsApp)</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white"><i class="bi bi-telephone-fill text-muted"></i></span>
                                                    <input type="text" name="whatsapp_phone_number_id" id="whatsapp_phone_number_id"
                                                        class="form-control @error('whatsapp_phone_number_id') is-invalid @enderror"
                                                        value="{{ old('whatsapp_phone_number_id', $setting->whatsapp_phone_number_id ?? '') }}"
                                                        placeholder="e.g. 1114172415111679" @if(!$canEdit) disabled @endif>
                                                </div>
                                                <div class="form-text" id="phoneHelpText">Meta Phone number ID from Meta / WACRM settings.</div>
                                                @error('whatsapp_phone_number_id')
                                                <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="row">
                                                <!-- API Base URL -->
                                                <div class="col-md-7 mb-3" id="apiUrlCol">
                                                    <label class="form-label fw-semibold" for="whatsapp_api_url">
                                                        WhatsApp API Host / Base URL <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="url" name="whatsapp_api_url" id="whatsapp_api_url"
                                                        class="form-control @error('whatsapp_api_url') is-invalid @enderror"
                                                        value="{{ old('whatsapp_api_url', $setting->whatsapp_api_url ?? '') }}"
                                                        placeholder="https://your-api-domain.com/" required @if(!$canEdit) disabled @endif>
                                                    <div class="form-text" id="apiUrlHelpText">Enter base API host URL</div>
                                                    @error('whatsapp_api_url')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                </div>

                                                <!-- API Version (Other Vendor Only) -->
                                                <div class="col-md-5 mb-3" id="apiVersionCol">
                                                    <label class="form-label fw-semibold" for="whatsapp_api_version">
                                                        API Version <span class="text-muted">(Optional)</span>
                                                    </label>
                                                    <input type="text" name="whatsapp_api_version" id="whatsapp_api_version"
                                                        class="form-control @error('whatsapp_api_version') is-invalid @enderror"
                                                        value="{{ old('whatsapp_api_version', $setting->whatsapp_api_version ?? '') }}"
                                                        placeholder="v20.0" @if(!$canEdit) disabled @endif>
                                                    <div class="form-text">Version (e.g. <code>v20.0</code>)</div>
                                                    @error('whatsapp_api_version')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- WhatsApp Template ID / Name -->
                                                <div class="col-md-7 mb-3">
                                                    <label class="form-label fw-semibold" for="whatsapp_template_id">
                                                        Default WhatsApp Template Name
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white"><i class="bi bi-file-earmark-code-fill text-muted"></i></span>
                                                        <input type="text" name="whatsapp_template_id" id="whatsapp_template_id"
                                                            class="form-control @error('whatsapp_template_id') is-invalid @enderror"
                                                            value="{{ old('whatsapp_template_id', $setting->whatsapp_template_id ?? '') }}"
                                                            placeholder="e.g. appointment_reminder or login_otp_notification"
                                                            @if(!$canEdit) disabled @endif>
                                                    </div>
                                                    <div class="form-text">Approved WhatsApp Message Template Name (general / OTP).</div>
                                                    @error('whatsapp_template_id')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                </div>

                                                <!-- WhatsApp Template Language Code -->
                                                <div class="col-md-5 mb-3">
                                                    <label class="form-label fw-semibold" for="whatsapp_language_code">
                                                        Language Code <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white"><i class="bi bi-translate text-muted"></i></span>
                                                        <input type="text" name="whatsapp_language_code" id="whatsapp_language_code"
                                                            class="form-control @error('whatsapp_language_code') is-invalid @enderror"
                                                            value="{{ old('whatsapp_language_code', $setting->whatsapp_language_code ?? 'en_US') }}"
                                                            placeholder="e.g. en_US, en, hi, ar" required @if(!$canEdit) disabled @endif>
                                                    </div>
                                                    <div class="form-text">e.g. <code>en_US</code>, <code>en</code>, <code>hi</code></div>
                                                    @error('whatsapp_language_code')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                </div>
                                            </div>

                                            <hr class="my-3">
                                            <h6 class="fw-bold text-success mb-3"><i class="bi bi-file-earmark-pdf me-1"></i> Quotation Template (this provider)</h6>
                                            <p class="small text-muted mb-3">
                                                Short professional body with Document (PDF) header.
                                                Variables: <code>@{{1}}</code> name, <code>@{{2}}</code> company, <code>@{{3}}</code> quote no,
                                                <code>@{{4}}</code> client, <code>@{{5}}</code> amount, <code>@{{6}}</code> sender.
                                            </p>
                                            <div class="row">
                                                <div class="col-md-7 mb-3">
                                                    <label class="form-label fw-semibold" for="quotation_template_id">
                                                        Quotation Template Name
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white"><i class="bi bi-receipt text-muted"></i></span>
                                                        <input type="text" name="quotation_template_id" id="quotation_template_id"
                                                            class="form-control @error('quotation_template_id') is-invalid @enderror"
                                                            value="{{ old('quotation_template_id', $setting->quotation_template_id ?? '') }}"
                                                            placeholder="e.g. quotation_share"
                                                            @if(!$canEdit) disabled @endif>
                                                    </div>
                                                    <div class="form-text">Approved template name for sending quotation + PDF (saved per provider).</div>
                                                    @error('quotation_template_id')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-5 mb-3">
                                                    <label class="form-label fw-semibold" for="quotation_language_code">
                                                        Quotation Language Code
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white"><i class="bi bi-translate text-muted"></i></span>
                                                        <input type="text" name="quotation_language_code" id="quotation_language_code"
                                                            class="form-control @error('quotation_language_code') is-invalid @enderror"
                                                            value="{{ old('quotation_language_code', $setting->quotation_language_code ?? ($setting->whatsapp_language_code ?? 'en_US')) }}"
                                                            placeholder="e.g. en_US"
                                                            @if(!$canEdit) disabled @endif>
                                                    </div>
                                                    <div class="form-text">Must match the approved quotation template language.</div>
                                                    @error('quotation_language_code')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold" for="quotation_message_format">
                                                    Quotation Message Format
                                                </label>
                                                <div class="alert alert-info border-0 small mb-2 py-2 px-3">
                                                    <div class="fw-semibold mb-1"><i class="bi bi-check-circle me-1"></i> Currently applied format</div>
                                                    <div class="font-monospace text-dark" id="quotationAppliedFormatText">{{ $setting->quotation_message_format }}</div>
                                                    @if(empty($setting->getRawOriginal('quotation_message_format')))
                                                        <div class="text-muted mt-1">System default is in use until you save a custom format.</div>
                                                    @endif
                                                </div>
                                                <textarea name="quotation_message_format" id="quotation_message_format" rows="4"
                                                    class="form-control @error('quotation_message_format') is-invalid @enderror"
                                                    placeholder="{{ \App\Models\WhatsappSetting::defaultQuotationMessageFormat() }}"
                                                    @if(!$canEdit) disabled @endif>{{ old('quotation_message_format', $setting->quotation_message_format) }}</textarea>
                                                <div class="form-text">
                                                    Preview text for quotation WhatsApp sends. Use placeholders:
                                                    @foreach(\App\Models\WhatsappSetting::quotationMessagePlaceholders() as $token => $label)
                                                        <code>{{ $token }}</code>@if(!$loop->last), @endif
                                                    @endforeach
                                                </div>
                                                @error('quotation_message_format')
                                                <div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                <div class="alert alert-light border small mt-3 mb-0" id="quotationMessageFormatPreview">
                                                    <strong>Preview with sample data:</strong><br>
                                                    <span id="quotationMessageFormatPreviewText">{{ \App\Models\WhatsappSetting::renderQuotationMessageFormat($setting->quotation_message_format, [
                                                        'contact_name' => 'John Doe',
                                                        'company_name' => 'ISARVA INFOTECH PRIVATE LIMITED',
                                                        'quote_no' => 'Q-123456',
                                                        'client_name' => 'SRJ Piping',
                                                        'amount' => 'INR 76,000.00',
                                                        'sender_name' => 'Abhishek A',
                                                    ]) }}</span>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between">
                                        @if($canEdit)
                                            <button type="submit" class="btn btn-success px-4 py-2 fw-semibold">
                                                <i class="bi bi-save me-1"></i> Save WhatsApp Settings
                                            </button>

                                            <button type="button" class="btn btn-outline-primary px-4 py-2 fw-semibold"
                                                onclick="handleSendTestClick()">
                                                <i class="bi bi-send me-1"></i> Send Test Message
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Right Column: Setup Instructions & Help Card -->
                                <div class="col-lg-4">
                                    <!-- Isarva Help Card -->
                                    <div class="card border rounded-3 mb-4 bg-light" id="helpIsarva">
                                        <div class="card-header bg-white fw-semibold text-success">
                                            <i class="bi bi-rocket-takeoff-fill me-2"></i> IsarvaCRM WhatsApp API Guide
                                        </div>
                                        <div class="card-body p-3">
                                            <ol class="ps-3 mb-0 small text-secondary">
                                                <li class="mb-2"><strong>API URL:</strong> Enter host domain (e.g. <code>https://wacrm.isarva.vercel.app/</code>)</li>
                                                <li class="mb-2"><strong>API Key:</strong> Get your key starting with <code>wacrm_live_...</code> from Settings &rarr; API keys.</li>
                                                <li class="mb-2"><strong>Endpoint:</strong> Calls <code>{whatsapp_api_url}api/v1/messages</code> with <code>Authorization: Bearer wacrm_live_...</code></li>
                                                <li class="mb-2"><strong>PDF upload:</strong> Quotation PDF is uploaded first to <code>{whatsapp_api_url}api/v1/media</code>, then sent via <code>api/v1/messages</code> using <code>document.id</code> in the template header.</li>
                                                <li class="mb-2"><strong>Payload:</strong> Quotation uses <code>body</code> (6 string variables) + <code>components</code> header with uploaded media id.</li>
                                            </ol>
                                        </div>
                                    </div>

                                    <!-- Other Vendor Help Card -->
                                    <div class="card border rounded-3 mb-4 bg-light d-none" id="helpOther">
                                        <div class="card-header bg-white fw-semibold text-primary">
                                            <i class="bi bi-diagram-3-fill me-2"></i> Direct Meta Cloud API Guide
                                        </div>
                                        <div class="card-body p-3">
                                            <ol class="ps-3 mb-0 small text-secondary">
                                                <li class="mb-2"><strong>API URL:</strong> Set to <code>https://graph.facebook.com</code></li>
                                                <li class="mb-2"><strong>Access Token:</strong> Get Meta System User Access Token starting with <code>EAAG...</code></li>
                                                <li class="mb-2"><strong>Phone Number ID:</strong> Meta Phone Number ID.</li>
                                                <li class="mb-2"><strong>Endpoint:</strong> Calls Meta Graph API <code>{api_url}/{version}/{phone_id}/messages</code></li>
                                            </ol>
                                        </div>
                                    </div>

                                    <div class="card border border-info rounded-3 bg-info bg-opacity-10">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold text-info-emphasis mb-2"><i class="bi bi-shield-check me-1"></i> Isolated Database Records</h6>
                                            <p class="small text-secondary mb-0">Isarva WhatsApp and Other Vendor settings are stored in separate database records. Activating one automatically deactivates the other (sets <code>is_active = 0</code>).</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Test Message Modal -->
    <div class="modal fade" id="testWhatsappModal" tabindex="-1" aria-labelledby="testWhatsappModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="testWhatsappModalLabel"><i class="bi bi-whatsapp text-success me-2"></i> Send Test WhatsApp Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="test-alert-container"></div>
                    <form id="testWhatsappForm">
                        @csrf
                        <div class="mb-3">
                            <label for="test_phone_number" class="form-label fw-semibold">Recipient Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                <input type="text" class="form-control" id="test_phone_number" name="test_phone_number"
                                    placeholder="e.g. +919876543210 or 919876543210" required>
                            </div>
                            <div class="form-text">Include country code (e.g., +91 for India, +1 for USA).</div>
                        </div>

                        <div class="mb-3">
                            <label for="test_params" class="form-label fw-semibold">Template Parameters (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-list-nested"></i></span>
                                <input type="text" class="form-control" id="test_params" name="test_params"
                                    placeholder="e.g. 123456 or Parameter1, Parameter2">
                            </div>
                            <div class="form-text">If your template uses variables like <code>&#123;&#123;1&#125;&#125;</code>, enter single value (e.g. <code>123456</code>) or comma-separated values.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="sendTestBtn" onclick="submitTestWhatsapp()">
                        <span id="sendTestBtnText"><i class="bi bi-send me-1"></i> Send Test</span>
                        <span id="sendTestSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Database Records per Provider Type
        const providerRecords = {
            isarva: {
                token: @json(old('whatsapp_access_token', $isarvaSetting->whatsapp_access_token ?? '')),
                phone_id: @json(old('whatsapp_phone_number_id', $isarvaSetting->whatsapp_phone_number_id ?? '')),
                url: @json(old('whatsapp_api_url', $isarvaSetting->whatsapp_api_url ?? '')),
                version: @json(old('whatsapp_api_version', $isarvaSetting->whatsapp_api_version ?? '')),
                template: @json(old('whatsapp_template_id', $isarvaSetting->whatsapp_template_id ?? '')),
                language: @json(old('whatsapp_language_code', $isarvaSetting->whatsapp_language_code ?? 'en_US')),
                quotation_template: @json(old('quotation_template_id', $isarvaSetting->quotation_template_id ?? '')),
                quotation_language: @json(old('quotation_language_code', $isarvaSetting->quotation_language_code ?? ($isarvaSetting->whatsapp_language_code ?? 'en_US'))),
                quotation_message_format: @json(old('quotation_message_format', $isarvaSetting->quotation_message_format ?? '')),
                is_active: @json($isarvaSetting->is_active ? 1 : 0)
            },
            other: {
                token: @json(old('whatsapp_access_token', $otherSetting->whatsapp_access_token ?? '')),
                phone_id: @json(old('whatsapp_phone_number_id', $otherSetting->whatsapp_phone_number_id ?? '')),
                url: @json(old('whatsapp_api_url', $otherSetting->whatsapp_api_url ?? '')),
                version: @json(old('whatsapp_api_version', $otherSetting->whatsapp_api_version ?? '')),
                template: @json(old('whatsapp_template_id', $otherSetting->whatsapp_template_id ?? '')),
                language: @json(old('whatsapp_language_code', $otherSetting->whatsapp_language_code ?? 'en_US')),
                quotation_template: @json(old('quotation_template_id', $otherSetting->quotation_template_id ?? '')),
                quotation_language: @json(old('quotation_language_code', $otherSetting->quotation_language_code ?? ($otherSetting->whatsapp_language_code ?? 'en_US'))),
                quotation_message_format: @json(old('quotation_message_format', $otherSetting->quotation_message_format ?? '')),
                is_active: @json($otherSetting->is_active ? 1 : 0)
            }
        };

        let activeProviderState = null;
        const defaultQuotationMessageFormat = @json(\App\Models\WhatsappSetting::defaultQuotationMessageFormat());

        function renderQuotationMessagePreview(format, vars) {
            const template = (format || '').trim() || defaultQuotationMessageFormat;
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

        function updateQuotationMessageFormatPreview() {
            const input = document.getElementById('quotation_message_format');
            const preview = document.getElementById('quotationMessageFormatPreviewText');
            const applied = document.getElementById('quotationAppliedFormatText');
            const format = (input?.value || '').trim() || defaultQuotationMessageFormat;
            if (applied) applied.textContent = format;
            if (!input || !preview) return;
            preview.textContent = renderQuotationMessagePreview(input.value, {
                contact_name: 'John Doe',
                company_name: 'ISARVA INFOTECH PRIVATE LIMITED',
                quote_no: 'Q-123456',
                client_name: 'SRJ Piping',
                amount: 'INR 76,000.00',
                sender_name: 'Abhishek A',
            });
        }

        function selectProvider(type) {
            const radio = document.getElementById('provider_' + type);
            if (radio) {
                radio.checked = true;
                onProviderChange(type);
            }
        }

        function onProviderChange(type) {
            const tokenLabel = document.getElementById('tokenLabel');
            const tokenInput = document.getElementById('whatsapp_access_token');
            const tokenHelp = document.getElementById('tokenHelpText');
            const phoneInput = document.getElementById('whatsapp_phone_number_id');
            const urlInput = document.getElementById('whatsapp_api_url');
            const versionInput = document.getElementById('whatsapp_api_version');
            const templateInput = document.getElementById('whatsapp_template_id');
            const langInput = document.getElementById('whatsapp_language_code');
            const quotationTemplateInput = document.getElementById('quotation_template_id');
            const quotationLangInput = document.getElementById('quotation_language_code');
            const quotationMessageFormatInput = document.getElementById('quotation_message_format');
            const activeInput = document.getElementById('is_active');

            const apiUrlCol = document.getElementById('apiUrlCol');
            const apiVersionCol = document.getElementById('apiVersionCol');
            const badge = document.getElementById('providerBadge');

            const helpIsarva = document.getElementById('helpIsarva');
            const helpOther = document.getElementById('helpOther');

            // Save currently typed form state into providerRecords before switching
            if (activeProviderState && providerRecords[activeProviderState]) {
                providerRecords[activeProviderState].token = tokenInput ? tokenInput.value : '';
                providerRecords[activeProviderState].phone_id = phoneInput ? phoneInput.value : '';
                providerRecords[activeProviderState].url = urlInput ? urlInput.value : '';
                providerRecords[activeProviderState].version = versionInput ? versionInput.value : '';
                providerRecords[activeProviderState].template = templateInput ? templateInput.value : '';
                providerRecords[activeProviderState].language = langInput ? langInput.value : '';
                providerRecords[activeProviderState].quotation_template = quotationTemplateInput ? quotationTemplateInput.value : '';
                providerRecords[activeProviderState].quotation_language = quotationLangInput ? quotationLangInput.value : '';
                providerRecords[activeProviderState].quotation_message_format = quotationMessageFormatInput ? quotationMessageFormatInput.value : '';
                providerRecords[activeProviderState].is_active = activeInput && activeInput.checked ? 1 : 0;
            }

            activeProviderState = type;
            const targetRecord = providerRecords[type] || {};

            document.querySelectorAll('.provider-card').forEach(c => {
                c.classList.remove('border-success', 'bg-success', 'border-primary', 'bg-primary', 'bg-opacity-10');
            });

            // Populate form fields with selected provider record
            if (tokenInput) tokenInput.value = targetRecord.token || '';
            if (phoneInput) phoneInput.value = targetRecord.phone_id || '';
            if (urlInput) urlInput.value = targetRecord.url || '';
            if (versionInput) versionInput.value = targetRecord.version || '';
            if (templateInput) templateInput.value = targetRecord.template || '';
            if (langInput) langInput.value = targetRecord.language || 'en_US';
            if (quotationTemplateInput) quotationTemplateInput.value = targetRecord.quotation_template || '';
            if (quotationLangInput) quotationLangInput.value = targetRecord.quotation_language || targetRecord.language || 'en_US';
            if (quotationMessageFormatInput) quotationMessageFormatInput.value = targetRecord.quotation_message_format || '';
            if (activeInput) activeInput.checked = (targetRecord.is_active == 1);
            updateQuotationMessageFormatPreview();
            hideWhatsappToken();

            if (type === 'isarva') {
                document.getElementById('provider_isarva').closest('.provider-card').classList.add('border-success', 'bg-success', 'bg-opacity-10');
                tokenLabel.innerText = 'IsarvaCRM API Key';
                tokenInput.placeholder = 'wacrm_live_...';
                tokenHelp.innerHTML = 'IsarvaCRM API key (Authorization: Bearer <code>wacrm_live_...</code>).';
                urlInput.placeholder = 'https://your-wacrm-domain.com/';

                // Hide Other Vendor specific fields
                if (apiVersionCol) apiVersionCol.classList.add('d-none');
                if (apiUrlCol) apiUrlCol.className = 'col-md-12 mb-3';

                badge.innerText = 'Isarva WhatsApp';
                badge.className = 'badge bg-success rounded-pill px-3 py-1';

                helpIsarva.classList.remove('d-none');
                helpOther.classList.add('d-none');
            } else {
                document.getElementById('provider_other').closest('.provider-card').classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                tokenLabel.innerText = 'Meta System User Access Token';
                tokenInput.placeholder = 'EAAG...';
                tokenHelp.innerHTML = 'Permanent System User Access Token from Meta Business Manager with <code>whatsapp_business_messaging</code> scope.';
                urlInput.placeholder = 'https://graph.facebook.com';
                versionInput.placeholder = 'v20.0';

                // Show Other Vendor specific fields
                if (apiVersionCol) apiVersionCol.classList.remove('d-none');
                if (apiUrlCol) apiUrlCol.className = 'col-md-7 mb-3';

                badge.innerText = 'Other Vendor / Meta Direct';
                badge.className = 'badge bg-primary rounded-pill px-3 py-1';

                helpIsarva.classList.add('d-none');
                helpOther.classList.remove('d-none');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const currentProvider = '{{ old("provider_type", $activeProvider ?? "isarva") }}';
            onProviderChange(currentProvider);
        });

        function handleSendTestClick() {
            const activeInput = document.getElementById('is_active');
            if (activeInput && !activeInput.checked) {
                showDisabledSwal();
                return;
            }

            const testModalEl = document.getElementById('testWhatsappModal');
            if (testModalEl) {
                const modal = new bootstrap.Modal(testModalEl);
                modal.show();
            }
        }

        function showDisabledSwal(msg) {
            const message = msg || 'WhatsApp Integration is currently disabled for the selected provider. Please turn on "Enable Integration for Selected Provider" and save settings to send messages.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Integration Disabled',
                    text: message,
                    confirmButtonColor: '#198754'
                });
            } else if (typeof swal !== 'undefined') {
                swal('Integration Disabled', message, 'warning');
            } else {
                alert(message);
            }
        }

        function hideWhatsappToken() {
            const input = document.getElementById('whatsapp_access_token');
            const icon = document.getElementById('toggleTokenIcon');
            if (!input) return;
            input.type = 'password';
            if (icon) {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        function revealWhatsappToken() {
            const input = document.getElementById('whatsapp_access_token');
            const icon = document.getElementById('toggleTokenIcon');
            if (!input) return;
            input.type = 'text';
            if (icon) {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
            // Auto-hide after 60 seconds for safety
            setTimeout(function () {
                hideWhatsappToken();
            }, 60000);
        }

        function toggleTokenVisibility() {
            const input = document.getElementById('whatsapp_access_token');
            if (!input) return;

            // Hiding never needs password
            if (input.type === 'text') {
                hideWhatsappToken();
                return;
            }

            // Showing requires login password
            if (typeof Swal === 'undefined') {
                alert('Password confirmation is required to view the token.');
                return;
            }

            Swal.fire({
                title: 'Confirm your password',
                text: 'Enter your login password to view the WhatsApp token.',
                input: 'password',
                inputPlaceholder: 'Login password',
                inputAttributes: {
                    autocapitalize: 'off',
                    autocomplete: 'current-password',
                    maxlength: 255
                },
                showCancelButton: true,
                confirmButtonText: 'Verify & Show',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#198754',
                showLoaderOnConfirm: true,
                preConfirm: (password) => {
                    if (!password) {
                        Swal.showValidationMessage('Password is required');
                        return false;
                    }

                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('password', password);

                    return fetch('{{ route("whatsapp.settings.verify_password") }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) {
                            throw new Error(data.error || 'Incorrect password. Unable to view token.');
                        }
                        return data;
                    })
                    .catch((error) => {
                        Swal.showValidationMessage(error.message || 'Incorrect password. Unable to view token.');
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value && result.value.success) {
                    revealWhatsappToken();
                }
            });
        }

        function submitTestWhatsapp() {
            const activeInput = document.getElementById('is_active');
            if (activeInput && !activeInput.checked) {
                const testModalEl = document.getElementById('testWhatsappModal');
                if (testModalEl) {
                    const bsModal = bootstrap.Modal.getInstance(testModalEl);
                    if (bsModal) bsModal.hide();
                }
                showDisabledSwal();
                return;
            }

            const phoneInput = document.getElementById('test_phone_number');
            const paramsInput = document.getElementById('test_params');
            const alertContainer = document.getElementById('test-alert-container');
            const btnText = document.getElementById('sendTestBtnText');
            const spinner = document.getElementById('sendTestSpinner');
            const btn = document.getElementById('sendTestBtn');

            alertContainer.innerHTML = '';

            if (!phoneInput.value.trim()) {
                alertContainer.innerHTML = '<div class="alert alert-warning py-2 small">Please enter a valid phone number.</div>';
                return;
            }

            btn.disabled = true;
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('test_phone_number', phoneInput.value.trim());
            if (paramsInput && paramsInput.value.trim()) {
                formData.append('test_params', paramsInput.value.trim());
            }

            fetch('{{ route("whatsapp.settings.test") }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    btn.disabled = false;
                    btnText.classList.remove('d-none');
                    spinner.classList.add('d-none');

                    if (data.success) {
                        alertContainer.innerHTML = `<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i> ${data.message}</div>`;
                    } else if (data.disabled || (data.error && data.error.toLowerCase().includes('disabled'))) {
                        const testModalEl = document.getElementById('testWhatsappModal');
                        if (testModalEl) {
                            const bsModal = bootstrap.Modal.getInstance(testModalEl);
                            if (bsModal) bsModal.hide();
                        }
                        showDisabledSwal(data.error);
                    } else {
                        let detailStr = data.error || 'Failed to send test message.';
                        alertContainer.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i> ${detailStr}</div>`;
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btnText.classList.remove('d-none');
                    spinner.classList.add('d-none');
                    alertContainer.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i> An error occurred while testing. Check server logs.</div>`;
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const formatInput = document.getElementById('quotation_message_format');
            if (formatInput) {
                formatInput.addEventListener('input', updateQuotationMessageFormatPreview);
                updateQuotationMessageFormatPreview();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection