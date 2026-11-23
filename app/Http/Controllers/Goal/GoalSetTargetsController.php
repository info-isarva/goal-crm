<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalTarget;
use App\Models\GoalTargetAuditLog;
use App\Services\Goal\GoalSetTargetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoalSetTargetsController extends Controller
{
    use AuthorizesGoal;

    public function __construct(private GoalSetTargetsService $service) {}

    public function show(Request $request): View
    {
        $this->requireGoalPermission('manage_targets_guard');
        $user = $request->user();
        $period = (string) $request->input('period', $this->service->defaultPeriod());
        $metric = (string) $request->input('metric', 'revenue');
        $payload = $this->service->tree($user, $period, $metric);

        return view('goal.targets.set', [
            'payload' => $payload,
            'metrics' => [
                'revenue' => 'Revenue',
                'disbursement' => 'Disbursement',
                'deals' => 'Deals closed',
            ],
            'canManage' => true,
        ]);
    }

    public function tree(Request $request): JsonResponse
    {
        $this->requireGoalPermission('manage_targets_guard');
        $data = $request->validate([
            'period' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'metric' => 'nullable|string|max:30',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->service->tree(
                $request->user(),
                $data['period'] ?? $this->service->defaultPeriod(),
                $data['metric'] ?? 'revenue'
            ),
        ]);
    }

    public function saveDraft(Request $request): JsonResponse
    {
        $this->requireGoalPermission('manage_targets_guard');
        $data = $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'metric' => 'required|string|max:30',
            'rows' => 'required|array|min:1',
            'rows.*.employee_id' => 'required|integer|exists:users,id',
            'rows.*.set_target' => 'required|numeric|min:0',
            'rows.*.owner_target' => 'nullable|numeric|min:0',
            'rows.*.row_source' => 'nullable|in:auto,edited',
        ]);

        $result = $this->service->saveDraft(
            $request->user(),
            $data['period'],
            $data['metric'],
            $data['rows'],
            false
        );

        return response()->json(['ok' => true, 'data' => $result]);
    }

    public function assign(Request $request): JsonResponse
    {
        $this->requireGoalPermission('manage_targets_guard');
        $data = $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'metric' => 'required|string|max:30',
            'rows' => 'required|array|min:1',
            'rows.*.employee_id' => 'required|integer|exists:users,id',
            'rows.*.set_target' => 'required|numeric|min:0',
            'rows.*.owner_target' => 'nullable|numeric|min:0',
            'rows.*.row_source' => 'nullable|in:auto,edited',
        ]);

        $result = $this->service->saveDraft(
            $request->user(),
            $data['period'],
            $data['metric'],
            $data['rows'],
            true
        );

        return response()->json(['ok' => true, 'data' => $result, 'message' => 'Targets assigned and team notified.']);
    }

    public function autoSplit(Request $request): JsonResponse
    {
        $this->requireGoalPermission('manage_targets_guard');
        $data = $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'metric' => 'required|string|max:30',
            'root_total' => 'required|numeric|min:0',
            'methods' => 'nullable|array',
            'methods.default' => 'nullable|string',
            'methods.l1' => 'nullable|string',
            'methods.l2' => 'nullable|string',
            'methods.l3' => 'nullable|string',
            'methods.staff' => 'nullable|string',
        ]);

        $result = $this->service->autoSplit(
            $request->user(),
            $data['period'],
            $data['metric'],
            (float) $data['root_total'],
            $data['methods'] ?? []
        );

        return response()->json(['ok' => true, 'data' => $result]);
    }

    public function requestRevision(Request $request, GoalTarget $target): JsonResponse
    {
        $this->requireGoalPermission('manage_targets_guard');
        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $updated = $this->service->requestRevision($request->user(), (int) $target->id, $data['reason']);

        return response()->json(['ok' => true, 'data' => ['id' => $updated->id, 'status' => $updated->status]]);
    }

    public function auditLog(Request $request): JsonResponse
    {
        $this->requireGoalPermission('manage_targets_guard');
        $data = $request->validate([
            'period' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'metric' => 'nullable|string|max:30',
        ]);
        $period = $data['period'] ?? $this->service->defaultPeriod();
        $metric = $data['metric'] ?? 'revenue';
        $editable = $this->service->editableEmployeeIds($request->user());

        $logs = GoalTargetAuditLog::query()
            ->with(['employee:id,name', 'changer:id,name'])
            ->where('period', $period)
            ->where('metric', $metric)
            ->whereIn('employee_id', $editable ?: [-1])
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (GoalTargetAuditLog $l) => [
                'id' => $l->id,
                'employee' => $l->employee?->name,
                'changed_by' => $l->changer?->name,
                'field' => $l->field,
                'old_value' => (float) $l->old_value,
                'new_value' => (float) $l->new_value,
                'action' => $l->action,
                'at' => optional($l->created_at)->format('d M Y H:i'),
            ]);

        return response()->json(['ok' => true, 'data' => $logs]);
    }
}
