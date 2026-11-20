<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPayoutAdjustment extends Model
{
    protected $table = 'goal_payout_adjustments';

    protected $fillable = [
        'goal_payout_id', 'amount', 'reason', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payout()
    {
        return $this->belongsTo(GoalPayout::class, 'goal_payout_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
