<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseNotificationService;
use App\Models\User;

class LeadApiController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'This is the create lead endpoint. Use POST method to create a lead.'], 200);
    }

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'title' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'organization_name' => 'required|string|max:255',
    //         'address' => 'nullable|string',
    //         'website' => 'nullable|string|max:255|url',
    //         'full_name' => ['required', 'string', 'max:255'],
            
    //         'email' => [
    //             'nullable',
    //             'email',
    //             'max:255',
    //             //'unique:people,email',
    //             'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
    //         ],
    //         'mobile' => [
    //             'required',
    //             'string',
    //             'max:20',
    //             'regex:/^\+?[0-9\-\s]{10,20}$/',
    //             //'unique:people,mobile',
    //         ],
    //         'categories' => 'integer',
    //         'lead_source' => 'nullable|integer|max:100',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $validated = $validator->validated();

    //     // Check for duplicate leads
    //     $titleLower = strtolower(trim($validated['title']));
    //     $orgNameInput = !empty($validated['organization_name']) ? trim($validated['organization_name']) : null;
    //     // Try to resolve existing organization and person (if any)
    //     $existingOrg = null;
    //     if ($orgNameInput) {
    //         $existingOrg = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($orgNameInput)])->first();
    //     }

    //     $personNameInput = !empty($validated['full_name']) ? trim($validated['full_name']) : null;
        
    //     $existingPerson = null;
    //     if ($personNameInput) {
    //         $parts = explode(' ', $personNameInput, 2);
            
    //         $first = $parts[0] ?? '';
    //         $last = $parts[1] ?? null;
    //         $personQuery = \App\Models\Person::whereRaw('LOWER(first_name) = ?', [strtolower($first)]);
    //         if ($last) $personQuery->whereRaw('LOWER(last_name) = ?', [strtolower($last)]);
    //         $existingPerson = $personQuery->first();
    //     }

    //      // Build a query that requires title match AND (org match OR person match when provided)
    //     $possibleDuplicateQuery = \App\Models\Lead::whereRaw('LOWER(title) = ?', [$titleLower])
    //         ->where(function ($q) use ($orgNameInput, $existingOrg, $personNameInput, $existingPerson) {
    //             $hasCondition = false;
    //             if ($orgNameInput) {
    //                 if ($existingOrg) {
    //                     $q->where('organization_id', $existingOrg->id);
    //                 } else {
    //                     $q->whereHas('organization', function ($q2) use ($orgNameInput) {
    //                         $q2->whereRaw('LOWER(name) = ?', [strtolower($orgNameInput)]);
    //                     });
    //                 }
    //                 $hasCondition = true;
    //             }

    //             if ($personNameInput) {
    //                 if ($existingPerson) {
    //                     if ($hasCondition) {
    //                         $q->orWhere('people_id', $existingPerson->id);
    //                     } else {
    //                         $q->where('people_id', $existingPerson->id);
    //                     }
    //                 } else {
    //                     if ($hasCondition) {
    //                         $q->orWhereHas('person', function ($q2) use ($personNameInput) {
    //                             $q2->whereRaw("LOWER(CONCAT(first_name, ' ', last_name)) = ?", [strtolower($personNameInput)])
    //                                 ->orWhereRaw("LOWER(first_name) = ?", [strtolower($personNameInput)]);
    //                         });
    //                     } else {
    //                         $q->whereHas('person', function ($q2) use ($personNameInput) {
    //                             $q2->whereRaw("LOWER(CONCAT(first_name, ' ', last_name)) = ?", [strtolower($personNameInput)])
    //                                 ->orWhereRaw("LOWER(first_name) = ?", [strtolower($personNameInput)]);
    //                         });
    //                     }
    //                 }
    //                 $hasCondition = true;
    //             }

    //             // If neither org nor person provided, then any lead with same title is considered a duplicate.
    //             if (!$orgNameInput && !$personNameInput) {
    //                 $q->whereRaw('1 = 1');
    //             }
    //         });

    //     $duplicate = $possibleDuplicateQuery->first();
        
    //     if ($duplicate) {
    //         return response()->json([
    //                 'message' => 'Duplicate lead found with the same title.',
    //                 'duplicate' => true,
    //                 'lead' => $duplicate->id
    //             ], 409);
    //     }

    //     // --- Organization ---
    //     $organizationId = null;
    //     if (!empty($validated['organization_name'])) {
    //         $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($validated['organization_name'])])->first();
    //         if (!$org) {
    //             $org = Organization::create([
    //                     'name' => $validated['organization_name'],
    //                     'industry_type' => 0,
    //                     'organization_type' => 0,
    //                     'address' => $validated['address'] ?? null,
    //                     'phone' => $validated['mobile'] ?? null,
    //                     'website' => $validated['website'] ?? null,
    //                     'created_by' => null,
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                     'updated_by' => null,
    //                 ]);
    //         }

    //         $organizationId = $org->id;
    //     }

    //      // --- Contact Person (People) ---
    //     $peopleId = null;
    //     if (!empty($validated['full_name'])) {
    //         $personName = trim($validated['full_name']);
           
    //         $parts = explode(' ', $personName, 2); // limit to 2 parts only
            
    //         $firstName = $parts[0] ?? '';
    //         $lastName = $parts[1] ?? null;

    //         // Check if mobile exists in SAME organization
    //         $mobileExists = Person::where('mobile', $validated['mobile'])
    //             ->where('organization_id', '!=', $organizationId)
    //             ->first();

    //         if ($mobileExists) {
    //             return response()->json(['errors' => ['mobile' => ['The mobile number is already associated with another person.']]], 422);
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 1. Check mobile in SAME organization
    //         |--------------------------------------------------------------------------
    //         */
    //         $sameOrgMobile = \App\Models\Person::where('mobile', $validated['mobile'])
    //             ->where('organization_id', $organizationId)
    //             ->first();

    //         if ($sameOrgMobile) {
    //             // Try to fetch by first_name and last_name
    //             $query = \App\Models\Person::whereRaw('LOWER(first_name) = ?', [strtolower($firstName)]);
    //             if ($lastName) {
    //                 $query->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)]);
    //             }
    //             $person = $query->where('mobile', $validated['mobile'])->where('organization_id', $organizationId)->first();

    //             if (!$person) {
    //                 $person = Person::create([
    //                         'first_name' => $firstName,
    //                         'last_name' => $lastName ?? null,
    //                         'email' => $validated['email'],
    //                         'mobile' => $validated['mobile'],
    //                         'organization_id' => $organizationId,
    //                         'created_by' => null,
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                         'updated_by' => null,
    //                     ]);
    //             }
    //             $peopleId = $person->id;
    //         }else {
    //             // Create new person
    //             $person = Person::create([
    //                     'first_name' => $firstName,
    //                     'last_name' => $lastName ?? null,
    //                     'email' => $validated['email'],
    //                     'mobile' => $validated['mobile'],
    //                     'organization_id' => $organizationId,
    //                     'created_by' => null,
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                     'updated_by' => null,
    //                 ]);
    //             $peopleId = $person->id;
    //         }          
    //     }
        
    //     // --- Create Lead ---
    //             $lead = Lead::create([
    //                     'title' => $validated['title'],
    //                     'description' => $validated['description'] ?? null,
    //                     'organization_id' => $organizationId,
    //                     'people_id' => $peopleId,
    //                     'status' => 'Not Contacted',
    //                     'lead_source' => $validated['lead_source'] ?? null,
    //                     'category' =>$validated['categories'],
    //                     'user_owner_id' => null,
    //                     'created_by' => null,
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                     'updated_by' => null,
    //             ]); 
                
    //         // }
    //     return response()->json(['lead' => $lead], 201);
    // }

    public function store(Request $request)
{

 
 
    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'organization_name' => 'required|string|max:255',
        'address' => 'nullable|string',
        'website' => 'nullable|string|max:255|url',
        'full_name' => ['required', 'string', 'max:255'],
        'email' => [
            'nullable',
            'email',
            'max:255',
            'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
        ],
        'mobile' => [
            'required',
            'string',
            'max:20',
            'regex:/^\+?[0-9\-\s]{10,20}$/',
        ],
        'categories' => 'integer',
        'lead_source' => 'nullable|integer|max:100',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $validated = $validator->validated();

    // ============================================================
    // STEP 1: Cross-check email & mobile in the people table
    // ============================================================
    $existingPersonByMobile = \App\Models\Person::where('mobile', $validated['mobile'])->first();
    $existingPersonByEmail  = !empty($validated['email'])
        ? \App\Models\Person::where('email', $validated['email'])->first()
        : null;

    // Pick whichever matched person we found (mobile takes priority)
    $matchedPerson = $existingPersonByMobile ?? $existingPersonByEmail;

    if ($matchedPerson) {
        // ============================================================
        // STEP 2: Person exists → check if this lead title is already
        //         registered against that person
        // ============================================================
        $titleLower = strtolower(trim($validated['title']));

        $existingLead = \App\Models\Lead::whereRaw('LOWER(title) = ?', [$titleLower])
            ->where('people_id', $matchedPerson->id)
            ->first();

        if ($existingLead) {
            // Lead already registered for this person
            return response()->json([
                'message'    => 'This email or mobile number is already registered having this page title as lead.',
                'duplicate'  => true,
                'lead'       => $existingLead->id,
                'person'     => $matchedPerson->id,
            ], 409);
        }

        // Person exists but NO lead with this title → reuse person, create lead below
        $peopleId = $matchedPerson->id;

        // Resolve organization from the matched person or from input
        $organizationId = $matchedPerson->organization_id;

    } else {
        // ============================================================
        // STEP 3: No matching person → run original duplicate-lead
        //         check by title + org/person name, then create both
        // ============================================================
        $titleLower    = strtolower(trim($validated['title']));
        $orgNameInput  = !empty($validated['organization_name']) ? trim($validated['organization_name']) : null;
        $personNameInput = !empty($validated['full_name']) ? trim($validated['full_name']) : null;

        $existingOrg = null;
        if ($orgNameInput) {
            $existingOrg = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($orgNameInput)])->first();
        }

        $existingPerson = null;
        if ($personNameInput) {
            $parts = explode(' ', $personNameInput, 2);
            $first = $parts[0] ?? '';
            $last  = $parts[1] ?? null;
            $personQuery = \App\Models\Person::whereRaw('LOWER(first_name) = ?', [strtolower($first)]);
            if ($last) $personQuery->whereRaw('LOWER(last_name) = ?', [strtolower($last)]);
            $existingPerson = $personQuery->first();
        }

        $possibleDuplicateQuery = \App\Models\Lead::whereRaw('LOWER(title) = ?', [$titleLower])
            ->where(function ($q) use ($orgNameInput, $existingOrg, $personNameInput, $existingPerson) {
                $hasCondition = false;

                if ($orgNameInput) {
                    if ($existingOrg) {
                        $q->where('organization_id', $existingOrg->id);
                    } else {
                        $q->whereHas('organization', function ($q2) use ($orgNameInput) {
                            $q2->whereRaw('LOWER(name) = ?', [strtolower($orgNameInput)]);
                        });
                    }
                    $hasCondition = true;
                }

                // if ($personNameInput) {
                //     $closure = function ($q2) use ($personNameInput) {
                //         $q2->whereRaw("LOWER(CONCAT(first_name, ' ', last_name)) = ?", [strtolower($personNameInput)])
                //            ->orWhereRaw("LOWER(first_name) = ?", [strtolower($personNameInput)]);
                //     };

                //     if ($existingPerson) {
                //         $hasCondition
                //             ? $q->orWhere('people_id', $existingPerson->id)
                //             : $q->where('people_id', $existingPerson->id);
                //     } else {
                //         $hasCondition
                //             ? $q->orWhereHas('person', $closure)
                //             : $q->whereHas('person', $closure);
                //     }
                //     $hasCondition = true;
                // }

                if (!$orgNameInput ) {
                    $q->whereRaw('1 = 1');
                }
            });

        $duplicate = $possibleDuplicateQuery->first();

        if ($duplicate) {
            return response()->json([
                'message'   => 'Already Registered: Already registered with the same organization or person name.',
                'duplicate' => true,
                'lead'      => $duplicate->id,
            ], 409);
        }

        // --- Organization ---
        $organizationId = null;
        if (!empty($validated['organization_name'])) {
            $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($validated['organization_name'])])->first();
            if (!$org) {
                $org = \App\Models\Organization::create([
                    'name'              => $validated['organization_name'],
                    'industry_type'     => 0,
                    'organization_type' => 0,
                    'address'           => $validated['address'] ?? null,
                    'phone'             => $validated['mobile'] ?? null,
                    'website'           => $validated['website'] ?? null,
                    'created_by'        => null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                    'updated_by'        => null,
                ]);
            }
            $organizationId = $org->id;
        }

        // --- Contact Person (People) ---
        $peopleId    = null;
        $personName  = trim($validated['full_name']);
        $parts       = explode(' ', $personName, 2);
        $firstName   = $parts[0] ?? '';
        $lastName    = $parts[1] ?? null;

        // Block mobile already used in a DIFFERENT organization
        $mobileExists = \App\Models\Person::where('mobile', $validated['mobile'])
            ->where('organization_id', '!=', $organizationId)
            ->first();

        if ($mobileExists) {
            return response()->json([
                'errors' => ['mobile' => ['The mobile number is already associated with another person.']]
            ], 422);
        }

        // Check mobile in SAME organization
        $sameOrgMobile = \App\Models\Person::where('mobile', $validated['mobile'])
            ->where('organization_id', $organizationId)
            ->first();

        if ($sameOrgMobile) {
            $query = \App\Models\Person::whereRaw('LOWER(first_name) = ?', [strtolower($firstName)]);
            if ($lastName) {
                $query->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)]);
            }
            $person = $query->where('mobile', $validated['mobile'])
                            ->where('organization_id', $organizationId)
                            ->first();

            if (!$person) {
                $person = \App\Models\Person::create([
                    'first_name'      => $firstName,
                    'last_name'       => $lastName ?? null,
                    'email'           => $validated['email'],
                    'mobile'          => $validated['mobile'],
                    'organization_id' => $organizationId,
                    'created_by'      => null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                    'updated_by'      => null,
                ]);
            }
            $peopleId = $person->id;
        } else {
            $person = \App\Models\Person::create([
                'first_name'      => $firstName,
                'last_name'       => $lastName ?? null,
                'email'           => $validated['email'],
                'mobile'          => $validated['mobile'],
                'organization_id' => $organizationId,
                'created_by'      => null,
                'created_at'      => now(),
                'updated_at'      => now(),
                'updated_by'      => null,
            ]);
            $peopleId = $person->id;
        }
    }

    // ============================================================
    // STEP 4: Create the Lead (reached only if no duplicate found)
    // ============================================================
    $lead = \App\Models\Lead::create([
        'title'         => $validated['title'],
        'description'   => $validated['description'] ?? null,
        'organization_id' => $organizationId,
        'people_id'     => $peopleId,
        'status'        => 'Not Contacted',
        'lead_source'   => $validated['lead_source'] ?? null,
        'category'      => $validated['categories'],
        'user_owner_id' => null,
        'created_by'    => null,
        'created_at'    => now(),
        'updated_at'    => now(),
        'updated_by'    => null,
    ]);

    // ============================================================
    // STEP 5: Send Push Notification
    // ============================================================

    try {
        $notificationService = app(FirebaseNotificationService::class);

        // 👉 Example: notify assigned user (if exists)
        // if (!empty($lead->user_owner_id)) {

        //     $assignedUser = User::find($lead->user_owner_id);

        //     if ($assignedUser && !empty($assignedUser->fcm_token)) {
        //         $notificationService->sendNotification(
        //             $assignedUser->fcm_token,
        //             "New Lead Assigned",
        //             "Lead: " . $lead->title,
        //             [
        //                 'type' => 'lead',
        //                 'lead_id' => (string)$lead->id
        //             ]
        //         );
        //     }
        // }

        // 👉 Example: notify admins
        $admins = User::whereIn('crm_role_type', [1, 2])->get();

        foreach ($admins as $admin) {
            // echo "Checking admin (User ID: {$admin->id}) with FCM token: {$admin->fcm_token}\n";
            if (!empty($admin->fcm_token)) {
                $notificationService->sendNotification(
                    $admin->fcm_token,
                    "New Lead Created",
                    "New Lead: " . $lead->title,
                    [
                        'type' => 'lead',
                        'lead_id' => (string)$lead->id
                    ]
                );

            }
                
        }

        $employeeUsers = User::where('email', 'marketing@isarvait.com')->get();
        foreach ($employeeUsers as $employee) {
            if (!empty($employee->fcm_token)) {
                $notificationService->sendNotification(
                    $employee->fcm_token,
                    "New Lead Created",
                    "New Lead: " . $lead->title,
                    [
                        'type' => 'lead',
                        'lead_id' => (string)$lead->id
                    ]
                );
            }
        }

         //send mail to admin & marketing@isarvait.com
        try {
            //$adminEmails = User::where('crm_role_type', 1)->pluck('email')->toArray();

            //Add two eamils to receive notification for new lead creation
            $adminEmails = [];
            $adminEmails[] = 'deepkiran@isarvait.com'; // Add more emails as needed
            $adminEmails[] = 'marketing@isarvait.com'; // Add more emails as needed
            $adminEmails[] = 'bdm@isarvait.com';
            // $adminEmails[] = 'ashoka@isarvait.com';

            \Mail::to($adminEmails)->send(new \App\Mail\NewLeadCreated($lead));
        } catch (\Exception $e) {
            \Log::error('Email Notification Error: ' . $e->getMessage());
        }

        

    } catch (\Exception $e) {
       \Log::error('Push Notification Error: ' . $e->getMessage());
    }

    return response()->json(['lead' => ['id' => $lead->id, 'title' => $lead->title]], 201);
}

    public function checkDuplicate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organization_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for duplicate leads
        $organization = Organization::where('name', $request->organization_name)->first();
        $person = Person::where('email', $request->email)->first();

        if ($organization && $person) {
            $existingLead = Lead::where('organization_id', $organization->id)
                ->where('people_id', $person->id)
                ->first();

            if ($existingLead) {
                return response()->json([
                    'message' => 'Duplicate lead found.',
                    'duplicate' => true,
                    'lead' => $existingLead->id,
                ], 200);
            }
        }

        return response()->json(['duplicate' => false], 200);
    }

    //passing type to filter categories based on type with general type as well which can be used for both leads and deals. This is to avoid creating separate category tables for leads and deals and to allow flexibility in categorization.

    function product_categories($type = null)
    {
        $categories = \App\Models\ProductCategory::when($type, function ($query, $type) {
            return $query->where('type', $type);
        })->get(['id', 'category_name', 'type']);
        return response()->json(['categories' => $categories], 200);
    }
}
