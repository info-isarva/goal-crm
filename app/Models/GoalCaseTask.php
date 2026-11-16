<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalCaseTask extends Model
{
    protected $table = 'goal_case_tasks';

    protected $fillable = [
        'goal_loan_case_id', 'title', 'task_type', 'status',
        'due_at', 'assigned_to', 'created_by', 'notes',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
