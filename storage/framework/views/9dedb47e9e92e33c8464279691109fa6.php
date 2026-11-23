
<?php
    $u = auth()->user();
    $can = fn (string $g) => $u->hasCrmPermission($g);
    $isSuper = (int) ($u->crm_role_type ?? -1) === 0;
    $isGoalTenant = \App\Services\Goal\GoalDashboardService::userUsesGoalDashboard($u);
    /** @var string $goalNavMode 'full' = 7 SOW groups; 'modules' = Goal ops only (classic CRM shell) */
    $goalNavMode = $goalNavMode ?? ($isGoalTenant ? 'full' : 'modules');
    $isFullGoalNav = $goalNavMode === 'full';

    $canLeadMgmt = $can('vrm_calling_guard') || $can('view_leads_guard') || $can('create_lead_guard')
        || $can('channel_lead_guard') || $can('bm_allocation_guard');
    $canLoanProc = $can('view_loan_cases_guard');
    // Case-ops sub-modules — dedicated guards, with edit_case_workspace as legacy fallback
    $canCaseOps = $can('edit_case_workspace_guard');
    $canVisitsHub = $can('customer_visits_guard') || $canCaseOps;
    $canDocsHub = $can('case_documents_guard') || $canCaseOps || $can('bm_allocation_guard') || $can('pms_verification_guard');
    $canBankHub = $can('bank_processing_guard') || $canCaseOps;
    $canSanctionHub = $can('sanction_disbursement_guard') || $canCaseOps || $can('record_disbursement_guard');
    $canPostDisb = $can('pms_verification_guard') || $can('accounts_queue_guard') || $can('payout_processing_guard');
    $canPerfFin = $can('view_targets_guard') || $can('manage_targets_guard') || $can('branch_mis_guard') || $can('revenue_ledger_guard');
    $canOrg = $can('manage_org_units_guard') || $can('manage_users_guard') || $can('manage_roles_guard')
        || $can('manage_permissions_guard') || $can('manage_company_guard')
        || $can('manage_crm_organization_guard') || $can('view_crm_organization_guard')
        || $can('manage_crm_user_guard') || $can('view_crm_user_guard') || $isSuper;
    $canProducts = $can('manage_products_guard') || $isSuper;
    $canDispositions = $can('manage_dispositions_guard') || $isSuper;
    $canSources = $can('manage_sources_guard') || $can('manage_crm_lead_source_guard') || $isSuper;
    $canPurposes = $can('manage_purposes_guard') || $isSuper;
    $canMasters = $canProducts || $canDispositions || $canSources || $canPurposes;
    $canAuditLogs = $isSuper || $can('view_audit_logs_guard') || $can('view_crm_activity_log_guard')
        || $can('manage_users_guard') || $can('manage_roles_guard');
    $canAnalytics = $isSuper || $can('goal_analytics_guard') || $can('mis_reports_guard')
        || $can('scheduled_reports_guard') || $canPerfFin;
    $hasAnyGoalModule = $canLeadMgmt || $canLoanProc || $canPostDisb || $canPerfFin || $can('manage_org_units_guard');

    $cwCase = request()->route('case');
    $cwTab = (string) request('tab', session('cw_tab', ''));
    $cwModule = (string) request('module', '');
    $cwHref = function (string $tab) use ($cwCase) {
        if ($tab === 'documents') {
            return route('goal.documents.index');
        }
        if ($tab === 'visits') {
            return route('goal.visits.index');
        }
        if (in_array($tab, ['lenders', 'queries'], true)) {
            return route('goal.bank-processing.index');
        }
        if (in_array($tab, ['sanction', 'disbursement'], true)) {
            return route('goal.sanction-disbursement.index');
        }
        if ($cwCase) {
            return route('goal.cases.show', $cwCase) . '?tab=' . urlencode($tab);
        }
        return route('goal.cases.index', ['module' => $tab]);
    };
    $cwTabActive = function (string $tab) use ($cwTab, $cwModule) {
        if ($tab === 'documents' && request()->routeIs('goal.documents.*')) {
            return true;
        }
        if ($tab === 'visits' && (request()->routeIs('goal.visits.*') || (request()->routeIs('goal.cases.show') && $cwTab === 'visits'))) {
            return true;
        }
        if (in_array($tab, ['lenders', 'queries'], true) && request()->routeIs('goal.bank-processing.*')) {
            return true;
        }
        if (in_array($tab, ['sanction', 'disbursement'], true) && request()->routeIs('goal.sanction-disbursement.*')) {
            return true;
        }
        if ($cwModule === $tab) {
            return true;
        }
        return request()->routeIs('goal.cases.show') && $cwTab === $tab;
    };
    $casesHubActive = request()->routeIs('goal.cases.*')
        && !request()->routeIs('goal.documents.*')
        && !request()->routeIs('goal.bank-processing.*')
        && !request()->routeIs('goal.sanction-disbursement.*')
        && !request()->routeIs('goal.visits.*')
        && !in_array($cwModule, ['visits', 'documents', 'lenders', 'queries', 'sanction', 'disbursement'], true)
        && !in_array($cwTab, ['documents', 'lenders', 'queries', 'sanction', 'disbursement', 'visits', 'activity'], true);
