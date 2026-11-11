<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('social-ads:sync')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('whatsapp-quotations:cleanup')->hourly();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('whatsapp-quotations:cleanup {--hours=24}', function () {
    $cutoff = now()->subHours(max(1, (int) $this->option('hours')))->getTimestamp();
    $removed = 0;

    foreach ([storage_path('app/public/whatsapp-quotations'), public_path('whatsapp-quotations')] as $dir) {
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.pdf') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff && @unlink($file)) {
                $removed++;
            }
        }
    }

    $this->info("Removed {$removed} WhatsApp quotation PDF(s).");
})->purpose('Delete WhatsApp quotation PDFs older than the given number of hours');
