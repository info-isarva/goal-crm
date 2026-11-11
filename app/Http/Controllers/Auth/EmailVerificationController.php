<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Carbon\Carbon;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);
        if (! hash_equals(sha1($user->email), (string) $hash)) {
            \Log::warning('Invalid verification link', ['id' => $id]);
            abort(403, 'Invalid verification link.');
        }
        if ($user->email_verified_at) {
            \Log::info('Email already verified', ['id' => $id]);
            return redirect()->route('verification.success');
        }
        $user->email_verified_at = Carbon::now();
        $user->save();
        event(new \Illuminate\Auth\Events\Verified($user));
        // Optionally, you can log the user in here if you want
        // Auth::login($user);
        return redirect()->route('verification.success');
    }

    public function success()
    {
        return view('auth.verification-success');
    }
}
