<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAnalyticsDashboard extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'widgets',
    ];

    protected function casts(): array
    {
        return [
            'widgets' => 'array',
        ];
    }
}
