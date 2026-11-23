<?php

namespace App\Services\Goal;

use App\Models\GoalCaseEvent;
use App\Models\GoalDisbursement;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\GoalRevenueEntry;
use App\Models\User;
use App\Services\FinancialYearService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GoalRevenueLedgerService
{
    public function __construct(
        protected FinancialYearService $fy
    ) {}

    /**
     * @param  array<int>  $scopeIds
     * @return array<string, mixed>
     */
    public function build(User $user, Request $request, array $scopeIds): array
    {
        $filters = $this->normalizeFilters($request);
        $base = $this->baseQuery($scopeIds, $filters);
        $filtered = $this->applyTabAndCard(clone $base, $filters);

        /** @var LengthAwarePaginator $entries */
        $entries = (clone $filtered)
            ->with([
                'loanCase:id,case_no,customer_name,product,owner_id,goal_org_unit_id,lender_name,goal_loan_lead_id',
                'disbursement:id,amount,disbursed_on,verified_at,status',
                'orgUnit:id,name,type,parent_id',
                'attributedUser:id,name',
            ])
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?: 25))
            ->withQueryString();

        $agg = $this->aggregateKpisAndTabs(clone $base);
        $verified = $this->verifiedDisbursementTotals($scopeIds, $filters);
        $kpis = array_merge($agg['kpis'], [
            'verified_disb' => $verified['amount'],
            'verified_disb_count' => $verified['count'],
        ]);

        $recentAdjustments = (clone $base)
            ->whereIn('entry_type', [GoalRevenueEntry::TYPE_ADJUSTMENT, GoalRevenueEntry::TYPE_REVERSAL])
            ->with(['loanCase:id,case_no', 'creator:id,name', 'approver:id,name'])
            ->latest('id')
            ->limit(5)
            ->get();

        $fyMap = $this->fy->fyRangeMap();

        return [
            'filters' => $filters,
            'entries' => $entries,
            'kpis' => $kpis,
            'attribution' => $this->attributionSummaryFast(clone $base),
            'recent_adjustments' => $recentAdjustments,
            'tab_counts' => $agg['tabs'],
            'filterOptions' => $this->filterOptions($scopeIds),
            'credit_options' => $this->creditOptions($scopeIds),
            'fy_range_map' => $fyMap,
            'fy_options' => array_keys($fyMap),
        ];
    }

    /**
     * One SQL pass for KPI amounts/counts + tab counts.
     *
     * @return array{kpis: array<string, float|int>, tabs: array<string, int>}
     */
    public function aggregateKpisAndTabs(Builder $base): array
    {
        $row = (clone $base)
            ->reorder()
            ->selectRaw("
                COUNT(*) as total_count,
                COALESCE(SUM(CASE WHEN COALESCE(entry_type,'recognized') IN ('recognized','') THEN amount ELSE 0 END),0) as recognized_amt,
                SUM(CASE WHEN COALESCE(entry_type,'recognized') IN ('recognized','') THEN 1 ELSE 0 END) as recognized_count,
                COALESCE(SUM(CASE WHEN entry_type = 'adjustment' THEN amount ELSE 0 END),0) as adjustment_amt,
                SUM(CASE WHEN entry_type = 'adjustment' THEN 1 ELSE 0 END) as adjustment_count,
                COALESCE(SUM(CASE WHEN entry_type = 'reversal' THEN amount ELSE 0 END),0) as reversal_amt,
                SUM(CASE WHEN entry_type = 'reversal' THEN 1 ELSE 0 END) as reversal_count,
                COALESCE(SUM(CASE WHEN status = 'pending_review' THEN amount ELSE 0 END),0) as pending_amt,
                SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending_count,
                COALESCE(SUM(CASE WHEN status IN ('posted','approved') OR status IS NULL OR status = '' THEN amount ELSE 0 END),0) as net_amt
            ")
            ->first();

        return [
            'kpis' => [
                'recognized' => (float) ($row->recognized_amt ?? 0),
                'recognized_count' => (int) ($row->recognized_count ?? 0),
                'adjustments' => (float) ($row->adjustment_amt ?? 0),
                'adjustment_count' => (int) ($row->adjustment_count ?? 0),
                'reversals' => (float) ($row->reversal_amt ?? 0),
                'reversal_count' => (int) ($row->reversal_count ?? 0),
                'pending' => (float) ($row->pending_amt ?? 0),
                'pending_count' => (int) ($row->pending_count ?? 0),
                'net' => (float) ($row->net_amt ?? 0),
            ],
            'tabs' => [
                'all' => (int) ($row->total_count ?? 0),
                'recognized' => (int) ($row->recognized_count ?? 0),
                'adjustment' => (int) ($row->adjustment_count ?? 0),
                'reversal' => (int) ($row->reversal_count ?? 0),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeFilters(Request $request): array
    {
        $period = $this->fy->resolveReportPeriod(
            $request->input('fy'),
            $request->input('date_from'),
            $request->input('date_to')
        );

        $tab = (string) $request->input('tab', 'all');
        if (! in_array($tab, ['all', 'recognized', 'adjustment', 'reversal'], true)) {
            $tab = 'all';
        }

        $card = (string) $request->input('card', '');
        if (! in_array($card, ['recognized', 'verified_disb', 'adjustment', 'reversal', 'pending'], true)) {
            $card = '';
        }

        $entryType = (string) $request->input('entry_type', '');
        if ($entryType === '' && in_array($tab, ['recognized', 'adjustment', 'reversal'], true)) {
            $entryType = $tab;
        }
        if ($card === 'recognized' || $card === 'verified_disb') {
            $entryType = 'recognized';
        } elseif ($card === 'adjustment') {
            $entryType = 'adjustment';
        } elseif ($card === 'reversal') {
            $entryType = 'reversal';
        }

        return [
            'fy' => $period['fy'],
            'fy_label' => $this->fy->label($this->fy->findByAnalyticsKey($period['fy']) ?? $this->fy->current()),
            'date_from' => $period['date_from'],
            'date_to' => $period['date_to'],
            'region' => $request->filled('region') ? (int) $request->input('region') : null,
            'cluster' => $request->filled('cluster') ? (int) $request->input('cluster') : null,
            'branch' => $request->filled('branch') ? (int) $request->input('branch') : null,
            'owner' => $request->filled('owner') ? (int) $request->input('owner') : null,
            'product' => trim((string) $request->input('product', '')),
            'source' => trim((string) $request->input('source', '')),
            'entry_type' => in_array($entryType, ['recognized', 'adjustment', 'reversal'], true) ? $entryType : '',
            'status' => trim((string) $request->input('status', '')),
            'q' => trim((string) $request->input('q', '')),
            'tab' => $tab,
            'card' => $card,
            'per_page' => in_array((int) $request->input('per_page'), [25, 50, 100], true)
                ? (int) $request->input('per_page')
                : 25,
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     */
    public function baseQuery(array $scopeIds, array $filters): Builder
    {
        $q = GoalRevenueEntry::query();
        $t = $q->getModel()->getTable();

        if ($scopeIds === [-1]) {
            $q->whereRaw('1 = 0');
        } elseif ($scopeIds !== []) {
            $q->whereIn($t.'.goal_org_unit_id', $scopeIds);
        }

        $from = Carbon::parse($filters['date_from'])->startOfDay();
        $to = Carbon::parse($filters['date_to'])->endOfDay();
        $q->whereBetween($t.'.created_at', [$from, $to]);

        $orgIds = $this->resolveOrgFilterIds($filters);
        if ($orgIds !== null) {
            if ($orgIds === []) {
                $q->whereRaw('1 = 0');
            } else {
                $q->whereIn($t.'.goal_org_unit_id', $orgIds);
            }
        }

        if (! empty($filters['owner'])) {
            $q->where($t.'.attributed_user_id', (int) $filters['owner']);
        }

        if ($filters['product'] !== '') {
            $q->whereHas('loanCase', fn (Builder $c) => $c->where('product', $filters['product']));
        }

        if ($filters['source'] !== '') {
            $q->whereHas('loanCase.lead', fn (Builder $c) => $c->where('source', $filters['source']));
        }

        if ($filters['q'] !== '') {
            $term = $filters['q'];
            $q->where(function (Builder $b) use ($term, $t) {
                $b->where($t.'.entry_ref', 'like', '%'.$term.'%')
                    ->orWhere($t.'.note', 'like', '%'.$term.'%')
                    ->orWhere($t.'.reason', 'like', '%'.$term.'%')
                    ->orWhereHas('loanCase', function (Builder $c) use ($term) {
                        $c->where('case_no', 'like', '%'.$term.'%')
                            ->orWhere('customer_name', 'like', '%'.$term.'%')
                            ->orWhere('mobile', 'like', '%'.$term.'%');
                    });
            });
        }

        return $q;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyTabAndCard(Builder $query, array $filters): Builder
    {
        $t = $query->getModel()->getTable();

        if ($filters['card'] === 'pending' || ($filters['status'] ?? '') === GoalRevenueEntry::STATUS_PENDING) {
            $query->where($t.'.status', GoalRevenueEntry::STATUS_PENDING);

            return $query;
        }

        $type = $filters['entry_type'] ?? '';
        if ($type === 'recognized') {
            $query->where(function (Builder $b) use ($t) {
                $b->where($t.'.entry_type', GoalRevenueEntry::TYPE_RECOGNIZED)
                    ->orWhereNull($t.'.entry_type')
                    ->orWhere($t.'.entry_type', '');
            });
        } elseif (in_array($type, ['adjustment', 'reversal'], true)) {
            $query->where($t.'.entry_type', $type);
        }

        if (($filters['status'] ?? '') !== '' && $filters['card'] !== 'pending') {
            $query->where($t.'.status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function kpis(Builder $base, array $scopeIds, array $filters): array
    {
        $recognizedQ = (clone $base)->where(function (Builder $b) {
            $b->where('entry_type', GoalRevenueEntry::TYPE_RECOGNIZED)
                ->orWhereNull('entry_type')
                ->orWhere('entry_type', '');
        });
        $adjQ = (clone $base)->where('entry_type', GoalRevenueEntry::TYPE_ADJUSTMENT);
        $revQ = (clone $base)->where('entry_type', GoalRevenueEntry::TYPE_REVERSAL);
        $pendingQ = (clone $base)->where('status', GoalRevenueEntry::STATUS_PENDING);

        $verified = $this->verifiedDisbursementTotals($scopeIds, $filters);

        return [
            'recognized' => (float) (clone $recognizedQ)->sum('amount'),
            'recognized_count' => (clone $recognizedQ)->count(),
            'verified_disb' => $verified['amount'],
            'verified_disb_count' => $verified['count'],
            'adjustments' => (float) (clone $adjQ)->sum('amount'),
            'adjustment_count' => (clone $adjQ)->count(),
            'reversals' => (float) (clone $revQ)->sum('amount'),
            'reversal_count' => (clone $revQ)->count(),
            'pending' => (float) (clone $pendingQ)->sum('amount'),
            'pending_count' => (clone $pendingQ)->count(),
            'net' => (float) (clone $base)->whereIn('status', [
                GoalRevenueEntry::STATUS_POSTED,
                GoalRevenueEntry::STATUS_APPROVED,
            ])->sum('amount'),
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @return array{amount: float, count: int}
     */
    public function verifiedDisbursementTotals(array $scopeIds, array $filters): array
    {
        $from = Carbon::parse($filters['date_from'])->startOfDay();
        $to = Carbon::parse($filters['date_to'])->endOfDay();

        $q = GoalDisbursement::query()
            ->from('goal_disbursements as d')
            ->join('goal_loan_cases as c', 'c.id', '=', 'd.goal_loan_case_id')
            ->whereNull('c.deleted_at')
            ->where('d.status', 'Verified')
            ->whereNotNull('d.verified_at')
            ->whereBetween('d.verified_at', [$from, $to]);

        if ($scopeIds === [-1]) {
            $q->whereRaw('1 = 0');
        } elseif ($scopeIds !== []) {
            $q->whereIn('c.goal_org_unit_id', $scopeIds);
        }

        $orgIds = $this->resolveOrgFilterIds($filters);
        if ($orgIds !== null) {
            $q->whereIn('c.goal_org_unit_id', $orgIds !== [] ? $orgIds : [-1]);
        }
        if (! empty($filters['owner'])) {
            $q->where('c.owner_id', (int) $filters['owner']);
        }
        if (($filters['product'] ?? '') !== '') {
            $q->where('c.product', $filters['product']);
        }

        $row = $q->selectRaw('COUNT(*) as cnt, COALESCE(SUM(d.amount),0) as amt')->first();

        return [
            'amount' => (float) ($row->amt ?? 0),
            'count' => (int) ($row->cnt ?? 0),
        ];
    }

    /**
     * Lightweight attribution via SQL group-by (employee + branch), then roll branch → cluster/region.
     *
     * @return array<string, mixed>
     */
    public function attributionSummaryFast(Builder $base): array
    {
        $empty = [
            'entries' => 0,
            'disbursements' => 0,
            'disbursed' => 0.0,
            'recognized' => 0.0,
            'adjustments' => 0.0,
            'reversals' => 0.0,
            'net' => 0.0,
        ];

        $company = array_merge(['key' => 'company', 'label' => 'Company'], $empty);
        $t = (clone $base)->getModel()->getTable();
        $rows = (clone $base)
            ->reorder()
            ->where(function (Builder $b) use ($t) {
                $b->whereIn($t.'.status', [GoalRevenueEntry::STATUS_POSTED, GoalRevenueEntry::STATUS_APPROVED])
                    ->orWhereNull($t.'.status')
                    ->orWhere($t.'.status', '');
            })
            ->leftJoin('goal_disbursements as d', 'd.id', '=', $t.'.goal_disbursement_id')
            ->selectRaw("
                {$t}.attributed_user_id as owner_id,
                {$t}.goal_org_unit_id as org_id,
                COUNT({$t}.id) as entries,
                COALESCE(SUM(CASE WHEN COALESCE({$t}.entry_type,'recognized') IN ('recognized','') THEN {$t}.amount ELSE 0 END),0) as recognized,
                COALESCE(SUM(CASE WHEN {$t}.entry_type = 'adjustment' THEN {$t}.amount ELSE 0 END),0) as adjustments,
                COALESCE(SUM(CASE WHEN {$t}.entry_type = 'reversal' THEN {$t}.amount ELSE 0 END),0) as reversals,
                COALESCE(SUM({$t}.amount),0) as net,
                SUM(CASE WHEN d.id IS NOT NULL THEN 1 ELSE 0 END) as disbursements,
                COALESCE(SUM(d.amount),0) as disbursed
            ")
            ->groupBy($t.'.attributed_user_id', $t.'.goal_org_unit_id')
            ->get();

        $ownerIds = $rows->pluck('owner_id')->filter()->unique()->all();
        $owners = $ownerIds !== []
            ? User::query()->whereIn('id', $ownerIds)->pluck('name', 'id')
            : collect();
        $units = GoalOrgUnit::query()->get(['id', 'name', 'type', 'parent_id'])->keyBy('id');

        $employee = [];
        $branch = [];
        $cluster = [];
        $region = [];

        $bump = function (array &$bucket, string $key, string $label, $row) use ($empty) {
            if (! isset($bucket[$key])) {
                $bucket[$key] = array_merge(['key' => $key, 'label' => $label], $empty);
            }
            $bucket[$key]['entries'] += (int) ($row->entries ?? 0);
            $bucket[$key]['recognized'] += (float) $row->recognized;
            $bucket[$key]['adjustments'] += (float) $row->adjustments;
            $bucket[$key]['reversals'] += (float) $row->reversals;
            $bucket[$key]['net'] += (float) $row->net;
            $bucket[$key]['disbursements'] += (int) $row->disbursements;
            $bucket[$key]['disbursed'] += (float) $row->disbursed;
        };

        foreach ($rows as $row) {
            $oid = (int) ($row->owner_id ?: 0);
            $bump($employee, 'u'.$oid, $owners[$oid] ?? 'Unassigned', $row);

            $orgId = (int) ($row->org_id ?: 0);
            $unit = $units[$orgId] ?? null;
            if ($unit && $unit->type === GoalOrgUnit::TYPE_BRANCH) {
                $bump($branch, 'b'.$unit->id, $unit->name, $row);
            }

            // Walk parents once using parent_id map (no recursive queries).
            $cursor = $unit;
            $guard = 0;
            while ($cursor && $guard < 6) {
                if ($cursor->type === GoalOrgUnit::TYPE_CLUSTER) {
                    $bump($cluster, 'c'.$cursor->id, $cursor->name, $row);
                }
                if ($cursor->type === GoalOrgUnit::TYPE_REGION) {
                    $bump($region, 'r'.$cursor->id, $cursor->name, $row);
                }
                $cursor = $cursor->parent_id ? ($units[$cursor->parent_id] ?? null) : null;
                $guard++;
            }

            $company['entries'] += (int) ($row->entries ?? 0);
            $company['recognized'] += (float) $row->recognized;
            $company['adjustments'] += (float) $row->adjustments;
            $company['reversals'] += (float) $row->reversals;
            $company['net'] += (float) $row->net;
            $company['disbursements'] += (int) $row->disbursements;
            $company['disbursed'] += (float) $row->disbursed;
        }

        $top = function (array $bucket): array {
            $list = array_values($bucket);
            usort($list, fn ($a, $b) => ($b['net'] <=> $a['net']));

            return array_slice($list, 0, 8);
        };

        return [
            'employee' => $top($employee),
            'branch' => $top($branch),
            'cluster' => $top($cluster),
            'region' => $top($region),
            'company' => $company,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function entryDetail(GoalRevenueEntry $entry): array
    {
        $entry->loadMissing([
            'loanCase.owner',
            'loanCase.orgUnit.parent.parent',
            'disbursement.verifier',
            'attributedUser',
            'orgUnit.parent.parent',
            'creator',
            'approver',
            'parentEntry',
            'childEntries',
        ]);

        $case = $entry->loanCase;
        $branch = $entry->orgUnit;
        $cluster = $branch?->parent;
        $region = $cluster?->parent;
        if ($branch && $branch->type === GoalOrgUnit::TYPE_CLUSTER) {
            $cluster = $branch;
            $region = $branch->parent;
            $branch = null;
        }
        if ($branch && $branch->type === GoalOrgUnit::TYPE_REGION) {
            $region = $branch;
            $cluster = null;
            $branch = null;
        }

        // Prefer linked disbursement; otherwise fall back to case's latest verified disbursement.
        $disb = $entry->disbursement;
        if (! $disb && $case) {
            $disb = GoalDisbursement::query()
                ->with('verifier:id,name')
                ->where('goal_loan_case_id', $case->id)
                ->whereNotNull('verified_at')
                ->latest('verified_at')
                ->latest('id')
                ->first();
        }

        $mis = is_array($case?->branch_mis) ? $case->branch_mis : [];
        $defaultRatePct = round(((float) config('goal.revenue_rate', 0.0035)) * 100, 4);
        $ratePct = isset($mis['payin_pct']) && $mis['payin_pct'] !== '' && $mis['payin_pct'] !== null
            ? (float) $mis['payin_pct']
            : $defaultRatePct;
        $ratePctLabel = rtrim(rtrim(number_format($ratePct, 4, '.', ''), '0'), '.');
        $policyRef = trim((string) ($mis['commercial_policy_ref'] ?? $mis['policy_ref'] ?? $mis['commercial_ref'] ?? ''));
        if ($policyRef === '') {
            $policyRef = 'Goal commercial · '.$ratePctLabel.'%';
        }
        $basis = isset($mis['payin_pct']) && $mis['payin_pct'] !== '' && $mis['payin_pct'] !== null
            ? 'Case pay-in rate '.$ratePctLabel.'% of verified disbursement'
            : 'Company commercial rate '.$ratePctLabel.'% of verified disbursement';
        if (! empty($mis['exception_note']) || ! empty($mis['sm_remark'])) {
            $basis .= ' · '.trim((string) ($mis['exception_note'] ?? $mis['sm_remark']));
        }

        $attrMode = $entry->attributed_user_id
            ? 'Employee'
            : (($entry->goal_org_unit_id ? ucfirst((string) ($entry->orgUnit?->type ?: 'org')) : 'Unassigned'));

        $audits = [];
        if ($case) {
            $audits = GoalCaseEvent::query()
                ->where('goal_loan_case_id', $case->id)
                ->where(function (Builder $b) use ($entry, $disb) {
                    $b->whereIn('event_type', ['revenue', 'disbursement'])
                        ->orWhere('related_type', GoalRevenueEntry::class)
                        ->orWhere(function (Builder $x) use ($entry) {
                            $x->where('related_type', GoalRevenueEntry::class)
                                ->where('related_id', $entry->id);
                        });
                    if ($disb?->id) {
                        $b->orWhere(function (Builder $x) use ($disb) {
                            $x->where('related_type', GoalDisbursement::class)
                                ->where('related_id', $disb->id);
                        });
                    } elseif ($entry->goal_disbursement_id) {
                        $b->orWhere(function (Builder $x) use ($entry) {
                            $x->where('related_type', GoalDisbursement::class)
                                ->where('related_id', $entry->goal_disbursement_id);
                        });
                    }
                })
                ->with('user:id,name')
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn ($a) => [
                    'when' => optional($a->created_at)->format('d M Y, H:i'),
                    'event' => $a->title ?: ($a->event_type ?: 'Event'),
                    'actor' => $a->user?->name ?: 'System',
                    'body' => $a->body,
                ])
                ->all();
        }

        // Always include this ledger row's own posting as an audit beat when no events exist.
        if ($audits === []) {
            $audits[] = [
                'when' => optional($entry->posted_at ?? $entry->created_at)->format('d M Y, H:i'),
                'event' => $entry->typeLabel().' posted',
                'actor' => $entry->creator?->name ?: ($entry->approver?->name ?: 'System'),
                'body' => $entry->reason ?: $entry->note,
            ];
        }

        $period = optional($entry->posted_at ?? $entry->created_at)->format('M Y') ?: now()->format('M Y');
        $ownerName = $entry->attributedUser?->name ?: ($case?->owner?->name ?: null);

        return [
            'id' => $entry->id,
            'entry_ref' => $entry->entry_ref ?: ('RV-'.$entry->id),
            'status' => $entry->statusLabel(),
            'status_key' => $entry->status ?: GoalRevenueEntry::STATUS_POSTED,
            'entry_type' => $entry->typeLabel(),
            'entry_type_key' => $entry->entry_type ?: GoalRevenueEntry::TYPE_RECOGNIZED,
            'amount' => (float) $entry->amount,
            'reason' => $entry->reason ?: $entry->note,
            'note' => $entry->note,
            'posted_at' => optional($entry->posted_at ?? $entry->created_at)->format('d M Y H:i'),
            'case' => [
                'id' => $case?->id,
                'ref' => $case?->case_no,
                'customer' => $case?->customer_name,
                'product' => $case?->product,
                'source' => $case?->lead?->source,
                'branch' => $branch?->name ?: $case?->orgUnit?->name,
                'employee' => $ownerName,
                'url' => $case ? route('goal.cases.show', $case) : null,
            ],
            'disbursement' => [
                'id' => $disb?->id,
                'ref' => $disb ? ('DSB-'.str_pad((string) $disb->id, 4, '0', STR_PAD_LEFT)) : null,
                'amount' => $disb ? (float) $disb->amount : null,
                'date' => optional($disb?->disbursed_on ?? $disb?->verified_at)->format('d M Y'),
                'verified' => (bool) $disb?->verified_at,
                'verified_on' => optional($disb?->verified_at)->format('d M Y'),
                'verified_by' => $disb?->verifier?->name,
            ],
            'revenue' => [
                'policy' => $policyRef,
                'policy_label' => $policyRef,
                'rate_pct' => $ratePct,
                'basis' => $basis,
                'recognized' => (float) $entry->amount,
                'type' => $entry->typeLabel(),
                'posting_date' => optional($entry->posted_at ?? $entry->created_at)->format('d M Y'),
                'remarks' => $entry->note ?: ($entry->reason ?: null),
            ],
            'attribution' => [
                'owner' => $ownerName ?: '—',
                'mode' => $attrMode,
                'branch' => $branch?->name ?: ($case?->orgUnit?->name ?: '—'),
                'cluster' => $cluster?->name ?: '—',
                'region' => $region?->name ?: '—',
            ],
            'target' => [
                'period' => $period,
                'included' => in_array($entry->status, [GoalRevenueEntry::STATUS_POSTED, GoalRevenueEntry::STATUS_APPROVED, null, ''], true),
                'contribution' => (float) $entry->amount,
                'credited_to' => $ownerName ?: '—',
            ],
            'parent_ref' => $entry->parentEntry?->entry_ref,
            'reversible_remaining' => $entry->reversibleRemaining(),
            'audit' => $audits,
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @return array<string, Collection>
     */
    public function filterOptions(array $scopeIds): array
    {
        if ($scopeIds === [-1]) {
            $units = collect();
        } elseif ($scopeIds === []) {
            // Empty array from caller means unrestricted (global) — all active units.
            $units = GoalOrgUnit::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'type', 'parent_id']);
        } else {
            // Include scoped units + ancestors so Region/Cluster dropdowns work when scope is branch-only.
            $scoped = GoalOrgUnit::query()
                ->where('is_active', true)
                ->whereIn('id', $scopeIds)
                ->get(['id', 'name', 'type', 'parent_id']);
            $ancestorIds = [];
            foreach ($scoped as $unit) {
                foreach ($unit->ancestorIds() as $aid) {
                    $ancestorIds[$aid] = true;
                }
            }
            $ancestors = $ancestorIds !== []
                ? GoalOrgUnit::query()->where('is_active', true)->whereIn('id', array_keys($ancestorIds))->get(['id', 'name', 'type', 'parent_id'])
                : collect();
            $units = $scoped->concat($ancestors)->unique('id')->sortBy('name')->values();
        }

        $caseQ = GoalLoanCase::query();
        if ($scopeIds === [-1]) {
            $caseQ->whereRaw('1 = 0');
        } elseif ($scopeIds !== []) {
            $caseQ->whereIn('goal_org_unit_id', $scopeIds);
        }

        $attributedOwnerIds = GoalRevenueEntry::query()
            ->when($scopeIds === [-1], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereIn('goal_org_unit_id', $scopeIds))
            ->whereNotNull('attributed_user_id')
            ->distinct()
            ->pluck('attributed_user_id');

        // Employees for filter: active users in scoped org units + anyone attributed on ledger rows.
        $ownerQuery = User::query()->where('status', 1)->orderBy('name');
        if ($scopeIds === [-1]) {
            $ownerQuery->whereRaw('1 = 0');
        } elseif ($scopeIds !== []) {
            $ownerQuery->where(function (Builder $b) use ($scopeIds, $attributedOwnerIds) {
                $b->whereIn('goal_org_unit_id', $scopeIds);
                if ($attributedOwnerIds->isNotEmpty()) {
                    $b->orWhereIn('id', $attributedOwnerIds);
                }
            });
        }
        $ownerUsers = $ownerQuery->get(['id', 'name', 'goal_org_unit_id', 'job_role', 'crm_role_type']);

        // Resolve region/cluster/branch for each owner so the UI can cascade-filter.
        $unitById = GoalOrgUnit::query()
            ->whereIn('id', $ownerUsers->pluck('goal_org_unit_id')->filter()->unique()->all())
            ->get(['id', 'type', 'parent_id'])
            ->keyBy('id');
        // Also load ancestors we may walk into.
        $extraParentIds = $unitById->pluck('parent_id')->filter()->unique()->all();
        if ($extraParentIds !== []) {
            $more = GoalOrgUnit::query()->whereIn('id', $extraParentIds)->get(['id', 'type', 'parent_id'])->keyBy('id');
            $unitById = $unitById->union($more);
            $grand = $more->pluck('parent_id')->filter()->unique()->all();
            if ($grand !== []) {
                $unitById = $unitById->union(
                    GoalOrgUnit::query()->whereIn('id', $grand)->get(['id', 'type', 'parent_id'])->keyBy('id')
                );
            }
        }

        $owners = $ownerUsers->map(function (User $u) use ($unitById) {
            $regionId = null;
            $clusterId = null;
            $branchId = null;
            $cursor = $u->goal_org_unit_id ? ($unitById[(int) $u->goal_org_unit_id] ?? null) : null;
            $guard = 0;
            while ($cursor && $guard < 8) {
                if ($cursor->type === GoalOrgUnit::TYPE_BRANCH) {
                    $branchId = (int) $cursor->id;
                } elseif ($cursor->type === GoalOrgUnit::TYPE_CLUSTER) {
                    $clusterId = (int) $cursor->id;
                } elseif ($cursor->type === GoalOrgUnit::TYPE_REGION) {
                    $regionId = (int) $cursor->id;
                }
                $cursor = $cursor->parent_id ? ($unitById[(int) $cursor->parent_id] ?? null) : null;
                $guard++;
            }

            $roleKey = GoalDashboardService::ROLE_KEYS[(int) ($u->crm_role_type ?? -1)] ?? null;
            $roleLabel = $roleKey
                ? (GoalDashboardService::ROLE_LABELS[$roleKey] ?? strtoupper($roleKey))
                : (match (strtolower((string) ($u->job_role ?? ''))) {
                    'bm', 'branch_manager' => 'Branch Manager',
                    'jsm' => 'Junior Sales Manager',
                    'sm' => 'Sales Manager',
                    'ssm' => 'Senior Sales Manager',
                    'rd' => 'Regional Director',
                    'cluster' => 'Cluster Head',
                    'vrm' => 'VRM',
                    default => $u->job_role ? strtoupper((string) $u->job_role) : 'User',
                });

            return (object) [
                'id' => $u->id,
                'name' => $u->name,
                'role' => $roleLabel,
                'label' => $u->name.' ('.$roleLabel.')',
                'goal_org_unit_id' => $u->goal_org_unit_id,
                'region_id' => $regionId,
                'cluster_id' => $clusterId,
                'branch_id' => $branchId,
            ];
        })->values();

        return [
            'regions' => $units->where('type', GoalOrgUnit::TYPE_REGION)->values(),
            'clusters' => $units->where('type', GoalOrgUnit::TYPE_CLUSTER)->values(),
            'branches' => $units->where('type', GoalOrgUnit::TYPE_BRANCH)->values(),
            'products' => (clone $caseQ)->whereNotNull('product')->where('product', '!=', '')->distinct()->orderBy('product')->pluck('product'),
            'sources' => \App\Models\GoalLoanLead::query()
                ->when($scopeIds === [-1], fn ($q) => $q->whereRaw('1 = 0'))
                ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereIn('goal_org_unit_id', $scopeIds))
                ->whereNotNull('source')->where('source', '!=', '')
                ->distinct()->orderBy('source')->pluck('source'),
            'owners' => $owners,
        ];
    }

    /**
     * Recognized credits available for adjustment/reversal linking.
     *
     * @param  array<int>  $scopeIds
     */
    public function creditOptions(array $scopeIds): Collection
    {
        return GoalRevenueEntry::query()
            ->when($scopeIds === [-1], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereIn('goal_org_unit_id', $scopeIds))
            ->where(function (Builder $b) {
                $b->where('entry_type', GoalRevenueEntry::TYPE_RECOGNIZED)
                    ->orWhereNull('entry_type')
                    ->orWhere('entry_type', '');
            })
            ->where(function (Builder $b) {
                $b->whereIn('status', [GoalRevenueEntry::STATUS_POSTED, GoalRevenueEntry::STATUS_APPROVED])
                    ->orWhereNull('status')
                    ->orWhere('status', '');
            })
            ->with(['loanCase:id,case_no,customer_name'])
            ->latest('id')
            ->limit(100)
            ->get(['id', 'entry_ref', 'amount', 'goal_loan_case_id', 'goal_disbursement_id']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int>|null null = no org dimension filter
     */
    protected function resolveOrgFilterIds(array $filters): ?array
    {
        if (! empty($filters['branch'])) {
            return [(int) $filters['branch']];
        }
        if (! empty($filters['cluster'])) {
            $unit = GoalOrgUnit::query()->find((int) $filters['cluster']);

            return $unit ? $unit->descendantIds() : [];
        }
        if (! empty($filters['region'])) {
            $unit = GoalOrgUnit::query()->find((int) $filters['region']);

            return $unit ? $unit->descendantIds() : [];
        }

        return null;
    }
}
