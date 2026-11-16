<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalLoanCase;
use App\Models\GoalRevenueEntry;

class GoalMisController extends Controller
{
    use AuthorizesGoal;

    public function branchMis()
    {
        $this->requireGoalPermission('branch_mis_guard');
        $query = GoalLoanCase::query()->with(['owner', 'orgUnit', 'disbursements'])->latest();
        $scope = $this->scopedOrgIds();
        if ($scope !== []) {
            $query->whereIn('goal_org_unit_id', $scope);
        }
        $cases = $query->paginate(50);
        return view('goal.mis.branch', compact('cases'));
    }

    public function revenueLedger()
    {
        $this->requireGoalPermission('revenue_ledger_guard');
        $entries = GoalRevenueEntry::query()->with(['loanCase'])->latest()->paginate(50);
        return view('goal.mis.revenue', compact('entries'));
    }
}
