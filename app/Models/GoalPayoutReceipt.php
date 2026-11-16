<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPayoutReceipt extends Model
{
    protected $table = 'goal_payout_receipts';

    protected $fillable = [
        'goal_payout_id', 'amount', 'received_on', 'reference', 'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_on' => 'date',
    ];

    public function payout()
    {
        return $this->belongsTo(GoalPayout::class, 'goal_payout_id');
    }
}
