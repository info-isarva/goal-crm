<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
         $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'compliance.consent' => \App\Http\Middleware\EnsureComplianceConsent::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webforms/*',
            'social-ads/webhook/*',
        ]);
        // Log out other devices when the password hash stored in the session no longer matches.
        $middleware->authenticateSessions();
        // Ensure a financial year exists and is selected for authenticated Goal/CRM pages
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureFinancialYearSelected::class);
        // Audit trail: log authenticated mutating requests (and export/download GETs)
        $middleware->appendToGroup('web', \App\Http\Middleware\LogUserActions::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\LogUserActions::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
