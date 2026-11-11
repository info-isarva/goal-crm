
<div class="bg-white rounded-4 shadow-sm p-4 mb-4">
    <h5 class="fw-semibold mb-3"><i class="bi bi-shield-lock text-warning me-2"></i> Two-Factor Authentication (Email)</h5>
    <form method="POST" action="{{ route('profile.2fa.update') }}">
        @csrf
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="2fa_enabled" name="2fa_enabled" value="1" {{ auth()->user()->{"2fa_enabled"} ? 'checked' : '' }}>
            <label class="form-check-label" for="2fa_enabled">
                Enable email-based 2FA
            </label>
        </div>
        <button type="submit" class="btn btn-warning">Update 2FA Setting</button>
    </form>
    @if(session('status_2fa'))
        <div class="alert alert-success mt-3">{{ session('status_2fa') }}</div>
    @endif
</div>
