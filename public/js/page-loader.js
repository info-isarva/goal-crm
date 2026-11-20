/**
 * Global page navigation loader for Goal Loan CRM.
 * Shows overlay when leaving a page (sidebar / nav / form GET|POST).
 * Skips file downloads / exports — those keep the page mounted.
 */
(function () {
    'use strict';

    var loader = null;
    var hideTimer = null;
    var showTimer = null;
    var stuckTimer = null;
    var active = false;
    var suppressUnloadShowUntil = 0;
    var MIN_VISIBLE_MS = 280;
    var STUCK_CLEAR_MS = 1800;

    function el() {
        if (loader) return loader;
        loader = document.getElementById('goalPageLoader');
        return loader;
    }

    function clearStuckWatch() {
        clearTimeout(stuckTimer);
        stuckTimer = null;
    }

    function armStuckWatch() {
        clearStuckWatch();
        // Downloads / cancelled navigations never unload — clear overlay if still here.
        stuckTimer = setTimeout(function () {
            if (document.visibilityState !== 'hidden') {
                hide(true);
            }
        }, STUCK_CLEAR_MS);
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
            armStuckWatch();
        };

        if (immediate) {
            run();
        } else {
            // Avoid flash on instant same-page / cancelled navigations
            showTimer = setTimeout(run, 80);
        }
    }

    function hide(force) {
        var node = el();
        if (!node) return;
        clearTimeout(showTimer);
        clearTimeout(hideTimer);
        clearStuckWatch();
        var delay = (!force && active) ? MIN_VISIBLE_MS : 0;
        hideTimer = setTimeout(function () {
            active = false;
            node.classList.remove('is-active');
            node.setAttribute('aria-busy', 'false');
            node.setAttribute('aria-hidden', 'true');
            document.documentElement.classList.remove('gpl-busy');
        }, delay);
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
            if (path.indexOf('/download') !== -1) return true;
            if (/\.(csv|xlsx|xls|pdf|zip|doc|docx)(\?|$)/i.test(path)) return true;
            var format = String(url.searchParams.get('format') || '').toLowerCase();
            if (['csv', 'xlsx', 'xls', 'excel', 'pdf'].indexOf(format) !== -1) return true;
            return false;
        } catch (e) {
            return false;
        }
    }

    function markDownloadNavigation() {
        // Chrome may fire beforeunload for attachment downloads without leaving the page.
        suppressUnloadShowUntil = Date.now() + 5000;
        hide(true);
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
        if (!a) return;

        var href = a.getAttribute('href') || '';
        var isDownload = a.hasAttribute('download')
            || a.hasAttribute('data-no-loader')
            || !!a.closest('[data-no-loader]')
            || isLikelyFileDownload(href);

        if (isDownload) {
            markDownloadNavigation();
            return;
        }

        if (shouldIgnoreLink(a, event)) return;
        show(false);
    }

    function onSubmit(event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') return;
        if (form.hasAttribute('data-no-loader') || form.closest('[data-no-loader]')) return;
        if (form.getAttribute('target') && form.getAttribute('target') !== '_self') return;
        show(false);
        setTimeout(function () {
            if (event.defaultPrevented) hide(true);
        }, 0);
    }

    function bind() {
        document.addEventListener('click', onLinkClick, true);
        document.addEventListener('submit', onSubmit, true);

        window.addEventListener('pageshow', function () {
            hide(true);
        });
        window.addEventListener('focus', function () {
            // Returning from a download / print dialog: ensure overlay is gone.
            if (Date.now() < suppressUnloadShowUntil) {
                hide(true);
            }
        });
        window.addEventListener('beforeunload', function () {
            if (Date.now() < suppressUnloadShowUntil) {
                return;
            }
            show(true);
        });

        if (document.readyState === 'loading') {
            show(true);
            document.addEventListener('DOMContentLoaded', function () {
                hide(true);
            });
        }
        window.addEventListener('load', function () {
            hide(true);
        });
    }

    window.GoalPageLoader = {
        show: function () { show(true); },
        hide: function () { hide(true); }
    };

    if (document.readyState === 'loading') {
        bind();
    } else {
        bind();
        hide(true);
    }
})();
