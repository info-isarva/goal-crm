<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalVrmCallLog extends Model
{
    use SoftDeletes;

    protected $table = 'goal_vrm_call_logs';

    protected $fillable = [
        'user_id', 'goal_loan_lead_id', 'goal_org_unit_id', 'customer_name', 'mobile',
        'disposition', 'interest_level', 'duration_seconds', 'called_at', 'callback_at', 'notes',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'callback_at' => 'datetime',
        'deleted_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    /** @return list<string> */
    public static function allowedInterestLevels(): array
    {
        return ['Hot', 'Warm', 'Cold'];
    }

    /** @deprecated Prefer GoalVrmDisposition::activeNames() — kept as seed/fallback list */
    public const DISPOSITIONS = [
        'Interested',
        'Not Interested',
        'No Answer',
        'Busy',
        'Callback',
        'Invalid Number',
    ];

    /**
     * @return list<string>
     */
    public static function allowedDispositionNames(): array
    {
        $fromMaster = GoalVrmDisposition::activeNames();
        if ($fromMaster !== []) {
            return $fromMaster;
        }

        return self::DISPOSITIONS;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lead()
    {
        return $this->belongsTo(GoalLoanLead::class, 'goal_loan_lead_id');
    }

    public function orgUnit()
    {
        return $this->belongsTo(GoalOrgUnit::class, 'goal_org_unit_id');
    }

    public function resolvedBranchName(): ?string
    {
        return optional($this->orgUnit)->name
            ?: optional(optional($this->lead)->orgUnit)->name
            ?: optional(optional($this->user)->orgUnit)->name;
    }
}
