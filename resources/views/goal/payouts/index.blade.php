@extends('layouts.app')
@section('content')
<div class="container-fluid p-4">
    <h4 class="mb-3">Payout Processing</h4>
    <div class="card"><div class="card-body p-0 table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Case</th><th>Status</th><th>Expected</th><th>Received</th><th>Receipt / Exception</th></tr></thead>
            <tbody>
            @forelse($payouts as $payout)
                <tr>
                    <td><a href="{{ route('goal.cases.show', $payout->goal_loan_case_id) }}">{{ $payout->loanCase?->case_no }}</a></td>
                    <td><span class="badge bg-secondary">{{ $payout->status }}</span></td>
                    <td>{{ number_format($payout->expected_amount, 2) }}</td>
                    <td>{{ number_format($payout->received_amount, 2) }}</td>
                    <td>
                        <form class="d-flex gap-1 mb-1" method="POST" action="{{ route('goal.payouts.receipt', $payout) }}">@csrf
                            <input type="text" inputmode="decimal" data-decimals="2" name="amount" class="form-control js-decimal" placeholder="Amt" required>
                            <input type="date" name="received_on" class="form-control" required>
                            <input name="reference" class="form-control" placeholder="Ref">
                            <button class="btn btn-sm btn-custom">Receipt</button>
                        </form>
                        <form class="d-flex gap-1" method="POST" action="{{ route('goal.payouts.exception', $payout) }}">@csrf
                            <input name="exception_reason" class="form-control" placeholder="Exception reason" required>
                            <button class="btn btn-sm btn-outline-danger">Exception</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-3 text-muted">No payouts.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $payouts->links() }}</div>
</div>
@endsection
