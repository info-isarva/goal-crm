<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        if(!auth()->user()->hasCrmPermission('view_crm_activity_log_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $query = ActivityLog::with('user');
        if ($request->filled('user_name')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->user_name . '%');
                $q->orWhere('email', 'like', '%' . $request->user_name . '%');
                $q->orWhere('module', 'like', '%' . $request->user_name . '%');
                $q->orWhere('action', 'like', '%' . $request->user_name . '%');
                $q->orWhere('type', 'like', '%' . $request->user_name . '%');
            });
        }
        if($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        $logs = $query->orderByDesc('created_at')->paginate(30)->appends($request->all());
        return view('activity_logs.index', compact('logs'));
    }
    public function show(ActivityLog $activityLog)
    {
        if (!auth()->user()->hasCrmPermission('view_crm_activity_log_guard')) {
            abort(403, 'Unauthorized action.');
        }
        return view('activity_logs.show', compact('activityLog'));
    }
}
