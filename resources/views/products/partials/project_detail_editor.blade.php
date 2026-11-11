@php
    $projectDetailValue = $projectDetailValue ?? '';
@endphp
@include('quotations.partials.rich_editor_assets')
@include('partials.tinymce_draw_tool')
<div class="mb-3">
    <label for="project_detail" class="form-label">Project Detail</label>
    <div class="qt-rich-wrap">
        <textarea class="form-control qt-rich-editor" id="project_detail" name="project_detail" rows="8"></textarea>
        <script type="application/json" id="project_detail_json">@json($projectDetailValue)</script>
    </div>
    @error('project_detail')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

@once
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.2.4/dist/purify.min.js"></script>
    <script>
    (function () {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')
            ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            : '{{ csrf_token() }}';
        var uploadUrl = @json(route('products.editor.upload_image'));
        var placeholderItems = [
            { text: 'Product Name', value: '{' + '{product_name}}' },
            { text: 'Product Code', value: '{' + '{product_code}}' },
            { text: 'Customer Name', value: '{' + '{customer_name}}' },
            { text: 'Customer Company', value: '{' + '{customer_company}}' },
            { text: 'Quotation Number', value: '{' + '{quotation_number}}' },
            { text: 'Quotation Date', value: '{' + '{quotation_date}}' },
            { text: 'Valid Until', value: '{' + '{quotation_expiry_date}}' },
            { text: 'Reference', value: '{' + '{quotation_reference}}' },
            { text: 'Proposal Version', value: '{' + '{proposal_version}}' },
            { text: 'Prepared By', value: '{' + '{sales_person}}' },
            { text: 'Grand Total', value: '{' + '{grand_total}}' },
            { text: 'Company Name', value: '{' + '{company_name}}' },
            { text: 'Company Email', value: '{' + '{company_email}}' },
            { text: 'Company Phone', value: '{' + '{company_phone}}' }
        ];

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
                    'colspan', 'rowspan', 'src', 'alt', 'href', 'target', 'rel', 'data-mce-src'
                ],
                FORBID_TAGS: ['script', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'link', 'meta', 'base'],
                FORBID_ATTR: ['onerror', 'onclick', 'onload', 'onmouseover']
            });
        }

        function validateHtmlSource(html) {
            var errors = [];
            if (/<\s*(script|iframe|object|embed|form)\b/i.test(html)) {
                errors.push('Disallowed tags found (script/iframe/object/embed/form).');
            }
            if (/\son\w+\s*=/i.test(html)) {
                errors.push('Inline event handlers are not allowed.');
            }
            if (/javascript\s*:/i.test(html)) {
                errors.push('javascript: URLs are not allowed.');
            }
            ['p', 'div', 'table', 'ul', 'ol', 'li', 'tr', 'td', 'th', 'h1', 'h2', 'h3', 'h4'].forEach(function (tag) {
                var open = (html.match(new RegExp('<' + tag + '\\b', 'gi')) || []).length;
                var close = (html.match(new RegExp('</' + tag + '>', 'gi')) || []).length;
                if (open !== close) {
                    errors.push('Unbalanced <' + tag + '> tags (' + open + ' open / ' + close + ' close).');
                }
            });
            return errors;
        }

        function buildToolbar() {
            return [
                'undo redo | styles | bold italic underline strikethrough | forecolor backcolor |',
                'alignleft aligncenter alignright alignjustify | outdent indent |',
                'bullist numlist | blockquote | link image draw table |',
                'removeformat | code preview fullscreen | placeholders help'
            ].join(' ');
        }

        function syncEditor() {
            if (typeof tinymce === 'undefined') return;
            tinymce.triggerSave();
            var ed = tinymce.get('project_detail');
            if (!ed) return;
            var clean = sanitizeHtmlClient(ed.getContent({ format: 'html' }));
            ed.setContent(clean, { format: 'raw' });
            ed.save();
            var ta = document.getElementById('project_detail');
            if (ta) ta.value = clean === '<p></p>' || clean === '<p><br></p>' ? '' : clean;
        }

        function initProjectDetailEditor() {
            var ta = document.getElementById('project_detail');
            if (!ta || typeof tinymce === 'undefined' || ta.getAttribute('data-rte-ready') === '1') {
                return;
            }
            ta.setAttribute('data-rte-ready', '1');

            var initial = readInitial('project_detail');
            if (initial && !ta.value) {
                ta.value = initial;
            }

            tinymce.init({
                selector: '#project_detail',
                license_key: 'gpl',
                base_url: 'https://cdn.jsdelivr.net/npm/tinymce@7.6.1',
                suffix: '.min',
                height: 320,
                menubar: 'file edit view insert format tools table help',
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'visualchars', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'directionality',
                    'nonbreaking', 'quickbars'
                ].join(' '),
                toolbar: buildToolbar(),
                toolbar_mode: 'sliding',
                quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote',
                quickbars_insert_toolbar: 'image table hr',
                style_formats: [
                    { title: 'Paragraph', format: 'p' },
                    { title: 'Heading 1', format: 'h1' },
                    { title: 'Heading 2', format: 'h2' },
                    { title: 'Heading 3', format: 'h3' },
                    { title: 'Heading 4', format: 'h4' }
                ],
                block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4',
                lists_indent_on_tab: true,
                branding: false,
                promotion: false,
                convert_urls: false,
                relative_urls: false,
                remove_script_host: false,
                extended_valid_elements: 'img[class|src|border=0|alt|title|hspace|vspace|width|height|align|style],' +
                    'table[style|class|border|width|cellspacing|cellpadding|align],' +
                    'tr[style|class],td[style|class|colspan|rowspan|width|height|align|valign],th[style|class|colspan|rowspan|width|height|align|valign]',
                valid_children: '+body[style]',
                content_style: 'body{font-family:DejaVu Sans,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;}' +
                    'table{border-collapse:collapse;width:100%;}td,th{border:1px solid #cbd5e1;padding:6px;}' +
                    'img{max-width:100%;height:auto;}',
                images_upload_credentials: true,
                images_reuse_filename: false,
                automatic_uploads: true,
                file_picker_types: 'image',
                images_upload_handler: function (blobInfo, progress) {
                    return new Promise(function (resolve, reject) {
                        var formData = new FormData();
                        formData.append('file', blobInfo.blob(), blobInfo.filename());
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', uploadUrl);
                        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        xhr.upload.onprogress = function (e) {
                            if (e.lengthComputable && typeof progress === 'function') {
                                progress(e.loaded / e.total * 100);
                            }
                        };
                        xhr.onload = function () {
                            if (xhr.status < 200 || xhr.status >= 300) {
                                reject('Image upload failed (' + xhr.status + ').');
                                return;
                            }
                            try {
                                var json = JSON.parse(xhr.responseText);
                                if (!json || !json.location) {
                                    reject('Invalid upload response.');
                                    return;
                                }
                                resolve(json.location);
                            } catch (err) {
                                reject('Invalid upload response.');
                            }
                        };
                        xhr.onerror = function () { reject('Image upload failed.'); };
                        xhr.send(formData);
                    });
                },
                setup: function (editor) {
                    editor.ui.registry.addMenuButton('placeholders', {
                        text: 'Placeholders',
                        tooltip: 'Insert placeholder',
                        fetch: function (callback) {
                            callback(placeholderItems.map(function (item) {
                                return {
                                    type: 'menuitem',
                                    text: item.text,
                                    onAction: function () {
                                        editor.insertContent(item.value);
                                    }
                                };
                            }));
                        }
                    });

                    if (window.TinyMceDrawTool) {
                        window.TinyMceDrawTool.registerButton(editor, uploadUrl, csrfToken);
                    }

                    editor.on('change keyup SetContent', function () {
                        editor.save();
                    });

                    editor.on('GetContent', function (e) {
                        if (e.format === 'html' || e.format === 'raw') {
                            e.content = sanitizeHtmlClient(e.content);
                        }
                    });
                },
                init_instance_callback: function (editor) {
                    var statusId = 'project_detail_html_status';
                    var statusEl = document.getElementById(statusId);
                    if (!statusEl) {
                        statusEl = document.createElement('div');
                        statusEl.id = statusId;
                        statusEl.className = 'qt-html-validate-ok mt-1';
                        ta.parentNode.appendChild(statusEl);
                    }
                    editor.on('NodeChange change CodeEditorBlur', function () {
                        var html = editor.getContent({ format: 'html' });
                        var errors = validateHtmlSource(html);
                        if (errors.length) {
                            statusEl.className = 'qt-html-validate-err mt-1';
                            statusEl.textContent = 'HTML check: ' + errors[0];
                        } else {
                            statusEl.className = 'qt-html-validate-ok mt-1';
                            statusEl.textContent = 'HTML check: OK';
                        }
                    });
                }
            });
        }

        function boot() {
            initProjectDetailEditor();

            var form = document.getElementById('project_detail')?.closest('form');
            if (form && !form.__productRteSubmitBound) {
                form.__productRteSubmitBound = true;
                form.addEventListener('submit', function (e) {
                    syncEditor();
                    var ta = document.getElementById('project_detail');
                    var errors = validateHtmlSource(ta ? (ta.value || '') : '');
                    var statusEl = document.getElementById('project_detail_html_status');
                    if (errors.length) {
                        e.preventDefault();
                        if (statusEl) {
                            statusEl.className = 'qt-html-validate-err mt-1';
                            statusEl.textContent = 'HTML check: ' + errors[0];
                        }
                        alert('Please fix HTML validation errors in Project Detail before saving.');
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    })();
    </script>
    @endpush
@endonce
