<?php

namespace App\Services\SocialAds;

use App\Models\SocialAdForm;
use App\Models\SocialAdLead;
use App\Services\LeadIntakeService;
use App\Support\SocialAdFieldMapper;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Turns an ad lead (already parsed into question => answer pairs) into a CRM lead and logs the result.
 */
class SocialAdLeadProcessor
{
    public function __construct(protected LeadIntakeService $intake)
    {
    }

    /**
     * @param  array<string, string>  $answers  question key => answer
     * @param  array<string, string>  $labels  question key => question label
     * @param  array{campaign_name?: ?string, adset_name?: ?string, ad_name?: ?string, submitted_at?: mixed, is_test?: bool}  $meta
     */
    public function handle(SocialAdForm $form, ?string $externalId, array $answers, array $labels = [], array $meta = [], array $payload = []): SocialAdLead
    {
        if ($externalId !== null && $externalId !== '') {
            $existing = SocialAdLead::where('platform', $form->platform)->where('external_id', $externalId)->first();
            if ($existing) {
                return $existing;
            }
        }

        $form->rememberQuestions(array_map('strval', array_keys($answers)), $labels);

        try {
            $log = SocialAdLead::create([
                'platform' => $form->platform,
                'social_ad_form_id' => $form->id,
                'external_id' => $externalId ?: null,
                'answers' => $answers,
                'payload' => $payload ?: null,
                'status' => SocialAdLead::STATUS_PENDING,
                'is_test' => !empty($meta['is_test']),
                'campaign_name' => isset($meta['campaign_name']) ? Str::limit((string) $meta['campaign_name'], 250, '') : null,
                'adset_name' => isset($meta['adset_name']) ? Str::limit((string) $meta['adset_name'], 250, '') : null,
                'ad_name' => isset($meta['ad_name']) ? Str::limit((string) $meta['ad_name'], 250, '') : null,
                'submitted_at' => $this->parseTime($meta['submitted_at'] ?? null),
            ]);
        } catch (QueryException $e) {
            // Webhook and scheduled sync can deliver the same lead at the same moment.
            $existing = SocialAdLead::where('platform', $form->platform)->where('external_id', $externalId)->first();
            if ($existing) {
                return $existing;
            }
            throw $e;
        }

        return $this->createLead($log);
    }

    /**
     * Create (or re-try creating) the CRM lead for a logged ad lead.
     */
    public function createLead(SocialAdLead $log): SocialAdLead
    {
        $form = $log->form;
        if (!$form) {
            $log->update(['status' => SocialAdLead::STATUS_FAILED, 'error_message' => 'The ad form for this lead was removed.']);

            return $log;
        }
        if ($log->is_test) {
            $log->update(['status' => SocialAdLead::STATUS_TEST, 'error_message' => 'Test lead received. No CRM lead was created.']);

            return $log;
        }
        if (!$form->is_enabled) {
            $log->update(['status' => SocialAdLead::STATUS_SKIPPED, 'error_message' => 'Form is turned off in CRM. Turn it on and click Retry to import.']);

            return $log;
        }

        $data = SocialAdFieldMapper::map($log->answers ?? [], $form->questionLabels(), $form->field_mapping ?? []);
        $data = LeadIntakeService::normalizeName($data);
        $adContext = array_filter([
            $log->campaign_name ? 'Campaign: ' . $log->campaign_name : null,
            $log->ad_name ? 'Ad: ' . $log->ad_name : null,
        ]);
        if ($adContext) {
            $data['description'] = trim(($data['description'] ?? '') . "\n" . implode(' | ', $adContext));
        }

        if (trim((string) ($data['first_name'] ?? '')) === '' && empty($data['email']) && empty($data['mobile'])) {
            $log->update([
                'status' => SocialAdLead::STATUS_FAILED,
                'error_message' => 'No name, email or phone found. Check the field mapping for this form, then click Retry.',
            ]);

            return $log;
        }

        try {
            $lead = $this->intake->create($data, $form->leadOptions());
            $log->update(['status' => SocialAdLead::STATUS_CREATED, 'lead_id' => $lead->id, 'error_message' => null]);
            $form->increment('leads_count');
            $form->forceFill(['last_lead_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::error('Social ad lead import failed', ['social_ad_lead_id' => $log->id, 'error' => $e->getMessage()]);
            $log->update(['status' => SocialAdLead::STATUS_FAILED, 'error_message' => Str::limit($e->getMessage(), 1000, '')]);
        }

        return $log;
    }

    protected function parseTime($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if (is_numeric($value)) {
                $ts = (int) $value;

                return Carbon::createFromTimestamp($ts > 9999999999 ? intdiv($ts, 1000) : $ts, config('app.timezone'));
            }

            return Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Throwable $e) {
            return null;
        }
    }
}
