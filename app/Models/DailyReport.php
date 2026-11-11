<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'report_date',
        'other_activity',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function isSubmitted(): bool
    {
        return ($this->status ?? 'submitted') === 'submitted';
    }

    public function isDraft(): bool
    {
        return ($this->status ?? 'submitted') === 'draft';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function outreaches()
    {
        return $this->hasMany(DailyReportOutreach::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
