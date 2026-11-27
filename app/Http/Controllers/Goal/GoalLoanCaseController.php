<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalCaseTask;
use App\Models\GoalLoanCase;
use App\Models\GoalLoanLead;
use App\Models\GoalLoanLeadEvent;
use App\Models\GoalLoanPurpose;
use App\Models\GoalOrgUnit;
use App\Models\Product;
use App\Models\User;
use App\Services\Goal\GoalCaseAudit;
use App\Services\Goal\GoalCaseRequiredFields;
use App\Services\Goal\GoalWorkflow;
use App\Services\Goal\Pd01Service;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalLoanCaseController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('view_loan_cases_guard');

        $request->validate([
            'q' => 'nullable|string|max:150',
            'tab' => 'nullable|string|max:40',
            'module' => 'nullable|string|max:40',
            'branch' => 'nullable|integer',
            'status' => 'nullable|string|max:80',
            'product' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|in:10,25,50,100',
        ]);

        // Sidebar Loan Processing deep-links → workspace tab when a case is opened
        $moduleMeta = [
            'visits' => [
                'tab' => 'activity',
                'title' => 'Customer Visits & Follow-ups',
                'sub' => 'Open a case to manage visits, tasks and follow-ups.',
                'icon' => 'bi-geo-alt',
            ],
            'activity' => [
                'tab' => 'activity',
                'title' => 'Customer Visits & Follow-ups',
                'sub' => 'Open a case to manage visits, tasks and follow-ups.',
                'icon' => 'bi-geo-alt',
            ],
            'documents' => [
                'tab' => 'documents',
                'title' => 'Documents',
                'sub' => 'Document management and verification — open a case to continue.',
                'icon' => 'bi-file-earmark-text',
            ],
            'lenders' => [
                'tab' => 'lenders',
                'title' => 'Bank Processing',
                'sub' => 'Bank login and lender applications — open a case to continue.',
                'icon' => 'bi-bank',
            ],
            'queries' => [
                'tab' => 'queries',
                'title' => 'Bank Processing',
                'sub' => 'Bank queries and lender follow-ups — open a case to continue.',
                'icon' => 'bi-bank',
            ],
            'sanction' => [
                'tab' => 'sanction',
                'title' => 'Sanction & Disbursement',
                'sub' => 'Sanction and verified disbursement — open a case to continue.',
                'icon' => 'bi-award',
            ],
            'disbursement' => [
                'tab' => 'disbursement',
                'title' => 'Sanction & Disbursement',
                'sub' => 'Sanction and verified disbursement — open a case to continue.',
                'icon' => 'bi-award',
            ],
        ];
        $module = (string) $request->input('module', '');
        // Dedicated module hubs — keep old deep-links working
        if (in_array($module, ['visits', 'activity'], true)) {
            return redirect()->route('goal.visits.index');
        }
        if ($module === 'documents') {
            return redirect()->route('goal.documents.index');
        }
        if (in_array($module, ['lenders', 'queries'], true)) {
            return redirect()->route('goal.bank-processing.index');
        }
        if (in_array($module, ['sanction', 'disbursement'], true)) {
            return redirect()->route('goal.sanction-disbursement.index');
        }
        $moduleView = $moduleMeta[$module] ?? null;
        $workspaceTab = $moduleView['tab'] ?? null;
        if ($workspaceTab) {
            session(['cw_tab' => $workspaceTab]);
        } else {
            session()->forget('cw_tab');
        }

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

        // Module hubs: focused KPIs + per-row progress (still opens case workspace tabs)
        $moduleKpis = null;
        $caseModuleStats = [];
        if ($workspaceTab === 'documents') {
            $docRows = (clone $kpiBase)->get(['id', 'customer_details']);
            $moduleKpis = [
                'cases' => $docRows->count(),
                'pending' => 0,
                'verified' => 0,
                'rejected' => 0,
                'missing' => 0,
            ];
            foreach ($docRows as $row) {
                $stats = $this->summarizeCaseDocuments($row);
                $moduleKpis['pending'] += $stats['pending'];
                $moduleKpis['verified'] += $stats['verified'];
                $moduleKpis['rejected'] += $stats['rejected'];
                $moduleKpis['missing'] += $stats['missing'];
            }
            foreach ($cases as $case) {
                $caseModuleStats[$case->id] = $this->summarizeCaseDocuments($case);
            }
        } elseif (in_array($workspaceTab, ['lenders', 'queries'], true)) {
            $appBase = \App\Models\GoalBankApplication::query()
                ->whereHas('loanCase', function ($q) use ($scope) {
                    if ($scope !== []) {
                        $q->whereIn('goal_org_unit_id', $scope);
                    }
                });
            $queryBase = \App\Models\GoalBankQuery::query()
                ->whereHas('loanCase', function ($q) use ($scope) {
                    if ($scope !== []) {
                        $q->whereIn('goal_org_unit_id', $scope);
                    }
                });
            $moduleKpis = [
                'applications' => (clone $appBase)->count(),
                'login' => (clone $appBase)->where('status', 'Bank Login')->count(),
                'processing' => (clone $appBase)->where('status', 'Processing')->count(),
                'queries' => (clone $queryBase)->where('status', '!=', 'Resolved')->count(),
                'sanctioned' => (clone $appBase)->where('status', 'Sanctioned')->count(),
            ];
            $cases->loadCount([
                'bankApplications',
                'bankQueries as open_queries_count' => fn ($q) => $q->where('status', '!=', 'Resolved'),
            ]);
            foreach ($cases as $case) {
                $caseModuleStats[$case->id] = [
                    'applications' => (int) ($case->bank_applications_count ?? 0),
                    'open_queries' => (int) ($case->open_queries_count ?? 0),
                ];
            }
        } elseif (in_array($workspaceTab, ['sanction', 'disbursement'], true)) {
            $moduleKpis = [
                'sanctioned_cases' => (clone $kpiBase)->where('status', 'Sanctioned')->count(),
                'disbursed_cases' => (clone $kpiBase)->where('status', 'Disbursed')->count(),
                'active_sanctions' => \App\Models\GoalSanction::query()
                    ->where('status', 'Active')
                    ->whereHas('loanCase', function ($q) use ($scope) {
                        if ($scope !== []) {
                            $q->whereIn('goal_org_unit_id', $scope);
                        }
                    })->count(),
                'pending_disb' => \App\Models\GoalDisbursement::query()
                    ->whereNull('verified_at')
                    ->where('status', 'Pending Verification')
                    ->whereHas('loanCase', function ($q) use ($scope) {
                        if ($scope !== []) {
                            $q->whereIn('goal_org_unit_id', $scope);
                        }
                    })->count(),
                'verified_disb' => \App\Models\GoalDisbursement::query()
                    ->whereNotNull('verified_at')
                    ->whereHas('loanCase', function ($q) use ($scope) {
                        if ($scope !== []) {
                            $q->whereIn('goal_org_unit_id', $scope);
                        }
                    })->count(),
            ];
            $cases->load(['sanctions' => fn ($q) => $q->latest(), 'disbursements']);
            foreach ($cases as $case) {
                $active = $case->sanctions->firstWhere('status', 'Active');
                $verified = (float) $case->disbursements->whereNotNull('verified_at')->sum('amount');
                $pending = (float) $case->disbursements
                    ->whereNull('verified_at')
                    ->where('status', 'Pending Verification')
                    ->sum('amount');
                $caseModuleStats[$case->id] = [
                    'sanction_amount' => $active?->sanction_amount ?? $case->sanction_amount,
                    'verified_disb' => $verified,
                    'pending_disb' => $pending,
                ];
            }
        }

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
            'caseBranchManagerIds',
            'module',
            'moduleView',
            'workspaceTab',
            'moduleKpis',
            'caseModuleStats'
        ));
    }

    public function export(Request $request): StreamedResponse
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
        ]);

        $scope = $this->scopedOrgIds();
        $tab = $request->input('tab', 'all');

        $baseQuery = GoalLoanCase::query()->with([
            'owner:id,name',
            'orgUnit:id,name',
        ]);
        if ($scope !== []) {
            $baseQuery->whereIn('goal_org_unit_id', $scope);
        }

        if ($tab === 'mine') {
            $baseQuery->where('owner_id', auth()->id());
        } elseif (! $request->filled('status')) {
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

        $rows = $baseQuery->latest('updated_at')->limit(5000)->get();
        $filename = 'loan-cases-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Case No', 'Customer', 'Mobile', 'Product', 'Status',
                'Loan Amount', 'Branch', 'Owner', 'Updated At',
            ]);
            foreach ($rows as $case) {
                fputcsv($out, [
                    $case->case_no,
                    $case->customer_name,
                    $case->mobile,
                    $case->product,
                    $case->status,
                    $case->loan_amount,
                    $case->orgUnit?->name,
                    $case->owner?->name,
                    optional($case->updated_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Aggregate document checklist progress for a case (customer_details.documents).
     * Checklist-first: "received" means recorded (optional file), not upload-required.
     *
     * @return array{total:int,required:int,received:int,uploaded:int,pending:int,verified:int,rejected:int,missing:int,label:string}
     */
    protected function summarizeCaseDocuments(GoalLoanCase $case): array
    {
        $docs = collect(data_get($case->customer_details, 'documents', []) ?: []);
        $received = $docs->filter(fn ($d) => \App\Services\Goal\GoalDocumentCatalog::isReceived(is_array($d) ? $d : []))->count();
        $uploaded = $docs->filter(fn ($d) => ! empty($d['path'] ?? null) || ! empty($d['storage_path'] ?? null))->count();
        $pending = $docs->filter(function ($d) {
            $row = is_array($d) ? $d : [];
            $st = \App\Services\Goal\GoalDocumentCatalog::normalizeStatus($row);

            return $st === 'Pending' && \App\Services\Goal\GoalDocumentCatalog::isReceived($row);
        })->count();
        $verified = $docs->where('status', 'Verified')->count();
        $rejected = $docs->where('status', 'Rejected')->count();
        $mandatory = $docs->filter(fn ($d) => ! empty($d['mandatory']))->count();
        $mandatoryReceived = $docs->filter(function ($d) {
            $row = is_array($d) ? $d : [];

            return ! empty($row['mandatory']) && \App\Services\Goal\GoalDocumentCatalog::isReceived($row);
        })->count();
        $missing = max(0, $mandatory - $mandatoryReceived);
        $total = $docs->count();
        $label = $total === 0
            ? 'No checklist'
            : ($missing > 0
                ? $missing.' missing'
                : ($pending > 0 ? $pending.' pending' : ($rejected > 0 ? $rejected.' rejected' : 'Complete')));

        return [
            'total' => $total,
            'required' => $mandatory,
            'received' => $received,
            'uploaded' => $uploaded,
            'pending' => $pending,
            'verified' => $verified,
            'rejected' => $rejected,
            'missing' => $missing,
            'label' => $label,
        ];
    }

    /**
     * Case document checklist as stored (staff add items; nothing auto-required from catalog).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function ensureDocumentChecklist(GoalLoanCase $case): array
    {
        $details = is_array($case->customer_details) ? $case->customer_details : [];

        return array_values($details['documents'] ?? []);
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

        $purposes = GoalLoanPurpose::activeNames();
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
            'mobile' => PhoneNumber::mobileRules(true),
            'alt_mobile' => PhoneNumber::mobileRules(false),
            'email' => 'nullable|email|max:150',
            'preferred_contact_time' => 'nullable|string|max:80',
            'consent_recorded' => 'accepted',
            'product' => 'required|string|max:100',
            'loan_amount' => $amountRule,
            'purpose' => [
                'nullable',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $allowed = GoalLoanPurpose::activeNames();
                    if ($allowed !== [] && ! in_array((string) $value, $allowed, true)) {
                        $fail('Select a valid purpose from the master list.');
                    }
                },
            ],
            'tenure_months' => ['nullable', 'string', 'max:5', 'regex:/^\d{1,3}$/'],
            'employment_type' => $saveAction === 'next' ? 'required|string|max:60' : 'nullable|string|max:60',
            'monthly_income' => $saveAction === 'next'
                ? ['required', 'string', 'max:20', 'regex:/^\d{1,12}(\.\d{1,2})?$/']
                : $money,
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
            'consent_recorded.accepted' => 'Customer consent must be recorded.',
            'loan_amount.required' => 'Requested amount is required to proceed.',
            'loan_amount.regex' => 'Enter a valid amount (digits, optional 2 decimals).',
            'employment_type.required' => 'Employment type is required to proceed.',
            'monthly_income.required' => 'Monthly income is required to proceed.',
            'monthly_income.regex' => 'Enter a valid monthly income.',
            'existing_obligations.regex' => 'Enter a valid obligations amount.',
            'net_income.regex' => 'Enter a valid net income.',
            'tenure_months.regex' => 'Tenure must be a whole number of months.',
            'work_experience_years.regex' => 'Enter valid work experience in years.',
        ], array_merge(PhoneNumber::messages('mobile'), PhoneNumber::messages('alt_mobile')));

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

        // Checklist is built on the case workspace (Add document + Required checkbox).
        $docs = [];

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
            'tasks' => fn ($q) => $q->with('assignee:id,name')->latest()->limit(50),
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

        // Hydrate Branch MIS for the tab (pipeline sync + commercial auto-calc; not persisted until Save / PD-01).
        $case->setAttribute(
            'branch_mis',
            app(\App\Services\Goal\GoalBranchMisService::class)->apply($case, [])
        );

        $next = GoalWorkflow::CASE_TRANSITIONS[$case->status] ?? [];
        $user = auth()->user();
        $workspaceLocked = GoalWorkflow::isWorkspaceLocked($case->status);
        $canOverrideLock = $user->hasCrmPermission('manage_permissions_guard');
        $canEdit = $user->hasCrmPermission('edit_case_workspace_guard')
            && (! $workspaceLocked || $canOverrideLock);
        // Status transitions (e.g. Disbursed → PMS, Rejected → Closed) stay available.
        $canAdvanceStatus = $user->hasCrmPermission('edit_case_workspace_guard') && $next !== [];
        $canDisburse = $user->hasCrmPermission('record_disbursement_guard')
            && in_array($case->status, ['Sanctioned', 'Disbursed'], true);
        $canReassign = $user->hasCrmPermission('bm_allocation_guard')
            && (! $workspaceLocked || $canOverrideLock);
        $canVerifyDocs = ($user->hasCrmPermission('bm_allocation_guard')
            || $user->hasCrmPermission('pms_verification_guard')
            || $user->hasCrmPermission('manage_permissions_guard'))
            && (! $workspaceLocked || $canOverrideLock);
        $canPms = $user->hasCrmPermission('pms_verification_guard');

        $bmId = $this->resolveCaseBranchManagerId($case);
        $branchManager = $bmId
            ? User::query()->find($bmId, ['id', 'name', 'job_role', 'crm_role_type', 'mobile'])
            : null;

        $progress = $this->caseProgressSteps($case);

        $canViewMis = $user->isSuperAdmin()
            || $user->hasCrmPermission('branch_mis_guard')
            || $user->hasCrmPermission('manage_permissions_guard');
        $canViewFollowups = $user->isSuperAdmin()
            || $user->hasCrmPermission('customer_visits_guard')
            || $user->hasCrmPermission('edit_case_workspace_guard')
            || $user->hasCrmPermission('manage_permissions_guard');

        $workspaceTabs = [
            'overview' => ['label' => 'Overview', 'icon' => 'bi-grid'],
            'customer' => ['label' => 'Customer', 'icon' => 'bi-person'],
            'recommendation' => ['label' => 'Recommendation', 'icon' => 'bi-lightbulb'],
            'documents' => ['label' => 'Documents', 'icon' => 'bi-file-earmark'],
            'banking' => ['label' => 'Banking', 'icon' => 'bi-bank'],
        ];
        if ($canViewMis) {
            $workspaceTabs['mis'] = ['label' => 'Branch MIS', 'icon' => 'bi-clipboard-data'];
        }
        if ($canViewFollowups) {
            $workspaceTabs['followups'] = ['label' => 'Follow-ups', 'icon' => 'bi-geo-alt'];
        }

        // Prefer explicit ?tab= (sidebar / list deep-link); else consume session from module hub.
        // Legacy keys map into consolidated tabs + in-pane sections.
        $requestedTab = (string) request('tab', session('cw_tab', 'overview'));
        [$activeTab, $activeSection] = $this->resolveWorkspaceTab(
            $requestedTab,
            (string) request('section', ''),
            array_keys($workspaceTabs)
        );
        session()->forget('cw_tab');

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

        $docs = $this->ensureDocumentChecklist($case);
        $summary = $this->summarizeCaseDocuments($case);
        $docSummary = [
            'required' => $summary['required'],
            'received' => $summary['received'],
            'uploaded' => $summary['uploaded'],
            'pending' => $summary['pending'],
            'verified' => $summary['verified'],
            'rejected' => $summary['rejected'],
            'missing' => $summary['missing'],
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
            'canAdvanceStatus',
            'workspaceLocked',
            'canDisburse',
            'canReassign',
            'canVerifyDocs',
            'canPms',
            'branchManager',
            'progress',
            'activeTab',
            'activeSection',
            'workspaceTabs',
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

    /**
     * Map legacy / hub tab keys onto the consolidated workspace tabs.
     *
     * @param  list<string>  $visibleTabs
     * @return array{0: string, 1: string}
     */
    protected function resolveWorkspaceTab(string $requestedTab, string $requestedSection, array $visibleTabs): array
    {
        $legacy = [
            'lenders' => ['banking', 'lenders'],
            'queries' => ['banking', 'queries'],
            'sanction' => ['banking', 'sanction'],
            'disbursement' => ['banking', 'disbursement'],
            'visits' => ['followups', 'visits'],
            'activity' => ['followups', 'activity'],
        ];

        if (isset($legacy[$requestedTab])) {
            [$tab, $section] = $legacy[$requestedTab];
        } elseif ($requestedTab === 'banking') {
            $tab = 'banking';
            $section = $requestedSection !== '' ? $requestedSection : 'lenders';
        } elseif ($requestedTab === 'followups') {
            $tab = 'followups';
            $section = $requestedSection !== '' ? $requestedSection : 'visits';
        } else {
            $tab = $requestedTab;
            $section = '';
        }

        $bankingSections = ['lenders', 'queries', 'sanction', 'disbursement'];
        $followSections = ['visits', 'activity'];

        if ($tab === 'banking' && ! in_array($section, $bankingSections, true)) {
            $section = 'lenders';
        }
        if ($tab === 'followups' && ! in_array($section, $followSections, true)) {
            $section = 'visits';
        }

        if (! in_array($tab, $visibleTabs, true)) {
            // Hidden by role — fall back to overview (or first visible).
            $tab = in_array('overview', $visibleTabs, true) ? 'overview' : ($visibleTabs[0] ?? 'overview');
            $section = '';
        }

        return [$tab, $section];
    }

    public function updateWorkspace(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, (string) $request->input('cw_tab', 'overview'))) {
            return $redirect;
        }

        $mobileRule = PhoneNumber::mobileRules(false);
        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'mobile' => PhoneNumber::mobileRules(true),
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
        ], array_merge([
            'customer_name.required' => 'Customer name is required.',
            'loan_amount.regex' => 'Enter a valid amount (digits, optional 2 decimals).',
        ], PhoneNumber::messages('mobile'), PhoneNumber::messages('customer_details.applicant.mobile'), PhoneNumber::messages('customer_details.co_applicant.mobile')));

        // Use the full posted customer_details payload. $request->validate() / validated()
        // drops nested keys that are not listed in the rules (e.g. father_name, employment),
        // which made most Customer-tab fields appear to "save" then vanish on reload.
        $incomingCd = is_array($request->input('customer_details'))
            ? $request->input('customer_details')
            : [];

        $docs = data_get($incomingCd, 'documents', []);
        if (is_array($docs)) {
            foreach ($docs as $i => $doc) {
                if (($doc['status'] ?? '') === 'Rejected' && empty(trim((string) ($doc['rejection_reason'] ?? '')))) {
                    return $this->workspaceValidationError(
                        $request,
                        ["customer_details.documents.$i.rejection_reason" => 'Rejection reason is required for rejected documents.'],
                        $data['cw_tab'] ?? 'documents'
                    );
                }
            }
        }

        // Empty co-applicant / optional selects often post as "" — normalize before required checks.
        $incomingCd = $this->normalizeCustomerDetailsInput($incomingCd);
        if ($incomingCd !== []) {
            $reqErrors = GoalCaseRequiredFields::validateCustomerDetails($incomingCd);
            if ($reqErrors !== []) {
                return $this->workspaceValidationError(
                    $request,
                    $reqErrors,
                    $data['cw_tab'] ?? 'customer'
                );
            }
        }
        $mergedCd = $this->mergeWorkspaceJson($case->customer_details, $incomingCd);
        // Preserve verification status from stored docs — only verify endpoints may set Verified/Rejected.
        if (array_key_exists('documents', $incomingCd)) {
            $existingByKey = collect(data_get($case->customer_details, 'documents', []) ?: [])
                ->keyBy(fn ($d) => ($d['party'] ?? 'Applicant').'|'.($d['key'] ?? ''));
            $mergedDocs = [];
            foreach (array_values($incomingCd['documents'] ?? []) as $doc) {
                $k = ($doc['party'] ?? 'Applicant').'|'.($doc['key'] ?? '');
                $prev = $existingByKey->get($k, []);
                $incomingStatus = $doc['status'] ?? null;
                if (in_array($incomingStatus, ['Verified', 'Rejected'], true)) {
                    $doc['status'] = $prev['status'] ?? 'Pending';
                    $doc['reviewer'] = $prev['reviewer'] ?? null;
                    $doc['rejection_reason'] = $prev['rejection_reason'] ?? null;
                }
                // Keep file path / upload metadata from stored row unless newly uploaded elsewhere.
                foreach (['path', 'storage_path', 'file_name', 'uploaded_at', 'uploaded_by', 'uploaded_by_id'] as $fileKey) {
                    if (empty($doc[$fileKey]) && ! empty($prev[$fileKey])) {
                        $doc[$fileKey] = $prev[$fileKey];
                    }
                }
                $mergedDocs[] = $doc;
            }
            $mergedCd['documents'] = $mergedDocs;
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
        $prevEquifax = data_get($case->bm_reco, 'equifax');
        // Prefer full posted bm_reco / collateral / loans (same validated()-key trap as customer_details).
        $incomingBm = $request->input('bm_reco');
        if (is_array($incomingBm)) {
            unset($incomingBm['equifax']); // Equifax managed via dedicated record route
        } else {
            $incomingBm = $data['bm_reco'] ?? null;
        }
        $case->bm_reco = $this->mergeWorkspaceJson($case->bm_reco, $incomingBm);
        if (is_array($prevEquifax) && (
            ! empty($prevEquifax['path'])
            || ! empty($prevEquifax['received'])
            || filled($prevEquifax['recorded_at'] ?? null)
        )) {
            $mergedBm = is_array($case->bm_reco) ? $case->bm_reco : [];
            $mergedBm['equifax'] = $prevEquifax;
            $case->bm_reco = $mergedBm;
        }

        // Keep Borrower Profile (age/DOB/PAN/etc.) in sync with Customer tab.
        $cwTab = (string) ($data['cw_tab'] ?? $request->input('cw_tab', 'customer'));
        [$syncedCd, $syncedBm] = $this->syncBorrowerProfileWithCustomer(
            is_array($case->customer_details) ? $case->customer_details : [],
            is_array($case->bm_reco) ? $case->bm_reco : [],
            $cwTab
        );
        $case->customer_details = $syncedCd;
        $case->bm_reco = $syncedBm;
        $incomingCollateral = $request->input('collateral');
        $case->collateral = $this->mergeWorkspaceJson(
            $case->collateral,
            is_array($incomingCollateral) ? $incomingCollateral : ($data['collateral'] ?? null)
        );
        $incomingLoans = $request->input('existing_loans');
        if (! is_array($incomingLoans)) {
            $incomingLoans = $data['existing_loans'] ?? null;
        }
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
        if (array_key_exists('branch_mis', $data) || ($data['cw_tab'] ?? '') === 'mis') {
            $case->branch_mis = app(\App\Services\Goal\GoalBranchMisService::class)
                ->apply($case, $data['branch_mis'] ?? []);
        } else {
            $case->branch_mis = $this->mergeWorkspaceJson($case->branch_mis, $data['branch_mis'] ?? null);
        }
        $case->save();
        $this->syncLinkedLeadFromCase($case);

        GoalCaseAudit::record($case, 'updated', 'Case details updated', auth()->user(), [
            'from' => json_encode($before),
            'to' => json_encode([
                'customer_name' => $case->customer_name,
                'mobile' => $case->mobile,
                'product' => $case->product,
                'loan_amount' => (string) $case->loan_amount,
            ]),
        ]);

        $tab = $data['cw_tab'] ?? 'customer';
        if ($request->expectsJson() || $request->ajax()) {
            $live = $this->workspaceLivePayload($case);

            return response()->json([
                'success' => true,
                'message' => 'Case workspace saved.',
                'cw_tab' => $tab,
                'reload' => false,
                'customer_name' => $live['customer_name'] ?? $case->customer_name,
                'mobile' => $live['mobile'] ?? $case->mobile,
                'borrower_profile' => data_get($case->bm_reco, 'profile', []) ?: [],
                'customer_details' => [
                    'applicant' => data_get($case->customer_details, 'applicant', []) ?: [],
                    'co_applicant' => data_get($case->customer_details, 'co_applicant', []) ?: [],
                    'employment' => data_get($case->customer_details, 'employment', []) ?: [],
                    'residence' => data_get($case->customer_details, 'residence', []) ?: [],
                    'property' => data_get($case->customer_details, 'property', []) ?: [],
                    'purpose' => data_get($case->customer_details, 'purpose'),
                    'tenure' => data_get($case->customer_details, 'tenure', data_get($case->customer_details, 'loan.tenure')),
                    'preferred_contact_time' => data_get($case->customer_details, 'preferred_contact_time'),
                    'obligations' => data_get($case->customer_details, 'obligations'),
                ],
                'live' => $live,
            ]);
        }

        return back()
            ->with('success', 'Case workspace saved.')
            ->with('cw_tab', $tab);
    }

    /**
     * @param  array<string, string|array<int, string>>  $errors
     */
    protected function workspaceValidationError(Request $request, array $errors, string $tab = 'customer')
    {
        $normalized = [];
        foreach ($errors as $key => $message) {
            $normalized[$key] = is_array($message) ? array_values($message) : [(string) $message];
        }

        if ($request->expectsJson() || $request->ajax()) {
            $first = collect($normalized)->flatten()->first() ?: 'Please fix the highlighted fields.';

            return response()->json([
                'message' => $first,
                'errors' => $normalized,
                'cw_tab' => $tab,
            ], 422);
        }

        $flat = [];
        foreach ($normalized as $key => $messages) {
            $flat[$key] = $messages[0] ?? 'Invalid.';
        }

        return back()
            ->withInput()
            ->withErrors($flat)
            ->with('cw_tab', $tab);
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

    /**
     * Snapshot used by the case workspace JS to refresh Overview / header / cross-tab UI without reload.
     *
     * @return array<string, mixed>
     */
    protected function workspaceLivePayload(GoalLoanCase $case): array
    {
        $cd = is_array($case->customer_details) ? $case->customer_details : [];
        $app = is_array($cd['applicant'] ?? null) ? $cd['applicant'] : [];
        $emp = is_array($cd['employment'] ?? null) ? $cd['employment'] : [];
        $mobileDigits = preg_replace('/\D+/', '', (string) ($case->mobile ?? '')) ?: '';
        $mobileDisplay = strlen($mobileDigits) === 10
            ? ('+91 '.substr($mobileDigits, 0, 5).' '.substr($mobileDigits, 5))
            : ($case->mobile ? ('+91 '.$case->mobile) : '—');
        $email = trim((string) ($app['email'] ?? ($cd['email'] ?? '')));
        $ten = data_get($cd, 'tenure', data_get($cd, 'loan.tenure'));
        $fmt = function ($n): string {
            if ($n === null || $n === '') {
                return '—';
            }
            $num = (float) $n;
            $neg = $num < 0;
            $num = abs($num);
            $int = (string) (int) floor($num);
            $dec = round($num - floor($num), 2);
            $last3 = substr($int, -3);
            $rest = substr($int, 0, -3);
            if ($rest !== '') {
                $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
                $int = $rest.','.$last3;
            }
            $out = '₹ '.$int;
            if ($dec > 0) {
                $out .= '.'.str_pad((string) (int) round($dec * 100), 2, '0', STR_PAD_LEFT);
            }

            return ($neg ? '-' : '').$out;
        };

        return [
            'customer_name' => (string) ($case->customer_name ?? ''),
            'mobile' => (string) ($case->mobile ?? ''),
            'mobile_digits' => $mobileDigits,
            'mobile_display' => $mobileDisplay,
            'email' => $email !== '' ? $email : '—',
            'product' => (string) ($case->product ?: '—'),
            'loan_amount' => $case->loan_amount,
            'loan_amount_display' => $fmt($case->loan_amount),
            'branch' => (string) ($case->orgUnit?->name ?? '—'),
            'status' => (string) ($case->status ?? ''),
            'employment_type' => (string) (($emp['type_applicant'] ?? $cd['employment_type'] ?? '') ?: '—'),
            'monthly_income_display' => $fmt($emp['income_applicant'] ?? ($cd['monthly_income'] ?? null)),
            'purpose' => (string) (($cd['purpose'] ?? data_get($cd, 'loan.purpose')) ?: '—'),
            'tenure_display' => $ten ? ($ten.' months') : '—',
            'obligations_display' => $fmt($cd['obligations'] ?? null),
            'preferred_contact_time' => (string) (($cd['preferred_contact_time'] ?? '') ?: '—'),
            'applicant_name' => (string) ($app['name'] ?? $case->customer_name ?? ''),
            'applicant_age' => (string) ($app['age'] ?? ''),
            'applicant_dob' => (string) ($app['dob'] ?? ''),
            'applicant_pan' => (string) ($app['pan'] ?? ''),
        ];
    }

    /**
     * Two-way sync between customer_details and bm_reco.profile for shared borrower fields.
     *
     * @param  array<string, mixed>  $customerDetails
     * @param  array<string, mixed>  $bmReco
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function syncBorrowerProfileWithCustomer(array $customerDetails, array $bmReco, string $tab): array
    {
        $filled = static function ($value): bool {
            if ($value === null) {
                return false;
            }
            if (is_string($value)) {
                return trim($value) !== '';
            }

            return $value !== '';
        };

        $parties = [
            'applicant' => 'applicant',
            'co_applicant' => 'co_applicant',
        ];

        foreach ($parties as $cdKey => $bmParty) {
            $profile = data_get($bmReco, "profile.$bmParty", []);
            if (! is_array($profile)) {
                $profile = [];
            }

            $name = data_get($customerDetails, "$cdKey.name");
            $age = data_get($customerDetails, "$cdKey.age");
            $dob = data_get($customerDetails, "$cdKey.dob");
            $pan = data_get($customerDetails, "$cdKey.pan");
            $relationship = $cdKey === 'applicant'
                ? (data_get($customerDetails, "$cdKey.relationship") ?: 'Self')
                : data_get($customerDetails, "$cdKey.relationship");
            $profession = $cdKey === 'applicant'
                ? (data_get($customerDetails, 'employment.designation_applicant')
                    ?: data_get($customerDetails, 'employment.type_applicant'))
                : (data_get($customerDetails, 'employment.designation_co')
                    ?: data_get($customerDetails, 'employment.type_co'));
            $netIncome = $cdKey === 'applicant'
                ? data_get($customerDetails, 'employment.income_applicant')
                : data_get($customerDetails, 'employment.income_co');
            $grossIncome = $cdKey === 'applicant'
                ? (data_get($customerDetails, 'employment.gross_income_applicant') ?: $netIncome)
                : (data_get($customerDetails, 'employment.gross_income_co') ?: $netIncome);

            $customerMap = [
                'name' => $name,
                'age' => $age,
                'dob' => $dob,
                'pan' => $pan,
                'relationship' => $relationship,
                'profession' => $profession,
                'net_income' => $netIncome,
                'gross_income' => $grossIncome,
            ];

            if ($tab === 'recommendation') {
                // Recommendation edits write back into Customer when filled.
                foreach (['name', 'age', 'dob', 'pan', 'relationship'] as $field) {
                    $v = $profile[$field] ?? null;
                    if ($filled($v)) {
                        data_set($customerDetails, "$cdKey.$field", is_string($v) ? trim($v) : $v);
                    }
                }
                if ($filled($profile['profession'] ?? null)) {
                    $prof = trim((string) $profile['profession']);
                    if ($cdKey === 'applicant') {
                        if (! $filled(data_get($customerDetails, 'employment.designation_applicant'))) {
                            data_set($customerDetails, 'employment.designation_applicant', $prof);
                        }
                    } elseif (! $filled(data_get($customerDetails, 'employment.designation_co'))) {
                        data_set($customerDetails, 'employment.designation_co', $prof);
                    }
                }
                if ($filled($profile['net_income'] ?? null)) {
                    $incomePath = $cdKey === 'applicant' ? 'employment.income_applicant' : 'employment.income_co';
                    if (! $filled(data_get($customerDetails, $incomePath))) {
                        data_set($customerDetails, $incomePath, $profile['net_income']);
                    }
                }
                if ($filled($profile['gross_income'] ?? null)) {
                    $grossPath = $cdKey === 'applicant' ? 'employment.gross_income_applicant' : 'employment.gross_income_co';
                    data_set($customerDetails, $grossPath, $profile['gross_income']);
                }
            }

            // Always keep BM Borrower Profile filled from Customer when profile slot is empty,
            // and overwrite shared borrower fields from Customer after Customer-tab saves.
            // Prevents AJAX Customer save + stale empty Recommendation form from wiping profile.
            foreach ($customerMap as $field => $value) {
                if (! $filled($value)) {
                    continue;
                }
                $shouldWrite = $tab === 'customer'
                    ? true
                    : ! $filled($profile[$field] ?? null);
                if ($shouldWrite) {
                    $profile[$field] = is_string($value) ? trim($value) : $value;
                }
            }
            data_set($bmReco, "profile.$bmParty", $profile);
        }

        return [$customerDetails, $bmReco];
    }

    /**
     * Normalize posted customer_details (trim strings, drop empty nested leaves for cleaner JSON).
     *
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    protected function normalizeCustomerDetailsInput(array $incoming): array
    {
        $walk = function ($value) use (&$walk) {
            if (! is_array($value)) {
                if (is_string($value)) {
                    $value = trim($value);
                }

                return $value;
            }

            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $walk($v);
            }

            return $out;
        };

        $normalized = $walk($incoming);

        // Applicant relationship defaults to Self when left blank.
        if (is_array($normalized['applicant'] ?? null)) {
            $rel = trim((string) ($normalized['applicant']['relationship'] ?? ''));
            if ($rel === '') {
                $normalized['applicant']['relationship'] = 'Self';
            }
        }

        return $normalized;
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
            $case->loadMissing('disbursements');
            $hasVerified = $case->disbursements->whereNotNull('verified_at')->isNotEmpty();

            // Single PD-01 path: never atomically verify via status transition.
            // If amount fields are present, create Pending Verification only.
            if (! empty($data['amount']) || ! empty($data['bank_reference']) || ! empty($data['disbursed_on'])) {
                try {
                    $pd01->recordPending($case, [
                        'amount' => $data['amount'],
                        'disbursed_on' => $data['disbursed_on'],
                        'bank_reference' => $data['bank_reference'],
                        'evidence_note' => $data['evidence_note'] ?? null,
                    ], auth()->user());
                } catch (\Throwable $e) {
                    return $this->ajaxFail(['to_status' => $e->getMessage()], 'disbursement');
                }

                return $this->ajaxOk(
                    'Disbursement recorded as Pending Verification. Use Verify (PD-01) to recognize revenue and open PMS / Accounts.',
                    'disbursement'
                );
            }

            if (! $hasVerified) {
                return $this->ajaxFail([
                    'to_status' => 'Record and verify a disbursement (PD-01) before moving the case to Disbursed.',
                ], 'disbursement');
            }
            // Fall through: allow status update when verified disbursement already exists.
        }

        $case->loadMissing(['bankApplications', 'bankQueries', 'sanctions', 'disbursements']);
        $docs = collect(data_get($case->customer_details, 'documents', []) ?: []);
        $mandatory = $docs->where('mandatory', true);
        // Only docs marked Required on the case must be verified. None required = gate passes.
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
            'sancEvidence' => $hasSancEvidence,
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
            return $this->ajaxFail(['to_status' => implode(' ', $gate['errors'])], 'overview');
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

        return $this->ajaxOk('Case status updated.', 'overview');
    }

    public function storeTask(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'overview')) {
            return $redirect;
        }
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'task_type' => 'nullable|in:visit,followup,document,other',
            'due_at' => 'nullable|date|after_or_equal:now',
            'notes' => 'nullable|string',
        ], [
            'due_at.after_or_equal' => 'Task due date must be today or a future date/time.',
        ]);
        $task = GoalCaseTask::create([
            'goal_loan_case_id' => $case->id,
            'title' => $data['title'],
            'task_type' => $data['task_type'] ?? 'visit',
            'due_at' => $data['due_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assigned_to' => $case->owner_id,
            'created_by' => auth()->id(),
            'status' => GoalCaseTask::STATUS_OPEN,
        ]);
        GoalCaseAudit::record($case, 'visit', 'Task scheduled', auth()->user(), [
            'to' => optional($task->due_at)->format('d-m-Y H:i'),
            'related_type' => GoalCaseTask::class,
            'related_id' => $task->id,
            'body' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Task added.')->with('cw_tab', 'followups');
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

    protected function documentCatalog(): array
    {
        return \App\Services\Goal\GoalDocumentCatalog::catalog();
    }

    protected function documentsForProduct(?string $productName): array
    {
        return \App\Services\Goal\GoalDocumentCatalog::forProduct($productName);
    }

    protected function productKey(?string $name): string
    {
        return \App\Services\Goal\GoalDocumentCatalog::productKey($name);
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

    /**
     * Keep Loan Leads list in sync when Case Workspace updates customer / product fields.
     */
    protected function syncLinkedLeadFromCase(GoalLoanCase $case): void
    {
        if (! $case->goal_loan_lead_id) {
            return;
        }

        $lead = $case->relationLoaded('lead')
            ? $case->lead
            : GoalLoanLead::query()->find($case->goal_loan_lead_id);

        if (! $lead) {
            return;
        }

        $name = trim((string) ($case->customer_name ?? ''));
        if ($name === '') {
            $name = trim((string) data_get($case->customer_details, 'applicant.name', ''));
        }
        $mobile = preg_replace('/\D+/', '', (string) ($case->mobile ?? ''));
        if ($mobile === '') {
            $mobile = preg_replace('/\D+/', '', (string) data_get($case->customer_details, 'applicant.mobile', ''));
        }
        $email = trim((string) data_get($case->customer_details, 'applicant.email', ''));

        $dirty = false;
        if ($name !== '' && (string) $lead->customer_name !== $name) {
            $lead->customer_name = \Illuminate\Support\Str::limit($name, 150, '');
            $dirty = true;
        }
        if ($mobile !== '' && (string) $lead->mobile !== $mobile) {
            $lead->mobile = $mobile;
            $dirty = true;
        }
        if ($email !== '' && (string) ($lead->email ?? '') !== $email) {
            $lead->email = \Illuminate\Support\Str::limit($email, 150, '');
            $dirty = true;
        }
        if (filled($case->product) && (string) ($lead->product ?? '') !== (string) $case->product) {
            $lead->product = $case->product;
            $dirty = true;
        }
        if ($case->loan_amount !== null && (string) ($lead->loan_amount ?? '') !== (string) $case->loan_amount) {
            $lead->loan_amount = $case->loan_amount;
            $dirty = true;
        }

        if (! $dirty) {
            return;
        }

        $lead->save();
        app(\App\Services\Goal\GoalPartyService::class)->attachLead($lead);
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
