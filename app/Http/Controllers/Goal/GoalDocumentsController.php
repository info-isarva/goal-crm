<?php

namespace App\Http\Controllers\Goal;

use App\Exports\GoalSheetExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Library\MpdfDocument;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\Product;
use App\Services\Goal\GoalCaseAudit;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalDocumentsController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('view_loan_cases_guard');

        $request->validate([
            'q' => 'nullable|string|max:150',
            'tab' => 'nullable|in:all,pending,verified,rejected',
            'branch' => 'nullable|integer',
            'case_id' => 'nullable|integer',
            'product' => 'nullable|string|max:100',
            'doc_type' => 'nullable|string|max:120',
            'status' => 'nullable|in:Pending,Verified,Rejected',
            'reviewer' => 'nullable|string|max:150',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|in:10,25,50,100',
        ]);

        $rows = $this->collectDocuments();
        $kpis = [
            'total' => $rows->count(),
            'pending' => $rows->filter(fn ($r) => ($r['status'] ?? '') === 'Pending' && ! empty($r['is_received']))->count(),
            'verified' => $rows->where('status', 'Verified')->count(),
            'rejected' => $rows->where('status', 'Rejected')->count(),
        ];

        $tab = (string) $request->input('tab', 'all');
        $filtered = $this->applyFilters($rows, $request, $tab);

        $perPage = (int) ($request->input('per_page') ?: 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->input('page', 1));
        $documents = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $documents->withQueryString();

        $scope = $this->scopedOrgIds();
        $branches = GoalOrgUnit::query()
            ->ofType('branch')
            ->active()
            ->when($scope !== [], fn ($q) => $q->whereIn('id', $scope))
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->where('product_status', 1)
            ->orderBy('product_name')
            ->get(['id', 'product_name']);

        $cases = GoalLoanCase::query()
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get(['id', 'case_no', 'customer_name', 'product']);

        $docTypes = $rows->pluck('label')->filter()->unique()->sort()->values();
        $reviewers = $rows->pluck('reviewer')->filter()->unique()->sort()->values();

        $tabs = [
            'all' => ['label' => 'All Documents', 'count' => $kpis['total'], 'icon' => 'bi-files'],
            'pending' => ['label' => 'Pending Verification', 'count' => $kpis['pending'], 'icon' => 'bi-hourglass-split'],
            'verified' => ['label' => 'Verified', 'count' => $kpis['verified'], 'icon' => 'bi-check2-circle'],
            'rejected' => ['label' => 'Rejected', 'count' => $kpis['rejected'], 'icon' => 'bi-x-circle'],
        ];

        $user = auth()->user();
        $canUpload = $user->hasCrmPermission('edit_case_workspace_guard');
        $canVerify = $user->hasCrmPermission('bm_allocation_guard')
            || $user->hasCrmPermission('pms_verification_guard')
            || $user->hasCrmPermission('manage_permissions_guard');

        return view('goal.documents.index', compact(
            'documents',
            'kpis',
            'tabs',
            'tab',
            'branches',
            'products',
            'cases',
            'docTypes',
            'reviewers',
            'canUpload',
            'canVerify',
            'perPage'
        ));
    }

    public function export(Request $request): StreamedResponse|BinaryFileResponse|\Illuminate\Http\Response
    {
        $this->requireGoalPermission('view_loan_cases_guard');

        $request->validate([
            'q' => 'nullable|string|max:150',
            'tab' => 'nullable|in:all,pending,verified,rejected',
            'branch' => 'nullable|integer',
            'case_id' => 'nullable|integer',
            'product' => 'nullable|string|max:100',
            'doc_type' => 'nullable|string|max:120',
            'status' => 'nullable|in:Pending,Verified,Rejected',
            'reviewer' => 'nullable|string|max:150',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'format' => 'nullable|in:csv,xlsx,excel,pdf',
        ]);

        $format = strtolower((string) $request->input('format', 'csv'));
        if (! in_array($format, ['csv', 'xlsx', 'excel', 'pdf'], true)) {
            $format = 'csv';
        }
        if ($format === 'excel') {
            $format = 'xlsx';
        }

        $tab = (string) $request->input('tab', 'all');
        $rows = $this->applyFilters($this->collectDocuments(), $request, $tab);
        $stamp = now()->format('Ymd-His');
        $headings = [
            'Case Reference', 'Customer', 'Document Type', 'Party', 'Required',
            'Recorded By', 'Recorded On', 'Verification Status', 'Reviewer', 'Branch', 'Product',
        ];

        $sheetRows = [];
        foreach ($rows as $row) {
            $statusLabel = $row['display_status']
                ?? ($row['status'] === 'Pending'
                    ? (! empty($row['is_received']) ? 'Pending Verification' : 'Not recorded')
                    : $row['status']);
            $sheetRows[] = [
                $row['case_no'],
                $row['customer_name'],
                $row['label'],
                $row['party'] ?? 'Applicant',
                ! empty($row['mandatory']) ? 'Yes' : 'No',
                $row['uploaded_by'],
                $row['uploaded_at'],
                $statusLabel,
                $row['reviewer'],
                $row['branch'],
                $row['product'],
            ];
        }

        if ($format === 'pdf') {
            return MpdfDocument::fromView(
                'goal.documents.exports.register-pdf',
                [
                    'headings' => $headings,
                    'rows' => $sheetRows,
                    'generatedAt' => now(),
                ],
                'document-register-'.$stamp.'.pdf',
                true,
                'A4-L'
            );
        }

        if ($format === 'xlsx') {
            return Excel::download(
                new GoalSheetExport($sheetRows, $headings, 'Documents'),
                'document-register-'.$stamp.'.xlsx',
                ExcelWriter::XLSX
            );
        }

        return response()->streamDownload(function () use ($sheetRows, $headings) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings);
            foreach ($sheetRows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'document-register-'.$stamp.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Secure document viewer / download (auth + org scope). Does not expose public storage URLs.
     */
    public function file(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('view_loan_cases_guard');
        $this->assertCaseInScope($case);

        $data = $request->validate([
            'key' => 'required|string|max:60',
            'party' => 'nullable|string|max:40',
            'disposition' => 'nullable|in:inline,attachment',
        ]);

        $doc = $this->findDocument($case, $data['key'], $data['party'] ?? 'Applicant');
        if (!$doc) {
            abort(404, 'Document not found.');
        }

        $path = $doc['path'] ?? ($doc['storage_path'] ?? null);
        $disk = $this->resolveGoalDisk($path);
        if (! $path || ! $disk) {
            abort(404, 'File not found.');
        }

        $disposition = ($data['disposition'] ?? 'inline') === 'attachment' ? 'attachment' : 'inline';
        $name = $doc['file_name'] ?? basename($path);
        $mime = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

        return Storage::disk($disk)->response($path, $name, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.addslashes($name).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * Add a checklist document for a selected case/customer (no file).
     */
    public function store(Request $request)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');

        $data = $request->validate([
            'case_id' => 'required|integer|exists:goal_loan_cases,id',
            'label' => 'required|string|max:120',
            'party' => 'nullable|in:Applicant,Co-Applicant',
            'mandatory' => 'nullable|boolean',
        ]);

        $case = GoalLoanCase::query()->findOrFail((int) $data['case_id']);
        $this->assertCaseInScope($case);

        $party = $data['party'] ?? 'Applicant';
        $label = trim($data['label']);
        $mandatory = $request->boolean('mandatory');
        $baseKey = \Illuminate\Support\Str::slug($label);
        if ($baseKey === '') {
            $baseKey = 'doc';
        }
        $baseKey = \Illuminate\Support\Str::limit($baseKey, 50, '');

        $details = is_array($case->customer_details) ? $case->customer_details : [];
        $docs = array_values($details['documents'] ?? []);
        $used = [];
        foreach ($docs as $d) {
            if (($d['party'] ?? 'Applicant') === $party) {
                $used[(string) ($d['key'] ?? '')] = true;
            }
        }
        $key = $baseKey;
        $n = 2;
        while (isset($used[$key])) {
            $key = \Illuminate\Support\Str::limit($baseKey, 46, '').'-'.$n;
            $n++;
        }

        $docs[] = [
            'key' => $key,
            'label' => $label,
            'party' => $party,
            'mandatory' => $mandatory,
            'status' => 'Pending',
        ];

        $details['documents'] = $docs;
        $case->customer_details = $details;
        $case->save();

        GoalCaseAudit::record($case, 'document', 'Document added to checklist', auth()->user(), [
            'to' => $label,
            'body' => ($mandatory ? 'Required' : 'Optional').' · '.$case->case_no,
        ]);

        return redirect()
            ->route('goal.documents.index', ['case_id' => $case->id, 'tab' => 'all'])
            ->with('success', 'Document added for '.$case->case_no.' — '.$case->customer_name.'.');
    }

    /** @deprecated Use store() checklist add instead of file upload. */
    public function upload(Request $request)
    {
        return $this->store($request);
    }

    public function verify(Request $request, GoalLoanCase $case)
    {
        $user = auth()->user();
        if (
            !$user->hasCrmPermission('bm_allocation_guard')
            && !$user->hasCrmPermission('edit_case_workspace_guard')
            && !$user->hasCrmPermission('pms_verification_guard')
            && !$user->hasCrmPermission('manage_permissions_guard')
        ) {
            abort(403, 'Unauthorized action.');
        }
        $this->assertCaseInScope($case);

        $data = $request->validate([
            'key' => 'required|string|max:60',
            'party' => 'nullable|string|max:40',
            'action' => 'required|in:Verified,Rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($data['action'] === 'Rejected' && empty(trim((string) ($data['rejection_reason'] ?? '')))) {
            return back()->withErrors(['rejection_reason' => 'Rejection reason is mandatory.']);
        }

        if (
            $data['action'] === 'Verified'
            && !$user->hasCrmPermission('bm_allocation_guard')
            && !$user->hasCrmPermission('pms_verification_guard')
            && !$user->hasCrmPermission('manage_permissions_guard')
        ) {
            return back()->withErrors(['action' => 'You are not authorized to verify documents.']);
        }

        $details = is_array($case->customer_details) ? $case->customer_details : [];
        $docs = array_values($details['documents'] ?? []);
        $party = $data['party'] ?? 'Applicant';
        $updated = false;

        foreach ($docs as &$doc) {
            if (($doc['key'] ?? '') === $data['key'] && ($doc['party'] ?? 'Applicant') === $party) {
                $from = $doc['status'] ?? 'Pending';
                $doc['status'] = $data['action'];
                $doc['reviewer'] = $user->name;
                $doc['reviewed_at'] = now()->toDateTimeString();
                $doc['rejection_reason'] = $data['action'] === 'Rejected' ? $data['rejection_reason'] : null;
                $updated = true;
                GoalCaseAudit::record($case, 'document', 'Document '.$data['action'], $user, [
                    'from' => $from,
                    'to' => $data['action'],
                    'body' => $doc['label'] ?? $data['key'],
                ]);
                break;
            }
        }
        unset($doc);

        if (!$updated) {
            return back()->withErrors(['key' => 'Document not found.']);
        }

        $details['documents'] = $docs;
        $case->customer_details = $details;
        $case->save();

        $tab = $data['action'] === 'Verified' ? 'verified' : 'rejected';

        return redirect()
            ->route('goal.documents.index', ['tab' => $tab])
            ->with('success', 'Document '.$data['action'].'.');
    }

    protected function collectDocuments(): Collection
    {
        $scope = $this->scopedOrgIds();
        $cases = GoalLoanCase::query()
            ->with(['orgUnit:id,name'])
            ->when($scope !== [], fn ($q) => $q->whereIn('goal_org_unit_id', $scope))
            ->get(['id', 'case_no', 'customer_name', 'product', 'goal_org_unit_id', 'customer_details', 'updated_at']);

        $rows = collect();
        foreach ($cases as $case) {
            foreach (array_values(data_get($case->customer_details, 'documents', []) ?: []) as $doc) {
                if (! is_array($doc) || empty($doc['key'])) {
                    continue;
                }

                $status = \App\Services\Goal\GoalDocumentCatalog::normalizeStatus($doc);
                $isReceived = \App\Services\Goal\GoalDocumentCatalog::isReceived($doc);
                $displayStatus = \App\Services\Goal\GoalDocumentCatalog::displayStatus($doc);

                $key = (string) ($doc['key'] ?? '');
                $party = (string) ($doc['party'] ?? 'Applicant');

                $rows->push([
                    'case_id' => $case->id,
                    'case_no' => $case->case_no,
                    'customer_name' => $case->customer_name,
                    'product' => $case->product,
                    'branch' => $case->orgUnit?->name,
                    'branch_id' => $case->goal_org_unit_id,
                    'key' => $key,
                    'label' => (string) ($doc['label'] ?? $doc['key'] ?? 'Document'),
                    'party' => $party,
                    'is_received' => $isReceived,
                    'mandatory' => ! empty($doc['mandatory']),
                    'uploaded_by' => $doc['uploaded_by'] ?? null,
                    'uploaded_at' => $doc['uploaded_at'] ?? ($doc['received_at'] ?? null),
                    'status' => $status,
                    'display_status' => $displayStatus,
                    'reviewer' => $doc['reviewer'] ?? null,
                    'rejection_reason' => $doc['rejection_reason'] ?? null,
                    'sort_at' => $doc['uploaded_at'] ?? ($doc['received_at'] ?? optional($case->updated_at)->toDateTimeString()),
                ]);
            }
        }

        return $rows->sortByDesc('sort_at')->values();
    }

    protected function applyFilters(Collection $rows, Request $request, string $tab): Collection
    {
        if ($tab === 'pending') {
            $rows = $rows->filter(fn ($r) => ($r['status'] ?? '') === 'Pending' && ! empty($r['is_received']));
        } elseif ($tab === 'verified') {
            $rows = $rows->where('status', 'Verified');
        } elseif ($tab === 'rejected') {
            $rows = $rows->where('status', 'Rejected');
        }

        if ($request->filled('status') && $tab === 'all') {
            $status = (string) $request->input('status');
            if ($status === 'Pending') {
                $rows = $rows->filter(fn ($r) => ($r['status'] ?? '') === 'Pending' && ! empty($r['is_received']));
            } else {
                $rows = $rows->where('status', $status);
            }
        }

        if ($request->filled('q')) {
            $q = mb_strtolower(trim((string) $request->input('q')));
            $rows = $rows->filter(function ($row) use ($q) {
                return str_contains(mb_strtolower((string) $row['case_no']), $q)
                    || str_contains(mb_strtolower((string) $row['customer_name']), $q)
                    || str_contains(mb_strtolower((string) ($row['file_name'] ?? '')), $q)
                    || str_contains(mb_strtolower((string) $row['label']), $q);
            });
        }

        if ($request->filled('branch')) {
            $rows = $rows->where('branch_id', (int) $request->input('branch'));
        }

        if ($request->filled('case_id')) {
            $rows = $rows->where('case_id', (int) $request->input('case_id'));
        }

        if ($request->filled('product')) {
            $rows = $rows->where('product', $request->input('product'));
        }

        if ($request->filled('doc_type')) {
            $rows = $rows->where('label', $request->input('doc_type'));
        }

        if ($request->filled('reviewer')) {
            $rows = $rows->where('reviewer', $request->input('reviewer'));
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $from = $request->input('date_from');
            $to = $request->input('date_to');
            $rows = $rows->filter(function ($row) use ($from, $to) {
                $raw = $row['uploaded_at'] ?? null;
                if (!$raw) {
                    return false;
                }
                try {
                    $day = \Carbon\Carbon::parse($raw)->toDateString();
                } catch (\Throwable) {
                    return false;
                }
                if ($from && $day < $from) {
                    return false;
                }
                if ($to && $day > $to) {
                    return false;
                }

                return true;
            });
        }

        return $rows->values();
    }

    protected function findDocument(GoalLoanCase $case, string $key, string $party): ?array
    {
        foreach (array_values(data_get($case->customer_details, 'documents', []) ?: []) as $doc) {
            if (($doc['key'] ?? '') === $key && ($doc['party'] ?? 'Applicant') === $party) {
                return $doc;
            }
        }

        return null;
    }

    /**
     * Prefer private local disk; fall back to legacy public for older uploads.
     */
    protected function resolveGoalDisk(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (Storage::disk('local')->exists($path)) {
            return 'local';
        }
        if (Storage::disk('public')->exists($path)) {
            return 'public';
        }

        return null;
    }

    protected function assertCaseInScope(GoalLoanCase $case): void
    {
        $scope = $this->scopedOrgIds();
        if ($scope !== [] && $case->goal_org_unit_id && !in_array((int) $case->goal_org_unit_id, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
