<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalTarget extends Model
{
    use HasFactory;

    protected $table = 'goal_targets';

    protected $fillable = [
        'period', 'metric', 'goal_org_unit_id', 'user_id',
        'base_target', 'opening_shortfall', 'adjustments', 'actual',
        'closing_shortfall', 'status',
    ];

    protected $casts = [
        'base_target' => 'decimal:2',
        'opening_shortfall' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'actual' => 'decimal:2',
        'closing_shortfall' => 'decimal:2',
    ];

    public function orgUnit()
    {
        return $this->belongsTo(GoalOrgUnit::class, 'goal_org_unit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function effectiveTarget(): float
    {
        return (float) $this->base_target + (float) $this->opening_shortfall + (float) $this->adjustments;
    }

    public function recalculateClosingShortfall(): void
    {
        $effective = $this->effectiveTarget();
        $this->closing_shortfall = max(0, $effective - (float) $this->actual);
    }
}
