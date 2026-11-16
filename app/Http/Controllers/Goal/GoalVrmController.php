<?php

namespace App\Http\Controllers\Goal;

use App\Exports\GoalVrmCallLogsExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Library\MpdfDocument;
use App\Models\GoalLoanLead;
use App\Models\GoalOrgUnit;
use App\Models\GoalVrmCallLog;
use App\Models\Product;
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

        $validated = $request->validate([
            'q' => 'nullable|string|max:150',
            'disposition' => 'nullable|in:Interested,Not Interested,No Answer,Busy,Callback,Invalid Number',
            'disposition_filter' => 'nullable|in:Interested,Not Interested,No Answer,Busy,Callback,Invalid Number',
            'lead_status' => 'nullable|in:all,yes,no',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'date_filter' => 'nullable|date',
            'per_page' => 'nullable|in:10,25,50,100',
            'log_call' => 'nullable',
        ]);

        $userId = auth()->id();
        $todayCount = GoalVrmCallLog::query()
            ->where('user_id', $userId)
            ->whereDate('called_at', today())
            ->count();

        $baseQuery = $this->filteredCallLogsQuery($request);
        $summaryQuery = clone $baseQuery;

        $totalCalls = (clone $summaryQuery)->count();
        $interestedCount = (clone $summaryQuery)->where('disposition', 'Interested')->count();
        $callbackCount = (clone $summaryQuery)->where('disposition', 'Callback')->count();
        $leadsCreatedCount = (clone $summaryQuery)->whereNotNull('goal_loan_lead_id')->count();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $logs = (clone $baseQuery)
            ->with(['lead.orgUnit'])
            ->latest('called_at')
            ->paginate($perPage)
            ->withQueryString();

        $branches = GoalOrgUnit::query()->ofType('branch')->active()->orderBy('name')->get();
        $products = $this->activeProducts();

        return view('goal.vrm.index', compact(
            'todayCount',
            'totalCalls',
            'interestedCount',
            'callbackCount',
            'leadsCreatedCount',
            'logs',
            'perPage',
            'branches',
            'products'
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
        abort_unless((int) $log->user_id === (int) auth()->id(), 403);

        $log->load(['lead.orgUnit', 'user']);
        $lead = $log->lead;

        $historyQuery = GoalVrmCallLog::query()
            ->where('user_id', auth()->id())
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
            'branch' => optional($lead?->orgUnit)->name,
            'logged_by' => optional($log->user)->name,
            'can_edit' => true,
            'can_delete' => true,
            'update_url' => route('goal.vrm.calls.update', $log),
            'delete_url' => route('goal.vrm.calls.destroy', $log),
            'callback_at_input' => optional($log->callback_at)->format('d-m-Y H:i'),
            'goal_org_unit_id' => $lead?->goal_org_unit_id,
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

        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'mobile' => 'nullable|string|max:20',
            'disposition' => ['required', Rule::in(GoalVrmCallLog::DISPOSITIONS)],
            'callback_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'lead_type' => 'nullable|in:immediate,future',
            'product' => 'nullable|string|max:100',
            'loan_amount' => 'nullable|numeric|min:0',
            'goal_org_unit_id' => 'nullable|exists:goal_org_units,id',
        ]);

        $log->update([
            'customer_name' => $data['customer_name'],
            'mobile' => $data['mobile'] ?? null,
            'disposition' => $data['disposition'],
            'callback_at' => $data['callback_at'] ?? null,
            'notes' => $data['notes'] ?? null,
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
                if (!empty($data['lead_type'])) {
                    $leadUpdates['lead_type'] = $data['lead_type'];
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
            foreach (GoalVrmCallLog::DISPOSITIONS as $allowedDisp) {
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

        $request->validate([
            'format' => 'nullable|in:excel,csv,pdf',
            'q' => 'nullable|string|max:150',
            'disposition' => 'nullable|in:Interested,Not Interested,No Answer,Busy,Callback,Invalid Number',
            'disposition_filter' => 'nullable|in:Interested,Not Interested,No Answer,Busy,Callback,Invalid Number',
            'lead_status' => 'nullable|in:all,yes,no',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'date_filter' => 'nullable|date',
        ]);

        $format = $request->input('format', 'excel');
        $headings = ['#', 'When', 'Customer', 'Mobile', 'Disposition', 'Lead', 'Notes', 'Branch', 'Callback At'];

        $logs = $this->filteredCallLogsQuery($request)
            ->with(['lead.orgUnit'])
            ->latest('called_at')
            ->limit(5000)
            ->get();

        $rows = [];
        foreach ($logs as $i => $log) {
            $rows[] = [
                $i + 1,
                optional($log->called_at)->format('d-m-Y H:i'),
                $log->customer_name,
                $log->mobile,
                $log->disposition,
                $log->goal_loan_lead_id ? 'Yes' : 'No',
                $log->notes,
                optional(optional($log->lead)->orgUnit)->name,
                optional($log->callback_at)->format('d-m-Y H:i'),
            ];
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

        return Excel::download(new GoalVrmCallLogsExport($rows), $fileName, $writerType);
    }

    protected function filteredCallLogsQuery(Request $request)
    {
        $query = GoalVrmCallLog::query()->where('user_id', auth()->id());

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
            'mobile' => 'nullable|string|max:20',
            'disposition' => 'required|in:Interested,Not Interested,No Answer,Busy,Callback,Invalid Number',
            'callback_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'create_lead' => 'nullable|boolean',
            'lead_type' => 'nullable|in:immediate,future',
            'product' => [
                'nullable',
                'string',
                'max:100',
                Rule::in($this->activeProducts()->pluck('product_name')->all()),
            ],
            'loan_amount' => 'nullable|numeric|min:0',
            'goal_org_unit_id' => 'nullable|exists:goal_org_units,id',
            'save_and_new' => 'nullable|boolean',
        ]);

        $leadId = null;
        if ($request->boolean('create_lead') && in_array($data['disposition'], ['Interested', 'Callback'], true)) {
            $this->requireGoalPermission('create_lead_guard');
            $leadType = $data['lead_type'] ?? ($data['disposition'] === 'Callback' ? 'future' : 'immediate');
            $lead = GoalLoanLead::create([
                'ref_no' => 'LD-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
                'customer_name' => $data['customer_name'],
                'mobile' => $data['mobile'] ?? null,
                'source' => 'vrm',
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
            $leadId = $lead->id;
        }

        GoalVrmCallLog::create([
            'user_id' => auth()->id(),
            'goal_loan_lead_id' => $leadId,
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

    public function storeQuickProduct(Request $request)
    {
        $this->requireGoalPermission('vrm_calling_guard');

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
}
