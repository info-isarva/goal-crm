<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DebugNotifications extends Command
{
    protected $signature = 'debug:notifications {--user=}';
    protected $description = 'Show info about task reminders, notifications and queued jobs for debugging.';

    public function handle()
    {
        $now = Carbon::now();

        $this->info("Now: {$now}");

        $pending = DB::table('task_reminders')
            ->where('remind_at', '<=', $now)
            ->where(function ($q) {
                $q->where('email_sent', 0)->orWhere('notification_sent', 0);
            })
            ->count();

        $this->info("Pending reminders (due & not sent): {$pending}");

        $totalReminders = DB::table('task_reminders')->count();
        $this->info("Total reminders: {$totalReminders}");

        $jobs = DB::table('jobs')->count();
        $this->info("Total queued jobs (jobs table): {$jobs}");

        $failed = DB::table('failed_jobs')->count();
        $this->info("Failed jobs: {$failed}");

        $this->info('---- Recent notifications ----');
        $notes = DB::table('notifications')->orderBy('created_at','desc')->limit(15)->get();
        foreach ($notes as $n) {
            $this->line("[notif] id={$n->id} notifiable_type={$n->notifiable_type} notifiable_id={$n->notifiable_id} read_at={$n->read_at} created_at={$n->created_at}");
            $this->line('   data=' . substr($n->data, 0, 200));
        }

        $userId = $this->option('user');
        if ($userId) {
            $this->info("---- For user {$userId} ----");
            $unread = DB::table('notifications')->where('notifiable_type','App\\Models\\User')->where('notifiable_id', $userId)->whereNull('read_at')->count();
            $this->info("Unread notifications for user {$userId}: {$unread}");
        }

        return 0;
    }
}
