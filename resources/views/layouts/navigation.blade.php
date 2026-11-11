<link href="{{ asset('css/nav-custom.css') }}?v={{ @filemtime(public_path('css/nav-custom.css')) }}" rel="stylesheet">
<nav id="mobileNavRoot" class="dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 custom-flex">
    <!-- Primary Navigation Menu -->
    <div class="-me-2 items-center desktop-hamburger">
        <button type="button" class="btn btn-warning position-fixed top-0 start-0 m-2 z-3 js-mobile-menu-toggle"
            aria-controls="mobileSidebar" aria-expanded="false" aria-label="Toggle menu"
            style="border-radius:50%;width:48px;height:48px;">
            <i class="bi bi-list fs-3"></i>
        </button>

    </div>

    <!-- spacer to create gap between fixed header and page content (visible on all sizes) -->
    <div class="header-spacer"></div>

    <!-- Mobile Topbar (visible on <=1023px) -->
    <div class="mobile-topbar w-100 d-flex align-items-center justify-content-between px-3 py-2">
        <div class="d-flex align-items-center gap-2 w-100 justify-content-between">
            <div class="d-flex align-items-center">
                <button type="button" id="mobileMenuToggle" class="btn btn-ghost p-0 d-inline-flex align-items-center js-mobile-menu-toggle" aria-controls="mobileSidebar" aria-expanded="false" aria-label="Toggle menu" style="width:40px;height:40px;border-radius:50%;position:relative;z-index:11001;">
                    <i class="bi bi-list fs-3 text-dark"></i>
                </button>
                <!-- <div class="mobile-app-title ms-2 fw-bold">Isarva CRM</div> -->
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Mobile Financial Year Dropdown -->
                <div class="d-flex align-items-center position-relative" id="mobile-fy-selector-container">
                    <!-- <div class="d-flex align-items-center px-2 py-1 rounded-3" style="background: #f9ae4d;color: #000;gap: 8px;min-width: 140px;border: 2px solid #f9a84d;"> -->
                         <div class="d-flex align-items-center px-2 py-1 rounded-3" style="background: #ffff;color: #000;gap: 8px;min-width: 140px;border: 2px solid #ffff;">
                        <i class="bi bi-calendar-event" style="font-size:1.1rem;"></i>
                        <span class="fw-semibold" style="font-size:14px;">FY:</span>
                        <button type="button" class="btn btn-light d-flex align-items-center px-2 py-1 rounded-2" id="mobileFyDropdownBtn" style="font-size:13px; font-weight:500; border:2px solid #e3eafc; box-shadow:none;">
                            Select
                            <span class="badge bg-success ms-2" style="font-size:11px;">Current</span>
                        </button>
                    </div>
                    <!-- Dropdown -->
                    <div id="mobileFyDropdown" class="position-absolute shadow-lg rounded-3" style="top:38px; left:0; min-width:180px; background:#fff; z-index:1000; display:none;">
                        <div class="px-2 pt-2 pb-1 border-bottom fw-semibold text-dark" style="font-size:13px;"><i class="bi bi-calendar-event me-2"></i> Select Financial Year</div>
                        @php
                            $fys = \App\Models\FinancialYear::orderBy('from_date','desc')->get();
                            $selectedFyId = session('selected_financial_year', null);
                        @endphp
                        @foreach($fys as $fy)
                            <div class="d-flex align-items-center justify-content-between px-2 py-1 fy-item-mobile" data-fy-id="{{ $fy->id }}" style="border-bottom:1px solid #f0f0f0;">
                                <span style="font-size:12px;">{{ $fy->from_date->format('d M, Y') }} - {{ $fy->to_date->format('d M, Y') }}</span>
                                @if($fy->active)
                                    <span class="badge bg-success me-2" style="font-size:11px;">Current</span>
                                @elseif($fy->id == $selectedFyId)
                                    <span class="badge bg-primary me-2" style="font-size:11px;">Selected</span>
                                @endif
                            </div>
                        @endforeach
                        <script>
                        document.addEventListener('DOMContentLoaded', function(){
                            document.querySelectorAll('#mobileFyDropdown .fy-item-mobile').forEach(function(el){
                                el.addEventListener('click', function(){
                                    var fyId = this.getAttribute('data-fy-id');
                                    fetch("{{ route('financial_year.select') }}", {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                        body: JSON.stringify({ financial_year_id: fyId })
                                    }).then(function(r){ return r.json(); }).then(function(j){
                                        if(j.success){ location.reload(); } else { alert('Failed: ' + (j.error || 'unknown')); }
                                    }).catch(function(e){ alert('Request failed: ' + e.message); });
                                });
                            });
                        });
                        </script>
                    </div>
                </div>
            </div>
            @php $mobileUnread = Auth::user() ? Auth::user()->unreadNotifications()->count() : 0; @endphp
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light position-relative" type="button" id="mobileNotificationDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" style="padding:8px 10px;">
                        <i class="bi bi-bell" style="font-size:1.1rem;color:#222;"></i>
                        @if($mobileUnread > 0)
                            <span id="mobileNotifCount" class="badge bg-danger position-absolute" style="font-size:10px; top:0; right:0; transform:translate(35%, -35%);">{{ $mobileUnread }}</span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="mobileNotificationDropdownBtn" style="min-width:280px; max-height:360px; overflow:auto;">
                        <li class="px-3 py-2 border-bottom fw-semibold d-flex align-items-center justify-content-between">Notifications
                            <a href="#" id="mobileMarkAllRead" class="btn btn-sm btn-link">Mark all</a>
                        </li>
                        @php $mobileNotes = Auth::user() ? Auth::user()->notifications()->orderBy('created_at','desc')->limit(10)->get() : collect(); @endphp
                        <div id="mobileNotifItems">
                        @forelse($mobileNotes as $notification)
                        @php $notifLink = $notification->data['task_link'] ?? $notification->data['related_link'] ?? ''; @endphp
                        <li class="px-3 py-2 notif-item {{ $notification->read_at ? '' : 'unread' }}" data-id="{{ $notification->id }}" data-link="{{ $notifLink }}" style="cursor:pointer;">
                            <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                            <div class="notif-body">{!! $notification->data['message'] ?? ($notification->data['title'] ?? 'Notification') !!}</div>
                        </li>
                        @empty
                        <li class="px-3 py-3 text-muted small">No notifications</li>
                        @endforelse
                        </div>
                        <li><hr class="dropdown-divider m-0"></li>
                        <li><a class="dropdown-item text-center" href="{{ route('notifications.index') }}">View all</a></li>
                    </ul>
                </div>
            </div>
                        {{-- Mobile notification bell for small screens --}}
                        <!-- <div class="mobile-topbar-right d-lg-none">
                            <ul class="topbar-right-menu">   
                                <li class="nav-item dropdown">
                                    <a class="nav-link" href="#" id="mobileNavbarDropdownNotification" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-bell"></i>
                                        <span class="notif-count" id="mobileNotifCount">0</span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileNavbarDropdownNotification" id="mobileNotifItems">
                                        <li class="dropdown-item"><a href="javascript:void(0)">No notifications</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div> -->
        </div>

        <div class="dropdown">
                <button class="btn btn-sm btn-light d-inline-flex align-items-center justify-content-center" type="button" id="mobileProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0 8px; display: flex; align-items: center;">
                    @php $avatar = Auth::user()->avatar; @endphp
                    @if($avatar)
                        <img src="{{ asset('assets/employee_profile_image/' . $avatar) }}" class="rounded-circle shadow-sm" style="width:40px; height:40px; object-fit:cover;" alt="User">
                    @else
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width:36px;height:36px;font-weight:700;font-size:16px;">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U',0,2)) }}
                        </div>
                    @endif
                    <!-- <span class="fw-semibold text-dark ms-2" style="font-size: 16px;">{{ Auth::user()->name }}</span> -->
                    <i class="bi bi-chevron-down ms-2"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileProfileDropdown">
                    <li class="px-3 py-2 user-dropdown-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;">{{ strtoupper(substr(Auth::user()->name ?? 'U',0,2)) }}</div>
                            <div>
                                <div class="fw-semibold">{{ Auth::user()->name }}</div>
                                <div class="text-muted small">{{ Auth::user()->email }}</div>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider m-0"></li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                    <li><a class="dropdown-item" href="{{ route('password.edit') }}">Change Password</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">Sign out</button>
                        </form>
                    </li>
                </ul>
            </div>
    </div>
    <div id="mobileSidebarBackdrop" class="mobile-sidebar-backdrop" hidden></div>
    <div id="mobileSidebar" class="mobile-sidebar sidebar" aria-hidden="true">
        <div class="d-flex flex-column h-100 justify-content-between pt-4 pb-3 px-3">
            <div class="sidebar-scroll flex-grow-1"
                style="overflow-y:auto;  scrollbar-width: none; scrollbar-color: #f9c74f #fffbe6; -webkit-overflow-scrolling: touch;">
                <div class="mb-4 d-flex align-items-center justify-content-center">
                <span class="fs-4 fw-bold">
                    @php $company = \App\Models\Company::current(); @endphp
                    <img src="{{ $company->logo_url }}" alt="{{ $company->name ?? 'Company Logo' }}" class="company-logo" style="height:40px;max-width:120px;object-fit:contain;">
                </span>
                {{-- <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-dismiss="offcanvas" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button> --}}
            </div>
            <ul class="nav flex-column fw-semibold sidebar-menu-gap">
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

                @if((auth()->user()->hasCrmPermission('view_crm_organization_guard') || auth()->user()->hasCrmPermission('create_crm_organization_guard') || auth()->user()->hasCrmPermission('edit_crm_organization_guard') || auth()->user()->hasCrmPermission('delete_crm_organization_guard') || auth()->user()->hasCrmPermission('manage_crm_organization_guard')) && auth()->user()->hasCrmPermission('manage_crm_organization_guard'))
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('organizations.*') ? 'active' : '' }}" href="{{ route('organizations.index') }}"><i class="bi bi-building me-2"></i> <span class="nav-label">Company</span></a></li>
                @endif
                <!-- @if((auth()->user()->hasCrmPermission('view_crm_customer_guard') || auth()->user()->hasCrmPermission('create_crm_customer_guard') || auth()->user()->hasCrmPermission('edit_crm_customer_guard') || auth()->user()->hasCrmPermission('delete_crm_customer_guard') || auth()->user()->hasCrmPermission('manage_crm_customer_guard')) && auth()->user()->hasCrmPermission('manage_crm_customer_guard'))
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="bi bi-person-badge-fill me-2"></i> <span class="nav-label">Company Owner</span></a></li>
                @endif -->
                @if((auth()->user()->hasCrmPermission('view_crm_contact_person_guard') || auth()->user()->hasCrmPermission('create_crm_contact_person_guard') || auth()->user()->hasCrmPermission('edit_crm_contact_person_guard') || auth()->user()->hasCrmPermission('delete_crm_contact_person_guard') || auth()->user()->hasCrmPermission('manage_crm_contact_person_guard')) && auth()->user()->hasCrmPermission('manage_crm_contact_person_guard'))
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('people.*') ? 'active' : '' }}" href="{{ route('people.index') }}"><i class="bi bi-person-lines-fill me-2"></i> <span class="nav-label">Contacts</span></a></li>
                @endif

                    @include('layouts.sidebar_menu_reports')

                    @include('layouts.sidebar_menu_settings')

                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="nav-link text-danger border-0 bg-transparent" style="width:100%;text-align:left;display:flex;align-items:center;gap:0.5rem;">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </li>

            </ul>
        </div>
        <div class="user-card p-3 d-flex align-items-center gap-2">
            @php
                $avatar = Auth::user()->avatar;
            @endphp
            @if($avatar)
                <img src="{{ asset('assets/employee_profile_image/' . $avatar) }}" class="rounded-circle" style="width: 50px; height:50px; object-fit:cover;" alt="User">
            @else
                <img src="{{ asset('user-thumbnail.png') }}" class="rounded-circle" style="width: 50px; height:50px; object-fit:cover;" alt="User">
            @endif
            <div>
                <div class="fw-bold">{{ Auth::user()->name }}</div>
                    @php
                        $roleId = Auth::user()->crm_role_type;
                        $roleName = null;
                        if ($roleId === 0) {
                            $roleName = 'Super Admin';
                        } elseif (isset($roles) && isset($roles[$roleId])) {
                            $roleName = $roles[$roleId];
                        } else {
                            // fallback: try to get from DB if not passed in $roles
                            $roleModel = \App\Models\Role::find($roleId);
                            $roleName = $roleModel ? ucfirst($roleModel->name) : 'Unknown';
                        }
                        $badgeClass = match(strtolower($roleName)) {
                            'super admin' => 'bg-dark',
                            'admin' => 'bg-warning text-dark',
                            'manager' => 'bg-primary',
                            'employee' => 'bg-info text-dark',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $roleName }}</span>
                </div>
            </div>
        </div>

    </div>
