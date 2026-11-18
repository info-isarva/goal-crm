<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run the task reminders processor every minute
        $schedule->command('task:process-reminders')->everyMinute()->withoutOverlapping();

        // Month-end: roll Approved target closing shortfall into next period (1st @ 01:10)
        $schedule->command('goal:carry-forward-targets')
            ->monthlyOn(1, '01:10')
            ->withoutOverlapping();

        // Bank query pre-due / overdue alerts (Spec §9)
        $schedule->command('goal:bank-query-reminders')
            ->dailyAt('09:15')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
