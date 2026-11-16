@php $unit = $unit ?? null; @endphp
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label">Name *</label>
        <input name="name" class="form-control" value="{{ old('name', $unit->name ?? '') }}" required>
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Code</label>
        <input name="code" class="form-control" value="{{ old('code', $unit->code ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Type *</label>
        <select name="type" class="form-select" required>
            @foreach(['region','cluster','branch'] as $t)
                <option value="{{ $t }}" @selected(old('type', $unit->type ?? '')===$t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Parent</label>
        <select name="parent_id" class="form-select">
            <option value="">— None —</option>
            @foreach($parents as $p)
                <option value="{{ $p->id }}" @selected(old('parent_id', $unit->parent_id ?? '')==$p->id)>{{ $p->name }} ({{ $p->type }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 form-check mt-4">
        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" @checked(old('is_active', $unit->is_active ?? true))>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
