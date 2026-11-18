<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalRevenueEntry extends Model
{
    protected $table = 'goal_revenue_entries';

    protected $fillable = [
        'goal_loan_case_id', 'goal_disbursement_id', 'amount', 'entry_type',
        'attributed_user_id', 'goal_org_unit_id', 'note', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function disbursement()
    {
        return $this->belongsTo(GoalDisbursement::class, 'goal_disbursement_id');
    }

    public function orgUnit()
    {
        return $this->belongsTo(GoalOrgUnit::class, 'goal_org_unit_id');
    }
}
