<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalLoanCase extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'goal_loan_cases';

    protected $fillable = [
        'case_no', 'goal_loan_lead_id', 'goal_party_id', 'goal_org_unit_id', 'owner_id', 'status',
        'customer_name', 'mobile', 'product', 'loan_amount', 'lender_name', 'application_ref',
        'customer_details', 'bm_reco', 'collateral', 'existing_loans', 'branch_mis',
        'bank_query', 'bank_query_due', 'sanction_amount', 'sanction_date', 'rejection_reason',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'sanction_amount' => 'decimal:2',
        'customer_details' => 'array',
        'bm_reco' => 'array',
        'collateral' => 'array',
        'existing_loans' => 'array',
        'branch_mis' => 'array',
        'bank_query_due' => 'date',
        'sanction_date' => 'date',
    ];

    public function lead()
    {
        return $this->belongsTo(GoalLoanLead::class, 'goal_loan_lead_id');
    }

    public function party()
    {
        return $this->belongsTo(GoalCustomerParty::class, 'goal_party_id');
    }

    public function orgUnit()
    {
        return $this->belongsTo(GoalOrgUnit::class, 'goal_org_unit_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function disbursements()
    {
        return $this->hasMany(GoalDisbursement::class, 'goal_loan_case_id');
    }

    public function tasks()
    {
        return $this->hasMany(GoalCaseTask::class, 'goal_loan_case_id');
    }

    public function pmsItems()
    {
        return $this->hasMany(GoalPmsItem::class, 'goal_loan_case_id');
    }

    public function accountsItems()
    {
        return $this->hasMany(GoalAccountsItem::class, 'goal_loan_case_id');
    }

    public function payouts()
    {
        return $this->hasMany(GoalPayout::class, 'goal_loan_case_id');
    }

    public function bankApplications()
    {
        return $this->hasMany(GoalBankApplication::class, 'goal_loan_case_id');
    }

    public function bankQueries()
    {
        return $this->hasMany(GoalBankQuery::class, 'goal_loan_case_id');
    }

    public function sanctions()
    {
        return $this->hasMany(GoalSanction::class, 'goal_loan_case_id');
    }

    public function events()
    {
        return $this->hasMany(GoalCaseEvent::class, 'goal_loan_case_id');
    }

    /**
     * Aggregate case status rule (approved): case.status is authoritative for the
     * progress tracker and is advanced only via validated transitions. Lender
     * applications keep independent statuses. When deriving a summary badge for
     * multi-app cases, use furthest_non_rejected_application among apps; never
     * auto-mutate case.status from that derivation.
     */
    public function aggregateApplicationStatus(): ?string
    {
        $rank = [
            'Rejected' => 0,
            'Bank Login' => 1,
            'Bank Query' => 2,
            'Processing' => 3,
            'Sanctioned' => 4,
            'Disbursed' => 5,
        ];
        $best = null;
        $bestRank = -1;
        foreach ($this->bankApplications as $app) {
            if (($app->status ?? '') === 'Rejected') {
                continue;
            }
            $r = $rank[$app->status] ?? -1;
            if ($r > $bestRank) {
                $bestRank = $r;
                $best = $app->status;
            }
        }

        return $best;
    }
}
