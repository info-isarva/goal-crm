<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Support\RecordAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $filter = $request->query('filter', 'all');
        $today  = Carbon::today();
        $assignedTo = $request->query('assigned_to');
        $userId = $user->id;
        $employeeIds = collect();

        // If the user is a manager, fetch their employees
        if ($user->crm_role_type === 2) {
            $employeeIds = User::where('assign_manager', $user->id)->pluck('id');
        }

        if ($user->crm_role_type !== 0 && $user->crm_role_type !== 1) {
            // Employees can only see their own tasks
            $baseQuery = Task::where(function ($query) use ($userId, $employeeIds) {
                $query->where('user_assigned_id', $userId)
                    ->orWhere('created_by', $userId);

                // Include tasks for employees if the user is a manager
                if ($employeeIds->isNotEmpty()) {
                    $query->orWhereIn('user_assigned_id', $employeeIds)
                          ->orWhereIn('created_by', $employeeIds);
                }
            });
        }

        if ($user->crm_role_type === 0 || $user->crm_role_type === 1) {
            // Admins and Managers can see all tasks
            $baseQuery = Task::query();
        }

        if ($assignedTo) {
            $baseQuery->where('user_assigned_id', $assignedTo);
        }

        // Apply filters
        // if ($filter === 'deals') {
        //     $baseQuery->where('related_type', 'deal');
        // }

        // if ($filter === 'leads') {
        //     $baseQuery->where('related_type', 'lead');
        // }

        $title = $request->query('title');
        if ($title) {
            $baseQuery->where('name', 'like', "%$title%");
        }

        $perPage = $request->query('per_page', 10); // Default to 10 items per page

        $tasks = [
            'overdue' => [
                'count' => (clone $baseQuery)->where('due_at', '<', $today)->where('status', '!=', 'Completed')->count(),
                'data' => (clone $baseQuery)
                    ->where('due_at', '<', $today)
                    ->where('status', '!=', 'Completed') // Exclude completed tasks from overdue
                    ->orderBy('due_at', 'desc')
                    ->paginate($perPage, ['*'], 'overdue_page'),
            ],

            'today' => [
                'count' => (clone $baseQuery)->whereDate('due_at', $today)->where('status', '!=', 'Completed')->count(),
                'data' => (clone $baseQuery)
                    ->whereDate('due_at', $today)
                    ->where('status', '!=', 'Completed') // Exclude completed tasks from today's tasks
                    ->orderBy('due_at', 'desc')
                    ->paginate($perPage, ['*'], 'today_page'),
            ],

            'upcoming' => [
                'count' => (clone $baseQuery)->whereDate('due_at', '>', $today)->where('status', '!=', 'Completed')->count(),
                'data' => (clone $baseQuery)
                    ->whereDate('due_at', '>', $today)
                    ->where('status', '!=', 'Completed') // Exclude completed tasks from upcoming tasks
                    ->orderBy('due_at', 'asc')
                    ->paginate($perPage, ['*'], 'upcoming_page'),
            ],
        ];

        // Add completed tasks for the Completed tab (with filters)
        $completedQuery = Task::where(function ($query) use ($userId, $employeeIds) {
            $query->where('user_assigned_id', $userId)
                ->orWhere('created_by', $userId);

            // Include completed tasks for employees if the user is a manager
            if ($employeeIds->isNotEmpty()) {
                $query->orWhereIn('user_assigned_id', $employeeIds)
                      ->orWhereIn('created_by', $employeeIds);
            }
        })
        ->where('status', 'Completed');

        if ($assignedTo) {
            $completedQuery->where('user_assigned_id', $assignedTo);
        }

        // if ($filter === 'deals') {
        //     $completedQuery->where('related_type', 'deal');
        // }

        // if ($filter === 'leads') {
        //     $completedQuery->where('related_type', 'lead');
        // }

        if ($title) {
            $completedQuery->where('name', 'like', "%$title%");
        }

        $tasks['completed'] = [
            'count' => $completedQuery->count(),
            'data' => $completedQuery
                ->orderBy('completed_at', 'desc')
                ->paginate($perPage, ['*'], 'completed_page'),
        ];

        // Add organization_name to each task in all tabs
        foreach (['overdue', 'today', 'upcoming', 'completed'] as $tab) {
            foreach ($tasks[$tab]['data'] as $task) {
                $this->addOrganizationName($task);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    function addOrganizationName($task)
    {
        try {
            if ($task->related_type === 'deal') {
                $task->organization_name =
                    Deal::with('organization')->find($task->related_id)->organization->name ?? 'No Organization';
            } elseif ($task->related_type === 'lead') {
                $task->organization_name =
                    Lead::with('organization')->find($task->related_id)->organization->name ?? 'No Organization';
            } else {
                $task->organization_name = 'Invalid Related Type';
            }
        } catch (\Exception $e) {
            $task->organization_name = 'Error Fetching Organization';
        }

        return $task;
    }

    //Create task API method
    public function store(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        // Validate request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'required|date',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
            'priority' => 'required|in:high,higest,low,lowest,normal',
            'status' => 'required|in:Not Started,Deferred,In Progress,Completed,Waiting for input',
            'reminder_offset' => 'nullable|integer|min:0',
            'reminder_notifications_enabled' => 'nullable|boolean',
            'user_owner_id' => 'nullable|integer',
            'user_assigned_id' => 'nullable|integer',
        ]);
        if (!RecordAccess::canAccessRelated($user, $validatedData['related_type'], $validatedData['related_id'])) {
            return $this->accessDenied();
        }
        // Create task
        $task = new Task();
        $task->name = $validatedData['name'];
        $task->description = $validatedData['description'] ?? null;
        $task->due_at = $validatedData['due_at'] ?? null;
        $task->related_type = $validatedData['related_type'];
        $task->related_id = $validatedData['related_id'];
        $task->priority = $validatedData['priority'];
        $task->status = $validatedData['status'];
        $task->reminder_notifications_enabled = $validatedData['reminder_notifications_enabled'] ?? true;
        $task->user_owner_id = $validatedData['user_assigned_id']? $validatedData['user_assigned_id'] : $user->id;
        $task->user_assigned_id = $validatedData['user_assigned_id'];
        $task->completed_at = $validatedData['status'] === 'Completed' ? now() : null;
        $task->created_by = $user->id;
        $task->save();

        // Check if reminder notifications are enabled before creating reminders
        if ($validatedData['status'] !== 'Completed' && $task->reminder_notifications_enabled) {
            $offset = $validatedData['reminder_offset'] ?? 30;
            try {
                $task->reminders()->delete();
            } catch (\Exception $e) {
                // Log the exception or handle it as needed
            }
            $task->createReminder($offset);
        }

        $recipientIds = collect([
            $task->user_owner_id,
            $task->user_assigned_id,
        ])->filter()->unique()->values();
        foreach ($recipientIds as $userId) {
            $mailUser = \App\Models\User::find($userId);
            if ($mailUser && !empty($mailUser->email)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($mailUser->email)
                        ->send(new \App\Mail\TaskNotification($task, $mailUser));
                } catch (\Throwable $e) {
                    \Log::warning('Mobile task create email failed', [
                        'task_id' => $task->id,
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $task,
        ], 201);
    }
    
    //Update task API method
    public function update(Request $request, $id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        // Validate request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|string|in:normal,high,highest,low,lowest',
            'status' => 'required|string|in:Not Started,Deferred,In Progress,Completed,Waiting for input',
            'due_at' => 'required|date',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
            'reminder_notifications_enabled' => 'nullable|boolean',
            'reminder_offset' => 'nullable|integer|min:0',
            'user_assigned_id' => 'nullable|integer',
        ]);
        if ($validated['status'] === 'Completed') {
            $completed_at = now();
        } else {
            $completed_at = null;
        }

        // Find task
        $task = Task::findOrFail($id);
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        
        }
        if (!RecordAccess::canAccessActivity($user, $task)) {
            return $this->accessDenied();
        }
        $relatedChanged = strtolower((string) $validated['related_type']) !== strtolower((string) $task->related_type)
            || (int) $validated['related_id'] !== (int) $task->related_id;
        if ($relatedChanged && !RecordAccess::canAccessRelated($user, $validated['related_type'], $validated['related_id'])) {
            return $this->accessDenied();
        }
        
        // Update task
        $task->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'due_at' => $validated['due_at'],
            'priority' => $validated['priority'],
            'completed_at' => $completed_at,
            'status' => $validated['status'],
            'related_type' => $validated['related_type'],
            'related_id' => $validated['related_id'],
            'user_assigned_id' => $validated['user_assigned_id'],
            'user_owner_id' => $validated['user_assigned_id'] ? $validated['user_assigned_id'] : $task->user_owner_id,
            'reminder_notifications_enabled' => $validated['reminder_notifications_enabled'] ?? ($task->reminder_notifications_enabled ?? true),
            'updated_by' => Auth::id(),
        ]);

        // Manage reminders based on the flag
        if (($validated['reminder_notifications_enabled'] ?? ($task->reminder_notifications_enabled ?? true)) && $validated['status'] !== 'Completed') {
            // recreate reminder with provided offset (or default 30)
            try {
                $task->reminders()->delete();
            } catch (\Exception $e) {
                // ignore
            }
            $offset = $request->input('reminder_offset', 30);
            $task->createReminder($offset);
        }

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    //Delete task API method
    public function destroy($id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        // Find task
        $task = Task::findOrFail($id);
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }
        if (!RecordAccess::canAccessActivity($user, $task)) {
            return $this->accessDenied();
        }
        // Delete task
        $task->delete();
        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }

    //mark task as completed
    public function markAsCompleted($id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        // Find task
        $task = Task::findOrFail($id);
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }
        if (!RecordAccess::canAccessActivity($user, $task)) {
            return $this->accessDenied();
        }
        // Mark task as completed
        $task->status = 'completed';
        $task->completed_at = now();
        $task->save();
        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    //Show task details API method
    public function show($id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        // Find task
        $task = Task::findOrFail($id);
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], 404);
        }
        if (!RecordAccess::canAccessActivity($user, $task)) {
            return $this->accessDenied();
        }
        return response()->json([
            'success' => true,
            'data' => $task,
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