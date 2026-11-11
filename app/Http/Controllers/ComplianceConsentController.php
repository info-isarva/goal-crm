<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ComplianceConsentController extends Controller
{
    public function showForm()
    {
        $user = Auth::user();
        // Only for admin/superadmin
        if (!in_array($user->crm_role_type, [0, 1])) {
            return redirect()->route('dashboard');
        }
        // Show form if not agreed this month
        $now = Carbon::now();
        $lastAgreed = $user->compliance_consent_agreed_at;
        $showForm = !$user->compliance_consent_agreed || !$lastAgreed || $now->format('Y-m') !== Carbon::parse($lastAgreed)->format('Y-m');
        if (!$showForm) {
            return redirect()->route('dashboard');
        }
        return view('compliance_consent_form');
    }

    public function submitForm(Request $request)
    {
        $user = Auth::user();
        $agree = $request->input('consent') == '1';
        $user->compliance_consent_agreed = $agree;
        $user->compliance_consent_agreed_at = $agree ? now() : null;
        $user->save();
        if ($agree) {
            return redirect()->route('dashboard')->with('success', 'Thank you for agreeing to the compliance consent.');
        } else {
            return redirect()->route('compliance.consent.form')->with('error', 'You must agree to the compliance consent to continue.');
        }
    }
}
