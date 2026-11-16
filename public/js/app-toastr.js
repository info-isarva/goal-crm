/**
 * Global toast notifications (toastr-compatible API).
 * Usage: toastr.success('Saved'), toastr.error('Failed'), toastr.warning(...), toastr.info(...)
 */
(function (window) {
    'use strict';

    var defaults = {
        positionClass: 'toast-top-right',
        timeOut: 4200,
        extendedTimeOut: 1800,
        newestOnTop: true,
        preventDuplicates: true,
        closeButton: true,
        progressBar: true
    };

    var options = Object.assign({}, defaults);
    var recent = {};
    var container = null;

    var ICONS = {
        success: 'bi bi-check-circle-fill',
        error: 'bi bi-x-circle-fill',
        warning: 'bi bi-exclamation-triangle-fill',
        info: 'bi bi-info-circle-fill'
    };

    var TITLES = {
        success: 'Success',
        error: 'Error',
        warning: 'Notice',
        info: 'Info'
    };

    function stripHtml(html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = String(html == null ? '' : html);
        return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
    }

    function ensureContainer() {
        if (container && document.body.contains(container)) return container;
        container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
        container.className = options.positionClass || defaults.positionClass;
        return container;
    }

    function clear(el) {
        if (!el || !el.parentNode) return;
        el.classList.remove('is-in');
        el.classList.add('is-out');
        setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 220);
    }

    function show(type, message, title, override) {
        var msg = stripHtml(message);
        if (!msg) return null;

        var cfg = Object.assign({}, options, override || {});
        var key = type + '|' + msg;
        if (cfg.preventDuplicates && recent[key]) return null;

        var wrap = ensureContainer();
        var el = document.createElement('div');
        el.className = 'toast-item toast-' + type;
        el.setAttribute('role', 'alert');

        var ico = document.createElement('span');
        ico.className = 'toast-ico';
        ico.innerHTML = '<i class="' + (ICONS[type] || ICONS.info) + '"></i>';

        var body = document.createElement('div');
        body.className = 'toast-body';

        var titleText = title != null && String(title).length ? stripHtml(title) : TITLES[type];
        if (titleText) {
            var t = document.createElement('div');
            t.className = 'toast-title';
            t.textContent = titleText;
            body.appendChild(t);
        }

        var m = document.createElement('div');
        m.className = 'toast-msg';
        m.textContent = msg;
        body.appendChild(m);

        el.appendChild(ico);
        el.appendChild(body);

        if (cfg.closeButton !== false) {
            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'toast-close';
            closeBtn.setAttribute('aria-label', 'Close');
            closeBtn.innerHTML = '&times;';
            closeBtn.addEventListener('click', function () { clear(el); });
            el.appendChild(closeBtn);
        }

        var timeout = Number(cfg.timeOut);
        if (cfg.progressBar !== false && timeout > 0) {
            var bar = document.createElement('div');
            bar.className = 'toast-progress';
            bar.style.animationDuration = timeout + 'ms';
            el.appendChild(bar);
        }

        if (cfg.newestOnTop) {
            wrap.insertBefore(el, wrap.firstChild);
        } else {
            wrap.appendChild(el);
        }

        requestAnimationFrame(function () { el.classList.add('is-in'); });

        var hideTimer = null;
        var remaining = timeout;
        var startedAt = Date.now();

        function schedule(ms) {
            if (ms <= 0) return;
            clearTimeout(hideTimer);
            startedAt = Date.now();
            remaining = ms;
            hideTimer = setTimeout(function () { clear(el); }, ms);
        }

        if (timeout > 0) {
            schedule(timeout);
            el.addEventListener('mouseenter', function () {
                clearTimeout(hideTimer);
                remaining = Math.max(0, remaining - (Date.now() - startedAt));
                var barEl = el.querySelector('.toast-progress');
                if (barEl) barEl.style.animationPlayState = 'paused';
            });
            el.addEventListener('mouseleave', function () {
                var barEl = el.querySelector('.toast-progress');
                if (barEl) barEl.style.animationPlayState = 'running';
                schedule(remaining > 0 ? remaining : (cfg.extendedTimeOut || 0));
            });
        }

        if (cfg.preventDuplicates) {
            recent[key] = true;
            setTimeout(function () { delete recent[key]; }, Math.max(timeout, 800));
        }

        return el;
    }

    var api = {
        options: options,
        success: function (message, title, override) { return show('success', message, title, override); },
        error: function (message, title, override) { return show('error', message, title, override); },
        warning: function (message, title, override) { return show('warning', message, title, override); },
        info: function (message, title, override) { return show('info', message, title, override); },
        clear: function () {
            var wrap = document.getElementById('toast-container');
            if (!wrap) return;
            Array.prototype.slice.call(wrap.children).forEach(clear);
        },
        remove: clear
    };

    window.toastr = api;
    window.AppToast = api;
})(window);
