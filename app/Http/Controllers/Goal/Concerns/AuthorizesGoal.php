<?php

namespace App\Http\Controllers\Goal\Concerns;

trait AuthorizesGoal
{
    protected function requireGoalPermission(string $guard): void
    {
        if (!auth()->user()->hasCrmPermission($guard)) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function scopedOrgIds(): array
    {
        return auth()->user()->goalScopedOrgUnitIds();
    }
}
