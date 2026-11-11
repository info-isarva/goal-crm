{{-- Wire "+ New Industries" into #industry_id Select2 + modal submit --}}
<script>
(function () {
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return (meta && meta.getAttribute('content')) || '';
    }

    function clearIndustryErrors() {
        ['industry_name_error', 'industry_description_error'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.style.display = 'none';
            el.textContent = '';
        });
    }

    function openIndustryModal() {
        var form = document.getElementById('industryNewForm');
        if (form) form.reset();
        clearIndustryErrors();
        var modalEl = document.getElementById('industryNewModal');
        if (!modalEl) return;
        new bootstrap.Modal(modalEl).show();
    }

    function initIndustryNewOption() {
        if (typeof $ === 'undefined' || !$.fn.select2) return;
        var $sel = $('#industry_id');
        if (!$sel.length) return;

        if ($sel.find('option[value="__new_industry__"]').length === 0) {
            $sel.append('<option value="__new_industry__">+ New Industries</option>');
        }

        var currentVal = $sel.val();
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.off('select2:select.industryNew');
            $sel.select2('destroy');
        }

        function formatIndustryOption(option) {
            if (!option.id) return option.text;
            if (option.id === '__new_industry__') {
                return $('<span class="text-primary fw-semibold">+ New Industries</span>');
            }
            return option.text;
        }

        $sel.select2({
            placeholder: 'Select Industries',
            allowClear: true,
            width: '100%',
            templateResult: formatIndustryOption,
            templateSelection: function (option) {
                if (option.id === '__new_industry__') return 'New Industries';
                return option.text;
            }
        });

        if (currentVal && currentVal !== '__new_industry__') {
            $sel.val(String(currentVal)).trigger('change');
        }

        $sel.on('select2:select.industryNew', function (e) {
            if (e.params.data.id !== '__new_industry__') return;
            $sel.val(null).trigger('change');
            setTimeout(openIndustryModal, 100);
        });
    }

    function bindIndustryForm() {
        var form = document.getElementById('industryNewForm');
        if (!form || form.dataset.bound === '1') return;
        form.dataset.bound = '1';

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearIndustryErrors();

            var csrf = getCsrfToken();
            var formData = new FormData(form);
            if (csrf) formData.append('_token', csrf);

            fetch('/industries/ajax-create', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: formData
            })
            .then(async function (res) {
                if (res.status === 422) {
                    var data = await res.json();
                    if (data.errors) {
                        Object.keys(data.errors).forEach(function (key) {
                            var errorDiv = document.getElementById('industry_' + key + '_error');
                            if (errorDiv) {
                                errorDiv.textContent = data.errors[key][0];
                                errorDiv.style.display = 'block';
                            }
                        });
                    }
                    throw new Error('Validation error');
                }
                if (!res.ok) {
                    throw new Error('Request failed');
                }
                return res.json();
            })
            .then(function (industry) {
                var $sel = (typeof $ !== 'undefined') ? $('#industry_id') : null;
                var selectEl = document.getElementById('industry_id');
                if (!selectEl) return;

                var existing = selectEl.querySelector('option[value="' + industry.id + '"]');
                if (!existing) {
                    var opt = new Option(industry.name, industry.id, true, true);
                    // Insert before the "+ New Industries" option when present
                    var newOpt = selectEl.querySelector('option[value="__new_industry__"]');
                    if (newOpt) {
                        selectEl.insertBefore(opt, newOpt);
                    } else {
                        selectEl.appendChild(opt);
                    }
                }

                if ($sel && $sel.length) {
                    $sel.val(String(industry.id)).trigger('change');
                } else {
                    selectEl.value = String(industry.id);
                }

                form.reset();
                var modal = bootstrap.Modal.getInstance(document.getElementById('industryNewModal'));
                if (modal) modal.hide();
            })
            .catch(function () {});
        });
    }

    function boot() {
        initIndustryNewOption();
        bindIndustryForm();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            // After page-level Select2 inits
            setTimeout(boot, 50);
        });
    } else {
        setTimeout(boot, 50);
    }
})();
</script>
