<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Models excluded from automatic Eloquent audit logging
    |--------------------------------------------------------------------------
    */
    'exclude_models' => [
        App\Models\ActivityLog::class,
    ],
];
