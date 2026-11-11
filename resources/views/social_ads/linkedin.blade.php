@extends('layouts.app')

@section('content')
@include('social_ads.partials.styles')
@php
    $connected = $integration->isConnected();
    $activeAccounts = $accounts->where('is_subscribed', true)->count();
@endphp
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        @include('social_ads.partials.header', [
            'title' => 'LinkedIn Lead Gen Forms',
            'subtitle' => 'Leads from LinkedIn Lead Gen Form campaigns are imported into the CRM every 15 minutes.',
            'icon' => 'bi-linkedin',
            'color' => '#0a66c2',
            'showLeadsLink' => true,
            'leadsLinkParams' => ['platform' => 'linkedin'],
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
                        <h6 class="sa-section-title"><span class="sa-step {{ $hasAppCredentials ? 'done' : '' }}">{!! $hasAppCredentials ? '<i class="bi bi-check"></i>' : '1' !!}</span> LinkedIn App Details</h6>
                        <div class="sa-section-sub">From <a href="https://www.linkedin.com/developers/apps" target="_blank" rel="noopener">linkedin.com/developers/apps</a> &rarr; your app &rarr; Auth.</div>
                        <form method="POST" action="{{ route('social_ads.linkedin.credentials') }}" autocomplete="off">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Client ID <span class="text-danger">*</span></label>
                                    <input type="text" name="client_id" class="form-control form-control-sm" value="{{ old('client_id', $integration->credential('client_id')) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Client Secret <span class="text-danger">*</span></label>
                                    <input type="password" name="client_secret" class="form-control form-control-sm" autocomplete="new-password"
                                        placeholder="{{ $integration->credential('client_secret') ? '•••••••• saved (leave blank to keep)' : 'Paste Client Secret' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small">API Version (YYYYMM)</label>
                                    <input type="text" name="api_version" class="form-control form-control-sm" value="{{ old('api_version', $integration->credential('api_version')) }}" placeholder="{{ $apiVersion }}">
                                    <div class="sa-hint mt-1">Leave blank to use {{ $apiVersion }}.</div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold small">OAuth Scopes</label>
                                    <input type="text" name="scopes" class="form-control form-control-sm" value="{{ old('scopes', $integration->credential('scopes')) }}" placeholder="{{ \App\Services\SocialAds\LinkedInLeadGenService::DEFAULT_SCOPES }}">
                                    <div class="sa-hint mt-1">Leave blank for the default. Only change if LinkedIn granted your app different scopes.</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-custom btn-sm mt-3"><i class="bi bi-check2 me-1"></i>Save App Details</button>
                        </form>
                    </div>

                    <div class="sa-section">
                        <h6 class="sa-section-title"><span class="sa-step {{ $connected ? 'done' : '' }}">{!! $connected ? '<i class="bi bi-check"></i>' : '2' !!}</span> Connect LinkedIn Account</h6>
                        @if($connected)
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="small">
                                    Connected <span class="text-muted">since {{ $integration->connected_at->format('d M Y, h:i A') }}</span>
                                    @if($integration->token_expires_at)
                                        <div class="text-muted">Access valid until {{ $integration->token_expires_at->format('d M Y') }}@if(!$integration->credential('refresh_token')), then reconnect @else (renews automatically)@endif.</div>
                                    @endif
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('social_ads.linkedin.connect') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-linkedin me-1"></i>Reconnect</a>
                                    <form method="POST" action="{{ route('social_ads.linkedin.refresh_accounts') }}">@csrf<button class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Refresh Ad Accounts</button></form>
                                    <form method="POST" action="{{ route('social_ads.linkedin.disconnect') }}" onsubmit="return confirm('Disconnect LinkedIn? New leads will stop arriving.');">@csrf<button class="btn btn-outline-danger btn-sm">Disconnect</button></form>
                                </div>
                            </div>
                        @else
                            <div class="sa-section-sub">Log in with a LinkedIn member who has access to your Campaign Manager ad account(s).</div>
                            @if($hasAppCredentials)
                                <a href="{{ route('social_ads.linkedin.connect') }}" class="btn btn-sm text-white" style="background: #0a66c2;"><i class="bi bi-linkedin me-1"></i>Connect with LinkedIn</a>
                            @else
                                <button class="btn btn-sm btn-secondary" disabled><i class="bi bi-linkedin me-1"></i>Connect with LinkedIn</button>
                                <span class="sa-hint ms-2">Save the App details first.</span>
                            @endif
                        @endif
                    </div>

                    <div class="sa-section">
                        <h6 class="sa-section-title"><span class="sa-step {{ $activeAccounts ? 'done' : '' }}">{!! $activeAccounts ? '<i class="bi bi-check"></i>' : '3' !!}</span> Ad Accounts</h6>
                        <div class="sa-section-sub">Leads are imported from ad accounts with <strong>Import Leads</strong> turned on.</div>
                        @if($accounts->isEmpty())
                            <div class="text-muted small">No ad accounts yet. Connect LinkedIn above.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table sa-table mb-0">
                                    <thead class="table-light"><tr><th>Ad Account</th><th>Forms</th><th>Last Sync</th><th>Import Leads</th><th class="text-end">Actions</th></tr></thead>
                                    <tbody>
                                        @foreach($accounts as $acc)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $acc->name }}</div>
                                                    <small class="text-muted">ID {{ $acc->external_id }}@if(!empty($acc->meta['status'])) &middot; {{ ucfirst(strtolower($acc->meta['status'])) }}@endif</small>
                                                    @if($acc->last_error)<div class="small text-danger">{{ \Illuminate\Support\Str::limit($acc->last_error, 160) }}</div>@endif
                                                </td>
                                                <td>{{ $forms->where('social_ad_account_id', $acc->id)->count() }}</td>
                                                <td class="small text-nowrap">{{ $acc->last_synced_at ? $acc->last_synced_at->format('d M Y, h:i A') : '—' }}</td>
                                                <td>
                                                    <form method="POST" action="{{ route($acc->is_subscribed ? 'social_ads.accounts.unsubscribe' : 'social_ads.accounts.subscribe', $acc) }}">
                                                        @csrf
                                                        <button class="badge border-0 {{ $acc->is_subscribed ? 'sa-badge-on' : 'sa-badge-off' }}">
                                                            <i class="bi {{ $acc->is_subscribed ? 'bi-toggle-on' : 'bi-toggle-off' }} me-1"></i>{{ $acc->is_subscribed ? 'On' : 'Off' }}
                                                        </button>
                                                    </form>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <form method="POST" action="{{ route('social_ads.accounts.sync_forms', $acc) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary" title="Sync forms"><i class="bi bi-ui-checks me-1"></i>Forms</button></form>
                                                    <form method="POST" action="{{ route('social_ads.accounts.sync_leads', $acc) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary" title="Fetch leads now"><i class="bi bi-arrow-repeat me-1"></i>Leads</button></form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="sa-section">
                        <h6 class="sa-section-title"><span class="sa-step {{ $forms->isNotEmpty() ? 'done' : '' }}">{!! $forms->isNotEmpty() ? '<i class="bi bi-check"></i>' : '4' !!}</span> Lead Gen Forms</h6>
                        <div class="sa-section-sub">Set owner, source and field mapping per form with <i class="bi bi-gear"></i>.</div>
                        @include('social_ads.partials.forms_table', [
                            'forms' => $forms,
                            'showAccount' => true,
                            'accountLabel' => 'Ad Account',
                            'emptyText' => 'Forms appear after connecting LinkedIn and syncing an ad account.',
                        ])
                    </div>

                    @include('social_ads.partials.defaults_section')
                </div>

                <div class="col-xl-4">
                    <div class="sa-section">
                        <h6 class="sa-section-title"><i class="bi bi-link-45deg text-primary"></i> Value for your LinkedIn App</h6>
                        @include('social_ads.partials.copy_field', ['id' => 'saLiRedirect', 'label' => 'Authorized Redirect URL', 'value' => $redirectUri, 'hint' => 'App → Auth → OAuth 2.0 settings'])
                    </div>
                    <div class="sa-section sa-guide">
                        <h6 class="sa-section-title"><i class="bi bi-journal-text text-primary"></i> One-time Setup Guide</h6>
                        <ol class="mt-2">
                            <li>Create an app at <a href="https://www.linkedin.com/developers/apps" target="_blank" rel="noopener">LinkedIn Developers</a> linked to your company Page.</li>
                            <li>Under <strong>Products</strong>, request <strong>Lead Sync API</strong> (and Advertising API). LinkedIn reviews and approves access; this can take a few days.</li>
                            <li>Under <strong>Auth</strong>, add the <em>Authorized Redirect URL</em> shown above.</li>
                            <li>Copy the Client ID and Client Secret into Step 1 and save.</li>
                            <li>Click <strong>Connect with LinkedIn</strong> using a member with access to your ad account.</li>
                            <li>Turn on <strong>Import Leads</strong> for your ad accounts. Leads are fetched every 15 minutes, or click <em>Leads</em> to fetch now.</li>
                        </ol>
                        <div class="sa-hint mt-2">Scopes used: <code>{{ $scopes }}</code>.</div>
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
