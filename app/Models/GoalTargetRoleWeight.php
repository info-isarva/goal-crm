<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalTargetRoleWeight extends Model
{
    protected $table = 'goal_target_role_weights';

    protected $fillable = [
        'job_role',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'decimal:4',
        'is_active' => 'boolean',
    ];
}
