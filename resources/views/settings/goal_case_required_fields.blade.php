@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold">Loan Case Required Fields</h4>
                    <small class="text-muted">
                        Super Admin only — choose which Customer Details fields are mandatory on Loan Case workspace.
                    </small>
                </div>
                <a href="{{ route('settings.index') }}" class="btn btn-light btn-sm">&laquo; Back to Settings</a>
            </div>
        </div>

        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('goal_case_required_fields.settings.update') }}">
                @csrf
                <p class="small text-muted mb-3">
                    Toggle <strong>Required</strong> on to show a red asterisk and enforce the field when saving Case → Customer.
                    Unchecked fields stay optional.
                </p>

                <div class="row g-3">
                    @foreach($groups as $groupName => $fields)
                        <div class="col-12 col-xl-6">
                            <div class="border rounded-3 p-3 h-100">
                                <h6 class="fw-semibold mb-3">
                                    <i class="bi bi-ui-checks me-1 text-primary"></i>{{ $groupName }}
                                </h6>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($fields as $key => $meta)
                                        <div class="form-check form-switch d-flex align-items-center justify-content-between gap-3 m-0 py-1 border-bottom border-light">
                                            <label class="form-check-label flex-grow-1" for="req_{{ md5($key) }}">
                                                {{ $meta['label'] }}
                                                <div class="small text-muted">{{ $key }}</div>
                                            </label>
                                            <input class="form-check-input m-0"
                                                   type="checkbox"
                                                   role="switch"
                                                   id="req_{{ md5($key) }}"
                                                   name="required[{{ $key }}]"
                                                   value="1"
                                                   @checked(!empty($map[$key]))>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Save required fields
                    </button>
                    <a href="{{ route('settings.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
