<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalAccountsItem;
use App\Services\Goal\GoalAuditService;
use App\Services\Goal\Pd01Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalAccountsController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('accounts_queue_guard');

        $filters = $this->validatedFilters($request);
        $base = $this->scopeByLoanCase(GoalAccountsItem::query());
        $kpis = [
            'total' => (clone $base)->count(),
            'awaiting' => (clone $base)->where(function ($q) {
                $q->whereNull('report_number')->orWhere('report_number', '');
            })->count(),
            'reported' => (clone $base)->where('status', 'Report Entered')->count(),
            'approved' => (clone $base)->where('status', 'Approved')->count(),
        ];

        $items = $this->filteredQuery($filters)
            ->with(['loanCase'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('goal.accounts.index', compact('items', 'kpis', 'filters'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('accounts_queue_guard');

        $filters = $this->validatedFilters($request);
        $rows = $this->filteredQuery($filters)->with(['loanCase'])->latest()->limit(5000)->get();
        $filename = 'accounts-queue-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Case', 'Customer', 'Status', 'Report Number', 'Approved At']);
            foreach ($rows as $item) {
                fputcsv($out, [
                    $item->loanCase?->case_no,
                    $item->loanCase?->customer_name,
                    $item->status,
                    $item->report_number,
                    optional($item->approved_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function updateReport(Request $request, GoalAccountsItem $item, Pd01Service $pd01)
    {
        $this->requireGoalPermission('accounts_queue_guard');
        $this->assertItemInScope($item);

        $data = $request->validate([
            'report_number' => 'required|string|max:100',
        ]);
        $item->update([
            'report_number' => $data['report_number'],
            'status' => 'Report Entered',
            'updated_by' => auth()->id(),
            // Clear prior approval when report changes
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $pd01->refreshPayoutEligibility($item->loanCase);

        return back()->with('success', 'Report number saved. Approve to unlock payout eligibility.');
    }

    public function approve(GoalAccountsItem $item, Pd01Service $pd01)
    {
        $this->requireGoalPermission('accounts_queue_guard');
        $this->assertItemInScope($item);

        if (! filled($item->report_number)) {
            return back()->withErrors(['report_number' => 'Enter a report number before approval.']);
        }

        $item->update([
            'status' => 'Approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'updated_by' => auth()->id(),
        ]);
        $case = $item->loanCase;
        $pd01->refreshPayoutEligibility($case);
        if ($case) {
            GoalAuditService::recordFinancial(
                $case,
                GoalAuditService::TYPE_ACCOUNTS,
                'Accounts approved',
                $item->fresh(),
                auth()->user(),
                ['to' => 'Approved', 'body' => $item->report_number]
            );
        }

        return back()->with('success', 'Accounts item approved. Payout eligibility refreshed.');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:awaiting,reported,approved,on_hold',
        ]);
    }

    private function filteredQuery(array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? null;

        return $this->scopeByLoanCase(GoalAccountsItem::query())
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->whereHas('loanCase', function (Builder $c) use ($q) {
                    $c->where('case_no', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%");
                });
            })
            ->when($status === 'awaiting', function (Builder $query) {
                $query->where(function ($q) {
                    $q->whereNull('report_number')->orWhere('report_number', '');
                });
            })
            ->when($status === 'reported', fn (Builder $query) => $query->where('status', 'Report Entered'))
            ->when($status === 'approved', fn (Builder $query) => $query->where('status', 'Approved'))
            ->when($status === 'on_hold', fn (Builder $query) => $query->whereIn('status', ['On Hold', 'Pending', 'Awaiting Report']));
    }

    private function assertItemInScope(GoalAccountsItem $item): void
    {
        $item->loadMissing('loanCase');
        $scope = $this->scopedOrgIds();
        $orgId = (int) ($item->loanCase?->goal_org_unit_id ?? 0);
        if ($scope === [] || ! $orgId || ! in_array($orgId, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
