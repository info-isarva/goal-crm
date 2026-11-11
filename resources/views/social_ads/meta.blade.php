@extends('layouts.app')

@section('content')
@include('social_ads.partials.styles')
@php
    $connected = $integration->isConnected();
    $subscribedCount = $pages->where('is_subscribed', true)->count();
@endphp
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        @include('social_ads.partials.header', [
            'title' => 'Facebook & Instagram Lead Ads',
            'subtitle' => 'Leads from Facebook and Instagram Instant Forms are created in the CRM within seconds.',
            'icon' => 'bi-facebook',
            'color' => '#1877f2',
            'showLeadsLink' => true,
            'leadsLinkParams' => ['platform' => 'meta'],
        ])

        <div class="card-body p-4">
            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger py-2"><ul class="mb-0 small">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @if($hostWarning)
                <div class="alert alert-danger py-2 small d-flex gap-2">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <div><strong>Facebook login will fail from this address.</strong> {{ $hostWarning }}</div>
                </div>
            @endif
            @if($integration->last_error)
                <div class="alert alert-warning py-2 small d-flex gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><strong>Last error:</strong> {{ $integration->last_error }}</div>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-xl-8">
                    {{-- Step 1 --}}
                    <div class="sa-section">
                        <h6 class="sa-section-title"><span class="sa-step {{ $hasAppCredentials ? 'done' : '' }}">{!! $hasAppCredentials ? '<i class="bi bi-check"></i>' : '1' !!}</span> Meta App Details</h6>
                        <div class="sa-section-sub">From your app at <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">developers.facebook.com/apps</a> &rarr; App settings &rarr; Basic.</div>
                        <form method="POST" action="{{ route('social_ads.meta.credentials') }}" autocomplete="off">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small">App ID <span class="text-danger">*</span></label>
                                    <input type="text" name="app_id" class="form-control form-control-sm" value="{{ old('app_id', $integration->credential('app_id')) }}" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small">App Secret <span class="text-danger">*</span></label>
                                    <input type="password" name="app_secret" class="form-control form-control-sm" autocomplete="new-password"
                                        placeholder="{{ $integration->credential('app_secret') ? '•••••••• saved (leave blank to keep)' : 'Paste App Secret' }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold small">API Version</label>
                                    <input type="text" name="graph_version" class="form-control form-control-sm" value="{{ old('graph_version', $integration->credential('graph_version', 'v21.0')) }}" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-custom btn-sm mt-3"><i class="bi bi-check2 me-1"></i>Save App Details</button>
                        </form>
                    </div>

                    {{-- Step 2 --}}
                    <div class="sa-section">
                        <h6 class="sa-section-title"><span class="sa-step {{ $connected ? 'done' : '' }}">{!! $connected ? '<i class="bi bi-check"></i>' : '2' !!}</span> Connect Facebook Account</h6>
                        @if($connected)
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="small">
                                    Connected as <strong>{{ $integration->connected_name }}</strong>
                                    <span class="text-muted">since {{ $integration->connected_at->format('d M Y, h:i A') }}</span>
                                    @if($integration->token_expires_at)
                                        <div class="text-muted">Login valid until {{ $integration->token_expires_at->format('d M Y') }}. Page access keeps working after that; reconnect only to add new Pages.</div>
                                    @endif
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('social_ads.meta.connect') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-facebook me-1"></i>Reconnect</a>
                                    <form method="POST" action="{{ route('social_ads.meta.refresh_pages') }}">@csrf<button class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Refresh Pages</button></form>
                                    <form method="POST" action="{{ route('social_ads.meta.disconnect') }}" onsubmit="return confirm('Disconnect Facebook? New leads will stop arriving.');">@csrf<button class="btn btn-outline-danger btn-sm">Disconnect</button></form>
                                </div>
                            </div>
                        @else
                            <div class="sa-section-sub">Log in with a Facebook account that is an admin of your business Page(s), and allow all requested permissions.</div>
                            @if($hasAppCredentials)
                                <a href="{{ route('social_ads.meta.connect') }}" class="btn btn-sm text-white" style="background: #1877f2;"><i class="bi bi-facebook me-1"></i>Connect with Facebook</a>
                            @else
                                <button class="btn btn-sm btn-secondary" disabled><i class="bi bi-facebook me-1"></i>Connect with Facebook</button>
                                <span class="sa-hint ms-2">Save the App details first.</span>
                            @endif
                        @endif
                    </div>

                    {{-- Step 3 --}}
                    <div class="sa-section">
                        <h6 class="sa-section-title"><span class="sa-step {{ $subscribedCount ? 'done' : '' }}">{!! $subscribedCount ? '<i class="bi bi-check"></i>' : '3' !!}</span> Choose Pages</h6>
                        <div class="sa-section-sub">Turn on <strong>Receive Leads</strong> for every Page that runs lead ads (Instagram lead ads use the linked Facebook Page).</div>
                        @if($pages->isEmpty())
                            <div class="text-muted small">No Pages yet. Connect your Facebook account above.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table sa-table mb-0">
                                    <thead class="table-light"><tr><th>Page</th><th>Forms</th><th>Receive Leads</th><th class="text-end">Actions</th></tr></thead>
                                    <tbody>
                                        @foreach($pages as $page)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $page->name }}</div>
                                                    <small class="text-muted">ID {{ $page->external_id }}@if(!empty($page->meta['category'])) &middot; {{ $page->meta['category'] }}@endif</small>
                                                    @if($page->last_error)<div class="small text-danger">{{ \Illuminate\Support\Str::limit($page->last_error, 160) }}</div>@endif
                                                    @if(!$page->access_token)<div class="small text-warning">Access removed. Reconnect Facebook to use this Page.</div>@endif
                                                </td>
                                                <td>{{ $forms->where('social_ad_account_id', $page->id)->count() }}</td>
                                                <td>
                                                    @if($page->is_subscribed)
                                                        <form method="POST" action="{{ route('social_ads.accounts.unsubscribe', $page) }}" onsubmit="return confirm('Stop receiving leads from this Page?');">
                                                            @csrf
                                                            <button class="badge border-0 sa-badge-on"><i class="bi bi-toggle-on me-1"></i>On</button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('social_ads.accounts.subscribe', $page) }}">
                                                            @csrf
                                                            <button class="badge border-0 sa-badge-off" @disabled(!$page->access_token)><i class="bi bi-toggle-off me-1"></i>Off</button>
                                                        </form>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST" action="{{ route('social_ads.accounts.sync_forms', $page) }}" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-secondary" @disabled(!$page->access_token)><i class="bi bi-arrow-repeat me-1"></i>Sync Forms</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Step 4 --}}
                    <div class="sa-section">
                        <h6 class="sa-section-title"><span class="sa-step {{ $forms->isNotEmpty() ? 'done' : '' }}">{!! $forms->isNotEmpty() ? '<i class="bi bi-check"></i>' : '4' !!}</span> Lead Forms</h6>
                        <div class="sa-section-sub">Each Instant Form can have its own owner, source and field mapping (<i class="bi bi-gear"></i>). <i class="bi bi-arrow-repeat"></i> fetches leads now; the CRM also checks every 15 minutes.</div>
                        @include('social_ads.partials.forms_table', [
                            'forms' => $forms,
                            'showAccount' => true,
                            'accountLabel' => 'Page',
                            'emptyText' => 'Forms appear here after you turn on Receive Leads for a Page.',
                        ])
                    </div>

                    @include('social_ads.partials.defaults_section')
                </div>

                <div class="col-xl-4">
                    <div class="sa-section">
                        <h6 class="sa-section-title"><i class="bi bi-link-45deg text-primary"></i> Values for your Meta App</h6>
                        <div class="sa-section-sub">Copy these into the Meta developer dashboard.</div>
                        @include('social_ads.partials.copy_field', ['id' => 'saMetaRedirect', 'label' => 'Valid OAuth Redirect URI', 'value' => $redirectUri, 'hint' => 'Facebook Login for Business → Settings'])
                        @include('social_ads.partials.copy_field', ['id' => 'saMetaWebhook', 'label' => 'Webhook Callback URL', 'value' => $webhookUrl, 'hint' => 'Webhooks → Page → subscribe to "leadgen"'])
                        @include('social_ads.partials.copy_field', ['id' => 'saMetaVerify', 'label' => 'Webhook Verify Token', 'value' => $integration->webhook_key])
                        @if($connected)
                            <form method="POST" action="{{ route('social_ads.meta.register_webhook') }}">
                                @csrf
                                <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-broadcast me-1"></i>Register Webhook Automatically</button>
                            </form>
                        @endif
                    </div>

                    <div class="sa-section sa-guide">
                        <h6 class="sa-section-title"><i class="bi bi-journal-text text-primary"></i> One-time Setup Guide</h6>
                        <ol class="mt-2">
                            <li>Go to <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">Meta for Developers</a> and create an app of type <strong>Business</strong>.</li>
                            <li>Add the <strong>Facebook Login for Business</strong> product and paste the <em>Valid OAuth Redirect URI</em>.</li>
                            <li>Add the <strong>Webhooks</strong> product, choose <em>Page</em>, paste the Callback URL and Verify Token, then subscribe to <code>leadgen</code>. (Or use "Register Webhook Automatically" after connecting.)</li>
                            <li>Copy the App ID and App Secret into Step 1 and save.</li>
                            <li>Click <strong>Connect with Facebook</strong> and allow: {{ implode(', ', $scopes) }}.</li>
                            <li>Turn on <strong>Receive Leads</strong> for your Pages.</li>
                            <li>In Meta Business Suite &rarr; Business settings &rarr; <strong>Integrations &rarr; Leads Access</strong>, make sure this app (or "All CRMs") has access.</li>
                            <li>Test with the <a href="https://developers.facebook.com/tools/lead-ads-testing" target="_blank" rel="noopener">Lead Ads Testing Tool</a>.</li>
                        </ol>
                        <div class="sa-hint mt-2">Live use requires the app to be in <strong>Live</strong> mode with Advanced Access for <code>leads_retrieval</code> and Page permissions (Meta App Review). Your CRM must be on a public HTTPS address for webhooks.</div>
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
