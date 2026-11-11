@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold">Weekly Target Alert Timings</h4>
                    <small class="text-muted">
                        Set morning, afternoon and evening reminder times for employees and managers
                        who have not reached their weekly assigned target (monthly ÷ 4).
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
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('weekly_target_alert.settings.update') }}">
                @csrf

                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="border rounded-3 p-3 h-100">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-toggle-on me-1 text-primary"></i> Alert status
                            </h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="weekly_target_alert_enabled"
                                       name="weekly_target_alert_enabled"
                                       value="1"
                                       @checked($enabled)>
                                <label class="form-check-label" for="weekly_target_alert_enabled">
                                    Enable weekly target alert popups
                                </label>
                            </div>
                            <p class="small text-muted mb-0">
                                When enabled, employees and managers behind weekly target see a popup
                                at each scheduled time, plus a header announcement they can reopen anytime.
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="border rounded-3 p-3 h-100">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-clock me-1 text-primary"></i> Scheduled times
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="weekly_target_alert_morning" class="form-label">Morning</label>
                                    <input type="time"
                                           class="form-control @error('weekly_target_alert_morning') is-invalid @enderror"
                                           id="weekly_target_alert_morning"
                                           name="weekly_target_alert_morning"
                                           value="{{ $morning }}"
                                           required>
                                    @error('weekly_target_alert_morning')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="weekly_target_alert_afternoon" class="form-label">Afternoon</label>
                                    <input type="time"
                                           class="form-control @error('weekly_target_alert_afternoon') is-invalid @enderror"
                                           id="weekly_target_alert_afternoon"
                                           name="weekly_target_alert_afternoon"
                                           value="{{ $afternoon }}"
                                           required>
                                    @error('weekly_target_alert_afternoon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="weekly_target_alert_evening" class="form-label">Evening</label>
                                    <input type="time"
                                           class="form-control @error('weekly_target_alert_evening') is-invalid @enderror"
                                           id="weekly_target_alert_evening"
                                           name="weekly_target_alert_evening"
                                           value="{{ $evening }}"
                                           required>
                                    @error('weekly_target_alert_evening')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <p class="small text-muted mt-3 mb-0">
                                Times must be in order (Morning &lt; Afternoon &lt; Evening).
                                Example: 09:00, 14:00, 18:00. Each slot shows once per day per user.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-custom">Save Timings</button>
                    <a href="{{ route('settings.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
