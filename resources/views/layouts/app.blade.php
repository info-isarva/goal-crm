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

    <link rel="icon" href="{{ $company->favicon_url }}">
    <link rel="shortcut icon" href="{{ $company->favicon_url }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Bootstrap 5 & DataTables CSS --}}
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Bootstrap 5 and Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!---Custom CSS --->
    <link href="{{ asset('css/custom-style.css') }}" rel="stylesheet">
    <link href="{{ asset('css/hub-page-skeleton.css') }}?v={{ @filemtime(public_path('css/hub-page-skeleton.css')) }}" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="{{ asset('css/select2.min.css') }}" rel="stylesheet" />

    <!-- Font Awesome for time/date icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="font-sans antialiased">
    <!-- Before </body> -->
    <!-- jQuery and Select2 JS (only once, before custom scripts) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/swal.min.js') }}"></script>
    <script src="{{ asset('js/swal.js') }}"></script>
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
        <!-- <div class="container-fluid min-vh-100 bg-light"> -->
        <div class="min-vh-100 bg-light">
            <div class="row g-0">
                @include('layouts.sidebar')
                <main class="col-12 col-lg-12   custom-margin  @auth with-top-header @endauth d-flex flex-column min-vh-100"
                    style="max-width:calc(100%-280px);">

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

                        <div class="top-slim-header px-3">
                            <div class="d-flex align-items-center py-2 w-100" style="gap: 0;">
                                <!-- Left side: Dashboard title and sidebar toggle -->
                                <div class="d-flex align-items-center" style="gap: 12px;">
                                    <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle">
                                        <i class="bi bi-arrow-bar-left"></i>
                                    </button>
                                    <h5 class="mb-0" id="pageTitle">
                                        <span>@yield('page_title', $generatedTitle)</span>
                                    </h5>
                                </div>
                                <!-- Spacer to push right section -->
                                <div style="flex:1 1 auto;"></div>


                                <!-- Right side: Notifications + Profile dropdown -->
                                <div class="d-flex align-items-center" style="gap: 16px;">
                                    <!-- Financial Year Dropdown -->
                                    <div class="d-flex align-items-center me-3 position-relative fy-desktop-dropdown"
                                        id="fy-selector-container">
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
                                        @endphp
                                        <div class="d-flex align-items-center px-3 py-1 rounded-3"
                                            style="background: rgb(239 246 255 / var(--tw-bg-opacity, 1) 1);color: #000;gap: 10px;min-width: 270px;border: 2px solid rgb(239 246 255 / var(--tw-bg-opacity, 1) 1);">
                                            <i class="bi bi-calendar-event" style="font-size:1.3rem;"></i>
                                            <span class="fw-semibold" style="font-size:16px;">FY:</span>
                                            <button type="button"
                                                class="btn btn-light d-flex align-items-center px-3 py-1 rounded-2"
                                                id="fyDropdownBtn"
                                                style="font-size:15px; font-weight:500; border:2px solid #e3eafc; box-shadow:none;">
                                                {{ $__buttonLabel }}
                                                @if ($__isCurrent)
                                                    <span class="badge bg-success ms-2"
                                                        style="font-size:13px;">Current</span>
                                                @elseif($__selectedFyId)
                                                    <span class="badge bg-primary ms-2"
                                                        style="font-size:13px;">Selected</span>
                                                @endif
                                            </button>
                                        </div>
                                        <!-- Dropdown -->
                                        <div id="fyDropdown" class="position-absolute shadow-lg rounded-3"
                                            style="top:48px; left:0; min-width:270px; background:#fff; z-index:1000; display:none;">
                                            <div class="px-3 pt-3 pb-2 border-bottom fw-semibold text-dark"
                                                style="font-size:15px;"><i class="bi bi-calendar-event me-2"></i> Select
                                                Financial Year</div>
                                            @php
                                                $fys = \App\Models\FinancialYear::orderBy('from_date', 'desc')->get();
                                                $selectedFyId = session('selected_financial_year', null);
                                            @endphp
                                            @foreach ($fys as $fy)
                                                <div class="d-flex align-items-center justify-content-between px-3 py-2 fy-item"
                                                    data-fy-id="{{ $fy->id }}"
                                                    style="border-bottom:1px solid #f0f0f0; cursor:pointer;">
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

                                    <!-- Notifications dropdown -->
                                    <style>
                                        /* Visual styling for unread notifications in header */
                                        .notif-item.unread {
                                            background: #fff8e6;
                                            font-weight: 600;
                                            border-left: 4px solid #f39c12;
                                        }

                                        .notif-item {
                                            transition: background .12s ease;
                                        }

                                        .notif-item .notif-body {
                                            display: block;
                                        }
                                    </style>
                                    <!-- Calendar Icon -->
                                <div class="d-flex align-items-center me-3">
                                    <a href="{{ route('calendar.events') }}" 
                                    class="btn btn-light d-flex align-items-center justify-content-center"
                                    style="width:42px; height:42px; border:2px solid #e3eafc;">
                                    
                                        <i class="fas fa-calendar-alt" style="font-size:1.3rem;"></i>
                                    </a>
                                </div>

                                @if(in_array((int) (Auth::user()->crm_role_type ?? -1), [2, 3], true))
                                    <style>
                                        #weeklyTargetAnnouncement {
                                            display: none;
                                            align-items: center;
                                            gap: 8px;
                                            border-radius: 8px;
                                            padding: 7px 12px;
                                            font-size: 13px;
                                            font-weight: 600;
                                            cursor: pointer;
                                            border: 1px solid transparent;
                                            max-width: 280px;
                                            white-space: nowrap;
                                            line-height: 1.2;
                                            transition: background .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
                                        }
                                        #weeklyTargetAnnouncement.is-unread,
                                        #weeklyTargetAnnouncement.is-read {
                                            display: inline-flex;
                                            background: #fff4e5;
                                            color: #c62828;
                                            border-color: #ffb74d;
                                        }
                                        #weeklyTargetAnnouncement.is-unread {
                                            background: #ffe0b2;
                                            color: #b71c1c;
                                            border-color: #fb8c00;
                                            box-shadow: 0 1px 2px rgba(251, 140, 0, 0.18);
                                        }
                                        #weeklyTargetAnnouncement.is-read {
                                            font-weight: 500;
                                            opacity: 0.95;
                                        }
                                        #weeklyTargetAnnouncement:hover {
                                            filter: brightness(0.98);
                                        }
                                        #weeklyTargetAnnouncement .wta-pct {
                                            background: rgba(198, 40, 40, 0.12);
                                            color: #c62828;
                                            border-radius: 999px;
                                            padding: 2px 8px;
                                            font-size: 12px;
                                            font-weight: 700;
                                        }
                                        #weeklyTargetAnnouncement.is-unread .wta-pct {
                                            background: #c62828;
                                            color: #fff;
                                        }
                                    </style>
                                    <button type="button"
                                        id="weeklyTargetAnnouncement"
                                        class="btn btn-sm"
                                        title="Weekly target alert — click to view">
                                        <i class="bi bi-megaphone-fill"></i>
                                        <span class="wta-text">Weekly Target</span>
                                        <span class="wta-pct" id="weeklyTargetAnnouncementPct">0%</span>
                                    </button>
                                @endif
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light position-relative" type="button"
                                            id="notificationDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false"
                                            style="padding:8px 10px;">
                                            <i class="bi bi-bell" style="font-size:1.1rem;"></i>
                                            @php $unreadCount = Auth::user() ? Auth::user()->unreadNotifications()->count() : 0; @endphp
                                            @if ($unreadCount > 0)
                                                <span id="notifCount"
                                                    class="badge bg-danger position-absolute top-0 start-100 translate-middle"
                                                    style="font-size:11px;">{{ $unreadCount }}</span>
                                            @endif
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end p-0"
                                            aria-labelledby="notificationDropdownBtn"
                                            style="min-width:320px; max-height:420px; overflow:auto;">
                                            <li
                                                class="px-3 py-2 border-bottom fw-semibold d-flex align-items-center justify-content-between">
                                                Notifications
                                                <a href="#" id="markAllRead" class="btn btn-sm btn-link">Mark all</a>
                                            </li>
                                            @php $notes = Auth::user() ? Auth::user()->notifications()->orderBy('created_at','desc')->limit(10)->get() : collect(); @endphp
                                            <div id="notifItems">
                                                @forelse($notes as $notification)
                                                    @php
                                                        $notifLink =
                                                            $notification->data['meeting_link'] ??
                                                            ($notification->data['task_link'] ??
                                                                ($notification->data['related_link'] ?? ''));
                                                    @endphp
                                                    <li class="px-3 py-2 notif-item {{ $notification->read_at ? '' : 'unread' }}"
                                                        data-id="{{ $notification->id }}"
                                                        data-link="{{ $notifLink }}" style="cursor:pointer;">
                                                        <div class="small text-muted">
                                                            {{ $notification->created_at->diffForHumans() }}</div>
                                                        <div class="notif-body">{!! $notification->data['message'] ?? ($notification->data['title'] ?? 'Notification') !!}</div>
                                                    </li>
                                                @empty
                                                    <li class="px-3 py-3 text-muted small">No notifications</li>
                                                @endforelse
                                            </div>
                                            <li>
                                                <hr class="dropdown-divider m-0">
                                            </li>
                                            <li><a class="dropdown-item text-center"
                                                    href="{{ route('notifications.index') }}">View all</a></li>
                                        </ul>
                                    </div>

                                    <div class="dropdown">
                                        <button
                                            class="btn btn-sm btn-light d-inline-flex align-items-center justify-content-center"
                                            type="button" id="mobileProfileDropdown" data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            style="padding: 0 8px; display: flex; align-items: center;">
                                            @php $avatar = Auth::user()->avatar; @endphp
                                            @if ($avatar && file_exists(public_path('assets/employee_profile_image/' . $avatar)))
                                                <img src="{{ asset('assets/employee_profile_image/' . $avatar) }}"
                                                    class="rounded-circle shadow-sm border"
                                                    style="width:40px; height:40px; object-fit:cover;" alt="User">
                                            @else
                                                <img src="{{ asset('user-thumbnail.png') }}"
                                                    class="rounded-circle shadow-sm border"
                                                    style="width:40px; height:40px;object-fit:cover;" alt="User">
                                            @endif
                                            <span class="fw-semibold text-dark ms-2"
                                                style="font-size: 16px;">{{ Auth::user()->name }}</span>
                                            <i class="bi bi-chevron-down ms-2"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end"
                                            aria-labelledby="mobileProfileDropdown">
                                            <li class="px-3 py-2 user-dropdown-header">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                                        style="width:32px;height:32px;">
                                                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</div>
                                                    <div>
                                                        <div class="fw-semibold">{{ Auth::user()->name }}</div>
                                                        <div class="text-muted small">{{ Auth::user()->email }}</div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider m-0">
                                            </li>
                                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a>
                                            </li>
                                            <li><a class="dropdown-item" href="{{ route('password.edit') }}">Change
                                                    Password</a></li>
                                            <li>
                                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-danger">Sign
                                                        out</button>
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

                    {{-- Flash messages (success/error/validation) --}}
                    
                        @if (session('success'))
                        <div class="container-fluid p-3">
                            <div id="app-flash-success" class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>{!! session('success') !!}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        </div>
                        @endif

                        @if (session('error'))
                        <div class="container-fluid p-3">
                            <div id="app-flash-error" class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-octagon-fill me-2"></i>{!! session('error') !!}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        </div>
                        @endif

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                ['app-flash-success', 'app-flash-error'].forEach(function (id) {
                                    var el = document.getElementById(id);
                                    if (!el) return;
                                    // Slowly fade out after 4s, then remove
                                    setTimeout(function () {
                                        el.style.transition = 'opacity 1.2s ease';
                                        el.style.opacity = '0';
                                        setTimeout(function () {
                                            try {
                                                var bsAlert = bootstrap.Alert.getOrCreateInstance(el);
                                                bsAlert.close();
                                            } catch (e) {
                                                if (el.parentNode) el.parentNode.removeChild(el);
                                            }
                                        }, 1200);
                                    }, 4000);
                                });
                            });
                        </script>

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
                        if (auth()->check() && $__currentRoute && $__currentRoute !== 'settings.index') {
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

                    <div class="top-slim-footer px-3 mt-auto" >
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
            });
        </script>
        {{-- Bootstrap and DataTables JS --}}
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <script src="{{ asset('js/custom-script.js') }}"></script>
        <script src="{{ asset('js/hub-page-skeleton.js') }}?v={{ @filemtime(public_path('js/hub-page-skeleton.js')) }}"></script>
        <script src="{{ asset('js/select2.min.js') }}"></script>
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
                        var link = i.task_link || '';
                        html += '<li class="px-3 py-2 notif-item ' + cls + '" data-id="' + i.id +
                            '" data-link="' + link + '" style="cursor:pointer;">';
                        html += '<div class="small text-muted">' + (i.created_at || '') + '</div>';
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
