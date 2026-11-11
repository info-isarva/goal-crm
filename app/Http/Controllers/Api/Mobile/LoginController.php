<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Support\MobileAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Handle a login request to the application (Mobile API).
     */
    public function login(Request $request)
    {
        // If Google token is present, use Google login
        if ($request->has('google_token')) {
            $googleToken = (string) $request->google_token;
            if ($googleToken === '' || !MobileAuth::googleTokenAudienceIsValid($googleToken)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google token',
                ], 401);
            }

            try {
                $googleUser = Socialite::driver('google')->stateless()->userFromToken($googleToken);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google token',
                ], 401);
            }

            $user = User::where('email', $googleUser->getEmail())->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your email is not registered. Please contact admin.',
                ], 404);
            }

            if ($blocked = MobileAuth::blockedResponse($user)) {
                return $blocked;
            }

            $user->update([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
            ]);

            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful (Google)',
                'token' => $token,
                'user' => MobileAuth::userPayload($user),
            ]);
        }

        // Otherwise, use email/password login
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $throttleKey = 'mobile-login:' . Str::lower((string) $request->email) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many login attempts. Please try again in ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minute(s).',
            ], 429);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check((string) $request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 300);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }
        RateLimiter::clear($throttleKey);

        if ($blocked = MobileAuth::blockedResponse($user)) {
            return $blocked;
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => MobileAuth::userPayload($user),
        ]);
    }
}
