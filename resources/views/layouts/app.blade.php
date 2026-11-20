<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $company = \App\Models\Company::current();
    @endphp

    <title>{{ !empty($company->name) ? $company->name : config('app.name', 'Laravel') }}</title>

    <script>
        (function () {
            try {
                var t = localStorage.getItem('sidebar-theme') || 'soft';
                if (t === 'light') t = 'soft';
                if (t !== 'soft' && t !== 'green' && t !== 'dark') t = 'soft';
                document.documentElement.setAttribute('data-sidebar-theme', t);
            } catch (e) {
                document.documentElement.setAttribute('data-sidebar-theme', 'soft');
            }
        })();
    </script>

    <link rel="icon" href="{{ $company->favicon_url }}">
    <link rel="shortcut icon" href="{{ $company->favicon_url }}">
    <!-- Fonts: Inter (ISARVA enterprise typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- DataTables (non-Bootstrap) --}}
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">

    <!-- Scripts (Tailwind via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Icons only (not Bootstrap CSS framework) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!---Custom CSS (Tailwind via Vite above; app chrome extras below) --->
    <link href="{{ asset('css/app-theme.css') }}?v={{ @filemtime(public_path('css/app-theme.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/custom-style.css') }}" rel="stylesheet">
    <link href="{{ asset('css/isarva-typography.css') }}?v={{ @filemtime(public_path('css/isarva-typography.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hub-page-skeleton.css') }}?v={{ @filemtime(public_path('css/hub-page-skeleton.css')) }}" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="{{ asset('css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/select2-theme.css') }}?v={{ @filemtime(public_path('css/select2-theme.css')) }}" rel="stylesheet" />
    <!-- Flatpickr (global date / datetime) -->
    <link href="{{ asset('css/flatpickr.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/flatpickr-monthSelect.css') }}?v={{ @filemtime(public_path('css/flatpickr-monthSelect.css')) }}" rel="stylesheet" />
    <link href="{{ asset('css/flatpickr-theme.css') }}?v={{ @filemtime(public_path('css/flatpickr-theme.css')) }}" rel="stylesheet" />
    <!-- Global toastr-style flash notifications -->
    <link href="{{ asset('css/app-toastr.css') }}?v={{ @filemtime(public_path('css/app-toastr.css')) }}" rel="stylesheet" />
    <link href="{{ asset('css/page-loader.css') }}?v={{ @filemtime(public_path('css/page-loader.css')) }}" rel="stylesheet" />

    <!-- Font Awesome for time/date icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="font-sans antialiased">
    {{-- Global page transition loader --}}
    <div id="goalPageLoader" class="is-active" aria-busy="true" aria-hidden="false" role="status" aria-live="polite">
        <div class="gpl-backdrop"></div>
        <div class="gpl-progress" aria-hidden="true"><span></span></div>
        <div class="gpl-card">
            <div class="gpl-orbit">
                <div class="gpl-core"><i class="bi bi-building"></i></div>
            </div>
            <p class="gpl-text">Loading<span class="gpl-dots" aria-hidden="true"><i></i><i></i><i></i></span></p>
            <p class="gpl-sub">Please wait</p>
        </div>
    </div>
    <script src="{{ asset('js/page-loader.js') }}?v={{ @filemtime(public_path('js/page-loader.js')) }}"></script>

    <!-- Before </body> -->
    <!-- jQuery and Select2 JS (only once, before custom scripts) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/swal.min.js') }}"></script>
    <script src="{{ asset('js/swal.js') }}"></script>
    <script src="{{ asset('js/app-toastr.js') }}?v={{ @filemtime(public_path('js/app-toastr.js')) }}"></script>
    @stack('styles')
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">

        {{-- @include('layouts.navigation') --}}
        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <div class="min-h-screen bg-slate-50">
            <div class="flex min-h-screen flex-col lg:flex-row">
                @include('layouts.sidebar')
                <main class="custom-margin @auth with-top-header @endauth flex min-h-screen w-full min-w-0 max-w-full flex-1 flex-col overflow-x-clip">

                    @auth

                        @php
                            $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
                            $routeTitles = [
                                'dashboard' => 'Dashboard',
                                'leads.index' => 'Leads',
                                'deals.index' => 'Deals',
                                'deals.pipeline' => 'Deals Pipeline',
                                'organizations.index' => 'Company',
                                'organizations.create' => 'Company',
                                'organizations.show' => 'Company',
                                'organizations.edit' => 'Company',
                                'people.index' => 'Contact Persons',
                                'people.show' => 'Contact Persons',
                                'people.edit' => 'Contact Persons',
                                'people.create' => 'Contact Persons',
                                'customers.index' => 'Company Owners',
                                'customers.show' => 'Comapny Owner',
                                'customers.edit' => 'Comapny Owner',
                                'customers.create' => 'Comapny Owner',
                                'users.index' => 'Users',
                                'settings.index' => 'Settings',
                                'profile.edit' => 'Personal Settings',
                                'company.edit' => 'Company Settings',
                                'whatsapp.settings.edit' => 'WhatsApp Settings',
                                'email.settings.edit' => 'Email Settings',
                                'gmail.settings.edit' => 'Gmail Account Setup',
                                'quotation_templates.index' => 'Quotation Templates',
                                'permissions.index' => 'Permissions',
                                'roles.index' => 'Roles',
                                'tax_rates.index' => 'Tax Rates',
                                'product_categories.index' => 'Product Categories',
                                'main_categories.index' => 'Main Categories',
                                'lead_sources.index' => 'Lead Sources',
                                'password.edit' => 'Change Password',
                                'backup' => 'Backup Data',
                                'company.close_year.page' => 'Close Financial Year',
                                'quotations.email_send_logs' => 'Email Send Logs',
                                'quotations.whatsapp_send_logs' => 'WhatsApp Send Logs',
                                'calllogs.index' => 'Call Logs',
                                'calllogs.create' => 'Call Logs',
                                'calllogs.edit' => 'Call Logs',
                                'calllogs.show' => 'Call Logs',
                                'calllogs.import.page' => 'Call Logs',
                                'activity-logs.index' => 'Audit Logs',
                                'activity-logs.show' => 'Audit Logs',
                                // Reports list + individual reports
                                'reports.index' => 'Reports',
                                'reports.today_leads' => "Today's Leads Report",
                                'reports.leads_by_status' => 'Leads by Status Report',
                                'reports.leads_by_source_custom' => 'Leads by Source',
                                'reports.converted_leads' => 'Converted Leads Report',
                                'reports.lead_deals' => 'Lead Deals Report',
                                'reports.today_closed_won_deals' => "Today's Sales",
                                'reports.deals_by_source_custom' => 'Sales by Source',
                                'reports.open_deals' => 'Open Deals Report',
                                'reports.lost_deals' => 'Lost Deals Report',
                                'reports.deals_this_month' => 'Deals Closing this Month',
                                'reports.user_daily_report' => 'User Daily Report',
                                'daily_reports.index' => 'Daily Reports',
                                'daily_reports.create' => 'Enter Daily Report',
                                'daily_reports.edit' => 'Edit Daily Report',
                                'daily_reports.show' => 'Daily Report',
                                'outreach_types.index' => 'Outreach Types',
                                'outreach_types.create' => 'Add Outreach Type',
                                'outreach_types.edit' => 'Edit Outreach Type',
                                'reports.monthly_user_report' => 'Monthly User Report',
                                'reports.monthly_user_performance' => 'Monthly User Performance Report',
                                'reports.weekly_activity' => 'Weekly Activity Report',
                                'task-reports.index' => 'Reminder Reports',
                                'reports.task' => 'Task Report',
                                'reports.product_category_user_report' => 'Product Category Wise User Report',
                                'calllogs.report' => 'Calls Reports',
                                'reports.monthly_target_report' => 'Monthly Target Report',
                                'reports.leads' => 'Leads Analytics',
                                'reports.deals' => 'Deals Analytics',
                                'reports.revenue_by_month' => 'Revenue Reports',
                                'analytics.reports' => 'User Performance Analytics Reports',
                                'reports.month_lead_comparison' => 'Month Lead Comparison Report',
                                'reports.user_activity' => 'User Activity Report',
                                'reports.user_reports' => 'User Reports',
                                'analytics.dashboard' => 'Analytics Dashboard',
                            ];

                            if ($currentRoute && isset($routeTitles[$currentRoute])) {
                                $generatedTitle = $routeTitles[$currentRoute];
                            } elseif ($currentRoute) {
                                // Fallback: take first segment of route name or URI
                                $parts = explode('.', $currentRoute);
                                $generatedTitle = ucfirst($parts[0]);
                            } else {
                                $segments = request()->segments();
                                $generatedTitle = $segments ? ucfirst($segments[0]) : 'Dashboard';
                            }

                            if ($currentRoute === 'settings.index'
                                || \App\Http\Controllers\SettingsController::isSettingsSubPage($currentRoute)) {
                                $generatedTitle = 'Settings';
                            }

                            if ($currentRoute === 'reports.index'
                                || \App\Http\Controllers\ReportController::isReportSubPage($currentRoute)) {
                                $generatedTitle = 'Reports';
                            }
                        @endphp

                        @php
                            $__selectedFyId = session('selected_financial_year', null);
                            $__activeFy = \App\Models\FinancialYear::where('active', 1)->first();
                            $__selectedFy = $__selectedFyId
                                ? \App\Models\FinancialYear::find($__selectedFyId)
                                : $__activeFy;
                            $__buttonLabel = $__selectedFy
                                ? \Carbon\Carbon::parse($__selectedFy->from_date)->format('M, Y') .
                                    ' - ' .
                                    \Carbon\Carbon::parse($__selectedFy->to_date)->format('M, Y')
                                : 'Select Year';
                            $__isCurrent = $__selectedFy && $__selectedFy->active;
                            $__usesGoal = \App\Services\Goal\GoalDashboardService::userUsesGoalDashboard(Auth::user());
                            $__headerSearchAction = $__usesGoal
                                ? route('goal.leads.index')
                                : route('leads.index');
                            $__unreadCount = Auth::user() ? Auth::user()->unreadNotifications()->count() : 0;
                            $__avatar = Auth::user()->avatar ?? null;
                            $__hasAvatar = $__avatar && file_exists(public_path('assets/employee_profile_image/' . $__avatar));
                        @endphp

                        <div class="top-slim-header">
                            <div class="top-header-inner">
                                {{-- 1. Sidebar toggle --}}
                                <div class="header-left-tools">
                                    <button type="button"
                                            class="header-sidebar-toggle d-none d-lg-inline-flex"
                                            id="sidebarCollapseBtn"
                                            title="Collapse sidebar"
                                            aria-label="Collapse sidebar">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </button>
                                </div>

                                {{-- 2. Global search --}}
                                <form class="header-search" method="GET" action="{{ $__headerSearchAction }}" role="search">
                                    <i class="bi bi-search header-search-ico" aria-hidden="true"></i>
                                    <input type="search"
                                           name="q"
                                           id="globalHeaderSearch"
                                           class="header-search-input"
                                           value="{{ request('q') }}"
                                           placeholder="Search for leads, cases, branches..."
                                           autocomplete="off"
                                           aria-label="Global search">
                                    <kbd class="header-search-kbd" title="Keyboard shortcut">Ctrl + K</kbd>
                                </form>

                                {{-- 3–6. Right cluster: FY → Calendar → Notifications → Profile --}}
                                <div class="header-right-tools">
                                    <div class="fy-desktop-dropdown position-relative" id="fy-selector-container">
                                        <div class="header-fy-wrap">
                                            <i class="bi bi-calendar2-check header-fy-ico" aria-hidden="true"></i>
                                            <span class="header-fy-label">FY:</span>
                                            <button type="button" class="header-fy-btn" id="fyDropdownBtn">
                                                <span class="header-fy-btn-text">{{ $__buttonLabel }}</span>
                                                @if ($__isCurrent)
                                                    <span class="badge bg-success header-fy-badge">Current</span>
                                                @elseif($__selectedFyId)
                                                    <span class="badge bg-primary header-fy-badge">Selected</span>
                                                @endif
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </div>
                                        <div id="fyDropdown" class="header-fy-menu">
                                            <div class="header-fy-menu-title">
                                                <i class="bi bi-calendar-event me-2"></i> Select Financial Year
                                            </div>
                                            @php
                                                $fys = \App\Models\FinancialYear::orderBy('from_date', 'desc')->get();
                                                $selectedFyId = session('selected_financial_year', null);
                                            @endphp
                                            @foreach ($fys as $fy)
                                                <div class="d-flex align-items-center justify-content-between fy-item"
                                                    data-fy-id="{{ $fy->id }}">
                                                    <span>{{ $fy->from_date->format('M, Y') }} -
                                                        {{ $fy->to_date->format('M, Y') }}</span>
                                                    <div class="d-flex align-items-center" style="gap:8px;">
                                                        @if ($fy->active)
                                                            <span class="badge bg-success">Current</span>
                                                        @endif
                                                        @if ($fy->id == $selectedFyId)
                                                            <i class="bi bi-check-lg text-success" title="Selected"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <a href="{{ route('calendar.events') }}"
                                       class="header-icon-btn"
                                       title="Calendar"
                                       aria-label="Open calendar">
                                        <i class="bi bi-calendar3"></i>
                                    </a>

                                    @if(in_array((int) (Auth::user()->crm_role_type ?? -1), [2, 3], true))
                                        <button type="button"
                                            id="weeklyTargetAnnouncement"
                                            class="btn btn-sm header-wta-btn"
                                            title="Weekly target alert — click to view">
                                            <i class="bi bi-megaphone-fill"></i>
                                            <span class="wta-text">Weekly Target</span>
                                            <span class="wta-pct" id="weeklyTargetAnnouncementPct">0%</span>
                                        </button>
                                    @endif

                                    <div class="dropdown">
                                        <button class="header-icon-btn position-relative" type="button"
                                            id="notificationDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false"
                                            title="Notifications" aria-label="Notifications">
                                            <i class="bi bi-bell"></i>
                                            @if ($__unreadCount > 0)
                                                <span id="notifCount" class="header-notif-badge">{{ $__unreadCount > 99 ? '99+' : $__unreadCount }}</span>
                                            @endif
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end p-0 header-notif-menu"
                                            aria-labelledby="notificationDropdownBtn">
                                            <li class="px-3 py-2 border-bottom fw-semibold d-flex align-items-center justify-content-between">
                                                Notifications
                                                <a href="#" id="markAllRead" class="btn btn-sm btn-link">Mark all</a>
                                            </li>
                                            @php $notes = Auth::user() ? Auth::user()->notifications()->orderBy('created_at','desc')->limit(10)->get() : collect(); @endphp
                                            <div id="notifItems">
                                                @forelse($notes as $notification)
                                                    @php
                                                        $notifData = $notification->data ?? [];
                                                        $notifLink = $notifData['url']
                                                            ?? $notifData['meeting_link']
                                                            ?? $notifData['task_link']
                                                            ?? $notifData['related_link']
                                                            ?? '';
                                                        $notifTitle = $notifData['title'] ?? null;
                                                        $notifMessage = $notifData['message'] ?? ($notifData['title'] ?? 'Notification');
                                                    @endphp
                                                    <li class="px-3 py-2 notif-item {{ $notification->read_at ? '' : 'unread' }}"
                                                        data-id="{{ $notification->id }}"
                                                        data-link="{{ $notifLink }}" style="cursor:pointer;">
                                                        <div class="small text-muted">
                                                            {{ $notification->created_at->diffForHumans() }}</div>
                                                        @if($notifTitle)
                                                            <div class="fw-semibold small">{{ $notifTitle }}</div>
                                                        @endif
                                                        <div class="notif-body">{!! $notifMessage !!}</div>
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
                                        <button class="header-profile-btn" type="button" id="mobileProfileDropdown"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            @if ($__hasAvatar)
                                                <img src="{{ asset('assets/employee_profile_image/' . $__avatar) }}"
                                                    class="header-profile-avatar" alt="{{ Auth::user()->name }}">
                                            @else
                                                <span class="header-profile-avatar header-profile-fallback" aria-hidden="true">
                                                    <i class="bi bi-person-fill"></i>
                                                </span>
                                            @endif
                                            <span class="header-profile-name">{{ Auth::user()->name }}</span>
                                            <i class="bi bi-chevron-down header-profile-caret"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end"
                                            aria-labelledby="mobileProfileDropdown">
                                            <li class="px-3 py-2 user-dropdown-header">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                                        style="width:32px;height:32px;">
                                                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</div>
                                                    <div>
                                                        <div class="fw-semibold">{{ Auth::user()->name }}</div>
                                                        <div class="text-muted small">{{ Auth::user()->email }}</div>
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
                            @php
                                $selectedFyId = session('selected_financial_year', null);
                                $activeFy = \App\Models\FinancialYear::where('active', 1)->first();
                                $isHistorical = $selectedFyId && $activeFy && $selectedFyId != $activeFy->id;
                                $selectedFy = $selectedFyId ? \App\Models\FinancialYear::find($selectedFyId) : null;
                            @endphp

                        </div>
                    @endauth

                    @php
                        $companyForNotice = \App\Models\Company::current();
                        $noticeDays = null;
                        $noticeExpired = false;
                        if (!empty($companyForNotice->expire_date)) {
                            $todayDate = \Carbon\Carbon::today();
                            $expDate = \Carbon\Carbon::parse($companyForNotice->expire_date)->startOfDay();
                            $noticeDays = (int) $todayDate->diffInDays($expDate, false);
                            $noticeExpired = \Carbon\Carbon::parse($companyForNotice->expire_date)->endOfDay()->isPast();
                        }
                    @endphp

                    @if($noticeExpired && auth()->check() && (int)(auth()->user()->crm_role_type ?? -1) === 0)
                        <div class="px-4 pt-3">
                            <div class="alert alert-danger mb-0 rounded-3 shadow-sm d-flex align-items-center justify-content-between p-3" style="background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7;">
                                <div>
                                    <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                                    Application subscription expired on <strong>{{ \Carbon\Carbon::parse($companyForNotice->expire_date)->format('d M Y') }}</strong>. Regular user access is currently blocked.
                                </div>
                                <a href="{{ route('company.edit') }}" class="btn btn-sm btn-danger fw-semibold px-3 ms-3">Update Expire Date</a>
                            </div>
                        </div>
                    @elseif(!$noticeExpired && $noticeDays !== null && $noticeDays >= 0 && $noticeDays <= 5)
                        <div class="px-4 pt-3">
                            <div class="alert alert-warning mb-0 rounded-3 shadow-sm d-flex align-items-center justify-content-between p-3" style="background-color: #fff3cd; color: #664d03; border: 1px solid #ffecb5;">
                                <div>
                                    <i class="fas fa-clock me-2 fs-5"></i>
                                    Notice: Your account / application setup will expire <strong>{{ $noticeDays == 0 ? 'today' : ($noticeDays == 1 ? 'within 1 day' : 'within ' . $noticeDays . ' days') }}</strong> (on {{ \Carbon\Carbon::parse($companyForNotice->expire_date)->format('d M Y') }}). Please contact administrator to renew.
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($isHistorical)
                        <div class="pb-4">
                            <div class="alert alert-warning mb-0 rounded-0 d-flex align-items-center justify-content-between"
                                role="alert" style="border-left:4px solid #f39c12;">
                                <div>
                                    <strong>Read-only:</strong>
                                    You are viewing a historical financial year @if ($selectedFy)
                                        ({{ \Carbon\Carbon::parse($selectedFy->from_date)->format('d M, Y') }} -
                                        {{ \Carbon\Carbon::parse($selectedFy->to_date)->format('d M, Y') }})
                                    @endif. Creating, editing or deleting records is disabled for
                                    this period.
                                </div>

                            </div>

                        </div>
                    @endif

                    {{-- Flash messages → global toastr (session success/error/warning/info) --}}
                    @php
                        $__flashToast = [];
                        if (session()->has('success')) {
                            $__flashToast[] = ['type' => 'success', 'message' => session('success')];
                        }
                        if (session()->has('error')) {
                            $__flashToast[] = ['type' => 'error', 'message' => session('error')];
                        } elseif (isset($errors) && $errors->any()) {
                            $__flashToast[] = ['type' => 'error', 'message' => $errors->first()];
                        }
                        if (session()->has('warning')) {
                            $__flashToast[] = ['type' => 'warning', 'message' => session('warning')];
                        }
                        if (session()->has('info')) {
                            $__flashToast[] = ['type' => 'info', 'message' => session('info')];
                        }
                        if (session()->has('status') && !session()->has('success')) {
                            $__flashToast[] = ['type' => 'success', 'message' => session('status')];
                        }
                    @endphp
                    @if(count($__flashToast))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                if (!window.toastr) return;
                                var flashes = @json($__flashToast);
                                flashes.forEach(function (item) {
                                    var fn = toastr[item.type] || toastr.info;
                                    fn.call(toastr, item.message);
                                });
                            });
                        </script>
                    @endif

                    @php
                        $__reportBackTitle = null;
                        $__currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
                        if (auth()->check() && $__currentRoute) {
                            $__reportBackTitle = \App\Http\Controllers\ReportController::resolvePageTitle($__currentRoute);
                            if (\App\Http\Controllers\ReportController::usesModernReportShell($__currentRoute)) {
                                $__reportBackTitle = null;
                            }
                        }
                    @endphp

                    @php
                        $__settingsBackTitle = null;
                        // Custom-hero pages render their own title / back controls
                        $__settingsSkipBackHeader = in_array($__currentRoute, ['users.index', 'users.create', 'users.edit'], true);
                        if (auth()->check() && $__currentRoute && $__currentRoute !== 'settings.index' && !$__settingsSkipBackHeader) {
                            $__settingsTitles = \App\Http\Controllers\SettingsController::pageTitles();
                            if (\App\Http\Controllers\SettingsController::isSettingsSubPage($__currentRoute)) {
                                $__settingsBackTitle = $__settingsTitles[$__currentRoute]
                                    ?? (isset($generatedTitle) ? $generatedTitle : 'Settings');
                            }
                        }
                    @endphp

                    @if (!empty($__reportBackTitle))
                        @include('reports.partials.back_header', ['title' => $__reportBackTitle])
                    @elseif (!empty($__settingsBackTitle))
                        @include('settings.partials.back_header', ['title' => $__settingsBackTitle])
                    @endif

                    @if (!empty($__reportBackTitle) || !empty($__settingsBackTitle))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                document.querySelectorAll('.report-page-title').forEach(function (el) {
                                    var block = el.closest('.my-3, .report-page-title-row');
                                    if (block && block.querySelectorAll('a, button, form, input, select').length === 0) {
                                        block.style.display = 'none';
                                    } else {
                                        el.style.display = 'none';
                                    }
                                });

                                @if (!empty($__settingsBackTitle))
                                var nodes = document.querySelectorAll('main .container-fluid');
                                var content = null;
                                for (var i = 0; i < nodes.length; i++) {
                                    if (!nodes[i].classList.contains('report-page-back-header')
                                        && !nodes[i].classList.contains('settings-page-back-header')) {
                                        content = nodes[i];
                                        break;
                                    }
                                }
                                if (content) {
                                    var heading = content.querySelector('h1, h2, h3, h4, h5');
                                    if (heading) {
                                        var block = heading.closest('.my-3');
                                        if (block && block.querySelectorAll('a, button, form, input, select').length === 0) {
                                            block.style.display = 'none';
                                        } else {
                                            heading.style.display = 'none';
                                        }
                                    }
                                }
                                @endif
                            });
                        </script>
                    @endif

                    @yield('content')
