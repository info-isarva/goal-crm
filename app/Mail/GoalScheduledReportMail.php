<?php

namespace App\Mail;

use App\Models\GoalScheduledReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GoalScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GoalScheduledReport $schedule,
        public array $dash,
        public string $attachmentPath,
        public string $attachmentName,
        public string $attachmentMime
    ) {}

    public function build()
    {
        return $this->subject('Scheduled Report: '.$this->schedule->name)
            ->view('emails.goal-scheduled-report')
            ->with([
                'schedule' => $this->schedule,
                'dash' => $this->dash,
                'scopeLabel' => $this->dash['scope_label'] ?? '—',
            ])
            ->attach($this->attachmentPath, [
                'as' => $this->attachmentName,
                'mime' => $this->attachmentMime,
            ]);
    }
}
