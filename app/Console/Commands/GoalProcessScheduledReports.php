<?php

namespace App\Console\Commands;

use App\Services\Goal\GoalScheduledReportService;
use Illuminate\Console\Command;

class GoalProcessScheduledReports extends Command
{
    protected $signature = 'goal:process-scheduled-reports {--limit=25 : Max schedules to process}';

    protected $description = 'Generate and email due Goal scheduled analytics reports';

    public function handle(GoalScheduledReportService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $count = $service->processDue($limit);
        $this->info("Processed {$count} scheduled report(s).");

        return self::SUCCESS;
    }
}
