<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\FinancialYear;
use App\Support\RecordAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    //Lead report filer by report period - today, last-1-week, this-month, last-month,  yearly, between-dates with user token
    public function leadReport(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $filter = $request->get('filter', 'today');
       
        // $startDate = $request->input('start_date');
        // $endDate = $request->input('end_date');
        // Get current financial year
        $fy = FinancialYear::where('active', 1)->first();
        // Validate report period
        $validPeriods = ['today', 'last_week', 'this_month', 'last_month', 'yearly', 'between'];
        if (!in_array($filter, $validPeriods)) {
            return response()->json(['error' => 'Invalid report period'], 400);
        }

        $baseQuery = Lead::query();

        // Role-based filtering
        if (RecordAccess::isAdmin($user)) { // Admin
            // Admin can view all leads
            $baseQuery = Lead::query();
        } elseif (RecordAccess::isManager($user)) { // Manager
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
            
            $baseQuery->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);
                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });

            
        } else { // Employee
            $baseQuery->where('user_owner_id', $user->id);
        }

        if ($filter === 'today') {
            $baseQuery->whereDate('created_at', now());
        } elseif ($filter === 'last_week') {
            $baseQuery->where('created_at', '>=', now()->subWeek());
        } elseif ($filter === 'this_month') {
            $baseQuery->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        } elseif ($filter === 'last_month') {
            $baseQuery->whereMonth('created_at', now()->subMonth()->month)
                  ->whereYear('created_at', now()->subMonth()->year);
        } elseif ($filter === 'yearly') {
            // For analytics 'yearly' use the selected financial year when available, otherwise use calendar year
            if ($fy) {
                $start = Carbon::parse($fy->from_date)->startOfDay()->toDateString();
                $end = Carbon::parse($fy->to_date)->endOfDay()->toDateString();
                $baseQuery->whereDate('created_at', '>=', $start)
                          ->whereDate('created_at', '<=', $end);
            } else {
                $baseQuery->whereYear('created_at', now()->year);

                print($baseQuery->get()); exit;
            }
        } elseif ($filter === 'between') {
            // prefer request values; if missing and we have an FY, use its range
            $start = $request->get('start_date');
            $end = $request->get('end_date');
            if ((!$start || !$end) && $fy) {
                $start = Carbon::parse($fy->from_date)->startOfDay()->toDateString();
                $end = Carbon::parse($fy->to_date)->endOfDay()->toDateString();
            }
            if ($start && $end) {
                $baseQuery->whereDate('created_at', '>=', Carbon::parse($start)->startOfDay()->toDateString())
                          ->whereDate('created_at', '<=', Carbon::parse($end)->endOfDay()->toDateString());
            }
        }
        $leads = (clone $baseQuery)->orderByDesc('created_at')->with(['customer', 'organization', 'person', 'owner', 'leadSource'])->get();

        $leads->transform(function ($lead) {
            return [
                'id' => $lead->id,
                'title' => $lead->title,
                'customer_name' => $lead->customer ? $lead->customer->name : null,
                'organization_name' => $lead->organization ? $lead->organization->name : null,
                'contact_person_name' => $lead->person ? $lead->person->first_name. ' ' . $lead->person->last_name : null,
                'lead_source' => $lead->leadSource ? $lead->leadSource->name : null,
                'status' => $lead->status,
                'priority' => $lead->label,
                'created_at' => $lead->created_at->format('d-m-Y H:i'),
                'owner_name' => $lead->owner ? $lead->owner->name : null,
            ];
        });

        // Debugging: Log the base query and filters
        \Log::info('Lead Report Debugging', [
            'user_id' => $user->id,
            'crm_role_type' => $user->crm_role_type,
            'filter' => $filter,
            'query' => $baseQuery->toSql(),
        ]);

        return response()->json(['leads' => $leads], 200);
    }

    //Deal report filer by report period - today, last-1-week, this-month, last-month,  yearly, between-dates with user token
    public function dealReport(Request $request)
    {
        // Similar implementation as leadReport but for deals
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        // Implementation would go here...
        $filter = $request->get('filter', 'today');
        $stageFilter = $request->get('stage', '');

         $employeeIds = collect();
        if (RecordAccess::isManager($user)) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id )->pluck('id');
        }

        $baseQuery = \App\Models\Deal::query();

        // start/end for view defaults
        $start = $request->get('start_date');
        $end = $request->get('end_date');

        $fy = FinancialYear::where('active', 1)->first();
        if (RecordAccess::isManager($user)) { // Manager
            $baseQuery->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);
                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        }
        // Only show own records for employees
        if (!RecordAccess::isAdmin($user) && !RecordAccess::isManager($user)) {
            $baseQuery->where('user_owner_id', $user->id);
        }

        if ($filter === 'today') {
            // If user asked for Closed Won deals for today, use close_date (deal closed today)
            if ($stageFilter === 'Closed Won') {
                $baseQuery->whereDate('close_date', now());
            } else {
                $baseQuery->whereDate('created_at', now());
            }
        } elseif ($filter === 'last_week') {
            $baseQuery->where('created_at', '>=', now()->subWeek());
        } elseif ($filter === 'this_month') {
            $baseQuery->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        } elseif ($filter === 'last_month') {
            $baseQuery->whereMonth('created_at', now()->subMonth()->month)
                  ->whereYear('created_at', now()->subMonth()->year);
        } elseif ($filter === 'yearly') {
            // Use selected/active FY when available for yearly analytics
            if ($fy) {
                $start = Carbon::parse($fy->from_date)->startOfDay()->toDateString();
                $end = Carbon::parse($fy->to_date)->endOfDay()->toDateString();
                $baseQuery->whereDate('created_at', '>=', $start)
                          ->whereDate('created_at', '<=', $end);
            } else {
                $baseQuery->whereYear('created_at', now()->year);
            }
        } elseif ($filter === 'between') {
            // prefer request values; if missing and we have FY, use the FY range
            $start = $request->get('start_date');
            $end = $request->get('end_date');
            if ((!$start || !$end) && $fy) {
                $start = Carbon::parse($fy->from_date)->startOfDay()->toDateString();
                $end = Carbon::parse($fy->to_date)->endOfDay()->toDateString();
            }
            if ($start && $end) {
                $baseQuery->whereDate('created_at', '>=', $start)
                          ->whereDate('created_at', '<=', $end);
            }
        }
        // removed extra closing brace
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

        $deals = (clone $baseQuery)->orderByDesc('created_at')->with(['organization', 'person', 'owner', 'dealSource', 'stage'])->get();
        $deals->transform(function ($deal) {
            return [
                'id' => $deal->id,
                'title' => $deal->title,
                'organization_name' => $deal->organization ? $deal->organization->name : null,
                'contact_person_name' => $deal->person ? $deal->person->first_name. ' ' . $deal->person->last_name : null,
                'deal_source' => $deal->dealSource ? $deal->dealSource->name : null,
                'amount' => $deal->amount,
                'stage' => $deal->stage_name,
                'stage_id' => $deal->stage_id,
                'created_at' =>  $deal->created_at->format('d-m-Y H:i'),
                'owner_name' => $deal->owner ? $deal->owner->name : null,
            ];
        });

        return response()->json(['deals' => $deals], 200);
    }

    // End of Deal report function

    //Revenue report function can be added here
    public function revenueReport(Request $request)
    {
        // Implementation would go here...
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $filter = $request->get('filter', 'this_month');
        $employeeIds = collect();
        if (RecordAccess::isManager($user)) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }
        $baseQuery = \App\Models\Deal::query()->where('stage_id', \App\Models\Stage::closedWonId());
        if (RecordAccess::isManager($user)) { // Manager
            $baseQuery->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);
                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        }
        // Only show own records for employees
        if (!RecordAccess::isAdmin($user) && !RecordAccess::isManager($user)) {
            $baseQuery->where('user_owner_id', $user->id);
        }

        // Initialize start/end from request; may be overridden by FY logic
        $start = $request->get('start_date');
        $end = $request->get('end_date');
        $fy = FinancialYear::where('active', 1)->first();

        if ($filter === 'all') {
            // If a financial year is selected, limit 'all' to that FY range only
            if ($fy) {
                $start = Carbon::parse($fy->from_date)->startOfDay()->toDateString();
                $end = Carbon::parse($fy->to_date)->endOfDay()->toDateString();
                $baseQuery->whereDate('close_date', '>=', $start)
                          ->whereDate('close_date', '<=', $end);
            } else {
                // No FY selected: show all closed won deals across years
            }
        } elseif ($filter === 'today') {
            $baseQuery->whereDate('close_date', now());
        } elseif ($filter === 'last_7_days') {
            $baseQuery->where('close_date', '>=', now()->subDays(7));
        } elseif ($filter === 'this_month') {
            $baseQuery->whereMonth('close_date', now()->month)
                  ->whereYear('close_date', now()->year);
        } elseif ($filter === 'last_month') {
            $baseQuery->whereMonth('close_date', now()->subMonth()->month)
                  ->whereYear('close_date', now()->subMonth()->year);
        } elseif ($filter === 'this_year') {
            // Use selected/active financial year as the 'this_year' period when available
            if ($fy) {
                $start = Carbon::parse($fy->from_date)->startOfDay()->toDateString();
                $end = Carbon::parse($fy->to_date)->endOfDay()->toDateString();
                $baseQuery->whereDate('close_date', '>=', $start)
                          ->whereDate('close_date', '<=', $end);
            } else {
                $baseQuery->whereYear('close_date', now()->year);
            }
        } elseif ($filter === 'last_year') {
            // When FY selected, consider 'last_year' relative to calendar year; keep default behaviour
            $baseQuery->whereYear('close_date', now()->subYear()->year);
        } elseif ($filter === 'between') {
            $start = $request->get('start_date');
            $end = $request->get('end_date');
            // If dates are not provided and we have a selected FY, use FY range
            if ((!$start || !$end) && $fy) {
                $start = Carbon::parse($fy->from_date)->startOfDay()->toDateString();
                $end = Carbon::parse($fy->to_date)->endOfDay()->toDateString();
            }
            if ($start && $end) {
                $baseQuery->whereDate('close_date', '>=', $start)
                          ->whereDate('close_date', '<=', $end);
            }
        }

        $month = $request->get('month'); // e.g. 'Aug 2025'
        $deals = (clone $baseQuery)
                ->whereRaw("DATE_FORMAT(close_date, '%b %Y') = ?", [$month])
                ->with(['person', 'organization'])
                ->orderBy('close_date')
                ->get();
         $deals = (clone $baseQuery)->orderBy('close_date')->get();

        // Group by month and year
        $monthlyRevenue = collect();
        $grouped = $deals->groupBy(function($deal) {
            return Carbon::parse($deal->close_date)->format('M Y');
        });
        $totalAmount = 0;
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

        $monthlyRevenue->transform(function ($item) use (&$totalAmount) {
            $totalAmount += $item['amount'];
            return $item;
        }); 

        return response()->json(['monthly_revenue' => $monthlyRevenue, 'total_amount' => $totalAmount], 200);
    }

    // End of Revenue report function

    //Converted leads report function can be added here
    public function convertedLeadsReport(Request $request)
    {
        // Similar implementation as leadReport but for converted leads
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        $fy = FinancialYear::where('active', 1)->first();
        // Implementation would go here...
        $employeeIds = collect();
        if (RecordAccess::isManager($user)) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
        }

         $query = \App\Models\Lead::with(['customer', 'organization', 'person', 'owner'])
            ->whereNotNull('converted_at');
        // If the user is a manager, include their employees' leads
        if (RecordAccess::isManager($user)) { // Manager
            $query->where(function ($q) use ($user, $employeeIds) {
                $q->where('user_owner_id', $user->id);

                if ($employeeIds->isNotEmpty()) {
                    $q->orWhereIn('user_owner_id', $employeeIds);
                }
            });
        }

        if (!RecordAccess::isAdmin($user) && !RecordAccess::isManager($user)) {
            $query->where('user_owner_id', $user->id);
        }
        // If we have a financial year (selected or active), filter converted leads to that FY
        if ($fy) {
            $start = Carbon::parse($fy->from_date)->startOfDay();
            $end = Carbon::parse($fy->to_date)->endOfDay();
            $query->whereBetween('converted_at', [$start, $end]);
        }

        $leads = $query->orderByDesc('converted_at')->get();

        // Fetch category names for each lead
        $leads->transform(function ($lead) {
            $categoryIds = explode(',', $lead->category ?? '');
            return [
                'id' => $lead->id,
                'title' => $lead->title,
                'customer_name' => $lead->customer ? $lead->customer->name : null,
                'organization_name' => $lead->organization ? $lead->organization->name : null,
                'contact_person_name' => $lead->person ? $lead->person->first_name. ' ' . $lead->person->last_name : null,
                'lead_source' => $lead->leadSource ? $lead->leadSource->name : null,
                'status' => $lead->status,
                'priority' => $lead->label,
                'created_at' => $lead->created_at->format('d-m-Y H:i'),
                'owner_name' => $lead->owner ? $lead->owner->name : null,
                'converted_at' => $lead->converted_at ? $lead->converted_at->format('d-m-Y H:i') : null,
                'category_names' => \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray(),
            ];
        });

        return response()->json(['converted_leads' => $leads], 200);
    }
    // End of Converted leads report function

    //User Performance report function can be added here
    public function userPerformanceReport(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        // Implementation would go here...
        $isAdminOrManager = RecordAccess::isAdmin($user) || RecordAccess::isManager($user);
        // If regular employee, only show their own report
        $user_id = $request->input('user_id');
        if (!$isAdminOrManager && !$user_id) {
            $user_id = $user->id;
        }elseif (!$isAdminOrManager && $user_id && $user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to user performance report',
            ], 403);
        }

        // Fetch leads created by the user
        $year = $request->input('year', null);
        $month = $request->input('month', null);

        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $fy = $activeFy;
        $query = \App\Models\UserMonthlySales::with('user')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        // Determine FY range if available
        $fyStart = null;
        $fyEnd = null;
        if (!empty($fy)) {
            try {
                $fyStart = \Carbon\Carbon::parse($fy->from_date)->startOfMonth();
                $fyEnd = \Carbon\Carbon::parse($fy->to_date)->endOfMonth();
            } catch (\Exception $e) {
                $fyStart = null;
                $fyEnd = null;
            }
        }

        // Build requested months list (intersected with FY if FY is present)
        $months = [];

        // Helper: check if a year/month is inside FY range
        $insideFy = function($y, $m) use ($fyStart, $fyEnd) {
            if (empty($fyStart) || empty($fyEnd)) return true; // no FY to check against
            try {
                $d = \Carbon\Carbon::create($y, $m, 1)->startOfMonth();
            } catch (\Exception $e) {
                return false;
            }
            return $d->gte($fyStart->copy()->startOfMonth()) && $d->lte($fyEnd->copy()->startOfMonth());
        };

        // If explicit year and month provided -> validate against FY and use that single month
        if ($request->filled('year') || $request->filled('month')) {
            // when one of them is missing, we interpret accordingly (see below)
            $reqYear = $request->filled('year') ? (int)$year : null;
            $reqMonth = $request->filled('month') ? (int)$month : null;

            if ($reqYear && $reqMonth) {
                // both provided: only include if inside FY (if FY exists)
                if ($insideFy($reqYear, $reqMonth)) {
                    $months[] = ['year' => $reqYear, 'month' => $reqMonth];
                } else {
                    // explicit month/year outside FY -> no data
                    $months = [];
                }
            } elseif ($reqYear && !$reqMonth) {
                // year provided: include all months within FY that match this year (if FY),
                // otherwise include all months 1..12 for that year
                if (!empty($fyStart) && !empty($fyEnd)) {
                    $p = $fyStart->copy();
                    while ($p->lte($fyEnd)) {
                        if ((int)$p->format('Y') === $reqYear) {
                            $months[] = ['year' => (int)$p->format('Y'), 'month' => (int)$p->format('n')];
                        }
                        $p->addMonth();
                    }
                } else {
                    for ($m = 1; $m <= 12; $m++) {
                        $months[] = ['year' => $reqYear, 'month' => $m];
                    }
                }
            } elseif ($reqMonth && !$reqYear) {
                // month provided but no year: include all FY months that match this month,
                // or if no FY, include current year-only month
                if (!empty($fyStart) && !empty($fyEnd)) {
                    $p = $fyStart->copy();
                    while ($p->lte($fyEnd)) {
                        if ((int)$p->format('n') === $reqMonth) {
                            $months[] = ['year' => (int)$p->format('Y'), 'month' => (int)$p->format('n')];
                        }
                        $p->addMonth();
                    }
                } else {
                    $months[] = ['year' => (int)date('Y'), 'month' => $reqMonth];
                }
            }
        } else {
            // No explicit year/month: show current month only
            $months[] = ['year' => (int)date('Y'), 'month' => (int)date('n')];
        }

        // If months list is empty, we will return no data (respecting FY constraints)
        $report = [];

        if (!empty($months)) {
            // Fetch monthly sales rows for these months and optional user filter
            $queryMonths = \App\Models\UserMonthlySales::with('user')->orderBy('year', 'desc')->orderBy('month', 'desc');
            if(RecordAccess::isManager($user)) {
                // If the user is a manager, limit to their team members
                $teamMemberIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id')->toArray();
                $queryMonths->where(function ($q) use ($teamMemberIds, $user) {
                    $q->whereIn('user_id', $teamMemberIds)
                        ->orWhere('user_id', $user->id);
                });
            }
            if ($user_id) {
                $queryMonths->where('user_id', $user_id);
            }
            // add where clauses for each year/month pair
            $queryMonths->where(function($q) use ($months) {
                foreach ($months as $m) {
                    $q->orWhere(function($q2) use ($m) {
                        $q2->where('year', $m['year'])->where('month', $m['month']);
                    });
                }
            });
            $monthlySales = $queryMonths->get();

            // map existing rows
            $map = [];
            foreach ($monthlySales as $row) {
                $map[$row->user_id . '|' . $row->year . '|' . $row->month] = $row;
            }

            // get users list for output (respect crm_role_type)
            if(RecordAccess::isManager($user)) {
                // If the user is a manager, limit to their team members
                $allUsers = \App\Models\User::where('assign_manager', $user->id)
                    ->orWhere('id', $user->id)
                    ->orderBy('name')
                    ->get();
            } else {
            $allUsers = \App\Models\User::where('crm_role_type', '!=', 0)->orderBy('name')->get();
            }
            foreach ($allUsers as $u) {
                if ($user_id && $user_id != $u->id) continue;
                foreach ($months as $m) {
                    $key = $u->id . '|' . $m['year'] . '|' . $m['month'];
                    if (isset($map[$key])) {
                        $row = $map[$key];
                        $report[] = [
                            'user_id' => $row->user_id,
                            'user_name' => $row->user ? $row->user->name : $u->name,
                            'year' => $row->year,
                            'month' => $row->month,
                            'month_name' => Carbon::create()->month($row->month)->format('F'),
                            'sales_target' => $row->sales_target,
                            'achieved_sales' => $row->achieved_sales,
                            'progress' => $row->sales_target > 0 ? round(($row->achieved_sales / $row->sales_target) * 100, 2) : 0
                        ];
                    } else {
                        $report[] = [
                            'user_id' => $u->id,
                            'user_name' => $u->name,
                            'year' => $m['year'],
                            'month' => $m['month'],
                            'month_name' => Carbon::create()->month($m['month'])->format('F'),
                            'sales_target' => 0,
                            'achieved_sales' => 0,
                            'progress' => 0
                        ];
                    }
                }
            }
        }

        return response()->json(['user_performance' => $report], 200);
    }

    /**
     * Weekly activity report for leads & deals.
     * Each lead/deal includes Status, Owner, Amount and related Notes, Meetings, Tasks (with status).
     *
     * Query params:
     * - filter: last_week (default) | this_week | between
     * - start_date / end_date: required when filter=between
     * - type: all (default) | lead | deal
     */
    public function weeklyActivityReport(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $filter = $request->get('filter', 'last_week');
        $type = $request->get('type', 'all'); // all | lead | deal

        if ($filter === 'this_week') {
            $start = now()->startOfWeek(); // Monday
            $end = now()->endOfWeek();     // Sunday
        } elseif ($filter === 'between') {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            if (!$startDate || !$endDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'start_date and end_date are required for between filter',
                ], 400);
            }
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            // Last 7 days = previous calendar week (last Monday → last Sunday)
            $start = now()->startOfWeek()->subWeek()->startOfDay();
            $end = now()->startOfWeek()->subWeek()->endOfWeek();
        }

        $ownerIds = $this->accessibleOwnerIds($user);

        $includeLeads = in_array($type, ['all', 'lead'], true);
        $includeDeals = in_array($type, ['all', 'deal'], true);

        $leadIdsFromActivities = collect();
        $dealIdsFromActivities = collect();

        if ($includeLeads || $includeDeals) {
            $noteRelated = Note::query()
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                })
                ->whereIn('related_type', ['lead', 'deal'])
                ->get(['related_type', 'related_id']);

            $meetingRelated = Meeting::query()
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end]);
                })
                ->whereIn('related_type', ['lead', 'deal'])
                ->get(['related_type', 'related_id']);

            $taskRelated = Task::query()
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
        }

        $leadsPayload = [];
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
            $notesByLead = $this->mapNotesByRelated('lead', $leadIds, $start, $end);
            $meetingsByLead = $this->mapMeetingsByRelated('lead', $leadIds, $start, $end);
            $tasksByLead = $this->mapTasksByRelated('lead', $leadIds, $start, $end);
            $callsByLead = $this->mapCallsByRelated('lead', $leadIds, $start, $end);

            $leadsPayload = $leads->map(function ($lead) use ($notesByLead, $meetingsByLead, $tasksByLead, $callsByLead) {
                return [
                    'id' => $lead->id,
                    'type' => 'lead',
                    'title' => $lead->title,
                    'status' => $lead->status,
                    'owner_id' => $lead->user_owner_id,
                    'owner_name' => $lead->owner->name ?? null,
                    'amount' => $lead->amount,
                    'activities' => [
                        'notes' => $notesByLead->get($lead->id, collect())->values(),
                        'meetings' => $meetingsByLead->get($lead->id, collect())->values(),
                        'tasks' => $tasksByLead->get($lead->id, collect())->values(),
                        'calls' => $callsByLead->get($lead->id, collect())->values(),
                    ],
                ];
            })->values();
        }

        $dealsPayload = [];
        if ($includeDeals) {
            $dealQuery = Deal::with('owner')->whereIn('id', $dealIdsFromActivities);
            if ($ownerIds !== null) {
                $dealQuery->whereIn('user_owner_id', $ownerIds);
            }
            $deals = $dealQuery->orderByDesc('updated_at')->get();

            $dealIds = $deals->pluck('id');
            $notesByDeal = $this->mapNotesByRelated('deal', $dealIds, $start, $end);
            $meetingsByDeal = $this->mapMeetingsByRelated('deal', $dealIds, $start, $end);
            $tasksByDeal = $this->mapTasksByRelated('deal', $dealIds, $start, $end);
            $callsByDeal = $this->mapCallsByRelated('deal', $dealIds, $start, $end);

            $dealsPayload = $deals->map(function ($deal) use ($notesByDeal, $meetingsByDeal, $tasksByDeal, $callsByDeal) {
                return [
                    'id' => $deal->id,
                    'type' => 'deal',
                    'title' => $deal->title,
                    'status' => $deal->status,
                    'stage' => $deal->stage_name,
                    'owner_id' => $deal->user_owner_id,
                    'owner_name' => $deal->owner->name ?? null,
                    'amount' => $deal->amount,
                    'activities' => [
                        'notes' => $notesByDeal->get($deal->id, collect())->values(),
                        'meetings' => $meetingsByDeal->get($deal->id, collect())->values(),
                        'tasks' => $tasksByDeal->get($deal->id, collect())->values(),
                        'calls' => $callsByDeal->get($deal->id, collect())->values(),
                    ],
                ];
            })->values();
        }

        return response()->json([
            'success' => true,
            'period' => [
                'filter' => $filter,
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ],
            'leads' => $leadsPayload,
            'deals' => $dealsPayload,
        ], 200);
    }

    /**
     * null = all owners (admin), otherwise list of allowed user_owner_id values.
     */
    private function accessibleOwnerIds($user)
    {
        if (RecordAccess::isAdmin($user)) {
            return null;
        }

        if (RecordAccess::isManager($user)) {
            $employeeIds = User::where('assign_manager', $user->id)->pluck('id');
            return $employeeIds->push($user->id)->unique()->values();
        }

        return collect([$user->id]);
    }

    private function mapNotesByRelated(string $relatedType, $relatedIds, Carbon $start, Carbon $end)
    {
        if ($relatedIds->isEmpty()) {
            return collect();
        }

        $userNames = User::pluck('name', 'id');

        return Note::where('related_type', $relatedType)
            ->whereIn('related_id', $relatedIds)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('updated_at', [$start, $end]);
            })
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('related_id')
            ->map(function ($notes) use ($userNames) {
                return $notes->map(function ($note) use ($userNames) {
                    return [
                        'id' => $note->id,
                        'content' => $note->content,
                        'created_by' => $note->created_by,
                        'created_by_name' => $userNames[$note->created_by] ?? null,
                        'created_at' => $note->created_at ? Carbon::parse($note->created_at)->format('d-m-Y h:i A') : null,
                        'updated_at' => $note->updated_at ? Carbon::parse($note->updated_at)->format('d-m-Y h:i A') : null,
                    ];
                });
            });
    }

    private function mapMeetingsByRelated(string $relatedType, $relatedIds, Carbon $start, Carbon $end)
    {
        if ($relatedIds->isEmpty()) {
            return collect();
        }

        $userNames = User::pluck('name', 'id');

        return Meeting::where('related_type', $relatedType)
            ->whereIn('related_id', $relatedIds)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('updated_at', [$start, $end]);
            })
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('related_id')
            ->map(function ($meetings) use ($userNames) {
                return $meetings->map(function ($meeting) use ($userNames) {
                    return [
                        'id' => $meeting->id,
                        'subject' => $meeting->name,
                        'description' => $meeting->description,
                        'status' => null,
                        'start_time' => $meeting->start_at ? Carbon::parse($meeting->start_at)->format('d-m-Y h:i A') : null,
                        'end_time' => $meeting->finish_at ? Carbon::parse($meeting->finish_at)->format('d-m-Y h:i A') : null,
                        'venue' => $meeting->venue ?? null,
                        'owner_name' => $userNames[$meeting->user_owner_id] ?? null,
                        'created_at' => $meeting->created_at ? Carbon::parse($meeting->created_at)->format('d-m-Y h:i A') : null,
                        'updated_at' => $meeting->updated_at ? Carbon::parse($meeting->updated_at)->format('d-m-Y h:i A') : null,
                    ];
                });
            });
    }

    private function mapTasksByRelated(string $relatedType, $relatedIds, Carbon $start, Carbon $end)
    {
        if ($relatedIds->isEmpty()) {
            return collect();
        }

        $userNames = User::pluck('name', 'id');

        return Task::where('related_type', $relatedType)
            ->whereIn('related_id', $relatedIds)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('updated_at', [$start, $end]);
            })
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('related_id')
            ->map(function ($tasks) use ($userNames) {
                return $tasks->map(function ($task) use ($userNames) {
                    return [
                        'id' => $task->id,
                        'title' => $task->name,
                        'description' => $task->description,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'due_date' => $task->due_at ? Carbon::parse($task->due_at)->format('d-m-Y h:i A') : null,
                        'owner_name' => $userNames[$task->user_owner_id] ?? null,
                        'created_at' => $task->created_at ? Carbon::parse($task->created_at)->format('d-m-Y h:i A') : null,
                        'updated_at' => $task->updated_at ? Carbon::parse($task->updated_at)->format('d-m-Y h:i A') : null,
                    ];
                });
            });
    }

    private function mapCallsByRelated(string $relatedType, $relatedIds, Carbon $start, Carbon $end)
    {
        if ($relatedIds->isEmpty()) {
            return collect();
        }

        $userNames = User::pluck('name', 'id');

        return \App\Models\Call::where('related_type', $relatedType)
            ->whereIn('related_id', $relatedIds)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('updated_at', [$start, $end])
                    ->orWhereBetween('start_at', [$start, $end]);
            })
            ->orderByDesc('start_at')
            ->get()
            ->groupBy('related_id')
            ->map(function ($calls) use ($userNames) {
                return $calls->map(function ($call) use ($userNames) {
                    return [
                        'id' => $call->id,
                        'subject' => $call->name,
                        'description' => $call->description,
                        'start_time' => $call->start_at ? Carbon::parse($call->start_at)->format('d-m-Y h:i A') : null,
                        'end_time' => $call->finish_at ? Carbon::parse($call->finish_at)->format('d-m-Y h:i A') : null,
                        'location' => $call->location ?? null,
                        'owner_name' => $userNames[$call->user_owner_id] ?? ($userNames[$call->created_by] ?? null),
                        'created_at' => $call->created_at ? Carbon::parse($call->created_at)->format('d-m-Y h:i A') : null,
                        'updated_at' => $call->updated_at ? Carbon::parse($call->updated_at)->format('d-m-Y h:i A') : null,
                    ];
                });
            });
    }
}
