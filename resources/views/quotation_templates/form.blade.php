@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material-darker.min.css">
<style>
    .qt-editor-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 320px); gap: 1rem; width: 100%; }
    .qt-editor-layout > div { min-width: 0; }
    @media (max-width: 1400px) { .qt-editor-layout { grid-template-columns: 1fr; } }
    .CodeMirror { height: 320px; border: 1px solid #dee2e6; border-radius: 6px; font-size: 13px; }
    .qt-placeholder-panel { max-height: 720px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa; }
    .qt-placeholder-group { border-bottom: 1px solid #eee; padding: .75rem .9rem; }
    .qt-placeholder-group:last-child { border-bottom: 0; }
    .qt-ph-row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .35rem; }
    .qt-ph-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; color: #334155; word-break: break-all; }
    .qt-copy-toast {
        position: fixed; bottom: 24px; right: 24px; z-index: 1080;
        background: #198754; color: #fff; padding: .6rem 1rem; border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,.15); display: none;
    }
    #previewModal .modal-dialog { max-width: 920px; }
    #previewModal .modal-body { background: #e8e8e8; padding: 16px; }
    #previewFrame { width: 100%; min-height: 85vh; border: 0; background: #e8e8e8; border-radius: 6px; }
</style>

<div class="container-fluid p-4">
    <form method="POST"
          action="{{ $mode === 'edit' ? route('quotation_templates.update', $template) : route('quotation_templates.store') }}"
          id="quotationTemplateForm">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="card mt-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="mb-0">{{ $mode === 'edit' ? 'Edit Template' : 'Create Quotation Template' }}</h4>
                    @if($mode === 'edit')
                        <small class="text-muted">Version {{ $template->version }} · Updated {{ optional($template->updated_at)->diffForHumans() }}</small>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('quotation_templates.index') }}" class="btn btn-light btn-sm">Cancel</a>
                    @if(!empty($canPreview))
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPreview"><i class="bi bi-eye"></i> Preview</button>
                    @endif
                    <button type="submit" class="btn btn-custom btn-sm"><i class="bi bi-save"></i> Save</button>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $template->name ?? '') }}" required maxlength="150">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        @if(!empty($canDeactivate))
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $template->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $template->status ?? '') === 'inactive')>Inactive</option>
                        </select>
                        @else
                        <input type="hidden" name="status" value="{{ old('status', $template->status ?? 'active') }}">
                        <div class="form-control-plaintext">
                            {{ (old('status', $template->status ?? 'active') === 'inactive') ? 'Inactive' : 'Active' }}
                        </div>
                        @endif
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        @if(!empty($canSetDefault))
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="is_default" name="is_default"
                                @checked(old('is_default', $template->is_default ?? false))>
                            <label class="form-check-label" for="is_default">
                                Set as default template
                                <small class="text-muted d-block">Only one default is allowed</small>
                            </label>
                        </div>
                        @elseif(!empty($template?->is_default))
                        <div class="text-muted small">This is the default template</div>
                        @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" maxlength="1000">{{ old('description', $template->description ?? '') }}</textarea>
                    </div>
                </div>

                <div class="qt-editor-layout">
                    <div>
                        <label class="form-label fw-semibold">HTML Content <span class="text-danger">*</span></label>
                        <textarea name="html_content" id="html_content" class="form-control @error('html_content') is-invalid @enderror">{{ old('html_content', $html) }}</textarea>
                        @error('html_content')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                        <label class="form-label fw-semibold mt-3">CSS Content</label>
                        <textarea name="css_content" id="css_content" class="form-control">{{ old('css_content', $css) }}</textarea>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Available Placeholders</label>
                            <small class="text-muted">Click Copy to clipboard</small>
                        </div>
                        <div class="qt-placeholder-panel">
                            @foreach($placeholderGroups as $group => $placeholders)
                                <div class="qt-placeholder-group">
                                    <div class="fw-semibold mb-2 text-uppercase" style="font-size:11px; letter-spacing:.04em; color:#64748b;">{{ $group }}</div>
                                    @foreach($placeholders as $ph)
                                        <div class="qt-ph-row">
                                            <code class="qt-ph-code">{{ $ph }}</code>
                                            <button type="button" class="btn btn-sm btn-outline-secondary qt-copy-btn" data-placeholder="{{ $ph }}">Copy</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="qt-copy-toast" id="copyToast">Placeholder copied!</div>

<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quotation Preview (Sample Data)</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnPrintPreview">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-2">
                <iframe id="previewFrame" title="Quotation Preview"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script>
(function () {
    const htmlEditor = CodeMirror.fromTextArea(document.getElementById('html_content'), {
        mode: 'htmlmixed',
        lineNumbers: true,
        lineWrapping: true,
        theme: 'default',
        indentUnit: 2,
        tabSize: 2,
    });
    const cssEditor = CodeMirror.fromTextArea(document.getElementById('css_content'), {
        mode: 'css',
        lineNumbers: true,
        lineWrapping: true,
        theme: 'default',
        indentUnit: 2,
        tabSize: 2,
    });

    document.getElementById('quotationTemplateForm').addEventListener('submit', function () {
        htmlEditor.save();
        cssEditor.save();
    });

    document.querySelectorAll('.qt-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const text = btn.getAttribute('data-placeholder') || '';
            navigator.clipboard.writeText(text).then(function () {
                const toast = document.getElementById('copyToast');
                toast.style.display = 'block';
                setTimeout(function () { toast.style.display = 'none'; }, 1600);
            });
        });
    });

    const btnPreview = document.getElementById('btnPreview');
    if (btnPreview) {
    btnPreview.addEventListener('click', function () {
        htmlEditor.save();
        cssEditor.save();
        const htmlContent = htmlEditor.getValue();
        const cssContent = cssEditor.getValue();
        if (!String(htmlContent || '').trim()) {
            alert('Enter HTML Content before preview.');
            return;
        }
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading…';

        fetch(@json(route('quotation_templates.preview_post')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                html_content: htmlContent,
                css_content: cssContent,
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success) throw new Error(data.error || 'Preview failed');
            const frame = document.getElementById('previewFrame');
            frame.srcdoc = data.html;
            new bootstrap.Modal(document.getElementById('previewModal')).show();
        })
        .catch(function (err) {
            alert(err.message || 'Unable to preview template');
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-eye"></i> Preview';
        });
    });
    }

    document.getElementById('btnPrintPreview').addEventListener('click', function () {
        const frame = document.getElementById('previewFrame');
        if (!frame || !frame.contentWindow) {
            alert('Open preview first.');
            return;
        }
        frame.contentWindow.focus();
        frame.contentWindow.print();
    });
})();
</script>
@endsection
