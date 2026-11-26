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
        'campaign_name',
        'description',
        'lead_source',
        'source',
        'total_rows',
        'success_count',
        'failed_count',
        'status',
        'default_lead_status',
        'assign_method',
        'assigned_label',
        'assigned_at',
        'assign_draft',
    ];

    protected $casts = [
        'assign_draft' => 'array',
        'assigned_at' => 'datetime',
    ];

    public function displayName(): string
    {
        $name = trim((string) ($this->campaign_name ?? ''));

        return $name !== '' ? $name : (string) $this->filename;
    }

    public function campaignStatusLabel(int $unassignedCount = 0): string
    {
        if ($this->status === 'processing') {
            return 'Draft';
        }
        if ($this->status === 'failed') {
            return 'Failed';
        }
        if ($this->status === 'assigned' || ((int) $this->success_count > 0 && $unassignedCount === 0)) {
            return 'Completed';
        }
        if ((int) $this->success_count > 0) {
            return 'Active';
        }

        return 'Draft';
    }

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
