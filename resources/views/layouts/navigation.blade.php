<link href="{{ asset('css/nav-custom.css') }}?v={{ @filemtime(public_path('css/nav-custom.css')) }}" rel="stylesheet">
<nav id="mobileNavRoot" class="custom-flex" aria-label="Mobile navigation">
    <div class="-me-2 items-center desktop-hamburger">
        <button type="button" class="btn btn-warning position-fixed top-0 start-0 m-2 z-3 js-mobile-menu-toggle"
            aria-controls="mobileSidebar" aria-expanded="false" aria-label="Toggle menu"
            style="border-radius:50%;width:48px;height:48px;">
            <i class="bi bi-list fs-3"></i>
        </button>
    </div>

    <div class="header-spacer"></div>

    @php
        $mobileUnread = Auth::user() ? Auth::user()->unreadNotifications()->count() : 0;
        $mobileSelectedFyId = session('selected_financial_year', null);
        $mobileActiveFy = \App\Models\FinancialYear::where('active', 1)->first();
        $mobileSelectedFy = $mobileSelectedFyId
            ? \App\Models\FinancialYear::find($mobileSelectedFyId)
            : $mobileActiveFy;
        $mobileFyShort = $mobileSelectedFy
            ? \Carbon\Carbon::parse($mobileSelectedFy->from_date)->format('Y')
                . '–'
                . \Carbon\Carbon::parse($mobileSelectedFy->to_date)->format('y')
            : 'FY';
        $mobileFyIsCurrent = $mobileSelectedFy && $mobileSelectedFy->active;
        $mobileAvatar = Auth::user()->avatar ?? null;
        $mobileHasAvatar = $mobileAvatar && file_exists(public_path('assets/employee_profile_image/' . $mobileAvatar));
        $mobileInitials = strtoupper(substr(Auth::user()->name ?? 'U', 0, 2));
        $mobileFys = \App\Models\FinancialYear::orderBy('from_date', 'desc')->get();
    @endphp

    <div class="mobile-topbar">
        <div class="mtb-left">
            <button type="button"
                    id="mobileMenuToggle"
                    class="mtb-icon-btn js-mobile-menu-toggle"
                    aria-controls="mobileSidebar"
                    aria-expanded="false"
                    aria-label="Open menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
        </div>

        <div class="mtb-center">
            <div class="mtb-fy" id="mobile-fy-selector-container">
                <button type="button" class="mtb-fy-btn" id="mobileFyDropdownBtn" aria-haspopup="listbox" aria-expanded="false">
                    <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                    <span class="mtb-fy-text">{{ $mobileFyShort }}</span>
                    @if($mobileFyIsCurrent)
                        <span class="mtb-fy-badge">Current</span>
                    @elseif($mobileSelectedFyId)
                        <span class="mtb-fy-badge is-selected">Selected</span>
                    @endif
                    <i class="bi bi-chevron-down mtb-fy-caret" aria-hidden="true"></i>
                </button>
                <div id="mobileFyDropdown" class="mtb-fy-menu" role="listbox" hidden>
                    <div class="mtb-fy-menu-title">
                        <i class="bi bi-calendar-event" aria-hidden="true"></i>
                        Select Financial Year
                    </div>
                    @foreach($mobileFys as $fy)
                        <button type="button"
                                class="mtb-fy-item fy-item-mobile"
                                data-fy-id="{{ $fy->id }}"
                                role="option"
                                aria-selected="{{ (int) $fy->id === (int) $mobileSelectedFyId ? 'true' : 'false' }}">
                            <span class="mtb-fy-item-label">
                                {{ $fy->from_date->format('M Y') }} – {{ $fy->to_date->format('M Y') }}
                            </span>
                            <span class="mtb-fy-item-meta">
                                @if($fy->active)
                                    <span class="mtb-fy-badge">Current</span>
                                @endif
                                @if((int) $fy->id === (int) $mobileSelectedFyId)
                                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                                @endif
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mtb-right">
            <div class="dropdown">
                <button class="mtb-icon-btn position-relative"
                        type="button"
                        id="mobileNotificationDropdownBtn"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Notifications">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    @if($mobileUnread > 0)
                        <span id="mobileNotifCount" class="mtb-notif-badge">{{ $mobileUnread > 99 ? '99+' : $mobileUnread }}</span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end mtb-dropdown-panel"
                    aria-labelledby="mobileNotificationDropdownBtn">
                    <li class="mtb-dropdown-head">
                        <span>Notifications</span>
                        <a href="#" id="mobileMarkAllRead" class="btn btn-sm btn-link p-0">Mark all</a>
                    </li>
                    @php $mobileNotes = Auth::user() ? Auth::user()->notifications()->orderBy('created_at', 'desc')->limit(10)->get() : collect(); @endphp
                    <div id="mobileNotifItems">
                        @forelse($mobileNotes as $notification)
                            @php $notifLink = $notification->data['task_link'] ?? $notification->data['related_link'] ?? ''; @endphp
                            <li class="px-3 py-2 notif-item {{ $notification->read_at ? '' : 'unread' }}"
                                data-id="{{ $notification->id }}"
                                data-link="{{ $notifLink }}"
                                style="cursor:pointer;">
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

            <div class="dropdown">
                <button class="mtb-profile-btn"
                        type="button"
                        id="mobileProfileDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Account menu">
                    @if($mobileHasAvatar)
                        <img src="{{ asset('assets/employee_profile_image/' . $mobileAvatar) }}"
                             class="mtb-avatar"
                             alt="">
                    @else
                        <span class="mtb-avatar mtb-avatar-fallback" aria-hidden="true">{{ $mobileInitials }}</span>
                    @endif
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end mtb-dropdown-panel" aria-labelledby="mobileProfileDropdown">
                    <li class="px-3 py-2 user-dropdown-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                 style="width:32px;height:32px;font-size:.78rem;font-weight:700;">
                                {{ $mobileInitials }}
                            </div>
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ Auth::user()->name }}</div>
                                <div class="text-muted small text-truncate">{{ Auth::user()->email }}</div>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider m-0"></li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                    <li><a class="dropdown-item" href="{{ route('password.edit') }}">Change Password</a></li>
                    <li><hr class="dropdown-divider m-0"></li>
                    <li class="px-3 py-2">
                        <div class="sidebar-theme-switch" role="group" aria-label="Sidebar theme">
                            <div class="sidebar-theme-label">Sidebar theme</div>
                            <div class="sidebar-theme-options" role="group">
                                <button type="button" class="btn btn-outline-secondary sidebar-theme-btn" data-sidebar-theme-set="soft" title="Goal teal / cyan (reference)">Goal</button>
                                <button type="button" class="btn btn-outline-secondary sidebar-theme-btn" data-sidebar-theme-set="green" title="Solid green active">Green</button>
                                <button type="button" class="btn btn-outline-secondary sidebar-theme-btn" data-sidebar-theme-set="dark" title="Classic navy">Dark</button>
                            </div>
                        </div>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">Sign out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div id="mobileSidebarBackdrop" class="mobile-sidebar-backdrop" hidden></div>
    <div id="mobileSidebar" class="mobile-sidebar sidebar" aria-hidden="true">
        @php $company = \App\Models\Company::current(); @endphp
        <div class="mobile-drawer">
            <div class="mobile-drawer-head">
                <div class="mobile-drawer-brand">
                    <img src="{{ $company->logo_url }}"
                         alt="{{ $company->name ?? 'Company Logo' }}"
                         class="company-logo mobile-drawer-logo">
                </div>
                <button type="button"
                        class="mobile-drawer-close js-mobile-menu-close"
                        aria-label="Close menu">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <div class="sidebar-scroll mobile-drawer-scroll">
                <ul class="nav flex-column fw-semibold sidebar-menu sidebar-menu-gap">
                    @include('layouts.sidebar_menu')
                </ul>
            </div>
        </div>
    </div>
</nav>

<script src="{{ asset('js/nav-script.js') }}?v={{ @filemtime(public_path('js/nav-script.js')) }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var fyBtn = document.getElementById('mobileFyDropdownBtn');
    var fyMenu = document.getElementById('mobileFyDropdown');
    if (fyBtn && fyMenu) {
        fyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var open = fyMenu.hasAttribute('hidden');
            if (open) {
                fyMenu.removeAttribute('hidden');
                fyBtn.setAttribute('aria-expanded', 'true');
            } else {
                fyMenu.setAttribute('hidden', '');
                fyBtn.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('#mobile-fy-selector-container')) {
                fyMenu.setAttribute('hidden', '');
                fyBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.querySelectorAll('#mobileFyDropdown .fy-item-mobile').forEach(function (el) {
        el.addEventListener('click', function () {
            var fyId = this.getAttribute('data-fy-id');
            fetch("{{ route('financial_year.select') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ financial_year_id: fyId })
            }).then(function (r) { return r.json(); }).then(function (j) {
                if (j.success) { location.reload(); }
                else { alert('Failed: ' + (j.error || 'unknown')); }
            }).catch(function (e) { alert('Request failed: ' + e.message); });
        });
    });
});
</script>
