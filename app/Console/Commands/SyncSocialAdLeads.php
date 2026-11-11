<?php

namespace App\Console\Commands;

use App\Models\SocialAdAccount;
use App\Models\SocialAdForm;
use App\Models\SocialAdIntegration;
use App\Services\SocialAds\LinkedInLeadGenService;
use App\Services\SocialAds\MetaLeadAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncSocialAdLeads extends Command
{
    protected $signature = 'social-ads:sync {--platform= : meta or linkedin}';

    protected $description = 'Pull new leads from Facebook/Instagram and LinkedIn lead forms (backup for missed webhooks)';

    public function handle(MetaLeadAdsService $meta, LinkedInLeadGenService $linkedin): int
    {
        $platform = $this->option('platform');

        if (!$platform || $platform === SocialAdIntegration::META) {
            if (SocialAdIntegration::for(SocialAdIntegration::META)->isConnected()) {
                $forms = SocialAdForm::where('platform', SocialAdIntegration::META)
                    ->where('is_enabled', true)
                    ->whereHas('account', fn ($q) => $q->where('is_subscribed', true)->whereNotNull('access_token'))
                    ->get();
                foreach ($forms as $form) {
                    try {
                        $n = $meta->syncFormLeads($form);
                        $this->line("Meta form {$form->name}: {$n} new");
                    } catch (\Throwable $e) {
                        $form->account?->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);
                        $this->warn("Meta form {$form->name}: {$e->getMessage()}");
                    }
                }
            }
        }

        if (!$platform || $platform === SocialAdIntegration::LINKEDIN) {
            if (SocialAdIntegration::for(SocialAdIntegration::LINKEDIN)->isConnected()) {
                foreach (SocialAdAccount::where('platform', SocialAdIntegration::LINKEDIN)->where('is_subscribed', true)->get() as $account) {
                    try {
                        $n = $linkedin->syncLeads($account);
                        $this->line("LinkedIn account {$account->name}: {$n} new");
                    } catch (\Throwable $e) {
                        $account->update(['last_error' => Str::limit($e->getMessage(), 1000, '')]);
                        $this->warn("LinkedIn account {$account->name}: {$e->getMessage()}");
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
