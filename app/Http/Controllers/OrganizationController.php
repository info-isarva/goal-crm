<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Quotation;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrganizationsExport;

class OrganizationController extends Controller
{
    public function __construct()
    {
        // Prevent creating/editing/deleting organizations when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'create', 'store', 'edit', 'update', 'destroy', 'ajaxCreate'
        ]);
    }

    // Display a listing of organizations
    public function index()
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_organization_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $view = request('view', 'Recently Modified Accounts');
        $user = auth()->user();
        $query = Organization::with([
            'owner',
            'industry',
            'people' => function($q) {
                $q->orderBy('created_at');
            }
        ]);

        switch ($view) {
            case 'All Accounts':
                // No additional filter
                break;
            case 'My Accounts':
                $query->where('user_owner_id', $user->id);
                break;
            case 'New Last Week':
                $query->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]);
                break;
            case 'New This Week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'Recently Created Accounts':
                $query->orderBy('created_at', 'desc');
                break;
            case 'Recently Modified Accounts':
                $query->orderBy('updated_at', 'desc');
                break;
            case 'Unread Accounts':
                $query->where('is_read', false); // Add this field if needed
                break;
            default:
                // No additional filter
                break;
        }

        // Apply financial year filter: if a historical (non-active) FY is selected, limit organizations to those created within that FY.
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        if ($selectedFyId && $activeFy && $selectedFyId != $activeFy->id) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
            if ($fy) {
                $query->whereBetween('created_at', [\Carbon\Carbon::parse($fy->from_date)->startOfDay(), \Carbon\Carbon::parse($fy->to_date)->endOfDay()]);
            }
        }

        $organizationTypes = [
            1 => 'Private',
            2 => 'Public',
            3 => 'Government',
            4 => 'Non-Profit',
            5 => 'Other'
        ];

        // Collections for dropdowns in the view
        $industries = \App\Models\Industry::orderBy('name')->get();
        $owners = \App\Models\User::whereNotIn('crm_role_type', [0, 1])->orderBy('name')->get();

        // Individual filters: name (text), industry_type (select), organization_type (select), owner_id (select)
        $nameFilter = trim(request('name', ''));
        $industryFilter = request('industry_type', null);
        $orgTypeFilter = request('organization_type', null);
        $ownerFilter = request('owner_id', null);

        if ($nameFilter !== '') {
            $query->where('name', 'like', '%' . $nameFilter . '%');
        }

        if (!empty($industryFilter)) {
            $query->where('industry_type', $industryFilter);
        }

        if ($orgTypeFilter !== null && $orgTypeFilter !== '') {
            $query->where('organization_type', $orgTypeFilter);
        }

        if (!empty($ownerFilter)) {
            $query->where('user_owner_id', $ownerFilter);
        }

        // Keep compatibility with the free-text 'q' (if provided) as an additional narrowing filter
        $q = trim(request('q', ''));
        if ($q !== '') {
            $term = $q;
            $query->where(function($sub) use ($term, $organizationTypes) {
                // Match organization name
                $sub->where('name', 'like', '%' . $term . '%');

                // Match industry name via relationship
                $sub->orWhereHas('industry', function($iq) use ($term) {
                    $iq->where('name', 'like', '%' . $term . '%');
                });

                // Match organization type label (e.g., Private, Public)
                $matchedTypeIds = [];
                foreach ($organizationTypes as $id => $label) {
                    if (stripos($label, $term) !== false) {
                        $matchedTypeIds[] = $id;
                    }
                }
                if (!empty($matchedTypeIds)) {
                    $sub->orWhereIn('organization_type', $matchedTypeIds);
                }
            });
        }

        $organizations = $query->orderBy('name')->paginate(15)->appends(request()->except('page'));
        return view('organizations.index', compact('organizations', 'organizationTypes', 'industries', 'owners'));
    }

    // Show organization details
    public function show($id)
    {
        if (!auth()->user()->hasCrmPermission('view_crm_organization_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $organization = Organization::with(['owner', 'industry'])->findOrFail($id);
        $organizationTypes = [
            1 => 'Private',
            2 => 'Public',
            3 => 'Government',
            4 => 'Non-Profit',
            5 => 'Other'
        ];
        return view('organizations.show', compact('organization', 'organizationTypes'));
    }

    // Show the form for creating a new organization
    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_organization_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $industries = \App\Models\Industry::orderBy('name')->get();
        $owners = \App\Models\User::orderBy('name')->get();
        $currentUserId = auth()->id();
        $countries = [
            'India', 'United Arab Emirates', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Singapore', 'Japan', 'China', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman', 'Switzerland', 'Malaysia', 'New Zealand', 'South Africa', 'Other'
        ];
        $organizationTypes = [
            1 => 'Private',
            2 => 'Public',
            3 => 'Government',
            4 => 'Non-Profit',
            5 => 'Other'
        ];
        return view('organizations.create', compact('industries', 'owners', 'currentUserId', 'countries', 'organizationTypes'));
    }

    // Store a new organization
    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_organization_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // For AJAX modal, accept minimal fields and return JSON
        // Accept AJAX and non-AJAX requests for modal
        // Accept AJAX and modal requests for organization creation
        if ($request->isMethod('post') && ($request->ajax() || $request->wantsJson() || str_contains($request->header('Accept'), 'application/json'))) {
            try {
                $validated = $request->validate([
                    'org_name' => 'required|string|max:255',
                    'org_address' => 'nullable|string|max:255',
                    'org_phone' => 'nullable|string|max:20',
                    'org_website' => 'nullable|string|max:255',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            if (!auth()->check()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $org = Organization::create([
                'name' => $validated['org_name'],
                'industry_type' => 0,
                'organization_type' => 0,
                'address' => $validated['org_address'] ?? null,
                'phone' => $validated['org_phone'] ?? null,
                'website' => $validated['org_website'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
                'updated_by' => auth()->id(),
            ]);
            return response()->json(['id' => $org->id, 'name' => $org->name]);
        }
        // ...existing code...
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
                function($attribute, $value, $fail) {
                    if (\App\Models\Organization::where('name', $value)->exists()) {
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
        $org = Organization::create([
            'name' => $validated['name'],
            'industry_type' => $validated['industry_type'] ? $validated['industry_type'] : 0, // Default to 1 if not provided
            'organization_type' => $validated['organization_type'] ? $validated['organization_type'] : 0, // Default to 1 if not provided
            'website' => $validated['website'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'country' => $validated['country'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'description' => $validated['description'] ?? null,
            'number_of_employees' => $validated['number_of_employees'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
            'updated_by' => auth()->id(),
            'user_owner_id' => $validated['user_owner_id'] ?? null,
        ]);
        return redirect()->route('organizations.show', $org->id)->with('success', 'Organization created successfully!');
    }

    // Show the form for editing an organization
    public function edit($id)
    {
         if (!auth()->user()->hasCrmPermission('edit_crm_organization_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $organization = Organization::findOrFail($id);
        $industries = \App\Models\Industry::orderBy('name')->get();
        $owners = \App\Models\User::orderBy('name')->get();
        $countries = [
            'India', 'United Arab Emirates', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Singapore', 'Japan', 'China', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman', 'Switzerland', 'Malaysia', 'New Zealand', 'South Africa', 'Other'
        ];
        $organizationTypes = [
            1 => 'Private',
            2 => 'Public',
            3 => 'Government',
            4 => 'Non-Profit',
            5 => 'Other'
        ];
        return view('organizations.edit', compact('organization', 'industries', 'owners', 'countries', 'organizationTypes'));
    }

    // Update the organization
    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_organization_guard')) {
            abort(403, 'Unauthorized action.');
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
        $organization = Organization::findOrFail($id);
        $organization->update($validated);
        return redirect()->route('organizations.show', $organization->id)->with('success', 'Organization updated successfully!');
    }

    // Delete an organization and related customers and people
    public function destroy($id)
    {
         if (!auth()->user()->hasCrmPermission('delete_crm_organization_guard')) {
            abort(403, 'Unauthorized action.');
        }



        $organization = \App\Models\Organization::find($id);
        if (!$organization) {
            return redirect()->route('organizations.index')->with('error', 'Organization not found.');
        }

        // Check for related leads, deals, or persons
        $hasRelatedRecords = $organization->leads()->exists() || $organization->deals()->exists() || $organization->people()->exists();
        if ($hasRelatedRecords) {
            $message = 'Unable to delete the organization. It has related leads, deals, or persons.';
            return redirect()->route('organizations.index')->with('error', $message)->with('sweetalert', [
                'title' => 'Deletion Blocked',
                'text' => $message,
                'icon' => 'error',
                'confirmButtonText' => 'OK'
            ]);
        }

        // Delete related customers
        foreach ($organization->customers ?? [] as $customer) {
            $customer->delete();
        }
        // Delete related people
        foreach ($organization->people ?? [] as $person) {
            $person->delete();
        }

        $organization->delete();

        return redirect()->route('organizations.index')->with('success', 'Organization deleted successfully!');
    }

    /**
     * Export organizations to Excel using the same filters as index.
     */
    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_organization_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $view = $request->get('view', 'Recently Modified Accounts');
        $user = auth()->user();
        $query = Organization::with(['owner', 'industry']);

        switch ($view) {
            case 'All Accounts':
                break;
            case 'My Accounts':
                $query->where('user_owner_id', $user->id);
                break;
            case 'New Last Week':
                $query->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]);
                break;
            case 'New This Week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'Recently Created Accounts':
                $query->orderBy('created_at', 'desc');
                break;
            case 'Recently Modified Accounts':
                $query->orderBy('updated_at', 'desc');
                break;
            case 'Unread Accounts':
                $query->where('is_read', false);
                break;
            default:
                break;
        }

        // financial year filter
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        if ($selectedFyId && $activeFy && $selectedFyId != $activeFy->id) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
            if ($fy) {
                $query->whereBetween('created_at', [\Carbon\Carbon::parse($fy->from_date)->startOfDay(), \Carbon\Carbon::parse($fy->to_date)->endOfDay()]);
            }
        }

        // filters
        $organizationTypes = [
            1 => 'Private',
            2 => 'Public',
            3 => 'Government',
            4 => 'Non-Profit',
            5 => 'Other'
        ];

        $nameFilter = trim($request->get('name', ''));
        $industryFilter = $request->get('industry_type', null);
        $orgTypeFilter = $request->get('organization_type', null);
        $ownerFilter = $request->get('owner_id', null);

        if ($nameFilter !== '') {
            $query->where('name', 'like', '%' . $nameFilter . '%');
        }

        if (!empty($industryFilter)) {
            $query->where('industry_type', $industryFilter);
        }

        if ($orgTypeFilter !== null && $orgTypeFilter !== '') {
            $query->where('organization_type', $orgTypeFilter);
        }

        if (!empty($ownerFilter)) {
            $query->where('user_owner_id', $ownerFilter);
        }

        $q = trim($request->get('q', ''));
        if ($q !== '') {
            $term = $q;
            $query->where(function($sub) use ($term, $organizationTypes) {
                $sub->where('name', 'like', '%' . $term . '%');
                $sub->orWhereHas('industry', function($iq) use ($term) {
                    $iq->where('name', 'like', '%' . $term . '%');
                });
                $matchedTypeIds = [];
                foreach ($organizationTypes as $id => $label) {
                    if (stripos($label, $term) !== false) {
                        $matchedTypeIds[] = $id;
                    }
                }
                if (!empty($matchedTypeIds)) {
                    $sub->orWhereIn('organization_type', $matchedTypeIds);
                }
            });
        }

        $orgs = $query->orderBy('name')->get();

        $rows = [];
        foreach ($orgs as $o) {
            $rows[] = [
                $o->name,
                optional($o->industry)->name ?? '-',
                $organizationTypes[$o->organization_type] ?? '-',
                $o->website ?? '-',
                $o->city ?? '-',
                optional($o->owner)->name ?? '-',
                $o->created_at ? $o->created_at->toDateTimeString() : '-',
                $o->updated_at ? $o->updated_at->toDateTimeString() : '-',
            ];
        }

        $fileName = 'organizations_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new OrganizationsExport($rows), $fileName);
    }

      // AJAX: Create new organization (minimal fields)
    public function ajaxCreate(Request $request)
    {
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
                'industry_type.exists' => 'Selected industry is not valid.',
                'org_country.max' => 'Country may not exceed 100 characters.',
                'org_state.max' => 'State may not exceed 100 characters.',
                'org_city.max' => 'City may not exceed 100 characters.',
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
                'org_country' => 'nullable|string|max:100',
                'org_state' => 'nullable|string|max:100',
                'org_city' => 'nullable|string|max:100',
                'org_phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\-\s]{10,20}$/', 'unique:organizations,phone'],
                'org_website' => 'nullable|string|max:255|url',
                'industry_type' => 'nullable|integer|exists:industries,id',
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $org = Organization::create([
            'name' => $validated['org_name'],
            'industry_type' => $validated['industry_type'] ?? 0,
            'organization_type' => 0,
            'address' => $validated['org_address'] ?? null,
            'country' => $validated['org_country'] ?? null,
            'state' => $validated['org_state'] ?? null,
            'city' => $validated['org_city'] ?? null,
            'phone' => $validated['org_phone'] ?? null,
            'website' => $validated['org_website'] ?? null,
            'user_owner_id' => auth()->id(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
            'updated_by' => auth()->id(),
        ]);
        return response()->json([
            'id' => $org->id,
            'name' => $org->name,
            'industry_type' => $org->industry_type,
            'industry_name' => optional($org->industry)->name,
        ]);
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

    // AJAX endpoint to get full organization details by name
    public function details(Request $request)
    {
        $name = $request->get('name', '');
        $org = Organization::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if (!$org) {
            return response()->json(['error' => 'Not found'], 404);
        }
        // Try to include owner and a primary contact person (if any)
        $owner = null;
        if ($org->user_owner_id) {
            $ownerModel = $org->owner()->first();
            if ($ownerModel) {
                $owner = ['id' => $ownerModel->id, 'name' => $ownerModel->name];
            }
        }

        // Build deals list with next proposal version for quotation autofill
        $deals = $org->deals()->orderByDesc('updated_at')->orderBy('title')->get();
        $maxByDeal = Quotation::query()
            ->whereIn('deal_id', $deals->pluck('id'))
            ->selectRaw('deal_id, MAX(proposal_version) as max_version')
            ->groupBy('deal_id')
            ->pluck('max_version', 'deal_id');

        $customersList = [];
        foreach ($deals as $cust) {
            $next = (int) ($maxByDeal[$cust->id] ?? 0) + 1;
            $customersList[] = [
                'id' => $cust->id,
                'name' => $cust->title,
                'next_proposal_version' => max(1, $next),
            ];
        }

        $peopleList = [];
        foreach ($org->allContactPersons()->get() as $person) {
            $peopleList[] = [
                'id' => $person->id,
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'full_name' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                'email' => $person->email,
                'phone' => $person->mobile ?? $person->phone,
            ];
        }

        $primaryPerson = $peopleList[0] ?? null;

        return response()->json([
            'address' => $org->address,
            'city' => $org->city,
            'state' => $org->state,
            'zip' => $org->pincode,
            'country' => $org->country,
            'owner' => $owner,
            'deals' => $customersList,
            'people' => $peopleList,
            'primary_person' => $primaryPerson,
            'industry_type' => $org->industry_type ?: null,
            'industry_id' => $org->industry_type ?: null,
            'industry_name' => optional($org->industry)->name,
        ]);
    }
}
