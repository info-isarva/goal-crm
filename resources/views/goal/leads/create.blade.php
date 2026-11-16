@extends('layouts.app')
@section('content')
<div class="container-fluid p-4">
    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ !empty($channel) ? 'Channel Lead' : 'Create Loan Lead' }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('goal.leads.store') }}">@csrf
                @if(!empty($channel))<input type="hidden" name="channel" value="1"><input type="hidden" name="source" value="channel">@endif
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Customer name *</label><input name="customer_name" class="form-control" value="{{ old('customer_name') }}" required>@error('customer_name')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                    <div class="col-md-3"><label class="form-label">Mobile</label><input name="mobile" class="form-control" value="{{ old('mobile') }}"></div>
                    <div class="col-md-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email') }}"></div>
                    <div class="col-md-2"><label class="form-label">Lead type *</label>
                        <select name="lead_type" class="form-select"><option value="immediate">Immediate</option><option value="future">Future</option></select>
                    </div>
                    @if(!empty($channel))
                    <div class="col-md-4"><label class="form-label">Channel partner</label><input name="channel_partner" class="form-control" value="{{ old('channel_partner') }}"></div>
                    @endif
                    <div class="col-md-3"><label class="form-label">Product</label><input name="product" class="form-control" value="{{ old('product') }}"></div>
                    <div class="col-md-3"><label class="form-label">Loan amount</label><input name="loan_amount" type="text" inputmode="decimal" data-decimals="2" class="form-control js-decimal" value="{{ old('loan_amount') }}"></div>
                    <div class="col-md-3"><label class="form-label">Branch</label>
                        <select name="goal_org_unit_id" class="form-select">
                            <option value="">—</option>
                            @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('goal_org_unit_id', auth()->user()->goal_org_unit_id)==$b->id)>{{ $b->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Follow-up (Future)</label><input type="datetime-local" name="follow_up_at" class="form-control" value="{{ old('follow_up_at') }}"></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-custom">Save lead</button>
                    <a href="{{ route('goal.leads.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
