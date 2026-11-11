<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared checks for the mobile app login endpoints.
 */
class MobileAuth
{
    /**
     * Error response when the account may not sign in (inactive user or expired subscription).
     */
    public static function blockedResponse(User $user): ?JsonResponse
    {
        if ((int) $user->status !== 1) {
            return response()->json(['success' => false, 'message' => 'Your account is inactive. Please contact support.'], 403);
        }

        if ((int) ($user->crm_role_type ?? -1) !== 0) {
            $company = Company::first();
            if ($company && !empty($company->expire_date)) {
                $expire = \Carbon\Carbon::parse($company->expire_date)->endOfDay();
                if ($expire->isPast()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Application subscription expired on ' . $expire->format('d M Y') . '. Please contact your administrator to renew.',
                    ], 403);
                }
            }
        }

        return null;
    }

    /**
     * Confirms a Google access token was issued to one of our OAuth clients. Enforced once
     * GOOGLE_MOBILE_CLIENT_IDS (comma separated Android / iOS client ids) is configured.
     */
    public static function googleTokenAudienceIsValid(string $accessToken): bool
    {
        $mobileIds = array_values(array_filter(array_map('trim', explode(',', (string) config('services.google.mobile_client_ids', '')))));
        if (empty($mobileIds)) {
            Log::warning('GOOGLE_MOBILE_CLIENT_IDS is not set: mobile Google login accepts tokens from any Google OAuth client.');
            return true;
        }

        $allowed = array_values(array_filter(array_merge($mobileIds, [(string) config('services.google.client_id')])));

        try {
            $info = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['access_token' => $accessToken]);
        } catch (\Throwable $e) {
            Log::warning('Google tokeninfo request failed', ['error' => $e->getMessage()]);
            return false;
        }

        if (!$info->ok()) {
            return false;
        }

        $aud = (string) ($info->json('aud') ?? '');
        $azp = (string) ($info->json('azp') ?? '');

        return in_array($aud, $allowed, true) || in_array($azp, $allowed, true);
    }

    public static function userPayload(User $user): array
    {
        $data = $user->toArray();
        if ($user->avatar) {
            $data['avatar'] = url('/assets/employee_profile_image/' . $user->avatar);
        }

        return $data;
    }
}
