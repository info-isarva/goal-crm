<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebFormSubmission extends Model
{
    protected $fillable = [
        'web_form_id',
        'lead_id',
        'payload',
        'status',
        'error_message',
        'ip_address',
        'user_agent',
        'page_url',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function webForm()
    {
        return $this->belongsTo(WebForm::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
