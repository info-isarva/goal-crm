<script>
(function () {
    function initQuotationWizard(options) {
        var form = document.querySelector(options.formSelector || '#quotationWizardForm') || document.querySelector('form');
        if (!form) return;

        form.setAttribute('novalidate', 'novalidate');

        var panels = Array.prototype.slice.call(form.querySelectorAll('.qt-wizard-panel'));
        var steppers = Array.prototype.slice.call(document.querySelectorAll('.qt-wizard-stepper-item'));
        var btnPrev = document.getElementById('qtWizardPrev');
        var btnNext = document.getElementById('qtWizardNext');
        var btnSubmit = document.getElementById('qtWizardSubmit');
        var current = 0;

        // Per-step mandatory fields (matches QuotationController store/update rules)
        var stepRequiredIds = {
            0: ['subject', 'quotation_owner', 'status', 'valid_until', 'organization_id', 'people_id', 'deal_id'],
            1: ['billing_country', 'billing_street', 'billing_city', 'billing_state'],
            2: [],
            3: [], // products validated separately
            4: [],
            5: []
        };

        var fieldLabels = {
            subject: 'Subject',
            quotation_owner: 'Quotation Owner',
            status: 'Quotation Stage',
            valid_until: 'Valid Until',
            organization_id: 'Company Name',
            people_id: 'Contact Name',
            deal_id: 'Deal Name',
            billing_country: 'Billing Country / Region',
            billing_street: 'Billing Street Address',
            billing_city: 'Billing City',
            billing_state: 'Billing State / Province',
            billing_zip: 'Billing Zip / Postal Code',
            shipping_country: 'Shipping Country / Region',
            shipping_street: 'Shipping Street Address',
            shipping_city: 'Shipping City',
            shipping_state: 'Shipping State / Province'
        };

        function showStep(index) {
            if (index < 0 || index >= panels.length) return;
            current = index;
            panels.forEach(function (panel, i) {
                panel.classList.toggle('is-active', i === current);
            });
            steppers.forEach(function (item, i) {
                item.classList.toggle('is-active', i === current);
                item.classList.toggle('is-complete', i < current);
            });
            if (btnPrev) btnPrev.style.visibility = current === 0 ? 'hidden' : 'visible';
            if (btnNext) btnNext.style.display = current === panels.length - 1 ? 'none' : 'inline-flex';
            if (btnSubmit) btnSubmit.style.display = current === panels.length - 1 ? 'inline-flex' : 'none';
            if (current === panels.length - 1) {
                refreshReview();
            }
            setTimeout(function () {
                if (!window.CrmQuill) return;
                var active = panels[current];
                if (!active) return;
                active.querySelectorAll('textarea.qt-rich-editor').forEach(function (ta) {
                    var ed = CrmQuill.get(ta.id);
                    if (!ed) return;
                    try { ed.focus(); } catch (err) {}
                });
            }, 50);
            try {
                window.scrollTo({ top: Math.max(0, form.offsetTop - 20), behavior: 'smooth' });
            } catch (e) {}
        }

        function clearStepErrors(panel) {
            if (!panel) return;
            panel.querySelectorAll('.qt-step-error-banner').forEach(function (el) { el.remove(); });
            panel.querySelectorAll('.qt-field-error').forEach(function (el) { el.remove(); });
            panel.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        }

        function fieldLabel(field) {
            if (!field) return 'This field';
            if (field.id && fieldLabels[field.id]) return fieldLabels[field.id];
            var label = field.id ? panelLabelFor(field.id) : null;
            if (label) return label;
            var name = field.getAttribute('name') || '';
            if (name.indexOf('[product_id]') !== -1) return 'Product';
            if (name.indexOf('[quantity]') !== -1) return 'Quantity';
            if (name.indexOf('[price]') !== -1) return 'Price';
            return 'This field';
        }

        function panelLabelFor(id) {
            var lab = document.querySelector('label[for="' + id + '"]');
            if (!lab) return null;
            return (lab.textContent || '').replace(/\*/g, '').trim() || null;
        }

        function markInvalid(field, message) {
            if (!field) return;
            field.classList.add('is-invalid');
            var wrap = field.closest('.mb-3') || field.closest('td') || field.parentElement;
            if (!wrap) return;
            var existing = wrap.querySelector('.qt-field-error');
            if (existing) {
                existing.textContent = message;
                return;
            }
            var err = document.createElement('div');
            err.className = 'text-danger small mt-1 qt-field-error';
            err.textContent = message;
            wrap.appendChild(err);
        }

        function isEmpty(field) {
            if (!field) return true;
            if (field.type === 'checkbox' || field.type === 'radio') return !field.checked;
            return !String(field.value || '').trim();
        }

        function showBanner(panel, count) {
            var banner = document.createElement('div');
            banner.className = 'alert alert-danger py-2 px-3 qt-step-error-banner';
            banner.setAttribute('role', 'alert');
            banner.textContent = count === 1
                ? 'Please fill the required field before continuing.'
                : 'Please fill all ' + count + ' required fields before continuing.';
            var title = panel.querySelector('.qt-wizard-panel-title');
            if (title && title.parentElement === panel) {
                title.insertAdjacentElement('afterend', banner);
            } else {
                panel.insertBefore(banner, panel.firstChild);
            }
        }

        function validateProducts(panel) {
            if (window.QtQuotationItems && typeof window.QtQuotationItems.validateProductsStep === 'function') {
                return window.QtQuotationItems.validateProductsStep();
            }
            var rows = panel.querySelectorAll('#quotation-items tr');
            var invalid = [];
            if (!rows.length) {
                var bannerOnly = document.createElement('div');
                bannerOnly.className = 'alert alert-danger py-2 px-3 qt-step-error-banner';
                bannerOnly.textContent = 'Add at least one product before continuing.';
                panel.insertBefore(bannerOnly, panel.firstChild);
                return false;
            }
            rows.forEach(function (row) {
                var product = row.querySelector('select[name*="[product_id]"]');
                var qty = row.querySelector('input[name*="[quantity]"]');
                var price = row.querySelector('input[name*="[price]"]');
                if (product && isEmpty(product)) {
                    markInvalid(product, 'Product is required.');
                    invalid.push(product);
                }
                if (qty && (isEmpty(qty) || Number(qty.value) < 1)) {
                    markInvalid(qty, 'Quantity is required.');
                    invalid.push(qty);
                }
                if (price && isEmpty(price)) {
                    markInvalid(price, 'Price is required.');
                    invalid.push(price);
                }
            });
            if (invalid.length) {
                showBanner(panel, invalid.length);
                try { invalid[0].focus(); } catch (e) {}
                return false;
            }
            return true;
        }

        function validateStep(index) {
            var panel = panels[index];
            if (!panel) return true;
            clearStepErrors(panel);

            // Sync Quill before validating textareas in this panel
            if (window.CrmQuill) {
                try {
                    if (window.QuotationFormHelpers && typeof window.QuotationFormHelpers.syncRichEditors === 'function') {
                        window.QuotationFormHelpers.syncRichEditors();
                    } else {
                        CrmQuill.syncAll();
                    }
                } catch (e) {}
            }

            if (index === 2) {
                return validateProducts(panel);
            }

            var invalid = [];
            var requiredIds = stepRequiredIds[index] || [];

            requiredIds.forEach(function (id) {
                var field = document.getElementById(id);
                if (!field || !panel.contains(field)) return;
                if (field.disabled) return;
                if (isEmpty(field)) {
                    markInvalid(field, (fieldLabels[id] || 'This field') + ' is required.');
                    invalid.push(field);
                }
            });

            // Shipping required when enabled
            if (index === 1) {
                var shipToggle = document.getElementById('enable-shipping-address');
                if (shipToggle && shipToggle.checked) {
                    ['shipping_country', 'shipping_street', 'shipping_city', 'shipping_state'].forEach(function (id) {
                        var field = document.getElementById(id);
                        if (!field || field.disabled) return;
                        if (isEmpty(field)) {
                            markInvalid(field, (fieldLabels[id] || 'This field') + ' is required.');
                            invalid.push(field);
                        }
                    });
                }
            }

            // Also catch any [required] fields in the panel not already checked
            panel.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
                if (field.disabled || field.type === 'hidden') return;
                if (invalid.indexOf(field) !== -1) return;
                if (isEmpty(field) || (typeof field.checkValidity === 'function' && !field.checkValidity())) {
                    markInvalid(field, fieldLabel(field) + ' is required.');
                    invalid.push(field);
                }
            });

            if (invalid.length) {
                showBanner(panel, invalid.length);
                try {
                    invalid[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    invalid[0].focus();
                } catch (e) {}
                return false;
            }
            return true;
        }

        function textVal(id) {
            var el = document.getElementById(id);
            return el && el.value ? String(el.value).trim() : '—';
        }

        function selectText(id) {
            var el = document.getElementById(id);
            if (!el || !el.options || el.selectedIndex < 0) return '—';
            var opt = el.options[el.selectedIndex];
            return (opt && opt.text) ? opt.text.trim() : '—';
        }

        function syncRichEditorsToForm() {
            if (window.QuotationFormHelpers && typeof window.QuotationFormHelpers.syncRichEditors === 'function') {
                window.QuotationFormHelpers.syncRichEditors();
                return;
            }
            if (!window.CrmQuill) return;
            ['description', 'terms_conditions', 'project_detail'].forEach(function (id) {
                if (CrmQuill.get(id)) CrmQuill.sync(id);
            });
        }

        function refreshReview() {
            syncRichEditorsToForm();

            var iframe = document.getElementById('qtReviewTemplatePreview');
            var loading = document.getElementById('qtReviewPreviewLoading');
            var nameEl = document.getElementById('qtReviewTemplateName');
            var templateIdEl = document.getElementById('quotation_template_id');
            var templateNameEl = document.getElementById('qtSelectedName');

            if (nameEl) {
                nameEl.textContent = (templateNameEl && templateNameEl.textContent)
                    ? templateNameEl.textContent.trim()
                    : 'Selected template preview';
            }

            if (!iframe) return;
            if (loading) loading.classList.remove('is-hidden');

            var formData = new FormData(form);
            formData.delete('_method');
            formData.set('wizard_draft', '1');
            if (templateIdEl && templateIdEl.value) {
                formData.set('quotation_template_id', templateIdEl.value);
            }

            var previewUrl = @json(route('quotation_templates.preview_post'));

            fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content
                        || (form.querySelector('input[name="_token"]') || {}).value
                        || ''
                },
                body: formData
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data || !data.success) {
                        throw new Error((data && (data.error || data.message))
                            || ('Preview failed (' + response.status + ').'));
                    }
                    return data.html;
                });
            })
            .then(function (html) {
                iframe.srcdoc = html;
                if (loading) loading.classList.add('is-hidden');
            })
            .catch(function (err) {
                var message = (err && err.message)
                    ? String(err.message)
                    : 'Unable to load template preview. Please try again.';
                iframe.srcdoc = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;padding:24px;color:#b91c1c;">'
                    + message.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    + '</body></html>';
                if (loading) loading.classList.add('is-hidden');
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', function (e) {
                e.preventDefault();
                if (!validateStep(current)) return;
                showStep(current + 1);
            });
        }
        if (btnPrev) {
            btnPrev.addEventListener('click', function () {
                clearStepErrors(panels[current]);
                showStep(current - 1);
            });
        }
        steppers.forEach(function (item, i) {
            item.addEventListener('click', function () {
                if (i === current) return;
                if (i < current) {
                    clearStepErrors(panels[current]);
                    showStep(i);
                    return;
                }
                for (var s = current; s < i; s++) {
                    if (!validateStep(s)) {
                        showStep(s);
                        return;
                    }
                }
                showStep(i);
            });
        });

        form.addEventListener('submit', function (e) {
            for (var s = 0; s < panels.length - 1; s++) {
                if (!validateStep(s)) {
                    e.preventDefault();
                    showStep(s);
                    return;
                }
            }
        });

        // Clear error styling when user edits a field
        form.addEventListener('input', function (e) {
            var field = e.target;
            if (!field || !field.classList.contains('is-invalid')) return;
            field.classList.remove('is-invalid');
            var wrap = field.closest('.mb-3') || field.closest('td') || field.parentElement;
            if (wrap) {
                var err = wrap.querySelector('.qt-field-error');
                if (err) err.remove();
            }
        });
        form.addEventListener('change', function (e) {
            var field = e.target;
            if (!field || !field.classList.contains('is-invalid')) return;
            field.classList.remove('is-invalid');
            var wrap = field.closest('.mb-3') || field.closest('td') || field.parentElement;
            if (wrap) {
                var err = wrap.querySelector('.qt-field-error');
                if (err) err.remove();
            }
        });

        showStep(0);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initQuotationWizard({});
    });
})();
</script>
