/**
 * Global page navigation loader for Goal Loan CRM.
 * Shows overlay when leaving a page (sidebar / nav / form GET|POST).
 */
(function () {
    'use strict';

    var loader = null;
    var hideTimer = null;
    var showTimer = null;
    var active = false;
    var MIN_VISIBLE_MS = 280;

    function el() {
        if (loader) return loader;
        loader = document.getElementById('goalPageLoader');
        return loader;
    }

    function show(immediate) {
        var node = el();
        if (!node) return;
        clearTimeout(hideTimer);
        clearTimeout(showTimer);

        var run = function () {
            active = true;
            node.classList.add('is-active');
            node.setAttribute('aria-busy', 'true');
            node.setAttribute('aria-hidden', 'false');
            document.documentElement.classList.add('gpl-busy');
        };

        if (immediate) {
            run();
        } else {
            // Avoid flash on instant same-page / cancelled navigations
            showTimer = setTimeout(run, 80);
        }
    }

    function hide() {
        var node = el();
        if (!node) return;
        clearTimeout(showTimer);
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () {
            active = false;
            node.classList.remove('is-active');
            node.setAttribute('aria-busy', 'false');
            node.setAttribute('aria-hidden', 'true');
            document.documentElement.classList.remove('gpl-busy');
        }, active ? MIN_VISIBLE_MS : 0);
    }

    function sameOrigin(href) {
        try {
            var url = new URL(href, window.location.href);
            return url.origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function isLikelyFileDownload(href) {
        try {
            var url = new URL(href, window.location.href);
            var path = (url.pathname || '').toLowerCase();
            if (path.indexOf('/export') !== -1) return true;
            if (/\.(csv|xlsx|xls|pdf|zip|doc|docx)(\?|$)/i.test(path)) return true;
            var format = String(url.searchParams.get('format') || '').toLowerCase();
            if (['csv', 'xlsx', 'xls', 'excel', 'pdf'].indexOf(format) !== -1) return true;
            return false;
        } catch (e) {
            return false;
        }
    }

    function shouldIgnoreLink(a, event) {
        if (!a) return true;
        if (a.hasAttribute('data-no-loader') || a.closest('[data-no-loader]')) return true;
        if (a.target && a.target !== '' && a.target !== '_self') return true;
        if (a.hasAttribute('download')) return true;
        if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) return true;
        if (event && typeof event.button === 'number' && event.button !== 0) return true;

        var href = a.getAttribute('href');
        if (!href || href === '#' || href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) {
            return true;
        }
        if (href.charAt(0) === '#') return true;
        if (!sameOrigin(href)) return true;
        // File downloads keep the current page mounted, so the loader would never clear.
        if (isLikelyFileDownload(href)) return true;

        try {
            var next = new URL(href, window.location.href);
            var cur = new URL(window.location.href);
            if (next.pathname === cur.pathname && next.search === cur.search && next.hash !== cur.hash) {
                return true; // hash-only change
            }
        } catch (e) { /* continue */ }

        return false;
    }

    function onLinkClick(event) {
        var a = event.target && event.target.closest ? event.target.closest('a[href]') : null;
        if (shouldIgnoreLink(a, event)) return;
        show(false);
    }

    function onSubmit(event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') return;
        if (form.hasAttribute('data-no-loader') || form.closest('[data-no-loader]')) return;
        if (form.getAttribute('target') && form.getAttribute('target') !== '_self') return;
        // AJAX / fetch forms often preventDefault — only show if navigation proceeds
        show(false);
        // If submit was cancelled shortly after, hide
        setTimeout(function () {
            if (event.defaultPrevented) hide();
        }, 0);
    }

    function bind() {
        document.addEventListener('click', onLinkClick, true);
        document.addEventListener('submit', onSubmit, true);

        window.addEventListener('pageshow', function (e) {
            hide();
            if (e.persisted) hide();
        });
        window.addEventListener('pagehide', function () {
            // keep visible during unload
        });
        window.addEventListener('beforeunload', function () {
            // browser may ignore DOM updates; still attempt
            show(true);
        });

        // Initial paint: brief loader then hide (covers slow first paint)
        if (document.readyState === 'loading') {
            show(true);
            document.addEventListener('DOMContentLoaded', hide);
        }
        window.addEventListener('load', hide);
    }

    // Public API for manual use (e.g. SPA-ish redirects)
    window.GoalPageLoader = {
        show: function () { show(true); },
        hide: hide
    };

    if (document.readyState === 'loading') {
        // Insert may run before DOM ready if script is deferred; bind ASAP
        bind();
    } else {
        bind();
        hide();
    }
})();
