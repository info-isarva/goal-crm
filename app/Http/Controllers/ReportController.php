<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeadsByStatusExport;
use App\Exports\LeadsBySourceExport;
use App\Exports\ConvertedLeadsExport;
use App\Exports\TodayLeadsExport;
use App\Exports\WeeklyActivityExport;
use App\Exports\LeadDealsReportExport;
use App\Models\Deal;



class ReportController extends Controller
{
    public function __construct()
    {
        // Middleware can be applied here if needed
        //  $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
        //     'todayLeads',
        //     'todayClosedWonDeals',

        // ]);
    }

    /**
     * Display titles for individual report pages (content back header).
     *
     * @return array<string, string>
     */
    public static function pageTitles(): array
    {
        return [
            'reports.today_leads' => "Today's Leads Report",
            'reports.leads_by_status' => 'Leads by Status Report',
            'reports.leads_by_source_custom' => 'Leads by Source',
            'reports.converted_leads' => 'Converted Leads Report',
            'reports.lead_deals' => 'Lead Deals Report',
            'reports.today_closed_won_deals' => "Today's Sales",
            'reports.deals_by_source_custom' => 'Sales by Source',
            'reports.open_deals' => 'Open Deals Report',
            'reports.lost_deals' => 'Lost Deals Report',
            'reports.deals_this_month' => 'Deals Closing this Month',
            'reports.user_daily_report' => 'User Daily Report',
            'reports.sales_marketing_daily' => 'Daily Sales & Marketing Report',
            'reports.consolidated_sales' => 'Consolidated Sales Report',
            'reports.monthly_user_report' => 'Monthly User Report',
            'reports.monthly_user_performance' => 'Monthly User Performance Report',
            'reports.weekly_activity' => 'Weekly Activity Report',
            'task-reports.index' => 'Reminder Reports',
            'reports.task' => 'Task Report',
            'reports.product_category_user_report' => 'Product Category Wise User Report',
            'calllogs.report' => 'Calls Reports',
            'reports.monthly_target_report' => 'Monthly Target Report',
            'reports.leads' => 'Leads Analytics',
            'reports.deals' => 'Deals Analytics',
            'reports.revenue_by_month' => 'Revenue Reports',
            'analytics.reports' => 'User Performance Analytics Reports',
            'reports.month_lead_comparison' => 'Month Lead Comparison Report',
            'reports.user_activity' => 'User Activity Report',
            'reports.user_reports' => 'User Reports',
            'reports.user_wise' => 'User Wise Report',
        ];
    }

    /**
     * Resolve the report page title for the current or given route.
     */
    public static function resolvePageTitle(?string $routeName = null): ?string
    {
        $routeName = $routeName ?? Route::currentRouteName();
        if (!$routeName || $routeName === 'reports.index') {
            return null;
        }

        if (preg_match('/_(excel|pdf)$/', $routeName)) {
            return null;
        }

        $titles = static::pageTitles();
        if (isset($titles[$routeName])) {
            return $titles[$routeName];
        }

        if (str_starts_with($routeName, 'reports.')) {
            return ucwords(str_replace('_', ' ', substr($routeName, strlen('reports.'))));
        }

        return null;
    }

    /**
     * Whether the route uses the modern in-page report shell (toolbar + paper).
     */
    public static function usesModernReportShell(?string $routeName = null): bool
    {
        $routeName = $routeName ?? Route::currentRouteName();

        return static::resolvePageTitle($routeName) !== null
            && $routeName !== 'reports.index';
    }

    /**
     * Whether the route is an individual report page (not the reports hub).
     */
    public static function isReportSubPage(?string $routeName = null): bool
    {
        return static::resolvePageTitle($routeName) !== null;
    }

