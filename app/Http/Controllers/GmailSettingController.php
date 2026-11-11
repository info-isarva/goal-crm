<?php

namespace App\Http\Controllers;

use App\Services\EmailComposeSettingsService;
use App\Services\GoogleGmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GmailSettingController extends Controller
{
    /**
     * Show Gmail account setup page for the logged-in user.
     */
    public function edit(GoogleGmailService $gmailService)
    {
        $user = auth()->user();
        if (!$user->canSendQuotationEmail()) {
            abort(403, 'Unauthorized access. You do not have permission to manage Gmail settings.');
        }

        $connected = $gmailService->userHasGmailAccess($user);
        $tokenValid = false;

        if ($connected) {
            try {
                $gmailService->authorizeClientForUser($user);
                $tokenValid = true;
            } catch (\Throwable $e) {
                $tokenValid = false;
            }
        }

        $connectUrl = route('google.gmail.connect', [
            'return' => route('gmail.settings.edit'),
        ]);

        return view('settings.gmail', [
            'user' => $user,
            'connected' => $connected,
            'tokenValid' => $tokenValid,
            'connectUrl' => $connectUrl,
            'gmailEmail' => $user->email,
            'gmailName' => $user->name,
        ]);
    }

    /**
     * Disconnect Gmail from the current user account.
     */
    public function disconnect(Request $request)
    {
        $user = auth()->user();
        if (!$user->canSendQuotationEmail()) {
            abort(403);
        }

        $user->update([
            'google_token' => null,
            'google_refresh_token' => null,
        ]);

        session()->forget(['google_token', 'google_refresh_token', 'google_user_id', 'google_gmail_scopes']);

        return redirect()
            ->route('gmail.settings.edit')
            ->with('success', 'Gmail account disconnected successfully.');
    }

    /**
     * Send a short test email via the connected Gmail account.
     */
    public function sendTest(Request $request, GoogleGmailService $gmailService)
    {
        $user = auth()->user();
        if (!$user->canSendQuotationEmail()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        if (!$gmailService->userHasGmailAccess($user)) {
            return response()->json([
                'success' => false,
                'needs_connect' => true,
                'connect_url' => route('google.gmail.connect', ['return' => route('gmail.settings.edit')]),
                'error' => 'Connect your Gmail account first.',
            ], 422);
        }

        $validated = $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        $htmlBody = '<p>This is a test email from <strong>' . e(config('app.name')) . '</strong>.</p>'
            . '<p>Your Gmail account is connected and ready to send quotation emails.</p>'
            . '<p style="color:#64748b;font-size:12px;">Sent at ' . now()->format('d M Y, h:i A') . '</p>';
        $htmlBody = app(EmailComposeSettingsService::class)->finalizeOutgoingHtml($user, $htmlBody);

        try {
            $gmailService->sendMessage(
                $user,
                [$validated['test_email']],
                [],
                [],
                config('app.name') . ' — Gmail connection test',
                $htmlBody,
                []
            );
        } catch (\Throwable $e) {
            Log::error('Gmail settings test failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage() ?: 'Failed to send test email via Gmail.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Test email sent successfully via Gmail. Please check the inbox.',
        ]);
    }
}
