<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPmsItem extends Model
{
    public const STATUSES = [
        'Pending Verification',
        'In Progress',
        'Verified',
        'Rejected',
        'On Hold',
        'Follow-up Due',
        'Closed',
    ];

    public const PENDING_STATUSES = ['Pending', 'Pending Verification', 'In Progress', 'On Hold', 'Rejected'];

    protected $table = 'goal_pms_items';

    protected $fillable = [
        'goal_loan_case_id', 'goal_disbursement_id', 'status',
        'assigned_to', 'verification_started_at', 'verification_remarks',
        'notes', 'follow_up_at', 'activity_type', 'referral_org_unit_id',
        'verified_by', 'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'verification_started_at' => 'datetime',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function disbursement()
    {
        return $this->belongsTo(GoalDisbursement::class, 'goal_disbursement_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function referralOrgUnit()
    {
        return $this->belongsTo(GoalOrgUnit::class, 'referral_org_unit_id');
    }

    public function verifications()
    {
        return $this->hasMany(GoalPmsVerification::class, 'goal_pms_item_id')->latest('id');
    }

    public function followups()
    {
        return $this->hasMany(GoalPmsFollowup::class, 'goal_pms_item_id')->latest('id');
    }

    public function assignmentHistory()
    {
        return $this->hasMany(GoalPmsAssignmentHistory::class, 'goal_pms_item_id')->latest('assigned_at');
    }

    public function activityLogs()
    {
        return $this->hasMany(GoalPmsActivityLog::class, 'goal_pms_item_id')->latest('created_at');
    }

    public function statusLabel(): string
    {
        return $this->status === 'Pending' ? 'Pending Verification' : (string) $this->status;
    }

    public function isPendingVerification(): bool
    {
        return in_array($this->status, ['Pending', 'Pending Verification'], true);
    }

    public function canStartVerification(): bool
    {
        return in_array($this->status, ['Pending', 'Pending Verification', 'Rejected', 'On Hold'], true);
    }

    public function canCompleteVerification(): bool
    {
        return in_array($this->status, ['Pending', 'Pending Verification', 'In Progress', 'On Hold', 'Rejected'], true);
    }
}
