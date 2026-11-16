<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalCaseTask;
use App\Models\GoalLoanCase;
use App\Models\GoalLoanLead;
use App\Models\GoalLoanLeadEvent;
use App\Models\GoalOrgUnit;
use App\Models\Product;
use App\Models\User;
use App\Services\Goal\GoalCaseAudit;
use App\Services\Goal\GoalWorkflow;
use App\Services\Goal\Pd01Service;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GoalLoanCaseController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('view_loan_cases_guard');

        $request->validate([
            'q' => 'nullable|string|max:150',
            'tab' => 'nullable|string|max:40',
            'branch' => 'nullable|integer',
            'status' => 'nullable|string|max:80',
            'product' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|in:10,25,50,100',
        ]);

        $scope = $this->scopedOrgIds();
        $tab = $request->input('tab', 'all');

        $baseQuery = GoalLoanCase::query()->with([
            'owner:id,name,job_role,crm_role_type,reports_to_user_id,goal_org_unit_id',
            'orgUnit:id,name',
            'lead:id,ref_no,status,assigned_to',
        ]);
        if ($scope !== []) {
            $baseQuery->whereIn('goal_org_unit_id', $scope);
        }

        // KPI counts (org-scoped only — ignore tab/filters)
        $kpiBase = GoalLoanCase::query();
        if ($scope !== []) {
            $kpiBase->whereIn('goal_org_unit_id', $scope);
        }
        $now = now();
        $currentPeriod = (clone $kpiBase)
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->count();
        $priorPeriod = (clone $kpiBase)
            ->where('created_at', '>=', $now->copy()->subDays(60))
            ->where('created_at', '<', $now->copy()->subDays(30))
            ->count();

        $trendPct = null;
        if ($priorPeriod > 0) {
            $trendPct = round((($currentPeriod - $priorPeriod) / $priorPeriod) * 100, 1);
        } elseif ($currentPeriod > 0) {
            $trendPct = 100.0;
        }

        $kpis = [
            'total' => (clone $kpiBase)->count(),
            'in_process' => (clone $kpiBase)->where('status', 'Processing')->count(),
            'sanctioned' => (clone $kpiBase)->where('status', 'Sanctioned')->count(),
            'disbursed' => (clone $kpiBase)->where('status', 'Disbursed')->count(),
            'rejected' => (clone $kpiBase)->where('status', 'Rejected')->count(),
            'total_trend_pct' => $trendPct,
        ];

        // Tabs (status tabs skipped when explicit Case Status filter is set)
        if ($tab === 'mine') {
            $baseQuery->where('owner_id', auth()->id());
        } elseif (!$request->filled('status')) {
            if ($tab === 'in_process') {
                $baseQuery->where('status', 'Processing');
            } elseif ($tab === 'bank_query') {
                $baseQuery->where('status', 'Bank Query');
            } elseif ($tab === 'sanctioned') {
                $baseQuery->where('status', 'Sanctioned');
            } elseif ($tab === 'disbursed') {
                $baseQuery->where('status', 'Disbursed');
            } elseif ($tab === 'rejected') {
                $baseQuery->where('status', 'Rejected');
            }
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $baseQuery->where(function ($inner) use ($q) {
                $inner->where('case_no', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        }

        if ($request->filled('branch')) {
            $baseQuery->where('goal_org_unit_id', (int) $request->input('branch'));
        }

        if ($request->filled('product')) {
            $baseQuery->where('product', $request->input('product'));
        }

        if ($request->filled('date_from')) {
            $baseQuery->whereDate('updated_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('updated_at', '<=', $request->input('date_to'));
        }

        $perPage = (int) ($request->input('per_page') ?: 25);
        $cases = (clone $baseQuery)
            ->latest('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        // Resolve BM for each case: stored id → owner's manager → default BM for branch
        $resolvedBmIds = [];
        foreach ($cases as $case) {
            $resolvedBmIds[] = $this->resolveCaseBranchManagerId($case);
        }
        $resolvedBmIds = array_values(array_unique(array_filter($resolvedBmIds)));
        $branchManagersById = $resolvedBmIds === []
            ? collect()
            : User::query()
                ->whereIn('id', $resolvedBmIds)
                ->get(['id', 'name', 'job_role', 'crm_role_type'])
                ->keyBy('id');
        $caseBranchManagerIds = [];
        foreach ($cases as $case) {
            $caseBranchManagerIds[$case->id] = $this->resolveCaseBranchManagerId($case);
        }

        $statuses = array_keys(GoalWorkflow::CASE_TRANSITIONS);

        $branches = GoalOrgUnit::query()
            ->ofType('branch')
            ->active()
            ->when($scope !== [], fn ($q) => $q->whereIn('id', $scope))
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('product_status', 1)
            ->orderBy('product_name')
            ->get(['id', 'product_name']);

        $canCreate = auth()->user()->hasCrmPermission('edit_case_workspace_guard');

        $tabs = [
            'all' => ['label' => 'All Cases', 'icon' => 'bi-layers'],
            'mine' => ['label' => 'My Cases', 'icon' => 'bi-person'],
            'in_process' => ['label' => 'In Process', 'icon' => 'bi-hourglass-split'],
            'bank_query' => ['label' => 'Bank Query', 'icon' => 'bi-bank'],
            'sanctioned' => ['label' => 'Sanctioned', 'icon' => 'bi-check-circle'],
            'disbursed' => ['label' => 'Disbursed', 'icon' => 'bi-coin'],
            'rejected' => ['label' => 'Rejected', 'icon' => 'bi-x-circle'],
        ];

        return view('goal.cases.index', compact(
            'cases',
            'kpis',
            'statuses',
            'branches',
            'products',
            'canCreate',
            'tab',
            'tabs',
            'perPage',
            'branchManagersById',
            'caseBranchManagerIds'
        ));
    }

    /**
     * Prefer stored BM on case details, else the assignee's reports_to manager,
     * else the default BM for the case branch.
     */
    protected function resolveCaseBranchManagerId(GoalLoanCase $case): ?int
    {
        $stored = (int) data_get($case->customer_details, 'branch_manager_id');
        if ($stored > 0) {
            return $stored;
        }

        $ownerReportsTo = (int) ($case->owner?->reports_to_user_id ?? 0);
        if ($ownerReportsTo > 0) {
            return $ownerReportsTo;
        }

        $bm = $this->defaultBmForBranch($case->goal_org_unit_id, $this->scopedOrgIds());

        return $bm?->id ? (int) $bm->id : null;
    }

    public function create(Request $request)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $scope = $this->scopedOrgIds();

        $products = Product::query()
            ->where('product_status', 1)
            ->orderBy('product_name')
            ->get(['id', 'product_name']);

        $purposes = [
            'Home Renovation', 'Education', 'Medical', 'Wedding',
            'Business Expansion', 'Debt Consolidation', 'Travel', 'Other',
        ];
        $contactTimes = [
            '9:00 AM - 12:00 PM', '10:00 AM - 5:00 PM', '12:00 PM - 3:00 PM',
            '3:00 PM - 6:00 PM', '6:00 PM - 9:00 PM',
        ];
        $employmentTypes = ['Salaried', 'Self Employed', 'Business', 'Professional', 'Other'];

        $branches = GoalOrgUnit::query()
            ->ofType('branch')
            ->active()
            ->when($scope !== [], fn ($q) => $q->whereIn('id', $scope))
            ->orderBy('name')
            ->get();

        $owners = $this->salesUsers($scope);
        $branchManagers = $this->bmUsers($scope);

        $prefillLead = null;
        if ($request->filled('lead_id')) {
            $prefillLead = $this->findEligibleLead((int) $request->input('lead_id'), $scope);
        }

        return view('goal.cases.create', [
            'products' => $products,
            'purposes' => $purposes,
            'contactTimes' => $contactTimes,
            'employmentTypes' => $employmentTypes,
            'branches' => $branches,
            'owners' => $owners,
            'branchManagers' => $branchManagers,
            'documentCatalog' => $this->documentCatalog(),
            'lookupUrl' => route('goal.cases.lead-lookup'),
            'productDocsUrl' => route('goal.cases.product-documents'),
            'storeUrl' => route('goal.cases.store'),
            'prefillLead' => $prefillLead ? $this->serializeLeadForCreate($prefillLead) : null,
        ]);
    }

    public function leadLookup(Request $request)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $scope = $this->scopedOrgIds();

        if ($request->filled('lead_id')) {
            $lead = $this->findSelectableLead((int) $request->input('lead_id'), $scope, true);
            if (!$lead) {
                return response()->json(['ok' => false, 'message' => 'Lead not found or outside your branch scope.'], 404);
            }
            if ($lead->loanCase) {
                return response()->json([
                    'ok' => false,
                    'message' => 'This lead already has case ' . $lead->loanCase->case_no . '.',
                    'case_id' => $lead->loanCase->id,
                ], 409);
            }

            $payload = $this->serializeLeadForCreate($lead);
            $gate = $this->leadEligibilityError($lead);
            $payload['can_create'] = $gate === null;
            $payload['eligibility_message'] = $gate;

            return response()->json(['ok' => true, 'lead' => $payload]);
        }

        $q = trim((string) $request->query('q', ''));
        // Search any in-scope lead without a case (exclude closed). Status shown in label;
        // create/store still enforces Qualified / Documents Pending.
        $results = $this->selectableLeadsQuery($scope)
            ->with(['assignee:id,name', 'orgUnit:id,name'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('ref_no', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('updated_at')
            ->limit(25)
            ->get()
            ->map(function (GoalLoanLead $lead) {
                $eligible = $this->leadEligibilityError($lead) === null;

                return [
                    'id' => $lead->id,
                    'text' => $lead->ref_no . ' · ' . $lead->customer_name . ' · ' . ($lead->mobile ?: '—') . ' · ' . $lead->status,
                    'ref_no' => $lead->ref_no,
                    'customer_name' => $lead->customer_name,
                    'mobile' => $lead->mobile,
                    'status' => $lead->status,
                    'branch_name' => $lead->orgUnit?->name,
                    'can_create' => $eligible,
                ];
            })
            ->values();

        return response()->json(['ok' => true, 'results' => $results]);
    }

    public function productDocuments(Request $request)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $product = (string) $request->query('product', '');

        return response()->json([
            'ok' => true,
            'documents' => $this->documentsForProduct($product),
        ]);
    }

    public function storeFromLead(Request $request, GoalLoanLead $lead)
    {
        $request->merge(['goal_loan_lead_id' => $lead->id]);

        return $this->store($request);
    }

    public function store(Request $request)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $scope = $this->scopedOrgIds();
        $saveAction = $request->input('save_action', 'draft');
        $money = ['nullable', 'string', 'max:20', 'regex:/^\d{1,12}(\.\d{1,2})?$/'];
        $amountRule = $saveAction === 'next'
            ? ['required', 'string', 'max:20', 'regex:/^\d{1,12}(\.\d{1,2})?$/']
            : $money;

        $hasLeadId = $request->filled('goal_loan_lead_id');
        $caseOrigin = $hasLeadId ? 'existing_lead' : 'new_customer';

        $data = $request->validate([
            'goal_loan_lead_id' => 'nullable|integer|exists:goal_loan_leads,id',
            'customer_ref' => 'nullable|string|max:40',
            'customer_name' => 'required|string|max:150',
            'mobile' => ['required', 'string', 'max:15', 'regex:/^[6-9]\d{9}$/'],
            'alt_mobile' => ['nullable', 'string', 'max:15', 'regex:/^[6-9]\d{9}$/'],
            'email' => 'nullable|email|max:150',
            'preferred_contact_time' => 'nullable|string|max:80',
            'consent_recorded' => 'accepted',
            'product' => 'required|string|max:100',
            'loan_amount' => $amountRule,
            'purpose' => 'nullable|string|max:100',
            'tenure_months' => ['nullable', 'string', 'max:5', 'regex:/^\d{1,3}$/'],
            'employment_type' => 'nullable|string|max:60',
            'monthly_income' => $money,
            'existing_obligations' => $money,
            'employer_name' => 'nullable|string|max:150',
            'work_experience_years' => ['nullable', 'string', 'max:5', 'regex:/^\d{1,2}(\.\d{1,2})?$/'],
            'net_income' => $money,
            'goal_org_unit_id' => $caseOrigin === 'new_customer'
                ? 'required|integer|exists:goal_org_units,id'
                : 'nullable|integer|exists:goal_org_units,id',
            'owner_id' => $caseOrigin === 'new_customer'
                ? 'required|integer|exists:users,id'
                : 'nullable|integer|exists:users,id',
            'branch_manager_id' => $caseOrigin === 'new_customer'
                ? 'required|integer|exists:users,id'
                : 'nullable|integer|exists:users,id',
            'documents' => 'nullable|array',
            'documents.*.key' => 'required_with:documents|string|max:60',
            'documents.*.label' => 'required_with:documents|string|max:120',
            'documents.*.mandatory' => 'nullable|boolean',
            'documents.*.file' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
            'save_action' => 'nullable|in:draft,next',
        ], [
            'goal_org_unit_id.required' => 'Branch is required when creating without a lead.',
            'branch_manager_id.required' => 'Branch Manager is required when creating without a lead.',
            'owner_id.required' => 'Assigned To (SM/JSM) is required when creating without a lead.',
            'mobile.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'alt_mobile.regex' => 'Enter a valid 10-digit alternate mobile number.',
            'consent_recorded.accepted' => 'Customer consent must be recorded.',
            'loan_amount.required' => 'Requested amount is required to proceed.',
            'loan_amount.regex' => 'Enter a valid amount (digits, optional 2 decimals).',
            'monthly_income.regex' => 'Enter a valid monthly income.',
            'existing_obligations.regex' => 'Enter a valid obligations amount.',
            'net_income.regex' => 'Enter a valid net income.',
            'tenure_months.regex' => 'Tenure must be a whole number of months.',
            'work_experience_years.regex' => 'Enter valid work experience in years.',
        ]);

        $data['case_origin'] = $caseOrigin;

        $lead = null;
        if ($caseOrigin === 'existing_lead') {
            $lead = $this->findEligibleLead((int) $data['goal_loan_lead_id'], $scope, true);
            if (!$lead) {
                return back()->withInput()->withErrors(['goal_loan_lead_id' => 'Lead not found or outside your branch scope.']);
            }
            if ($lead->loanCase) {
                return redirect()
                    ->route('goal.cases.show', $lead->loanCase)
                    ->with('success', 'This lead already has case ' . $lead->loanCase->case_no . '.');
            }
            if ($err = $this->leadEligibilityError($lead)) {
                return back()->withInput()->withErrors(['goal_loan_lead_id' => $err]);
            }
        }

        $productOk = Product::query()
            ->where('product_status', 1)
            ->where('product_name', $data['product'])
            ->exists();
        if (!$productOk) {
            return back()->withInput()->withErrors(['product' => 'Select an active product from configuration.']);
        }

        $loanAmount = $this->parseAmount($data['loan_amount'] ?? null);
        if ($saveAction === 'next' && ($loanAmount === null || $loanAmount <= 0)) {
            return back()->withInput()->withErrors(['loan_amount' => 'Requested amount must be a positive value.']);
        }
        $monthlyIncome = $this->parseAmount($data['monthly_income'] ?? null);
        $obligations = $this->parseAmount($data['existing_obligations'] ?? null);
        $netIncome = $this->parseAmount($data['net_income'] ?? null);
        $experience = $this->parseAmount($data['work_experience_years'] ?? null);
        if ($monthlyIncome !== null && $monthlyIncome < 0) {
            return back()->withInput()->withErrors(['monthly_income' => 'Income cannot be negative.']);
        }
        if ($obligations !== null && $obligations < 0) {
            return back()->withInput()->withErrors(['existing_obligations' => 'Obligations cannot be negative.']);
        }
        if ($netIncome === null && $monthlyIncome !== null) {
            $netIncome = max(0, $monthlyIncome - ($obligations ?? 0));
        }

        $branchId = (int) ($data['goal_org_unit_id'] ?? $lead?->goal_org_unit_id ?? 0);
        if (!$branchId) {
            return back()->withInput()->withErrors(['goal_org_unit_id' => 'Branch is required.']);
        }
        if ($scope !== [] && !in_array($branchId, array_map('intval', $scope), true)) {
            return back()->withInput()->withErrors(['goal_org_unit_id' => 'Branch is outside your organization scope.']);
        }

        $ownerId = (int) ($data['owner_id'] ?? $lead?->assigned_to ?? auth()->id());
        $ownerIds = $this->salesUsers($scope, $branchId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($lead?->assigned_to) {
            $ownerIds[] = (int) $lead->assigned_to;
        }
        $ownerIds[] = (int) auth()->id();
        if (!in_array($ownerId, array_unique($ownerIds), true)) {
            return back()->withInput()->withErrors(['owner_id' => 'Selected owner is not eligible for this branch.']);
        }

        $bmId = $data['branch_manager_id'] ?? null;
        if ($bmId) {
            $bmOk = $this->bmUsers($scope)->contains(fn ($u) => (int) $u->id === (int) $bmId);
            if (!$bmOk) {
                return back()->withInput()->withErrors(['branch_manager_id' => 'Selected BM is outside your organization scope.']);
            }
        } else {
            $bmId = $this->defaultBmForBranch($branchId, $scope)?->id;
        }

        $allowedDocs = collect($this->documentsForProduct($data['product']))->keyBy('key');
        $docs = [];
        foreach ($data['documents'] ?? [] as $i => $doc) {
            $key = $doc['key'] ?? null;
            if (!$key || !$allowedDocs->has($key)) {
                continue;
            }
            $meta = $allowedDocs->get($key);
            $uploaded = $request->file("documents.$i.file");
            $docs[] = [
                'key' => $key,
                'label' => $meta['label'],
                'mandatory' => (bool) ($meta['mandatory'] ?? false),
                'status' => 'Pending',
                'reviewer' => null,
                'rejection_reason' => null,
                'uploaded_at' => null,
                'file_name' => $uploaded?->getClientOriginalName(),
                'file_tmp' => $uploaded,
            ];
        }

        foreach ($allowedDocs as $key => $meta) {
            if (!collect($docs)->contains(fn ($d) => $d['key'] === $key)) {
                $docs[] = [
                    'key' => $key,
                    'label' => $meta['label'],
                    'mandatory' => (bool) ($meta['mandatory'] ?? false),
                    'status' => 'Pending',
                    'reviewer' => null,
                    'rejection_reason' => null,
                    'uploaded_at' => null,
                    'file_name' => null,
                    'file_tmp' => null,
                ];
            }
        }

        $tenure = isset($data['tenure_months']) && $data['tenure_months'] !== ''
            ? (int) $data['tenure_months']
            : null;
        $consentAt = now();
        $isNewCustomer = ($data['case_origin'] ?? 'existing_lead') === 'new_customer';

        try {
            $case = DB::transaction(function () use (
                $lead, $data, $loanAmount, $monthlyIncome, $obligations, $netIncome, $experience, $tenure,
                $branchId, $ownerId, $bmId, $docs, $consentAt, $saveAction, $isNewCustomer
            ) {
                if ($isNewCustomer || !$lead) {
                    $lead = GoalLoanLead::create([
                        'ref_no' => 'LD-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
                        'customer_name' => $data['customer_name'],
                        'mobile' => $data['mobile'],
                        'email' => $data['email'] ?? null,
                        'source' => 'direct_case',
                        'product' => $data['product'],
                        'loan_amount' => $loanAmount,
                        'lead_type' => 'immediate',
                        'status' => 'Qualified',
                        'consent_recorded' => true,
                        'goal_org_unit_id' => $branchId,
                        'created_by' => auth()->id(),
                        'assigned_to' => $ownerId,
                        'assigned_by' => auth()->id(),
                        'assigned_at' => $consentAt,
                    ]);

                    GoalLoanLeadEvent::record(
                        $lead,
                        'status_change',
                        'Lead Created',
                        'Created with case (new customer) · Source: direct_case',
                        ['from_status' => null, 'to_status' => 'Qualified']
                    );
                }

                $customerRef = $data['customer_ref'] ?: $this->customerRefForLead($lead);

                $persistedDocs = [];
                foreach ($docs as $doc) {
                    $row = [
                        'key' => $doc['key'],
                        'label' => $doc['label'],
                        'mandatory' => $doc['mandatory'],
                        'status' => 'Pending',
                        'reviewer' => null,
                        'rejection_reason' => null,
                        'file_name' => $doc['file_name'],
                        'uploaded_at' => $doc['file_tmp'] ? $consentAt->toDateTimeString() : null,
                        'storage_path' => null,
                    ];
                    $persistedDocs[] = $row;
                }

                $customerDetails = array_filter([
                    'customer_ref' => $customerRef,
                    'alt_mobile' => $data['alt_mobile'] ?? null,
                    'email' => $data['email'] ?? null,
                    'preferred_contact_time' => $data['preferred_contact_time'] ?? null,
                    'consent' => [
                        'recorded' => true,
                        'recorded_at' => $consentAt->toIso8601String(),
                        'recorded_by' => auth()->id(),
                        'source' => $isNewCustomer ? 'create_case_new_customer' : 'create_case',
                    ],
                    'purpose' => $data['purpose'] ?? null,
                    'tenure_months' => $tenure,
                    'employment_type' => $data['employment_type'] ?? null,
                    'monthly_income' => $monthlyIncome,
                    'existing_obligations' => $obligations,
                    'employer_name' => $data['employer_name'] ?? null,
                    'work_experience_years' => $experience,
                    'net_income' => $netIncome,
                    'branch_manager_id' => $bmId,
                    'lead_ref' => $lead->ref_no,
                    'lead_source' => $lead->source,
                    'documents' => $persistedDocs,
                ], fn ($v) => $v !== null && $v !== '');

                $case = GoalLoanCase::create([
                    'case_no' => 'CS-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
                    'goal_loan_lead_id' => $lead->id,
                    'goal_org_unit_id' => $branchId,
                    'owner_id' => $ownerId,
                    'status' => 'Draft',
                    'customer_name' => $data['customer_name'],
                    'mobile' => $data['mobile'],
                    'product' => $data['product'],
                    'loan_amount' => $loanAmount,
                    'customer_details' => $customerDetails,
                    'bm_reco' => null,
                ]);

                $details = $case->customer_details ?? [];
                $docRows = $details['documents'] ?? [];
                foreach ($docs as $i => $doc) {
                    if (empty($doc['file_tmp'])) {
                        continue;
                    }
                    $ext = $doc['file_tmp']->getClientOriginalExtension() ?: 'bin';
                    $path = $doc['file_tmp']->storeAs(
                        'private/goal-cases/' . $case->id,
                        $doc['key'] . '-' . Str::lower(Str::random(6)) . '.' . $ext
                    );
                    $docRows[$i]['storage_path'] = $path;
                    $docRows[$i]['path'] = $path;
                    $docRows[$i]['file_name'] = $doc['file_name'];
                    $docRows[$i]['uploaded_at'] = $consentAt->toDateTimeString();
                    $docRows[$i]['status'] = $docRows[$i]['status'] ?? 'Pending';
                }
                $details['documents'] = array_values($docRows);
                $case->customer_details = $details;
                $case->save();

                GoalCaseAudit::record($case, 'created', 'Case created', auth()->user(), [
                    'to' => $case->case_no,
                    'meta' => ['lead_id' => $lead->id],
                ]);

                if (!$lead->consent_recorded) {
                    $lead->consent_recorded = true;
                }
                if (!empty($data['product'])) {
                    $lead->product = $data['product'];
                }
                if ($loanAmount !== null) {
                    $lead->loan_amount = $loanAmount;
                }
                $fromStatus = $lead->status;
                if ($fromStatus === 'Qualified' && GoalWorkflow::canLead($fromStatus, 'Documents Pending')) {
                    $lead->status = 'Documents Pending';
                }
                $lead->save();

                GoalLoanLeadEvent::record(
                    $lead,
                    'case',
                    'Case Created · ' . $case->case_no,
                    $isNewCustomer
                        ? ($saveAction === 'next'
                            ? 'Loan case created for new customer'
                            : 'Loan case draft created for new customer')
                        : ($saveAction === 'next'
                            ? 'Loan case created from Create Case form'
                            : 'Loan case draft created from Create Case form'),
                    [
                        'from_status' => $fromStatus,
                        'to_status' => $lead->status,
                        'meta' => [
                            'case_id' => $case->id,
                            'case_no' => $case->case_no,
                            'save_action' => $saveAction,
                            'case_origin' => $isNewCustomer ? 'new_customer' : 'existing_lead',
                        ],
                    ]
                );

                return $case;
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'goal_loan_lead_id' => 'Case could not be created. Please review the form and try again.',
            ]);
        }

        $msg = $saveAction === 'next'
            ? 'Case ' . $case->case_no . ' created. Continue processing in Case Details.'
            : 'Case ' . $case->case_no . ' saved as draft.';

        return redirect()
            ->route('goal.cases.show', $case)
            ->with('success', $msg);
    }

    public function show(GoalLoanCase $case)
    {
        $this->requireGoalPermission('view_loan_cases_guard');
        $this->assertCaseInScope($case);

        $case->load([
            'owner:id,name,job_role,crm_role_type,reports_to_user_id,mobile,goal_org_unit_id',
            'orgUnit:id,name',
            'lead:id,ref_no,status,source,assigned_to,consent_recorded',
            'tasks' => fn ($q) => $q->latest()->limit(30),
            'disbursements' => fn ($q) => $q->latest(),
            'disbursements.application',
            'disbursements.verifier',
            'pmsItems',
            'accountsItems',
            'payouts',
            'bankApplications' => fn ($q) => $q->latest(),
            'bankApplications.queries',
            'bankApplications.sanctions',
            'bankQueries' => fn ($q) => $q->latest(),
            'bankQueries.application',
            'bankQueries.responsible',
            'sanctions' => fn ($q) => $q->latest(),
            'sanctions.application',
            'sanctions.recorder',
            'events' => fn ($q) => $q->with('user:id,name,job_role,crm_role_type')->latest()->limit(100),
        ]);

        $next = GoalWorkflow::CASE_TRANSITIONS[$case->status] ?? [];
        $user = auth()->user();
        $canEdit = $user->hasCrmPermission('edit_case_workspace_guard');
        $canDisburse = $user->hasCrmPermission('record_disbursement_guard');
        $canReassign = $user->hasCrmPermission('bm_allocation_guard');
        $canVerifyDocs = $user->hasCrmPermission('bm_allocation_guard')
            || $user->hasCrmPermission('pms_verification_guard')
            || $user->hasCrmPermission('manage_permissions_guard');
        $canPms = $user->hasCrmPermission('pms_verification_guard');

        $bmId = $this->resolveCaseBranchManagerId($case);
        $branchManager = $bmId
            ? User::query()->find($bmId, ['id', 'name', 'job_role', 'crm_role_type', 'mobile'])
            : null;

        $progress = $this->caseProgressSteps($case);

        $activeTab = session('cw_tab', request('tab', 'overview'));
        $allowedTabs = ['overview', 'customer', 'recommendation', 'documents', 'lenders', 'queries', 'sanction', 'disbursement', 'activity'];
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'overview';
        }

        $scope = $this->scopedOrgIds();
        $reassignUsers = ($canReassign || $canEdit)
            ? $this->salesUsers($scope, $case->goal_org_unit_id)
            : collect();

        $openQuery = $case->bankQueries
            ->first(fn ($q) => $q->status !== 'Resolved');

        $activeSanction = $case->sanctions->firstWhere('status', 'Active');
        $verifiedDisbTotal = (float) $case->disbursements->whereNotNull('verified_at')->sum('amount');
        $pendingDisbTotal = (float) $case->disbursements
            ->whereNull('verified_at')
            ->where('status', 'Pending Verification')
            ->sum('amount');
        $sanctionCap = (float) ($activeSanction?->sanction_amount ?? $case->sanction_amount ?? 0);

        $docs = data_get($case->customer_details, 'documents', []) ?: [];
        $docSummary = [
            'required' => collect($docs)->where('mandatory', true)->count(),
            'uploaded' => collect($docs)->filter(fn ($d) => !empty($d['path'] ?? null))->count(),
            'pending' => collect($docs)->whereIn('status', ['Pending', 'Uploaded'])->count(),
            'verified' => collect($docs)->where('status', 'Verified')->count(),
            'rejected' => collect($docs)->where('status', 'Rejected')->count(),
        ];

        $lenderSummary = [
            'total' => $case->bankApplications->count(),
            'login' => $case->bankApplications->where('status', 'Bank Login')->count(),
            'processing' => $case->bankApplications->where('status', 'Processing')->count(),
            'query' => $case->bankApplications->where('status', 'Bank Query')->count(),
            'sanctioned' => $case->bankApplications->where('status', 'Sanctioned')->count(),
            'rejected' => $case->bankApplications->where('status', 'Rejected')->count(),
        ];

        return view('goal.cases.show', compact(
            'case',
            'next',
            'canEdit',
            'canDisburse',
            'canReassign',
            'canVerifyDocs',
            'canPms',
            'branchManager',
            'progress',
            'activeTab',
            'reassignUsers',
            'openQuery',
            'activeSanction',
            'verifiedDisbTotal',
            'pendingDisbTotal',
            'sanctionCap',
            'docSummary',
            'lenderSummary'
        ));
    }

    public function updateWorkspace(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);

        $mobileRule = ['nullable', 'string', 'max:15', 'regex:/^[6-9]\d{9}$/'];
        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'mobile' => $mobileRule,
            'product' => 'nullable|string|max:100',
            'loan_amount' => ['nullable', 'string', 'max:20', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'lender_name' => 'nullable|string|max:150',
            'application_ref' => 'nullable|string|max:100',
            'customer_details' => 'nullable|array',
            'customer_details.applicant.name' => 'nullable|string|max:150',
            'customer_details.applicant.mobile' => $mobileRule,
            'customer_details.applicant.email' => 'nullable|email|max:150',
            'customer_details.applicant.official_email' => 'nullable|email|max:150',
            'customer_details.co_applicant.mobile' => $mobileRule,
            'customer_details.co_applicant.email' => 'nullable|email|max:150',
            'customer_details.co_applicant.official_email' => 'nullable|email|max:150',
            'customer_details.documents' => 'nullable|array',
            'customer_details.documents.*.key' => 'nullable|string|max:60',
            'customer_details.documents.*.label' => 'nullable|string|max:120',
            'customer_details.documents.*.status' => 'nullable|in:Pending,Verified,Rejected,Uploaded',
            'customer_details.documents.*.reviewer' => 'nullable|string|max:120',
            'customer_details.documents.*.rejection_reason' => 'nullable|string|max:500',
            'bm_reco' => 'nullable|array',
            'collateral' => 'nullable|array',
            'existing_loans' => 'nullable|array',
            'branch_mis' => 'nullable|array',
            'cw_tab' => 'nullable|string|max:40',
        ], [
            'customer_name.required' => 'Customer name is required.',
            'mobile.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'loan_amount.regex' => 'Enter a valid amount (digits, optional 2 decimals).',
        ]);

        $docs = data_get($data, 'customer_details.documents', []);
        if (is_array($docs)) {
            foreach ($docs as $i => $doc) {
                if (($doc['status'] ?? '') === 'Rejected' && empty(trim((string) ($doc['rejection_reason'] ?? '')))) {
                    return back()
                        ->withInput()
                        ->withErrors(["customer_details.documents.$i.rejection_reason" => 'Rejection reason is required for rejected documents.'])
                        ->with('cw_tab', $data['cw_tab'] ?? 'documents');
                }
            }
        }

        $incomingCd = $data['customer_details'] ?? [];
        $mergedCd = $this->mergeWorkspaceJson($case->customer_details, $incomingCd);
        if (array_key_exists('documents', $incomingCd)) {
            $mergedCd['documents'] = array_values($incomingCd['documents'] ?? []);
        }

        // Keep top-level identity in sync with applicant form fields when provided
        $applicantName = trim((string) data_get($incomingCd, 'applicant.name', ''));
        $applicantMobile = preg_replace('/\D+/', '', (string) data_get($incomingCd, 'applicant.mobile', ''));
        if ($applicantName !== '') {
            $data['customer_name'] = $applicantName;
            data_set($mergedCd, 'applicant.name', $applicantName);
        }
        if ($applicantMobile !== '') {
            $data['mobile'] = $applicantMobile;
            data_set($mergedCd, 'applicant.mobile', $applicantMobile);
        }

        $case->customer_name = $data['customer_name'];
        $case->mobile = $data['mobile'] ?? $case->mobile;
        if (array_key_exists('product', $data)) {
            $case->product = $data['product'] ?? $case->product;
        }
        if (array_key_exists('loan_amount', $data)) {
            $case->loan_amount = $this->parseAmount($data['loan_amount']);
        }
        $case->lender_name = $data['lender_name'] ?? $case->lender_name;
        $case->application_ref = $data['application_ref'] ?? $case->application_ref;
        $before = [
            'customer_name' => $case->customer_name,
            'mobile' => $case->mobile,
            'product' => $case->product,
            'loan_amount' => (string) $case->loan_amount,
        ];

        $case->customer_details = $mergedCd;
        $case->bm_reco = $this->mergeWorkspaceJson($case->bm_reco, $data['bm_reco'] ?? null);
        $case->collateral = $this->mergeWorkspaceJson($case->collateral, $data['collateral'] ?? null);
        $incomingLoans = $data['existing_loans'] ?? null;
        if (is_array($incomingLoans)) {
            if (array_key_exists('bt_source', $incomingLoans)) {
                $bt = $incomingLoans['bt_source'];
                if (is_array($bt) && (isset($bt[0]) || $bt === [])) {
                    $incomingLoans['bt_source'] = array_values(array_filter(
                        $bt,
                        static fn ($row) => is_array($row)
                    ));
                }
            }
            if (array_key_exists('rows', $incomingLoans) && is_array($incomingLoans['rows'])) {
                $incomingLoans['rows'] = array_values(array_filter(
                    $incomingLoans['rows'],
                    static fn ($row) => is_array($row)
                ));
            }
        }
        $case->existing_loans = $this->mergeWorkspaceJson($case->existing_loans, $incomingLoans);
        if (is_array($incomingLoans)) {
            $mergedLoans = is_array($case->existing_loans) ? $case->existing_loans : [];
            if (array_key_exists('bt_source', $incomingLoans)) {
                $mergedLoans['bt_source'] = $incomingLoans['bt_source'];
            }
            if (array_key_exists('rows', $incomingLoans)) {
                $mergedLoans['rows'] = $incomingLoans['rows'];
            }
            $case->existing_loans = $mergedLoans;
        }
        $case->branch_mis = $this->mergeWorkspaceJson($case->branch_mis, $data['branch_mis'] ?? null);
        $case->save();

        GoalCaseAudit::record($case, 'updated', 'Case details updated', auth()->user(), [
            'from' => json_encode($before),
            'to' => json_encode([
                'customer_name' => $case->customer_name,
                'mobile' => $case->mobile,
                'product' => $case->product,
                'loan_amount' => (string) $case->loan_amount,
            ]),
        ]);

        return back()
            ->with('success', 'Case workspace saved.')
            ->with('cw_tab', $data['cw_tab'] ?? 'customer');
    }

    protected function assertCaseInScope(GoalLoanCase $case): void
    {
        $scope = $this->scopedOrgIds();
        if ($scope !== [] && $case->goal_org_unit_id && !in_array((int) $case->goal_org_unit_id, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function mergeWorkspaceJson($existing, $incoming): array
    {
        $base = is_array($existing) ? $existing : [];
        $in = is_array($incoming) ? $incoming : [];
        if ($in === []) {
            return $base;
        }

        return array_replace_recursive($base, $in);
    }

    protected function caseProgressSteps(GoalLoanCase $case): array
    {
        $status = $case->status;
        $stages = GoalWorkflow::CASE_PROGRESS_STAGES;
        $icons = [
            'Draft' => 'bi-file-earmark',
            'Ready for Login' => 'bi-clipboard-check',
            'Bank Login' => 'bi-bank',
            'Bank Query' => 'bi-search',
            'Processing' => 'bi-gear',
            'Sanctioned' => 'bi-file-earmark-check',
            'Disbursed' => 'bi-wallet',
            'PMS' => 'bi-flag',
        ];
        $order = array_flip($stages);
        $idx = $order[$status] ?? ($status === 'Rejected' || $status === 'Closed' ? 3 : 0);

        // Map stage label -> first time case entered that status (from audit events / created_at).
        $reachedAt = [];
        $reachedAt['Draft'] = $case->created_at;
        $eventsAsc = collect($case->events ?? [])->sortBy('created_at')->values();
        foreach ($eventsAsc as $ev) {
            if (!in_array($ev->event_type, ['status', 'created', 'application', 'sanction', 'disbursement', 'query'], true)) {
                continue;
            }
            $to = trim((string) ($ev->to_value ?? ''));
            if ($to !== '' && isset($order[$to]) && empty($reachedAt[$to])) {
                $reachedAt[$to] = $ev->created_at;
            }
            // Heuristics from event titles when to_value is amount/ref rather than status
            $title = strtolower((string) $ev->title);
            if (str_contains($title, 'lender application') && empty($reachedAt['Bank Login'])) {
                $reachedAt['Bank Login'] = $ev->created_at;
            }
            if (str_contains($title, 'query raised') && empty($reachedAt['Bank Query'])) {
                $reachedAt['Bank Query'] = $ev->created_at;
            }
            if ((str_contains($title, 'sanction recorded') || str_contains($title, 'sanction')) && empty($reachedAt['Sanctioned'])) {
                $reachedAt['Sanctioned'] = $ev->created_at;
            }
            if (str_contains($title, 'disbursement verified') && empty($reachedAt['Disbursed'])) {
                $reachedAt['Disbursed'] = $ev->created_at;
            }
            if (str_contains($title, 'transferred to pms') && empty($reachedAt['PMS'])) {
                $reachedAt['PMS'] = $ev->created_at;
            }
        }
        // Current status fallback timestamp
        if (empty($reachedAt[$status])) {
            $reachedAt[$status] = $case->updated_at ?? $case->created_at;
        }

        // Backfill skipped stages so the tracker shows timestamps like the design mock.
        for ($i = 0; $i <= $idx; $i++) {
            $label = $stages[$i];
            if (!empty($reachedAt[$label])) {
                continue;
            }
            $fill = null;
            for ($j = $i + 1; $j <= $idx; $j++) {
                if (!empty($reachedAt[$stages[$j]])) {
                    $fill = $reachedAt[$stages[$j]];
                    break;
                }
            }
            if (!$fill) {
                for ($j = $i - 1; $j >= 0; $j--) {
                    if (!empty($reachedAt[$stages[$j]])) {
                        $fill = $reachedAt[$stages[$j]];
                        break;
                    }
                }
            }
            if ($fill) {
                $reachedAt[$label] = $fill;
            }
        }

        $labels = [];
        foreach ($stages as $i => $label) {
            $state = 'todo';
            if ($status === 'Rejected' && $i === $idx) {
                $state = 'rejected';
            } elseif ($status === 'Closed' && $i === $idx) {
                $state = 'rejected';
            } elseif ($i < $idx) {
                $state = 'done';
            } elseif ($i === $idx) {
                $state = 'active';
            }

            $at = $reachedAt[$label] ?? null;
            $labels[] = [
                'key' => Str::slug($label),
                'label' => ($status === 'Rejected' && $i === $idx) ? 'Rejected' : $label,
                'state' => $state,
                'icon' => $icons[$label] ?? 'bi-circle',
                'at' => $at,
                'at_label' => $at ? $at->timezone(config('app.timezone'))->format('d M, h:i a') : null,
            ];
        }

        return $labels;
    }

    public function transition(Request $request, GoalLoanCase $case, Pd01Service $pd01)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        $data = $request->validate([
            'to_status' => 'required|string',
            'lender_name' => 'nullable|string|max:150',
            'application_ref' => 'nullable|string|max:100',
            'bank_query' => 'nullable|string',
            'bank_query_due' => 'nullable|date',
            'sanction_amount' => 'nullable|numeric|min:0',
            'sanction_date' => 'nullable|date',
            'rejection_reason' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'disbursed_on' => 'nullable|date',
            'bank_reference' => 'nullable|string|max:100',
            'evidence_note' => 'nullable|string|max:255',
        ]);

        if ($data['to_status'] === 'Disbursed') {
            $this->requireGoalPermission('record_disbursement_guard');
            try {
                $pd01->execute($case, [
                    'amount' => $data['amount'],
                    'disbursed_on' => $data['disbursed_on'],
                    'bank_reference' => $data['bank_reference'],
                    'evidence_note' => $data['evidence_note'] ?? null,
                ], auth()->user());
            } catch (\Throwable $e) {
                return back()->withErrors(['to_status' => $e->getMessage()])->with('cw_tab', 'bank');
            }
            return back()->with('success', 'PD-01 complete — PMS + Accounts queues opened, payout On Hold.')->with('cw_tab', 'bank');
        }

        $case->loadMissing(['bankApplications', 'bankQueries', 'sanctions', 'disbursements']);
        $docs = collect(data_get($case->customer_details, 'documents', []) ?: []);
        $mandatory = $docs->where('mandatory', true);
        $requiredDocsComplete = $mandatory->isEmpty()
            || $mandatory->every(fn ($d) => ($d['status'] ?? '') === 'Verified');
        $caseDetailsComplete = filled($case->customer_name) && filled($case->product) && (float) $case->loan_amount > 0;
        $hasLender = $case->bankApplications->isNotEmpty() || filled($data['lender_name'] ?? $case->lender_name);
        $queriesCleared = !$case->bankQueries
            ->where('blocks_processing', true)
            ->contains(fn ($q) => $q->status !== 'Resolved');
        $activeSanc = $case->sanctions->firstWhere('status', 'Active');
        $hasSancEvidence = $activeSanc && filled($activeSanc->evidence_path);

        $ctx = [
            'lender' => $hasLender ? ($data['lender_name'] ?? $case->lender_name ?: 'app') : null,
            'queryText' => $data['bank_query'] ?? $case->bank_query,
            'queryDue' => $data['bank_query_due'] ?? $case->bank_query_due,
            'queryOwner' => true, // legacy transition path; dedicated query store enforces owner
            'sancAmt' => $data['sanction_amount'] ?? $activeSanc?->sanction_amount ?? $case->sanction_amount,
            'sancDate' => $data['sanction_date'] ?? $activeSanc?->sanction_date ?? $case->sanction_date,
            'sancEvidence' => $hasSancEvidence || !empty($data['sanction_amount']),
            'reason' => $data['rejection_reason'] ?? null,
            'requiredDocsComplete' => $requiredDocsComplete,
            'caseDetailsComplete' => $caseDetailsComplete,
            'queriesCleared' => $queriesCleared,
            'verifiedDisbursement' => $case->disbursements->whereNotNull('verified_at')->isNotEmpty(),
            'pmsEligible' => $case->status === 'Disbursed' && $case->disbursements->whereNotNull('verified_at')->isNotEmpty(),
        ];
        $fromStatus = $case->status;
        $gate = GoalWorkflow::validateCase($fromStatus, $data['to_status'], $ctx);
        if (!$gate['ok']) {
            return back()->withErrors(['to_status' => implode(' ', $gate['errors'])])->with('cw_tab', 'overview');
        }

        if (!empty($data['lender_name'])) {
            $case->lender_name = $data['lender_name'];
        }
        if (!empty($data['application_ref'])) {
            $case->application_ref = $data['application_ref'];
        }
        if (!empty($data['bank_query'])) {
            $case->bank_query = $data['bank_query'];
        }
        if (!empty($data['bank_query_due'])) {
            $case->bank_query_due = $data['bank_query_due'];
        }
        if (!empty($data['sanction_amount'])) {
            $case->sanction_amount = $data['sanction_amount'];
        }
        if (!empty($data['sanction_date'])) {
            $case->sanction_date = $data['sanction_date'];
        }
        if (!empty($data['rejection_reason'])) {
            $case->rejection_reason = $data['rejection_reason'];
        }
        $case->status = $data['to_status'];
        $case->save();

        GoalCaseAudit::record($case, 'status', 'Case status changed', auth()->user(), [
            'from' => $fromStatus,
            'to' => $data['to_status'],
        ]);

        return back()->with('success', 'Case status updated.')->with('cw_tab', 'overview');
    }

    public function storeTask(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'task_type' => 'nullable|in:visit,followup,document,other',
            'due_at' => 'nullable|date|after_or_equal:now',
            'notes' => 'nullable|string',
        ], [
            'due_at.after_or_equal' => 'Task due date must be today or a future date/time.',
        ]);
        GoalCaseTask::create([
            'goal_loan_case_id' => $case->id,
            'title' => $data['title'],
            'task_type' => $data['task_type'] ?? 'visit',
            'due_at' => $data['due_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assigned_to' => $case->owner_id,
            'created_by' => auth()->id(),
            'status' => 'Open',
        ]);
        return back()->with('success', 'Task added.')->with('cw_tab', 'tasks');
    }

    protected function salesUsers(array $scope, $branchId = null): Collection
    {
        return User::query()
            ->where(function ($q) {
                $q->whereIn('crm_role_type', [10, 11, 12])
                    ->orWhereIn('job_role', ['jsm', 'sm', 'ssm']);
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($inner) use ($branchId) {
                    $inner->where('goal_org_unit_id', $branchId)
                        ->orWhereNull('goal_org_unit_id');
                });
            })
            ->when(!$branchId && $scope !== [], function ($q) use ($scope) {
                $q->where(function ($inner) use ($scope) {
                    $inner->whereIn('goal_org_unit_id', $scope)
                        ->orWhereNull('goal_org_unit_id');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'job_role', 'crm_role_type', 'goal_org_unit_id', 'reports_to_user_id', 'mobile', 'email']);
    }

    protected function bmUsers(array $scope): Collection
    {
        return User::query()
            ->where(function ($q) {
                $q->where('crm_role_type', 8)
                    ->orWhereIn('job_role', ['bm', 'branch_manager']);
            })
            ->when($scope !== [], function ($q) use ($scope) {
                $q->where(function ($inner) use ($scope) {
                    $inner->whereIn('goal_org_unit_id', $scope)
                        ->orWhereNull('goal_org_unit_id');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'job_role', 'crm_role_type', 'goal_org_unit_id', 'reports_to_user_id']);
    }

    protected function parseAmount($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        $clean = preg_replace('/[^\d.]/', '', $raw);
        if ($clean === '' || $clean === '.') {
            return null;
        }
        return round((float) $clean, 2);
    }

    /**
     * Product document catalog — keys are matched by product name keywords until
     * a dedicated product-document configuration table exists.
     */
    protected function documentCatalog(): array
    {
        return [
            ['key' => 'pan', 'label' => 'PAN Card', 'products' => ['*'], 'mandatory' => true],
            ['key' => 'aadhaar', 'label' => 'Aadhaar', 'products' => ['*'], 'mandatory' => true],
            ['key' => 'photo', 'label' => 'Photograph', 'products' => ['*'], 'mandatory' => true],
            ['key' => 'address_proof', 'label' => 'Address Proof', 'products' => ['*'], 'mandatory' => true],
            ['key' => 'income_proof', 'label' => 'Income Proof', 'products' => ['personal', 'business', 'home'], 'mandatory' => true],
            ['key' => 'bank_statement', 'label' => 'Bank Statement (6 months)', 'products' => ['personal', 'business', 'home'], 'mandatory' => true],
            ['key' => 'salary_slip', 'label' => 'Salary Slips (3 months)', 'products' => ['personal', 'home'], 'mandatory' => false],
            ['key' => 'property_docs', 'label' => 'Property Documents', 'products' => ['home'], 'mandatory' => true],
            ['key' => 'business_proof', 'label' => 'Business Registration / ITR', 'products' => ['business'], 'mandatory' => true],
        ];
    }

    protected function documentsForProduct(?string $productName): array
    {
        $key = $this->productKey($productName);
        $out = [];
        foreach ($this->documentCatalog() as $doc) {
            $products = $doc['products'] ?? ['*'];
            if (in_array('*', $products, true) || ($key && in_array($key, $products, true))) {
                $out[] = [
                    'key' => $doc['key'],
                    'label' => $doc['label'],
                    'mandatory' => (bool) ($doc['mandatory'] ?? false),
                ];
            }
        }

        return $out;
    }

    protected function productKey(?string $name): string
    {
        $p = strtolower((string) $name);
        if (str_contains($p, 'home')) {
            return 'home';
        }
        if (str_contains($p, 'personal')) {
            return 'personal';
        }
        if (str_contains($p, 'business') || str_contains($p, 'sme')) {
            return 'business';
        }

        return 'other';
    }

    protected function selectableLeadsQuery(array $scope)
    {
        return GoalLoanLead::query()
            ->whereDoesntHave('loanCase')
            ->whereNotIn('status', ['Closed / Lost'])
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope));
    }

    protected function findSelectableLead(int $id, array $scope, bool $withCase = false): ?GoalLoanLead
    {
        return GoalLoanLead::query()
            ->with(['assignee:id,name,job_role,crm_role_type', 'orgUnit:id,name'])
            ->when($withCase, fn ($query) => $query->with(['loanCase:id,goal_loan_lead_id,case_no,bm_reco']))
            ->when($scope !== [], fn ($query) => $query->whereIn('goal_org_unit_id', $scope))
            ->where('id', $id)
            ->first();
    }

    protected function findEligibleLead(int $id, array $scope, bool $withCase = false): ?GoalLoanLead
    {
        return $this->findSelectableLead($id, $scope, $withCase);
    }

    protected function leadEligibilityError(GoalLoanLead $lead): ?string
    {
        if ($lead->relationLoaded('loanCase') ? $lead->loanCase : $lead->loanCase()->exists()) {
            $caseNo = $lead->loanCase->case_no ?? $lead->loanCase()->value('case_no');

            return 'This lead already has case ' . $caseNo . '.';
        }
        if (!in_array($lead->status, ['Qualified', 'Documents Pending'], true)) {
            return 'Lead must be Qualified before creating a case. Current status: ' . $lead->status . '. Update the lead status on Lead Details first.';
        }

        return null;
    }

    protected function customerRefForLead(GoalLoanLead $lead): string
    {
        $mobile = preg_replace('/\D+/', '', (string) $lead->mobile);

        return 'CUS-' . strtoupper(substr(sha1($mobile ?: ('L' . $lead->id)), 0, 10));
    }

    protected function defaultBmForBranch($branchId, array $scope): ?User
    {
        if (!$branchId) {
            return null;
        }

        return $this->bmUsers($scope)
            ->first(fn (User $u) => (int) $u->goal_org_unit_id === (int) $branchId)
            ?: $this->bmUsers($scope)->first();
    }

    protected function serializeLeadForCreate(GoalLoanLead $lead): array
    {
        $bm = $this->defaultBmForBranch($lead->goal_org_unit_id, $this->scopedOrgIds());
        $bmReco = null;
        if ($lead->relationLoaded('loanCase') && $lead->loanCase && is_array($lead->loanCase->bm_reco) && $lead->loanCase->bm_reco !== []) {
            $raw = $lead->loanCase->bm_reco;
            $bmReco = [
                'amount' => $raw['amount'] ?? $raw['recommended_amount'] ?? null,
                'by' => $raw['by'] ?? $raw['recommended_by'] ?? null,
                'date' => $raw['date'] ?? $raw['recommended_at'] ?? null,
                'remarks' => $raw['remarks'] ?? $raw['note'] ?? null,
                'status' => $raw['status'] ?? 'Recorded',
            ];
            if (empty($bmReco['amount']) && empty($bmReco['remarks'])) {
                $bmReco = null;
            }
        }

        $fmtOwner = function (?User $u) {
            if (!$u) {
                return null;
            }
            $role = match ((int) ($u->crm_role_type ?? -1)) {
                10 => 'JSM',
                11 => 'SM',
                12 => 'SSM',
                8 => 'BM',
                default => strtoupper((string) ($u->job_role ?: 'User')),
            };

            return $role . ' - ' . $u->name;
        };

        return [
            'id' => $lead->id,
            'ref_no' => $lead->ref_no,
            'customer_ref' => $this->customerRefForLead($lead),
            'customer_name' => $lead->customer_name,
            'mobile' => $lead->mobile,
            'email' => $lead->email,
            'product' => $lead->product,
            'loan_amount' => $lead->loan_amount,
            'goal_org_unit_id' => $lead->goal_org_unit_id,
            'branch_name' => $lead->orgUnit?->name,
            'owner_id' => $lead->assigned_to,
            'owner_label' => $fmtOwner($lead->assignee),
            'branch_manager_id' => $bm?->id,
            'branch_manager_label' => $fmtOwner($bm),
            'consent_recorded' => (bool) $lead->consent_recorded,
            'source' => $lead->source,
            'source_label' => $this->sourceLabel($lead->source),
            'status' => $lead->status,
            'bm_reco' => $bmReco,
            'documents' => $this->documentsForProduct($lead->product),
        ];
    }

    protected function sourceLabel(?string $source): string
    {
        return match (strtolower((string) $source)) {
            'vrm' => 'VRM',
            'channel' => 'Channel',
            'walkin', 'walk-in' => 'Walk-in',
            'referral' => 'Referral',
            default => $source ? Str::title($source) : '—',
        };
    }
}
