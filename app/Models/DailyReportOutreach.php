<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReportOutreach extends Model
{
    protected $fillable = [
        'daily_report_id',
        'outreach_type_id',
        'count',
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function outreachType()
    {
        return $this->belongsTo(OutreachType::class);
    }
}
