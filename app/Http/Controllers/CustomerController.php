<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomersExport;

class CustomerController extends Controller
{
    public function __construct()
    {
        // Prevent creating/editing/deleting customers when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'create', 'store', 'edit', 'update', 'destroy', 'ajaxCreate'
        ]);
    }
    // List all customers
    public function index()
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_customer_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $query = \App\Models\Customer::with(['organization', 'owner'])->orderBy('name');

        // Filters: single q (search name or organization name) and owner_id
        $q = request('q');
        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhereHas('organization', function ($oq) use ($q) {
                        $oq->where('name', 'like', '%' . $q . '%');
                    });
            });
        }

        $ownerId = request('owner_id');
        if ($ownerId) {
            $query->where('user_owner_id', $ownerId);
        }

        $customers = $query->paginate(15)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    // Export customers to Excel with current filters
    public function exportExcel(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_customer_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $query = \App\Models\Customer::with(['organization', 'owner'])->orderBy('name');

        $q = $request->get('q');
        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhereHas('organization', function ($oq) use ($q) {
                        $oq->where('name', 'like', '%' . $q . '%');
                    });
            });
        }

        $ownerId = $request->get('owner_id');
        if ($ownerId) {
            $query->where('user_owner_id', $ownerId);
        }

        $rows = $query->get()->map(function ($c) {
            return [
                $c->name,
                optional($c->organization)->name ?? '-',
                optional($c->owner)->name ?? '-',
                $c->phone,
                $c->email,
                $c->created_at ? $c->created_at->toDateTimeString() : '',
                $c->updated_at ? $c->updated_at->toDateTimeString() : '',
            ];
        })->toArray();

        $export = new CustomersExport($rows);
        $fileName = 'customers_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download($export, $fileName);
    }

    // Show customer details
    public function show($id)
    {
        if (!auth()->user()->hasCrmPermission('view_crm_customer_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $customer = \App\Models\Customer::with(['organization', 'owner'])->findOrFail($id);
        return view('customers.show', compact('customer'));
    }


    // Show the form for creating a new customer
    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_customer_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $owners = \App\Models\User::orderBy('name')->get();
        $currentUserId = auth()->id();
        $organizations = \App\Models\Organization::orderBy('name')->get();
        return view('customers.create', compact('owners', 'currentUserId', 'organizations'));
    }

     // Store a new customer, create organization if not found
    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_customer_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $messages = [
            'name.required' => 'Please enter the Company Owner name',
            'name.regex' => 'Company Owner name must start with a letter',
            'name.max' => 'Company Owner name may not exceed 255 characters',
            'organization_id.required' => 'Please select a Company',
            'organization_id.exists' => 'Selected Company does not exist',
            'owner_id.required' => 'Please select an account owner',
            'owner_id.exists' => 'Selected account owner does not exist',
            'phone.max' => 'Phone number may not exceed 20 characters',
            'phone.regex' => 'Please enter a valid phone number (with country code if applicable)',
            'email.email' => 'Please enter a valid email address',
            'email.max' => 'Email address may not exceed 255 characters',
            'email.regex' => 'Please enter a valid email address',
        ];

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'regex:/^[A-Za-z].*/',
                'max:255'
            ],
            'organization_id' => 'required|integer|exists:organizations,id',
            'owner_id' => 'required|integer|exists:users,id',
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9\-\s]{10,20}$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            ],
        ], $messages);
        $orgId = $request->input('organization_id');
        if ($orgId) {
            $org = \App\Models\Organization::find($orgId);
        } else {
            $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($validated['organization_name'])])->first();
            if (!$org) {
                $org = \App\Models\Organization::create([
                    'name' => $validated['organization_name'],
                    'industry_type' => 1, // default or null
                    'organization_type' => 1, // default or null
                    'user_owner_id' => $validated['owner_id'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        // Server-side duplicate prevention: ensure no customer with same name exists under the same organization
        $existing = \App\Models\Customer::where('organization_id', $org->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->first();
        if ($existing) {
            return redirect()->back()->withInput()->withErrors(['name' => 'A customer with the same name already exists for the selected company.']);
        }
        $customer = \App\Models\Customer::create([
            'name' => $validated['name'],
            'organization_id' => $org->id,
            'user_owner_id' => $validated['owner_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
                'updated_by' => auth()->id(),
        ]);
    return redirect()->route('customers.show', $customer->id)->with('success', 'Customer created successfully!');
    }


    // Show the form for editing a customer
    public function edit($id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_customer_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $customer = \App\Models\Customer::findOrFail($id);
        $organizations = \App\Models\Organization::orderBy('name')->get();
        $owners = \App\Models\User::orderBy('name')->get();
        return view('customers.edit', compact('customer', 'organizations', 'owners'));
    }

    // Update the customer (with organization name logic)
    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_customer_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $messages = [
            'name.required' => 'Please enter the Company Owner name',
            'name.regex' => 'Company Owner name must start with a letter',
            'name.max' => 'Company Owner name may not exceed 255 characters',
            'organization_id.required' => 'Please select a Company',
            'organization_id.exists' => 'Selected Company does not exist',
            'owner_id.required' => 'Please select an account owner',
            'owner_id.exists' => 'Selected account owner does not exist',
            'phone.max' => 'Phone number may not exceed 20 characters',
            'phone.regex' => 'Please enter a valid phone number (with country code if applicable)',
            'email.email' => 'Please enter a valid email address',
            'email.max' => 'Email address may not exceed 255 characters',
            'email.regex' => 'Please enter a valid email address',
        ];
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'regex:/^[A-Za-z].*/',
                'max:255'
            ],
            'organization_id' => 'required|integer|exists:organizations,id',
            'owner_id' => 'required|integer|exists:users,id',
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9\-\s]{10,20}$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            ],
        ], $messages);
        $org = \App\Models\Organization::find($validated['organization_id']);
        $customer = \App\Models\Customer::findOrFail($id);
        $customer->update([
            'name' => $validated['name'],
            'organization_id' => $org->id,
            'user_owner_id' => $validated['owner_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);
        return redirect()->route('customers.show', $customer->id)->with('success', 'Customer updated successfully!');
        // return redirect()->route('customers.index')->with('success', 'Customer updated successfully!');
    }

    // Delete a customer
    public function destroy($id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_customer_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $customer = \App\Models\Customer::findOrFail($id);
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully!');
    }




     // AJAX: Create new customer (minimal fields)
    public function ajaxCreate(Request $request)
    {
        try {
            // Custom messages for AJAX customer create
            $messages = [
                'cust_name.required' => 'Please enter the customer name.',
                'cust_name.max' => 'Customer name may not exceed 255 characters.',

                'cust_org_id.required' => 'Please select or enter the Company name',

                'phone.max' => 'Phone may not exceed 20 characters.',
                'phone.regex' => 'Please enter a valid phone number.',

                'email.email' => 'Please enter a valid email address.',
                'email.max' => 'Email may not exceed 255 characters.',
            ];

            $validated = $request->validate([
                'cust_name' => 'required|string|max:255',
                'cust_org_id' => 'required',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
            ], $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        // Resolve organization id: input might be numeric id or organization name
        $orgInput = $validated['cust_org_id'];
        $org = null;
        if (is_numeric($orgInput)) {
            $org = \App\Models\Organization::find((int)$orgInput);
        } else {
            $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($orgInput)])->first();
        }
        if (!$org) {
            return response()->json(['errors' => ['cust_org_id' => ['Organization not found']]], 422);
        }

        // Server-side duplicate check: same name (case-insensitive) under same organization
        $existing = \App\Models\Customer::where('organization_id', $org->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['cust_name'])])
            ->first();
        if ($existing) {
            // Inform client that this customer already exists for this organization
            return response()->json([
                'duplicate' => true,
                'id' => $existing->id,
                'name' => $existing->name,
            ], 200);
        }

        $customer = \App\Models\Customer::create([
            'name' => $validated['cust_name'],
            'organization_id' => $org->id,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'user_owner_id' => auth()->id(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
            'updated_by' => auth()->id(),
        ]);
        return response()->json(['id' => $customer->id, 'name' => $customer->name]);
    }

    // AJAX autocomplete for customer names
    public function autocomplete(Request $request)
    {
        $search = $request->get('q', '');
        $orgName = $request->get('organization', '');
        // $results = [];
        $query = Customer::query();
        if ($orgName) {
            $org = \App\Models\Organization::where('name', $orgName)->first();
            if ($org) {
                $query->where('organization_id', $org->id);
            }
        }
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if(!$orgName && !$search) {
            $results = $query->orderBy('name')->pluck('name');
        }else {
            // $query->where('name', 'like', '%' . $search . '%');
            $results = $query->orderBy('name')->pluck('name');
        }

        return response()->json($results);
    }

    // AJAX endpoint to get customer details and first related person
    public function details(Request $request)
    {
        $name = $request->get('name', '');
        $orgName = $request->get('organization', '');
        $org = null;
        if ($orgName) {
            $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($orgName)])->first();
        }
        $query = Customer::query();
        if ($org) {
            $query->where('organization_id', $org->id);
        }
        $customer = $query->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if (!$customer) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $person = $customer->people()->first();
        return response()->json([
            'customer_phone' => $customer->phone,
            'person_name' => $person ? $person->name : ''
        ]);



        //Old code for person details
        // $name = $request->get('name', '');
        // $orgName = $request->get('organization', '');
        // $org = null;
        // if ($orgName) {
        //     $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($orgName)])->first();
        // }
        // $query = Customer::query();
        // if ($org) {
        //     $query->where('organization_id', $org->id);
        // }
        // $customer = $query->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        // if (!$customer) {
        //     return response()->json(['error' => 'Not found'], 404);
        // }
        // $person = $customer->people()->first();
        // return response()->json([
        //     'customer_phone' => $customer->phone,
        //     'person_name' => $person ? $person->first_name : '',
        //     'person_phone' => $person ? $person->phone : '',
        //     'person_mobile' => $person ? $person->mobile : '',
        //     'person_email' => $person ? $person->email : ''
        // ]);
    }
}
