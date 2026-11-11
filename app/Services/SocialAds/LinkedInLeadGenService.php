<?php

namespace App\Services\SocialAds;

use App\Models\SocialAdAccount;
use App\Models\SocialAdForm;
use App\Models\SocialAdIntegration;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * LinkedIn Lead Gen Forms via the Marketing "Lead Sync" REST API (polling).
 */
class LinkedInLeadGenService
{
    public const DEFAULT_SCOPES = 'r_ads r_marketing_leadgen_automation';

    public function __construct(protected SocialAdLeadProcessor $processor)
    {
    }

    public function integration(): SocialAdIntegration
    {
        return SocialAdIntegration::for(SocialAdIntegration::LINKEDIN);
    }

    public function apiVersion(): string
    {
        $v = preg_replace('/\D/', '', (string) $this->integration()->credential('api_version', ''));

        return strlen($v) === 6 ? $v : now()->subMonths(2)->format('Ym');
    }

    public function scopes(): string
    {
        return trim((string) $this->integration()->credential('scopes', '')) ?: self::DEFAULT_SCOPES;
    }

    public function hasAppCredentials(): bool
    {
        $i = $this->integration();

        return $i->credential('client_id') && $i->credential('client_secret');
    }

    public function redirectUri(): string
    {
        return route('social_ads.linkedin.callback');
    }

    public function authorizeUrl(string $state): string
    {
        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->integration()->credential('client_id'),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'scope' => $this->scopes(),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function completeOAuth(string $code): int
    {
        $token = $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
        ]);
        $this->storeToken($token);

        $i = $this->integration();
        $i->fill([
            'is_enabled' => true,
            'connected_at' => now(),
            'connected_name' => 'LinkedIn Ads',
            'last_error' => null,
            'updated_by' => auth()->id(),
        ])->save();

