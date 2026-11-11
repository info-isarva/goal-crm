<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventHistoricalFinancialYear
{
    /**
     * Handle an incoming request.
     * If a non-active (historical) financial year is selected, block create/edit/delete actions.
     * Returns JSON for AJAX requests, otherwise redirects back with an error message.
     */
    public function handle(Request $request, Closure $next)
    {
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                $message = 'Selected financial year is closed. Creating or modifying records is disabled for historical years.';
                // If AJAX or expecting JSON, return JSON response
                if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json(['success' => false, 'message' => $message], 403);
                }
                // Otherwise redirect back with flash message
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
