<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Organization;

class CompanyOwnerController extends Controller
{
    /**
     * List company owners for a given organization (requires valid token).
     */
    public function index(Request $request, $organizationId)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        // Assuming Organization has a relation 'owners' or a user_owner_id field
        $organization = Organization::find($organizationId);
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
            ], 404);
        }
        $owner = Customer::where('organization_id', $organizationId)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ];
            });

        return response()->json([
            'success' => true,
            'owner' => $owner,
        ]);
    }
}
