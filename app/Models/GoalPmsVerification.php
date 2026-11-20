<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPmsVerification extends Model
{
    protected $table = 'goal_pms_verifications';

    protected $fillable = [
        'goal_pms_item_id',
        'verification_result',
        'verification_remarks',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function pmsItem()
    {
        return $this->belongsTo(GoalPmsItem::class, 'goal_pms_item_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
