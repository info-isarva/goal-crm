<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalLoanLead extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'goal_loan_leads';

    protected $fillable = [
        'ref_no', 'customer_name', 'mobile', 'email', 'source', 'channel_partner',
        'product', 'loan_amount', 'lead_type', 'status', 'disposition', 'follow_up_at',
        'notes', 'consent_recorded', 'goal_org_unit_id', 'goal_party_id', 'goal_contact_import_id',
        'created_by', 'assigned_to', 'assigned_by', 'assigned_at', 'closure_reason',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'follow_up_at' => 'datetime',
        'assigned_at' => 'datetime',
        'consent_recorded' => 'boolean',
    ];

    public function orgUnit()
    {
        return $this->belongsTo(GoalOrgUnit::class, 'goal_org_unit_id');
    }

    public function party()
    {
        return $this->belongsTo(GoalCustomerParty::class, 'goal_party_id');
    }

    public function contactImport()
    {
        return $this->belongsTo(GoalContactImport::class, 'goal_contact_import_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(GoalLoanLeadAssignment::class, 'goal_loan_lead_id');
    }

    public function loanCase()
    {
        return $this->hasOne(GoalLoanCase::class, 'goal_loan_lead_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function vrmCalls()
    {
        return $this->hasMany(GoalVrmCallLog::class, 'goal_loan_lead_id');
    }

    public function events()
    {
        return $this->hasMany(GoalLoanLeadEvent::class, 'goal_loan_lead_id');
    }
}
