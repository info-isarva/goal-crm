<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalLoanCase;
use App\Models\GoalLoanLead;
use App\Models\GoalLoanLeadEvent;
use App\Models\GoalOrgUnit;
use App\Models\Product;
use App\Models\User;
use App\Services\Goal\GoalDashboardService;
use App\Services\Goal\GoalWorkflow;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GoalLoanLeadController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        abort_unless(
            auth()->user()->hasCrmPermission('view_leads_guard')
            || auth()->user()->hasCrmPermission('create_lead_guard')
            || auth()->user()->hasCrmPermission('channel_lead_guard'),
            403
        );

        $validated = $request->validate([
            'q' => 'nullable|string|max:150',
            'lead_type' => 'nullable|in:immediate,future',
            'status' => 'nullable|string|max:80',
            'source' => 'nullable|string|max:40',
            'per_page' => 'nullable|in:10,25,50,100',
        ]);

        $baseQuery = GoalLoanLead::query()->with(['assignee', 'orgUnit']);
        $scope = $this->scopedOrgIds();
        if ($scope === []) {
            $baseQuery->whereRaw('0 = 1');
        } else {
            $baseQuery->whereIn('goal_org_unit_id', $scope);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $baseQuery->where(function ($inner) use ($q) {
                $inner->where('customer_name', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%")
                    ->orWhere('ref_no', 'like', "%{$q}%")
                    ->orWhere('channel_partner', 'like', "%{$q}%");
            });
        }
        if ($request->filled('lead_type')) {
            $baseQuery->where('lead_type', $request->input('lead_type'));
        }
        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        }
        if ($request->filled('source')) {
            $baseQuery->where('source', $request->input('source'));
        }

        $summaryQuery = clone $baseQuery;
        $totalLeads = (clone $summaryQuery)->count();
        $immediateCount = (clone $summaryQuery)->where('lead_type', 'immediate')->count();
        $futureCount = (clone $summaryQuery)->where('lead_type', 'future')->count();
        $channelCount = (clone $summaryQuery)->where('source', 'channel')->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $leads = (clone $baseQuery)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $statuses = GoalLoanLead::query()
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $branches = GoalOrgUnit::query()->ofType('branch')->active()->orderBy('name')->get();
        $products = Product::query()
            ->where('product_status', 1)
            ->orderBy('product_name')
            ->get(['id', 'product_name']);

        $canCreate = auth()->user()->hasCrmPermission('create_lead_guard')
            || auth()->user()->hasCrmPermission('channel_lead_guard');

        $openCreate = $request->boolean('create')
            || $request->boolean('channel')
            || session('reopen_create_lead')
            || session()->has('errors');

        return view('goal.leads.index', compact(
            'leads',
            'totalLeads',
            'immediateCount',
            'futureCount',
            'channelCount',
            'perPage',
            'statuses',
            'branches',
            'products',
            'canCreate',
            'openCreate'
        ));
    }

    public function create(Request $request)
    {
        // Create UI now lives as a popup on Loan Leads index.
        return redirect()->route('goal.leads.index', array_filter([
            'create' => 1,
            'channel' => $request->boolean('channel') ? 1 : null,
        ]));
    }

    public function store(Request $request)
    {
        $channel = $request->boolean('channel') || $request->input('source') === 'channel';
        $canCreate = auth()->user()->hasCrmPermission('create_lead_guard');
        $canChannel = auth()->user()->hasCrmPermission('channel_lead_guard');

        if ($channel) {
            abort_unless($canChannel || $canCreate, 403);
        } else {
            abort_unless($canCreate || $canChannel, 403);
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'mobile' => PhoneNumber::mobileRules(true),
            'email' => 'nullable|email',
            'source' => 'nullable|string|max:40',
            'channel_partner' => 'nullable|string|max:150',
            'product' => 'nullable|string|max:100',
            'loan_amount' => 'nullable|numeric|min:0',
            'lead_type' => 'required|in:immediate,future',
            'disposition' => 'nullable|string|max:40',
            'follow_up_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'goal_org_unit_id' => 'nullable|exists:goal_org_units,id,is_active,1',
            'consent_recorded' => 'nullable|boolean',
        ], PhoneNumber::messages('mobile'));

        if (($data['lead_type'] ?? '') === 'future' && empty($data['follow_up_at'])) {
            return back()->withInput()->withErrors(['follow_up_at' => 'Follow-up required for Future leads.'])->with('reopen_create_lead', true);
        }

        $status = ($data['lead_type'] ?? 'immediate') === 'immediate'
            ? 'Pending BM Allocation'
            : 'Future Prospect';

        $source = $data['source'] ?? ($channel ? 'channel' : 'manual');
        if (strtolower((string) $source) === 'channel' && ! filled(trim((string) ($data['channel_partner'] ?? '')))) {
            return back()->withInput()->withErrors([
                'channel_partner' => 'Channel partner name is required for channel leads.',
            ])->with('reopen_create_lead', true);
        }

        $lead = GoalLoanLead::create([
            ...$data,
            'ref_no' => 'LD-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
            'source' => $source,
            'status' => $status,
            'consent_recorded' => $request->boolean('consent_recorded'),
            'created_by' => auth()->id(),
            'goal_org_unit_id' => $data['goal_org_unit_id'] ?? auth()->user()->goal_org_unit_id,
        ]);

        app(\App\Services\Goal\GoalPartyService::class)->attachLead($lead);

        GoalLoanLeadEvent::record(
            $lead,
            'status_change',
            'Lead Created',
            'Type: ' . ucfirst($lead->lead_type) . ' · Source: ' . ($source ?: 'manual'),
            ['from_status' => null, 'to_status' => $status]
        );

        if (!empty($data['follow_up_at'])) {
            GoalLoanLeadEvent::record(
                $lead,
                'follow_up',
                'Follow-up Scheduled',
                Carbon::parse($data['follow_up_at'])->format('d M Y, h:i a'),
                ['meta' => ['follow_up_at' => $data['follow_up_at']]]
            );
        }

        if ($request->boolean('save_and_new')) {
            return redirect()
                ->route('goal.leads.index', ['create' => 1])
                ->with('success', 'Lead created.')
                ->with('reopen_create_lead', true);
        }

        return redirect()
            ->route('goal.leads.index')
            ->with('success', 'Lead created.');
    }

    public function show(GoalLoanLead $lead)
    {
        $this->requireGoalPermission('view_leads_guard');
        $this->assertLeadInScope($lead);

        $lead->load([
            'assignee',
            'assigner',
            'creator',
            'orgUnit',
            'party',
            'assignments.toUser',
            'assignments.fromUser',
            'assignments.assigner',
            'loanCase',
            'events.user',
            'vrmCalls' => fn ($q) => $q->latest('called_at')->limit(20),
        ]);

        $this->ensureCreatedEvent($lead);

        $next = GoalWorkflow::LEAD_TRANSITIONS[$lead->status] ?? [];
        $priority = $this->priorityFor($lead);
        $assigneeLabel = $this->salesLabel($lead->assignee);
        $hasVisit = $lead->events->contains(fn ($e) => $e->event_type === 'visit');
        $progress = $this->buildProgress($lead, $hasVisit);
        $timeline = $this->buildTimeline($lead);
        $statusHistory = $lead->events
            ->where('event_type', 'status_change')
            ->sortByDesc('created_at')
            ->values();
        $bmReco = $this->bmRecoFromCase($lead->loanCase);
        $sales = $this->salesUsers($this->scopedOrgIds(), $lead->goal_org_unit_id);
        $branches = GoalOrgUnit::query()->ofType('branch')->active()->orderBy('name')->get();
        $products = Product::query()
            ->where('product_status', 1)
            ->orderBy('product_name')
            ->get(['id', 'product_name']);

        $canAllocate = auth()->user()->hasCrmPermission('bm_allocation_guard');
        $canConvert = !$lead->loanCase
            && in_array('Documents Pending', $next, true);
        $canOpenCase = (bool) $lead->loanCase;

        $noteEntries = $this->parseNotes($lead->notes);
        $linkedMatches = app(\App\Services\Goal\GoalPartyService::class)
            ->matchesForMobile($lead->mobile, $lead->id, $this->scopedOrgIds());
        $linkedLeads = $linkedMatches['leads'];
        $linkedCases = $linkedMatches['cases'];

        return view('goal.leads.show', compact(
            'lead',
            'next',
            'priority',
            'assigneeLabel',
            'progress',
            'timeline',
            'statusHistory',
            'bmReco',
            'sales',
            'branches',
            'products',
            'canAllocate',
            'canConvert',
            'canOpenCase',
            'noteEntries',
            'hasVisit',
            'linkedLeads',
            'linkedCases'
        ));
    }

    public function update(Request $request, GoalLoanLead $lead)
    {
        $this->requireGoalPermission('view_leads_guard');
        $this->assertLeadInScope($lead);

        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'mobile' => PhoneNumber::mobileRules(true),
            'email' => 'nullable|email',
            'product' => 'nullable|string|max:100',
            'loan_amount' => 'nullable|numeric|min:0',
            'lead_type' => 'required|in:immediate,future',
            'source' => 'nullable|string|max:40',
            'channel_partner' => 'nullable|string|max:150',
            'goal_org_unit_id' => 'nullable|exists:goal_org_units,id,is_active,1',
            'disposition' => 'nullable|string|max:40',
            'consent_recorded' => 'nullable|boolean',
        ], PhoneNumber::messages('mobile'));

        $source = strtolower((string) ($data['source'] ?? $lead->source ?? ''));
        if ($source === 'channel' && ! filled(trim((string) ($data['channel_partner'] ?? $lead->channel_partner ?? '')))) {
            return back()->withInput()->withErrors([
                'channel_partner' => 'Channel partner name is required for channel leads.',
            ]);
        }

        $lead->fill($data);
        $lead->consent_recorded = $request->boolean('consent_recorded');
        $lead->save();
        app(\App\Services\Goal\GoalPartyService::class)->attachLead($lead);

        return back()->with('success', 'Lead details updated.');
    }

    public function partyMatches(Request $request)
    {
        $this->requireGoalPermission('view_leads_guard');
        $data = $request->validate([
            'mobile' => PhoneNumber::mobileRules(true),
            'exclude_lead_id' => 'nullable|integer',
        ], PhoneNumber::messages('mobile'));

        $scope = $this->scopedOrgIds();
        $matches = app(\App\Services\Goal\GoalPartyService::class)->matchesForMobile(
            $data['mobile'],
            isset($data['exclude_lead_id']) ? (int) $data['exclude_lead_id'] : null,
            $scope
        );

        return response()->json([
            'party_id' => $matches['party']?->id,
            'leads' => $matches['leads']->map(fn ($l) => [
                'id' => $l->id,
                'ref_no' => $l->ref_no,
                'customer_name' => $l->customer_name,
                'status' => $l->status,
                'url' => route('goal.leads.show', $l),
            ])->values(),
            'cases' => $matches['cases']->map(fn ($c) => [
                'id' => $c->id,
                'case_no' => $c->case_no,
                'customer_name' => $c->customer_name,
                'status' => $c->status,
                'url' => route('goal.cases.show', $c),
            ])->values(),
        ]);
    }

    public function addNote(Request $request, GoalLoanLead $lead)
    {
        $this->requireGoalPermission('view_leads_guard');
        $this->assertLeadInScope($lead);

        $data = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $stamp = now()->format('d M Y, h:i a');
        $author = auth()->user()->name ?? 'User';
        $note = trim($data['note']);
        $block = '[' . $stamp . ' · ' . $author . '] ' . $note;
        $lead->notes = trim(($lead->notes ? $lead->notes . "\n\n" : '') . $block);
        $lead->save();

        GoalLoanLeadEvent::record($lead, 'note', 'Note Added', $note);

        return back()->with('success', 'Note added.')->with('ld_tab', 'overview');
    }

    public function deleteNote(Request $request, GoalLoanLead $lead, int $index)
    {
        $this->requireGoalPermission('view_leads_guard');
        $this->assertLeadInScope($lead);

        $chunks = preg_split("/\n{2,}/", trim((string) $lead->notes)) ?: [];
        $chunks = array_values(array_filter(array_map('trim', $chunks), fn ($c) => $c !== ''));

        if (!array_key_exists($index, $chunks)) {
            return back()->with('error', 'Note not found.')->with('ld_tab', 'overview');
        }

        $removed = $chunks[$index];
        unset($chunks[$index]);
        $lead->notes = trim(implode("\n\n", $chunks)) ?: null;
        $lead->save();

        $preview = \Illuminate\Support\Str::limit(
            preg_replace('/^\[.*?\]\s*/s', '', $removed) ?: $removed,
            80
        );
        GoalLoanLeadEvent::record($lead, 'note', 'Note Deleted', $preview);

        return back()->with('success', 'Note deleted.')->with('ld_tab', 'overview');
    }

    public function reschedule(Request $request, GoalLoanLead $lead)
    {
        $this->requireGoalPermission('view_leads_guard');
        $this->assertLeadInScope($lead);

        $data = $request->validate([
            'follow_up_at' => 'required|date|after_or_equal:now',
        ], [
            'follow_up_at.after_or_equal' => 'Follow-up must be today or a future date/time.',
        ]);

        $prev = optional($lead->follow_up_at)->format('d M Y, h:i a');
        $lead->follow_up_at = $data['follow_up_at'];
        $lead->save();

        $when = Carbon::parse($data['follow_up_at'])->format('d M Y, h:i a');
        GoalLoanLeadEvent::record(
            $lead,
            'follow_up',
            $prev ? 'Follow-up Rescheduled' : 'Follow-up Scheduled',
            ($prev ? 'From ' . $prev . ' → ' : '') . $when,
            ['meta' => ['follow_up_at' => $data['follow_up_at'], 'previous' => $prev]]
        );

        return back()->with('success', 'Follow-up rescheduled.')->with('ld_tab', 'overview');
    }

    public function logVisit(Request $request, GoalLoanLead $lead)
    {
        $this->requireGoalPermission('view_leads_guard');
        $this->assertLeadInScope($lead);

        $data = $request->validate([
            'visited_at' => 'nullable|date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $visitedAt = !empty($data['visited_at'])
            ? Carbon::parse($data['visited_at'])
            : now();

        GoalLoanLeadEvent::record(
            $lead,
            'visit',
            'Customer Visit Logged',
            trim(($data['remarks'] ?? '') !== '' ? $data['remarks'] : 'Visit recorded for lead workflow'),
            [
                'meta' => [
                    'visited_at' => $visitedAt->toDateTimeString(),
                    'remarks' => $data['remarks'] ?? null,
                ],
            ]
        );

        // Advance workflow toward Visited when still early in the funnel
        $from = $lead->status;
        if (in_array($from, ['Assigned', 'Contact Pending'], true)
            && GoalWorkflow::canLead($from, 'Qualified')
            && ($lead->consent_recorded || $request->boolean('consent_recorded'))
        ) {
            // Optional auto-advance not forced — keep status unless user opts in
        }

        return back()->with('success', 'Customer visit logged.')->with('ld_tab', 'overview');
    }

    public function convert(Request $request, GoalLoanLead $lead)
    {
        $this->requireGoalPermission('view_leads_guard');
        $this->assertLeadInScope($lead);

        if ($lead->loanCase) {
            return redirect()->route('goal.cases.show', $lead->loanCase);
        }

        $next = GoalWorkflow::LEAD_TRANSITIONS[$lead->status] ?? [];
        if (!in_array('Documents Pending', $next, true)) {
            return back()->withErrors([
                'convert' => 'Lead must reach Qualified before converting to a case. Current status: ' . $lead->status,
            ]);
        }
        if (! filled($lead->product) && ! filled($request->input('product'))) {
            return back()->withErrors([
                'convert' => 'Set a product on the lead before converting to a case.',
            ]);
        }

        $request->merge([
            'to_status' => 'Documents Pending',
            'consent_recorded' => $lead->consent_recorded ? 1 : $request->input('consent_recorded'),
            'product' => $request->input('product', $lead->product),
        ]);

        return $this->transition($request, $lead);
    }

    public function transition(Request $request, GoalLoanLead $lead)
    {
        $this->requireGoalPermission('view_leads_guard');
        $this->assertLeadInScope($lead);

        $data = $request->validate([
            'to_status' => 'required|string',
            'follow_up_at' => 'nullable|date|after_or_equal:now',
            'closure_reason' => 'nullable|string|max:255',
            'consent_recorded' => 'nullable|boolean',
            'product' => 'nullable|string|max:100',
        ], [
            'follow_up_at.after_or_equal' => 'Follow-up must be today or a future date/time.',
        ]);

        $lead->loadMissing('loanCase');
        $ctx = [
            'follow_up' => $data['follow_up_at'] ?? $lead->follow_up_at,
            'reason' => $data['closure_reason'] ?? null,
            'consent' => $request->boolean('consent_recorded') || $lead->consent_recorded,
            'product' => $data['product'] ?? $lead->product,
            'assignee' => $lead->assigned_to,
            'docsVerified' => $this->leadMandatoryDocsVerified($lead),
        ];

        $gate = GoalWorkflow::validateLead($lead->status, $data['to_status'], $ctx);
        if (!$gate['ok']) {
            return back()->withErrors(['to_status' => implode(' ', $gate['errors'])]);
        }

        $fromStatus = $lead->status;
        $lead->status = $data['to_status'];
        if (!empty($data['follow_up_at'])) {
            $lead->follow_up_at = $data['follow_up_at'];
        }
        if (!empty($data['closure_reason'])) {
            $lead->closure_reason = $data['closure_reason'];
        }
        if ($request->boolean('consent_recorded')) {
            $lead->consent_recorded = true;
        }
        if (!empty($data['product'])) {
            $lead->product = $data['product'];
        }
        $lead->save();

        GoalLoanLeadEvent::record(
            $lead,
            'status_change',
            'Status Changed',
            $fromStatus . ' → ' . $data['to_status']
                . (!empty($data['closure_reason']) ? ' · ' . $data['closure_reason'] : ''),
            [
                'from_status' => $fromStatus,
                'to_status' => $data['to_status'],
                'meta' => [
                    'closure_reason' => $data['closure_reason'] ?? null,
                    'follow_up_at' => $data['follow_up_at'] ?? null,
                ],
            ]
        );

        if (!empty($data['follow_up_at'])) {
            GoalLoanLeadEvent::record(
                $lead,
                'follow_up',
                'Follow-up Scheduled',
                Carbon::parse($data['follow_up_at'])->format('d M Y, h:i a'),
                ['meta' => ['follow_up_at' => $data['follow_up_at']]]
            );
        }

        if ($data['to_status'] === 'Documents Pending' && !$lead->loanCase) {
            $owner = $lead->assigned_to
                ? \App\Models\User::query()->find($lead->assigned_to)
                : null;
            $bmId = $owner?->reports_to_user_id
                ?: \App\Models\User::query()
                    ->where(function ($q) {
                        $q->where('crm_role_type', 8)->orWhereIn('job_role', ['bm', 'branch_manager']);
                    })
                    ->when($lead->goal_org_unit_id, fn ($q) => $q->where('goal_org_unit_id', $lead->goal_org_unit_id))
                    ->value('id');

            $seedDocs = \App\Services\Goal\GoalDocumentCatalog::forProduct($lead->product, true);
            $case = GoalLoanCase::create([
                'case_no' => 'CS-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
                'goal_loan_lead_id' => $lead->id,
                'goal_party_id' => $lead->goal_party_id,
                'goal_org_unit_id' => $lead->goal_org_unit_id,
                'owner_id' => $lead->assigned_to,
                'status' => 'Draft',
                'customer_name' => $lead->customer_name,
                'mobile' => $lead->mobile,
                'product' => $lead->product,
                'loan_amount' => $lead->loan_amount,
                'customer_details' => array_filter([
                    'lead_ref' => $lead->ref_no,
                    'lead_source' => $lead->source,
                    'branch_manager_id' => $bmId ? (int) $bmId : null,
                    'consent' => [
                        'recorded' => (bool) $lead->consent_recorded,
                        'source' => 'lead_convert',
                    ],
                    'documents' => $seedDocs,
                ], fn ($v) => $v !== null && $v !== ''),
            ]);
            app(\App\Services\Goal\GoalPartyService::class)->attachCase($case, $lead);

            GoalLoanLeadEvent::record(
                $lead,
                'case',
                'Case Created · ' . $case->case_no,
                'Converted from lead workflow',
                ['to_status' => $data['to_status'], 'meta' => ['case_id' => $case->id, 'case_no' => $case->case_no]]
            );

            return redirect()
                ->route('goal.cases.show', $case)
                ->with('success', 'Lead converted to case ' . $case->case_no . '.');
        }

        return back()->with('success', 'Lead status updated.')->with('ld_tab', 'overview');
    }

    protected function assertLeadInScope(GoalLoanLead $lead): void
    {
        $scope = $this->scopedOrgIds();
        if ($scope === [] || ($lead->goal_org_unit_id && ! in_array((int) $lead->goal_org_unit_id, array_map('intval', $scope), true))) {
            abort(403, 'Lead is outside your branch scope.');
        }
    }

    protected function leadMandatoryDocsVerified(GoalLoanLead $lead): bool
    {
        $case = $lead->loanCase;
        if (! $case) {
            return false;
        }
        $docs = collect(data_get($case->customer_details, 'documents', []) ?: []);
        $mandatory = $docs->where('mandatory', true);

        return $mandatory->isNotEmpty()
            && $mandatory->every(function ($d) {
                return ($d['status'] ?? '') === 'Verified'
                    && filled($d['path'] ?? ($d['storage_path'] ?? null));
            });
    }

    protected function priorityFor(GoalLoanLead $lead): string
    {
        $type = strtolower((string) ($lead->lead_type ?? ''));
        if ($type === 'immediate') {
            return 'High';
        }
        if ($type === 'future') {
            return 'Medium';
        }

        return 'Low';
    }

    protected function salesLabel(?User $user): string
    {
        if (!$user) {
            return '—';
        }
        $roleKey = GoalDashboardService::ROLE_KEYS[(int) ($user->crm_role_type ?? -1)] ?? null;
        $job = strtolower((string) ($user->job_role ?? ''));
        $code = strtoupper($roleKey ?: ($job ?: 'SALES'));

        return $code . ' - ' . $user->name;
    }

    protected function salesUsers(array $scope, $branchId = null): Collection
    {
        return User::query()
            ->where(function ($q) {
                $q->whereIn('crm_role_type', [10, 11, 12])
                    ->orWhereIn('job_role', ['jsm', 'sm', 'ssm']);
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($inner) use ($branchId) {
                    $inner->where('goal_org_unit_id', $branchId)
                        ->orWhereNull('goal_org_unit_id');
                });
            })
            ->when(!$branchId && $scope !== [], function ($q) use ($scope) {
                $q->where(function ($inner) use ($scope) {
                    $inner->whereIn('goal_org_unit_id', $scope)
                        ->orWhereNull('goal_org_unit_id');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'job_role', 'crm_role_type', 'goal_org_unit_id', 'mobile', 'email']);
    }

    protected function buildProgress(GoalLoanLead $lead, bool $hasVisit = false): array
    {
        $status = $lead->status;
        $caseStatus = $lead->loanCase?->status;

        $steps = [
            ['key' => 'new', 'label' => 'New Lead', 'icon' => 'bi-check-lg'],
            ['key' => 'allocated', 'label' => 'Allocated', 'icon' => 'bi-people-fill'],
            ['key' => 'progress', 'label' => 'In Progress', 'icon' => 'bi-hourglass-split'],
            ['key' => 'visited', 'label' => 'Visited', 'icon' => 'bi-geo-alt'],
            ['key' => 'case', 'label' => 'Case Created', 'icon' => 'bi-folder2-open'],
            ['key' => 'disbursed', 'label' => 'Disbursed', 'icon' => 'bi-cash-coin'],
        ];

        $index = 0;
        if ($status === 'New') {
            $index = 0;
        } elseif ($status === 'Pending BM Allocation') {
            // Lead created → waiting on allocation stage
            $index = 1;
        } elseif ($status === 'Assigned') {
            $index = 1;
        } elseif (in_array($status, ['Contact Pending', 'Future Prospect'], true)) {
            $index = 2;
        } elseif ($status === 'Qualified') {
            $index = 3;
        }

        // Customer visit advances the visual tracker to Visited without requiring Qualified
        if ($hasVisit && $index < 3 && $index >= 1) {
            $index = 3;
        }

        if (in_array($status, ['Documents Pending', 'Ready for Bank Login', 'Bank Login'], true) || $lead->loanCase) {
            $index = 4;
        }
        if ($caseStatus === 'Disbursed') {
            $index = 5;
        }

        $visitAt = optional(
            $lead->events->firstWhere('event_type', 'visit')?->created_at
        )->format('d M, h:i a');

        $times = [
            0 => optional($lead->created_at)->format('d M, h:i a'),
            1 => optional($lead->assigned_at)->format('d M, h:i a'),
            2 => null,
            3 => $visitAt,
            4 => optional($lead->loanCase?->created_at)->format('d M, h:i a'),
            5 => null,
        ];

        return collect($steps)->map(function ($step, $i) use ($index, $times) {
            $state = $i < $index ? 'done' : ($i === $index ? 'current' : 'todo');
            if ($index === 5 && $i === 5) {
                $state = 'done';
            }

            return $step + [
                'state' => $state,
                // Timestamps only on reached stages (matches mockup)
                'at' => ($i <= $index) ? ($times[$i] ?? null) : null,
            ];
        })->all();
    }

    protected function buildTimeline(GoalLoanLead $lead): array
    {
        $items = [];
        $seenKeys = [];

        $toneMap = [
            'status_change' => ['purple', 'bi-signpost-2'],
            'follow_up' => ['slate', 'bi-calendar-event'],
            'note' => ['amber', 'bi-journal-text'],
            'visit' => ['blue', 'bi-geo-alt'],
            'allocation' => ['blue', 'bi-person-plus'],
            'reassign' => ['amber', 'bi-arrow-left-right'],
            'case' => ['purple', 'bi-folder-check'],
            'call' => ['green', 'bi-telephone'],
            'other' => ['slate', 'bi-activity'],
        ];

        foreach ($lead->events as $event) {
            [$tone, $icon] = $toneMap[$event->event_type] ?? $toneMap['other'];
            if ($event->event_type === 'status_change' && str_starts_with(strtolower($event->title), 'lead created')) {
                $tone = 'green';
                $icon = 'bi-telephone-inbound';
            }
            $key = 'event:' . $event->id;
            $seenKeys[$key] = true;
            $by = $event->user?->name ? ('By ' . $event->user->name) : null;
            $items[] = [
                'tone' => $tone,
                'icon' => $icon,
                'title' => $event->title,
                'meta' => trim(implode(' · ', array_filter([$event->body, $by]))),
                'at' => optional($event->created_at)->format('d M Y, h:i a'),
                'sort' => optional($event->created_at)->timestamp ?? 0,
                'type' => $event->event_type,
            ];
        }

        // Legacy fallbacks for older leads without event rows
        if (!$lead->events->contains(fn ($e) => str_starts_with(strtolower($e->title), 'lead created'))) {
            $items[] = [
                'tone' => 'green',
                'icon' => 'bi-telephone-inbound',
                'title' => 'Lead Created',
                'meta' => 'Source: ' . ($this->sourceLabel($lead->source) ?: '—'),
                'at' => optional($lead->created_at)->format('d M Y, h:i a'),
                'sort' => optional($lead->created_at)->timestamp ?? 0,
                'type' => 'status_change',
            ];
        }

        foreach ($lead->assignments as $a) {
            $key = 'assign:' . $a->id;
            if (isset($seenKeys[$key])) {
                continue;
            }
            // Skip if matching allocation/reassign event already logged around same time
            $hasEvent = $lead->events->contains(function ($e) use ($a) {
                return in_array($e->event_type, ['allocation', 'reassign'], true)
                    && abs(optional($e->created_at)->timestamp - optional($a->created_at)->timestamp) < 5;
            });
            if ($hasEvent) {
                continue;
            }
            $to = $this->salesLabel($a->toUser);
            $items[] = [
                'tone' => $a->action === 'reassign' ? 'amber' : 'blue',
                'icon' => $a->action === 'reassign' ? 'bi-arrow-left-right' : 'bi-person-plus',
                'title' => $a->action === 'reassign' ? 'Reassigned to ' . $to : 'Allocated to ' . $to,
                'meta' => trim(($a->assigner?->name ? 'By ' . $a->assigner->name : '') . ($a->reason ? ' · ' . $a->reason : '')),
                'at' => optional($a->created_at)->format('d M Y, h:i a'),
                'sort' => optional($a->created_at)->timestamp ?? 0,
                'type' => $a->action === 'reassign' ? 'reassign' : 'allocation',
            ];
        }

        foreach ($lead->vrmCalls as $call) {
            $items[] = [
                'tone' => 'green',
                'icon' => 'bi-telephone',
                'title' => 'VRM Call · ' . ($call->disposition ?: 'Logged'),
                'meta' => $call->notes ? Str::limit($call->notes, 80) : ($call->customer_name ?: ''),
                'at' => optional($call->called_at ?: $call->created_at)->format('d M Y, h:i a'),
                'sort' => optional($call->called_at ?: $call->created_at)->timestamp ?? 0,
                'type' => 'call',
            ];
        }

        if ($lead->loanCase && !$lead->events->contains(fn ($e) => $e->event_type === 'case')) {
            $items[] = [
                'tone' => 'purple',
                'icon' => 'bi-folder-check',
                'title' => 'Case Created · ' . $lead->loanCase->case_no,
                'meta' => 'Status: ' . $lead->loanCase->status,
                'at' => optional($lead->loanCase->created_at)->format('d M Y, h:i a'),
                'sort' => optional($lead->loanCase->created_at)->timestamp ?? 0,
                'type' => 'case',
            ];
        }

        $bm = $this->bmRecoFromCase($lead->loanCase);
        if ($bm) {
            $items[] = [
                'tone' => 'purple',
                'icon' => 'bi-file-earmark-text',
                'title' => 'BM Recommendation Added',
                'meta' => ($bm['amount'] ?? null) ? ('₹ ' . number_format((float) $bm['amount'], 0)) : ($bm['remarks'] ?? ''),
                'at' => $bm['date'] ?? null,
                'sort' => isset($bm['date_raw']) ? Carbon::parse($bm['date_raw'])->timestamp : (optional($lead->loanCase?->updated_at)->timestamp ?? 0),
                'type' => 'other',
            ];
        }

        return collect($items)->sortByDesc('sort')->values()->take(20)->all();
    }

    protected function ensureCreatedEvent(GoalLoanLead $lead): void
    {
        $exists = $lead->events->contains(function ($e) {
            return $e->event_type === 'status_change'
                && str_contains(strtolower($e->title), 'lead created');
        });
        if ($exists) {
            return;
        }

        $event = GoalLoanLeadEvent::record(
            $lead,
            'status_change',
            'Lead Created',
            'Source: ' . ($this->sourceLabel($lead->source) ?: '—'),
            [
                'user_id' => $lead->created_by,
                'from_status' => null,
                'to_status' => $lead->getOriginal('status') ?: $lead->status,
            ]
        );
        if ($lead->created_at) {
            $event->created_at = $lead->created_at;
            $event->updated_at = $lead->created_at;
            $event->save();
        }
        $lead->setRelation('events', $lead->events->push($event)->sortByDesc('created_at')->values());
    }

    protected function bmRecoFromCase(?GoalLoanCase $case): ?array
    {
        if (!$case) {
            return null;
        }
        $raw = $case->bm_reco;
        if (!$raw) {
            return null;
        }
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (!is_array($raw) || $raw === []) {
            return null;
        }

        $amount = $raw['amount'] ?? $raw['recommended_amount'] ?? $raw['reco_amount'] ?? null;
        $by = $raw['by'] ?? $raw['recommended_by'] ?? $raw['bm_name'] ?? null;
        $dateRaw = $raw['date'] ?? $raw['recommended_at'] ?? $raw['at'] ?? null;
        $remarks = $raw['remarks'] ?? $raw['note'] ?? $raw['comment'] ?? null;

        return [
            'amount' => $amount,
            'by' => $by,
            'date' => $dateRaw ? Carbon::parse($dateRaw)->format('d M Y') : null,
            'date_raw' => $dateRaw,
            'remarks' => $remarks,
        ];
    }

    protected function parseNotes(?string $notes): array
    {
        if (!$notes || !trim($notes)) {
            return [];
        }
        $chunks = preg_split("/\n{2,}/", trim($notes)) ?: [];
        $out = [];
        foreach ($chunks as $i => $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            $author = null;
            $at = null;
            $body = $chunk;
            if (preg_match('/^\[(.*?)\]\s*(.*)$/s', $chunk, $m)) {
                $meta = $m[1];
                $body = trim($m[2]);
                if (str_contains($meta, ' · ')) {
                    [$at, $author] = array_map('trim', explode(' · ', $meta, 2));
                } else {
                    $at = $meta;
                }
            }
            $out[] = [
                'index' => $i,
                'body' => $body,
                'author' => $author,
                'at' => $at,
            ];
        }

        return array_reverse($out);
    }

    protected function sourceLabel(?string $source): string
    {
        return match (strtolower((string) $source)) {
            'vrm' => 'VRM Calling',
            'channel' => 'Channel Partner',
            'walkin' => 'Walk-in',
            'referral' => 'Referral',
            'manual' => 'Manual',
            default => $source ? ucfirst($source) : '',
        };
    }
}
