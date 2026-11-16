<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPayout extends Model
{
    protected $table = 'goal_payouts';

    protected $fillable = [
        'goal_loan_case_id', 'goal_disbursement_id', 'expected_amount',
        'received_amount', 'status', 'exception_reason',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function receipts()
    {
        return $this->hasMany(GoalPayoutReceipt::class, 'goal_payout_id');
    }

    public function disbursement()
    {
        return $this->belongsTo(GoalDisbursement::class, 'goal_disbursement_id');
    }
}
