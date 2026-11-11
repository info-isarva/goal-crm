(function () {
    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function () {
        // Mobile FY Dropdown
        var btn = document.getElementById('mobileFyDropdownBtn');
        var dropdown = document.getElementById('mobileFyDropdown');
        var container = document.getElementById('mobile-fy-selector-container');
        if (btn && dropdown && container) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            });
            document.addEventListener('click', function (e) {
                if (!container.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });
        }

        // Mobile hamburger menu (vanilla JS — no Alpine)
        var sidebar = document.getElementById('mobileSidebar');
        var backdrop = document.getElementById('mobileSidebarBackdrop');
        var toggles = Array.prototype.slice.call(document.querySelectorAll('.js-mobile-menu-toggle'));

        if (!sidebar) {
            console.warn('[nav] #mobileSidebar not found');
            return;
        }

        // Keep drawer/backdrop on <body> so parent overflow/transform cannot clip them
        if (backdrop && backdrop.parentNode !== document.body) {
            document.body.appendChild(backdrop);
        }
        if (sidebar.parentNode !== document.body) {
            document.body.appendChild(sidebar);
        }

        function setMenuOpen(open) {
            open = !!open;
            sidebar.classList.toggle('open', open);
            sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
            if (backdrop) {
                backdrop.classList.toggle('open', open);
                if (open) {
                    backdrop.removeAttribute('hidden');
                } else {
                    backdrop.setAttribute('hidden', '');
                }
            }
            toggles.forEach(function (el) {
                el.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.body.classList.toggle('mobile-menu-open', open);
            document.body.style.overflow = open ? 'hidden' : '';
        }

        function toggleMenu(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            setMenuOpen(!sidebar.classList.contains('open'));
        }

        toggles.forEach(function (el) {
            el.addEventListener('click', toggleMenu);
        });

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                setMenuOpen(false);
            });
        }

        sidebar.addEventListener('click', function (e) {
            var link = e.target.closest('a.nav-link');
            if (!link) return;
            var href = link.getAttribute('href') || '';
            if (href && href.charAt(0) !== '#') {
                setMenuOpen(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setMenuOpen(false);
        });

        window.addEventListener('resize', function () {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                setMenuOpen(false);
            }
        });

        setMenuOpen(false);

        // Smooth Bootstrap collapse height animation inside sidebars
        function enableSmoothSidebarCollapse() {
            document.querySelectorAll('.sidebar .collapse').forEach(function (el) {
                if (el.dataset.smoothBound === '1') return;
                el.dataset.smoothBound = '1';
                el.style.overflow = 'hidden';

                el.addEventListener('show.bs.collapse', function () {
                    el.style.height = '0px';
                    var full = el.scrollHeight + 'px';
                    requestAnimationFrame(function () {
                        el.style.transition = 'height 280ms ease';
                        el.style.height = full;
                    });
                    var onEnd = function (ev) {
                        if (ev && ev.propertyName !== 'height') return;
                        el.style.height = null;
                        el.style.transition = null;
                        el.removeEventListener('transitionend', onEnd);
                    };
                    el.addEventListener('transitionend', onEnd);
                });

                el.addEventListener('hide.bs.collapse', function () {
                    el.style.height = el.scrollHeight + 'px';
                    requestAnimationFrame(function () {
                        el.style.transition = 'height 240ms ease';
                        el.style.height = '0px';
                    });
                    var onEndHide = function (ev) {
                        if (ev && ev.propertyName !== 'height') return;
                        el.style.height = null;
                        el.style.transition = null;
                        el.removeEventListener('transitionend', onEndHide);
                    };
                    el.addEventListener('transitionend', onEndHide);
                });
            });
        }

        enableSmoothSidebarCollapse();
    });
})();
