{{-- Expects $forms (collection of SocialAdForm), optional $showAccount, $emptyText --}}
@if($forms->isEmpty())
    <div class="text-center text-muted py-4 small">{{ $emptyText ?? 'No forms yet.' }}</div>
@else
    <div class="table-responsive">
        <table class="table table-hover sa-table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Form</th>
                    @if(!empty($showAccount))<th>{{ $accountLabel ?? 'Page / Account' }}</th>@endif
                    <th>Lead Owner</th>
                    <th>Source</th>
                    <th class="text-center">Leads</th>
                    <th class="text-nowrap">Last Lead</th>
                    <th>Import</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($forms as $f)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $f->name }}</div>
                            <small class="text-muted">
                                @if($f->external_id) ID {{ $f->external_id }} @endif
                                @if($f->status) &middot; {{ ucfirst($f->status) }} @endif
                                &middot; {{ count($f->questions ?? []) }} field(s)
                            </small>
                        </td>
                        @if(!empty($showAccount))<td class="small">{{ $f->account->name ?? '—' }}</td>@endif
                        <td class="small">{{ $f->owner->name ?? '—' }}</td>
                        <td class="small">{{ $f->leadSource->name ?? '—' }}</td>
                        <td class="text-center">
                            <a href="{{ route('social_ads.leads', ['form' => $f->id]) }}" class="fw-semibold text-decoration-none">{{ number_format($f->leads_count) }}</a>
                        </td>
                        <td class="small text-nowrap">{{ $f->last_lead_at ? $f->last_lead_at->format('d M Y, h:i A') : '—' }}</td>
                        <td>
                            @if(auth()->user()->hasCrmPermission('edit_crm_social_ads_guard') || auth()->user()->hasCrmPermission('manage_crm_social_ads_guard'))
                                <form method="POST" action="{{ route('social_ads.forms.toggle', $f) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="badge border-0 {{ $f->is_enabled ? 'sa-badge-on' : 'sa-badge-off' }}" title="Click to turn {{ $f->is_enabled ? 'off' : 'on' }}">
                                        <i class="bi {{ $f->is_enabled ? 'bi-toggle-on' : 'bi-toggle-off' }} me-1"></i>{{ $f->is_enabled ? 'On' : 'Off' }}
                                    </button>
                                </form>
                            @else
                                <span class="badge border-0 {{ $f->is_enabled ? 'sa-badge-on' : 'sa-badge-off' }}">
                                    <i class="bi {{ $f->is_enabled ? 'bi-toggle-on' : 'bi-toggle-off' }} me-1"></i>{{ $f->is_enabled ? 'On' : 'Off' }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if(auth()->user()->hasCrmPermission('edit_crm_social_ads_guard') || auth()->user()->hasCrmPermission('manage_crm_social_ads_guard'))
                                @if(in_array($f->platform, ['meta', 'linkedin'], true))
                                    <form method="POST" action="{{ route('social_ads.forms.sync', $f) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Fetch leads now"><i class="bi bi-arrow-repeat"></i></button>
                                    </form>
                                @endif
                                <a href="{{ route('social_ads.forms.edit', $f) }}" class="btn btn-sm btn-outline-secondary" title="Field mapping &amp; lead settings"><i class="bi bi-gear"></i></a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
