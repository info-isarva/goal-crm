<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalBankApplication extends Model
{
    use SoftDeletes;

    protected $table = 'goal_bank_applications';

    protected $fillable = [
        'goal_loan_case_id', 'lender_name', 'application_ref', 'login_date', 'status',
        'requested_amount', 'follow_up_at', 'banker_name', 'banker_contact', 'banker_email',
        'evidence_path', 'evidence_name', 'created_by', 'notes',
    ];

    protected $casts = [
        'login_date' => 'date',
        'follow_up_at' => 'date',
        'requested_amount' => 'decimal:2',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function queries()
    {
        return $this->hasMany(GoalBankQuery::class, 'goal_bank_application_id');
    }

    public function sanctions()
    {
        return $this->hasMany(GoalSanction::class, 'goal_bank_application_id');
    }

    public function disbursements()
    {
        return $this->hasMany(GoalDisbursement::class, 'goal_bank_application_id');
    }

    public function activeSanction()
    {
        return $this->hasOne(GoalSanction::class, 'goal_bank_application_id')
            ->where('status', 'Active')
            ->latestOfMany();
    }
}
