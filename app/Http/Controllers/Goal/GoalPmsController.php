<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalOrgUnit;
use App\Models\GoalPmsItem;
use App\Services\Goal\GoalAuditService;
use App\Services\Goal\Pd01Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalPmsController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('pms_verification_guard');

        $filters = $this->validatedFilters($request);
        $base = $this->scopeByLoanCase(GoalPmsItem::query());
        $kpis = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->whereNotIn('status', ['Verified', 'Closed'])->count(),
            'verified' => (clone $base)->where('status', 'Verified')->count(),
            'follow_up' => (clone $base)->where('status', 'Follow-up Due')->count(),
        ];

        $items = $this->filteredQuery($filters)
            ->with(['loanCase', 'referralOrgUnit'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $branches = GoalOrgUnit::query()->ofType('branch')->active()->orderBy('name')->get(['id', 'name']);

        return view('goal.pms.index', compact('items', 'kpis', 'filters', 'branches'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('pms_verification_guard');

        $filters = $this->validatedFilters($request);
        $rows = $this->filteredQuery($filters)->with(['loanCase', 'referralOrgUnit'])->latest()->limit(5000)->get();
        $filename = 'pms-queue-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Case', 'Customer', 'Status', 'Activity', 'Follow-up', 'Referral Branch', 'Notes', 'Verified At']);
            foreach ($rows as $item) {
                fputcsv($out, [
                    $item->loanCase?->case_no,
                    $item->loanCase?->customer_name,
                    $item->status,
                    $item->activity_type,
                    optional($item->follow_up_at)->format('Y-m-d H:i'),
                    $item->referralOrgUnit?->name,
                    $item->notes,
                    optional($item->verified_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function verify(Request $request, GoalPmsItem $item, Pd01Service $pd01)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $this->assertItemInScope($item);

        $data = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'required|in:Verified,Follow-up Due,Closed',
            'follow_up_at' => 'nullable|date',
            'activity_type' => 'nullable|in:portfolio,follow_up,repeat_opportunity,branch_referral',
            'referral_org_unit_id' => 'nullable|exists:goal_org_units,id',
        ]);

        if ($data['status'] === 'Follow-up Due' && empty($data['follow_up_at'])) {
            return back()->withErrors(['follow_up_at' => 'Follow-up date is required when status is Follow-up Due.']);
        }
        if (($data['activity_type'] ?? null) === 'branch_referral' && empty($data['referral_org_unit_id'])) {
            return back()->withErrors(['referral_org_unit_id' => 'Select a referral branch for branch referral activity.']);
        }

        $updates = [
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $item->notes,
            'follow_up_at' => $data['follow_up_at'] ?? null,
            'activity_type' => $data['activity_type'] ?? $item->activity_type,
            'referral_org_unit_id' => $data['referral_org_unit_id'] ?? null,
        ];
        if ($data['status'] === 'Verified') {
            $updates['verified_by'] = auth()->id();
            $updates['verified_at'] = now();
        }
        $item->update($updates);
        $case = $item->loanCase;
        $pd01->refreshPayoutEligibility($case);
        if ($case) {
            GoalAuditService::recordFinancial(
                $case,
                GoalAuditService::TYPE_PMS,
                'PMS '.$data['status'],
                $item->fresh(),
                auth()->user(),
                ['to' => $data['status'], 'body' => $data['notes'] ?? null]
            );
        }

        return back()->with('success', 'PMS item updated.');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:pending,Verified,Follow-up Due,Closed',
        ]);
    }

    private function filteredQuery(array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? null;

        $query = $this->scopeByLoanCase(GoalPmsItem::query())
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->whereHas('loanCase', function (Builder $c) use ($q) {
                    $c->where('case_no', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%");
                });
            })
            ->when($status === 'pending', fn (Builder $query) => $query->whereNotIn('status', ['Verified', 'Closed']))
            ->when(in_array($status, ['Verified', 'Follow-up Due', 'Closed'], true), fn (Builder $query) => $query->where('status', $status));

        return $query;
    }

    private function assertItemInScope(GoalPmsItem $item): void
    {
        $item->loadMissing('loanCase');
        $scope = $this->scopedOrgIds();
        $orgId = (int) ($item->loanCase?->goal_org_unit_id ?? 0);
        if ($scope === [] || ! $orgId || ! in_array($orgId, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
