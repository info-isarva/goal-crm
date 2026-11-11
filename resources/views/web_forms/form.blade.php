@extends('layouts.app')

@section('content')
@php
    $isEdit = $form->exists;
    $selectedCategories = old('default_category_ids', $form->defaultCategoryIdList());
@endphp
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h4 class="mb-1 fw-bold">{{ $isEdit ? 'Edit Web Form' : 'New Web Form' }}</h4>
                    <small class="text-muted">Choose the fields visitors fill in and how the resulting leads are created.</small>
                </div>
                <a href="{{ route('web_forms.index') }}" class="btn btn-light btn-sm">&laquo; Back to Web Forms</a>
            </div>
        </div>

        <div class="card-body p-4">
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
                .wf-section-title { font-size: .95rem; font-weight: 700; color: #0f172a; margin: 0 0 14px; }
                .wf-section { border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 20px; background: #fff; }
                .wf-hint { color: #64748b; font-size: .8rem; margin-top: 4px; }
                .wf-fields-table td, .wf-fields-table th { vertical-align: middle; }
                .wf-fields-table tr.wf-off td:not(:first-child):not(:nth-child(2)) { opacity: .45; }
                .wf-order-btn { padding: 0 6px; line-height: 1.2; }
                .wf-preview-sticky { position: sticky; top: 16px; }
                .wf-preview { border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; background: #fff; box-shadow: 0 4px 18px rgba(15,23,42,.06); }
                .wf-preview h5 { font-weight: 700; margin-bottom: 4px; }
                .wf-preview .wf-p-intro { color: #6b7280; font-size: .85rem; margin-bottom: 14px; }
                .wf-preview .wf-p-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 12px; }
                .wf-preview .wf-p-full { grid-column: 1 / -1; }
                .wf-preview label { font-size: .78rem; font-weight: 600; margin-bottom: 4px; color: #374151; }
                .wf-preview .wf-p-input { border: 1px solid #d1d5db; border-radius: 7px; height: 34px; background: #fff; font-size: .78rem; color: #9ca3af; padding: 7px 10px; overflow: hidden; white-space: nowrap; }
                .wf-preview .wf-p-textarea { height: 70px; }
                .wf-preview .wf-p-btn { margin-top: 14px; color: #fff; border: 0; border-radius: 7px; padding: 8px 20px; font-weight: 600; font-size: .85rem; }
                .wf-color-input { width: 56px; height: 38px; padding: 3px; }
            </style>

            <form method="POST" action="{{ $isEdit ? route('web_forms.update', $form) : route('web_forms.store') }}" id="webFormBuilder" autocomplete="off">
                @csrf
                @if($isEdit) @method('PUT') @endif

                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="wf-section">
                            <h6 class="wf-section-title">Form Details</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="wf_name">Form Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="wf_name" class="form-control @error('name') is-invalid @enderror" maxlength="150" required
                                        value="{{ old('name', $form->name) }}" placeholder="e.g. Website Contact Form">
                                    <div class="wf-hint">Internal name, visitors don't see it.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="wf_heading">Form Heading</label>
                                    <input type="text" name="heading" id="wf_heading" class="form-control" maxlength="150"
                                        value="{{ old('heading', $form->heading) }}" placeholder="e.g. Contact Us">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="wf_intro">Intro Text</label>
                                    <textarea name="intro_text" id="wf_intro" class="form-control" rows="2" maxlength="500">{{ old('intro_text', $form->intro_text) }}</textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="wf_button">Button Text <span class="text-danger">*</span></label>
                                    <input type="text" name="submit_button_text" id="wf_button" class="form-control" maxlength="50" required
                                        value="{{ old('submit_button_text', $form->submit_button_text) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="wf_color">Theme Color</label>
                                    <div class="d-flex gap-2">
                                        <input type="color" id="wf_color_picker" class="form-control form-control-color wf-color-input" value="{{ old('theme_color', $form->theme_color) }}">
                                        <input type="text" name="theme_color" id="wf_color" class="form-control @error('theme_color') is-invalid @enderror" maxlength="7"
                                            value="{{ old('theme_color', $form->theme_color) }}">
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input type="hidden" name="is_active" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="wf_active" value="1" @checked(old('is_active', $form->is_active))>
                                        <label class="form-check-label fw-semibold" for="wf_active">Active (accept submissions)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wf-section">
                            <h6 class="wf-section-title mb-1">Form Fields</h6>
                            <div class="wf-hint mb-3">Tick the fields to show, rename labels, mark them required, and use the arrows to change the order. Email or Mobile must be enabled.</div>
                            @error('fields')
                                <div class="alert alert-danger py-2 small">{{ $message }}</div>
                            @enderror
                            <div class="table-responsive">
                                <table class="table table-sm wf-fields-table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 64px;">Order</th>
                                            <th style="width: 60px;" class="text-center">Show</th>
                                            <th style="min-width: 160px;">Label</th>
                                            <th style="min-width: 160px;">Placeholder</th>
                                            <th style="width: 90px;" class="text-center">Required</th>
                                            <th class="text-muted small fw-normal" style="min-width: 120px;">CRM Field</th>
                                        </tr>
                                    </thead>
                                    <tbody id="wfFieldRows">
                                        @foreach($fieldRows as $i => $row)
                                            <tr data-type="{{ $row['type'] }}" class="{{ $row['enabled'] ? '' : 'wf-off' }}">
                                                <td class="text-nowrap">
                                                    <button type="button" class="btn btn-light btn-sm wf-order-btn" data-move="up" title="Move up"><i class="bi bi-arrow-up"></i></button>
                                                    <button type="button" class="btn btn-light btn-sm wf-order-btn" data-move="down" title="Move down"><i class="bi bi-arrow-down"></i></button>
                                                    <input type="hidden" data-name="key" value="{{ $row['key'] }}">
                                                </td>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input wf-enabled" data-name="enabled" value="1"
                                                        @checked($row['enabled']) @disabled($row['locked'])>
                                                    @if($row['locked'])
                                                        <input type="hidden" data-name="enabled" value="1">
                                                    @endif
                                                </td>
                                                <td><input type="text" class="form-control form-control-sm wf-label" data-name="label" maxlength="100" value="{{ $row['label'] }}"></td>
                                                <td><input type="text" class="form-control form-control-sm wf-placeholder" data-name="placeholder" maxlength="150" value="{{ $row['placeholder'] }}"></td>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input wf-required" data-name="required" value="1"
                                                        @checked($row['required']) @disabled($row['locked'])>
                                                </td>
                                                <td class="text-muted small">{{ $row['catalog_label'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="wf-section">
                            <h6 class="wf-section-title">Lead Settings</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="wf_source">Lead Source <span class="text-danger">*</span></label>
                                    <select name="lead_source_id" id="wf_source" class="form-select @error('lead_source_id') is-invalid @enderror" required>
                                        <option value="">Select lead source</option>
                                        @foreach($leadSources as $src)
                                            <option value="{{ $src->id }}" @selected((string) old('lead_source_id', $form->lead_source_id) === (string) $src->id)>{{ $src->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="wf_owner">Lead Owner <span class="text-danger">*</span></label>
                                    <select name="owner_id" id="wf_owner" class="form-select @error('owner_id') is-invalid @enderror" required>
                                        <option value="">Select user</option>
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}" @selected((string) old('owner_id', $form->owner_id) === (string) $u->id)>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="wf-hint">New leads are owned by and assigned to this user.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="wf_status">Lead Status <span class="text-danger">*</span></label>
                                    <select name="lead_status" id="wf_status" class="form-select" required>
                                        @foreach($leadStatuses as $st)
                                            <option value="{{ $st->name }}" @selected(old('lead_status', $form->lead_status) === $st->name)>{{ $st->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="wf_priority">Priority <span class="text-danger">*</span></label>
                                    <select name="lead_priority" id="wf_priority" class="form-select" required>
                                        @foreach(['high' => 'High', 'normal' => 'Normal', 'low' => 'Low'] as $val => $lbl)
                                            <option value="{{ $val }}" @selected(old('lead_priority', $form->lead_priority) === $val)>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="wf_categories">Default Product/Services</label>
                                    <select name="default_category_ids[]" id="wf_categories" class="form-select" multiple size="4">
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" @selected(in_array((int) $cat->id, array_map('intval', (array) $selectedCategories), true))>{{ $cat->category_name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="wf-hint">Used when the Product/Service field is hidden or the visitor leaves it empty.</div>
                                </div>
                            </div>
                        </div>

                        <div class="wf-section">
                            <h6 class="wf-section-title">After Submission &amp; Security</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="wf_success">Thank-you Message <span class="text-danger">*</span></label>
                                    <textarea name="success_message" id="wf_success" class="form-control" rows="2" maxlength="500" required>{{ old('success_message', $form->success_message) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="wf_redirect">Redirect URL (optional)</label>
                                    <input type="url" name="redirect_url" id="wf_redirect" class="form-control @error('redirect_url') is-invalid @enderror" maxlength="500"
                                        value="{{ old('redirect_url', $form->redirect_url) }}" placeholder="https://yourwebsite.com/thank-you">
                                    <div class="wf-hint">If set, visitors go to this page instead of seeing the message.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="wf_domains">Allowed Websites (optional)</label>
                                    <input type="text" name="allowed_domains" id="wf_domains" class="form-control" maxlength="1000"
                                        value="{{ old('allowed_domains', $form->allowed_domains) }}" placeholder="yourwebsite.com, landing.example.com">
                                    <div class="wf-hint">Comma separated. Leave empty to accept submissions from any website. Subdomains are included.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-custom"><i class="bi bi-check2 me-1"></i>{{ $isEdit ? 'Save Changes' : 'Create & Get Embed Code' }}</button>
                            <a href="{{ route('web_forms.index') }}" class="btn btn-light">Cancel</a>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="wf-preview-sticky">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold mb-0">Live Preview</h6>
                                <small class="text-muted">How visitors will see it</small>
                            </div>
                            <div class="wf-preview" id="wfPreview">
                                <h5 id="wfPrevHeading"></h5>
                                <div class="wf-p-intro" id="wfPrevIntro"></div>
                                <div class="wf-p-grid" id="wfPrevGrid"></div>
                                <button type="button" class="wf-p-btn" id="wfPrevBtn"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('webFormBuilder');
    var tbody = document.getElementById('wfFieldRows');

    function syncRow(tr) {
        var cb = tr.querySelector('.wf-enabled');
        tr.classList.toggle('wf-off', !cb.checked);
    }

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-move]');
        if (!btn) return;
        var tr = btn.closest('tr');
        if (btn.getAttribute('data-move') === 'up' && tr.previousElementSibling) {
            tbody.insertBefore(tr, tr.previousElementSibling);
        } else if (btn.getAttribute('data-move') === 'down' && tr.nextElementSibling) {
            tbody.insertBefore(tr.nextElementSibling, tr);
        }
        renderPreview();
    });

    tbody.addEventListener('change', function (e) {
        if (e.target.classList.contains('wf-enabled')) syncRow(e.target.closest('tr'));
        renderPreview();
    });
    tbody.addEventListener('input', renderPreview);

    ['wf_heading', 'wf_intro', 'wf_button'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', renderPreview);
    });

    var picker = document.getElementById('wf_color_picker');
    var colorText = document.getElementById('wf_color');
    picker.addEventListener('input', function () { colorText.value = picker.value; renderPreview(); });
    colorText.addEventListener('input', function () {
        if (/^#[0-9a-fA-F]{6}$/.test(colorText.value)) { picker.value = colorText.value; renderPreview(); }
    });

    function renderPreview() {
        document.getElementById('wfPrevHeading').textContent = document.getElementById('wf_heading').value;
        document.getElementById('wfPrevIntro').textContent = document.getElementById('wf_intro').value;
        var btn = document.getElementById('wfPrevBtn');
        btn.textContent = document.getElementById('wf_button').value || 'Submit';
        btn.style.background = /^#[0-9a-fA-F]{6}$/.test(colorText.value) ? colorText.value : '#5b3cc4';

        var grid = document.getElementById('wfPrevGrid');
        grid.innerHTML = '';
        var halves = [];
        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (tr) {
            if (!tr.querySelector('.wf-enabled').checked) return;
            var type = tr.getAttribute('data-type');
            var full = type === 'textarea' || type === 'select';
            var wrap = document.createElement('div');
            if (full) wrap.className = 'wf-p-full'; else halves.push(wrap);
            var label = document.createElement('label');
            label.textContent = tr.querySelector('.wf-label').value || '';
            if (tr.querySelector('.wf-required').checked) {
                var star = document.createElement('span');
                star.className = 'text-danger ms-1';
                star.textContent = '*';
                label.appendChild(star);
            }
            var box = document.createElement('div');
            box.className = 'wf-p-input' + (type === 'textarea' ? ' wf-p-textarea' : '');
            box.textContent = tr.querySelector('.wf-placeholder').value || (type === 'select' ? 'Select' : '');
            wrap.appendChild(label);
            wrap.appendChild(box);
            grid.appendChild(wrap);
        });
        if (halves.length % 2 === 1) halves[halves.length - 1].className = 'wf-p-full';
    }

    form.addEventListener('submit', function () {
        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (tr, i) {
            Array.prototype.forEach.call(tr.querySelectorAll('[data-name]'), function (input) {
                input.name = 'fields[' + i + '][' + input.getAttribute('data-name') + ']';
            });
        });
    });

    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#wf_categories').select2({ width: '100%', placeholder: 'Select Product/Services', closeOnSelect: false });
    }

    renderPreview();
})();
</script>
@endpush
