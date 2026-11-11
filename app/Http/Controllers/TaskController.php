<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use PhpParser\Node\Expr\Print_;
use Carbon\Carbon;
use App\Models\User;
use App\Models\TaskReminder;
use App\Mail\TaskNotification;

class TaskController extends Controller
{
    public function __construct()
    {
        // Prevent creating tasks when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'store'
        ]);
    }

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $today  = Carbon::today();
        $userId = Auth::id();
        $assignedTo = $request->query('assigned_to');

        $user = Auth::user();
        $employeeIds = collect();

        // If the user is a manager, fetch their employees
        if ($user->crm_role_type === 2) {
            $employeeIds = User::where('assign_manager', $user->id)->pluck('id');
        }
        
        if($user->crm_role_type !== 0 && $user->crm_role_type !== 1){
            // Employees can only see their own tasks
            $baseQuery = Task::where(function ($query) use ($userId, $employeeIds) {
                $query->where('user_assigned_id', $userId)
                    ->orWhere('created_by', $userId);

                // Include tasks for employees if the user is a manager
                if ($employeeIds->isNotEmpty()) {
                    $query->orWhereIn('user_assigned_id', $employeeIds)
                          ->orWhereIn('created_by', $employeeIds);
                }
            })->where('status', '!=', 'Completed');
        
        }

        if($user->crm_role_type === 0 || $user->crm_role_type === 1){
            // Admins and Managers can see all tasks
            $baseQuery = Task::where('status', '!=', 'Completed');
        }
       
        if ($assignedTo) {
            $baseQuery->where('user_assigned_id', $assignedTo);
        }

        /**
         * Apply filter separately
         */
        if ($filter === 'deals') {
            $baseQuery->where('related_type', 'deal');
        }

        if ($filter === 'leads') {
            $baseQuery->where('related_type', 'lead');
        }

        $title = $request->query('title');
        if ($title) {
            $baseQuery->where('name', 'like', "%$title%");
        }

        $tasks = [
            'overdue' => (clone $baseQuery)
                ->where('due_at', '<', $today)
                ->orderBy('due_at', 'desc')
                ->paginate(10, ['*'], 'overdue_page'),

            'today' => (clone $baseQuery)
                ->whereDate('due_at', $today)
                ->orderBy('due_at', 'desc')
                ->paginate(10, ['*'], 'today_page'),

            'upcoming' => (clone $baseQuery)
                ->whereDate('due_at', '>', $today)
                ->orderBy('due_at', 'asc')
                ->paginate(10, ['*'], 'upcoming_page'),
        ];
        $now = Carbon::now();
       

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
        if ($filter === 'deals') {
            $completedQuery->where('related_type', 'deal');
        }
        if ($filter === 'leads') {
            $completedQuery->where('related_type', 'lead');
        }
        if ($title) {
            $completedQuery->where('name', 'like', "%$title%");
        }

        $tasks['completed'] = $completedQuery
            ->orderBy('completed_at', 'desc')
            ->paginate(10, ['*'], 'completed_page');

        $totalCounts = [
            'overdue' => (clone $baseQuery)->where('due_at', '<', $today)->count(),
            'today' => (clone $baseQuery)->whereDate('due_at', $today)->count(),
            'upcoming' => (clone $baseQuery)->whereDate('due_at', '>', $today)->count(),
            'completed' => $completedQuery->count(),
        ];

        // Add organization_name to each task in all tabs
        foreach (['overdue', 'today', 'upcoming', 'completed'] as $tab) {
            foreach ($tasks[$tab] as $task) {
                $this->addOrganizationName($task);
            }
        }

        // Ensure managers are fetched for the dropdown
        $managers = User::where('crm_role_type', 2)->get();

        return view('tasks.index', compact('tasks', 'totalCounts', 'managers'));
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

    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'required|date',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
            'priority' => 'required|in:high,highest,low,lowest,normal',
            'status' => 'required|in:Not Started,Deferred,In Progress,Completed,Waiting for input',
            'reminder_offset' => 'nullable|integer|min:0',
            'reminder_notifications_enabled' => 'nullable|boolean',
            'user_owner_id' => 'nullable|integer',
            'user_assigned_id' => 'nullable|integer',
        ]);
        \App\Support\RecordAccess::authorizeRelated($validated['related_type'], $validated['related_id']);

        $task = Task::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'due_at' => $validated['due_at'],
            'related_type' => $validated['related_type'],
            'related_id' => $validated['related_id'],
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'user_owner_id' => $validated['user_owner_id'] ?? Auth::id(),
            'user_assigned_id' => $validated['user_assigned_id'] ?? null,
            'created_by' => Auth::id(),
            'reminder_notifications_enabled' => $validated['reminder_notifications_enabled'] ?? true,
            'completed_at' => $validated['status'] === 'Completed' ? now() : null,

        ]);

        // Check if reminder notifications are enabled before creating reminders
        if ($validated['status'] !== 'Completed' && $task->reminder_notifications_enabled) {
            $offset = $request->input('reminder_offset', 30);
            try {
                $task->reminders()->delete();
            } catch (\Exception $e) {
                // Ignore if relation not available yet
            }
            $task->createReminder($offset);
        }

        // Notify owner / assignee by email (includes related lead/deal link)
        $this->sendTaskCreatedEmails($task);

        // Redirect to the related deal or lead show page, focusing on the tasks tab
        if ($validated['related_type'] === 'deal') {
            return redirect()->route('deals.show', $validated['related_id'])->with(['success' => 'Task added successfully', 'show_tasks_tab' => true]);
        } elseif ($validated['related_type'] === 'lead') {
            return redirect()->route('leads.show', $validated['related_id'])->with(['success' => 'Task added successfully', 'show_tasks_tab' => true]);
        }

        return redirect()->back()->with(['success' => 'Task added successfully', 'show_tasks_tab' => true]);
    }

    /**
     * Email owner and assignee when a task is created.
     */
    protected function sendTaskCreatedEmails(Task $task): void
    {
        $recipientIds = collect([
            $task->user_owner_id,
            $task->user_assigned_id,
        ])->filter()->unique()->values();

        foreach ($recipientIds as $userId) {
            $user = User::find($userId);
            if (!$user || empty($user->email)) {
                continue;
            }
            try {
                Mail::to($user->email)->send(new TaskNotification($task, $user));
            } catch (\Throwable $e) {
                \Log::warning('Task create email failed', [
                    'task_id' => $task->id,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Show a task by redirecting to its related lead or deal page, focusing on the tasks tab.
     */
    public function show($id)
    {
        $task = Task::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($task);
        if ($task->related_type === 'lead') {
            return redirect()->route('leads.show', $task->related_id)->with('show_tasks_tab', true);
        } elseif ($task->related_type === 'deal') {
            return redirect()->route('deals.show', $task->related_id)->with('show_tasks_tab', true);
        } else {
            abort(404, 'Task not related to a lead or deal.');
        }
    }

    /**
     * Edit a task and return its details.
     */
    public function edit($id)
    {
        $task = Task::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($task);

        // Fetch related options based on the task's related type
       $relatedOptions = $task->related_type === 'deal'
            ? \App\Models\Deal::with('organization:id,name')
                ->select('id', 'organization_id', 'title')
                ->get()
                ->map(function ($deal) {
                    return [
                        'id'   => $deal->id,
                        'text' => ($deal->organization->name ?? 'Unknown') . ' - ' . $deal->title
                    ];
                })
            : \App\Models\Lead::with('organization:id,name')
                ->select('id', 'organization_id', 'title')
                ->get()
                ->map(function ($lead) {
                    return [
                        'id'   => $lead->id,
                        'text' => ($lead->organization->name ?? 'Unknown') . ' - ' . $lead->title
                    ];
                });

        return response()->json([
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'priority' => $task->priority,
            'status' => $task->status,
            'due_at' => $task->due_at ? $task->due_at->format('Y-m-d H:i') : null,
            'related_type' => $task->related_type,
            'related_id' => $task->related_id,
            'user_assigned_id' => $task->user_assigned_id,
            'reminder_notifications_enabled' => $task->reminder_notifications_enabled ?? true,
            // Compute reminder offset (minutes) from the first reminder if present, otherwise default 30
            'reminder_offset' => (function () use ($task) {
                try {
                    $first = $task->reminders()->orderBy('remind_at')->first();
                    if ($first && $task->due_at) {
                        $due = $task->due_at instanceof \Carbon\Carbon ? $task->due_at : Carbon::parse($task->due_at);
                        $remindAt = $first->remind_at instanceof \Carbon\Carbon ? $first->remind_at : Carbon::parse($first->remind_at);
                        return max(1, $due->diffInMinutes($remindAt));
                    }
                } catch (\Exception $e) {
                    // ignore
                }
                return 30;
            })(),
            'related_options' => $relatedOptions,
        ]);
    }

    /**
     * Handle AJAX requests for task name suggestions.
     */
    public function autocomplete(Request $request)
    {
        $query = $request->get('q', '');

        $tasks = Task::where('name', 'LIKE', "%{$query}%")
            ->with(['deal.organization', 'lead.organization']) // Include related records
            ->select('id', 'name', 'related_type', 'related_id')
            ->limit(10)
            ->get()
            ->map(function ($task) {
                $task->organization_name = $task->related_type === 'deal'
                    ? $task->deal->organization->name ?? 'No Organization'
                    : ($task->lead->organization->name ?? 'No Organization');
                return $task;
            });

        return response()->json($tasks);
    }

     /**
     * Mark a completed task as incomplete (AJAX).
     */
    public function incomplete($id)
    {
        $task = Task::findOrFail($id);
        if (!\App\Support\RecordAccess::canAccessActivity(Auth::user(), $task)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this task.'], 403);
        }
        // Only allow if currently completed
        if ($task->status === 'Completed') {
            $task->status = 'Not Started';
            $task->completed_at = null;
            $task->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Task is not completed.'], 400);
    }
}
