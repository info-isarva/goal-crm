<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalLoanLeadEvent extends Model
{
    protected $table = 'goal_loan_lead_events';

    protected $fillable = [
        'goal_loan_lead_id',
        'user_id',
        'event_type',
        'title',
        'body',
        'from_status',
        'to_status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(GoalLoanLead::class, 'goal_loan_lead_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function record(
        GoalLoanLead $lead,
        string $type,
        string $title,
        ?string $body = null,
        array $extra = []
    ): self {
        return self::create([
            'goal_loan_lead_id' => $lead->id,
            'user_id' => $extra['user_id'] ?? auth()->id(),
            'event_type' => $type,
            'title' => $title,
            'body' => $body,
            'from_status' => $extra['from_status'] ?? null,
            'to_status' => $extra['to_status'] ?? null,
            'meta' => $extra['meta'] ?? null,
        ]);
    }
}
