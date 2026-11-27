<?php

namespace App\Services\Goal;

class GoalWorkflow
{
    public const LEAD_TRANSITIONS = [
        'New' => ['Pending BM Allocation', 'Closed / Lost'],
        'Pending BM Allocation' => ['Assigned', 'Closed / Lost'],
        // After BM allocation: sales / VRM move through contact → appointment → create case.
        'Assigned' => ['Contact Pending', 'Appointment Fixed', 'Create Case', 'Closed / Lost'],
        'Contact Pending' => ['Appointment Fixed', 'Create Case', 'Future Prospect', 'Closed / Lost'],
        'Appointment Fixed' => ['Contact Pending', 'Create Case', 'Closed / Lost'],
        'Qualified' => ['Create Case', 'Documents Pending', 'Closed / Lost'],
        'Future Prospect' => ['Contact Pending', 'Appointment Fixed', 'Closed / Lost'],
        // "Create Case" is a move-to action; stored status becomes Documents Pending when applied.
        'Create Case' => [],
        'Documents Pending' => ['Ready for Bank Login', 'Closed / Lost'],
        'Ready for Bank Login' => ['Bank Login', 'Closed / Lost'],
        'Bank Login' => ['Closed / Lost'],
        'Closed / Lost' => [],
    ];

    public const CASE_TRANSITIONS = [
        'Draft' => ['Ready for Login', 'Closed'],
        'Ready for Login' => ['Bank Login', 'Closed'],
        'Bank Login' => ['Bank Query', 'Processing', 'Closed'],
        'Bank Query' => ['Processing', 'Bank Query', 'Closed'],
        'Processing' => ['Sanctioned', 'Rejected', 'Bank Query', 'Closed'],
        'Sanctioned' => ['Disbursed', 'Closed'],
        'Disbursed' => ['PMS'],
        'PMS' => [],
        'Rejected' => ['Closed'],
        'Closed' => [],
    ];

    /** Progress tracker stage order (informational only — not clickable). */
    public const CASE_PROGRESS_STAGES = [
        'Draft',
        'Ready for Login',
        'Bank Login',
        'Bank Query',
        'Processing',
        'Sanctioned',
        'Disbursed',
        'PMS',
    ];

    public static function canLead(string $from, string $to): bool
    {
        return in_array($to, self::LEAD_TRANSITIONS[$from] ?? [], true);
    }

