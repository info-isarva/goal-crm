<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Models\GoalScheduledReport;
use App\Services\Goal\GoalDashboardService;
use App\Services\Goal\GoalScheduledReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GoalScheduledReportController extends Controller
{
    public function __construct(
        protected GoalScheduledReportService $service
    ) {}

    public function index()
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);

        $schedules = GoalScheduledReport::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->withCount('runs')
            ->latest()
            ->paginate(20);

        return view('goal.scheduled-reports.index', compact('schedules'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);

        $data = $this->validated($request);
        $data['filters'] = $this->snapshotFilters($request);
        $this->service->createForUser($user, $data);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Schedule created successfully.',
                'redirect' => route('goal.scheduled-reports.index'),
            ]);
        }

        return redirect()
            ->route('goal.scheduled-reports.index')
            ->with('success', 'Schedule created successfully.');
    }

    public function update(Request $request, GoalScheduledReport $scheduled_report)
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);
        $this->authorizeOwner($user, $scheduled_report);

        $data = $this->validated($request);
        if ($request->filled('keep_filters') || ! $request->has('fy')) {
            $data['filters'] = $scheduled_report->filters ?? [];
        } else {
            $data['filters'] = $this->snapshotFilters($request);
        }
        $this->service->updateSchedule($scheduled_report, $data);

        return redirect()
            ->route('goal.scheduled-reports.index')
            ->with('success', 'Schedule updated successfully.');
    }

    public function pause(GoalScheduledReport $scheduled_report)
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);
        $this->authorizeOwner($user, $scheduled_report);
        $this->service->pause($scheduled_report);

        return back()->with('success', 'Schedule paused.');
    }

    public function resume(GoalScheduledReport $scheduled_report)
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);
        $this->authorizeOwner($user, $scheduled_report);
        $this->service->resume($scheduled_report);

        return back()->with('success', 'Schedule resumed.');
    }

    public function destroy(GoalScheduledReport $scheduled_report)
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);
        $this->authorizeOwner($user, $scheduled_report);
        $scheduled_report->delete();

        return back()->with('success', 'Schedule deleted.');
    }

    public function runNow(GoalScheduledReport $scheduled_report)
    {
        $user = auth()->user();
        $this->authorizeAnalytics($user);
        $this->authorizeOwner($user, $scheduled_report);

        $run = $this->service->run($scheduled_report, true);
        if ($run->status === 'success') {
            return back()->with('success', 'Report generated and emailed successfully.');
        }

        $raw = (string) ($run->error_message ?: 'Unknown error');
        $message = $this->friendlyMailError($raw);

        return back()->with('error', $message);
    }

    protected function friendlyMailError(string $raw): string
    {
        $lower = strtolower($raw);
        if (str_contains($lower, 'authentication failed')
            || str_contains($lower, 'failed to authenticate')
            || str_contains($lower, '535')) {
            return 'Email could not be sent: SMTP authentication failed. Update credentials under Settings → Email Settings (or .env MAIL_*), then click Run Now again.';
        }
        if (str_contains($lower, 'connection could not be established')
            || str_contains($lower, 'timed out')) {
            return 'Email could not be sent: SMTP server is unreachable. Check MAIL_HOST / MAIL_PORT and network access, then try Run Now again.';
        }

        return 'Run failed: '.\Illuminate\Support\Str::limit($raw, 280);
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'report_type' => ['required', Rule::in(array_keys(GoalScheduledReport::TYPES))],
            'frequency' => ['required', Rule::in(array_keys(GoalScheduledReport::FREQUENCIES))],
            'run_time' => ['required', 'string', 'max:8'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'no_end_date' => ['nullable', 'boolean'],
            'format' => ['required', Rule::in(array_keys(GoalScheduledReport::FORMATS))],
            'recipients' => ['required'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'day_of_month' => ['nullable', 'integer', 'between:1,28'],
            'interval_days' => ['nullable', 'integer', 'between:1,365'],
        ]);

        $noEnd = $request->boolean('no_end_date');
        if (! $noEnd && empty($validated['end_date'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'end_date' => 'End date is required unless No End Date is checked.',
            ]);
        }

        $recipients = $this->parseRecipients($validated['recipients']);
        if (count($recipients) < 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'recipients' => 'Add at least one valid email address.',
            ]);
        }

        $runTime = $this->normalizeTime($validated['run_time']);

        $frequencyConfig = match ($validated['frequency']) {
            'weekly' => [
                'weekdays' => array_values(array_unique(array_map('intval', $request->input('weekdays', [1])))),
            ],
            'monthly' => [
                'day_of_month' => (int) $request->input('day_of_month', 1),
            ],
            'custom' => [
                'interval_days' => max(1, (int) $request->input('interval_days', 7)),
            ],
            default => [],
        };

        if ($validated['frequency'] === 'weekly' && empty($frequencyConfig['weekdays'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'weekdays' => 'Select at least one weekday for weekly schedules.',
            ]);
        }

        return [
            'name' => trim($validated['name']),
            'report_type' => $validated['report_type'],
            'frequency' => $validated['frequency'],
            'frequency_config' => $frequencyConfig,
            'run_time' => $runTime,
            'start_date' => Carbon::parse($validated['start_date'])->toDateString(),
            'end_date' => $noEnd ? null : Carbon::parse($validated['end_date'])->toDateString(),
            'no_end_date' => $noEnd,
            'format' => $validated['format'],
            'recipients' => $recipients,
        ];
    }

    protected function snapshotFilters(Request $request): array
    {
        return [
            'fy' => (string) $request->input('fy', ''),
            'date_from' => (string) $request->input('date_from', now()->startOfMonth()->format('Y-m-d')),
            'date_to' => (string) $request->input('date_to', now()->format('Y-m-d')),
            'region' => $request->input('region'),
            'cluster' => $request->input('cluster'),
            'branch' => $request->input('branch'),
            'product' => $request->input('product'),
            'owner' => $request->input('owner'),
            'target_tab' => 'employee',
        ];
    }

    protected function parseRecipients(mixed $raw): array
    {
        if (is_array($raw)) {
            $parts = $raw;
        } else {
            $parts = preg_split('/[\s,;]+/', (string) $raw) ?: [];
        }

        $emails = [];
        foreach ($parts as $p) {
            $email = strtolower(trim((string) $p));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = $email;
            }
        }

        return array_values($emails);
    }

    protected function normalizeTime(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $value, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            $ampm = strtoupper($m[3]);
            if ($ampm === 'AM') {
                $h = $h === 12 ? 0 : $h;
            } else {
                $h = $h === 12 ? 12 : $h + 12;
            }

            return sprintf('%02d:%02d', $h, $min);
        }
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'run_time' => 'Select a valid time.',
        ]);
    }

    protected function authorizeAnalytics($user): void
    {
        if (! GoalDashboardService::userUsesGoalDashboard($user)) {
            abort(403, 'Unauthorized action.');
        }

        $can = $user->isSuperAdmin()
            || $user->hasCrmPermission('view_targets_guard')
            || $user->hasCrmPermission('manage_targets_guard')
            || $user->hasCrmPermission('branch_mis_guard')
            || $user->hasCrmPermission('revenue_ledger_guard');
        if (! $can) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function authorizeOwner($user, GoalScheduledReport $schedule): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }
        if ((int) $schedule->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }
}