?>

<?php if($isFullGoalNav): ?>

<li class="nav-item sidebar-section"><span class="sidebar-section-label">Dashboard</span></li>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
        <i class="bi bi-house-door"></i> <span class="nav-label">Home</span>
    </a>
</li>
<?php if($can('goal_analytics_guard') || $isSuper || $canPerfFin): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.analytics') ? 'active' : ''); ?>" href="<?php echo e(route('goal.analytics')); ?>">
        <i class="bi bi-bar-chart-fill"></i> <span class="nav-label">Analytics Dashboard</span>
    </a>
</li>
<?php endif; ?>
<?php if($can('mis_reports_guard') || $isSuper || $canPerfFin): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.mis-reports', 'goal.mis-reports.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.mis-reports')); ?>">
        <i class="bi bi-file-earmark-bar-graph"></i> <span class="nav-label">MIS Reports</span>
    </a>
</li>
<?php endif; ?>
<?php if($can('scheduled_reports_guard') || $isSuper || $canPerfFin): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.scheduled-reports.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.scheduled-reports.index')); ?>">
        <i class="bi bi-calendar2-check"></i> <span class="nav-label">Scheduled Reports</span>
    </a>
</li>
<?php endif; ?>
<?php elseif($hasAnyGoalModule): ?>
<li class="nav-item sidebar-section"><span class="sidebar-section-label">Goal Loan CRM</span></li>
<?php endif; ?>


<?php if($canLeadMgmt): ?>
<?php if($isFullGoalNav): ?><li class="nav-item sidebar-section"><span class="sidebar-section-label">Lead Management</span></li><?php endif; ?>

<?php if($can('vrm_calling_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.vrm.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.vrm.index')); ?>">
        <i class="bi bi-telephone-outbound"></i> <span class="nav-label">VRM Calling</span>
    </a>
</li>
<?php endif; ?>

<?php if($can('view_leads_guard') || $can('create_lead_guard') || $can('channel_lead_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.leads.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.leads.index')); ?>">
        <i class="bi bi-people-fill"></i> <span class="nav-label">Loan Leads</span>
    </a>
</li>
<?php endif; ?>

<?php if($can('bm_allocation_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.allocation.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.allocation.index')); ?>">
        <i class="bi bi-diagram-3"></i> <span class="nav-label">BM Allocation</span>
    </a>
</li>
<?php endif; ?>
<?php endif; ?>


<?php if($canLoanProc): ?>
<?php if($isFullGoalNav): ?><li class="nav-item sidebar-section"><span class="sidebar-section-label">Loan Processing</span></li><?php endif; ?>
<li class="nav-item">
    <a class="nav-link <?php echo e($casesHubActive ? 'active' : ''); ?>" href="<?php echo e(route('goal.cases.index')); ?>">
        <i class="bi bi-briefcase-fill"></i> <span class="nav-label">Loan Cases</span>
    </a>
</li>
<?php if($isFullGoalNav): ?>
<?php if($canVisitsHub): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e($cwTabActive('visits') || request()->routeIs('goal.visits.*') || request()->routeIs('tasks.*') ? 'active' : ''); ?>"
       href="<?php echo e(route('goal.visits.index')); ?>">
        <i class="bi bi-geo-alt"></i> <span class="nav-label">Customer Visits &amp; Follow-ups</span>
    </a>
</li>
<?php endif; ?>
<?php if($canDocsHub): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.documents.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.documents.index')); ?>">
        <i class="bi bi-file-earmark-text"></i> <span class="nav-label">Documents</span>
    </a>
</li>
<?php endif; ?>
<?php if($canBankHub): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.bank-processing.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.bank-processing.index')); ?>">
        <i class="bi bi-bank"></i> <span class="nav-label">Bank Processing</span>
    </a>
</li>
<?php endif; ?>
<?php if($canSanctionHub): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.sanction-disbursement.*') || $cwTabActive('sanction') || $cwTabActive('disbursement') ? 'active' : ''); ?>"
       href="<?php echo e(route('goal.sanction-disbursement.index')); ?>">
        <i class="bi bi-award"></i> <span class="nav-label">Sanction &amp; Disbursement</span>
    </a>
</li>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>


<?php if($canPostDisb): ?>
<?php if($isFullGoalNav): ?><li class="nav-item sidebar-section"><span class="sidebar-section-label">Post-Disbursement</span></li><?php endif; ?>

<?php if($can('pms_verification_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.pms.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.pms.index')); ?>">
        <i class="bi bi-layers"></i> <span class="nav-label">PMS Queue</span>
    </a>
</li>
<?php endif; ?>

<?php if($can('accounts_queue_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.accounts.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.accounts.index')); ?>">
        <i class="bi bi-journal-check"></i> <span class="nav-label">Accounts Queue</span>
    </a>
</li>
<?php endif; ?>

<?php if($can('payout_processing_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.payouts.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.payouts.index')); ?>">
        <i class="bi bi-cash-stack"></i> <span class="nav-label">Payout Management</span>
    </a>
</li>
<?php endif; ?>
<?php endif; ?>