    /**
     * All Reports list page (opened from sidebar Reports menu).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $reports = collect($this->reportCatalog())
            ->filter(function (array $report) use ($user) {
                if ($user && $user->isSuperAdmin()) {
                    return true;
                }

                $permission = $report['permission'] ?? null;
                if (!$permission) {
                    return true;
                }
                if (is_array($permission)) {
                    foreach ($permission as $guard) {
                        if ($user->hasCrmPermission($guard)) {
                            return true;
                        }
                    }

                    return false;
                }

                return $user->hasCrmPermission($permission);
            })
            ->map(function (array $report) {
                $report['url'] = route($report['route']);
                unset($report['permission'], $report['route']);

                return $report;
            })
            ->values();

        $folders = $reports->pluck('folder')->unique()->sort()->values();
        $filter = $request->get('folder', 'all');
        $search = trim((string) $request->get('q', ''));

        return view('reports.index', compact('reports', 'folders', 'filter', 'search'));
    }

    /**
     * Built-in report catalog for the All Reports page.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function reportCatalog(): array
    {
        return [
            [
                'id' => 'today_leads',
                'name' => "Today's Leads",
                'description' => 'Leads created today with owner, company, and status.',
                'folder' => 'Leads Reports',
                'route' => 'reports.today_leads',
                'permission' => 'todays_leads_reports_guard',
            ],
            [
                'id' => 'leads_by_status',
                'name' => 'Leads by Status',
                'description' => 'Lead counts and list filtered by status.',
                'folder' => 'Leads Reports',
                'route' => 'reports.leads_by_status',
                'permission' => 'leads_by_status_guard',
            ],
            [
                'id' => 'leads_by_source',
                'name' => 'Leads by Source',
                'description' => 'Lead performance grouped by lead source.',
                'folder' => 'Leads Reports',
                'route' => 'reports.leads_by_source_custom',
                'permission' => 'leads_by_source_guard',
            ],
            [
                'id' => 'converted_leads',
                'name' => 'Converted Leads',
                'description' => 'Leads that were converted into deals.',
                'folder' => 'Leads Reports',
                'route' => 'reports.converted_leads',
                'permission' => 'converted_leads_guard',
            ],
            [
                'id' => 'lead_deals',
                'name' => 'Lead Deals Report',
                'description' => 'Deal outcomes linked from converted leads.',
                'folder' => 'Leads Reports',
                'route' => 'reports.lead_deals',
                'permission' => 'lead_deal_reports_guard',
            ],
            [
                'id' => 'today_sales',
                'name' => "Today's Sales",
                'description' => 'Closed-won deals recorded today.',
                'folder' => 'Deals Reports',
                'route' => 'reports.today_closed_won_deals',
                'permission' => 'today_deals_reports_guard',
            ],
            [
                'id' => 'sales_by_source',
                'name' => 'Sales by Source',
                'description' => 'Won deals grouped by source.',
                'folder' => 'Deals Reports',
                'route' => 'reports.deals_by_source_custom',
                'permission' => 'deal_by_source_reports_guard',
            ],
            [
                'id' => 'open_sales',
                'name' => 'Open Sales',
                'description' => 'Currently open deals in the pipeline.',
                'folder' => 'Deals Reports',
                'route' => 'reports.open_deals',
                'permission' => 'open_deals_guard',
            ],
            [
                'id' => 'lost_sales',
                'name' => 'Lost Sales',
                'description' => 'Deals marked as lost.',
                'folder' => 'Deals Reports',
                'route' => 'reports.lost_deals',
                'permission' => 'lost_deals_guard',
            ],
            [
                'id' => 'sales_closing_month',
                'name' => 'Sales Closing This Month',
                'description' => 'Deals expected to close in the current month.',
                'folder' => 'Deals Reports',
                'route' => 'reports.deals_this_month',
                'permission' => 'deals_closing_this_month_guard',
            ],
            [
                'id' => 'daily_user',
                'name' => 'Daily User Report',
                'description' => 'Day-wise user activity across leads, deals, and calls.',
                'folder' => 'User Reports',
                'route' => 'reports.user_daily_report',
                'permission' => 'user_report_guard',
            ],
            [
                'id' => 'daily_report_entry',
                'name' => 'Daily Report Entry',
                'description' => 'Submit your daily report with outreach counts and other activities.',
                'folder' => 'User Reports',
                'route' => 'daily_reports.index',
                'permission' => 'manage_crm_daily_report_guard',
            ],
            [
                'id' => 'sales_marketing_daily',
                'name' => 'Daily Sales & Marketing Report',
                'description' => 'Team-wide daily sales and marketing matrix with targets, leads, and activity details.',
                'folder' => 'User Reports',
                'route' => 'reports.sales_marketing_daily',
                'permission' => ['daily_sales_and_marketing_report_guard', 'sales_marketing_daily_report_guard'],
            ],
            [
                'id' => 'consolidated_sales',
                'name' => 'Consolidated Sales Report',
                'description' => 'Consolidated sales target, revenue status, activity matrix, and analytics charts.',
                'folder' => 'User Reports',
                'route' => 'reports.consolidated_sales',
                'permission' => 'consolidated_sales_report_guard',
            ],
            [
                'id' => 'monthly_user',
                'name' => 'Monthly User Report',
                'description' => 'Monthly summary of user CRM activity.',
                'folder' => 'User Reports',
                'route' => 'reports.monthly_user_report',
                'permission' => 'user_report_guard',
            ],
            [
                'id' => 'user_performance',
                'name' => 'User Performance Report',
                'description' => 'Performance metrics by user for the selected period.',
                'folder' => 'User Reports',
                'route' => 'reports.monthly_user_performance',
                'permission' => 'user_report_guard',
            ],
            [
                'id' => 'weekly_activity',
                'name' => 'Weekly Activity Report',
                'description' => 'Weekly activity overview for the sales team.',
                'folder' => 'User Reports',
                'route' => 'reports.weekly_activity',
                'permission' => 'weekly_activity_reports_guard',
            ],
            [
                'id' => 'reminder_report',
                'name' => 'Reminder Report',
                'description' => 'Task reminder status and follow-ups.',
                'folder' => 'User Reports',
                'route' => 'task-reports.index',
                'permission' => 'task_reminder_reports_guard',
            ],
            [
                'id' => 'user_task',
                'name' => 'User Task Report',
                'description' => 'Tasks assigned to users with completion status.',
                'folder' => 'User Reports',
                'route' => 'reports.task',
                'permission' => 'user_task_report_guard',
            ],
            [
                'id' => 'product_wise',
                'name' => 'Product Wise Reports',
                'description' => 'User performance by product category.',
                'folder' => 'User Reports',
                'route' => 'reports.product_category_user_report',
                'permission' => 'product_wise_reports_guard',
            ],
            [
                'id' => 'call_logs',
                'name' => 'Call Logs Report',
                'description' => 'Inbound and outbound call activity by user.',
                'folder' => 'User Reports',
                'route' => 'calllogs.report',
                'permission' => 'view_calls_reports_guard',
            ],
            [
                'id' => 'monthly_target',
                'name' => 'Monthly Target Report',
                'description' => 'Target vs achievement for the month.',
                'folder' => 'User Reports',
                'route' => 'reports.monthly_target_report',
                'permission' => 'monthly_target_reports_guard',
            ],
            [
                'id' => 'analytics_leads',
                'name' => 'Leads Analytics',
                'description' => 'Analytical charts and trends for leads.',
                'folder' => 'Analytics Reports',
                'route' => 'reports.leads',
                'permission' => 'leads_analytics_report_guard',
            ],
            [
                'id' => 'analytics_deals',
                'name' => 'Deals Analytics',
                'description' => 'Analytical charts and trends for deals.',
                'folder' => 'Analytics Reports',
                'route' => 'reports.deals',
                'permission' => 'deals_analytics_reports_guard',
            ],
            [
                'id' => 'revenue_reports',
                'name' => 'Revenue Reports',
                'description' => 'Revenue by month with trend analysis.',
                'folder' => 'Analytics Reports',
                'route' => 'reports.revenue_by_month',
                'permission' => 'revenue_analytics_reports_guard',
            ],
            [
                'id' => 'user_perf_analytics',
                'name' => 'User Performance Analytics',
                'description' => 'Advanced user performance analytics views.',
                'folder' => 'Analytics Reports',
                'route' => 'analytics.reports',
                'permission' => 'user_performance_analytics_reports_guard',
            ],
            [
                'id' => 'lead_comparison',
                'name' => 'Lead Comparison Report',
                'description' => 'Month-over-month lead comparison.',
                'folder' => 'Analytics Reports',
                'route' => 'reports.month_lead_comparison',
                'permission' => 'month_lead_comparison_report_guard',
            ],
        ];
    }

    /**** Today Leads Report ****/
    public function todayLeads(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('todays_leads_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $today = Carbon::today();
        $user = auth()->user();

        // Determine if selected financial year is historical
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;

        // If a historical financial year is selected, do not show today's leads
        if ($isHistorical) {
            // Return the view with empty leads and a flag so UI can display a proper message
            $leads = collect();
            $historicalSelected = true;
            return view('reports.today_leads', compact('leads', 'historicalSelected'));
        }

        // Normal behaviour: show today's leads for current/active FY
        // If the user is a manager, fetch their employees
        $employeeIds = collect();
        if ($user->crm_role_type === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }



        // Update query to include manager's and employees' leads
        $query = Lead::with(['customer', 'organization', 'person', 'owner'])
            ->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);

                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            })->whereDate('created_at', $today);

         if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
            $query->where('user_owner_id', $user->id);
        }
        $leads = $query->orderByDesc('created_at')->get();
        $historicalSelected = false;
         // Fetch category names for each lead
        $leads->transform(function ($lead) {
            $categoryIds = explode(',', $lead->category ?? '');
            $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
            return $lead;
        });
        return view('reports.today_leads', compact('leads', 'historicalSelected'));
    }

    // Export today's leads to Excel
    public function exportTodayLeads(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('todays_leads_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $today = Carbon::today();
        $user = auth()->user();

        // Financial year check
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;

        if ($user->crm_role_type === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }

         // If a historical financial year is selected, do not show today's leads

        if ($isHistorical) {
            $leads = collect();
        } else {
            $query = Lead::with(['customer', 'organization', 'person', 'owner'])->whereDate('created_at', $today);

            if ($user->crm_role_type === 2) { // Manager
                $query->where(function ($q) use ($user, $employeeIds) {
                    $q->where('user_owner_id', $user->id);

                    if ($employeeIds->isNotEmpty()) {
                        $q->orWhereIn('user_owner_id', $employeeIds);
                    }
                });
            }

            if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
                $query->where('user_owner_id', $user->id);
            }
            $leads = $query->orderByDesc('created_at')->get();

        }
         // Fetch category names for each lead
        $leads->transform(function ($lead) {
            $categoryIds = explode(',', $lead->category ?? '');
            $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
            return $lead;
        });

        $rows = $leads->map(function($lead) {
            $categoryList = '';
            if(!empty($lead->category_names))
            {
                $categoryList = implode(', ', $lead->category_names);
            }
            else
            {
                $categoryList = 'N/A';
            }
            return [
                $lead->title,
                // optional($lead->customer)->name ?? '-',
                optional($lead->organization)->name ?? '-',
                $lead->person ? trim($lead->person->first_name . ' ' . $lead->person->last_name) : '-',
                optional($lead->owner)->name ?? '-',
                $categoryList,
                $lead->status ?? '-',

                $lead->created_at ? $lead->created_at->format('d-m-Y H:i') : '',
            ];
        })->toArray();

        $export = new TodayLeadsExport($rows);
        $fileName = 'today_leads_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download($export, $fileName);
    }

    /*** Today Closed Won Deals Report ***/
    public function todayClosedWonDeals(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('today_deals_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $today = Carbon::today();
        $user = auth()->user();


        // Determine if selected financial year is historical
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;

        // If a historical financial year is selected, do not show today's closed won deals
        if ($isHistorical) {
            $deals = collect();
            $total = 0;
            $historicalSelected = true;
            return view('reports.today_closed_won_deals', compact('deals', 'total', 'historicalSelected'));
        }

        // Normal behaviour: show today's closed won deals for current/active FY
        // Show deals which were closed (close_date) today and are Closed Won
        $query = \App\Models\Deal::with(['organization', 'owner', 'dealSource'])->whereDate('close_date', $today)->where('stage_id', \App\Models\Stage::closedWonId());
        if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
            $query->where('user_owner_id', $user->id);
        }

        // Compute total across all matching deals (not just the current page)
        $total = (clone $query)->get()->sum(function($d){ return is_numeric($d->amount) ? (float)$d->amount : 0; });

        // Paginate the result for the UI to match other reports
        $perPage = 15;
        $deals = $query->orderByDesc('created_at')->paginate($perPage)->appends(request()->query());
        $historicalSelected = false;
        return view('reports.today_closed_won_deals', compact('deals', 'total', 'historicalSelected'));
    }

    // Export Today's Closed Won Deals to Excel (respects owner restriction & FY historical guard)
    public function exportTodayClosedWonDeals(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('today_deals_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $today = Carbon::today();
        $user = auth()->user();

        // Determine if selected financial year is historical
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;

        if ($isHistorical) {
            $deals = collect();
        } else {
            $query = \App\Models\Deal::with(['organization', 'owner', 'dealSource'])->whereDate('close_date', $today)->where('stage_id', \App\Models\Stage::closedWonId());
            if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
                $query->where('user_owner_id', $user->id);
            }
            $deals = $query->orderByDesc('created_at')->get();
        }

        $rows = $deals->map(function($d) {
            return [
                $d->title,
                $d->organization ? $d->organization->name : '-',
                $d->owner ? $d->owner->name : '-',
                $d->dealSource ? $d->dealSource->name : '-',
                is_numeric($d->amount) ? \App\Helpers\MoneyFormatter::format($d->amount) : ($d->amount ?: '-'),
                $d->close_date ? Carbon::parse($d->close_date)->format('d-m-Y') : '',
            ];
        })->toArray();

        $export = new \App\Exports\TodayClosedWonDealsExport($rows);
        $fileName = 'today_closed_won_deals_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download($export, $fileName);
    }

    /*** Converted Leads Report ***/
    public function convertedLeads(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('converted_leads_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$start, $end, $period] = static::resolveReportPeriod($request, 'this_month');
        $user = auth()->user();
        $usersList = $this->convertedLeadsFilterUsers($user);
        $filterUserId = $request->input('user_id');

        $query = \App\Models\Lead::with(['customer', 'organization', 'person', 'owner'])
            ->whereNotNull('converted_at');

        $this->applyConvertedLeadsOwnerScope($query, $user, $filterUserId);

        if ($start && $end) {
            $query->whereDate('converted_at', '>=', $start)
                ->whereDate('converted_at', '<=', $end);
        }

        $perPage = 15;
        $leads = $query->orderByDesc('converted_at')->paginate($perPage)->appends(request()->query());
        $leads->transform(function ($lead) {
            $categoryIds = explode(',', $lead->category ?? '');
            $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();

            return $lead;
        });

        return view('reports.converted_leads', compact('leads', 'start', 'end', 'period', 'usersList', 'filterUserId'));
    }

    // Export converted leads report
    public function exportConvertedLeads(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('converted_leads_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$start, $end] = array_slice(static::resolveReportPeriod($request, 'this_month'), 0, 2);
        $user = auth()->user();
        $filterUserId = $request->input('user_id');

        $query = Lead::with(['customer', 'organization', 'person', 'owner'])->whereNotNull('converted_at');

        $this->applyConvertedLeadsOwnerScope($query, $user, $filterUserId);

        if ($start && $end) {
            $query->whereDate('converted_at', '>=', $start)
                ->whereDate('converted_at', '<=', $end);
        }

        $leads = $query->orderByDesc('converted_at')->get();

         // Fetch category names for each lead
        $leads->transform(function ($lead) {
            $categoryIds = explode(',', $lead->category ?? '');
            $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
            return $lead;
        });

        $rows = $leads->map(function($lead) {
            $categoryList = '';
            if(!empty($lead->category_names))
            {
                $categoryList = implode(', ', $lead->category_names);
            }
            else
            {
                $categoryList = 'N/A';
            }
            return [
                $lead->title,
                // optional($lead->customer)->name ?? '-',
                $categoryList,
                optional($lead->organization)->name ?? '-',
                $lead->person ? trim($lead->person->first_name . ' ' . $lead->person->last_name) : '-',
                optional($lead->owner)->name ?? '-',
                $lead->status ?? '-',
                $lead->converted_at ? Carbon::parse($lead->converted_at)->format('d-m-Y H:i') : '',
            ];
        })->toArray();

        $export = new ConvertedLeadsExport($rows);
        $fileName = 'converted_leads_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download($export, $fileName);
    }

    /**
     * Users available in the Converted Leads report filter.
     */
    protected function convertedLeadsFilterUsers(\App\Models\User $user)
    {
        $roleType = (int) $user->crm_role_type;

        if (in_array($roleType, [0, 1], true)) {
            return \App\Models\User::query()
                ->where('status', 1)
                ->whereNotIn('crm_role_type', [0, 1])
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if ($roleType === 2) {
            $teamIds = \App\Models\User::where('assign_manager', $user->id)
                ->where('status', 1)
                ->pluck('id');

            return \App\Models\User::whereIn('id', $teamIds->push($user->id)->unique())
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return \App\Models\User::where('id', $user->id)->get(['id', 'name']);
    }

    /**
     * Apply owner scope (and optional user filter) for converted leads queries.
     */
    protected function applyConvertedLeadsOwnerScope($query, \App\Models\User $user, $filterUserId = null): void
    {
        $roleType = (int) $user->crm_role_type;
        $filterUserId = $filterUserId ? (int) $filterUserId : null;

        if (in_array($roleType, [0, 1], true)) {
            if ($filterUserId) {
                $query->where('user_owner_id', $filterUserId);
            }

            return;
        }

        if ($roleType === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
            $allowedIds = $employeeIds->push($user->id)->unique();

            if ($filterUserId && $allowedIds->contains($filterUserId)) {
                $query->where('user_owner_id', $filterUserId);
            } else {
                $query->where(function ($q) use ($user, $employeeIds) {
                    $q->where('user_owner_id', $user->id);
                    if ($employeeIds->isNotEmpty()) {
                        $q->orWhereIn('user_owner_id', $employeeIds);
                    }
                });
            }

            return;
        }

        $query->where('user_owner_id', $user->id);
    }

    /*** Leads by Status Report ***/
    public function leadsByStatus(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('leads_by_status_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Get all statuses from lead_status table
        $statuses = \App\Models\LeadStatus::orderBy('name')->pluck('name');
        if ($statuses === null || $statuses->isEmpty()) {
            $statuses = collect(['No Statuses Available']); // Default fallback
        }
        $user = auth()->user();

        $query = Lead::with(['customer', 'organization', 'person', 'owner']);
        // If a historical financial year is selected, restrict to that FY's created_at range
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && (! $activeFy || $selectedFyId != $activeFy->id);
        // Prefer explicitly selected FY; otherwise fall back to active FY when available
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
        } else {
            $fy = $activeFy;
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employeeIds = collect();
        if ($user->crm_role_type === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }

        if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
            $query->where('user_owner_id', $user->id);
        }

        if ($user->crm_role_type === 2) { // Manager
            $query->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);

                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        }

        // If we have a financial year (selected or active), filter converted leads to that FY
        if ($fy) {
            $start = Carbon::parse($fy->from_date)->startOfDay();
            $end = Carbon::parse($fy->to_date)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }
        $perPage = 15;
        $leads = $query->orderByDesc('created_at')->paginate($perPage)->appends(request()->query());
        // Fetch category names for each lead
            $leads->transform(function ($lead) {
                $categoryIds = explode(',', $lead->category ?? '');
                $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
                return $lead;
            });
        return view('reports.leads_by_status', compact('leads', 'statuses'));
    }

    // Export Leads by Status respecting status filter and FY context
    public function exportLeadsByStatus(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('leads_by_status_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();

        $employeeIds = collect();
        if ($user->crm_role_type === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }
        // Determine financial year context
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
        } else {
            $fy = $activeFy;
        }
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;


        $query = Lead::with(['customer', 'organization', 'person', 'owner']);

        // Apply status filter if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($user->crm_role_type === 2) { // Manager
            $query->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);

                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        }

        // Owner restriction for employees
        if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
            $query->where('user_owner_id', $user->id);
        }

        // Apply FY range if present
        if ($fy) {
            $start = Carbon::parse($fy->from_date)->startOfDay();
            $end = Carbon::parse($fy->to_date)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        // If a historical FY is selected and user asked for short-relative filters it is handled in view by returning empty data.
        // For export, we'll export whatever the query yields (which will be empty for historical restricted cases).

        $leads = $query->orderByDesc('created_at')->get();
 // Fetch category names for each lead
        $leads->transform(function ($lead) {
            $categoryIds = explode(',', $lead->category ?? '');
            $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
            return $lead;
        });

        $rows = $leads->map(function($lead) {
             $categoryList = '';
            if(!empty($lead->category_names))
            {
                $categoryList = implode(', ', $lead->category_names);
            }
            else
            {
                $categoryList = 'N/A';
            }
            return [
                $lead->title,
                // optional($lead->customer)->name ?? '-',
                optional($lead->organization)->name ?? '-',
                $lead->person ? trim($lead->person->first_name . ' ' . $lead->person->last_name) : '-',
                optional($lead->owner)->name ?? '-',
                $categoryList,
                $lead->status ?? '-',
                $lead->created_at ? $lead->created_at->format('d-m-Y H:i') : '',
            ];
        })->toArray();

        $export = new LeadsByStatusExport($rows);
        $fileName = 'leads_by_status_' . ($request->status ? strtolower(str_replace(' ', '_', $request->status)) . '_' : '') . now()->format('Ymd_His') . '.xlsx';
        return Excel::download($export, $fileName);
    }

    /*** Open Deals Report ***/
    public function openDeals(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('open_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$start, $end, $period] = static::resolveReportPeriod($request, 'this_month');
        $user = auth()->user();
        $filterUserId = $request->input('user_id');
        $stageFilter = $request->input('stage');
        $usersList = $this->convertedLeadsFilterUsers($user);

        $stages = \App\Models\Stage::query()
            ->orderBy('order_by')
            ->pluck('name');

        if ($stages->isEmpty()) {
            $stages = collect(['Open', 'Closed Won', 'Closed Lost']);
        } elseif (!$stages->contains('Closed Won')) {
            $stages = $stages->push('Closed Won');
        }
        if (!$stages->contains('Closed Lost')) {
            $stages = $stages->push('Closed Lost');
        }

        $query = \App\Models\Deal::with(['organization', 'person', 'owner']);

        $this->applyConvertedLeadsOwnerScope($query, $user, $filterUserId);

        if ($stageFilter) {
            if (strtolower($stageFilter) === 'open') {
                $query->whereNotIn('stage_id', array_filter([\App\Models\Stage::closedWonId(), \App\Models\Stage::closedLostId()]));
            } else {
                $stageId = \App\Models\Stage::resolveId($stageFilter);
                if ($stageId) {
                    $query->where('stage_id', $stageId);
                }
            }
        } else {
            $query->whereNotIn('stage_id', array_filter([\App\Models\Stage::closedWonId(), \App\Models\Stage::closedLostId()]));
        }

        if ($start && $end) {
            $query->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end);
        }

        $deals = $query->orderByDesc('created_at')
            ->paginate(15)
            ->appends(request()->query());

        return view('reports.open_deals', compact(
            'deals', 'start', 'end', 'period', 'usersList', 'filterUserId', 'stages', 'stageFilter'
        ));
    }

     /*** Lost Deals Report ***/
    public function lostDeals(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('lost_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$start, $end, $period] = static::resolveReportPeriod($request, 'this_month');
        $user = auth()->user();
        $filterUserId = $request->input('user_id');
        $leadSourceId = $request->input('lead_source');
        $usersList = $this->convertedLeadsFilterUsers($user);
        $leadSources = \App\Models\LeadSource::orderBy('name')->get(['id', 'name']);

        $query = \App\Models\Deal::with(['organization', 'owner', 'dealSource'])
            ->where('stage_id', \App\Models\Stage::closedLostId());

        $this->applyConvertedLeadsOwnerScope($query, $user, $filterUserId);

        if ($leadSourceId) {
            $query->where('lead_source', $leadSourceId);
        }

        if ($start && $end) {
            $query->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end);
        }

        $deals = $query->orderByDesc('created_at')
            ->paginate(15)
            ->appends(request()->query());

        return view('reports.lost_deals', compact(
            'deals', 'start', 'end', 'period', 'usersList', 'filterUserId', 'leadSources', 'leadSourceId'
        ));
    }

     /*** Deals Closing This Month Report ***/
    public function dealsThisMonth(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('deals_closing_this_month_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        $user = auth()->user();
        $filterUserId = $request->input('user_id');
        $stageFilter = $request->input('stage');
        $usersList = $this->convertedLeadsFilterUsers($user);

        $stages = \App\Models\Stage::query()
            ->orderBy('order_by')
            ->pluck('name');

        if ($stages->isEmpty()) {
            $stages = collect(['Open', 'Closed Won', 'Closed Lost']);
        } elseif (!$stages->contains('Closed Won')) {
            $stages = $stages->push('Closed Won');
        }
        if (!$stages->contains('Closed Lost')) {
            $stages = $stages->push('Closed Lost');
        }

        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;

        if ($isHistorical) {
            $deals = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
            $historicalSelected = true;

            return view('reports.deals_this_month', compact(
                'deals', 'historicalSelected', 'usersList', 'filterUserId', 'stages', 'stageFilter'
            ));
        }

        $query = \App\Models\Deal::with(['organization', 'owner'])
            ->whereBetween('close_date', [$start, $end]);

        $this->applyConvertedLeadsOwnerScope($query, $user, $filterUserId);

        if ($stageFilter) {
            $stageId = \App\Models\Stage::resolveId($stageFilter);
            if ($stageId) {
                $query->where('stage_id', $stageId);
            }
        }

        $deals = $query->orderByDesc('close_date')
            ->paginate(15)
            ->appends(request()->query());

        $historicalSelected = false;

        return view('reports.deals_this_month', compact(
            'deals', 'historicalSelected', 'usersList', 'filterUserId', 'stages', 'stageFilter'
        ));
    }

     /*** Revenue by Month Report ***/
    public function revenueByMonth(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('revenue_analytics_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$start, $end, $period] = static::resolveReportPeriod($request, 'this_month');

        $user = auth()->user();
        $employeeIds = collect();
        if ($user->crm_role_type === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }
        $baseQuery = \App\Models\Deal::query()->where('stage_id', \App\Models\Stage::closedWonId());
        if ($user->crm_role_type === 2) { // Manager
            $baseQuery->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);
                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        }
        // Only show own records for employees
        if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
            $baseQuery->where('user_owner_id', $user->id);
        }

        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;

        $shortPeriods = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month'];
        if ($isHistorical && in_array($period, $shortPeriods, true)) {
            $monthlyRevenue = collect();
            $historicalSelected = true;

            return view('reports.revenue_by_month', compact(
                'monthlyRevenue', 'start', 'end', 'period', 'selectedFyId', 'historicalSelected'
            ));
        }

        if ($start && $end) {
            $baseQuery->whereDate('close_date', '>=', $start)
                      ->whereDate('close_date', '<=', $end);
        }

        // AJAX: return deals for selected month
        if ($request->ajax() || $request->get('ajax')) {
            $month = $request->get('month'); // e.g. 'Aug 2025'
            $deals = (clone $baseQuery)
                ->whereRaw("DATE_FORMAT(close_date, '%b %Y') = ?", [$month])
                ->with(['person', 'organization'])
                ->orderBy('close_date')
                ->get();
            $result = $deals->map(function($deal) {
                return [
                    'title' => $deal->title,
                    'company' => $deal->organization ? $deal->organization->name : '-',
                    'contact_person' => $deal->person ? ($deal->person->first_name . ' ' . $deal->person->last_name) : '-',
                    'stage' => $deal->stage_name,
                    'amount' => $deal->amount,
                ];
            });
            return response()->json($result);
        }

        $deals = (clone $baseQuery)->orderBy('close_date')->get();

        // Group by month and year
        $monthlyRevenue = collect();
        $grouped = $deals->groupBy(function($deal) {
            return Carbon::parse($deal->close_date)->format('M Y');
        });
        foreach ($grouped as $month => $items) {
            $monthlyRevenue->push([
                'month' => $month,
                'amount' => $items->sum('amount'),
            ]);
        }

        // Sort by date ascending
        $monthlyRevenue = $monthlyRevenue->sortBy(function($row) {
            return Carbon::parse('01 ' . $row['month']);
        })->values();

        return view('reports.revenue_by_month', compact(
            'monthlyRevenue', 'start', 'end', 'period', 'selectedFyId'
        ));
    }

    /*** Deals Report ***/
    public function deals(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('deals_analytics_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }
        [$start, $end, $period] = self::resolveReportPeriod($request, 'today');
        $user = auth()->user();
        $filterUserId = $request->input('user_id');
        $leadSourceId = $request->input('lead_source');
        $stageFilter = $request->input('stage', '');
        $usersList = $this->convertedLeadsFilterUsers($user);
        $leadSources = \App\Models\LeadSource::orderBy('name')->get(['id', 'name']);

        $stages = \App\Models\Stage::query()
            ->orderBy('order_by')
            ->pluck('name');

        if ($stages->isEmpty()) {
            $stages = collect(['Open', 'Closed Won', 'Closed Lost']);
        } else {
            if (!$stages->contains('Open')) {
                $stages = $stages->prepend('Open');
            }
            if (!$stages->contains('Closed Won')) {
                $stages = $stages->push('Closed Won');
            }
            if (!$stages->contains('Closed Lost')) {
                $stages = $stages->push('Closed Lost');
            }
        }

        if ($filterUserId && !$usersList->pluck('id')->contains((int) $filterUserId)) {
            $filterUserId = null;
        }

        $baseQuery = \App\Models\Deal::query();
        $this->applyConvertedLeadsOwnerScope($baseQuery, $user, $filterUserId);

        if ($leadSourceId) {
            $baseQuery->where('lead_source', $leadSourceId);
        }

        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;

        $shortPeriods = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month'];
        if ($isHistorical && in_array($period, $shortPeriods, true)) {
            $perPage = 15;
            $deals = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return view('reports.deals', [
                'deals' => $deals,
                'dealSourceLabels' => [],
                'dealSourceCounts' => [],
                'dealStageLabels' => [],
                'dealStageCounts' => [],
                'start' => $start,
                'end' => $end,
                'period' => $period,
                'historicalSelected' => true,
                'monthLabels' => [],
                'monthDealCounts' => [],
                'dealOwnerLabels' => [],
                'dealOwnerCounts' => [],
                'usersList' => $usersList,
                'filterUserId' => $filterUserId,
                'leadSources' => $leadSources,
                'leadSourceId' => $leadSourceId,
                'stages' => $stages,
                'stageFilter' => $stageFilter,
            ]);
        }

        if ($period === 'today' && $stageFilter === 'Closed Won') {
            $baseQuery->whereDate('close_date', now());
        } elseif ($start && $end) {
            $baseQuery->whereDate('created_at', '>=', $start)
                      ->whereDate('created_at', '<=', $end);
        }

        if ($stageFilter) {
            if ($stageFilter === 'Open') {
                $baseQuery->whereNotIn('stage_id', array_filter([\App\Models\Stage::closedWonId(), \App\Models\Stage::closedLostId()]));
            } else {
                $stageId = \App\Models\Stage::resolveId($stageFilter);
                if ($stageId) {
                    $baseQuery->where('stage_id', $stageId);
                }
            }
        }

        $deals = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->with(['organization', 'person', 'owner', 'dealSource', 'stage'])
            ->paginate(15)
            ->withQueryString();

        // For analytics, get all filtered records (not paginated)
        $allDeals = (clone $baseQuery)->with(['dealSource', 'owner', 'stage'])->get();

        // Deal source analytics
        $dealSourceData = $allDeals->groupBy(function($deal) {
            return $deal->dealSource->name ?? 'Unknown';
        });
        $dealSourceLabels = $dealSourceData->keys()->toArray();
        $dealSourceCounts = $dealSourceData->map->count()->values()->toArray();

        // Deal stage analytics
        $dealStageData = $allDeals->groupBy(function ($deal) {
            return $deal->stage_name ?? 'Unknown';
        });
        $dealStageLabels = $dealStageData->keys()->toArray();
        $dealStageCounts = $dealStageData->map->count()->values()->toArray();

        //Month wise deals analytics for selected period (regardless of pagination) - used for month filter in view
        $monthWiseData = $allDeals->groupBy(function($deal) {
            return Carbon::parse($deal->created_at)->format('M y');
        });

        $monthLabels = $monthWiseData->keys()->toArray();
        $monthDealCounts = $monthWiseData->map->count()->values()->toArray();

        // Deal owner-wise analytics
        $dealOwnerData = $allDeals->groupBy(function($deal) {
            return $deal->owner->name ?? 'Unknown';
        });
        $dealOwnerLabels = $dealOwnerData->keys()->toArray();
        $dealOwnerCounts = $dealOwnerData->map->count()->values()->toArray();

        $historicalSelected = false;
        return view('reports.deals', compact(
            'deals',
            'dealSourceLabels',
            'dealSourceCounts',
            'dealStageLabels',
            'dealStageCounts',
            'start',
            'end',
            'period',
            'historicalSelected',
            'monthLabels',
            'monthDealCounts',
            'dealOwnerLabels',
            'dealOwnerCounts',
            'usersList',
            'filterUserId',
            'leadSources',
            'leadSourceId',
            'stages',
            'stageFilter'
        ));
    }

     /*** Leads Report ***/
    public function leads(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('leads_analytics_report_guard')) {
            abort(403, 'Unauthorized action.');
        }
        [$start, $end, $period] = self::resolveReportPeriod($request, 'today');
        $user = auth()->user();
        $filterUserId = $request->input('user_id');
        $leadSourceId = $request->input('lead_source');
        $statusFilter = $request->input('status');
        $usersList = $this->convertedLeadsFilterUsers($user);
        $leadSources = \App\Models\LeadSource::orderBy('name')->get(['id', 'name']);
        $statuses = \App\Models\LeadStatus::orderBy('name')->pluck('name');
        if ($statuses->isEmpty()) {
            $statuses = collect(['New', 'Contacted', 'Qualified', 'Lost', 'Converted']);
        }

        if ($filterUserId && !$usersList->pluck('id')->contains((int) $filterUserId)) {
            $filterUserId = null;
        }

        $baseQuery = Lead::query();
        $this->applyConvertedLeadsOwnerScope($baseQuery, $user, $filterUserId);

        if ($leadSourceId) {
            $baseQuery->where('lead_source', $leadSourceId);
        }
        if ($statusFilter) {
            $baseQuery->where('status', $statusFilter);
        }

        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;

        $shortPeriods = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month'];
        if ($isHistorical && in_array($period, $shortPeriods, true)) {
            $perPage = 15;
            $leads = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return view('reports.leads', [
                'leads' => $leads,
                'leadSourceLabels' => [],
                'leadSourceCounts' => [],
                'leadStatusLabels' => [],
                'leadStatusCounts' => [],
                'start' => $start,
                'end' => $end,
                'period' => $period,
                'historicalSelected' => true,
                'monthLabels' => [],
                'monthLeadCounts' => [],
                'leadOwnerLabels' => [],
                'leadOwnerCounts' => [],
                'usersList' => $usersList,
                'filterUserId' => $filterUserId,
                'leadSources' => $leadSources,
                'leadSourceId' => $leadSourceId,
                'statuses' => $statuses,
                'statusFilter' => $statusFilter,
            ]);
        }

        if ($start && $end) {
            $baseQuery->whereDate('created_at', '>=', $start)
                      ->whereDate('created_at', '<=', $end);
        }
        $leads = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->with(['customer', 'organization', 'person', 'owner', 'leadSource'])
            ->paginate(15)
            ->withQueryString();

        // For analytics, get all filtered records (not paginated)
        $allLeads = (clone $baseQuery)->with(['leadSource', 'owner'])->get();

        // Lead source analytics
        $leadSourceData = $allLeads->groupBy(function($lead) {
            return $lead->leadSource->name ?? 'Unknown';
        });
        $leadSourceLabels = $leadSourceData->keys()->toArray();
        $leadSourceCounts = $leadSourceData->map->count()->values()->toArray();

        // Lead status analytics
        $leadStatusData = $allLeads->groupBy('status');
        $leadStatusLabels = $leadStatusData->keys()->toArray();
        $leadStatusCounts = $leadStatusData->map->count()->values()->toArray();

        // Month-wise leads analytics
        $monthData = $allLeads->groupBy(function($lead) {
            return $lead->created_at->format('M y'); // Group by year-month
        });
        $monthLabels = $monthData->keys()->toArray();
        $monthLeadCounts = $monthData->map->count()->values()->toArray();

        // Lead owner-wise analytics
        $leadOwnerData = $allLeads->groupBy(function($lead) {
            return $lead->owner->name ?? 'Unknown';
        });
        $leadOwnerLabels = $leadOwnerData->keys()->toArray();
        $leadOwnerCounts = $leadOwnerData->map->count()->values()->toArray();

        return view('reports.leads', compact(
            'leads',
            'leadSourceLabels',
            'leadSourceCounts',
            'leadStatusLabels',
            'leadStatusCounts',
            'start',
            'end',
            'period',
            'monthLabels',
            'monthLeadCounts',
            'leadOwnerLabels',
            'leadOwnerCounts',
            'usersList',
            'filterUserId',
            'leadSources',
            'leadSourceId',
            'statuses',
            'statusFilter'
        ));
    }

    /**
     * Preset labels for the date range picker.
     *
     * @return array<string, string>
     */
    public static function periodLabels(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'year' => 'Year',
            'custom' => 'Custom',
        ];
    }

    /**
     * Human-readable period text for report meta headers.
     */
    public static function periodText(string $period, ?string $start = null, ?string $end = null): string
    {
        if ($start && $end) {
            try {
                $s = \Carbon\Carbon::parse($start)->format('d M Y');
                $e = \Carbon\Carbon::parse($end)->format('d M Y');
                if ($s === $e) {
                    return $s;
                }
                return $s . ' - ' . $e;
            } catch (\Throwable $ex) {
                return $start . ' - ' . $end;
            }
        }

        return static::periodLabels()[$period] ?? ucfirst(str_replace('_', ' ', $period));
    }

    /**
     * Resolve report period presets into start/end dates.
     *
     * @return array{0: string, 1: string, 2: string} [start, end, period]
     */
    public static function resolveReportPeriod(Request $request, string $defaultPeriod = 'this_month'): array
    {
        $period = $request->get('period', $defaultPeriod);
        $allowed = [
            'today', 'yesterday', 'this_week', 'last_week',
            'this_month', 'last_month', 'year', 'custom',
        ];
        if (!in_array($period, $allowed, true)) {
            $period = $defaultPeriod;
        }

        $legacyStart = $request->get('start_date') ?: $request->get('from_date');
        $legacyEnd = $request->get('end_date') ?: $request->get('to_date');
        if (!$request->has('period') && $legacyStart && $legacyEnd) {
            $period = 'custom';
        }

        $now = Carbon::now();
        $start = null;
        $end = null;

        switch ($period) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                break;
            case 'this_week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'year':
                $selectedFyId = session('selected_financial_year', null);
                $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
                $fy = $selectedFyId
                    ? \App\Models\FinancialYear::find($selectedFyId)
                    : $activeFy;
                if ($fy) {
                    $start = Carbon::parse($fy->from_date)->startOfDay();
                    $end = Carbon::parse($fy->to_date)->endOfDay();
                } else {
                    $start = $now->copy()->startOfYear();
                    $end = $now->copy()->endOfYear();
                }
                break;
            case 'custom':
                $startRaw = $request->get('start_date') ?: $request->get('from_date');
                $endRaw = $request->get('end_date') ?: $request->get('to_date');
                if ($startRaw && $endRaw) {
                    $start = Carbon::parse($startRaw)->startOfDay();
                    $end = Carbon::parse($endRaw)->endOfDay();
                    if ($start->gt($end)) {
                        [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                    }
                } else {
                    $start = $now->copy()->startOfMonth();
                    $end = $now->copy()->endOfMonth();
                }
                break;
        }

        return [
            $start ? $start->toDateString() : null,
            $end ? $end->toDateString() : null,
            $period,
        ];
    }

    /*** Leads By Source Report (custom) ***/
    public function leadsBySource(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('leads_by_source_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$start, $end, $period] = $this->resolveReportPeriod($request, 'this_month');

        $user = auth()->user();
        $employeeIds = collect();
        if ($user->crm_role_type === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }
        $query = Lead::with(['leadSource', 'organization', 'person', 'owner']);
        if ($start && $end) {
            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        }
        if ($user->crm_role_type === 2) { // Manager
            $query->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);

                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        }
        if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
            $query->where('user_owner_id', $user->id);
        }

        // Paginate leads and group only the current page results by lead source
        $perPage = 15;
        $leadsPaginator = $query->orderBy('lead_source')->orderByDesc('created_at')->paginate($perPage)->appends(request()->query());
        // Fetch category names for each lead
        $leadsPaginator->transform(function ($lead) {
            $categoryIds = explode(',', $lead->category ?? '');
            $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
            return $lead;
        });
        $currentPageLeads = $leadsPaginator->getCollection();

        // Group current page leads by lead source for display
        $grouped = $currentPageLeads->groupBy(function($lead) {
            return $lead->leadSource->name ?? '-';
        });

        $periodLabels = [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'year' => 'Year',
            'custom' => 'Custom',
        ];

        return view('reports.leads_by_source', compact(
            'grouped', 'start', 'end', 'leadsPaginator', 'period', 'periodLabels'
        ));
    }

    // Export Leads by Source respecting start/end date and FY context
    public function exportLeadsBySource(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('leads_by_source_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();

        $employeeIds = collect();
        if ($user->crm_role_type === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }

        [$start, $end] = $this->resolveReportPeriod($request, 'this_month');

        $query = Lead::with(['customer', 'organization', 'person', 'owner', 'leadSource']);

        if ($start && $end) {
            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        }

        if ($user->crm_role_type === 2) { // Manager
            $query->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);

                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        }
        // Owner restriction for employees
        if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
            $query->where('user_owner_id', $user->id);
        }

        // Build and fetch results
        $leads = $query->orderByDesc('created_at')->get();

         // Fetch category names for each lead
        $leads->transform(function ($lead) {
            $categoryIds = explode(',', $lead->category ?? '');
            $lead->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
            return $lead;
        });

        // Map to rows grouped by lead source; since view groups per page, for export we'll include source name on each row
        $rows = $leads->map(function($lead) {
             $categoryList = '';
            if(!empty($lead->category_names))
            {
                $categoryList = implode(', ', $lead->category_names);
            }
            else
            {
                $categoryList = 'N/A';
            }
            return [
                optional($lead->leadSource)->name ?? '-',
                $lead->person ? trim(($lead->person->first_name ?? '') . ' ' . ($lead->person->last_name ?? '')) : '-',
                $categoryList,
                $lead->person && ($lead->person->mobile ?? '') ? $lead->person->mobile : ($lead->mobile ?? '-'),
                $lead->organization ? $lead->organization->name : '-',
                $lead->title,
                $lead->created_at ? $lead->created_at->format('d-m-Y h:i A') : '-',
                $lead->owner ? $lead->owner->name : '-',
            ];
        })->toArray();

        $export = new LeadsBySourceExport($rows);
        $fileName = 'leads_by_source_' . ($start ? $start . '_' : '') . ($end ? $end . '_' : '') . now()->format('Ymd_His') . '.xlsx';
        return Excel::download($export, $fileName);
    }

    /*** Deals By Source Report (custom) ***/
    public function dealsBySource(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('deal_by_source_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$start, $end, $period] = static::resolveReportPeriod($request, 'this_month');

        $user = auth()->user();
        $employeeIds = collect();
        if ($user->crm_role_type === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }
        $query = \App\Models\Deal::with(['dealSource', 'organization', 'person', 'owner']);
        // If start/end provided, filter by created_at
        if ($start && $end) {
            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        }
        if ($user->crm_role_type === 2) { // Manager
            $query->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);

                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        }
        // Owner restriction for employees
        if (in_array($user->crm_role_type, [3, '3', 'employee'])) {
            $query->where('user_owner_id', $user->id);
        }
        $deals = $query->orderBy('lead_source')->orderByDesc('created_at')->get();

        // Group deals by deal source
        $grouped = $deals->groupBy(function($deal) {
            return $deal->dealSource->name ?? '-';
        });

        return view('reports.deals_by_source', compact('grouped', 'start', 'end', 'period'));
    }

    /*** Task Report ***/
    public function taskReport(Request $request)
    {

        [$start, $end, $period] = self::resolveReportPeriod($request, 'this_month');

        $today  = Carbon::today();
        $user = auth()->user();
        $assignUsers = $this->convertedLeadsFilterUsers($user);
        $assignedTo = $request->input('assigned_to');
        if ($assignedTo && !$assignUsers->pluck('id')->contains((int) $assignedTo)) {
            $assignedTo = null;
        }

        $employeeIds = collect();
        if ($user->crm_role_type === 2) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)
                ->where('status', 1)
                ->pluck('id');
        }

        if (in_array($user->crm_role_type, [0, 1])) {
            // Admin, Super Admin, and Manager can see all tasks
            $query = \App\Models\Task::query();
        }elseif ($user->crm_role_type === 2) {
            // Managers can see their own tasks and those of their employees
            $query = \App\Models\Task::where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id)
                    ->orWhere('user_assigned_id', $user->id);

                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds)
                        ->orWhereIn('user_assigned_id', $employeeIds);
                }
            });
        }else {
            // Users can only see their owned and assigned tasks
            $query = \App\Models\Task::where(function ($q) use ($user) {
                $q->where('user_owner_id', $user->id)
                  ->orWhere('user_assigned_id', $user->id);
            });
        }

        $query->where(function ($q) {
            $q->where(function ($ownerQ) {
                $ownerQ->whereNull('user_owner_id')
                    ->orWhereHas('owner', fn ($uq) => $uq->where('status', 1));
            })->where(function ($assignQ) {
                $assignQ->whereNull('user_assigned_id')
                    ->orWhereHas('assignee', fn ($uq) => $uq->where('status', 1));
            });
        });

        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'overdue':
                    $query->where('due_at', '<', $today)
                            ->where('status', '!=', 'completed');
                    break;
                case 'today':
                    $query->whereDate('due_at', $today)
                    ->where('status', '!=', 'completed');
                    break;
                case 'upcoming':
                    $query->where('due_at', '>', $today)
                    ->where('status', '!=', 'completed');
                    break;
                case 'completed':
                    $query->where('status', 'completed');
                    break;
            }
        }

        if ($start && $end) {
            $query->whereDate('due_at', '>=', $start)->whereDate('due_at', '<=', $end);
        }

        if ($assignedTo) {
            $query->where('user_assigned_id', $assignedTo);
        }

        $tasks = $query->paginate(10)->withQueryString();
        $tasks->getCollection()->transform(fn ($task) =>
            $this->addOrganizationName($task)
        );

        return view('reports.task_report', compact('tasks', 'start', 'end', 'period', 'assignUsers', 'assignedTo'));
    }

    function addOrganizationName($task)
    {
        try {
            if ($task->related_type === 'deal') {
                $task->organization_name =
                    Deal::with('organization')->find($task->related_id)->organization->name ?? 'No Organization';
            } elseif ($task->related_type === 'lead') {
                $task->organization_name =
                    Lead::with('organization')->find($task->related_id)->organization->name ?? 'No Organization';
            } else {
                $task->organization_name = 'Invalid Related Type';
            }
        } catch (\Exception $e) {
            $task->organization_name = 'Error Fetching Organization';
        }

        return $task;
    }

