@extends('layouts.app')
@section('content')
<div class="container-fluid p-4">
    <h4 class="mb-3">Branch MIS</h4>
    <div class="card"><div class="card-body p-0 table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Ref / Case</th><th>LAN</th><th>Customer</th><th>Contact</th><th>Product</th>
                    <th>Status</th><th>Bank</th><th>PayIn%</th><th>Payout%</th><th>Net Rev</th><th>Source</th><th>Owner</th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                @php $m = $case->branch_mis ?? []; @endphp
                <tr>
                    <td><a href="{{ route('goal.cases.show', $case) }}">{{ $case->case_no }}</a></td>
                    <td>{{ $m['lan_number'] ?? '—' }}</td>
                    <td>{{ $case->customer_name }}</td>
                    <td>{{ $case->mobile }}</td>
                    <td>{{ $case->product }}</td>
                    <td>{{ $case->status }}</td>
                    <td>{{ $m['bank_name'] ?? $case->lender_name }}</td>
                    <td>{{ $m['payin_pct'] ?? '—' }}</td>
                    <td>{{ $m['payout_pct'] ?? '—' }}</td>
                    <td>{{ $m['net_revenue'] ?? '—' }}</td>
                    <td>{{ $m['source'] ?? '—' }}</td>
                    <td>{{ $case->owner?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="p-3 text-muted">No case MIS rows.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $cases->links() }}</div>
</div>
@endsection
