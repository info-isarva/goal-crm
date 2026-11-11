@extends('layouts.app')

@section('content')
<style>
    .activity-section {
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 2px 16px 0 rgba(31, 38, 135, 0.07);
        margin-bottom: 2rem;
        padding: 2rem 1.5rem 1.5rem 1.5rem;
        border: 1px solid #f3f4f6;
    }
    .activity-section-header {
        display: flex;
        align-items: center;
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1.2rem;
    }
    .activity-section-header .icon {
        font-size: 2rem;
        margin-right: 0.7rem;
        background: linear-gradient(135deg, #6366f1 0%, #06d6a0 100%);
        color: #fff;
        border-radius: 12px;
        padding: 0.4rem 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .activity-info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(90deg, #f1f5ff 0%, #f7fafc 100%);
        border-radius: 10px;
        padding: 0.7rem 1.2rem;
        margin-bottom: 0.7rem;
        font-size: 1.08rem;
    }
    .activity-info-row.pastel-green { background: linear-gradient(90deg, #e6f9f0 0%, #f7fafc 100%); }
    .activity-info-row.pastel-pink { background: linear-gradient(90deg, #fbeaff 0%, #f7fafc 100%); }
    .activity-info-row.pastel-yellow { background: linear-gradient(90deg, #fffbe6 0%, #f7fafc 100%); }
    .activity-info-label {
        font-weight: 500;
        color: #6b7280;
    }
    .activity-info-value {
        font-weight: 600;
        color: #222;
    }
    .activity-user-card {
        display: flex;
        align-items: center;
        background: linear-gradient(90deg, #f1f5ff 0%, #f7fafc 100%);
        border-radius: 12px;
        padding: 1rem 1.2rem;
        margin-bottom: 0.7rem;
    }
    .activity-user-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1 0%, #06d6a0 100%);
        color: #fff;
        font-size: 1.7rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1.2rem;
    }
    .activity-user-details {
        flex: 1;
    }
    .activity-model-changes {
        background: #f7fafc;
        border-radius: 12px;
        padding: 1.2rem;
        margin-top: 1rem;
        font-size: 1rem;
        color: #222;
        border: 1px solid #e5e7eb;
    }
    .activity-model-changes pre {
        background: #e6f9f0;
        border-radius: 8px;
        padding: 1rem;
        font-size: 0.98rem;
        color: #222;
        border: 1px solid #d1fae5;
    }
    @media (max-width: 900px) {
        .activity-section { padding: 1.2rem 0.7rem 1rem 0.7rem; }
    }
</style>
<div class="container-fluid p-4" >
    <div class="row g-4 justify-content-between">
            <div class="col-12 col-lg-6 d-flex">
                <div class="activity-section flex-fill d-flex flex-column justify-content-between" style="min-height:220px;">
                    <div>
                        <div class="activity-section-header mb-3"><span class="icon"><i class="bi bi-info-circle"></i></span> Basic Information</div>
                        <div class="activity-info-row pastel-pink">
                            <span class="activity-info-label">Event Type</span>
                            <span class="activity-info-value">{{ ucfirst($activityLog->action) }}</span>
                        </div>
                        <div class="activity-info-row pastel-green">
                            <span class="activity-info-label">Log Category</span>
                            <span class="activity-info-value">{{ $activityLog->module }}</span>
                        </div>
                        <div class="activity-info-row pastel-pink">
                            <span class="activity-info-label">Subject Type</span>
                            <span class="activity-info-value">{{ $activityLog->subject_type ?? '-' }}</span>
                        </div>
                        <div class="activity-info-row pastel-yellow">
                            <span class="activity-info-label">Subject ID</span>
                            <span class="activity-info-value">#{{ $activityLog->subject_id ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6 d-flex">
                <div class="activity-section flex-fill d-flex flex-column justify-content-between" style="min-height:220px;">
                    <div>
                        <div class="activity-section-header mb-3"><span class="icon"><i class="bi bi-person-circle"></i></span> User Information</div>
                        <div class="activity-user-card mb-2" style="background:linear-gradient(90deg,#f1f5ff 0%,#f7fafc 100%);border-radius:12px;">
                            <div class="activity-user-avatar" style="background:linear-gradient(135deg,#06d6a0 0%,#6366f1 100%);">
                                {{ $activityLog->user ? strtoupper(substr($activityLog->user->name,0,2)) : '?' }}
                            </div>
                            <div class="activity-user-details">
                                <div class="fw-bold" style="font-size:1.1rem;">{{ $activityLog->user ? $activityLog->user->name : 'Unknown' }}</div>
                                <div class="text-muted small">{{ $activityLog->user ? $activityLog->user->email : '' }}</div>
                            </div>
                        </div>
                        <div class="activity-info-row pastel-green">
                            <span class="activity-info-label">User ID</span>
                            <span class="activity-info-value">#{{ $activityLog->user ? $activityLog->user->id : '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        <div class="col-12">
            <div class="activity-section">
                <div class="activity-section-header"><span class="icon"><i class="bi bi-database"></i></span> Model Changes</div>
                <div class="mb-3 d-flex flex-wrap gap-3 align-items-center">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-semibold">
                        <i class="bi bi-calendar-plus me-1"></i> Created: {{ $activityLog->created_at ? $activityLog->created_at->format('M d, Y H:i:s') : '-' }}
                    </span>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle fw-semibold">
                        <i class="bi bi-calendar-check me-1"></i> Updated: {{ $activityLog->updated_at ? $activityLog->updated_at->format('M d, Y H:i:s') : '-' }}
                    </span>
                </div>
                @if($activityLog->details)
                    <div class="fw-semibold mb-2 text-success"><i class="bi bi-check-circle"></i> New Values</div>
                    <div class="activity-model-changes">
                        @php
                            $json = null;
                            try {
                                $json = json_decode($activityLog->details, true, 512, JSON_THROW_ON_ERROR);
                            } catch (\Throwable $e) {
                                $json = null;
                            }
                        @endphp
                        @if($json)
                            <pre>{{ json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            <pre>{{ $activityLog->details }}</pre>
                        @endif
                    </div>
                @else
                    <div class="text-muted">No model changes recorded.</div>
                @endif
            </div>
        </div>
        <div class="col-12 text-center mt-3">
            <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-primary px-5 py-2 rounded-pill shadow-sm fw-bold"><i class="bi bi-arrow-left"></i> Back to Logs</a>
        </div>
    </div>
</div>

@endsection
