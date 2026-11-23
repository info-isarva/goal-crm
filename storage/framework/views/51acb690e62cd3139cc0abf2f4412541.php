<?php
    $roleId = Auth::user()->crm_role_type ?? null;
    $roleName = null;
    if ($roleId === 0) {
        $roleName = 'Super Admin';
    } elseif (isset($roles) && isset($roles[$roleId])) {
        $roleName = $roles[$roleId];
    } else {
        $roleModel = \App\Models\Role::find($roleId);
        $roleName = $roleModel ? ucfirst($roleModel->name) : 'User';
    }
    $avatar = Auth::user()->avatar;
?>
<nav class="sidebar custom-block offcanvas-md offcanvas-start min-h-screen w-full p-0 lg:w-[270px] lg:shrink-0" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarLabel" style="z-index:1045;">
    <div class="sidebar-shell flex h-full flex-col justify-between">
        <div class="sidebar-brand">
            <?php $company = \App\Models\Company::current(); ?>
            <div class="sidebar-brand-row">
                <div class="sidebar-brand-mark">
                    <img
                        src="<?php echo e($company->logo_url ?? asset('images/logoisarva-1.svg')); ?>"
                        alt="<?php echo e($company->name ?? 'Goal Corporation'); ?>"
                        class="company-logo sidebar-brand-logo"
                    >
                </div>
                <div class="sidebar-brand-text nav-label">
                    <div class="sidebar-brand-title">GOAL CORPORATION</div>
                    <div class="sidebar-brand-sub">Goal Loan CRM</div>
                </div>
            </div>
        </div>

        <div class="sidebar-scroll grow">
            <ul class="nav sidebar-menu sidebar-menu-gap flex-col">
                <?php echo $__env->make('layouts.sidebar_menu', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </ul>
        </div>

        <div class="user-card dropdown dropup">
            
            <button type="button"
                    class="user-card-toggle"
                    id="sidebarUserMenu"
                    data-bs-toggle="dropdown"
                    data-bs-popper-config='{"strategy":"fixed","modifiers":[{"name":"preventOverflow","options":{"boundary":"viewport","padding":8}}]}'
                    aria-expanded="false">
                <?php if($avatar): ?>
                    <img src="<?php echo e(asset('assets/employee_profile_image/' . $avatar)); ?>" alt="<?php echo e(Auth::user()->name); ?>">
                <?php else: ?>
                    <span class="user-avatar-fallback" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                <?php endif; ?>
                <div class="user-meta nav-label">
                    <div class="user-name"><?php echo e(Auth::user()->name); ?></div>
                    <div class="user-role"><?php echo e(strtoupper($roleName)); ?></div>
                </div>
                <i class="bi bi-chevron-down user-card-chevron nav-label" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sidebarUserMenu">
                <li><a class="dropdown-item" href="<?php echo e(route('profile.edit')); ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="<?php echo e(route('password.edit')); ?>"><i class="bi bi-key me-2"></i>Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="<?php echo e(route('logout')); ?>" class="m-0">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Sidebar Toggle Button for Mobile -->

<?php echo $__env->make('layouts.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
    @media (max-width: 767.98px) {
        /* Desktop offcanvas panel only — not the Alpine mobile drawer */
        #sidebarOffcanvas.sidebar {
            width: 80vw !important;
            min-width: 0 !important;
            max-width: 90vw !important;
        }
        #sidebarOffcanvas .sidebar-scroll {
            max-height: calc(100vh - 100px) !important;
        }
    }

     .sidebar-menu-gap .nav-item {
        margin-bottom: 0;
        margin-top: 0;
    }
    .sidebar-menu-gap .nav-item.sidebar-section {
        margin-top: 10px;
        margin-bottom: 2px;
    }
</style>

