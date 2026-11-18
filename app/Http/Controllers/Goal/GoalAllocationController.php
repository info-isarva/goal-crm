<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalLoanLead;
use App\Models\GoalLoanLeadAssignment;
use App\Models\GoalLoanLeadEvent;
use App\Models\GoalOrgUnit;
use App\Models\User;
use App\Notifications\GoalCaseAlert;
use App\Services\Goal\GoalDashboardService;
use App\Services\Goal\GoalWorkflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalAllocationController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('bm_allocation_guard');
        $scope = $this->scopedOrgIds();

        $pending = GoalLoanLead::query()
            ->with('orgUnit')
            ->where('status', 'Pending BM Allocation')
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->latest()
            ->get();

        $assigned = GoalLoanLead::query()
            ->with(['assignee', 'orgUnit', 'assignments' => function ($q) {
                $q->with(['fromUser', 'toUser', 'assigner'])->latest();
            }])
            ->whereNotNull('assigned_to')
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->latest('assigned_at')
            ->limit(100)
            ->get();

        $branches = GoalOrgUnit::query()
            ->ofType(GoalOrgUnit::TYPE_BRANCH)
            ->active()
            ->when($scope !== [], fn ($q) => $q->whereIn('id', $scope))
            ->orderBy('name')
            ->get();

        $sales = $this->salesUsers($scope);
        $kpis = $this->buildKpis($scope);
        $tab = in_array($request->input('tab'), ['pending', 'assigned'], true)
            ? $request->input('tab')
            : (session('tab', 'pending'));

        return view('goal.allocation.index', compact(
            'pending',
            'assigned',
            'sales',
            'branches',
            'tab',
            'kpis'
        ));
    }

    public function assign(Request $request, GoalLoanLead $lead)
    {
        $this->requireGoalPermission('bm_allocation_guard');
        $this->assertLeadInScope($lead);

        $data = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500',
            'action' => 'nullable|in:assign,reassign',
        ]);

        $action = $data['action'] ?? ($lead->assigned_to ? 'reassign' : 'assign');
        if ($action === 'reassign' && empty(trim((string) ($data['reason'] ?? '')))) {
            throw ValidationException::withMessages([
                'reason' => 'Reassignment reason is mandatory.',
            ]);
        }

        $this->assertSalesAssignee((int) $data['assigned_to'], $lead);
        $this->applyAssignment($lead, (int) $data['assigned_to'], $action, $data['reason'] ?? null);

        return back()
            ->with('success', $action === 'reassign' ? 'Lead reassigned.' : 'Lead assigned.')
            ->with('tab', $action === 'reassign' ? 'assigned' : 'pending');
    }

    public function assignBulk(Request $request)
    {
        $this->requireGoalPermission('bm_allocation_guard');

        // Backward compatible: shared assignee + lead_ids
        // Or per-row: assignments[{lead_id, assigned_to}]
        if ($request->filled('assignments')) {
            $data = $request->validate([
                'assignments' => 'required|array|min:1',
                'assignments.*.lead_id' => 'required|integer|exists:goal_loan_leads,id',
                'assignments.*.assigned_to' => 'required|exists:users,id',
            ]);

            $scope = $this->scopedOrgIds();
            $count = 0;
            foreach ($data['assignments'] as $row) {
                $lead = GoalLoanLead::query()
                    ->whereKey($row['lead_id'])
                    ->where('status', 'Pending BM Allocation')
                    ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
                    ->first();
                if (!$lead) {
                    continue;
                }
                $this->assertSalesAssignee((int) $row['assigned_to'], $lead);
                $this->applyAssignment($lead, (int) $row['assigned_to'], 'assign', null);
                $count++;
            }

            if ($count === 0) {
                throw ValidationException::withMessages([
                    'assignments' => 'No pending leads selected in your branch scope.',
                ]);
            }

            return back()
                ->with('success', $count === 1 ? '1 lead assigned.' : "{$count} leads assigned.")
                ->with('tab', 'pending');
        }

        $data = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer|exists:goal_loan_leads,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $scope = $this->scopedOrgIds();
        $leads = GoalLoanLead::query()
            ->whereIn('id', $data['lead_ids'])
            ->where('status', 'Pending BM Allocation')
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->get();

        if ($leads->isEmpty()) {
            throw ValidationException::withMessages([
                'lead_ids' => 'No pending leads selected in your branch scope.',
            ]);
        }

        $count = 0;
        foreach ($leads as $lead) {
            $this->assertSalesAssignee((int) $data['assigned_to'], $lead);
            $this->applyAssignment($lead, (int) $data['assigned_to'], 'assign', null);
            $count++;
        }

        return back()
            ->with('success', $count === 1 ? '1 lead assigned.' : "{$count} leads assigned.")
            ->with('tab', 'pending');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('bm_allocation_guard');
        $scope = $this->scopedOrgIds();
        $tab = in_array($request->input('tab'), ['pending', 'assigned'], true)
            ? $request->input('tab')
            : 'pending';

        $filename = 'bm-allocation-'.$tab.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($scope, $tab) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            if ($tab === 'assigned') {
                fputcsv($out, ['Ref', 'Customer', 'Branch', 'Assignee', 'Assigned At', 'Status', 'Lead Type']);
                $rows = GoalLoanLead::query()
                    ->with(['assignee', 'orgUnit'])
                    ->whereNotNull('assigned_to')
                    ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
                    ->latest('assigned_at')
                    ->limit(5000)
                    ->get();
                foreach ($rows as $lead) {
                    fputcsv($out, [
                        $lead->ref_no,
                        $lead->customer_name,
                        $lead->orgUnit?->name,
                        $lead->assignee?->name,
                        optional($lead->assigned_at)->format('Y-m-d H:i'),
                        $lead->status,
                        $lead->lead_type,
                    ]);
                }
            } else {
                fputcsv($out, ['Ref', 'Customer', 'Mobile', 'Branch', 'Lead Type', 'Status', 'Received On']);
                $rows = GoalLoanLead::query()
                    ->with('orgUnit')
                    ->where('status', 'Pending BM Allocation')
                    ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
                    ->latest()
                    ->limit(5000)
                    ->get();
                foreach ($rows as $lead) {
                    fputcsv($out, [
                        $lead->ref_no,
                        $lead->customer_name,
                        $lead->mobile,
                        $lead->orgUnit?->name,
                        $lead->lead_type,
                        $lead->status,
                        optional($lead->created_at)->format('Y-m-d H:i'),
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function history(GoalLoanLead $lead)
    {
        $this->requireGoalPermission('bm_allocation_guard');
        $this->assertLeadInScope($lead);

        $lead->load([
            'assignee',
            'orgUnit',
            'assignments' => fn ($q) => $q->with(['fromUser', 'toUser', 'assigner'])->latest(),
        ]);

        $rows = $lead->assignments->map(function (GoalLoanLeadAssignment $row) {
            return [
                'action' => $row->action,
                'from' => $this->salesLabel($row->fromUser),
                'to' => $this->salesLabel($row->toUser),
                'by' => $row->assigner?->name ?? '—',
                'reason' => $row->reason ?: '—',
                'at' => optional($row->created_at)->format('d M Y, h:i a'),
            ];
        })->values();

        return response()->json([
            'ref_no' => $lead->ref_no,
            'customer' => $lead->customer_name,
            'branch' => $lead->orgUnit?->name,
            'status' => $lead->status,
            'current' => $this->salesLabel($lead->assignee),
            'show_url' => route('goal.leads.show', $lead),
            'history' => $rows,
        ]);
    }

    protected function buildKpis(array $scope): array
    {
        $pending = GoalLoanLead::query()
            ->where('status', 'Pending BM Allocation')
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->count();

        $assignmentBase = GoalLoanLeadAssignment::query()
            ->when($scope !== [], function ($q) use ($scope) {
                $q->whereHas('lead', fn ($lq) => $lq->whereIn('goal_org_unit_id', $scope));
            });

        $todayStart = Carbon::today();
        $yesterdayStart = Carbon::yesterday();
        $monthStart = Carbon::now()->startOfMonth();
        $prevMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd = Carbon::now()->subMonthNoOverflow()->endOfMonth();

        $assignedToday = (clone $assignmentBase)->where('action', 'assign')->where('created_at', '>=', $todayStart)->count();
        $assignedYesterday = (clone $assignmentBase)->where('action', 'assign')
            ->whereBetween('created_at', [$yesterdayStart, $todayStart])->count();

        $reassignedToday = (clone $assignmentBase)->where('action', 'reassign')->where('created_at', '>=', $todayStart)->count();
        $reassignedYesterday = (clone $assignmentBase)->where('action', 'reassign')
            ->whereBetween('created_at', [$yesterdayStart, $todayStart])->count();

        $monthTotal = (clone $assignmentBase)->where('created_at', '>=', $monthStart)->count();
        $prevMonthTotal = (clone $assignmentBase)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        return [
            'pending' => $pending,
            'assigned_today' => $assignedToday,
            'assigned_today_trend' => $this->trendPct($assignedToday, $assignedYesterday),
            'reassigned_today' => $reassignedToday,
            'reassigned_today_trend' => $this->trendPct($reassignedToday, $reassignedYesterday),
            'month_total' => $monthTotal,
            'month_total_trend' => $this->trendPct($monthTotal, $prevMonthTotal),
        ];
    }

    protected function trendPct(int $current, int $previous): ?string
    {
        if ($previous <= 0) {
            return $current > 0 ? '+100%' : null;
        }
        $pct = (int) round((($current - $previous) / $previous) * 100);
        $sign = $pct > 0 ? '+' : '';

        return $sign . $pct . '%';
    }

    protected function applyAssignment(GoalLoanLead $lead, int $toUserId, string $action, ?string $reason): void
    {
        if ($action === 'assign') {
            $gate = GoalWorkflow::validateLead(
                'Pending BM Allocation',
                'Assigned',
                ['assignee' => $toUserId]
            );
            if ($lead->status === 'Pending BM Allocation' && !$gate['ok']) {
                throw ValidationException::withMessages([
                    'assigned_to' => implode(' ', $gate['errors']),
                ]);
            }
            if ($lead->status !== 'Pending BM Allocation' && $lead->status !== 'Assigned') {
                throw ValidationException::withMessages([
                    'assigned_to' => 'Lead is not available for BM allocation.',
                ]);
            }
        } elseif (!in_array($lead->status, ['Pending BM Allocation', 'Assigned'], true) && !$lead->assigned_to) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Lead cannot be reassigned in its current status.',
            ]);
        }

        $fromUser = $lead->assigned_to;
        $fromStatus = $lead->status;

        GoalLoanLeadAssignment::create([
            'goal_loan_lead_id' => $lead->id,
            'from_user_id' => $fromUser,
            'to_user_id' => $toUserId,
            'assigned_by' => auth()->id(),
            'action' => $action,
            'reason' => $reason,
        ]);

        $lead->assigned_to = $toUserId;
        $lead->assigned_by = auth()->id();
        $lead->assigned_at = now();
        if ($lead->status === 'Pending BM Allocation') {
            $lead->status = 'Assigned';
        }
        $lead->save();

        $toLabel = $this->salesLabel(User::query()->find($toUserId));
        GoalLoanLeadEvent::record(
            $lead,
            $action === 'reassign' ? 'reassign' : 'allocation',
            ($action === 'reassign' ? 'Reassigned to ' : 'Allocated to ') . $toLabel,
            trim(($reason ? $reason . ' · ' : '') . ($fromStatus !== $lead->status ? ($fromStatus . ' → ' . $lead->status) : '')),
            [
                'from_status' => $fromStatus,
                'to_status' => $lead->status,
                'meta' => [
                    'from_user_id' => $fromUser,
                    'to_user_id' => $toUserId,
                    'action' => $action,
                    'reason' => $reason,
                ],
            ]
        );

        if ($fromStatus !== $lead->status) {
            GoalLoanLeadEvent::record(
                $lead,
                'status_change',
                'Status Changed',
                $fromStatus . ' → ' . $lead->status,
                ['from_status' => $fromStatus, 'to_status' => $lead->status]
            );
        }

        $assignee = User::query()->find($toUserId);
        $this->notifyAssignee(
            $assignee,
            $action === 'reassign' ? 'Lead reassigned to you' : 'New lead assigned',
            ($lead->ref_no ?: 'Lead').' · '.($lead->customer_name ?: 'Customer'),
            route('goal.leads.show', $lead),
            'lead-assign-'.$lead->id.'-'.$toUserId
        );
    }

    protected function salesUsers(array $scope): Collection
    {
        return User::query()
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereIn('crm_role_type', [10, 11, 12])
                    ->orWhereIn('job_role', ['jsm', 'sm', 'ssm']);
            })
            ->when($scope !== [], function ($q) use ($scope) {
                $q->where(function ($inner) use ($scope) {
                    $inner->whereIn('goal_org_unit_id', $scope)
                        ->orWhereNull('goal_org_unit_id');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'job_role', 'crm_role_type', 'goal_org_unit_id']);
    }

    protected function assertSalesAssignee(int $userId, GoalLoanLead $lead): void
    {
        $user = User::query()->find($userId);
        if (!$user) {
            throw ValidationException::withMessages(['assigned_to' => 'Selected sales user not found.']);
        }

        $roleKey = GoalDashboardService::ROLE_KEYS[(int) ($user->crm_role_type ?? -1)] ?? null;
        $job = strtolower((string) ($user->job_role ?? ''));
        $ok = in_array($roleKey, ['jsm', 'sm', 'ssm'], true) || in_array($job, ['jsm', 'sm', 'ssm'], true);
        if (!$ok) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Assignee must be JSM, SM, or SSM.',
            ]);
        }

        if ((int) ($user->status ?? 1) !== 1) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Assignee must be an active user.',
            ]);
        }

        $leadOrg = (int) ($lead->goal_org_unit_id ?? 0);
        $userOrg = (int) ($user->goal_org_unit_id ?? 0);
        if (! $leadOrg || ! $userOrg || $leadOrg !== $userOrg) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Assignee must belong to the same branch as the lead.',
            ]);
        }
    }

    protected function notifyAssignee(?User $user, string $title, string $message, string $url, string $eventKey): void
    {
        if (! $user) {
            return;
        }
        $exists = $user->notifications()
            ->whereNull('read_at')
            ->where('type', GoalCaseAlert::class)
            ->where('data->event', $eventKey)
            ->where('data->url', $url)
            ->where('created_at', '>=', now()->subHours(6))
            ->exists();
        if ($exists) {
            return;
        }
        $user->notify(new GoalCaseAlert($title, $message, $url, $eventKey));
    }

    protected function assertLeadInScope(GoalLoanLead $lead): void
    {
        $scope = $this->scopedOrgIds();
        if ($scope !== [] && !in_array((int) $lead->goal_org_unit_id, array_map('intval', $scope), true)) {
            abort(403, 'Lead is outside your branch scope.');
        }
    }

    protected function salesLabel(?User $user): string
    {
        if (!$user) {
            return '—';
        }

        $roleKey = GoalDashboardService::ROLE_KEYS[(int) ($user->crm_role_type ?? -1)] ?? null;
        $job = strtolower((string) ($user->job_role ?? ''));
        $code = strtoupper($roleKey ?: ($job ?: 'SALES'));

        return "{$code} - {$user->name}";
    }
}
