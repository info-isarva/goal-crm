{{-- Goal Loan CRM — SOW 7-group sidebar (role-scoped via guards) --}}
@php
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
    // Case-ops sub-modules — not for view-only oversight / Accounts (SOW role scope)
    $canCaseOps = $can('edit_case_workspace_guard');
    $canDocsHub = $canCaseOps || $can('bm_allocation_guard') || $can('pms_verification_guard');
    $canSanctionHub = $canCaseOps || $can('record_disbursement_guard');
    $canPostDisb = $can('pms_verification_guard') || $can('accounts_queue_guard') || $can('payout_processing_guard');
    $canPerfFin = $can('view_targets_guard') || $can('manage_targets_guard') || $can('branch_mis_guard') || $can('revenue_ledger_guard');
    $canOrg = $can('manage_org_units_guard') || $can('manage_users_guard') || $can('manage_roles_guard')
        || $can('manage_permissions_guard')
        || $can('manage_crm_organization_guard') || $can('view_crm_organization_guard')
        || $can('manage_crm_user_guard') || $can('view_crm_user_guard') || $isSuper;
    $canAuditLogs = $isSuper || $can('view_crm_activity_log_guard') || $can('manage_users_guard') || $can('manage_roles_guard');
    $canAnalytics = $isSuper || $canPerfFin;
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
@endphp

@if($isFullGoalNav)
{{-- 1. Dashboard --}}
<li class="nav-item sidebar-section"><span class="sidebar-section-label">Dashboard</span></li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <i class="bi bi-house-door"></i> <span class="nav-label">Home</span>
    </a>
</li>
@if($canAnalytics)
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.analytics') ? 'active' : '' }}" href="{{ route('goal.analytics') }}">
        <i class="bi bi-bar-chart-fill"></i> <span class="nav-label">Analytics / MIS Reports</span>
    </a>
</li>
@endif
@elseif($hasAnyGoalModule)
<li class="nav-item sidebar-section"><span class="sidebar-section-label">Goal Loan CRM</span></li>
@endif

{{-- 2. Lead Management --}}
@if($canLeadMgmt)
@if($isFullGoalNav)<li class="nav-item sidebar-section"><span class="sidebar-section-label">Lead Management</span></li>@endif

@if($can('vrm_calling_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.vrm.*') ? 'active' : '' }}" href="{{ route('goal.vrm.index') }}">
        <i class="bi bi-telephone-outbound"></i> <span class="nav-label">VRM Calling</span>
    </a>
</li>
@endif

@if($can('view_leads_guard') || $can('create_lead_guard') || $can('channel_lead_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.leads.*') ? 'active' : '' }}" href="{{ route('goal.leads.index') }}">
        <i class="bi bi-people-fill"></i> <span class="nav-label">Loan Leads</span>
    </a>
</li>
@endif

@if($can('bm_allocation_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.allocation.*') ? 'active' : '' }}" href="{{ route('goal.allocation.index') }}">
        <i class="bi bi-diagram-3"></i> <span class="nav-label">BM Allocation</span>
    </a>
</li>
@endif
@endif

{{-- 3. Loan Processing — workspace tabs deep-link into Loan Cases --}}
@if($canLoanProc)
@if($isFullGoalNav)<li class="nav-item sidebar-section"><span class="sidebar-section-label">Loan Processing</span></li>@endif
<li class="nav-item">
    <a class="nav-link {{ $casesHubActive ? 'active' : '' }}" href="{{ route('goal.cases.index') }}">
        <i class="bi bi-briefcase-fill"></i> <span class="nav-label">Loan Cases</span>
    </a>
</li>
@if($isFullGoalNav)
@if($canCaseOps)
<li class="nav-item">
    <a class="nav-link {{ $cwTabActive('visits') || request()->routeIs('goal.visits.*') || request()->routeIs('tasks.*') ? 'active' : '' }}"
       href="{{ route('goal.visits.index') }}">
        <i class="bi bi-geo-alt"></i> <span class="nav-label">Customer Visits &amp; Follow-ups</span>
    </a>
</li>
@endif
@if($canDocsHub)
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.documents.*') ? 'active' : '' }}" href="{{ route('goal.documents.index') }}">
        <i class="bi bi-file-earmark-text"></i> <span class="nav-label">Documents</span>
    </a>
</li>
@endif
@if($canCaseOps)
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.bank-processing.*') ? 'active' : '' }}" href="{{ route('goal.bank-processing.index') }}">
        <i class="bi bi-bank"></i> <span class="nav-label">Bank Processing</span>
    </a>
