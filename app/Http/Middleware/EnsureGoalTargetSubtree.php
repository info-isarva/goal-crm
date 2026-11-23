<?php

namespace App\Http\Middleware;

use App\Services\Goal\GoalSetTargetsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side permission rule for Set Targets writes:
 * actor may only touch employee IDs in their reporting subtree (never self).
 */
class EnsureGoalTargetSubtree
{
    public function __construct(private GoalSetTargetsService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $ids = [];
        if ($request->filled('employee_id')) {
            $ids[] = (int) $request->input('employee_id');
        }
        foreach ((array) $request->input('rows', []) as $row) {
            if (isset($row['employee_id'])) {
                $ids[] = (int) $row['employee_id'];
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));

        foreach ($ids as $employeeId) {
            $this->service->assertCanEdit($user, $employeeId);
        }

        if ($request->route('target')) {
            $target = $request->route('target');
            $eid = (int) ($target->user_id ?? 0);
            if ($eid > 0) {
                $this->service->assertCanEdit($user, $eid);
            } else {
                throw ValidationException::withMessages([
                    'target' => 'Only employee targets can be revised via this flow.',
                ]);
            }
        }

        return $next($request);
    }
}
