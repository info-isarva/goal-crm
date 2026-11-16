<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalAccountsItem extends Model
{
    protected $table = 'goal_accounts_items';

    protected $fillable = [
        'goal_loan_case_id', 'goal_disbursement_id', 'status',
        'report_number', 'updated_by',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }
}
