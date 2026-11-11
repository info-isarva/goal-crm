<?php
namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;

class NotificationController extends Controller
{
    
    public function saveToken(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        
        
        $user->fcm_token = $request->token;
        $user->save();

        return response()->json(['message' => 'Token saved']);
    }

    //list the notifications for the user
    public function listNotifications(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $notes = $user->notifications()->orderBy('created_at', 'desc')->get();
        $items = $notes->map(function($n){
            $data = $n->data ?? [];
            return [
                'id' => $n->id,
                'message' => $data['message'] ?? ($data['title'] ?? 'Notification'),
                'created_at' => $n->created_at->diffForHumans(),
                'read_at' => $n->read_at ? true : false,
                'related_type' => $data['related_type'] ?? null,
                'related_id' => $data['related_id'] ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'unread' => $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }

    /**
     * If click the particular notification, mark it as read and redirect to the related page.
     */
    public function clickNotification($id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $notif = $user->notifications()->where('id', $id)->first();
        if (!$notif) {
            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
        }

        $notif->markAsRead();

        $data = $notif->data ?? [];
        return response()->json([
            'success' => true,
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
        ]);
    }
   
}
