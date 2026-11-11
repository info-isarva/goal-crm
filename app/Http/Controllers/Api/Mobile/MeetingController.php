<?php
namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Meeting;
use Illuminate\Support\Facades\Auth;
use App\Models\Lead;
use App\Models\Deal;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Models\Participant; // Add this at the top to use the Participant model
use App\Models\Person as Contact;
use Illuminate\Support\Facades\Mail;
use App\Mail\MeetingNotification;
use App\Mail\HostReminderNotification;
use App\Models\Person;
use App\Support\RecordAccess;
use Illuminate\Support\Facades\Log; // Ensure Log facade is imported correctly
use App\Notifications\MeetingReminderNotification;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class MeetingController extends Controller
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
            'venue' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
            'user_owner_id' => 'nullable|integer',
            'user_participant_id' => 'nullable',
            // 'user_participant_id.*' => 'integer',
        ], [], [
            'name' => 'Meeting Title',
            'start_at' => 'Start Time',
            'finish_at' => 'End Time',
        ]);

        if (!RecordAccess::canAccessRelated($user, $validated['related_type'], $validated['related_id'])) {
            return $this->accessDenied();
        }

        // Decode user_participant_id if it is a JSON string
        if (is_string($validated['user_participant_id'])) {
            $validated['user_participant_id'] = json_decode($validated['user_participant_id'], true) ?? [];
        }

        // Ensure user_participant_id is an array before applying array_map
        if (!is_array($validated['user_participant_id'])) {
            $validated['user_participant_id'] = [];
        }

        //$validated['user_participant_id'] = array_map('intval', $validated['user_participant_id'] ?? []);
        
        // try {
            $meeting = Meeting::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_at' => Carbon::parse($validated['start_at'])->format('Y-m-d H:i:s'),
                'finish_at' => Carbon::parse($validated['finish_at'])->format('Y-m-d H:i:s'),
                'venue' => $validated['venue'] ?? null,
                'location' => $validated['location'] ?? null,
                'related_type' => $validated['related_type'],
                'related_id' => $validated['related_id'],
                'user_owner_id' => $validated['user_owner_id'] ?? $user->id,
                // 'meet_link' => $meetLink,
                // 'google_event_id' => $event->id,
                // 'user_restored_id' => [], //isset($validated['user_participant_id']) ? json_encode($validated['user_participant_id']) : null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            

            $offset = $request->input('host_reminder');
            if($offset) {
               try {
                    $meeting->reminders()->delete();
                } catch (\Exception $e) {
                    // Ignore if relation not available yet
                }
                $meeting->createReminder($offset);
               
            }
            // After creating the meeting, store participants in the participants table
            if (!empty($validated['user_participant_id'])) {
                foreach ($validated['user_participant_id'] as $participant) {
                    Participant::create([
                        'meeting_id' => $meeting->id,
                        'type' => $participant['type'], // Assuming type is always 'user' for now
                        'user_id' => ($participant['type'] === 'user') ? $participant['id'] : null,
                        'person_id' => ($participant['type'] === 'contact') ? $participant['id'] : null,
                    ]);
                    if(isset($participant['id']) && isset($participant['type']) && $participant['type'] === 'user') {
                        $user = User::find($participant['id']);
                    }

                    if(isset($participant['id']) && isset($participant['type']) && $participant['type'] === 'contact') {
                        $user = Person::find($participant['id']);
                    }
                    // if ($user->email) {
                    //     Mail::to($user->email)->send(new MeetingNotification($meeting));
                    // }
                }
            }

            // Send email notification to the host when the meeting is created
            $host = User::find($validated['user_owner_id']);
            // if ($host) { 
            //     Mail::to($host->email)->send(new HostReminderNotification($meeting));
            // }

            return response()->json([
                'success' => true,
                'message' => 'Meeting added successfully!',
                'meeting' => $meeting
            ]);
        // } catch (ValidationException $e) {
        //     return response()->json([
        //         'success' => false,
        //         'errors' => $e->errors()
        //     ], 422);
        // } catch (Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'An unexpected error occurred. Please try again later.'
        //     ], 500);
        // }
    }

    public function update(Request $request, Meeting $meeting)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!RecordAccess::canAccessActivity($user, $meeting)) {
            return $this->accessDenied();
        }
         
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
            'venue' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
            'user_owner_id' => 'nullable|integer',
            'user_participant_id' => 'nullable',
            // 'user_participant_id.*' => 'integer',
        ], [], [
            'name' => 'Meeting Title',
            'start_at' => 'Start Time',
            'finish_at' => 'End Time',
        ]);

        $relatedChanged = strtolower((string) $validated['related_type']) !== strtolower((string) $meeting->related_type)
            || (int) $validated['related_id'] !== (int) $meeting->related_id;
        if ($relatedChanged && !RecordAccess::canAccessRelated($user, $validated['related_type'], $validated['related_id'])) {
            return $this->accessDenied();
        }

        // Decode user_participant_id if it is a JSON string
        if (is_string($validated['user_participant_id'])) {
            $validated['user_participant_id'] = json_decode($validated['user_participant_id'], true) ?? [];
        }

        // Ensure user_participant_id is an array before applying array_map
        if (!is_array($validated['user_participant_id'])) {
            $validated['user_participant_id'] = [];
        }
        
        // try {
             $meeting->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_at' => Carbon::parse($validated['start_at'])->format('Y-m-d H:i:s'),
                'finish_at' => Carbon::parse($validated['finish_at'])->format('Y-m-d H:i:s'),
                'venue' => $validated['venue'] ?? null,
                'location' => $validated['location'] ?? null,
                'related_type' => $validated['related_type'],
                'related_id' => $validated['related_id'],
                'user_owner_id' => $validated['user_owner_id'] ?? $user->id,
                
                'updated_by' => $user->id,
            ]);

            $offset = $request->input('host_reminder');
            if($offset) {
               try {
                    $meeting->reminders()->delete();
                } catch (\Exception $e) {
                    // Ignore if relation not available yet
                }
                $meeting->createReminder($offset);
               
            }

            // 2. Add new participants
            if (!empty($validated['user_participant_id'])) {
                // Sync participants
                // 1. Delete existing participants for this meeting
                Participant::where('meeting_id', $meeting->id)->delete();

                foreach ($validated['user_participant_id'] as $participant) {
                    // Check if participant data is valid before creating
                    if (isset($participant['id']) && isset($participant['type'])) {
                        Participant::create([
                            'meeting_id' => $meeting->id,
                            'type' => $participant['type'],
                            'user_id' => ($participant['type'] === 'user') ? $participant['id'] : null,
                            'person_id' => ($participant['type'] === 'contact') ? $participant['id'] : null,
                        ]);
                        // Send email notification to the participant
                        if ($participant['type'] === 'user') {
                            $user = User::find($participant['id']);
                            // if ($user->email) {
                            //     Mail::to($user->email)->send(new MeetingNotification($meeting));
                            // }
                        } 
                        if ($participant['type'] === 'contact') {
                            $contact = Person::find($participant['id']);
                            // if ($contact->email) {
                            //     Mail::to($contact->email)->send(new MeetingNotification($meeting));
                            // }
                        }
                    }
                }
            }

             $result = response()->json([
                'success' => true,
                'message' => 'Meeting updated successfully!',
                'meeting' => $meeting
            ]);
        // } catch (ValidationException $e) {
        //     return response()->json([
        //         'success' => false,
        //         'errors' => $e->errors()
        //     ], 422);
        // } catch (Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'An unexpected error occurred. Please try again later.'
        //     ], 500);
        // }
    }

    public function destroy(Meeting $meeting)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!RecordAccess::canAccessActivity($user, $meeting)) {
            return $this->accessDenied();
        }
        $meeting->update(['deleted_by' => $user->id]);
        $meeting->delete();
        return response()->json([
            'success' => true,
            'message' => 'Meeting deleted successfully',
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
