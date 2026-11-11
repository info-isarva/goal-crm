@extends('layouts.guest')

@section('content')
<div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="card shadow p-4 text-center" style="max-width: 400px;">
        <h2 class="mb-3 text-success">Email Verified!</h2>
        <p class="mb-4">Your email has been successfully verified.<br> You can now log in to your account.</p>
        <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-4">Go to Login Page</a>
    </div>
</div>
@endsection
