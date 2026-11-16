<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalPmsItem;
use App\Services\Goal\Pd01Service;
use Illuminate\Http\Request;

class GoalPmsController extends Controller
{
    use AuthorizesGoal;

    public function index()
    {
        $this->requireGoalPermission('pms_verification_guard');
        $items = GoalPmsItem::query()->with(['loanCase'])->latest()->paginate(25);
        return view('goal.pms.index', compact('items'));
    }

    public function verify(Request $request, GoalPmsItem $item, Pd01Service $pd01)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $data = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'required|in:Verified,Follow-up Due,Closed',
        ]);
        $item->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $item->notes,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);
        $pd01->refreshPayoutEligibility($item->loanCase);
        return back()->with('success', 'PMS item updated.');
    }
}
