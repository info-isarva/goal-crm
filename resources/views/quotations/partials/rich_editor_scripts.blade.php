@include('partials.quill_editor_assets')
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.2.4/dist/purify.min.js"></script>
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : '{{ csrf_token() }}';

    var uploadUrl = @json(route('quotations.editor.upload_image'));
    var placeholderItems = [
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
            ADD_TAGS: ['table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'colgroup', 'col', 'caption', 'font'],
            ADD_ATTR: [
                'style', 'class', 'align', 'border', 'cellpadding', 'cellspacing', 'width', 'height',
                'colspan', 'rowspan', 'src', 'alt', 'href', 'target', 'rel', 'bgcolor', 'color', 'face', 'size'
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

    function setProposalVersion(version) {
        var v = Math.max(1, parseInt(version, 10) || 1);
        var hidden = document.getElementById('proposal_version');
        var display = document.getElementById('proposal_version_display');
        if (hidden) hidden.value = String(v);
        if (display) display.value = 'v' + v;
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
                    reject('Image upload failed (' + xhr.status + ').');
                    return;
                }
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (!json || !json.location) {
                        reject(json && json.error ? json.error : 'Invalid upload response.');
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
    }

    function bindImageUpload(quill) {
        var toolbar = quill.getModule('toolbar');
        if (!toolbar) return;
        toolbar.addHandler('image', function () {
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
                    alert(typeof err === 'string' ? err : 'Image upload failed.');
                });
            };
        });
    }

    function updateHtmlStatus(textareaId, html) {
        var statusId = textareaId + '_html_status';
        var statusEl = document.getElementById(statusId);
        var ta = document.getElementById(textareaId);
        if (!statusEl && ta && ta.parentNode) {
            statusEl = document.createElement('div');
            statusEl.id = statusId;
            ta.parentNode.appendChild(statusEl);
        }
        if (!statusEl) return;
        var errors = validateHtmlSource(html || '');
        if (errors.length) {
            statusEl.className = 'qt-html-validate-err mt-1';
            statusEl.textContent = 'HTML check: ' + errors[0];
        } else {
            statusEl.className = 'qt-html-validate-ok mt-1';
            statusEl.textContent = 'HTML check: OK';
        }
    }

    function sizeClassFor(minHeight) {
        if ((minHeight || 0) >= 300) return 'crm-quill-tall';
        if ((minHeight || 0) >= 250) return 'crm-quill-medium';
        return 'crm-quill-short';
    }

    function initEditor(textareaId, minHeight) {
        var ta = document.getElementById(textareaId);
        if (!ta || !window.CrmQuill) {
            return;
        }
        if (ta.getAttribute('data-rte-ready') === '1') {
            if (window.__qtPendingProjectDetail && textareaId === 'project_detail') {
                CrmQuill.setHtml(textareaId, window.__qtPendingProjectDetail);
                window.__qtPendingProjectDetail = null;
            }
            return;
        }
        ta.setAttribute('data-rte-ready', '1');

        var initial = readInitial(textareaId);
        if (textareaId === 'project_detail' && window.__qtPendingProjectDetail) {
            initial = window.__qtPendingProjectDetail;
            window.__qtPendingProjectDetail = null;
        }
        if (initial && !ta.value) {
            ta.value = initial;
        }

        var quill = CrmQuill.create(textareaId, {
            sizeClass: sizeClassFor(minHeight),
            wrapClass: 'qt-rich-quill',
            html: initial || ta.value || '',
            placeholder: 'Start typing...',
            menubar: true,
            placeholders: placeholderItems,
            onChange: function (html) {
                updateHtmlStatus(textareaId, html);
            }
        });
        if (!quill) return;

        bindImageUpload(quill);
        updateHtmlStatus(textareaId, CrmQuill.getHtml(textareaId));
    }

    function syncAllEditors() {
        if (!window.CrmQuill) return;
        ['description', 'terms_conditions', 'project_detail'].forEach(function (id) {
            if (!CrmQuill.get(id)) return;
            CrmQuill.sync(id);
            var ta = document.getElementById(id);
            if (!ta) return;
            // Sanitize safely but keep inline styles (do not re-setHtml into Quill)
            var clean = sanitizeHtmlClient(ta.value || '');
            ta.value = clean === '<p></p>' || clean === '<p><br></p>' ? '' : clean;
            updateHtmlStatus(id, ta.value);
        });
    }

    window.QuotationFormHelpers = {
        setProposalVersion: setProposalVersion,
        applyProjectDetailContent: function (html) {
            if (!html) {
                return;
            }
            html = sanitizeHtmlClient(html);

            var jsonEl = document.getElementById('project_detail_json');
            if (jsonEl) {
                jsonEl.textContent = JSON.stringify(html);
            }
            window.__qtPendingProjectDetail = html;

            var panel = document.getElementById('projectDetailPanel');
            var toggleBtn = document.getElementById('btnToggleProjectDetail');
            if (panel) {
                panel.style.display = 'block';
            }
            if (toggleBtn) {
                toggleBtn.classList.add('is-open');
            }

            if (window.CrmQuill && CrmQuill.get('project_detail')) {
                CrmQuill.setHtml('project_detail', html);
                window.__qtPendingProjectDetail = null;
                return;
            }

            initEditor('project_detail', 280);
        },
        fetchNextProposalVersion: function (dealTitle, organizationName, excludeQuotationId) {
            if (!dealTitle) {
                setProposalVersion(1);
                return;
            }
            var params = new URLSearchParams();
            params.set('deal_title', dealTitle);
            if (organizationName) params.set('organization', organizationName);
            if (excludeQuotationId) params.set('exclude_quotation_id', String(excludeQuotationId));
            fetch('{{ route('quotations.next_proposal_version') }}?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) { setProposalVersion(data.proposal_version || 1); })
                .catch(function () {});
        },
        syncRichEditors: syncAllEditors,
        initEditors: function () {
            initEditor('description', 240);
            initEditor('terms_conditions', 320);

            var toggleBtn = document.getElementById('btnToggleProjectDetail');
            var panel = document.getElementById('projectDetailPanel');
            if (toggleBtn && panel && !toggleBtn.__qtBound) {
                toggleBtn.__qtBound = true;
                var projectInitial = readInitial('project_detail');
                if (window.__qtPendingProjectDetail) {
                    projectInitial = window.__qtPendingProjectDetail;
                }
                var hasContent = projectInitial && projectInitial.replace(/<[^>]*>/g, '').trim() !== '';
                panel.style.display = hasContent ? 'block' : 'none';
                if (hasContent) {
                    toggleBtn.classList.add('is-open');
                    initEditor('project_detail', 280);
                }
                toggleBtn.addEventListener('click', function () {
                    var opening = panel.style.display === 'none';
                    panel.style.display = opening ? 'block' : 'none';
                    toggleBtn.classList.toggle('is-open', opening);
                    if (opening) {
                        initEditor('project_detail', 280);
                        setTimeout(function () {
                            var ed = window.CrmQuill ? CrmQuill.get('project_detail') : null;
                            if (ed) ed.focus();
                        }, 50);
                    }
                });
            }

            document.querySelectorAll('form').forEach(function (form) {
                if (form.__qtRteSubmitBound) return;
                if (!form.querySelector('textarea.qt-rich-editor')) return;
                form.__qtRteSubmitBound = true;
                form.addEventListener('submit', function (e) {
                    syncAllEditors();
                    var blocking = false;
                    ['description', 'terms_conditions', 'project_detail'].forEach(function (id) {
                        var ta = document.getElementById(id);
                        if (!ta) return;
                        var errors = validateHtmlSource(ta.value || '');
                        var statusEl = document.getElementById(id + '_html_status');
                        if (errors.length) {
                            blocking = true;
                            if (statusEl) {
                                statusEl.className = 'qt-html-validate-err mt-1';
                                statusEl.textContent = 'HTML check: ' + errors[0];
                            }
                        }
                    });
                    if (blocking) {
                        e.preventDefault();
                        alert('Please fix HTML validation errors in the rich text fields before saving.');
                    }
                });
            });
        }
    };

    function boot() {
        window.QuotationFormHelpers.initEditors();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
