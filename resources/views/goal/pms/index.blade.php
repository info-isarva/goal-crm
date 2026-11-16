@extends('layouts.app')
@section('content')
<div class="container-fluid p-4">
    <h4 class="mb-3">PMS Verification Queue</h4>
    <div class="card"><div class="card-body p-0 table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Case</th><th>Customer</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td><a href="{{ route('goal.cases.show', $item->goal_loan_case_id) }}">{{ $item->loanCase?->case_no }}</a></td>
                    <td>{{ $item->loanCase?->customer_name }}</td>
                    <td>{{ $item->status }}</td>
                    <td>
                        <form class="d-flex gap-1" method="POST" action="{{ route('goal.pms.verify', $item) }}">@csrf
                            <select name="status" class="form-select"><option>Verified</option><option>Follow-up Due</option><option>Closed</option></select>
                            <input name="notes" class="form-control" placeholder="Notes">
                            <button class="btn btn-sm btn-custom">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-3 text-muted">No PMS items.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection
