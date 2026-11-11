@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'quotationWhatsappLogsSkeleton',
        'columns' => 6,
        'rows' => 10,
        'filterFields' => 2,
    ])

    <div class="card mt-0">
        <div class="card-header">
            <h4 class="mb-1"><i class="bi bi-whatsapp text-success me-2"></i>WhatsApp Send Logs</h4>
            <div class="small text-muted">Quotation WhatsApp delivery history</div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('quotations.whatsapp_send_logs') }}" class="mb-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Quote no or phone number"
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="sent" @selected(request('status') === 'sent')>Sent</option>
                            <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-custom w-100">Filter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Quote No.</th>
                            <th>WhatsApp Number</th>
                            <th>Message Preview</th>
                            <th>PDF</th>
                            <th>Sent By</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap small">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                <td>
                                    @if($log->quotation)
                                        <a href="{{ route('quotations.show', $log->quotation_id) }}">{{ $log->quotation->quote_no }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small">{{ $log->recipient ?: '—' }}</td>
                                <td class="small">
                                    @if($log->message_preview)
                                        {{ Str::limit($log->message_preview, 140) }}
                                    @else
                                        —
                                    @endif
                                    @if($log->error_message)
                                        <div class="text-danger">{{ Str::limit($log->error_message, 120) }}</div>
                                    @endif
                                </td>
                                <td class="small">{{ $log->pdf_filename ?: '—' }}</td>
                                <td class="small">{{ optional($log->sender)->name ?? '—' }}</td>
                                <td>
                                    @if($log->isSuccess())
                                        <span class="badge bg-success">Sent</span>
                                    @else
                                        <span class="badge bg-danger">Failed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No WhatsApp send logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $logs->links() }}</div>
        </div>
    </div>
</div>
@endsection
