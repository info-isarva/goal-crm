@extends('layouts.guest')
@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark fw-bold">Two-Factor Authentication</div>
                <div class="card-body">
                    <div class="mb-3 text-muted">
                        A 6-digit code has been sent to your email. Please enter it below to continue.
                    </div>
                    <form method="POST" action="{{ route('auth.2fa.verify.post') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">Authentication Code</label>
                            <input id="code" type="text" class="form-control @error('code') is-invalid @enderror" name="code" required autofocus maxlength="6" pattern="\d{6}">
                            @error('code')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning px-4">Verify</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
