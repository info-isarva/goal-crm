<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalBankQuery extends Model
{
    use SoftDeletes;

    protected $table = 'goal_bank_queries';

    protected $fillable = [
        'query_ref', 'goal_loan_case_id', 'goal_bank_application_id', 'details',
        'responsible_user_id', 'raised_at', 'due_at', 'status', 'blocks_processing',
        'resolution_remarks', 'resolution_evidence_path', 'resolved_by', 'resolved_at', 'created_by',
    ];

    protected $casts = [
        'raised_at' => 'datetime',
        'due_at' => 'date',
        'resolved_at' => 'datetime',
        'blocks_processing' => 'boolean',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function application()
    {
        return $this->belongsTo(GoalBankApplication::class, 'goal_bank_application_id');
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayStatus(): string
    {
        if (in_array($this->status, ['Resolved'], true)) {
            return 'Resolved';
        }
        if ($this->due_at && $this->due_at->isPast()) {
            return 'Overdue';
        }
        return $this->status ?: 'Open';
    }
}
