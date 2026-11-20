<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Library\MpdfDocument;
use App\Models\GoalOrgUnit;
use App\Models\GoalPmsFollowup;
use App\Models\GoalPmsItem;
use App\Models\User;
use App\Services\Goal\GoalPmsQueueService;
use App\Services\Goal\Pd01Service;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GoalPmsController extends Controller
{
    use AuthorizesGoal;

    public function __construct(protected GoalPmsQueueService $pms) {}

    public function index(Request $request)
    {
        $this->requireGoalPermission('pms_verification_guard');

        $filters = $this->pms->validatedFilters($request);
        $base = $this->scopeByLoanCase(GoalPmsItem::query());
        $kpis = $this->pms->kpis(clone $base);
        $tabCounts = $this->pms->tabCounts(clone $base);

        $items = $this->pms->applyFilters(clone $base, $filters)
            ->with([
                'loanCase.orgUnit',
                'disbursement',
                'assignee',
                'followups' => fn ($q) => $q->whereNotIn('status', ['Completed', 'Cancelled'])->orderBy('scheduled_date'),
            ])
            ->paginate(20)
            ->withQueryString();

        $selectedId = (int) ($filters['selected'] ?? ($items->first()?->id ?? 0));
        $selected = null;
        if ($selectedId > 0) {
            $selected = (clone $base)->with([
                'loanCase.orgUnit',
                'disbursement',
                'assignee',
                'followups' => fn ($q) => $q->with('assignee')->orderBy('scheduled_date')->orderBy('id'),
                'activityLogs' => fn ($q) => $q->with('user')->latest('created_at')->limit(8),
            ])->find($selectedId);
        }

        $workflow = $selected ? $this->pms->workflowEvents($selected) : [];
        $branches = $this->scopedBranches();
        $pmsUsers = $this->pmsUsers();
        $missingOrgScope = $this->scopedOrgIds() === [];

        $payload = compact(
            'items', 'kpis', 'tabCounts', 'filters', 'branches', 'pmsUsers', 'selected', 'workflow', 'missingOrgScope'
        );

        if ($request->boolean('partial') || $request->ajax()) {
            return response()
                ->view('goal.pms.partials.queue_results', $payload)
                ->header('Cache-Control', 'no-store');
        }

        return view('goal.pms.index', $payload);
    }

    public function show(GoalPmsItem $item)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $this->assertItemInScope($item);

        $item->load([
            'loanCase.orgUnit',
            'loanCase.owner',
            'disbursement.verifier',
            'assignee',
            'verifier',
            'verifications.verifier',
            'followups.assignee',
            'followups.completer',
            'assignmentHistory.newUser',
            'assignmentHistory.previousUser',
            'assignmentHistory.assigner',
            'activityLogs.user',
        ]);

        $workflow = $this->pms->workflowEvents($item);
        $pmsUsers = $this->pmsUsersForItem($item);
        $branches = $this->scopedBranches();
        $missingOrgScope = false;

        return view('goal.pms.show', compact('item', 'workflow', 'pmsUsers', 'branches', 'missingOrgScope'));
    }

    public function export(Request $request): StreamedResponse|\Illuminate\Http\Response
    {
        $this->requireGoalPermission('pms_verification_guard');

        $filters = $this->pms->validatedFilters($request);
        $format = $filters['format'] ?? 'csv';
        $rows = $this->pms->applyFilters($this->scopeByLoanCase(GoalPmsItem::query()), $filters)
            ->with(['loanCase.orgUnit', 'disbursement', 'assignee'])
            ->limit(5000)
            ->get();

        if ($format === 'pdf') {
            $html = view('goal.pms.export-pdf', [
                'rows' => $rows,
                'generatedAt' => now(),
            ])->render();

            return response(MpdfDocument::binary($html), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="pms-queue-'.now()->format('Ymd-His').'.pdf"',
            ]);
        }

        $filename = 'pms-queue-'.now()->format('Ymd-His').'.'.$format;
        $delimiter = $format === 'xlsx' ? ',' : ',';

        return response()->streamDownload(function () use ($rows, $delimiter) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Case Reference', 'Customer', 'Product', 'Branch', 'Disbursement Date',
                'Disbursed Amount', 'PMS Status', 'Assigned PMS User', 'Next Follow-up',
            ], $delimiter);
            foreach ($rows as $item) {
                fputcsv($out, [
                    $item->loanCase?->case_no,
                    $item->loanCase?->customer_name,
                    $item->loanCase?->product,
                    $item->loanCase?->orgUnit?->name,
                    optional($item->disbursement?->disbursed_on)->format('Y-m-d'),
                    $item->disbursement?->amount,
                    $item->statusLabel(),
                    $item->assignee?->name,
                    optional($item->follow_up_at)->format('Y-m-d H:i'),
                ], $delimiter);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function start(GoalPmsItem $item)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $this->assertItemInScope($item);

        try {
            $this->pms->startVerification($item, auth()->user());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Verification started.');
    }

    public function verify(Request $request, GoalPmsItem $item, Pd01Service $pd01)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $this->assertItemInScope($item);

        $data = $request->validate([
            'verification_result' => 'required|in:Verified,Rejected,On Hold',
            'verification_remarks' => 'nullable|required_if:verification_result,Rejected,On Hold|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'follow_up_at' => 'nullable|date',
            'activity_type' => 'nullable|in:portfolio,follow_up,repeat_opportunity,branch_referral',
            'referral_org_unit_id' => [
                'nullable',
                'required_if:activity_type,branch_referral',
                Rule::in($this->scopedBranchIds()),
            ],
        ], [
            'verification_remarks.required_if' => 'Remarks are required when result is Rejected or On Hold.',
            'referral_org_unit_id.required_if' => 'Select a referral branch for branch referral activity.',
            'referral_org_unit_id.in' => 'Selected referral branch is outside your organizational scope.',
        ]);

        try {
            $this->pms->completeVerification($item, auth()->user(), $data, $pd01);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'PMS verification saved.');
    }

    public function reassign(Request $request, GoalPmsItem $item)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $this->assertItemInScope($item);

        $allowedIds = $this->pmsUserIdsForItem($item);
        $data = $request->validate([
            'assigned_to' => ['required', 'integer', Rule::in($allowedIds)],
            'reassignment_reason' => 'nullable|string|max:500',
        ], [
            'assigned_to.in' => 'Assignee must be a PMS user within your organizational scope.',
        ]);

        $this->pms->reassign($item, auth()->user(), (int) $data['assigned_to'], $data['reassignment_reason'] ?? null);

        return back()->with('success', 'Case reassigned.');
    }

    public function storeFollowup(Request $request, GoalPmsItem $item)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $this->assertItemInScope($item);

        $allowedIds = $this->pmsUserIdsForItem($item);
        $data = $request->validate([
            'followup_type' => 'required|in:'.implode(',', array_keys(GoalPmsFollowup::TYPES)),
            'subject' => 'required|string|max:180',
            'description' => 'nullable|string|max:2000',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'nullable|date_format:H:i',
            'assigned_to' => ['nullable', 'integer', Rule::in($allowedIds)],
            'priority' => 'nullable|in:Low,Medium,High',
        ], [
            'subject.required' => 'Follow-up subject is required.',
            'scheduled_date.required' => 'Follow-up date is required.',
            'scheduled_date.after_or_equal' => 'Follow-up date cannot be in the past.',
            'assigned_to.in' => 'Follow-up owner must be a PMS user within your organizational scope.',
        ]);

        $this->pms->createFollowup($item, auth()->user(), $data);

        return back()->with('success', 'Follow-up scheduled.');
    }

    public function completeFollowup(Request $request, GoalPmsFollowup $followup)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $followup->loadMissing('pmsItem.loanCase');
        $this->assertItemInScope($followup->pmsItem);

        $data = $request->validate([
            'completion_remarks' => 'nullable|string|max:2000',
        ]);

        $this->pms->completeFollowup($followup, auth()->user(), $data['completion_remarks'] ?? null);

        return back()->with('success', 'Follow-up completed.');
    }

    public function cancelFollowup(Request $request, GoalPmsFollowup $followup)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $followup->loadMissing('pmsItem.loanCase');
        $this->assertItemInScope($followup->pmsItem);

        $data = $request->validate([
            'completion_remarks' => 'nullable|string|max:2000',
        ]);

        $this->pms->cancelFollowup($followup, auth()->user(), $data['completion_remarks'] ?? null);

        return back()->with('success', 'Follow-up cancelled.');
    }

    public function rescheduleFollowup(Request $request, GoalPmsFollowup $followup)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $followup->loadMissing('pmsItem.loanCase');
        $this->assertItemInScope($followup->pmsItem);

        $data = $request->validate([
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'nullable|date_format:H:i',
        ], [
            'scheduled_date.after_or_equal' => 'Reschedule date cannot be in the past.',
        ]);

        $this->pms->rescheduleFollowup(
            $followup,
            auth()->user(),
            $data['scheduled_date'],
            $data['scheduled_time'] ?? null
        );

        return back()->with('success', 'Follow-up rescheduled.');
    }

    /**
     * Active PMS-role users visible within the actor's org scope.
     * Super Admin / MD / CEO see all PMS users; others only overlapping org units.
     */
    protected function pmsUsers(): Collection
    {
        $query = User::query()
            ->where('crm_role_type', 13)
            ->where('status', 1)
            ->orderBy('name')
            ->limit(200);

        if (! $this->isGlobalOrgViewer()) {
            $scope = array_map('intval', $this->scopedOrgIds());
            if ($scope === []) {
                return collect();
            }
            $query->whereIn('goal_org_unit_id', $scope);
        }

        return $query->get(['id', 'name', 'goal_org_unit_id']);
    }

    /**
     * Assignees for a specific PMS item: scoped PMS users + current assignee (if any).
     */
    protected function pmsUsersForItem(GoalPmsItem $item): Collection
    {
        $users = $this->pmsUsers();
        $currentId = (int) ($item->assigned_to ?? 0);
        if ($currentId > 0 && ! $users->contains(fn ($u) => (int) $u->id === $currentId)) {
            $current = User::query()
                ->where('id', $currentId)
                ->where('crm_role_type', 13)
                ->first(['id', 'name', 'goal_org_unit_id']);
            if ($current) {
                $users = $users->prepend($current)->unique('id')->values();
            }
        }

        return $users;
    }

    protected function pmsUserIds(): array
    {
        return $this->pmsUsers()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    protected function pmsUserIdsForItem(GoalPmsItem $item): array
    {
        return $this->pmsUsersForItem($item)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    protected function scopedBranches(): Collection
    {
        $query = GoalOrgUnit::query()->ofType('branch')->active()->orderBy('name');

        if (! $this->isGlobalOrgViewer()) {
            $scope = array_map('intval', $this->scopedOrgIds());
            if ($scope === []) {
                return collect();
            }
            $query->whereIn('id', $scope);
        }

        return $query->get(['id', 'name']);
    }

    protected function scopedBranchIds(): array
    {
        return $this->scopedBranches()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    protected function isGlobalOrgViewer(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin() || in_array((int) ($user->crm_role_type ?? -1), [1, 4, 5], true);
    }

    private function assertItemInScope(?GoalPmsItem $item): void
    {
        if (! $item) {
            abort(404);
        }
        $item->loadMissing('loanCase');
        $scope = array_map('intval', $this->scopedOrgIds());
        $orgId = (int) ($item->loanCase?->goal_org_unit_id ?? 0);
        if ($scope === [] || ! $orgId || ! in_array($orgId, $scope, true)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
