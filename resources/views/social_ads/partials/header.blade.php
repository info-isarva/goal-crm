<div class="card-header bg-white border-bottom py-3">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div class="d-flex align-items-center gap-3">
            @isset($icon)
                <span class="sa-platform-icon" style="background: {{ $color ?? '#5b3cc4' }};"><i class="bi {{ $icon }}"></i></span>
            @endisset
            <div>
                <h4 class="mb-1 fw-bold">{{ $title }}</h4>
                @isset($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endisset
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(!empty($showLeadsLink))
                <a href="{{ route('social_ads.leads', $leadsLinkParams ?? []) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list-ul me-1"></i>Leads Log</a>
            @endif
            <a href="{{ $backUrl ?? route('social_ads.index') }}" class="btn btn-light btn-sm">&laquo; {{ $backLabel ?? 'Back to Social Ads' }}</a>
        </div>
    </div>
</div>
