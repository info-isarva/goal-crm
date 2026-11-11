<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

class Email2FAController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function showForm(Request $request)
    {
        // Only show if 2FA session exists
        if (!Session::has('2fa:code')) {
            return redirect()->route('login');
        }
        return view('auth.2fa-email');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);
        $code = Session::get('2fa:code');
        $expires = Session::get('2fa:expires');
        if (!$code || !$expires || now()->gt($expires)) {
            $this->clearPendingLogin();
            return redirect()->route('login')->withErrors(['email' => 'The verification code has expired. Please login again.']);
        }

        if (!hash_equals((string) $code, (string) $request->input('code'))) {
            $attempts = (int) Session::get('2fa:attempts', 0) + 1;
            Session::put('2fa:attempts', $attempts);
            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->clearPendingLogin();
                return redirect()->route('login')->withErrors(['email' => 'Too many invalid verification codes. Please login again.']);
            }
            return back()->withErrors(['code' => 'Invalid code. ' . (self::MAX_ATTEMPTS - $attempts) . ' attempt(s) left.']);
        }

        // 2FA success: log the user in and clear session
        $userId = Session::get('2fa:user:id');
        $user = \App\Models\User::find($userId);
        $this->clearPendingLogin();

        if (!$user || (int) $user->status !== 1) {
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        Session::put('2fa:passed', true);
        return redirect()->intended(route('dashboard'));
    }

    private function clearPendingLogin(): void
    {
        Session::forget(['2fa:code', '2fa:expires', '2fa:user:id', '2fa:attempts']);
    }
}
