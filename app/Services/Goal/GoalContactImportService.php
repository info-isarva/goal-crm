<?php

namespace App\Services\Goal;

use App\Models\GoalContactImport;
use App\Models\GoalContactImportRow;
use App\Models\GoalLoanLead;
use App\Models\GoalLoanLeadAssignment;
use App\Models\GoalLoanLeadEvent;
use App\Models\GoalOrgUnit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class GoalContactImportService
{
    public const HEADER_ALIASES = [
        'name' => 'name',
        'customer name' => 'name',
        'customer' => 'name',
        'full name' => 'name',
        'mobile' => 'mobile',
        'mobile number' => 'mobile',
        'phone' => 'mobile',
        'phone number' => 'mobile',
        'contact' => 'mobile',
        'email' => 'email',
        'e mail' => 'email',
        'product' => 'product',
        'loan product' => 'product',
        'loan amount' => 'loan_amount',
        'amount' => 'loan_amount',
        'source' => 'source',
        'lead source' => 'source',
        'notes' => 'notes',
        'remarks' => 'notes',
        'comment' => 'notes',
        'branch code' => 'branch_code',
        'branch' => 'branch_code',
        'org code' => 'branch_code',
    ];

    public const TEMPLATE_HEADERS = [
        'name',
        'mobile',
        'email',
        'product',
        'loan_amount',
        'source',
        'notes',
        'branch_code',
    ];

    public function __construct(
        protected GoalPartyService $parties
    ) {
    }

    /**
     * @return list<array{0:string,1:string|int|float|null}>
     */
    public function templateSampleRows(): array
    {
        return [
            ['Asha Verma', '9876543210', 'asha@example.com', 'Home Loan', 2500000, 'Dialer', 'Warm lead', ''],
            ['Ravi Kumar', '9123456780', '', 'Personal Loan', 500000, 'Campaign', '', ''],
        ];
    }

    /**
     * @return array{extension:string,readerType:?string}
     */
    public function detectSource(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'csv');
        $readerType = match ($extension) {
            'csv' => ExcelWriter::CSV,
            'xlsx' => ExcelWriter::XLSX,
            'xls' => ExcelWriter::XLS,
            default => null,
        };

        return ['extension' => $extension, 'readerType' => $readerType];
    }

    /**
     * @param  list<int>  $scopedOrgIds
     */
    public function processUpload(
        User $actor,
        UploadedFile $file,
        array $scopedOrgIds,
        ?int $defaultOrgUnitId,
        string $defaultLeadStatus = 'Future Prospect',
        bool $allowBranchCode = true
    ): GoalContactImport {
        $meta = $this->detectSource($file);
        if ($meta['readerType'] === null && ! in_array($meta['extension'], ['csv', 'xlsx', 'xls'], true)) {
            throw ValidationException::withMessages([
                'import_file' => 'Unsupported file type. Upload CSV or Excel.',
            ]);
        }

        try {
            $sheets = Excel::toArray(null, $file, null, $meta['readerType']);
            $rows = $sheets[0] ?? [];
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'import_file' => 'Unable to read the file. Please use the sample template.',
            ]);
        }

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'import_file' => 'The file has no data rows.',
            ]);
        }

        $indexes = $this->mapHeaderIndexes($rows[0] ?? []);
        if (! isset($indexes['mobile'])) {
            throw ValidationException::withMessages([
                'import_file' => 'Required column missing: mobile (or phone).',
            ]);
        }

        $defaultOrgUnitId = $this->resolveDefaultOrgUnitId($actor, $scopedOrgIds, $defaultOrgUnitId);
        $branchMap = $this->branchCodeMap($scopedOrgIds);

        $import = GoalContactImport::create([
            'imported_by' => $actor->id,
            'goal_org_unit_id' => $defaultOrgUnitId,
            'filename' => $file->getClientOriginalName() ?: 'contacts.'.$meta['extension'],
            'source' => in_array($meta['extension'], ['csv', 'xlsx', 'xls'], true) ? $meta['extension'] : 'csv',
            'total_rows' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'status' => 'processing',
            'default_lead_status' => in_array($defaultLeadStatus, ['Future Prospect', 'Pending BM Allocation'], true)
                ? $defaultLeadStatus
                : 'Future Prospect',
        ]);

        $success = 0;
        $failed = 0;
        $total = 0;
        $seenMobiles = [];

        foreach (array_slice($rows, 1) as $offset => $raw) {
            if (! is_array($raw) || ! array_filter($raw, fn ($v) => filled($v))) {
                continue;
            }

            $total++;
            $rowNumber = $offset + 2;
            $payload = $this->extractPayload($raw, $indexes);

            try {
                $result = $this->createLeadFromRow(
                    $import,
                    $actor,
                    $payload,
                    $scopedOrgIds,
                    $defaultOrgUnitId,
                    $branchMap,
                    $allowBranchCode,
                    $seenMobiles
                );

                GoalContactImportRow::create([
                    'goal_contact_import_id' => $import->id,
                    'row_number' => $rowNumber,
                    'payload' => $payload,
                    'goal_loan_lead_id' => $result['lead']?->id,
                    'status' => $result['status'],
                    'error_message' => $result['error'],
                ]);

                if ($result['status'] === 'ok') {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                GoalContactImportRow::create([
                    'goal_contact_import_id' => $import->id,
                    'row_number' => $rowNumber,
                    'payload' => $payload,
                    'goal_loan_lead_id' => null,
                    'status' => 'error',
                    'error_message' => Str::limit($e->getMessage(), 480),
                ]);
            }
        }

        $import->total_rows = $total;
        $import->success_count = $success;
        $import->failed_count = $failed;
        $import->status = $this->resolveBatchStatus($total, $success, $failed);
        $import->save();

        return $import->fresh(['importer', 'orgUnit']);
    }

    /**
     * @param  list<int>  $scopedOrgIds
     * @param  array<string, true>  $seenMobiles
     * @return array{lead:?GoalLoanLead,status:string,error:?string}
     */
    protected function createLeadFromRow(
        GoalContactImport $import,
        User $actor,
        array $payload,
        array $scopedOrgIds,
        ?int $defaultOrgUnitId,
        array $branchMap,
        bool $allowBranchCode,
        array &$seenMobiles
    ): array {
        $mobile = GoalPartyService::normalizeMobile($payload['mobile'] ?? null);
        if (! $mobile || strlen($mobile) < 10) {
            return ['lead' => null, 'status' => 'error', 'error' => 'Valid mobile is required.'];
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $name = 'Contact '.$mobile;
        }

        if (isset($seenMobiles[$mobile])) {
            return ['lead' => null, 'status' => 'duplicate', 'error' => 'Duplicate mobile in this file.'];
        }
        $seenMobiles[$mobile] = true;

        $orgUnitId = $defaultOrgUnitId;
        $branchCode = trim((string) ($payload['branch_code'] ?? ''));
        if ($allowBranchCode && $branchCode !== '') {
            $codeKey = Str::upper($branchCode);
            if (! isset($branchMap[$codeKey])) {
                return ['lead' => null, 'status' => 'error', 'error' => 'Unknown or out-of-scope branch_code: '.$branchCode];
            }
            $orgUnitId = $branchMap[$codeKey];
        }

        if (! $orgUnitId) {
            return ['lead' => null, 'status' => 'error', 'error' => 'No branch/org unit available for this row.'];
        }

        if ($scopedOrgIds !== [] && ! in_array((int) $orgUnitId, array_map('intval', $scopedOrgIds), true)) {
            return ['lead' => null, 'status' => 'error', 'error' => 'Branch is outside your org scope.'];
        }

        $existing = GoalLoanLead::query()
            ->whereIn('goal_org_unit_id', $scopedOrgIds !== [] ? $scopedOrgIds : [$orgUnitId])
            ->where(function ($q) use ($mobile) {
                $q->where('mobile', $mobile)
                    ->orWhereHas('party', fn ($p) => $p->where('normalized_mobile', $mobile));
            })
            ->exists();

        if ($existing) {
            return ['lead' => null, 'status' => 'duplicate', 'error' => 'Lead with this mobile already exists in scope.'];
        }

        $email = trim((string) ($payload['email'] ?? ''));
        $product = trim((string) ($payload['product'] ?? ''));
        $source = trim((string) ($payload['source'] ?? '')) ?: 'Import';
        $notes = trim((string) ($payload['notes'] ?? ''));
        $loanAmount = $this->parseAmount($payload['loan_amount'] ?? null);
        $status = $import->default_lead_status ?: 'Future Prospect';
        $leadType = $status === 'Pending BM Allocation' ? 'immediate' : 'future';

        $lead = GoalLoanLead::create([
            'ref_no' => 'LD-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'customer_name' => Str::limit($name, 150, ''),
            'mobile' => $mobile,
            'email' => $email !== '' ? Str::limit($email, 150, '') : null,
            'source' => Str::limit($source, 80, ''),
            'product' => $product !== '' ? Str::limit($product, 100, '') : null,
            'loan_amount' => $loanAmount,
            'lead_type' => $leadType,
            'status' => $status,
            'notes' => $notes !== '' ? $notes : null,
            'goal_org_unit_id' => $orgUnitId,
            'goal_contact_import_id' => $import->id,
            'created_by' => $actor->id,
            'assigned_to' => null,
        ]);

        $this->parties->attachLead($lead);

        GoalLoanLeadEvent::record(
            $lead,
            'status_change',
            'Lead Imported',
            'Imported via contact batch #'.$import->id.' · Source: '.$source,
            ['from_status' => null, 'to_status' => $status, 'meta' => ['import_id' => $import->id]]
        );

        return ['lead' => $lead, 'status' => 'ok', 'error' => null];
    }

    /**
     * @param  list<int>  $vrmIds
     * @param  array<int, int>  $customCounts  user_id => count
     */
    public function splitBatch(
        GoalContactImport $import,
        User $actor,
        array $scopedOrgIds,
        string $mode,
        array $vrmIds = [],
        array $customCounts = [],
        ?int $branchId = null,
        ?int $singleVrmId = null,
        ?int $clusterId = null
    ): array {
        $leads = $import->unassignedLeads()
            ->when($scopedOrgIds !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scopedOrgIds))
            ->orderBy('id')
            ->get();

        if ($leads->isEmpty()) {
            throw ValidationException::withMessages([
                'split' => 'No unassigned contacts remain in this batch.',
            ]);
        }

        return match ($mode) {
            'even' => $this->splitEvenToVrms($leads, $actor, $scopedOrgIds, $vrmIds),
            'custom' => $this->splitCustomToVrms($leads, $actor, $scopedOrgIds, $customCounts),
            'cluster' => $this->assignAllToCluster($leads, $actor, $scopedOrgIds, $clusterId),
            'branch' => $this->assignAllToBranch($leads, $actor, $scopedOrgIds, $branchId),
            'vrm' => $this->assignAllToOneVrm($leads, $actor, $scopedOrgIds, $singleVrmId),
            default => throw ValidationException::withMessages(['mode' => 'Invalid split mode.']),
        };
    }

    /**
     * Confirm assignment from Assign tab. Only valid (imported ok) unassigned leads are touched.
     * Invalid / duplicate import rows are never assigned.
     *
     * @param  array<string, mixed>  $config
     * @param  list<int>  $scopedOrgIds
     * @return array{assigned:int,skipped:int,failed:int,mode:string,valid:int}
     */
    public function confirmAssignment(
        GoalContactImport $import,
        User $actor,
        array $scopedOrgIds,
        array $config
    ): array {
        $method = (string) ($config['method'] ?? 'branch');
        $allocation = (string) ($config['allocation'] ?? 'all');
        $leadStatus = (string) ($config['lead_status'] ?? 'Future Prospect');
        if (! in_array($leadStatus, ['Future Prospect', 'Pending BM Allocation', 'Assigned'], true)) {
            $leadStatus = 'Future Prospect';
        }
        $notify = (bool) ($config['notify'] ?? false);

        $duplicateCount = $import->rows()->where('status', 'duplicate')->count();
        $invalidCount = $import->rows()->where('status', 'error')->count();
        // Invalid never assigned; duplicates are excluded from the assignable lead pool.
        $skipped = $duplicateCount + $invalidCount;

        $leads = $import->unassignedLeads()
            ->when($scopedOrgIds !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scopedOrgIds))
            ->orderBy('id')
            ->get();

        $valid = $leads->count();
        if ($valid === 0) {
            throw ValidationException::withMessages([
                'assign' => 'No valid unassigned contacts remain in this batch.',
            ]);
        }

        // Stamp destination org when provided (region/cluster/branch cascade).
        $branchId = isset($config['branch_id']) ? (int) $config['branch_id'] : null;
        $clusterId = isset($config['cluster_id']) ? (int) $config['cluster_id'] : null;

        $assigned = 0;
        $failed = 0;

        try {
            if ($method === 'branch') {
                if (! $branchId) {
                    throw ValidationException::withMessages(['branch_id' => 'Select a branch.']);
                }
                $result = $this->assignAllToBranch($leads, $actor, $scopedOrgIds, $branchId);
                $assigned = (int) ($result['assigned'] ?? 0);
                $this->applyLeadStatus($leads, $leadStatus);
            } elseif ($method === 'cluster') {
                if (! $clusterId) {
                    throw ValidationException::withMessages(['cluster_id' => 'Select a cluster.']);
                }
                $result = $this->assignAllToCluster($leads, $actor, $scopedOrgIds, $clusterId);
                $assigned = (int) ($result['assigned'] ?? 0);
                $this->applyLeadStatus($leads, $leadStatus);
            } elseif ($method === 'vrm') {
                $vrmId = isset($config['vrm_id']) ? (int) $config['vrm_id'] : 0;
                if (! $vrmId) {
                    throw ValidationException::withMessages(['vrm_id' => 'Select a VRM.']);
                }
                if ($branchId) {
                    foreach ($leads as $lead) {
                        $lead->goal_org_unit_id = $branchId;
                        $lead->save();
                    }
                }
                $result = $this->assignAllToOneVrm($leads, $actor, $scopedOrgIds, $vrmId, $notify, $leadStatus);
                $assigned = (int) ($result['assigned'] ?? 0);
            } else { // custom — equal or percentage across selected VRMs
                $vrmIds = array_values(array_filter(array_map('intval', $config['vrm_ids'] ?? [])));
                if ($vrmIds === []) {
                    throw ValidationException::withMessages(['vrm_ids' => 'Select at least one VRM for custom rules.']);
                }
                if ($branchId) {
                    foreach ($leads as $lead) {
                        $lead->goal_org_unit_id = $branchId;
                        $lead->save();
                    }
                }
                if ($allocation === 'percentage') {
                    $percents = array_map('floatval', $config['percentages'] ?? []);
                    $counts = $this->countsFromPercentages($leads->count(), $vrmIds, $percents);
                    $result = $this->splitCustomToVrms($leads, $actor, $scopedOrgIds, $counts, $notify, $leadStatus);
                } elseif ($allocation === 'counts') {
                    $rawCounts = $config['counts'] ?? [];
                    $counts = $this->countsFromCustomTotals($leads->count(), $vrmIds, $rawCounts);
                    $result = $this->splitCustomToVrms($leads, $actor, $scopedOrgIds, $counts, $notify, $leadStatus);
                } else {
                    $result = $this->splitEvenToVrms($leads, $actor, $scopedOrgIds, $vrmIds, $notify, $leadStatus);
                }
                $assigned = (int) ($result['assigned'] ?? 0);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $failed = $valid;
            $assigned = 0;
        }

        $import->assign_draft = null;
        $import->default_lead_status = $leadStatus;
        if ($branchId) {
            $import->goal_org_unit_id = $branchId;
        } elseif ($clusterId) {
            $import->goal_org_unit_id = $clusterId;
        }

        $remainingUnassigned = $import->unassignedLeads()
            ->when($scopedOrgIds !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scopedOrgIds))
            ->count();
        if ($remainingUnassigned === 0 && (int) $import->success_count > 0) {
            $import->status = 'assigned';
        }

        $import->save();

        return [
            'assigned' => $assigned,
            'skipped' => $skipped,
            'failed' => $failed,
            'mode' => $method,
            'valid' => $valid,
        ];
    }

    /**
     * @param  Collection<int, GoalLoanLead>  $leads
     */
    protected function applyLeadStatus(Collection $leads, string $status): void
    {
        foreach ($leads as $lead) {
            if ($lead->assigned_to) {
                continue;
            }
            $from = $lead->status;
            if ($from === $status) {
                continue;
            }
            $lead->status = $status;
            $lead->lead_type = $status === 'Pending BM Allocation' ? 'immediate' : 'future';
            $lead->save();
            GoalLoanLeadEvent::record(
                $lead,
                'status_change',
                'Status Changed',
                $from.' → '.$status,
                ['from_status' => $from, 'to_status' => $status]
            );
        }
    }

    /**
     * @param  list<int>  $vrmIds
     * @param  array<int|string, float|int>  $percents
     * @return array<int, int>
     */
    protected function countsFromPercentages(int $total, array $vrmIds, array $percents): array
    {
        $sum = 0.0;
        $normalized = [];
        foreach ($vrmIds as $id) {
            $p = (float) ($percents[$id] ?? 0);
            if ($p < 0) {
                $p = 0;
            }
            $normalized[$id] = $p;
            $sum += $p;
        }
        if ($sum <= 0) {
            throw ValidationException::withMessages(['percentages' => 'Enter percentages that add up to more than 0.']);
        }
        if (abs($sum - 100) > 0.5) {
            throw ValidationException::withMessages(['percentages' => 'Percentages must total 100%.']);
        }

        $counts = [];
        $allocated = 0;
        $ids = array_keys($normalized);
        $last = array_key_last($ids);
        foreach ($ids as $i => $id) {
            if ($i === $last) {
                $counts[$id] = max(0, $total - $allocated);
            } else {
                $n = (int) floor($total * ($normalized[$id] / 100));
                $counts[$id] = $n;
                $allocated += $n;
            }
        }

        return $counts;
    }

    /**
     * @param  list<int>  $vrmIds
     * @param  array<int|string, int|float|string|null>  $rawCounts
     * @return array<int, int>
     */
    protected function countsFromCustomTotals(int $total, array $vrmIds, array $rawCounts): array
    {
        $counts = [];
        $sum = 0;
        foreach ($vrmIds as $id) {
            $n = (int) ($rawCounts[$id] ?? 0);
            if ($n < 0) {
                $n = 0;
            }
            $counts[$id] = $n;
            $sum += $n;
        }

        if ($sum === 0) {
            throw ValidationException::withMessages([
                'counts' => 'Enter contact counts for the selected VRMs.',
            ]);
        }

        if ($sum !== $total) {
            throw ValidationException::withMessages([
                'counts' => 'Custom split must total exactly '.$total.' contacts (currently '.$sum.').',
            ]);
        }

        return $counts;
    }

    /**
     * @return array{total:int,valid:int,duplicates:int,invalid:int,unassigned:int}
     */
    public function batchSummary(GoalContactImport $import, array $scopedOrgIds = []): array
    {
        $duplicates = $import->rows()->where('status', 'duplicate')->count();
        $invalid = $import->rows()->where('status', 'error')->count();
        $unassigned = $import->unassignedLeads()
            ->when($scopedOrgIds !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scopedOrgIds))
            ->count();

        return [
            'total' => (int) $import->total_rows,
            'valid' => (int) $import->success_count,
            'duplicates' => $duplicates,
            'invalid' => $invalid,
            'unassigned' => $unassigned,
        ];
    }

    /**
     * @param  list<int>  $scopedOrgIds
     * @return Collection<int, User>
     */
    public function vrmsInScope(array $scopedOrgIds, ?int $branchId = null): Collection
    {
        $branchIds = $scopedOrgIds;
        if ($branchId) {
            if ($scopedOrgIds !== [] && ! in_array($branchId, array_map('intval', $scopedOrgIds), true)) {
                return collect();
            }
            $branchIds = [$branchId];
        }

        return User::query()
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('crm_role_type', 9)
                    ->orWhere('job_role', 'vrm');
            })
            ->when($branchIds !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $branchIds))
            ->orderBy('name')
            ->get(['id', 'name', 'goal_org_unit_id', 'crm_role_type', 'job_role']);
    }

    /**
     * @param  list<int>  $scopedOrgIds
     * @return Collection<int, GoalOrgUnit>
     */
    public function branchesInScope(array $scopedOrgIds): Collection
    {
        return GoalOrgUnit::query()
            ->ofType(GoalOrgUnit::TYPE_BRANCH)
            ->active()
            ->when($scopedOrgIds !== [], fn ($q) => $q->whereIn('id', $scopedOrgIds))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'parent_id', 'type']);
    }

    /**
     * @param  list<int>  $scopedOrgIds
     * @return Collection<int, GoalOrgUnit>
     */
    public function clustersInScope(array $scopedOrgIds): Collection
    {
        return GoalOrgUnit::query()
            ->ofType(GoalOrgUnit::TYPE_CLUSTER)
            ->active()
            ->when($scopedOrgIds !== [], function ($q) use ($scopedOrgIds) {
                $q->where(function ($inner) use ($scopedOrgIds) {
                    $inner->whereIn('id', $scopedOrgIds)
                        ->orWhereHas('children', fn ($c) => $c->whereIn('id', $scopedOrgIds));
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'parent_id', 'type']);
    }

    /**
     * Org picker for Cluster / RD / MD+: regions, clusters, and branches in scope.
     *
     * @param  list<int>  $scopedOrgIds
     * @return array{regions:Collection<int, GoalOrgUnit>, clusters:Collection<int, GoalOrgUnit>, branches:Collection<int, GoalOrgUnit>}
     */
    public function orgOptionsInScope(array $scopedOrgIds): array
    {
        $base = GoalOrgUnit::query()
            ->active()
            ->when($scopedOrgIds !== [], fn ($q) => $q->whereIn('id', $scopedOrgIds))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'parent_id', 'type']);

        $branches = $base->where('type', GoalOrgUnit::TYPE_BRANCH)->values();
        $clusters = $base->where('type', GoalOrgUnit::TYPE_CLUSTER)->values();
        $regions = $base->where('type', GoalOrgUnit::TYPE_REGION)->values();

        // Include ancestor clusters / regions so Region → Cluster → Branch cascade works when scope is branch-only.
        if ($scopedOrgIds !== []) {
            $clusterIds = $clusters->pluck('id')
                ->merge($branches->pluck('parent_id'))
                ->filter()
                ->unique()
                ->values()
                ->all();
            if ($clusterIds !== []) {
                $extraClusters = GoalOrgUnit::query()
                    ->active()
                    ->ofType(GoalOrgUnit::TYPE_CLUSTER)
                    ->whereIn('id', $clusterIds)
                    ->get(['id', 'name', 'code', 'parent_id', 'type']);
                $clusters = $clusters->concat($extraClusters)->unique('id')->sortBy('name')->values();
            }

            $regionIds = $regions->pluck('id')
                ->merge($clusters->pluck('parent_id'))
                ->filter()
                ->unique()
                ->values()
                ->all();
            if ($regionIds !== []) {
                $extraRegions = GoalOrgUnit::query()
                    ->active()
                    ->ofType(GoalOrgUnit::TYPE_REGION)
                    ->whereIn('id', $regionIds)
                    ->get(['id', 'name', 'code', 'parent_id', 'type']);
                $regions = $regions->concat($extraRegions)->unique('id')->sortBy('name')->values();
            }
        }

        return [
            'regions' => $regions,
            'clusters' => $clusters,
            'branches' => $branches,
        ];
    }

    /**
     * @param  Collection<int, GoalLoanLead>  $leads
     * @param  list<int>  $scopedOrgIds
     * @param  list<int>  $vrmIds
     */
    protected function splitEvenToVrms(
        Collection $leads,
        User $actor,
        array $scopedOrgIds,
        array $vrmIds,
        bool $notify = true,
        ?string $leadStatus = null
    ): array {
        $vrms = $this->assertVrms($vrmIds, $scopedOrgIds);
        if ($vrms->isEmpty()) {
            throw ValidationException::withMessages(['vrm_ids' => 'Select at least one VRM.']);
        }

        $assigned = 0;
        $vrmList = $vrms->values();
        $count = $vrmList->count();

        foreach ($leads->values() as $i => $lead) {
            $vrm = $vrmList[$i % $count];
            $this->assignLeadToVrm($lead, $actor, $vrm, 'Contact split (even)', $notify, $leadStatus);
            $assigned++;
        }

        return ['assigned' => $assigned, 'mode' => 'even'];
    }

    /**
     * @param  Collection<int, GoalLoanLead>  $leads
     * @param  list<int>  $scopedOrgIds
     * @param  array<int, int>  $customCounts
     */
    protected function splitCustomToVrms(
        Collection $leads,
        User $actor,
        array $scopedOrgIds,
        array $customCounts,
        bool $notify = true,
        ?string $leadStatus = null
    ): array {
        $customCounts = array_filter(
            array_map('intval', $customCounts),
            fn ($n) => $n > 0
        );

        if ($customCounts === []) {
            throw ValidationException::withMessages(['counts' => 'Enter at least one VRM count.']);
        }

        $needed = array_sum($customCounts);
        if ($needed > $leads->count()) {
            throw ValidationException::withMessages([
                'counts' => "Requested {$needed} contacts but only {$leads->count()} unassigned.",
            ]);
        }

        $vrms = $this->assertVrms(array_map('intval', array_keys($customCounts)), $scopedOrgIds)
            ->keyBy('id');

        $queue = $leads->values();
        $cursor = 0;
        $assigned = 0;

        foreach ($customCounts as $vrmId => $count) {
            $vrm = $vrms->get((int) $vrmId);
            if (! $vrm) {
                continue;
            }
            for ($i = 0; $i < $count; $i++) {
                $lead = $queue->get($cursor);
                if (! $lead) {
                    break 2;
                }
                $this->assignLeadToVrm($lead, $actor, $vrm, 'Contact split (custom)', $notify, $leadStatus);
                $cursor++;
                $assigned++;
            }
        }

        return ['assigned' => $assigned, 'mode' => 'custom'];
    }

    /**
     * Spread unassigned leads across branches under the selected cluster (still unassigned).
     *
     * @param  Collection<int, GoalLoanLead>  $leads
     * @param  list<int>  $scopedOrgIds
     */
    protected function assignAllToCluster(Collection $leads, User $actor, array $scopedOrgIds, ?int $clusterId): array
    {
        if (! $clusterId) {
            throw ValidationException::withMessages(['cluster_id' => 'Select a cluster.']);
        }

        $cluster = GoalOrgUnit::query()
            ->ofType(GoalOrgUnit::TYPE_CLUSTER)
            ->active()
            ->find($clusterId);

        if (! $cluster) {
            throw ValidationException::withMessages(['cluster_id' => 'Cluster not found.']);
        }

        $branchIds = $this->branchesInScope($scopedOrgIds)
            ->filter(function (GoalOrgUnit $branch) use ($cluster, $scopedOrgIds) {
                $underCluster = in_array((int) $branch->id, array_map('intval', $cluster->descendantIds()), true)
                    && (int) $branch->id !== (int) $cluster->id;
                if (! $underCluster) {
                    return false;
                }
                if ($scopedOrgIds === []) {
                    return true;
                }

                return in_array((int) $branch->id, array_map('intval', $scopedOrgIds), true);
            })
            ->values();

        if ($branchIds->isEmpty()) {
            throw ValidationException::withMessages([
                'cluster_id' => 'No branches found under this cluster in your scope.',
            ]);
        }

        $moved = 0;
        $list = $branchIds->values();
        $count = $list->count();
        foreach ($leads->values() as $i => $lead) {
            $branch = $list[$i % $count];
            $from = $lead->goal_org_unit_id;
            $lead->goal_org_unit_id = $branch->id;
            $lead->save();

            GoalLoanLeadEvent::record(
                $lead,
                'note',
                'Assigned to cluster',
                'Batch assign → '.$cluster->name.' / '.$branch->name,
                [
                    'meta' => [
                        'from_org_unit_id' => $from,
                        'to_org_unit_id' => $branch->id,
                        'cluster_id' => $cluster->id,
                        'import_id' => $lead->goal_contact_import_id,
                        'by' => $actor->id,
                    ],
                ]
            );
            $moved++;
        }

        return ['assigned' => $moved, 'mode' => 'cluster', 'cluster_id' => $cluster->id];
    }

    /**
     * @param  Collection<int, GoalLoanLead>  $leads
     * @param  list<int>  $scopedOrgIds
     */
    protected function assignAllToBranch(Collection $leads, User $actor, array $scopedOrgIds, ?int $branchId): array
    {
        if (! $branchId) {
            throw ValidationException::withMessages(['branch_id' => 'Select a branch.']);
        }
        if ($scopedOrgIds !== [] && ! in_array($branchId, array_map('intval', $scopedOrgIds), true)) {
            throw ValidationException::withMessages(['branch_id' => 'Branch is outside your scope.']);
        }

        $branch = GoalOrgUnit::query()
            ->ofType(GoalOrgUnit::TYPE_BRANCH)
            ->active()
            ->find($branchId);

        if (! $branch) {
            throw ValidationException::withMessages(['branch_id' => 'Branch not found.']);
        }

        $moved = 0;
        foreach ($leads as $lead) {
            $from = $lead->goal_org_unit_id;
            $lead->goal_org_unit_id = $branch->id;
            $lead->save();

            GoalLoanLeadEvent::record(
                $lead,
                'note',
                'Assigned to branch',
                'Batch assign → '.$branch->name.($branch->code ? ' ('.$branch->code.')' : ''),
                [
                    'meta' => [
                        'from_org_unit_id' => $from,
                        'to_org_unit_id' => $branch->id,
                        'import_id' => $lead->goal_contact_import_id,
                        'by' => $actor->id,
                    ],
                ]
            );
            $moved++;
        }

        return ['assigned' => $moved, 'mode' => 'branch', 'branch_id' => $branch->id];
    }

    /**
     * @param  Collection<int, GoalLoanLead>  $leads
     * @param  list<int>  $scopedOrgIds
     */
    protected function assignAllToOneVrm(
        Collection $leads,
        User $actor,
        array $scopedOrgIds,
        ?int $vrmId,
        bool $notify = true,
        ?string $leadStatus = null
    ): array {
        if (! $vrmId) {
            throw ValidationException::withMessages(['vrm_id' => 'Select a VRM.']);
        }
        $vrm = $this->assertVrms([$vrmId], $scopedOrgIds)->first();
        if (! $vrm) {
            throw ValidationException::withMessages(['vrm_id' => 'VRM not found in your scope.']);
        }

        $assigned = 0;
        foreach ($leads as $lead) {
            $this->assignLeadToVrm($lead, $actor, $vrm, 'Contact split (single VRM)', $notify, $leadStatus);
            $assigned++;
        }

        return ['assigned' => $assigned, 'mode' => 'vrm', 'vrm_id' => $vrm->id];
    }

    protected function assignLeadToVrm(
        GoalLoanLead $lead,
        User $actor,
        User $vrm,
        string $reason,
        bool $notify = true,
        ?string $leadStatus = null
    ): void {
        $fromUser = $lead->assigned_to;
        $fromStatus = $lead->status;

        if ($vrm->goal_org_unit_id && (int) $lead->goal_org_unit_id !== (int) $vrm->goal_org_unit_id) {
            $lead->goal_org_unit_id = $vrm->goal_org_unit_id;
        }

        GoalLoanLeadAssignment::create([
            'goal_loan_lead_id' => $lead->id,
            'from_user_id' => $fromUser,
            'to_user_id' => $vrm->id,
            'assigned_by' => $actor->id,
            'action' => $fromUser ? 'reassign' : 'assign',
            'reason' => $reason,
        ]);

        $lead->assigned_to = $vrm->id;
        $lead->assigned_by = $actor->id;
        $lead->assigned_at = now();
        // Owning a VRM always sets Assigned (lead_status before assign is applied on branch/cluster paths).
        if (in_array($lead->status, ['Future Prospect', 'Pending BM Allocation', 'New', 'Assigned'], true) || $leadStatus) {
            $lead->status = 'Assigned';
        }
        $lead->save();

        GoalLoanLeadEvent::record(
            $lead,
            'allocation',
            'Assigned to '.$vrm->name,
            $reason.($fromStatus !== $lead->status ? ' · '.$fromStatus.' → '.$lead->status : ''),
            [
                'from_status' => $fromStatus,
                'to_status' => $lead->status,
                'meta' => [
                    'from_user_id' => $fromUser,
                    'to_user_id' => $vrm->id,
                    'import_split' => true,
                ],
            ]
        );

        if ($fromStatus !== $lead->status) {
            GoalLoanLeadEvent::record(
                $lead,
                'status_change',
                'Status Changed',
                $fromStatus.' → '.$lead->status,
                ['from_status' => $fromStatus, 'to_status' => $lead->status]
            );
        }

        if ($notify) {
            GoalNotify::send(
                $vrm,
                'New contact assigned',
                ($lead->ref_no ?: 'Lead').' · '.($lead->customer_name ?: 'Customer'),
                route('goal.leads.show', $lead),
                'contact-split-'.$lead->id.'-'.$vrm->id,
                'assign',
                $lead->ref_no,
                true
            );
        }
    }

    /**
     * @param  list<int>  $vrmIds
     * @param  list<int>  $scopedOrgIds
     * @return Collection<int, User>
     */
    protected function assertVrms(array $vrmIds, array $scopedOrgIds): Collection
    {
        $vrmIds = array_values(array_unique(array_filter(array_map('intval', $vrmIds))));
        if ($vrmIds === []) {
            return collect();
        }

        $vrms = $this->vrmsInScope($scopedOrgIds)->whereIn('id', $vrmIds)->values();
        if ($vrms->count() !== count($vrmIds)) {
            throw ValidationException::withMessages([
                'vrm_ids' => 'One or more selected VRMs are outside your scope.',
            ]);
        }

        return $vrms;
    }

    /**
     * @param  list<int>  $scopedOrgIds
     */
    public function resolveDefaultOrgUnitId(User $actor, array $scopedOrgIds, ?int $requested): ?int
    {
        $role = (int) ($actor->crm_role_type ?? -1);
        $own = (int) ($actor->goal_org_unit_id ?? 0);

        // BM (and branch-locked actors): always own branch.
        if ($role === 8 || strtolower((string) ($actor->job_role ?? '')) === 'bm') {
            return $own > 0 ? $own : null;
        }

        if ($requested) {
            if ($scopedOrgIds !== [] && ! in_array($requested, array_map('intval', $scopedOrgIds), true)) {
                throw ValidationException::withMessages([
                    'goal_org_unit_id' => 'Selected org unit is outside your scope.',
                ]);
            }

            return $requested;
        }

        if ($own > 0 && ($scopedOrgIds === [] || in_array($own, array_map('intval', $scopedOrgIds), true))) {
            return $own;
        }

        $options = $this->orgOptionsInScope($scopedOrgIds);

        return $options['branches']->first()?->id
            ?? $options['clusters']->first()?->id
            ?? $options['regions']->first()?->id;
    }

    public function actorCanPickBranch(User $actor): bool
    {
        $role = (int) ($actor->crm_role_type ?? -1);

        return $actor->isSuperAdmin()
            || in_array($role, [1, 4, 5, 6, 7], true)
            || in_array(strtolower((string) ($actor->job_role ?? '')), ['rd', 'cluster', 'md', 'ceo'], true);
    }

    /**
     * @param  list<int>  $scopedOrgIds
     * @return array<string, int>
     */
    protected function branchCodeMap(array $scopedOrgIds): array
    {
        $map = [];
        foreach ($this->branchesInScope($scopedOrgIds) as $branch) {
            $code = trim((string) ($branch->code ?? ''));
            if ($code !== '') {
                $map[Str::upper($code)] = (int) $branch->id;
            }
            $map[Str::upper((string) $branch->id)] = (int) $branch->id;
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int>
     */
    protected function mapHeaderIndexes(array $headerRow): array
    {
        $header = array_map(function ($h) {
            return Str::of((string) $h)->lower()->replace(['_', '-'], ' ')->trim()->toString();
        }, $headerRow);

        $indexes = [];
        foreach ($header as $i => $label) {
            if (isset(self::HEADER_ALIASES[$label])) {
                $indexes[self::HEADER_ALIASES[$label]] = $i;
            }
        }

        return $indexes;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $indexes
     * @return array<string, mixed>
     */
    protected function extractPayload(array $row, array $indexes): array
    {
        $payload = [];
        foreach (['name', 'mobile', 'email', 'product', 'loan_amount', 'source', 'notes', 'branch_code'] as $key) {
            if (! isset($indexes[$key])) {
                $payload[$key] = null;
                continue;
            }
            $val = $row[$indexes[$key]] ?? null;
            $payload[$key] = is_scalar($val) ? trim((string) $val) : null;
        }

        return $payload;
    }

    protected function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $cleaned = preg_replace('/[^\d.]/', '', (string) $value);

        return $cleaned !== '' && is_numeric($cleaned) ? (float) $cleaned : null;
    }

    protected function resolveBatchStatus(int $total, int $success, int $failed): string
    {
        if ($total === 0) {
            return 'failed';
        }
        if ($failed === 0) {
            return 'ready';
        }
        if ($success === 0) {
            return 'failed';
        }

        return 'partial';
    }
}
