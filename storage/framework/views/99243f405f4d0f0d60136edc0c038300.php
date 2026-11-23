
<?php
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
?>
<li class="nav-item">
    <a class="nav-link <?php echo e(request()->routeIs($settingsActiveRoutes) ? 'active' : ''); ?>"
       href="<?php echo e(route('settings.index')); ?>">
        <i class="bi bi-gear-fill"></i>
        <span class="nav-label">Settings</span>
    </a>
</li>
<?php /**PATH C:\laragon\www\CEM-Goal Corporation\isarva-crm-ver2\resources\views/layouts/sidebar_menu_settings.blade.php ENDPATH**/ ?>