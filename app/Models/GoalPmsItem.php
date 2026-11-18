<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPmsItem extends Model
{
    protected $table = 'goal_pms_items';

    protected $fillable = [
        'goal_loan_case_id', 'goal_disbursement_id', 'status',
        'notes', 'follow_up_at', 'activity_type', 'referral_org_unit_id',
        'verified_by', 'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'follow_up_at' => 'datetime',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function referralOrgUnit()
    {
        return $this->belongsTo(GoalOrgUnit::class, 'referral_org_unit_id');
    }
}
