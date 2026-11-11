<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAdLead extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CREATED = 'created';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_TEST = 'test';

    protected $fillable = [
        'platform',
        'social_ad_form_id',
        'external_id',
        'answers',
        'payload',
        'lead_id',
        'status',
        'error_message',
        'is_test',
        'campaign_name',
        'adset_name',
        'ad_name',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'payload' => 'array',
        'is_test' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(SocialAdForm::class, 'social_ad_form_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
