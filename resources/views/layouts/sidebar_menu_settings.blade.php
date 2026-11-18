{{-- Sidebar Settings: single link to settings hub page --}}
@php
    $settingsActiveRoutes = [
        'settings.*',
        'profile.*',
        'company.edit',
        'company.close_year.*',
        'whatsapp.settings.*',
        'email.settings.*',
        'gmail.settings.*',
        'email.compose.settings.*',
        'quotation.settings.*',
        'quotation_templates.*',
        'access.settings.*',
        'backup',
        'tax_rates.*',
        'product_categories.*',
        'main_categories.*',
        'lead_sources.*',
        'outreach_types.*',
        'password.*',
        'quotation_email_templates.*',
        'quotations.email_send_logs',
        'quotations.whatsapp_send_logs',
        'web_forms.*',
        'social_ads.*',
    ];

    // Goal SOW nav has dedicated User / Audit items — don't double-activate Settings there
    $isGoalNav = \App\Services\Goal\GoalDashboardService::userUsesGoalDashboard(auth()->user());
    if (!$isGoalNav) {
        array_push(
            $settingsActiveRoutes,
            'users.*',
            'permissions.*',
            'roles.*',
            'activity-logs.*'
        );
    }
@endphp
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs($settingsActiveRoutes) ? 'active' : '' }}"
       href="{{ route('settings.index') }}">
        <i class="bi bi-gear-fill"></i>
        <span class="nav-label">Settings</span>
    </a>
</li>