<!-- Footer Section -->

                    <div class="top-slim-footer px-3" >
                        <div class="container-fluid text-center">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <p class="text-sm text-gray-500 mb-0">© 2026 CRM. All rights reserved.</p>
                                <!-- <div class="d-flex align-items-center">
                                    <a href="#"
                                        class="text-sm text-gray-500 hover:text-blue-600 transition-colors me-3">Privacy
                                        Policy</a>
                                    <a href="#"
                                        class="text-sm text-gray-500 hover:text-blue-600 transition-colors me-3">Terms
                                        of Service</a>
                                    <a href="#"
                                        class="text-sm text-gray-500 hover:text-blue-600 transition-colors">Support</a>
                                </div> -->
                                <div class="d-flex align-items-center">
                                    <span class="text-sm text-gray-500 me-1">Version</span>
                                    <span class="text-sm font-medium text-gray-900">2.0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </main>
            </div>

        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('#fyDropdown .fy-item').forEach(function(el) {
                    el.addEventListener('click', function() {
                        var fyId = this.getAttribute('data-fy-id');
                        fetch("{{ route('financial_year.select') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                financial_year_id: fyId
                            })
                        }).then(function(r) {
                            return r.json();
                        }).then(function(j) {
                            if (j.success) {
                                location.reload();
                            } else {
                                alert('Failed to select financial year: ' + (j.error ||
                                    'unknown'));
                            }
                        }).catch(function(e) {
                            alert('Request failed: ' + e.message);
                        });
                    });
                });

                // Header global search: Ctrl/Cmd + K focuses the search field
                var globalSearch = document.getElementById('globalHeaderSearch');
                if (globalSearch) {
                    document.addEventListener('keydown', function(e) {
                        var key = (e.key || '').toLowerCase();
                        if ((e.ctrlKey || e.metaKey) && key === 'k') {
                            var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                            if (tag === 'input' || tag === 'textarea' || (e.target && e.target.isContentEditable)) {
                                if (e.target !== globalSearch) return;
                            }
                            e.preventDefault();
                            globalSearch.focus();
                            globalSearch.select();
                        }
                    });
                }
            });
        </script>
        {{-- DataTables JS (no Bootstrap) --}}
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="{{ asset('js/tw-ui.js') }}?v={{ @filemtime(public_path('js/tw-ui.js')) }}"></script>

        <script src="{{ asset('js/custom-script.js') }}?v={{ @filemtime(public_path('js/custom-script.js')) }}"></script>
        <script src="{{ asset('js/hub-page-skeleton.js') }}?v={{ @filemtime(public_path('js/hub-page-skeleton.js')) }}"></script>
        <script src="{{ asset('js/select2.min.js') }}"></script>
        <script src="{{ asset('js/select2-init.js') }}?v={{ @filemtime(public_path('js/select2-init.js')) }}"></script>
        <script src="{{ asset('js/flatpickr.min.js') }}"></script>
        <script src="{{ asset('js/flatpickr-monthSelect.js') }}"></script>
        <script src="{{ asset('js/flatpickr-init.js') }}?v={{ @filemtime(public_path('js/flatpickr-init.js')) }}"></script>
        <script src="{{ asset('js/decimal-input.js') }}?v={{ @filemtime(public_path('js/decimal-input.js')) }}"></script>
        @yield('scripts')
        @stack('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var notifItems = document.querySelectorAll('.notif-item');
                var markAllBtn = document.getElementById('markAllRead');
                var notifCountEl = document.getElementById('notifCount');
                var mobileNotifCountEl = document.getElementById('mobileNotifCount');
                var mobileMarkAllBtn = document.getElementById('mobileMarkAllRead');
                var mobileNotifContainerEl = document.getElementById('mobileNotifItems');

                function postMark(payload, cb) {
                    fetch("{{ route('notifications.markRead') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    }).then(function(r) {
                        return r.json();
                    }).then(function(j) {
                        if (cb) cb(j);
                    }).catch(function(e) {
                        console.error('Mark read failed', e);
                    });
                }

                notifItems.forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        var id = this.getAttribute('data-id');
                        var link = this.getAttribute('data-link');
                        postMark({
                            id: id
                        }, function(resp) {
                            if (resp && resp.success) {
                                item.classList.remove('unread');
                                if (notifCountEl) {
                                    var v = parseInt(resp.unread || 0);
                                    notifCountEl.textContent = v > 0 ? v : '';
                                    if (v === 0) notifCountEl.style.display = 'none';
                                }
                                if (mobileNotifCountEl) {
                                    var mv = parseInt(resp.unread || 0);
                                    mobileNotifCountEl.textContent = mv > 0 ? mv : '';
                                    if (mv === 0) mobileNotifCountEl.style.display = 'none';
                                }
                                if (link) {
                                    window.location = link;
                                }
                            }
                        });
                    });
                });

                if (markAllBtn) {
                    markAllBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        postMark({
                            all: 1
                        }, function(resp) {
                            if (resp && resp.success) {
                                document.querySelectorAll('.notif-item.unread').forEach(function(n) {
                                    n.classList.remove('unread');
                                });
                                if (notifCountEl) notifCountEl.style.display = 'none';
                                if (mobileNotifCountEl) mobileNotifCountEl.style.display = 'none';
                            }
                        });
                    });
                }

                // Polling: fetch recent notifications every 15 seconds and update dropdown
                function renderNotifItems(items) {
                    var container = document.getElementById('notifItems');
                    var mobileContainer = document.getElementById('mobileNotifItems');
                    if (!container && !mobileContainer) return;
                    if (!items || items.length === 0) {
                        if (container) container.innerHTML =
                            '<li class="px-3 py-3 text-muted small">No notifications</li>';
                        if (mobileContainer) mobileContainer.innerHTML =
                            '<li class="px-3 py-3 text-muted small">No notifications</li>';
                        return;
                    }
                    var html = '';
                    items.forEach(function(i) {
                        var cls = i.read_at ? '' : 'unread';
                        var link = i.url || i.task_link || '';
                        html += '<li class="px-3 py-2 notif-item ' + cls + '" data-id="' + i.id +
                            '" data-link="' + link + '" style="cursor:pointer;">';
                        html += '<div class="small text-muted">' + (i.created_at || '') + '</div>';
                        if (i.title) {
                            html += '<div class="fw-semibold small">' + i.title + '</div>';
                        }
                        html += '<div class="notif-body">' + (i.message || '') + '</div>';
                        html += '</li>';
                    });
                    if (container) container.innerHTML = html;
                    if (mobileContainer) mobileContainer.innerHTML = html;
                    // rebind handlers for both containers
                    var bindSelector = '#notifItems .notif-item, #mobileNotifItems .notif-item';
                    document.querySelectorAll(bindSelector).forEach(function(item) {
                        item.addEventListener('click', function(e) {
                            var id = this.getAttribute('data-id');
                            var link = this.getAttribute('data-link');
                            postMark({
                                id: id
                            }, function(resp) {
                                if (resp && resp.success) {
                                    item.classList.remove('unread');
                                    if (notifCountEl) {
                                        var v = parseInt(resp.unread || 0);
                                        notifCountEl.textContent = v > 0 ? v : '';
                                        if (v === 0) notifCountEl.style.display = 'none';
                                    }
                                    if (mobileNotifCountEl) {
                                        var mv = parseInt(resp.unread || 0);
                                        mobileNotifCountEl.textContent = mv > 0 ? mv : '';
                                        if (mv === 0) mobileNotifCountEl.style.display = 'none';
                                    }
                                    if (link) {
                                        window.location = link;
                                    }
                                }
                            });
                        });
                    });
                }

                function fetchNotifications() {
                    fetch("{{ route('notifications.recent') }}", {
                            credentials: 'same-origin'
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(j) {
                            if (!j || !j.success) return;
                            var unread = j.unread || 0;
                            if (notifCountEl) {
                                notifCountEl.textContent = unread > 0 ? unread : '';
                                notifCountEl.style.display = unread > 0 ? '' : 'none';
                            }
                            if (mobileNotifCountEl) {
                                mobileNotifCountEl.textContent = unread > 0 ? unread : '';
                                mobileNotifCountEl.style.display = unread > 0 ? '' : 'none';
                            }
                            renderNotifItems(j.items || []);
                        }).catch(function(e) {
                            console.error('Failed to fetch notifications', e);
                        });
                }

                // initial fetch and interval
                fetchNotifications();
                // Poll every 1 seconds (user requested)
                setInterval(fetchNotifications, 30000);

                // Weekly assigned target alert — timed popups + header announcement
                (function () {
                    var roleType = {{ (int) (Auth::user()->crm_role_type ?? -1) }};
                    if ([2, 3].indexOf(roleType) === -1) return;
                    if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') return;

                    var userId = {{ (int) Auth::id() }};
                    var alertBusy = false;
                    var lastPayload = null;
                    var annEl = document.getElementById('weeklyTargetAnnouncement');
                    var annPctEl = document.getElementById('weeklyTargetAnnouncementPct');

                    function todayStr() {
                        return new Date().toISOString().slice(0, 10);
                    }

                    function storageKey(slot) {
                        return 'weeklyTargetAlert_' + userId + '_' + todayStr() + '_' + slot;
                    }

                    function headerReadKey() {
                        return 'weeklyTargetHeaderRead_' + userId + '_' + todayStr();
                    }

                    function alreadyShown(slot) {
                        try {
                            return localStorage.getItem(storageKey(slot)) === '1';
                        } catch (e) {
                            return false;
                        }
                    }

                    function markShown(slot) {
                        try {
                            localStorage.setItem(storageKey(slot), '1');
                        } catch (e) {}
                    }

                    function isHeaderRead() {
                        try {
                            return localStorage.getItem(headerReadKey()) === '1';
                        } catch (e) {
                            return false;
                        }
                    }

                    function markHeaderRead() {
                        try {
                            localStorage.setItem(headerReadKey(), '1');
                        } catch (e) {}
                    }

                    function clearHeaderRead() {
                        try {
                            localStorage.removeItem(headerReadKey());
                        } catch (e) {}
                    }

                    function buildAlertHtml(data) {
                        var pct = Math.max(0, Math.min(100, Number(data.percentage) || 0));
                        var barColor = pct < 50 ? '#dc3545' : (pct < 80 ? '#fd7e14' : '#ffc107');
                        var slotTitle = (data.slot_label || 'Weekly Target')
                            + (data.slot_time ? ' (' + data.slot_time + ')' : '');
                        return ''
                            + '<div style="text-align:left;font-size:14px;line-height:1.5;">'
                            + '<p style="margin:0 0 10px;color:#64748b;"><strong>' + slotTitle + '</strong> · '
                            + (data.week_label || 'This week')
                            + ' <span style="color:#94a3b8;">(' + (data.week_range || '') + ')</span></p>'
                            + '<div style="display:flex;justify-content:space-between;margin-bottom:6px;">'
                            + '<span>Weekly Assigned Target</span>'
                            + '<strong>' + (data.weekly_target_formatted || '0.00') + '</strong></div>'
                            + '<div style="display:flex;justify-content:space-between;margin-bottom:6px;">'
                            + '<span>Achieved</span>'
                            + '<strong>' + (data.weekly_achieved_formatted || '0.00') + '</strong></div>'
                            + '<div style="display:flex;justify-content:space-between;margin-bottom:12px;">'
                            + '<span>Shortfall</span>'
                            + '<strong style="color:#dc3545;">' + (data.shortfall_formatted || '0.00') + '</strong></div>'
                            + '<div style="background:#e9ecef;border-radius:8px;height:14px;overflow:hidden;margin-bottom:8px;">'
                            + '<div style="height:100%;width:' + pct + '%;background:' + barColor + ';border-radius:8px;"></div></div>'
                            + '<div style="text-align:center;font-size:22px;font-weight:700;color:' + barColor + ';">'
                            + pct + '%</div>'
                            + '<p style="margin:10px 0 0;font-size:12px;color:#94a3b8;text-align:center;">'
                            + 'Monthly target ÷ 4 = weekly assigned target'
                            + (data.month_target_formatted ? ' (' + data.month_target_formatted + ' / 4)' : '')
                            + '</p></div>';
                    }

                    function openAlertModal(data, options) {
                        options = options || {};
                        if (!data || !data.available) return;
                        if (alertBusy) return;
                        alertBusy = true;

                        Swal.fire({
                            icon: 'warning',
                            title: 'Weekly Target Not Reached',
                            html: buildAlertHtml(data),
                            confirmButtonText: 'Got it',
                            confirmButtonColor: '#2563eb',
                            width: 460
                        }).then(function () {
                            if (options.markSlot && data.slot && data.slot !== 'preview') {
                                markShown(data.slot);
                            }
                            if (options.markHeaderRead) {
                                markHeaderRead();
                                updateAnnouncement(data);
                            }
                            alertBusy = false;
                        });
                    }

                    function updateAnnouncement(data) {
                        if (!annEl) return;
                        if (!data || !data.available) {
                            annEl.classList.remove('is-unread', 'is-read');
                            annEl.style.display = 'none';
                            return;
                        }
                        var pct = Math.max(0, Math.min(100, Number(data.percentage) || 0));
                        if (annPctEl) annPctEl.textContent = pct + '%';
                        annEl.classList.remove('is-unread', 'is-read');
                        if (isHeaderRead()) {
                            annEl.classList.add('is-read');
                        } else {
                            annEl.classList.add('is-unread');
                        }
                    }

                    function showTimedPopup(data) {
                        if (!data || !data.show || !data.slot || data.slot === 'preview') return;
                        if (alreadyShown(data.slot)) return;
                        // New scheduled slot → announcement back to unread color
                        clearHeaderRead();
                        updateAnnouncement(data);
                        // Timed auto popup — keep previous concept
                        openAlertModal(data, { markSlot: true, markHeaderRead: false });
                    }

                    function fetchAlert(force, cb) {
                        var url = "{{ route('notifications.weekly_target_alert') }}";
                        if (force) url += (url.indexOf('?') >= 0 ? '&' : '?') + 'force=1';
                        fetch(url, {
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            lastPayload = data;
                            if (typeof cb === 'function') cb(data);
                        })
                        .catch(function () {});
                    }

                    function checkWeeklyTargetAlert() {
                        if (alertBusy) return;
                        fetchAlert(false, function (data) {
                            updateAnnouncement(data);
                            showTimedPopup(data);
                        });
                    }

                    if (annEl) {
                        annEl.addEventListener('click', function () {
                            fetchAlert(true, function (data) {
                                updateAnnouncement(data);
                                if (!data || !data.available) {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Weekly Target On Track',
                                            text: data && data.reached
                                                ? 'You have reached your weekly assigned target.'
                                                : 'No weekly target alert right now.',
                                            confirmButtonColor: '#2563eb'
                                        });
                                    }
                                    return;
                                }
                                openAlertModal(data, { markSlot: false, markHeaderRead: true });
                            });
                        });
                    }

                    window.openWeeklyTargetAlert = function () {
                        if (annEl) annEl.click();
                    };

                    checkWeeklyTargetAlert();
                    setInterval(checkWeeklyTargetAlert, 60000);
                })();
            });
        </script>
    </div>
</body>

<!-- Hover popover for collapsed sidebar: show only the hovered icon's title and submenu -->
<div id="sidebarHoverPop" class="sidebar-hover-pop" aria-hidden="true" style="display:none;">
    <div class="popover-inner p-2">
        <div class="popover-menu p-2"></div>
    </div>
</div>



</body>

</html>
