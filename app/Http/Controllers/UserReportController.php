<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\ProductCategory;
use App\Library\MpdfDocument;

class UserReportController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }
        [$start, $end, $period] = ReportController::resolveReportPeriod($request, 'this_month');
        $fromDate = $start;
        $toDate = $end;
        $userId = request('user_id');

        // Filter users (exclude super admin)
        $usersQuery = User::query();
        if ($userId) {
            $usersQuery->where('id', $userId);
        }
        $usersQuery->where('crm_role_type', '!=', '0'); // Exclude super admin
        $users = $usersQuery->get();

        // Filter leads
        $leadsQuery = Lead::query();
        if ($userId) {
            $leadsQuery->where('user_owner_id', $userId);
        }
        if ($fromDate) {
            $leadsQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $leadsQuery->whereDate('created_at', '<=', $toDate);
        }
        $leads = $leadsQuery->get();

        // Filter deals
        $dealsQuery = Deal::query();
        if ($userId) {
            $dealsQuery->where('user_owner_id', $userId);
        }
        if ($fromDate) {
            $dealsQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $dealsQuery->whereDate('created_at', '<=', $toDate);
        }
        $deals = $dealsQuery->get();

        // Get leads count per user
        $leadsCountQuery = Lead::query();
        if ($userId) {
            $leadsCountQuery->where('user_owner_id', $userId);
        }
        if ($fromDate) {
            $leadsCountQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $leadsCountQuery->whereDate('created_at', '<=', $toDate);
        }
        $leadsCountQuery->whereNull('converted_at');
        $leadsCount = $leadsCountQuery->get()->groupBy('user_owner_id')->map->count();

        // Get deals count per user
        $dealsCount = $deals->groupBy('user_owner_id')->map->count();

        // Filter converted leads for count
        $convertedLeadsCountQuery = Lead::query();
        if ($userId) {
            $convertedLeadsCountQuery->where('user_owner_id', $userId);
        }
        if ($fromDate) {
            $convertedLeadsCountQuery->whereDate('converted_at', '>=', $fromDate);
        }
        if ($toDate) {
            $convertedLeadsCountQuery->whereDate('converted_at', '<=', $toDate);
        }
        $convertedLeadsCountQuery->whereNotNull('converted_at');
        $convertedLeadsCount = $convertedLeadsCountQuery->get()->groupBy('user_owner_id')->map->count();

        // Prepare report data
        $report = $users->map(function ($user) use ($leadsCount, $dealsCount, $convertedLeadsCount) {
            return [
                'user' => $user,
                'leads_count' => $leadsCount[$user->id] ?? 0,
                'converted_leads_count' => $convertedLeadsCount[$user->id] ?? 0,
                'deals_count' => $dealsCount[$user->id] ?? 0,
            ];
        });

        return view('reports.user_report', compact('report', 'start', 'end', 'period', 'fromDate', 'toDate'));
    }

    public function convertedLeads($userId)
    {
         if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $fromDate = request('from_date');
        $toDate = request('to_date');
        $leadsQuery = \App\Models\Lead::where('user_owner_id', $userId)
            ->whereNotNull('converted_at');

        // If no explicit from/to provided, and a financial year is selected (or active exists),
        // restrict to that FY's converted_at range so the user report follows FY selection.
        if (!$fromDate && !$toDate) {
            $selectedFyId = session('selected_financial_year', null);
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($selectedFyId) {
                $fy = \App\Models\FinancialYear::find($selectedFyId);
            } else {
                $fy = $activeFy;
            }
            if (!empty($fy)) {
                $start = \Carbon\Carbon::parse($fy->from_date)->startOfDay()->toDateString();
                $end = \Carbon\Carbon::parse($fy->to_date)->endOfDay()->toDateString();
                $leadsQuery->whereDate('converted_at', '>=', $start)->whereDate('converted_at', '<=', $end);
            }
        }

        if ($fromDate) {
            $leadsQuery->whereDate('converted_at', '>=', $fromDate);
        }
        if ($toDate) {
            $leadsQuery->whereDate('converted_at', '<=', $toDate);
        }
        $leads = $leadsQuery->get();
        $html = '<table class="table table-striped">';
        $html .= '<thead><tr><th>Lead Title</th><th>Company Name</th><th>Status</th><th>Converted At</th></tr></thead><tbody>';
        foreach ($leads as $lead) {
            $company = $lead->organization ? $lead->organization->name : '-';
            $html .= '<tr>';
            $html .= '<td><a href="/leads/' . $lead->id . '" target="_blank">' . e($lead->title) . '</a></td>';
            $html .= '<td>' . e($company) . '</td>';
            $html .= '<td>' . e($lead->status) . '</td>';
            $html .= '<td>' . ($lead->converted_at ? $lead->converted_at->format('d-m-Y') : '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return response($html);
    }

    public function userDeals($userId)
    {
         if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $month = request('month');
        $startDate = request('start_date');
        $endDate = request('end_date');
        $stage = request('stage');
        $categoryId = request('category');
        $normalizedStage = strtolower(trim((string) $stage));
        $isClosedWonStage = in_array($normalizedStage, ['closed won', 'closed_won'], true);

        if ((!$startDate || !$endDate) && $month) {
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
        }

        $dealsQuery = \App\Models\Deal::where('user_owner_id', $userId);
        if ($startDate && $endDate) {
            if ($isClosedWonStage) {
                $dealsQuery->whereDate('close_date', '>=', $startDate)
                          ->whereDate('close_date', '<=', $endDate);
            } else {
                $dealsQuery->whereDate('created_at', '>=', $startDate)
                          ->whereDate('created_at', '<=', $endDate);
            }
        }
        if ($stage) {
            if ($isClosedWonStage) {
                $dealsQuery->where(function ($query) {
                    $query->where('stage_id', \App\Models\Stage::closedWonId())
                          ->orWhereRaw('LOWER(status) = ?', ['closed won']);
                });
            } else {
                $stageId = \App\Models\Stage::resolveId($stage);
                if ($stageId) {
                    $dealsQuery->where('stage_id', $stageId);
                }
            }
        }

        if (!empty($categoryId) && $categoryId !== 'all') {
            $dealsQuery->whereRaw('FIND_IN_SET(?, category)', [$categoryId]);
        }

        $deals = $dealsQuery->get();
        $categoryMap = ProductCategory::pluck('category_name', 'id');
        
        $html = '<table class="table table-striped">';
        $html .= '<thead><tr><th>Deal Title</th><th>Company Name</th><th>Contact Person</th><th>Category</th><th>Status</th><th>Amount</th><th>Created At</th><th>Closed Date</th></tr></thead><tbody>';
        foreach ($deals as $deal) {
            $company = $deal->organization ? $deal->organization->name : '-';
            $contact = $deal->person ? trim($deal->person->first_name . ' ' . $deal->person->last_name) : '-';
            $dealAmount = is_numeric($deal->amount) ? number_format((float) $deal->amount, 2) : '-';
            $categoryNames = '-';
            if (!empty($deal->category)) {
                $ids = collect(explode(',', (string) $deal->category))
                    ->map(fn($id) => trim($id))
                    ->filter();

                $names = $ids->map(fn($id) => $categoryMap->get($id))
                    ->filter()
                    ->implode(', ');

                if ($names !== '') {
                    $categoryNames = $names;
                }
            }
            $html .= '<tr>';
            $html .= '<td><a href="/deals/' . $deal->id . '" target="_blank">' . e($deal->title) . '</a></td>';
            $html .= '<td>' . e($company) . '</td>';
            $html .= '<td>' . e($contact) . '</td>';
            $html .= '<td>' . e($categoryNames) . '</td>';

            $html .= '<td>' . e($deal->status) . '</td>';
            $html .= '<td>' . e($dealAmount) . '</td>';
            $html .= '<td>' . ($deal->created_at ? date('d-m-Y', strtotime($deal->created_at)) : '-') . '</td>';
            $html .= '<td>' . ($deal->close_date ? date('d-m-Y', strtotime($deal->close_date)) : '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return response($html);
    }
    public function leadDetails($leadId)
    {
         if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $lead = \App\Models\Lead::findOrFail($leadId);
        $html = '<div>';
        $html .= '<h5>' . e($lead->title) . '</h5>';
        $html .= '<p><strong>Status:</strong> ' . e($lead->status) . '</p>';
        $html .= '<p><strong>Description:</strong> ' . e($lead->description) . '</p>';
        $html .= '<p><strong>Amount:</strong> ' . e($lead->amount) . '</p>';
        $html .= '<p><strong>Expected Close:</strong> ' . e($lead->expected_close) . '</p>';
        $html .= '</div>';
        return response($html);
    }
    public function userLeads($userId)
    {
         if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $month = request('month');
        $startDate = request('start_date');
        $endDate = request('end_date');
        $categoryId = request('category');

        if ((!$startDate || !$endDate) && $month) {
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
        }

        $leadsQuery = \App\Models\Lead::where('user_owner_id', $userId);
        if ($startDate && $endDate) {
            $leadsQuery->whereDate('created_at', '>=', $startDate)
                      ->whereDate('created_at', '<=', $endDate);
        }

        if (!empty($categoryId) && $categoryId !== 'all') {
            $leadsQuery->whereRaw('FIND_IN_SET(?, category)', [$categoryId]);
        }

        $leads = $leadsQuery->get();
        $categoryMap = ProductCategory::pluck('category_name', 'id');
        $html = '<table class="table table-striped">';
        $html .= '<thead><tr><th>Lead Title</th><th>Company Name</th><th>Contact Person</th><th>Category</th><th>Status</th><th>Created At</th></tr></thead><tbody>';
        foreach ($leads as $lead) {
            $company = $lead->organization ? $lead->organization->name : '-';
            $contact = $lead->person ? trim($lead->person->first_name . ' ' . $lead->person->last_name) : '-';
            $categoryNames = '-';
            if (!empty($lead->category)) {
                $ids = collect(explode(',', (string) $lead->category))
                    ->map(fn($id) => trim($id))
                    ->filter();

                $names = $ids->map(fn($id) => $categoryMap->get($id))
                    ->filter()
                    ->implode(', ');

                if ($names !== '') {
                    $categoryNames = $names;
                }
            }
            $html .= '<tr>';
            $html .= '<td><a href="/leads/' . $lead->id . '" target="_blank">' . e($lead->title) . '</a></td>';
            $html .= '<td>' . e($company) . '</td>';
            $html .= '<td>' . e($contact) . '</td>';
            $html .= '<td>' . e($categoryNames) . '</td>';
            $html .= '<td>' . e($lead->status) . '</td>';
            $html .= '<td>' . ($lead->created_at ? date('d-m-Y', strtotime($lead->created_at)) : '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return response($html);
    }

    public function userReports(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$start, $end, $period] = ReportController::resolveReportPeriod($request, 'today');
        $fromDate = $start;
        $toDate = $end;
        $userId = $request->input('user_id');

        // Get users (exclude super admin)
        // $usersQuery = User::query()->where('crm_role_type', '!=', '0');
        $usersQuery = User::query()->whereNotIn('crm_role_type', [0, 1]);
        if ($userId) {
            $usersQuery->where('id', $userId);
        }
        $users = $usersQuery->get();

        $results = [];

        foreach ($users as $user) {
            // Leads for user
            $leadsQuery = Lead::where('user_owner_id', $user->id);
            if ($fromDate) {
                $leadsQuery->whereDate('converted_at', '>=', $fromDate);
            }
            if ($toDate) {
                $leadsQuery->whereDate('converted_at', '<=', $toDate);
            }
            $leads = $leadsQuery->get();

            // Deals for user
            $dealsQuery = Deal::where('user_owner_id', $user->id);
            if ($fromDate) {
                $dealsQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $dealsQuery->whereDate('created_at', '<=', $toDate);
            }
            $deals = $dealsQuery->get();

            $results[] = [
                'user' => $user,
                'leads' => $leads,
                'deals' => $deals,
            ];
        }

        // You can return a view or JSON as needed
        return view('reports.user_reports_list', compact('results', 'fromDate', 'toDate', 'userId', 'start', 'end', 'period'));
    }

    public function userReportsPdf(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$fromDate, $toDate] = array_slice(ReportController::resolveReportPeriod($request, 'today'), 0, 2);
        $userId = $request->input('user_id');

        // $usersQuery = User::query()->where('crm_role_type', '!=', '0');
        $usersQuery = User::query()->whereNotIn('crm_role_type', [0, 1]);
        if ($userId) {
            $usersQuery->where('id', $userId);
        }
        $users = $usersQuery->get();

        $results = [];
        foreach ($users as $user) {
            $leadsQuery = Lead::where('user_owner_id', $user->id);
            if ($fromDate) {
                $leadsQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $leadsQuery->whereDate('created_at', '<=', $toDate);
            }
            $leads = $leadsQuery->get();

            $dealsQuery = Deal::where('user_owner_id', $user->id);
            if ($fromDate) {
                $dealsQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $dealsQuery->whereDate('created_at', '<=', $toDate);
            }
            $deals = $dealsQuery->get();

            $results[] = [
                'user' => $user,
                'leads' => $leads,
                'deals' => $deals,
            ];
        }

        return MpdfDocument::fromView(
            'reports.user_reports_list_pdf',
            compact('results', 'fromDate', 'toDate', 'userId'),
            'user_reports.pdf'
        );
    }

    public function monthlyUserReport(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser = auth()->user();
        $isAdminOrManager = in_array($currentUser->crm_role_type, [0, 1, 2]);

        // If regular employee, only show their own report
        $userId = $request->input('user_id');
        if (!$isAdminOrManager && !$userId) {
            $userId = $currentUser->id;
        } elseif (!$isAdminOrManager && $userId && $userId != $currentUser->id) {
            // Regular employee trying to view another employee's report
            abort(403, 'You can only view your own report.');
        }
    
        // Do not default month immediately; apply financial year semantics first
        $month = $request->input('month', null);

        // Resolve selected or active financial year
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
        } else {
            $fy = $activeFy;
        }

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

        $selectedOutsideFy = false;

        // If no month provided, default to current month (we still validate against FY below)
        if (empty($month)) {
            $month = date('Y-m');
        } else {
            // Validate provided month format and ensure it's inside FY (if FY present)
            try {
                $mStart = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                if (!empty($fyStart) && !empty($fyEnd)) {
                    if ($mStart->lt($fyStart) || $mStart->gt($fyEnd)) {
                        $selectedOutsideFy = true;
                    }
                }
            } catch (\Exception $e) {
                // Invalid month format; fall back to FY start or current month
                if (!empty($fyStart)) {
                    $month = $fyStart->format('Y-m');
                } else {
                    $month = date('Y-m');
                }
            }
        }

        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $usersQuery = \App\Models\User::query()
            ->where('crm_role_type', '!=', '0')
            ->where('status', 1);
        if ($currentUser->crm_role_type === 2) {
            // If manager, only show their team members
            $usersQuery->where(function ($q) use ($currentUser) {
                $q->where('assign_manager', $currentUser->id)
                    ->orWhere('id', $currentUser->id);
            });
        } else {
            if ($userId) {
                $usersQuery->where('id', $userId);
            }
        }
        $users = $usersQuery->get();

        $results = [];
        // If the requested month is outside the selected financial year, return empty results
        if (!$selectedOutsideFy) {
            foreach ($users as $user) {
                $calls_count = \App\Models\CallLog::where('created_by', $user->id)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->count();
                
                // Total deals generated based on created_at
                $total_deals = \App\Models\Deal::where('user_owner_id', $user->id)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->count();

                // Deals closed won and total value based on close_date
                $deals = \App\Models\Deal::where('user_owner_id', $user->id)
                    ->whereDate('close_date', '>=', $startDate)
                    ->whereDate('close_date', '<=', $endDate)
                    ->get();
                $deals_won_count = $deals->where('stage_id', \App\Models\Stage::closedWonId())->count();
               
                $deal_amount = $deals->where('stage_id', \App\Models\Stage::closedWonId())->sum('amount');

                $leads_count = \App\Models\Lead::where('user_owner_id', $user->id)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->count();

                $deal_details = \App\Models\Deal::where('user_owner_id', $user->id)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get(); // Ensure deals_count is calculated
                $deals_count = $deal_details->count();
                $deals_lost_count = $deal_details->where('stage_id', \App\Models\Stage::closedLostId())->count();
                $results[] = [
                    'month' => date('F Y', strtotime($startDate)),
                    'user' => $user,
                    'calls_count' => $calls_count,
                    'total_deals' => $total_deals, // Total deals generated
                    'deals_count' => $deals_count, // Add deals_count to results
                    'deals_won_count' => $deals_won_count,
                    'deals_lost_count' => $deals_lost_count,
                    'deal_amount' => $deal_amount,
                    'leads_count' => $leads_count,
                ];
            }
        }

        return view('reports.monthly_user_report', compact('results', 'month', 'userId', 'startDate', 'endDate', 'selectedOutsideFy', 'selectedFyId'));
    }

    public function exportMonthlyUserReportExcel(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser = auth()->user();
        $isAdminOrManager = in_array($currentUser->crm_role_type, [0, 1, 2]);

        // If regular employee, only show their own report
        $userId = $request->input('user_id');
        if (!$isAdminOrManager && !$userId) {
            $userId = $currentUser->id;
        } elseif (!$isAdminOrManager && $userId && $userId != $currentUser->id) {
            // Regular employee trying to view another employee's report
            abort(403, 'You can only view your own report.');
        }
    
        // Do not default month immediately; apply financial year semantics first
        $month = $request->input('month', null);

        // Resolve selected or active financial year
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
        } else {
            $fy = $activeFy;
        }

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

        $selectedOutsideFy = false;

        // If no month provided, default to current month (we still validate against FY below)
        if (empty($month)) {
            $month = date('Y-m');
        } else {
            // Validate provided month format and ensure it's inside FY (if FY present)
            try {
                $mStart = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                if (!empty($fyStart) && !empty($fyEnd)) {
                    if ($mStart->lt($fyStart) || $mStart->gt($fyEnd)) {
                        $selectedOutsideFy = true;
                    }
                }
            } catch (\Exception $e) {
                // Invalid month format; fall back to FY start or current month
                if (!empty($fyStart)) {
                    $month = $fyStart->format('Y-m');
                } else {
                    $month = date('Y-m');
                }
            }
        }

        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $usersQuery = \App\Models\User::query()->where('crm_role_type', '!=', '0');
        if ($currentUser->crm_role_type === 2) {
            // If manager, only show their team members
            $usersQuery->where('assign_manager', $currentUser->id)->orWhere('id', $currentUser->id);
        } else {
            if ($userId) {
                $usersQuery->where('id', $userId);
            }
        }
        $users = $usersQuery->get();

        $results = [];
        // If the requested month is outside the selected financial year, return empty results
        if (!$selectedOutsideFy) {
            foreach ($users as $user) {
                $calls_count = \App\Models\CallLog::where('created_by', $user->id)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->count();
                
                // Total deals generated based on created_at
                $total_deals = \App\Models\Deal::where('user_owner_id', $user->id)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->count();

                // Deals closed won and total value based on close_date
                $deals = \App\Models\Deal::where('user_owner_id', $user->id)
                    ->whereDate('close_date', '>=', $startDate)
                    ->whereDate('close_date', '<=', $endDate)
                    ->get();
                $deals_won_count = $deals->where('stage_id', \App\Models\Stage::closedWonId())->count();
               
                $deal_amount = $deals->where('stage_id', \App\Models\Stage::closedWonId())->sum('amount');

                $leads_count = \App\Models\Lead::where('user_owner_id', $user->id)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->count();

                $deal_details = \App\Models\Deal::where('user_owner_id', $user->id)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get(); // Ensure deals_count is calculated
                $deals_count = $deal_details->count();
                $deals_lost_count = $deal_details->where('stage_id', \App\Models\Stage::closedLostId())->count();
                $results[] = [
                    'month' => date('F Y', strtotime($startDate)),
                    'user' => $user,
                    'calls_count' => $calls_count,
                    'total_deals' => $total_deals, // Total deals generated
                    'deals_count' => $deals_count, // Add deals_count to results
                    'deals_won_count' => $deals_won_count,
                    'deals_lost_count' => $deals_lost_count,
                    'deal_amount' => $deal_amount,
                    'leads_count' => $leads_count,
                ];
            }
        }

        return MpdfDocument::fromView(
            'reports.monthly_user_report_pdf',
            compact('results', 'month', 'userId', 'startDate', 'endDate', 'selectedOutsideFy', 'selectedFyId'),
            'monthly_user_report.pdf'
        );
    }

    /**
     * AJAX: Return user's call logs for the month for modal popup
     */
    public function userCallsModal(Request $request, $userId)
    {
        $currentUser = auth()->user();
        if (!$currentUser->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }
        if (!in_array((int) $currentUser->crm_role_type, [0, 1, 2], true) && (int) $userId !== (int) $currentUser->id) {
            abort(403, 'You can only view your own report.');
        }
        $month = $request->input('month', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            $month = date('Y-m');
        }
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $calls = \App\Models\CallLog::where('created_by', $userId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->get(['name', 'company_name', 'mobile_number', 'call_status']);
        if ($calls->isEmpty()) {
            return response()->json(['html' => '<p>No data found.</p>']);
        }
        $html = '<table class="table table-bordered"><thead><tr><th>Name</th><th>Company</th><th>Mobile</th><th>Status</th></tr></thead><tbody>';
        foreach ($calls as $call) {
            $html .= '<tr>';
            $html .= '<td>' . e($call->name) . '</td>';
            $html .= '<td>' . e($call->company_name) . '</td>';
            $html .= '<td>' . e($call->mobile_number) . '</td>';
            $html .= '<td>' . e($call->call_status) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * User Daily Report: Returns daily report for users with required fields and date range
     */
     public function userDailyReport(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser = auth()->user();
        $isAdminOrManager = in_array($currentUser->crm_role_type, [0, 1, 2]);

        $userId = $request->input('user_id');
        if (!$isAdminOrManager && !$userId) {
            $userId = $currentUser->id;
        } elseif (!$isAdminOrManager && $userId && $userId != $currentUser->id) {
            abort(403, 'You can only view your own report.');
        }

        [$startDate, $endDate, $period] = ReportController::resolveReportPeriod($request, 'today');

        // Resolve selected or active financial year
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
        } else {
            $fy = $activeFy;
        }

        $fyStart = null;
        $fyEnd = null;
        if (!empty($fy)) {
            try {
                $fyStart = \Carbon\Carbon::parse($fy->from_date)->startOfDay();
                $fyEnd = \Carbon\Carbon::parse($fy->to_date)->endOfDay();
            } catch (\Exception $e) {
                $fyStart = null;
                $fyEnd = null;
            }
        }

        $selectedOutsideFy = false;

        if (empty($startDate) && empty($endDate)) {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
        } else {
            // Normalize partial inputs: if one side missing, use the other side
            if (empty($startDate) && !empty($endDate)) {
                $startDate = $endDate;
            }
            if (empty($endDate) && !empty($startDate)) {
                $endDate = $startDate;
            }

            // If FY exists, intersect the requested range with FY range
            if (!empty($fyStart) && !empty($fyEnd)) {
                try {
                    $reqStart = \Carbon\Carbon::parse($startDate)->startOfDay();
                    $reqEnd = \Carbon\Carbon::parse($endDate)->endOfDay();
                    $interStart = $reqStart->gt($fyStart) ? $reqStart : $fyStart;
                    $interEnd = $reqEnd->lt($fyEnd) ? $reqEnd : $fyEnd;
                    if ($interStart->gt($interEnd)) {
                        // No overlap with FY -> mark outside and return empty
                        $selectedOutsideFy = true;
                    } else {
                        $startDate = $interStart->format('Y-m-d');
                        $endDate = $interEnd->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    // On parse error fallback to FY or today
                    if (!empty($fyStart) && !empty($fyEnd)) {
                        $startDate = $fyStart->format('Y-m-d');
                        $endDate = $fyEnd->format('Y-m-d');
                    } else {
                        $startDate = date('Y-m-d');
                        $endDate = date('Y-m-d');
                    }
                }
            }
        }
        $usersQuery = \App\Models\User::query()->where('status', 1);
        if($currentUser->crm_role_type === 2) {
            // If manager, only show their team members
            $usersQuery->where(function ($q) use ($currentUser) {
                $q->where('assign_manager', $currentUser->id)
                    ->orWhere('id', $currentUser->id);
            });
        } else {
             if ($userId) {
                $usersQuery->where('id', $userId);
            }
        }

        $users = $usersQuery->get();
        $results = [];
        // If the requested range is outside the selected FY, return empty results
        if (!$selectedOutsideFy) {
            $datePeriod = \Carbon\CarbonPeriod::create($startDate, $endDate);
            foreach ($users as $user) {
                foreach ($datePeriod as $dateObj) {
                    $date = $dateObj->format('Y-m-d');
                    $call_count = \App\Models\CallLog::where('created_by', $user->id)
                        ->whereDate('created_at', $date)
                        ->count();
                    $leads = \App\Models\Lead::where('user_owner_id', $user->id)
                        ->whereDate('created_at', $date)
                        ->get();
                    $deals = \App\Models\Deal::where('user_owner_id', $user->id)
                        ->whereDate('created_at', $date)
                        ->get();
                    if ($leads->isEmpty() && $deals->isEmpty()) {
                        $results[] = [
                            'user' => $user,
                            'date' => date('d-m-Y', strtotime($date)),
                            'call_count' => $call_count,
                            'lead_name' => '',
                            'lead_source' => '',
                            'deal_title' => '',
                            'stage' => '',
                            'status' => '',
                            'closed_date' => '',
                            'deal_amount' => '',
                            'loss_reason' => '',
                        ];
                    } else {
                        // Map deals to leads by converted_lead_id
                        foreach ($leads as $lead) {
                            $deal = $deals->where('converted_lead_id', $lead->id)->first();
                            $lead_source = '';
                            if ($lead->lead_source) {
                                $sourceObj = \App\Models\LeadSource::where('id', $lead->lead_source)->orWhere('name', $lead->lead_source)->first();
                                $lead_source = $sourceObj ? $sourceObj->name : $lead->lead_source;
                            }
                            $stage = $deal ? $deal->stage_name : '';
                            $status = $deal ? $deal->status : '';
                            $closed_date = $deal ? ($deal->close_date ? date('d-m-Y', strtotime($deal->close_date)) : '') : '';
                            $deal_amount = $deal ? (is_numeric($deal->amount) ? (float)$deal->amount : $deal->amount) : '';
                            $loss_reason = ($deal && strtolower((string) ($deal->stage_name ?? '')) === 'closed lost') ? $deal->reason_for_loss : '';
                            $deal_title = $deal ? $deal->title : '';
                            $results[] = [
                                'user' => $user,
                                'date' => date('d-m-Y', strtotime($date)),
                                'call_count' => $call_count,
                                'lead_name' => $lead->title,
                                'lead_source' => $lead_source,
                                'deal_title' => $deal_title,
                                'stage' => $stage,
                                'status' => $status,
                                'closed_date' => $closed_date,
                                'deal_amount' => $deal_amount,
                                'loss_reason' => $loss_reason,
                            ];
                        }
                         // If there are deals but no leads, add a leading row to show call_count
                         if ($leads->isEmpty() && $deals->isNotEmpty()) {
                             $results[] = [
                                 'user' => $user,
                                 'date' => date('d-m-Y', strtotime($date)),
                                 'call_count' => $call_count,
                                 'lead_name' => '',
                                 'lead_source' => '',
                                 'deal_title' => '',
                                 'stage' => '',
                                 'status' => '',
                                 'closed_date' => '',
                                 'deal_amount' => '',
                                 'loss_reason' => '',
                             ];
                         }

                         // Add deals not linked to a lead
                         foreach ($deals as $deal) {
                             if (!$leads->where('id', $deal->converted_lead_id)->count()) {
                                 $results[] = [
                                     'user' => $user,
                                     'date' => date('d-m-Y', strtotime($date)),
                                     'call_count' => '',
                                     'lead_name' => '',
                                     'lead_source' => '',
                                     'deal_title' => $deal->title,
                                     'stage' => $deal->stage_name,
                                     'status' => $deal->status,
                                     'closed_date' => $deal->close_date ? date('d-m-Y', strtotime($deal->close_date)) : '',
                                     'deal_amount' => is_numeric($deal->amount) ? (float)$deal->amount : $deal->amount,
                                     'loss_reason' => strtolower((string) ($deal->stage_name ?? '')) === 'closed lost' ? $deal->reason_for_loss : '',
                                 ];
                             }
                         }
                    }
                }
            }
        }
        $start = $startDate;
        $end = $endDate;
        return view('reports.user_daily_report', compact('results', 'startDate', 'endDate', 'userId', 'selectedOutsideFy', 'start', 'end', 'period'));
    }

    /**
     * Export Daily User Report as Excel
     */
    public function exportDailyUserReportExcel(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser = auth()->user();
        $isAdminOrManager = in_array($currentUser->crm_role_type, [0, 1, 2]);

        // If regular employee, only show their own report
        $userId = $request->input('user_id');
        if (!$isAdminOrManager && !$userId) {
            $userId = $currentUser->id;
        } elseif (!$isAdminOrManager && $userId && $userId != $currentUser->id) {
            // Regular employee trying to view another employee's report
            abort(403, 'You can only export your own report.');
        }

        [$startDate, $endDate] = array_slice(ReportController::resolveReportPeriod($request, 'today'), 0, 2);
        $usersQuery = \App\Models\User::query();
        if($currentUser->crm_role_type === 2) {
            // If manager, only show their team members
            $usersQuery->where('assign_manager', $currentUser->id)->orWhere('id', $currentUser->id);
        } else {
             if ($userId) {
                $usersQuery->where('id', $userId);
            }
        }
        $users = $usersQuery->get();
        $results = [];
        $datePeriod = \Carbon\CarbonPeriod::create($startDate, $endDate);
        foreach ($users as $user) {
            foreach ($datePeriod as $dateObj) {
                $date = $dateObj->format('Y-m-d');
                $call_count = \App\Models\CallLog::where('created_by', $user->id)
                    ->whereDate('created_at', $date)
                    ->count();
                $leads = \App\Models\Lead::where('user_owner_id', $user->id)
                    ->whereDate('created_at', $date)
                    ->get();
                $deals = \App\Models\Deal::where('user_owner_id', $user->id)
                    ->whereDate('created_at', $date)
                    ->get();
                if ($leads->isEmpty() && $deals->isEmpty()) {
                    $results[] = [
                        'user' => $user,
                        'date' => date('d-m-Y', strtotime($date)),
                        'call_count' => $call_count,
                        'lead_name' => '',
                        'lead_source' => '',
                        'deal_title' => '',
                        'stage' => '',
                        'status' => '',
                        'closed_date' => '',
                        'deal_amount' => '',
                        'loss_reason' => '',
                    ];
                } else {
                    foreach ($leads as $lead) {
                        $deal = $deals->where('converted_lead_id', $lead->id)->first();
                        $lead_source = '';
                        if ($lead->lead_source) {
                            $sourceObj = \App\Models\LeadSource::where('id', $lead->lead_source)->orWhere('name', $lead->lead_source)->first();
                            $lead_source = $sourceObj ? $sourceObj->name : $lead->lead_source;
                        }
                        $stage = $deal ? $deal->stage_name : '';
                        $status = $deal ? $deal->status : '';
                        $closed_date = $deal ? ($deal->close_date ? date('d-m-Y', strtotime($deal->close_date)) : '') : '';
                        $deal_amount = $deal ? (is_numeric($deal->amount) ? (float)$deal->amount : $deal->amount) : '';
                        $loss_reason = ($deal && strtolower((string) ($deal->stage_name ?? '')) === 'closed lost') ? $deal->reason_for_loss : '';
                        $deal_title = $deal ? $deal->title : '';
                        $results[] = [
                            'user' => $user,
                            'date' => date('d-m-Y', strtotime($date)),
                            'call_count' => $results ? '' : $call_count,
                            'lead_name' => $lead->title,
                            'lead_source' => $lead_source,
                            'deal_title' => $deal_title,
                            'stage' => $stage,
                            'status' => $status,
                            'closed_date' => $closed_date,
                            'deal_amount' => $deal_amount,
                            'loss_reason' => $loss_reason,
                        ];
                    }
                    foreach ($deals as $deal) {
                        if (!$leads->where('id', $deal->converted_lead_id)->count()) {
                            $results[] = [
                                'user' => $user,
                                'date' => date('d-m-Y', strtotime($date)),
                                'call_count' => '',
                                'lead_name' => '',
                                'lead_source' => '',
                                'deal_title' => $deal->title,
                                'stage' => $deal->stage_name,
                                'status' => $deal->status,
                                'closed_date' => $deal->close_date ? date('d-m-Y', strtotime($deal->close_date)) : '',
                                'deal_amount' => is_numeric($deal->amount) ? (float)$deal->amount : $deal->amount,
                                'loss_reason' => strtolower((string) ($deal->stage_name ?? '')) === 'closed lost' ? $deal->reason_for_loss : '',
                            ];
                        }
                    }
                }
            }
        }
        $fileName = 'user_daily_report_' . $startDate . '_to_' . $endDate . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DailyUserReportExport($results),
            $fileName
        );
    }

     /**
     * Show monthly user performance report: sales target, achieved sales, progress.
     * Supports optional year/month filter via request.
     * Fetch currency details from Company table.
     */
 public function monthlyUserPerformanceReport(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('view_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser = auth()->user();
        $isAdminOrManager = in_array($currentUser->crm_role_type, [0, 1, 2]);

        // If regular employee, only show their own report
        $user_id = $request->input('user_id');
        // If crm_role_type=3 (regular employee), always restrict to their own report
        if ($currentUser->crm_role_type == 3) {
            $user_id = $currentUser->id;
        } elseif (!$isAdminOrManager && !$user_id) {
            $user_id = $currentUser->id;
        } elseif (!$isAdminOrManager && $user_id && $user_id != $currentUser->id) {
            // Regular employee trying to view another employee's report
            abort(403, 'You can only view your own report.');
        }

        if ($user_id && !User::where('id', $user_id)->where('status', 1)->exists()) {
            $user_id = null;
        }

        // Do not default year/month here so we can detect whether they were explicitly provided
        $year = $request->input('year', null);
        $month = $request->input('month', null);
        // Financial year selection: if a financial year is selected and no explicit year/month
        // is provided, use the FY range to limit which monthly records are shown.
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
        } else {
            $fy = $activeFy;
        }
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
            $queryMonths = \App\Models\UserMonthlySales::with(['user' => function ($q) {
                    $q->where('status', 1);
                }])
                ->whereHas('user', function ($q) {
                    $q->where('status', 1);
                })
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc');

            if ($currentUser->crm_role_type === 2) {
                // If the user is a manager, limit to their active team members
                $teamMemberIds = User::where('assign_manager', $currentUser->id)
                    ->where('status', 1)
                    ->pluck('id')
                    ->toArray();
                $queryMonths->where(function ($q) use ($teamMemberIds, $currentUser) {
                    $q->whereIn('user_id', $teamMemberIds)
                        ->orWhere('user_id', $currentUser->id);
                });
            }

            if ($user_id) {
                $queryMonths->where('user_id', $user_id);
            }

            // Add where clauses for each year/month pair
            $queryMonths->where(function ($q) use ($months) {
                foreach ($months as $m) {
                    $q->orWhere(function ($q2) use ($m) {
                        $q2->where('year', $m['year'])->where('month', $m['month']);
                    });
                }
            });

            $monthlySales = $queryMonths->get();

            // Map existing rows
            $map = [];
            foreach ($monthlySales as $row) {
                $map[$row->user_id . '|' . $row->year . '|' . $row->month] = $row;
            }

            // Get active users list for output (respect crm_role_type)
            if ($currentUser->crm_role_type === 2) {
                $allUsers = User::where(function ($q) use ($currentUser) {
                        $q->where('assign_manager', $currentUser->id)
                            ->orWhere('id', $currentUser->id);
                    })
                    ->where('status', 1)
                    ->orderBy('name')
                    ->get();
            } elseif ($currentUser->crm_role_type == 3) {
                $allUsers = User::where('id', $currentUser->id)
                    ->where('status', 1)
                    ->get();
            } else {
                $allUsers = User::where('crm_role_type', '!=', 0)
                    ->where('status', 1)
                    ->orderBy('name')
                    ->get();
            }

            foreach ($allUsers as $u) {
                if ($user_id && $user_id != $u->id) continue;
                foreach ($months as $m) {
                    $key = $u->id . '|' . $m['year'] . '|' . $m['month'];
                    if (isset($map[$key])) {
                        $row = $map[$key];
                        $remaining_target = $row->sales_target > 0 ? max(0, $row->sales_target - $row->achieved_sales) : 0;
                        $progress = $row->sales_target > 0 ? round(($row->achieved_sales / $row->sales_target) * 100, 2) : 0;
                        $report[] = [
                            'user_id' => $row->user_id,
                            'user_name' => $row->user ? $row->user->name : $u->name,
                            'year' => $row->year,
                            'month' => $row->month,
                            'sales_target' => $row->sales_target,
                            'achieved_sales' => $row->achieved_sales,
                            'generated_deals' => $row->generated_deals ?? 0, // Added field
                            'contacted_leads' => $row->contacted_leads ?? 0, // Added field
                            'progress' => $progress,
                            'remaining_target' => $remaining_target
                        ];
                    } else {
                        $report[] = [
                            'user_id' => $u->id,
                            'user_name' => $u->name,
                            'year' => $m['year'],
                            'month' => $m['month'],
                            'sales_target' => 0,
                            'achieved_sales' => 0,
                            'generated_deals' => 0, // Default value
                            'contacted_leads' => 0, // Default value
                            'progress' => 0,
                            'remaining_target' => 0
                        ];
                    }
                }
            }
        }
        $company = \App\Models\Company::first();
        $currency_symbol = $company ? $company->currency_symbol : config('app.currency_symbol', '₹');

        // Dropdown: active users only (role-scoped)
        if ($currentUser->crm_role_type === 2) {
            $users = User::where(function ($q) use ($currentUser) {
                    $q->where('assign_manager', $currentUser->id)
                        ->orWhere('id', $currentUser->id);
                })
                ->where('status', 1)
                ->orderBy('name')
                ->get();
        } elseif ($currentUser->crm_role_type == 3) {
            $users = User::where('id', $currentUser->id)
                ->where('status', 1)
                ->get();
        } else {
            $users = User::where('crm_role_type', '!=', 0)
                ->where('status', 1)
                ->orderBy('name')
                ->get();
        }

        return view('reports.monthly_user_performance', compact('report', 'year', 'month', 'currency_symbol', 'users', 'user_id'));
    }

    public function monthlyTargetReport(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('monthly_target_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser = auth()->user();
        $isAdminOrManager = in_array($currentUser->crm_role_type, [0, 1, 2]);

        $user_id = $request->input('user_id');
        if ($currentUser->crm_role_type == 3) {
            $user_id = $currentUser->id;
        } elseif (!$isAdminOrManager && !$user_id) {
            $user_id = $currentUser->id;
        } elseif (!$isAdminOrManager && $user_id && $user_id != $currentUser->id) {
            abort(403, 'You can only view your own report.');
        }

        $categoryId = $request->input('category');

        $today = \Carbon\Carbon::today();
        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        $selectedFy = $selectedFyId ? \App\Models\FinancialYear::find($selectedFyId) : null;
        $effectiveFy = $selectedFy ?: $activeFy;
        $fyStart = $effectiveFy && $effectiveFy->from_date ? \Carbon\Carbon::parse($effectiveFy->from_date)->startOfDay() : null;
        $fyEnd = $effectiveFy && $effectiveFy->to_date ? \Carbon\Carbon::parse($effectiveFy->to_date)->endOfDay() : null;

        [$startDate, $endDate, $period] = ReportController::resolveReportPeriod($request, 'this_month');

        if ($fyStart && $fyEnd && $startDate && $endDate) {
            $selectedStart = \Carbon\Carbon::parse($startDate)->startOfDay();
            $selectedEnd = \Carbon\Carbon::parse($endDate)->endOfDay();

            if ($selectedEnd->lt($fyStart) || $selectedStart->gt($fyEnd)) {
                $startDate = null;
                $endDate = null;
            } else {
                if ($selectedStart->lt($fyStart)) {
                    $selectedStart = $fyStart->copy();
                }
                if ($selectedEnd->gt($fyEnd)) {
                    $selectedEnd = $fyEnd->copy();
                }

                $startDate = $selectedStart->toDateString();
                $endDate = $selectedEnd->toDateString();
            }
        }

        $month = $startDate ? \Carbon\Carbon::parse($startDate)->format('Y-m') : $today->format('Y-m');
        $periodLabel = ReportController::periodText($period, $startDate, $endDate);
        if ($startDate && $endDate) {
            $rangeStart = \Carbon\Carbon::parse($startDate);
            $rangeEnd = \Carbon\Carbon::parse($endDate);
            if ($rangeStart->format('Y-m') === $rangeEnd->format('Y-m')) {
                $periodLabel = $rangeStart->format('F Y');
            } elseif ($rangeStart->isSameDay($rangeStart->copy()->startOfMonth())
                && $rangeEnd->isSameDay($rangeEnd->copy()->endOfMonth())) {
                $periodLabel = $rangeStart->format('M Y') . ' - ' . $rangeEnd->format('M Y');
            } else {
                $periodLabel = $rangeStart->format('d M Y') . ' - ' . $rangeEnd->format('d M Y');
            }
        }

        $filterUsers = $this->monthlyTargetFilterUsers($currentUser);
        if ($user_id && !$filterUsers->pluck('id')->contains((int) $user_id)) {
            $user_id = null;
        }

        $usersQuery = User::query()
            ->where('crm_role_type', '!=', 0)
            ->where('status', 1);
        if ($currentUser->crm_role_type === 2) {
            $usersQuery->where(function ($q) use ($currentUser) {
                $q->where('assign_manager', $currentUser->id)
                  ->orWhere('id', $currentUser->id);
            });
        }
        if ($user_id) {
            $usersQuery->where('id', $user_id);
        }
        if ($currentUser->crm_role_type == 3) {
            $usersQuery->where('id', $currentUser->id);
        }

        $usersForReport = $usersQuery->orderBy('name')->get();

        $report = [];
        $totals = [
            'generated_leads' => 0,
            'target_deals' => 0,
            'closed_won_deals' => 0,
            'target_revenue' => 0,
            'achieved_revenue' => 0,
        ];
        foreach ($usersForReport as $u) {
            $leadsQuery = Lead::where('user_owner_id', $u->id);
            if ($startDate && $endDate) {
                $leadsQuery->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            } else {
                $leadsQuery->whereRaw('1=0');
            }
            if (!empty($categoryId) && $categoryId !== 'all') {
                $this->applyProductCategoryFilter($leadsQuery, $categoryId);
            }
            $generatedLeads = $leadsQuery->count();

            $targetDealsQuery = Deal::where('user_owner_id', $u->id);
            if ($startDate && $endDate) {
                $targetDealsQuery->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            } else {
                $targetDealsQuery->whereRaw('1=0');
            }
            if (!empty($categoryId) && $categoryId !== 'all') {
                $this->applyProductCategoryFilter($targetDealsQuery, $categoryId);
            }
            $targetDeals = $targetDealsQuery->count();

            $closedWonQuery = Deal::where('user_owner_id', $u->id)
                ->where('stage_id', \App\Models\Stage::closedWonId());
            if ($startDate && $endDate) {
                $closedWonQuery->whereDate('close_date', '>=', $startDate)
                    ->whereDate('close_date', '<=', $endDate);
            } else {
                $closedWonQuery->whereRaw('1=0');
            }
            if (!empty($categoryId) && $categoryId !== 'all') {
                $this->applyProductCategoryFilter($closedWonQuery, $categoryId);
            }
            $closedWonDeals = $closedWonQuery->count();
            $achievedRevenue = (float) $closedWonQuery->sum('amount');

            $totals['generated_leads'] += $generatedLeads;
            $totals['target_deals'] += $targetDeals;
            $totals['closed_won_deals'] += $closedWonDeals;
            $totals['achieved_revenue'] += $achievedRevenue;

            $targetRevenue = (float) ($u->sales_target ?? 0);
            $totals['target_revenue'] += $targetRevenue;
            $achievement = $targetRevenue > 0 ? round(($achievedRevenue / $targetRevenue) * 100, 2) : 0;

            $report[] = [
                'user_id' => $u->id,
                'user_name' => $u->name,
                'month' => $month,
                'period_label' => $periodLabel,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'generated_leads' => $generatedLeads,
                'target_deals' => $targetDeals,
                'closed_won_deals' => $closedWonDeals,
                'target_revenue' => $targetRevenue,
                'achieved_revenue' => $achievedRevenue,
                'achievement' => $achievement,
            ];
        }

        $company = \App\Models\Company::first();
        $currency_symbol = $company ? $company->currency_symbol : config('app.currency_symbol', '₹');
        $users = $filterUsers;
        $categories = ProductCategory::orderBy('category_name')->get(['id', 'category_name']);

        return view('reports.monthly_target_report', compact(
            'report', 'month', 'currency_symbol', 'users', 'user_id', 'categories', 'categoryId',
            'totals', 'period', 'periodLabel', 'startDate', 'endDate'
        ));
    }

    /**
     * Users available in Monthly Target report filters.
     */
    protected function monthlyTargetFilterUsers(User $user)
    {
        $roleType = (int) $user->crm_role_type;

        if (in_array($roleType, [0, 1], true)) {
            return User::query()
                ->where('status', 1)
                ->whereNotIn('crm_role_type', [0, 1])
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if ($roleType === 2) {
            $teamIds = User::where('assign_manager', $user->id)
                ->where('status', 1)
                ->pluck('id');

            return User::whereIn('id', $teamIds->push($user->id)->unique())
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return User::where('id', $user->id)->get(['id', 'name']);
    }

    /**
     * Apply product category filter for leads/deals (supports CSV and single id values).
     */
    protected function applyProductCategoryFilter($query, $categoryId): void
    {
        $query->where(function ($q) use ($categoryId) {
            $q->where('category', $categoryId)
                ->orWhereRaw('FIND_IN_SET(?, category)', [$categoryId]);
        });
    }

    public function displayAnalyticsReports(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('view_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $currentUser = auth()->user();
        $isAdminOrManager = in_array($currentUser->crm_role_type, [0, 1, 2]);

        // If regular employee, only show their own report
        $user_id = $request->input('user_id');
        if (!$isAdminOrManager && !$user_id) {
            $user_id = $currentUser->id;
        } elseif (!$isAdminOrManager && $user_id && $user_id != $currentUser->id) {
            // Regular employee trying to view another employee's report
            abort(403, 'You can only view your own report.');
        }

        // Month range filter (presets + custom from/to month)
        $monthPeriod = $request->input('month_period', 'this_month');
        $allowedMonthPeriods = ['this_month', 'last_month', 'year', 'custom'];
        if (!in_array($monthPeriod, $allowedMonthPeriods, true)) {
            $monthPeriod = 'this_month';
        }

        // Legacy year/month query params still supported
        if (!$request->filled('month_period') && ($request->filled('year') || $request->filled('month'))) {
            $legacyYear = $request->filled('year') ? (int) $request->input('year') : (int) date('Y');
            $legacyMonth = $request->input('month');
            if ($legacyMonth === 'all' || $legacyMonth === '' || $legacyMonth === null) {
                $monthPeriod = 'custom';
                $fromYear = $legacyYear;
                $fromMonth = 1;
                $toYear = $legacyYear;
                $toMonth = 12;
            } else {
                $monthPeriod = 'custom';
                $fromYear = $legacyYear;
                $fromMonth = (int) $legacyMonth;
                $toYear = $legacyYear;
                $toMonth = (int) $legacyMonth;
            }
        }

        $selectedFyId = session('selected_financial_year', null);
        $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
        } else {
            $fy = $activeFy;
        }

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

        $insideFy = function ($y, $m) use ($fyStart, $fyEnd) {
            if (empty($fyStart) || empty($fyEnd)) {
                return true;
            }
            try {
                $d = \Carbon\Carbon::create($y, $m, 1)->startOfMonth();
            } catch (\Exception $e) {
                return false;
            }
            return $d->gte($fyStart->copy()->startOfMonth()) && $d->lte($fyEnd->copy()->startOfMonth());
        };

        $now = \Carbon\Carbon::now();
        if (!isset($fromYear)) {
            switch ($monthPeriod) {
                case 'last_month':
                    $lm = $now->copy()->subMonth();
                    $fromYear = $toYear = (int) $lm->year;
                    $fromMonth = $toMonth = (int) $lm->month;
                    break;
                case 'year':
                    if (!empty($fyStart) && !empty($fyEnd)) {
                        $fromYear = (int) $fyStart->year;
                        $fromMonth = (int) $fyStart->month;
                        $toYear = (int) $fyEnd->year;
                        $toMonth = (int) $fyEnd->month;
                    } else {
                        $fromYear = $toYear = (int) $now->year;
                        $fromMonth = 1;
                        $toMonth = 12;
                    }
                    break;
                case 'custom':
                    $fromYear = (int) ($request->input('from_year') ?: $now->year);
                    $fromMonth = (int) ($request->input('from_month') ?: $now->month);
                    $toYear = (int) ($request->input('to_year') ?: $now->year);
                    $toMonth = (int) ($request->input('to_month') ?: $now->month);
                    break;
                case 'this_month':
                default:
                    $fromYear = $toYear = (int) $now->year;
                    $fromMonth = $toMonth = (int) $now->month;
                    $monthPeriod = 'this_month';
                    break;
            }
        }

        $fromMonth = max(1, min(12, (int) $fromMonth));
        $toMonth = max(1, min(12, (int) $toMonth));

        $rangeStart = \Carbon\Carbon::create($fromYear, $fromMonth, 1)->startOfMonth();
        $rangeEnd = \Carbon\Carbon::create($toYear, $toMonth, 1)->startOfMonth();
        if ($rangeStart->gt($rangeEnd)) {
            $tmp = $rangeStart->copy();
            $rangeStart = $rangeEnd->copy();
            $rangeEnd = $tmp;
            $fromYear = (int) $rangeStart->year;
            $fromMonth = (int) $rangeStart->month;
            $toYear = (int) $rangeEnd->year;
            $toMonth = (int) $rangeEnd->month;
        }

        $months = [];
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $y = (int) $cursor->format('Y');
            $m = (int) $cursor->format('n');
            if ($insideFy($y, $m)) {
                $months[] = ['year' => $y, 'month' => $m];
            }
            $cursor->addMonth();
        }

        $year = $fromYear;
        $month = $fromMonth;

        $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $periodLabels = [
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'year' => 'Year',
            'custom' => 'Custom',
        ];
        if ($monthPeriod === 'custom') {
            $periodText = ($monthNames[$fromMonth - 1] ?? '') . ' ' . $fromYear
                . ' - ' . ($monthNames[$toMonth - 1] ?? '') . ' ' . $toYear;
        } else {
            $periodText = $periodLabels[$monthPeriod] ?? 'This Month';
        }
        // If months list is empty, we will return no data (respecting FY constraints)
        $report = [];

        if (!empty($months)) {
            // Fetch monthly sales rows for these months and optional user filter
            $queryMonths = \App\Models\UserMonthlySales::with('user')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc');

            if ($currentUser->crm_role_type === 2) {
                // If the user is a manager, limit to their team members
                $teamMemberIds = User::where('assign_manager', $currentUser->id)->pluck('id')->toArray();
                $queryMonths->whereIn('user_id', $teamMemberIds)
                    ->orWhere('user_id', $currentUser->id);
            }

            if ($user_id) {
                $queryMonths->where('user_id', $user_id);
            }

            // Add where clauses for each year/month pair
            $queryMonths->where(function ($q) use ($months) {
                foreach ($months as $m) {
                    $q->orWhere(function ($q2) use ($m) {
                        $q2->where('year', $m['year'])->where('month', $m['month']);
                    });
                }
            });

            $monthlySales = $queryMonths->get();

            // Map existing rows
            $map = [];
            foreach ($monthlySales as $row) {
                $map[$row->user_id . '|' . $row->year . '|' . $row->month] = $row;
            }

            // Get users list for output (respect crm_role_type)
            if ($currentUser->crm_role_type === 2) {
                // If the user is a manager, limit to their team members
                $allUsers = User::where(function ($q) use ($currentUser) {
                        $q->where('assign_manager', $currentUser->id)
                            ->orWhere('id', $currentUser->id);
                    })
                    ->where('status', 1)
                    ->orderBy('name')
                    ->get();
            } else {
                $allUsers = User::where('crm_role_type', '!=', 0)
                    ->where('status', 1)
                    ->orderBy('name')
                    ->get();
            }

            foreach ($allUsers as $u) {
                if ($user_id && $user_id != $u->id) continue;
                foreach ($months as $m) {
                    $key = $u->id . '|' . $m['year'] . '|' . $m['month'];
                    $startDate = \Carbon\Carbon::create($m['year'], $m['month'], 1)->startOfMonth();
                    $endDate = \Carbon\Carbon::create($m['year'], $m['month'], 1)->endOfMonth();

                    $generatedDealsCount = \App\Models\Deal::where('user_owner_id', $u->id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->count();

                    $contactedLeadsCount = \App\Models\Lead::where('user_owner_id', $u->id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->count();

                    if (isset($map[$key])) {
                        $row = $map[$key];
                        $report[] = [
                            'user_id' => $row->user_id,
                            'user_name' => $row->user ? $row->user->name : $u->name,
                            'year' => $row->year,
                            'month' => $row->month,
                            'sales_target' => $row->sales_target,
                            'achieved_sales' => $row->achieved_sales,
                            'generated_deals' => $generatedDealsCount,
                            'contacted_leads' => $contactedLeadsCount,
                            'progress' => $row->sales_target > 0 ? round(($row->achieved_sales / $row->sales_target) * 100, 2) : 0
                        ];
                    } else {
                        $report[] = [
                            'user_id' => $u->id,
                            'user_name' => $u->name,
                            'year' => $m['year'],
                            'month' => $m['month'],
                            'sales_target' => 0,
                            'achieved_sales' => 0,
                            'generated_deals' => 0,
                            'contacted_leads' => 0,
                            'progress' => 0
                        ];
                    }
                }
            }
        }
        $company = \App\Models\Company::first();
        $currency_symbol = $company ? $company->currency_symbol : config('app.currency_symbol', '₹');
        // Only show users who are not superadmin (assuming crm_role_type 0 = superadmin)
        $users = User::where('crm_role_type', '!=', 0)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
        return view('reports.analytics_report', compact('report', 'year', 'month', 'currency_symbol', 'users', 'user_id', 'monthPeriod', 'fromYear', 'fromMonth', 'toYear', 'toMonth', 'periodText'));
    }

    public function productCategoryUserReport(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }

        [$start, $end, $period] = ReportController::resolveReportPeriod($request, 'this_month');
        $currentUser = auth()->user();
        $userId = $request->input('user_id');
        $categoryId = $request->input('category');
        $fromDate = $start;
        $toDate = $end;

        $filterUsers = $this->monthlyTargetFilterUsers($currentUser);
        if ($userId && !$filterUsers->pluck('id')->contains((int) $userId)) {
            $userId = null;
        }

        $users = $userId
            ? $filterUsers->where('id', (int) $userId)->values()
            : $filterUsers;

        // Filter leads
        $leadsQuery = Lead::query();
        if ($userId) {
            $leadsQuery->where('user_owner_id', $userId);
        } else {
            $leadsQuery->whereIn('user_owner_id', $filterUsers->pluck('id'));
        }
        if ($categoryId && $categoryId !== 'all') {
            $leadsQuery->whereRaw("FIND_IN_SET(?, category)", [$categoryId]);
        }
        if ($fromDate) {
            $leadsQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $leadsQuery->whereDate('created_at', '<=', $toDate);
        }
        $leads = $leadsQuery->get();

        // Filter deals
        $dealsQuery = Deal::query();
        if ($userId) {
            $dealsQuery->where('user_owner_id', $userId);
        } else {
            $dealsQuery->whereIn('user_owner_id', $filterUsers->pluck('id'));
        }
        if ($categoryId && $categoryId !== 'all') {
            $dealsQuery->whereRaw("FIND_IN_SET(?, category)", [$categoryId]);
        }
        if ($fromDate) {
            $dealsQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $dealsQuery->whereDate('created_at', '<=', $toDate);
        }
        $deals = $dealsQuery->get();

        // Prepare report data grouped by category
        $results = [];
        foreach ($users as $user) {
            $userLeads = $leads->where('user_owner_id', $user->id);
            $userDeals = $deals->where('user_owner_id', $user->id);

            $allCategories = \App\Models\ProductCategory::pluck('category_name', 'id');

            foreach ($allCategories as $catId => $catName) {
                if ($categoryId && $categoryId !== 'all' && $catId != $categoryId) {
                    continue; // Skip other categories if a specific one is selected
                }

                $leadCount = $userLeads->filter(function ($lead) use ($catId) {
                    return in_array($catId, explode(',', $lead->category));
                })->count();

                $dealCount = $userDeals->filter(function ($deal) use ($catId) {
                    return in_array($catId, explode(',', $deal->category));
                })->count();
                $dealWon = $userDeals->filter(function ($deal) use ($catId) {
                    return in_array($catId, explode(',', $deal->category)) 
                        && $deal->stage_name == 'Closed Won';
                })->count();
                $dealLost = $userDeals->filter(function ($deal) use ($catId) {
                    return in_array($catId, explode(',', $deal->category)) 
                        && $deal->stage_name == 'Closed Lost';
                })->count();

                $results[$catName][] = [
                    'user_id' => $user->id,
                    'user' => $user->name,
                    'lead_count' => $leadCount,
                    'deal_count' => $dealCount,
                    'deal_won' => $dealWon,
                    'deal_lost' => $dealLost,
                    'category_id' => $catId
                ];
            }

            // Add "No Category" for leads and deals without a category
            if (!$categoryId || $categoryId === 'all') {
                $noCategoryLeadCount = $userLeads->filter(function ($lead) {
                    return empty($lead->category);
                })->count();

                $noCategoryDealCount = $userDeals->filter(function ($deal) {
                    return empty($deal->category);
                })->count();

                $noCategoryDealWon = $userDeals->filter(function ($deal) {
                    return empty($deal->category) 
                        && strtolower((string) ($deal->stage_name ?? '')) === 'closed won';
                })->count();
                $noCategoryDealLost = $userDeals->filter(function ($deal) {
                    return empty($deal->category) 
                        && strtolower((string) ($deal->stage_name ?? '')) === 'closed lost';
                })->count();

                $results['No Category'][] = [
                    'user_id' => $user->id,
                    'user' => $user->name,
                    'lead_count' => $noCategoryLeadCount,
                    'deal_count' => $noCategoryDealCount,
                    'deal_won' => $noCategoryDealWon,
                    'deal_lost' => $noCategoryDealLost,
                    'category_id' => null
                ];
            }
        }

        return view('reports.product_category_user_report', compact(
            'results', 'start', 'end', 'period', 'filterUsers', 'userId', 'categoryId'
        ));
    }

    /**
     * Fetch leads list for a user and category.
     */
    public function fetchLeadsList($userId)
    {
        if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }
        
        $category = request()->query('category');

        $leadsQuery = Lead::query()->where('user_owner_id', $userId);

        if ($category) {
            $leadsQuery->whereRaw("FIND_IN_SET(?, category)", [$category]);
        } else {
            $leadsQuery->whereNull('category');
        }

        $leads = $leadsQuery->get();

        return view('partials.leads_list', compact('leads'));
    }

    /**
     * Fetch deals list for a user and category.
     */
    public function fetchDealsList($userId)
    {
         if (!auth()->user()->hasCrmPermission('user_report_guard')) {
            abort(403, 'Unauthorized action.');
        }
        
        $category = request()->query('category');
        $stage = request()->query('stage'); // Optional stage filter for deals list 

        $dealsQuery = Deal::query()->where('user_owner_id', $userId);

        if ($category) {
            $dealsQuery->whereRaw("FIND_IN_SET(?, category)", [$category]);
        } else {
            $dealsQuery->whereNull('category');
        }

        if ($stage) {
            $stageId = \App\Models\Stage::resolveId($stage);
            if ($stageId) {
                $dealsQuery->where('stage_id', $stageId);
            }
        }

        $deals = $dealsQuery->get();

        return view('partials.deals_list', compact('deals'));
    }
}
