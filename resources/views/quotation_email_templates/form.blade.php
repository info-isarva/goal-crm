@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">
<style>
.qet-design-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
    background: #fff;
}
.qet-design-card:hover,
.qet-design-card.active {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
}
.qet-design-card .title { font-weight: 600; font-size: 14px; }
.qet-design-card .desc { font-size: 12px; color: #64748b; margin-top: 2px; }
.qet-placeholder-chip {
    display: inline-block;
    font-size: 12px;
    padding: 3px 8px;
    margin: 0 6px 6px 0;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    background: #f8fafc;
    cursor: pointer;
    font-family: ui-monospace, monospace;
}
.qet-placeholder-chip:hover { background: #e0e7ff; border-color: #93c5fd; }
.qet-preview-frame {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f1f5f9;
    min-height: 280px;
    overflow: auto;
}
.qet-preview-inner {
    background: #fff;
    min-height: 260px;
    padding: 16px !important;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #334155;
}
.qet-preview-inner table {
    width: 100% !important;
    max-width: 100% !important;
    table-layout: auto !important;
    border-collapse: collapse;
}
.qet-preview-inner td,
.qet-preview-inner th {
    white-space: normal !important;
    word-break: normal !important;
    overflow-wrap: break-word;
    vertical-align: top;
}
.qet-preview-inner img {
    max-width: 100%;
    height: auto;
}
.qet-preview-inner p { margin: 0 0 12px; }
.crm-quill-wrap.qet-email-editor .ql-editor {
    min-height: 320px;
}
.crm-quill-wrap.qet-email-editor .ql-editor.ql-blank::before {
    /* Email templates are injected via innerHTML; Quill Delta may stay empty and keep ql-blank */
    display: none !important;
}
.crm-quill-wrap.qet-email-editor .ql-editor table {
    width: 100% !important;
    table-layout: auto !important;
}
.crm-quill-wrap.qet-email-editor .ql-editor td,
.crm-quill-wrap.qet-email-editor .ql-editor th {
    white-space: normal !important;
    word-break: normal !important;
    min-width: 80px;
}
</style>
<div class="container-fluid p-4">
    <div class="card mt-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">{{ $template->exists ? 'Edit Email Template' : 'Create Email Template' }}</h4>
                <small class="text-muted">Choose a design format, customize content, and preview before saving</small>
            </div>
            <a href="{{ route('quotation_email_templates.index') }}" class="btn btn-light btn-sm border">&laquo; Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ $template->exists ? route('quotation_email_templates.update', $template) : route('quotation_email_templates.store') }}" id="emailTemplateForm">
                @csrf
                @if($template->exists) @method('PUT') @endif

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="email_template_name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $template->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <input type="text" name="description" class="form-control"
                                value="{{ old('description', $template->description) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Design Format</label>
                            <div class="row g-2" id="emailDesignPresetGrid">
                                @foreach($designPresets as $preset)
                                    <div class="col-md-6">
                                        <div class="qet-design-card" data-preset-id="{{ $preset['id'] }}">
                                            <div class="title">{{ $preset['name'] }}</div>
                                            <div class="desc">{{ $preset['description'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">Select a format to apply its subject and body layout. You can edit after applying.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="email_template_subject" class="form-control @error('subject') is-invalid @enderror"
                                value="{{ old('subject', $template->subject) }}" required>
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Body <span class="text-danger">*</span></label>
                            <textarea name="body" id="email_template_body" rows="14"
                                class="form-control @error('body') is-invalid @enderror">{{ old('body', $template->body) }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                @checked(old('is_active', $template->is_active ?? true))>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1"
                                @checked(old('is_default', $template->is_default ?? false))>
                            <label class="form-check-label" for="is_default">Set as default template</label>
                        </div>

                        <button type="submit" class="btn btn-custom">Save Template</button>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border bg-light mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-2">Placeholders</h6>
                                <p class="small text-muted mb-2">Click to insert into subject or body (last focused field).</p>
                                <div id="emailPlaceholderChips">
                                    @foreach($placeholders as $token => $label)
                                        <button type="button" class="qet-placeholder-chip" data-token="{{ $token }}" title="{{ $label }}">{{ $token }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card border">
                            <div class="card-header bg-white py-2">
                                <strong class="small">Live Preview</strong>
                            </div>
                            <div class="card-body p-2">
                                <div class="small text-muted mb-2 px-1">
                                    Subject: <span id="emailPreviewSubject" class="text-dark fw-semibold"></span>
                                </div>
                                <div class="qet-preview-frame">
                                    <div class="qet-preview-inner p-2" id="emailPreviewBody"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('partials.quill_editor_assets')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const designPresets = @json(array_values($designPresets));
    const sampleVariables = @json($sampleVariables);
    let lastFocusedField = 'body';
    let editorReady = false;

    function renderPreviewText(text) {
        let output = text || '';
        Object.keys(sampleVariables).forEach(function (key) {
            output = output.split('{' + key + '}').join(sampleVariables[key]);
        });
        return output;
    }

    function getTemplateBody() {
        if (editorReady && window.CrmQuill && CrmQuill.get('email_template_body')) {
            return CrmQuill.getHtml('email_template_body');
        }
        return document.getElementById('email_template_body')?.value || '';
    }

    function updatePreview() {
        const subject = document.getElementById('email_template_subject')?.value || '';
        const body = getTemplateBody();
        document.getElementById('emailPreviewSubject').textContent = renderPreviewText(subject);
        document.getElementById('emailPreviewBody').innerHTML = renderPreviewText(body);
    }

    function applyPreset(presetId) {
        const preset = designPresets.find(function (item) { return item.id === presetId; });
        if (!preset) return;

        const fire = (typeof Swal !== 'undefined' && Swal.fire)
            ? Swal.fire({
                title: 'Apply format?',
                html: 'Apply <strong>' + String(preset.name).replace(/</g, '&lt;') + '</strong> format?<br><span class="text-muted">This will replace the current subject and body.</span>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, apply',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            })
            : Promise.resolve({
                isConfirmed: window.confirm('Apply "' + preset.name + '" format? This will replace the current subject and body.')
            });

        fire.then(function (result) {
            if (!result.isConfirmed) return;

            document.getElementById('email_template_subject').value = preset.subject;
            if (editorReady && window.CrmQuill && CrmQuill.get('email_template_body')) {
                CrmQuill.setHtml('email_template_body', preset.body);
            } else {
                document.getElementById('email_template_body').value = preset.body;
            }
            document.querySelectorAll('.qet-design-card').forEach(function (card) {
                card.classList.toggle('active', card.getAttribute('data-preset-id') === presetId);
            });
            updatePreview();
        });
    }

    document.querySelectorAll('.qet-design-card').forEach(function (card) {
        card.addEventListener('click', function () {
            applyPreset(card.getAttribute('data-preset-id'));
        });
    });

    document.getElementById('email_template_subject')?.addEventListener('focus', function () {
        lastFocusedField = 'subject';
    });
    document.getElementById('email_template_subject')?.addEventListener('input', updatePreview);

    document.querySelectorAll('.qet-placeholder-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            const token = chip.getAttribute('data-token');
            if (lastFocusedField === 'subject') {
                const input = document.getElementById('email_template_subject');
                const start = input.selectionStart ?? input.value.length;
                const end = input.selectionEnd ?? input.value.length;
                input.value = input.value.slice(0, start) + token + input.value.slice(end);
                input.focus();
                input.setSelectionRange(start + token.length, start + token.length);
                updatePreview();
                return;
            }
            const quill = window.CrmQuill ? CrmQuill.get('email_template_body') : null;
            if (editorReady && quill) {
                if (typeof CrmQuill.insertText === 'function') {
                    CrmQuill.insertText('email_template_body', token);
                } else {
                    const range = quill.getSelection(true);
                    quill.insertText(range ? range.index : quill.getLength(), token, 'user');
                    CrmQuill.sync('email_template_body');
                }
            } else {
                const textarea = document.getElementById('email_template_body');
                const start = textarea.selectionStart ?? textarea.value.length;
                const end = textarea.selectionEnd ?? textarea.value.length;
                textarea.value = textarea.value.slice(0, start) + token + textarea.value.slice(end);
                textarea.focus();
            }
            updatePreview();
        });
    });

    if (window.CrmQuill) {
        const quill = CrmQuill.create('email_template_body', {
            sizeClass: 'crm-quill-tall',
            wrapClass: 'qet-email-editor',
            disableTables: true,
            emailLayout: true,
            placeholder: 'Write email template body...',
            onChange: updatePreview
        });
        if (quill) {
            // Ensure saved/preset HTML is painted into the email canvas
            const seed = document.getElementById('email_template_body')?.value || '';
            if (seed) {
                CrmQuill.setHtml('email_template_body', seed);
            }
            quill.on('selection-change', function (range) {
                if (range) lastFocusedField = 'body';
            });
            if (quill.__crmEmailCanvas) {
                quill.__crmEmailCanvas.addEventListener('focus', function () {
                    lastFocusedField = 'body';
                });
            }
            editorReady = true;
        }
        updatePreview();
    } else {
        document.getElementById('email_template_body')?.addEventListener('input', updatePreview);
        updatePreview();
    }

    document.getElementById('emailTemplateForm')?.addEventListener('submit', function () {
        if (window.CrmQuill) CrmQuill.sync('email_template_body');
    });
});
</script>
@endsection
