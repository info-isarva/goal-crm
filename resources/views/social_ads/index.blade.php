@extends('layouts.app')

@section('content')
@include('social_ads.partials.styles')
<style>
    .sa-hub-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; height: 100%; display: flex; flex-direction: column; background: #fff; transition: box-shadow .15s, border-color .15s; }
    .sa-hub-card:hover { box-shadow: 0 6px 20px rgba(15, 23, 42, .08); border-color: #cbd5e1; }
    .sa-hub-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 14px 0; }
    .sa-hub-stat { background: #f8fafc; border-radius: 8px; padding: 8px 10px; text-align: center; }
    .sa-hub-stat .num { font-weight: 700; font-size: 1.1rem; color: #0f172a; line-height: 1.2; }
    .sa-hub-stat .lbl { font-size: .7rem; color: #64748b; text-transform: uppercase; letter-spacing: .03em; }
</style>
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        @include('social_ads.partials.header', [
            'title' => 'Social Ads',
            'subtitle' => 'Leads from your ad campaigns arrive in the CRM automatically, with company, contact, source and owner filled in.',
            'backUrl' => route('settings.index'),
            'backLabel' => 'Back to Settings',
            'showLeadsLink' => true,
        ])

        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                @foreach($platforms as $p)
                    @php $i = $p['integration']; @endphp
                    <div class="col-md-6 col-xl-3">
                        <div class="sa-hub-card">
                            <div class="d-flex align-items-start gap-3">
                                <span class="sa-platform-icon" style="background: {{ $p['color'] }};"><i class="bi {{ $p['icon'] }}"></i></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold">{{ $p['label'] }}</div>
                                    @if($p['connected'])
                                        <span class="badge sa-badge-on mt-1"><i class="bi bi-check-circle me-1"></i>{{ $p['key'] === 'generic' ? 'In use' : ($p['key'] === 'google' ? 'Receiving leads' : 'Connected') }}</span>
                                    @else
                                        <span class="badge sa-badge-off mt-1">Not set up</span>
                                    @endif
                                    @if($i->last_error && in_array($p['key'], ['meta', 'linkedin', 'google'], true))
                                        <span class="badge sa-badge-err mt-1" title="{{ $i->last_error }}"><i class="bi bi-exclamation-triangle me-1"></i>Error</span>
                                    @endif
                                </div>
                            </div>
                            <div class="sa-hub-stats">
                                <div class="sa-hub-stat"><div class="num">{{ number_format($p['forms']) }}</div><div class="lbl">Forms</div></div>
                                <div class="sa-hub-stat"><div class="num">{{ number_format($p['leads_today']) }}</div><div class="lbl">Today</div></div>
                                <div class="sa-hub-stat"><div class="num">{{ number_format($p['leads_total']) }}</div><div class="lbl">Total</div></div>
                            </div>
                            @if($p['failed'] > 0)
                                <a href="{{ route('social_ads.leads', ['platform' => $p['key'], 'status' => 'failed']) }}" class="small text-danger text-decoration-none mb-2">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $p['failed'] }} failed lead(s), review
                                </a>
                            @endif
                            <div class="mt-auto">
                                <a href="{{ route('social_ads.' . $p['key']) }}" class="btn btn-sm w-100 {{ $p['connected'] ? 'btn-outline-primary' : 'btn-custom' }}">
                                    {{ $p['connected'] ? 'Manage' : 'Set up' }} <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold mb-0">Recent Ad Leads</h6>
                <a href="{{ route('social_ads.leads') }}" class="small text-decoration-none">View all &raquo;</a>
            </div>
            @if($recent->isEmpty())
                <div class="sa-section text-center text-muted small mb-0">
                    No ad leads yet. Set up a platform above; new leads will appear here as soon as someone submits your ad form.
                </div>
            @else
                <div class="table-responsive border rounded-3">
                    <table class="table table-hover sa-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Received</th>
                                <th>Platform</th>
                                <th>Form</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th class="pe-3">CRM Lead</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent as $r)
                                @php
                                    $ans = \App\Support\SocialAdFieldMapper::map($r->answers ?? [], $r->form?->questionLabels() ?? [], $r->form?->field_mapping ?? []);
                                    $ans = \App\Services\LeadIntakeService::normalizeName($ans);
                                @endphp
                                <tr>
                                    <td class="ps-3 small text-nowrap">{{ $r->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="small text-nowrap">
                                        <i class="bi {{ $platforms[$r->platform]['icon'] ?? 'bi-plug' }} me-1" style="color: {{ $platforms[$r->platform]['color'] ?? '#64748b' }}"></i>{{ $platforms[$r->platform]['short'] ?? $r->platform }}
                                    </td>
                                    <td class="small">{{ $r->form->name ?? '—' }}</td>
                                    <td class="small">
                                        {{ trim(($ans['first_name'] ?? '') . ' ' . ($ans['last_name'] ?? '')) ?: '—' }}
                                        @if(!empty($ans['email']))<div class="text-muted">{{ $ans['email'] }}</div>@endif
                                    </td>
                                    <td>@include('social_ads.partials.status_badge', ['status' => $r->status, 'title' => $r->error_message])</td>
                                    <td class="pe-3 small">
                                        @if($r->lead)
                                            <a href="{{ route('leads.show', $r->lead) }}" class="text-decoration-none">{{ $r->lead->title }}</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
