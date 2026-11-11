@extends('layouts.guest')

@section('content')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center" style="background: #0066ff;">
    <div class="card shadow-sm rounded-4 p-4" style="max-width: 400px; width: 100%;">
        <h4 class="text-dark mb-4 text-center fw-semibold">Create an Account</h4>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-3">
                <input id="name" type="text" placeholder="Full Name"
                    class="form-control @error('name') is-invalid @enderror"
                    name="name" value="{{ old('name') }}" required autofocus>
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <input id="email" type="email" placeholder="Email Address"
                    class="form-control @error('email') is-invalid @enderror"
                    name="email" value="{{ old('email') }}" required>
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <input id="password" type="password" placeholder="Password"
                    class="form-control @error('password') is-invalid @enderror"
                    name="password" required>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <input id="password-confirm" type="password" placeholder="Confirm Password"
                    class="form-control" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                Register Now
            </button>
        </form>

        <div class="text-center mt-3 small">
            Already have an account?
            <a href="{{ route('login') }}">Log in</a>
        </div>

        <!-- <hr class="my-3">

        <p class="text-center small mb-2">Or sign up with</p> -->

        <!-- <div class="d-grid gap-2">
            <a href="{{ route('google.login') }}" class="btn btn-danger">
                <i class="fab fa-google me-2"></i> SIGN UP WITH GOOGLE
            </a>
            <a href="#" class="btn btn-primary" style="background-color: #3b5998;">
                <i class="fab fa-facebook-f me-2"></i> SIGN UP WITH FACEBOOK
            </a>
            <a href="#" class="btn btn-info text-white">
                <i class="fab fa-twitter me-2"></i> SIGN UP WITH TWITTER
            </a>
        </div> -->
    </div>
</div>
@endsection
