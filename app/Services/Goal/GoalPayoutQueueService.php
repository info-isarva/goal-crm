<?php

namespace App\Services\Goal;

use App\Models\GoalAccountsItem;
use App\Models\GoalCaseEvent;
use App\Models\GoalPayout;
use App\Models\GoalPmsItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GoalPayoutQueueService
{
    public function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:120',
            'tab' => 'nullable|in:all,on_hold,eligible,processing,paid,exception',
            'customer' => 'nullable|string|max:120',
            'report_number' => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:100',
            'branch_id' => 'nullable|integer',
            'lender' => 'nullable|string|max:120',
            'payout_status' => 'nullable|in:On Hold,Eligible,Processing,Paid,Exception',
            'reconciliation_status' => 'nullable|in:Pending,Partial,Matched,Exception,Approved',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'selected' => 'nullable|integer',
            'format' => 'nullable|in:csv,xlsx,pdf',
            'partial' => 'nullable',
        ]);
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $customer = trim((string) ($filters['customer'] ?? ''));
        $reportNumber = trim((string) ($filters['report_number'] ?? ''));
        $paymentRef = trim((string) ($filters['payment_reference'] ?? ''));
        $branchId = $filters['branch_id'] ?? null;
        $lender = trim((string) ($filters['lender'] ?? ''));
        $payoutStatus = $filters['payout_status'] ?? null;
        $recon = $filters['reconciliation_status'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $tab = $filters['tab'] ?? 'all';

        $query
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->where(function (Builder $inner) use ($q) {
                    $inner->whereHas('loanCase', function (Builder $c) use ($q) {
                        $c->where('case_no', 'like', "%{$q}%")
                            ->orWhere('customer_name', 'like', "%{$q}%")
                            ->orWhere('lender_name', 'like', "%{$q}%");
                    })->orWhereHas('disbursement.accountsItem', function (Builder $a) use ($q) {
                        $a->where('report_number', 'like', "%{$q}%");
                    })->orWhereHas('receipts', function (Builder $r) use ($q) {
                        $r->where('reference', 'like', "%{$q}%");
                    });
                });
            })
            ->when($customer !== '', fn (Builder $query) => $query->whereHas(
                'loanCase',
                fn (Builder $c) => $c->where('customer_name', 'like', "%{$customer}%")
            ))
            ->when($reportNumber !== '', fn (Builder $query) => $query->whereHas(
                'disbursement.accountsItem',
                fn (Builder $a) => $a->where('report_number', 'like', "%{$reportNumber}%")
            ))
            ->when($paymentRef !== '', fn (Builder $query) => $query->whereHas(
                'receipts',
                fn (Builder $r) => $r->where('reference', 'like', "%{$paymentRef}%")
            ))
            ->when($branchId, fn (Builder $query) => $query->whereHas(
                'loanCase',
                fn (Builder $c) => $c->where('goal_org_unit_id', (int) $branchId)
            ))
            ->when($lender !== '', fn (Builder $query) => $query->whereHas(
                'loanCase',
                fn (Builder $c) => $c->where('lender_name', 'like', "%{$lender}%")
            ))
            ->when($dateFrom || $dateTo, function (Builder $query) use ($dateFrom, $dateTo) {
                $query->whereHas('disbursement', function (Builder $d) use ($dateFrom, $dateTo) {
                    if ($dateFrom) {
                        $d->whereDate('disbursed_on', '>=', $dateFrom);
                    }
                    if ($dateTo) {
                        $d->whereDate('disbursed_on', '<=', $dateTo);
                    }
                });
            })
            ->when($payoutStatus, fn (Builder $query) => $query->where('status', $payoutStatus))
            ->when($recon, fn (Builder $query) => $query->where('reconciliation_status', $recon));

        $this->applyTab($query, $tab);

        return $query->latest('goal_payouts.updated_at');
    }

    public function applyTab(Builder $query, ?string $tab): void
    {
        $tab = $tab ?: 'all';
        $map = [
            'on_hold' => 'On Hold',
            'eligible' => 'Eligible',
            'processing' => 'Processing',
            'paid' => 'Paid',
            'exception' => 'Exception',
        ];
        if (isset($map[$tab])) {
            $query->where('status', $map[$tab]);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function kpis(Builder $base): array
    {
        $statuses = [
            'on_hold' => 'On Hold',
            'eligible' => 'Eligible',
            'processing' => 'Processing',
            'paid' => 'Paid',
            'exception' => 'Exception',
        ];

        $totalCount = (clone $base)->count();
        $totalExpected = (float) (clone $base)->sum('expected_amount');
        $out = [
            'total' => $totalCount,
            'total_expected' => $totalExpected,
        ];

        foreach ($statuses as $key => $status) {
            $q = (clone $base)->where('status', $status);
            $count = (clone $q)->count();
            $amount = (float) (clone $q)->sum('expected_amount');
            $out[$key] = $count;
            $out[$key.'_amount'] = $amount;
            $out[$key.'_pct'] = $totalExpected > 0
                ? round(($amount / $totalExpected) * 100, 1)
                : ($totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0.0);
        }

        return $out;
    }

    /**
     * @return array<string,int>
     */
    public function tabCounts(Builder $base): array
    {
        $counts = ['all' => (clone $base)->count()];
        foreach (['on_hold', 'eligible', 'processing', 'paid', 'exception'] as $tab) {
            $q = clone $base;
            $this->applyTab($q, $tab);
            $counts[$tab] = $q->count();
        }

        return $counts;
    }

    /**
     * @return array{expected:float,received:float,adjustments:float,outstanding:float,status:?string,reconciliation:?string,difference_reason:?string}
     */
    public function reconciliationSummary(?GoalPayout $payout): array
    {
        if (! $payout) {
            return [
                'expected' => 0.0,
                'received' => 0.0,
                'adjustments' => 0.0,
                'outstanding' => 0.0,
                'status' => null,
                'reconciliation' => null,
                'difference_reason' => null,
            ];
        }

        $expected = (float) $payout->expected_amount;
        $received = (float) $payout->received_amount;
        $adjustments = (float) ($payout->relationLoaded('adjustments')
            ? $payout->adjustments->sum('amount')
            : $payout->adjustments()->sum('amount'));

        return [
            'expected' => $expected,
            'received' => $received,
            'adjustments' => $adjustments,
            'outstanding' => round(max(0, $expected - $received - $adjustments), 2),
            'status' => $payout->status,
            'reconciliation' => $payout->reconciliation_status,
            'difference_reason' => $payout->difference_reason,
        ];
    }

    public function accountsItem(GoalPayout $payout): ?GoalAccountsItem
    {
        $payout->loadMissing('disbursement.accountsItem');

        return $payout->disbursement?->accountsItem
            ?? GoalAccountsItem::query()->where('goal_disbursement_id', $payout->goal_disbursement_id)->first();
    }

    /**
     * @return list<array{key:string,label:string,done:bool,detail:?string}>
     */
    public function confirmations(GoalPayout $payout): array
    {
        $payout->loadMissing(['disbursement']);
        $accounts = $this->accountsItem($payout);
        $pms = GoalPmsItem::query()->where('goal_disbursement_id', $payout->goal_disbursement_id)->first();
        $pmsOk = $pms && ($pms->status === 'Verified' || $pms->verified_at);
        $reportOk = $accounts && $accounts->hasReportNumber();
        $accountsOk = $accounts && $accounts->isApproved();
        $disbOk = (bool) $payout->disbursement?->verified_at;

        return [
            [
                'key' => 'disbursement',
                'label' => 'Verified Disbursement (PD-01)',
                'done' => $disbOk,
                'detail' => $disbOk ? 'Verified' : 'Pending',
            ],
            [
                'key' => 'report',
                'label' => 'Report Number Captured',
                'done' => (bool) $reportOk,
                'detail' => $reportOk ? (string) $accounts->report_number : 'Pending',
            ],
            [
                'key' => 'pms',
                'label' => 'PMS Confirmation',
                'done' => (bool) $pmsOk,
                'detail' => $pmsOk
                    ? ('Verified'.($pms->verified_at ? ' on '.$pms->verified_at->format('d M Y') : ''))
                    : 'Pending',
            ],
            [
                'key' => 'accounts',
                'label' => 'Accounts Processing & Due Check',
                'done' => (bool) $accountsOk,
                'detail' => $accountsOk
                    ? ('Approved'.($accounts->approved_at ? ' on '.$accounts->approved_at->format('d M Y') : ''))
                    : 'Pending',
            ],
        ];
    }

    public function allConfirmationsDone(GoalPayout $payout): bool
    {
        foreach ($this->confirmations($payout) as $c) {
            if (empty($c['done'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Authorize hold release / eligibility unlock (Accounts Approver).
     *
     * @throws ValidationException
     */
    public function releaseHold(GoalPayout $payout, $actor): GoalPayout
    {
        if ($payout->status === 'Exception') {
            throw ValidationException::withMessages([
                'status' => 'Resolve the payout exception before releasing hold.',
            ]);
        }

        if ($payout->status === 'Paid') {
            throw ValidationException::withMessages([
                'status' => 'Payout is already Paid.',
            ]);
        }

        if (! $this->allConfirmationsDone($payout)) {
            throw ValidationException::withMessages([
                'status' => 'Required confirmations are incomplete (PD-01, report number, PMS, Accounts approval).',
            ]);
        }

        // Separation of duties: approver must not be the same user who created open adjustments.
        $ownAdjustment = $payout->adjustments()
            ->where('created_by', $actor->id)
            ->exists();
        if ($ownAdjustment) {
            throw ValidationException::withMessages([
                'status' => 'Separation of duties: you cannot release hold on a payout with your own adjustment.',
            ]);
        }

        if (in_array($payout->status, ['On Hold', 'Eligible'], true)) {
            $payout->status = 'Eligible';
            if (! filled($payout->reconciliation_status) || $payout->reconciliation_status === 'Exception') {
                $payout->reconciliation_status = 'Pending';
            }
            $payout->exception_reason = null;
            $payout->save();
        }

        if ($payout->loanCase) {
            GoalAuditService::recordFinancial(
                $payout->loanCase,
                GoalAuditService::TYPE_PAYOUT,
                'Payout hold released / approved',
                $payout->fresh(),
                $actor,
                ['to' => $payout->status, 'body' => 'Approve / Release Hold']
            );
        }

        return $payout->fresh();
    }

    public function ageingDays(GoalPayout $payout): ?int
    {
        if (in_array($payout->status, ['Paid'], true)) {
            return null;
        }
        $from = $payout->updated_at ?: $payout->created_at;
        if (! $from) {
            return null;
        }

        return (int) $from->diffInDays(now());
    }

    /**
     * @return list<array{title:string,meta:string}>
     */
    public function recentActivity(GoalPayout $payout, int $limit = 8): array
    {
        $caseId = (int) $payout->goal_loan_case_id;
        if ($caseId <= 0) {
            return [];
        }

        return GoalCaseEvent::query()
            ->with('user')
            ->where('goal_loan_case_id', $caseId)
            ->whereIn('event_type', [
                GoalAuditService::TYPE_ACCOUNTS,
                GoalAuditService::TYPE_PAYOUT,
                GoalAuditService::TYPE_DISBURSEMENT,
                GoalAuditService::TYPE_PMS,
            ])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (GoalCaseEvent $ev) {
                $actor = $ev->user?->name ?: 'System';
                $when = optional($ev->created_at)?->format('d M Y, h:i A') ?: '—';

                return [
                    'title' => (string) ($ev->title ?: $ev->event_type),
                    'meta' => 'by '.$actor.' | '.$when,
                ];
            })
            ->all();
    }
}
