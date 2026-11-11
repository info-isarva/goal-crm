<?php
namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Log; // Ensure Log facade is imported correctly
use App\Notifications\MeetingReminderNotification;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class MeetingController extends Controller
{
    public function __construct()
    {
        // Prevent creating/editing/deleting meetings when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'create',
            'store',
            'edit',
            'update',
            'destroy'
        ]);
    }

    private function authorizeMeetingView(Meeting $meeting): void
    {
        $user = Auth::user();
        if (\App\Support\RecordAccess::canAccessActivity($user, $meeting)) {
            return;
        }
        $isParticipant = Participant::where('meeting_id', $meeting->id)
            ->where('type', 'user')
            ->where('user_id', $user->id)
            ->exists();
        abort_unless($isParticipant, 403, 'You do not have access to this meeting.');
    }

    public function show($id)
    {
        $meeting = Meeting::with([
            'participants.user',
            'participants.contact',
            'createdBy',
            'modifiedBy',
            'lead.person',
            'deal.person',
        ])->findOrFail($id);
        $this->authorizeMeetingView($meeting);
        foreach ($meeting->participants as $participant) {
            if ($participant->type === 'user') {
                $participant->user = User::find($participant->user_id);
            } elseif ($participant->type === 'contact') {
                $participant->contact = Person::find($participant->person_id);
            }
        }

        $users = User::all();

        $leads = Lead::all();

        $deals = Deal::all();

        $contacts = Contact::all();

        return view('meetings.show', compact('meeting', 'users', 'contacts', 'leads', 'deals'));
    }
    public function store(Request $request)
    {
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
        \App\Support\RecordAccess::authorizeRelated($validated['related_type'], $validated['related_id']);

        // Decode user_participant_id if it is a JSON string
        if (is_string($validated['user_participant_id'])) {
            $validated['user_participant_id'] = json_decode($validated['user_participant_id'], true) ?? [];
        }

        // Ensure user_participant_id is an array before applying array_map
        if (!is_array($validated['user_participant_id'])) {
            $validated['user_participant_id'] = [];
        }

        //$validated['user_participant_id'] = array_map('intval', $validated['user_participant_id'] ?? []);

        try {
            $meeting = Meeting::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_at' => $validated['start_at'],
                'finish_at' => $validated['finish_at'],
                'venue' => $validated['venue'] ?? null,
                'location' => $validated['location'] ?? null,
                'related_type' => $validated['related_type'],
                'related_id' => $validated['related_id'],
                'user_owner_id' => $validated['user_owner_id'] ?? Auth::id(),
                // 'meet_link' => $meetLink,
                // 'google_event_id' => $event->id,
                // 'user_restored_id' => [], //isset($validated['user_participant_id']) ? json_encode($validated['user_participant_id']) : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
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
                    if ($user->email) {
                        Mail::to($user->email)->send(new MeetingNotification($meeting));
                    }
                }
            }

            // Send email notification to the host when the meeting is created
            $host = User::find($validated['user_owner_id']);
            if ($host) { 
                Mail::to($host->email)->send(new HostReminderNotification($meeting));
            }

             $result = response()->json([
                'success' => true,
                'message' => 'Meeting added successfully!',
                'meeting' => $validated['user_participant_id']
            ]);
        } catch (ValidationException $e) {
            $result = response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            $result = response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }

        if ($validated['related_type'] === 'deal') {
            return redirect()->route('deals.show', $validated['related_id'])->with(['success' => 'Meeting added successfully!', 'show_meetings_tab' => true]);
        } elseif ($validated['related_type'] === 'lead') {
            return redirect()->route('leads.show', $validated['related_id'])->with(['success' => 'Meeting added successfully!', 'show_meetings_tab' => true]);
        }
    }

    public function update(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($meeting);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'finish_at' => 'required|date|after_or_equal:start_at',
            'location' => 'nullable|string|max:255',
            'user_owner_id' => 'nullable|integer',
            'user_participant_id' => 'nullable|array',
            'user_participant_id.*' => 'integer',
        ]);
        $meeting->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_at' => $validated['start_at'],
            'finish_at' => $validated['finish_at'],
            'location' => $validated['location'] ?? null,
            'user_owner_id' => $validated['user_owner_id'] ?? null,
            'user_restored_id' => isset($validated['user_participant_id']) ? json_encode($validated['user_participant_id']) : null,
            'updated_by' => Auth::id(),
        ]);
        return redirect()->back()->with('success', 'Meeting updated successfully');
    }


    public function destroy($id)
    {
        $call = Meeting::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($call);
        $call->update(['deleted_by' => Auth::id()]);
        $call->delete();
        return redirect()->back()->with('success', 'Meeting deleted');
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');
        $filterType = $request->query('filter_type', 'default');
        $hostedBy = $request->query('hosted_by', null);

        $user = Auth::user();
             
    
        $meetings = Meeting::query();

        if ($user->crm_role_type === 0 || $user->crm_role_type === 1) {
            // Admin and Super Admin can view all meetings
            $meetings = $meetings;
        } else {
            // Employees and Managers can view their created and participant meetings
            $meetings = $meetings->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                      ->orWhereHas('participants', function ($participantQuery) use ($user) {
                          $participantQuery->where('user_id', $user->id);
                      });
            });
        }

        switch ($tab) {
            case 'today':
                $meetings->whereDate('start_at', now()->toDateString());
                break;
            case 'upcoming':
                $meetings->whereDate('start_at', '>', now()->toDateString());
                break;
            case 'completed':
                $meetings->whereDate('finish_at', '<', now()->toDateString());
                break;
        }

        $meetings = $meetings->orderBy('created_at', 'desc')->get();
        
        foreach ($meetings as $meeting) {
             $meeting->related_name = $meeting->related_type === 'lead' 
                ? ($meeting->lead ? $meeting->lead->title : null) 
                : ($meeting->related_type === 'deal' 
                    ? ($meeting->deal ? $meeting->deal->title : null) 
                    : null);
            $meeting->participants_list = $meeting->participants()->with(['user', 'contact'])->get()->map(function ($participant) {
                    if ($participant->type === 'user') {
                        $participant->user = User::find($participant->user_id);
                    } elseif ($participant->type === 'contact') {
                        $participant->contact = Person::find($participant->person_id);
                    }
                return [
                    'participant_id' => $participant->id,
                    'id' => $participant->type === 'user' 
                        ? ($participant->user ? $participant->user->id: null) 
                        : ($participant->contact ? $participant->contact->id : null),
                    'type' => $participant->type,
                    'name' => $participant->type === 'user' 
                        ? ($participant->user ? $participant->user->name : null) 
                        : ($participant->contact ? $participant->contact->first_name : null),
                ];
            });
            $meeting->participants_names = $meeting->participants_list->pluck('name')->implode(', ');
            $meeting->participants_lists = $meeting->participants()->with('user')->get()->map(function ($participant) {
                return [
                    'id' => ($participant->type === 'user') ? $participant->user_id : $participant->person_id,
                    'type' => $participant->type
                ];
            })->toArray();
        }

        $users = User::all();
        $leads = Lead::all();
        $deals = Deal::all();
        $contacts = Contact::all();

        return view('meetings.index', compact('meetings', 'tab', 'filterType', 'hostedBy', 'users', 'leads', 'deals', 'contacts'));
    }


    public function meetingStore(Request $request)
    {
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
        if (!\App\Support\RecordAccess::canAccessRelated(Auth::user(), $validated['related_type'], $validated['related_id'])) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this record.'], 403);
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

        if($request->has('create_google_meeting')) {
                     $user = auth()->user();

            $client = new Google_Client();
            $client->setClientId(env('GOOGLE_CLIENT_ID')); 
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

            $client->setAccessToken([
                'access_token' => $user->google_token,
                'refresh_token' => $user->google_refresh_token,
            ]);

            // Refresh token if expired
            if ($client->isAccessTokenExpired()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                $user->update([
                    'google_token' => $newToken['access_token']
                ]);
            }

            $attendes = [];
            if (!empty($validated['user_participant_id'])) {
                foreach ($validated['user_participant_id'] as $participant) {
                    if(isset($participant['id']) && isset($participant['type']) && $participant['type'] === 'user') {
                        $user = User::find($participant['id']);
                        if ($user) {
                            $attendes[] = ['email' => $user->email];
                        }
                    }
                    if(isset($participant['id']) && isset($participant['type']) && $participant['type'] === 'contact') {
                        $contact = Person::find($participant['id']);
                        if ($contact) {
                            $attendes[] = ['email' => $contact->email];
                        }
                    }
                }
            }

            $service = new Google_Service_Calendar($client);
            $event = new Google_Service_Calendar_Event([
                'summary' => $validated['name'],
                'start' => [
                    'dateTime' => Carbon::parse($validated['start_at'])->toIso8601String(),
                    'timeZone' => 'Asia/Kolkata',
                ],
                'end' => [
                    'dateTime' => Carbon::parse($validated['finish_at'])->toIso8601String(),
                    'timeZone' => 'Asia/Kolkata',
                ],
                // ✅ Add Participants Here
                'attendees' => $attendes,
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => uniqid(),
                        'conferenceSolutionKey' => [
                            'type' => 'hangoutsMeet',
                        ],
                    ],
                ],
            ]);

            $event = $service->events->insert(
                'primary',
                $event,
                ['conferenceDataVersion' => 1]
            );

            // 🎯 Google Meet Link
            $meetLink = $event->hangoutLink;
        }else {
            $meetLink = null;
            $event = null;
        }
   

        try {
            $meeting = Meeting::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_at' => $validated['start_at'],
                'finish_at' => $validated['finish_at'],
                'venue' => $validated['venue'] ?? null,
                'location' => $validated['location'] ?? null,
                'related_type' => $validated['related_type'],
                'related_id' => $validated['related_id'],
                'user_owner_id' => $validated['user_owner_id'] ?? Auth::id(),
                'meet_link' => $meetLink,
                'google_event_id' =>$event ? $event->id : null,
                // 'user_restored_id' => [], //isset($validated['user_participant_id']) ? json_encode($validated['user_participant_id']) : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
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
                        if($participant['id'] == $validated['user_owner_id']) {
                            continue; // Skip sending email to the host as they will receive a separate notification
                        }
                        $user = User::find($participant['id']);
                    }

                    if(isset($participant['id']) && isset($participant['type']) && $participant['type'] === 'contact') {
                        $user = Person::find($participant['id']);
                    }
                    if ($user) {
                        Mail::to($user->email)->send(new MeetingNotification($meeting));
                    }
                }
            }

            // Send email notification to the host when the meeting is created
            $host = User::find($validated['user_owner_id']);
            if ($host) { 
                Mail::to($host->email)->send(new HostReminderNotification($meeting));
            }

            return response()->json([
                'success' => true,
                'message' => 'Meeting added successfully!',
                'meeting' => $validated['user_participant_id']
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } 
        catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }
    }

    public function addParticipants(Request $request)
    {

        // Validate the request data
        $validated = $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'participants' => 'required|array',
            'participants.*.id' => 'required|integer',
            'participants.*.type' => 'required|in:user,contact',
        ]);

        $meeting = Meeting::findOrFail($validated['meeting_id']);
        if (!\App\Support\RecordAccess::canAccessActivity(Auth::user(), $meeting)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this meeting.'], 403);
        }

        foreach ($validated['participants'] as $participant) {
            Participant::create([
                'meeting_id' => $validated['meeting_id'],
                'user_id' => $participant['id'],

                'type' => $participant['type'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Participants added successfully!',
        ]);
    }

    public function removeParticipant($meetingId, $participantId)
    {
        $meeting = Meeting::findOrFail($meetingId);
        if (!\App\Support\RecordAccess::canAccessActivity(Auth::user(), $meeting)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this meeting.'], 403);
        }
        try {
            $participant = $meeting->participants()->findOrFail($participantId);

            $participant->delete();

            return response()->json(['success' => true, 'message' => 'Participant removed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to remove participant.'], 500);
        }
    }

    public function meetingUpdate(Request $request)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'meeting_id' => 'required|exists:meetings,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_at' => 'required|date',
                'finish_at' => 'required|date|after_or_equal:start_at',
                'venue' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'related_type' => 'required|string|max:50',
                'related_id' => 'required|integer',
                'user_owner_id' => 'nullable|integer',
                'user_participant_id' => 'nullable', // Allow string (JSON) or array
                // 'user_participant_id.*' => 'integer', // Removed to allow JSON string validation
            ]);


            // Find the meeting
            $meeting = Meeting::find($validated['meeting_id']);
            if (!$meeting) {
                return response()->json(['success' => false, 'message' => 'Meeting not found.'], 404);
            }
            if (!\App\Support\RecordAccess::canAccessActivity(Auth::user(), $meeting)
                || !\App\Support\RecordAccess::canAccessRelated(Auth::user(), $validated['related_type'], $validated['related_id'])) {
                return response()->json(['success' => false, 'message' => 'You do not have access to this meeting.'], 403);
            }

            // Decode user_participant_id if it is a JSON string
            if (isset($validated['user_participant_id']) && is_string($validated['user_participant_id'])) {
                $validated['user_participant_id'] = json_decode($validated['user_participant_id'], true) ?? [];
            }

            // Ensure user_participant_id is an array
            if (isset($validated['user_participant_id']) && !is_array($validated['user_participant_id'])) {
                $validated['user_participant_id'] = [];
            }
            if(!$meeting->meet_link) {
                if($request->has('create_google_meeting')) {
                    $user = auth()->user();

                    $client = new Google_Client();
                    $client->setClientId(env('GOOGLE_CLIENT_ID')); 
                    $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

                    $client->setAccessToken([
                        'access_token' => $user->google_token,
                        'refresh_token' => $user->google_refresh_token,
                    ]);

                    // Refresh token if expired
                    if ($client->isAccessTokenExpired()) {
                        $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                        $user->update([
                            'google_token' => $newToken['access_token']
                        ]);
                    }

                    $attendes = [];
                    if (!empty($validated['user_participant_id'])) {
                        foreach ($validated['user_participant_id'] as $participant) {
                            if(isset($participant['id']) && isset($participant['type']) && $participant['type'] === 'user') {
                                $user = User::find($participant['id']);
                                if ($user) {
                                    $attendes[] = ['email' => $user->email];
                                }
                            }
                            if(isset($participant['id']) && isset($participant['type']) && $participant['type'] === 'contact') {
                                $contact = Person::find($participant['id']);
                                if ($contact) {
                                    $attendes[] = ['email' => $contact->email];
                                }
                            }
                        }
                    }

                    $service = new Google_Service_Calendar($client);
                    $event = new Google_Service_Calendar_Event([
                        'summary' => $validated['name'],
                        'start' => [
                            'dateTime' => Carbon::parse($validated['start_at'])->toIso8601String(),
                            'timeZone' => 'Asia/Kolkata',
                        ],
                        'end' => [
                            'dateTime' => Carbon::parse($validated['finish_at'])->toIso8601String(),
                            'timeZone' => 'Asia/Kolkata',
                        ],
                        // ✅ Add Participants Here
                        'attendees' => $attendes,
                        'conferenceData' => [
                            'createRequest' => [
                                'requestId' => uniqid(),
                                'conferenceSolutionKey' => [
                                    'type' => 'hangoutsMeet',
                                ],
                            ],
                        ],
                    ]);

                    $event = $service->events->insert(
                        'primary',
                        $event,
                        ['conferenceDataVersion' => 1]
                    );

                    // 🎯 Google Meet Link
                    $meetLink = $event->hangoutLink;
                    $googleEventId = $event->id;
                }else {
                    $meetLink = null;
                    $googleEventId  = null;
                }
            }else {
                if($request->has('create_google_meeting')) {
                    $meetLink = $meeting->meet_link; // Retain existing meet link if not creating a new one
                    $googleEventId = $meeting->google_event_id; // Retain existing event if not creating a new one
                }else {
                    $meetLink = null; // Retain existing meet link if not creating a new one
                    $googleEventId = null; // Retain existing event if not creating a new one
                }
            }
            
            // Update the meeting
            $meeting->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_at' => $validated['start_at'],
                'finish_at' => $validated['finish_at'],
                'venue' => $validated['venue'] ?? null,
                'location' => $validated['location'] ?? null,
                'related_type' => $validated['related_type'],
                'related_id' => $validated['related_id'],
                'user_owner_id' => $validated['user_owner_id'] ?? null,
                'meet_link' => $meetLink,
                'google_event_id' =>$googleEventId,
                // 'user_restored_id' => isset($validated['user_participant_id']) ? json_encode($validated['user_participant_id']) : null,
                'updated_by' => Auth::id(),
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

            // Sync participants
            // 1. Delete existing participants for this meeting
            Participant::where('meeting_id', $meeting->id)->delete();

            // 2. Add new participants
            if (!empty($validated['user_participant_id'])) {
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
                            if ($user) {
                                Mail::to($user->email)->send(new MeetingNotification($meeting));
                            }
                        } 
                        if ($participant['type'] === 'contact') {
                            $contact = Person::find($participant['id']);
                            if ($contact) {
                                Mail::to($contact->email)->send(new MeetingNotification($meeting));
                            }
                        }
                    }
                }
            }

            // Send email notification to the host when the meeting is updated
            // $host = User::find($validated['user_owner_id']);
            // if ($host) {
            //     Mail::to($host->email)->send(new HostReminderNotification($meeting));
            // }

            // Schedule a reminder notification for the host when the meeting is updated
            // if ($validated['host_reminder']) {
            //     $host = User::find($validated['user_owner_id']);
            //     if ($host) {
            //         $reminderTime = \Carbon\Carbon::parse($validated['start_at'])->subMinutes($validated['host_reminder']);
            //         Notification::send($host, new MeetingReminderNotification($meeting, ['remind_at' => $reminderTime]));
            //     }
            // }

            return response()->json(['success' => true, 'message' => 'Meeting updated successfully']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Meeting Update Error:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update meeting. Please try again.'], 500);
        }
    }

      public function fetchMeetings()
    {
        $userId = auth()->id();

        $meetings = Meeting::where('created_by', $userId)
            ->orWhereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orWhere('user_owner_id', $userId)
            ->get(['id', 'name', 'start_at', 'finish_at']);

        $events = $meetings->map(function ($meeting) {
            return [
                'id' => $meeting->id,
                'title' => $meeting->name,
                'start' => $meeting->start_at,
                'end' => $meeting->finish_at,
            ];
        });

        return response()->json($events);
    }
}
