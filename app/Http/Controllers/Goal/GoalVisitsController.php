<?php

namespace App\Http\Controllers\Goal;

use App\Exports\GoalVisitsExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Library\MpdfDocument;
use App\Models\GoalCaseTask;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\User;
use App\Services\Goal\GoalCaseAudit;
use App\Services\Goal\GoalWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalVisitsController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');

        $filters = $this->validatedFilters($request);
        $base = $this->tasksQuery($filters);

        $this->refreshEscalations(clone $base);

        // Load once — tabs / KPIs / calendar filter client-side (no full page refresh).
        $tasks = (clone $base)
            ->with([
                'loanCase:id,case_no,customer_name,mobile,goal_org_unit_id,owner_id,status',
                'loanCase.orgUnit:id,name',
                'assignee:id,name,job_role,crm_role_type',
            ])
            ->orderByRaw("CASE WHEN status = 'Open' THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->limit(500)
            ->get();

        $kpis = [
            'today' => $tasks->filter(fn (GoalCaseTask $t) => $t->isDueToday())->count(),
            'upcoming' => $tasks->filter(fn (GoalCaseTask $t) => $t->isUpcoming())->count(),
            'overdue' => $tasks->filter(fn (GoalCaseTask $t) => $t->isOverdue())->count(),
            'completed_month' => $tasks->filter(function (GoalCaseTask $t) {
                return $t->isCompleted()
                    && $t->completed_at
                    && $t->completed_at->isSameMonth(now());
            })->count(),
            'all' => $tasks->count(),
        ];

        $overdueTasks = $tasks->filter(fn (GoalCaseTask $t) => $t->isOverdue())
            ->sortBy('due_at')
            ->take(12)
            ->values();

        $activity = $tasks->sortByDesc(fn (GoalCaseTask $t) => $t->updated_at?->timestamp ?? 0)
            ->take(12)
            ->values();

        $calendarMonth = Carbon::parse(($filters['cal'] ?: now()->format('Y-m')).'-01')->startOfMonth();
        $calendarDays = [];
        foreach ($tasks as $task) {
            if (! $task->due_at) {
                continue;
            }
            $key = $task->due_at->format('Y-m-d');
            $calendarDays[$key] = ($calendarDays[$key] ?? 0) + 1;
        }

        $branches = GoalOrgUnit::query()
            ->where('type', GoalOrgUnit::TYPE_BRANCH)
            ->orderBy('name')
            ->get(['id', 'name']);
        $assignees = $this->salesUsers($this->scopedOrgIds());
        $cases = GoalLoanCase::query()
            ->with('orgUnit:id,name')
            ->tap(fn (Builder $b) => $this->scopeByOrgColumn($b))
            ->whereNotIn('status', ['Closed', 'Rejected'])
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'case_no', 'customer_name', 'goal_org_unit_id', 'owner_id', 'status']);

        return view('goal.visits.index', compact(
            'tasks', 'kpis', 'filters', 'overdueTasks', 'activity',
            'calendarMonth', 'calendarDays', 'branches', 'assignees', 'cases'
        ));
    }

    public function store(Request $request)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->normalizeScheduledTime($request);

        $data = $request->validate([
            'goal_loan_case_id' => 'required|integer|exists:goal_loan_cases,id',
            'task_type' => 'required|in:visit,followup',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|date_format:H:i',
            'assigned_to' => 'required|integer|exists:users,id',
            'notes' => 'nullable|string|max:2000',
            'location' => 'nullable|string|max:255',
        ]);

        $case = GoalLoanCase::query()->findOrFail((int) $data['goal_loan_case_id']);
        $this->assertCaseInScope($case);
        if (GoalWorkflow::isWorkspaceLocked($case->status)
            && ! auth()->user()?->hasCrmPermission('manage_permissions_guard')) {
            return back()->withErrors(['goal_loan_case_id' => GoalWorkflow::workspaceLockMessage($case->status)]);
        }
        if ($err = $this->assertAssigneeInCaseBranch($case, (int) $data['assigned_to'])) {
            return back()->withErrors(['assigned_to' => $err])->withInput();
        }

        $dueAt = Carbon::parse($data['scheduled_date'].' '.$data['scheduled_time']);
        $typeLabel = $data['task_type'] === 'visit' ? 'Customer Visit' : 'Follow-up';

        $task = GoalCaseTask::create([
            'goal_loan_case_id' => $case->id,
            'title' => $typeLabel.' — '.$case->customer_name,
            'task_type' => $data['task_type'],
            'status' => GoalCaseTask::STATUS_OPEN,
            'due_at' => $dueAt,
            'assigned_to' => (int) $data['assigned_to'],
            'created_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
            'location' => $data['location'] ?? null,
        ]);

        GoalCaseAudit::record($case, 'visit', $typeLabel.' scheduled', auth()->user(), [
            'to' => $dueAt->format('d-m-Y H:i'),
            'related_type' => GoalCaseTask::class,
            'related_id' => $task->id,
            'body' => $data['notes'] ?? null,
            'meta' => ['task_type' => $data['task_type'], 'assigned_to' => (int) $data['assigned_to']],
        ]);

        return redirect()
            ->route('goal.visits.index', [
                'cal' => $dueAt->format('Y-m'),
                'day' => $dueAt->format('Y-m-d'),
            ])
            ->with('success', $typeLabel.' scheduled.');
    }

    public function update(Request $request, GoalCaseTask $task)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $case = $task->loanCase;
        $this->assertCaseInScope($case);
        if ($task->isCompleted()) {
            return back()->withErrors(['task' => 'Completed tasks cannot be edited.']);
        }
        $this->normalizeScheduledTime($request);

        $data = $request->validate([
            'task_type' => 'required|in:visit,followup',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|date_format:H:i',
            'assigned_to' => 'required|integer|exists:users,id',
            'notes' => 'nullable|string|max:2000',
            'location' => 'nullable|string|max:255',
        ]);
        if ($err = $this->assertAssigneeInCaseBranch($case, (int) $data['assigned_to'])) {
            return back()->withErrors(['assigned_to' => $err])->withInput();
        }

        $fromAssignee = $task->assigned_to;
        $dueAt = Carbon::parse($data['scheduled_date'].' '.$data['scheduled_time']);
        $task->fill([
            'task_type' => $data['task_type'],
            'title' => ($data['task_type'] === 'visit' ? 'Customer Visit' : 'Follow-up').' — '.$case->customer_name,
            'due_at' => $dueAt,
            'assigned_to' => (int) $data['assigned_to'],
            'notes' => $data['notes'] ?? null,
            'location' => $data['location'] ?? null,
            'escalated_level' => null,
            'escalated_at' => null,
        ]);
        $task->save();

        $body = (int) $fromAssignee !== (int) $data['assigned_to'] ? 'Task updated / reassigned' : 'Task updated';
        GoalCaseAudit::record($case, 'visit', $body, auth()->user(), [
            'from' => (string) $fromAssignee,
            'to' => (string) $data['assigned_to'],
            'related_type' => GoalCaseTask::class,
            'related_id' => $task->id,
        ]);

        return redirect()
            ->route('goal.visits.index', [
                'cal' => $dueAt->format('Y-m'),
                'day' => $dueAt->format('Y-m-d'),
            ])
            ->with('success', 'Task updated.');
    }

    public function complete(Request $request, GoalCaseTask $task)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $case = $task->loanCase;
        $this->assertCaseInScope($case);

        if ($task->isCompleted()) {
            return back()->with('success', 'Task already completed.');
        }

        $data = $request->validate([
            'outcome' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'completed_at' => 'nullable|date',
        ]);

        $task->status = GoalCaseTask::STATUS_COMPLETED;
        $task->completed_at = ! empty($data['completed_at'])
            ? Carbon::parse($data['completed_at'])
            : now();
        $task->completed_by = auth()->id();
        $task->outcome = $data['outcome'];
        if (! empty($data['notes'])) {
            $task->notes = trim(($task->notes ? $task->notes."\n" : '').$data['notes']);
        }
        $task->save();

        GoalCaseAudit::record($case, 'visit', $task->typeLabel().' completed', auth()->user(), [
            'to' => $data['outcome'],
            'related_type' => GoalCaseTask::class,
            'related_id' => $task->id,
            'body' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Task marked complete.');
    }

    public function export(Request $request): StreamedResponse|BinaryFileResponse|\Illuminate\Http\Response
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $request->validate(['format' => 'nullable|in:pdf,excel,csv']);
        $format = $request->input('format', 'csv');
        $filters = $this->validatedFilters($request);
        $tasks = $this->tasksQuery($filters)
            ->with(['loanCase.orgUnit', 'assignee'])
            ->orderBy('due_at')
            ->limit(5000)
            ->get();

        $headings = [
            'Case Reference', 'Customer', 'Task Type', 'Scheduled', 'Assigned To',
            'Branch', 'Status', 'Outcome', 'Escalation', 'Notes',
        ];
        $rows = [];
        foreach ($tasks as $task) {
            $rows[] = [
                $task->loanCase?->case_no,
                $task->loanCase?->customer_name,
                $task->typeLabel(),
                optional($task->due_at)->format('Y-m-d H:i'),
                $task->assignee?->name,
                $task->loanCase?->orgUnit?->name,
                $task->displayStatus(),
                $task->outcome,
                $task->escalationLabel(),
                $task->notes,
            ];
        }

        $stamp = now()->format('Ymd-His');

        if ($format === 'pdf') {
            return MpdfDocument::fromView(
                'goal.visits.exports.visits-pdf',
                compact('headings', 'rows'),
                'customer-visits-'.$stamp.'.pdf',
                true,
                'A4-L'
            );
        }

        $writerType = $format === 'excel' ? ExcelWriter::XLSX : ExcelWriter::CSV;
        $ext = $format === 'excel' ? 'xlsx' : 'csv';

        return Excel::download(
            new GoalVisitsExport($rows, $headings),
            'customer-visits-'.$stamp.'.'.$ext,
            $writerType
        );
    }

    /**
     * Accept 12h picker values (e.g. "12:00 PM") and normalize to H:i for validation.
     */
    protected function normalizeScheduledTime(Request $request): void
    {
        $raw = trim(preg_replace('/\s+/', ' ', (string) $request->input('scheduled_time', '')) ?? '');
        if ($raw === '') {
            return;
        }

        foreach (['H:i', 'G:i', 'h:i A', 'h:i a', 'g:i A', 'g:i a'] as $format) {
            $parsed = Carbon::hasFormat($raw, $format) ? Carbon::createFromFormat($format, $raw) : null;
            if (! $parsed) {
                continue;
            }
            $request->merge(['scheduled_time' => $parsed->format('H:i')]);

            return;
        }
    }

    protected function validatedFilters(Request $request): array
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:150',
            'branch' => 'nullable|integer',
            'assigned_to' => 'nullable|integer',
            'task_type' => 'nullable|in:visit,followup',
            'status' => 'nullable|in:Open,Completed,Cancelled',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'tab' => 'nullable|in:all,today,upcoming,overdue,completed',
            'day' => 'nullable|date',
            'cal' => 'nullable|date_format:Y-m',
        ]);

        return [
            'q' => trim((string) ($data['q'] ?? '')),
            'branch' => isset($data['branch']) ? (int) $data['branch'] : null,
            'assigned_to' => isset($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            'task_type' => (string) ($data['task_type'] ?? ''),
            'status' => (string) ($data['status'] ?? ''),
            'date_from' => (string) ($data['date_from'] ?? ''),
            'date_to' => (string) ($data['date_to'] ?? ''),
            'tab' => (string) ($data['tab'] ?? 'all'),
            'day' => (string) ($data['day'] ?? ''),
            'cal' => (string) ($data['cal'] ?? ''),
        ];
    }

    protected function tasksQuery(array $filters): Builder
    {
        $query = GoalCaseTask::query()
            ->visitTypes()
            ->whereHas('loanCase', function (Builder $q) use ($filters) {
                $this->scopeByOrgColumn($q);
                if (! empty($filters['branch'])) {
                    $q->where('goal_org_unit_id', $filters['branch']);
                }
                if ($filters['q'] !== '') {
                    $q->where(function (Builder $inner) use ($filters) {
                        $inner->where('case_no', 'like', '%'.$filters['q'].'%')
                            ->orWhere('customer_name', 'like', '%'.$filters['q'].'%')
                            ->orWhere('mobile', 'like', '%'.$filters['q'].'%');
                    });
                }
            });

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }
        if ($filters['task_type'] !== '') {
            $query->where('task_type', $filters['task_type']);
        }
        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if ($filters['date_from'] !== '') {
            $query->whereDate('due_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== '') {
            $query->whereDate('due_at', '<=', $filters['date_to']);
        }
        // Note: `day` / `tab` are client-side only (do not filter the loaded set here).

        return $query;
    }

    protected function refreshEscalations(Builder $base): void
    {
        (clone $base)->overdue()->limit(500)->get()->each->syncEscalation();
    }

    /** @return array<string,int> Y-m-d => count */
    protected function calendarMap(Builder $base, Carbon $month): array
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $map = [];
        (clone $base)
            ->whereBetween('due_at', [$from, $to])
            ->get(['due_at'])
            ->each(function (GoalCaseTask $task) use (&$map) {
                if (! $task->due_at) {
                    return;
                }
                $key = $task->due_at->format('Y-m-d');
                $map[$key] = ($map[$key] ?? 0) + 1;
            });

        return $map;
    }

    protected function salesUsers(array $scope): Collection
    {
        return User::query()
            ->where(function ($q) {
                $q->whereIn('crm_role_type', [10, 11, 12])
                    ->orWhereIn('job_role', ['jsm', 'sm', 'ssm']);
            })
            ->when($scope !== [], function ($q) use ($scope) {
                $q->where(function ($inner) use ($scope) {
                    $inner->whereIn('goal_org_unit_id', $scope)
                        ->orWhereNull('goal_org_unit_id');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'goal_org_unit_id', 'job_role', 'crm_role_type']);
    }

    protected function assertCaseInScope(GoalLoanCase $case): void
    {
        $scope = $this->scopedOrgIds();
        if ($scope !== [] && $case->goal_org_unit_id && ! in_array((int) $case->goal_org_unit_id, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function assertAssigneeInCaseBranch(GoalLoanCase $case, int $assigneeId): ?string
    {
        if (! $case->goal_org_unit_id) {
            return null;
        }
        $user = User::query()->find($assigneeId);
        if (! $user) {
            return 'Selected employee was not found.';
        }
        if ((int) $user->goal_org_unit_id !== (int) $case->goal_org_unit_id) {
            return 'Assigned To must be a staff member from the case branch.';
        }

        return null;
    }
}
