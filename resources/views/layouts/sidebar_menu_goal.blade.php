{{-- Goal Loan CRM navigation --}}
@php
    $u = auth()->user();
    $can = fn (string $g) => $u->hasCrmPermission($g);
    $goalAny = $can('vrm_calling_guard') || $can('view_leads_guard') || $can('bm_allocation_guard')
        || $can('view_loan_cases_guard') || $can('pms_verification_guard') || $can('accounts_queue_guard')
        || $can('payout_processing_guard') || $can('view_targets_guard') || $can('branch_mis_guard')
        || $can('manage_org_units_guard') || $can('revenue_ledger_guard');
@endphp

@if($goalAny)
<li class="nav-item sidebar-section"><span class="sidebar-section-label">Goal Loan CRM</span></li>

@if($can('vrm_calling_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.vrm.*') ? 'active' : '' }}" href="{{ route('goal.vrm.index') }}"><i class="bi bi-telephone-outbound"></i> <span class="nav-label">VRM Calling</span></a></li>
@endif

@if($can('view_leads_guard') || $can('create_lead_guard') || $can('channel_lead_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.leads.*') ? 'active' : '' }}" href="{{ route('goal.leads.index') }}"><i class="bi bi-people-fill"></i> <span class="nav-label">Loan Leads</span></a></li>
@endif

@if($can('bm_allocation_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.allocation.*') ? 'active' : '' }}" href="{{ route('goal.allocation.index') }}"><i class="bi bi-person-check"></i> <span class="nav-label">BM Allocation</span></a></li>
@endif

@if($can('view_loan_cases_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.cases.*') ? 'active' : '' }}" href="{{ route('goal.cases.index') }}"><i class="bi bi-briefcase-fill"></i> <span class="nav-label">Loan Cases</span></a></li>
@endif

@if($can('pms_verification_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.pms.*') ? 'active' : '' }}" href="{{ route('goal.pms.index') }}"><i class="bi bi-shield-check"></i> <span class="nav-label">PMS Queue</span></a></li>
@endif

@if($can('accounts_queue_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.accounts.*') ? 'active' : '' }}" href="{{ route('goal.accounts.index') }}"><i class="bi bi-journal-check"></i> <span class="nav-label">Accounts Queue</span></a></li>
@endif

@if($can('payout_processing_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.payouts.*') ? 'active' : '' }}" href="{{ route('goal.payouts.index') }}"><i class="bi bi-cash-stack"></i> <span class="nav-label">Payouts</span></a></li>
@endif

@if($can('view_targets_guard') || $can('manage_targets_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.targets.*') ? 'active' : '' }}" href="{{ route('goal.targets.index') }}"><i class="bi bi-bullseye"></i> <span class="nav-label">Targets</span></a></li>
@endif

@if($can('branch_mis_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.branch-mis') ? 'active' : '' }}" href="{{ route('goal.branch-mis') }}"><i class="bi bi-table"></i> <span class="nav-label">Branch MIS</span></a></li>
@endif

@if($can('revenue_ledger_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.revenue-ledger') ? 'active' : '' }}" href="{{ route('goal.revenue-ledger') }}"><i class="bi bi-currency-rupee"></i> <span class="nav-label">Revenue Ledger</span></a></li>
@endif

@if($can('manage_org_units_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('goal.org-units.*') ? 'active' : '' }}" href="{{ route('goal.org-units.index') }}"><i class="bi bi-diagram-2"></i> <span class="nav-label">Org Hierarchy</span></a></li>
@endif
@endif
