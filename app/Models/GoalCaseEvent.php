<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalCaseEvent extends Model
{
    protected $table = 'goal_case_events';

    protected $fillable = [
        'goal_loan_case_id', 'user_id', 'event_type', 'title', 'body',
        'from_value', 'to_value', 'related_type', 'related_id', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
