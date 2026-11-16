@extends('layouts.app')
@section('content')
<div class="container-fluid p-4">
    <h4 class="mb-3">Accounts Queue</h4>
    <div class="card"><div class="card-body p-0 table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Case</th><th>Status</th><th>Report #</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td><a href="{{ route('goal.cases.show', $item->goal_loan_case_id) }}">{{ $item->loanCase?->case_no }}</a><div class="small text-muted">{{ $item->loanCase?->customer_name }}</div></td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->report_number ?: '—' }}</td>
                    <td>
                        <form class="d-flex gap-1" method="POST" action="{{ route('goal.accounts.report', $item) }}">@csrf
                            <input name="report_number" class="form-control" placeholder="Report number" value="{{ $item->report_number }}" required>
                            <button class="btn btn-sm btn-custom">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-3 text-muted">No accounts items.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection
