<?php

namespace App\Services\Goal;

use App\Helpers\ActivityLogger;
use App\Models\GoalDisbursement;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\GoalRevenueEntry;
use App\Models\GoalTarget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GoalRevenueReportService
{
    public const REPORTS = [
        'summary' => [
            'key' => 'summary',
            'title' => 'Revenue Summary Report',
            'blurb' => 'Total recognized revenue, disbursements, adjustments and reversals.',
            'icon' => 'bi-bar-chart-fill',
            'tone' => 'green',
            'priority' => true,
        ],
        'ledger' => [
            'key' => 'ledger',
            'title' => 'Detailed Revenue Ledger',
            'blurb' => 'Complete transaction register with all entries.',
            'icon' => 'bi-layers',
            'tone' => 'teal',
            'priority' => true,
        ],
        'employee' => [
            'key' => 'employee',
            'title' => 'Employee-wise Revenue Report',
            'blurb' => 'Individual contribution, ranking and achievement.',
            'icon' => 'bi-people-fill',
            'tone' => 'violet',
            'priority' => true,
        ],
        'org' => [
            'key' => 'org',
            'title' => 'Branch / Cluster / Region Revenue',
            'blurb' => 'Organizational performance by location hierarchy.',
            'icon' => 'bi-building',
            'tone' => 'blue',
            'priority' => true,
        ],
        'commercial' => [
            'key' => 'commercial',
            'title' => 'PayIn / Payout / Net Revenue Report',
            'blurb' => 'Commercial figures and lender-wise breakdown.',
            'icon' => 'bi-cash-stack',
            'tone' => 'amber',
            'priority' => false,
        ],
        'adjustment' => [
            'key' => 'adjustment',
            'title' => 'Revenue Adjustment & Reversal Report',
            'blurb' => 'Corrections with reason and approver audit trail.',
            'icon' => 'bi-arrow-left-right',
            'tone' => 'mint',
            'priority' => true,
        ],
        'target' => [
            'key' => 'target',
            'title' => 'Revenue Target vs Achievement Report',
            'blurb' => 'Base target, shortfall, actual and closing gap.',
            'icon' => 'bi-bullseye',
            'tone' => 'rose',
            'priority' => false,
        ],
        'recon' => [
            'key' => 'recon',
            'title' => 'Disbursement vs Revenue Reconciliation',
            'blurb' => 'Matched, missing and mismatched revenue entries.',
            'icon' => 'bi-clipboard-data',
            'tone' => 'navy',
            'priority' => false,
        ],
    ];

    public function __construct(
        protected GoalRevenueLedgerService $ledger,
        protected GoalBranchMisService $branchMis
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function catalog(): array
    {
        return array_values(self::REPORTS);
    }

    public function resolveReportKey(?string $key): string
    {
        $key = strtolower(trim((string) $key));

        return array_key_exists($key, self::REPORTS) ? $key : 'summary';
    }

    /**
     * @param  array<int>  $scopeIds
     * @return array<string, mixed>
     */
    public function preview(User $user, array $scopeIds, Request $request, ?string $reportKey = null): array
    {
        $reportKey = $this->resolveReportKey($reportKey ?: $request->input('report'));
        $filters = $this->ledger->normalizeFilters($request);
        $payload = $this->build($user, $scopeIds, $filters, $reportKey, true);

        return [
            'ok' => true,
            'report' => self::REPORTS[$reportKey],
            'filters' => $filters,
            'meta' => $payload['meta'],
            'kpis' => $payload['kpis'],
            'columns' => $payload['columns'],
            'rows' => array_slice($payload['rows'], 0, 25),
            'row_count' => count($payload['rows']),
            'charts' => $payload['charts'] ?? [],
            'totals' => $payload['totals'] ?? [],
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $scopeIds, array $filters, string $reportKey, bool $preview = false): array
    {
        $reportKey = $this->resolveReportKey($reportKey);
        $canSeeContact = $this->canSeeCustomerContact($user);
        $ratePct = round(((float) config('goal.revenue_rate', 0.0035)) * 100, 4);

        $meta = [
            'report_id' => 'REV-RPT-'.now()->format('Ymd-His'),
            'report_key' => $reportKey,
            'title' => self::REPORTS[$reportKey]['title'],
            'company' => 'Goal Corporation',
            'product' => 'Goal Loan CRM',
            'fy_label' => $filters['fy_label'] ?? '',
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
            'period_label' => $this->periodLabel($filters),
            'generated_at' => now()->format('d M Y H:i'),
            'generated_by' => $user->name ?? 'System',
            'rate_pct' => $ratePct,
            'filter_labels' => $this->filterLabels($filters),
        ];

        return match ($reportKey) {
            'ledger' => $this->buildLedger($scopeIds, $filters, $meta, $canSeeContact, $preview),
            'employee' => $this->buildEmployee($scopeIds, $filters, $meta, $preview),
            'org' => $this->buildOrg($scopeIds, $filters, $meta, $preview),
            'commercial' => $this->buildCommercial($scopeIds, $filters, $meta, $preview),
            'adjustment' => $this->buildAdjustment($scopeIds, $filters, $meta, $preview),
            'target' => $this->buildTarget($scopeIds, $filters, $meta, $preview),
            'recon' => $this->buildRecon($scopeIds, $filters, $meta, $preview),
            default => $this->buildSummary($scopeIds, $filters, $meta, $preview),
        };
    }

    public function logExport(User $user, string $reportKey, string $format, array $filters, int $rowCount): void
    {
        ActivityLogger::log([
            'user_id' => $user->id,
            'type' => 'export',
            'module' => 'goal_revenue_ledger',
            'action' => 'download_report',
            'details' => [
                'report' => $this->resolveReportKey($reportKey),
                'format' => strtolower($format),
                'rows' => $rowCount,
                'fy' => $filters['fy'] ?? null,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
                'region' => $filters['region'] ?? null,
                'cluster' => $filters['cluster'] ?? null,
                'branch' => $filters['branch'] ?? null,
                'owner' => $filters['owner'] ?? null,
            ],
        ]);
    }

    protected function canSeeCustomerContact(User $user): bool
    {
        return $user->hasCrmPermission('edit_case_workspace')
            || $user->hasCrmPermission('manage_users_guard')
            || $user->hasCrmPermission('view_audit_logs_guard');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function periodLabel(array $filters): string
    {
        $from = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->format('d M Y') : '';
        $to = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->format('d M Y') : '';

        return trim($from.($from && $to ? ' – ' : '').$to);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<string>
     */
    protected function filterLabels(array $filters): array
    {
        return array_values(array_filter([
            ! empty($filters['fy_label']) ? 'FY: '.$filters['fy_label'] : null,
            $this->periodLabel($filters) !== '' ? 'Period: '.$this->periodLabel($filters) : null,
            ($filters['entry_type'] ?? '') !== '' ? 'Type: '.ucfirst((string) $filters['entry_type']) : null,
            ($filters['status'] ?? '') !== '' ? 'Status: '.ucfirst(str_replace('_', ' ', (string) $filters['status'])) : null,
            ($filters['product'] ?? '') !== '' ? 'Product: '.$filters['product'] : null,
            ($filters['source'] ?? '') !== '' ? 'Source: '.$filters['source'] : null,
        ]));
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     */
    protected function entriesQuery(array $scopeIds, array $filters, bool $applyTypeStatus = true): Builder
    {
        $q = $this->ledger->baseQuery($scopeIds, $filters);
        if ($applyTypeStatus) {
            // For report exports, honor entry_type/status but ignore page tabs/cards.
            $reportFilters = $filters;
            $reportFilters['tab'] = 'all';
            $reportFilters['card'] = '';
            $q = $this->ledger->applyTabAndCard($q, $reportFilters);
        }

        return $q;
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function buildSummary(array $scopeIds, array $filters, array $meta, bool $preview): array
    {
        $base = $this->entriesQuery($scopeIds, $filters, false);
        $agg = $this->ledger->aggregateKpisAndTabs(clone $base);
        $verified = $this->ledger->verifiedDisbursementTotals($scopeIds, $filters);
        $attr = $this->ledger->attributionSummaryFast(clone $base);

        $kpis = [
            'recognized' => (float) ($agg['kpis']['recognized'] ?? 0),
            'verified_disb' => (float) ($verified['amount'] ?? 0),
            'adjustments' => (float) ($agg['kpis']['adjustments'] ?? 0),
            'reversals' => (float) ($agg['kpis']['reversals'] ?? 0),
            'net' => (float) ($agg['kpis']['net'] ?? 0),
        ];

        $branchRows = collect($attr['branch'] ?? [])->map(function ($r) use ($kpis) {
            $rev = (float) ($r['net'] ?? 0);
            $total = (float) ($kpis['net'] ?: 0);

            return [
                $r['label'] ?? '—',
                $this->num((float) ($r['disbursed'] ?? 0)),
                $this->num($rev),
                $total > 0 ? round(($rev / $total) * 100, 2).'%' : '0%',
            ];
        })->values()->all();

        $monthRows = $this->monthlyTrend($scopeIds, $filters);

        return [
            'meta' => $meta,
            'kpis' => $kpis,
            'columns' => ['Branch', 'Disbursement Amount', 'Recognized Revenue', 'Contribution %'],
            'rows' => $branchRows,
            'totals' => [
                'Disbursement Amount' => $this->num(array_sum(array_map(fn ($r) => (float) str_replace([',', '₹', ' '], '', (string) ($r[1] ?? 0)), $branchRows))),
                'Recognized Revenue' => $this->num($kpis['net']),
            ],
            'charts' => [
                'monthly' => $monthRows,
                'branch' => collect($attr['branch'] ?? [])->take(6)->map(fn ($r) => [
                    'label' => $r['label'] ?? '—',
                    'value' => (float) ($r['net'] ?? 0),
                ])->values()->all(),
            ],
            'sheets' => [
                ['title' => 'Summary', 'headings' => ['Metric', 'Amount'], 'rows' => [
                    ['Recognized Revenue', $this->num($kpis['recognized'])],
                    ['Verified Disbursement', $this->num($kpis['verified_disb'])],
                    ['Adjustments', $this->num($kpis['adjustments'])],
                    ['Reversals', $this->num($kpis['reversals'])],
                    ['Net Revenue', $this->num($kpis['net'])],
                ]],
                ['title' => 'Branch Contribution', 'headings' => ['Branch', 'Disbursement Amount', 'Recognized Revenue', 'Contribution %'], 'rows' => $branchRows],
                ['title' => 'Monthly Trend', 'headings' => ['Month', 'Recognized', 'Adjustments', 'Reversals', 'Net'], 'rows' => array_map(fn ($m) => [
                    $m['label'], $this->num($m['recognized']), $this->num($m['adjustments']), $this->num($m['reversals']), $this->num($m['net']),
                ], $monthRows)],
            ],
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function buildLedger(array $scopeIds, array $filters, array $meta, bool $canSeeContact, bool $preview): array
    {
        $limit = $preview ? 200 : 8000;
        $entries = $this->entriesQuery($scopeIds, $filters)
            ->with([
                'loanCase:id,case_no,customer_name,mobile,product,lender_name,goal_loan_lead_id,branch_mis,goal_org_unit_id',
                'loanCase.lead:id,source',
                'disbursement:id,amount,disbursed_on,verified_at',
                'orgUnit:id,name',
                'attributedUser:id,name',
                'parentEntry:id,entry_ref',
            ])
            ->latest('id')
            ->limit($limit)
            ->get();

        $columns = [
            'Entry ID', 'Case Reference', 'LAN Number', 'Customer Name',
            'Customer Contact', 'Product / Sub Product', 'Bank Name', 'Banker Name',
            'Branch', 'Employee', 'Disbursement Reference', 'Disbursement Date',
            'Disbursed Amount', 'PayIn %', 'PayIn Amount', 'Payout %', 'Payout Amount',
            'Recognized Revenue', 'Entry Type', 'Adjustment / Reversal Reference',
            'Entry Status', 'Posting Date',
        ];

        $rows = [];
        $recognized = 0.0;
        $adjustments = 0.0;
        $reversals = 0.0;
        foreach ($entries as $e) {
            $case = $e->loanCase;
            $mis = $case
                ? $this->branchMis->apply($case, is_array($case->branch_mis) ? $case->branch_mis : [])
                : [];
            $type = (string) ($e->entry_type ?: 'recognized');
            $amt = (float) $e->amount;
            if ($type === 'adjustment') {
                $adjustments += $amt;
            } elseif ($type === 'reversal') {
                $reversals += $amt;
            } else {
                $recognized += $amt;
            }

            $rows[] = [
                $e->entry_ref ?: ('RV-'.$e->id),
                $case?->case_no,
                $mis['lan_number'] ?? ($mis['lan'] ?? ''),
                $case?->customer_name,
                $canSeeContact ? ($case?->mobile ?: '') : '••••',
                $case?->product,
                $case?->lender_name ?: ($mis['bank_name'] ?? ''),
                $mis['banker_name'] ?? '',
                $e->orgUnit?->name,
                $e->attributedUser?->name,
                $e->disbursement ? ('DSB-'.str_pad((string) $e->disbursement->id, 4, '0', STR_PAD_LEFT)) : '',
                optional($e->disbursement?->disbursed_on ?? $e->disbursement?->verified_at)->format('d M Y'),
                $this->num((float) ($e->disbursement?->amount ?? 0)),
                $mis['payin_pct'] ?? '',
                $this->num((float) ($mis['payin_amt'] ?? 0)),
                $mis['payout_pct'] ?? '',
                $this->num((float) ($mis['payout_amount'] ?? 0)),
                $this->num($amt),
                $e->typeLabel(),
                $e->parentEntry?->entry_ref ?: '',
                $e->statusLabel(),
                optional($e->posted_at ?? $e->created_at)->format('d M Y H:i'),
            ];
        }

        return [
            'meta' => $meta,
            'kpis' => [
                'recognized' => $recognized,
                'adjustments' => $adjustments,
                'reversals' => $reversals,
                'net' => $recognized + $adjustments + $reversals,
                'entries' => count($rows),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'sheets' => [
                ['title' => 'Detailed Ledger', 'headings' => $columns, 'rows' => $rows],
            ],
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function buildEmployee(array $scopeIds, array $filters, array $meta, bool $preview): array
    {
        $attr = $this->ledger->attributionSummaryFast($this->entriesQuery($scopeIds, $filters, false));
        $targets = $this->targetsByUser($filters);
        $columns = ['Employee', 'Disbursements', 'Disbursed Amount', 'Revenue', 'Target', 'Achievement %'];
        $rows = [];
        foreach ($attr['employee'] ?? [] as $r) {
            $userId = (int) str_replace('u', '', (string) ($r['key'] ?? '0'));
            $rev = (float) ($r['net'] ?? 0);
            $target = (float) ($targets[$userId]['effective'] ?? 0);
            $ach = $target > 0 ? round(($rev / $target) * 100, 2) : null;
            $rows[] = [
                $r['label'] ?? 'Unassigned',
                (int) ($r['disbursements'] ?? $r['entries'] ?? 0),
                $this->num((float) ($r['disbursed'] ?? 0)),
                $this->num($rev),
                $target > 0 ? $this->num($target) : '—',
                $ach !== null ? $ach.'%' : '—',
            ];
        }

        return [
            'meta' => $meta,
            'kpis' => [
                'employees' => count($rows),
                'revenue' => (float) ($attr['company']['net'] ?? 0),
                'disbursed' => (float) ($attr['company']['disbursed'] ?? 0),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'sheets' => [
                ['title' => 'Employee Revenue', 'headings' => $columns, 'rows' => $rows],
            ],
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function buildOrg(array $scopeIds, array $filters, array $meta, bool $preview): array
    {
        $attr = $this->ledger->attributionSummaryFast($this->entriesQuery($scopeIds, $filters, false));
        $netTotal = (float) ($attr['company']['net'] ?? 0);
        $map = function (array $list) use ($netTotal) {
            return collect($list)->map(function ($r) use ($netTotal) {
                $rev = (float) ($r['net'] ?? 0);

                return [
                    $r['label'] ?? '—',
                    $this->num((float) ($r['disbursed'] ?? 0)),
                    $this->num($rev),
                    $netTotal > 0 ? round(($rev / $netTotal) * 100, 2).'%' : '0%',
                ];
            })->values()->all();
        };

        $columns = ['Unit', 'Disbursement', 'Recognized Revenue', 'Contribution %'];
        $branchRows = $map($attr['branch'] ?? []);
        $clusterRows = $map($attr['cluster'] ?? []);
        $regionRows = $map($attr['region'] ?? []);

        return [
            'meta' => $meta,
            'kpis' => [
                'net' => $netTotal,
                'branches' => count($branchRows),
                'clusters' => count($clusterRows),
                'regions' => count($regionRows),
            ],
            'columns' => $columns,
            'rows' => $branchRows,
            'sections' => [
                ['title' => 'Branch', 'rows' => $branchRows],
                ['title' => 'Cluster', 'rows' => $clusterRows],
                ['title' => 'Region', 'rows' => $regionRows],
            ],
            'sheets' => [
                ['title' => 'Branch', 'headings' => $columns, 'rows' => $branchRows],
                ['title' => 'Cluster', 'headings' => $columns, 'rows' => $clusterRows],
                ['title' => 'Region', 'headings' => $columns, 'rows' => $regionRows],
            ],
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function buildCommercial(array $scopeIds, array $filters, array $meta, bool $preview): array
    {
        $caseIds = $this->entriesQuery($scopeIds, $filters, false)
            ->whereNotNull('goal_loan_case_id')
            ->distinct()
            ->pluck('goal_loan_case_id');

        $cases = GoalLoanCase::query()
            ->when($scopeIds === [-1], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($scopeIds !== [] && $scopeIds !== [-1], fn ($q) => $q->whereIn('goal_org_unit_id', $scopeIds))
            ->when($caseIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $caseIds))
            ->when($caseIds->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['orgUnit:id,name'])
            ->limit($preview ? 200 : 5000)
            ->get();

        $columns = [
            'Case Reference', 'Bank', 'Product', 'Disbursed Amount',
            'PayIn %', 'PayIn Amount', 'Payout %', 'Payout Amount', 'Net Revenue', 'Payout Month',
        ];
        $rows = [];
        $payin = 0.0;
        $payout = 0.0;
        $net = 0.0;
        foreach ($cases as $case) {
            $mis = $this->branchMis->apply($case, is_array($case->branch_mis) ? $case->branch_mis : []);
            $payinAmt = (float) ($mis['payin_amt'] ?? 0);
            $payoutAmt = (float) ($mis['payout_amount'] ?? 0);
            $netAmt = $payinAmt - $payoutAmt;
            $payin += $payinAmt;
            $payout += $payoutAmt;
            $net += $netAmt;
            $rows[] = [
                $case->case_no,
                $case->lender_name ?: ($mis['bank_name'] ?? ''),
                $case->product,
                $this->num((float) ($mis['disbursement_amount'] ?? $mis['disbursed_amount'] ?? 0)),
                $mis['payin_pct'] ?? '',
                $this->num($payinAmt),
                $mis['payout_pct'] ?? '',
                $this->num($payoutAmt),
                $this->num($netAmt),
                $mis['payout_month'] ?? ($mis['month'] ?? ''),
            ];
        }

        return [
            'meta' => $meta,
            'kpis' => [
                'payin' => $payin,
                'payout' => $payout,
                'net' => $net,
                'cases' => count($rows),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'sheets' => [
                ['title' => 'PayIn Payout Net', 'headings' => $columns, 'rows' => $rows],
            ],
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function buildAdjustment(array $scopeIds, array $filters, array $meta, bool $preview): array
    {
        $entries = $this->entriesQuery($scopeIds, $filters, false)
            ->whereIn('entry_type', [GoalRevenueEntry::TYPE_ADJUSTMENT, GoalRevenueEntry::TYPE_REVERSAL])
            ->with([
                'loanCase:id,case_no',
                'parentEntry:id,entry_ref,amount',
                'creator:id,name',
                'approver:id,name',
            ])
            ->latest('id')
            ->limit($preview ? 200 : 5000)
            ->get();

        $columns = [
            'Original Entry ID', 'Adjustment Entry ID', 'Case Reference', 'Entry Type',
            'Original Amount', 'Adjustment Amount', 'Reason', 'Requested By', 'Approved By', 'Date', 'Status',
        ];
        $rows = [];
        foreach ($entries as $e) {
            $rows[] = [
                $e->parentEntry?->entry_ref ?: '—',
                $e->entry_ref ?: ('RV-'.$e->id),
                $e->loanCase?->case_no,
                $e->typeLabel(),
                $this->num((float) ($e->parentEntry?->amount ?? 0)),
                $this->num((float) $e->amount),
                $e->reason ?: $e->note,
                $e->creator?->name,
                $e->approver?->name ?: $e->creator?->name,
                optional($e->posted_at ?? $e->created_at)->format('d M Y'),
                $e->statusLabel(),
            ];
        }

        return [
            'meta' => $meta,
            'kpis' => [
                'count' => count($rows),
                'amount' => (float) $entries->sum('amount'),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'sheets' => [
                ['title' => 'Adjustments Reversals', 'headings' => $columns, 'rows' => $rows],
            ],
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function buildTarget(array $scopeIds, array $filters, array $meta, bool $preview): array
    {
        $attr = $this->ledger->attributionSummaryFast($this->entriesQuery($scopeIds, $filters, false));
        $targets = GoalTarget::query()
            ->with(['user:id,name'])
            ->when($scopeIds === [-1], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($scopeIds !== [] && $scopeIds !== [-1], function ($q) use ($scopeIds) {
                $q->where(function ($b) use ($scopeIds) {
                    $b->whereIn('goal_org_unit_id', $scopeIds)
                        ->orWhereNull('goal_org_unit_id');
                });
            })
            ->limit($preview ? 200 : 5000)
            ->get();

        $revByUser = [];
        foreach ($attr['employee'] ?? [] as $r) {
            $uid = (int) str_replace('u', '', (string) ($r['key'] ?? '0'));
            $revByUser[$uid] = (float) ($r['net'] ?? 0);
        }

        $columns = [
            'Employee', 'Base Target', 'Opening Shortfall', 'Effective Target',
            'Actual Revenue', 'Closing Shortfall',
        ];
        $rows = [];
        if ($targets->isNotEmpty()) {
            foreach ($targets as $t) {
                $actual = $revByUser[(int) $t->user_id] ?? (float) $t->actual;
                $effective = $t->effectiveTarget();
                $closing = max(0, $effective - $actual);
                $rows[] = [
                    $t->user?->name ?: '—',
                    $this->num((float) $t->base_target),
                    $this->num((float) $t->opening_shortfall),
                    $this->num($effective),
                    $this->num($actual),
                    $this->num($closing),
                ];
            }
        } else {
            // Fallback: employees with revenue but no formal target rows.
            foreach ($attr['employee'] ?? [] as $r) {
                $rows[] = [
                    $r['label'] ?? '—',
                    '—',
                    '—',
                    '—',
                    $this->num((float) ($r['net'] ?? 0)),
                    '—',
                ];
            }
        }

        return [
            'meta' => $meta,
            'kpis' => [
                'targets' => count($rows),
                'actual' => (float) ($attr['company']['net'] ?? 0),
            ],
            'columns' => $columns,
            'rows' => $rows,
            'sheets' => [
                ['title' => 'Target vs Achievement', 'headings' => $columns, 'rows' => $rows],
            ],
        ];
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function buildRecon(array $scopeIds, array $filters, array $meta, bool $preview): array
    {
        $from = Carbon::parse($filters['date_from'])->startOfDay();
        $to = Carbon::parse($filters['date_to'])->endOfDay();
        $rate = (float) config('goal.revenue_rate', 0.0035);

        $disbQ = GoalDisbursement::query()
            ->with(['loanCase:id,case_no,goal_org_unit_id'])
            ->whereNotNull('verified_at')
            ->whereBetween('verified_at', [$from, $to]);

        if ($scopeIds === [-1]) {
            $disbQ->whereRaw('1 = 0');
        } elseif ($scopeIds !== []) {
            $disbQ->whereHas('loanCase', fn ($c) => $c->whereIn('goal_org_unit_id', $scopeIds));
        }

        $disbursements = $disbQ->latest('verified_at')->limit($preview ? 200 : 5000)->get();
        $revByDisb = GoalRevenueEntry::query()
            ->whereIn('goal_disbursement_id', $disbursements->pluck('id')->filter()->all() ?: [-1])
            ->where(function ($b) {
                $b->whereIn('status', [GoalRevenueEntry::STATUS_POSTED, GoalRevenueEntry::STATUS_APPROVED])
                    ->orWhereNull('status')
                    ->orWhere('status', '');
            })
            ->get()
            ->groupBy('goal_disbursement_id');

        $columns = [
            'Case Ref', 'Disbursement Ref', 'Verified Amount', 'Expected Revenue',
            'Posted Revenue', 'Difference', 'Status',
        ];
        $rows = [];
        $matched = 0;
        $missing = 0;
        foreach ($disbursements as $d) {
            $posted = (float) ($revByDisb->get($d->id)?->sum('amount') ?? 0);
            $expected = round(((float) $d->amount) * $rate, 2);
            $diff = round($expected - $posted, 2);
            $status = abs($diff) < 0.01 ? 'Matched' : ($posted <= 0 ? 'Missing Entry' : 'Mismatch');
            if ($status === 'Matched') {
                $matched++;
            } elseif ($status === 'Missing Entry') {
                $missing++;
            }
            $rows[] = [
                $d->loanCase?->case_no,
                'DSB-'.str_pad((string) $d->id, 4, '0', STR_PAD_LEFT),
                $this->num((float) $d->amount),
                $this->num($expected),
                $this->num($posted),
                $this->num($diff),
                $status,
            ];
        }

        return [
            'meta' => $meta,
            'kpis' => [
                'disbursements' => count($rows),
                'matched' => $matched,
                'missing' => $missing,
            ],
            'columns' => $columns,
            'rows' => $rows,
            'sheets' => [
                ['title' => 'Reconciliation', 'headings' => $columns, 'rows' => $rows],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{effective: float}>
     */
    protected function targetsByUser(array $filters): array
    {
        $out = [];
        foreach (GoalTarget::query()->get(['user_id', 'base_target', 'opening_shortfall', 'adjustments']) as $t) {
            $uid = (int) $t->user_id;
            if ($uid <= 0) {
                continue;
            }
            $out[$uid] = [
                'effective' => (float) $t->base_target + (float) $t->opening_shortfall + (float) $t->adjustments,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int>  $scopeIds
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    protected function monthlyTrend(array $scopeIds, array $filters): array
    {
        $from = Carbon::parse($filters['date_from'])->startOfMonth();
        $to = Carbon::parse($filters['date_to'])->endOfMonth();
        $cursor = $from->copy();
        $months = [];
        while ($cursor <= $to && count($months) < 12) {
            $mFrom = $cursor->copy()->startOfMonth();
            $mTo = $cursor->copy()->endOfMonth();
            $mf = $filters;
            $mf['date_from'] = $mFrom->toDateString();
            $mf['date_to'] = $mTo->toDateString();
            $agg = $this->ledger->aggregateKpisAndTabs($this->ledger->baseQuery($scopeIds, $mf));
            $months[] = [
                'label' => $cursor->format('M Y'),
                'recognized' => (float) ($agg['kpis']['recognized'] ?? 0),
                'adjustments' => (float) ($agg['kpis']['adjustments'] ?? 0),
                'reversals' => (float) ($agg['kpis']['reversals'] ?? 0),
                'net' => (float) ($agg['kpis']['net'] ?? 0),
            ];
            $cursor->addMonth();
        }

        return $months;
    }

    protected function num(float $n): string
    {
        return number_format($n, 2, '.', '');
    }
}
