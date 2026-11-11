<?php

namespace App\Http\Controllers;

use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\ProductCategory;
use App\Models\SocialAdAccount;
use App\Models\SocialAdForm;
use App\Models\SocialAdIntegration;
use App\Models\SocialAdLead;
use App\Models\User;
use App\Services\SocialAds\LinkedInLeadGenService;
use App\Services\SocialAds\MetaLeadAdsService;
use App\Services\SocialAds\SocialAdLeadProcessor;
use App\Support\SocialAdFieldMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SocialAdsController extends Controller
{
    public static function userCanManage($user): bool
    {
        if (!$user) {
            return false;
        }
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return true;
        }
        if (!\App\Models\Company::current()->isSocialAdsEnabled()) {
            return false;
        }
        return $user->hasCrmPermission('manage_crm_social_ads_guard')
            || $user->hasCrmPermission('view_crm_social_ads_guard')
            || $user->hasCrmPermission('create_crm_social_ads_guard')
            || $user->hasCrmPermission('edit_crm_social_ads_guard')
            || $user->hasCrmPermission('delete_crm_social_ads_guard');
    }

    protected function authorizeSocialAds(string $action): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return;
        }
        if (!\App\Models\Company::current()->isSocialAdsEnabled()) {
            abort(403, 'Social Ads module is currently disabled.');
        }
        if ($user->hasCrmPermission('manage_crm_social_ads_guard')) {
            return;
        }

        $guardMap = [
            'view' => 'view_crm_social_ads_guard',
            'create' => 'create_crm_social_ads_guard',
            'edit' => 'edit_crm_social_ads_guard',
            'delete' => 'delete_crm_social_ads_guard',
        ];

        $guard = $guardMap[$action] ?? null;
        if (!$guard || !$user->hasCrmPermission($guard)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $this->authorizeSocialAds('view');

        $platforms = [];
        foreach (SocialAdIntegration::platforms() as $key => $def) {
            $integration = SocialAdIntegration::for($key);
            $platforms[$key] = $def + [
                'key' => $key,
                'integration' => $integration,
                'forms' => SocialAdForm::where('platform', $key)->count(),
                'leads_total' => SocialAdLead::where('platform', $key)->where('status', SocialAdLead::STATUS_CREATED)->count(),
                'leads_today' => SocialAdLead::where('platform', $key)->where('status', SocialAdLead::STATUS_CREATED)->whereDate('created_at', today())->count(),
                'failed' => SocialAdLead::where('platform', $key)->where('status', SocialAdLead::STATUS_FAILED)->count(),
                'connected' => $key === SocialAdIntegration::GENERIC
                    ? SocialAdForm::where('platform', $key)->exists()
                    : ($key === SocialAdIntegration::GOOGLE ? SocialAdForm::where('platform', $key)->exists() : $integration->isConnected()),
            ];
        }

        $recent = SocialAdLead::with(['form:id,name,platform', 'lead:id,title'])->orderByDesc('id')->limit(10)->get();

        return view('social_ads.index', compact('platforms', 'recent'));
    }

    public function meta(MetaLeadAdsService $meta)
    {
        $this->authorizeSocialAds('view');

        $integration = $meta->integration();
        $pages = SocialAdAccount::where('platform', SocialAdIntegration::META)->orderBy('name')->get();

        return view('social_ads.meta', $this->sharedData(SocialAdIntegration::META) + [
            'integration' => $integration,
            'pages' => $pages,
            'hasAppCredentials' => $meta->hasAppCredentials(),
            'redirectUri' => $meta->redirectUri(),
            'webhookUrl' => route('social_ads.webhook.meta'),
            'scopes' => MetaLeadAdsService::SCOPES,
            'hostWarning' => $this->metaHostWarning(),
        ]);
    }

    /**
     * Facebook rejects IP-address redirect URLs and plain HTTP outside localhost.
     */
    protected function metaHostWarning(): ?string
    {
        $host = request()->getHost();
        $port = request()->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ':' . $port;

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return "Facebook does not accept IP addresses such as {$host}. Open the CRM at http://localhost{$portSuffix} (and set APP_URL=http://localhost{$portSuffix} in .env), then copy the redirect URI again.";
        }
        if (!request()->isSecure() && $host !== 'localhost') {
            return "Facebook only accepts HTTPS redirect URLs for {$host}. Open the CRM on https:// before connecting.";
        }

        return null;
    }

    public function google()
    {
        $this->authorizeSocialAds('view');

        $integration = SocialAdIntegration::for(SocialAdIntegration::GOOGLE);
        if (!$integration->webhook_key) {
            $integration->update(['webhook_key' => Str::random(40)]);
        }

        return view('social_ads.google', $this->sharedData(SocialAdIntegration::GOOGLE) + [
            'integration' => $integration,
            'webhookUrl' => route('social_ads.webhook.google'),
        ]);
    }

    public function linkedin(LinkedInLeadGenService $linkedin)
    {
        $this->authorizeSocialAds('view');

        return view('social_ads.linkedin', $this->sharedData(SocialAdIntegration::LINKEDIN) + [
            'integration' => $linkedin->integration(),
            'accounts' => SocialAdAccount::where('platform', SocialAdIntegration::LINKEDIN)->orderBy('name')->get(),
            'hasAppCredentials' => $linkedin->hasAppCredentials(),
            'redirectUri' => $linkedin->redirectUri(),
            'apiVersion' => $linkedin->apiVersion(),
            'scopes' => $linkedin->scopes(),
        ]);
    }

    public function generic()
    {
        $this->authorizeSocialAds('view');

        return view('social_ads.generic', $this->sharedData(SocialAdIntegration::GENERIC) + [
            'integration' => SocialAdIntegration::for(SocialAdIntegration::GENERIC),
        ]);
    }

    public function genericStore(Request $request)
    {
        $this->authorizeSocialAds('create');

        $request->validate(['name' => 'required|string|max:150']);
        $integration = SocialAdIntegration::for(SocialAdIntegration::GENERIC);
        $form = SocialAdForm::create(array_merge($integration->formDefaults(), [
            'platform' => SocialAdIntegration::GENERIC,
            'name' => $request->input('name'),
            'token' => SocialAdForm::generateToken(),
            'is_enabled' => true,
        ]));

        return redirect()->route('social_ads.forms.edit', $form)
            ->with('success', 'Webhook created. Copy the URL below into Zapier, Make, Pabbly or your landing page tool.');
    }

    public function saveDefaults(Request $request, string $platform)
    {
        $this->authorizeSocialAds('edit');
        abort_unless(array_key_exists($platform, SocialAdIntegration::platforms()), 404);

        $defaults = $this->validatedDefaults($request);
        $integration = SocialAdIntegration::for($platform);
        $integration->settings = array_merge($integration->settings ?? [], $defaults);
        $integration->updated_by = auth()->id();
        $integration->save();

        $message = 'Default lead settings saved for new forms.';
        if ($request->boolean('apply_to_existing')) {
            $n = SocialAdForm::where('platform', $platform)->update($defaults);
            $message = "Default lead settings saved and applied to {$n} existing form(s).";
        }

        return back()->with('success', $message);
    }

    public function googleRegenerateKey()
    {
        $this->authorizeSocialAds('edit');

        SocialAdIntegration::for(SocialAdIntegration::GOOGLE)->update(['webhook_key' => Str::random(40), 'updated_by' => auth()->id()]);

        return back()->with('success', 'New Google Ads key generated. Update the key in every Google Ads lead form.');
    }

    public function formEdit(SocialAdForm $socialAdForm)
    {
        $this->authorizeSocialAds('edit');

        $form = $socialAdForm->load('account');

        return view('social_ads.form_edit', $this->lookupData() + [
            'form' => $form,
            'platform' => SocialAdIntegration::platforms()[$form->platform] ?? ['label' => $form->platform, 'icon' => 'bi-plug', 'color' => '#64748b'],
            'mapping' => $form->effectiveMapping(),
            'crmFields' => SocialAdFieldMapper::crmFields(),
            'samples' => $this->sampleAnswers($form),
        ]);
    }

    public function formUpdate(Request $request, SocialAdForm $socialAdForm)
    {
        $this->authorizeSocialAds('edit');

        $defaults = $this->validatedDefaults($request);
        $request->validate([
            'name' => 'required|string|max:255',
            'mapping' => 'nullable|array',
            'mapping.*' => ['nullable', Rule::in(array_merge(array_keys(SocialAdFieldMapper::crmFields()), [SocialAdFieldMapper::IGNORE]))],
            'new_questions' => 'nullable|string|max:2000',
        ]);

        $questions = $socialAdForm->questions ?? [];
        $known = array_column($questions, 'key');
        foreach (preg_split('/[\r\n,]+/', (string) $request->input('new_questions')) ?: [] as $key) {
            $key = trim($key);
            if ($key !== '' && !in_array($key, $known, true)) {
                $questions[] = ['key' => Str::limit($key, 100, ''), 'label' => Str::limit($key, 100, '')];
                $known[] = $key;
            }
        }

        $mapping = [];
        foreach ((array) $request->input('mapping', []) as $key => $field) {
            if (in_array((string) $key, $known, true) && $field !== null && $field !== '') {
                $mapping[(string) $key] = $field;
            }
        }

        $socialAdForm->fill($defaults + [
            'name' => $request->input('name'),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);
        $socialAdForm->questions = $questions;
        $socialAdForm->field_mapping = $mapping;
        $socialAdForm->save();

        return redirect()->route('social_ads.forms.edit', $socialAdForm)->with('success', 'Form settings saved. New leads from this form will use them.');
    }

    public function formToggle(SocialAdForm $socialAdForm)
    {
        $this->authorizeSocialAds('edit');

        $socialAdForm->update(['is_enabled' => !$socialAdForm->is_enabled]);

        $name = e($socialAdForm->name);

        return back()->with('success', $socialAdForm->is_enabled
            ? "\"{$name}\" turned on. Its leads will be imported."
            : "\"{$name}\" turned off. Its leads will be logged but not imported.");
    }

    public function formSync(SocialAdForm $socialAdForm, MetaLeadAdsService $meta, LinkedInLeadGenService $linkedin)
    {
        $this->authorizeSocialAds('edit');

        try {
            if ($socialAdForm->platform === SocialAdIntegration::META) {
                $n = $meta->syncFormLeads($socialAdForm, $socialAdForm->last_synced_at ? null : now()->subDays(90)->timestamp);
            } elseif ($socialAdForm->platform === SocialAdIntegration::LINKEDIN && $socialAdForm->account) {
                $n = $linkedin->syncLeads($socialAdForm->account);
            } else {
                return back()->with('error', 'This form receives leads automatically; there is nothing to sync.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', e($e->getMessage()));
        }

        return back()->with('success', "Sync complete. {$n} new lead(s) received.");
    }

    public function formRegenerateToken(SocialAdForm $socialAdForm)
    {
        $this->authorizeSocialAds('edit');
        abort_unless($socialAdForm->platform === SocialAdIntegration::GENERIC, 404);

        $socialAdForm->update(['token' => SocialAdForm::generateToken()]);

        return back()->with('success', 'New webhook URL generated. Update it in the tool that sends leads; the old URL no longer works.');
    }

    public function formDestroy(SocialAdForm $socialAdForm)
    {
        $this->authorizeSocialAds('delete');

        $platform = $socialAdForm->platform;
        $socialAdForm->delete();

        return redirect()->route('social_ads.' . $platform)->with('success', 'Form removed. Already imported leads are kept.');
    }

    public function leads(Request $request)
    {
        $this->authorizeSocialAds('view');

        $platform = $request->get('platform');
        $status = $request->get('status');
        $formId = $request->integer('form');
        $search = trim((string) $request->get('q', ''));
        $perPage = in_array((int) $request->get('per_page'), [25, 50, 100], true) ? (int) $request->get('per_page') : 25;

        $leads = SocialAdLead::with(['form:id,name,platform', 'lead:id,title'])
            ->when($platform, fn ($q) => $q->where('platform', $platform))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($formId, fn ($q) => $q->where('social_ad_form_id', $formId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('answers', 'like', '%' . $search . '%')
                        ->orWhere('campaign_name', 'like', '%' . $search . '%')
                        ->orWhere('ad_name', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('social_ads.leads', [
            'leads' => $leads,
            'platforms' => SocialAdIntegration::platforms(),
            'forms' => SocialAdForm::orderBy('name')->get(['id', 'name', 'platform']),
            'platform' => $platform,
            'status' => $status,
            'formId' => $formId,
            'search' => $search,
            'perPage' => $perPage,
            'failedCount' => SocialAdLead::whereIn('status', [SocialAdLead::STATUS_FAILED, SocialAdLead::STATUS_SKIPPED])->count(),
        ]);
    }

    public function retry(SocialAdLead $socialAdLead, SocialAdLeadProcessor $processor)
    {
        $this->authorizeSocialAds('edit');

        if ($socialAdLead->status === SocialAdLead::STATUS_CREATED) {
            return back()->with('error', 'This lead was already imported.');
        }
        $socialAdLead->is_test = false;
        $processor->createLead($socialAdLead);

        return $socialAdLead->status === SocialAdLead::STATUS_CREATED
            ? back()->with('success', 'Lead imported successfully.')
            : back()->with('error', 'Could not import: ' . e($socialAdLead->error_message));
    }

    public function retryFailed(SocialAdLeadProcessor $processor)
    {
        $this->authorizeSocialAds('edit');

        $ok = 0;
        $failed = 0;
        SocialAdLead::whereIn('status', [SocialAdLead::STATUS_FAILED, SocialAdLead::STATUS_SKIPPED])
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->each(function (SocialAdLead $log) use ($processor, &$ok, &$failed) {
                $processor->createLead($log);
                $log->status === SocialAdLead::STATUS_CREATED ? $ok++ : $failed++;
            });

        return back()->with($failed ? 'error' : 'success', "Retry finished: {$ok} imported, {$failed} still failing.");
    }

    protected function sharedData(string $platform): array
    {
        $integration = SocialAdIntegration::for($platform);

        return $this->lookupData() + [
            'platformKey' => $platform,
            'platform' => SocialAdIntegration::platforms()[$platform],
            'defaults' => $integration->formDefaults(),
            'forms' => SocialAdForm::with(['account:id,name', 'owner:id,name', 'leadSource:id,name'])
                ->where('platform', $platform)
                ->orderBy('name')
                ->get(),
        ];
    }

    protected function lookupData(): array
    {
        return [
            'leadSources' => LeadSource::orderBy('name')->get(['id', 'name']),
            'leadStatuses' => LeadStatus::orderBy('id')->get(['id', 'name']),
            'users' => User::where('status', 1)->orderBy('name')->get(['id', 'name']),
            'categories' => ProductCategory::where('status', 1)->orderBy('category_name')->get(['id', 'category_name']),
        ];
    }

    protected function validatedDefaults(Request $request): array
    {
        $request->validate([
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'lead_status' => 'required|exists:leads_status,name',
            'lead_priority' => ['required', Rule::in(['high', 'normal', 'low'])],
            'owner_id' => 'required|exists:users,id',
            'default_category_ids' => 'nullable|array',
            'default_category_ids.*' => 'integer|exists:product_categories,id',
        ], [
            'owner_id.required' => 'Please select who should own the leads.',
        ]);

        return [
            'lead_source_id' => $request->input('lead_source_id') ?: null,
            'lead_status' => $request->input('lead_status'),
            'lead_priority' => $request->input('lead_priority'),
            'owner_id' => (int) $request->input('owner_id'),
            'default_category_ids' => implode(',', array_map('intval', (array) $request->input('default_category_ids', []))) ?: null,
        ];
    }

    /**
     * Latest answer per question, to help admins recognise fields while mapping.
     */
    protected function sampleAnswers(SocialAdForm $form): array
    {
        $samples = [];
        foreach ($form->leads()->orderByDesc('id')->limit(5)->get(['answers']) as $log) {
            foreach ($log->answers ?? [] as $key => $value) {
                if (!isset($samples[$key]) && trim((string) $value) !== '') {
                    $samples[$key] = Str::limit((string) $value, 60);
                }
            }
        }

        return $samples;
    }
}