        return $this->refreshAdAccounts();
    }

    public function refreshAdAccounts(): int
    {
        $res = $this->api('adAccountUsers', 'q=authenticatedUser');
        $count = 0;
        foreach ($res['elements'] ?? [] as $row) {
            $id = $this->urnId((string) ($row['account'] ?? ''));
            if ($id === '') {
                continue;
            }
            $name = 'Ad Account ' . $id;
            $status = null;
            try {
                $acc = $this->api('adAccounts/' . $id);
                $name = $acc['name'] ?? $name;
                $status = $acc['status'] ?? null;
            } catch (\Throwable $e) {
                // Name lookup is optional.
            }
            $account = SocialAdAccount::firstOrNew(['platform' => SocialAdIntegration::LINKEDIN, 'external_id' => $id]);
            $wasNew = !$account->exists;
            $account->fill([
                'name' => $name,
                'meta' => ['role' => $row['role'] ?? null, 'status' => $status],
                'last_error' => null,
            ]);
            if ($wasNew) {
                $account->is_subscribed = true;
            }
            $account->save();
            $count++;
        }

        return $count;
    }

    public function syncForms(SocialAdAccount $account): int
    {
        $integration = $this->integration();
        $count = 0;
        $start = 0;
        do {
            $res = $this->api('leadForms', 'q=owner&owner=' . $this->ownerParam($account) . '&count=100&start=' . $start);
            $elements = $res['elements'] ?? [];
            foreach ($elements as $f) {
                $formId = (string) ($f['id'] ?? '');
                if ($formId === '') {
                    continue;
                }
                $form = SocialAdForm::firstOrNew(['platform' => SocialAdIntegration::LINKEDIN, 'external_id' => $formId]);
                if (!$form->exists) {
                    $form->fill($integration->formDefaults());
                    $form->is_enabled = true;
                }
                $form->fill([
                    'social_ad_account_id' => $account->id,
                    'name' => $f['name'] ?? ('LinkedIn Form ' . $formId),
                    'status' => strtolower((string) ($f['state'] ?? '')) ?: null,
                ]);
                $form->questions = $this->questionsFromForm($f);
                $form->save();
                $count++;
            }
            $start += count($elements);
        } while (count($elements) === 100 && $start < 2000);

        $account->update(['last_synced_at' => now(), 'last_error' => null]);

        return $count;
    }

    /**
     * Import lead form responses submitted since the last sync for one ad account.
     */
    public function syncLeads(SocialAdAccount $account, ?int $sinceMs = null): int
    {
        $sinceMs = $sinceMs ?? (($account->meta['leads_synced_at'] ?? null)
            ? ((int) $account->meta['leads_synced_at'] - 3600) * 1000
            : now()->subDays(30)->getTimestampMs());
        $untilMs = now()->getTimestampMs();
        $startedAt = now()->timestamp;

        $count = 0;
        $start = 0;
        do {
            $query = 'q=owner&owner=' . $this->ownerParam($account)
                . '&leadType=(leadType:SPONSORED)&limitedToTestLeads=false'
                . '&submittedAtTimeRange=(start:' . $sinceMs . ',end:' . $untilMs . ')'
                . '&count=100&start=' . $start;
            $res = $this->api('leadFormResponses', $query);
            $elements = $res['elements'] ?? [];
            foreach ($elements as $resp) {
                if ($this->processResponse($resp, $account)?->wasRecentlyCreated) {
                    $count++;
                }
            }
            $start += count($elements);
        } while (count($elements) === 100 && $start < 5000);

        $meta = $account->meta ?? [];
        $meta['leads_synced_at'] = $startedAt;
        $account->update(['meta' => $meta, 'last_synced_at' => now(), 'last_error' => null]);

        return $count;
    }

    protected function processResponse(array $resp, SocialAdAccount $account): ?\App\Models\SocialAdLead
    {
        $formId = '';
        if (preg_match('/leadGenForm:(\d+)/', (string) ($resp['versionedLeadGenFormUrn'] ?? $resp['leadGenFormUrn'] ?? ''), $m)) {
            $formId = $m[1];
        }
        $form = SocialAdForm::where('platform', SocialAdIntegration::LINKEDIN)->where('external_id', $formId)->first();
        if (!$form) {
            try {
                $this->syncForms($account);
            } catch (\Throwable $e) {
                // Fall through and create a placeholder form below.
            }
            $form = SocialAdForm::firstOrCreate(
                ['platform' => SocialAdIntegration::LINKEDIN, 'external_id' => $formId ?: null],
                array_merge($this->integration()->formDefaults(), [
                    'social_ad_account_id' => $account->id,
                    'name' => 'LinkedIn Form ' . ($formId ?: 'unknown'),
                    'is_enabled' => true,
                ])
            );
        }

        $keyById = [];
        foreach ($form->questions ?? [] as $q) {
            if (isset($q['question_id'])) {
                $keyById[(string) $q['question_id']] = $q['key'];
            }
        }

        $answers = [];
        foreach ($resp['formResponse']['answers'] ?? [] as $a) {
            $qid = (string) ($a['questionId'] ?? '');
            $key = $keyById[$qid] ?? ('question_' . $qid);
            $answers[$key] = $this->answerText($a['answerDetails'] ?? []);
        }

        $campaignUrn = (string) ($resp['leadMetadata']['sponsoredLeadMetadata']['campaign'] ?? '');

        return $this->processor->handle($form, (string) ($resp['id'] ?? ''), $answers, $form->questionLabels(), [
            'campaign_name' => $campaignUrn !== '' ? 'Campaign #' . $this->urnId($campaignUrn) : null,
            'submitted_at' => $resp['submittedAt'] ?? null,
            'is_test' => !empty($resp['testLead']),
        ], $resp);
    }

    protected function questionsFromForm(array $form): array
    {
        $questions = [];
        foreach ($form['content']['questions'] ?? [] as $q) {
            $qid = (string) ($q['questionId'] ?? '');
            $predefined = (string) ($q['predefinedField'] ?? '');
            $label = $q['question']['localized']['en_US'] ?? (is_array($q['question']['localized'] ?? null) ? reset($q['question']['localized']) : null) ?? $q['name'] ?? $predefined;
            $questions[] = [
                'key' => $predefined !== '' ? $predefined : ('question_' . $qid),
                'label' => (string) ($label ?: ('Question ' . $qid)),
                'question_id' => $qid,
            ];
        }

        return $questions;
    }

    protected function answerText(array $details): string
    {
        if (isset($details['textQuestionAnswer']['answer'])) {
            return (string) $details['textQuestionAnswer']['answer'];
        }
        if (isset($details['multipleChoiceAnswer']['options'])) {
            return implode(', ', array_map('strval', (array) $details['multipleChoiceAnswer']['options']));
        }
        $flat = [];
        array_walk_recursive($details, function ($v) use (&$flat) {
            if (is_scalar($v)) {
                $flat[] = (string) $v;
            }
        });

        return implode(', ', $flat);
    }

    public function disconnect(): void
    {
        $i = $this->integration();
        $credentials = $i->credentials ?? [];
        unset($credentials['access_token'], $credentials['refresh_token'], $credentials['refresh_expires_at']);
        $i->credentials = $credentials;
        $i->fill(['is_enabled' => false, 'connected_at' => null, 'connected_name' => null, 'token_expires_at' => null])->save();
    }

    protected function ownerParam(SocialAdAccount $account): string
    {
        return '(sponsoredAccount:' . rawurlencode('urn:li:sponsoredAccount:' . $account->external_id) . ')';
    }

    protected function urnId(string $urn): string
    {
        return preg_match('/(\d+)\)?$/', $urn, $m) ? $m[1] : '';
    }

    protected function accessToken(): string
    {
        $i = $this->integration();
        $token = (string) $i->credential('access_token', '');
        if ($token === '') {
            throw new RuntimeException('LinkedIn is not connected.');
        }
        if ($i->token_expires_at && $i->token_expires_at->isPast()) {
            if (!$i->credential('refresh_token')) {
                throw new RuntimeException('LinkedIn access expired. Click "Connect with LinkedIn" again.');
            }
            $this->storeToken($this->tokenRequest([
                'grant_type' => 'refresh_token',
                'refresh_token' => $i->credential('refresh_token'),
            ]));
            $token = (string) $this->integration()->credential('access_token');
        }

        return $token;
    }

    protected function tokenRequest(array $params): array
    {
        $i = $this->integration();
        $response = Http::timeout(25)->asForm()->acceptJson()->post('https://www.linkedin.com/oauth/v2/accessToken', $params + [
            'client_id' => $i->credential('client_id'),
            'client_secret' => $i->credential('client_secret'),
        ]);
        $json = $response->json() ?? [];
        if ($response->failed() || empty($json['access_token'])) {
            throw new RuntimeException('LinkedIn: ' . ($json['error_description'] ?? $json['error'] ?? ('HTTP ' . $response->status())));
        }

        return $json;
    }

    protected function storeToken(array $token): void
    {
        $i = $this->integration();
        $values = ['access_token' => $token['access_token']];
        if (!empty($token['refresh_token'])) {
            $values['refresh_token'] = $token['refresh_token'];
            $values['refresh_expires_at'] = isset($token['refresh_token_expires_in'])
                ? now()->addSeconds((int) $token['refresh_token_expires_in'])->toIso8601String()
                : null;
        }
        $i->setCredentials($values);
        $i->token_expires_at = isset($token['expires_in']) ? now()->addSeconds((int) $token['expires_in'] - 300) : null;
        $i->save();
    }

    /**
     * Call the versioned LinkedIn REST API. $query is a pre-encoded Rest.li query string.
     */
    public function api(string $resource, string $query = ''): array
    {
        $url = 'https://api.linkedin.com/rest/' . ltrim($resource, '/') . ($query !== '' ? '?' . $query : '');
        $response = Http::timeout(25)
            ->withToken($this->accessToken())
            ->withHeaders([
                'LinkedIn-Version' => $this->apiVersion(),
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->acceptJson()
            ->get($url);
        $json = $response->json() ?? [];
        if ($response->failed()) {
            throw new RuntimeException('LinkedIn: ' . ($json['message'] ?? ('HTTP ' . $response->status())));
        }

        return is_array($json) ? $json : [];
    }
}
