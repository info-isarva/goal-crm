document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Custom Script Loaded ===');
    
    // Bootstrap's data-api should handle collapse
    if(typeof bootstrap !== 'undefined') {
        console.log('✓ Bootstrap loaded');
        initFixedTableDropdowns();
    } else {
        console.warn('⚠️ Bootstrap not available');
    }

    /**
     * Keep row action (3-dots) menus above scrollable tables / pipeline columns.
     * overflow:auto|hidden on .table-responsive clips absolute dropdowns.
     */
    function initFixedTableDropdowns() {
        if (!bootstrap.Dropdown) return;

        var selector = [
            '.table-responsive [data-bs-toggle="dropdown"]',
            '.pipeline-body [data-bs-toggle="dropdown"]',
            '.deal-card [data-bs-toggle="dropdown"]',
            'table td [data-bs-toggle="dropdown"]',
            'table th [data-bs-toggle="dropdown"]'
        ].join(',');

        document.querySelectorAll(selector).forEach(function(el) {
            if (el.closest('nav, .navbar, .sidebar, #sidebarOffcanvas, header.navbar')) {
                return;
            }

            el.setAttribute('data-bs-popper-config', JSON.stringify({ strategy: 'fixed' }));

            var existing = bootstrap.Dropdown.getInstance(el);
            if (existing) {
                existing.dispose();
            }

            new bootstrap.Dropdown(el, {
                popperConfig: function(defaultConfig) {
                    var modifiers = (defaultConfig && defaultConfig.modifiers)
                        ? defaultConfig.modifiers.slice()
                        : [];
                    modifiers.push({
                        name: 'preventOverflow',
                        options: { boundary: 'viewport', padding: 8 }
                    });
                    return Object.assign({}, defaultConfig, {
                        strategy: 'fixed',
                        modifiers: modifiers
                    });
                }
            });
        });
    }
    
    // Add logging AND fix to track collapse behavior
    // DO NOT prevent default - let Bootstrap's data-api handle the toggle
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            var href = this.getAttribute('href') || this.getAttribute('data-bs-target');
            if(!href || !href.startsWith('#')) return;
            
            var target = document.querySelector(href);
            if(!target) {
                console.warn('Collapse target not found:', href);
                return;
            }
            
            console.log('Collapse trigger clicked:', href);
            var wasShow = target.classList.contains('show');
            console.log('  Before: .show =', wasShow, 'height =', getComputedStyle(target).height);
            
            // The problem: Bootstrap toggles height but takes time to add .show class
            // Solution: After Bootstrap starts the animation, ensure .show is present
            // We need to check in a loop until the height is NOT 0 (meaning it's expanding)
            var checkShow = setInterval(function() {
                var currentHeight = getComputedStyle(target).height;
                var heightInPx = parseFloat(currentHeight);
                
                // If height is animating (not 0 and not exactly at full height yet), force add .show
                if(heightInPx > 0 && !target.classList.contains('show')) {
                    console.log('  Force adding .show class (height=' + currentHeight + ')');
                    target.classList.add('show');
                }
                
                // Once animation finishes (height stable or reaches end), stop checking
                if((heightInPx === 0) || (heightInPx > 300)) {
                    clearInterval(checkShow);
                    console.log('  Final: .show =', target.classList.contains('show'), 'height =', getComputedStyle(target).height);
                }
            }, 30);
            
            // CRITICAL: Do NOT call preventDefault() or stopPropagation()
            // Bootstrap's data-api needs the click event to propagate naturally
        });
    });

});

