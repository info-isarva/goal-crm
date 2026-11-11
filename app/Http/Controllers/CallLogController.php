<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CallLog;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CallLogsExport;
use App\Exports\CallLogsReportsExport;
use App\Models\Call;
use App\Models\User;
use Carbon\Carbon;

class CallLogController extends Controller
{
    public function __construct()
    {
        // Prevent creating/importing/editing/deleting call logs when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'store', 'import', 'edit', 'update', 'destroy'
        ]);
    }
    // Show user's own call logs
    public function index()
    {
        if(!Auth::user()->hasCrmPermission('manage_crm_call_logs_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        // Build base query and apply role-based filter
        $query = CallLog::orderByDesc('created_at');
        if (!in_array($user->crm_role_type, [0, 1])) {
            $query->where('created_by', $user->id);
        }

        // Apply quick search filter (single query field 'q') that matches name OR mobile number
        $search = trim((string) request()->get('q', ''));
        if ($search !== '') {
            $query->where(function($sub) use ($search) {
                $sub->where('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile_number', 'like', '%' . $search . '%');
            });
        }

        // Apply financial year filter at query level if selected
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
            if ($fy) {
                $from = \Carbon\Carbon::parse($fy->from_date)->startOfDay();
                $to = \Carbon\Carbon::parse($fy->to_date)->endOfDay();
                $query->whereBetween('created_at', [$from, $to]);
            }
        }

        $calls = $query->paginate(15)->appends(request()->except('page'));
        return view('calls.index', compact('calls'));
    }

    /**
     * Export call logs to Excel (current filters applied)
     */
    public function exportExcel(Request $request)
    {
        if(!Auth::user()->hasCrmPermission('manage_crm_call_logs_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $query = CallLog::orderByDesc('created_at');
        if (!in_array($user->crm_role_type, [0, 1])) {
            $query->where('created_by', $user->id);
        }

        // Apply q search (name or mobile)
        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function($sub) use ($search) {
                $sub->where('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile_number', 'like', '%' . $search . '%');
            });
        }

        // Apply financial year filter
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $fy = \App\Models\FinancialYear::find($selectedFyId);
            if ($fy) {
                $from = \Carbon\Carbon::parse($fy->from_date)->startOfDay();
                $to = \Carbon\Carbon::parse($fy->to_date)->endOfDay();
                $query->whereBetween('created_at', [$from, $to]);
            }
        }

        $calls = $query->with('creator')->get();

        // Map to rows for export
        $rows = $calls->map(function($call) {
            return [
                $call->name ?? '',
                $call->mobile_number ?? '',
                $call->company_name ?? '',
                $call->email ?? '',
                $call->call_status ?? '',
                $call->lead_status ?? '',
                $call->requirement ?? '',
                $call->source ?? '',
                optional($call->creator)->name ?? ($call->created_by ?? ''),
                $call->created_at ? $call->created_at->format('Y-m-d H:i:s') : '',
            ];
        })->toArray();

        $fileName = 'call-logs-' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new CallLogsExport($rows), $fileName);
    }

    // Store a new call log
    public function store(Request $request)
    {
        if(!Auth::user()->hasCrmPermission('create_crm_call_logs_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent creating call logs when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Creating call logs is disabled for historical years.');
            }
        }
        // Validate the array of logs so errors are keyed as logs.0.name, logs.1.mobile_number, etc.
        $rules = [
            'logs' => 'required|array|min:1',
            'logs.*.name' => 'required|string|max:255',
            'logs.*.company_name' => 'nullable|string|max:255',
            'logs.*.mobile_number' => ['required', 'string', 'max:13', 'regex:/^\+?[0-9\-\s]{7,20}$/'],
            'logs.*.email' => 'nullable|email|max:255',
            'logs.*.requirement' => 'nullable|string',
            'logs.*.call_status' => 'required|string|max:50',
            'logs.*.lead_status' => 'nullable|string|max:50',
        ];

        $messages = [
            'logs.required' => 'Please add at least one call log.',
            'logs.*.name.required' => 'Please enter the contact name.',
            'logs.*.name.max' => 'Name may not be greater than :max characters.',
            'logs.*.mobile_number.required' => 'Please enter a mobile number.',
            'logs.*.mobile_number.max' => 'Mobile number may not be greater than :max characters.',
            'logs.*.mobile_number.regex' => 'Please enter a valid mobile number (digits, spaces, + and - allowed).',
            'logs.*.email.email' => 'Please enter a valid email address.',
            'logs.*.email.max' => 'Email may not be greater than :max characters.',
            'logs.*.call_status.required' => 'Please select call status.',
        ];
        $attributes = [
            'logs.*.name' => 'Name',
            'logs.*.company_name' => 'Company Name',
            'logs.*.mobile_number' => 'Mobile Number',
            'logs.*.email' => 'Email',
            'logs.*.requirement' => 'Requirement',
            'logs.*.call_status' => 'Call Status',
            'logs.*.lead_status' => 'Lead Status',
        ];

        $validatedAll = $request->validate($rules, $messages, $attributes);
        $logs = $validatedAll['logs'];
        $count = 0;
        foreach ($logs as $log) {
            $log['created_by'] = Auth::id();
            CallLog::create($log);
            $count++;
        }
        return redirect()->route('calllogs.index')->with('success', $count.' call log(s) added successfully');
    }

     // Bulk import call logs from Excel
    public function import(Request $request)
    {
        if(!Auth::user()->hasCrmPermission('import_crm_call_logs_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent importing call logs when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Importing call logs is disabled for historical years.');
            }
        }
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $uploaded = $request->file('import_file');
        $rows = [];
        // Use Laravel Excel if available. Pass the UploadedFile instance so the library
        // can detect reader type (avoids "No ReaderType or WriterType could be detected" errors).
        if (class_exists('Maatwebsite\\Excel\\Facades\\Excel')) {
            try {
                // Attempt to give the reader a hint based on extension. This helps when
                // automatic detection fails and avoids the "No ReaderType or WriterType" error.
                $readerType = null;
                $extension = strtolower($uploaded->getClientOriginalExtension() ?? '');
                if ($extension) {
                    if ($extension === 'csv') {
                        $readerType = \Maatwebsite\Excel\Excel::CSV;
                    } elseif (in_array($extension, ['xls', 'xlsx'])) {
                        // Let the library pick between XLS/XLSX when possible, but hint XLSX for xlsx
                        $readerType = $extension === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::XLS;
                    }
                }

                $arrays = \Maatwebsite\Excel\Facades\Excel::toArray(null, $uploaded, null, $readerType);
                // toArray returns an array of sheets; pick the first sheet if present
                $rows = isset($arrays[0]) ? $arrays[0] : [];
            } catch (\PhpOffice\PhpSpreadsheet\Reader\InvalidFileException $e) {
                // If Excel reader fails for some reason, fallback to reading as CSV if possible
                $rows = [];
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Call log import failed', ['error' => $e->getMessage()]);
                return redirect()->back()->with('error', 'Import failed: the file could not be read. Please upload a valid xlsx/xls/csv file.');
            }
        }

        // If rows still empty and file is a CSV (or Excel fallback failed), parse as CSV
        if (empty($rows)) {
            // Use native file reading for CSV; ensure we read the uploaded file stream
            try {
                $stream = fopen($uploaded->getRealPath(), 'r');
                if ($stream !== false) {
                    $rows = [];
                    while (($data = fgetcsv($stream)) !== false) {
                        $rows[] = $data;
                    }
                    fclose($stream);
                }
            } catch (\Exception $e) {
                $rows = [];
            }
        }

        if (empty($rows) || !isset($rows[0])) {
            return redirect()->back()->with('error', 'Imported file appears empty or invalid. Please upload a valid xlsx/xls/csv file.');
        }

        $header = array_map('strtolower', array_map('trim', $rows[0]));
        unset($rows[0]);
        
        $records = [];
        foreach ($rows as $row) {
            $data = array_combine($header, $row);
            if (!$data['mobile number']) continue; // skip incomplete

            // Convert Excel serial date to Y-m-d if needed
            $nextFollowUp = $data['next follow up dates'] ?? null;
            if ($nextFollowUp && is_numeric($nextFollowUp)) {
                try {
                    if (class_exists('PhpOffice\\PhpSpreadsheet\\Shared\\Date')) {
                        $nextFollowUp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($nextFollowUp)->format('Y-m-d');
                    } else {
                        $nextFollowUp = null;
                    }
                } catch (\Exception $e) {
                    $nextFollowUp = null;
                }
            }

            $createAt = $data['created at'] ?? null;
            if ($createAt && is_numeric($createAt)) {
                try {
                    if (class_exists('PhpOffice\\PhpSpreadsheet\\Shared\\Date')) {
                        $createAt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($createAt)->format('Y-m-d H:i:s');
                    } else {
                        $createAt = now()->toDateTimeString();
                    }
                } catch (\Exception $e) {
                    $createAt = now()->toDateTimeString();
                }
            } else {
                $createAt = now()->toDateTimeString();
            }

            // Normalize lead_status: map common variants; store NULL if empty
            $rawLeadStatus = trim($data['lead status'] ?? '');
            $normalizedLead = null;
            if ($rawLeadStatus !== '') {
                $lc = strtolower($rawLeadStatus);
                if (in_array($lc, ['try', 'tried'])) {
                    $normalizedLead = 'Try';
                } else {
                    $allowed = [
                        'interested' => 'Interested',
                        'not interested' => 'Not Interested',
                        'call later' => 'Call Later',
                        'follow up' => 'Follow Up',
                        'closed' => 'Closed',
                        'share the details' => 'Share the Details',
                        'try' => 'Try',
                    ];
                    if (isset($allowed[$lc])) {
                        $normalizedLead = $allowed[$lc];
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Import: unknown lead_status value, defaulting to Try', ['raw' => $rawLeadStatus]);
                        $normalizedLead = 'Try';
                    }
                }
            }

            $records[] = [
                'name' => $data['name'] ?? '',
                'company_name' => $data['company name'] ?? '',
                'address' => $data['address'] ?? '',
                'mobile_number' => $data['mobile number'] ?? '',
                'email' => $data['email'] ?? '',
                'requirement' => $data['requirement'] ?? '',
                'estimated_budget' => $data['estimated budget'] ?? '',
                'call_status' => $data['call status'] ?? '',
                'lead_status' => $normalizedLead,
                'next_follow_up_date' => $nextFollowUp,
                'next_action' => $data['next action'] ?? '',
                'remarks' => $data['remarks'] ?? '',
                'source' => $data['source'] ?? '',
                'created_by' => auth()->id(),
                'created_at' => $createAt,
                'updated_at' => $createAt,
            ];
        }

        if (count($records) > 100) {
            // Dispatch to queue for bulk insert
            \App\Jobs\BulkImportCallLogs::dispatch($records);
            $msg = 'Bulk call logs are being imported in the background.';
        } else {
            foreach ($records as $rec) {
                \App\Models\CallLog::create($rec);
            }
            $msg = 'Bulk call logs imported successfully';
        }
        return redirect()->route('calllogs.index')->with('success', $msg);
    }

     // Show edit form for a call log
    public function edit($id)
    {
        if(!Auth::user()->hasCrmPermission('edit_crm_call_logs_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent editing call logs when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Editing call logs is disabled for historical years.');
            }
        }
        $call = CallLog::findOrFail($id);
        $this->authorizeCallLog($call);
        return view('calls.edit', compact('call'));
    }

    /**
     * Call logs are private to their creator (Super Admin / Admin see all), matching the list filter.
     */
    private function authorizeCallLog(CallLog $call): void
    {
        $user = Auth::user();
        if (in_array((int) $user->crm_role_type, [0, 1], true)) {
            return;
        }
        abort_unless((int) $call->created_by === (int) $user->id, 403, 'Unauthorized action.');
    }

    // Show details for a single call log
    public function show($id)
    {
        if(!Auth::user()->hasCrmPermission('manage_crm_call_logs_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $call = CallLog::findOrFail($id);
        $this->authorizeCallLog($call);
        return view('calls.show', compact('call'));
    }

    // Update a call log
    public function update(Request $request, $id)
    {
        if(!Auth::user()->hasCrmPermission('edit_crm_call_logs_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent updating call logs when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Editing call logs is disabled for historical years.');
            }
        }
        $call = CallLog::findOrFail($id);
        $this->authorizeCallLog($call);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'mobile_number' => ['required', 'string', 'max:13', 'regex:/^\+?[0-9\-\s]{7,20}$/'],
            'email' => 'nullable|email|max:255',
            'requirement' => 'nullable|string',
            'estimated_budget' => 'nullable|string|max:255',
            'call_status' => 'required|string|max:50',
            'lead_status' => 'nullable|string|max:50',
            'next_follow_up_date' => 'nullable|date',
            'next_action' => 'nullable|string',
            'remarks' => 'nullable|string',
            'source' => 'nullable|string|max:255',
        ]);
        $validated['updated_by'] = Auth::id();
        $call->update($validated);
        return redirect()->route('calllogs.index')->with('success', 'Call log updated successfully');
    }

    // Delete a call log
    public function destroy($id)
    {
        if(!Auth::user()->hasCrmPermission('delete_crm_call_logs_guard')) {
            abort(403, 'Unauthorized action.');
        }
        // Prevent deleting call logs when viewing an old/closed financial year
        $selectedFyId = session('selected_financial_year', null);
        if ($selectedFyId) {
            $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
            if ($activeFy && $selectedFyId != $activeFy->id) {
                return redirect()->back()->with('error', 'Selected financial year is closed. Deleting call logs is disabled for historical years.');
            }
        }
        $call = CallLog::findOrFail($id);
        $this->authorizeCallLog($call);
        $call->deleted_by = Auth::id();
        $call->save();
        $call->delete();
        return redirect()->route('calllogs.index')->with('success', 'Call log deleted successfully');
    }

    /**
     * Apply date range from resolveReportPeriod to a Call query.
     */
    private function applyCallReportDateFilter($query, Request $request): array
    {
        [$start, $end, $period] = ReportController::resolveReportPeriod($request, 'today');

        if ($start && $end) {
            $query->whereBetween('calls.created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay(),
            ]);
        }

        return [$start, $end, $period];
    }

    /**
     * Users available in the Calls report filter.
     */
    private function callReportFilterUsers(User $user)
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
     * Apply created_by scope (and optional user filter) for calls report queries.
     */
    private function applyCallReportUserScope($query, User $user, $filterUserId = null): void
    {
        $roleType = (int) $user->crm_role_type;
        $filterUserId = $filterUserId ? (int) $filterUserId : null;

        if (in_array($roleType, [0, 1], true)) {
            if ($filterUserId) {
                $query->where('created_by', $filterUserId);
            }

            return;
        }

        if ($roleType === 2) {
            $employeeIds = User::where('assign_manager', $user->id)
                ->where('status', 1)
                ->pluck('id');
            $allowedIds = $employeeIds->push($user->id)->unique();

            if ($filterUserId && $allowedIds->contains($filterUserId)) {
                $query->where('created_by', $filterUserId);
            } else {
                $query->where(function ($q) use ($user, $employeeIds) {
                    $q->where('created_by', $user->id);
                    if ($employeeIds->isNotEmpty()) {
                        $q->orWhereIn('created_by', $employeeIds);
                    }
                });
            }

            return;
        }

        $query->where('created_by', $user->id);
    }

    /**
     * Build aggregated calls report query/results for the current filters.
     */
    private function buildCallReport(Request $request): array
    {
        $user = Auth::user();
        $filterUserId = $request->input('user_id');
        $filterUsers = $this->callReportFilterUsers($user);

        if ($filterUserId && !$filterUsers->pluck('id')->contains((int) $filterUserId)) {
            $filterUserId = null;
        }

        $query = Call::query();
        [$start, $end, $period] = $this->applyCallReportDateFilter($query, $request);
        $this->applyCallReportUserScope($query, $user, $filterUserId);

        $report = $query->selectRaw('
            created_by,
            COUNT(*) as total_calls,
            SUM(CASE WHEN related_type = "call_log" THEN 1 ELSE 0 END) as call_logs,
            SUM(CASE WHEN related_type = "contact" THEN 1 ELSE 0 END) as contacts,
            SUM(CASE WHEN related_type = "lead" THEN 1 ELSE 0 END) as followup_leads,
            SUM(CASE WHEN related_type = "deal" THEN 1 ELSE 0 END) as followup_deals
        ')
            ->groupBy('created_by')
            ->with('creator')
            ->get();

        return compact('report', 'start', 'end', 'period', 'filterUsers', 'filterUserId');
    }

    /**
     * Generate Call Log Reports
     */
    public function report(Request $request)
    {
        if (!Auth::user()->hasCrmPermission('view_calls_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $this->buildCallReport($request);

        return view('reports.call_logs', $data);
    }

    public function exportCalls(Request $request) {
        if (!Auth::user()->hasCrmPermission('view_calls_reports_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $this->buildCallReport($request);
        $report = $data['report'];

        $rows = $report->map(function($item) {
            return [
                optional($item->creator)->name ?? ($item->created_by ?? 'User ID: ' . $item->created_by),
                $item->total_calls,
                $item->call_logs,
                $item->contacts,
                $item->followup_leads,
                $item->followup_deals,
            ];
        })->toArray();

        $fileName = 'calls-report-' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new CallLogsReportsExport($rows), $fileName);

    }
}