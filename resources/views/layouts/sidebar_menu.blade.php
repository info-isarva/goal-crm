{{-- Sidebar menu links for after 2FA is passed --}}

<li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-house-door me-2"></i> <span class="nav-label">Home</span></a></li>
{{-- Top-level Call Logs menu item --}}

@if( Auth::user()->hasCrmPermission('create_crm_call_logs_guard') || Auth::user()->hasCrmPermission('edit_crm_call_logs_guard')  || Auth::user()->hasCrmPermission('manage_crm_call_logs_guard'))
<li class="nav-item mb-2">
    <a class="nav-link {{ request()->routeIs('calllogs.index') ? 'active' : '' }}" href="{{ route('calllogs.index') }}">
        <i class="bi bi-telephone-inbound me-2"></i> <span class="nav-label">Call Logs</span>
    </a>
</li>

@endif
@if((auth()->user()->hasCrmPermission('view_crm_leads_guard') || auth()->user()->hasCrmPermission('create_crm_leads_guard') || auth()->user()->hasCrmPermission('edit_crm_leads_guard') || auth()->user()->hasCrmPermission('delete_crm_leads_guard') || auth()->user()->hasCrmPermission('manage_crm_leads_guard')) && auth()->user()->hasCrmPermission('manage_crm_leads_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('leads.*') ? 'active' : '' }}" href="{{ route('leads.index') }}"><i class="bi bi-people-fill me-2"></i> <span class="nav-label">Leads</span></a></li>
@endif
@if((auth()->user()->hasCrmPermission('view_crm_organization_guard') || auth()->user()->hasCrmPermission('create_crm_organization_guard') || auth()->user()->hasCrmPermission('edit_crm_organization_guard') || auth()->user()->hasCrmPermission('delete_crm_organization_guard') || auth()->user()->hasCrmPermission('manage_crm_organization_guard')) && auth()->user()->hasCrmPermission('manage_crm_organization_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('organizations.*') ? 'active' : '' }}" href="{{ route('organizations.index') }}"><i class="bi bi-building me-2"></i> <span class="nav-label">Company</span></a></li>
@endif
<!-- @if((auth()->user()->hasCrmPermission('view_crm_customer_guard') || auth()->user()->hasCrmPermission('create_crm_customer_guard') || auth()->user()->hasCrmPermission('edit_crm_customer_guard') || auth()->user()->hasCrmPermission('delete_crm_customer_guard') || auth()->user()->hasCrmPermission('manage_crm_customer_guard')) && auth()->user()->hasCrmPermission('manage_crm_customer_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="bi bi-person-badge-fill me-2"></i> <span class="nav-label">Company Owner</span></a></li>
@endif -->
@if((auth()->user()->hasCrmPermission('view_crm_contact_person_guard') || auth()->user()->hasCrmPermission('create_crm_contact_person_guard') || auth()->user()->hasCrmPermission('edit_crm_contact_person_guard') || auth()->user()->hasCrmPermission('delete_crm_contact_person_guard') || auth()->user()->hasCrmPermission('manage_crm_contact_person_guard')) && auth()->user()->hasCrmPermission('manage_crm_contact_person_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('people.*') ? 'active' : '' }}" href="{{ route('people.index') }}"><i class="bi bi-person-lines-fill me-2"></i> <span class="nav-label">Contacts</span></a></li>
@endif

@if((auth()->user()->hasCrmPermission('view_crm_deals_guard') || auth()->user()->hasCrmPermission('create_crm_deals_guard') || auth()->user()->hasCrmPermission('edit_crm_deals_guard') || auth()->user()->hasCrmPermission('delete_crm_deals_guard') || auth()->user()->hasCrmPermission('manage_crm_deals_guard')) && auth()->user()->hasCrmPermission('manage_crm_deals_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('deals.*') ? 'active' : '' }}" href="{{ route('deals.index') }}"><i class="bi bi-briefcase-fill me-2"></i> <span class="nav-label">Deals</span></a></li>
@endif

@if(
    auth()->user()->hasCrmPermission('manage_crm_daily_report_guard')
    || auth()->user()->hasCrmPermission('create_crm_daily_report_guard')
    || auth()->user()->hasCrmPermission('view_crm_daily_report_guard')
    || (int) (auth()->user()->crm_role_type ?? -1) === 0
)
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('daily_reports.*') ? 'active' : '' }}" href="{{ route('daily_reports.index') }}">
        <i class="bi bi-journal-text me-2"></i> <span class="nav-label">Daily Report</span>
    </a>
</li>
@endif

<li class="nav-item"><a class="nav-link {{ request()->routeIs('tasks.index') ? 'active' : '' }}" href="{{ route('tasks.index') }}"><i class="fas fa-tasks me-2"></i> <span class="nav-label">Tasks</span></a></li>

<li class="nav-item"><a class="nav-link {{ request()->routeIs('meetings.index') ? 'active' : '' }}" href="{{ route('meetings.index') }}"><i class="fas fa-calendar-alt me-2"></i> <span class="nav-label">Meeting</span></a></li>

<!-- Products -->
 @if((auth()->user()->hasCrmPermission('view_crm_product_guard') || auth()->user()->hasCrmPermission('create_crm_product_guard') || auth()->user()->hasCrmPermission('edit_crm_product_guard') || auth()->user()->hasCrmPermission('delete_crm_product_guard') || auth()->user()->hasCrmPermission('manage_crm_product_guard')) && auth()->user()->hasCrmPermission('manage_crm_product_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><i class="bi bi-box-seam me-2"></i> <span class="nav-label">Products</span></a></li>
@endif
@if((auth()->user()->hasCrmPermission('view_crm_quotations_guard') || auth()->user()->hasCrmPermission('create_crm_quotations_guard') || auth()->user()->hasCrmPermission('edit_crm_quotations_guard') || auth()->user()->hasCrmPermission('delete_crm_quotations_guard') || auth()->user()->hasCrmPermission('manage_crm_quotations_guard')) && auth()->user()->hasCrmPermission('manage_crm_quotations_guard'))
<li class="nav-item"><a class="nav-link {{ request()->routeIs('quotations.*') && !request()->routeIs(['quotations.email_send_logs', 'quotations.whatsapp_send_logs']) ? 'active' : '' }}" href="{{ route('quotations.index') }}"><i class="bi bi-receipt me-2"></i> <span class="nav-label">Quotations</span></a></li>
@endif

@include('layouts.sidebar_menu_reports')
@include('layouts.sidebar_menu_settings')
<li class="nav-item">
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="nav-link text-danger border-0 bg-transparent" style="width:100%;text-align:left;display:flex;align-items:center;gap:0.5rem;">
            <i class="bi bi-box-arrow-right me-2"></i> <span class="nav-label">Sign out</span>
        </button>
    </form>
</li>