// Sidebar toggle logic (desktop collapse)
(function(){
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebarOffcanvas') || document.querySelector('.sidebar');
    var body = document.body;

    function applyState(collapsed) {
        if(!sidebar) return;
        if(collapsed) {
            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
        } else {
            sidebar.classList.remove('collapsed');
            body.classList.remove('sidebar-collapsed');
        }
    }

    // Initialize from localStorage (desktop only)
    try {
        var stored = localStorage.getItem('sidebar-collapsed');
        if(stored === '1') applyState(true);
    } catch(e) { /* ignore */ }

    if(toggleBtn) {
        toggleBtn.addEventListener('click', function(e){
            e.preventDefault();
            // Tablet/mobile use Alpine mobile drawer (navigation.blade.php), not Bootstrap offcanvas.
            // Calling Offcanvas here left a stuck .offcanvas-backdrop that locked the whole UI.
            if(window.innerWidth < 1024) {
                try {
                    var existing = bootstrap.Offcanvas.getInstance(sidebar);
                    if (existing) existing.hide();
                } catch (err) { /* ignore */ }
                document.querySelectorAll('.offcanvas-backdrop').forEach(function (el) {
                    el.remove();
                });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
                return;
            }

            var now = sidebar.classList.contains('collapsed');
            applyState(!now);
            // If the sidebar was collapsed and is now being expanded, ensure any hover popover is closed.
            // (window.hideSidebarPopover is exposed by the popover module when available)
            if (now) {
                try { window.hideSidebarPopover && window.hideSidebarPopover(); } catch(e){}
            }
            try { localStorage.setItem('sidebar-collapsed', !now ? '1' : '0'); } catch(e){}
            // rotate chevron icon
            var icon = toggleBtn.querySelector('i');
            if(icon) icon.classList.toggle('bi-arrow-bar-right', !now), icon.classList.toggle('bi-arrow-bar-left', now);
            // update nav-link tooltips for collapsed/expanded state if helpers available
            try { window.updateSidebarTooltips && window.updateSidebarTooltips(!now); } catch(e) {}
            try { window.enableSidebarBootstrapTooltips && window.enableSidebarBootstrapTooltips(!now); } catch(e) {}
        });
    }
})();


