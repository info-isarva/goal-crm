<?php

namespace App\Services\Goal;

use App\Models\GoalCaseEvent;
use App\Models\GoalLoanCase;
use App\Models\User;

class GoalCaseAudit
{
    public static function record(
        GoalLoanCase $case,
        string $eventType,
        string $title,
        ?User $actor = null,
        array $extra = []
    ): GoalCaseEvent {
        return GoalCaseEvent::create([
            'goal_loan_case_id' => $case->id,
            'user_id' => $actor?->id ?? auth()->id(),
            'event_type' => $eventType,
            'title' => $title,
            'body' => $extra['body'] ?? null,
            'from_value' => isset($extra['from']) ? (string) $extra['from'] : null,
            'to_value' => isset($extra['to']) ? (string) $extra['to'] : null,
            'related_type' => $extra['related_type'] ?? null,
            'related_id' => $extra['related_id'] ?? null,
            'meta' => $extra['meta'] ?? null,
        ]);
    }
}
