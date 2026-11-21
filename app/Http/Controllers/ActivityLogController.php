<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->hasCrmPermission('view_crm_activity_log_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'user_name' => 'nullable|string|max:150',
            'module' => 'nullable|string|max:100',
            'action' => 'nullable|string|max:100',
            'user_id' => 'nullable|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'per_page' => 'nullable|in:10,25,50,100',
        ]);

        $query = ActivityLog::query()->with('user:id,name,email');

        if ($request->filled('user_name')) {
            $term = trim((string) $request->user_name);
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', function ($uq) use ($term) {
                    $uq->where('name', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%');
                })
                    ->orWhere('module', 'like', '%'.$term.'%')
                    ->orWhere('action', 'like', '%'.$term.'%')
                    ->orWhere('type', 'like', '%'.$term.'%')
                    ->orWhere('ip_address', 'like', '%'.$term.'%');
            });
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
        }
        $fySvc = app(\App\Services\FinancialYearService::class);
        [$fyFrom, $fyTo] = $fySvc->range();
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        } else {
            $query->where('created_at', '>=', $fyFrom);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        } else {
            $query->where('created_at', '<=', $fyTo);
        }

        $perPage = (int) ($request->input('per_page') ?: 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $logs = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $modules = ActivityLog::query()
            ->whereNotNull('module')
            ->where('module', '!=', '')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $actions = ActivityLog::query()
            ->whereNotNull('action')
            ->where('action', '!=', '')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $users = User::query()
            ->whereIn('id', ActivityLog::query()->select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        $filters = [
            'user_name' => (string) $request->input('user_name', ''),
            'module' => (string) $request->input('module', ''),
            'action' => (string) $request->input('action', ''),
            'user_id' => $request->input('user_id'),
            'from' => (string) ($request->filled('from') ? $request->input('from') : $fyFrom->toDateString()),
            'to' => (string) ($request->filled('to') ? $request->input('to') : $fyTo->toDateString()),
            'per_page' => $perPage,
            'fy_label' => $fySvc->label(),
        ];

        return view('activity_logs.index', compact(
            'logs',
            'modules',
            'actions',
            'users',
            'filters',
            'perPage'
        ));
    }

    public function show(ActivityLog $activityLog)
    {
        if (! auth()->user()->hasCrmPermission('view_crm_activity_log_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $activityLog->loadMissing('user:id,name,email');

        return view('activity_logs.show', compact('activityLog'));
    }
}
