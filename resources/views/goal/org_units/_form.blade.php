@php $unit = $unit ?? null; @endphp
<div class="row g-3 mb-1">
    <div class="col-md-5">
        <label class="form-label fw-semibold" for="org-name">Name <span class="text-danger">*</span></label>
        <input id="org-name" name="name" class="form-control" value="{{ old('name', $unit->name ?? '') }}" required maxlength="150" placeholder="e.g. South Region">
        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold" for="org-code">Code</label>
        <input id="org-code" name="code" class="form-control" value="{{ old('code', $unit->code ?? '') }}" maxlength="50" placeholder="Optional short code">
        @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="org-type">Type <span class="text-danger">*</span></label>
        <select id="org-type" name="type" class="form-select" required>
            @foreach(['region','cluster','branch'] as $t)
                <option value="{{ $t }}" @selected(old('type', $unit->type ?? '') === $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
        @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="org-parent">Parent</label>
        <select id="org-parent" name="parent_id" class="form-select">
            <option value="">— None (top level) —</option>
            @foreach($parents as $p)
                <option value="{{ $p->id }}" @selected((string) old('parent_id', $unit->parent_id ?? '') === (string) $p->id)>
                    {{ $p->name }} ({{ ucfirst($p->type) }})
                </option>
            @endforeach
        </select>
        @error('parent_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        <div class="form-text">Typical: Region → Cluster → Branch.</div>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active"
                @checked((string) old('is_active', ($unit->is_active ?? true) ? '1' : '0') === '1')>
            <label class="form-check-label fw-semibold" for="is_active">Active</label>
            <div class="form-text">Inactive units stay in history but drop out of pickers. Inactivate all child units before inactivating a parent.</div>
            @error('is_active')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