</li>
@endif
@if($canSanctionHub)
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.sanction-disbursement.*') || $cwTabActive('sanction') || $cwTabActive('disbursement') ? 'active' : '' }}"
       href="{{ route('goal.sanction-disbursement.index') }}">
        <i class="bi bi-award"></i> <span class="nav-label">Sanction &amp; Disbursement</span>
    </a>
</li>
@endif
@endif
@endif

{{-- 4. Post-Disbursement --}}
@if($canPostDisb)
@if($isFullGoalNav)<li class="nav-item sidebar-section"><span class="sidebar-section-label">Post-Disbursement</span></li>@endif

@if($can('pms_verification_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.pms.*') ? 'active' : '' }}" href="{{ route('goal.pms.index') }}">
        <i class="bi bi-layers"></i> <span class="nav-label">PMS Queue</span>
    </a>
</li>
@endif

@if($can('accounts_queue_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.accounts.*') ? 'active' : '' }}" href="{{ route('goal.accounts.index') }}">
        <i class="bi bi-journal-check"></i> <span class="nav-label">Accounts Queue</span>
    </a>
</li>
@endif

@if($can('payout_processing_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.payouts.*') ? 'active' : '' }}" href="{{ route('goal.payouts.index') }}">
        <i class="bi bi-cash-stack"></i> <span class="nav-label">Payout Management</span>
    </a>
</li>
@endif
@endif

{{-- 5. Performance & Finance --}}
@if($canPerfFin)
@if($isFullGoalNav)<li class="nav-item sidebar-section"><span class="sidebar-section-label">Performance &amp; Finance</span></li>@endif

@if($can('view_targets_guard') || $can('manage_targets_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.targets.*') ? 'active' : '' }}" href="{{ route('goal.targets.index') }}">
        <i class="bi bi-bullseye"></i> <span class="nav-label">Targets</span>
    </a>
</li>
@endif

@if($can('revenue_ledger_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.revenue-ledger') ? 'active' : '' }}" href="{{ route('goal.revenue-ledger') }}">
        <i class="bi bi-currency-rupee"></i> <span class="nav-label">Revenue Ledger</span>
    </a>
</li>
@endif

@if($can('branch_mis_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.branch-mis') ? 'active' : '' }}" href="{{ route('goal.branch-mis') }}">
        <i class="bi bi-grid-3x3-gap"></i> <span class="nav-label">Branch MIS</span>
    </a>
</li>
@endif
@endif

{{-- 6. Organization --}}
@if($isFullGoalNav && $canOrg)
<li class="nav-item sidebar-section"><span class="sidebar-section-label">Organization</span></li>

@if($can('manage_org_units_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.org-units.*') ? 'active' : '' }}" href="{{ route('goal.org-units.index') }}">
        <i class="bi bi-diagram-2"></i> <span class="nav-label">Org Hierarchy</span>
    </a>
</li>
@endif

@if($can('manage_crm_organization_guard') || $can('view_crm_organization_guard') || $isSuper)
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('organizations.*') || request()->routeIs('company.edit') ? 'active' : '' }}"
       href="{{ route('organizations.index') }}">
        <i class="bi bi-building"></i> <span class="nav-label">Company / Branches</span>
    </a>
</li>
@endif

@if($can('manage_users_guard') || $can('manage_roles_guard') || $can('manage_permissions_guard') || $isSuper
    || $can('manage_crm_user_guard') || $can('view_crm_user_guard') || $can('create_crm_user_guard') || $can('edit_crm_user_guard')
    || $can('manage_crm_role_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('access.settings.*') ? 'active' : '' }}"
       href="{{ route('users.index') }}">
        <i class="bi bi-people"></i> <span class="nav-label">User &amp; Role Management</span>
    </a>
</li>
@endif
@elseif(!$isFullGoalNav && $can('manage_org_units_guard'))
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('goal.org-units.*') ? 'active' : '' }}" href="{{ route('goal.org-units.index') }}">
        <i class="bi bi-diagram-2"></i> <span class="nav-label">Org Hierarchy</span>
    </a>
</li>
@endif

@if($isFullGoalNav)
{{-- 7. System --}}
<li class="nav-item sidebar-section"><span class="sidebar-section-label">System</span></li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
        <i class="bi bi-bell"></i> <span class="nav-label">Notifications</span>
    </a>
</li>
@if($canAuditLogs)
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">
        <i class="bi bi-clipboard-data"></i> <span class="nav-label">Audit Logs</span>
    </a>
</li>
@endif
@include('layouts.sidebar_menu_settings')
@endif