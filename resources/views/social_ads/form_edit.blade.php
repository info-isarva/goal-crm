@extends('layouts.app')

@section('content')
@include('social_ads.partials.styles')
@php
    $questions = $form->questions ?? [];
    $oldMapping = old('mapping', $form->field_mapping ?? []);
@endphp
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        @include('social_ads.partials.header', [
            'title' => $form->name,
            'subtitle' => $platform['label'] . ($form->account ? ' · ' . $form->account->name : '') . ($form->external_id ? ' · Form ID ' . $form->external_id : ''),
            'icon' => $platform['icon'],
            'color' => $platform['color'],
            'backUrl' => route('social_ads.' . $form->platform),
            'backLabel' => 'Back to ' . ($platform['short'] ?? 'platform'),
            'showLeadsLink' => true,
            'leadsLinkParams' => ['form' => $form->id],
        ])

        <div class="card-body p-4">
            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger py-2"><ul class="mb-0 small">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            @if($form->platform === 'generic')
                <div class="sa-section">
                    <h6 class="sa-section-title"><i class="bi bi-plug text-primary"></i> Webhook URL</h6>
                    <div class="row align-items-end">
                        <div class="col-lg-9">
                            @include('social_ads.partials.copy_field', ['id' => 'saFormUrl', 'label' => 'POST leads to this URL', 'value' => $form->webhookUrl()])
                        </div>
                        <div class="col-lg-3 mb-3">
                            <form method="POST" action="{{ route('social_ads.forms.regenerate_token', $form) }}" onsubmit="return confirm('Generate a new URL? The current URL stops working immediately.');">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-arrow-repeat me-1"></i>New URL</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('social_ads.forms.update', $form) }}">
                @csrf
                @method('PUT')
                <div class="row g-4">
                    <div class="col-xl-5">
                        <div class="sa-section">
                            <h6 class="sa-section-title"><i class="bi bi-sliders text-primary"></i> Form &amp; Lead Settings</h6>
                            <div class="sa-section-sub">Applied to every new lead from this form.</div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Name in CRM <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" maxlength="255" required value="{{ old('name', $form->name) }}">
                                <div class="sa-hint mt-1">Used in lead titles, e.g. "{{ $platform['short'] ?? '' }} - {{ \Illuminate\Support\Str::limit($form->name, 40, '') }} - Company".</div>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="is_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_enabled" value="1" id="saFormEnabled" @checked(old('is_enabled', $form->is_enabled))>
                                <label class="form-check-label fw-semibold small" for="saFormEnabled">Import leads from this form</label>
                            </div>
                            @include('social_ads.partials.lead_fields', ['values' => $form])
                        </div>
                    </div>

                    <div class="col-xl-7">
                        <div class="sa-section">
                            <h6 class="sa-section-title"><i class="bi bi-arrow-left-right text-primary"></i> Field Mapping</h6>
                            <div class="sa-section-sub">Choose which CRM field each ad question fills. <strong>Auto</strong> uses the detected match; unmatched answers are added to the lead description.</div>

                            @if(empty($questions))
                                <div class="alert alert-light border small mb-3">
                                    No fields known yet. They appear after the first lead arrives
                                    @if(in_array($form->platform, ['meta', 'linkedin'], true)) or after syncing forms @endif.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm sa-table mb-3">
                                        <thead class="table-light">
                                            <tr><th>Ad Question</th><th>Latest Answer</th><th style="min-width: 200px;">CRM Field</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach($questions as $q)
                                                @php
                                                    $key = $q['key'];
                                                    $auto = \App\Support\SocialAdFieldMapper::guess($key, $q['label'] ?? null);
                                                    $current = $oldMapping[$key] ?? '';
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold small">{{ $q['label'] ?? $key }}</div>
                                                        @if(($q['label'] ?? $key) !== $key)<code class="small text-muted">{{ $key }}</code>@endif
                                                    </td>
                                                    <td class="small text-muted">{{ $samples[$key] ?? '—' }}</td>
                                                    <td>
                                                        <select name="mapping[{{ $key }}]" class="form-select form-select-sm">
                                                            <option value="">Auto: {{ $auto ? ($crmFields[$auto] ?? $auto) : 'Add to description' }}</option>
                                                            @foreach($crmFields as $fk => $fl)
                                                                <option value="{{ $fk }}" @selected($current === $fk)>{{ $fl }}</option>
                                                            @endforeach
                                                            <option value="{{ \App\Support\SocialAdFieldMapper::IGNORE }}" @selected($current === \App\Support\SocialAdFieldMapper::IGNORE)>Don't import</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <label class="form-label fw-semibold small mb-1">Add field names manually (optional)</label>
                            <textarea name="new_questions" class="form-control form-control-sm" rows="2" placeholder="One per line, e.g. budget, preferred_time">{{ old('new_questions') }}</textarea>
                            <div class="sa-hint mt-1">Useful to set up mapping before the first lead arrives.</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-custom"><i class="bi bi-check2 me-1"></i>Save Settings</button>
                        <a href="{{ route('social_ads.' . $form->platform) }}" class="btn btn-light">Cancel</a>
                    </div>
                </div>
            </form>

            <hr class="my-4">
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(in_array($form->platform, ['meta', 'linkedin'], true))
                    <form method="POST" action="{{ route('social_ads.forms.sync', $form) }}">
                        @csrf
                        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Fetch Leads Now</button>
                    </form>
                @endif
                @if(auth()->user()->hasCrmPermission('delete_crm_social_ads_guard') || auth()->user()->hasCrmPermission('manage_crm_social_ads_guard'))
                    <form method="POST" action="{{ route('social_ads.forms.destroy', $form) }}" onsubmit="return confirm('Remove this form from the CRM? Imported leads are kept.{{ in_array($form->platform, ['meta', 'linkedin'], true) ? ' It will come back on the next form sync.' : '' }}');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Remove Form</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('social_ads.partials.copy_js')
@endpush
