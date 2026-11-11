<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects the website lead API with a shared secret sent as the
 * "X-Api-Key" header (or "api_key" field for simple form posts).
 */
class VerifyLeadApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.lead_api.key');

        if ($expected === '') {
            return response()->json(['message' => 'Lead API is not configured.'], 503);
        }

        $given = (string) ($request->header('X-Api-Key') ?? $request->input('api_key', ''));

        if ($given === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
