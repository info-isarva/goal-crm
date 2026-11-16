<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalSanction extends Model
{
    use SoftDeletes;

    protected $table = 'goal_sanctions';

    protected $fillable = [
        'goal_loan_case_id', 'goal_bank_application_id', 'sanction_amount', 'sanction_date',
        'validity_until', 'status', 'revision', 'terms', 'evidence_path', 'evidence_name',
        'recorded_by', 'change_reason',
    ];

    protected $casts = [
        'sanction_amount' => 'decimal:2',
        'sanction_date' => 'date',
        'validity_until' => 'date',
    ];

    public function loanCase()
    {
        return $this->belongsTo(GoalLoanCase::class, 'goal_loan_case_id');
    }

    public function application()
    {
        return $this->belongsTo(GoalBankApplication::class, 'goal_bank_application_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
