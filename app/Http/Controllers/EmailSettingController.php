<?php

namespace App\Http\Controllers;

use App\Models\EmailSetting;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmailSettingController extends Controller
{
    /**
     * Display the Email settings view (Super Admin only).
     */
    public function edit()
    {
        $user = auth()->user();
        if (!$user || $user->crm_role_type !== 0) {
            abort(403, 'Unauthorized access. Only Super Admin can access Email Settings.');
        }

        $canEdit = true;
        $smtpSetting = EmailSetting::getSettings('smtp');
        $sendmailSetting = EmailSetting::getSettings('sendmail');

        $activeProvider = ($sendmailSetting->exists && $sendmailSetting->is_active) ? 'sendmail' : 'smtp';
        $setting = ($activeProvider === 'sendmail') ? $sendmailSetting : $smtpSetting;

        return view('settings.email', compact('smtpSetting', 'sendmailSetting', 'activeProvider', 'setting', 'canEdit'));
    }

    /**
     * Update email integration settings (Super Admin only).
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->crm_role_type !== 0) {
            abort(403, 'Unauthorized action. Only Super Admin can modify Email Settings.');
        }

        $validated = $request->validate([
            'provider_type'     => 'required|in:smtp,sendmail',
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer|min:1|max:65535',
            'mail_encryption'   => 'nullable|in:tls,ssl,none',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name'    => 'required|string|max:255',
            'sendmail_path'     => 'nullable|string|max:255',
            'is_active'         => 'nullable|boolean',
        ]);

        $provider = $validated['provider_type'];
        $isActive = $request->has('is_active') ? 1 : 0;

        if ($provider === 'smtp') {
            $request->validate([
                'mail_host' => 'required|string|max:255',
                'mail_port' => 'required|integer|min:1|max:65535',
            ]);
        }

        $setting = EmailSetting::firstOrNew(['provider_type' => $provider]);
        $setting->mail_host         = $validated['mail_host'] ?? null;
        $setting->mail_port         = $validated['mail_port'] ?? null;
        $setting->mail_encryption   = ($validated['mail_encryption'] ?? null) === 'none'
            ? null
            : ($validated['mail_encryption'] ?? 'tls');
        $setting->mail_username     = $validated['mail_username'] ?? null;
        if ($request->filled('mail_password')) {
            $setting->mail_password = $validated['mail_password'];
        }
        $setting->mail_from_address = $validated['mail_from_address'];
        $setting->mail_from_name    = $validated['mail_from_name'];
        $setting->sendmail_path     = $validated['sendmail_path'] ?? config('mail.mailers.sendmail.path');
        $setting->is_active         = $isActive;
        $setting->save();

        $otherProvider = ($provider === 'smtp') ? 'sendmail' : 'smtp';
        EmailSetting::where('provider_type', $otherProvider)->update(['is_active' => 0]);

        return redirect()->route('email.settings.edit')->with('success', ucfirst($provider) . ' email settings saved successfully.');
    }

    /**
     * Send a test email (Super Admin only).
     */
    public function sendTest(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->crm_role_type !== 0) {
            return response()->json(['success' => false, 'error' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'test_email'        => 'required|email|max:255',
            'provider_type'     => 'nullable|in:smtp,sendmail',
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer|min:1|max:65535',
            'mail_encryption'   => 'nullable|in:tls,ssl,none',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name'    => 'nullable|string|max:255',
            'sendmail_path'     => 'nullable|string|max:255',
            'is_active'         => 'nullable|boolean',
        ]);

        $service = new EmailService(EmailSetting::getSettings($validated['provider_type'] ?? null));
        $result = $service->sendTestEmail($validated['test_email'], [
            'provider_type'     => $validated['provider_type'] ?? null,
            'mail_host'         => $validated['mail_host'] ?? null,
            'mail_port'         => $validated['mail_port'] ?? null,
            'mail_encryption'   => $validated['mail_encryption'] ?? null,
            'mail_username'     => $validated['mail_username'] ?? null,
            'mail_password'     => $validated['mail_password'] ?? null,
            'mail_from_address' => $validated['mail_from_address'] ?? null,
            'mail_from_name'    => $validated['mail_from_name'] ?? null,
            'sendmail_path'     => $validated['sendmail_path'] ?? null,
            'is_active'         => $request->has('is_active') ? 1 : 0,
        ]);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully! Please check the inbox.',
            ]);
        }

        return response()->json([
            'success'  => false,
            'disabled' => $result['disabled'] ?? false,
            'error'    => $result['error'] ?? 'Failed to send test email.',
        ], 422);
    }

    /**
     * Verify login password before revealing SMTP password (Super Admin only).
     */
    public function verifyPassword(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->crm_role_type !== 0) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'error'   => 'Incorrect password. Unable to view password.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password verified.',
        ]);
    }
}
