<?php

namespace App\Services\Goal;

use App\Models\GoalCaseTask;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\User;
use App\Notifications\GoalCaseAlert;
use Illuminate\Support\Collection;

class GoalNotify
{
    public const TYPES = [
        'immediate',
        'assign',
        'callback',
        'task',
        'visit',
        'pd01',
        'payout',
        'target',
        'admin',
    ];

    /**
     * Send a Goal in-app alert with 6-hour unread dedupe on event+url.
     */
    public static function send(
        ?User $user,
        string $title,
        string $message,
        string $url,
        string $eventKey,
        string $type = 'task',
        ?string $ref = null,
        bool $action = true
    ): bool {
        if (! $user) {
            return false;
        }

        $type = in_array($type, self::TYPES, true) ? $type : 'task';

        $exists = $user->notifications()
            ->whereNull('read_at')
            ->where('type', GoalCaseAlert::class)
            ->where('data->event', $eventKey)
            ->where('data->url', $url)
            ->where('created_at', '>=', now()->subHours(6))
            ->exists();

        if ($exists) {
            return false;
        }

        $user->notify(new GoalCaseAlert(
            $title,
            $message,
            $url,
            $eventKey,
            $type,
            $ref,
            $action
        ));

        return true;
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'immediate' => 'Immediate → BM',
            'assign' => 'Assigned',
            'callback' => 'Callback',
            'task' => 'Task',
            'visit' => 'Visit',
            'pd01' => 'PD-01',
            'payout' => 'Payout hold',
            'target' => 'Target',
            'admin' => 'Admin',
            default => ucfirst($type),
        };
    }

    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            'immediate' => 'bg-danger',
            'assign' => 'bg-primary',
            'callback' => 'bg-info text-dark',
            'task' => 'bg-warning text-dark',
            'visit' => 'bg-teal',
            'pd01' => 'bg-success',
            'payout' => 'bg-warning text-dark',
            'target' => 'bg-secondary',
            'admin' => 'bg-dark',
            default => 'bg-light text-dark',
        };
    }

    /**
     * Infer category from legacy payloads that only stored event keys.
     */
    public static function inferType(array $data): string
    {
        $type = (string) ($data['type'] ?? '');
        if (in_array($type, self::TYPES, true)) {
            return $type;
        }

        $event = strtolower((string) ($data['event'] ?? ''));
        if (str_contains($event, 'lead-assign') || str_contains($event, 'assign')) {
            return 'assign';
        }
        if (str_contains($event, 'bq-') || str_contains($event, 'bank')) {
            return 'task';
        }
        if (str_contains($event, 'visit') || str_contains($event, 'followup') || str_contains($event, 'escalat')) {
            return 'visit';
        }
        if (str_contains($event, 'callback') || str_contains($event, 'vrm')) {
            return 'callback';
        }
        if (str_contains($event, 'payout') || str_contains($event, 'hold')) {
            return 'payout';
        }
        if (str_contains($event, 'pd01') || str_contains($event, 'disburs')) {
            return 'pd01';
        }
        if (str_contains($event, 'target')) {
            return 'target';
        }
        if (str_contains($event, 'immediate')) {
            return 'immediate';
        }
        if (str_contains($event, 'admin') || str_contains($event, 'perm')) {
            return 'admin';
        }

        return 'task';
    }

    public static function linkFromData(array $data): string
    {
        return (string) (
            $data['url']
            ?? $data['task_link']
            ?? $data['related_link']
            ?? $data['meeting_link']
            ?? ''
        );
    }

    public static function visitAssigned(GoalCaseTask $task, ?User $assignee = null): void
    {
        $case = $task->loanCase;
        $assignee = $assignee ?: $task->assignee;
        if (! $case || ! $assignee) {
            return;
        }

        $typeLabel = $task->typeLabel();
        $when = optional($task->due_at)->format('d M Y, h:i A') ?: 'unscheduled';

        self::send(
            $assignee,
            $typeLabel.' assigned to you',
            ($case->case_no ?: 'Case').' · '.($case->customer_name ?: 'Customer').' · '.$when,
            route('goal.visits.index', ['day' => optional($task->due_at)->format('Y-m-d'), 'cal' => optional($task->due_at)->format('Y-m')]),
            'visit-assign-'.$task->id.'-'.$assignee->id,
            'visit',
            $case->case_no,
            true
        );
    }

    /**
     * Notify BM / Cluster when a visit task escalates.
     */
    public static function visitEscalation(GoalCaseTask $task): void
    {
        $case = $task->relationLoaded('loanCase') ? $task->loanCase : $task->loanCase()->first();
        if (! $case || ! $task->escalated_level) {
            return;
        }

        $recipients = self::escalationRecipients($case, $task->escalated_level);
        $typeLabel = $task->typeLabel();
        $levelLabel = $task->escalationLabel();
        $url = route('goal.visits.index', ['tab' => 'overdue']);
        $eventKey = 'visit-escalation-'.$task->id.'-'.$task->escalated_level;

        foreach ($recipients as $user) {
            self::send(
                $user,
                $levelLabel,
                $typeLabel.' overdue · '.($case->case_no ?: 'Case').' · '.($case->customer_name ?: 'Customer'),
                $url,
                $eventKey,
                'visit',
                $case->case_no,
                true
            );
        }
    }

    /**
     * @return Collection<int, User>
     */
    public static function escalationRecipients(GoalLoanCase $case, string $level): Collection
    {
        $branchId = (int) ($case->goal_org_unit_id ?? 0);
        if (! $branchId) {
            return collect();
        }

        if ($level === 'cluster') {
            $branch = GoalOrgUnit::query()->find($branchId);
            $clusterId = $branch?->parent_id;
            $orgIds = array_values(array_filter([$branchId, $clusterId ? (int) $clusterId : null]));

            return User::query()
                ->whereIn('goal_org_unit_id', $orgIds)
                ->where(function ($q) {
                    $q->where('crm_role_type', 7)
                        ->orWhereIn('job_role', ['cluster', 'cluster_head', 'cluster-head']);
                })
                ->get();
        }

        // BM level
        return User::query()
            ->where('goal_org_unit_id', $branchId)
            ->where(function ($q) {
                $q->where('crm_role_type', 8)
                    ->orWhereIn('job_role', ['bm', 'branch_manager']);
            })
            ->get();
    }
}
