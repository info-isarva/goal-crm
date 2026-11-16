<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Services\Goal\GoalDashboardService;
use Throwable;

class GoalAnalyticsController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!GoalDashboardService::userUsesGoalDashboard($user)) {
            abort(403, 'Unauthorized action.');
        }

        $period = request('period', 'this_month');
        $allowedPeriods = ['today', 'this_month', 'last_month', 'this_year'];
        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'this_month';
        }

        $interval = request('interval', 'weekly');
        if (!in_array($interval, ['weekly', 'monthly'], true)) {
            $interval = 'weekly';
        }

        try {
            $dash = app(GoalDashboardService::class)->build($user, $period, $interval);
            $dash['load_error'] = null;
        } catch (Throwable $e) {
            report($e);
            $dash = [
                'role_key' => 'bm',
                'role_label' => 'User',
                'scope_label' => '—',
                'badge' => 'Analytics unavailable',
                'period' => $period,
                'period_label' => ucwords(str_replace('_', ' ', $period)),
                'interval' => $interval,
                'interval_label' => $interval === 'monthly' ? 'Monthly' : 'Weekly',
                'period_options' => [
                    'today' => 'Today',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'this_year' => 'This Year',
                ],
                'sections' => [
                    'team_targets' => false,
                    'recent_cases' => false,
                    'recent_leads' => false,
                    'show_source_chart' => true,
                    'show_secondary_chart' => true,
                ],
                'kpis' => [],
                'metrics' => [
                    'disbursed_cases' => 0,
                    'pipeline_cases' => 0,
                    'total_leads_period' => 0,
                ],
                'target' => [
                    'title' => 'Revenue Target',
                    'subtitle' => '—',
                    'effective' => 0,
                    'actual' => 0,
                    'percent' => 0,
                    'remaining' => 0,
                    'unit' => 'money',
                    'snap' => [],
                ],
                'leads_by_source' => [],
                'cases_by_status' => [],
                'trend' => ['labels' => [], 'leads' => [], 'cases' => [], 'calls' => []],
                'analytics' => [
                    'trend' => [
                        'title' => 'Leads & Cases Trend',
                        'subtitle' => '—',
                        'labels' => [],
                        'series' => [],
                    ],
                    'distribution_a' => ['show' => true, 'title' => 'Leads by Source', 'items' => [], 'center_value' => 0, 'center_label' => 'Total'],
                    'distribution_b' => ['show' => true, 'title' => 'Case Status', 'items' => [], 'center_value' => 0, 'center_label' => 'Cases'],
                ],
                'actions' => [],
                'load_error' => 'Unable to load analytics data. Please try again.',
            ];
        }

        if (request()->wantsJson() || request('format') === 'json') {
            return response()->json([
                'ok' => empty($dash['load_error']),
                'error' => $dash['load_error'] ?? null,
                'period' => $dash['period'],
                'interval' => $dash['interval'],
                'target' => $dash['target'],
                'metrics' => $dash['metrics'],
                'kpis' => $dash['kpis'],
                'analytics' => $dash['analytics'],
                'leads_by_source' => $dash['leads_by_source'] ?? [],
                'cases_by_status' => $dash['cases_by_status'] ?? [],
            ]);
        }

        return view('goal.analytics', compact('dash', 'user'));
    }
}
