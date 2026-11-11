<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Person;
use App\Models\CallLog;
use App\Support\RecordAccess;

class ContactPersonController extends Controller
{
    /**
     * List contact persons for a given organization (requires valid token).
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

         $contacts = Person::where('organization_id', $organizationId)
            ->get()
            ->map(function ($person) {
                return [
                    'id' => $person->id,
                    'fullname' => trim($person->first_name . ' ' . $person->last_name),
                    'email' => $person->email,
                    'mobile' => $person->mobile,
                ];
            });
        return response()->json([
            'success' => true,
            'contacts' => $contacts,
        ]);
    }

    //List All Contact Persons  API
    public function listAllContacts(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $contacts = Person::paginate(15)
        ->through(function ($person) {
            return [
                'id'       => $person->id,
                'fullname' => trim($person->first_name . ' ' . $person->last_name),
                'email'    => $person->email,
                'mobile'   => $person->mobile,
            ];
        });
        return response()->json([
            'success' => true,
            'contacts' => $contacts,
        ]);
    }

    //Contact Person Details API
    public function show(Request $request, $contactId)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

         $person = Person::find($contactId);
         if (!$person) {
            return response()->json([
                'success' => false,
                'message' => 'Contact person not found',
            ], 404);
         }

        //  $contactDetails = [
        //     'id' => $person->id,
        //     'first_name' => $person->first_name,
        //     'last_name' => $person->last_name,
        //     'email' => $person->email,
        //     'mobile' => $person->mobile,
        //     'phone' => $person->phone,
        //     'designation' => $person->designation,
        //     'address' => $person->address,
        //     // Add other fields as necessary
        //  ];

        $contactDetails = $person;

        return response()->json([
            'success' => true,
            'contact' => $contactDetails,
        ]);
    }
    
    //Create Contact Person API (Optional)
    public function store(Request $request)
    {
        // Implementation for creating a contact person
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        // Validate and create contact person logic here
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^\w+$/'],
            'last_name' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z .\'-]*$/'],
            'gender' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:people,email',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9\-\s]{10,20}$/',
            ],
            'mobile' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+?[0-9\-\s]{10,20}$/',
                'unique:people,mobile',
            ],
            'job_title' => 'nullable|string|max:255',
            'lead_source' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'owner_id' => 'required|integer|exists:users,id',
        ], [
            'first_name.regex' => 'First name must be a single word.',
            'last_name.regex' => 'Last name may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'email.regex' => 'Please enter a valid email address.',
            'phone.regex' => 'Please enter a valid phone number.',
            'mobile.regex' => 'Please enter a valid mobile number.',
        ]);

        $person = new Person();
        $person->first_name = $validated['first_name'];
        $person->last_name = $validated['last_name'] ?? null;
        $person->gender = $validated['gender'] ?? null;
        $person->dob = $validated['dob'] ?? null;
        $person->email = $validated['email'] ?? null;
        $person->phone = $validated['phone'] ?? null;
        $person->mobile = $validated['mobile'];
        $person->job_title = $validated['job_title'] ?? null;
        $person->lead_source = $validated['lead_source'] ?? null;
        $person->address = $validated['address'] ?? null;
        $person->notes = $validated['notes'] ?? null;
        $person->user_owner_id = $validated['owner_id'];
        $person->created_by = $user->id;
        $person->save();

        return response()->json([
            'success' => true,
            'message' => 'Contact person created successfully',
            'contact' => $person->id,
        ], 201);
    }

    //Update Contact Person API (Optional)
    public function update(Request $request, $contactId)
    {
        // Implementation for updating a contact person
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!$user->hasCrmPermission('edit_crm_contact_person_guard')) {
            return $this->permissionDenied();
        }
        // Validate and update contact person logic here
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^\w+$/'],
            'last_name' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z .\'-]*$/'],
            'gender' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:people,email,' . $contactId,
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9\-\s]{10,20}$/',
            ],
            'mobile' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+?[0-9\-\s]{10,20}$/',
                'unique:people,mobile,' . $contactId,
            ],
            'job_title' => 'nullable|string|max:255',
            'lead_source' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'owner_id' => 'required|integer|exists:users,id',
        ], [
            'first_name.regex' => 'First name may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'last_name.regex' => 'Last name may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'email.regex' => 'Please enter a valid email address.',
            'phone.regex' => 'Please enter a valid phone number.',
            'mobile.regex' => 'Please enter a valid mobile number.',
        ]);
        $person = Person::find($contactId);
        if (!$person) {
            return response()->json([
                'success' => false,
                'message' => 'Contact person not found',
            ], 404);
        }
        $person->first_name = $validated['first_name'];
        $person->last_name = $validated['last_name'] ?? null;
        $person->gender = $validated['gender'] ?? null;
        $person->dob = $validated['dob'] ?? null;
        $person->email = $validated['email'] ?? null;
        $person->phone = $validated['phone'] ?? null;
        $person->mobile = $validated['mobile'];
        $person->job_title = $validated['job_title'] ?? null;
        $person->lead_source = $validated['lead_source'] ?? null;
        $person->address = $validated['address'] ?? null;
        $person->notes = $validated['notes'] ?? null;
        $person->user_owner_id = $validated['owner_id'];
        $person->save();

        return response()->json([
            'success' => true,
            'message' => 'Contact person updated successfully',
            'contact' => $person->id,
        ]);
    }

    //Delete Contact Person API (Optional)
    public function destroy(Request $request, $contactId)
    {
        // Implementation for deleting a contact person
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!$user->hasCrmPermission('delete_crm_contact_person_guard')) {
            return $this->permissionDenied();
        }

        $person = Person::find($contactId);
        if (!$person) {
            return response()->json([
                'success' => false,
                'message' => 'Contact person not found',
            ], 404);
        }

        // cross check assign any deals or leads before deleting (optional)
        // (Implementation for checking deals or leads would go here)
        if($person->deals()->count() > 0 || $person->leads()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete contact person assigned to deals or leads',
            ], 400);
        }

        $person->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact person deleted successfully',
        ]);
    }

     // AJAX: Create new People (minimal fields)
    public function ajaxCreate(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);    
        }
        try {
             // Custom messages for AJAX person create
            $messages = [
                'person_first_name.required' => 'Please enter the first name.',
                'person_first_name.max' => 'First name may not exceed 255 characters.',
                'person_first_name.regex' => 'First name must be a single word.',

                'person_last_name.max' => 'Last name may not exceed 255 characters.',
                'person_last_name.regex' => 'Last name may only contain letters, spaces, dots, apostrophes, and hyphens.',

                'person_org_id.required' => 'Please select or enter the Company name',
                'person_org_id.integer' => 'Invalid organization selected.',
                'person_org_id.exists' => 'The selected organization does not exist.',

                'person_email.email' => 'Please enter a valid email address.',
                'person_email.max' => 'Email may not exceed 255 characters.',
                'person_email.regex' => 'Please enter a valid email address.',

                'person_phone.required' => 'Please enter a mobile number for the contact.',
                'person_phone.regex' => 'Please enter a valid mobile number.',
                'person_phone.max' => 'Mobile number may not exceed 20 characters.',
                'person_phone.unique' => 'This mobile number is already used by another contact.',
            ];
            // Validate the request
            $validated = $request->validate([
                'person_first_name' => ['required', 'string', 'max:255', 'regex:/^\w+$/'],
                'person_last_name' =>['nullable', 'string', 'max:255', 'regex:/^[A-Za-z .\'-]*$/'],
                'person_org_id' => 'required|integer|exists:organizations,id',
                'person_email' => 'nullable|email|max:255|regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
                'person_phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\-\s]{10,20}$/', 'unique:people,mobile'],
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        
        // Server-side duplicate check: same first+last name under the same organization
        $existing = Person::where('organization_id', $validated['person_org_id'])
            ->whereRaw('LOWER(first_name) = ?', [strtolower($validated['person_first_name'])])
            ->whereRaw('LOWER(COALESCE(last_name, "")) = ?', [strtolower($validated['person_last_name'] ?? '')])
            ->first();
        if ($existing) {
            return response()->json([
                'duplicate' => true,
                'id' => $existing->id,
                'first_name' => $existing->first_name,
                'last_name' => $existing->last_name,
            ], 200);
        }

        $person = Person::create( [
            'first_name' => $validated['person_first_name'],
            'last_name' => $validated['person_last_name'] ?? null,
            'organization_id' => $validated['person_org_id'],
            'email' => $validated['person_email'] ?? null,
            'mobile' => $validated['person_phone'] ?? null,
            'user_owner_id' => $user->id,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);
        return response()->json(['id' => $person->id, 'first_name' => $person->first_name, 'last_name' => $person->last_name]);
    }

    // AJAX autocomplete for person (contact) names
    public function autocomplete(Request $request)
    {
        $search = $request->get('q', '');
        $orgId = $request->get('organization', '');
        $query = Person::query();
        if ($orgId) {
            $query->where('organization_id', $orgId);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%');
            });
        }
        $results = $query->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        return response()->json($results);
    }

    public function autosearch(Request $request)
    {
        $search = $request->get('q', '');
        $orgId = $request->get('organization', '');

        $personQuery = Person::query();
        if ($orgId) {
            $personQuery->where('organization_id', $orgId);
        }
        if ($search) {
            $personQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhereRaw("TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) LIKE ?", ['%' . $search . '%'])
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('mobile', 'like', '%' . $search . '%');
            });
        }
        $contacts = $personQuery->orderBy('first_name')->get()->map(function ($person) {
            return [
                'id'       => $person->id,
                'fullname' => trim($person->first_name . ' ' . $person->last_name),
                'email'    => $person->email,
                'mobile'   => $person->mobile,
                'type'     => 'contact',
            ];
        });

        $callLogQuery = CallLog::query();
        if (!RecordAccess::isAdmin($request->user())) {
            $callLogQuery->where('created_by', $request->user()?->id);
        }
        if ($search) {
            $callLogQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('mobile_number', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        $callLogs = $callLogQuery->orderBy('name')->get()->map(function ($call) {
            return [
                'id'       => $call->id,
                'fullname' => $call->name,
                'email'    => $call->email,
                'mobile'   => $call->mobile_number,
                'type'     => 'call_log',
            ];
        });

        $results = $contacts->merge($callLogs)->values();

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