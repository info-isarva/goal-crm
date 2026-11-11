<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    public function __construct()
    {
        // Prevent creating notes when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'store'
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
            'noted_at' => 'required|date',
        ]);
        \App\Support\RecordAccess::authorizeRelated($validated['related_type'], $validated['related_id']);

        $note = Note::create([
            'content' => $validated['content'],
            'related_type' => $validated['related_type'],
            'related_id' => $validated['related_id'],
            'noted_at' => $validated['noted_at'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with(['success' => 'Note added successfully', 'show_notes_tab' => true]);
    }
}
