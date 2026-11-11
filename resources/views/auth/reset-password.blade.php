<x-guest-layout>
    @php
        $company = \App\Models\Company::current();
        $companyName = !empty($company->name) ? $company->name : 'Isarva CRM';
    @endphp
    <link rel="stylesheet" href="/css/login-custom.css?v=2">
    <div class="login-bg-gradient">
        <div class="login-card-container">
            <div class="login-form-section">
                <div class="login-logo-wrap text-center mb-3">
                    <a href="{{ url('/') }}" class="login-logo-link" aria-label="{{ $companyName }} home">
                        <img src="{{ $company->logo_url }}" alt="{{ $companyName }}" class="login-logo" style="max-height: 60px; object-fit: contain;">
                        <span class="visually-hidden">{{ $companyName }}</span>
                    </a>
                </div>
                <h4 class="text-dark mb-4 text-center fw-semibold">Reset Password</h4>

                <p class="text-muted small mb-3">Choose a new password for your account.</p>

                <!-- Session Status -->
                <x-auth-session-status class="mb-3" :status="session('status')" />

                <form method="POST" action="{{ route('password.store') }}">
                    @csrf

                    <!-- Password Reset Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 1.5rem 0 0 1.5rem;">
                                <i class="fa fa-envelope text-secondary"></i>
                            </span>
                            <input type="email" id="email" name="email" class="form-control rounded-end-pill @error('email') is-invalid @enderror" placeholder="Enter Email" required autofocus style="border-radius: 0 1.5rem 1.5rem 0;" value="{{ old('email', $request->email) }}">
                        </div>
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 1.5rem 0 0 1.5rem;">
                                <i class="fa fa-lock text-secondary"></i>
                            </span>
                            <input type="password" id="password" name="password" class="form-control rounded-end-pill @error('password') is-invalid @enderror" placeholder="Enter new password" required autocomplete="new-password" style="border-radius: 0 1.5rem 1.5rem 0;">
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 1.5rem 0 0 1.5rem;">
                                <i class="fa fa-lock text-secondary"></i>
                            </span>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control rounded-end-pill @error('password_confirmation') is-invalid @enderror" placeholder="Confirm password" required autocomplete="new-password" style="border-radius: 0 1.5rem 1.5rem 0;">
                        </div>
                        @error('password_confirmation')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-cta w-100 fw-semibold">Reset Password</button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="small text-decoration-none">Back to sign in</a>
                </div>
            </div>
            <div class="login-image-section d-flex flex-column justify-content-center align-items-center position-relative">
                <div class="login-hero-text w-100 text-center">
                    <h1 class="fw-bold mb-2 login-hero-title">You're almost there</h1>
                    <div class="mb-4 login-hero-subtitle">Set a secure password to regain access to your account.</div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