<!-- Hover flyout panel for collapsed sidebar -->
<style>
    .sidebar-flyout {
        position: fixed;
        background: var(--sb-flyout-bg, #FFFFFF);
        border: 1px solid var(--sb-flyout-border, #E8EEF2);
        box-shadow: var(--sb-flyout-shadow, 0 12px 28px rgba(36, 50, 71, 0.12));
        border-radius: 14px;
        padding: 0.5rem;
        z-index: 2000;
        min-width: 180px;
        max-width: 340px;
        color: var(--sb-flyout-text, #243247);
    }
    html[data-sidebar-theme="soft"],
    html[data-sidebar-theme="light"] {
        --sb-flyout-bg: #FFFFFF;
        --sb-flyout-border: #E2E8F0;
        --sb-flyout-shadow: 0 12px 28px rgba(15, 23, 42, 0.10);
        --sb-flyout-text: #0F172A;
        --sb-flyout-hover: #F1F5F9;
        --sb-flyout-accent: #0F766E;
        --sb-flyout-icon: #0D9488;
    }
    html[data-sidebar-theme="green"] {
        --sb-flyout-bg: #FFFFFF;
        --sb-flyout-border: #E8EEF2;
        --sb-flyout-shadow: 0 12px 28px rgba(36, 50, 71, 0.12);
        --sb-flyout-text: #243247;
        --sb-flyout-hover: rgba(22, 134, 83, 0.08);
        --sb-flyout-accent: #087F5B;
        --sb-flyout-icon: #168653;
    }
    html[data-sidebar-theme="dark"] {
        --sb-flyout-bg: #0d1a2f;
        --sb-flyout-border: rgba(148,163,184,0.2);
        --sb-flyout-shadow: 0 12px 28px rgba(2,8,23,0.45);
        --sb-flyout-text: #e8eef8;
        --sb-flyout-hover: rgba(56,189,248,0.16);
        --sb-flyout-accent: #fff;
        --sb-flyout-icon: #7dd3fc;
    }
    .sidebar-flyout::before {
        content: '';
        position: absolute;
        left: -8px;
        width: 0;
        height: 0;
        border-top: 8px solid transparent;
        border-bottom: 8px solid transparent;
        border-right: 8px solid var(--sb-flyout-bg, #FFFFFF);
        top: var(--arrow-top, 16px);
        filter: drop-shadow(-1px 0 0 var(--sb-flyout-border, #E8EEF2));
    }
    .sidebar-flyout .flyout-title {
        font-weight: 600;
        color: var(--sb-flyout-text, #243247);
        display: flex;
        align-items: center;
        gap: .25rem;
        padding: .5rem;
        border-radius: 10px;
    }
    .sidebar-flyout .flyout-title.clickable { 
        cursor: pointer;
    }
    .sidebar-flyout .flyout-title.clickable:hover {
        background: var(--sb-flyout-hover, rgba(22, 134, 83, 0.08));
    }
    .sidebar-flyout .flyout-submenu {
        margin-top: .25rem;
    }
    .sidebar-flyout .nav-link {
        padding: .35rem .55rem;
        color: var(--sb-flyout-text, #243247);
        font-size: 0.9rem;
        border-radius: 10px;
        gap: .35rem;
    }
    .sidebar-flyout .nav-link:hover {
        background: var(--sb-flyout-hover, rgba(22, 134, 83, 0.08));
        color: var(--sb-flyout-accent, #087F5B);
    }
    .sidebar-flyout .nav-link i {
        color: var(--sb-flyout-icon, #168653);
    }
    .sidebar-theme-switch {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    .sidebar-theme-switch .sidebar-theme-label {
        display: block;
        margin: 0;
        padding: 0;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        line-height: 1.2;
        position: static;
    }
    .sidebar-theme-switch .sidebar-theme-options {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 6px;
        width: 100%;
        position: static;
    }
    .sidebar-theme-switch .sidebar-theme-btn {
        position: static !important;
        float: none !important;
        width: 100%;
        margin: 0 !important;
        font-size: 0.72rem;
        line-height: 1.2;
        padding: 0.4rem 0.35rem;
        border-radius: 8px !important;
        white-space: nowrap;
    }
    .sidebar-theme-btn.is-active {
        background: #087F5B !important;
        border-color: #087F5B !important;
        color: #fff !important;
    }
    html[data-sidebar-theme="dark"] .sidebar-theme-btn.is-active {
        background: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        var flyout = null;
        var hideTimer = null;

        function removeFlyout() {
            if (flyout && flyout.parentNode) {
                flyout.parentNode.removeChild(flyout);
            }
            flyout = null;
        }

        function showFlyoutForLink(link) {
            removeFlyout();
            flyout = document.createElement('div');
            flyout.className = 'sidebar-flyout';

            // title (clone anchor icon + label)
            var title = document.createElement('div');
            title.className = 'flyout-title';
            // clone icon if exists
            var icon = link.querySelector('i');
            if (icon) title.appendChild(icon.cloneNode(true));
            // label text
            var labelSpan = document.createElement('span');
            labelSpan.textContent = link.querySelector('.nav-label') ? link.querySelector('.nav-label').textContent.trim() : link.textContent.trim();
            title.appendChild(labelSpan);
            // behavior for clicking title:
            // - if the link is a normal href (not a hash), navigate to it
            // - if the link targets a collapse submenu (href starts with '#'), toggle that submenu smoothly
            try {
                var href = link.getAttribute('href') || link.getAttribute('data-href') || '';
                var targetSelector = link.getAttribute('href') || link.getAttribute('data-bs-target') || '';
                if (targetSelector && targetSelector.startsWith('#')) {
                    // toggle the submenu collapse when clicking the title
                    title.classList.add('clickable');
                    title.addEventListener('click', function(ev) {
                        ev.preventDefault();
                        var target = document.querySelector(targetSelector);
                        if (!target) return;
                        // If Bootstrap collapse is available, use it for smooth animation
                        try {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                                // initialize if not already
                                var bs = bootstrap.Collapse.getInstance(target);
                                if (!bs) bs = new bootstrap.Collapse(target, { toggle: false });
                                bs.toggle();
                            } else {
                                // fallback: toggle show class
                                target.classList.toggle('show');
                            }
                        } catch (err) {
                            target.classList.toggle('show');
                        }
                    });
                    // also open submenu on hover for quicker access
                    title.addEventListener('mouseenter', function(ev) {
                        ev.preventDefault();
                        var target = document.querySelector(targetSelector);
                        if (!target) return;
                        try {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                                var bsShow = bootstrap.Collapse.getInstance(target);
                                if (!bsShow) bsShow = new bootstrap.Collapse(target, { toggle: false });
                                bsShow.show();
                            } else {
                                target.classList.add('show');
                            }
                        } catch (err) {
                            target.classList.add('show');
                        }
                    });
                } else if (href && !href.startsWith('#') && href.trim() !== '') {
                    title.classList.add('clickable');
                    title.addEventListener('click', function(ev) {
                        ev.preventDefault();
                        window.location.href = href;
                    });
                }
            } catch (e) { /* ignore */ }
            flyout.appendChild(title);

            // if link toggles a collapse submenu, clone submenu contents
            var targetSelector = link.getAttribute('href') || link.getAttribute('data-bs-target');
            if (targetSelector && targetSelector.startsWith('#')) {
                var target = document.querySelector(targetSelector);
                if (target) {
                    var submenu = document.createElement('div');
                    submenu.className = 'flyout-submenu';
                    // clone inner list of links
                    var clone = target.querySelector('ul');
                    if (clone) {
                        submenu.appendChild(clone.cloneNode(true));
                        flyout.appendChild(submenu);
                        // add click handlers to submenu links so they navigate without closing sidebar
                        var submenuLinks = submenu.querySelectorAll('a.nav-link');
                        submenuLinks.forEach(function(sublink) {
                            sublink.addEventListener('click', function(ev) {
                                
                                var subhref = sublink.getAttribute('href');
                                if (subhref && !subhref.startsWith('#')) {
                                    // navigate to submenu link without closing/expanding sidebar
                                    ev.preventDefault();
                                    window.location.href = subhref;
                                }
                            });
                        });
                    }
                }
            }

            document.body.appendChild(flyout);

            // position to the right of sidebar (or offcanvas) near the link
            var rect = link.getBoundingClientRect();
            var sidebarRect = sidebar.getBoundingClientRect();
            var left = sidebarRect.right + 8;
            var top = Math.max(8, rect.top - 8);
            // ensure within viewport vertically
            var maxTop = window.innerHeight - flyout.offsetHeight - 8;
            if (top > maxTop) top = Math.max(8, maxTop);
            flyout.style.left = left + 'px';
            flyout.style.top = top + 'px';

            // position arrow vertically so it points to the link center
            try {
                var arrowOffset = (rect.top + (rect.height / 2)) - top - 8; // adjust for triangle height
                flyout.style.setProperty('--arrow-top', (arrowOffset > 8 ? arrowOffset : 8) + 'px');
            } catch (e) { /* ignore */ }

            // when mouse leaves flyout, hide it
            flyout.addEventListener('mouseenter', function() { clearTimeout(hideTimer); });
            flyout.addEventListener('mouseleave', function() { hideTimer = setTimeout(removeFlyout, 120); });
        }

        // delegate mouseenter on nav-links
        sidebar.addEventListener('mouseover', function(e) {
            var link = e.target.closest('.nav-link');
            if (!link) return;
            // only show flyout when sidebar is collapsed (or small screens)
            if (!sidebar.classList.contains('collapsed')) return;
            clearTimeout(hideTimer);
            // short delay to avoid flicker
            hideTimer = setTimeout(function() { showFlyoutForLink(link); }, 80);
        });

        // If hovering specifically over the down/up chevron icon, open the related submenu flyout.
        sidebar.addEventListener('mouseover', function(e) {
            var che = e.target.closest('.bi-chevron-down, .bi-chevron-up');
            if (!che) return;
            // find the related nav-link
            var link = che.closest('.nav-link') || (che.closest('.nav-item') ? che.closest('.nav-item').querySelector('.nav-link') : null);
            if (!link) return;
            if (!sidebar.classList.contains('collapsed')) return;
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function() { showFlyoutForLink(link); }, 80);
        });

        sidebar.addEventListener('mouseout', function(e) {
            var related = e.relatedTarget;
            // if moving into flyout, keep it
            if (flyout && related && flyout.contains(related)) return;
            clearTimeout(hideTimer);
            hideTimer = setTimeout(removeFlyout, 180);
        });

        // cleanup on scroll/resize
        window.addEventListener('scroll', function() { removeFlyout(); });
        window.addEventListener('resize', function() { removeFlyout(); });

        // Click behavior: when sidebar is collapsed and user clicks the down-arrow or a link that targets a collapse,
        // open the flyout submenu so they can interact with it via click.
        sidebar.addEventListener('click', function(e) {
            if (!sidebar.classList.contains('collapsed')) return;
            var clickedChevron = e.target.closest('.bi-chevron-down, .bi-chevron-up');
            var link = e.target.closest('.nav-link');
            if (!link && clickedChevron) {
                // maybe clicked the chevron inside a nav-link; find the closest nav-item's link
                link = e.target.closest('.nav-item') ? e.target.closest('.nav-item').querySelector('.nav-link') : null;
            }
            if (!link) return;

            var targetSelector = link.getAttribute('href') || link.getAttribute('data-bs-target') || '';
            if (targetSelector && targetSelector.startsWith('#')) {
                e.preventDefault();
                e.stopPropagation();
                try { showFlyoutForLink(link); } catch (err) { /* ignore */ }
            }
        });

        // Add hover functionality for submenu titles
        sidebar.addEventListener('mouseover', function(e) {
            var submenuTitle = e.target.closest('.flyout-title');
            if (!submenuTitle) return;

            var targetSelector = submenuTitle.getAttribute('data-bs-target') || submenuTitle.getAttribute('href');
            if (targetSelector && targetSelector.startsWith('#')) {
                var target = document.querySelector(targetSelector);
                if (target) {
                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                            var bsInstance = bootstrap.Collapse.getInstance(target);
                            if (!bsInstance) bsInstance = new bootstrap.Collapse(target, { toggle: false });
                            bsInstance.show();
                        } else {
                            target.classList.add('show');
                        }
                    } catch (err) {
                        target.classList.add('show');
                    }
                }
            }
        });

        sidebar.addEventListener('mouseout', function(e) {
            var submenuTitle = e.target.closest('.flyout-title');
            if (!submenuTitle) return;

            var targetSelector = submenuTitle.getAttribute('data-bs-target') || submenuTitle.getAttribute('href');
            if (targetSelector && targetSelector.startsWith('#')) {
                var target = document.querySelector(targetSelector);
                if (target) {
                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                            var bsInstance = bootstrap.Collapse.getInstance(target);
                            if (!bsInstance) bsInstance = new bootstrap.Collapse(target, { toggle: false });
                            bsInstance.hide();
                        } else {
                            target.classList.remove('show');
                        }
                    } catch (err) {
                        target.classList.remove('show');
                    }
                }
            }
        });
    });
</script>

<!-- Remove native browser tooltips inside sidebar by moving `title` into `aria-label` -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        // Function to remove tooltips from elements
        function removeTooltips() {
            var elementsWithTooltips = sidebar.querySelectorAll('[title], [data-bs-toggle], [data-bs-original-title], [data-bs-title]');
            elementsWithTooltips.forEach(function(el) {
                try {
                    if (el.hasAttribute('title')) {
                        el.removeAttribute('title');
                    }
                    // if (el.getAttribute('data-bs-toggle') === 'tooltip') {
                    //     el.removeAttribute('data-bs-toggle');
                    // }
                    if (el.hasAttribute('data-bs-original-title')) {
                        el.removeAttribute('data-bs-original-title');
                    }
                    if (el.hasAttribute('data-bs-title')) {
                        el.removeAttribute('data-bs-title');
                    }
                } catch (e) {
                    console.error('Error removing tooltip attributes:', e);
                }
            });
        }

        // Initial tooltip removal
        removeTooltips();

        // Reapply tooltip removal on sidebar toggle button click
        var toggleButton = document.querySelector('[data-bs-toggle="offcanvas"]');
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                setTimeout(removeTooltips, 100); // Delay to ensure DOM updates are applied
            });
        }
    });

    // document.addEventListener('DOMContentLoaded', function() {
    //     const sidebar = document.querySelector('#sidebarOffcanvas');
    //     const crmText = document.querySelector('span.nav-label.text-white');

    //     document.querySelector('[data-bs-toggle="offcanvas"]').addEventListener('click', function() {
    //         if (sidebar.classList.contains('collapsed')) {
    //             sidebar.classList.remove('collapsed');
    //             crmText.style.display = 'inline';
    //         } else {
    //             sidebar.classList.add('collapsed');
    //             crmText.style.display = 'none';
    //         }
    //     });
    // });
</script>
<?php /**PATH C:\laragon\www\CEM-Goal Corporation\isarva-crm-ver2\resources\views/layouts/sidebar.blade.php ENDPATH**/ ?>