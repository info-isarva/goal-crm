<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class RequireTwoFA
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only enforce 2FA if user has enabled it
        if (Auth::check() && Auth::user()->{"2fa_enabled"}) {
            if (!Session::get('2fa:passed')) {
                // Allow access to 2FA routes, logout, and profile (so a logged-in user
                // can enable 2FA from their profile without being redirected and
                // losing menus/contents).
                if ($request->is('2fa/email') || $request->is('logout') || $request->is('profile') || $request->is('profile/*')) {
                    return $next($request);
                }

                return redirect()->route('auth.2fa.verify');
            }
        }
        return $next($request);
    }
}
