<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalBankApplication;
use App\Models\GoalDisbursement;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\GoalSanction;
use App\Models\User;
use Illuminate\Http\Request;

class GoalSanctionDisbursementController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('view_loan_cases_guard');

        $request->validate([
            'q' => 'nullable|string|max:150',
            'tab' => 'nullable|in:all,sanctioned,pending,partial,completed',
            'branch' => 'nullable|integer',
            'lender' => 'nullable|string|max:150',
            'owner' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|in:10,25,50,100',
        ]);

        $scope = $this->scopedOrgIds();
        $tab = (string) $request->input('tab', 'all');

        $base = GoalSanction::query()
            ->with([
                'loanCase:id,case_no,customer_name,goal_org_unit_id,owner_id,loan_amount,status',
                'loanCase.orgUnit:id,name',
                'loanCase.owner:id,name',
                'application:id,lender_name,application_ref,requested_amount,status,goal_loan_case_id',
                'recorder:id,name',
            ])
            ->where('status', 'Active')
            ->whereHas('loanCase', function ($q) use ($scope) {
                if ($scope !== []) {
                    $q->whereIn('goal_org_unit_id', $scope);
                }
            });

        $kpiBase = clone $base;

        // Aggregate disbursements per sanction/case for KPIs
        $disbAgg = GoalDisbursement::query()
            ->selectRaw("
                goal_loan_case_id,
                COALESCE(SUM(CASE WHEN verified_at IS NOT NULL THEN amount ELSE 0 END), 0) as verified_total,
                COALESCE(SUM(CASE WHEN verified_at IS NULL AND status = 'Pending Verification' THEN amount ELSE 0 END), 0) as pending_total
            ")
            ->whereHas('loanCase', function ($q) use ($scope) {
                if ($scope !== []) {
                    $q->whereIn('goal_org_unit_id', $scope);
                }
            })
            ->groupBy('goal_loan_case_id')
            ->get()
            ->keyBy('goal_loan_case_id');

        $activeRows = (clone $kpiBase)->get(['id', 'goal_loan_case_id', 'sanction_amount']);
        $sanctionedAmount = (float) $activeRows->sum('sanction_amount');
        $verifiedAmount = 0.0;
        $tabCounts = [
            'all' => $activeRows->count(),
            'sanctioned' => 0,
            'pending' => 0,
            'partial' => 0,
            'completed' => 0,
        ];

        foreach ($activeRows as $row) {
            $verified = (float) ($disbAgg[$row->goal_loan_case_id]->verified_total ?? 0);
            $pending = (float) ($disbAgg[$row->goal_loan_case_id]->pending_total ?? 0);
            $cap = (float) $row->sanction_amount;
            $remaining = max(0, $cap - $verified);
            $verifiedAmount += $verified;
            $bucket = $this->statusBucket($verified, $pending, $remaining);
            $tabCounts[$bucket]++;
        }

        // "Sanctioned" tab lists all active sanctions (same as All for this hub)
        $tabCounts['sanctioned'] = $activeRows->count();

        $kpis = [
            'applications' => $activeRows->count(),
            'sanctioned_amount' => $sanctionedAmount,
            'verified_amount' => $verifiedAmount,
            'remaining_amount' => max(0, $sanctionedAmount - $verifiedAmount),
        ];

        $tabs = [
            'all' => ['label' => 'All', 'count' => $tabCounts['all'], 'icon' => 'bi-layers'],
            'sanctioned' => ['label' => 'Sanctioned', 'count' => $tabCounts['sanctioned'], 'icon' => 'bi-award'],
            'pending' => ['label' => 'Disbursement Pending', 'count' => $tabCounts['pending'], 'icon' => 'bi-hourglass-split'],
            'partial' => ['label' => 'Partially Disbursed', 'count' => $tabCounts['partial'], 'icon' => 'bi-pie-chart'],
            'completed' => ['label' => 'Completed', 'count' => $tabCounts['completed'], 'icon' => 'bi-check2-circle'],
        ];

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $base->where(function ($inner) use ($q) {
                $inner->whereHas('loanCase', function ($cq) use ($q) {
                    $cq->where('case_no', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%");
                })->orWhereHas('application', function ($aq) use ($q) {
                    $aq->where('lender_name', 'like', "%{$q}%")
                        ->orWhere('application_ref', 'like', "%{$q}%");
                });
            });
        }

        if ($request->filled('branch')) {
            $branchId = (int) $request->input('branch');
            $base->whereHas('loanCase', fn ($q) => $q->where('goal_org_unit_id', $branchId));
        }

        if ($request->filled('lender')) {
            $lender = $request->input('lender');
            $base->whereHas('application', fn ($q) => $q->where('lender_name', $lender));
        }

        if ($request->filled('owner')) {
            $ownerId = (int) $request->input('owner');
            $base->whereHas('loanCase', fn ($q) => $q->where('owner_id', $ownerId));
        }

        if ($request->filled('date_from')) {
            $base->whereDate('sanction_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $base->whereDate('sanction_date', '<=', $request->input('date_to'));
        }

        // Tab filters need post-load bucket — fetch then filter in PHP for accuracy
        $candidates = $base->latest('sanction_date')->latest('id')->get();
        $filtered = $candidates->filter(function (GoalSanction $s) use ($disbAgg, $tab) {
            if ($tab === 'all' || $tab === 'sanctioned') {
                return true;
            }
            $verified = (float) ($disbAgg[$s->goal_loan_case_id]->verified_total ?? 0);
            $pending = (float) ($disbAgg[$s->goal_loan_case_id]->pending_total ?? 0);
            $remaining = max(0, (float) $s->sanction_amount - $verified);
            return $this->statusBucket($verified, $pending, $remaining) === $tab;
        })->values();

        $perPage = (int) ($request->input('per_page') ?: 25);
        $page = max(1, (int) $request->input('page', 1));
        $slice = $filtered->forPage($page, $perPage)->values();
        $rows = new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $register = $slice->map(function (GoalSanction $s) use ($disbAgg) {
            $verified = (float) ($disbAgg[$s->goal_loan_case_id]->verified_total ?? 0);
            $pending = (float) ($disbAgg[$s->goal_loan_case_id]->pending_total ?? 0);
            $cap = (float) $s->sanction_amount;
            $remaining = max(0, $cap - $verified);
            $bucket = $this->statusBucket($verified, $pending, $remaining);

            return (object) [
                'sanction' => $s,
                'case' => $s->loanCase,
                'application' => $s->application,
                'verified' => $verified,
                'pending' => $pending,
                'remaining' => $remaining,
                'bucket' => $bucket,
                'status_label' => match ($bucket) {
                    'completed' => 'Completed',
                    'partial' => 'Partially Disbursed',
                    default => 'Disbursement Pending',
                },
            ];
        });

        $recentDisbursements = GoalDisbursement::query()
            ->with([
                'loanCase:id,case_no,customer_name,goal_org_unit_id',
                'application:id,lender_name,application_ref',
                'verifier:id,name',
                'creator:id,name',
            ])
            ->whereHas('loanCase', function ($q) use ($scope) {
                if ($scope !== []) {
                    $q->whereIn('goal_org_unit_id', $scope);
                }
            })
            ->latest('disbursed_on')
            ->latest('id')
            ->limit(12)
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

        // Cases eligible for sanction (have bank apps not rejected) / disbursement (have active sanction)
        $casesForSanction = GoalLoanCase::query()
            ->with(['bankApplications' => fn ($q) => $q->where('status', '!=', 'Rejected')->orderByDesc('id')])
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->whereHas('bankApplications', fn ($q) => $q->where('status', '!=', 'Rejected'))
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get(['id', 'case_no', 'customer_name', 'loan_amount', 'status']);

        $casesForDisbursement = GoalLoanCase::query()
            ->with([
                'sanctions' => fn ($q) => $q->where('status', 'Active')->latest('id'),
                'bankApplications' => fn ($q) => $q->whereIn('status', ['Sanctioned', 'Disbursed', 'Processing'])->orderByDesc('id'),
            ])
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->where(function ($q) {
                $q->whereHas('sanctions', fn ($sq) => $sq->where('status', 'Active'))
                    ->orWhereNotNull('sanction_amount');
            })
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get(['id', 'case_no', 'customer_name', 'loan_amount', 'sanction_amount', 'status']);

        $canEdit = auth()->user()->hasCrmPermission('edit_case_workspace_guard');
        $canVerify = auth()->user()->hasCrmPermission('record_disbursement_guard');

        $workflow = [
            ['key' => 'Processing', 'label' => 'Processing', 'desc' => 'Bank application under process'],
            ['key' => 'Sanctioned', 'label' => 'Sanctioned', 'desc' => 'Loan sanctioned by lender'],
            ['key' => 'Disbursement Recorded', 'label' => 'Disbursement Recorded', 'desc' => 'Disbursement entry created'],
            ['key' => 'Disbursement Verified', 'label' => 'Disbursement Verified', 'desc' => 'Verified & revenue eligible'],
            ['key' => 'PMS & Accounts', 'label' => 'PMS & Accounts Triggered', 'desc' => 'Queues opened in parallel'],
        ];
        $activeWorkflow = match ($tab) {
            'pending' => 'Disbursement Recorded',
            'partial' => 'Disbursement Verified',
            'completed' => 'PMS & Accounts',
            'sanctioned' => 'Sanctioned',
            default => ($tabCounts['pending'] > 0 ? 'Disbursement Recorded' : 'Sanctioned'),
        };

        $caseAppsJson = $casesForSanction->mapWithKeys(function (GoalLoanCase $c) {
            return [$c->id => $c->bankApplications->map(fn ($a) => [
                'id' => $a->id,
                'label' => trim(($a->lender_name ?: 'Lender').' — '.($a->application_ref ?: '#'.$a->id)),
                'requested' => (float) $a->requested_amount,
            ])->values()];
        });

        $caseDisbJson = $casesForDisbursement->mapWithKeys(function (GoalLoanCase $c) {
            $active = $c->sanctions->first();
            return [$c->id => [
                'sanction_id' => $active?->id,
                'sanction_amount' => (float) ($active?->sanction_amount ?? $c->sanction_amount ?? 0),
                'apps' => $c->bankApplications->map(fn ($a) => [
                    'id' => $a->id,
                    'label' => trim(($a->lender_name ?: 'Lender').' — '.($a->application_ref ?: '#'.$a->id)),
                ])->values(),
            ]];
        });

        return view('goal.sanction-disbursement.index', compact(
            'rows',
            'register',
            'recentDisbursements',
            'kpis',
            'tabs',
            'tab',
            'branches',
            'lenders',
            'owners',
            'casesForSanction',
            'casesForDisbursement',
            'caseAppsJson',
            'caseDisbJson',
            'canEdit',
            'canVerify',
            'workflow',
            'activeWorkflow'
        ));
    }

    protected function statusBucket(float $verified, float $pending, float $remaining): string
    {
        if ($remaining <= 0.009) {
            return 'completed';
        }
        if ($verified > 0) {
            return 'partial';
        }

        return 'pending';
    }
}
