<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalLoanLeadAssignment extends Model
{
    protected $table = 'goal_loan_lead_assignments';

    protected $fillable = [
        'goal_loan_lead_id', 'from_user_id', 'to_user_id', 'assigned_by', 'action', 'reason',
    ];

    public function lead()
    {
        return $this->belongsTo(GoalLoanLead::class, 'goal_loan_lead_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
