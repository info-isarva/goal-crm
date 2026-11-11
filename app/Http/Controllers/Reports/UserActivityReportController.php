<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Note;
use App\Models\Call;
use App\Models\Meeting;
use App\Models\Project;
use Carbon\Carbon;

class UserActivityReportController extends Controller
{
    public function index(Request $request)
    {
        \Log::info('UserActivityReportController@index accessed', ['request' => $request->all()]);

        // Fetch all projects
        $projects = Project::all(['id', 'name']);

        // Fetch activities related to the project
        $tasks = Task::all(['title', 'created_at']);
        $notes = Note::all(['content', 'created_at']);
        $calls = Call::all(['subject', 'created_at']);
        $meetings = Meeting::all(['topic', 'created_at']);

        // Combine all activities
        $activities = collect();

        foreach ($tasks as $task) {
            $activities->push([
                'type' => 'Task',
                'title' => $task->title,
                'created_at' => $task->created_at,
            ]);
        }

        foreach ($notes as $note) {
            $activities->push([
                'type' => 'Note',
                'title' => $note->content,
                'created_at' => $note->created_at,
            ]);
        }

        foreach ($calls as $call) {
            $activities->push([
                'type' => 'Call',
                'title' => $call->subject,
                'created_at' => $call->created_at,
            ]);
        }

        foreach ($meetings as $meeting) {
            $activities->push([
                'type' => 'Meeting',
                'title' => $meeting->topic,
                'created_at' => $meeting->created_at,
            ]);
        }

        // Sort activities by creation date
        $activities = $activities->sortByDesc('created_at');

        \Log::info('Activities fetched successfully', ['activities' => $activities]);

        // Pass projects and activities to the view
        return view('reports.user_activity', ['activities' => $activities, 'projects' => $projects]);
    }
}