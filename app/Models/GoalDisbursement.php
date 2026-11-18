<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalDisbursement extends Model
{
    use HasFactory;

    protected $table = 'goal_disbursements';

    protected $fillable = [
        'goal_loan_case_id', 'goal_bank_application_id', 'goal_sanction_id',
        'amount', 'disbursed_on', 'bank_reference', 'status',
        'evidence_note', 'verified_by', 'verified_at', 'idempotency_key', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'disbursed_on' => 'date',
        'verified_at' => 'datetime',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function application()
    {
        return $this->belongsTo(GoalBankApplication::class, 'goal_bank_application_id');
    }

    public function sanction()
    {
        return $this->belongsTo(GoalSanction::class, 'goal_sanction_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function revenueEntry()
    {
        return $this->hasOne(GoalRevenueEntry::class, 'goal_disbursement_id');
    }

    public function pmsItem()
    {
        return $this->hasOne(GoalPmsItem::class, 'goal_disbursement_id');
    }

    public function accountsItem()
    {
        return $this->hasOne(GoalAccountsItem::class, 'goal_disbursement_id');
    }

    public function payout()
    {
        return $this->hasOne(GoalPayout::class, 'goal_disbursement_id');
    }
}
