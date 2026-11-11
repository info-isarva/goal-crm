<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAdAccount extends Model
{
    protected $fillable = [
        'platform',
        'external_id',
        'name',
        'access_token',
        'token_expires_at',
        'is_subscribed',
        'meta',
        'last_synced_at',
        'last_error',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'is_subscribed' => 'boolean',
        'meta' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = ['access_token'];

    public function forms()
    {
        return $this->hasMany(SocialAdForm::class);
    }
}
