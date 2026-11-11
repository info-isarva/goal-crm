@extends('layouts.app')

@section('content')
    @include('quotations.partials.rich_editor_assets')

    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4 border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center"
                                style="width: 42px; height: 42px;">
                                <i class="bi bi-file-text fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">Quotation Terms &amp; Conditions</h4>
                                <small class="text-muted">Default terms pre-filled when creating a new quotation</small>
                            </div>
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
                                <div>Users with <strong>Manage Quotation Terms &amp; Conditions</strong> permission can access or modify quotation default terms.</div>
                            </div>
                        @endunless

                        <form method="POST" action="{{ route('quotation.settings.update') }}" id="quotationTermsSettingForm">
                            @csrf

                            <div class="mb-3">
                                <div class="qt-rich-label mb-1">
                                    <label for="default_terms_conditions" class="form-label mb-0 fw-semibold">Default Terms &amp; Conditions</label>
                                    <span class="qt-rich-hint">Rich text · lists · images · full toolbar</span>
                                </div>
                                <div class="qt-rich-wrap">
                                    <textarea class="form-control qt-rich-editor" id="default_terms_conditions" name="default_terms_conditions" rows="12" @if(!$canEdit) disabled @endif></textarea>
                                    <script type="application/json" id="default_terms_conditions_json">@json(old('default_terms_conditions', $setting->default_terms_conditions ?? '') ?? '')</script>
                                </div>
                                @error('default_terms_conditions')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text mt-2">
                                    This content is loaded automatically in the <strong>Terms and Conditions</strong> field each time a new quotation is created. Users can still edit it per quotation.
                                </div>
                            </div>

                            @if($canEdit)
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-check-lg me-1"></i> Save Default Terms
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@include('partials.quill_editor_assets')
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.2.4/dist/purify.min.js"></script>
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : @json(csrf_token());
    var uploadUrl = @json(route('quotations.editor.upload_image'));
    var canEdit = @json($canEdit ?? false);

    function readInitial(textareaId) {
        var jsonEl = document.getElementById(textareaId + '_json');
        if (jsonEl) {
            try {
                var parsed = JSON.parse(jsonEl.textContent || '""');
                if (typeof parsed === 'string') {
                    return parsed;
                }
            } catch (e) { /* ignore */ }
        }
        var ta = document.getElementById(textareaId);
        return ta ? (ta.value || '') : '';
    }

    function sanitizeHtmlClient(html) {
        if (typeof DOMPurify === 'undefined') {
            return html || '';
        }
        return DOMPurify.sanitize(html || '', {
            USE_PROFILES: { html: true },
            ADD_TAGS: ['table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'colgroup', 'col', 'caption'],
            ADD_ATTR: [
                'style', 'class', 'align', 'border', 'cellpadding', 'cellspacing', 'width', 'height',
                'colspan', 'rowspan', 'src', 'alt', 'href', 'target', 'rel'
            ],
            FORBID_TAGS: ['script', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'link', 'meta', 'base'],
            FORBID_ATTR: ['onerror', 'onclick', 'onload', 'onmouseover']
        });
    }

    function uploadImageFile(file) {
        return new Promise(function (resolve, reject) {
            var formData = new FormData();
            formData.append('file', file, file.name || 'image.png');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function () {
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject('Upload failed');
                    return;
                }
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (json.location) resolve(json.location);
                    else reject(json.error || 'Upload failed');
                } catch (err) {
                    reject('Upload failed');
                }
            };
            xhr.onerror = function () { reject('Upload failed'); };
            xhr.send(formData);
        });
    }

    function syncEditor() {
        if (!window.CrmQuill || !CrmQuill.get('default_terms_conditions')) return;
        CrmQuill.sync('default_terms_conditions');
        var ta = document.getElementById('default_terms_conditions');
        if (!ta) return;
        var clean = sanitizeHtmlClient(ta.value || '');
        ta.value = clean === '<p></p>' || clean === '<p><br></p>' ? '' : clean;
    }

    function initTermsEditor() {
        var ta = document.getElementById('default_terms_conditions');
        if (!ta || !window.CrmQuill || ta.getAttribute('data-rte-ready') === '1') {
            return;
        }
        ta.setAttribute('data-rte-ready', '1');

        var initial = readInitial('default_terms_conditions');
        if (initial && !ta.value) {
            ta.value = initial;
        }

        var quill = CrmQuill.create('default_terms_conditions', {
            sizeClass: 'crm-quill-tall',
            wrapClass: 'qt-rich-quill',
            html: initial || ta.value || '',
            placeholder: 'Enter default terms and conditions...',
            menubar: true,
            placeholders: [
                { text: 'Customer Name', value: '{' + '{customer_name}}' },
                { text: 'Customer Company', value: '{' + '{customer_company}}' },
                { text: 'Quotation Number', value: '{' + '{quotation_number}}' },
                { text: 'Quotation Date', value: '{' + '{quotation_date}}' },
                { text: 'Valid Until', value: '{' + '{quotation_expiry_date}}' },
                { text: 'Company Name', value: '{' + '{company_name}}' },
                { text: 'Grand Total', value: '{' + '{grand_total}}' }
            ]
        });
        if (!quill) return;

        if (!canEdit) {
            quill.enable(false);
        }

        var toolbar = quill.getModule('toolbar');
        if (toolbar) {
            toolbar.addHandler('image', function () {
                if (!canEdit) return;
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.click();
                input.onchange = function () {
                    var file = input.files && input.files[0];
                    if (!file) return;
                    uploadImageFile(file).then(function (url) {
                        var range = quill.getSelection(true);
                        var index = range ? range.index : quill.getLength();
                        quill.insertEmbed(index, 'image', url, 'user');
                        quill.setSelection(index + 1, 0);
                    }).catch(function (err) {
                        alert(typeof err === 'string' ? err : 'Upload failed');
                    });
                };
            });
        }
    }

    var form = document.getElementById('quotationTermsSettingForm');
    if (form) {
        form.addEventListener('submit', function () {
            syncEditor();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTermsEditor);
    } else {
        initTermsEditor();
    }
})();
</script>
@endpush
