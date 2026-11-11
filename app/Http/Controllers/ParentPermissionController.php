<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\ParentPermission;

class ParentPermissionController extends Controller
{
    public function store(Request $request)
    {
        if (!in_array((int) (auth()->user()->crm_role_type ?? -1), [0, 1], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:parent_permissions,name',
            
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first('name'),
            ], 422);
        }

        $parent = ParentPermission::create([
            'name' => $request->name,
            'created_by' => auth()->id(),
            'created_at' => now(),
                
        ]);

        return response()->json([
            'success' => true,
            'parent' => $parent,
        ]);
    }

    public function autocomplete(Request $request)
    {
        $query = $request->input('q');
        $results = [];
        if ($query) {
            $parents = \App\Models\ParentPermission::where('name', 'like', "%{$query}%")
                ->orderBy('name')
                ->get();
        } else {
            $parents = \App\Models\ParentPermission::orderBy('name')->get();
        }
        foreach ($parents as $parent) {
            $results[] = [
                'id' => $parent->id,
                'text' => $parent->name
            ];
        }
        return response()->json(['results' => $results]);
    }
}
