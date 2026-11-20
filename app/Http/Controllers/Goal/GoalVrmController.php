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
use App\Support\PhoneNumber;
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
            'lead_status' => 'nullable|in:all,yes,no',
            'callbacks_due' => 'nullable|in:1,0',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'date_filter' => 'nullable|date',
            'per_page' => 'nullable|in:10,25,50,100',
            'log_call' => 'nullable',
        ]);

        $visibleUserIds = $this->visibleCallLogUserIds();
        $seesTeamLogs = count($visibleUserIds) > 1;

        $todayCount = GoalVrmCallLog::query()
            ->whereIn('user_id', $visibleUserIds)
            ->whereDate('called_at', today())
            ->count();

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
        $interestedCount = (clone $summaryQuery)->where('disposition', 'Interested')->count();
        $callbackCount = (clone $summaryQuery)->where('disposition', 'Callback')->count();
        $leadsCreatedCount = (clone $summaryQuery)->whereNotNull('goal_loan_lead_id')->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $logs = (clone $baseQuery)
            ->with(['orgUnit', 'lead.orgUnit', 'user:id,name,crm_role_type,goal_org_unit_id', 'user.crmRole:id,name', 'user.orgUnit:id,name'])
            ->latest('called_at')
            ->paginate($perPage)
            ->withQueryString();

        $branches = $this->selectableBranches();
        $branchLocked = $this->isBranchLockedToOwn();
        $products = $this->activeProducts();
        $dispositions = $this->activeDispositions();
        $canQuickAddProduct = $this->canManageProductMaster();
        $canQuickAddDisposition = $this->canManageDispositionMaster();

        return view('goal.vrm.index', compact(
            'todayCount',
            'callbacksDueCount',
            'totalCalls',
            'interestedCount',
            'callbackCount',
            'leadsCreatedCount',
            'logs',
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
            'callback_at_input' => optional($log->callback_at)->format('d-m-Y H:i'),
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
            'disposition' => ['nullable', Rule::in($allowedDispositions)],
            'callback_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'product' => 'nullable|string|max:100',
            'loan_amount' => 'nullable|numeric|min:0',
            'goal_org_unit_id' => ['nullable', Rule::in($this->allowedBranchIds())],
        ], PhoneNumber::messages('mobile'));

        // Disposition is locked after create — keep the original value.
        $data['disposition'] = $log->disposition;
        $data['goal_org_unit_id'] = $this->resolveBranchId($data['goal_org_unit_id'] ?? null);

        $log->update([
            'customer_name' => $data['customer_name'],
            'mobile' => $data['mobile'] ?? null,
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

        $leadStatus = $request->input('lead_status', 'all');
        if ($leadStatus === 'yes') {
            $query->whereNotNull('goal_loan_lead_id');
        } elseif ($leadStatus === 'no') {
            $query->whereNull('goal_loan_lead_id');
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

    public function store(Request $request)
    {
        $this->requireGoalPermission('vrm_calling_guard');
        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'mobile' => PhoneNumber::mobileRules(true),
            'disposition' => ['required', Rule::in(GoalVrmCallLog::allowedDispositionNames())],
            'callback_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'create_lead' => 'nullable|boolean',
            'product' => [
                Rule::requiredIf(fn () => $request->boolean('create_lead')),
                'nullable',
                'string',
                'max:100',
                Rule::in($this->activeProducts()->pluck('product_name')->all()),
            ],
            'loan_amount' => [
                Rule::requiredIf(fn () => $request->boolean('create_lead')),
                'nullable',
                'numeric',
                'min:0',
            ],
            'goal_org_unit_id' => ['nullable', Rule::in($this->allowedBranchIds())],
            'save_and_new' => 'nullable|boolean',
        ], array_merge(PhoneNumber::messages('mobile'), [
            'product.required' => 'Please select a product when creating a lead.',
            'loan_amount.required' => 'Please enter loan amount when creating a lead.',
        ]));

        $data['goal_org_unit_id'] = $this->resolveBranchId($data['goal_org_unit_id'] ?? null);

        $dispositionMaster = GoalVrmDisposition::findByName($data['disposition']);
        $canCreateLeadFromDisposition = $dispositionMaster
            ? (bool) $dispositionMaster->creates_lead
            : in_array($data['disposition'], ['Interested', 'Callback'], true);

        $leadId = null;
        if ($request->boolean('create_lead') && $canCreateLeadFromDisposition) {
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

        GoalVrmCallLog::create([
            'user_id' => auth()->id(),
            'goal_loan_lead_id' => $leadId,
            'goal_org_unit_id' => $data['goal_org_unit_id'] ?? auth()->user()->goal_org_unit_id,
            'customer_name' => $data['customer_name'],
            'mobile' => $data['mobile'] ?? null,
            'disposition' => $data['disposition'],
            'called_at' => now(),
            'callback_at' => $data['callback_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $message = 'Call logged' . ($leadId ? ' and lead created.' : '.');

        if ($request->boolean('save_and_new')) {
            return redirect()
                ->route('goal.vrm.index', ['log_call' => 1])
                ->with('success', $message)
                ->with('reopen_log_call', true);
        }

        return redirect()->route('goal.vrm.index')->with('success', $message);
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
        return (int) (auth()->user()->crm_role_type ?? -1) === 9;
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
