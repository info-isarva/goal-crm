<?php

namespace App\Http\Controllers\Goal\Concerns;

use App\Models\GoalLoanCase;
use App\Services\Goal\GoalWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait AuthorizesGoal
{
    protected function requireGoalPermission(string $guard): void
    {
        if (! auth()->user()->hasCrmPermission($guard)) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Block sales/workspace mutations once the case is Disbursed / PMS / Rejected / Closed.
     * Admins with manage_permissions_guard may override.
     */
    protected function assertCaseWorkspaceUnlocked(GoalLoanCase $case, ?string $cwTab = null): RedirectResponse|JsonResponse|null
    {
        if (! GoalWorkflow::isWorkspaceLocked($case->status)) {
            return null;
        }
        if (auth()->user()?->hasCrmPermission('manage_permissions_guard')) {
            return null;
        }

        $message = GoalWorkflow::workspaceLockMessage($case->status);
        $request = request();
        if ($request instanceof Request && ($request->expectsJson() || $request->ajax())) {
            return response()->json([
                'message' => $message,
                'errors' => ['workspace' => [$message]],
            ], 423);
        }

        return back()
            ->withInput()
            ->withErrors(['workspace' => $message])
            ->with('cw_tab', $cwTab ?: 'overview');
    }

    protected function scopedOrgIds(): array
    {
        return auth()->user()->goalScopedOrgUnitIds();
    }

    /**
     * Restrict a query that relates to a loan case.
     * Empty scope (no org unit, non-global) ⇒ no rows — never unfiltered "see all".
     */
    protected function scopeByLoanCase(Builder $query, string $relation = 'loanCase'): Builder
    {
        $scope = $this->scopedOrgIds();
        if ($scope === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas($relation, function (Builder $q) use ($scope) {
            $q->whereIn('goal_org_unit_id', $scope);
        });
    }

    /**
     * Restrict a query that has goal_org_unit_id on the root table.
     */
    protected function scopeByOrgColumn(Builder $query, string $column = 'goal_org_unit_id'): Builder
    {
        $scope = $this->scopedOrgIds();
        if ($scope === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn($column, $scope);
    }
}
