<?php

namespace App\Console\Commands;

use App\Models\GoalTarget;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GoalCarryForwardTargets extends Command
{
    protected $signature = 'goal:carry-forward-targets
                            {--period= : Source period YYYY-MM (defaults to previous month)}
                            {--dry-run : Show actions without writing}';

    protected $description = 'Roll closing shortfall from Approved targets into next-period opening shortfall (Spec TGT carry-forward).';

    public function handle(): int
    {
        $period = $this->option('period') ?: Carbon::now()->subMonthNoOverflow()->format('Y-m');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            $this->error('Invalid --period. Use YYYY-MM.');

            return self::FAILURE;
        }

        $nextPeriod = Carbon::createFromFormat('Y-m', $period)->addMonthNoOverflow()->format('Y-m');
        $dry = (bool) $this->option('dry-run');

        $sources = GoalTarget::query()
            ->where('period', $period)
            ->where('status', 'Approved')
            ->where('closing_shortfall', '>', 0)
            ->get();

        if ($sources->isEmpty()) {
            $this->info("No Approved targets with closing shortfall for {$period}.");

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($sources, $nextPeriod, $dry, &$created, &$updated, &$skipped) {
            foreach ($sources as $src) {
                $src->recalculateClosingShortfall();
                $shortfall = (float) $src->closing_shortfall;
                if ($shortfall <= 0) {
                    $skipped++;
                    continue;
                }

                $existing = GoalTarget::query()
                    ->where('period', $nextPeriod)
                    ->where('metric', $src->metric)
                    ->where('goal_org_unit_id', $src->goal_org_unit_id)
                    ->where('user_id', $src->user_id)
                    ->first();

                if ($existing) {
                    // Idempotent: only fill opening when still zero / untouched draft
                    if ((float) $existing->opening_shortfall <= 0 && $existing->status === 'Draft') {
                        if (! $dry) {
                            $existing->opening_shortfall = $shortfall;
                            $existing->recalculateClosingShortfall();
                            $existing->save();
                        }
                        $updated++;
                        $this->line("Update opening SF {$nextPeriod} #{$existing->id} ← {$shortfall}");
                    } else {
                        $skipped++;
                        $this->line("Skip existing {$nextPeriod} #{$existing->id} (opening already set or Approved)");
                    }
                    continue;
                }

                if (! $dry) {
                    $target = new GoalTarget([
                        'period' => $nextPeriod,
                        'metric' => $src->metric,
                        'goal_org_unit_id' => $src->goal_org_unit_id,
                        'user_id' => $src->user_id,
                        'base_target' => $src->base_target,
                        'opening_shortfall' => $shortfall,
                        'adjustments' => 0,
                        'actual' => 0,
                        'status' => 'Draft',
                    ]);
                    $target->recalculateClosingShortfall();
                    $target->save();
                }
                $created++;
                $this->line("Create {$nextPeriod} draft from #{$src->id} opening SF {$shortfall}");
            }
        });

        $this->info(($dry ? '[dry-run] ' : '')."Done. created={$created} updated={$updated} skipped={$skipped}");

        return self::SUCCESS;
    }
}