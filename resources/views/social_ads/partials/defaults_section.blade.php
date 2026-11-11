<div class="sa-section">
    <h6 class="sa-section-title"><i class="bi bi-sliders text-primary"></i> Default Lead Settings</h6>
    <div class="sa-section-sub">Applied to new forms found on this platform. You can change them per form afterwards.</div>
    <form method="POST" action="{{ route('social_ads.defaults', $platformKey) }}">
        @csrf
        @include('social_ads.partials.lead_fields', ['values' => $defaults])
        <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
            <button type="submit" class="btn btn-custom btn-sm"><i class="bi bi-check2 me-1"></i>Save Defaults</button>
            @if($forms->isNotEmpty())
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="apply_to_existing" value="1" id="saApplyExisting">
                    <label class="form-check-label small" for="saApplyExisting">Also apply to the {{ $forms->count() }} existing form(s)</label>
                </div>
            @endif
        </div>
    </form>
</div>
