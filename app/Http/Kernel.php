<?php
// Register the Require2FA middleware in the HTTP Kernel

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        // ...existing code...
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            // ...existing middleware...
            \App\Http\Middleware\CheckUserStatus::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\NoCache::class
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // existing middleware
        'auth' => \App\Http\Middleware\Authenticate::class,
        // your 2FA middleware
        'require2fa' => \App\Http\Middleware\RequireTwoFA::class,
        // Prevent modifications when a historical financial year is selected
        'prevent.historical.fy' => \App\Http\Middleware\PreventHistoricalFinancialYear::class,
        'compliance.consent' => \App\Http\Middleware\EnsureComplianceConsent::class,
    ];
}
