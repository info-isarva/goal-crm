<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPmsAssignmentHistory extends Model
{
    protected $table = 'goal_pms_assignment_history';

    protected $fillable = [
        'goal_pms_item_id',
        'previous_user_id',
        'new_user_id',
        'assigned_by',
        'reassignment_reason',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function pmsItem()
    {
        return $this->belongsTo(GoalPmsItem::class, 'goal_pms_item_id');
    }

    public function previousUser()
    {
        return $this->belongsTo(User::class, 'previous_user_id');
    }

    public function newUser()
    {
        return $this->belongsTo(User::class, 'new_user_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
