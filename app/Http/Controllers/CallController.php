<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use Illuminate\Support\Facades\Auth;

class CallController extends Controller
{
    public function __construct()
    {
        // Prevent creating/editing/deleting calls when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'create', 'store', 'edit', 'update', 'destroy'
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
            'location' => 'nullable|string|max:255',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
            'user_owner_id' => 'nullable|integer',
            'user_assigned_id' => 'nullable|integer',
            'user_call_participant_id' => 'nullable',
            'user_call_participant_id.*' => 'integer',
        ]);
        \App\Support\RecordAccess::authorizeRelated($validated['related_type'], $validated['related_id']);

        $call = Call::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'start_at' => $validated['start_at'],
            'finish_at' => $validated['finish_at'],
            'location' => $validated['location'],
            'related_type' => $validated['related_type'],
            'related_id' => $validated['related_id'],
            'user_owner_id' => $validated['user_owner_id'] ?? Auth::id(),
            'user_assigned_id' => $validated['user_assigned_id'] ?? null,
            'user_restored_id' => $validated['user_call_participant_id'] ?? null, //isset($validated['user_call_participant_id']) ? json_encode($validated['user_call_participant_id']) : null,
            'created_by' => Auth::id(),
        ]);
        return redirect()->back()->with(['success' => 'Call added successfully', 'show_calls_tab' => true]);
    }
}
