<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\Organization;
use App\Models\Customer;
use App\Models\Person;
use App\Models\Meeting;
use App\Models\Task;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $period = request('period', 'this_month');
        $allowedPeriods = ['this_month', 'last_month', '6_months', 'year', 'previous_year'];
        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'this_month';
        }

        $periodMeta = $this->resolveDashboardPeriod($period);
        $rangeStart = $periodMeta['start'];
        $rangeEnd = $periodMeta['end'];
        $compareStart = $periodMeta['compare_start'];
        $compareEnd = $periodMeta['compare_end'];
        $periodLabel = $periodMeta['label'];
        $compareLabel = $periodMeta['compare_label'];
        $periodOptions = [
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            '6_months' => '6 Months',
            'year' => 'Year',
            'previous_year' => 'Previous Year',
        ];

        // Anchor month/year for single-month widgets (this/last month)
        $year = (int) $rangeStart->format('Y');
        $month = (int) $rangeStart->format('n');
        $monthCount = max(1, (int) $rangeStart->copy()->startOfMonth()->diffInMonths($rangeEnd->copy()->startOfMonth()) + 1);

        $company = \App\Models\Company::first();
        $currency_symbol = $company ? $company->currency_symbol : config('app.currency_symbol', '₹');

        // User sales for selected period
        if (in_array($period, ['this_month', 'last_month'], true)) {
            $sales = \App\Models\UserMonthlySales::where('user_id', $user->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();
            $user_target = $user->sales_target ?? 0;
        } else {
            $achieved = \App\Models\UserMonthlySales::where('user_id', $user->id)
                ->where(function ($q) use ($rangeStart, $rangeEnd) {
                    $q->whereRaw('(year * 100 + month) >= ?', [(int) $rangeStart->format('Ym')])
                        ->whereRaw('(year * 100 + month) <= ?', [(int) $rangeEnd->format('Ym')]);
                })
                ->sum('achieved_sales');
            $sales = (object) ['achieved_sales' => $achieved];
            $user_target = ($user->sales_target ?? 0) * $monthCount;
        }
        $salesPeriodLabel = $periodMeta['short_label'];

        $isAdminOrManager = ($user->crm_role_type === 0 || $user->crm_role_type === 1 || $user->crm_role_type === 2);

        $scopeOwner = function ($query) use ($user, $isAdminOrManager) {
            if (!$isAdminOrManager) {
                $query->where(function ($q) use ($user) {
                    $q->where('user_owner_id', $user->id)
                        ->orWhere('created_by', $user->id);
                });
            }
            return $query;
        };

        $scopeOwnerColumn = function ($query, $column = 'user_owner_id') use ($user, $isAdminOrManager) {
            if (!$isAdminOrManager) {
                $query->where($column, $user->id);
            }
            return $query;
        };

        // KPI counts for selected period
        $totalLeads = $scopeOwner(Lead::query())
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->count();

        $totalDeals = $scopeOwner(Deal::query())
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->count();

        $dealsCreatedThisMonth = $scopeOwnerColumn(Deal::query())
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->count();
        $dealsCreatedLastMonth = $scopeOwnerColumn(Deal::query())
            ->whereBetween('created_at', [$compareStart, $compareEnd])
            ->count();

        $dealsCreatedChangePercent = $this->percentChange($dealsCreatedThisMonth, $dealsCreatedLastMonth);

        $dealsClosingThisMonth = $scopeOwnerColumn(Deal::query())
            ->whereBetween('close_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->count();
        $dealsClosingLastMonth = $scopeOwnerColumn(Deal::query())
            ->whereBetween('close_date', [$compareStart->toDateString(), $compareEnd->toDateString()])
            ->count();

        $dealsClosingChangePercent = $this->percentChange($dealsClosingThisMonth, $dealsClosingLastMonth);

        // Lists / secondary widgets (keep day-based lists as today; closing list follows period)
        $dealsClosingFilter = request('deals_closing_filter', 'month');
        $dealsQuery = $scopeOwnerColumn(Deal::query());
        if ($dealsClosingFilter === 'today') {
            $dealsQuery->whereDate('close_date', now()->toDateString());
        } elseif ($dealsClosingFilter === 'week') {
            $dealsQuery->whereBetween('close_date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dealsClosingFilter === '30days') {
            $dealsQuery->whereBetween('close_date', [now()->toDateString(), now()->addDays(30)->toDateString()]);
        } else {
            $dealsQuery->whereBetween('close_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);
        }
        $dealsClosingThisMonthList = $dealsQuery->with(['organization', 'customer', 'person', 'owner'])
            ->orderBy('close_date', 'asc')
            ->paginate(3, ['*'], 'deals_closing_month_page');

        if ($isAdminOrManager) {
            $meetings = Meeting::whereDate('start_at', '>=', now()->toDateString())
                ->orderBy('start_at')
                ->with(['owner', 'person'])
                ->paginate(3, ['*'], 'meetings_page');
            $tasks = Task::whereDate('due_at', '>=', now()->toDateString())
                ->orderBy('due_at')
                ->with(['owner'])
                ->paginate(3, ['*'], 'tasks_page');
            $dealsToday = Deal::whereDate('created_at', now()->toDateString())
                ->with(['organization', 'customer', 'person', 'owner'])
                ->paginate(3, ['*'], 'deals_page');
            $leadsToday = Lead::whereDate('created_at', now()->toDateString())
                ->with(['organization', 'customer', 'person', 'owner'])
                ->paginate(3, ['*'], 'leads_page');
        } else {
            $meetings = Meeting::where('created_by', $user->id)
                ->whereDate('start_at', '>=', now()->toDateString())
                ->orderBy('start_at')
                ->with(['owner', 'person'])
                ->paginate(3, ['*'], 'meetings_page');
            $tasks = Task::where('created_by', $user->id)
                ->whereDate('due_at', '>=', now()->toDateString())
                ->orderBy('due_at')
                ->with(['owner'])
                ->paginate(3, ['*'], 'tasks_page');
            $dealsToday = Deal::where('created_by', $user->id)
                ->whereDate('created_at', now()->toDateString())
                ->with(['organization', 'customer', 'person', 'owner'])
                ->paginate(3, ['*'], 'deals_page');
            $leadsToday = Lead::where('created_by', $user->id)
                ->whereDate('created_at', now()->toDateString())
                ->with(['organization', 'customer', 'person', 'owner'])
                ->paginate(3, ['*'], 'leads_page');
        }

        // Chart series for selected period
        $chartData = $this->buildPeriodChartSeries($period, $rangeStart, $rangeEnd, $user, $isAdminOrManager);
        $chartLabels = $chartData['labels'];
        $leadsAnalyticsSeries = $chartData['leads'];
        $dealsAnalyticsSeries = $chartData['deals'];
        // Keep legacy month-keyed maps for compatibility
        $leadsAnalytics = collect($leadsAnalyticsSeries)->values();
        $dealsAnalytics = collect($dealsAnalyticsSeries)->values();

        $leadsLast15Days = $scopeOwnerColumn(Lead::query(), 'user_owner_id')
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->mapWithKeys(function ($count, $date) {
                return [Carbon::parse($date)->format('d/m/Y') => $count];
            });

        $dealsLast15Days = $scopeOwnerColumn(Deal::query(), 'user_owner_id')
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->mapWithKeys(function ($count, $date) {
                return [Carbon::parse($date)->format('d/m/Y') => $count];
            });

        // Leads by source for selected period
        $leadsBySourceQuery = Lead::leftJoin('lead_sources', 'leads.lead_source', '=', 'lead_sources.id')
            ->whereBetween('leads.created_at', [$rangeStart, $rangeEnd]);
        if (!$isAdminOrManager) {
            $leadsBySourceQuery->where('leads.user_owner_id', $user->id);
        }
        $leadsBySource = $leadsBySourceQuery
            ->selectRaw("COALESCE(lead_sources.name, 'Unknown') as source, COUNT(*) as count")
            ->groupBy('source')
            ->orderByDesc('count')
            ->pluck('count', 'source')
            ->toArray();

        $palette = [
            '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f', '#edc949', '#af7aa1', '#ff9da7', '#9c755f', '#bab0ac'
        ];
        $leadSourceColors = [];
        $i = 0;
        foreach ($leadsBySource as $sourceName => $count) {
            $leadSourceColors[$sourceName] = $palette[$i % count($palette)];
            $i++;
        }

        // Employee targets for selected period (admin)
        $employeeTargets = null;
        if ($user->crm_role_type === 0 || $user->crm_role_type === 1) {
            $employees = \App\Models\User::whereNotIn('crm_role_type', [0, 1])
                ->where('status', 1)
                ->orderBy('name')
                ->get();

            $employeeTargets = [];
            foreach ($employees as $emp) {
                if (in_array($period, ['this_month', 'last_month'], true)) {
                    $monthlySales = \App\Models\UserMonthlySales::where('user_id', $emp->id)
                        ->where('year', $year)
                        ->where('month', $month)
                        ->first();
                    $achievedSales = $monthlySales ? $monthlySales->achieved_sales : 0;
                    $targetAmount = $emp->sales_target ?? 0;
                } else {
                    $achievedSales = \App\Models\UserMonthlySales::where('user_id', $emp->id)
                        ->whereRaw('(year * 100 + month) >= ?', [(int) $rangeStart->format('Ym')])
                        ->whereRaw('(year * 100 + month) <= ?', [(int) $rangeEnd->format('Ym')])
                        ->sum('achieved_sales');
                    $targetAmount = ($emp->sales_target ?? 0) * $monthCount;
                }

                $employeeTargets[] = [
                    'name' => $emp->name,
                    'target_amount' => $targetAmount,
                    'achieved_sales' => $achievedSales,
                    'progress' => $targetAmount > 0
                        ? round($achievedSales / $targetAmount * 100, 2)
                        : 0,
                ];
            }
        }

        $todaysTaskReminders = Task::where('created_by', $user->id)
            ->whereDate('due_at', now()->toDateString())
            ->whereHas('reminders', function ($query) {
                $query->whereDate('remind_at', now()->toDateString());
            })
            ->with(['reminders', 'owner'])
            ->get();

        $dealsCreatedTitle = 'Deals Created (' . $periodLabel . ')';
        $dealsClosingTitle = 'Deals Closing (' . $periodLabel . ')';
        $leadsBySourceTitle = 'Leads by Source (' . $periodLabel . ')';
        $leadsDealsChartTitle = 'Leads & Deals (' . $periodLabel . ')';
        $employeeTargetsPeriodLabel = $periodMeta['short_label'];

        return view('dashboard-2', compact(
            'todaysTaskReminders',
            'totalLeads',
            'totalDeals',
            'dealsCreatedThisMonth',
            'dealsCreatedChangePercent',
            'dealsClosingThisMonth',
            'dealsClosingChangePercent',
            'leadsToday',
            'dealsToday',
            'dealsClosingThisMonthList',
            'meetings',
            'tasks',
            'leadsAnalytics',
            'dealsAnalytics',
            'leadsAnalyticsSeries',
            'dealsAnalyticsSeries',
            'chartLabels',
            'leadsLast15Days',
            'dealsLast15Days',
            'leadsBySource',
            'leadSourceColors',
            'user',
            'sales',
            'currency_symbol',
            'user_target',
            'salesPeriodLabel',
            'employeeTargets',
            'year',
            'month',
            'period',
            'periodOptions',
            'periodLabel',
            'compareLabel',
            'dealsCreatedTitle',
            'dealsClosingTitle',
            'leadsBySourceTitle',
            'leadsDealsChartTitle',
            'employeeTargetsPeriodLabel'
        ));
    }

    /**
     * Resolve date ranges and labels for dashboard period filter.
     */
    protected function resolveDashboardPeriod(string $period): array
    {
        switch ($period) {
            case 'last_month':
                $start = now()->subMonthNoOverflow()->startOfMonth();
                $end = now()->subMonthNoOverflow()->endOfMonth();
                $compareStart = now()->subMonthsNoOverflow(2)->startOfMonth();
                $compareEnd = now()->subMonthsNoOverflow(2)->endOfMonth();
                return [
                    'start' => $start,
                    'end' => $end,
                    'compare_start' => $compareStart,
                    'compare_end' => $compareEnd,
                    'label' => 'Last Month',
                    'short_label' => $start->format('F Y'),
                    'compare_label' => 'vs prior month',
                ];
            case '6_months':
                $start = now()->subMonthsNoOverflow(5)->startOfMonth();
                $end = now()->endOfDay();
                $compareStart = now()->subMonthsNoOverflow(11)->startOfMonth();
                $compareEnd = now()->subMonthsNoOverflow(6)->endOfMonth();
                return [
                    'start' => $start,
                    'end' => $end,
                    'compare_start' => $compareStart,
                    'compare_end' => $compareEnd,
                    'label' => 'Last 6 Months',
                    'short_label' => $start->format('M Y') . ' – ' . now()->format('M Y'),
                    'compare_label' => 'vs previous 6 months',
                ];
            case 'year':
                $start = now()->startOfYear();
                $end = now()->endOfDay();
                $compareStart = now()->subYear()->startOfYear();
                $compareEnd = now()->subYear()->endOfYear();
                return [
                    'start' => $start,
                    'end' => $end,
                    'compare_start' => $compareStart,
                    'compare_end' => $compareEnd,
                    'label' => 'Year ' . now()->format('Y'),
                    'short_label' => now()->format('Y'),
                    'compare_label' => 'vs last year',
                ];
            case 'previous_year':
                $start = now()->subYear()->startOfYear();
                $end = now()->subYear()->endOfYear();
                $compareStart = now()->subYears(2)->startOfYear();
                $compareEnd = now()->subYears(2)->endOfYear();
                return [
                    'start' => $start,
                    'end' => $end,
                    'compare_start' => $compareStart,
                    'compare_end' => $compareEnd,
                    'label' => 'Previous Year (' . $start->format('Y') . ')',
                    'short_label' => $start->format('Y'),
                    'compare_label' => 'vs prior year',
                ];
            case 'this_month':
            default:
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
                $compareStart = now()->subMonthNoOverflow()->startOfMonth();
                $compareEnd = now()->subMonthNoOverflow()->endOfMonth();
                return [
                    'start' => $start,
                    'end' => $end,
                    'compare_start' => $compareStart,
                    'compare_end' => $compareEnd,
                    'label' => 'This Month',
                    'short_label' => $start->format('F Y'),
                    'compare_label' => 'vs last month',
                ];
        }
    }

    protected function percentChange(int $current, int $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }
        if ($current > 0) {
            return 100.0;
        }
        return 0.0;
    }

    /**
     * Build chart labels + lead/deal counts for the selected period.
     */
    protected function buildPeriodChartSeries(string $period, Carbon $rangeStart, Carbon $rangeEnd, $user, bool $isAdminOrManager): array
    {
        $labels = [];
        $leads = [];
        $deals = [];

        $applyOwner = function ($query) use ($user, $isAdminOrManager) {
            if (!$isAdminOrManager) {
                $query->where('user_owner_id', $user->id);
            }
            return $query;
        };

        if (in_array($period, ['this_month', 'last_month'], true)) {
            $leadRows = $applyOwner(Lead::query())
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->selectRaw('DATE(created_at) as bucket, COUNT(*) as count')
                ->groupBy('bucket')
                ->pluck('count', 'bucket');

            $dealRows = $applyOwner(Deal::query())
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->selectRaw('DATE(created_at) as bucket, COUNT(*) as count')
                ->groupBy('bucket')
                ->pluck('count', 'bucket');

            $cursor = $rangeStart->copy()->startOfDay();
            $endDay = $rangeEnd->copy()->startOfDay();
            while ($cursor->lte($endDay)) {
                $key = $cursor->toDateString();
                $labels[] = $cursor->format('d M');
                $leads[] = (int) ($leadRows[$key] ?? 0);
                $deals[] = (int) ($dealRows[$key] ?? 0);
                $cursor->addDay();
            }
        } else {
            $leadRows = $applyOwner(Lead::query())
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as count')
                ->groupBy('y', 'm')
                ->get()
                ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->y, $row->m));

            $dealRows = $applyOwner(Deal::query())
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as count')
                ->groupBy('y', 'm')
                ->get()
                ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->y, $row->m));

            $cursor = $rangeStart->copy()->startOfMonth();
            $endMonth = $rangeEnd->copy()->startOfMonth();
            while ($cursor->lte($endMonth)) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('M y');
                $leads[] = (int) (optional($leadRows->get($key))->count ?? 0);
                $deals[] = (int) (optional($dealRows->get($key))->count ?? 0);
                $cursor->addMonthNoOverflow();
            }
        }

        return [
            'labels' => $labels,
            'leads' => $leads,
            'deals' => $deals,
        ];
    }

    /**
     * Show current user's sales target and achieved sales for current month on dashboard.
     */
    public function userSalesWidget()
    {
        $user = auth()->user();
        $year = date('Y');
        $month = date('n');
        $sales = \App\Models\UserMonthlySales::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        $company = \App\Models\Company::first();
        $currency_symbol = $company ? $company->currency_symbol : config('app.currency_symbol', '₹');
        // Always fetch target from user table for current month
        $user_target = $user->sales_target ?? 0;
        return view('dashboard.user_sales_widget', compact('sales', 'currency_symbol', 'user_target'));
    }

    /**
     * Show all previous months' sales target and achieved sales for current user.
     */
    public function userSalesHistory()
    {
        $user = auth()->user();
        $salesHistory = \App\Models\UserMonthlySales::where('user_id', $user->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
        $company = \App\Models\Company::first();
        $currency_symbol = $company ? $company->currency_symbol : config('app.currency_symbol', '₹');
        return view('dashboard.user_sales_history', compact('salesHistory', 'currency_symbol'));
    }

    /**
     * Show all employees' current month target list (admin only).
     * Displays: name, target amount, and achieved sales.
     */
    public function employeeTargetsDashboard()
    {
        $user = auth()->user();
        
        // Admin-only authorization check
        if ($user->crm_role_type !== 0 && $user->crm_role_type !== 1) {
            abort(403, 'Unauthorized. This dashboard is for admins only.');
        }

        $year = date('Y');
        $month = date('n');

        // Get all users (except superadmin)
        $employees = \App\Models\User::whereNotIn('crm_role_type', [0, 1])
        ->where('status', 1)
        ->orderBy('name')
        ->get();

        
        // Build the target list with achieved sales
        $employeeTargets = [];
        foreach ($employees as $emp) {
            // Get current month's achieved sales
            $monthlySales = \App\Models\UserMonthlySales::where('user_id', $emp->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            $employeeTargets[] = [
                'user_id' => $emp->id,
                'name' => $emp->name,
                'target_amount' => $emp->sales_target ?? 0,
                'achieved_sales' => $monthlySales ? $monthlySales->achieved_sales : 0,
                'progress' => $emp->sales_target > 0 
                    ? round(($monthlySales ? $monthlySales->achieved_sales : 0) / $emp->sales_target * 100, 2)
                    : 0,
            ];
        }

        // Get company details for currency symbol
        $company = \App\Models\Company::first();
        $currency_symbol = $company ? $company->currency_symbol : config('app.currency_symbol', '₹');

        // Get current month name for display
        $monthName = \Carbon\Carbon::now()->month((int)$month)->format('F Y');

        return view('dashboard.employee_targets_dashboard', compact(
            'employeeTargets',
            'currency_symbol',
            'monthName',
            'year',
            'month'
        ));
    }
}
