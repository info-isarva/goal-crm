@extends('layouts.app')

@section('content')
@include('social_ads.partials.styles')
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        @include('social_ads.partials.header', [
            'title' => 'Social Ads Leads Log',
            'subtitle' => 'Every lead received from your ad platforms and what happened to it.',
        ])

        <div class="card-body p-0">
            <form method="GET" class="d-flex flex-wrap align-items-end gap-2 px-4 py-3 border-bottom">
                <div>
                    <label class="form-label small fw-semibold mb-1">Platform</label>
                    <select name="platform" class="form-select form-select-sm" style="min-width: 150px;">
                        <option value="">All platforms</option>
                        @foreach($platforms as $key => $p)
                            <option value="{{ $key }}" @selected($platform === $key)>{{ $p['short'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-semibold mb-1">Form</label>
                    <select name="form" class="form-select form-select-sm" style="min-width: 200px; max-width: 280px;">
                        <option value="">All forms</option>
                        @foreach($forms as $f)
                            <option value="{{ $f->id }}" @selected($formId === $f->id)>{{ $platforms[$f->platform]['short'] ?? $f->platform }} · {{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" style="min-width: 140px;">
                        <option value="">All statuses</option>
                        @foreach(['created' => 'Lead Created', 'failed' => 'Failed', 'skipped' => 'Skipped', 'test' => 'Test Lead', 'pending' => 'Pending'] as $val => $lbl)
                            <option value="{{ $val }}" @selected($status === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-semibold mb-1">Search</label>
                    <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Name, email, campaign…" style="min-width: 200px;">
                </div>
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <button class="btn btn-custom btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('social_ads.leads') }}" class="btn btn-light btn-sm">Reset</a>
                @if($failedCount > 0 && (auth()->user()->hasCrmPermission('edit_crm_social_ads_guard') || auth()->user()->hasCrmPermission('manage_crm_social_ads_guard')))
                    <span class="ms-auto"></span>
                    <form method="POST" action="{{ route('social_ads.leads.retry_failed') }}" onsubmit="return confirm('Retry importing all failed and skipped leads?');">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Retry {{ $failedCount }} Failed/Skipped</button>
                    </form>
                @endif
            </form>

            @if($leads->isEmpty())
                <div class="text-center text-muted py-5">
                    <div class="mb-2" style="font-size: 2rem;"><i class="bi bi-inbox"></i></div>
                    No ad leads found.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover sa-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Received</th>
                                <th>Platform / Form</th>
                                <th>Answers</th>
                                <th>Campaign / Ad</th>
                                <th>Status</th>
                                <th class="pe-4">CRM Lead</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leads as $row)
                                @php $labels = $row->form?->questionLabels() ?? []; @endphp
                                <tr>
                                    <td class="ps-4 small text-nowrap">
                                        {{ $row->created_at->format('d M Y') }}<br>
                                        <span class="text-muted">{{ $row->created_at->format('h:i A') }}</span>
                                        @if($row->submitted_at && $row->submitted_at->diffInMinutes($row->created_at, true) > 5)
                                            <div class="text-muted" title="Submitted on the ad platform">Submitted {{ $row->submitted_at->format('d M, h:i A') }}</div>
                                        @endif
                                    </td>
                                    <td class="small">
                                        <i class="bi {{ $platforms[$row->platform]['icon'] ?? 'bi-plug' }} me-1" style="color: {{ $platforms[$row->platform]['color'] ?? '#64748b' }}"></i>{{ $platforms[$row->platform]['short'] ?? $row->platform }}
                                        <div class="text-muted">{{ $row->form->name ?? 'Removed form' }}</div>
                                    </td>
                                    <td class="small" style="max-width: 360px;">
                                        @foreach(array_slice($row->answers ?? [], 0, 6, true) as $k => $v)
                                            @if(trim((string) $v) !== '')
                                                <div class="text-truncate"><span class="text-muted">{{ $labels[$k] ?? \Illuminate\Support\Str::headline((string) $k) }}:</span> {{ $v }}</div>
                                            @endif
                                        @endforeach
                                        @if(count($row->answers ?? []) > 6)
                                            <details>
                                                <summary class="text-primary" style="cursor: pointer;">+{{ count($row->answers) - 6 }} more</summary>
                                                @foreach(array_slice($row->answers, 6, null, true) as $k => $v)
                                                    <div><span class="text-muted">{{ $labels[$k] ?? \Illuminate\Support\Str::headline((string) $k) }}:</span> {{ $v }}</div>
                                                @endforeach
                                            </details>
                                        @endif
                                    </td>
                                    <td class="small">
                                        {{ $row->campaign_name ?: '—' }}
                                        @if($row->ad_name)<div class="text-muted">{{ $row->ad_name }}</div>@endif
                                    </td>
                                    <td>
                                        @include('social_ads.partials.status_badge', ['status' => $row->status])
                                        @if($row->error_message && $row->status !== 'created')
                                            <div class="small text-muted mt-1" style="max-width: 220px;">{{ \Illuminate\Support\Str::limit($row->error_message, 140) }}</div>
                                        @endif
                                    </td>
                                    <td class="pe-4 small">
                                        @if($row->lead)
                                            <a href="{{ route('leads.show', $row->lead) }}" class="text-decoration-none">{{ $row->lead->title }}</a>
                                        @elseif($row->lead_id)
                                            <span class="text-muted">Lead #{{ $row->lead_id }} (deleted)</span>
                                        @elseif(in_array($row->status, ['failed', 'skipped', 'test'], true))
                                            @if(auth()->user()->hasCrmPermission('edit_crm_social_ads_guard') || auth()->user()->hasCrmPermission('manage_crm_social_ads_guard'))
                                                <form method="POST" action="{{ route('social_ads.leads.retry', $row) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-primary text-nowrap">
                                                        <i class="bi bi-arrow-clockwise me-1"></i>{{ $row->status === 'test' ? 'Import Anyway' : 'Retry' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">Show</span>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="var u = new URL(window.location.href); u.searchParams.set('per_page', this.value); u.searchParams.delete('page'); window.location.href = u.toString();">
                            @foreach([25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span class="small text-muted">Showing {{ $leads->firstItem() }}–{{ $leads->lastItem() }} of {{ $leads->total() }}</span>
                    </div>
                    {{ $leads->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
