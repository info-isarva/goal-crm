<?php

namespace App\Http\Controllers;

use App\Models\SocialAdForm;
use App\Models\SocialAdIntegration;
use App\Services\SocialAds\GoogleAdsLeadService;
use App\Services\SocialAds\MetaLeadAdsService;
use App\Services\SocialAds\SocialAdLeadProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Public endpoints that ad platforms call (no login, CSRF exempt).
 */
class SocialAdsWebhookController extends Controller
{
    /**
     * Meta webhook verification handshake (GET with hub.* params).
     */
    public function metaVerify(Request $request)
    {
        $integration = SocialAdIntegration::for(SocialAdIntegration::META);
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        if ($mode === 'subscribe' && $integration->webhook_key && hash_equals((string) $integration->webhook_key, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Verification failed', 403);
    }

    public function metaReceive(Request $request, MetaLeadAdsService $meta)
    {
        $raw = $request->getContent();
        if (!$meta->verifySignature($raw, $request->header('X-Hub-Signature-256'))) {
            Log::warning('Meta lead webhook rejected: invalid signature');

            return response('Invalid signature', 403);
        }

        $payload = json_decode($raw, true) ?: [];
        $changes = [];
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') === 'leadgen' && is_array($change['value'] ?? null)) {
                    $changes[] = $change['value'];
                }
            }
        }

        if ($changes) {
            // Respond to Meta immediately; fetching lead details happens after the response is sent.
            dispatch(function () use ($changes) {
                $service = app(MetaLeadAdsService::class);
                foreach ($changes as $value) {
                    try {
                        $service->handleLeadgen($value);
                    } catch (\Throwable $e) {
                        Log::error('Meta lead webhook processing failed', ['value' => $value, 'error' => $e->getMessage()]);
                        SocialAdIntegration::for(SocialAdIntegration::META)->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);
                    }
                }
            })->afterResponse();
        }

        return response('EVENT_RECEIVED', 200);
    }

    public function google(Request $request, GoogleAdsLeadService $google)
    {
        $payload = $request->json()->all() ?: $request->all();
        $integration = SocialAdIntegration::for(SocialAdIntegration::GOOGLE);

        if (!$google->keyIsValid($integration, $payload['google_key'] ?? null)) {
            return response()->json(['error' => 'Invalid key'], 403);
        }

        try {
            $google->handle($payload);
        } catch (\Throwable $e) {
            Log::error('Google Ads lead webhook failed', ['error' => $e->getMessage()]);
            $integration->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);

            return response()->json(['error' => 'Could not process lead'], 500);
        }

        return response()->json((object) []);
    }

    public function generic(Request $request, string $token, SocialAdLeadProcessor $processor)
    {
        $form = SocialAdForm::where('platform', SocialAdIntegration::GENERIC)->where('token', $token)->first();
        if (!$form) {
            return response()->json(['ok' => false, 'message' => 'Unknown webhook'], 404);
        }

        $input = $request->isJson() ? ($request->json()->all() ?: []) : $request->all();
        $answers = $this->flatten($input);
        $externalId = null;
        foreach (['lead_id', 'leadgen_id', 'external_id', 'submission_id', 'entry_id', 'id'] as $idKey) {
            if (isset($answers[$idKey]) && $answers[$idKey] !== '') {
                $externalId = Str::limit((string) $answers[$idKey], 140, '');
                unset($answers[$idKey]);
                break;
            }
        }
        $meta = [
            'campaign_name' => $answers['campaign_name'] ?? $answers['utm_campaign'] ?? null,
            'adset_name' => $answers['adset_name'] ?? $answers['utm_content'] ?? null,
            'ad_name' => $answers['ad_name'] ?? $answers['utm_term'] ?? null,
            'submitted_at' => $answers['created_time'] ?? $answers['submitted_at'] ?? null,
            'is_test' => filter_var($answers['is_test'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
        foreach (['campaign_name', 'adset_name', 'ad_name', 'created_time', 'submitted_at', 'is_test', 'utm_campaign', 'utm_content', 'utm_term'] as $k) {
            unset($answers[$k]);
        }

        if (empty($answers)) {
            return response()->json(['ok' => false, 'message' => 'No lead fields received'], 422);
        }

        $log = $processor->handle($form, $externalId ? $form->id . ':' . $externalId : null, $answers, [], $meta, $input);
        $form->forceFill(['last_synced_at' => now()])->save();

        return response()->json([
            'ok' => in_array($log->status, ['created', 'test'], true),
            'status' => $log->status,
            'lead_id' => $log->lead_id,
            'message' => $log->error_message,
        ], $log->status === 'failed' ? 422 : 200);
    }

    /**
     * Flatten nested input one level deep: {"data": {"email": "x"}} => ["email" => "x"].
     */
    protected function flatten(array $input, string $prefix = ''): array
    {
        $out = [];
        foreach ($input as $key => $value) {
            $key = (string) $key;
            if (str_starts_with($key, '_') || in_array($key, ['token', 'google_key'], true)) {
                continue;
            }
            if (is_array($value)) {
                if (array_is_list($value) && count(array_filter($value, 'is_scalar')) === count($value)) {
                    $out[$prefix . $key] = implode(', ', array_map('strval', $value));
                } elseif ($prefix === '') {
                    $out += $this->flatten($value, in_array($key, ['data', 'fields', 'lead', 'payload', 'answers', 'contact'], true) ? '' : $key . '_');
                }
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $out[$prefix . $key] = trim((string) $value);
            }
        }

        return $out;
    }
}
