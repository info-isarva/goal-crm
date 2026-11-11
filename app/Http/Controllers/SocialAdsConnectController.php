<?php

namespace App\Http\Controllers;

use App\Models\SocialAdAccount;
use App\Models\SocialAdIntegration;
use App\Services\SocialAds\LinkedInLeadGenService;
use App\Services\SocialAds\MetaLeadAdsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * OAuth connection flows for Meta (Facebook/Instagram) and LinkedIn.
 */
class SocialAdsConnectController extends Controller
{
    protected function ensureCanManage(): void
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
        if (!$user->hasCrmPermission('manage_crm_social_ads_guard') && !$user->hasCrmPermission('edit_crm_social_ads_guard')) {
            abort(403, 'Unauthorized action.');
        }
    }

    // ---------- Meta ----------

    public function metaSaveCredentials(Request $request, MetaLeadAdsService $meta)
    {
        $this->ensureCanManage();

        $integration = $meta->integration();
        $request->validate([
            'app_id' => 'required|regex:/^\d{5,25}$/',
            'app_secret' => [$integration->credential('app_secret') ? 'nullable' : 'required', 'string', 'max:100'],
            'graph_version' => ['required', 'regex:/^v?\d{1,2}\.\d$/'],
        ], [
            'app_id.regex' => 'App ID should be the numeric ID shown in your Meta app dashboard.',
            'graph_version.regex' => 'Graph API version looks like v21.0.',
        ]);

        $values = [
            'app_id' => $request->input('app_id'),
            'graph_version' => $request->input('graph_version'),
        ];
        if ($request->filled('app_secret')) {
            $values['app_secret'] = trim($request->input('app_secret'));
        }
        $integration->setCredentials($values);
        $integration->updated_by = auth()->id();
        $integration->save();

        return back()->with('success', 'Meta app details saved. Next, click "Connect with Facebook".');
    }

    public function metaConnect(Request $request, MetaLeadAdsService $meta)
    {
        $this->ensureCanManage();

        if (!$meta->hasAppCredentials()) {
            return redirect()->route('social_ads.meta')->with('error', 'Save your Meta App ID and App Secret first.');
        }
        $state = Str::random(40);
        $request->session()->put('social_ads_meta_state', $state);

        return redirect()->away($meta->authorizeUrl($state));
    }

    public function metaCallback(Request $request, MetaLeadAdsService $meta)
    {
        $this->ensureCanManage();

        $expected = $request->session()->pull('social_ads_meta_state');
        if ($request->filled('error')) {
            return redirect()->route('social_ads.meta')->with('error', 'Facebook connection was cancelled: ' . e($request->input('error_description', $request->input('error'))));
        }
        if (!$expected || !hash_equals($expected, (string) $request->input('state'))) {
            return redirect()->route('social_ads.meta')->with('error', 'The Facebook login session expired. Please click "Connect with Facebook" again.');
        }

        try {
            $pages = $meta->completeOAuth((string) $request->input('code'));
        } catch (\Throwable $e) {
            $meta->integration()->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);

            return redirect()->route('social_ads.meta')->with('error', e($e->getMessage()));
        }

        $webhookNote = '';
        try {
            $meta->registerAppWebhook();
        } catch (\Throwable $e) {
            $webhookNote = ' Webhook could not be registered automatically (' . e($e->getMessage()) . '); add it in your Meta app dashboard using the details on this page.';
        }

        return redirect()->route('social_ads.meta')->with('success', "Facebook connected. {$pages} Page(s) found. Turn on \"Receive Leads\" for the Pages you advertise from." . $webhookNote);
    }

    public function metaRefreshPages(MetaLeadAdsService $meta)
    {
        $this->ensureCanManage();

        try {
            $n = $meta->refreshPages();
        } catch (\Throwable $e) {
            return back()->with('error', e($e->getMessage()));
        }

        return back()->with('success', "{$n} Page(s) refreshed.");
    }

    public function metaRegisterWebhook(MetaLeadAdsService $meta)
    {
        $this->ensureCanManage();

        try {
            $meta->registerAppWebhook();
        } catch (\Throwable $e) {
            return back()->with('error', 'Webhook registration failed: ' . e($e->getMessage()) . ' Your CRM must be reachable on a public HTTPS address.');
        }

        return back()->with('success', 'Webhook registered with Meta. New leads will arrive instantly.');
    }

    public function metaDisconnect(MetaLeadAdsService $meta)
    {
        $this->ensureCanManage();

        $meta->disconnect();

        return redirect()->route('social_ads.meta')->with('success', 'Facebook disconnected. Existing forms and imported leads are kept.');
    }

    // ---------- LinkedIn ----------

    public function linkedinSaveCredentials(Request $request, LinkedInLeadGenService $linkedin)
    {
        $this->ensureCanManage();

        $integration = $linkedin->integration();
        $request->validate([
            'client_id' => 'required|string|max:100',
            'client_secret' => [$integration->credential('client_secret') ? 'nullable' : 'required', 'string', 'max:200'],
            'api_version' => ['nullable', 'regex:/^\d{6}$/'],
            'scopes' => 'nullable|string|max:300',
        ], [
            'api_version.regex' => 'API version is a month in YYYYMM format, e.g. ' . now()->subMonths(2)->format('Ym') . '.',
        ]);

        $values = [
            'client_id' => trim($request->input('client_id')),
            'api_version' => $request->input('api_version'),
            'scopes' => trim((string) $request->input('scopes')),
        ];
        if ($request->filled('client_secret')) {
            $values['client_secret'] = trim($request->input('client_secret'));
        }
        $integration->setCredentials($values);
        $integration->updated_by = auth()->id();
        $integration->save();

        return back()->with('success', 'LinkedIn app details saved. Next, click "Connect with LinkedIn".');
    }

    public function linkedinConnect(Request $request, LinkedInLeadGenService $linkedin)
    {
        $this->ensureCanManage();

        if (!$linkedin->hasAppCredentials()) {
            return redirect()->route('social_ads.linkedin')->with('error', 'Save your LinkedIn Client ID and Client Secret first.');
        }
        $state = Str::random(40);
        $request->session()->put('social_ads_linkedin_state', $state);

        return redirect()->away($linkedin->authorizeUrl($state));
    }

    public function linkedinCallback(Request $request, LinkedInLeadGenService $linkedin)
    {
        $this->ensureCanManage();

        $expected = $request->session()->pull('social_ads_linkedin_state');
        if ($request->filled('error')) {
            return redirect()->route('social_ads.linkedin')->with('error', 'LinkedIn connection was cancelled: ' . e($request->input('error_description', $request->input('error'))));
        }
        if (!$expected || !hash_equals($expected, (string) $request->input('state'))) {
            return redirect()->route('social_ads.linkedin')->with('error', 'The LinkedIn login session expired. Please click "Connect with LinkedIn" again.');
        }

        try {
            $accounts = $linkedin->completeOAuth((string) $request->input('code'));
            $forms = 0;
            foreach (SocialAdAccount::where('platform', SocialAdIntegration::LINKEDIN)->where('is_subscribed', true)->get() as $account) {
                try {
                    $forms += $linkedin->syncForms($account);
                } catch (\Throwable $e) {
                    $account->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);
                }
            }
        } catch (\Throwable $e) {
            $linkedin->integration()->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);

            return redirect()->route('social_ads.linkedin')->with('error', e($e->getMessage()));
        }

        return redirect()->route('social_ads.linkedin')->with('success', "LinkedIn connected. {$accounts} ad account(s) and {$forms} lead form(s) found.");
    }

    public function linkedinRefreshAccounts(LinkedInLeadGenService $linkedin)
    {
        $this->ensureCanManage();

        try {
            $n = $linkedin->refreshAdAccounts();
        } catch (\Throwable $e) {
            return back()->with('error', e($e->getMessage()));
        }

        return back()->with('success', "{$n} ad account(s) refreshed.");
    }

    public function linkedinDisconnect(LinkedInLeadGenService $linkedin)
    {
        $this->ensureCanManage();

        $linkedin->disconnect();

        return redirect()->route('social_ads.linkedin')->with('success', 'LinkedIn disconnected. Existing forms and imported leads are kept.');
    }

    // ---------- Pages / ad accounts ----------

    public function accountSubscribe(SocialAdAccount $socialAdAccount, MetaLeadAdsService $meta, LinkedInLeadGenService $linkedin)
    {
        $this->ensureCanManage();

        try {
            if ($socialAdAccount->platform === SocialAdIntegration::META) {
                $meta->subscribePage($socialAdAccount);
            } else {
                $socialAdAccount->update(['is_subscribed' => true]);
                $linkedin->syncForms($socialAdAccount);
            }
        } catch (\Throwable $e) {
            $socialAdAccount->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);

            return back()->with('error', e($e->getMessage()));
        }

        return back()->with('success', 'Now receiving leads from "' . e($socialAdAccount->name) . '". Its lead forms are listed below.');
    }

    public function accountUnsubscribe(SocialAdAccount $socialAdAccount, MetaLeadAdsService $meta)
    {
        $this->ensureCanManage();

        try {
            if ($socialAdAccount->platform === SocialAdIntegration::META) {
                $meta->unsubscribePage($socialAdAccount);
            } else {
                $socialAdAccount->update(['is_subscribed' => false]);
            }
        } catch (\Throwable $e) {
            return back()->with('error', e($e->getMessage()));
        }

        return back()->with('success', 'Stopped receiving leads from "' . e($socialAdAccount->name) . '".');
    }

    public function accountSyncForms(SocialAdAccount $socialAdAccount, MetaLeadAdsService $meta, LinkedInLeadGenService $linkedin)
    {
        $this->ensureCanManage();

        try {
            $n = $socialAdAccount->platform === SocialAdIntegration::META
                ? $meta->syncForms($socialAdAccount)
                : $linkedin->syncForms($socialAdAccount);
        } catch (\Throwable $e) {
            $socialAdAccount->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);

            return back()->with('error', e($e->getMessage()));
        }

        return back()->with('success', "{$n} lead form(s) synced from \"" . e($socialAdAccount->name) . '".');
    }

    public function accountSyncLeads(SocialAdAccount $socialAdAccount, LinkedInLeadGenService $linkedin)
    {
        $this->ensureCanManage();
        abort_unless($socialAdAccount->platform === SocialAdIntegration::LINKEDIN, 404);

        try {
            $n = $linkedin->syncLeads($socialAdAccount);
        } catch (\Throwable $e) {
            $socialAdAccount->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);

            return back()->with('error', e($e->getMessage()));
        }

        return back()->with('success', "Sync complete. {$n} new lead(s) received from LinkedIn.");
    }
}
