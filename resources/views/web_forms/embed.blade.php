@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h4 class="mb-1 fw-bold">Embed Code &mdash; {{ $form->name }}</h4>
                    <small class="text-muted">
                        Paste one of these into your website or landing page. Submissions create leads automatically.
                        @unless($form->is_active)
                            <span class="badge bg-warning text-dark ms-1">Form is inactive, it will show as unavailable</span>
                        @endunless
                    </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('web_forms.index') }}" class="btn btn-light btn-sm">&laquo; Back to Web Forms</a>
                    @if(auth()->user()->hasCrmPermission('edit_crm_web_forms_guard') || auth()->user()->hasCrmPermission('manage_crm_web_forms_guard'))
                        <a href="{{ route('web_forms.edit', $form) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Form</a>
                    @endif
                    <a href="{{ route('web_forms.submissions', $form) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-inbox me-1"></i>Submissions ({{ number_format($form->submissions_count) }})</a>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <style>
                .wf-code-block { position: relative; margin-bottom: 6px; }
                .wf-code-block textarea {
                    font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
                    font-size: .8rem; background: #0f172a; color: #e2e8f0; border: 0; border-radius: 8px;
                    padding: 14px 14px; resize: vertical; width: 100%;
                }
                .wf-copy-btn { position: absolute; top: 8px; right: 8px; }
                .wf-embed-nav .nav-link { font-weight: 600; color: #475569; }
                .wf-embed-nav .nav-link.active { color: #5b3cc4; border-bottom-color: #5b3cc4; }
                .wf-steps { color: #475569; font-size: .875rem; padding-left: 1.1rem; margin-bottom: 14px; }
                .wf-steps li { margin-bottom: 3px; }
                .wf-preview-frame { width: 100%; height: 720px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f5f6fa; }
            </style>

            <div class="row g-4">
                <div class="col-xl-7">
                    <ul class="nav nav-tabs wf-embed-nav mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#wf-tab-script" type="button" role="tab">JavaScript <span class="badge bg-success-subtle text-success ms-1">Recommended</span></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#wf-tab-iframe" type="button" role="tab">iFrame</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#wf-tab-link" type="button" role="tab">Direct Link</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#wf-tab-html" type="button" role="tab">Plain HTML</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="wf-tab-script" role="tabpanel">
                            <ol class="wf-steps">
                                <li>Copy the code below.</li>
                                <li>Paste it into your page's HTML where the form should appear (WordPress: <em>Custom HTML</em> block; Elementor/Wix/Webflow: <em>HTML / Embed</em> widget).</li>
                                <li>Publish. The form uses your page's font and adjusts to mobile screens. Changes you make here appear on the site automatically.</li>
                            </ol>
                            <div class="wf-code-block">
                                <textarea class="form-control" rows="3" readonly id="wfCodeScript">{{ $scriptSnippet }}</textarea>
                                <button type="button" class="btn btn-sm btn-light wf-copy-btn" data-copy="wfCodeScript"><i class="bi bi-clipboard me-1"></i>Copy</button>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="wf-tab-iframe" role="tabpanel">
                            <ol class="wf-steps">
                                <li>Use this if your website builder does not allow scripts.</li>
                                <li>Adjust the <code>height</code> value if the form is cut off.</li>
                            </ol>
                            <div class="wf-code-block">
                                <textarea class="form-control" rows="3" readonly id="wfCodeIframe">{{ $iframeSnippet }}</textarea>
                                <button type="button" class="btn btn-sm btn-light wf-copy-btn" data-copy="wfCodeIframe"><i class="bi bi-clipboard me-1"></i>Copy</button>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="wf-tab-link" role="tabpanel">
                            <ol class="wf-steps">
                                <li>Share this link in emails, WhatsApp, social media, or ads. It opens the form on its own page.</li>
                            </ol>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" readonly id="wfCodeLink" value="{{ $form->hostedUrl() }}">
                                <button type="button" class="btn btn-outline-secondary" data-copy="wfCodeLink"><i class="bi bi-clipboard me-1"></i>Copy</button>
                                <a href="{{ $form->hostedUrl() }}" target="_blank" rel="noopener" class="btn btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Open</a>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="wf-tab-html" role="tabpanel">
                            <ol class="wf-steps">
                                <li>For developers who want full control over the design. Style it with your own CSS.</li>
                                <li>Keep the <code>name</code> attributes and the hidden <code>_hp_website</code> field (spam protection) unchanged.</li>
                                <li>After submitting, visitors see the thank-you page or go to your Redirect URL.</li>
                                <li>If you change the fields later, copy this code again.</li>
                            </ol>
                            <div class="wf-code-block">
                                <textarea class="form-control" rows="14" readonly id="wfCodeHtml">{{ $htmlSnippet }}</textarea>
                                <button type="button" class="btn btn-sm btn-light wf-copy-btn" data-copy="wfCodeHtml"><i class="bi bi-clipboard me-1"></i>Copy</button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-2">Form Summary</h6>
                    <div class="row small g-2 mb-3">
                        <div class="col-sm-6"><span class="text-muted">Lead Source:</span> {{ $form->leadSource->name ?? '—' }}</div>
                        <div class="col-sm-6"><span class="text-muted">Lead Owner:</span> {{ $form->owner->name ?? '—' }}</div>
                        <div class="col-sm-6"><span class="text-muted">Lead Status:</span> {{ $form->lead_status }}</div>
                        <div class="col-sm-6"><span class="text-muted">Priority:</span> {{ ucfirst($form->lead_priority) }}</div>
                        <div class="col-sm-6"><span class="text-muted">Allowed Websites:</span> {{ $form->allowed_domains ?: 'Any website' }}</div>
                        <div class="col-sm-6"><span class="text-muted">After Submit:</span> {{ $form->redirect_url ? 'Redirect to ' . $form->redirect_url : 'Show thank-you message' }}</div>
                        <div class="col-12"><span class="text-muted">Fields:</span>
                            {{ collect($fields)->map(fn ($f) => $f['label'] . ($f['required'] ? ' *' : ''))->implode(', ') }}
                        </div>
                    </div>

                    <form action="{{ route('web_forms.regenerate', $form) }}" method="POST" id="wfRegenerateForm">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Regenerate Embed Code</button>
                        <span class="text-muted small ms-2">Use this if the code was misused or leaked.</span>
                    </form>
                </div>

                <div class="col-xl-5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0">Live Preview</h6>
                        <small class="text-muted">Test submissions here create real leads</small>
                    </div>
                    <iframe src="{{ $form->hostedUrl() }}" class="wf-preview-frame" title="Web form preview"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
    var regenForm = document.getElementById('wfRegenerateForm');
    if (!regenForm) return;
    var confirmed = false;
    regenForm.addEventListener('submit', function (e) {
        if (confirmed) return;
        e.preventDefault();
        var message = 'The current code on your websites will stop working until you replace it with the new code.';
        var ask = window.Swal
            ? Swal.fire({
                title: 'Regenerate embed code?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, regenerate',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true
            })
            : Promise.resolve({ isConfirmed: window.confirm('Regenerate embed code?\n' + message) });
        ask.then(function (result) {
            if (!result.isConfirmed) return;
            confirmed = true;
            var btn = regenForm.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Regenerating...';
            }
            regenForm.submit();
        });
    });
})();

document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var target = document.getElementById(btn.getAttribute('data-copy'));
        var text = target.value;
        var done = function () {
            var original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied';
            setTimeout(function () { btn.innerHTML = original; }, 1600);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            target.select();
            document.execCommand('copy');
            done();
        }
    });
});
</script>
@endpush
