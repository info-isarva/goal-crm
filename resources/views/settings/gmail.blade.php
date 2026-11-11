@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4 border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle p-2 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center"
                            style="width: 42px; height: 42px;">
                            <i class="bi bi-google fs-4"></i>
                        </div>
                        <div>
                            <!-- <h4 class="mb-0 fw-bold">Gmail Account Setup</h4> -->
                            <small class="text-muted">Connect your Gmail to send quotations directly from CRM</small>
                            <div class="mt-1">
                                <a href="{{ route('email.compose.settings.edit') }}" class="small">
                                    <i class="bi bi-fonts me-1"></i>Compose Settings (font &amp; signature)
                                </a>
                            </div>
                        </div>
                    </div>
                    <div>
                        @if($connected && $tokenValid)
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                <i class="bi bi-check-circle-fill me-1"></i> Connected
                            </span>
                        @elseif($connected && !$tokenValid)
                            <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Reconnect Required
                            </span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">
                                <i class="bi bi-x-circle me-1"></i> Not Connected
                            </span>
                        @endif
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- @if(session('success'))
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                    @endif -->

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card border rounded-3 mb-4">
                                <div class="card-header bg-light fw-semibold text-dark">
                                    <i class="bi bi-person-badge me-2"></i> Connected Account
                                </div>
                                <div class="card-body p-4">
                                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center"
                                             style="width:64px;height:64px;">
                                            <i class="bi bi-envelope-at fs-3"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold fs-5">{{ $gmailName }}</div>
                                            <div class="text-muted">{{ $gmailEmail ?: 'No email on profile' }}</div>
                                            @if($connected && $tokenValid)
                                                <div class="small text-success mt-1">
                                                    <i class="bi bi-shield-check me-1"></i> Gmail send access authorized
                                                </div>
                                            @elseif($connected)
                                                <div class="small text-warning mt-1">
                                                    <i class="bi bi-shield-exclamation me-1"></i> Token expired — please reconnect
                                                </div>
                                            @else
                                                <div class="small text-muted mt-1">
                                                    Connect Gmail once to enable Send via Gmail on quotations
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="alert alert-info py-2 small mb-3">
                                        <i class="bi bi-info-circle me-1"></i>
                                        You can connect only the Gmail account that matches your CRM login email
                                        <strong>{{ $gmailEmail }}</strong>.
                                    </div>

                                    <div class="d-flex flex-wrap gap-2">
                                        @if(!$connected || !$tokenValid)
                                            <a href="{{ $connectUrl }}" class="btn btn-danger px-4">
                                                <i class="bi bi-google me-1"></i>
                                                {{ $connected ? 'Reconnect Gmail' : 'Connect Gmail Account' }}
                                            </a>
                                        @else
                                            <a href="{{ $connectUrl }}" class="btn btn-outline-danger px-4">
                                                <i class="bi bi-arrow-repeat me-1"></i> Reconnect
                                            </a>
                                            <form method="POST" action="{{ route('gmail.settings.disconnect') }}" class="d-inline" id="gmailDisconnectForm">
                                                @csrf
                                                <button type="button" class="btn btn-outline-secondary px-4" id="gmailDisconnectBtn">
                                                    <i class="bi bi-plug me-1"></i> Disconnect
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($connected && $tokenValid)
                            <div class="card border rounded-3 mb-4">
                                <div class="card-header bg-light fw-semibold text-dark">
                                    <i class="bi bi-send me-2"></i> Send Test Email
                                </div>
                                <div class="card-body p-4">
                                    <p class="text-muted small mb-3">
                                        Send a short test message from your connected Gmail to confirm setup.
                                    </p>
                                    <div id="gmail-test-alert"></div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-8">
                                            <label for="gmail_test_email" class="form-label fw-semibold">Recipient Email</label>
                                            <input type="email" id="gmail_test_email" class="form-control"
                                                   value="{{ $gmailEmail }}" placeholder="name@example.com">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-primary w-100" id="gmailTestBtn" onclick="sendGmailTest()">
                                                <span id="gmailTestBtnText"><i class="bi bi-send me-1"></i> Send Test</span>
                                                <span id="gmailTestSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="col-lg-4">
                            <div class="card border rounded-3 mb-4 bg-light">
                                <div class="card-header bg-white fw-semibold text-danger">
                                    <i class="bi bi-info-circle me-2"></i> Setup Guide
                                </div>
                                <div class="card-body p-3">
                                    <ol class="ps-3 mb-0 small text-secondary">
                                        <li class="mb-2"><strong>Enable Gmail API:</strong> In Google Cloud Console → APIs &amp; Services → enable <em>Gmail API</em>.</li>
                                        <li class="mb-2"><strong>OAuth consent:</strong> Add scope <code>gmail.send</code> (Send email on your behalf).</li>
                                        <li class="mb-2"><strong>Connect:</strong> Click Connect Gmail and approve <strong>Send email</strong>.</li>
                                        <li class="mb-2"><strong>If reconnect fails:</strong> Remove CRM app access at <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener">Google Account permissions</a>, then connect again.</li>
                                        <li class="mb-2"><strong>Test:</strong> Send a test email to verify the connection.</li>
                                        <li class="mb-0"><strong>Note:</strong> This is separate from SMTP Email Settings.</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="card border rounded-3 bg-light">
                                <div class="card-header bg-white fw-semibold text-dark">
                                    <i class="bi bi-shield-lock me-2"></i> Permissions Used
                                </div>
                                <div class="card-body p-3 small text-secondary">
                                    <div class="mb-2"><code>gmail.send</code></div>
                                    <div>CRM only sends emails you compose. It does not read your inbox.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($connected && $tokenValid)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var disconnectBtn = document.getElementById('gmailDisconnectBtn');
    var disconnectForm = document.getElementById('gmailDisconnectForm');

    if (disconnectBtn && disconnectForm) {
        disconnectBtn.addEventListener('click', function () {
            var fire = (typeof Swal !== 'undefined' && Swal.fire)
                ? Swal.fire({
                    title: 'Disconnect Gmail?',
                    text: 'Disconnect Gmail from this CRM account? You will need to connect again to send quotations via Gmail.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, disconnect',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                })
                : Promise.resolve({ isConfirmed: window.confirm('Disconnect Gmail from this CRM account?') });

            fire.then(function (result) {
                if (result.isConfirmed) {
                    disconnectForm.submit();
                }
            });
        });
    }
});

