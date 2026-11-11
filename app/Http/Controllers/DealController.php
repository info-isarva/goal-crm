<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Deal;
use App\Models\Industry;
use App\Models\Quotation;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Person as Contact;
use App\Services\FirebaseNotificationService;

class DealController extends Controller
{
    public function __construct()
    {
        // Apply middleware to block create/edit/delete actions when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'create', 'store', 
            'edit', 'update', 'destroy', 'markWon', 'markLost', 'reopen', 'updateStage'
        ]);
    }

    public function index()
    {
        // Deals Pipeline View

        if (!auth()->user()->hasCrmPermission('manage_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $user = auth()->user();
        $stages = Stage::orderBy('order_by')->get();
        $query = \App\Models\Deal::with(['organization', 'person', 'owner', 'stage']);
        // Role-based base filter
        if (!($user->crm_role_type === 0 || $user->crm_role_type === 1 || $user->crm_role_type === 2)) {
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('user_owner_id', $user->id);
            });
        }elseif ($user->crm_role_type === 2) { // Manager
            $employeeIds = User::where('assign_manager', $user->id)->pluck('id');
            $query->where(function($q) use ($user, $employeeIds) {
                $q->where('created_by', $user->id)
                  ->orWhere('user_owner_id', $user->id)
                  ->orWhereIn('created_by', $employeeIds)
                  ->orWhereIn('user_owner_id', $employeeIds);
            });
        }

        // Search/Filter logic (allow pipeline to accept same filters)
        $title = request('title');
        $label = request('label');
        $view = request('view');
        $lead_source = request('lead_source');
        $reqStart = request('start_date');
        $reqEnd = request('end_date');
        $category = request('category');

        if ($title) {
            $term = $title;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhereHas('person', function ($p) use ($term) {
                        $p->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$term}%"]);
                    })
                    ->orWhereHas('organization', function ($o) use ($term) {
                        $o->where('name', 'like', "%{$term}%");
                    });
            });
        }
        if ($label) {
            $query->where('label', $label);
        }
        // Apply 'view' filters (Closed Won / Closed Lost / My Deals / Open Deals / Recently ...)
        if ($view) {
            switch ($view) {
                case 'Closed Won':
                    $query->where('status', 'closed won');
                    break;
                case 'Closed Lost':
                    $query->where('status', 'closed lost');
                    break;
                case 'Open Deals':
                    $query->where('status', 'open');
                    break;
                case 'My Deals':
                    $query->where('user_owner_id', $user->id);
                    break;
                case 'Recently Created Deals':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'Recently Modified Deals':
                    $query->orderBy('updated_at', 'desc');
                    break;
                case 'All Deals':
                default:
                    // no-op
                    break;
            }
        }
        if ($lead_source) {
            $query->where('lead_source', $lead_source);
        }

        // Apply date filters (start_date / end_date) if provided. Respect selected financial year
        $selectedFyId = session('selected_financial_year', null);
        
        // $fyFrom = null; $fyTo = null;
        // if ($selectedFyId) {
        //     $fy = \App\Models\FinancialYear::find($selectedFyId);
        //     if ($fy) {
        //         $fyFrom = \Carbon\Carbon::parse($fy->from_date)->startOfDay();
        //         $fyTo = \Carbon\Carbon::parse($fy->to_date)->endOfDay();
        //     }
        // }

        // //How to check previous financial year


        // try { $start = $reqStart ? \Carbon\Carbon::parse($reqStart)->startOfDay() : null; } catch (\Exception $e) { $start = null; }
        // try { $end = $reqEnd ? \Carbon\Carbon::parse($reqEnd)->endOfDay() : null; } catch (\Exception $e) { $end = null; }

        // if ($start || $end) {
        //     if ($fyFrom) { if ($start && $start->lt($fyFrom)) $start = $fyFrom; if (!$start) $start = $fyFrom; }
        //     if ($fyTo) { if ($end && $end->gt($fyTo)) $end = $fyTo; if (!$end) $end = $fyTo; }
        //     if ($start && $end && $start->gt($end)) {
        //         $query->whereRaw('0 = 1');
        //     } else {
        //         if ($start && $end) { $query->whereBetween('created_at', [$start, $end]); }
        //         elseif ($start) { $query->where('created_at', '>=', $start); }
        //         elseif ($end) { $query->where('created_at', '<=', $end); }
        //     }
        // } 
        // else {
        //     if ($fyFrom && $fyTo) { $query->whereBetween('created_at', [$fyFrom, $fyTo]); }
        // }


        // Get Selected Financial Year
        $fyFrom = null;
        $fyTo   = null;
        $prevFyFrom = null;
        $prevFyTo   = null;

        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);

            if ($fy) {
                // Current FY
                $fyFrom = \Carbon\Carbon::parse($fy->from_date)->startOfDay();
                $fyTo   = \Carbon\Carbon::parse($fy->to_date)->endOfDay();

                // Previous FY (year - 1)
                $prevFyFrom = $fyFrom->copy()->subYear();
                $prevFyTo   = $fyTo->copy()->subYear();
            }
        }


        // Request Date Filters
        try { 
            $start = $reqStart ? \Carbon\Carbon::parse($reqStart)->startOfDay() : null; 
        } catch (\Exception $e) { 
            $start = null; 
        }

        try { 
            $end = $reqEnd ? \Carbon\Carbon::parse($reqEnd)->endOfDay() : null; 
        } catch (\Exception $e) { 
            $end = null; 
        }


        // Apply Filters
        if ($start || $end) {

            // Apply custom range within current FY
            if ($fyFrom) {
                if ($start && $start->lt($fyFrom)) $start = $fyFrom;
                if (!$start) $start = $fyFrom;
            }

            if ($fyTo) {
                if ($end && $end->gt($fyTo)) $end = $fyTo;
                if (!$end) $end = $fyTo;
            }

            if ($start && $end && $start->gt($end)) {
                $query->whereRaw('0 = 1');
            } else {
                if ($start && $end) {
                    $query->whereBetween('created_at', [$start, $end]);
                } elseif ($start) {
                    $query->where('created_at', '>=', $start);
                } elseif ($end) {
                    $query->where('created_at', '<=', $end);
                }
            }

        } else {

            // Default: Current FY + Previous FY logic
            if ($fyFrom && $fyTo) {

                $query->where(function ($q) use ($fyFrom, $fyTo, $prevFyFrom, $prevFyTo) {

                    // Current Financial Year (ALL deals)
                    $q->whereBetween('created_at', [$fyFrom, $fyTo]);

                    // Previous Financial Year (Exclude Closed Won & Closed Lost)
                    if ($prevFyFrom && $prevFyTo) {
                        $q->orWhere(function ($sub) use ($prevFyFrom, $prevFyTo) {
                            $sub->whereBetween('created_at', [$prevFyFrom, $prevFyTo])
                                ->whereNotIn('stage_id', array_filter([Stage::closedWonId(), Stage::closedLostId()]));
                        });
                    }

                });

            }

        }

        if ($category) {
            \Log::info('Category Filter Debug:', [
                'category_input' => $category,
                'query_before' => $query->toSql(),
            ]);

            $query->whereRaw("FIND_IN_SET(?, category)", [$category]);

            \Log::info('Query After Applying Filter:', ['query_after' => $query->toSql()]);
        }

        $deals = $query->orderByDesc('created_at')->get();

        // Optionally, add computed fields for display
        foreach ($deals as $deal) {
            $deal->contact_name = $deal->person ? ($deal->person->first_name . ' ' . $deal->person->last_name) : ($deal->person_name ?? null);
            $deal->owner_name = $deal->owner ? $deal->owner->name : null;
        }

        $leadSources = \App\Models\LeadSource::orderBy('name')->get();
        $priorities = [ ['value'=>'high','label'=>'High'], ['value'=>'normal','label'=>'Normal'], ['value'=>'low','label'=>'Low'] ];
         // Fetch active categories for the filter
        $categories = \App\Models\ProductCategory::where('status', 1)->orderBy('category_name')->get();
        if (!$categories || $categories->isEmpty()) {
            $categories = collect(['No Categories Available']); // Default fallback
        }
        return view('deals.pipeline', compact('stages', 'deals', 'leadSources', 'priorities', 'user', 'categories'));
    }

    public function list()
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $user = auth()->user();
        $view = request('view', 'All Deals');
        $createdThisMonth = request('created_this_month');
        $closingThisMonth = request('closing_this_month');
        $query = \App\Models\Deal::with(['owner', 'stage']);

        // Role-based base filter
        if (!($user->crm_role_type === 0 || $user->crm_role_type === 1 || $user->crm_role_type === 2)) {
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('user_owner_id', $user->id);
            });
        }elseif ($user->crm_role_type === 2) { // Manager
            $employeeIds = User::where('assign_manager', $user->id)->pluck('id');
            $query->where(function($q) use ($user, $employeeIds) {
                $q->where('created_by', $user->id)
                  ->orWhere('user_owner_id', $user->id)
                  ->orWhereIn('created_by', $employeeIds)
                  ->orWhereIn('user_owner_id', $employeeIds);
            });
        }

        // View filter
        switch ($view) {
            case 'Closed Won':
                $query->where('status', 'closed won');
                break;
            case 'Closed Lost':
                $query->where('status', 'closed lost');
                break;
            case 'Open Deals':
                $query->where('status', 'open');
                break;
            case 'My Deals':
                $query->where('user_owner_id', $user->id);
                break;
            case 'Recently Created Deals':
                $query->orderBy('created_at', 'desc');
                break;
            case 'Recently Modified Deals':
                $query->orderBy('updated_at', 'desc');
                break;
            case 'All Deals':
            default:
                // No additional filter
                break;
        }

        // Search/Filter logic
        $title = request('title');
        $label = request('label');
        $stage = request('stage');
        $lead_source = request('lead_source');
        $reqStart = request('start_date');
        $reqEnd = request('end_date');

        if ($title) {
            $term = $title;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhereHas('person', function ($p) use ($term) {
                        $p->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$term}%"]);
                    })
                    ->orWhereHas('organization', function ($o) use ($term) {
                        $o->where('name', 'like', "%{$term}%");
                    });
            });
        }
        if ($label) {
            $query->where('label', $label);
        }
        if ($stage) {
            $stageId = Stage::resolveId($stage);
            if ($stageId) {
                $query->where('stage_id', $stageId);
            }
        }
        if ($lead_source) {
            $query->where('lead_source', $lead_source);
        }

        // Filter for deals created this month (from dashboard card)
        if ($createdThisMonth) {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        // Filter for deals closing this month (from dashboard card)
        if ($closingThisMonth) {
            $query->whereMonth('close_date', now()->month)->whereYear('close_date', now()->year);
        }

        // For filter dropdowns
        $stages = \App\Models\Stage::orderBy('order_by')->get();
        $leadSources = \App\Models\LeadSource::orderBy('name')->get();
        $priorities = [
            ['value' => 'high', 'label' => 'High'],
            ['value' => 'normal', 'label' => 'Normal'],
            ['value' => 'low', 'label' => 'Low'],
        ];
        // Apply date filters (start_date / end_date) if provided. Respect selected financial year
        // by intersecting the requested range with the FY range. If the intersection is empty,
        // return no results.
        $selectedFyId = session('selected_financial_year', null);
        $fyFrom = null;
        $fyTo = null;
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
            if ($fy) {
                $fyFrom = \Carbon\Carbon::parse($fy->from_date)->startOfDay();
                $fyTo = \Carbon\Carbon::parse($fy->to_date)->endOfDay();
            }
        }

        try {
            $start = $reqStart ? \Carbon\Carbon::parse($reqStart)->startOfDay() : null;
        } catch (\Exception $e) {
            $start = null;
        }
        try {
            $end = $reqEnd ? \Carbon\Carbon::parse($reqEnd)->endOfDay() : null;
        } catch (\Exception $e) {
            $end = null;
        }

        if ($start || $end) {
            if ($fyFrom) {
                if ($start && $start->lt($fyFrom)) $start = $fyFrom;
                if (!$start) $start = $fyFrom;
            }
            if ($fyTo) {
                if ($end && $end->gt($fyTo)) $end = $fyTo;
                if (!$end) $end = $fyTo;
            }

            if ($start && $end && $start->gt($end)) {
                $query->whereRaw('0 = 1');
            } else {
                if ($start && $end) {
                    $query->whereBetween('created_at', [$start, $end]);
                } elseif ($start) {
                    $query->where('created_at', '>=', $start);
                } elseif ($end) {
                    $query->where('created_at', '<=', $end);
                }
            }
        } else {
            if ($fyFrom && $fyTo) {
                $query->whereBetween('created_at', [$fyFrom, $fyTo]);
            }
        }

        $deals = $query->orderByDesc('created_at')->paginate(15)->appends(request()->except('page'));
        return view('deals.index', compact('deals', 'stages', 'priorities', 'user', 'leadSources'));
    }

    public function createFromLead($leadId)
    {
        if (!auth()->user()->hasCrmPermission('convert_crm_leads_to_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent creating deals when viewing an old/closed financial year
        // $selectedFyId = session('selected_financial_year', null);
        // if ($selectedFyId) {
        //     $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        //     if ($activeFy && $selectedFyId != $activeFy->id) {
        //         return redirect()->back()->with('error', 'Selected financial year is closed. Creating deals is disabled for historical years.');
        //     }
        // }

        $lead = Lead::with(['customer', 'organization', 'person', 'owner', 'industry'])->findOrFail($leadId);
        \App\Support\RecordAccess::authorizeLead($lead);
        $leadSources = \App\Models\LeadSource::orderBy('name')->get();
        $users = \App\Models\User::where('status', 1)->orderBy('name')->get();
        $stages = \App\Models\Stage::orderBy('order_by')->orderBy('order_by')->get();
        $categories = \App\Models\ProductCategory::where('status', 1)->orderBy('category_name')->get();
        $industries = Industry::orderBy('name')->get();
        return view('deals.convertDeal', compact('lead', 'leadSources', 'users', 'stages', 'categories', 'industries'));
    }

    public function storeFromLead(Request $request, $leadId)
    {
         if (!auth()->user()->hasCrmPermission('convert_crm_leads_to_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent storing deals when viewing an old/closed financial year
        // $selectedFyId = session('selected_financial_year', null);
        // if ($selectedFyId) {
        //     $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
        //     if ($activeFy && $selectedFyId != $activeFy->id) {
        //         return redirect()->back()->with('error', 'Selected financial year is closed. Creating deals is disabled for historical years.');
        //     }
        // }

        \App\Support\RecordAccess::authorizeLead(Lead::findOrFail($leadId));

        // Normalize stage input (id or name) for case-insensitive checks
        $stageIdResolved = Stage::resolveId($request->input('stage', ''));
        $stageNameResolved = $stageIdResolved ? Stage::nameById($stageIdResolved) : null;
        $isClosedLost = $stageNameResolved && strtolower(trim($stageNameResolved)) === 'closed lost';

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric',
            'label' => 'nullable|string',
            'lead_source' => 'nullable|string',
            'user_owner_id' => 'nullable|exists:users,id',
            'stage' => 'required',
            'probability' => 'nullable|integer|min:0|max:100',
            'close_date' => 'required|date',
            'reason_for_loss' => $isClosedLost ? 'required|string|max:255' : 'nullable|string|max:255',
            'industry_id' => 'nullable|exists:industries,id',
        ]);
        if (!$stageIdResolved) {
            return redirect()->back()->withInput()->withErrors(['stage' => 'Please select a valid deal stage']);
        }
        $validated['stage_id'] = $stageIdResolved;
        $validated['stage_name'] = $stageNameResolved;
        // Load the lead early so we can perform duplicate checks using its organization/person
        $lead = Lead::findOrFail($leadId);

        // Duplicate prevention: title + organization + person
        $titleToCheck = $validated['title'] ?? $lead->getAttribute('title');
        $orgId = $lead->getAttribute('organization_id');
        $peopleId = $lead->getAttribute('people_id');
        if ($titleToCheck && $orgId && $peopleId) {
            $exists = \App\Models\Deal::whereRaw('LOWER(title) = ?', [strtolower($titleToCheck)])
                ->where('organization_id', $orgId)
                ->where('people_id', $peopleId)
                ->exists();
            if ($exists) {
                return redirect()->back()->withInput()->withErrors(['title' => 'A deal with the same title, company and contact person already exists.']);
            }
        }

        try {
            \DB::transaction(function () use ($request, $leadId, $validated, $lead) {

                // Determine status based on stage
                $stageLower = isset($validated['stage_name']) ? strtolower($validated['stage_name']) : '';
                $status = 'open';
                if ($stageLower === 'closed won') {
                    $status = 'Closed won';
                } elseif ($stageLower === 'closed lost') {
                    $status = 'Closed lost';
                }

                $ownerId = $validated['user_owner_id'] ?? $lead->getAttribute('user_owner_id');
                $industryId = $validated['industry_id'] ?? $lead->getAttribute('industry_id');

                $deal = Deal::create([
                    'title' => $validated['title'] ?? $lead->getAttribute('title'),
                    'description' => $validated['description'] ?? $lead->getAttribute('description'),
                    'amount' => $validated['amount'] ?? $lead->getAttribute('amount'),
                    'probability' => $validated['probability'] ?? 10,
                    'label' => $validated['label'] ?? $lead->getAttribute('label'),
                    'lead_source' => $validated['lead_source'] ?? $lead->getAttribute('lead_source'),
                    'referral_name' => $validated['referral_name']
                        ?? $lead->getAttribute('referral_name'),
                    'category' => $lead->getAttribute('category'),
                    'industry_id' => $industryId,
                    'main_category_id' => $lead->getAttribute('main_category_id'),
                    'organization_id' => $lead->getAttribute('organization_id'),
                    'customer_id' => $lead->getAttribute('customer_id'),
                    'people_id' => $lead->getAttribute('people_id'),
                    'user_owner_id' => $ownerId,
                    'assigned_id' => $lead->getAttribute('assigned_id'),
                    'stage_id' => $validated['stage_id'] ?? null,
                    'close_date' => $validated['close_date'] ?? null,
                    'status' => $status,
                    'reason_for_loss' => $validated['reason_for_loss'] ?? null,
                    'created_by' => $lead->getAttribute('created_by'),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'converted_lead_id' => $lead->id,
                ]);

                // Keep company industry in sync when converting
                if ($industryId && $deal->organization_id) {
                    \App\Models\Organization::where('id', $deal->organization_id)->update([
                        'industry_type' => $industryId,
                        'updated_by' => auth()->id(),
                        'updated_at' => now(),
                    ]);
                }

                // If converted to Closed Won, update monthly sales for the owner
                if ($status === 'Closed won' && $deal->user_owner_id && $deal->amount) {
                    $user = \App\Models\User::find($deal->user_owner_id);
                    $salesTarget = $user ? ($user->sales_target ?? 0) : 0;
                    $closeDate = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : now();
                    $year = $closeDate->year;
                    $month = $closeDate->month;
                    \App\Models\UserMonthlySales::updateOrCreate(
                        [
                            'user_id' => $deal->user_owner_id,
                            'year' => $year,
                            'month' => $month
                        ],
                        [
                            'achieved_sales' => DB::raw('achieved_sales + ' . (float) ($deal->amount ?? 0)),
                            'sales_target' => $salesTarget
                        ]
                    );
                    
                    try {
                        $notificationService = app(FirebaseNotificationService::class);
                        $admins = User::where('crm_role_type', 1)->get();

                        foreach ($admins as $admin) {
                            // echo "Checking admin (User ID: {$admin->id}) with FCM token: {$admin->fcm_token}\n";
                            if (!empty($admin->fcm_token)) {
                                $notificationService->sendNotification(
                                    $admin->fcm_token,
                                    "Deal Closed Won",
                                    "The deal '{$deal->title}' has been marked as Closed Won.",
                                    [
                                        'type' => 'deal',
                                        'deal_id' => (string)$deal->id
                                    ]
                                );

                                //echo "Notification sent to admin (User ID: {$admin->id})\n";
                            } else {
                            }
                                
                        }

                        $assignedUser = User::find($deal->user_owner_id);
                        if ($assignedUser && !empty($assignedUser->fcm_token)) {
                            $notificationService->sendNotification(
                                $assignedUser->fcm_token,
                                "Deal Closed Won",
                                "The deal '{$deal->title}' assigned to you has been marked as Closed Won.",
                                [
                                    'type' => 'deal',
                                    'deal_id' => (string)$deal->id
                                ]
                            );
                        } elseif ($assignedUser) {
                        }
                        
                    } catch (\Exception $e) {
                        Log::error('Error sending Firebase notification for Closed Won deal', ['deal_id' => $deal->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                    }
                }

                $lead->update(['converted_at' => now()]);
            });
        } catch (\Exception $e) {
            Log::error('Error converting lead to deal', ['lead_id' => $leadId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Unable to convert lead to deal due to an internal error. Please contact admin.');
        }
        return redirect()->route('deals.index')->with('success', 'Lead converted to deal successfully');
    }

    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent creating deals when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Creating deals is disabled for historical years.');
            }
        }
        $leadSources = \App\Models\LeadSource::orderBy('name')->get();
        $stages = \App\Models\Stage::orderBy('order_by')->orderBy('order_by')->get();
        $users = \App\Models\User::where('status', 1)->orderBy('name')->get();
        $currentUser = auth()->user();
        $organizations = \App\Models\Organization::orderBy('name')->get(); // <-- Add this line

        // Fetch active categories for the form
        $categories = \App\Models\ProductCategory::where('status', 1)->orderBy('category_name')->get();
        $industries = Industry::orderBy('name')->get();
        $countries = [
            'India', 'United Arab Emirates', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Singapore', 'Japan', 'China', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman', 'Switzerland', 'Malaysia', 'New Zealand', 'South Africa', 'Other'
        ];
        return view('deals.create', compact( 'currentUser', 'leadSources', 'stages', 'users', 'organizations', 'categories', 'industries', 'countries'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent storing deals when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Creating deals is disabled for historical years.');
            }
        }

        $messages = [
            'organization_id.required' => 'Please select or enter a company name',
            'organization_id.string' => 'Company name must be text',
            'organization_id.max' => 'Company name may not exceed 255 characters',
            'customer_id.string' => 'Company owner name must be text',
            'customer_id.max' => 'Company owner name may not exceed 255 characters',
            'people_id.required' => 'Please select or enter a contact person',
            'people_id.string' => 'Contact person name must be text',
            'people_id.max' => 'Contact person name may not exceed 255 characters',
            'amount.required' => 'Please enter the deal amount',
            'amount.numeric' => 'Deal amount must be a valid number',
            'probability.integer' => 'Probability must be a whole number',
            'probability.min' => 'Probability must be between 0 and 100',
            'probability.max' => 'Probability must be between 0 and 100',
            'label.required' => 'Please select a priority level',
            'label.string' => 'Priority must be selected from the list',
            'title.required' => 'Please enter the deal title',
            'title.string' => 'Deal title must be text',
            'title.max' => 'Deal title may not exceed 255 characters',
            'description.string' => 'Description must be text',
            'lead_source.required' => 'Please select a lead source',
            'lead_source.string' => 'Lead source must be selected from the list',
            'referral_name.required' => 'Please enter the Referral Name.',
            'referral_name.max' => 'Referral Name may not exceed 255 characters.',
            'user_owner_id.required' => 'Please select an account owner',
            'user_owner_id.exists' => 'Selected account owner does not exist',
            'stage.required' => 'Please select a deal stage',
            'close_date.required' => 'Please select an expected close date',
            'close_date.date' => 'Expected close date must be a valid date',
            'industry_id.exists' => 'Selected industry is invalid.',
            'categories.required' => 'Please select at least one category.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.exists' => 'Selected category is invalid.',
        ];

        $stageIdResolved = Stage::resolveId($request->input('stage', ''));
        $stageNameResolved = $stageIdResolved ? Stage::nameById($stageIdResolved) : null;
        $isClosedLost = $stageNameResolved && strtolower(trim($stageNameResolved)) === 'closed lost';

        $validated = $request->validate([
            'organization_id' => 'required|string|max:255',
            'customer_id' => 'nullable|string|max:255',
            'people_id' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'status' => 'nullable|string',
            'label' => 'required|string',

            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'lead_source' => 'required|string',
            'referral_name' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => \App\Models\LeadSource::isReferralSourceId($request->input('lead_source'))),
                'nullable',
                'string',
                'max:255',
            ],
            'user_owner_id' => 'required|exists:users,id',
            'stage' => 'required',
            'probability' => 'nullable|integer|min:0|max:100',
            'close_date' => 'required|date',
            'reason_for_loss' => $isClosedLost ? 'required|string|max:255' : 'nullable|string|max:255',
            'industry_id' => 'nullable|exists:industries,id',

            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
        ], $messages);

        if (!$stageIdResolved) {
            return redirect()->back()->withInput()->withErrors(['stage' => 'Please select a valid deal stage']);
        }
        $validated['stage_id'] = $stageIdResolved;
        $validated['stage_name'] = $stageNameResolved;

        // --- Organization ---
        $organizationId = null;
        $industryId = $validated['industry_id'] ?? null;
        if (!empty($validated['organization_id'])) {
             $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($validated['organization_id'])])->first();
            if ($org) {
                $organizationId = $org->id;
                if ($industryId) {
                    $org->update([
                        'industry_type' => $industryId,
                        'updated_by' => auth()->id(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                $org = \App\Models\Organization::create([
                    'name' => $validated['organization_id'],
                    'industry_type' => $industryId ?? 0,
                    'organization_type' => 1, // default or null
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
                $organizationId = $org->id;
            }
        }

        // --- Customer ---
        $customerId = null;
        if (!empty($validated['customer_id'])) {
            $customer = \App\Models\Customer::whereRaw('LOWER(name) = ?', [strtolower($validated['customer_id'])])->where('organization_id', $organizationId)->first();
            if ($customer) {
                $customerId = $customer->id;
            } else {
                $customer = \App\Models\Customer::create([
                    'name' => $validated['customer_id'],
                    'organization_id' => $organizationId,
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
                $customerId = $customer->id;
            }
        }
        // --- Person ---
        $peopleId = null;
        if (!empty($validated['people_id'])) {
            $personName = trim($validated['people_id']);
            $parts = explode(' ', $personName, 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';

            $person = \App\Models\Person::findByNameForOrganization($personName, $organizationId);
            if ($person) {
                if ($organizationId) {
                    $person->attachToOrganization((int) $organizationId);
                }
                $peopleId = $person->id;
            } else {
                $person = \App\Models\Person::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'organization_id' => $organizationId,
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
                if ($organizationId) {
                    $person->attachToOrganization((int) $organizationId);
                }
                $peopleId = $person->id;
            }
        }

        // Server-side duplicate prevention: title + organization + person
        if (!empty($validated['title']) && $organizationId && $peopleId) {
            $exists = \App\Models\Deal::whereRaw('LOWER(title) = ?', [strtolower($validated['title'])])
                ->where('organization_id', $organizationId)

                ->exists();
            if ($exists) {
                return redirect()->back()->withInput()->withErrors(['title' => 'A deal with the same title, company and contact person already exists.']);
            }
        }


        // Determine status from provided stage (so creating with Closed Won/Lost sets status correctly)
        $stageLowerCreate = isset($validated['stage_name']) ? strtolower(trim($validated['stage_name'])) : '';
        $statusToSave = 'open';
        if ($stageLowerCreate === 'closed won') {
            $statusToSave = 'Closed won';
        } elseif ($stageLowerCreate === 'closed lost') {
            $statusToSave = 'Closed lost';
        }

        $deal = Deal::create( [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'] ?? null,
            'probability' => $validated['probability'] ?? 10,
            'label' => $validated['label'] ?? null,
            'lead_source' => $validated['lead_source'] ?? null,
            'referral_name' => \App\Models\LeadSource::isReferralSourceId($validated['lead_source'] ?? null)
                ? ($validated['referral_name'] ?? null)
                : null,
            'industry_id' => $industryId,
            'category' => implode(',', $validated['categories']), // Store categories as a comma-separated string
            'user_owner_id' => $validated['user_owner_id'] ?? null,
            'stage_id' => $validated['stage_id'] ?? null,
            'status' => $statusToSave,
            'close_date' => $validated['close_date'] ?? null,
            'assigned_id' => $validated['user_owner_id'] ?? null,
            'organization_id' => $organizationId,
            'customer_id' => $customerId,
            'people_id' => $peopleId,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
            'updated_by' => auth()->id(),
        ]);
        // If created as Closed Won, update monthly sales for the owner
        if ($statusToSave === 'Closed won' && $deal->user_owner_id && $deal->amount) {
            $user = \App\Models\User::find($deal->user_owner_id);
            $salesTarget = $user ? ($user->sales_target ?? 0) : 0;
            $closeDate = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : now();
            $year = $closeDate->year;
            $month = $closeDate->month;
            \App\Models\UserMonthlySales::updateOrCreate(
                [
                    'user_id' => $deal->user_owner_id,
                    'year' => $year,
                    'month' => $month
                ],
                [
                    'achieved_sales' => DB::raw('achieved_sales + ' . (float) ($deal->amount ?? 0)),
                    'sales_target' => $salesTarget
                ]
            );

            try {
                $notificationService = app(FirebaseNotificationService::class);
                $admins = User::where('crm_role_type', 1)->get();

                foreach ($admins as $admin) {
                    // echo "Checking admin (User ID: {$admin->id}) with FCM token: {$admin->fcm_token}\n";
                    if (!empty($admin->fcm_token)) {
                        $notificationService->sendNotification(
                            $admin->fcm_token,
                            "Deal Closed Won",
                            "The deal '{$deal->title}' has been marked as Closed Won.",
                            [
                                'type' => 'deal',
                                'deal_id' => (string)$deal->id
                            ]
                        );

                        //echo "Notification sent to admin (User ID: {$admin->id})\n";
                    } else {
                    }
                        
                }

                $assignedUser = User::find($deal->user_owner_id);
                if ($assignedUser && !empty($assignedUser->fcm_token)) {
                    $notificationService->sendNotification(
                        $assignedUser->fcm_token,
                        "Deal Closed Won",
                        "The deal '{$deal->title}' assigned to you has been marked as Closed Won.",
                        [
                            'type' => 'deal',
                            'deal_id' => (string)$deal->id
                        ]
                    );
                } elseif ($assignedUser) {
                }
                
            } catch (\Exception $e) {
                Log::error('Error sending Firebase notification for Closed Won deal', ['deal_id' => $deal->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        }
        return redirect()->route('deals.index')->with('success', 'Deal created successfully');
    }

    /**
     * AJAX: Check if a deal with the same title + organization + contact person already exists.
     * Returns JSON { duplicate: true|false }
     */
    public function checkDuplicate(Request $request)
    {
        $title = trim($request->get('title', ''));
        $orgInput = $request->get('organization', '');
        $personInput = $request->get('person', '');

        if ($title === '' || $orgInput === ''){ //|| $personInput === '') {
            return response()->json(['duplicat' => false]);
        }

        // Resolve organization id (accept numeric id or name)
        $organizationId = null;
        if (is_numeric($orgInput)) {
            $organizationId = intval($orgInput);
        } else {
            $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($orgInput)])->first();
            if ($org) $organizationId = $org->id;
        }

        // Resolve person id (accept numeric id or "First Last" name)
        $peopleId = null;
        if (is_numeric($personInput)) {
            $peopleId = intval($personInput);
        } else {
            $parts = explode(' ', trim($personInput), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? null;
            $personQuery = \App\Models\Person::whereRaw('LOWER(first_name) = ?', [strtolower($firstName)]);
            if ($lastName) {
                $personQuery->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)]);
            }
            if ($organizationId) {
                $personQuery->where('organization_id', $organizationId);
            }
            $person = $personQuery->first();
            if ($person) $peopleId = $person->id;
        }

        if (!$organizationId ) {
            return response()->json(['duplicat' => false]);
        }

        $exists = \App\Models\Deal::whereRaw('LOWER(title) = ?', [strtolower($title)])
            ->where('organization_id', $organizationId)
                ->when($peopleId, function ($query) use ($peopleId) {
                    $query->where('people_id', $peopleId);
                })
            ->exists();

        return response()->json(['duplicate' => $exists]);
    }

    public function ajaxCreate(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_deals_guard')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Prevent creating deals when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return response()->json(['error' => 'Selected financial year is closed. Creating deals is disabled for historical years.'], 400);
            }
        }

        $validated = $request->validate([
            'deal_name' => 'required|string|max:255',
            'deal_amount' => 'nullable|numeric',
            'deal_owner' => 'required|exists:users,id',
            'deal_stage' => 'required',
            'deal_date' => 'required|date',
            'cust_org_id' => 'required|exists:organizations,id',
            'deal_person_id' => 'required|exists:people,id',
            'reason_for_loss' => 'nullable|string|max:255',
            'probability' => 'nullable|integer|min:0|max:100',

        ]);

        $dealStageId = Stage::resolveId($validated['deal_stage']);
        if (!$dealStageId) {
            return response()->json(['error' => 'Please select a valid deal stage'], 422);
        }

        $deal = Deal::create([
            'title' => $validated['deal_name'],
            'amount' => $validated['deal_amount'],
            'user_owner_id' => $validated['deal_owner'],
            'stage_id' => $dealStageId,
            'close_date' => $validated['deal_date'],
            'organization_id' => $validated['cust_org_id'],
            'people_id' => $validated['deal_person_id'],
            'reason_for_loss' => $validated['reason_for_loss'] ?? null,
            'probability' => $validated['probability'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'deal' => $deal]);
    }

    public function edit($id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent editing deals when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Editing deals is disabled for historical years.');
            }
        }

        $deal = Deal::with(['customer', 'organization', 'person'])->findOrFail($id);
        \App\Support\RecordAccess::authorizeDeal($deal);
        $leadSources = \App\Models\LeadSource::orderBy('name')->get();
        $stages = \App\Models\Stage::orderBy('order_by')->orderBy('order_by')->get();
        $users = \App\Models\User::orderBy('name')->get();
        $organizations = \App\Models\Organization::orderBy('name')->get(); // <-- Add this line
        $categories = \App\Models\ProductCategory::where('status', 1)->orderBy('category_name')->get();
        $industries = Industry::orderBy('name')->get();
        $countries = [
            'India', 'United Arab Emirates', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Singapore', 'Japan', 'China', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman', 'Switzerland', 'Malaysia', 'New Zealand', 'South Africa', 'Other'
        ];
        return view('deals.edit', compact('deal', 'leadSources', 'stages', 'users', 'organizations', 'categories', 'industries', 'countries'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent updating deals when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Editing deals is disabled for historical years.');
            }
        }
        \App\Support\RecordAccess::authorizeDeal(Deal::findOrFail($id));

        $messages = [
            'organization_id.required' => 'Please select or enter a company name',
            'organization_id.string' => 'Company name must be text',
            'organization_id.max' => 'Company name may not exceed 255 characters',
            'customer_id.string' => 'Company owner name must be text',
            'customer_id.max' => 'Company owner name may not exceed 255 characters',
            'people_id.required' => 'Please select or enter a contact person',
            'people_id.string' => 'Contact person name must be text',
            'people_id.max' => 'Contact person name may not exceed 255 characters',
            'amount.required' => 'Please enter the deal amount',
            'amount.numeric' => 'Deal amount must be a valid number',
            'probability.integer' => 'Probability must be a whole number',
            'probability.min' => 'Probability must be between 0 and 100',
            'probability.max' => 'Probability must be between 0 and 100',
            'label.required' => 'Please select a priority level',
            'label.string' => 'Priority must be selected from the list',
            'title.required' => 'Please enter the deal title',
            'title.string' => 'Deal title must be text',
            'title.max' => 'Deal title may not exceed 255 characters',
            'description.string' => 'Description must be text',
            'lead_source.required' => 'Please select a lead source',
            'lead_source.string' => 'Lead source must be selected from the list',
            'referral_name.required' => 'Please enter the Referral Name.',
            'referral_name.max' => 'Referral Name may not exceed 255 characters.',
            'user_owner_id.required' => 'Please select an account owner',
            'user_owner_id.exists' => 'Selected account owner does not exist',
            'stage.required' => 'Please select a deal stage',
            'close_date.required' => 'Please select an expected close date',
            'close_date.date' => 'Expected close date must be a valid date',
            'industry_id.exists' => 'Selected industry is invalid.',
            'categories.required' => 'Please select at least one category.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.exists' => 'Selected category is invalid.',
        ];

        // Removed debug code causing memory exhaustion
        $stageIdResolved = Stage::resolveId($request->input('stage', ''));
        $stageNameResolved = $stageIdResolved ? Stage::nameById($stageIdResolved) : null;
        $isClosedLost = $stageNameResolved && strtolower(trim($stageNameResolved)) === 'closed lost';

        $validated = $request->validate([
            'organization_id' => 'required|string|max:255',
            'customer_id' => 'nullable|string|max:255',
            'people_id' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'status' => 'nullable|string',
            'label' => 'required|string',

            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'lead_source' => 'required|string',
            'referral_name' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => \App\Models\LeadSource::isReferralSourceId($request->input('lead_source'))),
                'nullable',
                'string',
                'max:255',
            ],
            'user_owner_id' => 'required|exists:users,id',
            'stage' => 'required',
            'probability' => 'nullable|integer|min:0|max:100',
            'close_date' => 'required|date',
            'reason_for_loss' => $isClosedLost ? 'required|string|max:255' : 'nullable|string|max:255',
            'industry_id' => 'nullable|exists:industries,id',
            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
        ], $messages);

        if (!$stageIdResolved) {
            return redirect()->back()->withInput()->withErrors(['stage' => 'Please select a valid deal stage']);
        }
        $validated['stage_id'] = $stageIdResolved;
        $validated['stage_name'] = $stageNameResolved;
        // $deal = Deal::findOrFail($id);
        // $deal->update($validated + [
        //     'updated_at' => now(),
        // ]);

        // --- Organization ---
        $organizationId = null;
        $industryId = $validated['industry_id'] ?? null;
        if (!empty($validated['organization_id'])) {
             $org = \App\Models\Organization::whereRaw('LOWER(name) = ?', [strtolower($validated['organization_id'])])->first();
            if ($org) {
                $organizationId = $org->id;
                if ($industryId) {
                    $org->update([
                        'industry_type' => $industryId,
                        'updated_by' => auth()->id(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                $org = \App\Models\Organization::create([
                    'name' => $validated['organization_id'],
                    'industry_type' => $industryId ?? 0,
                    'organization_type' => 1, // default or null
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
                $organizationId = $org->id;
            }
        }

        // --- Customer ---
        $customerId = null;
        if (!empty($validated['customer_id'])) {
            $customer = \App\Models\Customer::whereRaw('LOWER(name) = ?', [strtolower($validated['customer_id'])])->where('organization_id', $organizationId)->first();
            if ($customer) {
                $customerId = $customer->id;
            } else {
                $customer = \App\Models\Customer::create([
                    'name' => $validated['customer_id'],
                    'organization_id' => $organizationId,
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
                $customerId = $customer->id;
            }
        }
        // --- Person ---
        $peopleId = null;
        if (!empty($validated['people_id'])) {
            $personName = trim($validated['people_id']);
            $parts = explode(' ', $personName, 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';

            $person = \App\Models\Person::findByNameForOrganization($personName, $organizationId);
            if ($person) {
                if ($organizationId) {
                    $person->attachToOrganization((int) $organizationId);
                }
                $peopleId = $person->id;
            } else {
                $person = \App\Models\Person::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'organization_id' => $organizationId,
                    'user_owner_id' => $validated['user_owner_id'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
                if ($organizationId) {
                    $person->attachToOrganization((int) $organizationId);
                }
                $peopleId = $person->id;
            }
        }
        $deal = Deal::findOrFail($id);
        $status = $validated['status'] ?? $deal->status;
        $stageChanged = isset($validated['stage_id']) && (int) $validated['stage_id'] !== (int) $deal->stage_id;
        $wasClosedWon = strtolower((string) $deal->stage_name) === 'closed won';
        $isClosedWon = isset($validated['stage_name']) && strtolower($validated['stage_name']) === 'closed won';
        if (isset($validated['stage_name'])) {
            if ($isClosedWon) {
                $status = 'Closed won';
            } elseif (strtolower($validated['stage_name']) === 'closed lost') {
                $status = 'Closed lost';
            } else {
                $status = 'open';
            }
        }
        $deal->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'] ?? null,
            'probability' => $validated['probability'] ?? 10,
            'label' => $validated['label'] ?? null,
            'lead_source' => $validated['lead_source'] ?? null,
            'referral_name' => \App\Models\LeadSource::isReferralSourceId($validated['lead_source'] ?? null)
                ? ($validated['referral_name'] ?? null)
                : null,
            'industry_id' => $industryId,
            'category' => implode(',', $validated['categories']), // Store categories as a comma-separated string,
            'user_owner_id' => $validated['user_owner_id'] ?? null,
            'stage_id' => $validated['stage_id'] ?? null,
            'status' => $status,
            'close_date' => $validated['close_date'] ?? null,
            'assigned_id' => $validated['user_owner_id'] ?? null,
            'organization_id' => $organizationId,
            'customer_id' => $customerId,
            'people_id' => $peopleId,
            'reason_for_loss' => $validated['reason_for_loss'] ?? null,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);
        // Record stage change in stage_history if changed
        if ($stageChanged) {
            \App\Models\StageHistory::create([
                'deal_id' => $deal->id,
                'stage_name' => $validated['stage_name'] ?? Stage::nameById($validated['stage_id']),
                'amount' => $deal->amount,
                'probability' => $deal->probability,
                'close_date' => $deal->close_date,
                'modified_time' => now(),
                'modified_by' => auth()->id(),
            ]);
            // Only update monthly sales if moving to Closed Won and was not already Closed Won
            if ($isClosedWon && !$wasClosedWon && $deal->user_owner_id && $deal->amount) {
                    $closeDate = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : now();
                    $year = $closeDate->year;
                    $month = $closeDate->month;
                    $user = \App\Models\User::find($deal->user_owner_id);
                    $salesTarget = $user ? ($user->sales_target ?? 0) : 0;
                    \App\Models\UserMonthlySales::updateOrCreate(
                        [
                            'user_id' => $deal->user_owner_id,
                            'year' => $year,
                            'month' => $month
                        ],
                        [
                            'achieved_sales' => \DB::raw('achieved_sales + ' . (float) ($deal->amount ?? 0)),
                            'sales_target' => $salesTarget
                        ]
                    );

                     try {
                        $notificationService = app(FirebaseNotificationService::class);
                        $admins = User::where('crm_role_type', 1)->get();

                        foreach ($admins as $admin) {
                            // echo "Checking admin (User ID: {$admin->id}) with FCM token: {$admin->fcm_token}\n";
                            if (!empty($admin->fcm_token)) {
                                $notificationService->sendNotification(
                                    $admin->fcm_token,
                                    "Deal Closed Won",
                                    "The deal '{$deal->title}' has been marked as Closed Won.",
                                    [
                                        'type' => 'deal',
                                        'deal_id' => (string)$deal->id
                                    ]
                                );

                                //echo "Notification sent to admin (User ID: {$admin->id})\n";
                            } else {
                            }
                                
                        }

                        $assignedUser = User::find($deal->user_owner_id);
                        if ($assignedUser && !empty($assignedUser->fcm_token)) {
                            $notificationService->sendNotification(
                                $assignedUser->fcm_token,
                                "Deal Closed Won",
                                "The deal '{$deal->title}' assigned to you has been marked as Closed Won.",
                                [
                                    'type' => 'deal',
                                    'deal_id' => (string)$deal->id
                                ]
                            );
                        } elseif ($assignedUser) {
                        }
                        
                    } catch (\Exception $e) {
                        Log::error('Error sending Firebase notification for Closed Won deal', ['deal_id' => $deal->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                    }
                     
            }
        }
        return redirect()->route('deals.index')->with('success', 'Deal updated successfully');
    }

    public function show($id)
    {
        if (!auth()->user()->hasCrmPermission('view_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $users = User::all();
        $deal = Deal::with(['owner', 'industry', 'person'])->findOrFail($id);
        \App\Support\RecordAccess::authorizeDeal($deal);
        $leads = Lead::all();
        // Fetch category names for each lead
        $categoryIds = explode(',', $deal->category ?? '');
        $deal->category_names = \App\Models\ProductCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();

        $meetings = \App\Models\Meeting::where('related_type', 'deal')->where('related_id', $deal->id)->orderByDesc('start_at')->get();
                    //participants list for each meeting
        foreach ($meetings as $meeting) {
            $meeting->related_name = $meeting->related_type === 'lead' ? $meeting->lead->title : ($meeting->related_type === 'deal' ? $meeting->deal->title : null);
            $meeting->participants_list = $meeting->participants()->with('user')->get()->map(function ($participant) {
                if($participant->type === 'user') {
                    $user = \App\Models\User::find($participant->user_id);
                    $users = $user ? $user->name : null;
                }
                if($participant->type == 'contact'){
                    $contact = \App\Models\Person::find($participant->person_id);
                    $users = $contact ? $contact->first_name . ' ' . $contact->last_name : null;
                }
                return [
                    'id' => $participant->id,
                    'type' => $participant->type,
                    'name' => $users,
                ];
            });
            $meeting->participants_names = $meeting->participants_list->pluck('name')->implode(', ');
            $meeting->participants_lists = $meeting->participants()->with('user')->get()->map(function ($participant) {
                return [
                    'id' => $participant->type === 'user' ? $participant->user_id : ($participant->type === 'contact' ? $participant->person_id : $participant->id),
                    'type' => $participant->type
                ];
            })->toArray();
        }
         $contacts = Contact::all();
        $quotes = Quotation::with('owner')
            ->where('deal_id', $deal->id)
            ->orderByDesc('created_at')
            ->get();

        return view('deals.show', compact('deal', 'users', 'leads', 'meetings', 'contacts', 'quotes'));
    }

    public function showConvertDeals($id)
    {
        if (!auth()->user()->hasCrmPermission('view_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $deal = Deal::where('converted_lead_id', $id)->firstOrFail();
        return $this->show($deal->id);
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent deleting deals when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Deleting deals is disabled for historical years.');
            }
        }
        $deal = Deal::findOrFail($id);
        \App\Support\RecordAccess::authorizeDeal($deal);
        $deal->delete();
        return redirect()->route('deals.index')->with('success', 'Deal deleted successfully');
    }

    // ...existing code...

    public function markWon($id)
    {
        if (!auth()->user()->hasCrmPermission('won_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent marking as won when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Modifications are disabled for historical years.');
            }
        }
        $deal = Deal::findOrFail($id);
        \App\Support\RecordAccess::authorizeDeal($deal);
        $deal->update([
            'status' => 'Closed won',
            'stage_id' => Stage::closedWonId(),
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);
            // Update achieved_sales for the deal owner (total and monthly)
            if ($deal->user_owner_id && $deal->amount) {
                $user = User::find($deal->user_owner_id);
                $salesTarget = $user ? ($user->sales_target ?? 0) : 0;
                if ($user) {
                    // Update monthly sales
                        $closeDate = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : now();
                        $year = $closeDate->year;
                        $month = $closeDate->month;
                    \App\Models\UserMonthlySales::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'year' => $year,
                            'month' => $month
                        ],
                        [
                            'achieved_sales' => DB::raw('achieved_sales + ' . (float) ($deal->amount ?? 0)),
                            'sales_target' => $salesTarget
                        ]
                    );
                }
            }
        return redirect()->route('deals.index')->with('success', 'Deal marked as won.');
    }

    public function markLost($id)
    {
        if (!auth()->user()->hasCrmPermission('lost_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent marking as lost when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Modifications are disabled for historical years.');
            }
        }
        $deal = Deal::findOrFail($id);
        \App\Support\RecordAccess::authorizeDeal($deal);
        $deal->update([
            'status' => 'Closed lost',
            'stage_id' => Stage::closedLostId(),
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);
        return redirect()->route('deals.index')->with('success', 'Deal marked as lost.');
    }

    public function reopen($id)
    {
        if (!auth()->user()->hasCrmPermission('reopen_crm_deals_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent reopening when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Modifications are disabled for historical years.');
            }
        }
        $deal = Deal::findOrFail($id);
        \App\Support\RecordAccess::authorizeDeal($deal);
        $deal->update([
            'status' => 'open',
            'stage_id' => Stage::qualificationId(),
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);
        return redirect()->route('deals.index')->with('success', 'Deal reopened.');
    }

    public function updateStage(Request $request)
    {
        $authUser = auth()->user();
        if (!$authUser->hasCrmPermission('edit_crm_deals_guard') && !$authUser->hasCrmPermission('manage_crm_deals_guard')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return response()->json(['success' => false, 'message' => 'Selected financial year is closed. Modifications are disabled for historical years.'], 403);
            }
        }

        $data = $request->validate([
            'deal_id' => 'required|integer',
            'stage_id' => 'required|integer',
            'amount' => 'nullable|numeric|min:0|max:999999999999',
            'close_date' => 'nullable|date',
            'reason_for_loss' => 'nullable|string|max:2000',
        ]);

        $deal = \App\Models\Deal::findOrFail($data['deal_id']);
        \App\Support\RecordAccess::authorizeDeal($deal);
        $stage = Stage::find($data['stage_id']);
        if ($stage) {
            $oldStage = $deal->stage_name;
            $oldProbability = $deal->probability;
            $deal->stage_id = $stage->id;
            // Update probability if stage has a probability value
            if (!is_null($stage->probability)) {
                $deal->probability = $stage->probability;
            }
            // Handle reason_for_loss
            if (strtolower($stage->name) === 'closed lost') {
                $deal->reason_for_loss = $request->input('reason_for_loss', '');
                $deal->status = 'Closed lost';
            } elseif (strtolower($stage->name) === 'closed won') {
                $deal->reason_for_loss = null;
                $deal->status = 'Closed won';
                // Accept optional amount and close_date when marking won via pipeline
                if (isset($data['amount']) && $data['amount'] !== '') {
                    $deal->amount = (float) $data['amount'];
                }
                if (!empty($data['close_date'])) {
                    $deal->close_date = $data['close_date'];
                }
                    // Update achieved_sales for the deal owner (total and monthly)
                    if ($deal->user_owner_id && $deal->amount) {
                        $user = User::find($deal->user_owner_id);
                        $salesTarget = $user ? ($user->sales_target ?? 0) : 0;
                        if ($user) {
                             // Update monthly sales
                            $closeDate = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : now();
                            $year = $closeDate->year;
                            $month = $closeDate->month;
                            \App\Models\UserMonthlySales::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'year' => $year,
                                    'month' => $month
                                ],
                                [
                                    'achieved_sales' => DB::raw('achieved_sales + ' . (float) ($deal->amount ?? 0)),
                                    'sales_target' => $salesTarget
                                ]
                            );
                        }
                    }
            } else {
                $deal->reason_for_loss = null;
                $deal->status = 'open';
            }
            $deal->updated_by = auth()->id();
            $deal->updated_at = now();
            $deal->save();
            $newStage = $stage->name;
            $newProbability = $deal->probability;
            // Record stage change in stage_history
            \App\Models\StageHistory::create([
                'deal_id' => $deal->id,
                'stage_name' => $stage->name,
                'amount' => $deal->amount,
                'probability' => $deal->probability,
                'close_date' => $deal->close_date,
                'modified_time' => now(),
                'modified_by' => auth()->id(),
            ]);
            Log::info('Deal stage updated', [
                'deal_id' => $deal->id,
                'old_stage' => $oldStage,
                'new_stage' => $newStage,
                'old_probability' => $oldProbability,
                'new_probability' => $newProbability,
                'reason_for_loss' => $deal->reason_for_loss,
            ]);
            return response()->json([
                'success' => true,
                'old_stage' => $oldStage,
                'new_stage' => $newStage,
                'old_probability' => $oldProbability,
                'new_probability' => $newProbability
            ]);
        } else {
            Log::warning('Stage not found for update', [
                'deal_id' => $request->deal_id,
                'stage_id' => $request->stage_id
            ]);
            return response()->json(['success' => false, 'message' => 'Stage not found']);
        }
    }

    public function getDealDetails()
    {
        abort(404);
    }

    public function stageHistory($dealId)
    {
        \App\Support\RecordAccess::authorizeDeal(Deal::find($dealId));
        $history = \App\Models\StageHistory::with('user')
            ->where('deal_id', $dealId)
            ->orderByDesc('modified_time')
            ->get();
        return view('deals.stage_history', compact('history'));
    }

    // public function autoComplete(Request $request)
    // {
    //     $query = $request->input('q');
    //     $results = [];
    //     if ($query) {
    //         $deals = Deal::where('title', 'like', "%{$query}%")
    //             ->orderBy('title')
    //             ->get();
    //     } else {
    //         $deals = Deal::orderBy('title')->get();
    //     }
    //     foreach ($deals as $deal) {
    //         $results[] = [
    //             'id' => $deal->id,
    //             'text' => $deal->title.' ('.$deal->organization->name.')'
    //         ];
    //     }
    //     return response()->json(['results' => $results]);
    // }

    // AJAX autocomplete for customer names
    public function autoComplete(Request $request)
    {
        $search = $request->get('q', '');
        $orgName = $request->get('organization', '');
        $query = Deal::query();
        if ($orgName) {
            $org = \App\Models\Organization::where('name', $orgName)->first();
            if ($org) {
                $query->where('organization_id', $org->id);
            }
        }
        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $deals = $query->orderBy('title')->limit(50)->get(['id', 'title']);
        $maxByDeal = Quotation::query()
            ->whereIn('deal_id', $deals->pluck('id'))
            ->selectRaw('deal_id, MAX(proposal_version) as max_version')
            ->groupBy('deal_id')
            ->pluck('max_version', 'deal_id');

        $results = $deals->map(function ($deal) use ($maxByDeal) {
            $next = (int) ($maxByDeal[$deal->id] ?? 0) + 1;

            return [
                'id' => $deal->id,
                'name' => $deal->title,
                'next_proposal_version' => max(1, $next),
            ];
        })->values();

        return response()->json($results);
    }
}