<?php if($canPerfFin): ?>
<?php if($isFullGoalNav): ?><li class="nav-item sidebar-section"><span class="sidebar-section-label">Performance &amp; Finance</span></li><?php endif; ?>

<?php if($can('view_targets_guard') || $can('manage_targets_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.targets.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.targets.index')); ?>">
        <i class="bi bi-bullseye"></i> <span class="nav-label">Targets</span>
    </a>
</li>
<?php endif; ?>

<?php if($can('revenue_ledger_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.revenue-ledger', 'goal.revenue-ledger.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.revenue-ledger')); ?>">
        <i class="bi bi-currency-rupee"></i> <span class="nav-label">Revenue Ledger</span>
    </a>
</li>
<?php endif; ?>

<?php if($can('branch_mis_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.branch-mis', 'goal.branch-mis.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.branch-mis')); ?>">
        <i class="bi bi-grid-3x3-gap"></i> <span class="nav-label">Branch MIS</span>
    </a>
</li>
<?php endif; ?>
<?php endif; ?>


<?php if($isFullGoalNav && $canMasters): ?>
<li class="nav-item sidebar-section"><span class="sidebar-section-label">Masters</span></li>
<?php if($canProducts): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('products.*') ? 'active' : ''); ?>" href="<?php echo e(route('products.index')); ?>">
        <i class="bi bi-box-seam"></i> <span class="nav-label">Products</span>
    </a>
</li>
<?php endif; ?>
<?php if($canDispositions): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.masters.dispositions.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.masters.dispositions.index')); ?>">
        <i class="bi bi-list-check"></i> <span class="nav-label">Dispositions</span>
    </a>
</li>
<?php endif; ?>
<?php if($canSources): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.masters.sources.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.masters.sources.index')); ?>">
        <i class="bi bi-signpost-2"></i> <span class="nav-label">Sources</span>
    </a>
</li>
<?php endif; ?>
<?php if($canPurposes): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.masters.purposes.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.masters.purposes.index')); ?>">
        <i class="bi bi-bullseye"></i> <span class="nav-label">Purposes</span>
    </a>
</li>
<?php endif; ?>
<?php elseif(!$isFullGoalNav && ($canDispositions || $canSources || $canPurposes)): ?>
<?php if($canDispositions): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.masters.dispositions.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.masters.dispositions.index')); ?>">
        <i class="bi bi-list-check"></i> <span class="nav-label">Dispositions</span>
    </a>
</li>
<?php endif; ?>
<?php if($canSources): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.masters.sources.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.masters.sources.index')); ?>">
        <i class="bi bi-signpost-2"></i> <span class="nav-label">Sources</span>
    </a>
</li>
<?php endif; ?>
<?php if($canPurposes): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.masters.purposes.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.masters.purposes.index')); ?>">
        <i class="bi bi-bullseye"></i> <span class="nav-label">Purposes</span>
    </a>
</li>
<?php endif; ?>
<?php endif; ?>


<?php if($isFullGoalNav && $canOrg): ?>
<li class="nav-item sidebar-section"><span class="sidebar-section-label">Organization</span></li>

<?php if($can('manage_org_units_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.org-units.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.org-units.index')); ?>">
        <i class="bi bi-diagram-2"></i> <span class="nav-label">Org Hierarchy</span>
    </a>
</li>
<?php endif; ?>

<?php if($can('manage_users_guard') || $can('manage_roles_guard') || $can('manage_permissions_guard') || $isSuper
    || $can('manage_crm_user_guard') || $can('view_crm_user_guard') || $can('create_crm_user_guard') || $can('edit_crm_user_guard')
    || $can('manage_crm_role_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('access.settings.*') ? 'active' : ''); ?>"
       href="<?php echo e(route('users.index')); ?>">
        <i class="bi bi-people"></i> <span class="nav-label">User &amp; Role Management</span>
    </a>
</li>
<?php endif; ?>
<?php elseif(!$isFullGoalNav && $can('manage_org_units_guard')): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('goal.org-units.*') ? 'active' : ''); ?>" href="<?php echo e(route('goal.org-units.index')); ?>">
        <i class="bi bi-diagram-2"></i> <span class="nav-label">Org Hierarchy</span>
    </a>
</li>
<?php endif; ?>

<?php if($isFullGoalNav): ?>

<li class="nav-item sidebar-section"><span class="sidebar-section-label">System</span></li>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('notifications.*') ? 'active' : ''); ?>" href="<?php echo e(route('notifications.index')); ?>">
        <i class="bi bi-bell"></i> <span class="nav-label">Notifications</span>
    </a>
</li>
<?php if($canAuditLogs): ?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs('activity-logs.*') ? 'active' : ''); ?>" href="<?php echo e(route('activity-logs.index')); ?>">
        <i class="bi bi-clipboard-data"></i> <span class="nav-label">Audit Logs</span>
    </a>
</li>
<?php endif; ?>
<?php echo $__env->make('layouts.sidebar_menu_settings', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\CEM-Goal Corporation\isarva-crm-ver2\resources\views/layouts/sidebar_menu_goal.blade.php ENDPATH**/ ?>