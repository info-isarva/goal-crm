<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\DailyReport;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\OutreachType;
use App\Models\UserMonthlySales;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Sales Report — period-filtered matrix + revenue status + charts.
 */
class ConsolidatedSalesReportController extends SalesMarketingDailyReportController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $canAccess = (int) ($user->crm_role_type ?? -1) === 0
            || $user->hasCrmPermission('consolidated_sales_report_guard');

        if (!$canAccess) {
            abort(403, 'Unauthorized action.');
        }

        [$startYmd, $endYmd, $period] = ReportController::resolveReportPeriod($request, 'this_month');
        $rangeStart = Carbon::parse($startYmd)->startOfDay();
        $rangeEnd = Carbon::parse($endYmd)->endOfDay();

        $data = $this->buildConsolidatedData($user, $rangeStart, $rangeEnd, $request->query('user_id'));

        return view('reports.consolidated_sales', array_merge($data, [
            'period' => $period,
            'start' => $startYmd,
            'end' => $endYmd,
            'periodText' => ReportController::periodText($period, $startYmd, $endYmd),
        ]));
    }

    public function excel(Request $request)
    {
        $user = auth()->user();
        $canAccess = (int) ($user->crm_role_type ?? -1) === 0
            || $user->hasCrmPermission('consolidated_sales_report_guard');

        if (!$canAccess) {
            abort(403, 'Unauthorized action.');
        }

        [$startYmd, $endYmd, $period] = ReportController::resolveReportPeriod($request, 'this_month');
        $rangeStart = Carbon::parse($startYmd)->startOfDay();
        $rangeEnd = Carbon::parse($endYmd)->endOfDay();

        $data = $this->buildConsolidatedData($user, $rangeStart, $rangeEnd, $request->query('user_id'));
        $fileName = 'Consolidated_Sales_Report_' . $rangeEnd->format('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ConsolidatedSalesReportExport($data),
            $fileName
        );
    }

    protected function buildConsolidatedData($currentUser, Carbon $rangeStart, Carbon $rangeEnd, $filterUserId = null): array
    {
        $anchor = $rangeEnd->copy();
        $year = (int) $anchor->year;
        $month = (int) $anchor->month;
        $monthLabel = $anchor->format('F - Y');
        $shortMonthLabel = $anchor->format('M Y');

        $users = $this->resolveUsers($currentUser, $filterUserId);
        $userIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();

        $dailyReports = DailyReport::query()
            ->with(['outreaches.outreachType'])
            ->whereBetween('report_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->where(function ($q) {
                $q->where('status', 'submitted')->orWhereNull('status');
            })
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->get();

        // Only days with a submitted daily report count for a user; users with none in range are hidden
        $submittedDates = $this->submittedReportDates($dailyReports);
        $users = $users->filter(fn ($member) => isset($submittedDates[(int) $member->id]))->values();
        $userIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($userIds)) {
            // ->when($userIds, ...) skips the filter on an empty array, which would return everyone's data
            $userIds = [0];
        }

        $allProductGroups = $this->buildProductGroups();
        $outreachTypes = OutreachType::query()->active()->ordered()->get();
        $marketingTypes = $outreachTypes->filter(function ($type) {
            return !preg_match('/data\s*pre\s*analysis/i', (string) $type->name);
        })->values();

        $newCallsQuery = CallLog::query()
            ->selectRaw('created_by, COUNT(*) as cnt')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->when($userIds, fn ($q) => $q->whereIn('created_by', $userIds));
        $this->scopeToSubmittedReportDays($newCallsQuery, 'call_logs.created_by', 'call_logs.created_at');
        $newCallsByUser = $newCallsQuery->groupBy('created_by')->pluck('cnt', 'created_by');

        $leadsGenQuery = Lead::query()
            ->selectRaw('user_owner_id, COUNT(*) as cnt')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Junk Lead');
            })
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds));
        $this->scopeToSubmittedReportDays($leadsGenQuery, 'leads.user_owner_id', 'leads.created_at');
        $leadsGenByUser = $leadsGenQuery->groupBy('user_owner_id')->pluck('cnt', 'user_owner_id');

        $followUpsByUser = $this->followUpCounts($userIds, $rangeStart, $rangeEnd, $submittedDates);

        $callLogsQuery = CallLog::query()
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->when($userIds, fn ($q) => $q->whereIn('created_by', $userIds));
        $this->scopeToSubmittedReportDays($callLogsQuery, 'call_logs.created_by', 'call_logs.created_at');
        $callLogs = $callLogsQuery->get(['id', 'created_by', 'type']);

        $productCountsByUser = [];
        foreach ($userIds as $uid) {
            $productCountsByUser[$uid] = $this->emptyProductCounts($allProductGroups);
        }
        foreach ($callLogs as $log) {
            $uid = (int) $log->created_by;
            if (!isset($productCountsByUser[$uid])) {
                continue;
            }
            $haystack = $this->normalizeMatchText((string) ($log->type ?? ''));
            $matched = $this->matchProductColumn($haystack, $allProductGroups);
            if ($matched) {
                [$groupKey, $colKey] = $matched;
                $productCountsByUser[$uid][$groupKey][$colKey]++;
            }
        }

        $productGroups = $allProductGroups;

        $marketingByUser = [];
        foreach ($userIds as $uid) {
            $marketingByUser[$uid] = [];
            foreach ($marketingTypes as $type) {
                $marketingByUser[$uid][$type->id] = 0;
            }
        }
        $convertedQuery = Lead::query()
            ->selectRaw('user_owner_id, COUNT(*) as cnt')
            ->whereNotNull('converted_at')
            ->whereBetween('converted_at', [$rangeStart, $rangeEnd])
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds));
        $this->scopeToSubmittedReportDays($convertedQuery, 'leads.user_owner_id', 'leads.converted_at');
        $convertedByUser = $convertedQuery->groupBy('user_owner_id')->pluck('cnt', 'user_owner_id');

        $dataPreType = $outreachTypes->first(function ($type) {
            return preg_match('/data\s*pre\s*analysis/i', (string) $type->name);
        });

        $dataPreByUser = array_fill_keys($userIds, 0);
        foreach ($dailyReports as $report) {
            $uid = (int) $report->user_id;
            if (!isset($marketingByUser[$uid])) {
                continue;
            }
            foreach ($report->outreaches as $row) {
                $typeId = (int) $row->outreach_type_id;
                if (isset($marketingByUser[$uid][$typeId])) {
                    $marketingByUser[$uid][$typeId] += (int) $row->count;
                }
                if ($dataPreType && $typeId === (int) $dataPreType->id) {
                    $dataPreByUser[$uid] += (int) $row->count;
                }
            }
        }

        $weekMeta = $this->weekOfMonth($anchor);
        $weekStart = $weekMeta['start'];
        $weekEnd = $weekMeta['end'];
        $weekLabel = 'Week ' . $weekMeta['number'];

        $monthlySales = UserMonthlySales::query()
            ->where('year', $year)
            ->where('month', $month)
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->get()
            ->keyBy('user_id');

        $monthAchievedQuery = Deal::query()
            ->selectRaw('user_owner_id, COALESCE(SUM(amount), 0) as total')
            ->where(function ($q) {
                $q->where('stage_id', \App\Models\Stage::closedWonId())
                    ->orWhereRaw('LOWER(status) = ?', ['closed won']);
            })
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('close_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                    ->orWhere(function ($q2) use ($rangeStart, $rangeEnd) {
                        $q2->whereNull('close_date')
                            ->whereBetween('updated_at', [$rangeStart, $rangeEnd]);
                    });
            })
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds));
        $this->scopeToSubmittedReportDays($monthAchievedQuery, 'deals.user_owner_id', 'COALESCE(deals.close_date, deals.updated_at)');
        $monthAchievedFromDeals = $monthAchievedQuery->groupBy('user_owner_id')->pluck('total', 'user_owner_id');

        $weekAchievedQuery = Deal::query()
            ->selectRaw('user_owner_id, COALESCE(SUM(amount), 0) as total')
            ->where(function ($q) {
                $q->where('stage_id', \App\Models\Stage::closedWonId())
                    ->orWhereRaw('LOWER(status) = ?', ['closed won']);
            })
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('close_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                    ->orWhere(function ($q2) use ($weekStart, $weekEnd) {
                        $q2->whereNull('close_date')
                            ->whereBetween('updated_at', [$weekStart, $weekEnd]);
                    });
            })
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds));
        $this->scopeToSubmittedReportDays($weekAchievedQuery, 'deals.user_owner_id', 'COALESCE(deals.close_date, deals.updated_at)');
        $weekAchievedByUser = $weekAchievedQuery->groupBy('user_owner_id')->pluck('total', 'user_owner_id');

        // Open pipeline amount (not closed won / lost)
        $pipelineByUser = Deal::query()
            ->selectRaw('user_owner_id, COALESCE(SUM(amount), 0) as total')
            ->where(function ($q) {
                $q->whereNotIn('stage_id', array_filter([\App\Models\Stage::closedWonId(), \App\Models\Stage::closedLostId()]))
                    ->whereRaw('LOWER(COALESCE(status, "")) NOT IN (?, ?)', ['closed won', 'closed lost']);
            })
            ->when(
                Schema::hasColumn('deals', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at')
            )
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds))
            ->groupBy('user_owner_id')
            ->pluck('total', 'user_owner_id');

        // Estimated revenue current week = week target (planned) or open deals closing this week + week achieved
        $estWeekOpenByUser = Deal::query()
            ->selectRaw('user_owner_id, COALESCE(SUM(amount), 0) as total')
            ->where(function ($q) {
                $q->whereNotIn('stage_id', array_filter([\App\Models\Stage::closedWonId(), \App\Models\Stage::closedLostId()]))
                    ->whereRaw('LOWER(COALESCE(status, "")) NOT IN (?, ?)', ['closed won', 'closed lost']);
            })
            ->whereBetween('close_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->when(
                Schema::hasColumn('deals', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at')
            )
            ->when($userIds, fn ($q) => $q->whereIn('user_owner_id', $userIds))
            ->groupBy('user_owner_id')
            ->pluck('total', 'user_owner_id');

        $metricRows = [];
        $metricRows[] = ['key' => 'follow_ups', 'label' => 'Follow Ups'];
        $metricRows[] = ['key' => 'new_calls', 'label' => 'New Calls'];
        foreach ($marketingTypes as $type) {
            $metricRows[] = ['key' => 'mkt_' . $type->id, 'label' => $type->name, 'type' => 'marketing', 'id' => $type->id];
        }
        $metricRows[] = ['key' => 'leads_gen', 'label' => 'Leads Gen'];
        $metricRows[] = ['key' => 'leads_converted', 'label' => 'Lead Convert'];
        $metricRows[] = ['key' => 'data_pre_analysis', 'label' => 'Data Pre Analysis'];
        foreach ($productGroups as $group) {
            foreach ($group['columns'] as $colKey => $col) {
                $metricRows[] = [
                    'key' => 'prod_' . $group['key'] . '_' . $colKey,
                    'label' => $col['label'],
                    'type' => 'product',
                    'group' => $group['key'],
                    'col' => $colKey,
                ];
            }
        }

        $rows = [];
        $totals = [
            'est_week' => 0,
            'pipeline' => 0,
            'month_achieve' => 0,
            'month_balance' => 0,
            'week_target' => 0,
            'week_achieve' => 0,
            'week_balance' => 0,
            'metrics' => [],
        ];
        foreach ($metricRows as $m) {
            $totals['metrics'][$m['key']] = 0;
        }

        foreach ($users as $member) {
            $uid = (int) $member->id;
            $monthTarget = (float) ($member->sales_target ?? 0);
            $monthAchieve = max((float) ($monthlySales->get($uid)->achieved_sales ?? 0), (float) ($monthAchievedFromDeals[$uid] ?? 0));
            $monthBalance = max(0, $monthTarget - $monthAchieve);
            $weekTarget = $monthTarget > 0 ? round($monthTarget / 4, 2) : 0;
            $weekAchieve = (float) ($weekAchievedByUser[$uid] ?? 0);
            $weekBalance = max(0, $weekTarget - $weekAchieve);
            $pipeline = (float) ($pipelineByUser[$uid] ?? 0);
            $estWeek = (float) ($weekAchievedByUser[$uid] ?? 0) + (float) ($estWeekOpenByUser[$uid] ?? 0);
            if ($estWeek <= 0 && $weekTarget > 0) {
                $estWeek = $weekTarget;
            }

            $metrics = [
                'follow_ups' => (int) ($followUpsByUser[$uid] ?? 0),
                'new_calls' => (int) ($newCallsByUser[$uid] ?? 0),
                'leads_gen' => (int) ($leadsGenByUser[$uid] ?? 0),
                'leads_converted' => (int) ($convertedByUser[$uid] ?? 0),
                'data_pre_analysis' => (int) ($dataPreByUser[$uid] ?? 0),
            ];
            foreach ($marketingTypes as $type) {
                $metrics['mkt_' . $type->id] = (int) ($marketingByUser[$uid][$type->id] ?? 0);
            }
            foreach ($productGroups as $group) {
                foreach (array_keys($group['columns']) as $colKey) {
                    $metrics['prod_' . $group['key'] . '_' . $colKey] = (int) ($productCountsByUser[$uid][$group['key']][$colKey] ?? 0);
                }
            }

            foreach ($metrics as $k => $v) {
                $totals['metrics'][$k] = ($totals['metrics'][$k] ?? 0) + $v;
            }

            $totals['est_week'] += $estWeek;
            $totals['pipeline'] += $pipeline;
            $totals['month_achieve'] += $monthAchieve;
            $totals['month_balance'] += $monthBalance;
            $totals['week_target'] += $weekTarget;
            $totals['week_achieve'] += $weekAchieve;
            $totals['week_balance'] += $weekBalance;

            $rows[] = [
                'user' => $member,
                'est_week' => $estWeek,
                'pipeline' => $pipeline,
                'month_achieve' => $monthAchieve,
                'month_balance' => $monthBalance,
                'week_target' => $weekTarget,
                'week_achieve' => $weekAchieve,
                'week_balance' => $weekBalance,
                'metrics' => $metrics,
            ];
        }

        // Filter metric rows to show only those where at least one user has related data count (> 0)
        $visibleMetricRows = [];
        foreach ($metricRows as $m) {
            $val = (int) ($totals['metrics'][$m['key']] ?? 0);
            if ($val > 0) {
                $visibleMetricRows[] = $m;
            }
        }
        $metricRows = $visibleMetricRows;

        // Chart datasets
        $chartLabels = $users->pluck('name')->values()->all();
        $chartEstWeek = [];
        $chartPipeline = [];
        foreach ($rows as $r) {
            $chartEstWeek[] = round($r['est_week'], 2);
            $chartPipeline[] = round($r['pipeline'], 2);
        }

        $pieLabels = [];
        $pieValues = [];
        foreach ($metricRows as $m) {
            $val = (int) ($totals['metrics'][$m['key']] ?? 0);
            if ($val > 0) {
                $pieLabels[] = $m['label'];
                $pieValues[] = $val;
            }
        }

        return [
            'monthLabel' => $monthLabel,
            'shortMonthLabel' => $shortMonthLabel,
            'weekLabel' => $weekLabel,
            'users' => $users,
            'rows' => $rows,
            'totals' => $totals,
            'metricRows' => $metricRows,
            'chartLabels' => $chartLabels,
            'chartEstWeek' => $chartEstWeek,
            'chartPipeline' => $chartPipeline,
            'pieLabels' => $pieLabels,
            'pieValues' => $pieValues,
        ];
    }
}
