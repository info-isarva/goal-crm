@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-danger">
                <div class="card-body text-center py-5">
                    <div class="mb-3 text-danger">
                        <i class="bi bi-shield-lock" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="mb-2">Permission Denied</h4>
                    <p class="text-muted mb-4">
                        {{ $exception->getMessage() ?: 'You do not have permission to access this page or action.' }}
                    </p>
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="btn btn-light border me-2">
                        &laquo; Go Back
                    </a>
                    <a href="{{ route('settings.index') }}" class="btn btn-custom">Open Settings</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