    public static function canCase(string $from, string $to): bool
    {
        return in_array($to, self::CASE_TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Statuses where case workspace capture is frozen.
     * Accounts / PMS / revenue adjustment remain on their own modules.
     * Disbursed still allows PD-01 partials + transfer to PMS.
     *
     * @return list<string>
     */
    public static function workspaceLockedStatuses(): array
    {
        return ['Disbursed', 'PMS', 'Rejected', 'Closed'];
    }

    public static function isWorkspaceLocked(?string $status): bool
    {
        return in_array((string) $status, self::workspaceLockedStatuses(), true);
    }

    public static function workspaceLockMessage(?string $status = null): string
    {
        $status = $status ?: 'this status';

        return "Case workspace is locked after {$status}. Sales edits are disabled; use Accounts / PMS for post-disbursement work.";
    }

    public static function validateLead(string $from, string $to, array $ctx = []): array
    {
        if ($from === 'Closed / Lost') {
            return ['ok' => false, 'errors' => ['Closed lead cannot silently reopen.']];
        }
        if (!self::canLead($from, $to)) {
            return ['ok' => false, 'errors' => ["Illegal lead transition: {$from} → {$to}"]];
        }
        $errors = [];
        if ($from === 'Pending BM Allocation' && $to === 'Assigned' && empty($ctx['assignee'])) {
            $errors[] = 'Eligible JSM / SM / SSM required.';
        }
        if ($to === 'Future Prospect' && empty($ctx['follow_up'])) {
            $errors[] = 'Next follow-up date/time required.';
        }
        if ($to === 'Qualified' && empty($ctx['consent'])) {
            $errors[] = 'Consent must be recorded.';
        }
        if (in_array($to, ['Documents Pending', 'Create Case'], true) && empty($ctx['product'])) {
            $errors[] = 'Product is required before creating a case.';
        }
        if ($to === 'Appointment Fixed' && empty($ctx['follow_up'])) {
            $errors[] = 'Appointment / follow-up date and time required.';
        }
        if ($to === 'Ready for Bank Login' && empty($ctx['docsVerified'])) {
            $errors[] = 'Mandatory documents must be uploaded and verified before Ready for Bank Login.';
        }
        if ($to === 'Closed / Lost' && empty($ctx['reason'])) {
            $errors[] = 'Closure reason required.';
        }
        return ['ok' => $errors === [], 'errors' => $errors];
    }

    public static function validateCase(string $from, string $to, array $ctx = []): array
    {
        if (!self::canCase($from, $to)) {
            return ['ok' => false, 'errors' => ["Illegal case transition: {$from} → {$to}"]];
        }
        $errors = [];
        if ($from === 'Draft' && $to === 'Ready for Login') {
            // Document checklist is deferred; only require core customer/product details.
            if (empty($ctx['caseDetailsComplete'])) {
                $errors[] = 'Customer name and product are required before Ready for Login.';
            }
            if (empty($ctx['bmRecoApproved'])) {
                $errors[] = 'BM Recommendation must be approved by Cluster before Ready for Login / Banking.';
            }
        }
        if ($from === 'Ready for Login' && $to === 'Bank Login' && empty($ctx['lender'])) {
            $errors[] = 'Lender must be selected (create a lender application).';
        }
        if ($to === 'Bank Query' && (empty($ctx['queryText']) || empty($ctx['queryDue']) || empty($ctx['queryOwner']))) {
            $errors[] = 'Query details, responsible employee and due date required.';
        }
        if ($from === 'Bank Query' && $to === 'Processing' && empty($ctx['queriesCleared'])) {
            $errors[] = 'Blocking bank queries must be resolved or formally marked non-blocking.';
        }
        if ($to === 'Sanctioned' && (empty($ctx['sancAmt']) || empty($ctx['sancDate']) || empty($ctx['sancEvidence']))) {
            $errors[] = 'Sanction amount, date and supporting evidence required.';
        }
        if (in_array($to, ['Rejected', 'Closed'], true) && empty($ctx['reason'])) {
            $errors[] = ($to === 'Closed' ? 'Closure' : 'Rejection').' reason required.';
        }
        if ($to === 'Disbursed') {
            if (empty($ctx['verifiedDisbursement'])) {
                $pd = self::validatePd01($ctx + ['from' => $from]);
                if (!$pd['ok']) {
                    $errors = array_merge($errors, $pd['errors']);
                }
            }
        }
        if ($to === 'PMS' && empty($ctx['pmsEligible'])) {
            $errors[] = 'Case is not eligible for PMS transfer.';
        }
        return ['ok' => $errors === [], 'errors' => $errors];
    }

    public static function validatePd01(array $ctx): array
    {
        $errors = [];
        if (empty($ctx['amount']) || (float) $ctx['amount'] <= 0) {
            $errors[] = 'Disbursement amount required.';
        }
        if (empty($ctx['date'])) {
            $errors[] = 'Disbursement date required.';
        }
        if (empty($ctx['ref']) || trim((string) $ctx['ref']) === '') {
            $errors[] = 'Disbursement reference required.';
        }
        if (!empty($ctx['from']) && $ctx['from'] !== 'Sanctioned') {
            $errors[] = 'PD-01 only from Sanctioned.';
        }
        return ['ok' => $errors === [], 'errors' => $errors];
    }

    /** Lender application status graph (Bank Processing). */
    public const APPLICATION_TRANSITIONS = [
        'Bank Login' => ['Bank Query', 'Processing', 'Rejected'],
        'Bank Query' => ['Processing', 'Bank Query', 'Rejected'],
        'Processing' => ['Sanctioned', 'Rejected', 'Bank Query'],
        'Sanctioned' => ['Disbursed', 'Rejected'],
        'Disbursed' => [],
        'Rejected' => [],
    ];

    public static function canApplication(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::APPLICATION_TRANSITIONS[$from] ?? [], true);
    }

    public static function validateApplication(string $from, string $to, array $ctx = []): array
    {
        if (! self::canApplication($from, $to)) {
            return ['ok' => false, 'errors' => ["Illegal application transition: {$from} → {$to}"]];
        }
        $errors = [];
        if ($to === 'Rejected' && empty(trim((string) ($ctx['reason'] ?? '')))) {
            $errors[] = 'Rejection reason required.';
        }
        if ($to === 'Sanctioned' && empty($ctx['hasActiveSanction'])) {
            $errors[] = 'Record an Active sanction before marking the application Sanctioned.';
        }
        if ($to === 'Disbursed' && empty($ctx['verifiedDisbursement'])) {
            $errors[] = 'Verify a disbursement (PD-01) before marking application Disbursed.';
        }

        return ['ok' => $errors === [], 'errors' => $errors];
    }
}
