<?php
namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Support\RecordAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotesController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $validated = $request->validate([
            'content' => 'required|string',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
        ]);

        if (!RecordAccess::canAccessRelated($user, $validated['related_type'], $validated['related_id'])) {
            return $this->accessDenied();
        }

        $note = Note::create([
            'content' => $validated['content'],
            'related_type' => $validated['related_type'],
            'related_id' => $validated['related_id'],
            'noted_at' => now(),
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'note' => $note,
        ]);
    }

    public function update(Request $request, Note $note)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!RecordAccess::canAccessActivity($user, $note)) {
            return $this->accessDenied();
        }
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        // Only allow updating the content of the note
        $note->content = $validated['content'];
        $note->save();

        return response()->json([
            'success' => true,
            'note' => $note,
        ]);
    }

    //delete note
    public function destroy(Note $note)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!RecordAccess::canAccessActivity($user, $note)) {
            return $this->accessDenied();
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully',
        ]);
    }

    private function accessDenied()
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not have access to this record.',
        ], 403);
    }
}
