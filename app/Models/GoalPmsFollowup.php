<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPmsFollowup extends Model
{
    public const TYPES = [
        'emi_confirmation' => 'EMI Confirmation',
        'customer_welcome' => 'Customer Welcome',
        'loan_account_activation' => 'Loan Account Activation',
        'portfolio_review' => 'Portfolio Review',
        'customer_service' => 'Customer Service Follow-up',
        'other' => 'Other',
    ];

    public const PRIORITIES = ['Low', 'Medium', 'High'];

    protected $table = 'goal_pms_followups';

    protected $fillable = [
        'goal_pms_item_id',
        'goal_loan_case_id',
        'followup_type',
        'subject',
        'description',
        'scheduled_date',
        'scheduled_time',
        'assigned_to',
        'status',
        'priority',
        'completed_at',
        'completed_by',
        'completion_remarks',
        'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function pmsItem()
    {
        return $this->belongsTo(GoalPmsItem::class, 'goal_pms_item_id');
    }

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->followup_type] ?? (string) $this->followup_type;
    }

    public function dueAt()
    {
        $date = $this->scheduled_date?->format('Y-m-d') ?: null;
        if (! $date) {
            return null;
        }
        $time = $this->scheduled_time ? substr((string) $this->scheduled_time, 0, 8) : '23:59:59';

        return \Carbon\Carbon::parse($date.' '.$time);
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['Completed', 'Cancelled'], true)) {
            return false;
        }
        $due = $this->dueAt();

        return $due ? $due->lt(now()) : false;
    }

    public function displayStatus(): string
    {
        if ($this->isOverdue()) {
            return 'Overdue';
        }

        return (string) $this->status;
    }
}
