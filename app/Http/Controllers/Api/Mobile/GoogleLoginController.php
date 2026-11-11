<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Support\MobileAuth;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class GoogleLoginController extends Controller
{
    /**
     * Handle Google login for mobile API.
     */
    public function login(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:4096',
        ]);

        if (!MobileAuth::googleTokenAudienceIsValid($request->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Google token',
            ], 401);
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->token);
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
            'message' => 'Login successful',
            'token' => $token,
            'user' => MobileAuth::userPayload($user),
        ]);
    }
}
