@extends('layouts.app')

@section('content')
@php
    $previewFont = $fontFamily;
    $previewSize = $fontSize;
@endphp
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold">Email Configuration</h4>
                    <small class="text-muted">Compose defaults and email signature for all outgoing mail</small>
                </div>
                <a href="{{ route('settings.index') }}" class="btn btn-light btn-sm">&laquo; Back to Settings</a>
            </div>
            <ul class="nav nav-tabs border-0 mt-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active px-3" href="{{ route('email.compose.settings.edit') }}">Compose</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 text-muted" href="{{ route('gmail.settings.edit') }}">Gmail Account</a>
                </li>
                @if((int) (auth()->user()->crm_role_type ?? -1) === 0)
                    <li class="nav-item">
                        <a class="nav-link px-3 text-muted" href="{{ route('email.settings.edit') }}">SMTP Email</a>
                    </li>
                @endif
            </ul>
        </div>

        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('email.compose.settings.update') }}" id="emailComposeSettingsForm">
                @csrf

                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h5 class="fw-semibold mb-1">Compose Settings</h5>
                        <p class="text-muted small mb-0">Configure basic default email compose settings to personalize your composing experience.</p>
                    </div>
                </div>

                <div class="row g-3 align-items-center mb-3">
                    <div class="col-md-3">
                        <label class="form-label mb-0 fw-semibold" for="email_compose_font_family">Default Font Family</label>
                    </div>
                    <div class="col-md-5">
                        <select name="email_compose_font_family" id="email_compose_font_family" class="form-select">
                            @foreach($fontFamilies as $family)
                                <option value="{{ $family }}" @selected($fontFamily === $family)>{{ $family }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 align-items-center mb-3">
                    <div class="col-md-3">
                        <label class="form-label mb-0 fw-semibold" for="email_compose_font_size">Default Font Size</label>
                    </div>
                    <div class="col-md-5">
                        <select name="email_compose_font_size" id="email_compose_font_size" class="form-select" style="max-width: 120px;">
                            @foreach($fontSizes as $size)
                                <option value="{{ $size }}" @selected((int) $fontSize === (int) $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3"></div>
                    <div class="col-md-7">
                        <div class="border rounded-3 p-3 egm-compose-preview" id="composeFontPreview">
                            <div class="small text-muted mb-2">Preview</div>
                            <div id="composePreviewText" style="font-family: {{ $previewFont }}, sans-serif; font-size: {{ $previewSize }}px;">
                                The quick brown fox jumps over the lazy dog
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 align-items-center mb-3">
                    <div class="col-md-3">
                        <label class="form-label mb-0 fw-semibold">Default From Address</label>
                    </div>
                    <div class="col-md-7">
                        <div class="form-control bg-light">{{ $fromLabel }}</div>
                        <div class="form-text">Uses your CRM / connected Gmail login email.</div>
                    </div>
                </div>

                <div class="row g-3 align-items-center mb-4">
                    <div class="col-md-3">
                        <label class="form-label mb-0 fw-semibold">Default Reply-To Address</label>
                    </div>
                    <div class="col-md-7">
                        <div class="form-control bg-light">{{ $fromLabel }}</div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Email Signature</label>
                        <div class="text-muted small">Shown at the end of every email you send.</div>
                    </div>
                    <div class="col-md-9">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <div class="input-group" style="max-width: 280px;">
                                <input type="text" name="email_signature_name" id="email_signature_name"
                                       class="form-control" value="{{ old('email_signature_name', $signatureName) }}"
                                       maxlength="100" placeholder="Signature name">
                            </div>
                            <span class="badge rounded-pill text-bg-light border px-3 py-2">
                                {{ $fromLabel }}
                            </span>
                        </div>
                        <textarea id="email_signature_html" name="email_signature_html">{!! old('email_signature_html', $signatureHtml) !!}</textarea>
                        <div class="form-text">This signature is added automatically when composing and when sending emails.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('settings.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i> Save Compose Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.egm-compose-preview {
    background: #fff;
    min-height: 88px;
}
#emailComposeSettingsForm .nav-link.active {
    font-weight: 600;
}
</style>

@include('partials.quill_editor_assets')
<script>
(function () {
    const familyEl = document.getElementById('email_compose_font_family');
    const sizeEl = document.getElementById('email_compose_font_size');
    const preview = document.getElementById('composePreviewText');

    function updatePreview() {
        if (!preview) return;
        const family = familyEl ? familyEl.value : 'Verdana';
        const size = sizeEl ? sizeEl.value : '14';
        preview.style.fontFamily = family + ', sans-serif';
        preview.style.fontSize = size + 'px';
        const quill = window.CrmQuill ? CrmQuill.get('email_signature_html') : null;
        if (quill) {
            quill.root.style.fontFamily = family + ', sans-serif';
            quill.root.style.fontSize = size + 'px';
        }
    }

    familyEl?.addEventListener('change', updatePreview);
    sizeEl?.addEventListener('change', updatePreview);

    if (window.CrmQuill) {
        CrmQuill.create('email_signature_html', {
            sizeClass: 'crm-quill-medium',
            contentStyle: @json($editorContentStyle),
            placeholder: 'Write your email signature...',
            onChange: function () { /* synced to textarea */ }
        });
        updatePreview();
    }

    document.getElementById('emailComposeSettingsForm')?.addEventListener('submit', function () {
        if (window.CrmQuill) CrmQuill.sync('email_signature_html');
    });
})();
</script>
@endsection
