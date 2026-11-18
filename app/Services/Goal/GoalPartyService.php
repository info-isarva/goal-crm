<?php

namespace App\Services\Goal;

use App\Models\GoalCustomerParty;
use App\Models\GoalLoanCase;
use App\Models\GoalLoanLead;

class GoalPartyService
{
    public static function normalizeMobile(?string $mobile): ?string
    {
        if ($mobile === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $mobile) ?: '';
        if ($digits === '') {
            return null;
        }
        // Keep last 10 digits for IN mobiles when longer.
        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        return $digits;
    }

    public function resolveOrCreate(?string $mobile, ?string $name = null): ?GoalCustomerParty
    {
        $normalized = self::normalizeMobile($mobile);
        if (! $normalized) {
            return null;
        }

        $party = GoalCustomerParty::query()
            ->where('normalized_mobile', $normalized)
            ->first();

        if ($party) {
            if ($name && ! $party->canonical_name) {
                $party->canonical_name = $name;
                $party->save();
            }

            return $party;
        }

        return GoalCustomerParty::create([
            'normalized_mobile' => $normalized,
            'canonical_name' => $name,
        ]);
    }

    public function attachLead(GoalLoanLead $lead): ?GoalCustomerParty
    {
        $party = $this->resolveOrCreate($lead->mobile, $lead->customer_name);
        if (! $party) {
            return null;
        }
        if ((int) $lead->goal_party_id !== (int) $party->id) {
            $lead->goal_party_id = $party->id;
            $lead->save();
        }

        return $party;
    }

    public function attachCase(GoalLoanCase $case, ?GoalLoanLead $lead = null): ?GoalCustomerParty
    {
        $party = null;
        if ($lead?->goal_party_id) {
            $party = GoalCustomerParty::query()->find($lead->goal_party_id);
        }
        $party ??= $this->resolveOrCreate($case->mobile, $case->customer_name);
        if (! $party) {
            return null;
        }
        if ((int) $case->goal_party_id !== (int) $party->id) {
            $case->goal_party_id = $party->id;
            $case->save();
        }

        return $party;
    }

    /**
     * @param  array<int>|null  $scopedOrgIds  empty array ⇒ no rows; null ⇒ unscoped
     * @return array{party:?GoalCustomerParty,leads:\Illuminate\Support\Collection,cases:\Illuminate\Support\Collection}
     */
    public function matchesForMobile(?string $mobile, ?int $excludeLeadId = null, ?array $scopedOrgIds = null): array
    {
        $normalized = self::normalizeMobile($mobile);
        if (! $normalized) {
            return ['party' => null, 'leads' => collect(), 'cases' => collect()];
        }

        $applyScope = function ($query) use ($scopedOrgIds) {
            if ($scopedOrgIds === null) {
                return $query;
            }
            if ($scopedOrgIds === []) {
                return $query->whereRaw('0 = 1');
            }

            return $query->whereIn('goal_org_unit_id', $scopedOrgIds);
        };

        $party = GoalCustomerParty::query()->where('normalized_mobile', $normalized)->first();
        if (! $party) {
            $leads = $applyScope(GoalLoanLead::query())
                ->when($excludeLeadId, fn ($q) => $q->where('id', '!=', $excludeLeadId))
                ->where(function ($q) use ($mobile, $normalized) {
                    $q->where('mobile', $mobile)->orWhere('mobile', $normalized);
                })
                ->latest()
                ->limit(20)
                ->get();
            $cases = $applyScope(GoalLoanCase::query())
                ->where(function ($q) use ($mobile, $normalized) {
                    $q->where('mobile', $mobile)->orWhere('mobile', $normalized);
                })
                ->latest()
                ->limit(20)
                ->get();

            return ['party' => null, 'leads' => $leads, 'cases' => $cases];
        }

        $leads = $applyScope($party->leads())
            ->when($excludeLeadId, fn ($q) => $q->where('id', '!=', $excludeLeadId))
            ->latest()
            ->limit(20)
            ->get();
        $cases = $applyScope($party->cases())->latest()->limit(20)->get();

        return ['party' => $party, 'leads' => $leads, 'cases' => $cases];
    }
}
