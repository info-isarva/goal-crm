<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalVrmCallLog extends Model
{
    use SoftDeletes;

    protected $table = 'goal_vrm_call_logs';

    protected $fillable = [
        'user_id', 'goal_loan_lead_id', 'customer_name', 'mobile',
        'disposition', 'called_at', 'callback_at', 'notes',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'callback_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const DISPOSITIONS = [
        'Interested',
        'Not Interested',
        'No Answer',
        'Busy',
        'Callback',
        'Invalid Number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lead()
    {
        return $this->belongsTo(GoalLoanLead::class, 'goal_loan_lead_id');
    }
}
