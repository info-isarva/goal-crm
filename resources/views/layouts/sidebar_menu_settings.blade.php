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
        'users.*',
        'access.settings.*',
        'backup',
        'permissions.*',
        'roles.*',
        'tax_rates.*',
        'product_categories.*',
        'main_categories.*',
        'lead_sources.*',
        'outreach_types.*',
        'password.*',
        'quotation_email_templates.*',
        'activity-logs.*',
        'quotations.email_send_logs',
        'quotations.whatsapp_send_logs',
        'web_forms.*',
        'social_ads.*',
    ];
@endphp
<li class="nav-item mb-2">
    <a class="nav-link {{ request()->routeIs($settingsActiveRoutes) ? 'active' : '' }}"
       href="{{ route('settings.index') }}">
        <i class="bi bi-gear-fill me-2"></i>
        <span class="nav-label">Settings</span>
    </a>
</li>
