<?php

namespace App\Services\SocialAds;

use App\Models\SocialAdAccount;
use App\Models\SocialAdForm;
use App\Models\SocialAdIntegration;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Facebook & Instagram Lead Ads via the Meta Graph API.
 */
class MetaLeadAdsService
{
    public const SCOPES = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_metadata',
        'pages_manage_ads',
        'leads_retrieval',
        'business_management',
    ];

    protected const LEAD_FIELDS_FULL = 'id,created_time,field_data,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,form_id,platform,is_organic';
    protected const LEAD_FIELDS_BASIC = 'id,created_time,field_data,ad_id,form_id';

    public function __construct(protected SocialAdLeadProcessor $processor)
    {
    }

    public function integration(): SocialAdIntegration
    {
        return SocialAdIntegration::for(SocialAdIntegration::META);
    }

    public function version(): string
    {
        $v = trim((string) $this->integration()->credential('graph_version', 'v21.0'));

        return str_starts_with($v, 'v') ? $v : 'v' . $v;
    }

    public function hasAppCredentials(): bool
    {
        $i = $this->integration();

        return $i->credential('app_id') && $i->credential('app_secret');
    }

    public function redirectUri(): string
    {
        return route('social_ads.meta.callback');
    }

    public function authorizeUrl(string $state): string
    {
        return 'https://www.facebook.com/' . $this->version() . '/dialog/oauth?' . http_build_query([
            'client_id' => $this->integration()->credential('app_id'),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'scope' => implode(',', self::SCOPES),
            'response_type' => 'code',
        ]);
    }

    /**
     * Exchange the OAuth code for a long-lived user token, then store every Page the admin granted.
     *
     * @return int Number of Pages found
     */
    public function completeOAuth(string $code): int
    {
        $i = $this->integration();
        $short = $this->graph('GET', 'oauth/access_token', [
            'client_id' => $i->credential('app_id'),
            'client_secret' => $i->credential('app_secret'),
            'redirect_uri' => $this->redirectUri(),
            'code' => $code,
        ]);
        $long = $this->graph('GET', 'oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $i->credential('app_id'),
            'client_secret' => $i->credential('app_secret'),
            'fb_exchange_token' => $short['access_token'] ?? '',
        ]);
        $userToken = $long['access_token'] ?? $short['access_token'] ?? null;
        if (!$userToken) {
            throw new RuntimeException('Facebook did not return an access token.');
        }

        $me = $this->graph('GET', 'me', ['fields' => 'id,name', 'access_token' => $userToken]);

        $i->setCredentials(['user_token' => $userToken]);
        $i->fill([
            'is_enabled' => true,
            'connected_name' => $me['name'] ?? null,
            'connected_external_id' => $me['id'] ?? null,
            'connected_at' => now(),
            'token_expires_at' => isset($long['expires_in']) ? now()->addSeconds((int) $long['expires_in']) : null,
            'last_error' => null,
            'updated_by' => auth()->id(),
        ])->save();

        return $this->refreshPages();
    }

    /**
     * Re-read the Pages the connected user manages (Page tokens from a long-lived user token don't expire).
     */
    public function refreshPages(): int
    {
        $userToken = $this->integration()->credential('user_token');
        if (!$userToken) {
            throw new RuntimeException('Facebook is not connected.');
        }

        $count = 0;
        $params = ['fields' => 'id,name,access_token,category,tasks', 'limit' => 100, 'access_token' => $userToken];
        $path = 'me/accounts';
        do {
            $res = $this->graph('GET', $path, $params);
            foreach ($res['data'] ?? [] as $page) {
                if (empty($page['id']) || empty($page['access_token'])) {
                    continue;
                }
                $account = SocialAdAccount::firstOrNew(['platform' => SocialAdIntegration::META, 'external_id' => (string) $page['id']]);
                $account->fill([
                    'name' => $page['name'] ?? $page['id'],
                    'access_token' => $page['access_token'],
                    'meta' => ['category' => $page['category'] ?? null, 'tasks' => $page['tasks'] ?? []],
                    'last_error' => null,
                ])->save();
                $count++;
            }
            $next = $res['paging']['next'] ?? null;
            if ($next) {
                $path = $next;
                $params = [];
            }
        } while ($next);

        return $count;
    }

    public function subscribePage(SocialAdAccount $page): void
    {
        $this->graph('POST', $page->external_id . '/subscribed_apps', [
            'subscribed_fields' => 'leadgen',
            'access_token' => $page->access_token,
        ]);
        $page->update(['is_subscribed' => true, 'last_error' => null]);
        $this->syncForms($page);
    }

    public function unsubscribePage(SocialAdAccount $page): void
    {
        try {
            $this->graph('DELETE', $page->external_id . '/subscribed_apps', ['access_token' => $page->access_token]);
        } finally {
            $page->update(['is_subscribed' => false]);
        }
    }

    /**
     * Import the Page's lead forms (questions are used for field mapping).
     */
    public function syncForms(SocialAdAccount $page): int
    {
        $integration = $this->integration();
        $count = 0;
        $path = $page->external_id . '/leadgen_forms';
        $params = ['fields' => 'id,name,status,questions,created_time', 'limit' => 100, 'access_token' => $page->access_token];
        do {
            $res = $this->graph('GET', $path, $params);
            foreach ($res['data'] ?? [] as $f) {
                $form = SocialAdForm::firstOrNew(['platform' => SocialAdIntegration::META, 'external_id' => (string) $f['id']]);
                if (!$form->exists) {
                    $form->fill($integration->formDefaults());
                    $form->is_enabled = true;
                }
                $questions = [];
                foreach ($f['questions'] ?? [] as $q) {
                    if (!empty($q['key'])) {
                        $questions[] = ['key' => $q['key'], 'label' => $q['label'] ?? $q['key']];
                    }
                }
                $form->fill([
                    'social_ad_account_id' => $page->id,
                    'name' => $f['name'] ?? ('Form ' . $f['id']),
                    'status' => strtolower((string) ($f['status'] ?? '')) ?: null,
                ]);
                $form->questions = $this->mergeQuestions($form->questions ?? [], $questions);
                $form->save();
                $count++;
            }
            $next = $res['paging']['next'] ?? null;
            if ($next) {
                $path = $next;
                $params = [];
            }
        } while ($next);

        $page->update(['last_synced_at' => now(), 'last_error' => null]);

        return $count;
    }

    /**
     * Handle one "leadgen" webhook change.
     */
    public function handleLeadgen(array $value): void
    {
        $leadgenId = (string) ($value['leadgen_id'] ?? '');
        $pageId = (string) ($value['page_id'] ?? '');
        $formId = (string) ($value['form_id'] ?? '');
        if ($leadgenId === '') {
            return;
        }
        $page = SocialAdAccount::where('platform', SocialAdIntegration::META)->where('external_id', $pageId)->first();
        if (!$page) {
            throw new RuntimeException("Lead {$leadgenId} received for Page {$pageId}, which is not connected in CRM.");
        }
        $lead = $this->fetchLead($leadgenId, $page->access_token);
        $this->processLead($lead, $page, $formId ?: (string) ($lead['form_id'] ?? ''));
    }

    /**
     * Pull leads submitted since the last sync (catches anything a webhook missed).
     */
    public function syncFormLeads(SocialAdForm $form, ?int $sinceTimestamp = null): int
    {
        $page = $form->account;
        if (!$page || !$page->access_token) {
            throw new RuntimeException('The Facebook Page for this form is not connected.');
        }
        $since = $sinceTimestamp ?? ($form->last_synced_at ? $form->last_synced_at->copy()->subHour()->timestamp : now()->subDays(30)->timestamp);
        $startedAt = now();

        $count = 0;
        $path = $form->external_id . '/leads';
        $params = [
            'fields' => self::LEAD_FIELDS_BASIC,
            'limit' => 100,
            'filtering' => json_encode([['field' => 'time_created', 'operator' => 'GREATER_THAN', 'value' => $since]]),
            'access_token' => $page->access_token,
        ];
        do {
            $res = $this->graph('GET', $path, $params);
            foreach ($res['data'] ?? [] as $lead) {
                if ($this->processLead($lead, $page, $form->external_id)->wasRecentlyCreated) {
                    $count++;
                }
            }
            $next = $res['paging']['next'] ?? null;
            if ($next) {
                $path = $next;
                $params = [];
            }
        } while ($next);

        $form->forceFill(['last_synced_at' => $startedAt])->save();

        return $count;
    }

    public function fetchLead(string $leadgenId, string $pageToken): array
    {
        try {
            return $this->graph('GET', $leadgenId, ['fields' => self::LEAD_FIELDS_FULL, 'access_token' => $pageToken]);
        } catch (RuntimeException $e) {
            // Ad names need ads permissions; fall back to the lead data alone.
            return $this->graph('GET', $leadgenId, ['fields' => self::LEAD_FIELDS_BASIC, 'access_token' => $pageToken]);
        }
    }

    protected function processLead(array $lead, SocialAdAccount $page, string $formId): \App\Models\SocialAdLead
    {
        $form = SocialAdForm::where('platform', SocialAdIntegration::META)->where('external_id', $formId)->first();
        if (!$form) {
            $form = SocialAdForm::create(array_merge($this->integration()->formDefaults(), [
                'platform' => SocialAdIntegration::META,
                'social_ad_account_id' => $page->id,
                'external_id' => $formId ?: null,
                'name' => 'Facebook Form ' . ($formId ?: 'unknown'),
                'is_enabled' => true,
            ]));
            try {
                $this->syncForms($page);
                $form->refresh();
            } catch (\Throwable $e) {
                // Form name/questions are cosmetic; the lead can still be imported.
            }
        }

        $answers = [];
        foreach ($lead['field_data'] ?? [] as $field) {
            $name = (string) ($field['name'] ?? '');
            if ($name !== '') {
                $answers[$name] = implode(', ', array_map('strval', (array) ($field['values'] ?? [])));
            }
        }

        return $this->processor->handle($form, (string) ($lead['id'] ?? ''), $answers, $form->questionLabels(), [
            'campaign_name' => $lead['campaign_name'] ?? null,
            'adset_name' => $lead['adset_name'] ?? null,
            'ad_name' => $lead['ad_name'] ?? null,
            'submitted_at' => $lead['created_time'] ?? null,
            'is_test' => false,
        ], $lead);
    }

    /**
     * Register the app-level "page / leadgen" webhook with Meta (requires a public HTTPS URL).
     */
    public function registerAppWebhook(): void
    {
        $i = $this->integration();
        $this->graph('POST', $i->credential('app_id') . '/subscriptions', [
            'object' => 'page',
            'callback_url' => route('social_ads.webhook.meta'),
            'fields' => 'leadgen',
            'verify_token' => $i->webhook_key,
            'include_values' => 'true',
            'access_token' => $i->credential('app_id') . '|' . $i->credential('app_secret'),
        ]);
    }

    public function verifySignature(string $rawBody, ?string $header): bool
    {
        $secret = (string) $this->integration()->credential('app_secret');
        if ($secret === '' || !$header || !str_starts_with($header, 'sha256=')) {
            return false;
        }

        return hash_equals('sha256=' . hash_hmac('sha256', $rawBody, $secret), $header);
    }

    public function disconnect(): void
    {
        foreach (SocialAdAccount::where('platform', SocialAdIntegration::META)->get() as $page) {
            if ($page->is_subscribed) {
                try {
                    $this->unsubscribePage($page);
                } catch (\Throwable $e) {
                    // Token may already be revoked on Facebook's side.
                }
            }
            $page->update(['access_token' => null, 'is_subscribed' => false]);
        }
        $i = $this->integration();
        $credentials = $i->credentials ?? [];
        unset($credentials['user_token']);
        $i->credentials = $credentials;
        $i->fill(['is_enabled' => false, 'connected_at' => null, 'connected_name' => null, 'connected_external_id' => null, 'token_expires_at' => null])->save();
    }

    /**
     * Call the Graph API. $path may be a relative path or a full "paging.next" URL.
     */
    public function graph(string $method, string $path, array $params = []): array
    {
        $url = str_starts_with($path, 'https://') ? $path : 'https://graph.facebook.com/' . $this->version() . '/' . ltrim($path, '/');
        $http = Http::timeout(25)->acceptJson();
        $response = match (strtoupper($method)) {
            'POST' => $http->asForm()->post($url, $params),
            'DELETE' => $http->delete($url . (str_contains($url, '?') ? '&' : '?') . http_build_query($params)),
            default => $http->get($url, $params),
        };
        $json = $response->json() ?? [];
        if ($response->failed() || isset($json['error'])) {
            $message = $json['error']['error_user_msg'] ?? $json['error']['message'] ?? ('HTTP ' . $response->status());
            throw new RuntimeException('Facebook: ' . $message);
        }

        return is_array($json) ? $json : [];
    }

    protected function mergeQuestions(array $existing, array $fresh): array
    {
        $byKey = [];
        foreach (array_merge($fresh, $existing) as $q) {
            if (!empty($q['key']) && !isset($byKey[$q['key']])) {
                $byKey[$q['key']] = $q;
            }
        }

        return array_values($byKey);
    }
}
