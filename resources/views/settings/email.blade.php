@extends('layouts.app')

@section('content')
    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4 border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center"
                                style="width: 42px; height: 42px;">
                                <i class="bi bi-envelope-fill fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">Email Integration</h4>
                                <small class="text-muted">Configure SMTP or Sendmail for CRM notifications and system emails</small>
                            </div>
                        </div>
                        <div>
                            @if(($setting->is_active ?? false) && !empty($setting->mail_from_address))
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                    <i class="bi bi-check-circle-fill me-1"></i> Integration Active ({{ ucfirst($setting->provider_type) }})
                                </span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Setup Required
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-4">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @php $canEdit = $canEdit ?? false; @endphp
                        @unless($canEdit)
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                                <div>Only Super Admin users can access or modify email integration settings.</div>
                            </div>
                        @endunless

                        <form method="POST" action="{{ route('email.settings.update') }}" id="emailSettingForm">
                            @csrf

                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <div class="card border rounded-3 mb-4">
                                        <div class="card-header bg-light fw-semibold text-dark d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-sliders me-2"></i> Email Provider & Credentials</span>
                                            <span class="badge bg-primary rounded-pill px-3 py-1" id="providerBadge">
                                                {{ ($activeProvider ?? 'smtp') === 'smtp' ? 'SMTP' : 'Sendmail' }}
                                            </span>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-bold text-dark">Select Email Provider <span class="text-danger">*</span></label>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="card border p-3 cursor-pointer provider-card {{ old('provider_type', $activeProvider ?? 'smtp') === 'smtp' ? 'border-primary bg-primary bg-opacity-10' : '' }}" onclick="selectProvider('smtp')">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="provider_type" id="provider_smtp" value="smtp"
                                                                    {{ old('provider_type', $activeProvider ?? 'smtp') === 'smtp' ? 'checked' : '' }}
                                                                    onchange="onProviderChange('smtp')" @if(!$canEdit) disabled @endif>
                                                                <label class="form-check-label fw-bold text-dark cursor-pointer" for="provider_smtp">
                                                                    <i class="bi bi-hdd-network-fill text-primary me-1"></i> SMTP
                                                                </label>
                                                                <div class="text-muted small mt-1">Gmail, Outlook, Zoho, or custom SMTP server</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border p-3 cursor-pointer provider-card {{ old('provider_type', $activeProvider ?? 'smtp') === 'sendmail' ? 'border-success bg-success bg-opacity-10' : '' }}" onclick="selectProvider('sendmail')">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="provider_type" id="provider_sendmail" value="sendmail"
                                                                    {{ old('provider_type', $activeProvider ?? 'smtp') === 'sendmail' ? 'checked' : '' }}
                                                                    onchange="onProviderChange('sendmail')" @if(!$canEdit) disabled @endif>
                                                                <label class="form-check-label fw-bold text-dark cursor-pointer" for="provider_sendmail">
                                                                    <i class="bi bi-terminal-fill text-success me-1"></i> Sendmail
                                                                </label>
                                                                <div class="text-muted small mt-1">Use server sendmail / PHP mail transport</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @error('provider_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>

                                            <hr class="my-4">

                                            <div class="form-check form-switch mb-4 p-3 bg-light rounded border d-flex justify-content-between align-items-center">
                                                <div>
                                                    <label class="form-check-label fw-bold text-dark mb-0" for="is_active">Enable Integration for Selected Provider</label>
                                                    <div class="text-muted small">Turn on/off outbound email for this provider</div>
                                                </div>
                                                <input class="form-check-input ms-0 fs-4" type="checkbox" name="is_active" id="is_active" value="1"
                                                    {{ old('is_active', $setting->is_active ?? false) ? 'checked' : '' }} @if(!$canEdit) disabled @endif>
                                            </div>

                                            <div id="smtpFields">
                                                <div class="row">
                                                    <div class="col-md-8 mb-3">
                                                        <label class="form-label fw-semibold" for="mail_host">SMTP Host <span class="text-danger">*</span></label>
                                                        <input type="text" name="mail_host" id="mail_host"
                                                            class="form-control @error('mail_host') is-invalid @enderror"
                                                            value="{{ old('mail_host', $setting->mail_host ?? '') }}"
                                                            placeholder="e.g. smtp.gmail.com" @if(!$canEdit) disabled @endif>
                                                        @error('mail_host')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label fw-semibold" for="mail_port">Port <span class="text-danger">*</span></label>
                                                        <input type="number" name="mail_port" id="mail_port"
                                                            class="form-control @error('mail_port') is-invalid @enderror"
                                                            value="{{ old('mail_port', $setting->mail_port ?? 587) }}"
                                                            placeholder="587" @if(!$canEdit) disabled @endif>
                                                        @error('mail_port')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label fw-semibold" for="mail_encryption">Encryption</label>
                                                        <select name="mail_encryption" id="mail_encryption" class="form-select" @if(!$canEdit) disabled @endif>
                                                            @php $enc = old('mail_encryption', $setting->mail_encryption ?? 'tls'); @endphp
                                                            <option value="tls" @selected($enc === 'tls')>TLS (587)</option>
                                                            <option value="ssl" @selected($enc === 'ssl')>SSL (465)</option>
                                                            <option value="none" @selected($enc === 'none' || $enc === null || $enc === '')>None</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-8 mb-3">
                                                        <label class="form-label fw-semibold" for="mail_username">SMTP Username</label>
                                                        <input type="text" name="mail_username" id="mail_username"
                                                            class="form-control @error('mail_username') is-invalid @enderror"
                                                            value="{{ old('mail_username', $setting->mail_username ?? '') }}"
                                                            placeholder="e.g. notifications@yourcompany.com" @if(!$canEdit) disabled @endif>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold" for="mail_password">SMTP Password</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-white"><i class="bi bi-key-fill text-muted"></i></span>
                                                        <input type="password" name="mail_password" id="mail_password"
                                                            class="form-control @error('mail_password') is-invalid @enderror"
                                                            value="{{ old('mail_password', $setting->mail_password ?? '') }}"
                                                            placeholder="App password or SMTP password" @if(!$canEdit) disabled @endif>
                                                        <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()">
                                                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-text">Leave blank when saving to keep the existing password.</div>
                                                </div>
                                            </div>

                                            <div id="sendmailFields" class="d-none">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold" for="sendmail_path">Sendmail Path</label>
                                                    <input type="text" name="sendmail_path" id="sendmail_path"
                                                        class="form-control @error('sendmail_path') is-invalid @enderror"
                                                        value="{{ old('sendmail_path', $setting->sendmail_path ?? config('mail.mailers.sendmail.path')) }}"
                                                        placeholder="/usr/sbin/sendmail -bs -i" @if(!$canEdit) disabled @endif>
                                                </div>
                                            </div>

                                            <hr class="my-3">
                                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-badge me-1"></i> Sender Details</h6>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold" for="mail_from_address">From Email <span class="text-danger">*</span></label>
                                                    <input type="email" name="mail_from_address" id="mail_from_address"
                                                        class="form-control @error('mail_from_address') is-invalid @enderror"
                                                        value="{{ old('mail_from_address', $setting->mail_from_address ?? config('mail.from.address')) }}"
                                                        required @if(!$canEdit) disabled @endif>
                                                    @error('mail_from_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold" for="mail_from_name">From Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="mail_from_name" id="mail_from_name"
                                                        class="form-control @error('mail_from_name') is-invalid @enderror"
                                                        value="{{ old('mail_from_name', $setting->mail_from_name ?? config('mail.from.name')) }}"
                                                        required @if(!$canEdit) disabled @endif>
                                                    @error('mail_from_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($canEdit)
                                        <div class="d-flex align-items-center justify-content-between">
                                            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                                                <i class="bi bi-save me-1"></i> Save Email Settings
                                            </button>
                                            <button type="button" class="btn btn-outline-primary px-4 py-2 fw-semibold" onclick="handleSendTestClick()">
                                                <i class="bi bi-send me-1"></i> Send Test Email
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-lg-4">
                                    <div class="card border rounded-3 mb-4 bg-light" id="helpSmtp">
                                        <div class="card-header bg-white fw-semibold text-primary">
                                            <i class="bi bi-hdd-network-fill me-2"></i> SMTP Setup Guide
                                        </div>
                                        <div class="card-body p-3">
                                            <ol class="ps-3 mb-0 small text-secondary">
                                                <li class="mb-2"><strong>Host & Port:</strong> Use your provider SMTP host (e.g. <code>smtp.gmail.com</code>, port <code>587</code> TLS).</li>
                                                <li class="mb-2"><strong>Credentials:</strong> Use app-specific password for Gmail / Microsoft 365 when 2FA is enabled.</li>
                                                <li class="mb-2"><strong>From Address:</strong> Should match an authorized sender on your mail server.</li>
                                                <li class="mb-2"><strong>Test:</strong> Send a test email before enabling for all CRM notifications.</li>
                                            </ol>
                                        </div>
                                    </div>

                                    <div class="card border rounded-3 mb-4 bg-light d-none" id="helpSendmail">
                                        <div class="card-header bg-white fw-semibold text-success">
                                            <i class="bi bi-terminal-fill me-2"></i> Sendmail Guide
                                        </div>
                                        <div class="card-body p-3">
                                            <ol class="ps-3 mb-0 small text-secondary">
                                                <li class="mb-2"><strong>Server:</strong> Requires sendmail or compatible binary on your hosting server.</li>
                                                <li class="mb-2"><strong>Path:</strong> Default <code>/usr/sbin/sendmail -bs -i</code> on Linux.</li>
                                                <li class="mb-2"><strong>From Address:</strong> Set a valid domain email as the sender.</li>
                                            </ol>
                                        </div>
                                    </div>

                                    <div class="card border border-info rounded-3 bg-info bg-opacity-10">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold text-info-emphasis mb-2"><i class="bi bi-shield-check me-1"></i> Isolated Provider Records</h6>
                                            <p class="small text-secondary mb-0">SMTP and Sendmail settings are stored separately. Activating one deactivates the other.</p>
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

    <div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="testEmailModalLabel"><i class="bi bi-envelope text-primary me-2"></i> Send Test Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="test-email-alert-container"></div>
                    <div class="mb-3">
                        <label for="test_email" class="form-label fw-semibold">Recipient Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="test_email" name="test_email"
                                value="{{ auth()->user()->email ?? '' }}"
                                placeholder="you@company.com" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="sendTestEmailBtn" onclick="submitTestEmail()">
                        <span id="sendTestEmailBtnText"><i class="bi bi-send me-1"></i> Send Test</span>
                        <span id="sendTestEmailSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const providerRecords = {
            smtp: {
                host: @json(old('mail_host', $smtpSetting->mail_host ?? '')),
                port: @json(old('mail_port', $smtpSetting->mail_port ?? 587)),
                encryption: @json(old('mail_encryption', $smtpSetting->mail_encryption ?? 'tls')),
                username: @json(old('mail_username', $smtpSetting->mail_username ?? '')),
                password: @json(old('mail_password', $smtpSetting->mail_password ?? '')),
                from_address: @json(old('mail_from_address', $smtpSetting->mail_from_address ?? '')),
                from_name: @json(old('mail_from_name', $smtpSetting->mail_from_name ?? '')),
                sendmail_path: @json(old('sendmail_path', $smtpSetting->sendmail_path ?? '')),
                is_active: @json($smtpSetting->is_active ? 1 : 0)
            },
            sendmail: {
                host: @json(old('mail_host', $sendmailSetting->mail_host ?? '')),
                port: @json(old('mail_port', $sendmailSetting->mail_port ?? '')),
                encryption: @json(old('mail_encryption', $sendmailSetting->mail_encryption ?? 'tls')),
                username: @json(old('mail_username', $sendmailSetting->mail_username ?? '')),
                password: @json(old('mail_password', $sendmailSetting->mail_password ?? '')),
                from_address: @json(old('mail_from_address', $sendmailSetting->mail_from_address ?? '')),
                from_name: @json(old('mail_from_name', $sendmailSetting->mail_from_name ?? '')),
                sendmail_path: @json(old('sendmail_path', $sendmailSetting->sendmail_path ?? config('mail.mailers.sendmail.path'))),
                is_active: @json($sendmailSetting->is_active ? 1 : 0)
            }
        };

        let activeProviderState = null;

        function selectProvider(type) {
            const radio = document.getElementById('provider_' + type);
            if (radio) {
                radio.checked = true;
                onProviderChange(type);
            }
        }

        function onProviderChange(type) {
            const fields = ['mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name', 'sendmail_path'];
            const activeInput = document.getElementById('is_active');

            if (activeProviderState && providerRecords[activeProviderState]) {
                const rec = providerRecords[activeProviderState];
                rec.host = document.getElementById('mail_host')?.value || '';
                rec.port = document.getElementById('mail_port')?.value || '';
                rec.encryption = document.getElementById('mail_encryption')?.value || '';
                rec.username = document.getElementById('mail_username')?.value || '';
                rec.password = document.getElementById('mail_password')?.value || '';
                rec.from_address = document.getElementById('mail_from_address')?.value || '';
                rec.from_name = document.getElementById('mail_from_name')?.value || '';
                rec.sendmail_path = document.getElementById('sendmail_path')?.value || '';
                rec.is_active = activeInput && activeInput.checked ? 1 : 0;
            }

            activeProviderState = type;
            const target = providerRecords[type] || {};

            if (document.getElementById('mail_host')) document.getElementById('mail_host').value = target.host || '';
            if (document.getElementById('mail_port')) document.getElementById('mail_port').value = target.port || (type === 'smtp' ? 587 : '');
            if (document.getElementById('mail_encryption')) document.getElementById('mail_encryption').value = target.encryption || 'tls';
            if (document.getElementById('mail_username')) document.getElementById('mail_username').value = target.username || '';
            if (document.getElementById('mail_password')) document.getElementById('mail_password').value = target.password || '';
            if (document.getElementById('mail_from_address')) document.getElementById('mail_from_address').value = target.from_address || '';
            if (document.getElementById('mail_from_name')) document.getElementById('mail_from_name').value = target.from_name || '';
            if (document.getElementById('sendmail_path')) document.getElementById('sendmail_path').value = target.sendmail_path || '';
            if (activeInput) activeInput.checked = (target.is_active == 1);
            hideMailPassword();

            document.querySelectorAll('.provider-card').forEach(c => {
                c.classList.remove('border-success', 'bg-success', 'border-primary', 'bg-primary', 'bg-opacity-10');
            });

            const smtpFields = document.getElementById('smtpFields');
            const sendmailFields = document.getElementById('sendmailFields');
            const helpSmtp = document.getElementById('helpSmtp');
            const helpSendmail = document.getElementById('helpSendmail');
            const badge = document.getElementById('providerBadge');

            if (type === 'smtp') {
                document.getElementById('provider_smtp').closest('.provider-card').classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                smtpFields?.classList.remove('d-none');
                sendmailFields?.classList.add('d-none');
                helpSmtp?.classList.remove('d-none');
                helpSendmail?.classList.add('d-none');
                badge.innerText = 'SMTP';
                badge.className = 'badge bg-primary rounded-pill px-3 py-1';
            } else {
                document.getElementById('provider_sendmail').closest('.provider-card').classList.add('border-success', 'bg-success', 'bg-opacity-10');
                smtpFields?.classList.add('d-none');
                sendmailFields?.classList.remove('d-none');
                helpSmtp?.classList.add('d-none');
                helpSendmail?.classList.remove('d-none');
                badge.innerText = 'Sendmail';
                badge.className = 'badge bg-success rounded-pill px-3 py-1';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            onProviderChange('{{ old("provider_type", $activeProvider ?? "smtp") }}');
        });

        function handleSendTestClick() {
            const activeInput = document.getElementById('is_active');
            if (activeInput && !activeInput.checked) {
                showDisabledSwal('Email integration is disabled. Enable it and save settings to send test emails.');
                return;
            }
            const modalEl = document.getElementById('testEmailModal');
            if (modalEl) new bootstrap.Modal(modalEl).show();
        }

        function showDisabledSwal(msg) {
            const message = msg || 'Email integration is disabled.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Integration Disabled', text: message, confirmButtonColor: '#2563eb' });
            } else {
                alert(message);
            }
        }

        function hideMailPassword() {
            const input = document.getElementById('mail_password');
            const icon = document.getElementById('togglePasswordIcon');
            if (!input) return;
            input.type = 'password';
            if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
        }

        function revealMailPassword() {
            const input = document.getElementById('mail_password');
            const icon = document.getElementById('togglePasswordIcon');
            if (!input) return;
            input.type = 'text';
            if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
            setTimeout(hideMailPassword, 60000);
        }

        function togglePasswordVisibility() {
            const input = document.getElementById('mail_password');
            if (!input) return;
            if (input.type === 'text') { hideMailPassword(); return; }

            if (typeof Swal === 'undefined') {
                alert('Password confirmation is required to view the SMTP password.');
                return;
            }

            Swal.fire({
                title: 'Confirm your password',
                text: 'Enter your login password to view the SMTP password.',
                input: 'password',
                inputPlaceholder: 'Login password',
                showCancelButton: true,
                confirmButtonText: 'Verify & Show',
                confirmButtonColor: '#2563eb',
                showLoaderOnConfirm: true,
                preConfirm: (password) => {
                    if (!password) { Swal.showValidationMessage('Password is required'); return false; }
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('password', password);
                    return fetch('{{ route("email.settings.verify_password") }}', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: formData
                    }).then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) throw new Error(data.error || 'Incorrect password.');
                        return data;
                    }).catch((error) => Swal.showValidationMessage(error.message));
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) revealMailPassword();
            });
        }

        function collectFormPayload() {
            const activeInput = document.getElementById('is_active');
            const provider = document.querySelector('input[name="provider_type"]:checked')?.value || 'smtp';
            const payload = new FormData();
            payload.append('_token', '{{ csrf_token() }}');
            payload.append('provider_type', provider);
            payload.append('mail_host', document.getElementById('mail_host')?.value || '');
            payload.append('mail_port', document.getElementById('mail_port')?.value || '');
            payload.append('mail_encryption', document.getElementById('mail_encryption')?.value || '');
            payload.append('mail_username', document.getElementById('mail_username')?.value || '');
            payload.append('mail_password', document.getElementById('mail_password')?.value || '');
            payload.append('mail_from_address', document.getElementById('mail_from_address')?.value || '');
            payload.append('mail_from_name', document.getElementById('mail_from_name')?.value || '');
            payload.append('sendmail_path', document.getElementById('sendmail_path')?.value || '');
            if (activeInput && activeInput.checked) payload.append('is_active', '1');
            return payload;
        }

        function submitTestEmail() {
            const alertContainer = document.getElementById('test-email-alert-container');
            const emailInput = document.getElementById('test_email');
            const btn = document.getElementById('sendTestEmailBtn');
            const btnText = document.getElementById('sendTestEmailBtnText');
            const spinner = document.getElementById('sendTestEmailSpinner');

            alertContainer.innerHTML = '';
            if (!emailInput.value.trim()) {
                alertContainer.innerHTML = '<div class="alert alert-warning py-2 small">Please enter a valid email address.</div>';
                return;
            }

            const formData = collectFormPayload();
            formData.append('test_email', emailInput.value.trim());

            btn.disabled = true;
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');

            fetch('{{ route("email.settings.test") }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');

                if (data.success) {
                    alertContainer.innerHTML = `<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i> ${data.message}</div>`;
                } else if (data.disabled) {
                    bootstrap.Modal.getInstance(document.getElementById('testEmailModal'))?.hide();
                    showDisabledSwal(data.error);
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i> ${data.error || 'Failed to send test email.'}</div>`;
                }
            })
            .catch(() => {
                btn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
                alertContainer.innerHTML = '<div class="alert alert-danger py-2 small">An error occurred while sending the test email.</div>';
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