function sendGmailTest() {
    const alertBox = document.getElementById('gmail-test-alert');
    const btn = document.getElementById('gmailTestBtn');
    const btnText = document.getElementById('gmailTestBtnText');
    const spinner = document.getElementById('gmailTestSpinner');
    const email = (document.getElementById('gmail_test_email')?.value || '').trim();

    alertBox.innerHTML = '';
    if (!email) {
        alertBox.innerHTML = '<div class="alert alert-warning py-2 small">Please enter a recipient email.</div>';
        return;
    }

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('test_email', email);

    btn.disabled = true;
    btnText.classList.add('d-none');
    spinner.classList.remove('d-none');

    fetch('{{ route("gmail.settings.test") }}', {
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
            alertBox.innerHTML = '<div class="alert alert-warning py-2 small">' + (data.error || 'Please reconnect Gmail.') +
                ' <a href="' + data.connect_url + '">Connect now</a></div>';
            return;
        }

        if (data.success) {
            alertBox.innerHTML = '<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i> ' +
                (data.message || 'Test sent.') + '</div>';
            return;
        }

        alertBox.innerHTML = '<div class="alert alert-danger py-2 small">' + (data.error || 'Failed to send test.') + '</div>';
    })
    .catch(() => {
        btn.disabled = false;
        btnText.classList.remove('d-none');
        spinner.classList.add('d-none');
        alertBox.innerHTML = '<div class="alert alert-danger py-2 small">Network error while sending test email.</div>';
    });
}
</script>
@endif
@endsection
