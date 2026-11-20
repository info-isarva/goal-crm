<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPmsActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'goal_pms_activity_logs';

    protected $fillable = [
        'goal_pms_item_id',
        'goal_loan_case_id',
        'user_id',
        'activity_type',
        'description',
        'old_value',
        'new_value',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function pmsItem()
    {
        return $this->belongsTo(GoalPmsItem::class, 'goal_pms_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }
}
