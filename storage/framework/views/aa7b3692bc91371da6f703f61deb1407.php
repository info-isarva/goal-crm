
<?php
    $isGoalNav = \App\Services\Goal\GoalDashboardService::userUsesGoalDashboard(auth()->user());
?>

<?php if($isGoalNav): ?>
    <?php echo $__env->make('layouts.sidebar_menu_goal', ['goalNavMode' => 'full'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php else: ?>
    <li class="nav-item sidebar-section"><span class="sidebar-section-label">Dashboard</span></li>
    <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>"><i class="bi bi-house-door"></i> <span class="nav-label">Home</span></a></li>
    <?php echo $__env->make('layouts.sidebar_menu_reports', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('layouts.sidebar_menu_goal', ['goalNavMode' => 'modules'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <li class="nav-item sidebar-section"><span class="sidebar-section-label">Workspace</span></li>

    <?php if((auth()->user()->hasCrmPermission('view_crm_leads_guard') || auth()->user()->hasCrmPermission('create_crm_leads_guard') || auth()->user()->hasCrmPermission('edit_crm_leads_guard') || auth()->user()->hasCrmPermission('delete_crm_leads_guard') || auth()->user()->hasCrmPermission('manage_crm_leads_guard'))
        && auth()->user()->hasCrmPermission('manage_crm_leads_guard')): ?>
    <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('leads.*') ? 'active' : ''); ?>" href="<?php echo e(route('leads.index')); ?>"><i class="bi bi-people-fill"></i> <span class="nav-label">Leads</span></a></li>
    <?php endif; ?>
    <?php if((auth()->user()->hasCrmPermission('view_crm_organization_guard') || auth()->user()->hasCrmPermission('create_crm_organization_guard') || auth()->user()->hasCrmPermission('edit_crm_organization_guard') || auth()->user()->hasCrmPermission('delete_crm_organization_guard') || auth()->user()->hasCrmPermission('manage_crm_organization_guard')) && auth()->user()->hasCrmPermission('manage_crm_organization_guard')): ?>
    <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('organizations.*') ? 'active' : ''); ?>" href="<?php echo e(route('organizations.index')); ?>"><i class="bi bi-building"></i> <span class="nav-label">Company</span></a></li>
    <?php endif; ?>

    <?php if((auth()->user()->hasCrmPermission('view_crm_deals_guard') || auth()->user()->hasCrmPermission('create_crm_deals_guard') || auth()->user()->hasCrmPermission('edit_crm_deals_guard') || auth()->user()->hasCrmPermission('delete_crm_deals_guard') || auth()->user()->hasCrmPermission('manage_crm_deals_guard')) && auth()->user()->hasCrmPermission('manage_crm_deals_guard')): ?>
    <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('deals.*') ? 'active' : ''); ?>" href="<?php echo e(route('deals.index')); ?>"><i class="bi bi-briefcase-fill"></i> <span class="nav-label">Deals</span></a></li>
    <?php endif; ?>

    <?php if(
        auth()->user()->hasCrmPermission('manage_crm_daily_report_guard')
        || auth()->user()->hasCrmPermission('create_crm_daily_report_guard')
        || auth()->user()->hasCrmPermission('view_crm_daily_report_guard')
        || (int) (auth()->user()->crm_role_type ?? -1) === 0
    ): ?>
    <li class="nav-item">
        <a class="nav-link <?php echo e(request()->routeIs('daily_reports.*') ? 'active' : ''); ?>" href="<?php echo e(route('daily_reports.index')); ?>">
            <i class="bi bi-journal-text"></i> <span class="nav-label">Daily Report</span>
        </a>
    </li>
    <?php endif; ?>

    <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('tasks.index') ? 'active' : ''); ?>" href="<?php echo e(route('tasks.index')); ?>"><i class="fas fa-tasks"></i> <span class="nav-label">Tasks</span></a></li>

    <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('meetings.index') ? 'active' : ''); ?>" href="<?php echo e(route('meetings.index')); ?>"><i class="fas fa-calendar-alt"></i> <span class="nav-label">Meeting</span></a></li>

    <?php if((auth()->user()->hasCrmPermission('view_crm_product_guard') || auth()->user()->hasCrmPermission('create_crm_product_guard') || auth()->user()->hasCrmPermission('edit_crm_product_guard') || auth()->user()->hasCrmPermission('delete_crm_product_guard') || auth()->user()->hasCrmPermission('manage_crm_product_guard')) && auth()->user()->hasCrmPermission('manage_crm_product_guard')): ?>
    <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('products.*') ? 'active' : ''); ?>" href="<?php echo e(route('products.index')); ?>"><i class="bi bi-box-seam"></i> <span class="nav-label">Products</span></a></li>
    <?php endif; ?>
    <?php if((auth()->user()->hasCrmPermission('view_crm_quotations_guard') || auth()->user()->hasCrmPermission('create_crm_quotations_guard') || auth()->user()->hasCrmPermission('edit_crm_quotations_guard') || auth()->user()->hasCrmPermission('delete_crm_quotations_guard') || auth()->user()->hasCrmPermission('manage_crm_quotations_guard')) && auth()->user()->hasCrmPermission('manage_crm_quotations_guard')): ?>
    <li class="nav-item"><a class="nav-link <?php echo e(request()->routeIs('quotations.*') && !request()->routeIs(['quotations.email_send_logs', 'quotations.whatsapp_send_logs']) ? 'active' : ''); ?>" href="<?php echo e(route('quotations.index')); ?>"><i class="bi bi-receipt"></i> <span class="nav-label">Quotations</span></a></li>
    <?php endif; ?>

    <li class="nav-item sidebar-section"><span class="sidebar-section-label">System</span></li>
    <?php echo $__env->make('layouts.sidebar_menu_settings', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>

<li class="nav-item">
    <form method="POST" action="<?php echo e(route('logout')); ?>">
        <?php echo csrf_field(); ?>
        <button type="submit" class="nav-link text-danger">
            <i class="bi bi-box-arrow-right"></i> <span class="nav-label">Sign out</span>
        </button>
    </form>
</li>
<?php /**PATH C:\laragon\www\CEM-Goal Corporation\isarva-crm-ver2\resources\views/layouts/sidebar_menu.blade.php ENDPATH**/ ?>