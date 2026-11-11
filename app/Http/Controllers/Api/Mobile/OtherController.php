<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\ParentPermission;
use App\Models\Permission;
use App\Models\ProductCategory;
use App\Models\Stage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtherController extends Controller
{
    //lead source list api
    public function leadSourceList(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $leadSources = LeadSource::all('id', 'name');
        return response()->json([
            'success' => true,
            'lead_sources' => $leadSources,
        ]);
    }

    //Lead status list api
    public function leadStatusList(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $leadStatuses = LeadStatus::all('id', 'name');
        return response()->json([
            'success' => true,
            'lead_statuses' => $leadStatuses,
        ]);
    }  
    
    //Priority list api
    public function priorityList(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $priorities = [
            ['value' => 'high', 'name' => 'High'],
            ['value' => 'low', 'name' => 'Low'],
            ['value' => 'normal', 'name' => 'Normal'],
        ];
        return response()->json([
            'success' => true,
            'priorities' => $priorities,    
        ]);

    }

    //Category list api
    public function categoryList(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $categories = ProductCategory::all('id', 'category_name');
        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);

    }

    //User list api
    public function userList(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $usersQuery = \App\Models\User::query();

        // Superadmin can view active and inactive users. Others see active users only.
        if ((int) $user->crm_role_type !== 0) {
            $usersQuery->where('status', 1);
        }

        $users = $usersQuery->orderBy('name')->get(['id', 'name', 'email']);
        return response()->json([
            'success' => true,
            'users' => $users,
        ]); 
    }

    //Deal stage list api
    public function dealStageList(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $dealStages = Stage::all('id', 'name', 'probability');
        return response()->json([
            'success' => true,
            'deal_stages' => $dealStages,
        ]); 
    }

    //User Permission api
    public function userPermissions(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        //dISPLAY Parent permissions with their child permissions
        $permissions =  ParentPermission::with('permissions:id,name,guard_name,crm_permission,parent_id')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
        ]); 
    }

    // Main category (Industry) list api
    public function mainCategoryList(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $mainCategories = \App\Models\Industry::orderBy('name')->get(['id', 'name', 'description']);

        return response()->json([
            'success' => true,
            'main_categories' => $mainCategories,
        ]);
    }

}
