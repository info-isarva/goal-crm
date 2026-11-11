{{-- Back + title for individual settings pages (content area) --}}
<div class="container-fluid px-4 pt-4 pb-0 settings-page-back-header">
    <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('settings.index') }}"
           class="btn btn-sm btn-light border d-inline-flex align-items-center justify-content-center"
           style="width:34px;height:34px;padding:0;"
           title="Back to Settings"
           aria-label="Back to Settings">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0">{{ $title }}</h4>
    </div>
</div>
