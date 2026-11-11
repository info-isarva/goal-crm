<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use Illuminate\Support\Facades\Auth;

class CallActionsController extends Controller
{
    public function __construct()
    {
        // Prevent creating/editing/deleting calls when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'create', 'store', 'edit', 'update', 'destroy'
        ]);
    }
    public function update(Request $request, $id)
    {
        $call = Call::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($call);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
            'location' => 'nullable|string|max:255',
            'user_owner_id' => 'nullable|integer',
            'user_call_participant_id' => 'nullable',
            'user_call_participant_id.*' => 'integer',
        ]);
        $call->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'start_at' => $validated['start_at'],
            'finish_at' => $validated['finish_at'],
            'location' => $validated['location'],
            'user_owner_id' => $validated['user_owner_id'] ?? null,
            'user_restored_id' => $validated['user_call_participant_id'] ?? null, //isset($validated['user_call_participant_id']) ? json_encode($validated['user_call_participant_id']) : null,
            'updated_by' => Auth::id(),
        ]);
        return redirect()->back()->with('success', 'Call updated successfully');
    }

    public function destroy($id)
    {
        $call = Call::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($call);
        $call->update(['deleted_by' => Auth::id()]);
        $call->delete();
        return redirect()->back()->with('success', 'Call deleted');
    }
}
