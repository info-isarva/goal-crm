<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalAccountsItem;
use App\Services\Goal\Pd01Service;
use Illuminate\Http\Request;

class GoalAccountsController extends Controller
{
    use AuthorizesGoal;

    public function index()
    {
        $this->requireGoalPermission('accounts_queue_guard');
        $items = GoalAccountsItem::query()->with(['loanCase'])->latest()->paginate(25);
        return view('goal.accounts.index', compact('items'));
    }

    public function updateReport(Request $request, GoalAccountsItem $item, Pd01Service $pd01)
    {
        $this->requireGoalPermission('accounts_queue_guard');
        $data = $request->validate([
            'report_number' => 'required|string|max:100',
        ]);
        $item->update([
            'report_number' => $data['report_number'],
            'status' => 'Report Entered',
            'updated_by' => auth()->id(),
        ]);
        $pd01->refreshPayoutEligibility($item->loanCase);
        return back()->with('success', 'Report number saved.');
    }
}
