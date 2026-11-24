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

    /**
     * Manage permission, or view + editable downline (RD / CD / BM).
     */
    protected function requireSetTargetsAccess(): void
    {
        $user = auth()->user();
        if ($user && $this->service->canManageTargets($user)) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }

    public function show(Request $request): View
    {
        $this->requireSetTargetsAccess();
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
            'canManage' => (bool) ($payload['can_edit'] ?? false),
        ]);
    }

    public function tree(Request $request): JsonResponse
    {
        $this->requireSetTargetsAccess();
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
        $this->requireSetTargetsAccess();
        $data = $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'metric' => 'required|string|max:30',
            'rows' => 'required|array|min:1',
            'rows.*.employee_id' => 'required|integer|exists:users,id',
            'rows.*.set_target' => 'required|numeric|min:0',
            'rows.*.owner_target' => 'nullable|numeric|min:0',
            'rows.*.row_source' => 'nullable|in:auto,edited',
            'rows.*.org_unit_id' => 'nullable|integer',
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
        $this->requireSetTargetsAccess();
        $data = $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'metric' => 'required|string|max:30',
            'rows' => 'required|array|min:1',
            'rows.*.employee_id' => 'required|integer|exists:users,id',
            'rows.*.set_target' => 'required|numeric|min:0',
            'rows.*.owner_target' => 'nullable|numeric|min:0',
            'rows.*.row_source' => 'nullable|in:auto,edited',
            'rows.*.org_unit_id' => 'nullable|integer',
        ]);

        $result = $this->service->saveDraft(
            $request->user(),
            $data['period'],
            $data['metric'],
            $data['rows'],
            true
        );

        $submitted = (int) ($result['submitted'] ?? 0);
        $saved = (int) ($result['saved'] ?? 0);
        $message = $submitted > 0
            ? ($submitted === $saved
                ? 'Changes submitted for higher-authority approval.'
                : "Assigned {$saved} row(s); {$submitted} submitted for higher-authority approval.")
            : 'Targets assigned and team notified.';

        return response()->json(['ok' => true, 'data' => $result, 'message' => $message]);
    }

    public function autoSplit(Request $request): JsonResponse
    {
        $this->requireSetTargetsAccess();
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
        $this->requireSetTargetsAccess();
        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $updated = $this->service->requestRevision($request->user(), (int) $target->id, $data['reason']);

        return response()->json(['ok' => true, 'data' => ['id' => $updated->id, 'status' => $updated->status]]);
    }

    public function approveSubmitted(Request $request, GoalTarget $target): JsonResponse
    {
        $this->requireSetTargetsAccess();
        $updated = $this->service->approveSubmitted($request->user(), (int) $target->id);

        return response()->json([
            'ok' => true,
            'message' => 'Target approved and team notified.',
            'data' => [
                'id' => $updated->id,
                'status' => $updated->status,
                'pending_approvals' => $this->service->pendingApprovalsFor(
                    $request->user(),
                    (string) $updated->period,
                    (string) $updated->metric
                ),
            ],
        ]);
    }

    public function rejectSubmitted(Request $request, GoalTarget $target): JsonResponse
    {
        $this->requireSetTargetsAccess();
        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $updated = $this->service->rejectSubmitted(
            $request->user(),
            (int) $target->id,
            $data['reason'] ?? null
        );

        return response()->json([
            'ok' => true,
            'message' => 'Target change rejected. Submitter was notified.',
            'data' => [
                'id' => $updated->id,
                'status' => $updated->status,
                'pending_approvals' => $this->service->pendingApprovalsFor(
                    $request->user(),
                    (string) $updated->period,
                    (string) $updated->metric
                ),
            ],
        ]);
    }

    public function auditLog(Request $request): JsonResponse
    {
        $this->requireSetTargetsAccess();
        $data = $request->validate([
            'period' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
            'metric' => 'nullable|string|max:30',
        ]);
        $period = $data['period'] ?? $this->service->defaultPeriod();
        $metric = $data['metric'] ?? 'revenue';
        $actor = $request->user();
        $editable = $this->service->editableEmployeeIds($actor);
        $actorId = (int) $actor->id;

        $logs = GoalTargetAuditLog::query()
            ->with(['employee:id,name,job_role,crm_role_type', 'changer:id,name'])
            ->where('period', $period)
            ->where('metric', $metric)
            ->where(function ($q) use ($editable, $actorId) {
                if ($editable !== []) {
                    $q->whereIn('employee_id', $editable);
                } else {
                    $q->whereRaw('1 = 0');
                }
                // Include rows this user changed (e.g. their own submissions).
                $q->orWhere('changed_by', $actorId);
            })
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(function (GoalTargetAuditLog $l) {
                $meta = is_array($l->meta) ? $l->meta : [];
                $action = (string) ($l->action ?? 'update');
                $field = (string) ($l->field ?? '');
                $old = (float) ($l->old_value ?? 0);
                $new = (float) ($l->new_value ?? 0);
                $ownerOld = array_key_exists('owner_old', $meta) ? (float) $meta['owner_old'] : null;
                $ownerNew = array_key_exists('owner_new', $meta) ? (float) $meta['owner_new'] : null;

                $actionMeta = match ($action) {
                    'submit' => ['label' => 'Submitted for approval', 'tone' => 'submit'],
                    'approve' => ['label' => 'Approved', 'tone' => 'approve'],
                    'reject' => ['label' => 'Rejected', 'tone' => 'reject'],
                    'assign' => ['label' => 'Assigned', 'tone' => 'assign'],
                    'revision_request' => ['label' => 'Revision requested', 'tone' => 'warn'],
                    default => ['label' => $field === 'status' ? 'Status change' : 'Updated', 'tone' => 'update'],
                };

                $changes = [];
                if ($field === 'base_target' || $field === '') {
                    if (abs($old - $new) > 0.009) {
                        $changes[] = [
                            'label' => 'Total',
                            'old' => $old,
                            'new' => $new,
                        ];
                    }
                    if ($ownerOld !== null && $ownerNew !== null && abs($ownerOld - $ownerNew) > 0.009) {
                        $changes[] = [
                            'label' => 'Owner',
                            'old' => $ownerOld,
                            'new' => $ownerNew,
                        ];
                    }
                    if ($changes === [] && ($field === 'base_target' || $action === 'submit')) {
                        $changes[] = [
                            'label' => 'Total',
                            'old' => $old,
                            'new' => $new,
                        ];
                    }
                } elseif ($field === 'status') {
                    $from = (string) ($meta['from'] ?? $meta['old_status'] ?? '');
                    $to = (string) ($meta['to'] ?? $meta['new_status'] ?? '');
                    if ($from !== '' || $to !== '') {
                        $changes[] = [
                            'label' => 'Status',
                            'old_text' => $from !== '' ? $from : '—',
                            'new_text' => $to !== '' ? $to : '—',
                        ];
                    }
                }

                $reason = (string) ($meta['reason'] ?? '');

                return [
                    'id' => $l->id,
                    'employee' => (string) ($l->employee?->name ?? '—'),
                    'changed_by' => (string) ($l->changer?->name ?? '—'),
                    'field' => $field,
                    'old_value' => $old,
                    'new_value' => $new,
                    'owner_old' => $ownerOld,
                    'owner_new' => $ownerNew,
                    'action' => $action,
                    'action_label' => $actionMeta['label'],
                    'action_tone' => $actionMeta['tone'],
                    'changes' => $changes,
                    'reason' => $reason,
                    'at' => optional($l->created_at)->format('d M Y · H:i'),
                ];
            });

        return response()->json(['ok' => true, 'data' => $logs->values()]);
    }
}
