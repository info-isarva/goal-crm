<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalContactImport;
use App\Services\Goal\GoalContactImportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalContactImportController extends Controller
{
    use AuthorizesGoal;

    public function __construct(
        protected GoalContactImportService $imports
    ) {
    }

    public function index(Request $request)
    {
        $this->requireGoalPermission('import_contacts_guard');

        $actor = auth()->user();
        $scope = $this->scopedOrgIds();
        $canPickBranch = $this->imports->actorCanPickBranch($actor);
        $canSplit = $actor->hasCrmPermission('split_contacts_guard');
        $branches = $this->imports->branchesInScope($scope);
        $clusters = $canPickBranch ? $this->imports->clustersInScope($scope) : collect();
        $orgOptions = $canPickBranch
            ? $this->imports->orgOptionsInScope($scope)
            : ['regions' => collect(), 'clusters' => collect(), 'branches' => $branches];
        $vrms = $canSplit ? $this->imports->vrmsInScope($scope) : collect();

        $batchBase = GoalContactImport::query()
            ->when($scope !== [], function ($q) use ($scope, $actor) {
                $q->where(function ($inner) use ($scope, $actor) {
                    $inner->whereIn('goal_org_unit_id', $scope)
                        ->orWhere('imported_by', $actor->id)
                        ->orWhereHas('leads', fn ($l) => $l->whereIn('goal_org_unit_id', $scope));
                });
            });

        $batchStats = [
            'total' => (clone $batchBase)->count(),
            'completed' => (clone $batchBase)->whereIn('status', ['ready', 'partial', 'assigned'])->count(),
            'assigned' => (clone $batchBase)->where(function ($q) use ($scope) {
                $q->where('status', 'assigned')
                    ->orWhere(function ($inner) use ($scope) {
                        $inner->whereIn('status', ['ready', 'partial'])
                            ->where('success_count', '>', 0)
                            ->whereDoesntHave('leads', function ($l) use ($scope) {
                                $l->whereNull('assigned_to')
                                    ->when($scope !== [], fn ($x) => $x->whereIn('goal_org_unit_id', $scope));
                            });
                    });
            })->count(),
            'processing' => (clone $batchBase)->where('status', 'processing')->count(),
            'failed' => (clone $batchBase)->where('status', 'failed')->count(),
        ];

        $batches = (clone $batchBase)
            ->with(['importer:id,name', 'orgUnit:id,name,code'])
            ->withCount([
                'leads as unassigned_count' => function ($q) use ($scope) {
                    $q->whereNull('assigned_to')
                        ->when($scope !== [], fn ($inner) => $inner->whereIn('goal_org_unit_id', $scope));
                },
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $tab = in_array($request->query('tab'), ['import', 'assign'], true)
            ? $request->query('tab')
            : 'import';
        if ($tab === 'assign' && ! $canSplit) {
            $tab = 'import';
        }

        $selectedBatchId = (int) $request->query('batch', 0);
        $selectedBatch = null;
        $unassigned = collect();
        $batchSummary = null;
        $assignDraft = [];
        if ($selectedBatchId > 0) {
            $selectedBatch = GoalContactImport::query()->find($selectedBatchId);
            if ($selectedBatch) {
                try {
                    $this->assertImportInScope($selectedBatch);
                    $unassigned = $selectedBatch->unassignedLeads()
                        ->with('orgUnit:id,name,code')
                        ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
                        ->orderBy('id')
                        ->get();
                    $batchSummary = $this->imports->batchSummary($selectedBatch, $scope);
                    $assignDraft = is_array($selectedBatch->assign_draft) ? $selectedBatch->assign_draft : [];
                } catch (\Throwable $e) {
                    $selectedBatch = null;
                    $selectedBatchId = 0;
                }
            } else {
                $selectedBatchId = 0;
            }
        }

        if ($tab === 'assign' && $selectedBatchId === 0) {
            $firstOpen = $batches->first(fn ($b) => (int) ($b->unassigned_count ?? 0) > 0)
                ?: $batches->first();
            if ($firstOpen) {
                $selectedBatchId = (int) $firstOpen->id;
                $selectedBatch = GoalContactImport::query()->find($selectedBatchId) ?: $firstOpen;
                $unassigned = $selectedBatch->unassignedLeads()
                    ->with('orgUnit:id,name,code')
                    ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
                    ->orderBy('id')
                    ->get();
                $batchSummary = $this->imports->batchSummary($selectedBatch, $scope);
                $assignDraft = is_array($selectedBatch->assign_draft ?? null) ? $selectedBatch->assign_draft : [];
            }
        }

        $orgTree = [
            'regions' => ($orgOptions['regions'] ?? collect())->map(fn ($u) => [
                'id' => (int) $u->id, 'name' => $u->name, 'code' => $u->code, 'parent_id' => (int) ($u->parent_id ?? 0),
            ])->values(),
            'clusters' => ($orgOptions['clusters'] ?? collect())->map(fn ($u) => [
                'id' => (int) $u->id, 'name' => $u->name, 'code' => $u->code, 'parent_id' => (int) ($u->parent_id ?? 0),
            ])->values(),
            'branches' => ($orgOptions['branches'] ?? $branches)->map(fn ($u) => [
                'id' => (int) $u->id, 'name' => $u->name, 'code' => $u->code, 'parent_id' => (int) ($u->parent_id ?? 0),
            ])->values(),
        ];

        return view('goal.contacts.import', [
            'batches' => $batches,
            'batchStats' => $batchStats,
            'branches' => $branches,
            'clusters' => $clusters,
            'orgOptions' => $orgOptions,
            'orgTree' => $orgTree,
            'vrms' => $vrms,
            'canPickBranch' => $canPickBranch,
            'defaultOrgUnitId' => $this->imports->resolveDefaultOrgUnitId($actor, $scope, null),
            'canSplit' => $canSplit,
            'tab' => $tab,
            'selectedBatchId' => $selectedBatchId,
            'selectedBatch' => $selectedBatch,
            'unassigned' => $unassigned,
            'batchSummary' => $batchSummary,
            'assignDraft' => $assignDraft,
            'assignResult' => session('assign_result'),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireGoalPermission('import_contacts_guard');

        $actor = auth()->user();
        $scope = $this->scopedOrgIds();
        $canPickBranch = $this->imports->actorCanPickBranch($actor);

        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        // Upload-only: org/status are decided later on Assign. Soft-hold under actor default org.
        $import = $this->imports->processUpload(
            $actor,
            $request->file('import_file'),
            $scope,
            $this->imports->resolveDefaultOrgUnitId($actor, $scope, null),
            'Future Prospect',
            $canPickBranch
        );

        $msg = "Import complete: {$import->success_count} created";
        if ($import->failed_count > 0) {
            $msg .= ", {$import->failed_count} failed/skipped";
        }
        $msg .= '.';

        $canSplit = $actor->hasCrmPermission('split_contacts_guard');
        if ($canSplit && $import->success_count > 0) {
            return redirect()
                ->route('goal.contacts.import', ['tab' => 'assign', 'batch' => $import->id])
                ->with('success', $msg.' Continue on Assign to send contacts to a cluster, branch, or VRM.');
        }

        return redirect()
            ->route('goal.contacts.import', ['tab' => 'import'])
            ->with('success', $msg);
    }

    public function template(Request $request)
    {
        $this->requireGoalPermission('import_contacts_guard');

        $format = strtolower((string) $request->query('format', 'csv'));
        if (! in_array($format, ['csv', 'xlsx'], true)) {
            $format = 'csv';
        }

        $headers = GoalContactImportService::TEMPLATE_HEADERS;
        $sample = $this->imports->templateSampleRows();
        $rows = array_merge([$headers], $sample);

        if ($format === 'csv') {
            return new StreamedResponse(function () use ($rows) {
                $out = fopen('php://output', 'w');
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="goal_contacts_template.csv"',
            ]);
        }

        $export = new class($rows) implements \Maatwebsite\Excel\Concerns\FromArray {
            public function __construct(private array $rows)
            {
            }

            public function array(): array
            {
                return $this->rows;
            }
        };

        return Excel::download($export, 'goal_contacts_template.xlsx', ExcelWriter::XLSX);
    }

    public function show(GoalContactImport $import)
    {
        $this->requireGoalPermission('import_contacts_guard');
        $this->assertImportInScope($import);

        return redirect()->route('goal.contacts.import', [
            'tab' => auth()->user()->hasCrmPermission('split_contacts_guard') ? 'assign' : 'import',
            'batch' => $import->id,
        ]);
    }

    public function errors(GoalContactImport $import)
    {
        $this->requireGoalPermission('import_contacts_guard');
        $this->assertImportInScope($import);

        $errorRows = $import->rows()
            ->whereIn('status', ['error', 'duplicate'])
            ->orderBy('row_number')
            ->get();

        $filename = 'goal_contacts_errors_'.$import->id.'.csv';

        return new StreamedResponse(function () use ($errorRows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['row_number', 'status', 'error_message', 'name', 'mobile', 'email', 'product', 'loan_amount', 'source', 'notes', 'branch_code']);
            foreach ($errorRows as $row) {
                $p = $row->payload ?? [];
                fputcsv($out, [
                    $row->row_number,
                    $row->status,
                    $row->error_message,
                    $p['name'] ?? '',
                    $p['mobile'] ?? '',
                    $p['email'] ?? '',
                    $p['product'] ?? '',
                    $p['loan_amount'] ?? '',
                    $p['source'] ?? '',
                    $p['notes'] ?? '',
                    $p['branch_code'] ?? '',
                ]);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function splitForm(GoalContactImport $import)
    {
        $this->requireGoalPermission('split_contacts_guard');
        $this->assertImportInScope($import);

        return redirect()->route('goal.contacts.import', [
            'tab' => 'assign',
            'batch' => $import->id,
        ]);
    }

    public function split(Request $request, GoalContactImport $import)
    {
        $this->requireGoalPermission('split_contacts_guard');
        $this->assertImportInScope($import);

        $actor = auth()->user();
        $scope = $this->scopedOrgIds();
        $canPickBranch = $this->imports->actorCanPickBranch($actor);

        $data = $request->validate([
            'method' => 'required|in:branch,cluster,vrm,custom',
            'allocation' => 'nullable|in:all,equal,percentage,counts',
            'region_id' => 'nullable|integer|exists:goal_org_units,id',
            'cluster_id' => 'nullable|integer|exists:goal_org_units,id',
            'branch_id' => 'nullable|integer|exists:goal_org_units,id',
            'vrm_id' => 'nullable|integer|exists:users,id',
            'vrm_ids' => 'nullable|array',
            'vrm_ids.*' => 'integer|exists:users,id',
            'percentages' => 'nullable|array',
            'percentages.*' => 'nullable|numeric|min:0|max:100',
            'counts' => 'nullable|array',
            'counts.*' => 'nullable|integer|min:0',
            'lead_status' => 'nullable|in:Future Prospect,Pending BM Allocation',
            'notify' => 'nullable|boolean',
            'skip_duplicates' => 'nullable|boolean',
            'create_lead' => 'nullable|boolean',
            'update_existing' => 'nullable|boolean',
            // Legacy split fields (compat)
            'mode' => 'nullable|in:even,custom,cluster,branch,vrm',
        ]);

        $method = $data['method'] ?? $data['mode'] ?? 'branch';
        if (in_array($method, ['branch', 'cluster'], true) && ! $canPickBranch) {
            return redirect()
                ->route('goal.contacts.import', ['tab' => 'assign', 'batch' => $import->id])
                ->withErrors(['method' => 'Cluster / branch assign is available to Cluster / RD+ only.']);
        }

        $result = $this->imports->confirmAssignment($import, $actor, $scope, [
            'method' => $method,
            'allocation' => $data['allocation'] ?? 'all',
            'region_id' => isset($data['region_id']) ? (int) $data['region_id'] : null,
            'cluster_id' => isset($data['cluster_id']) ? (int) $data['cluster_id'] : null,
            'branch_id' => isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            'vrm_id' => isset($data['vrm_id']) ? (int) $data['vrm_id'] : null,
            'vrm_ids' => array_map('intval', $data['vrm_ids'] ?? []),
            'percentages' => $data['percentages'] ?? [],
            'counts' => $data['counts'] ?? [],
            'lead_status' => $data['lead_status'] ?? 'Future Prospect',
            'notify' => $request->boolean('notify'),
            'skip_duplicates' => $request->boolean('skip_duplicates', true),
        ]);

        return redirect()
            ->route('goal.contacts.import', ['tab' => 'assign', 'batch' => $import->id])
            ->with('success', 'Assignment complete.')
            ->with('assign_result', $result);
    }

    public function saveDraft(Request $request, GoalContactImport $import)
    {
        $this->requireGoalPermission('split_contacts_guard');
        $this->assertImportInScope($import);

        $data = $request->validate([
            'method' => 'nullable|in:branch,cluster,vrm,custom',
            'allocation' => 'nullable|in:all,equal,percentage,counts',
            'region_id' => 'nullable|integer|exists:goal_org_units,id',
            'cluster_id' => 'nullable|integer|exists:goal_org_units,id',
            'branch_id' => 'nullable|integer|exists:goal_org_units,id',
            'vrm_id' => 'nullable|integer|exists:users,id',
            'vrm_ids' => 'nullable|array',
            'vrm_ids.*' => 'integer|exists:users,id',
            'percentages' => 'nullable|array',
            'counts' => 'nullable|array',
            'counts.*' => 'nullable|integer|min:0',
            'lead_status' => 'nullable|in:Future Prospect,Pending BM Allocation',
            'notify' => 'nullable|boolean',
            'skip_duplicates' => 'nullable|boolean',
            'create_lead' => 'nullable|boolean',
            'update_existing' => 'nullable|boolean',
        ]);

        $import->assign_draft = [
            'method' => $data['method'] ?? 'branch',
            'allocation' => $data['allocation'] ?? 'all',
            'region_id' => $data['region_id'] ?? null,
            'cluster_id' => $data['cluster_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'vrm_id' => $data['vrm_id'] ?? null,
            'vrm_ids' => $data['vrm_ids'] ?? [],
            'percentages' => $data['percentages'] ?? [],
            'counts' => $data['counts'] ?? [],
            'lead_status' => $data['lead_status'] ?? 'Future Prospect',
            'notify' => $request->boolean('notify'),
            'skip_duplicates' => $request->boolean('skip_duplicates', true),
            'create_lead' => $request->boolean('create_lead', true),
            'update_existing' => $request->boolean('update_existing', true),
            'saved_at' => now()->toDateTimeString(),
        ];
        $import->save();

        return redirect()
            ->route('goal.contacts.import', ['tab' => 'assign', 'batch' => $import->id])
            ->with('success', 'Assignment draft saved. Contacts were not assigned yet.');
    }

    protected function assertImportInScope(GoalContactImport $import): void
    {
        $scope = $this->scopedOrgIds();
        if ($scope === []) {
            abort(403, 'Import is outside your org scope.');
        }

        if ((int) $import->imported_by === (int) auth()->id()) {
            return;
        }

        if ($import->goal_org_unit_id && in_array((int) $import->goal_org_unit_id, array_map('intval', $scope), true)) {
            return;
        }

        $hasLead = $import->leads()
            ->whereIn('goal_org_unit_id', $scope)
            ->exists();

        if ($hasLead) {
            return;
        }

        abort(403, 'Import is outside your org scope.');
    }
}
