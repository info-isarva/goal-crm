<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class EnsureComplianceConsent
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if ($user && in_array($user->crm_role_type, [0, 1])) {
            $now = Carbon::now();
            $lastAgreed = $user->compliance_consent_agreed_at;
            $showForm = !$user->compliance_consent_agreed || !$lastAgreed || $now->format('Y-m') !== optional($lastAgreed)->format('Y-m');
            // Only allow access to compliance consent page and logout if not completed
            if ($showForm) {
                if (!$request->is('compliance-consent') && !$request->is('logout')) {
                    return redirect()->route('compliance.consent.form');
                }
            }
        }
        return $next($request);
    }
}
