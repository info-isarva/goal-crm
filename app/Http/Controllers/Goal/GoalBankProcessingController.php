<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalBankApplication;
use App\Models\GoalBankQuery;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\User;
use App\Services\Goal\GoalCaseAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalBankProcessingController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('view_loan_cases_guard');

        $request->validate([
            'q' => 'nullable|string|max:150',
            'tab' => 'nullable|in:all,login,query,processing,sanctioned,rejected',
            'branch' => 'nullable|integer',
            'lender' => 'nullable|string|max:150',
            'status' => 'nullable|string|max:40',
            'owner' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|in:10,25,50,100',
        ]);

        $scope = $this->scopedOrgIds();
        $tab = (string) $request->input('tab', 'all');

        $base = GoalBankApplication::query()
            ->with([
                'loanCase:id,case_no,customer_name,goal_org_unit_id,owner_id,loan_amount',
                'loanCase.orgUnit:id,name',
                'loanCase.owner:id,name,job_role,crm_role_type',
            ])
            ->whereHas('loanCase', function ($q) use ($scope) {
                if ($scope !== []) {
                    $q->whereIn('goal_org_unit_id', $scope);
                }
            });

        $kpiBase = clone $base;
        $queryKpiBase = GoalBankQuery::query()
            ->where('status', '!=', 'Resolved')
            ->whereHas('loanCase', function ($q) use ($scope) {
                if ($scope !== []) {
                    $q->whereIn('goal_org_unit_id', $scope);
                }
            });

        $kpis = [
            'total' => (clone $kpiBase)->count(),
            'login' => (clone $kpiBase)->where('status', 'Bank Login')->count(),
            'queries' => (clone $queryKpiBase)->count(),
            'processing' => (clone $kpiBase)->where('status', 'Processing')->count(),
            'sanctioned' => (clone $kpiBase)->where('status', 'Sanctioned')->count(),
            'rejected' => (clone $kpiBase)->where('status', 'Rejected')->count(),
        ];

        $tabs = [
            'all' => ['label' => 'All Applications', 'count' => $kpis['total'], 'icon' => 'bi-layers'],
            'login' => ['label' => 'Bank Login', 'count' => $kpis['login'], 'icon' => 'bi-box-arrow-in-right'],
            'query' => ['label' => 'Bank Query', 'count' => $kpis['queries'], 'icon' => 'bi-chat-left-text'],
            'processing' => ['label' => 'Processing', 'count' => $kpis['processing'], 'icon' => 'bi-gear'],
            'sanctioned' => ['label' => 'Sanctioned', 'count' => $kpis['sanctioned'], 'icon' => 'bi-award'],
            'rejected' => ['label' => 'Rejected', 'count' => $kpis['rejected'], 'icon' => 'bi-x-circle'],
        ];

        if ($tab === 'login') {
            $base->where('status', 'Bank Login');
        } elseif ($tab === 'query') {
            $base->where('status', 'Bank Query');
        } elseif ($tab === 'processing') {
            $base->where('status', 'Processing');
        } elseif ($tab === 'sanctioned') {
            $base->where('status', 'Sanctioned');
        } elseif ($tab === 'rejected') {
            $base->where('status', 'Rejected');
        } elseif ($request->filled('status')) {
            $base->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $base->where(function ($inner) use ($q) {
                $inner->where('application_ref', 'like', "%{$q}%")
                    ->orWhere('lender_name', 'like', "%{$q}%")
                    ->orWhereHas('loanCase', function ($cq) use ($q) {
                        $cq->where('case_no', 'like', "%{$q}%")
                            ->orWhere('customer_name', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('branch')) {
            $branchId = (int) $request->input('branch');
            $base->whereHas('loanCase', fn ($q) => $q->where('goal_org_unit_id', $branchId));
        }

        if ($request->filled('lender')) {
            $base->where('lender_name', $request->input('lender'));
        }

        if ($request->filled('owner')) {
            $ownerId = (int) $request->input('owner');
            $base->whereHas('loanCase', fn ($q) => $q->where('owner_id', $ownerId));
        }

        if ($request->filled('date_from')) {
            $base->whereDate('login_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $base->whereDate('login_date', '<=', $request->input('date_to'));
        }

        $perPage = (int) ($request->input('per_page') ?: 25);
        $applications = $base->latest('updated_at')->paginate($perPage)->withQueryString();

        $pendingQueries = GoalBankQuery::query()
            ->with([
                'loanCase:id,case_no,customer_name',
                'application:id,lender_name,application_ref',
                'responsible:id,name',
            ])
            ->where('status', '!=', 'Resolved')
            ->whereHas('loanCase', function ($q) use ($scope) {
                if ($scope !== []) {
                    $q->whereIn('goal_org_unit_id', $scope);
                }
            })
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        $branches = GoalOrgUnit::query()
            ->ofType('branch')
            ->active()
            ->when($scope !== [], fn ($q) => $q->whereIn('id', $scope))
            ->orderBy('name')
            ->get(['id', 'name']);

        $lenders = GoalBankApplication::query()
            ->whereHas('loanCase', function ($q) use ($scope) {
                if ($scope !== []) {
                    $q->whereIn('goal_org_unit_id', $scope);
                }
            })
            ->whereNotNull('lender_name')
            ->distinct()
            ->orderBy('lender_name')
            ->pluck('lender_name');

        $owners = User::query()
            ->whereIn('id', GoalLoanCase::query()
                ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
                ->whereNotNull('owner_id')
                ->distinct()
                ->pluck('owner_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $cases = GoalLoanCase::query()
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get(['id', 'case_no', 'customer_name', 'loan_amount']);

        $assignees = User::query()
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name']);

        $canEdit = auth()->user()->hasCrmPermission('edit_case_workspace_guard');
        $statuses = ['Bank Login', 'Bank Query', 'Processing', 'Sanctioned', 'Rejected', 'Disbursed'];

        $workflow = [
            ['key' => 'Bank Login', 'label' => 'Bank Login', 'desc' => 'Application login with lender', 'count' => $kpis['login']],
            ['key' => 'Bank Query', 'label' => 'Bank Query', 'desc' => 'Clarifications with bank', 'count' => $kpis['queries']],
            ['key' => 'Processing', 'label' => 'Processing', 'desc' => 'Under bank assessment', 'count' => $kpis['processing']],
            ['key' => 'Sanctioned', 'label' => 'Sanctioned', 'desc' => 'Sanction recorded', 'count' => $kpis['sanctioned']],
        ];
        $activeWorkflow = match ($tab) {
            'login' => 'Bank Login',
            'query' => 'Bank Query',
            'processing' => 'Processing',
            'sanctioned' => 'Sanctioned',
            default => ($kpis['queries'] > 0 ? 'Bank Query' : ($kpis['login'] > 0 ? 'Bank Login' : 'Processing')),
        };

        return view('goal.bank-processing.index', compact(
            'applications',
            'pendingQueries',
            'kpis',
            'tabs',
            'tab',
            'branches',
            'lenders',
            'owners',
            'cases',
            'assignees',
            'canEdit',
            'statuses',
            'workflow',
            'activeWorkflow'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('view_loan_cases_guard');

        $request->validate([
            'q' => 'nullable|string|max:150',
            'tab' => 'nullable|in:all,login,query,processing,sanctioned,rejected',
            'branch' => 'nullable|integer',
            'lender' => 'nullable|string|max:150',
            'status' => 'nullable|string|max:40',
            'owner' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $scope = $this->scopedOrgIds();
        $tab = (string) $request->input('tab', 'all');

        $base = GoalBankApplication::query()
            ->with([
                'loanCase:id,case_no,customer_name,goal_org_unit_id,owner_id',
                'loanCase.orgUnit:id,name',
                'loanCase.owner:id,name',
            ])
            ->whereHas('loanCase', function ($q) use ($scope) {
                if ($scope !== []) {
                    $q->whereIn('goal_org_unit_id', $scope);
                }
            });

        if ($tab === 'login') {
            $base->where('status', 'Bank Login');
        } elseif ($tab === 'query') {
            $base->where('status', 'Bank Query');
        } elseif ($tab === 'processing') {
            $base->where('status', 'Processing');
        } elseif ($tab === 'sanctioned') {
            $base->where('status', 'Sanctioned');
        } elseif ($tab === 'rejected') {
            $base->where('status', 'Rejected');
        } elseif ($request->filled('status')) {
            $base->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $base->where(function ($inner) use ($q) {
                $inner->where('application_ref', 'like', "%{$q}%")
                    ->orWhere('lender_name', 'like', "%{$q}%")
                    ->orWhereHas('loanCase', function ($cq) use ($q) {
                        $cq->where('case_no', 'like', "%{$q}%")
                            ->orWhere('customer_name', 'like', "%{$q}%");
                    });
            });
        }
        if ($request->filled('branch')) {
            $branchId = (int) $request->input('branch');
            $base->whereHas('loanCase', fn ($q) => $q->where('goal_org_unit_id', $branchId));
        }
        if ($request->filled('lender')) {
            $base->where('lender_name', $request->input('lender'));
        }
        if ($request->filled('owner')) {
            $ownerId = (int) $request->input('owner');
            $base->whereHas('loanCase', fn ($q) => $q->where('owner_id', $ownerId));
        }
        if ($request->filled('date_from')) {
            $base->whereDate('login_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $base->whereDate('login_date', '<=', $request->input('date_to'));
        }

        $rows = $base->latest('updated_at')->limit(5000)->get();
        $filename = 'bank-processing-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Case', 'Customer', 'Lender', 'Application Ref', 'Status',
                'Login Date', 'Requested Amount', 'Branch', 'Owner',
            ]);
            foreach ($rows as $app) {
                fputcsv($out, [
                    $app->loanCase?->case_no,
                    $app->loanCase?->customer_name,
                    $app->lender_name,
                    $app->application_ref,
                    $app->status,
                    optional($app->login_date)->format('Y-m-d'),
                    $app->requested_amount,
                    $app->loanCase?->orgUnit?->name,
                    $app->loanCase?->owner?->name,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function storeApplication(Request $request)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');

        $data = $request->validate([
            'case_id' => 'required|integer|exists:goal_loan_cases,id',
            'lender_name' => 'required|string|max:150',
            'application_ref' => 'nullable|string|max:100',
            'login_date' => 'nullable|date',
            'status' => 'nullable|in:Bank Login,Bank Query,Processing,Sanctioned,Rejected,Disbursed',
            'requested_amount' => 'nullable|numeric|min:0',
            'follow_up_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $case = GoalLoanCase::query()->findOrFail((int) $data['case_id']);
        $this->assertCaseInScope($case);

        $status = $data['status'] ?? 'Bank Login';
        // New applications cannot jump to Sanctioned / Disbursed — use workspace PD-01 / sanction flows.
        if (in_array($status, ['Sanctioned', 'Disbursed'], true)) {
            return back()->withErrors([
                'status' => 'Create the application at Bank Login / Processing, then record sanction and verify disbursement.',
            ])->withInput();
        }
        if ($status === 'Rejected' && empty(trim((string) ($data['rejection_reason'] ?? '')))) {
            return back()->withErrors(['rejection_reason' => 'Rejected applications must record a rejection reason.'])->withInput();
        }

        $path = null;
        $name = null;
        if ($request->hasFile('evidence')) {
            $file = $request->file('evidence');
            $name = $file->getClientOriginalName();
            $path = $file->store('goal/bank-applications/'.$case->id, 'public');
        }

        $app = GoalBankApplication::create([
            'goal_loan_case_id' => $case->id,
            'lender_name' => $data['lender_name'],
            'application_ref' => $data['application_ref'] ?? null,
            'login_date' => $data['login_date'] ?? now()->toDateString(),
            'status' => $status,
            'requested_amount' => $data['requested_amount'] ?? $case->loan_amount,
            'follow_up_at' => $data['follow_up_at'] ?? null,
            'notes' => trim(($data['notes'] ?? '').(($data['rejection_reason'] ?? '') !== '' ? "\nRejection: ".$data['rejection_reason'] : '')),
            'evidence_path' => $path,
            'evidence_name' => $name,
            'created_by' => auth()->id(),
        ]);

        if (!$case->lender_name) {
            $case->lender_name = $app->lender_name;
            $case->application_ref = $app->application_ref;
            $case->save();
        }

        GoalCaseAudit::record($case, 'application', 'Lender application added', auth()->user(), [
            'to' => $app->lender_name,
            'related_type' => GoalBankApplication::class,
            'related_id' => $app->id,
            'body' => $app->application_ref,
        ]);

        return redirect()
            ->route('goal.bank-processing.index')
            ->with('success', 'Lender application saved.');
    }

    public function storeQuery(Request $request, GoalBankApplication $application)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $case = $application->loanCase;
        $this->assertCaseInScope($case);

        $data = $request->validate([
            'details' => 'required|string|max:2000',
            'responsible_user_id' => 'required|integer|exists:users,id',
            'due_at' => 'required|date',
            'blocks_processing' => 'nullable|boolean',
        ]);

        $query = GoalBankQuery::create([
            'query_ref' => 'BQ-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'goal_loan_case_id' => $case->id,
            'goal_bank_application_id' => $application->id,
            'details' => $data['details'],
            'responsible_user_id' => $data['responsible_user_id'],
            'raised_at' => now(),
            'due_at' => $data['due_at'],
            'status' => 'Open',
            'blocks_processing' => $request->boolean('blocks_processing', true),
            'created_by' => auth()->id(),
        ]);

        $application->status = 'Bank Query';
        $application->save();

        if (in_array($case->status, ['Bank Login', 'Processing'], true)) {
            $case->status = 'Bank Query';
            $case->bank_query = $data['details'];
            $case->bank_query_due = $data['due_at'];
            $case->save();
        }

        GoalCaseAudit::record($case, 'query', 'Bank query raised', auth()->user(), [
            'related_type' => GoalBankQuery::class,
            'related_id' => $query->id,
            'body' => Str::limit($data['details'], 200),
            'to' => $query->query_ref,
        ]);

        return redirect()
            ->route('goal.bank-processing.index', ['tab' => 'query'])
            ->with('success', 'Bank query recorded.');
    }

    protected function assertCaseInScope(GoalLoanCase $case): void
    {
        $scope = $this->scopedOrgIds();
        if ($scope !== [] && $case->goal_org_unit_id && !in_array((int) $case->goal_org_unit_id, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
