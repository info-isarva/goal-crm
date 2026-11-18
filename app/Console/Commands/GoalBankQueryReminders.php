<?php

namespace App\Console\Commands;

use App\Models\GoalBankQuery;
use App\Models\User;
use App\Notifications\GoalCaseAlert;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GoalBankQueryReminders extends Command
{
    protected $signature = 'goal:bank-query-reminders
                            {--dry-run : List alerts without notifying}';

    protected $description = 'Notify owners for bank queries due soon or overdue (Spec §9 SLA).';

    public function handle(): int
    {
        $preDueDays = (int) config('goal.bank_query_predue_days', 1);
        $dry = (bool) $this->option('dry-run');
        $today = Carbon::today();
        $predueUntil = $today->copy()->addDays(max(0, $preDueDays));

        $open = GoalBankQuery::query()
            ->with(['loanCase', 'responsible'])
            ->whereIn('status', ['Open', 'In Progress', 'Pending'])
            ->whereNotNull('due_at')
            ->get();

        $sent = 0;
        foreach ($open as $query) {
            $due = $query->due_at?->copy()?->startOfDay();
            if (! $due) {
                continue;
            }
            $overdue = $due->lt($today);
            $predue = ! $overdue && $due->lte($predueUntil);
            if (! $overdue && ! $predue) {
                continue;
            }

            $case = $query->loanCase;
            if (! $case) {
                continue;
            }

            $title = $overdue
                ? 'Bank query overdue'
                : 'Bank query due soon';
            $message = ($query->query_ref ?: 'Query').' · Case '.($case->case_no ?: $case->id)
                .' · due '.$due->format('d M Y');
            $url = route('goal.cases.show', $case);
            $eventKey = ($overdue ? 'bq-overdue-' : 'bq-predue-').$query->id.'-'.$due->toDateString();

            $recipients = collect();
            if ($query->responsible) {
                $recipients->push($query->responsible);
            }
            // Escalate to BM / SM / JSM on same branch when overdue.
            if ($overdue && $case->goal_org_unit_id) {
                $managers = User::query()
                    ->where('goal_org_unit_id', $case->goal_org_unit_id)
                    ->where(function ($q) {
                        $q->whereIn('crm_role_type', [8, 10, 11, 12])
                            ->orWhereIn('job_role', ['bm', 'branch_manager', 'jsm', 'sm', 'ssm']);
                    })
                    ->get();
                $recipients = $recipients->concat($managers);
            }

            foreach ($recipients->unique('id') as $user) {
                if ($dry) {
                    $this->line(($overdue ? 'OVERDUE' : 'PREDUE')." → {$user->email} · {$message}");
                    $sent++;
                    continue;
                }
                $exists = $user->notifications()
                    ->whereNull('read_at')
                    ->where('type', GoalCaseAlert::class)
                    ->where('data->event', $eventKey)
                    ->where('created_at', '>=', now()->subHours(20))
                    ->exists();
                if ($exists) {
                    continue;
                }
                $user->notify(new GoalCaseAlert($title, $message, $url, $eventKey));
                $sent++;
            }
        }

        $this->info(($dry ? '[dry-run] ' : '')."Bank query reminders processed. notifications={$sent}");

        return self::SUCCESS;
    }
}
