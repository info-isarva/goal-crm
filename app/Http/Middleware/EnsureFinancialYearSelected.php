<?php

namespace App\Http\Middleware;

use App\Services\FinancialYearService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinancialYearSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            try {
                app(FinancialYearService::class)->bootstrapSession();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }
}
