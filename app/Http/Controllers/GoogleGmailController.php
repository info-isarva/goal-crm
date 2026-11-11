<?php

namespace App\Http\Controllers;

use App\Services\GoogleGmailService;
use App\Support\SafeRedirect;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleGmailController extends Controller
{
    public const OAUTH_INTENT = 'gmail';

    public function connect(Request $request)
    {
        $user = auth()->user();
        $returnUrl = SafeRedirect::internal(
            $request->query('return', url()->previous()),
            route('gmail.settings.edit')
        );

        if (trim((string) $user->email) === '') {
            return redirect($returnUrl)->with(
                'error',
                'Your CRM account does not have an email address. Gmail can only be connected using your CRM login email.'
            );
        }

        // Clear old tokens so reconnect cannot reuse a login token without gmail.send.
        $user->update([
            'google_token' => null,
            'google_refresh_token' => null,
        ]);
        session()->forget(['google_token', 'google_refresh_token', 'google_user_id']);

        session([
            'oauth_intent'                 => self::OAUTH_INTENT,
            'google_gmail_return'          => $returnUrl,
            'google_gmail_connect_user_id' => $user->id,
            'google_gmail_expected_email'  => $user->email,
        ]);
        session()->save();

        $oauthParams = [
            'access_type' => 'offline',
            'prompt'      => 'consent select_account',
            'include_granted_scopes' => 'false',
        ];
        if (!empty($user->email)) {
            $oauthParams['login_hint'] = $user->email;
        }

        return Socialite::driver('google')
            ->setScopes([
                'openid',
                'profile',
                'email',
                GoogleGmailService::GMAIL_SEND_SCOPE,
            ])
            ->with($oauthParams)
            ->redirect();
    }

    /**
     * Legacy callback route — forwards to the shared Google OAuth handler.
     */
    public function callback(Request $request)
    {
        return app(GoogleController::class)->handleGoogleCallback($request);
    }

    /**
     * Complete Gmail connection after Google redirects back.
     *
     * @param  mixed  $googleUser
     */
    public function completeConnection($googleUser)
    {
        $returnUrl = $this->normalizeGmailReturnUrl(session('google_gmail_return', route('gmail.settings.edit')));
        $expectedUserId = session('google_gmail_connect_user_id');
        $expectedEmail = (string) session('google_gmail_expected_email', '');

        $this->clearGmailOAuthSession();

        if (!auth()->check()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your CRM session expired during Gmail authorization. Please sign in and try again.',
            ]);
        }

        if ((int) $expectedUserId !== (int) auth()->id()) {
            return redirect($returnUrl)->with('error', 'Gmail connection session mismatch. Please try again.');
        }

        $user = auth()->user();
        $googleEmail = (string) $googleUser->getEmail();
        if ($expectedEmail === '') {
            $expectedEmail = (string) $user->email;
        }

        if (!$this->emailsMatch($expectedEmail, $googleEmail)) {
            return redirect($returnUrl)->with(
                'error',
                'You can only connect Gmail using your CRM login email (' . $expectedEmail . '). '
                . 'The Google account selected was ' . ($googleEmail !== '' ? $googleEmail : 'unknown') . '. '
                . 'Please sign in with the same email address in Google and try again.'
            );
        }

        $scopes = array_filter(array_map('trim', (array) ($googleUser->approvedScopes ?? [])));
        if (!GoogleGmailService::scopesIncludeGmailSend($scopes)) {
            return redirect($returnUrl)->with(
                'error',
                'Gmail send permission was not granted. In Google consent, allow “Send email on your behalf”, then connect again. Also enable Gmail API in Google Cloud Console.'
            );
        }

        $refreshToken = $googleUser->refreshToken;
        if (empty($refreshToken)) {
            return redirect($returnUrl)->with(
                'error',
                'Google did not return a refresh token. Open https://myaccount.google.com/permissions , remove access for this CRM app, then click Connect Gmail again.'
            );
        }

        $oauthResult = app(GoogleGmailService::class)->storeOAuthTokensFromSocialite($user, $googleUser);
        app(GoogleGmailService::class)->syncOAuthSessionFromSocialite(
            $googleUser,
            (bool) ($oauthResult['gmail_connected'] ?? false),
            $oauthResult['scopes'] ?? []
        );

        if (!($oauthResult['gmail_connected'] ?? false)) {
            return redirect($returnUrl)->with(
                'error',
                'Gmail send permission was not granted or could not be verified. Please connect Gmail again and allow Send email.'
            );
        }

        return redirect($returnUrl)
            ->with('success', 'Gmail connected successfully with send permission. You can now send quotations via Gmail.')
            ->with('open_gmail', str_contains($returnUrl, '/quotations/'));
    }

    protected function normalizeGmailReturnUrl(string $returnUrl): string
    {
        $returnUrl = SafeRedirect::internal($returnUrl, route('gmail.settings.edit'));

        if (preg_match('#/quotations/(\d+)/gmail-compose(?:\?.*)?$#', $returnUrl, $matches)) {
            return route('quotations.show', $matches[1]) . '?open_gmail=1';
        }

        if (preg_match('#/quotations/(\d+)(?:\?.*)?$#', $returnUrl, $matches)) {
            $separator = str_contains($returnUrl, '?') ? '&' : '?';

            return str_contains($returnUrl, 'open_gmail=1')
                ? $returnUrl
                : $returnUrl . $separator . 'open_gmail=1';
        }

        return $returnUrl;
    }

    public function status(GoogleGmailService $gmailService)
    {
        $user = auth()->user();

        return response()->json([
            'connected'   => $gmailService->userHasGmailAccess($user),
            'email'       => $user->email,
            'connect_url' => route('google.gmail.connect', ['return' => url()->current()]),
        ]);
    }

    public function clearGmailOAuthSession(): void
    {
        session()->forget([
            'oauth_intent',
            'google_gmail_return',
            'google_gmail_connect_user_id',
            'google_gmail_expected_email',
        ]);
    }

    protected function emailsMatch(?string $expectedEmail, ?string $googleEmail): bool
    {
        $expected = strtolower(trim((string) $expectedEmail));
        $google = strtolower(trim((string) $googleEmail));

        return $expected !== '' && $google !== '' && $expected === $google;
    }
}
