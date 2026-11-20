<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PasswordController extends Controller
{

    public function edit(Request $request): View
    {
        return view('profile.change-password',  [
            'user' => $request->user()
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        // Plain value — User model casts password as hashed; other sessions cleared in User::booted.
        $user->update([
            'password' => $validated['password'],
        ]);

        // End this session too — must log in with the new password.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Password changed successfully. Please log in again.');
    }
}
