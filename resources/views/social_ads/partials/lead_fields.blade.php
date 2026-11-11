{{-- Lead defaults inputs. Expects $values (array/model with lead_source_id, lead_status, lead_priority, owner_id, default_category_ids) and lookups. --}}
@php
    $v = fn ($key) => old($key, data_get($values, $key));
    $selectedCats = old('default_category_ids', array_filter(array_map('intval', explode(',', (string) data_get($values, 'default_category_ids')))));
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold small">Lead Source</label>
        <select name="lead_source_id" class="form-select form-select-sm">
            <option value="">— None —</option>
            @foreach($leadSources as $src)
                <option value="{{ $src->id }}" @selected((string) $v('lead_source_id') === (string) $src->id)>{{ $src->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold small">Lead Owner <span class="text-danger">*</span></label>
        <select name="owner_id" class="form-select form-select-sm" required>
            <option value="">Select user</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected((string) $v('owner_id') === (string) $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold small">Lead Status <span class="text-danger">*</span></label>
        <select name="lead_status" class="form-select form-select-sm" required>
            @foreach($leadStatuses as $st)
                <option value="{{ $st->name }}" @selected(($v('lead_status') ?: \App\Services\LeadIntakeService::defaultStatus()) === $st->name)>{{ $st->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold small">Priority <span class="text-danger">*</span></label>
        <select name="lead_priority" class="form-select form-select-sm" required>
            @foreach(['high' => 'High', 'normal' => 'Normal', 'low' => 'Low'] as $val => $lbl)
                <option value="{{ $val }}" @selected(($v('lead_priority') ?: 'normal') === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold small">Product/Services</label>
        <select name="default_category_ids[]" class="form-select form-select-sm sa-select2" multiple data-placeholder="Select Product/Services">
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(in_array((int) $cat->id, array_map('intval', (array) $selectedCats), true))>{{ $cat->category_name }}</option>
            @endforeach
        </select>
    </div>
</div>
