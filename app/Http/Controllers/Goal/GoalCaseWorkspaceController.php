<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalBankApplication;
use App\Models\GoalBankQuery;
use App\Models\GoalDisbursement;
use App\Models\GoalLoanCase;
use App\Models\GoalSanction;
use App\Models\User;
use App\Services\Goal\GoalNotify;
use App\Library\MpdfDocument;
use App\Services\Goal\GoalCaseAudit;
use App\Services\Goal\Pd01Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoalCaseWorkspaceController extends Controller
{
    use AuthorizesGoal;

    public function reassign(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('bm_allocation_guard');
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'overview')) {
            return $redirect;
        }

        $data = $request->validate([
            'owner_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string|max:500',
        ]);

        $scope = $this->scopedOrgIds();
        $owner = User::query()->findOrFail($data['owner_id']);
        if ($scope !== [] && $owner->goal_org_unit_id && !in_array((int) $owner->goal_org_unit_id, array_map('intval', $scope), true)) {
            return back()->withErrors(['owner_id' => 'Selected employee is outside your organizational scope.'])->with('cw_tab', 'overview');
        }
        if ($case->goal_org_unit_id && $owner->goal_org_unit_id && (int) $owner->goal_org_unit_id !== (int) $case->goal_org_unit_id) {
            return back()->withErrors(['owner_id' => 'Assignee must belong to the case branch.'])->with('cw_tab', 'overview');
        }

        $from = $case->owner_id;
        $case->owner_id = $owner->id;
        $case->save();

        GoalCaseAudit::record($case, 'assignment', 'Case reassigned', auth()->user(), [
            'from' => (string) $from,
            'to' => (string) $owner->id,
            'body' => $data['reason'],
            'meta' => ['reason' => $data['reason'], 'new_owner' => $owner->name],
        ]);

        $this->notifyUser($owner, 'Case reassigned to you', $case->case_no.' — '.$case->customer_name, route('goal.cases.show', $case), 'reassignment-'.$case->id.'-'.$owner->id, 'assign', $case->case_no);

        return back()->with('success', 'Case reassigned to '.$owner->name.'.')->with('cw_tab', 'overview');
    }

    public function storeApplication(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'lenders')) {
            return $redirect;
        }

        $data = $request->validate([
            'lender_name' => 'required|string|max:150',
            'application_ref' => 'nullable|string|max:100',
            'login_date' => 'nullable|date',
            'status' => 'nullable|in:Bank Login,Bank Query,Processing,Sanctioned,Rejected,Disbursed',
            'requested_amount' => 'nullable|numeric|min:0',
            'follow_up_at' => 'nullable|date',
            'banker_name' => 'nullable|string|max:150',
            'banker_contact' => 'nullable|string|max:30',
            'banker_email' => 'nullable|email|max:150',
            'notes' => 'nullable|string|max:1000',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $path = null;
        $name = null;
        if ($request->hasFile('evidence')) {
            $file = $request->file('evidence');
            $name = $file->getClientOriginalName();
            $path = $file->store('goal/bank-applications/'.$case->id, 'public');
        }

        $app = GoalBankApplication::create([
            'goal_loan_case_id' => $case->id,
            'lender_name' => $data['lender_name'],
            'application_ref' => $data['application_ref'] ?? null,
            'login_date' => $data['login_date'] ?? now()->toDateString(),
            'status' => $data['status'] ?? 'Bank Login',
            'requested_amount' => $data['requested_amount'] ?? $case->loan_amount,
            'follow_up_at' => $data['follow_up_at'] ?? null,
            'banker_name' => $data['banker_name'] ?? null,
            'banker_contact' => $data['banker_contact'] ?? null,
            'banker_email' => $data['banker_email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'evidence_path' => $path,
            'evidence_name' => $name,
            'created_by' => auth()->id(),
        ]);

        if (!$case->lender_name) {
            $case->lender_name = $app->lender_name;
            $case->application_ref = $app->application_ref;
        }

        if (in_array($case->status, ['Draft', 'Ready for Login'], true) && $case->status !== 'Bank Login') {
            // Do not auto-jump Draft; Ready for Login → Bank Login still needs explicit transition.
            // Mirror lender onto case for legacy transition context.
            $case->save();
        } else {
            $case->save();
        }

        GoalCaseAudit::record($case, 'application', 'Lender application added', auth()->user(), [
            'to' => $app->lender_name,
            'related_type' => GoalBankApplication::class,
            'related_id' => $app->id,
            'body' => $app->application_ref,
        ]);

        return back()->with('success', 'Lender application saved.')->with('cw_tab', 'lenders');
    }

    public function updateApplicationStatus(Request $request, GoalBankApplication $application)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $case = $application->loanCase;
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'lenders')) {
            return $redirect;
        }

        $data = $request->validate([
            'status' => 'required|in:Bank Login,Bank Query,Processing,Sanctioned,Rejected,Disbursed',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $from = $application->status;
        $case->loadMissing(['sanctions', 'disbursements']);
        $gate = \App\Services\Goal\GoalWorkflow::validateApplication($from, $data['status'], [
            'reason' => $data['rejection_reason'] ?? null,
            'hasActiveSanction' => $case->sanctions->contains(fn ($s) => $s->status === 'Active'),
            'verifiedDisbursement' => $case->disbursements->whereNotNull('verified_at')->isNotEmpty(),
        ]);
        if (! $gate['ok']) {
            return back()->withErrors(['status' => implode(' ', $gate['errors'])])->with('cw_tab', 'lenders');
        }

        $application->status = $data['status'];
        $application->save();

        GoalCaseAudit::record($case, 'application', 'Application status updated', auth()->user(), [
            'from' => $from,
            'to' => $data['status'],
            'related_type' => GoalBankApplication::class,
            'related_id' => $application->id,
            'body' => $data['rejection_reason'] ?? null,
        ]);

        return back()->with('success', 'Application status updated.')->with('cw_tab', 'lenders');
    }

    public function storeQuery(Request $request, GoalBankApplication $application)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $case = $application->loanCase;
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'queries')) {
            return $redirect;
        }

        $data = $request->validate([
            'details' => 'required|string|max:2000',
            'responsible_user_id' => 'required|integer|exists:users,id',
            'due_at' => 'required|date',
            'blocks_processing' => 'nullable|boolean',
            'attachment' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $query = GoalBankQuery::create([
            'query_ref' => 'BQ-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'goal_loan_case_id' => $case->id,
            'goal_bank_application_id' => $application->id,
            'details' => $data['details'],
            'responsible_user_id' => $data['responsible_user_id'],
            'raised_at' => now(),
            'due_at' => $data['due_at'],
            'status' => 'Open',
            'blocks_processing' => $request->boolean('blocks_processing', true),
            'created_by' => auth()->id(),
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('goal/bank-queries/'.$query->id, 'public');
            $query->resolution_evidence_path = $path; // holding slot until dedicated column; evidence on raise
            $query->save();
        }

        $application->status = 'Bank Query';
        $application->save();

        if (in_array($case->status, ['Bank Login', 'Processing'], true)) {
            $case->status = 'Bank Query';
            $case->bank_query = $data['details'];
            $case->bank_query_due = $data['due_at'];
            $case->save();
        }

        GoalCaseAudit::record($case, 'query', 'Bank query raised', auth()->user(), [
            'related_type' => GoalBankQuery::class,
            'related_id' => $query->id,
            'body' => Str::limit($data['details'], 200),
            'to' => $query->query_ref,
        ]);

        if ($query->responsible_user_id) {
            $this->notifyUser(
                User::find($query->responsible_user_id),
                'Bank query assigned',
                $query->query_ref.' due '.optional($query->due_at)->format('d M Y'),
                route('goal.cases.show', ['case' => $case, 'tab' => 'queries']),
                'bank_query-'.$query->id,
                'task',
                $case->case_no
            );
        }

        return back()->with('success', 'Bank query recorded.')->with('cw_tab', 'queries');
    }

    public function resolveQuery(Request $request, GoalBankQuery $query)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $case = $query->loanCase;
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'queries')) {
            return $redirect;
        }

        $data = $request->validate([
            'resolution_remarks' => 'required|string|max:2000',
            'mark_non_blocking' => 'nullable|boolean',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        if ($request->hasFile('evidence')) {
            $query->resolution_evidence_path = $request->file('evidence')->store('goal/bank-queries/'.$query->id, 'public');
        }

        $query->resolution_remarks = $data['resolution_remarks'];
        $query->resolved_by = auth()->id();
        $query->resolved_at = now();
        $query->status = 'Resolved';
        if ($request->boolean('mark_non_blocking')) {
            $query->blocks_processing = false;
        }
        $query->save();

        GoalCaseAudit::record($case, 'query', 'Bank query resolved', auth()->user(), [
            'related_type' => GoalBankQuery::class,
            'related_id' => $query->id,
            'body' => Str::limit($data['resolution_remarks'], 200),
            'from' => 'Open',
            'to' => 'Resolved',
        ]);

        $blockingOpen = $case->bankQueries()
            ->where('blocks_processing', true)
            ->where('status', '!=', 'Resolved')
            ->exists();

        if (!$blockingOpen && $case->status === 'Bank Query') {
            $case->status = 'Processing';
            $case->save();
            GoalCaseAudit::record($case, 'status', 'Case moved to Processing', auth()->user(), [
                'from' => 'Bank Query',
                'to' => 'Processing',
            ]);
        }

        return back()->with('success', 'Query resolved.')->with('cw_tab', 'queries');
    }

    public function storeSanction(Request $request, GoalBankApplication $application)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $case = $application->loanCase;
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'sanction')) {
            return $redirect;
        }

        $data = $request->validate([
            'sanction_amount' => 'required|numeric|min:0.01',
            'sanction_date' => 'required|date',
            'validity_until' => 'nullable|date|after_or_equal:sanction_date',
            'terms' => 'nullable|string|max:2000',
            'change_reason' => 'nullable|string|max:500',
            'evidence' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $file = $request->file('evidence');
        $path = $file->store('goal/sanctions/'.$case->id, 'public');

        $sanction = DB::transaction(function () use ($application, $case, $data, $file, $path) {
            $prev = $application->sanctions()->where('status', 'Active')->orderByDesc('revision')->first();
            if ($prev) {
                $prev->status = 'Superseded';
                $prev->save();
            }

            $revision = $prev ? ((int) $prev->revision + 1) : 1;

            $row = GoalSanction::create([
                'goal_loan_case_id' => $case->id,
                'goal_bank_application_id' => $application->id,
                'sanction_amount' => $data['sanction_amount'],
                'sanction_date' => $data['sanction_date'],
                'validity_until' => $data['validity_until'] ?? null,
                'status' => 'Active',
                'revision' => $revision,
                'terms' => $data['terms'] ?? null,
                'evidence_path' => $path,
                'evidence_name' => $file->getClientOriginalName(),
                'recorded_by' => auth()->id(),
                'change_reason' => $data['change_reason'] ?? ($prev ? 'Revision' : null),
            ]);

            $application->status = 'Sanctioned';
            $application->save();

            $case->sanction_amount = $data['sanction_amount'];
            $case->sanction_date = $data['sanction_date'];
            if (in_array($case->status, ['Processing', 'Bank Query', 'Bank Login'], true)) {
                $case->status = 'Sanctioned';
            }
            $case->save();

            return $row;
        });

        GoalCaseAudit::record($case, 'sanction', 'Sanction recorded', auth()->user(), [
            'to' => (string) $data['sanction_amount'],
            'related_type' => GoalSanction::class,
            'related_id' => $sanction->id,
        ]);

        return back()->with('success', 'Sanction saved.')->with('cw_tab', 'sanction');
    }

    public function storeDisbursement(Request $request, GoalLoanCase $case, Pd01Service $pd01)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);

        $data = $request->validate([
            'goal_bank_application_id' => 'nullable|integer|exists:goal_bank_applications,id',
            'goal_sanction_id' => 'nullable|integer|exists:goal_sanctions,id',
            'amount' => 'required|numeric|min:0.01',
            'disbursed_on' => 'required|date',
            'bank_reference' => 'required|string|max:100',
            'evidence_note' => 'nullable|string|max:500',
            'evidence' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,webp',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        if (!empty($data['goal_bank_application_id'])) {
            $owns = GoalBankApplication::query()
                ->where('id', $data['goal_bank_application_id'])
                ->where('goal_loan_case_id', $case->id)
                ->exists();
            if (!$owns) {
                return back()->withErrors(['goal_bank_application_id' => 'Invalid lender application.'])->with('cw_tab', 'disbursement');
            }
        }

        $file = $request->file('evidence');
        $path = $file->store('goal/disbursements/'.$case->id, 'public');

        try {
            $activeSanction = $case->sanctions()->where('status', 'Active')->orderByDesc('id')->first();
            $row = $pd01->recordPending($case, [
                'amount' => $data['amount'],
                'disbursed_on' => $data['disbursed_on'],
                'bank_reference' => $data['bank_reference'],
                'evidence_note' => ($data['evidence_note'] ?? '').' | evidence:'.$path,
                'goal_bank_application_id' => $data['goal_bank_application_id'] ?? null,
                'goal_sanction_id' => $data['goal_sanction_id'] ?? $activeSanction?->id,
                'sanction_cap' => $activeSanction?->sanction_amount ?? $case->sanction_amount,
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ], auth()->user());
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->with('cw_tab', 'disbursement');
        }

        GoalCaseAudit::record($case, 'disbursement', 'Disbursement recorded (pending verification)', auth()->user(), [
            'to' => (string) $data['amount'],
            'related_type' => GoalDisbursement::class,
            'related_id' => $row->id,
        ]);

        if ($case->owner_id && (int) $case->owner_id !== (int) auth()->id()) {
            $this->notifyUser(
                $case->owner,
                'Disbursement awaiting verification',
                $case->case_no.' — ₹ '.number_format((float) $data['amount'], 0),
                route('goal.cases.show', ['case' => $case, 'tab' => 'disbursement']),
                'disbursement_pending-'.$row->id,
                'pd01',
                $case->case_no
            );
        }

        return back()->with('success', 'Disbursement recorded — awaiting verification.')->with('cw_tab', 'disbursement');
    }

    public function verifyDisbursement(GoalDisbursement $disbursement, Pd01Service $pd01)
    {
        $this->requireGoalPermission('record_disbursement_guard');
        $case = $disbursement->loanCase;
        $this->assertCaseInScope($case);

        try {
            $pd01->verify($disbursement, auth()->user());
        } catch (\Throwable $e) {
            return back()->withErrors(['disbursement' => $e->getMessage()])->with('cw_tab', 'disbursement');
        }

        return back()->with('success', 'Disbursement verified — revenue recognized.')->with('cw_tab', 'disbursement');
    }

    /**
     * Mark document(s) as received/collected without attaching a file (checklist-first).
     */
    public function receiveDocument(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'documents')) {
            return $redirect;
        }

        $data = $request->validate([
            'key' => 'nullable|string|max:60',
            'label' => 'nullable|string|max:120',
            'party' => 'nullable|in:Applicant,Co-Applicant',
            'mandatory' => 'nullable|boolean',
            'keys' => 'nullable|array',
            'keys.*' => 'string|max:60',
            'parties' => 'nullable|array',
            'parties.*' => 'nullable|in:Applicant,Co-Applicant',
            'bulk_missing' => 'nullable|boolean',
        ]);

        $details = is_array($case->customer_details) ? $case->customer_details : [];
        $docs = array_values($details['documents'] ?? []);

        $user = auth()->user();
        $now = now()->toDateTimeString();
        $recorded = 0;

        $targets = [];
        if (! empty($data['bulk_missing'])) {
            foreach ($docs as $doc) {
                $st = \App\Services\Goal\GoalDocumentCatalog::normalizeStatus($doc);
                if (! empty($doc['mandatory'])
                    && $st !== 'Verified'
                    && ! \App\Services\Goal\GoalDocumentCatalog::isReceived($doc)) {
                    $targets[] = [
                        'key' => (string) ($doc['key'] ?? ''),
                        'label' => (string) ($doc['label'] ?? $doc['key'] ?? 'Document'),
                        'party' => (string) ($doc['party'] ?? 'Applicant'),
                        'mandatory' => true,
                    ];
                }
            }
        } elseif (! empty($data['keys']) && is_array($data['keys'])) {
            foreach (array_values($data['keys']) as $i => $key) {
                $party = $data['parties'][$i] ?? 'Applicant';
                $existing = collect($docs)->first(fn ($d) => ($d['key'] ?? '') === $key && ($d['party'] ?? 'Applicant') === $party);
                $targets[] = [
                    'key' => (string) $key,
                    'label' => (string) ($existing['label'] ?? $key),
                    'party' => (string) $party,
                    'mandatory' => (bool) ($existing['mandatory'] ?? false),
                ];
            }
        } else {
            if (empty($data['key'])) {
                return back()->withErrors(['key' => 'Document key is required.'])->with('cw_tab', 'documents');
            }
            $targets[] = [
                'key' => (string) $data['key'],
                'label' => (string) ($data['label'] ?? $data['key']),
                'party' => (string) ($data['party'] ?? 'Applicant'),
                'mandatory' => (bool) ($data['mandatory'] ?? false),
            ];
        }

        foreach ($targets as $target) {
            if ($target['key'] === '') {
                continue;
            }
            $found = false;
            foreach ($docs as &$doc) {
                if (($doc['key'] ?? '') === $target['key'] && ($doc['party'] ?? 'Applicant') === $target['party']) {
                    $doc['label'] = $doc['label'] ?? $target['label'];
                    $doc['party'] = $target['party'];
                    $doc['uploaded_at'] = $now;
                    $doc['uploaded_by'] = $user->name;
                    $doc['uploaded_by_id'] = $user->id;
                    $doc['received_at'] = $now;
                    $doc['status'] = 'Pending';
                    $doc['reviewer'] = null;
                    $doc['reviewed_at'] = null;
                    $doc['rejection_reason'] = null;
                    if (! array_key_exists('mandatory', $doc)) {
                        $doc['mandatory'] = $target['mandatory'];
                    }
                    $found = true;
                    $recorded++;
                    break;
                }
            }
            unset($doc);

            if (! $found) {
                $docs[] = [
                    'key' => $target['key'],
                    'label' => $target['label'],
                    'party' => $target['party'],
                    'uploaded_at' => $now,
                    'uploaded_by' => $user->name,
                    'uploaded_by_id' => $user->id,
                    'received_at' => $now,
                    'status' => 'Pending',
                    'mandatory' => $target['mandatory'],
                ];
                $recorded++;
            }

            GoalCaseAudit::record($case, 'document', 'Document recorded', $user, [
                'to' => $target['label'],
                'body' => 'Marked received (no file)',
            ]);
        }

        $details['documents'] = $docs;
        $case->customer_details = $details;
        $case->save();

        $msg = $recorded === 1
            ? 'Document marked as received (pending verification).'
            : $recorded.' documents marked as received (pending verification).';

        return back()->with('success', $msg)->with('cw_tab', 'documents');
    }

    /**
     * Add a checklist document (no file). Staff choose whether it is required for this case.
     */
    public function addDocument(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'documents')) {
            return $redirect;
        }

        $data = $request->validate([
            'label' => 'required|string|max:120',
            'party' => 'nullable|in:Applicant,Co-Applicant',
            'mandatory' => 'nullable|boolean',
        ]);

        $party = $data['party'] ?? 'Applicant';
        $label = trim($data['label']);
        $mandatory = $request->boolean('mandatory');
        $baseKey = Str::slug($label);
        if ($baseKey === '') {
            $baseKey = 'doc';
        }
        $baseKey = Str::limit($baseKey, 50, '');

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
            $key = Str::limit($baseKey, 46, '').'-'.$n;
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
            'body' => $mandatory ? 'Required' : 'Optional',
        ]);

        return back()->with('success', 'Document added to checklist.')->with('cw_tab', 'documents');
    }

    /**
     * Toggle whether a checklist document is required for this case.
     */
    public function updateDocumentMeta(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'documents')) {
            return $redirect;
        }

        $data = $request->validate([
            'key' => 'required|string|max:60',
            'party' => 'nullable|in:Applicant,Co-Applicant',
            'mandatory' => 'nullable|boolean',
        ]);

        $party = $data['party'] ?? 'Applicant';
        $mandatory = $request->boolean('mandatory');
        $details = is_array($case->customer_details) ? $case->customer_details : [];
        $docs = array_values($details['documents'] ?? []);
        $updated = false;
        foreach ($docs as &$doc) {
            if (($doc['key'] ?? '') === $data['key'] && ($doc['party'] ?? 'Applicant') === $party) {
                $doc['mandatory'] = $mandatory;
                $updated = true;
                break;
            }
        }
        unset($doc);

        if (! $updated) {
            return back()->withErrors(['key' => 'Document not found.'])->with('cw_tab', 'documents');
        }

        $details['documents'] = $docs;
        $case->customer_details = $details;
        $case->save();

        return back()->with('success', $mandatory ? 'Document marked required.' : 'Document marked optional.')->with('cw_tab', 'documents');
    }

    public function verifyDocument(Request $request, GoalLoanCase $case)
    {
        // Authorized reviewer: BM allocation or edit workspace (permission-based, not title).
        $user = auth()->user();
        if (!$user->hasCrmPermission('bm_allocation_guard') && !$user->hasCrmPermission('edit_case_workspace_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'documents')) {
            return $redirect;
        }

        $data = $request->validate([
            'key' => 'required|string|max:60',
            'party' => 'nullable|string|max:40',
            'action' => 'required|in:Verified,Rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($data['action'] === 'Rejected' && empty(trim((string) ($data['rejection_reason'] ?? '')))) {
            return back()->withErrors(['rejection_reason' => 'Rejection reason is mandatory.'])->with('cw_tab', 'documents');
        }

        // Only BM (or users with bm_allocation) can verify/reject — SM may upload only.
        if ($data['action'] === 'Verified' && !$user->hasCrmPermission('bm_allocation_guard') && !$user->hasCrmPermission('pms_verification_guard')) {
            // Allow Super Admin style: if they have both edit + view they can still verify when granted bm or pms.
            if (!$user->hasCrmPermission('manage_permissions_guard')) {
                return back()->withErrors(['action' => 'You are not authorized to verify documents.'])->with('cw_tab', 'documents');
            }
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
            return back()->withErrors(['key' => 'Document not found.'])->with('cw_tab', 'documents');
        }

        $details['documents'] = $docs;
        $case->customer_details = $details;
        $case->save();

        return back()->with('success', 'Document '.$data['action'].'.')->with('cw_tab', 'documents');
    }

    public function transferPms(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('pms_verification_guard');
        $this->assertCaseInScope($case);

        if ($case->status !== 'Disbursed') {
            return back()->withErrors(['pms' => 'Only Disbursed cases are eligible for PMS transfer.'])->with('cw_tab', 'overview');
        }

        $verified = $case->disbursements()->whereNotNull('verified_at')->exists();
        if (!$verified) {
            return back()->withErrors(['pms' => 'Verified disbursement required before PMS transfer.'])->with('cw_tab', 'overview');
        }

        $pending = $case->pmsItems()
            ->whereIn('status', ['Pending', 'Pending Verification', 'In Progress'])
            ->first();
        if ($pending) {
            $pending->status = 'Verified';
            $pending->verified_by = auth()->id();
            $pending->verified_at = now();
            $pending->save();
        }

        $case->status = 'PMS';
        $case->save();

        GoalCaseAudit::record($case, 'status', 'Transferred to PMS', auth()->user(), [
            'from' => 'Disbursed',
            'to' => 'PMS',
        ]);

        return redirect()
            ->route('goal.pms.index')
            ->with('success', 'Case '.$case->case_no.' transferred to PMS.');
    }

    /**
     * Upload Equifax / credit-bureau report for BM Reco proposal (private disk).
     */
    public function uploadBmRecoEquifax(Request $request, GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'recommendation')) {
            return $redirect;
        }

        $data = $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $file = $request->file('file');
        $bm = is_array($case->bm_reco) ? $case->bm_reco : [];
        $prevPath = data_get($bm, 'equifax.path');

        $path = $file->store('goal/bm-reco/'.$case->id, 'local');
        if ($prevPath && $prevPath !== $path && Storage::disk('local')->exists($prevPath)) {
            Storage::disk('local')->delete($prevPath);
        }

        $bm['equifax'] = [
            'path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_at' => now()->toDateTimeString(),
            'uploaded_by' => auth()->user()?->name,
            'uploaded_by_id' => auth()->id(),
        ];
        $case->bm_reco = $bm;
        $case->save();

        GoalCaseAudit::record($case, 'document', 'Equifax report attached to BM Reco', auth()->user(), [
            'to' => $file->getClientOriginalName(),
        ]);

        return back()->with('success', 'Equifax report attached.')->with('cw_tab', 'recommendation');
    }

    public function downloadBmRecoEquifax(GoalLoanCase $case)
    {
        $this->requireGoalPermission('view_loan_cases_guard');
        $this->assertCaseInScope($case);

        $path = data_get($case->bm_reco, 'equifax.path');
        $name = data_get($case->bm_reco, 'equifax.file_name') ?: 'equifax-report.pdf';
        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Equifax file not found.');
        }

        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';

        return Storage::disk('local')->response($path, $name, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($name).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function removeBmRecoEquifax(GoalLoanCase $case)
    {
        $this->requireGoalPermission('edit_case_workspace_guard');
        $this->assertCaseInScope($case);
        if ($redirect = $this->assertCaseWorkspaceUnlocked($case, 'recommendation')) {
            return $redirect;
        }

        $bm = is_array($case->bm_reco) ? $case->bm_reco : [];
        $path = data_get($bm, 'equifax.path');
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
        unset($bm['equifax']);
        $case->bm_reco = $bm;
        $case->save();

        GoalCaseAudit::record($case, 'document', 'Equifax report removed from BM Reco', auth()->user());

        return back()->with('success', 'Equifax report removed.')->with('cw_tab', 'recommendation');
    }

    /**
     * Export BM Reco proposal brief as PDF (client BM Reco letter layout).
     */
    public function exportBmReco(GoalLoanCase $case)
    {
        $this->requireGoalPermission('view_loan_cases_guard');
        $this->assertCaseInScope($case);

        $case->loadMissing(['owner:id,name,mobile', 'orgUnit:id,name']);

        $stamp = now()->format('Ymd_His');
        $safeNo = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $case->case_no) ?: 'case';

        return MpdfDocument::fromView(
            'goal.cases.exports.bm-reco-pdf',
            [
                'case' => $case,
                'br' => is_array($case->bm_reco) ? $case->bm_reco : [],
                'col' => is_array($case->collateral) ? $case->collateral : [],
                'loans' => is_array($case->existing_loans) ? $case->existing_loans : [],
                'exportedAt' => now(),
            ],
            'BM-Reco_'.$safeNo.'_'.$stamp.'.pdf',
            true,
            'A4'
        );
    }

    protected function assertCaseInScope(GoalLoanCase $case): void
    {
        $scope = $this->scopedOrgIds();
        if ($scope !== [] && $case->goal_org_unit_id && !in_array((int) $case->goal_org_unit_id, array_map('intval', $scope), true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function notifyUser(?User $user, string $title, string $message, string $url, string $eventKey, string $type = 'task', ?string $ref = null): void
    {
        GoalNotify::send($user, $title, $message, $url, $eventKey, $type, $ref, true);
    }
}
