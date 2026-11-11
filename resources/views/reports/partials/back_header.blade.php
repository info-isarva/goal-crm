{{-- Back + title for individual report pages (content area) --}}
<div class="container-fluid px-4 pt-4 pb-0 report-page-back-header">
    <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('reports.index') }}"
           class="btn btn-sm btn-light border d-inline-flex align-items-center justify-content-center"
           style="width:34px;height:34px;padding:0;"
           title="Back to Reports"
           aria-label="Back to Reports">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0">{{ $title }}</h4>
    </div>
</div>
