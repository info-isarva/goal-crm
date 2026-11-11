<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Industry;
use App\Models\User;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use App\Models\Person as Contact;

class LeadController extends Controller
{
    public function __construct()
    {
        // Apply middleware to block create/edit/delete actions when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'create',
            'store',
            'edit',
            'update',
            'destroy'
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $view = request('view');
        $query = Lead::with(['customer', 'organization', 'person', 'owner', 'leadSource']);

        // Role-based base filter
        if (!($user->crm_role_type === 0 || $user->crm_role_type === 1 || $user->crm_role_type === 2)) {
            if( $user->email === 'marketing@isarvait.com') {
              $query->where(function ($q) use ($user) {
                  $q->where('created_by', $user->id)
                      ->orWhere('user_owner_id', $user->id)
                      ->orWhereNull('created_by')
                      ->orWhere('created_by', '')
                      ->orWhereNull('user_owner_id')
                      ->orWhere('user_owner_id', '');
              });
            }else {
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhere('user_owner_id', $user->id);
                });
            }
        } elseif ($user->crm_role_type === 2) { // Manager

            $employeeIds = User::where('assign_manager', $user->id)->pluck('id');
            $query->where(function ($q) use ($user, $employeeIds) {
                $q->where('created_by', $user->id)
                    ->orWhere('user_owner_id', $user->id)
                    ->orWhereNull('created_by')
                    ->orWhere('created_by', '')
                    ->orWhereIn('created_by', $employeeIds)
                    ->orWhereIn('user_owner_id', $employeeIds);
            });

            \Log::info('Manager Lead Query Debug:', [
                'manager_id' => $user->id,
                'employee_ids' => $employeeIds,
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);
        }

        // View filter
        switch ($view) {
            case 'Converted Leads':
            case 'All Converted Leads':
                $query->whereNotNull('converted_at');
                break;
            case 'Junk Leads':
                $query->where('status', 'Junk Lead');
                break;
            case 'Mailing Labels':
                $query->whereNotNull('mailing_label'); // Adjust if you have a different field
                break;
            case 'My Converted Leads':
                $query->whereNotNull('converted_at')->where('user_owner_id', $user->id);
                break;
            case 'My Leads':
                $query->where('user_owner_id', $user->id);
                break;
            case 'Not Qualified Leads':
                $query->where('status', 'not_qualified');
                break;
            case 'Open Leads':
                $query->whereNotIn('status', ['Lost Lead'])
                    ->whereNull('converted_at');
                break;
            case 'Recently Created Leads':
                $query->orderBy('created_at', 'desc');
                break;
            case 'Recently Modified Leads':
                $query->orderBy('updated_at', 'desc');
                break;
            case "Today's Leads":
                $query->whereDate('created_at', now()->toDateString());
                break;
            case 'Unread Leads':
                $query->where('is_read', false); // Add this field if needed
                break;
            case 'Unsubscribed Leads':
                $query->where('is_unsubscribed', true); // Add this field if needed
                break;
            case 'All Leads':
            default:
                // No additional filter
                break;
        }

        // Search/Filter logic
        // Prefer searching by contact (person name). Keep fallback to title for compatibility.
        $contact = request('contact');
        $title = request('title');
        $label = request('label');
        $lead_source = request('lead_source');
        $owner = request('owner');
        $category = request('category');
        $lead_status = request('lead_status');

        if ($contact) {
            $contactTerm = $contact;
            //need to search company name also if contact is given and company name is not given
            $query->whereHas('person', function ($q) use ($contactTerm) {
                $q->where('first_name', 'like', "%{$contactTerm}%")
                    ->orWhere('last_name', 'like', "%{$contactTerm}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$contactTerm}%"]);
            })->orWhereHas('organization', function ($q) use ($contactTerm) {
                $q->where('name', 'like', "%{$contactTerm}%");
            })->orWhere('title', 'like', "%{$contactTerm}%");
            
        } elseif ($title) {
            $query->where('title', 'like', "%$title%");
        }

        if ($label) {
            $query->where('label', $label);
        }
        if ($lead_source) {
            $query->where('lead_source', $lead_source);
        }
        if ($owner) {
            $query->where('user_owner_id', $owner);
        }
        if ($category) {
            \Log::info('Category Filter Debug:', [
                'category_input' => $category,
                'query_before' => $query->toSql(),
            ]);

            $query->whereRaw("FIND_IN_SET(?, category)", [$category]);

            \Log::info('Query After Applying Filter:', ['query_after' => $query->toSql()]);
        }
        if ($lead_status) {
            $query->where('status', $lead_status);
        } elseif ($view !== 'Junk Leads') {
            // Hide Junk Lead by default; only show when Junk is selected in filter or Junk Leads view
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Junk Lead');
            });
        }

        // Default: show only unconverted unless view is Converted Leads/All Converted Leads/My Converted Leads
        if (!in_array($view, ['Converted Leads', 'All Converted Leads', 'My Converted Leads'])) {
            $query->whereNull('converted_at');
        }

        // For filter dropdowns
        $leadSources = \App\Models\LeadSource::orderBy('name')->get();
        $owners = User::whereNotIn('crm_role_type', [0])->orderBy('name')->get();
        $priorities = [
            ['value' => 'high', 'label' => 'High'],
            ['value' => 'normal', 'label' => 'Normal'],
            ['value' => 'low', 'label' => 'Low'],
        ];

        // Apply date filters (start_date / end_date) if provided. Respect selected financial year
        // by intersecting the requested range with the FY range. If the intersection is empty,
        // return no results.
        $selectedFyId = session('selected_financial_year', null);
        $fyFrom = null;
        $fyTo = null;
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
            if ($fy) {
                $fyFrom = \Carbon\Carbon::parse($fy->from_date)->startOfDay();
                $fyTo = \Carbon\Carbon::parse($fy->to_date)->endOfDay();
            }
        }

        $reqStart = request('start_date');
        $reqEnd = request('end_date');
        try {
            $start = $reqStart ? \Carbon\Carbon::parse($reqStart)->startOfDay() : null;
        } catch (\Exception $e) {
            $start = null;
        }
        try {
            $end = $reqEnd ? \Carbon\Carbon::parse($reqEnd)->endOfDay() : null;
        } catch (\Exception $e) {
            $end = null;
        }

        if ($start || $end) {
            // Intersect with FY bounds if present
            if ($fyFrom) {
                if ($start && $start->lt($fyFrom))
                    $start = $fyFrom;
                if (!$start)
                    $start = $fyFrom;
            }
            if ($fyTo) {
                if ($end && $end->gt($fyTo))
                    $end = $fyTo;
                if (!$end)
                    $end = $fyTo;
            }

            $dateField = in_array($view, ['Converted Leads', 'All Converted Leads', 'My Converted Leads']) ? 'converted_at' : 'created_at';
            // If both exist ensure valid range
            if ($start && $end && $start->gt($end)) {
                // No results possible
                $query->whereRaw('0 = 1');
            } else {
                if ($start && $end) {
                    $query->whereBetween($dateField, [$start, $end]);
                } elseif ($start) {
                    $query->where($dateField, '>=', $start);
                } elseif ($end) {
                    $query->where($dateField, '<=', $end);
                }
            }
        } else {
            // No explicit date filters; apply FY bounds if set
            if ($fyFrom && $fyTo) {
                $dateField = in_array($view, ['Converted Leads', 'All Converted Leads', 'My Converted Leads']) ? 'converted_at' : 'created_at';
                $query->whereBetween($dateField, [$fyFrom, $fyTo]);
            }
        }

        // Default sorting by updated_at in descending order
        $sort = $request->get('sort', 'updated_at');
        if (!in_array($sort, ['updated_at', 'created_at', 'title', 'status', 'amount', 'converted_at', 'id'], true)) {
            $sort = 'updated_at';
        }
        $order = strtolower((string) $request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $order);

        $leads = $query->orderByDesc('updated_at')
            ->paginate(15)
            ->appends(request()->except(['page', 'view']))
            ->appends(['view' => $view]);

        // Fetch active categories for the filter
        $categories = \App\Models\ProductCategory::where('status', 1)->orderBy('category_name')->get();
        if (!$categories || $categories->isEmpty()) {
            $categories = collect(['No Categories Available']); // Default fallback
        }

        // Fetch category names for each lead
        $leads->getCollection()->transform(function ($lead) {
            $categoryIds = explode(',', $lead->category ?? '');
            $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
            return $lead;
        });

        // For status filter dropdown
        $leadStatuses = LeadStatus::orderBy('name')->get();

        return view('leads.index', compact('leads', 'leadSources', 'priorities', 'owners', 'user', 'categories', 'leadStatuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_leads_guard')) {
            abort(403, 'Unauthorized action.');
        }

        // --- Financial Year Check ---

        // Prevent creating leads when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Creating leads is disabled for historical years.');
            }
        }
        $users = User::where('status', 1)->orderBy('name')->get();
        $currentUser = auth()->user();
        $leadSources = \App\Models\LeadSource::orderBy('name')->get();
        $leadStatuses = LeadStatus::orderBy('name')->get();
        $organizations = \App\Models\Organization::orderBy('name')->get();
        $countries = [
            'India', 'United Arab Emirates', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Singapore', 'Japan', 'China', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman', 'Switzerland', 'Malaysia', 'New Zealand', 'South Africa', 'Other'
        ];

        // Fetch active categories for the form
        $categories = \App\Models\ProductCategory::where('status', 1)->orderBy('category_name')->get();
        $industries = Industry::orderBy('name')->get();

        return view('leads.create', compact('users', 'currentUser', 'leadSources', 'leadStatuses', 'countries', 'organizations', 'categories', 'industries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_leads_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent storing leads when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Creating leads is disabled for historical years.');
            }
        }
        $messages = [
            'title.required' => 'Please enter a title for the lead.',
            'title.max' => 'Title may not be longer than 100 characters.',
            'organization_id.required' => 'Please select or enter the Company name',
            'organization_id.max' => 'Company name may not exceed 255 characters.',
            'people_id.required' => 'Please select or add a Contact Person.',
            'people_id.max' => 'Contact person value is too long.',
            'user_owner_id.required' => 'Please assign an Owner.',
            'user_owner_id.exists' => 'The selected Owner is invalid.',
            'lead_source.required' => 'Please select a Lead Source.',
            'referral_name.required' => 'Please enter the Referral Name.',
            'referral_name.max' => 'Referral Name may not exceed 255 characters.',
            'label.required' => 'Please choose a Priority for this lead.',
            'status.required' => 'Please select a Lead Status.',
            'description.max' => 'Description may not exceed 250 characters.',
            'amount.numeric' => 'Amount must be a valid number.',
            'industry_id.exists' => 'Selected industry is invalid.',

            'categories.required' => 'Please select at least one category.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.exists' => 'Selected category is invalid.',
        ];

        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'organization_id' => 'required|string|max:255',
            'customer_id' => 'nullable|string|max:255',
            'people_id' => 'required|string|max:255',
            'amount' => 'nullable|numeric|max:50000000',
            'status' => 'required|string',
            'label' => 'required|string',
            'lead_source' => 'required|string',
            'referral_name' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => \App\Models\LeadSource::isReferralSourceId($request->input('lead_source'))),
                'nullable',
                'string',
                'max:255',
            ],
            'user_owner_id' => 'required|exists:users,id',
            'description' => 'nullable|string|max:250',
            'industry_id' => 'nullable|exists:industries,id',
            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
        ], $messages);

        // --- Duplicate check ---
        $titleLower = strtolower(trim($validated['title']));
        $orgNameInput = !empty($validated['organization_id']) ? trim($validated['organization_id']) : null;
        $personNameInput = !empty($validated['people_id']) ? trim($validated['people_id']) : null;

        // Try to resolve existing organization and person (if any)
        $existingOrg = null;
        if ($orgNameInput) {
            $existingOrg = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($orgNameInput)])->first();
        }

        $existingPerson = null;
        if ($personNameInput) {
            $parts = explode(' ', $personNameInput, 2);
            $first = $parts[0] ?? '';
            $last = $parts[1] ?? null;
            $personQuery = \App\Models\Person::whereRaw('LOWER(first_name) = ?', [strtolower($first)]);
            if ($last)
                $personQuery->whereRaw('LOWER(last_name) = ?', [strtolower($last)]);
            $existingPerson = $personQuery->first();
        }

        // Build a query that requires title match AND (org match OR person match when provided)
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

                if ($personNameInput) {
                    if ($existingPerson) {
                        if ($hasCondition) {
                            $q->orWhere('people_id', $existingPerson->id);
                        } else {
                            $q->where('people_id', $existingPerson->id);
                        }
                    } else {
                        if ($hasCondition) {
                            $q->orWhereHas('person', function ($q2) use ($personNameInput) {
                                $q2->whereRaw("LOWER(CONCAT(first_name, ' ', last_name)) = ?", [strtolower($personNameInput)])
                                    ->orWhereRaw("LOWER(first_name) = ?", [strtolower($personNameInput)]);
                            });
                        } else {
                            $q->whereHas('person', function ($q2) use ($personNameInput) {
                                $q2->whereRaw("LOWER(CONCAT(first_name, ' ', last_name)) = ?", [strtolower($personNameInput)])
                                    ->orWhereRaw("LOWER(first_name) = ?", [strtolower($personNameInput)]);
                            });
                        }
                    }
                    $hasCondition = true;
                }

                // If neither org nor person provided, then any lead with same title is considered a duplicate.
                if (!$orgNameInput && !$personNameInput) {
                    $q->whereRaw('1 = 1');
                }
            });

        $duplicate = $possibleDuplicateQuery->first();
        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', 'A similar lead already exists (Title / Organization / Contact person).')->with('duplicate_lead_id', $duplicate->id);
        }


        // --- Organization ---
        $organizationId = null;
        $industryId = $validated['industry_id'] ?? null;
        if (!empty($validated['organization_id'])) {
            $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($validated['organization_id'])])->first();
            if (!$org) {
                $org = \App\Models\Organization::create([
                    'name' => $validated['organization_id'],
                    'industry_type' => $industryId ?? 0,
                    'organization_type' => 0, // Default value, adjust as needed
                    'website' => '',
                    'address' => $request->get('org_address1', ''),
                    'city' => $request->get('org_city', ''),
                    'state' => $request->get('org_state', ''),
                    'pincode' => $request->get('org_zip', ''),
                    'country' => $request->get('org_country', ''),
                    'phone' => '',
                    'email' => '',
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id()
                ]);
            } elseif ($industryId) {
                $org->update([
                    'industry_type' => $industryId,
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
            }
            $organizationId = $org->id;
        }

        // --- Customer ---
        $customerId = null;
        if (!empty($validated['customer_id'])) {
            $customer = \App\Models\Customer::whereRaw('LOWER(name) = ?', [strtolower($validated['customer_id'])])->where('organization_id', $organizationId)->first();
            if (!$customer) {
                $customer = \App\Models\Customer::create([
                    'name' => $validated['customer_id'],
                    'phone' => '',
                    'organization_id' => $organizationId,
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id()
                ]);
            }
            $customerId = $customer->id;
        }

        // --- Contact Person (People) ---
        $peopleId = null;
        if (!empty($validated['people_id'])) {
            $personName = trim($validated['people_id']);
            $parts = explode(' ', $personName, 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';

            $person = \App\Models\Person::findByNameForOrganization($personName, $organizationId);
            if (!$person) {
                $person = \App\Models\Person::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'organization_id' => $organizationId,
                    'customer_id' => $customerId,
                    'email' => $request->input('person_email'),
                    'phone' => '',
                    'mobile' => $request->input('mobile'),
                    'job_title' => $request->input('job_title', ''),
                    'lead_source' => $validated['lead_source'],
                    'linkedin' => null,
                    'address' => null,
                    'notes' => null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                    'deleted_by' => null,
                    'deleted_at' => null,
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                ]);
            } else {
                $person->update([
                    'email' => $request->input('person_email', $person->email),
                    'mobile' => $request->input('mobile', $person->mobile),
                    'customer_id' => $customerId ?: $person->customer_id,
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
            }
            if ($organizationId) {
                $person->attachToOrganization((int) $organizationId);
            }
            $peopleId = $person->id;
        }
        // echo $validated['user_owner_id'];exit;
        $lead = Lead::create([
            'title' => $validated['title'],
            'organization_id' => $organizationId,
            'customer_id' => $customerId,
            'people_id' => $peopleId,
            'amount' => $validated['amount'] ?? null,
            'status' => $validated['status'],

            'category' => implode(',', $validated['categories']), // Store categories as a comma-separated string
            'industry_id' => $industryId,
            'lead_source' => $validated['lead_source'],
            'referral_name' => \App\Models\LeadSource::isReferralSourceId($validated['lead_source'])
                ? ($validated['referral_name'] ?? null)
                : null,
            'description' => $validated['description'] ?? null,
            'label' => $validated['label'],
            'user_owner_id' => $validated['user_owner_id'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
            'updated_by' => auth()->id(),
            'assigned_id' => $validated['user_owner_id'] ?? null,


        ]);
        // $lead->categories()->sync($validated['categories']);


        return redirect()->route('leads.index')->with('success', 'Lead created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!auth()->user()->hasCrmPermission('view_crm_leads_guard')) {
            abort(403, 'Unauthorized action.');
        }
        
        $users = User::all();
        $lead = Lead::with(['customer', 'organization', 'person', 'owner', 'industry'])->findOrFail($id);
        \App\Support\RecordAccess::authorizeLead($lead);
        // Fetch category names for each lead
        $categoryIds = explode(',', $lead->category ?? '');
        $leads = Lead::all();

        $contacts = Contact::all();
        $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
        $meetings = \App\Models\Meeting::where('related_type', 'lead')
                    ->where('related_id', $lead->id)
                    ->orderByDesc('start_at')
                    ->get();
                    //participants list for each meeting
        foreach ($meetings as $meeting) {
            $meeting->related_name = $meeting->related_type === 'lead' ? $meeting->lead->title : ($meeting->related_type === 'deal' ? $meeting->deal->title : null);
            $meeting->participants_list = $meeting->participants()->with('user')->get()->map(function ($participant) {
                if($participant->type === 'user') {
                    $user = \App\Models\User::find($participant->user_id);
                    $users = $user ? $user->name : null;
                }
                if($participant->type == 'contact'){
                    $contact = \App\Models\Person::find($participant->person_id);
                    $users = $contact ? $contact->first_name . ' ' . $contact->last_name : null;
                }
                return [
                    'id' => $participant->id,
                    'type' => $participant->type,
                    'name' => $users,
                ];
            });
            $meeting->participants_names = $meeting->participants_list->pluck('name')->implode(', ');
            $meeting->participants_lists = $meeting->participants()->with('user')->get()->map(function ($participant) {
                return [
                    'id' => $participant->type === 'user' ? $participant->user_id : ($participant->type === 'contact' ? $participant->person_id : $participant->id),
                    'type' => $participant->type
                ];
            })->toArray();
        }
        // Pass the current page to the show view for back button functionality
        $currentPage = request('page', 1);
        return view('leads.show', compact('lead', 'currentPage', 'users', 'contacts', 'meetings', 'leads'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_leads_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent editing leads when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Editing leads is disabled for historical years.');
            }
        }
        $lead = Lead::with(['customer', 'organization', 'person', 'owner'])->findOrFail($id);
        \App\Support\RecordAccess::authorizeLead($lead);
        $users = \App\Models\User::where('status', 1)->orderBy('name')->get();
        $currentUser = auth()->user();
        $leadSources = \App\Models\LeadSource::orderBy('name')->get();
        $leadStatuses = LeadStatus::orderBy('name')->get();
        $organizations = \App\Models\Organization::orderBy('name')->get();
        $categories = \App\Models\ProductCategory::where('status', 1)->orderBy('category_name')->get();
        $industries = Industry::orderBy('name')->get();
        $countries = [
            'India', 'United Arab Emirates', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Singapore', 'Japan', 'China', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman', 'Switzerland', 'Malaysia', 'New Zealand', 'South Africa', 'Other'
        ];
        return view('leads.edit', compact('lead', 'users', 'currentUser', 'leadSources', 'leadStatuses', 'organizations', 'categories', 'industries', 'countries'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_leads_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent updating leads when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Editing leads is disabled for historical years.');
            }
        }
        \App\Support\RecordAccess::authorizeLead(Lead::findOrFail($id));
        $messages = [
            'title.required' => 'Please enter a title.',
            'title.max' => 'Title may not be longer than 100 characters.',
            'organization_id.required' => 'Please select or enter the Company name',
            'organization_id.max' => 'Company name may not exceed 255 characters.',
            'people_id.required' => 'Please select or add a Contact Person.',
            'people_id.max' => 'Contact person value is too long.',
            'user_owner_id.required' => 'Please assign an Owner.',
            'user_owner_id.exists' => 'The selected Owner is invalid.',
            'lead_source.required' => 'Please select a Lead Source.',
            'referral_name.required' => 'Please enter the Referral Name.',
            'referral_name.max' => 'Referral Name may not exceed 255 characters.',
            'label.required' => 'Please choose a Priority.',
            'status.required' => 'Please select a Lead Status.',
            'description.max' => 'Description may not exceed 250 characters.',
            'amount.numeric' => 'Amount must be a valid number.',
            'reason_for_loss.required' => 'Please provide a reason for loss when marking the lead lost.',
            'industry_id.exists' => 'Selected industry is invalid.',
            'categories.required' => 'Please select at least one category.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.exists' => 'Selected category is invalid.',
        ];

        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'organization_id' => 'required|string|max:255',
            'customer_id' => 'nullable|string|max:255',
            'people_id' => 'required|string|max:255',
            'amount' => 'nullable|numeric',
            'status' => 'required|string',
            'label' => 'required|string',
            'lead_source' => 'required|string',
            'referral_name' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => \App\Models\LeadSource::isReferralSourceId($request->input('lead_source'))),
                'nullable',
                'string',
                'max:255',
            ],
            'user_owner_id' => 'required|exists:users,id',
            'description' => 'nullable|string|max:250',
            'reason_for_loss' => ($request->status === 'Closed Lost' || $request->status === 'Lost Lead') ? 'required|string|max:255' : 'nullable|string|max:255',
            'industry_id' => 'nullable|exists:industries,id',

            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
        ], $messages);

        // --- Organization ---
        $organizationId = null;
        $industryId = $validated['industry_id'] ?? null;
        if (!empty($validated['organization_id'])) {
            $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($validated['organization_id'])])->first();
            if (!$org) {
                $org = \App\Models\Organization::create([
                    'name' => $validated['organization_id'],
                    'industry_type' => $industryId ?? 0,
                    'website' => null,
                    'address' => $validated['org_address1'] ?? $request->get('org_address1'),
                    'city' => $validated['org_city'] ?? $request->get('org_city'),
                    'state' => $validated['org_state'] ?? $request->get('org_state'),
                    'pincode' => $validated['org_zip'] ?? $request->get('org_zip'),
                    'country' => $validated['org_country'] ?? $request->get('org_country'),
                    'phone' => null,
                    'email' => null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id()
                ]);
            } else {
                // Update org address fields if changed; sync industry when provided
                $orgUpdate = [
                    'address' => $request->get('org_address1', $org->address),
                    'city' => $request->get('org_city', $org->city),
                    'state' => $request->get('org_state', $org->state),
                    'pincode' => $request->get('org_zip', $org->pincode),
                    'country' => $request->get('org_country', $org->country),
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ];
                if ($industryId) {
                    $orgUpdate['industry_type'] = $industryId;
                }
                $org->update($orgUpdate);
            }
            $organizationId = $org->id;
        }

        // --- Customer ---
        $customerId = null;
        if (!empty($validated['customer_id'])) {
            $customer = \App\Models\Customer::whereRaw('LOWER(name) = ?', [strtolower($validated['customer_id'])])->where('organization_id', $organizationId)->first();
            if (!$customer) {
                $customer = \App\Models\Customer::create([
                    'name' => $validated['customer_id'],
                    'phone' => null,
                    'organization_id' => $organizationId,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id()
                ]);
            }
            $customerId = $customer->id;
        }

        // --- Contact Person (People) ---
        $peopleId = null;
        if (!empty($validated['people_id'])) {
            $personName = trim($validated['people_id']);
            $parts = explode(' ', $personName, 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';

            $person = \App\Models\Person::findByNameForOrganization($personName, $organizationId);
            if (!$person) {
                $person = \App\Models\Person::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'organization_id' => $organizationId,
                    'customer_id' => $customerId,
                    'email' => $request->input('person_email'),
                    'phone' => '',
                    'mobile' => $request->input('mobile'),
                    'job_title' => $request->input('job_title', ''),
                    'lead_source' => $validated['lead_source'],
                    'linkedin' => null,
                    'address' => null,
                    'notes' => null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                    'deleted_by' => null,
                    'deleted_at' => null,
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                ]);
            } else {
                $person->update([
                    'email' => $request->input('person_email', $person->email),
                    'mobile' => $request->input('mobile', $person->mobile),
                    'customer_id' => $customerId ?: $person->customer_id,
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
            }
            if ($organizationId) {
                $person->attachToOrganization((int) $organizationId);
            }
            $peopleId = $person->id;
        }

        $lead = Lead::findOrFail($id);
        $lead->update([
            'title' => $validated['title'],
            'organization_id' => $organizationId,
            'customer_id' => $customerId,
            'people_id' => $peopleId,
            'amount' => $validated['amount'] ?? null,
            'status' => $validated['status'],
            'lead_source' => $validated['lead_source'],
            'referral_name' => \App\Models\LeadSource::isReferralSourceId($validated['lead_source'])
                ? ($validated['referral_name'] ?? null)
                : null,
            'description' => $validated['description'] ?? null,
            'label' => $validated['label'],
            'user_owner_id' => $validated['user_owner_id'] ?? null,
            'reason_for_loss' => $validated['reason_for_loss'] ?? null,
            'industry_id' => $industryId,
            'category' => implode(',', $validated['categories']), // Store categories as a comma-separated string,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
            'assigned_id' => $validated['user_owner_id'] ?? null,
        ]);


        return redirect()->route('leads.show', $lead->id)->with('success', 'Lead updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_leads_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent deleting leads when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Deleting leads is disabled for historical years.');
            }
        }
        $lead = Lead::findOrFail($id);
        \App\Support\RecordAccess::authorizeLead($lead);
        $lead->delete();
        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully');
    }
}
