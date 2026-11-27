<?php

namespace App\Http\Controllers\Goal;

use App\Exports\GoalVrmCallLogsExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Library\MpdfDocument;
use App\Models\GoalLoanLead;
use App\Models\GoalOrgUnit;
use App\Models\GoalVrmCallLog;
use App\Models\GoalVrmDisposition;
use App\Models\Product;
use App\Models\User;
use App\Support\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class GoalVrmController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('vrm_calling_guard');

        $dispositionNames = GoalVrmCallLog::allowedDispositionNames();
        $validated = $request->validate([
            'q' => 'nullable|string|max:150',
            'disposition' => ['nullable', Rule::in($dispositionNames)],
            'disposition_filter' => ['nullable', Rule::in($dispositionNames)],
            'has_lead' => 'nullable|in:all,yes,no',
            'callbacks_due' => 'nullable|in:1,0',
            'interest_level' => ['nullable', Rule::in(GoalVrmCallLog::allowedInterestLevels())],
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'date_filter' => 'nullable|date',
            'per_page' => 'nullable|in:10,25,50,100',
            'log_call' => 'nullable',
            'tab' => 'nullable|in:calls,assigned,analytics',
            'call_status' => 'nullable|in:all,pending,called,connected,not_connected,callback',
            'vrm_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            // Assigned-tab lead status master value, or calls-tab shortcut (yes/no/all/hot).
            'lead_status' => 'nullable|string|max:80',
        ]);

        $canSeeAssignedContacts = $this->canSeeAssignedContactsTab();
        $isBmAssignedView = $this->isBmCaller();
        $defaultTab = 'calls';
        $tabInput = $validated['tab'] ?? $defaultTab;
        $activeTab = in_array($tabInput, ['assigned', 'calls', 'analytics'], true) ? $tabInput : $defaultTab;
        if ($activeTab === 'assigned' && ! $canSeeAssignedContacts) {
            return redirect()->route('goal.vrm.index', ['tab' => 'calls']);
        }

        $filterBranchId = (int) ($validated['branch_id'] ?? 0);
        $allowedBranchIds = $this->allowedBranchIds();
        if ($filterBranchId > 0 && ! in_array($filterBranchId, $allowedBranchIds, true)) {
            $filterBranchId = 0;
        }
        // VRMs are scoped to their own branch — ignore branch filter from query string.
        if ($this->isVrmCaller()) {
            $filterBranchId = 0;
        }
        $filterAssignedLeadStatus = trim((string) ($validated['lead_status'] ?? ''));
        // Calls-tab shortcuts should not filter the assigned contacts lead-status dropdown.
        if (in_array($filterAssignedLeadStatus, ['all', 'yes', 'no', 'hot'], true)) {
            $filterAssignedLeadStatus = '';
        }

        $visibleUserIds = $this->visibleCallLogUserIds();
        $seesTeamLogs = count($visibleUserIds) > 1;
        $assignedVrmIds = $canSeeAssignedContacts ? $this->assignedContactVrmIds() : [];
        $vrmOptions = $canSeeAssignedContacts && $isBmAssignedView
            ? $this->branchVrmOptions()
            : collect();
        $filterVrmId = (int) ($validated['vrm_id'] ?? 0);
        if ($filterVrmId > 0 && ! in_array($filterVrmId, $assignedVrmIds, true)) {
            $filterVrmId = 0;
        }

        $assignedContactsBase = GoalLoanLead::query()
            ->when($assignedVrmIds !== [], fn ($q) => $q->whereIn('assigned_to', $assignedVrmIds))
            ->when($assignedVrmIds === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->whereNotIn('status', ['Closed / Lost', 'Closed', 'Rejected', 'Cancelled', 'Disbursed']);

        $assignedContactsCount = $canSeeAssignedContacts ? (clone $assignedContactsBase)->count() : 0;
        $pendingToCallCount = $canSeeAssignedContacts
            ? (clone $assignedContactsBase)->whereDoesntHave('vrmCalls')->count()
            : 0;

        // Per-VRM totals for BM filter chips / summary
        $vrmAssignedCounts = [];
        if ($canSeeAssignedContacts && $isBmAssignedView && $vrmOptions->isNotEmpty()) {
            $counts = (clone $assignedContactsBase)
                ->selectRaw('assigned_to, COUNT(*) as total')
                ->groupBy('assigned_to')
                ->pluck('total', 'assigned_to');
            foreach ($vrmOptions as $vrm) {
                $vrmAssignedCounts[(int) $vrm->id] = (int) ($counts[(int) $vrm->id] ?? 0);
            }
        }

        // Progress card: VRM = own assigned queue; BM = branch VRMs' assigned total; MD+ = hidden.
        $showCallingProgress = $canSeeAssignedContacts;
        $progressUserIds = $this->isVrmCaller()
            ? [(int) auth()->id()]
            : ($this->isBmCaller() ? $assignedVrmIds : []);

        $todayCount = GoalVrmCallLog::query()
            ->whereIn('user_id', $visibleUserIds)
            ->whereDate('called_at', today())
            ->count();

        $todayProgressCount = $showCallingProgress && $progressUserIds !== []
            ? GoalVrmCallLog::query()
                ->whereIn('user_id', $progressUserIds)
                ->whereDate('called_at', today())
                ->count()
            : 0;
        $progressTarget = $showCallingProgress ? (int) $assignedContactsCount : 0;
        $callingProgress = [
            'show' => $showCallingProgress,
            'completed' => $todayProgressCount,
            'target' => $progressTarget,
            'pending' => max(0, $progressTarget - $todayProgressCount),
            'pct' => $progressTarget > 0
                ? min(100, round(($todayProgressCount / $progressTarget) * 100, 1))
                : 0.0,
        ];

        $assignedLeadStatuses = $canSeeAssignedContacts
            ? (clone $assignedContactsBase)
                ->whereNotNull('status')
                ->where('status', '!=', '')
                ->distinct()
                ->orderBy('status')
                ->pluck('status')
                ->values()
            : collect();

        $callbacksDueCount = GoalVrmCallLog::query()
            ->whereIn('user_id', $visibleUserIds)
            ->whereNotNull('callback_at')
            ->where('callback_at', '<=', now())
            ->where(function ($q) {
                $q->where('disposition', 'Callback')
                    ->orWhereNotNull('callback_at');
            })
            ->count();

        $baseQuery = $this->filteredCallLogsQuery($request);
        $summaryQuery = clone $baseQuery;

        $totalCalls = (clone $summaryQuery)->count();
        $hotLeadCount = (clone $summaryQuery)
            ->where(function ($q) {
                $q->whereRaw('LOWER(disposition) = ?', ['hot lead'])
                    ->orWhere('interest_level', 'Hot')
                    ->orWhereRaw('LOWER(disposition) = ?', ['interested']);
            })
            ->count();
        $callbackCount = (clone $summaryQuery)->where('disposition', 'Callback')->count();
        $leadsCreatedCount = (clone $summaryQuery)->whereNotNull('goal_loan_lead_id')->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $logs = (clone $baseQuery)
            ->with(['orgUnit', 'lead.orgUnit', 'user:id,name,crm_role_type,goal_org_unit_id', 'user.crmRole:id,name', 'user.orgUnit:id,name'])
            ->latest('called_at')
            ->paginate($perPage, ['*'], 'calls_page')
            ->withQueryString();

        $assignedContacts = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        $assignedContactGroups = collect();
        $callStatus = (string) ($validated['call_status'] ?? 'all');

        if ($canSeeAssignedContacts) {
            $assignedQuery = (clone $assignedContactsBase)
                ->with([
                    'orgUnit:id,name,code',
                    'assignee:id,name',
                    'assigner:id,name',
                    'vrmCalls' => fn ($q) => $q->latest('called_at')->limit(1),
                ])
                ->withCount('vrmCalls');

            if ($isBmAssignedView && $filterVrmId > 0) {
                $assignedQuery->where('assigned_to', $filterVrmId);
            }

            if ($request->filled('q') && $activeTab === 'assigned') {
                $q = trim((string) $request->input('q'));
                $digits = preg_replace('/\D+/', '', $q) ?: '';
                $assignedQuery->where(function ($inner) use ($q, $digits) {
                    $inner->where('customer_name', 'like', '%'.$q.'%')
                        ->orWhere('ref_no', 'like', '%'.$q.'%');
                    if ($digits !== '') {
                        $inner->orWhere('mobile', 'like', '%'.$digits.'%');
                    }
                });
            }

            if ($callStatus === 'pending') {
                $assignedQuery->whereDoesntHave('vrmCalls');
            } elseif ($callStatus === 'called') {
                $assignedQuery->whereHas('vrmCalls');
            } elseif ($callStatus === 'connected') {
                $assignedQuery->whereHas('vrmCalls', function ($q) {
                    $q->whereIn('disposition', ['Interested', 'Not Interested', 'Callback', 'Hot lead']);
                });
            } elseif ($callStatus === 'not_connected') {
                $assignedQuery->whereHas('vrmCalls', function ($q) {
                    $q->whereIn('disposition', ['No Answer', 'Busy', 'Invalid Number']);
                });
            } elseif ($callStatus === 'callback') {
                $assignedQuery->where(function ($q) {
                    $q->where('disposition', 'Callback')
                        ->orWhereHas('vrmCalls', fn ($c) => $c->where('disposition', 'Callback'));
                });
            }

            if ($filterAssignedLeadStatus !== '') {
                $assignedQuery->where('status', $filterAssignedLeadStatus);
            }

            if ($filterBranchId > 0) {
                $assignedQuery->where('goal_org_unit_id', $filterBranchId);
            }

            if ($isBmAssignedView) {
                $assignedQuery
                    ->orderBy('assigned_to')
                    ->orderByDesc('assigned_at')
                    ->orderByDesc('id');
            } else {
                $assignedQuery
                    ->orderByRaw('DATE(assigned_at) DESC')
                    ->orderByDesc('assigned_by')
                    ->orderByDesc('assigned_at')
                    ->orderByDesc('id');
            }

            $assignedContacts = $assignedQuery
                ->paginate($perPage, ['*'], 'assigned_page')
                ->withQueryString();

            $assignedContactGroups = $assignedContacts
                ->getCollection()
                ->groupBy(function ($contact) use ($isBmAssignedView) {
                    if ($isBmAssignedView) {
                        return 'vrm|'.(int) ($contact->assigned_to ?? 0);
                    }
                    $dateKey = optional($contact->assigned_at)->format('Y-m-d') ?: 'none';
                    $byKey = (int) ($contact->assigned_by ?? 0);

                    return $dateKey.'|'.$byKey;
                })
                ->map(function ($rows, $key) use ($isBmAssignedView) {
                    $first = $rows->first();

                    return [
                        'key' => $key,
                        'mode' => $isBmAssignedView ? 'vrm' : 'date',
                        'vrm_name' => optional($first?->assignee)->name ?: 'Unassigned VRM',
                        'vrm_id' => (int) ($first?->assigned_to ?? 0),
                        'assigned_on' => optional($first?->assigned_at)->format('d M Y') ?: '—',
                        'assigned_on_raw' => optional($first?->assigned_at)->format('Y-m-d'),
                        'assigned_by' => optional($first?->assigner)->name ?: '—',
                        'assigned_by_id' => (int) ($first?->assigned_by ?? 0),
                        'is_today' => optional($first?->assigned_at)->isToday() ?? false,
                        'total' => $rows->count(),
                        'pending' => $rows->filter(fn ($c) => (int) ($c->vrm_calls_count ?? 0) === 0)->count(),
                        'contacts' => $rows->values(),
                    ];
                })
                ->values();
        }

        $analyticsFrom = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $analyticsTo = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->endOfDay()
            : now()->endOfDay();

        $analyticsCallBase = GoalVrmCallLog::query()
            ->whereIn('user_id', $visibleUserIds)
            ->whereBetween('called_at', [$analyticsFrom, $analyticsTo]);

        $connectedDispositions = ['Interested', 'Not Interested', 'Callback', 'Hot lead'];
        $notConnectedDispositions = ['No Answer', 'Busy', 'Invalid Number'];

        $analyticsInterested = (clone $analyticsCallBase)
            ->where(function ($q) {
                $q->whereRaw('LOWER(disposition) = ?', ['interested'])
                    ->orWhereRaw('LOWER(disposition) = ?', ['hot lead'])
                    ->orWhereIn('interest_level', ['Hot', 'Warm']);
            })
            ->count();

        $analyticsTotalCalls = (clone $analyticsCallBase)->count();
        $analyticsConnected = (clone $analyticsCallBase)->whereIn('disposition', $connectedDispositions)->count();
        $analyticsNotConnected = (clone $analyticsCallBase)->whereIn('disposition', $notConnectedDispositions)->count();
        $analyticsCallbacks = (clone $analyticsCallBase)->where('disposition', 'Callback')->count();
        $analyticsLeadsCreated = (clone $analyticsCallBase)->whereNotNull('goal_loan_lead_id')->count();
        $analyticsConversionPct = $analyticsTotalCalls > 0
            ? round(($analyticsLeadsCreated / $analyticsTotalCalls) * 100, 1)
            : 0.0;

        $analyticsDispositionCounts = (clone $analyticsCallBase)
            ->selectRaw('disposition, COUNT(*) as total')
            ->groupBy('disposition')
            ->orderByDesc('total')
            ->pluck('total', 'disposition')
            ->all();

        $analyticsInterestCounts = (clone $analyticsCallBase)
            ->whereNotNull('interest_level')
            ->selectRaw('interest_level, COUNT(*) as total')
            ->groupBy('interest_level')
            ->orderByDesc('total')
            ->pluck('total', 'interest_level')
            ->all();

        $dailyTrendRows = (clone $analyticsCallBase)
            ->selectRaw('DATE(called_at) as call_day, COUNT(*) as total')
            ->groupBy('call_day')
            ->orderBy('call_day')
            ->get();

        $analyticsDailyTrend = [
            'labels' => $dailyTrendRows->map(fn ($r) => Carbon::parse($r->call_day)->format('d M'))->values()->all(),
            'values' => $dailyTrendRows->map(fn ($r) => (int) $r->total)->values()->all(),
        ];

        $followUpDue = (clone $analyticsCallBase)
            ->whereNotNull('callback_at')
            ->where('callback_at', '<=', now())
            ->count();
        $followUpUpcoming = (clone $analyticsCallBase)
            ->whereNotNull('callback_at')
            ->where('callback_at', '>', now())
            ->count();

        $leadConversionRows = (clone $analyticsCallBase)
            ->whereNotNull('goal_loan_lead_id')
            ->selectRaw('DATE(called_at) as call_day, COUNT(*) as total')
            ->groupBy('call_day')
            ->orderBy('call_day')
            ->get();

        $analyticsLeadTrend = [
            'labels' => $leadConversionRows->map(fn ($r) => Carbon::parse($r->call_day)->format('d M'))->values()->all(),
            'values' => $leadConversionRows->map(fn ($r) => (int) $r->total)->values()->all(),
        ];

        $callAnalytics = [
            'from' => $analyticsFrom->format('Y-m-d'),
            'to' => $analyticsTo->format('Y-m-d'),
            'assigned_contacts' => $assignedContactsCount,
            'total_calls' => $analyticsTotalCalls,
            'connected' => $analyticsConnected,
            'not_connected' => $analyticsNotConnected,
            'interested' => $analyticsInterested,
            'callbacks' => $analyticsCallbacks,
            'leads_created' => $analyticsLeadsCreated,
            'conversion_pct' => $analyticsConversionPct,
            'disposition_counts' => $analyticsDispositionCounts,
            'interest_counts' => $analyticsInterestCounts,
            'daily_trend' => $analyticsDailyTrend,
            'lead_trend' => $analyticsLeadTrend,
            'follow_up_due' => $followUpDue,
            'follow_up_upcoming' => $followUpUpcoming,
        ];

        $branches = $this->selectableBranches();
        $branchLocked = $this->isBranchLockedToOwn();
        $products = $this->activeProducts();
        $dispositions = $this->activeDispositions();
        $canQuickAddProduct = $this->canManageProductMaster();
        $canQuickAddDisposition = $this->canManageDispositionMaster();

        return view('goal.vrm.index', compact(
            'assignedContactsCount',
            'pendingToCallCount',
            'todayCount',
            'callbacksDueCount',
            'totalCalls',
            'hotLeadCount',
            'callbackCount',
            'leadsCreatedCount',
            'logs',
            'assignedContacts',
            'assignedContactGroups',
            'activeTab',
            'callStatus',
            'canSeeAssignedContacts',
            'isBmAssignedView',
            'vrmOptions',
            'vrmAssignedCounts',
            'filterVrmId',
            'filterBranchId',
            'filterAssignedLeadStatus',
            'assignedLeadStatuses',
            'callingProgress',
            'callAnalytics',
            'perPage',
            'branches',
            'branchLocked',
            'products',
            'dispositions',
            'canQuickAddProduct',
            'canQuickAddDisposition',
            'seesTeamLogs'
        ));
    }

    public function calls(Request $request)
    {
        // All call-log features live on the main VRM Calling page now.
        return redirect()->route('goal.vrm.index', $request->query());
    }

    public function showCall(GoalVrmCallLog $log)
    {
        $this->requireGoalPermission('vrm_calling_guard');
        $this->assertCanViewCallLog($log);

        $log->load(['orgUnit', 'lead.orgUnit', 'user.crmRole', 'user.orgUnit']);
        $lead = $log->lead;
        $isOwner = (int) $log->user_id === (int) auth()->id();
        $branchName = $log->resolvedBranchName();
        $branchId = $log->goal_org_unit_id
            ?: ($lead?->goal_org_unit_id)
            ?: optional($log->user)->goal_org_unit_id;

        $historyQuery = GoalVrmCallLog::query()
            ->whereIn('user_id', $this->visibleCallLogUserIds())
            ->with('lead:id,ref_no,lead_type,product,loan_amount,goal_org_unit_id');

        if (filled($log->mobile)) {
            $historyQuery->where('mobile', $log->mobile);
        } else {
            $historyQuery->where('customer_name', $log->customer_name);
        }

        $history = $historyQuery
            ->orderByDesc('called_at')
            ->limit(25)
            ->get()
            ->map(function (GoalVrmCallLog $item) use ($log) {
                return [
                    'id' => $item->id,
                    'call_code' => 'CL-' . str_pad((string) $item->id, 5, '0', STR_PAD_LEFT),
                    'called_at' => optional($item->called_at)->format('d M Y, h:i A'),
                    'disposition' => $item->disposition,
                    'lead' => $item->goal_loan_lead_id ? 'Yes' : 'No',
                    'notes' => $item->notes,
                    'is_current' => (int) $item->id === (int) $log->id,
                ];
            })
            ->values();

        $loanAmount = $lead?->loan_amount;
        $loanAmountFormatted = $loanAmount !== null
            ? '₹ ' . number_format((float) $loanAmount, 0, '.', ',')
            : null;

        return response()->json([
            'id' => $log->id,
            'call_code' => 'CL-' . str_pad((string) $log->id, 5, '0', STR_PAD_LEFT),
            'customer_name' => $log->customer_name,
            'customer_id' => $lead?->ref_no ?: ('CUST-' . str_pad((string) $log->id, 6, '0', STR_PAD_LEFT)),
            'mobile' => $log->mobile,
            'disposition' => $log->disposition,
            'notes' => $log->notes,
            'called_at' => optional($log->called_at)->format('d M Y, h:i A'),
            'callback_at' => optional($log->callback_at)->format('d M Y, h:i A'),
            'lead' => $log->goal_loan_lead_id ? 'Yes' : 'No',
            'lead_id' => $log->goal_loan_lead_id,
            'lead_url' => $log->goal_loan_lead_id ? route('goal.leads.show', $log->goal_loan_lead_id) : null,
            'lead_type' => $lead?->lead_type ? ucfirst($lead->lead_type) : null,
            'product' => $lead?->product,
            'loan_amount' => $loanAmountFormatted,
            'branch' => $branchName,
            'logged_by' => optional($log->user)->name,
            'logged_by_role' => optional($log->user)->goalCrmRoleLabel(),
            'can_edit' => $isOwner,
            'can_delete' => $isOwner && ! $log->goal_loan_lead_id,
            'update_url' => $isOwner ? route('goal.vrm.calls.update', $log) : null,
            'delete_url' => ($isOwner && ! $log->goal_loan_lead_id)
                ? route('goal.vrm.calls.destroy', $log)
                : null,
            'callback_at_input' => optional($log->callback_at)->format('Y-m-d H:i'),
            'goal_org_unit_id' => $branchId,
            'lead_type_raw' => $lead?->lead_type,
            'product_raw' => $lead?->product,
            'loan_amount_raw' => $lead?->loan_amount,
            'history' => $history,
            'history_count' => $history->count(),
        ]);
    }

    public function updateCall(Request $request, GoalVrmCallLog $log)
    {
        $this->requireGoalPermission('vrm_calling_guard');
        abort_unless((int) $log->user_id === (int) auth()->id(), 403);

        $allowedDispositions = GoalVrmCallLog::allowedDispositionNames();
        if ($log->disposition && !in_array($log->disposition, $allowedDispositions, true)) {
            $allowedDispositions[] = $log->disposition;
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'mobile' => PhoneNumber::mobileRules(true),
            'disposition' => ['required', Rule::in($allowedDispositions)],
            'callback_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'product' => 'nullable|string|max:100',
            'loan_amount' => 'nullable|numeric|min:0',
            'goal_org_unit_id' => ['nullable', Rule::in($this->allowedBranchIds())],
        ], PhoneNumber::messages('mobile'));

        $data['goal_org_unit_id'] = $this->resolveBranchId($data['goal_org_unit_id'] ?? null);

        $log->update([
            'customer_name' => $data['customer_name'],
            'mobile' => $data['mobile'] ?? null,
            'disposition' => $data['disposition'],
            'callback_at' => $data['callback_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'goal_org_unit_id' => $data['goal_org_unit_id'] ?: $log->goal_org_unit_id,
        ]);

        if ($log->goal_loan_lead_id) {
            $lead = GoalLoanLead::query()->find($log->goal_loan_lead_id);
            if ($lead) {
                $leadUpdates = [
                    'customer_name' => $data['customer_name'],
                    'mobile' => $data['mobile'] ?? null,
                    'disposition' => $data['disposition'],
                    'notes' => $data['notes'] ?? $lead->notes,
                ];
                if (array_key_exists('callback_at', $data) && $data['callback_at']) {
                    $leadUpdates['follow_up_at'] = $data['callback_at'];
                }
                if (array_key_exists('product', $data)) {
                    $leadUpdates['product'] = $data['product'] ?: null;
                }
                if (array_key_exists('loan_amount', $data)) {
                    $leadUpdates['loan_amount'] = $data['loan_amount'];
                }
                if (array_key_exists('goal_org_unit_id', $data)) {
                    $leadUpdates['goal_org_unit_id'] = $data['goal_org_unit_id'] ?: null;
                }
                $lead->update($leadUpdates);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Call log updated.']);
        }

        return redirect()
            ->back()
            ->with('success', 'Call log updated.');
    }

    public function destroyCall(Request $request, GoalVrmCallLog $log)
    {
        $this->requireGoalPermission('vrm_calling_guard');
        abort_unless((int) $log->user_id === (int) auth()->id(), 403);

        if ($log->goal_loan_lead_id) {
            $message = 'Call logs linked to a lead cannot be deleted.';
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return redirect()
                ->route('goal.vrm.index')
                ->with('error', $message);
        }

        $log->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Call log deleted.']);
        }

        return redirect()
            ->route('goal.vrm.index')
            ->with('success', 'Call log deleted.');
    }

    public function importForm()
    {
        $this->requireGoalPermission('vrm_calling_guard');

        return view('goal.vrm.import');
    }

    public function import(Request $request)
    {
        $this->requireGoalPermission('vrm_calling_guard');

        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $uploaded = $request->file('import_file');
        $rows = [];

        try {
            $extension = strtolower($uploaded->getClientOriginalExtension() ?? '');
            $readerType = null;
            if ($extension === 'csv') {
                $readerType = ExcelWriter::CSV;
            } elseif ($extension === 'xlsx') {
                $readerType = ExcelWriter::XLSX;
            } elseif ($extension === 'xls') {
                $readerType = ExcelWriter::XLS;
            }

            $sheets = Excel::toArray(null, $uploaded, null, $readerType);
            $rows = $sheets[0] ?? [];
        } catch (\Throwable $e) {
            return redirect()
                ->route('goal.vrm.calls.import')
                ->with('error', 'Unable to read the file. Please use the sample format.');
        }

        if (count($rows) < 2) {
            return redirect()
                ->route('goal.vrm.calls.import')
                ->with('error', 'The file has no data rows.');
        }

        $header = array_map(function ($h) {
            return Str::of((string) $h)->lower()->replace(['_', '-'], ' ')->trim()->toString();
        }, $rows[0] ?? []);

        $map = [
            'customer name' => 'customer_name',
            'customer' => 'customer_name',
            'name' => 'customer_name',
            'mobile' => 'mobile',
            'mobile number' => 'mobile',
            'phone' => 'mobile',
            'disposition' => 'disposition',
            'call status' => 'disposition',
            'status' => 'disposition',
            'notes' => 'notes',
            'remarks' => 'notes',
            'callback at' => 'callback_at',
            'callback' => 'callback_at',
            'follow up' => 'callback_at',
            'called at' => 'called_at',
            'call date' => 'called_at',
        ];

        $indexes = [];
        foreach ($header as $i => $label) {
            if (isset($map[$label])) {
                $indexes[$map[$label]] = $i;
            }
        }

        if (!isset($indexes['customer_name']) || !isset($indexes['disposition'])) {
            return redirect()
                ->route('goal.vrm.calls.import')
                ->with('error', 'Required columns missing: Customer Name and Disposition.');
        }

        $imported = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $row) {
            if (!is_array($row) || !array_filter($row, fn ($v) => filled($v))) {
                continue;
            }

            $customer = trim((string) ($row[$indexes['customer_name']] ?? ''));
            $dispositionRaw = trim((string) ($row[$indexes['disposition']] ?? ''));
            if ($customer === '' || $dispositionRaw === '') {
                $skipped++;
                continue;
            }

            $disposition = null;
            foreach (GoalVrmCallLog::allowedDispositionNames() as $allowedDisp) {
                if (strcasecmp($allowedDisp, $dispositionRaw) === 0) {
                    $disposition = $allowedDisp;
                    break;
                }
            }
            if ($disposition === null) {
                $skipped++;
                continue;
            }

            $mobile = isset($indexes['mobile']) ? trim((string) ($row[$indexes['mobile']] ?? '')) : null;
            $notes = isset($indexes['notes']) ? trim((string) ($row[$indexes['notes']] ?? '')) : null;
            $callbackAt = null;
            $calledAt = now();

            if (isset($indexes['callback_at']) && filled($row[$indexes['callback_at']] ?? null)) {
                try {
                    $callbackAt = \Carbon\Carbon::parse($row[$indexes['callback_at']]);
                } catch (\Throwable $e) {
                    $callbackAt = null;
                }
            }
            if (isset($indexes['called_at']) && filled($row[$indexes['called_at']] ?? null)) {
                try {
                    $calledAt = \Carbon\Carbon::parse($row[$indexes['called_at']]);
                } catch (\Throwable $e) {
                    $calledAt = now();
                }
            }

            GoalVrmCallLog::create([
                'user_id' => auth()->id(),
                'goal_org_unit_id' => auth()->user()->goal_org_unit_id,
                'customer_name' => $customer,
                'mobile' => $mobile ?: null,
                'disposition' => $disposition,
                'notes' => $notes ?: null,
                'callback_at' => $callbackAt,
                'called_at' => $calledAt,
            ]);
            $imported++;
        }

        return redirect()
            ->route('goal.vrm.index')
            ->with('success', "Imported {$imported} call log(s)" . ($skipped ? "; skipped {$skipped} row(s)." : '.'));
    }

    public function exportCalls(Request $request)
    {
        $this->requireGoalPermission('vrm_calling_guard');

        $dispositionNames = GoalVrmCallLog::allowedDispositionNames();
        $request->validate([
            'format' => 'nullable|in:excel,csv,pdf',
            'q' => 'nullable|string|max:150',
            'disposition' => ['nullable', Rule::in($dispositionNames)],
            'disposition_filter' => ['nullable', Rule::in($dispositionNames)],
            'lead_status' => 'nullable|in:all,yes,no',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'date_filter' => 'nullable|date',
        ]);

        $format = $request->input('format', 'excel');
        $includeCalledBy = count($this->visibleCallLogUserIds()) > 1;
        $headings = ['#', 'When', 'Customer', 'Mobile', 'Disposition', 'Lead', 'Notes', 'Branch', 'Callback At'];
        if ($includeCalledBy) {
            $headings[] = 'Called by';
        }

        $logs = $this->filteredCallLogsQuery($request)
            ->with(['orgUnit', 'lead.orgUnit', 'user:id,name,crm_role_type,goal_org_unit_id', 'user.crmRole:id,name', 'user.orgUnit:id,name'])
            ->latest('called_at')
            ->limit(5000)
            ->get();

        $rows = [];
        foreach ($logs as $i => $log) {
            $row = [
                $i + 1,
                optional($log->called_at)->format('d-m-Y H:i'),
                $log->customer_name,
                $log->mobile,
                $log->disposition,
                $log->goal_loan_lead_id ? 'Yes' : 'No',
                $log->notes,
                $log->resolvedBranchName(),
                optional($log->callback_at)->format('d-m-Y H:i'),
            ];
            if ($includeCalledBy) {
                $caller = optional($log->user)->name;
                $role = optional($log->user)->goalCrmRoleLabel();
                $row[] = $caller ? ($role ? "{$caller} ({$role})" : $caller) : null;
            }
            $rows[] = $row;
        }

        $stamp = now()->format('Ymd_His');

        if ($format === 'pdf') {
            return MpdfDocument::fromView(
                'goal.vrm.exports.calls-pdf',
                compact('headings', 'rows'),
                'vrm_recent_calls_' . $stamp . '.pdf',
                true,
                'A4-L'
            );
        }

        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        $ext = $format === 'csv' ? 'csv' : 'xlsx';
        $fileName = 'vrm_recent_calls_' . $stamp . '.' . $ext;

        return Excel::download(new GoalVrmCallLogsExport($rows, $headings), $fileName, $writerType);
    }

    protected function filteredCallLogsQuery(Request $request)
    {
        $query = GoalVrmCallLog::query()->whereIn('user_id', $this->visibleCallLogUserIds());

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('customer_name', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $disposition = $request->input('disposition', $request->input('disposition_filter'));
        if (!empty($disposition)) {
            $query->where('disposition', $disposition);
        }

        $hasLead = (string) $request->input('has_lead', '');
        $leadStatus = (string) $request->input('lead_status', 'all');
        if ($hasLead === '' && in_array($leadStatus, ['yes', 'no', 'all'], true)) {
            $hasLead = $leadStatus;
        }
        if ($hasLead === 'yes' || $leadStatus === 'yes') {
            $query->whereNotNull('goal_loan_lead_id');
        } elseif ($hasLead === 'no' || $leadStatus === 'no') {
            $query->whereNull('goal_loan_lead_id');
        } elseif ($leadStatus === 'hot') {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(disposition) = ?', ['hot lead'])
                    ->orWhere('interest_level', 'Hot')
                    ->orWhereRaw('LOWER(disposition) = ?', ['interested']);
            });
        }

        $interestLevel = trim((string) $request->input('interest_level', ''));
        if ($interestLevel !== '' && in_array($interestLevel, GoalVrmCallLog::allowedInterestLevels(), true)) {
            $query->where('interest_level', $interestLevel);
        }

        if ($request->input('callbacks_due') === '1') {
            $query->whereNotNull('callback_at')
                ->where('callback_at', '<=', now());
        }

        if ($request->filled('date_filter') && !$request->filled('from_date') && !$request->filled('to_date')) {
            $query->whereDate('called_at', $request->input('date_filter'));
        } else {
            if ($request->filled('from_date')) {
                $query->whereDate('called_at', '>=', $request->input('from_date'));
            }
            if ($request->filled('to_date')) {
                $query->whereDate('called_at', '<=', $request->input('to_date'));
            }
        }

        return $query;
    }

    public function searchAssignedContacts(Request $request)
    {
        $this->requireGoalPermission('vrm_calling_guard');

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $visibleUserIds = $this->visibleCallLogUserIds();
        $digits = preg_replace('/\D+/', '', $q) ?: '';

        $leads = GoalLoanLead::query()
            ->with(['orgUnit:id,name,code'])
            ->whereIn('assigned_to', $visibleUserIds)
            ->whereNotIn('status', ['Closed / Lost', 'Closed', 'Rejected', 'Cancelled', 'Disbursed'])
            ->where(function ($query) use ($q, $digits) {
                $query->where('customer_name', 'like', '%'.$q.'%')
                    ->orWhere('ref_no', 'like', '%'.$q.'%');
                if ($digits !== '') {
                    $query->orWhere('mobile', 'like', '%'.$digits.'%');
                }
            })
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'ref_no', 'customer_name', 'mobile', 'product', 'loan_amount', 'status', 'goal_org_unit_id', 'assigned_to']);

        return response()->json([
            'data' => $leads->map(fn (GoalLoanLead $lead) => [
                'id' => (int) $lead->id,
                'ref_no' => $lead->ref_no,
                'customer_name' => $lead->customer_name,
                'mobile' => $lead->mobile,
                'product' => $lead->product,
                'loan_amount' => $lead->loan_amount !== null ? (float) $lead->loan_amount : null,
                'status' => $lead->status,
                'goal_org_unit_id' => $lead->goal_org_unit_id ? (int) $lead->goal_org_unit_id : null,
                'branch_name' => $lead->orgUnit?->name,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireGoalPermission('vrm_calling_guard');

        $request->merge([
            'called_at' => $this->normalizeDateTimeInput($request->input('called_at')),
            'callback_at' => $this->normalizeDateTimeInput($request->input('callback_at')),
        ]);

        $callbackRequired = in_array($request->input('disposition'), ['Callback'], true);
        $notesRequired = in_array($request->input('disposition'), ['Invalid Number'], true);

        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'mobile' => PhoneNumber::mobileRules(true),
            'goal_loan_lead_id' => 'nullable|integer|exists:goal_loan_leads,id',
            'disposition' => ['required', Rule::in(GoalVrmCallLog::allowedDispositionNames())],
            'called_at' => 'nullable|date',
            'callback_at' => [
                Rule::requiredIf(fn () => $callbackRequired),
                'nullable',
                'date',
                'after_or_equal:'.now()->startOfDay()->toDateTimeString(),
            ],
            'notes' => [
                Rule::requiredIf(fn () => $notesRequired),
                'nullable',
                'string',
            ],
            'create_lead' => 'nullable|boolean',
            'product' => [
                Rule::requiredIf(fn () => $request->boolean('create_lead') && ! $request->filled('goal_loan_lead_id')),
                'nullable',
                'string',
                'max:100',
                Rule::in($this->activeProducts()->pluck('product_name')->all()),
            ],
            'loan_amount' => [
                Rule::requiredIf(fn () => $request->boolean('create_lead') && ! $request->filled('goal_loan_lead_id')),
                'nullable',
                'numeric',
                'min:0',
            ],
            'goal_org_unit_id' => ['nullable', Rule::in($this->allowedBranchIds())],
            'interest_level' => ['nullable', Rule::in(GoalVrmCallLog::allowedInterestLevels())],
            'duration_seconds' => 'nullable|integer|min:0|max:86400',
            'save_and_new' => 'nullable|boolean',
        ], array_merge(PhoneNumber::messages('mobile'), [
            'product.required' => 'Please select a product when creating a lead.',
            'loan_amount.required' => 'Please enter loan amount when creating a lead.',
            'callback_at.required' => 'Follow-up date and time are required for callbacks.',
            'callback_at.after_or_equal' => 'Follow-up date cannot be in the past.',
            'notes.required' => 'Remarks are required for this disposition.',
        ]));

        $data['goal_org_unit_id'] = $this->resolveBranchId($data['goal_org_unit_id'] ?? null);

        $dispositionMaster = GoalVrmDisposition::findByName($data['disposition']);
        $canCreateLeadFromDisposition = $dispositionMaster
            ? (bool) $dispositionMaster->creates_lead
            : in_array($data['disposition'], ['Interested', 'Callback'], true);

        $leadId = null;
        $assignedLeadId = (int) ($data['goal_loan_lead_id'] ?? 0);
        if ($assignedLeadId > 0) {
            $assignedLead = GoalLoanLead::query()->find($assignedLeadId);
            $visibleUserIds = $this->visibleCallLogUserIds();
            if (! $assignedLead || ! in_array((int) $assignedLead->assigned_to, $visibleUserIds, true)) {
                return back()->withInput()->withErrors([
                    'customer_name' => 'Selected contact is not in your assigned list.',
                ])->with('reopen_log_call', true);
            }
            $leadId = (int) $assignedLead->id;
            if (empty($data['goal_org_unit_id']) && $assignedLead->goal_org_unit_id) {
                $data['goal_org_unit_id'] = (int) $assignedLead->goal_org_unit_id;
            }
            if ($data['callback_at'] ?? null) {
                $assignedLead->follow_up_at = $data['callback_at'];
            }
            if (! empty($data['disposition'])) {
                $assignedLead->disposition = $data['disposition'];
            }
            if ($assignedLead->isDirty()) {
                $assignedLead->save();
            }
        } elseif ($request->boolean('create_lead') && $canCreateLeadFromDisposition) {
            $this->requireGoalPermission('create_lead_guard');

            $mobile = trim((string) ($data['mobile'] ?? ''));
            $name = trim((string) $data['customer_name']);
            $dup = $mobile !== ''
                ? GoalLoanLead::query()->where('mobile', $mobile)->orderByDesc('id')->first()
                : GoalLoanLead::query()
                    ->whereRaw('LOWER(customer_name) = ?', [mb_strtolower($name)])
                    ->whereDate('created_at', today())
                    ->orderByDesc('id')
                    ->first();

            if ($dup) {
                return back()->withInput()->withErrors([
                    'mobile' => 'Possible duplicate lead '.$dup->ref_no.' ('.$dup->customer_name.'). Clear create-lead or use the existing record.',
                ])->with('reopen_log_call', true);
            }

            $leadType = $dispositionMaster
                ? $dispositionMaster->resolveLeadType()
                : ($data['disposition'] === 'Callback' ? 'future' : 'immediate');
            $lead = GoalLoanLead::create([
                'ref_no' => 'LD-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
                'customer_name' => $data['customer_name'],
                'mobile' => $data['mobile'] ?? null,
                'source' => \App\Models\LeadSource::resolveCanonicalName('VRM') ?: 'VRM',
                'product' => $data['product'] ?? null,
                'loan_amount' => $data['loan_amount'] ?? null,
                'lead_type' => $leadType,
                'status' => $leadType === 'immediate' ? 'Pending BM Allocation' : 'Future Prospect',
                'disposition' => $data['disposition'],
                'follow_up_at' => $data['callback_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'goal_org_unit_id' => $data['goal_org_unit_id'] ?? auth()->user()->goal_org_unit_id,
                'created_by' => auth()->id(),
            ]);
            app(\App\Services\Goal\GoalPartyService::class)->attachLead($lead);
            $leadId = $lead->id;
        }

        $calledAt = ! empty($data['called_at'])
            ? Carbon::parse($data['called_at'])
            : now();

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $leadId, $calledAt) {
            GoalVrmCallLog::create([
                'user_id' => auth()->id(),
                'goal_loan_lead_id' => $leadId,
                'goal_org_unit_id' => $data['goal_org_unit_id'] ?? auth()->user()->goal_org_unit_id,
                'customer_name' => $data['customer_name'],
                'mobile' => $data['mobile'] ?? null,
                'disposition' => $data['disposition'],
                'interest_level' => $data['interest_level'] ?? null,
                'duration_seconds' => isset($data['duration_seconds']) ? (int) $data['duration_seconds'] : null,
                'called_at' => $calledAt,
                'callback_at' => $data['callback_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $message = 'Call logged' . ($leadId ? ' and lead linked.' : '.');
        $returnTab = $request->input('return_tab') === 'assigned' ? 'assigned' : 'calls';

        if ($request->boolean('save_and_new')) {
            return redirect()
                ->route('goal.vrm.index', ['tab' => $returnTab, 'log_call' => 1])
                ->with('success', $message)
                ->with('reopen_log_call', true);
        }

        return redirect()
            ->route('goal.vrm.index', ['tab' => $returnTab])
            ->with('success', $message);
    }

    public function storeQuickDisposition(Request $request)
    {
        $this->requireGoalPermission('vrm_calling_guard');
        abort_unless($this->canManageDispositionMaster(), 403, 'Only admins can add dispositions.');

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'creates_lead' => 'nullable|boolean',
            'maps_to_lead_type' => 'nullable|in:immediate,future',
        ]);

        $name = trim($data['name']);
        $existing = GoalVrmDisposition::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->is_active = true;
            if (array_key_exists('creates_lead', $data)) {
                $existing->creates_lead = (bool) $data['creates_lead'];
            }
            if (array_key_exists('maps_to_lead_type', $data)) {
                $existing->maps_to_lead_type = $data['maps_to_lead_type'] ?: null;
            }
            $existing->save();

            return response()->json([
                'id' => $existing->id,
                'name' => $existing->name,
                'creates_lead' => (bool) $existing->creates_lead,
                'maps_to_lead_type' => $existing->maps_to_lead_type,
                'reused' => true,
            ]);
        }

        $maxSort = (int) GoalVrmDisposition::query()->max('sort_order');
        $disposition = GoalVrmDisposition::create([
            'name' => $name,
            'creates_lead' => (bool) ($data['creates_lead'] ?? false),
            'maps_to_lead_type' => $data['maps_to_lead_type'] ?? null,
            'sort_order' => $maxSort + 10,
            'is_active' => true,
        ]);

        return response()->json([
            'id' => $disposition->id,
            'name' => $disposition->name,
            'creates_lead' => (bool) $disposition->creates_lead,
            'maps_to_lead_type' => $disposition->maps_to_lead_type,
            'reused' => false,
        ], 201);
    }

    public function updateDisposition(Request $request, GoalVrmDisposition $disposition)
    {
        $this->requireGoalPermission('vrm_calling_guard');
        abort_unless($this->canManageDispositionMaster(), 403, 'Only admins can update dispositions.');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('goal_vrm_dispositions', 'name')->ignore($disposition->id),
            ],
            'creates_lead' => 'nullable|boolean',
            'maps_to_lead_type' => 'nullable|in:immediate,future',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $disposition->fill([
            'name' => trim($data['name']),
            'creates_lead' => array_key_exists('creates_lead', $data)
                ? (bool) $data['creates_lead']
                : $disposition->creates_lead,
            'maps_to_lead_type' => array_key_exists('maps_to_lead_type', $data)
                ? ($data['maps_to_lead_type'] ?: null)
                : $disposition->maps_to_lead_type,
            'is_active' => array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : $disposition->is_active,
            'sort_order' => $data['sort_order'] ?? $disposition->sort_order,
        ])->save();

        return response()->json([
            'id' => $disposition->id,
            'name' => $disposition->name,
            'creates_lead' => (bool) $disposition->creates_lead,
            'maps_to_lead_type' => $disposition->maps_to_lead_type,
            'is_active' => (bool) $disposition->is_active,
            'sort_order' => (int) $disposition->sort_order,
        ]);
    }

    public function storeQuickProduct(Request $request)
    {
        $this->requireGoalPermission('vrm_calling_guard');
        abort_unless($this->canManageProductMaster(), 403, 'Only admins can add products.');

        $data = $request->validate([
            'product_name' => 'required|string|max:150',
        ]);

        $name = trim($data['product_name']);

        $existing = Product::withTrashed()
            ->whereRaw('LOWER(product_name) = ?', [Str::lower($name)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->product_status = 1;
            $existing->updated_by = auth()->id();
            $existing->save();

            return response()->json([
                'id' => $existing->id,
                'product_name' => $existing->product_name,
                'reused' => true,
            ]);
        }

        $baseCode = Str::upper(Str::limit(Str::slug($name, ''), 12, ''));
        if ($baseCode === '') {
            $baseCode = 'PROD';
        }
        $code = $baseCode;
        $suffix = 1;
        while (Product::withTrashed()->where('product_code', $code)->exists()) {
            $code = $baseCode . $suffix;
            $suffix++;
        }

        $product = Product::create([
            'product_name' => $name,
            'product_code' => $code,
            'product_type' => 'default',
            'unit_price' => 0,
            'product_status' => 1,
            'product_category_id' => null,
            'tax_status' => 0,
            'product_owner_id' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'id' => $product->id,
            'product_name' => $product->product_name,
            'reused' => false,
        ], 201);
    }

    protected function activeProducts()
    {
        return Product::query()
            ->where('product_status', 1)
            ->orderBy('product_name')
            ->get(['id', 'product_name']);
    }

    protected function activeDispositions()
    {
        return GoalVrmDisposition::query()
            ->active()
            ->get(['id', 'name', 'creates_lead', 'maps_to_lead_type', 'sort_order']);
    }

    protected function normalizeDateTimeInput(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})(?:\s+(\d{2}):(\d{2})(?::\d{2})?)?$/', $raw, $m)) {
            $normalized = $m[3].'-'.$m[2].'-'.$m[1];
            if (! empty($m[4])) {
                $normalized .= ' '.$m[4].':'.$m[5].':00';
            }

            return $normalized;
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $raw;
        }
    }

    /**
     * @return list<int>
     */
    protected function visibleCallLogUserIds(): array
    {
        $ids = auth()->user()->goalTeamUserIds();
        if ($ids === []) {
            return [(int) auth()->id()];
        }

        return $ids;
    }

    protected function assertCanViewCallLog(GoalVrmCallLog $log): void
    {
        abort_unless(
            auth()->user()->canViewGoalUserCallLogs((int) $log->user_id),
            403,
            'You cannot view this call log.'
        );
    }

    /** VRM role (type 9) may only use their assigned branch. */
    protected function isBranchLockedToOwn(): bool
    {
        return $this->isVrmCaller();
    }

    protected function isVrmCaller(): bool
    {
        $user = auth()->user();
        $role = (int) ($user->crm_role_type ?? -1);
        $job = strtolower((string) ($user->job_role ?? ''));

        return $role === 9 || $job === 'vrm';
    }

    protected function isBmCaller(): bool
    {
        $user = auth()->user();
        $role = (int) ($user->crm_role_type ?? -1);
        $job = strtolower((string) ($user->job_role ?? ''));

        return $role === 8 || $job === 'bm';
    }

    /** Assigned Contacts tab is only for BM (branch VRMs) and VRM (own queue). */
    protected function canSeeAssignedContactsTab(): bool
    {
        return $this->isBmCaller() || $this->isVrmCaller();
    }

    /**
     * VRM IDs whose assigned contacts appear in the Assigned Contacts tab.
     *
     * @return list<int>
     */
    protected function assignedContactVrmIds(): array
    {
        if ($this->isVrmCaller()) {
            return [(int) auth()->id()];
        }

        if ($this->isBmCaller()) {
            return $this->branchVrmOptions()->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        return [];
    }

    /**
     * VRMs under the current BM branch / org scope.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function branchVrmOptions()
    {
        $user = auth()->user();
        $orgIds = array_map('intval', $user->goalScopedOrgUnitIds());
        $own = (int) ($user->goal_org_unit_id ?? 0);
        if ($orgIds === [] && $own > 0) {
            $orgIds = [$own];
        }

        return User::query()
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('crm_role_type', 9)
                    ->orWhere('job_role', 'vrm');
            })
            ->when($orgIds !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $orgIds))
            ->orderBy('name')
            ->get(['id', 'name', 'goal_org_unit_id', 'crm_role_type', 'job_role']);
    }

    /** Quick-add / Masters: Super Admin, Admin, or Manage Products permission. */
    protected function canManageProductMaster(): bool
    {
        $user = auth()->user();
        if ($user->isSuperAdmin() || in_array((int) ($user->crm_role_type ?? -1), [1, 4, 5], true)) {
            return true;
        }

        return $user->hasCrmPermission('manage_products_guard')
            || $user->hasCrmPermission('manage_crm_product_guard');
    }

    /** Quick-add / Masters: Super Admin, Admin, or Manage Dispositions permission. */
    protected function canManageDispositionMaster(): bool
    {
        $user = auth()->user();
        if ($user->isSuperAdmin() || in_array((int) ($user->crm_role_type ?? -1), [1, 4, 5], true)) {
            return true;
        }

        return $user->hasCrmPermission('manage_dispositions_guard');
    }

    /**
     * Branch options for Log Call: VRM = own branch only;
     * others = active branches within their org scope (or all for company-wide roles).
     */
    protected function selectableBranches()
    {
        $query = GoalOrgUnit::query()->ofType('branch')->active()->orderBy('name');
        $allowed = $this->allowedBranchIds();
        if ($allowed === []) {
            return $query->whereRaw('1 = 0')->get();
        }

        return $query->whereIn('id', $allowed)->get();
    }

    /**
     * @return list<int|string>
     */
    protected function allowedBranchIds(): array
    {
        $user = auth()->user();
        $scoped = array_map('intval', $user->goalScopedOrgUnitIds());

        if ($this->isBranchLockedToOwn()) {
            $own = (int) ($user->goal_org_unit_id ?? 0);
            return $own > 0 ? [$own] : [];
        }

        if ($scoped === []) {
            // Super/Admin/MD/CEO already return all IDs from goalScopedOrgUnitIds
            return GoalOrgUnit::query()->ofType('branch')->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return GoalOrgUnit::query()
            ->ofType('branch')
            ->active()
            ->whereIn('id', $scoped)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function resolveBranchId(mixed $requested): ?int
    {
        $allowed = $this->allowedBranchIds();
        $own = (int) (auth()->user()->goal_org_unit_id ?? 0);

        if ($this->isBranchLockedToOwn()) {
            return $own > 0 ? $own : null;
        }

        $id = $requested !== null && $requested !== '' ? (int) $requested : null;
        if ($id && in_array($id, $allowed, true)) {
            return $id;
        }

        return $own > 0 && in_array($own, $allowed, true) ? $own : ($allowed[0] ?? null);
    }
}
