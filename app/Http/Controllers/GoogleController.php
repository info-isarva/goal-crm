<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use App\Models\User;
use App\Services\GoogleGmailService;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Calendar;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        // return Socialite::driver('google')
        // ->scopes([
        //     'https://www.googleapis.com/auth/calendar',
        // ])
        // ->with([
        //     'access_type' => 'offline',
        //     'prompt' => 'consent',
        //     'redirect_uri' => route('google.callback'), // Ensure proper redirect URI
        // ])
        // ->redirect();
        return Socialite::driver('google')
            ->setScopes([
                'openid',
                'profile',
                'email',
                GoogleGmailService::GMAIL_SEND_SCOPE,
            ])
            ->with([
                'access_type' => 'offline',
                'prompt'      => 'consent',
            ])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request = null)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            $returnUrl = session('google_gmail_return');
            $isGmailIntent = session('oauth_intent') === GoogleGmailController::OAUTH_INTENT;

            app(GoogleGmailController::class)->clearGmailOAuthSession();

            if ($isGmailIntent && $returnUrl) {
                return redirect(\App\Support\SafeRedirect::internal($returnUrl, route('gmail.settings.edit')))->with(
                    'error',
                    'Gmail authorization session expired. Please click Connect Gmail again. (Do not open the link in a different browser or tab.)'
                );
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in session expired. Please try signing in again.',
            ]);
        }

        if (session('oauth_intent') === GoogleGmailController::OAUTH_INTENT) {
            return app(GoogleGmailController::class)->completeConnection($googleUser);
        }
       
        
        // Check if user exists by email
        $user = User::where('email', $googleUser->getEmail())->first();
        if (!$user) {
            // If not found, redirect back with error
            return redirect('/login')->withErrors(['email' => 'Your email is not registered. Please contact admin.']);
        }

        if ((int) $user->status === 0) {
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive.']);
        }

        if ((int) ($user->crm_role_type ?? -1) !== 0) {
            $company = \App\Models\Company::first();
            if ($company && !empty($company->expire_date)) {
                $expireDate = \Carbon\Carbon::parse($company->expire_date)->endOfDay();
                if ($expireDate->isPast()) {
                    $formattedDate = \Carbon\Carbon::parse($company->expire_date)->format('d M Y');
                    return redirect()->route('login')->withErrors([
                        'email' => 'Application subscription expired on ' . $formattedDate . '. Please contact your administrator to renew.'
                    ]);
                }
            }
        }

        $gmailService = app(GoogleGmailService::class);
        $oauthResult = $gmailService->storeOAuthTokensFromSocialite($user, $googleUser);
        $gmailService->syncOAuthSessionFromSocialite(
            $googleUser,
            (bool) ($oauthResult['gmail_connected'] ?? false),
            $oauthResult['scopes'] ?? []
        );

        $user->update([
            'email_verified_at' => now(),
        ]);

        $loginSuccessMessage = ($oauthResult['gmail_connected'] ?? false)
            ? 'Logged in successfully. Gmail account connected automatically for quotation sending.'
            : null;

        // 2FA check
        if ($user->{"2fa_enabled"}) {
            $code = random_int(100000, 999999);
            session(['2fa:user:id' => $user->id]);
            session(['2fa:code' => $code]);
            session(['2fa:expires' => now()->addMinutes(10)]);
            session(['2fa:attempts' => 0]);
            \Mail::raw("Your 2FA code is: $code", function($message) use ($user) {
                $message->to($user->email)
                    ->subject('Your Two-Factor Authentication Code');
            });
            // Log login attempt (pre-2FA)
            \App\Helpers\ActivityLogger::log([
                'type' => 'login',
                'module' => 'User',
                'action' => 'login',
                'user_id' => $user->id,
                'note' => '2FA code sent to email (Google login)',
            ]);
            // Do not log in yet, redirect to 2FA code entry page
            return redirect()->route('auth.2fa.verify');
        } else {
            Auth::login($user);
            session()->regenerate();
            \App\Helpers\ActivityLogger::log([
                'type' => 'login',
                'module' => 'User',
                'action' => 'login',
                'user_id' => $user->id,
                'note' => 'Logged in without 2FA (Google login)',
            ]);

            $redirect = redirect('/dashboard');
            if ($loginSuccessMessage) {
                $redirect->with('success', $loginSuccessMessage);
            }

            return $redirect;
        }
    }

    public function validateGoogleToken()
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID')); 
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->addScope(Google_Service_Calendar::CALENDAR);

        if (!session()->has('google_token') || !session()->has('google_refresh_token')) {
            //generate token if not exists
            return response()->json([
                'valid' => false,
                'redirect' => route('google.redirectToG'), // Redirect to Google OAuth2 flow
            ]);
        }

        $client->setAccessToken([
            'access_token' => session('google_token'),
            'refresh_token' => session('google_refresh_token'),
        ]);

        if ($client->isAccessTokenExpired()) {
            try {
                $client->fetchAccessTokenWithRefreshToken(session('google_refresh_token'));
                session(['google_token' => $client->getAccessToken()['access_token']]);
            } catch (Exception $e) {
                return response()->json(['valid' => false]);
            }
        }

        return response()->json(['valid' => true]);
    }

    public function redirectToG()
    {
        return Socialite::driver('google')
        ->scopes([
            'https://www.googleapis.com/auth/calendar',
        ])
        ->with([
            'access_type' => 'offline',
            'prompt' => 'consent',
            'redirect_uri' => route('google.callback'), // Ensure proper redirect URI
        ])
        ->redirect();
    }

    public function createGoogleMeeting(Request $request)
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/google-consent-client.json'));
        $client->addScope(Google_Service_Calendar::CALENDAR);

        $client->setAccessToken([
            'access_token' => session('google_token'),
            'refresh_token' => session('google_refresh_token'),
        ]);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken(session('google_refresh_token'));
            session(['google_token' => $client->getAccessToken()['access_token']]);
        }

        $service = new Google_Service_Calendar($client);

        $event = new Google_Service_Calendar_Event([
            'summary' => $request->input('name'),
            'description' => $request->input('description', 'No description provided.'),
            'start' => [
                'dateTime' => $request->input('start_at'),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'end' => [
                'dateTime' => $request->input('finish_at'),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
        ]);

        try {
            $createdEvent = $service->events->insert('primary', $event);
            return response()->json(['success' => true, 'link' => $createdEvent->htmlLink]);
        } catch (\Exception $e) {
            \Log::error('Google meeting creation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Could not create the Google Calendar event.']);
        }
    }
}
