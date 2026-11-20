<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalScheduledReportRun extends Model
{
    protected $fillable = [
        'goal_scheduled_report_id',
        'status',
        'started_at',
        'finished_at',
        'recipients_sent',
        'file_name',
        'error_message',
    ];

    protected $casts = [
        'recipients_sent' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(GoalScheduledReport::class, 'goal_scheduled_report_id');
    }
}