</nav>
<script src="{{ asset('js/nav-script.js') }}?v={{ @filemtime(public_path('js/nav-script.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Forward clicks on `.nav-label` to the parent anchor.
    // Use a small guard flag to avoid re-entrancy when we programmatically trigger the anchor click.
    document.querySelectorAll('.nav-label').forEach(function(lbl){
        lbl.style.cursor = 'pointer';
        lbl.addEventListener('click', function(e){
            var a = this.closest('a');
            if (!a) return;
            var href = a.getAttribute('href') || '';
            var isCollapse = (a.getAttribute('data-bs-toggle') === 'collapse') || (href.charAt && href.charAt(0) === '#');
            if (!isCollapse) return; // allow normal navigation

            // If we've already forwarded, ignore to prevent infinite loop
            if (a.dataset._forwarding === '1') return;

            // Programmatically trigger the anchor click so Bootstrap handles the collapse consistently
            a.dataset._forwarding = '1';
            try {
                a.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
            } catch (err) {
                try { a.click(); } catch (e) {}
            }
            // Clear the guard after short delay
            setTimeout(function(){ try { delete a.dataset._forwarding; } catch(e){} }, 400);
        });
    });
});

// Delegated handler as a fallback: capture clicks on .nav-label anywhere and toggle collapse target
document.addEventListener('click', function(ev){
    try {
        var el = ev.target;
        if (!el) return;
        // find nearest anchor that is intended to toggle a collapse (either data-bs-toggle or href '#')
        var a = el.closest('a[data-bs-toggle="collapse"], a[href^="#"]');
        if (!a) return;
        // scope to anchors inside the sidebar/nav to avoid accidental global handling
        if (!a.closest('.sidebar') && !a.closest('nav')) return;

        var href = a.getAttribute('href') || '';
        var targetSelector = a.getAttribute('data-bs-target') || (href && href.charAt && href.charAt(0) === '#' ? href : null);

        if (targetSelector) {
            var target = document.querySelector(targetSelector);
            if (target && window.bootstrap && bootstrap.Collapse) {
                bootstrap.Collapse.getOrCreateInstance(target).toggle();
                ev.preventDefault();
                return;
            }
        }

        // fallback: trigger anchor click so native handlers run
        a.click();
    } catch (e) {
        // ignore errors silently
    }
});
</script>
