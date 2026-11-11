<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TaskReportController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('task_reminder_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser = auth()->user();
        $isAdminOrManager = in_array($currentUser->crm_role_type, [0, 1, 2]);

        [$startDate, $endDate, $period] = ReportController::resolveReportPeriod($request, 'today');

        $tasksQuery = Task::with(['owner', 'reminders'])
            ->filterByDate($startDate, $endDate);

        // If manager, limit to their team members
        if ($currentUser->crm_role_type === 2) {
            $teamMemberIds = \App\Models\User::where('assign_manager', $currentUser->id)->pluck('id');
            $tasksQuery->where(function ($q) use ($currentUser, $teamMemberIds) {
                $q->where('user_owner_id', $currentUser->id)->orWhere('user_assigned_id', $currentUser->id);
                if ($teamMemberIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $teamMemberIds)->orWhereIn('user_assigned_id', $teamMemberIds);
                }
            });
            // $tasksQuery->whereIn('user_owner_id', $teamMemberIds)->orWhere('user_owner_id', $currentUser->id)->orWhere('user_assigned_id', $currentUser->id)->orWhereIn('user_assigned_id', $teamMemberIds);   ;
        } elseif (!$isAdminOrManager) {
            // If regular employee, only show their own tasks
            $tasksQuery->where('user_owner_id', $currentUser->id)->orWhere('user_assigned_id', $currentUser->id);
        }

        $tasks = $tasksQuery->paginate(10)->withQueryString();

        $start = $startDate;
        $end = $endDate;

        return view('reports.task_reminders', compact('tasks', 'startDate', 'endDate', 'start', 'end', 'period'));
    }
}
