<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalTargetAuditLog extends Model
{
    protected $table = 'goal_target_audit_logs';

    protected $fillable = [
        'goal_target_id',
        'employee_id',
        'period',
        'metric',
        'changed_by',
        'field',
        'old_value',
        'new_value',
        'action',
        'meta',
    ];

    protected $casts = [
        'old_value' => 'decimal:2',
        'new_value' => 'decimal:2',
        'meta' => 'array',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(GoalTarget::class, 'goal_target_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