public function monthLeadComparisonReport(Request $request)
{
    if( !auth()->user()->hasCrmPermission('month_lead_comparison_report_guard') ) {
        abort(403, 'Unauthorized action.');
    }
    $financialYears = \App\Models\FinancialYear::all();
    $selectedFyId = $request->get('financial_year', session('selected_financial_year', null));
    $selectedFy = $financialYears->find($selectedFyId) ?? $financialYears->first();

    $timeRange = $request->get('time_range', 'current_vs_previous');

    $user = auth()->user();
    $roleType = (int) $user->crm_role_type;

    if (in_array($roleType, [0, 1], true)) {
        // Admin / Super Admin — all active lead owners
        $allUserIds = \App\Models\User::query()
            ->where('status', 1)
            ->whereNotIn('crm_role_type', [0, 1])
            ->orderBy('name')
            ->pluck('id');
    } elseif ($roleType === 2) {
        // Manager — own team + self
        $teamIds = \App\Models\User::where('assign_manager', $user->id)
            ->where('status', 1)
            ->pluck('id');
        $allUserIds = $teamIds->push($user->id)->unique()->values();
    } else {
        // Employee — only self
        $allUserIds = collect([$user->id]);
    }

    // Build users list for the dropdown
    $usersList = \App\Models\User::whereIn('id', $allUserIds)->orderBy('name')->get();

    // Handle selected user filter
    $selectedUserId = $request->get('user_id', 'all');

    // Filter users based on selection
    if ($selectedUserId !== 'all') {
        $users = collect([$selectedUserId]);
    } else {
        $users = $allUserIds;
    }

    $leadData = collect();
    $months = collect();

    if ($timeRange === 'current_vs_previous') {
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();

        $months = collect([
            $previousMonth->format('M Y'),
            $currentMonth->format('M Y')
        ]);

        $leadData = $users->mapWithKeys(function ($userId) use ($currentMonth, $previousMonth) {
            $currentMonthLeads = Lead::where('user_owner_id', $userId)
                ->whereBetween('created_at', [
                    $currentMonth,
                    $currentMonth->copy()->endOfMonth()
                ])
                ->count();

            $previousMonthLeads = Lead::where('user_owner_id', $userId)
                ->whereBetween('created_at', [
                    $previousMonth,
                    $previousMonth->copy()->endOfMonth()
                ])
                ->count();

            $owner = \App\Models\User::find($userId);
            $userName = $owner?->name ?? 'Unknown';

            return [
                $userName => [
                    $previousMonth->format('M Y') => $previousMonthLeads,
                    $currentMonth->format('M Y')  => $currentMonthLeads,
                ]
            ];
        });

    } elseif (in_array($timeRange, ['last_2_months', 'last_3_months', 'last_6_months'])) {
        $monthsCount = (int) filter_var($timeRange, FILTER_SANITIZE_NUMBER_INT);

        $months = collect(range(0, $monthsCount - 1))
            ->map(fn($i) => Carbon::now()->subMonths($i)->startOfMonth())
            ->reverse()
            ->values()
            ->map(fn($month) => $month->format('M Y'));

        $leadData = $users->mapWithKeys(function ($userId) use ($months) {
            $userLeads = $months->mapWithKeys(function ($month) use ($userId) {
                $leadCount = Lead::where('user_owner_id', $userId)
                    ->whereBetween('created_at', [
                        Carbon::parse('01 ' . $month)->startOfMonth(),
                        Carbon::parse('01 ' . $month)->endOfMonth()
                    ])
                    ->count();

                return [$month => $leadCount];
            });

            $owner = \App\Models\User::find($userId);
            $userName = $owner?->name ?? 'Unknown';

            return [$userName => $userLeads];
        });
    }

    return view('reports.month_lead_comparison_report', compact(
        'financialYears',
        'selectedFy',
        'timeRange',
        'months',
        'leadData',
        'usersList',
        'selectedUserId'
    ));
}
  
   public function lead_deals_reports(Request $request)
  {
    if (!auth()->user()->hasCrmPermission('lead_deal_reports_guard')) {
        abort(403, 'Unauthorized action.');
    }

    $data = $this->buildLeadDealsReportDataset($request);

    return view('reports.lead_deals_report', [
        'records' => $data['records'],
        'start' => $data['start'],
        'end' => $data['end'],
        'period' => $data['period'],
        'type' => $data['type'],
        'categoryId' => $data['categoryId'],
        'leadSourceId' => $data['leadSourceId'],
        'ownerId' => $data['ownerId'],
        'stageId' => $data['stageId'],
        'stages' => $data['stages'],
        'owners' => $data['owners'],
        'leadSources' => $data['leadSources'],
        'categories' => $data['categories'],
        'selectedColumns' => $data['selectedColumns'],
        'availableColumns' => $data['availableColumns'],
        'totalCount' => $data['totalCount'],
        'totalAmount' => $data['totalAmount'],
    ]);
  }

  public function exportLeadDealsReport(Request $request)
  {
    if (!auth()->user()->hasCrmPermission('lead_deal_reports_guard')) {
        abort(403, 'Unauthorized action.');
    }

    $data = $this->buildLeadDealsReportDataset($request);
    $type = $data['type'];
    $selectedColumns = $data['selectedColumns'];
    $availableColumns = $data['availableColumns'];

    $headings = [];
    foreach ($selectedColumns as $field) {
        $headings[] = $availableColumns[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    $rows = $data['records']->map(function ($row) use ($selectedColumns) {
        $line = [];
        foreach ($selectedColumns as $field) {
            if ($field === 'product_services') {
                $line[] = !empty($row->category_names) ? implode(', ', $row->category_names) : 'N/A';
            } elseif ($field === 'amount') {
                $line[] = is_numeric($row->amount) ? (float) $row->amount : null;
            } elseif ($field === 'name') {
                $line[] = $row->name ?: '-';
            } else {
                $line[] = $row->{$field} ?? '-';
            }
        }
        return $line;
    })->values()->all();

    $categoryLabel = 'All';
    if (!empty($data['categoryId'])) {
        $categoryLabel = optional($data['categories']->firstWhere('id', (int) $data['categoryId']))->category_name ?: $data['categoryId'];
    }
    $sourceLabel = 'All';
    if (!empty($data['leadSourceId'])) {
        $sourceLabel = optional($data['leadSources']->firstWhere('id', (int) $data['leadSourceId']))->name ?: $data['leadSourceId'];
    }
    $ownerLabel = 'All';
    if (!empty($data['ownerId'])) {
        $ownerLabel = optional($data['owners']->firstWhere('id', (int) $data['ownerId']))->name ?: $data['ownerId'];
    }
    $stageLabel = null;
    if ($type === 'deal') {
        $stageLabel = 'All';
        if (!empty($data['stageId'])) {
            $stageLabel = optional($data['stages']->firstWhere('id', (int) $data['stageId']))->name ?: $data['stageId'];
        }
    }

    $meta = [
        'title' => 'Lead Deals Report',
        'date_range' => ($data['start'] && $data['end'])
            ? \Carbon\Carbon::parse($data['start'])->format('d M Y') . ' – ' . \Carbon\Carbon::parse($data['end'])->format('d M Y')
            : '-',
        'period_label' => self::periodText($data['period'], $data['start'], $data['end']),
        'type_label' => $type === 'deal' ? 'Deals' : 'Leads',
        'category_label' => $categoryLabel,
        'source_label' => $sourceLabel,
        'owner_label' => $ownerLabel,
        'stage_label' => $stageLabel,
        'record_count' => $data['totalCount'],
        'total_amount' => $data['totalAmount'],
        'generated_on' => now()->format('d M Y h:i A'),
        'generated_by' => auth()->user()->name ?? '',
    ];

    $export = new LeadDealsReportExport($rows, $type, $meta, $headings);
    $fileName = 'lead_deals_report_' . $type . '_' . now()->format('Ymd_His') . '.xlsx';

    return Excel::download($export, $fileName);
  }

  private function buildLeadDealsReportDataset(Request $request): array
  {
    [$start, $end, $period] = static::resolveReportPeriod($request, 'this_month');

    $type = strtolower((string) $request->input('type', 'lead'));
    if (!in_array($type, ['lead', 'deal'], true)) {
        $type = 'lead';
    }

    $categoryId = $request->filled('category') ? (string) $request->input('category') : null;
    $leadSourceId = $request->filled('lead_source') ? (string) $request->input('lead_source') : null;
    $ownerId = $request->filled('owner') ? (string) $request->input('owner') : null;
    $stageId = ($type === 'deal' && $request->filled('stage')) ? (string) $request->input('stage') : null;

    $defaultColumns = ['name', 'company', 'lead_source', 'product_services', 'amount'];
    $availableColumns = [
        'name' => $type === 'deal' ? 'Deal Name' : 'Lead Name',
        'company' => 'Company',
        'lead_source' => 'Lead Source',
        'product_services' => 'Product/Services',
        'amount' => 'Amount',
        'owner_name' => 'Owner Name',
        'status' => $type === 'deal' ? 'Deal Status' : 'Lead Status',
        'priority' => 'Priority',
        'created_at' => 'Created At',
    ];
    if ($type === 'deal') {
        $availableColumns['stage'] = 'Stage';
    }

    // Support both columns[] and legacy extra_fields[]
    $requestedColumns = $request->input('columns', $request->input('extra_fields', null));
    if ($requestedColumns === null) {
        $selectedColumns = $defaultColumns;
    } else {
        if (!is_array($requestedColumns)) {
            $requestedColumns = $requestedColumns !== '' ? [$requestedColumns] : [];
        }
        $selectedColumns = array_values(array_filter($requestedColumns, function ($field) use ($availableColumns) {
            return isset($availableColumns[$field]);
        }));
        // Keep default visible columns if user only picked extras via legacy param
        if ($request->has('extra_fields') && !$request->has('columns') && !empty($selectedColumns)) {
            $selectedColumns = array_values(array_unique(array_merge($defaultColumns, $selectedColumns)));
        }
        if (empty($selectedColumns)) {
            $selectedColumns = $defaultColumns;
        }
    }

    // Preserve a sensible column order (available order), not request order only
    $selectedColumns = array_values(array_filter(array_keys($availableColumns), function ($field) use ($selectedColumns) {
        return in_array($field, $selectedColumns, true);
    }));

    $user = auth()->user();
    $employeeIds = collect();
    if ((int) $user->crm_role_type === 2) {
        $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
    }

    $ownersQuery = \App\Models\User::query()->orderBy('name');
    if ((int) $user->crm_role_type === 2) {
        $ownersQuery->where(function ($q) use ($user, $employeeIds) {
            $q->where('id', $user->id);
            if ($employeeIds->isNotEmpty()) {
                $q->orWhereIn('id', $employeeIds);
            }
        });
    } elseif (in_array((int) $user->crm_role_type, [3], true)) {
        $ownersQuery->where('id', $user->id);
    }
    $owners = $ownersQuery->get(['id', 'name']);
    $leadSources = \App\Models\LeadSource::orderBy('name')->get(['id', 'name']);
    $categories = \App\Models\ProductCategory::orderBy('category_name')->get(['id', 'category_name']);
    $categoryNameMap = $categories->pluck('category_name', 'id');
    $stages = \App\Models\Stage::orderBy('order_by')->get(['id', 'name']);

    $applyOwnerScope = function ($query) use ($user, $employeeIds, $ownerId) {
        if ($ownerId) {
            $query->where('user_owner_id', $ownerId);
            return;
        }
        if ((int) $user->crm_role_type === 2) {
            $query->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);
                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        } elseif (in_array((int) $user->crm_role_type, [3], true)) {
            $query->where('user_owner_id', $user->id);
        }
    };

    $applyCategoryFilter = function ($query) use ($categoryId) {
        if (!$categoryId) {
            return;
        }
        $query->where(function ($q) use ($categoryId) {
            $q->where('category', $categoryId)
                ->orWhereRaw('FIND_IN_SET(?, category)', [$categoryId]);
        });
    };

    $resolveCategoryNames = function ($categoryCsv) use ($categoryNameMap) {
        if ($categoryCsv === null || $categoryCsv === '') {
            return [];
        }
        $ids = array_filter(array_map('trim', explode(',', (string) $categoryCsv)));
        $names = [];
        foreach ($ids as $id) {
            if (isset($categoryNameMap[$id])) {
                $names[] = $categoryNameMap[$id];
            }
        }
        return $names;
    };

    $formatPriority = function ($label) {
        $map = [
            'high' => 'High',
            'normal' => 'Normal',
            'low' => 'Low',
        ];
        $key = strtolower(trim((string) $label));
        return $map[$key] ?? ($label ? ucfirst((string) $label) : '-');
    };

    $records = collect();

    if ($type === 'lead') {
        $query = \App\Models\Lead::query()
            ->with(['organization', 'person', 'leadSource', 'owner'])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Junk Lead');
            });

        if ($start && $end) {
            $query->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end);
        }
        if ($leadSourceId) {
            $query->where('lead_source', $leadSourceId);
        }
        $applyOwnerScope($query);
        $applyCategoryFilter($query);

        $records = $query->orderByDesc('created_at')->get()->map(function ($lead) use ($resolveCategoryNames, $formatPriority) {
            $personName = $lead->person
                ? trim(($lead->person->first_name ?? '') . ' ' . ($lead->person->last_name ?? ''))
                : '';
            return (object) [
                'id' => $lead->id,
                'type' => 'lead',
                'name' => $personName !== '' ? $personName : ($lead->title ?: '-'),
                'title' => $lead->title,
                'company' => optional($lead->organization)->name ?: '-',
                'lead_source' => optional($lead->leadSource)->name ?: '-',
                'category_names' => $resolveCategoryNames($lead->category),
                'amount' => $lead->amount,
                'owner_name' => optional($lead->owner)->name ?: '-',
                'status' => $lead->status ? ucfirst(str_replace('_', ' ', (string) $lead->status)) : '-',
                'priority' => $formatPriority($lead->label ?? null),
                'created_at' => $lead->created_at ? \Carbon\Carbon::parse($lead->created_at)->format('d M Y') : '-',
                'stage' => '-',
                'show_url' => route('leads.show', $lead->id),
            ];
        });
    } else {
        $query = \App\Models\Deal::query()
            ->with(['organization', 'person', 'dealSource', 'owner', 'stage'])
            ->whereNull('deleted_at');

        if ($start && $end) {
            $query->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end);
        }
        if ($leadSourceId) {
            $query->where('lead_source', $leadSourceId);
        }
        if ($stageId) {
            $query->where('stage_id', $stageId);
        }
        $applyOwnerScope($query);
        $applyCategoryFilter($query);

        $records = $query->orderByDesc('created_at')->get()->map(function ($deal) use ($resolveCategoryNames, $formatPriority) {
            $personName = $deal->person
                ? trim(($deal->person->first_name ?? '') . ' ' . ($deal->person->last_name ?? ''))
                : '';
            return (object) [
                'id' => $deal->id,
                'type' => 'deal',
                'name' => $personName !== '' ? $personName : ($deal->title ?: '-'),
                'title' => $deal->title,
                'company' => optional($deal->organization)->name ?: '-',
                'lead_source' => optional($deal->dealSource)->name ?: '-',
                'category_names' => $resolveCategoryNames($deal->category),
                'amount' => $deal->amount,
                'owner_name' => optional($deal->owner)->name ?: '-',
                'status' => $deal->status ? ucfirst(str_replace('_', ' ', (string) $deal->status)) : '-',
                'priority' => $formatPriority($deal->label ?? null),
                'created_at' => $deal->created_at ? \Carbon\Carbon::parse($deal->created_at)->format('d M Y') : '-',
                'stage' => $deal->stage_name ?: '-',
                'show_url' => route('deals.show', $deal->id),
            ];
        });
    }

    $totalCount = $records->count();
    $totalAmount = (float) $records->sum(function ($row) {
        return is_numeric($row->amount) ? (float) $row->amount : 0;
    });

    return [
        'records' => $records,
        'start' => $start,
        'end' => $end,
        'period' => $period,
        'type' => $type,
        'categoryId' => $categoryId,
        'leadSourceId' => $leadSourceId,
        'ownerId' => $ownerId,
        'stageId' => $stageId,
        'stages' => $stages,
        'owners' => $owners,
        'leadSources' => $leadSources,
        'categories' => $categories,
        'selectedColumns' => $selectedColumns,
        'availableColumns' => $availableColumns,
        'defaultColumns' => $defaultColumns,
        'totalCount' => $totalCount,
        'totalAmount' => $totalAmount,
    ];
  }

    /**
         * Weekly activity report: leads & deals with related Notes, Meetings, Tasks (with status).
         */
    public function weeklyActivityReport(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('weekly_activity_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $this->buildWeeklyActivityDataset($request);

        return view('reports.weekly_activity', [
            'records' => $data['records'],
            'period' => $data['period'],
            'type' => $data['type'],
            'start' => $data['start']->toDateString(),
            'end' => $data['end']->toDateString(),
            'userNames' => $data['userNames'],
            'users' => $data['users'],
            'user_id' => $data['user_id'],
        ]);
    }

    /**
     * Export weekly activity report to Excel.
     * Each lead/deal appears once; related Notes/Meetings/Tasks/Calls are listed underneath.
     */
    public function exportWeeklyActivity(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('weekly_activity_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $this->buildWeeklyActivityDataset($request);
        $userNames = $data['userNames'];
        $rows = [];
        $groups = [];
        $excelRow = 2; // relative to sheet before title rows are inserted (row 1 = column headers)

        foreach ($data['records'] as $row) {
            $activities = [];

            foreach ($row->notes as $note) {
                $noteContent = trim(strip_tags($note->content ?? ''));
                $activities[] = [
                    'Note',
                    $noteContent !== '' ? \Illuminate\Support\Str::limit($noteContent, 80) : '-',
                    $noteContent !== '' ? $noteContent : '-',
                    '-',
                    $note->created_at ? Carbon::parse($note->created_at)->format('d-m-Y H:i') : '-',
                    $userNames[$note->created_by] ?? '-',
                ];
            }

            foreach ($row->meetings as $meeting) {
                $title = $meeting->name ?: '-';
                $description = trim(strip_tags($meeting->description ?? ''));
                if ($description === '') {
                    $description = '-';
                }
                $activities[] = [
                    'Meeting',
                    $title,
                    $description,
                    '-',
                    $meeting->created_at ? Carbon::parse($meeting->created_at)->format('d-m-Y H:i') : '-',
                    $userNames[$meeting->user_owner_id] ?? '-',
                ];
            }

            foreach ($row->tasks as $task) {
                $title = $task->name ?: '-';
                $description = trim(strip_tags($task->description ?? ''));
                if ($description === '') {
                    $description = '-';
                }
                $activities[] = [
                    'Task',
                    $title,
                    $description,
                    $task->status ?: '-',
                    $task->created_at ? Carbon::parse($task->created_at)->format('d-m-Y H:i') : '-',
                    $userNames[$task->user_owner_id] ?? '-',
                ];
            }

            foreach ($row->calls as $call) {
                $title = $call->name ?: '-';
                $description = trim(strip_tags($call->description ?? ''));
                if ($description === '') {
                    $description = '-';
                }
                $when = $call->start_at
                    ? Carbon::parse($call->start_at)->format('d-m-Y H:i')
                    : ($call->created_at ? Carbon::parse($call->created_at)->format('d-m-Y H:i') : '-');
                $activities[] = [
                    'Call',
                    $title,
                    $description,
                    $call->location ?: '-',
                    $when,
                    $userNames[$call->user_owner_id] ?? ($userNames[$call->created_by] ?? '-'),
                ];
            }

            if (empty($activities)) {
                $activities[] = ['-', '-', 'No activities in this period', '-', '-', '-'];
            }

            $typeLabel = $row->entity_type === 'lead' ? 'Lead' : 'Deal';
            $entity = [
                $typeLabel,
                $row->title ?: '-',
                $row->status ?: '-',
                $row->stage ?: '-',
                $row->owner_name ?: '-',
                is_numeric($row->amount) ? $row->amount : ($row->amount ?: '-'),
            ];

            foreach ($activities as $i => $activity) {
                if ($i === 0) {
                    // Lead/Deal details shown only on the first activity row
                    $rows[] = array_merge($entity, $activity);
                } else {
                    // Keep identity columns empty for clean merge under the same lead/deal
                    $rows[] = array_merge(['', '', '', '', '', ''], $activity);
                }
            }

            $groups[] = [
                'start' => $excelRow,
                'count' => count($activities),
            ];
            $excelRow += count($activities);
        }

        $periodLabels = [
            'last_week' => 'Last 7 Days',
            'this_week' => 'This Week',
            'between' => 'Custom Range',
        ];
        $typeLabels = [
            'all' => 'Leads & Deals',
            'lead' => 'Leads Only',
            'deal' => 'Deals Only',
        ];
        $selectedUserLabel = 'All Users';
        if (!empty($data['user_id'])) {
            $selectedUserLabel = $userNames[$data['user_id']] ?? ('User #' . $data['user_id']);
        }

        $meta = [
            'title' => 'Weekly Activity Report',
            'date_range' => $data['start']->format('d M Y') . ' – ' . $data['end']->format('d M Y'),
            'period_label' => self::periodText($data['period'], $data['start']->toDateString(), $data['end']->toDateString()),
            'type_label' => $typeLabels[$data['type']] ?? $data['type'],
            'user_label' => $selectedUserLabel,
            'generated_on' => now()->format('d M Y h:i A'),
        ];

        $export = new WeeklyActivityExport($rows, $groups, $meta);
        $fileName = 'weekly_activity_' . $data['start']->format('Ymd') . '_' . $data['end']->format('Ymd') . '_' . now()->format('His') . '.xlsx';

        return Excel::download($export, $fileName);
    }

    /**
     * Shared dataset for weekly activity view + Excel export.
    */
    private function buildWeeklyActivityDataset(Request $request): array
    {
        $user = auth()->user();
        [$startStr, $endStr, $period] = self::resolveReportPeriod($request, 'last_week');
        $start = Carbon::parse($startStr)->startOfDay();
        $end = Carbon::parse($endStr)->endOfDay();
        $type = $request->get('type', 'all');

        $ownerIds = null;
        if ($user->crm_role_type === 2) {
            $ownerIds = \App\Models\User::where('assign_manager', $user->id)
                ->pluck('id')
                ->push($user->id)
                ->unique()
                ->values();
        } elseif (in_array($user->crm_role_type, [3, '3', 'employee'], true)) {
            $ownerIds = collect([$user->id]);
        }

        // Users available in the filter dropdown (active users only)
        if ($ownerIds !== null) {
            $users = \App\Models\User::whereIn('id', $ownerIds)
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            $users = \App\Models\User::where('crm_role_type', '!=', 0)
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // Optional user-wise filter (lead/deal owner) — only active users allowed
        $selectedUserId = $request->get('user_id');
        if ($selectedUserId) {
            $selectedUserId = (int) $selectedUserId;
            $allowed = $users->contains('id', $selectedUserId);
            if ($allowed) {
                $ownerIds = collect([$selectedUserId]);
            } else {
                $selectedUserId = null;
            }
        } else {
            $selectedUserId = null;
        }

        $includeLeads = in_array($type, ['all', 'lead'], true);
        $includeDeals = in_array($type, ['all', 'deal'], true);

        $noteRelated = \App\Models\Note::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                ->orWhereBetween('updated_at', [$start, $end]);
            })
            ->whereIn('related_type', ['lead', 'deal'])
            ->get(['related_type', 'related_id']);

        $meetingRelated = \App\Models\Meeting::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                ->orWhereBetween('updated_at', [$start, $end]);
            })
            ->whereIn('related_type', ['lead', 'deal'])
            ->get(['related_type', 'related_id']);

        $taskRelated = \App\Models\Task::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                ->orWhereBetween('updated_at', [$start, $end]);
            })
            ->whereIn('related_type', ['lead', 'deal'])
            ->get(['related_type', 'related_id']);

        $callRelated = \App\Models\Call::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                ->orWhereBetween('updated_at', [$start, $end])
                ->orWhereBetween('start_at', [$start, $end]);
            })
            ->whereIn('related_type', ['lead', 'deal'])
            ->get(['related_type', 'related_id']);

        $allRelated = $noteRelated->concat($meetingRelated)->concat($taskRelated)->concat($callRelated);
        $leadIdsFromActivities = $allRelated->where('related_type', 'lead')->pluck('related_id')->unique()->values();
        $dealIdsFromActivities = $allRelated->where('related_type', 'deal')->pluck('related_id')->unique()->values();

        $userNames = \App\Models\User::pluck('name', 'id');
        $records = collect();

        if ($includeLeads) {
            $leadQuery = Lead::with('owner')->whereIn('id', $leadIdsFromActivities)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'Junk Lead');
                });
            if ($ownerIds !== null) {
                $leadQuery->whereIn('user_owner_id', $ownerIds);
            }
            $leads = $leadQuery->orderByDesc('updated_at')->get();
            $leadIds = $leads->pluck('id');

            $notesByLead = $this->groupActivitiesByRelated(
                \App\Models\Note::where('related_type', 'lead')->whereIn('related_id', $leadIds)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                    })->orderByDesc('updated_at')->get()
            );
            $meetingsByLead = $this->groupActivitiesByRelated(
                \App\Models\Meeting::where('related_type', 'lead')->whereIn('related_id', $leadIds)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                    })->orderByDesc('updated_at')->get()
            );
            $tasksByLead = $this->groupActivitiesByRelated(
                \App\Models\Task::where('related_type', 'lead')->whereIn('related_id', $leadIds)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                    })->orderByDesc('updated_at')->get()
            );
            $callsByLead = $this->groupActivitiesByRelated(
                \App\Models\Call::where('related_type', 'lead')->whereIn('related_id', $leadIds)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end])
                        ->orWhereBetween('start_at', [$start, $end]);
                    })->orderByDesc('start_at')->get()
            );

            foreach ($leads as $lead) {
                $records->push((object) [
                    'id' => $lead->id,
                    'entity_type' => 'lead',
                    'title' => $lead->title,
                    'status' => $lead->status,
                    'stage' => null,
                    'owner_name' => $lead->owner->name ?? ($userNames[$lead->user_owner_id] ?? '-'),
                    'amount' => $lead->amount,
                    'notes' => $notesByLead->get($lead->id, collect()),
                    'meetings' => $meetingsByLead->get($lead->id, collect()),
                    'tasks' => $tasksByLead->get($lead->id, collect()),
                    'calls' => $callsByLead->get($lead->id, collect()),
                ]);
            }
        }

        if ($includeDeals) {
            $dealQuery = Deal::with('owner')->whereIn('id', $dealIdsFromActivities);
            if ($ownerIds !== null) {
                $dealQuery->whereIn('user_owner_id', $ownerIds);
            }
            $deals = $dealQuery->orderByDesc('updated_at')->get();
            $dealIds = $deals->pluck('id');

            $notesByDeal = $this->groupActivitiesByRelated(
                \App\Models\Note::where('related_type', 'deal')->whereIn('related_id', $dealIds)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                    })->orderByDesc('updated_at')->get()
            );
            $meetingsByDeal = $this->groupActivitiesByRelated(
                \App\Models\Meeting::where('related_type', 'deal')->whereIn('related_id', $dealIds)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                    })->orderByDesc('updated_at')->get()
            );
            $tasksByDeal = $this->groupActivitiesByRelated(
                \App\Models\Task::where('related_type', 'deal')->whereIn('related_id', $dealIds)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                    })->orderByDesc('updated_at')->get()
            );
            $callsByDeal = $this->groupActivitiesByRelated(
                \App\Models\Call::where('related_type', 'deal')->whereIn('related_id', $dealIds)
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end])
                        ->orWhereBetween('start_at', [$start, $end]);
                    })->orderByDesc('start_at')->get()
            );

            foreach ($deals as $deal) {
                $records->push((object) [
                    'id' => $deal->id,
                    'entity_type' => 'deal',
                    'title' => $deal->title,
                    'status' => $deal->status,
                    'stage' => $deal->stage_name,
                    'owner_name' => $deal->owner->name ?? ($userNames[$deal->user_owner_id] ?? '-'),
                    'amount' => $deal->amount,
                    'notes' => $notesByDeal->get($deal->id, collect()),
                    'meetings' => $meetingsByDeal->get($deal->id, collect()),
                    'tasks' => $tasksByDeal->get($deal->id, collect()),
                    'calls' => $callsByDeal->get($deal->id, collect()),
                ]);
            }
        }

        return [
            'records' => $records,
            'period' => $period,
            'type' => $type,
            'start' => $start,
            'end' => $end,
            'userNames' => $userNames,
            'users' => $users,
            'user_id' => $selectedUserId,
        ];
    }

    private function groupActivitiesByRelated($collection)
    {
        return $collection->groupBy('related_id');
    }
}
