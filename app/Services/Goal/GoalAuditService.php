<?php

namespace App\Services\Goal;

use App\Models\GoalCaseEvent;
use App\Models\GoalLoanCase;
use App\Models\GoalLoanLead;
use App\Models\GoalLoanLeadEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Consolidated Goal audit façade over case + lead event tables.
 */
class GoalAuditService
{
    public const TYPE_DISBURSEMENT = 'disbursement';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_PMS = 'pms';
    public const TYPE_ACCOUNTS = 'accounts';
    public const TYPE_APPLICATION = 'application';
    public const TYPE_STATUS = 'status_change';
    public const TYPE_ALLOCATION = 'allocation';

    public static function recordCase(
        GoalLoanCase $case,
        string $eventType,
        string $title,
        ?User $actor = null,
        array $extra = []
    ): GoalCaseEvent {
        return GoalCaseAudit::record($case, $eventType, $title, $actor, $extra);
    }

    public static function recordLead(
        GoalLoanLead $lead,
        string $type,
        string $title,
        ?string $body = null,
        array $extra = []
    ): GoalLoanLeadEvent {
        return GoalLoanLeadEvent::record($lead, $type, $title, $body, $extra);
    }

    /**
     * Attach a financial-queue mutation to the parent case timeline.
     */
    public static function recordFinancial(
        GoalLoanCase $case,
        string $eventType,
        string $title,
        ?Model $related = null,
        ?User $actor = null,
        array $extra = []
    ): GoalCaseEvent {
        if ($related) {
            $extra['related_type'] = $related::class;
            $extra['related_id'] = $related->getKey();
        }

        return self::recordCase($case, $eventType, $title, $actor, $extra);
    }

    /**
     * Unified timeline for a customer party (leads + cases), newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function partyTimeline(int $partyId, int $limit = 50): array
    {
        $leadIds = GoalLoanLead::query()->where('goal_party_id', $partyId)->pluck('id');
        $caseIds = GoalLoanCase::query()->where('goal_party_id', $partyId)->pluck('id');

        $leadEvents = GoalLoanLeadEvent::query()
            ->whereIn('goal_loan_lead_id', $leadIds)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (GoalLoanLeadEvent $e) => [
                'source' => 'lead',
                'entity_id' => $e->goal_loan_lead_id,
                'event_type' => $e->event_type,
                'title' => $e->title,
                'body' => $e->body,
                'at' => optional($e->created_at)->toIso8601String(),
            ]);

        $caseEvents = GoalCaseEvent::query()
            ->whereIn('goal_loan_case_id', $caseIds)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (GoalCaseEvent $e) => [
                'source' => 'case',
                'entity_id' => $e->goal_loan_case_id,
                'event_type' => $e->event_type,
                'title' => $e->title,
                'body' => $e->body,
                'at' => optional($e->created_at)->toIso8601String(),
            ]);

        return $leadEvents->concat($caseEvents)
            ->sortByDesc('at')
            ->take($limit)
            ->values()
            ->all();
    }
}
