<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $role = $user->crm_role_type; // Assuming 'role' is a field in the users table

        $data = [];

        if ($role === 1 || $role === 0) {
            $data['total_deals_value'] = Deal::sum('amount');
            $data['active_deals_count'] = Deal::where('status', 'Open')->count();
        } else {
            $data['total_deals_value'] = Deal::where('created_by', $user->id)
                ->orWhere('assigned_id', $user->id)
                                  ->orWhere('user_owner_id', $user->id)
                ->sum('amount');
            $data['active_deals_count'] = Deal::where('status', 'Open')
                ->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                          ->orWhere('assigned_id', $user->id)
                                  ->orWhere('user_owner_id', $user->id);
                })
                ->count();
        }

        if($role === 1 || $role === 0) {
            $data['today_leads_count'] = Lead::whereDate('created_at', today())->count();
            $data['high_leads_count'] = Lead::where('label', 'high')->count();
            $data['open_deals_count'] = Deal::where('status', 'open')->count();
            $data['pending_tasks_count'] = Task::where('status', '!=', 'Completed')->count();

            $deals = Deal::with(['organization', 'person', 'owner'])->where('label', 'high')
                                            ->whereNotIn('status', ['Closed Won', 'Closed Lost'])
                                            ->get();

            //task data fetch organizatuion name.
            $baseQuery = Task::where('status', '!=', 'Completed')
            ->where('priority', 'high')
            ->whereDate('due_at', '>=', today())
            ->whereDate('due_at', '<=', today()->addDays(2))
            ->get();

        } else {
            $data['today_leads_count'] = Lead::whereDate('created_at', today())
                ->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                          ->orWhere('assigned_id', $user->id)
                                  ->orWhere('user_owner_id', $user->id);
                })
                ->count();
            $data['high_leads_count'] = Lead::where('label', 'high')
                ->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                          ->orWhere('assigned_id', $user->id)
                                  ->orWhere('user_owner_id', $user->id);
                })
                ->count();
            $data['open_deals_count'] = Deal::where('status', 'open')
                ->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                          ->orWhere('assigned_id', $user->id)
                                  ->orWhere('user_owner_id', $user->id);
                })
                ->count();
            $data['pending_tasks_count'] = Task::where('status', '!=', 'Completed')
                ->where(function ($query) use ($user) {
                    $query->where('created_by', $user->id)
                          ->orWhere('user_assigned_id', $user->id);
                })
                ->count();
            $deals = Deal::with(['organization', 'person', 'owner'])
                        ->where('label', 'high')
                        ->whereNotIn('status', ['Closed Won', 'Closed Lost'])
                        ->where(function ($query) use ($user) {
                            $query->where('created_by', $user->id)
                                  ->orWhere('assigned_id', $user->id)
                                  ->orWhere('user_owner_id', $user->id);
                        })
                        ->get();
                        
             //task data fetch organizatuion name.
             $baseQuery = Task::where('status', '!=', 'Completed')
             ->where('priority', 'high')
             ->whereDate('due_at', '>=', today())
             ->whereDate('due_at', '<=', today()->addDays(2))
             ->where(function ($query) use ($user) {
                 $query->where('created_by', $user->id)
                       ->orWhere('user_assigned_id', $user->id);
                })
                ->get();
        }

        
        foreach ($deals as $deal) {
            $data['high_priority_deals'][] = [
                'title' => $deal->title,
                'amount' => $deal->amount,
                'organization_name' => $deal->organization_name,
                'contact_person' => $deal->person_name,
                'stage' => $deal->stage_name,
                'person' => $deal->person,
                'organization' => $deal->organization
            ];
        }

       
        $data['high_priority_tasks'] = $baseQuery->map(function ($task) {
            return [
                'name' => $task->name,
                'due_at' => $task->due_at, //\Carbon\Carbon::parse($task->due_at)->format('d M Y, h:i A'),
                'related_type' => $task->related_type,
                'related_name' => $this->getRelatedName($task),
                'organization' => $this->getRelatedOrganization($task),
            ];
        })->toArray();


        return response()->json($data);
    }

    function getRelatedName($task) {
        if ($task->related_type === 'lead') {
            $related = Lead::find($task->related_id);
            return $related ? $related->title : null;
        } elseif ($task->related_type === 'deal') {
            $related = Deal::find($task->related_id);
            return $related ? $related->title : null;
        }
        return null;
    }

    function getRelatedOrganization($task) {
        if ($task->related_type === 'lead') {
            $related = Lead::find($task->related_id);
            return $related && $related->organization ? $related->organization->name : null;
        } elseif ($task->related_type === 'deal') {
            $related = Deal::find($task->related_id);
            return $related && $related->organization ? $related->organization->name : null;
        }
        return null;
    }
}