<?php

namespace App\Services\SocialAds;

use App\Models\SocialAdForm;
use App\Models\SocialAdIntegration;
use App\Models\SocialAdLead;

/**
 * Google Ads lead form assets deliver leads to a webhook URL with a shared key.
 */
class GoogleAdsLeadService
{
    public function __construct(protected SocialAdLeadProcessor $processor)
    {
    }

    public function keyIsValid(SocialAdIntegration $integration, ?string $key): bool
    {
        return $integration->webhook_key && $key && hash_equals((string) $integration->webhook_key, (string) $key);
    }

    public function handle(array $payload): SocialAdLead
    {
        $integration = SocialAdIntegration::for(SocialAdIntegration::GOOGLE);
        $formId = (string) ($payload['form_id'] ?? 'unknown');

        $form = SocialAdForm::firstOrCreate(
            ['platform' => SocialAdIntegration::GOOGLE, 'external_id' => $formId],
            array_merge($integration->formDefaults(), [
                'name' => 'Google Ads Form ' . $formId,
                'is_enabled' => true,
            ])
        );

        $answers = [];
        $labels = [];
        foreach ($payload['user_column_data'] ?? [] as $col) {
            $key = (string) ($col['column_id'] ?? $col['column_name'] ?? '');
            if ($key === '') {
                continue;
            }
            $answers[$key] = (string) ($col['string_value'] ?? '');
            $labels[$key] = (string) ($col['column_name'] ?? $key);
        }

        $campaign = !empty($payload['campaign_id']) ? 'Campaign #' . $payload['campaign_id'] : null;
        $integration->forceFill(['last_synced_at' => now()])->save();

        return $this->processor->handle($form, (string) ($payload['lead_id'] ?? ''), $answers, $labels, [
            'campaign_name' => $campaign,
            'adset_name' => !empty($payload['adgroup_id']) ? 'Ad group #' . $payload['adgroup_id'] : null,
            'ad_name' => !empty($payload['creative_id']) ? 'Ad #' . $payload['creative_id'] : null,
            'submitted_at' => $payload['lead_submit_time'] ?? null,
            'is_test' => filter_var($payload['is_test'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ], $this->redact($payload));
    }

    protected function redact(array $payload): array
    {
        unset($payload['google_key']);

        return $payload;
    }
}
