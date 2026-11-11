@extends('layouts.app')

@section('content')
@include('social_ads.partials.styles')
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        @include('social_ads.partials.header', [
            'title' => 'Google Ads Lead Forms',
            'subtitle' => 'Leads from Google Ads lead form assets (Search, YouTube, Discovery, Performance Max) are sent straight to the CRM.',
            'icon' => 'bi-google',
            'color' => '#ea4335',
            'showLeadsLink' => true,
            'leadsLinkParams' => ['platform' => 'google'],
        ])

        <div class="card-body p-4">
            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger py-2"><ul class="mb-0 small">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @if($integration->last_error)
                <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle-fill me-1"></i><strong>Last error:</strong> {{ $integration->last_error }}</div>
            @endif

            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="sa-section">
                        <h6 class="sa-section-title"><span class="sa-step">1</span> Webhook Details</h6>
                        <div class="sa-section-sub">Use the same URL and key for all your Google Ads lead forms.</div>
                        <div class="row">
                            <div class="col-md-7">
                                @include('social_ads.partials.copy_field', ['id' => 'saGoogleUrl', 'label' => 'Webhook URL', 'value' => $webhookUrl])
                            </div>
                            <div class="col-md-5">
                                @include('social_ads.partials.copy_field', ['id' => 'saGoogleKey', 'label' => 'Key', 'value' => $integration->webhook_key])
                            </div>
                        </div>
                        <form method="POST" action="{{ route('social_ads.google.regenerate_key') }}" onsubmit="return confirm('Generate a new key? Leads stop arriving until you update the key in every Google Ads lead form.');">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Regenerate Key</button>
                        </form>
                    </div>

                    <div class="sa-section">
                        <h6 class="sa-section-title"><span class="sa-step {{ $forms->isNotEmpty() ? 'done' : '' }}">{!! $forms->isNotEmpty() ? '<i class="bi bi-check"></i>' : '2' !!}</span> Lead Forms</h6>
                        <div class="sa-section-sub">Forms appear automatically when Google sends the first lead (including "Send test data"). Rename them and set owners with <i class="bi bi-gear"></i>.</div>
                        @include('social_ads.partials.forms_table', [
                            'forms' => $forms,
                            'emptyText' => 'No leads received yet. Add the webhook to a lead form in Google Ads and click "Send test data".',
                        ])
                    </div>

                    @include('social_ads.partials.defaults_section')
                </div>

                <div class="col-xl-4">
                    <div class="sa-section sa-guide">
                        <h6 class="sa-section-title"><i class="bi bi-journal-text text-primary"></i> Setup in Google Ads</h6>
                        <ol class="mt-2">
                            <li>Open <a href="https://ads.google.com" target="_blank" rel="noopener">Google Ads</a> &rarr; <strong>Assets</strong> &rarr; <strong>Lead forms</strong> and open (or create) your form.</li>
                            <li>Scroll to <strong>Export leads from Google Ads</strong> &rarr; <strong>Other data integration options</strong> &rarr; <strong>Webhook integration</strong>.</li>
                            <li>Paste the <em>Webhook URL</em> and <em>Key</em> from this page.</li>
                            <li>Click <strong>Send test data</strong>. A "Test Lead" entry appears in the Leads Log (test leads are not added to your CRM leads).</li>
                            <li>Save the form. Real leads are now created automatically.</li>
                        </ol>
                        <div class="sa-hint mt-2">Your CRM must be reachable on a public HTTPS address. Google retries delivery if the CRM is temporarily down.</div>
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
