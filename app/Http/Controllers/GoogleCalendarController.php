<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    public function authenticate()
    {
        return redirect($this->googleCalendarService->getAuthUrl());
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');
        $token = $this->googleCalendarService->authenticate($code);

        // Store token in session or database
        session(['google_calendar_token' => $token]);

        return redirect('/calendar')->with('success', 'Google Calendar connected successfully!');
    }

    public function fetchEvents()
    {
        $token = session('google_calendar_token');

        if (!$token) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $this->googleCalendarService->getClient()->setAccessToken($token);
        $calendarService = $this->googleCalendarService->getCalendarService();

        $events = $calendarService->events->listEvents('primary');

        return response()->json($events->getItems());
    }
    
    public function getEvents()
    {
        $token = auth()->user()->google_token;

        // Validate and decode the token
        try {
            $decodedToken = json_decode($token, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON token');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 400);
        }

        $this->googleCalendarService->getClient()->setAccessToken($decodedToken);

        $calendarService = $this->googleCalendarService->getCalendarService();
        $events = $calendarService->events->listEvents('primary');

        // Ensure events are fetched as an array
        if ($events instanceof \Illuminate\Http\JsonResponse) {
            $events = $events->getData(true); // Convert JSON response to array
        }

        // Debugging: Log the events to check if they are fetched correctly
        \Log::info('Fetched events:', ['events' => $events]);

        $formattedEvents = [];

        foreach ($events as $event) {
            $formattedEvents[] = [
                'title' => $event['summary'] ?? 'No Title',
                'start' => $event['start']['dateTime'] ?? $event['start']['date'],
                'end' => $event['end']['dateTime'] ?? $event['end']['date'],
            ];
        }

        return $formattedEvents;
    }

    public function calendar()
    {
        $events = $this->getEvents();
        return view('calendar', compact('events'));
    }

    public function createMeeting(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'required|date|after:start',
        ]);

        $token = auth()->user()->google_token;

        // Validate and decode the token
        try {
            $decodedToken = json_decode($token, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON token');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 400);
        }

        $this->googleCalendarService->getClient()->setAccessToken($decodedToken);
        $calendarService = $this->googleCalendarService->getCalendarService();

        // Create the event
        $event = new \Google_Service_Calendar_Event([
            'summary' => $request->input('title'),
            'start' => ['dateTime' => $request->input('start'), 'timeZone' => config('app.timezone')],
            'end' => ['dateTime' => $request->input('end'), 'timeZone' => config('app.timezone')],
        ]);

        $createdEvent = $calendarService->events->insert('primary', $event);

        // Send email to the user with the meeting link
        \Mail::to(auth()->user()->email)->send(new \App\Mail\MeetingCreated($createdEvent->htmlLink));

        return redirect('/calendar')->with('success', 'Meeting created and added to Google Calendar!');
    }
}