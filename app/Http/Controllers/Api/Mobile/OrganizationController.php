<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Organization;

class OrganizationController extends Controller
{
    /**
     * List organizations for mobile API, requires valid token.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $organizations = Organization::all('id', 'name', 'phone', 'email', 'address');

        return response()->json([
            'success' => true,
            'organizations' => $organizations,
        ]);
    }

    //Create organization API for mobile app (requires token)
    public function store(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $messages = [
            'name.required' => 'Please enter the Company name',
            'name.regex' => 'Company name must start with a letter',
            'name.max' => 'Company name may not exceed 255 characters',
            'industry_type.required' => 'Please select an industry',
            'industry_type.exists' => 'Selected industry is not valid',
            'organization_type.required' => 'Please select an Company type',
            'website.url' => 'Please enter a valid website URL (e.g. https://example.com)',
            'address.required' => 'Please provide an address for the Company',
            'address.max' => 'Address may not exceed 255 characters',
            'phone.regex' => 'Phone number must be valid and include country code if applicable',
            'phone.max' => 'Phone number may not exceed 20 characters',
            'email.email' => 'Please enter a valid email address',
            'email.max' => 'Email address may not exceed 255 characters',
            'number_of_employees.min' => 'Number of employees must be at least 1',
            'number_of_employees.integer' => 'Number of employees must be a whole number',
            'user_owner_id.required' => 'Please select an account owner',
            'user_owner_id.exists' => 'Selected account owner is not valid'
        ];

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'regex:/^[A-Za-z].*/',
                'max:255',
                'unique:organizations,name'
            ],
            'industry_type' => 'required|integer|exists:industries,id',
            'organization_type' => 'required|integer',
            'website' => 'nullable|string|max:255|url',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\-\s]{7,20}$/'],
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'number_of_employees' => 'nullable|integer|min:1',
            'user_owner_id' => 'required|integer|exists:users,id',
        ], $messages);

        $nameExists = Organization::where('name', $validated['name'])->exists();
        if ($nameExists) {
            return response()->json([
                'success' => false,
                'message' => 'An organization with this name already exists.',
            ], 409);
        }

        $organization = Organization::create($validated);

        return response()->json([
            'success' => true,
            'organization' => $organization->id,
        ], 201);
    }

    //Get organization details API for mobile app (requires token)
    public function show($id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $organization = Organization::find($id);
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
            ], 404);
        }

        // Return organization details with contact persons list and leads & deals list
        $organization->load(['contactPersons', 'leads', 'deals']);

        //contact persons show only id, first name, last name, email, phone
        $organization->contactPersons->transform(function ($person) {
            return [
                'id' => $person->id,
                'full_name' => $person->first_name . ' ' . $person->last_name,
                'email' => $person->email,
                'mobile' => $person->mobile,
            ];
        });

        //leads & deals show only id title, contact person name, and leads converted or not
        $organization->leads->transform(function ($lead) {
            return [
                'id' => $lead->id,
                'title' => $lead->title,
                'contact_person_name' => $lead->person ? $lead->person->first_name . ' ' . $lead->person->last_name : '-',
                'is_converted' => $lead->converted_at ? 'Converted' : 'Not Converted',
            ];
        });

        //deals show only id title and contact person name
        $organization->deals->transform(function ($deal) {
            return [
                'id' => $deal->id,
                'title' => $deal->title,
                'contact_person_name' => $deal->person ? $deal->person->first_name . ' ' . $deal->person->last_name : '-',
                'stage' => $deal->stage_name ?: '-',
            ];
        });



        return response()->json([
            'success' => true,
            'organization' => $organization,
        ]);
    }

    //Update the organization API for mobile app (requires token)
    public function update(Request $request, $id)
    {
        // Implementation for updating organization details
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!$user->hasCrmPermission('edit_crm_organization_guard')) {
            return $this->permissionDenied();
        }

        $organization = Organization::find($id);
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
            ], 404);
        }

        $messages = [
            'name.required' => 'Please enter the Company name',
            'name.regex' => 'Company name must start with a letter',
            'name.max' => 'Company name may not exceed 255 characters',
            'industry_type.required' => 'Please select an industry',
            'industry_type.exists' => 'Selected industry is not valid',
            'organization_type.required' => 'Please select an Company type',
            'website.url' => 'Please enter a valid website URL (e.g. https://example.com)',
            'address.required' => 'Please provide an address for the Company',
            'address.max' => 'Address may not exceed 255 characters',
            'phone.regex' => 'Phone number must be valid and include country code if applicable',
            'phone.max' => 'Phone number may not exceed 20 characters',
            'email.email' => 'Please enter a valid email address',
            'email.max' => 'Email address may not exceed 255 characters',
            'number_of_employees.min' => 'Number of employees must be at least 1',
            'number_of_employees.integer' => 'Number of employees must be a whole number',
            'user_owner_id.required' => 'Please select an account owner',
            'user_owner_id.exists' => 'Selected account owner is not valid'
        ];

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'regex:/^[A-Za-z].*/',
                'max:255',
                function($attribute, $value, $fail) use ($id) {
                    if (\App\Models\Organization::where('name', $value)->where('id', '!=', $id)->exists()) {
                        $fail('The organization name has already been taken.');
                    }
                }
            ],
            'industry_type' => 'required|integer|exists:industries,id',
            'organization_type' => 'required|integer',
            'website' => 'nullable|string|max:255|url',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\-\s]{7,20}$/'],
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'number_of_employees' => 'nullable|integer|min:1',
            'user_owner_id' => 'required|integer|exists:users,id',
        ], $messages);

        $organization->update($validated);

        return response()->json([
            "success" => true,
            "message" => "Organization updated successfully",
        ]);
    }

    //Delete the organization API for mobile app (requires token)
    public function destroy($id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!$user->hasCrmPermission('delete_crm_organization_guard')) {
            return $this->permissionDenied();
        }

        $organization = Organization::find($id);
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
            ], 404);
        }
        // cross check if organization has related leads or deals before deletion
        if ($organization->leads()->count() > 0 || $organization->deals()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete organization with related leads or deals',
            ], 400);
        }
        //check if organization has related contact persons
        if ($organization->contactPersons()->count() > 0) {
            //delete related contact persons
            $organization->contactPersons()->delete();
        }
        //check company owners if any and delete
        if ($organization->customers()->count() > 0) {
            //delete related company owners
            $organization->customers()->delete();
        }
        $organization->delete();

        return response()->json([
            'success' => true,
            'message' => 'Organization deleted successfully',
        ]);
    }

      // AJAX: Create new organization (minimal fields)
    public function CompanyCreate(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);    
        }
        try {
            // Custom messages for AJAX validation so client receives friendly text
            $messages = [
                'org_name.required' => 'Please enter the Company name',
                'org_name.max' => 'Company name may not exceed 255 characters.',
                'org_name.regex' => 'Company name must start with a letter.',
                'org_address.required' => 'Please provide an address for the Company',
                'org_address.max' => 'Address may not exceed 255 characters.',
                'org_phone.regex' => 'Phone number must be valid and include country code if applicable.',
                'org_phone.max' => 'Phone number may not exceed 20 characters.',
                'org_phone.unique' => 'This phone number is already in use for another organization.',
                'org_website.url' => 'Please enter a valid Website URL (e.g. https://example.com).',
                'org_website.max' => 'Website URL may not exceed 255 characters.',
            ];

            $validated = $request->validate([
                'org_name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[A-Za-z].*/',
                    function($attribute, $value, $fail) {
                        if (\App\Models\Organization::where('name', $value)->exists()) {
                            $fail('The organization name has already been taken.');
                        }
                    }
                ],
                'org_address' => 'required|string|max:255',
                'org_phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\-\s]{10,20}$/', 'unique:organizations,phone'],
                'org_website' => 'nullable|string|max:255|url',
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        
        $org = Organization::create([
            'name' => $validated['org_name'],
            'industry_type' => 0,
            'organization_type' => 0,
            'address' => $validated['org_address'] ?? null,
            'phone' => $validated['org_phone'] ?? null,
            'website' => $validated['org_website'] ?? null,
            'user_owner_id' => $user->id,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);
        return response()->json(['id' => $org->id, 'name' => $org->name]);
    }

    // AJAX autocomplete for organization names
    public function autocomplete(Request $request)
    {
        $search = $request->get('q', '');
        if ($search) {
            $results = Organization::where('name', 'like', '%' . $search . '%')
                ->orderBy('name')

                ->get(['id', 'name']);
        } else {
            $results = Organization::orderBy('name')->get(['id', 'name']);
        }
        return response()->json($results);
    }

    private function permissionDenied()
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }
}