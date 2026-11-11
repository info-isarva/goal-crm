<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use Illuminate\Support\Facades\Auth;

class NoteActionsController extends Controller
{
    public function __construct()
    {
        // Prevent editing/deleting notes when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'update', 'pin', 'unpin', 'destroy'
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $note = Note::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($note);
        $validated = $request->validate([
            'content' => 'required|string',
            'noted_at' => 'required|date',
        ]);
        $note->update([
            'content' => $validated['content'],
            'noted_at' => $validated['noted_at'],
            'updated_by' => Auth::id(),
        ]);
        return redirect()->back()->with('success', 'Note updated successfully');
    }

    public function pin($id)
    {
        $note = Note::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($note);
        $note->update(['pinned' => 1, 'updated_by' => Auth::id()]);
        return redirect()->back()->with('success', 'Note pinned');
    }

    public function unpin($id)
    {
        $note = Note::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($note);
        $note->update(['pinned' => 0, 'updated_by' => Auth::id()]);
        return redirect()->back()->with('success', 'Note unpinned');
    }

    public function destroy($id)
    {
        $note = Note::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($note);
        $note->update(['deleted_by' => Auth::id()]);
        $note->delete();
        return redirect()->back()->with('success', 'Note deleted');
    }
}
