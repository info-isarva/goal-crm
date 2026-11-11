<div class="mb-3">
    <label class="form-label fw-semibold small mb-1" for="{{ $id }}">{{ $label }}</label>
    <div class="input-group input-group-sm sa-copy-group">
        <input type="text" class="form-control" id="{{ $id }}" value="{{ $value }}" readonly>
        <button type="button" class="btn btn-outline-secondary" data-sa-copy="{{ $id }}"><i class="bi bi-clipboard me-1"></i>Copy</button>
    </div>
    @isset($hint)
        <div class="sa-hint mt-1">{{ $hint }}</div>
    @endisset
</div>
