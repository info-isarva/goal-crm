<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class PushNotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        return response()->json(['message' => 'Push Notification Controller']);
    }
    public function saveToken(Request $request)
    {
        $user = auth()->user(); // or manually assign
        
        
        $user->fcm_token = $request->token;
        $user->save();

        return response()->json(['message' => 'Token saved']);
    }
}
