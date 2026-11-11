<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\User;
use App\Models\UserAnalyticsDashboard;
use App\Models\LeadSource;
use App\Models\Stage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardController extends Controller
{
    /**
     * Render the main dashboard view.
     */
    public function index()
    {
        $this->authorizeAnalyticsDashboard();

        $company = \App\Models\Company::first();
        $currency_symbol = $company ? $company->currency_symbol : config('app.currency_symbol', '₹');
        
        // Fetch users for the owner dropdown filter (All)
        $users = User::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $savedDashboards = UserAnalyticsDashboard::where('user_id', auth()->id())
            ->get(['name', 'widgets'])
            ->mapWithKeys(fn (UserAnalyticsDashboard $dashboard) => [$dashboard->name => $dashboard->widgets])
            ->all();
        
        return view('dashboard.analytics', compact('currency_symbol', 'users', 'savedDashboards'));
    }

    /**
     * Persist the authenticated user's analytics dashboard layouts.
     */
    public function saveDashboards(Request $request)
    {
        $this->authorizeAnalyticsDashboard();

        $validated = $request->validate([
            'dashboards' => ['required', 'array'],
            'active_dashboard' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($validated): void {
            $dashboards = $validated['dashboards'];
            $userId = auth()->id();

            UserAnalyticsDashboard::where('user_id', $userId)
                ->whereNotIn('name', array_keys($dashboards))
                ->delete();

            foreach ($dashboards as $name => $widgets) {
                UserAnalyticsDashboard::updateOrCreate(
                    ['user_id' => $userId, 'name' => $name],
                    ['widgets' => $widgets]
                );
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Clone one dashboard layout for the authenticated user.
     */
    public function cloneDashboard(Request $request)
    {
        $this->authorizeAnalyticsDashboard();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'source_name' => ['required', 'string', 'max:150'],
            'widgets' => ['required', 'array'],
        ]);

        $dashboard = UserAnalyticsDashboard::firstOrNew([
            'user_id' => auth()->id(),
            'name' => trim($validated['name']),
        ]);

        if ($dashboard->exists) {
            return response()->json([
                'success' => false,
                'message' => 'A dashboard with this name already exists.',
            ], 422);
        }

        $dashboard->widgets = $validated['widgets'];
        $dashboard->save();

        return response()->json(['success' => true]);
    }

    /**
     * Fetch dynamic aggregated data for dashboard widgets.
     */
    public function getWidgetData(Request $request)
    {
        $this->authorizeAnalyticsDashboard();

        $title = strtolower($request->input('title', ''));
        $module = $request->input('module', 'leads'); // leads, deals
        $metric = $request->input('metric', 'count'); // count, sum, avg
        $dimension = $request->input('dimension', 'status'); // status, stage, lead_source, label, owner
        $period = $request->input('period', 'all'); // today, this_week, this_month, last_month, this_quarter, this_year, all
        $userIdFilter = $request->input('user_id', 'all'); // all or user ID

        $user = auth()->user();
        $isAdminOrManager = ($user->crm_role_type === 0 || $user->crm_role_type === 1 || $user->crm_role_type === 2);

        // 1. Resolve Query Builder
        if ($module === 'deals') {
            $query = Deal::query();
            $amountColumn = 'deals.amount';
            
            // Use close_date for revenue, sales, won, or lost metrics; created_at for volume/pipeline.
            if ($metric === 'sum' || $metric === 'avg' || str_contains($title, 'revenue') || str_contains($title, 'sales') || str_contains($title, 'lost') || str_contains($title, 'won')) {
                $dateColumn = 'deals.close_date';
            } else {
                $dateColumn = 'deals.created_at';
            }
            
            $ownerColumn = 'deals.user_owner_id';
            $createdColumn = 'deals.created_by';

            // Filter stage based on widget intent/title (check 'lost' first to prevent 'revenue lost' matching 'revenue')
            if (str_contains($title, 'lost')) {
                $query->where('deals.stage_id', \App\Models\Stage::closedLostId());
            } elseif (str_contains($title, 'revenue') || str_contains($title, 'sales') || str_contains($title, 'won')) {
                $query->where('deals.stage_id', \App\Models\Stage::closedWonId());
            } elseif (str_contains($title, 'pipeline')) {
                $query->whereNotIn('deals.stage_id', array_filter([\App\Models\Stage::closedWonId(), \App\Models\Stage::closedLostId()]));
            }
        } elseif ($module === 'converted_leads') {
            $query = Lead::query()->whereNotNull('leads.converted_at');
            $amountColumn = 'leads.amount';
            $dateColumn = 'leads.converted_at';
            $ownerColumn = 'leads.user_owner_id';
            $createdColumn = 'leads.created_by';
        } else {
            $query = Lead::query();
            $amountColumn = 'leads.amount';
            $dateColumn = 'leads.created_at';
            $ownerColumn = 'leads.user_owner_id';
            $createdColumn = 'leads.created_by';
        }

        // 2. Apply Security Scopes
        if (!$isAdminOrManager) {
            $query->where(function ($q) use ($user, $ownerColumn, $createdColumn) {
                $q->where($ownerColumn, $user->id)
                  ->orWhere($createdColumn, $user->id);
            });
        }

        // 3. Apply Global User Filter
        if ($userIdFilter !== 'all') {
            $query->where($ownerColumn, $userIdFilter);
        }

        // 4. Resolve Financial Year from Session or Active Default
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
        } else {
            $fy = $activeFy;
        }

        // Adjust relative time queries based on the selected financial year year-offset
        $now = Carbon::now();
        if ($fy && $activeFy) {
            $activeStartYear = Carbon::parse($activeFy->from_date)->year;
            $selectedStartYear = Carbon::parse($fy->from_date)->year;
            $yearDifference = $selectedStartYear - $activeStartYear;
            if ($yearDifference !== 0) {
                $now->addYears($yearDifference);
            }
        }

        // Resolve Previous Financial Year boundaries based on selected/active $fy
        $prevFrom = null;
        $prevTo = null;
        if ($fy) {
            $prevFy = \App\Models\FinancialYear::where('to_date', '<', Carbon::parse($fy->from_date)->endOfDay())
                ->orderByDesc('to_date')
                ->first();
            if ($prevFy) {
                $prevFrom = Carbon::parse($prevFy->from_date)->startOfDay();
                $prevTo = Carbon::parse($prevFy->to_date)->endOfDay();
            } else {
                $prevFrom = Carbon::parse($fy->from_date)->subYear()->startOfDay();
                $prevTo = Carbon::parse($fy->to_date)->subYear()->endOfDay();
            }
        } else {
            $prevFrom = Carbon::now()->subYear()->startOfYear();
            $prevTo = Carbon::now()->subYear()->endOfYear();
        }

        // 5. Apply Period and Financial Year Filter
        if ($period === 'all') {
            if ($fy) {
                $query->whereBetween($dateColumn, [
                    Carbon::parse($fy->from_date)->startOfDay(),
                    Carbon::parse($fy->to_date)->endOfDay()
                ]);
            }
        } else {
            switch ($period) {
                case 'today':
                    $query->whereDate($dateColumn, $now->toDateString());
                    break;
                case 'this_week':
                    $query->whereBetween($dateColumn, [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereBetween($dateColumn, [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
                    break;
                case 'last_month':
                    $lastMonth = $now->copy()->subMonth();
                    $query->whereBetween($dateColumn, [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()]);
                    break;
                case '6_months':
                    $query->whereBetween($dateColumn, [$now->copy()->subMonths(6)->startOfDay(), $now->copy()->endOfDay()]);
                    break;
                case 'this_year':
                    $query->whereBetween($dateColumn, [$now->copy()->startOfYear(), $now->copy()->endOfYear()]);
                    break;
                case 'previous_year':
                    $query->whereBetween($dateColumn, [$prevFrom, $prevTo]);
                    break;
                case 'this_quarter':
                    $query->whereBetween($dateColumn, [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()]);
                    break;
            }
            if ($fy && $period !== 'previous_year') {
                $query->whereBetween($dateColumn, [
                    Carbon::parse($fy->from_date)->startOfDay(),
                    Carbon::parse($fy->to_date)->endOfDay()
                ]);
            }
        }

        if ($request->input('type') === 'target_meter') {
            $targetStart = null;
            $targetEnd = null;

            switch ($period) {
                case 'today':
                    $targetStart = $now->copy()->startOfDay();
                    $targetEnd = $now->copy()->endOfDay();
                    break;
                case 'this_week':
                    $targetStart = $now->copy()->startOfWeek();
                    $targetEnd = $now->copy()->endOfWeek();
                    break;
                case 'this_month':
                    $targetStart = $now->copy()->startOfMonth();
                    $targetEnd = $now->copy()->endOfMonth();
                    break;
                case 'last_month':
                    $targetStart = $now->copy()->subMonth()->startOfMonth();
                    $targetEnd = $now->copy()->subMonth()->endOfMonth();
                    break;
                case 'this_quarter':
                    $targetStart = $now->copy()->startOfQuarter();
                    $targetEnd = $now->copy()->endOfQuarter();
                    break;
                case 'this_year':
                    $targetStart = $now->copy()->startOfYear();
                    $targetEnd = $now->copy()->endOfYear();
                    break;
                case '6_months':
                    $targetStart = $now->copy()->subMonths(6)->startOfDay();
                    $targetEnd = $now->copy()->endOfDay();
                    break;
                case 'previous_year':
                    $targetStart = $prevFrom;
                    $targetEnd = $prevTo;
                    break;
                case 'all':
                    if ($fy) {
                        $targetStart = Carbon::parse($fy->from_date)->startOfDay();
                        $targetEnd = Carbon::parse($fy->to_date)->endOfDay();
                    }
                    break;
            }

            if ($fy && $period !== 'previous_year') {
                $targetStart = $targetStart
                    ? $targetStart->max(Carbon::parse($fy->from_date)->startOfDay())
                    : Carbon::parse($fy->from_date)->startOfDay();
                $targetEnd = $targetEnd
                    ? $targetEnd->min(Carbon::parse($fy->to_date)->endOfDay())
                    : Carbon::parse($fy->to_date)->endOfDay();
            }

            $targetStart ??= $now->copy()->startOfMonth();
            $targetEnd ??= $now->copy()->endOfMonth();

            $targetUsersQuery = User::query()
                ->where('status', 1);

            if (!$isAdminOrManager) {
                $targetUsersQuery->where('id', $user->id);
            }

            if ($userIdFilter !== 'all') {
                $targetUsersQuery->where('id', $userIdFilter);
            }

            $targetUsers = $targetUsersQuery->get(['id', 'sales_target']);
            $monthCount = $targetStart->copy()->startOfMonth()
                ->diffInMonths($targetEnd->copy()->startOfMonth()) + 1;
            $requestedTarget = $request->input('target');
            $target = is_numeric($requestedTarget) && (float) $requestedTarget > 0
                ? (float) $requestedTarget
                : (float) $targetUsers->sum('sales_target') * $monthCount;
            $userIds = $targetUsers->pluck('id');

            $achieved = $userIds->isEmpty()
                ? 0.0
                : (float) Deal::query()
                    ->where('stage_id', \App\Models\Stage::closedWonId())
                    ->whereIn('user_owner_id', $userIds)
                    ->whereBetween('close_date', [
                        $targetStart->copy()->startOfDay(),
                        $targetEnd->copy()->endOfDay(),
                    ])
                    ->sum('amount');
            $percentage = $target > 0 ? round($achieved / $target * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'target' => $target,
                'achieved' => $achieved,
                'percentage' => $percentage,
                'labels' => [],
                'values' => [],
                'total' => $achieved,
                'metric' => $metric,
                'dimension' => $dimension,
                'module' => $module,
            ]);
        }

        // 6. Build Select & Aggregation Metric
        switch ($metric) {
            case 'sum':
                $aggregateExpression = "SUM(COALESCE($amountColumn, 0))";
                break;
            case 'avg':
                $aggregateExpression = "AVG(COALESCE($amountColumn, 0))";
                break;
            case 'count':
            default:
                $aggregateExpression = "COUNT(*)";
                break;
        }

        // 7. Handle Dimension Grouping and Join
        $selectField = '';
        $groupField = '';

        if ($dimension === 'owner') {
            $query->leftJoin('users', $ownerColumn, '=', 'users.id');
            $selectField = "COALESCE(users.name, 'Unassigned')";
            $groupField = "COALESCE(users.name, 'Unassigned')";
        } elseif ($dimension === 'lead_source') {
            $tableName = ($module === 'deals') ? 'deals' : 'leads';
            $query->leftJoin('lead_sources', "$tableName.lead_source", '=', 'lead_sources.id');
            $selectField = "COALESCE(lead_sources.name, $tableName.lead_source, 'None')";
            $groupField = "COALESCE(lead_sources.name, $tableName.lead_source, 'None')";
        } elseif ($dimension === 'stage' && $module === 'deals') {
            $selectField = "COALESCE(stages.name, 'Unknown')";
            $groupField = "COALESCE(stages.name, 'Unknown')";
            $query->leftJoin('stages', 'deals.stage_id', '=', 'stages.id');
        } elseif ($dimension === 'status' && ($module === 'leads' || $module === 'converted_leads')) {
            $selectField = "leads.status";
            $groupField = "leads.status";
        } elseif ($dimension === 'label') {
            $tableName = ($module === 'deals') ? 'deals' : 'leads';
            $selectField = "$tableName.label";
            $groupField = "$tableName.label";
        } else {
            $column = ($module === 'deals') ? 'deals.status' : 'leads.status';
            $selectField = $column;
            $groupField = $column;
        }

        // 8. Execute Query
        $results = $query->selectRaw("$selectField as label, $aggregateExpression as value")
            ->groupBy(DB::raw($groupField))
            ->orderByDesc('value')
            ->get();

        // 9. Format Response
        $labels = [];
        $values = [];
        $total = 0;

        foreach ($results as $row) {
            $label = $row->label ? $row->label : 'None';
            $val = (float) $row->value;
            $labels[] = $label;
            $values[] = $val;
            $total += $val;
        }

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'values' => $values,
            'total' => $total,
            'metric' => $metric,
            'dimension' => $dimension,
            'module' => $module
        ]);
    }

    private function authorizeAnalyticsDashboard(): void
    {
        abort_unless(auth()->user()->hasCrmPermission('view_analytics_dashboard_guard'), 403, 'Unauthorized action.');
    }
}
