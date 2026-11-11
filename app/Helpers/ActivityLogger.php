<?php
namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log($params = [])
    {
        ActivityLog::create([
            'user_id'      => $params['user_id'] ?? (auth()->check() ? auth()->id() : null),
            'type'         => $params['type'] ?? null,
            'module'       => $params['module'] ?? null,
            'action'       => $params['action'] ?? null,
            'ip_address'   => $params['ip_address'] ?? request()->ip(),
            'device'       => $params['device'] ?? request()->header('User-Agent'),
            'details'      => $params['details'] ?? null,
            'subject_type' => $params['subject_type'] ?? null, // Model path
            'subject_id'   => $params['subject_id'] ?? null,   // Record id
        ]);
    }
}
