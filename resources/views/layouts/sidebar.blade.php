@php
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
@endphp
<nav class="sidebar min-vh-100 p-0 col-12 col-md-2 custom-block offcanvas-md offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarLabel" style="z-index:1045;">
    <div class="d-flex flex-column h-100 justify-content-between sidebar-shell">
        <div class="sidebar-brand">
            <div class="sidebar-brand-mark">
                @php $company = \App\Models\Company::current(); @endphp
                <img
                    src="{{ $company->logo_url ?? asset('images/logoisarva-1.svg') }}"
                    alt="{{ $company->name ?? 'Isarva' }}"
                    class="company-logo sidebar-brand-logo"
                >
            </div>
            <button class="btn btn-sm sidebar-close-mobile d-md-none" type="button" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="sidebar-scroll flex-grow-1">
            <ul class="nav flex-column sidebar-menu sidebar-menu-gap">
                @include('layouts.sidebar_menu')
            </ul>
        </div>

        <div class="user-card d-flex align-items-center">
            @if($avatar)
                <img src="{{ asset('assets/employee_profile_image/' . $avatar) }}" alt="{{ Auth::user()->name }}">
            @else
                <span class="user-avatar-fallback" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
            @endif
            <div class="user-meta nav-label">
                <div class="user-name">{{ Auth::user()->name }}</div>
                <div class="user-role">{{ strtoupper($roleName) }}</div>
            </div>
        </div>
    </div>
</nav>

<!-- Sidebar Toggle Button for Mobile -->
{{-- <button :class="{ 'open': open }"  class="btn btn-warning d-md-none position-fixed top-0 start-0 m-2 z-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" style="border-radius:50%;width:48px;height:48px;">
    <i class="bi bi-list fs-3"></i>
</button> --}}
@include('layouts.navigation')
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
    }
    .sidebar-menu-gap .nav-item.sidebar-section {
        margin-top: 14px;
        margin-bottom: 4px;
    }
</style>

<!-- Hover flyout panel for collapsed sidebar -->
<style>
    .sidebar-flyout {
        position: fixed;
        background: #0d1a2f;
        border: 1px solid rgba(148,163,184,0.2);
        box-shadow: 0 12px 28px rgba(2,8,23,0.45);
        border-radius: 14px;
        padding: 0.5rem;
        z-index: 2000;
        min-width: 180px;
        max-width: 340px;
        color: #e8eef8;
    }
    .sidebar-flyout::before {
        content: '';
        position: absolute;
        left: -8px;
        width: 0;
        height: 0;
        border-top: 8px solid transparent;
        border-bottom: 8px solid transparent;
        border-right: 8px solid #0d1a2f;
        top: var(--arrow-top, 16px);
    }
    .sidebar-flyout .flyout-title {
        font-weight: 600;
        color: #f8fafc;
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
        background: rgba(56,189,248,0.16);
    }
    .sidebar-flyout .flyout-submenu {
        margin-top: .25rem;
    }
    .sidebar-flyout .nav-link {
        padding: .35rem .55rem;
        color: #d7e0ee;
        font-size: 0.9rem;
        border-radius: 10px;
        gap: .35rem;
    }
    .sidebar-flyout .nav-link:hover {
        background: rgba(56,189,248,0.16);
        color: #fff;
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
