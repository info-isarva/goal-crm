<?php

namespace App\Services\Goal;

use App\Models\GoalAccountsItem;
use App\Models\GoalCaseEvent;
use App\Models\GoalPayout;
use App\Models\GoalPmsItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class GoalAccountsQueueService
{
    public function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:120',
            'tab' => 'nullable|in:all,on_hold,report_pending,eligible,processing,paid,exception',
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
        $tab = $filters['tab'] ?? 'all';
        $branchId = $filters['branch_id'] ?? null;
        $lender = trim((string) ($filters['lender'] ?? ''));
        $payoutStatus = $filters['payout_status'] ?? null;
        $recon = $filters['reconciliation_status'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $query
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->where(function (Builder $inner) use ($q) {
                    $inner->where('report_number', 'like', "%{$q}%")
                        ->orWhereHas('loanCase', function (Builder $c) use ($q) {
                            $c->where('case_no', 'like', "%{$q}%")
                                ->orWhere('customer_name', 'like', "%{$q}%")
                                ->orWhere('lender_name', 'like', "%{$q}%");
                        });
                });
            })
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
            ->when($payoutStatus, function (Builder $query) use ($payoutStatus) {
                $query->whereHas('payout', fn (Builder $p) => $p->where('status', $payoutStatus));
            })
            ->when($recon, function (Builder $query) use ($recon) {
                $query->whereHas('payout', fn (Builder $p) => $p->where('reconciliation_status', $recon));
            });

        $this->applyTab($query, $tab);

        return $query->latest('goal_accounts_items.id');
    }

    public function applyTab(Builder $query, ?string $tab): void
    {
        $tab = $tab ?: 'all';

        if ($tab === 'report_pending') {
            $query->where(function (Builder $q) {
                $q->whereNull('report_number')->orWhere('report_number', '');
            });

            return;
        }

        if ($tab === 'on_hold') {
            $query->whereHas('payout', fn (Builder $p) => $p->where('status', 'On Hold'));

            return;
        }

        if (in_array($tab, ['eligible', 'processing', 'paid', 'exception'], true)) {
            $map = [
                'eligible' => 'Eligible',
                'processing' => 'Processing',
                'paid' => 'Paid',
                'exception' => 'Exception',
            ];
            $query->whereHas('payout', fn (Builder $p) => $p->where('status', $map[$tab]));
        }
    }

    /**
     * @return array{total:int,on_hold:int,report_pending:int,eligible:int,processing:int}
     */
    public function kpis(Builder $base): array
    {
        $total = (clone $base)->count();
        $onHold = (clone $base)->whereHas('payout', fn (Builder $p) => $p->where('status', 'On Hold'))->count();
        $reportPending = (clone $base)->where(function (Builder $q) {
            $q->whereNull('report_number')->orWhere('report_number', '');
        })->count();
        $eligible = (clone $base)->whereHas('payout', fn (Builder $p) => $p->where('status', 'Eligible'))->count();
        $processing = (clone $base)->where(function (Builder $q) {
            $q->whereHas('payout', function (Builder $p) {
                $p->where('status', 'Processing')
                    ->orWhere(function (Builder $inner) {
                        $inner->whereRaw('expected_amount > received_amount + 0.01')
                            ->where('received_amount', '>', 0);
                    });
            });
        })->count();

        return compact('total', 'onHold', 'reportPending', 'eligible', 'processing') + [
            'on_hold' => $onHold,
            'report_pending' => $reportPending,
        ];
    }

    /**
     * @return array<string,int>
     */
    public function tabCounts(Builder $base): array
    {
        $counts = ['all' => (clone $base)->count()];
        foreach (['on_hold', 'report_pending', 'eligible', 'processing', 'paid', 'exception'] as $tab) {
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

    /**
     * @return list<array{key:string,label:string,done:bool,detail:?string}>
     */
    public function confirmations(GoalAccountsItem $item): array
    {
        $item->loadMissing(['payout', 'disbursement.pmsItem']);
        $pms = $item->disbursement?->pmsItem
            ?? GoalPmsItem::query()->where('goal_disbursement_id', $item->goal_disbursement_id)->first();
        $pmsOk = $pms && ($pms->status === 'Verified' || $pms->verified_at);
        $reportOk = $item->hasReportNumber();
        $accountsOk = $item->isApproved();

        return [
            [
                'key' => 'report',
                'label' => 'Report Number Captured',
                'done' => $reportOk,
                'detail' => $reportOk ? (string) $item->report_number : 'Pending',
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
                'done' => $accountsOk,
                'detail' => $accountsOk
                    ? ('Approved'.($item->approved_at ? ' on '.$item->approved_at->format('d M Y') : ''))
                    : 'Pending',
            ],
        ];
    }

    /**
     * @return list<array{label:string,state:string,detail:?string,at:?string,badge:?string}>
     */
    public function workflowEvents(GoalAccountsItem $item): array
    {
        $item->loadMissing(['disbursement', 'payout', 'approver']);
        $disb = $item->disbursement;
        $payout = $item->payout;
        $reportOk = $item->hasReportNumber();
        $confirmed = $item->isApproved();
        $pmsOk = GoalPmsItem::query()
            ->where('goal_disbursement_id', $item->goal_disbursement_id)
            ->where(function ($q) {
                $q->where('status', 'Verified')->orWhereNotNull('verified_at');
            })
            ->exists();

        return [
            [
                'label' => 'Verified Disbursement (PD-01)',
                'state' => $disb?->verified_at ? 'done' : 'pending',
                'detail' => $disb?->verified_at ? 'PD-01 completed' : null,
                'at' => optional($disb?->verified_at)?->toDateTimeString(),
                'badge' => $disb?->verified_at ? 'Verified' : null,
            ],
            [
                'label' => 'Accounts Queue Created',
                'state' => 'done',
                'detail' => 'Opened in parallel with PMS',
                'at' => optional($item->created_at)?->toDateTimeString(),
                'badge' => $item->status,
            ],
            [
                'label' => 'Report Number Captured',
                'state' => $reportOk ? 'done' : 'pending',
                'detail' => $reportOk ? (string) $item->report_number : 'Awaiting report number',
                'at' => $reportOk ? optional($item->updated_at)?->toDateTimeString() : null,
                'badge' => $reportOk ? 'Captured' : null,
            ],
            [
                'label' => 'Confirmations Complete',
                'state' => ($confirmed && $pmsOk) ? 'done' : 'pending',
                'detail' => ($confirmed && $pmsOk) ? 'PMS + Accounts cleared' : 'Awaiting PMS and/or Accounts approval',
                'at' => $confirmed ? optional($item->approved_at)?->toDateTimeString() : null,
                'badge' => ($confirmed && $pmsOk) ? 'Complete' : null,
            ],
            [
                'label' => 'Payout On Hold until eligible',
                'state' => match ($payout?->status) {
                    'Eligible', 'Processing', 'Paid' => 'done',
                    'Exception' => 'hold',
                    default => 'hold',
                },
                'detail' => $payout ? ('Status: '.$payout->status) : 'Payout pending',
                'at' => optional($payout?->updated_at)?->toDateTimeString(),
                'badge' => $payout?->status,
            ],
        ];
    }

    /**
     * @return list<array{title:string,meta:string}>
     */
    public function recentActivity(GoalAccountsItem $item, int $limit = 8): array
    {
        $caseId = (int) $item->goal_loan_case_id;
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

    public function commercialTerms(?array $branchMis): array
    {
        $mis = is_array($branchMis) ? $branchMis : [];

        return [
            'payout_pct' => $mis['payout_pct'] ?? null,
            'payout_amount' => isset($mis['payout_amount']) ? (float) $mis['payout_amount'] : null,
            'payin_pct' => $mis['payin_pct'] ?? null,
            'payin_amt' => isset($mis['payin_amt']) ? (float) $mis['payin_amt'] : null,
            'exception_note' => $mis['sm_remark'] ?? ($mis['exception_note'] ?? null),
        ];
    }
}
