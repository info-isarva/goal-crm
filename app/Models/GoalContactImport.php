<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalContactImport extends Model
{
    protected $table = 'goal_contact_imports';

    protected $fillable = [
        'imported_by',
        'goal_org_unit_id',
        'filename',
        'source',
        'total_rows',
        'success_count',
        'failed_count',
        'status',
        'default_lead_status',
        'assign_draft',
    ];

    protected $casts = [
        'assign_draft' => 'array',
    ];

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(GoalOrgUnit::class, 'goal_org_unit_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(GoalContactImportRow::class, 'goal_contact_import_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(GoalLoanLead::class, 'goal_contact_import_id');
    }

    public function unassignedLeads(): HasMany
    {
        return $this->leads()->whereNull('assigned_to');
    }
}
