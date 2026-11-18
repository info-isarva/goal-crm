<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\Product;
use App\Services\Goal\GoalCaseAudit;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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
            'pending' => $rows->where('status', 'Pending')->count(),
            'verified' => $rows->where('status', 'Verified')->count(),
            'rejected' => $rows->where('status', 'Rejected')->count(),
        ];

        $tab = (string) $request->input('tab', 'all');
        $filtered = $this->applyFilters($rows, $request, $tab);

        $perPage = (int) ($request->input('per_page') ?: 25);
        $page = max(1, (int) $request->input('page', 1));
        $documents = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

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
            'canVerify'
        ));
    }

    public function export(Request $request): StreamedResponse
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
        ]);

        $tab = (string) $request->input('tab', 'all');
        $rows = $this->applyFilters($this->collectDocuments(), $request, $tab);
        $filename = 'document-register-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Case Reference', 'Customer', 'Document Type', 'File Name',
                'Uploaded By', 'Uploaded On', 'Verification Status', 'Reviewer', 'Branch', 'Product',
            ]);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['case_no'],
                    $row['customer_name'],
                    $row['label'],
                    $row['file_name'],
                    $row['uploaded_by'],
                    $row['uploaded_at'],
                    $row['status'] === 'Pending' ? 'Pending Verification' : $row['status'],
                    $row['reviewer'],
                    $row['branch'],
                    $row['product'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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

    public function upload(Request $request)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');

        $data = $request->validate([
            'case_id' => 'required|integer|exists:goal_loan_cases,id',
            'key' => 'required|string|max:60',
            'label' => 'required|string|max:120',
            'party' => 'nullable|in:Applicant,Co-Applicant',
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $case = GoalLoanCase::query()->findOrFail((int) $data['case_id']);
        $this->assertCaseInScope($case);

        $file = $request->file('file');
        $path = $file->store('goal/case-docs/'.$case->id, 'local');
        $party = $data['party'] ?? 'Applicant';

        $details = is_array($case->customer_details) ? $case->customer_details : [];
        $docs = array_values($details['documents'] ?? []);
        $found = false;
        foreach ($docs as &$doc) {
            if (($doc['key'] ?? '') === $data['key'] && ($doc['party'] ?? 'Applicant') === $party) {
                $doc['path'] = $path;
                $doc['file_name'] = $file->getClientOriginalName();
                $doc['uploaded_at'] = now()->toDateTimeString();
                $doc['uploaded_by'] = auth()->user()->name;
                $doc['uploaded_by_id'] = auth()->id();
                $doc['status'] = 'Pending';
                $doc['reviewer'] = null;
                $doc['rejection_reason'] = null;
                $doc['label'] = $data['label'];
                $doc['party'] = $party;
                $found = true;
                break;
            }
        }
        unset($doc);

        if (!$found) {
            $docs[] = [
                'key' => $data['key'],
                'label' => $data['label'],
                'party' => $party,
                'path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'uploaded_at' => now()->toDateTimeString(),
                'uploaded_by' => auth()->user()->name,
                'uploaded_by_id' => auth()->id(),
                'status' => 'Pending',
                'mandatory' => false,
            ];
        }

        $details['documents'] = $docs;
        $case->customer_details = $details;
        $case->save();

        GoalCaseAudit::record($case, 'document', 'Document uploaded', auth()->user(), [
            'to' => $data['label'],
            'body' => $file->getClientOriginalName(),
        ]);

        return redirect()
            ->route('goal.documents.index', ['tab' => 'pending'])
            ->with('success', 'Document uploaded (pending verification).');
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
                $path = $doc['path'] ?? ($doc['storage_path'] ?? null);
                $fileName = $doc['file_name'] ?? null;
                if (empty($path) && empty($fileName)) {
                    continue;
                }

                $status = (string) ($doc['status'] ?? 'Pending');
                if ($status === 'Uploaded') {
                    $status = 'Pending';
                }
                if (!in_array($status, ['Pending', 'Verified', 'Rejected'], true)) {
                    $status = 'Pending';
                }

                $key = (string) ($doc['key'] ?? '');
                $party = (string) ($doc['party'] ?? 'Applicant');
                $hasFile = (bool) $this->resolveGoalDisk($path);

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
                    'file_name' => $fileName,
                    'path' => $path,
                    'has_file' => $hasFile,
                    'mandatory' => !empty($doc['mandatory']),
                    'preview_url' => $hasFile
                        ? route('goal.documents.file', [$case, 'key' => $key, 'party' => $party, 'disposition' => 'inline'])
                        : null,
                    'download_url' => $hasFile
                        ? route('goal.documents.file', [$case, 'key' => $key, 'party' => $party, 'disposition' => 'attachment'])
                        : null,
                    'uploaded_by' => $doc['uploaded_by'] ?? null,
                    'uploaded_at' => $doc['uploaded_at'] ?? null,
                    'status' => $status,
                    'reviewer' => $doc['reviewer'] ?? null,
                    'rejection_reason' => $doc['rejection_reason'] ?? null,
                    'sort_at' => $doc['uploaded_at'] ?? optional($case->updated_at)->toDateTimeString(),
                ]);
            }
        }

        return $rows->sortByDesc('sort_at')->values();
    }

    protected function applyFilters(Collection $rows, Request $request, string $tab): Collection
    {
        if ($tab === 'pending') {
            $rows = $rows->where('status', 'Pending');
        } elseif ($tab === 'verified') {
            $rows = $rows->where('status', 'Verified');
        } elseif ($tab === 'rejected') {
            $rows = $rows->where('status', 'Rejected');
        }

        if ($request->filled('status') && $tab === 'all') {
            $rows = $rows->where('status', $request->input('status'));
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
