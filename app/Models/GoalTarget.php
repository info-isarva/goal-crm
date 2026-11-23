<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalTarget extends Model
{
    use HasFactory;

    protected $table = 'goal_targets';

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_SUBMITTED = 'Submitted';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_LOCKED = 'Locked';

    protected $fillable = [
        'period', 'metric', 'goal_org_unit_id', 'user_id',
        'base_target', 'owner_target', 'opening_shortfall', 'adjustments', 'actual',
        'closing_shortfall', 'status', 'row_source', 'set_by', 'approved_by',
        'assigned_at', 'revision_reason',
    ];

    protected $casts = [
        'base_target' => 'decimal:2',
        'owner_target' => 'decimal:2',
        'opening_shortfall' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'actual' => 'decimal:2',
        'closing_shortfall' => 'decimal:2',
        'assigned_at' => 'datetime',
    ];

    public function orgUnit()
    {
        return $this->belongsTo(GoalOrgUnit::class, 'goal_org_unit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setByUser()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function effectiveTarget(): float
    {
        return (float) $this->base_target + (float) $this->opening_shortfall + (float) $this->adjustments;
    }

    public function teamTotal(): float
    {
        return max(0, (float) $this->base_target - (float) $this->owner_target);
    }

    public function isLocked(): bool
    {
        return in_array((string) $this->status, [self::STATUS_APPROVED, self::STATUS_LOCKED], true);
    }

    public function recalculateClosingShortfall(): void
    {
        $effective = $this->effectiveTarget();
        $this->closing_shortfall = max(0, $effective - (float) $this->actual);
    }
}
