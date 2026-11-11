<?php
namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Person;
use App\Models\User;
use App\Models\CallLog;
use App\Support\RecordAccess;

class CallController extends Controller
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
            'user_owner_id' => 'nullable|integer',
            'user_assigned_id' => 'nullable|integer',
            'user_call_participant_id' => 'nullable|integer',
            
        ]);

        if (!$this->canAttachTo($user, $validated['related_type'], $validated['related_id'])) {
            return $this->accessDenied();
        }

        $call = Call::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'start_at' => Carbon::parse($validated['start_at'])->format('Y-m-d H:i:s'), //YYYY-MM-DD HH:MM:SS format expected, parse to Carbon instance
            'finish_at' => Carbon::parse($validated['finish_at'])->format('Y-m-d H:i:s'),
            'related_type' => $validated['related_type'],
            'related_id' => $validated['related_id'],
            'user_owner_id' => $validated['user_owner_id'] ?? $user->id,
            'user_assigned_id' => $validated['user_assigned_id'] ?? null,
            'user_restored_id' => $validated['user_call_participant_id'] ?? null,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'call' => $call,
        ]);
    }

    public function update(Request $request, Call $call)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!RecordAccess::canAccessActivity($user, $call)) {
            return $this->accessDenied();
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
            // 'location' => 'nullable|string|max:255',
            'user_owner_id' => 'nullable|integer',
            'user_call_participant_id' => 'nullable|integer',
            'user_call_participant_id.*' => 'integer',
        ]);
        $call->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'start_at' => Carbon::parse($validated['start_at'])->format('Y-m-d H:i:s'),
            'finish_at' => Carbon::parse($validated['finish_at'])->format('Y-m-d H:i:s'),
            // 'location' => $validated['location'] ?? null,
            'user_owner_id' => $validated['user_owner_id'] ?? $user->id,
            'user_restored_id' => $validated['user_call_participant_id'] ?? null,
            'updated_by' => $user->id,
        ]);
        return response()->json([
            'success' => true,
            'call' => $call,
        ]);
    }

    public function destroy(Call $call)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!RecordAccess::canAccessActivity($user, $call)) {
            return $this->accessDenied();
        }
        $call->update(['deleted_by' => $user->id]);
        $call->delete();
        return response()->json([
            'success' => true,
            'message' => 'Call deleted successfully',
        ]);
    }

    //Contact person call history with conatct person name & call details
    public function contactCallHistory($contactId)
    {        
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401); 
        }

        $calls = Call::where('related_type', 'contact')
            ->where('related_id', $contactId)
            ->orderBy('start_at', 'desc')
            ->get()
            ->filter(fn ($call) => RecordAccess::canAccessActivity($user, $call))
            ->values();

        $calls->transform(function ($call) {
            
            return [
                'id' => $call->id,
                'name' => $call->name,
                'description' => $call->description,
                'start_at' => $call->start_at,
                'finish_at' => $call->finish_at,
                'related_type' => $call->related_type,
                'related_id' => $call->related_id,
                'person_name' => Person::find($call->related_id)->getFullNameAttribute(), // Get full name of the contact person
                'user_owner_id' => $call->user_owner_id,
                'called_by' => User::find($call->user_owner_id)->getUserName(), // Get name of the user who made the call
                'user_assigned_id' => $call->user_assigned_id,
                'user_call_participant_id' => $call->user_restored_id,
            ];
        });
        
        return response()->json([
            'success' => true,
            'calls' => $calls,
        ]);
    }

    function callLogsList() {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401); 
        }

       $calls = CallLog::orderBy('created_at', 'desc')
           ->when(!RecordAccess::isAdmin($user), fn ($q) => $q->where('created_by', $user->id))
           ->get()->map(function ($call) {
    return [
        'id'       => $call->id,
        'fullname' => $call->name,
        'mobile'   => $call->mobile_number,
        'email'    => $call->email,
        'type'     => 'call_log',
    ];
});

$contact = Person::orderBy('created_at', 'desc')->get()->map(function ($contact) {
    return [
        'id'       => $contact->id,
        'fullname' => $contact->getFullNameAttribute(),
        'mobile'   => $contact->mobile,
        'email'    => $contact->email,
        'type'     => 'contact',
    ];
});

$combined = collect($calls)->merge(collect($contact))->values();

        return response()->json([
            'success' => true,
            'calls' => $combined,
        ]);
    }

    function createCallLog(Request $request) {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401); 
        }

       $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'mobile_number' => 'required|string|max:20',
            'call_status' => 'nullable|string|max:50',
            'lead_status' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'discription' => 'nullable|string',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
            'user_owner_id' => 'nullable|integer',
            'user_assigned_id' => 'nullable|integer',
            'user_call_participant_id' => 'nullable|integer',
            
        ]);

        $callLog = CallLog::create([
            'name' => $validated['name'],
            'company_name' => $validated['company_name'] ?? null,
            'mobile_number' => $validated['mobile_number'] ?? null,
            'call_status' => $validated['call_status'] ?? null,
            'lead_status' => $validated['lead_status'] ?? null,
            'type' => $validated['type'] ?? null,
            'created_by' => $user->id,
        ]);

        if($callLog) {
            // Optionally, you can also create a corresponding Call record if needed
            Call::create([
                'name' => $validated['name'],
                'description' => $validated['discription'] ?? null,
                'start_at' => Carbon::parse($validated['start_at'])->format('Y-m-d H:i:s'),
                'finish_at' => Carbon::parse($validated['finish_at'])->format('Y-m-d H:i:s'),
                'related_type' => 'call_log',
                'related_id' => $callLog->id,
                'user_owner_id' => $validated['user_owner_id'] ?? $user->id,
                'user_assigned_id' => $validated['user_assigned_id'] ?? null,
                'user_restored_id' => $validated['user_call_participant_id'] ?? null,
                'created_by' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'call_log' => $callLog,
        ]);
    }

    private function canAttachTo($user, $relatedType, $relatedId): bool
    {
        $type = strtolower((string) $relatedType);
        if ($type === 'contact') {
            return true;
        }
        if ($type === 'call_log') {
            $log = CallLog::find($relatedId);
            return $log !== null && RecordAccess::canAccessOwned($user, $log->created_by, null);
        }

        return RecordAccess::canAccessRelated($user, $relatedType, $relatedId);
    }

    private function accessDenied()
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not have access to this record.',
        ], 403);
    }
}
