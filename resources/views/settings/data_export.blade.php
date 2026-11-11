@extends('layouts.app')

@section('content')
@php
    $moduleViewsJson = collect($modules)->mapWithKeys(function ($mod) {
        return [$mod['key'] => $mod['views'] ?? [['value' => 'all', 'label' => 'All']]];
    });
@endphp
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h4 class="mb-1 fw-bold">Export Data</h4>
                    <small class="text-muted">You can export a max of {{ number_format($maxRecords) }} records.</small>
                </div>
                <a href="{{ route('settings.index') }}" class="btn btn-light btn-sm">&laquo; Back to Settings</a>
            </div>
        </div>

        <div class="card-body p-4">
            @if(session('error'))
                <div class="alert alert-danger py-2">
                    {{ session('error') }}
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <style>
                .de-form-row {
                    display: grid;
                    grid-template-columns: 180px minmax(240px, 680px);
                    gap: 12px 20px;
                    align-items: center;
                    margin-bottom: 18px;
                }
                .de-form-row > label {
                    margin: 0;
                    font-weight: 600;
                    color: #334155;
                    font-size: 0.925rem;
                }
                .de-section-title {
                    font-size: 0.95rem;
                    font-weight: 700;
                    color: #0f172a;
                    margin: 0 0 18px;
                }
                .de-section-divider {
                    border: 0;
                    border-top: 1px solid #e2e8f0;
                    margin: 28px 0;
                }
                .de-hint {
                    color: #64748b;
                    font-size: 0.8rem;
                    margin-top: 4px;
                }
                .de-radio-group {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 1.25rem;
                    align-items: center;
                }
                .criteria-row {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 8px 12px;
                }
                @media (max-width: 768px) {
                    .de-form-row {
                        grid-template-columns: 1fr;
                        gap: 6px;
                    }
                }
            </style>

            <form method="POST" action="{{ route('data_export.download') }}" id="dataExportForm" autocomplete="off">
                @csrf

                <h6 class="de-section-title">Configure</h6>

                <div class="de-form-row">
                    <label for="module">Module</label>
                    <div style="max-width: 420px;">
                        <select name="module" id="module" class="form-select @error('module') is-invalid @enderror" required>
                            <option value="">Select a module</option>
                            @foreach($modules as $mod)
                                <option value="{{ $mod['key'] }}" @selected(old('module') === $mod['key'])>
                                    {{ $mod['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('module')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="de-form-row">
                    <label>Filter Records by</label>
                    <div class="de-radio-group">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="radio" name="filter_by" id="filter_custom_view" value="custom_view"
                                {{ old('filter_by', 'custom_view') === 'custom_view' ? 'checked' : '' }}>
                            <label class="form-check-label" for="filter_custom_view">Custom view</label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="radio" name="filter_by" id="filter_criteria" value="criteria"
                                {{ old('filter_by') === 'criteria' ? 'checked' : '' }}>
                            <label class="form-check-label" for="filter_criteria">Criteria</label>
                        </div>
                    </div>
                </div>

                <div class="de-form-row" id="customViewRow">
                    <label for="custom_view">Custom view</label>
                    <div style="max-width: 420px;">
                        <select name="custom_view" id="custom_view" class="form-select @error('custom_view') is-invalid @enderror">
                            <option value="all">All</option>
                        </select>
                        @error('custom_view')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="de-form-row d-none" id="criteriaRow">
                    <label>Criteria</label>
                    <div class="border rounded-3 p-3 bg-light-subtle shadow-sm">
                        <div id="criteriaRowsContainer"></div>
                        <div class="de-hint mt-2">Filter module records using custom field criteria conditions.</div>
                    </div>
                </div>

                <div class="de-form-row">
                    <label for="fields_mode">Fields to Export</label>
                    <div style="max-width: 420px;">
                        <select name="fields_mode" id="fields_mode" class="form-select">
                            <option value="all" @selected(old('fields_mode', 'all') === 'all')>All Fields</option>
                            <option value="choose" @selected(old('fields_mode') === 'choose')>Choose Fields</option>
                        </select>
                    </div>
                </div>

                <div class="de-form-row d-none" id="chooseFieldsRow">
                    <label for="fieldSearchInput">Select Fields</label>
                    <div>
                        <div class="border rounded-3 p-3 bg-white shadow-sm" style="max-width: 420px;">
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="fieldSearchInput" class="form-control border-start-0" placeholder="Search">
                            </div>
                            <div class="form-check mb-2 pb-2 border-bottom fw-semibold">
                                <input class="form-check-input" type="checkbox" id="selectAllFields" checked>
                                <label class="form-check-label text-dark" for="selectAllFields">Select All</label>
                            </div>
                            <div id="fieldsChecklist" style="max-height: 220px; overflow-y: auto;"></div>
                        </div>
                    </div>
                </div>

                <div class="de-form-row">
                    <label for="exclude_fields">Exclude Fields</label>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="exclude_fields" id="exclude_fields" value="1"
                            {{ old('exclude_fields') ? 'checked' : '' }}>
                        <label class="form-check-label" for="exclude_fields">
                            Exclude system IDs from export
                            <i class="bi bi-info-circle text-muted ms-1" title="When checked, ID columns are removed from the file."></i>
                        </label>
                    </div>
                </div>

                <hr class="de-section-divider">

                <h6 class="de-section-title">Preferences</h6>

                <div class="de-form-row">
                    <label>Choose File Type</label>
                    <div>
                        <div class="de-radio-group">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="format" id="format_csv" value="csv"
                                    {{ old('format', 'csv') === 'csv' ? 'checked' : '' }}>
                                <label class="form-check-label" for="format_csv">CSV</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="format" id="format_xlsx" value="xlsx"
                                    {{ old('format') === 'xlsx' ? 'checked' : '' }}>
                                <label class="form-check-label" for="format_xlsx">XLSX</label>
                            </div>
                            <i class="bi bi-info-circle text-muted" title="CSV is best for large data. XLSX opens directly in Excel."></i>
                        </div>
                        @error('format')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="de-form-row">
                    <label for="charset">Charset</label>
                    <div style="max-width: 420px;">
                        <select name="charset" id="charset" class="form-select">
                            <option value="UTF-8" @selected(old('charset', 'UTF-8') === 'UTF-8')>UTF-8 (Unicode)</option>
                            <option value="UTF-16" @selected(old('charset') === 'UTF-16')>UTF-16 (Unicode)</option>
                            <option value="ISO-8859-1" @selected(old('charset') === 'ISO-8859-1')>ISO-8859-1 (Western Europe)</option>
                            <option value="ISO-8859-2" @selected(old('charset') === 'ISO-8859-2')>ISO-8859-2 (Eastern Europe)</option>
                            <option value="ISO-8859-8" @selected(old('charset') === 'ISO-8859-8')>ISO-8859-8 (Hebrew)</option>
                            <option value="ISO-8859-9" @selected(old('charset') === 'ISO-8859-9')>ISO-8859-9 (Turkish)</option>
                            <option value="ISO-8859-11" @selected(old('charset') === 'ISO-8859-11')>ISO-8859-11 (Thai)</option>
                            <option value="GB2312" @selected(old('charset') === 'GB2312')>GB2312 (Simplified Chinese)</option>
                            <option value="BIG5" @selected(old('charset') === 'BIG5')>BIG5 (Traditional Chinese)</option>
                            <option value="EUC-JP" @selected(old('charset') === 'EUC-JP')>EUC-JP (Japanese)</option>
                            <option value="Shift_JIS" @selected(old('charset') === 'Shift_JIS')>Shift_JIS (Japanese)</option>
                            <option value="KOI8-R" @selected(old('charset') === 'KOI8-R')>KOI8-R (Russian)</option>
                            <option value="Windows-1252" @selected(old('charset') === 'Windows-1252')>Windows-1252 (ANSI)</option>
                        </select>
                    </div>
                </div>

                <div class="de-form-row">
                    <label for="set_password">Set Password</label>
                    <div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="set_password" name="set_password" value="1"
                                {{ old('set_password') ? 'checked' : '' }}>
                            <label class="form-check-label" for="set_password">Off</label>
                        </div>
                        <div id="passwordFields" class="d-none" style="max-width: 320px;">
                            <input type="password" name="export_password" id="export_password" class="form-control mb-2"
                                   placeholder="Enter password" autocomplete="new-password">
                            <input type="password" name="export_password_confirmation" id="export_password_confirmation" class="form-control"
                                   placeholder="Confirm password" autocomplete="new-password">
                            <div class="de-hint">File will download as a password-protected ZIP.</div>
                        </div>
                    </div>
                </div>

                <hr class="de-section-divider">

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4" id="exportBtn">Export</button>
                    <a href="{{ route('settings.index') }}" class="btn btn-light border">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var moduleViews = @json($moduleViewsJson);
    var moduleFields = @json($moduleFields ?? []);
    var crmUsers = @json($users ?? []);
    var oldView = @json(old('custom_view'));

    var $module = $('#module');
    var $view = $('#custom_view');
    var passwordSwitch = document.getElementById('set_password');
    var passwordLabel = passwordSwitch ? passwordSwitch.nextElementSibling : null;
    var passwordFields = document.getElementById('passwordFields');
    var fieldsModeSelect = document.getElementById('fields_mode');
    var chooseFieldsRow = document.getElementById('chooseFieldsRow');
    var fieldsChecklist = document.getElementById('fieldsChecklist');
    var selectAllFields = document.getElementById('selectAllFields');
    var fieldSearchInput = document.getElementById('fieldSearchInput');

    var criteriaContainer = document.getElementById('criteriaRowsContainer');
    var criteriaCounter = 0;

    if ($module.length && $.fn.select2) {
        $module.select2({
            placeholder: 'Select a module',
            allowClear: true,
            width: '100%'
        });
    }

    function fillViews(moduleKey) {
        var views = moduleViews[moduleKey] || [{ value: 'all', label: 'All' }];
        $view.empty();
        views.forEach(function (v) {
            var selected = oldView && oldView === v.value ? ' selected' : '';
            $view.append('<option value="' + v.value + '"' + selected + '>' + v.label + '</option>');
        });
        if (!oldView) {
            $view.val(views[0].value);
        }
        oldView = null;
    }

    function renderFieldsChecklist(moduleKey) {
        if (!fieldsChecklist) return;
        fieldsChecklist.innerHTML = '';
        var fields = moduleFields[moduleKey] || [];

        if (fields.length === 0) {
            fieldsChecklist.innerHTML = '<div class="text-muted small">No fields available for this module.</div>';
            return;
        }

        fields.forEach(function (field, idx) {
            var div = document.createElement('div');
            div.className = 'form-check field-item mb-1';
            var id = 'field_chk_' + idx;
            div.innerHTML = '<input class="form-check-input field-chk" type="checkbox" name="selected_fields[]" value="' + field + '" id="' + id + '" checked>' +
                            '<label class="form-check-label text-secondary small ms-1" for="' + id + '">' + field + '</label>';
            fieldsChecklist.appendChild(div);
        });

        if (selectAllFields) selectAllFields.checked = true;
    }

    function toggleFieldsMode() {
        var isChoose = fieldsModeSelect && fieldsModeSelect.value === 'choose';
        if (chooseFieldsRow) chooseFieldsRow.classList.toggle('d-none', !isChoose);
        if (isChoose && fieldsChecklist && fieldsChecklist.children.length === 0) {
            renderFieldsChecklist($module.val() || '');
        }
    }

    if (selectAllFields) {
        selectAllFields.addEventListener('change', function () {
            var chks = fieldsChecklist.querySelectorAll('.field-chk');
            chks.forEach(function (chk) {
                chk.checked = selectAllFields.checked;
            });
        });
    }

    if (fieldSearchInput) {
        fieldSearchInput.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            var items = fieldsChecklist.querySelectorAll('.field-item');
            items.forEach(function (item) {
                var text = item.textContent.toLowerCase();
                item.style.display = text.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }

    function toggleFilterMode() {
        var mode = document.querySelector('input[name="filter_by"]:checked');
        var isCustom = !mode || mode.value === 'custom_view';
        document.getElementById('customViewRow').classList.toggle('d-none', !isCustom);
        document.getElementById('criteriaRow').classList.toggle('d-none', isCustom);

        if (!isCustom && criteriaContainer && criteriaContainer.children.length === 0) {
            addCriteriaRow();
        }
    }

    function addCriteriaRow(fieldVal, opVal, valueVal) {
        if (!criteriaContainer) return;

        var rowIdx = criteriaCounter++;
        var rowNum = criteriaContainer.children.length + 1;
        var moduleKey = $module.val() || '';
        var fields = moduleFields[moduleKey] || ['ID', 'Title', 'Owner', 'Status', 'Created At'];

        var row = document.createElement('div');
        row.className = 'd-flex flex-wrap align-items-center gap-2 mb-2 criteria-row';
        row.setAttribute('data-row-idx', rowIdx);

        var fieldOptionsHtml = '';
        fields.forEach(function (f) {
            var sel = fieldVal === f ? ' selected' : '';
            fieldOptionsHtml += '<option value="' + f + '"' + sel + '>' + f + '</option>';
        });

        var opOptionsHtml = `
            <option value="is" ${opVal === 'is' ? 'selected' : ''}>is</option>
            <option value="is_not" ${opVal === 'is_not' ? 'selected' : ''}>is not</option>
            <option value="contains" ${opVal === 'contains' ? 'selected' : ''}>contains</option>
            <option value="does_not_contain" ${opVal === 'does_not_contain' ? 'selected' : ''}>doesn't contain</option>
            <option value="starts_with" ${opVal === 'starts_with' ? 'selected' : ''}>starts with</option>
            <option value="ends_with" ${opVal === 'ends_with' ? 'selected' : ''}>ends with</option>
            <option value="is_empty" ${opVal === 'is_empty' ? 'selected' : ''}>is empty</option>
            <option value="is_not_empty" ${opVal === 'is_not_empty' ? 'selected' : ''}>is not empty</option>
        `;

        row.innerHTML = `
            <span class="badge bg-secondary rounded-circle px-2 py-1 row-num">${rowNum}</span>
            <select name="criteria_rows[${rowIdx}][field]" class="form-select form-select-sm field-select" style="max-width: 180px;">
                ${fieldOptionsHtml}
            </select>
            <select name="criteria_rows[${rowIdx}][operator]" class="form-select form-select-sm op-select" style="max-width: 140px;">
                ${opOptionsHtml}
            </select>
            <div class="val-container flex-grow-1" style="max-width: 240px;">
                <input type="text" name="criteria_rows[${rowIdx}][value]" class="form-control form-control-sm val-input" value="${valueVal || ''}" placeholder="Click to Select / Value">
            </div>
            <button type="button" class="btn btn-outline-success btn-sm btn-add-criteria" title="Add Condition"><i class="bi bi-plus-lg"></i></button>
            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-criteria" title="Remove Condition"><i class="bi bi-dash-lg"></i></button>
        `;

        criteriaContainer.appendChild(row);
        updateCriteriaRowControls();
        bindCriteriaEvents(row);
    }

    function bindCriteriaEvents(row) {
        var fieldSelect = row.querySelector('.field-select');
        var valContainer = row.querySelector('.val-container');
        var addBtn = row.querySelector('.btn-add-criteria');
        var removeBtn = row.querySelector('.btn-remove-criteria');

        function updateValInput() {
            var f = fieldSelect.value.toLowerCase();
            var rowIdx = row.getAttribute('data-row-idx');

            if (f.indexOf('owner') !== -1 || f.indexOf('user') !== -1 || f.indexOf('author') !== -1 || f.indexOf('by') !== -1) {
                var options = '<option value="">Click to Select Users.</option>';
                crmUsers.forEach(function (u) {
                    options += '<option value="' + u.name + '">' + u.name + '</option>';
                });
                valContainer.innerHTML = '<select name="criteria_rows[' + rowIdx + '][value]" class="form-select form-select-sm val-input">' + options + '</select>';
            } else {
                valContainer.innerHTML = '<input type="text" name="criteria_rows[' + rowIdx + '][value]" class="form-control form-control-sm val-input" placeholder="Value...">';
            }
        }

        fieldSelect.addEventListener('change', updateValInput);
        addBtn.addEventListener('click', function () {
            addCriteriaRow();
        });
        removeBtn.addEventListener('click', function () {
            row.remove();
            updateCriteriaRowControls();
        });

        updateValInput();
    }

    function updateCriteriaRowControls() {
        if (!criteriaContainer) return;
        var rows = criteriaContainer.querySelectorAll('.criteria-row');
        rows.forEach(function (r, idx) {
            var numBadge = r.querySelector('.row-num');
            if (numBadge) numBadge.textContent = idx + 1;
            var removeBtn = r.querySelector('.btn-remove-criteria');
            if (removeBtn) {
                removeBtn.classList.toggle('d-none', rows.length === 1);
            }
        });
    }

    function togglePassword() {
        var on = passwordSwitch && passwordSwitch.checked;
        if (passwordLabel) passwordLabel.textContent = on ? 'On' : 'Off';
        if (passwordFields) passwordFields.classList.toggle('d-none', !on);
    }

    $module.on('change', function () {
        var key = this.value;
        fillViews(key);
        renderFieldsChecklist(key);
        if (criteriaContainer) {
            criteriaContainer.innerHTML = '';
            var mode = document.querySelector('input[name="filter_by"]:checked');
            if (mode && mode.value === 'criteria') {
                addCriteriaRow();
            }
        }
    });

    document.querySelectorAll('input[name="filter_by"]').forEach(function (el) {
        el.addEventListener('change', toggleFilterMode);
    });

    if (fieldsModeSelect) {
        fieldsModeSelect.addEventListener('change', toggleFieldsMode);
    }

    if (passwordSwitch) passwordSwitch.addEventListener('change', togglePassword);

    fillViews($module.val() || '');
    renderFieldsChecklist($module.val() || '');
    toggleFieldsMode();
    toggleFilterMode();
    togglePassword();

    document.getElementById('dataExportForm').addEventListener('submit', function () {
        var btn = document.getElementById('exportBtn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Exporting...';
            setTimeout(function () {
                btn.disabled = false;
                btn.textContent = 'Export';
            }, 12000);
        }
    });
});
</script>
@endpush
