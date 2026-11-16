<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalPayout;
use App\Models\GoalPayoutReceipt;
use Illuminate\Http\Request;

class GoalPayoutController extends Controller
{
    use AuthorizesGoal;

    public function index()
    {
        $this->requireGoalPermission('payout_processing_guard');
        $payouts = GoalPayout::query()->with(['loanCase'])->latest()->paginate(25);
        return view('goal.payouts.index', compact('payouts'));
    }

    public function recordReceipt(Request $request, GoalPayout $payout)
    {
        $this->requireGoalPermission('record_receipt_guard');
        if (!in_array($payout->status, ['Eligible', 'Processing'], true)) {
            return back()->withErrors(['amount' => 'Payout must be Eligible/Processing to record receipts.']);
        }
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'received_on' => 'required|date',
            'reference' => 'nullable|string|max:100',
        ]);

        GoalPayoutReceipt::create([
            'goal_payout_id' => $payout->id,
            'amount' => $data['amount'],
            'received_on' => $data['received_on'],
            'reference' => $data['reference'] ?? null,
            'recorded_by' => auth()->id(),
        ]);

        $payout->received_amount = (float) $payout->received_amount + (float) $data['amount'];
        if ((float) $payout->received_amount + 0.01 >= (float) $payout->expected_amount) {
            $payout->status = 'Paid';
        } else {
            $payout->status = 'Processing';
        }
        $payout->save();

        return back()->with('success', 'Receipt recorded.');
    }

    public function markException(Request $request, GoalPayout $payout)
    {
        $this->requireGoalPermission('payout_processing_guard');
        $data = $request->validate(['exception_reason' => 'required|string|max:255']);
        $payout->update([
            'status' => 'Exception',
            'exception_reason' => $data['exception_reason'],
        ]);
        return back()->with('success', 'Payout marked Exception.');
    }
}
