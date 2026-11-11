<section>

    <form method="post" action="{{ route('password.update') }}" class="mt-4">
        @csrf
        @method('put')

        <div class="mb-3">
            <label for="update_password_current_password" class="form-label">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control {{ $errors->updatePassword->has('current_password') ? 'is-invalid' : '' }}" autocomplete="current-password">
            @if($errors->updatePassword->has('current_password'))
                <div class="text-danger small mt-1">{{ $errors->updatePassword->first('current_password') }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label for="update_password_password" class="form-label">New Password</label>
            <input id="update_password_password" name="password" type="password" class="form-control {{ $errors->updatePassword->has('password') ? 'is-invalid' : '' }}" autocomplete="new-password">
            @if($errors->updatePassword->has('password'))
                <div class="text-danger small mt-1">{{ $errors->updatePassword->first('password') }}</div>
            @endif
        </div>

        <div class="mb-3">
            <label for="update_password_password_confirmation" class="form-label">Confirm Password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control {{ $errors->updatePassword->has('password_confirmation') ? 'is-invalid' : '' }}" autocomplete="new-password">
            @if($errors->updatePassword->has('password_confirmation'))
                <div class="text-danger small mt-1">{{ $errors->updatePassword->first('password_confirmation') }}</div>
            @endif
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-custom" style="padding: 6px 20px !important;">Save</button>
            @if (session('status') === 'password-updated')
                <span class="text-success ms-3">Saved.</span>
            @endif
        </div>
    </form>
</section>
