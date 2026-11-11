<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
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
        $user = Auth::user();

        if ($user && ((string) $user->status === '0' || (int) $user->status === 0)) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Please contact support.']);
        }

        if ($user && (int) ($user->crm_role_type ?? -1) !== 0) {
            $company = \App\Models\Company::first();
            if ($company && !empty($company->expire_date)) {
                $expireDate = \Carbon\Carbon::parse($company->expire_date)->endOfDay();
                if ($expireDate->isPast()) {
                    Auth::logout();
                    $formattedDate = \Carbon\Carbon::parse($company->expire_date)->format('d M Y');
                    return redirect()->route('login')->withErrors([
                        'email' => 'Application subscription expired on ' . $formattedDate . '. Please contact your administrator to renew.'
                    ]);
                }
            }
        }

        return $next($request);
    }
}