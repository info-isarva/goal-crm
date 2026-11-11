@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h4 class="mb-1 fw-bold">Submissions &mdash; {{ $form->name }}</h4>
                    <small class="text-muted">Every entry received from your website, with the lead it created.</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('web_forms.index') }}" class="btn btn-light btn-sm">&laquo; Back to Web Forms</a>
                    <a href="{{ route('web_forms.embed', $form) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-code-slash me-1"></i>Embed Code</a>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if($submissions->isEmpty())
                <div class="text-center py-5 text-muted">
                    <div class="mb-2" style="font-size: 2rem;"><i class="bi bi-inbox"></i></div>
                    No submissions yet. Once the form is on your website, entries will appear here.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 60px;">S.No</th>
                                <th class="text-nowrap">Received</th>
                                <th>Details</th>
                                <th>Page</th>
                                <th>Status</th>
                                <th class="pe-4">Lead</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $i => $sub)
                                <tr>
                                    <td class="ps-4">{{ $submissions->firstItem() + $i }}</td>
                                    <td class="text-nowrap">
                                        {{ $sub->created_at->format('d M Y') }}<br>
                                        <small class="text-muted">{{ $sub->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td class="small">
                                        @foreach(($sub->payload ?? []) as $key => $value)
                                            @if($value !== null && $value !== '')
                                                <div><span class="text-muted">{{ $fieldLabels[$key] ?? \Illuminate\Support\Str::headline($key) }}:</span> {{ \Illuminate\Support\Str::limit((string) $value, 160) }}</div>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td class="small" style="max-width: 220px;">
                                        @if($sub->page_url)
                                            <span class="d-inline-block text-truncate" style="max-width: 210px;" title="{{ $sub->page_url }}">{{ $sub->page_url }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                        @if($sub->ip_address)
                                            <div class="text-muted">IP: {{ $sub->ip_address }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sub->status === 'created')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Lead Created</span>
                                        @elseif($sub->status === 'failed')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{ $sub->error_message }}">Failed</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ ucfirst($sub->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="pe-4">
                                        @if($sub->lead)
                                            <a href="{{ route('leads.show', $sub->lead) }}" class="text-decoration-none">{{ $sub->lead->title }}</a>
                                        @elseif($sub->lead_id)
                                            <span class="text-muted">Lead #{{ $sub->lead_id }} (deleted)</span>
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
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label for="wfPerPage" class="small text-muted mb-0">Show</label>
                        <select name="per_page" id="wfPerPage" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            @foreach([25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span class="small text-muted">
                            Showing {{ $submissions->firstItem() }}–{{ $submissions->lastItem() }} of {{ $submissions->total() }}
                        </span>
                    </form>
                    {{ $submissions->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
