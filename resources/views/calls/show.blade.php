@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Call Log Details</h5>
                    <a href="{{ route('calllogs.index') }}" class="btn btn-secondary btn-sm">Back to List</a>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th>Name</th><td>{{ $call->name }}</td></tr>
                        <tr><th>Company</th><td>{{ $call->company_name }}</td></tr>
                        <tr><th>Mobile</th><td>{{ $call->mobile_number }}</td></tr>
                        <tr><th>Email</th><td>{{ $call->email }}</td></tr>
                        <tr><th>Requirement</th><td>{{ $call->requirement }}</td></tr>
                        <tr><th>Estimated Budget</th><td>{{ $call->estimated_budget }}</td></tr>
                        <tr><th>Call Status</th><td>{{ $call->call_status }}</td></tr>
                        <tr><th>Lead Status</th><td>{{ $call->lead_status }}</td></tr>
                        <tr><th>Next Follow Up</th><td>{{ $call->next_follow_up_date }}</td></tr>
                        <tr><th>Next Action</th><td>{{ $call->next_action }}</td></tr>
                        <tr><th>Remarks</th><td>{{ $call->remarks }}</td></tr>
                        <tr><th>Source</th><td>{{ $call->source }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
