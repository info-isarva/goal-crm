<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\CallLog;
use App\Models\DailyReport;
use App\Models\DailyReportOutreach;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Note;
use App\Models\OutreachType;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    protected function canManage(): bool
    {
        return auth()->user()->hasCrmPermission('manage_crm_daily_report_guard')
            || auth()->user()->hasCrmPermission('create_crm_daily_report_guard')
            || auth()->user()->hasCrmPermission('view_crm_daily_report_guard')
            || (int) (auth()->user()->crm_role_type ?? -1) === 0;
    }

    public function index(Request $request)
    {
        if (!$this->canManage()) {
            abort(403, 'Unauthorized action.');
        }

        $perPage = (int) $request->query('per_page', 25);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $filters = $this->resolveListFilters($request);

        $reports = $filters['query']
            ->paginate($perPage)
            ->appends($request->query());

        $exportParams = array_filter([
            'period' => $filters['period'],
            'start_date' => $filters['period'] === 'custom' ? $filters['start'] : null,
            'end_date' => $filters['period'] === 'custom' ? $filters['end'] : null,
            'user_id' => $filters['selectedUserId'],
        ]);

        return view('daily_reports.index', [
            'reports' => $reports,
            'users' => $filters['users'],
            'selectedUserId' => $filters['selectedUserId'],
            'isAdmin' => $filters['isAdmin'],
            'perPage' => $perPage,
            'period' => $filters['period'],
            'start' => $filters['start'],
            'end' => $filters['end'],
            'exportParams' => $exportParams,
        ]);
    }

    public function exportExcel(Request $request)
    {
        if (!$this->canManage()) {
            abort(403, 'Unauthorized action.');
        }

        $data = $this->buildExportData($request);
        $fileName = 'Daily_Reports_' . $data['meta']['file_suffix'] . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DailyReportsExport($data['rows'], $data['headings'], $data['meta'], $data['outreachColumnCount']),
            $fileName
        );
    }

    public function exportPdf(Request $request)
    {
        if (!$this->canManage()) {
            abort(403, 'Unauthorized action.');
        }

        $data = $this->buildExportData($request);
        $fileName = 'Daily_Reports_' . $data['meta']['file_suffix'] . '.pdf';

        return \App\Library\MpdfDocument::fromView('daily_reports.pdf', $data, $fileName, true, 'A4-L');
    }

    /**
     * Shared filters for the list page and exports: role scope, user filter and date period.
     */
    protected function resolveListFilters(Request $request): array
    {
        $user = auth()->user();
        $roleType = (int) ($user->crm_role_type ?? -1);
        $isAdmin = in_array($roleType, [0, 1], true);

        $users = collect();
        $selectedUserId = null;
        if ($isAdmin) {
            $users = \App\Models\User::where('status', 1)->orderBy('name')->get(['id', 'name']);
            if ($request->filled('user_id') && $users->contains('id', (int) $request->query('user_id'))) {
                $selectedUserId = (int) $request->query('user_id');
            }
        }

        [$start, $end, $period] = ReportController::resolveReportPeriod($request, 'this_month');
        if ($period === 'year') {
            $period = 'this_month';
            $start = Carbon::now()->startOfMonth()->toDateString();
            $end = Carbon::now()->endOfMonth()->toDateString();
        }
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end)->toDateString();

        $query = DailyReport::query()
            ->with(['user', 'outreaches.outreachType'])
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', $user->id))
            ->when($selectedUserId, fn ($q) => $q->where('user_id', $selectedUserId))
            ->whereDate('report_date', '>=', $start)
            ->whereDate('report_date', '<=', $end)
            ->orderByDesc('report_date')
            ->orderByDesc('id');

        return compact('query', 'users', 'selectedUserId', 'isAdmin', 'start', 'end', 'period');
    }

    protected function buildExportData(Request $request): array
    {
        $filters = $this->resolveListFilters($request);
        $reports = $filters['query']->get();

        // One column per outreach type used in these reports, in the admin-defined order
        $usedTypeIds = $reports->flatMap(fn ($r) => $r->outreaches->pluck('outreach_type_id'))->unique()->all();
        $outreachTypes = empty($usedTypeIds)
            ? collect()
            : OutreachType::query()->whereIn('id', $usedTypeIds)->ordered()->get(['id', 'name']);

        $headings = array_merge(
            ['S.No', 'Date', 'User'],
            $outreachTypes->pluck('name')->all(),
            ['Total Outreach', 'Other Activity', 'Status']
        );

        $rows = [];
        $typeTotals = array_fill_keys($outreachTypes->pluck('id')->all(), 0);
        $grandTotal = 0;
        foreach ($reports as $i => $report) {
            $counts = $report->outreaches->groupBy('outreach_type_id')->map(fn ($g) => (int) $g->sum('count'));
            $line = [
                $i + 1,
                optional($report->report_date)->format('d M Y'),
                optional($report->user)->name ?? '-',
            ];
            $rowTotal = 0;
            foreach ($outreachTypes as $type) {
                $cnt = (int) ($counts[$type->id] ?? 0);
                $line[] = $cnt;
                $rowTotal += $cnt;
                $typeTotals[$type->id] += $cnt;
            }
            $grandTotal += $rowTotal;
            $line[] = $rowTotal;
            $activity = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $report->other_activity), ENT_QUOTES, 'UTF-8')) ?? '');
            $line[] = $activity !== '' ? $activity : '-';
            $line[] = $report->status === 'draft' ? 'Draft' : 'Submitted';
            $rows[] = $line;
        }

        $totalsRow = null;
        if (!empty($rows)) {
            $totalsRow = array_merge(['', '', 'Total'], array_values($typeTotals), [$grandTotal, '', '']);
        }

        $userLabel = 'All Users';
        if ($filters['selectedUserId']) {
            $userLabel = optional($filters['users']->firstWhere('id', $filters['selectedUserId']))->name ?? $userLabel;
        } elseif (!$filters['isAdmin']) {
            $userLabel = auth()->user()->name;
        }

        $periodLabel = ReportController::periodLabels()[$filters['period']] ?? 'Custom';
        $rangeText = Carbon::parse($filters['start'])->format('d M Y') . ' - ' . Carbon::parse($filters['end'])->format('d M Y');

        $meta = [
            'title' => 'Daily Reports',
            'company' => optional(\App\Models\Company::first())->name ?? config('app.name', 'CRM'),
            'period_label' => $periodLabel,
            'range_text' => $rangeText,
            'user_label' => $userLabel,
            'record_count' => count($rows),
            'generated_on' => now()->format('d M Y h:i A'),
            'generated_by' => auth()->user()->name ?? '',
            'file_suffix' => $filters['start'] . '_to_' . $filters['end'],
        ];

        return [
            'rows' => $rows,
            'totalsRow' => $totalsRow,
            'headings' => $headings,
            'meta' => $meta,
            'outreachColumnCount' => $outreachTypes->count(),
        ];
    }

    public function create()
    {
        if (
            !auth()->user()->hasCrmPermission('create_crm_daily_report_guard')
            && !auth()->user()->hasCrmPermission('manage_crm_daily_report_guard')
            && (int) (auth()->user()->crm_role_type ?? -1) !== 0
        ) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $today = Carbon::today();
        $existing = DailyReport::with('outreaches')
            ->where('user_id', $user->id)
            ->whereDate('report_date', $today)
            ->first();

        if ($existing) {
            if ($existing->isSubmitted()) {
                return redirect()->route('daily_reports.show', $existing->id)
                    ->with('error', 'Your daily report for today has already been submitted and cannot be edited.');
            }
            return redirect()->route('daily_reports.edit', $existing->id);
        }

        $data = $this->buildFormData($user->id, $today);

        return view('daily_reports.create', $data);
    }

    public function store(Request $request)
    {
        if (
            !auth()->user()->hasCrmPermission('create_crm_daily_report_guard')
            && !auth()->user()->hasCrmPermission('manage_crm_daily_report_guard')
            && (int) (auth()->user()->crm_role_type ?? -1) !== 0
        ) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $this->validateReport($request);
        $user = auth()->user();
        $reportDate = Carbon::parse($validated['report_date'])->startOfDay();
        $today = Carbon::today();

        if (!$reportDate->equalTo($today)) {
            return redirect()
                ->route('daily_reports.create')
                ->withInput()
                ->with('error', 'Daily reports can only be submitted for today.');
        }

        $actionType = $request->input('action_type', 'submit');
        $status = $actionType === 'draft' ? 'draft' : 'submitted';

        $existing = DailyReport::where('user_id', $user->id)
            ->whereDate('report_date', $reportDate->toDateString())
            ->first();

        if ($existing) {
            if ($existing->isSubmitted()) {
                return redirect()
                    ->route('daily_reports.show', $existing->id)
                    ->with('error', 'Your daily report for today has already been submitted and cannot be edited.');
            }
            
            DB::transaction(function () use ($validated, $existing, $status) {
                $existing->update([
                    'other_activity' => $validated['other_activity'] ?? null,
                    'status' => $status,
                    'updated_by' => auth()->id(),
                ]);

                $existing->outreaches()->delete();
                $this->syncOutreaches($existing, $validated['outreaches'] ?? []);
            });

            $msg = $status === 'draft' ? 'Daily report saved as draft.' : 'Daily report submitted successfully!';

            return redirect()->route('daily_reports.index')->with('success', $msg);
        }

        DB::transaction(function () use ($validated, $user, $reportDate, $status) {
            $report = DailyReport::create([
                'user_id' => $user->id,
                'report_date' => $reportDate->toDateString(),
                'other_activity' => $validated['other_activity'] ?? null,
                'status' => $status,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->syncOutreaches($report, $validated['outreaches'] ?? []);
        });

        $msg = $status === 'draft' ? 'Daily report saved as draft.' : 'Daily report submitted successfully!';

        return redirect()->route('daily_reports.index')->with('success', $msg);
    }

    public function show($id)
    {
        if (!$this->canManage()) {
            abort(403, 'Unauthorized action.');
        }

        $report = DailyReport::with(['user', 'outreaches.outreachType'])->findOrFail($id);
        $this->authorizeReportAccess($report);

        $data = $this->buildFormData($report->user_id, $report->report_date);
        $data['report'] = $report;
        $data['readOnly'] = true;
        $data['canEdit'] = $this->isEditable($report);

        return view('daily_reports.show', $data);
    }

    public function edit($id)
    {
        if (
            !auth()->user()->hasCrmPermission('edit_crm_daily_report_guard')
            && !auth()->user()->hasCrmPermission('manage_crm_daily_report_guard')
            && (int) (auth()->user()->crm_role_type ?? -1) !== 0
        ) {
            abort(403, 'Unauthorized action.');
        }

        $report = DailyReport::with('outreaches')->findOrFail($id);
        $this->authorizeReportAccess($report);

        if (!$this->isEditable($report)) {
            return redirect()
                ->route('daily_reports.show', $report->id)
                ->with('error', 'Submitted daily reports cannot be edited.');
        }

        $data = $this->buildFormData($report->user_id, $report->report_date);
        $data['report'] = $report;

        return view('daily_reports.edit', $data);
    }

    public function update(Request $request, $id)
    {
        if (
            !auth()->user()->hasCrmPermission('edit_crm_daily_report_guard')
            && !auth()->user()->hasCrmPermission('manage_crm_daily_report_guard')
            && (int) (auth()->user()->crm_role_type ?? -1) !== 0
        ) {
            abort(403, 'Unauthorized action.');
        }

        $report = DailyReport::findOrFail($id);
        $this->authorizeReportAccess($report);

        if (!$this->isEditable($report)) {
            return redirect()
                ->route('daily_reports.show', $report->id)
                ->with('error', 'Submitted daily reports cannot be edited.');
        }

        $actionType = $request->input('action_type', 'submit');
        $status = $actionType === 'draft' ? 'draft' : 'submitted';

        $validated = $this->validateReport($request, false);

        DB::transaction(function () use ($validated, $report, $status) {
            $report->update([
                'other_activity' => $validated['other_activity'] ?? null,
                'status' => $status,
                'updated_by' => auth()->id(),
            ]);

            $report->outreaches()->delete();
            $this->syncOutreaches($report, $validated['outreaches'] ?? []);
        });

        $msg = $status === 'draft' ? 'Daily report saved as draft.' : 'Daily report submitted successfully!';

        return redirect()->route('daily_reports.index')->with('success', $msg);
    }

    protected function validateReport(Request $request, bool $requireDate = true): array
    {
        $rules = [
            'other_activity' => 'nullable|string|max:20000',
            'outreaches' => 'nullable|array|min:1',
            'outreaches.*.outreach_type_id' => 'required|exists:outreach_types,id',
            'outreaches.*.count' => 'required|integer|min:0|max:99999',
        ];

        if ($requireDate) {
            $rules['report_date'] = 'required|date';
        }

        return $request->validate($rules);
    }

    protected function syncOutreaches(DailyReport $report, array $outreaches): void
    {
        foreach ($outreaches as $row) {
            if (empty($row['outreach_type_id'])) {
                continue;
            }
            DailyReportOutreach::create([
                'daily_report_id' => $report->id,
                'outreach_type_id' => (int) $row['outreach_type_id'],
                'count' => (int) ($row['count'] ?? 0),
            ]);
        }
    }

    protected function authorizeReportAccess(DailyReport $report): void
    {
        $user = auth()->user();
        $roleType = (int) ($user->crm_role_type ?? -1);
        if (in_array($roleType, [0, 1], true)) {
            return;
        }
        if ((int) $report->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Edit allowed only for reports whose report_date is today AND status is draft.
     */
    protected function isEditable(DailyReport $report): bool
    {
        if (!$report->report_date) {
            return false;
        }

        $isToday = Carbon::parse($report->report_date)->isSameDay(Carbon::today());
        $isDraft = ($report->status ?? 'submitted') === 'draft';

        return $isToday && $isDraft;
    }

    protected function buildFormData(int $userId, $date): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        $outreachTypes = OutreachType::active()->ordered()->get();

        $todayLeads = Lead::query()
            ->with(['organization', 'person'])
            ->where('user_owner_id', $userId)
            ->whereBetween('created_at', [$day, $dayEnd])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $todayDeals = Deal::query()
            ->with(['organization', 'person'])
            ->where('user_owner_id', $userId)
            ->whereBetween('created_at', [$day, $dayEnd])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $activities = collect();

        Note::query()
            ->where('created_by', $userId)
            ->whereBetween('created_at', [$day, $dayEnd])
            ->where(function ($q) {
                $q->whereRaw('LOWER(related_type) LIKE ?', ['%lead%'])
                    ->orWhereRaw('LOWER(related_type) LIKE ?', ['%deal%']);
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->each(function ($note) use ($activities) {
                $related = $this->relatedLabel($note->related_type, $note->related_id);
                if ($related !== '') {
                    $activities->push([
                        'type' => 'Note',
                        'title' => \Illuminate\Support\Str::limit(strip_tags((string) $note->content), 80),
                        'related' => $related,
                        'at' => $note->created_at,
                    ]);
                }
            });

        Task::query()
            ->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)->orWhere('user_owner_id', $userId);
            })
            ->whereBetween('created_at', [$day, $dayEnd])
            ->where(function ($q) {
                $q->whereRaw('LOWER(related_type) LIKE ?', ['%lead%'])
                    ->orWhereRaw('LOWER(related_type) LIKE ?', ['%deal%']);
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->each(function ($task) use ($activities) {
                $related = $this->relatedLabel($task->related_type, $task->related_id);
                if ($related !== '') {
                    $activities->push([
                        'type' => 'Task',
                        'title' => $task->name ?? 'Task',
                        'related' => $related,
                        'at' => $task->created_at,
                    ]);
                }
            });

        Meeting::query()
            ->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)->orWhere('user_owner_id', $userId);
            })
            ->whereBetween('created_at', [$day, $dayEnd])
            ->where(function ($q) {
                $q->whereRaw('LOWER(related_type) LIKE ?', ['%lead%'])
                    ->orWhereRaw('LOWER(related_type) LIKE ?', ['%deal%']);
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->each(function ($meeting) use ($activities) {
                $related = $this->relatedLabel($meeting->related_type ?? null, $meeting->related_id ?? null);
                if ($related !== '') {
                    $activities->push([
                        'type' => 'Meeting',
                        'title' => $meeting->name ?? 'Meeting',
                        'related' => $related,
                        'at' => $meeting->created_at,
                    ]);
                }
            });

        if (class_exists(Call::class) && \Illuminate\Support\Facades\Schema::hasTable('calls')) {
            Call::query()
                ->where(function ($q) use ($userId) {
                    $q->where('created_by', $userId)->orWhere('user_owner_id', $userId);
                })
                ->whereBetween('created_at', [$day, $dayEnd])
                ->where(function ($q) {
                    $q->whereRaw('LOWER(related_type) LIKE ?', ['%lead%'])
                        ->orWhereRaw('LOWER(related_type) LIKE ?', ['%deal%']);
                })
                ->orderByDesc('id')
                ->limit(30)
                ->get()
                ->each(function ($call) use ($activities) {
                    $related = $this->relatedLabel($call->related_type ?? null, $call->related_id ?? null);
                    if ($related !== '') {
                        $activities->push([
                            'type' => 'Call',
                            'title' => $call->name ?? $call->subject ?? 'Call',
                            'related' => $related,
                            'at' => $call->created_at,
                        ]);
                    }
                });
        }

        if (class_exists(\App\Models\QuotationEmailSendLog::class) && \Illuminate\Support\Facades\Schema::hasTable('quotation_email_send_logs')) {
            \App\Models\QuotationEmailSendLog::query()
                ->with(['quotation.deal'])
                ->where('sent_by', $userId)
                ->whereBetween('created_at', [$day, $dayEnd])
                ->orderByDesc('id')
                ->limit(30)
                ->get()
                ->each(function ($emailLog) use ($activities) {
                    $deal = optional($emailLog->quotation)->deal;
                    if ($deal) {
                        $activities->push([
                            'type' => 'Email',
                            'title' => $emailLog->subject ?? 'Quotation Email Sent',
                            'related' => 'Deal: ' . ($deal->title ?? ('#' . $deal->id)),
                            'at' => $emailLog->created_at,
                        ]);
                    }
                });
        }

        $todayActivities = $activities->sortByDesc('at')->values();

        return compact('outreachTypes', 'todayLeads', 'todayDeals', 'todayActivities', 'day');
    }

    protected function relatedLabel(?string $type, $id): string
    {
        if (!$type || !$id) {
            return '';
        }

        $short = class_basename($type);
        try {
            if (stripos($type, 'Lead') !== false) {
                $lead = Lead::find($id);
                return $lead ? ('Lead: ' . ($lead->title ?? ('#' . $lead->id))) : ($short . ' #' . $id);
            }
            if (stripos($type, 'Deal') !== false) {
                $deal = Deal::find($id);
                return $deal ? ('Deal: ' . ($deal->title ?? ('#' . $deal->id))) : ($short . ' #' . $id);
            }
        } catch (\Throwable $e) {
            return $short . ' #' . $id;
        }

        return $short . ' #' . $id;
    }
}