// Show single-item popover for hovered icon when sidebar is collapsed
(function(){
    var sidebar = document.querySelector('.sidebar');
    var hoverPop = document.getElementById('sidebarHoverPop');
    var popMenu = hoverPop ? hoverPop.querySelector('.popover-menu') : null;
    var hideTimer = null;
    var currentLink = null;
    var rafPos = null;
    var pinned = false;

    // manage simple native tooltips for collapsed sidebar (title attributes)
    function setNavTitles(collapsed) {
        if(!sidebar) return;
        var isCollapsed = typeof collapsed === 'boolean' ? collapsed : (document.body.classList.contains('sidebar-collapsed') || sidebar.classList.contains('collapsed'));
        var links = sidebar.querySelectorAll('.nav-link');
        links.forEach(function(link){
            try {
                var label = link.querySelector('.nav-label');
                var text = label ? label.textContent.trim() : link.textContent.trim();
                if(isCollapsed) {
                    // set native title so user sees tooltip on hover
                    link.setAttribute('title', text);
                    link.setAttribute('aria-label', text);
                } else {
                    // remove title when expanded to avoid duplicate native tooltip
                    link.removeAttribute('title');
                    // keep aria-label for accessibility
                    // link.removeAttribute('aria-label');
                }
            } catch(e) { /* ignore individual link errors */ }
        });
    }

    // expose for toggle handler to call (safe no-op if overwritten)
    try { window.updateSidebarTooltips = setNavTitles; } catch(e) {}

    // Bootstrap-styled tooltips manager
    var _bsTooltips = new Map();
    function enableBootstrapTooltips(collapsed) {
        if(typeof collapsed === 'undefined') collapsed = (document.body.classList.contains('sidebar-collapsed') || sidebar.classList.contains('collapsed'));
        var links = sidebar ? sidebar.querySelectorAll('.nav-link') : [];
        if(!links || links.length === 0) return;
        // if bootstrap Tooltip not available, fallback to native titles
        if(typeof bootstrap === 'undefined' || typeof bootstrap.Tooltip === 'undefined') {
            // ensure native titles are set/removed accordingly
            try { setNavTitles(collapsed); } catch(e) {}
            return;
        }

        if(collapsed) {
            links.forEach(function(link){
                try {
                    var label = link.querySelector('.nav-label');
                    var text = label ? label.textContent.trim() : link.textContent.trim();
                    if(!text) return;
                    // set title for tooltip content
                    link.setAttribute('title', text);
                    link.setAttribute('data-bs-toggle', 'tooltip');
                    // avoid double-init
                    if(!_bsTooltips.has(link)) {
                        var tip = new bootstrap.Tooltip(link, {placement: 'right', trigger: 'hover focus', delay: { show: 120, hide: 80 }});
                        _bsTooltips.set(link, tip);
                    }
                } catch(e) { /* ignore */ }
            });
        } else {
            // dispose any existing Bootstrap tooltips and remove attributes
            _bsTooltips.forEach(function(tip, link){
                try { tip.dispose(); } catch(e) {}
            });
            _bsTooltips.clear();
            links.forEach(function(link){
                try { link.removeAttribute('data-bs-toggle'); link.removeAttribute('title'); } catch(e) {}
            });
        }
    }
    try { window.enableSidebarBootstrapTooltips = enableBootstrapTooltips; } catch(e) {}

    console.log('Sidebar popover module init', { sidebar: !!sidebar, hoverPop: !!hoverPop, popMenu: !!popMenu });

    function buildContentForLink(link) {
        // pick the first icon that is not a chevron (avoid grabbing the right-side collapse chevron)
        var iconEl = null;
        var icons = link.querySelectorAll('i');
        for(var ii=0; ii<icons.length; ii++){
            var cls = icons[ii].className || '';
            if(!/chevron/.test(cls)) { iconEl = icons[ii]; break; }
        }
        var labelEl = link.querySelector('.nav-label');
        var title = labelEl ? labelEl.textContent.trim() : link.textContent.trim();
        var iconHtml = iconEl ? iconEl.outerHTML : '';

        // Helper: recursively build items from a UL element
        function buildItemsFromUl(ul) {
            var html = '<div class="sub-items">';
            Array.prototype.forEach.call(ul.children, function(li){
                if(li.matches && li.matches('li')) {
                    var a = li.querySelector('a.nav-link');
                    if(!a) return;
                    // clone the anchor and remove any chevron icons so we don't duplicate toggles
                    var aClone = a.cloneNode(true);
                    // remove any chevrons or popover-specific markers from clones
                    aClone.querySelectorAll('.bi-chevron-down, .bi-chevron-right, .bi-chevron-left, .popover-toggle, .bi-popover-toggle').forEach(function(n){ n.remove(); });
                    // strip common large-padding classes (bootstrap) that could enlarge the popover rows
                    aClone.classList.remove('px-3','py-2','ps-3','pe-3','ms-2','me-2');
                    // ensure cloned anchors are not focusable/tabbable (popover handles interactions)
                    try{ aClone.setAttribute('tabindex','-1'); }catch(e){}
                    var aHtml = aClone.outerHTML;
                    // check for nested ul inside this li
                    var nested = li.querySelector('ul.nav');
                    var nestedHtml = '';
                    var hasNested = false;
                    if(nested) {
                        hasNested = true;
                        nestedHtml = buildItemsFromUl(nested);
                    }
                    // toggle only if nested exists
                    var toggle = hasNested ? '<span class="popover-toggle" title="Show submenu"><i class="bi bi-chevron-down bi-popover-toggle"></i></span>' : '';
                    // wrap anchor and toggle inside .item-row so sub-items (nestedHtml) are the next sibling element
                    var itemHtml = '<div class="popover-item"><div class="item-row">' + aHtml + toggle + '</div>' + nestedHtml + '</div>';
                    html += itemHtml;
                }
            });
            html += '</div>';
            return html;
        }

        var submenuHtml = '';
        var href = link.getAttribute('href') || '';
        if(href && href.startsWith('#')) {
            var submenu = document.querySelector(href);
            if(submenu) {
                var ul = submenu.querySelector('ul.nav') || submenu.querySelector('div');
                if(ul) {
                    submenuHtml = buildItemsFromUl(ul);
                }
            }
        }
        if(!submenuHtml) {
            var nestedRoot = link.parentElement.querySelector('ul.nav');
            if(nestedRoot) submenuHtml = buildItemsFromUl(nestedRoot);
        }

    var toggleHtml = submenuHtml ? '<span class="popover-toggle" title="Show submenu"><i class="bi bi-chevron-down bi-popover-toggle"></i></span>' : '';

    // sanitize title to remove stray chevrons
    var safeTitle = title.replace(/[\u2190-\u21FF]|chevron/gi, '').trim();
    // build as a popover-item so the delegated toggle handler can reliably find the sibling .sub-items
    // put the icon inside the anchor so icon and title are in the same inline element
    // insert the raw icon HTML directly before the title (no wrapper) to avoid extra spacing
    var html = '<div class="popover-item"><div class="item-row"><a class="nav-link" href="'+(link.getAttribute('href')||'#')+'" tabindex="-1">'+iconHtml+'<strong>'+safeTitle+'</strong></a>' + toggleHtml + '</div>' + submenuHtml + '</div>';
        return html;
    }

    function showForLink(link) {
        if(!sidebar || !hoverPop || !popMenu) {
            console.warn('Popover setup incomplete:', {sidebar: !!sidebar, hoverPop: !!hoverPop, popMenu: !!popMenu});
            return;
        }
        
        var isCollapsed = document.body.classList.contains('sidebar-collapsed') || sidebar.classList.contains('collapsed');
        if(!isCollapsed) {
            console.log('Sidebar not collapsed, skipping popover');
            return;
        }
        if(!link) return;

        // build and insert content
        var content = buildContentForLink(link);
        popMenu.innerHTML = content;
        
        // show first so we can measure size, then position centered vertically beside icon
        hoverPop.style.display = 'block';
        hoverPop.setAttribute('aria-hidden','false');
        hoverPop.style.visibility = 'visible';
        hoverPop.style.opacity = '1';

        // track current link so we can reposition on scroll
        // If already pinned to this link, do nothing
        currentLink = link;

        // position now and schedule any needed reflows
        scheduleReposition();
        
        // Auto-open the first submenu after DOM has settled (like Zoho behavior)
        // Use a small delay to ensure the DOM is fully rendered before manipulating classes
        setTimeout(function() {
            try {
                var firstSub = popMenu.querySelector('.sub-items');
                if(firstSub) {
                    console.log('Auto-opening first submenu in popover');
                    firstSub.classList.add('show');
                    // also mark the toggle as open if present
                    var parentItem = firstSub.closest('.popover-item');
                    if(parentItem) {
                        var toggle = parentItem.querySelector('.popover-toggle');
                        if(toggle) {
                            toggle.classList.add('open');
                            console.log('Marked toggle as open');
                        }
                    } else {
                        // fallback: open any top-level toggle
                        var topToggle = popMenu.querySelector('.popover-toggle');
                        if(topToggle) {
                            topToggle.classList.add('open');
                            console.log('Marked top-level toggle as open');
                        }
                    }
                }
            } catch(e) { 
                console.warn('Error auto-opening submenu:', e);
            }
        }, 50);
    }

    function hideForLink(link){
        if(currentLink === link) {
            hidePop();
            currentLink = null;
            pinned = false;
        }
    }

    function positionOnly() {
        if(!hoverPop || !currentLink) return;
        var rect = currentLink.getBoundingClientRect();
        var popRect = hoverPop.getBoundingClientRect();

        // Use viewport coordinates (getBoundingClientRect) — do not add window scroll offsets.
        var left = rect.right + 8;
        var top = rect.top + (rect.height / 2) - (popRect.height / 2);

        var viewportWidth = document.documentElement.clientWidth || window.innerWidth;
        if (left + popRect.width > viewportWidth - 8) {
            left = rect.left - popRect.width - 8;
        }

        var minTop = 8;
        var maxTop = (window.innerHeight - popRect.height - 8);
        if (top < minTop) top = minTop;
        if (top > maxTop) top = maxTop;

        hoverPop.style.left = left + 'px';
        hoverPop.style.top = top + 'px';
    }

    function scheduleReposition(){
        if(rafPos) cancelAnimationFrame(rafPos);
        rafPos = requestAnimationFrame(function(){
            positionOnly();
            rafPos = null;
        });
    }

    function hidePop(){ 
        if(hoverPop){ 
            hoverPop.style.display='none'; 
            hoverPop.style.visibility = 'hidden';
            hoverPop.style.opacity = '0';
            hoverPop.setAttribute('aria-hidden','true'); 
            popMenu && (popMenu.innerHTML=''); 
        }
    }

    // attach listeners to individual nav-links
    function bindNavLinks() {
        if(!sidebar) {
            console.warn('bindNavLinks: sidebar element not found');
            return;
        }
        var links = sidebar.querySelectorAll('.nav-link');
        if(!links || links.length === 0) {
            console.warn('bindNavLinks: no nav-link elements found inside sidebar');
            return;
        }
        links.forEach(function(link){
            // mouseenter shows popover for that single link when sidebar is collapsed
            link.addEventListener('mouseenter', function(e){
                var isCollapsed = document.body.classList.contains('sidebar-collapsed') || sidebar.classList.contains('collapsed');
                if(!isCollapsed) return;
                if(hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
                showForLink(link);
            });

            // click behavior when sidebar is collapsed:
            // - if the link points to a hash (collapse target) we intercept and show/pin the popover
            // - otherwise allow normal navigation (click opens the related page)
            link.addEventListener('click', function(e){
                var isCollapsed = document.body.classList.contains('sidebar-collapsed') || sidebar.classList.contains('collapsed');
                if(!isCollapsed) return;
                var href = link.getAttribute('href') || '';
                // if href is a collapse/hash target, intercept and show/pin popover
                if(href && href.startsWith('#')) {
                    e.preventDefault();
                    if(currentLink === link && pinned) {
                        hideForLink(link);
                    } else {
                        pinned = true;
                        if(hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
                        showForLink(link);
                    }
                    return;
                }
                // otherwise let the browser navigate to the link (no preventDefault)
                // but close any transient popover first
                if(currentLink && !pinned) hidePop();
            });

            // mouseleave starts a short timer to hide (unless popover hovered)
            link.addEventListener('mouseleave', function(){
                if(hideTimer) clearTimeout(hideTimer);
                // only auto-hide on mouseleave when not pinned
                hideTimer = setTimeout(function(){
                    // guard hoverPop existence before calling matches()
                    var popHovered = !(hoverPop && typeof hoverPop.matches === 'function') ? false : hoverPop.matches(':hover');
                    if(!popHovered && !pinned) { hidePop(); currentLink = null; }
                }, 120);
            });
        });
    }

    // keep open while hovering popover
    hoverPop && hoverPop.addEventListener('mouseenter', function(){ if(hideTimer){ clearTimeout(hideTimer); hideTimer=null; } });
    hoverPop && hoverPop.addEventListener('mouseleave', function(){ if(!pinned){ hidePop(); currentLink = null; } });

    // clicking outside the sidebar or popover closes any pinned popover
    document.addEventListener('click', function(e){
        if(!currentLink) return;
        if(e.target.closest && (e.target.closest('#sidebarHoverPop') || e.target.closest('.sidebar'))) return;
        hidePop(); currentLink = null; pinned = false;
    }, { capture: true });

    // reposition popover when page scrolls or resizes so it stays near the icon
    function onScrollOrResize(){
        if(!currentLink) return;
        scheduleReposition();
    }
    window.addEventListener('resize', onScrollOrResize, { passive: true });
    // listen to window/document scroll — passive listener to avoid blocking
    window.addEventListener('scroll', onScrollOrResize, { passive: true });
    document.addEventListener('scroll', onScrollOrResize, { passive: true });

    // Also bind to any scrollable ancestor of the sidebar so nested container scrolling repositions the popover
    var _boundScrollParents = [];
    function isScrollable(el) {
        if(!el || el === document.documentElement) return false;
        var style = getComputedStyle(el);
        var overflowY = style.overflowY;
        return (overflowY === 'auto' || overflowY === 'scroll') && el.scrollHeight > el.clientHeight;
    }

    function bindScrollParents() {
        try {
            var el = sidebar;
            while(el && el !== document.documentElement) {
                if(isScrollable(el)) {
                    if(_boundScrollParents.indexOf(el) === -1) {
                        el.addEventListener('scroll', onScrollOrResize, { passive: true });
                        _boundScrollParents.push(el);
                    }
                }
                el = el.parentElement;
            }
            // also try the main content container if present
            var mainContainer = document.querySelector('.container-fluid') || document.querySelector('main');
            if(mainContainer && _boundScrollParents.indexOf(mainContainer) === -1 && isScrollable(mainContainer)) {
                mainContainer.addEventListener('scroll', onScrollOrResize, { passive: true });
                _boundScrollParents.push(mainContainer);
            }
        } catch(e) { /* ignore */ }
    }

    // call once to bind current scroll parents
    bindScrollParents();

    // initialize when DOM ready
    bindNavLinks();
    // initialize native title tooltips and bootstrap tooltips according to current collapsed state
    try { setNavTitles && setNavTitles(); } catch(e) {}
    try { enableBootstrapTooltips && enableBootstrapTooltips(); } catch(e) {}
    // expose control functions for other scripts (e.g., click handlers)
    try { window.showForLink = showForLink; window.hideSidebarPopover = hidePop; } catch(e){}
    // re-bind if sidebar content changes (lightweight mutation observer)
    try {
        var obs = new MutationObserver(function(){ bindNavLinks(); });
        obs.observe(sidebar, { childList:true, subtree:true });
    } catch(e){}

    // delegated click handler inside popover for toggling sub-items
    if(popMenu) {
        popMenu.addEventListener('click', function(ev){
            var toggle = ev.target.closest('.popover-toggle');
            if(!toggle) return;
            ev.preventDefault();
            var popItem = toggle.closest('.popover-item') || popMenu;
            // find the nearest .sub-items inside this popover item
            var sub = popItem.querySelector('.sub-items');
            if(!sub) {
                // fallback to top-level sub-items (for title-row toggles)
                sub = popMenu.querySelector('.sub-items');
                if(!sub) return;
            }

            var isOpen = sub.classList.contains('show');
            if(isOpen) {
                sub.classList.remove('show');
                toggle.classList.remove('open');
            } else {
                // Accordion behavior: close sibling sub-items at the same level
                var parentContainer = popItem.parentElement || popMenu;
                var siblingSubs = parentContainer.querySelectorAll(':scope > .popover-item > .sub-items, :scope > .sub-items');
                siblingSubs.forEach(function(s){ if(s !== sub) s.classList.remove('show'); });
                // also remove open class from other toggles in same level
                var siblingToggles = parentContainer.querySelectorAll(':scope .popover-toggle.open');
                siblingToggles.forEach(function(t){ if(t !== toggle) t.classList.remove('open'); });

                sub.classList.add('show');
                toggle.classList.add('open');
            }
        });
    }

})();

// Toggle submenu when clicking the down-chevron; on collapsed sidebar, show popover for that item
(function(){
    document.addEventListener('click', function(e){
        var chevr = e.target.closest('.bi-chevron-down');
        if(!chevr) return;
        // if the click originated inside the popover, don't intercept — let popover handlers run
        if (e.target.closest && e.target.closest('#sidebarHoverPop')) return;

        e.preventDefault();
        e.stopPropagation();

        var link = chevr.closest('.nav-link');
        if(!link) return;

        // toggle the collapse target if present (href like #settings-submenu)
        var href = link.getAttribute('href') || '';
        if(href && href.startsWith('#')) {
            var target = document.querySelector(href);
            if(target) {
                try { var bs = bootstrap.Collapse.getOrCreateInstance(target); bs.toggle(); } catch(err) {}
            }
        }

        // if sidebar is collapsed, show the popover for this link so submenu is visible near icon
        if(document.body.classList.contains('sidebar-collapsed')) {
            try { window.showForLink && window.showForLink(link); } catch(err) {}
        }
    }, false);
})();


//Financial Year Dropdown
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('fyDropdownBtn');
    var dropdown = document.getElementById('fyDropdown');
    var container = document.getElementById('fy-selector-container');
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
