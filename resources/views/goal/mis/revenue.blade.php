@extends('layouts.app')
@section('content')
<div class="container-fluid p-4">
    <h4 class="mb-3">Revenue Ledger</h4>
    <div class="card"><div class="card-body p-0 table-responsive">
        <table class="table mb-0">
            <thead><tr><th>When</th><th>Case</th><th>Amount</th><th>Note</th></tr></thead>
            <tbody>
            @forelse($entries as $e)
                <tr>
                    <td>{{ $e->created_at }}</td>
                    <td><a href="{{ route('goal.cases.show', $e->goal_loan_case_id) }}">{{ $e->loanCase?->case_no }}</a></td>
                    <td>{{ number_format($e->amount, 2) }}</td>
                    <td>{{ $e->note }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-3 text-muted">No revenue entries. PD-01 creates REV-01 on disbursement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $entries->links() }}</div>
</div>
@endsection
