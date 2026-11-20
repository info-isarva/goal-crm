<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalAccountsItem extends Model
{
    protected $table = 'goal_accounts_items';

    protected $fillable = [
        'goal_loan_case_id', 'goal_disbursement_id', 'status',
        'report_number', 'report_date', 'report_document_path', 'remarks',
        'submitted_by', 'submitted_for_approval_at',
        'updated_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'submitted_for_approval_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function disbursement()
    {
        return $this->belongsTo(GoalDisbursement::class, 'goal_disbursement_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payout()
    {
        return $this->hasOne(GoalPayout::class, 'goal_disbursement_id', 'goal_disbursement_id');
    }

    public function hasReportNumber(): bool
    {
        return filled($this->report_number);
    }

    public function isApproved(): bool
    {
        return $this->status === 'Approved' && $this->hasReportNumber();
    }
}
