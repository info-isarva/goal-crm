@extends('layouts.app')

@section('content')
@include('social_ads.partials.styles')
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        @include('social_ads.partials.header', [
            'title' => 'Generic Lead Webhooks',
            'subtitle' => 'Receive leads from any tool that can send a webhook: Zapier, Make, Pabbly, Unbounce, Instapage, Typeform, TikTok via Zapier, and more.',
            'icon' => 'bi-plug',
            'color' => '#7c3aed',
            'showLeadsLink' => true,
            'leadsLinkParams' => ['platform' => 'generic'],
        ])

        <div class="card-body p-4">
            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger py-2"><ul class="mb-0 small">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="sa-section">
                        <h6 class="sa-section-title"><i class="bi bi-plus-circle text-primary"></i> New Webhook</h6>
                        <div class="sa-section-sub">Create one webhook per source so each gets its own owner, lead source and field mapping.</div>
                        <form method="POST" action="{{ route('social_ads.generic.store') }}" class="d-flex flex-wrap gap-2">
                            @csrf
                            <input type="text" name="name" class="form-control form-control-sm" style="max-width: 360px;" maxlength="150" required placeholder="e.g. TikTok Ads via Zapier" value="{{ old('name') }}">
                            <button class="btn btn-custom btn-sm"><i class="bi bi-plus-lg me-1"></i>Create Webhook</button>
                        </form>
                    </div>

                    <div class="sa-section">
                        <h6 class="sa-section-title"><i class="bi bi-plug text-primary"></i> Your Webhooks</h6>
                        @if($forms->isEmpty())
                            <div class="text-muted small">No webhooks yet.</div>
                        @else
                            @foreach($forms as $f)
                                <div class="border rounded-3 p-3 mb-2">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                        <div>
                                            <span class="fw-semibold">{{ $f->name }}</span>
                                            <span class="badge {{ $f->is_enabled ? 'sa-badge-on' : 'sa-badge-off' }} ms-1">{{ $f->is_enabled ? 'On' : 'Off' }}</span>
                                            <div class="small text-muted">
                                                {{ number_format($f->leads_count) }} lead(s) &middot; Owner: {{ $f->owner->name ?? '—' }}
                                                &middot; Last lead: {{ $f->last_lead_at ? $f->last_lead_at->format('d M Y, h:i A') : '—' }}
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <form method="POST" action="{{ route('social_ads.forms.toggle', $f) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $f->is_enabled ? 'Turn Off' : 'Turn On' }}</button></form>
                                            <a href="{{ route('social_ads.forms.edit', $f) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-gear me-1"></i>Settings &amp; Mapping</a>
                                        </div>
                                    </div>
                                    @include('social_ads.partials.copy_field', ['id' => 'saGenUrl' . $f->id, 'label' => 'Webhook URL (POST)', 'value' => $f->webhookUrl()])
                                </div>
                            @endforeach
                        @endif
                    </div>

                    @include('social_ads.partials.defaults_section')
                </div>

                <div class="col-xl-4">
                    <div class="sa-section sa-guide">
                        <h6 class="sa-section-title"><i class="bi bi-journal-text text-primary"></i> How to Send Leads</h6>
                        <ol class="mt-2">
                            <li>Send an HTTP <strong>POST</strong> to the webhook URL, as JSON or form data.</li>
                            <li>Common field names are recognised automatically: <code>name</code>, <code>first_name</code>, <code>last_name</code>, <code>email</code>, <code>phone</code>, <code>company</code>, <code>job_title</code>, <code>city</code>, <code>state</code>, <code>country</code>, <code>message</code>.</li>
                            <li>Other fields are added to the lead description, or map them under <em>Settings &amp; Mapping</em>.</li>
                            <li>Optional: <code>lead_id</code> (prevents duplicates), <code>campaign_name</code>, <code>ad_name</code>, <code>is_test</code>.</li>
                        </ol>
                        <div class="small fw-semibold mt-3 mb-1">Example JSON</div>
<pre class="bg-dark text-light rounded-3 p-3 small mb-0" style="white-space: pre-wrap;">{
  "lead_id": "12345",
  "name": "Priya Sharma",
  "email": "priya@example.com",
  "phone": "+91 98765 43210",
  "company": "Acme Pvt Ltd",
  "message": "Need a demo",
  "campaign_name": "Diwali Offer"
}</pre>
                        <div class="sa-hint mt-2">Response: <code>{"ok": true, "status": "created", "lead_id": 123}</code></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('social_ads.partials.copy_js')
@endpush
