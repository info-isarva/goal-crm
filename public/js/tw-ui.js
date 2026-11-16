/**
 * Lightweight Bootstrap API shim (modal + collapse) for Tailwind migration.
 * Supports data-bs-toggle="modal|collapse|tab" and window.bootstrap.Modal/Collapse.
 */
(function (window, document) {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }
    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    /* ── Collapse ─────────────────────────────────────────── */
    function Collapse(el, opts) {
        this.el = typeof el === 'string' ? qs(el) : el;
        this.opts = opts || {};
        if (!this.el) return;
        this.el._twCollapse = this;
        if (this.opts.toggle !== false && !Collapse._skipAuto) {
            this.toggle();
        }
    }
    Collapse.prototype.show = function () {
        if (!this.el) return;
        this.el.classList.add('show');
        this.el.style.display = '';
        this.el.removeAttribute('hidden');
        qsa('[data-bs-target="#' + this.el.id + '"], [href="#' + this.el.id + '"]').forEach(function (btn) {
            btn.classList.remove('collapsed');
            btn.setAttribute('aria-expanded', 'true');
        });
    };
    Collapse.prototype.hide = function () {
        if (!this.el) return;
        this.el.classList.remove('show');
        qsa('[data-bs-target="#' + this.el.id + '"], [href="#' + this.el.id + '"]').forEach(function (btn) {
            btn.classList.add('collapsed');
            btn.setAttribute('aria-expanded', 'false');
        });
    };
    Collapse.prototype.toggle = function () {
        if (!this.el) return;
        if (this.el.classList.contains('show')) this.hide();
        else this.show();
    };
    Collapse.getInstance = function (el) {
        el = typeof el === 'string' ? qs(el) : el;
        return el && el._twCollapse ? el._twCollapse : null;
    };
    Collapse.getOrCreateInstance = function (el, opts) {
        el = typeof el === 'string' ? qs(el) : el;
        if (!el) return null;
        if (!el._twCollapse) {
            Collapse._skipAuto = true;
            el._twCollapse = new Collapse(el, Object.assign({ toggle: false }, opts || {}));
            Collapse._skipAuto = false;
        }
        return el._twCollapse;
    };

    /* ── Modal ────────────────────────────────────────────── */
    var backdropEl = null;
    function ensureBackdrop() {
        if (!backdropEl) {
            backdropEl = document.createElement('div');
            backdropEl.className = 'tw-modal-backdrop';
            document.body.appendChild(backdropEl);
        }
        return backdropEl;
    }
    function Modal(el, opts) {
        this.el = typeof el === 'string' ? qs(el) : el;
        this.opts = opts || {};
        if (this.el) this.el._twModal = this;
    }
    Modal.prototype.show = function () {
        if (!this.el) return;
        ensureBackdrop().classList.add('show');
        this.el.classList.add('show');
        this.el.style.display = 'block';
        this.el.removeAttribute('aria-hidden');
        this.el.setAttribute('aria-modal', 'true');
        document.body.classList.add('tw-modal-open');
        this.el.dispatchEvent(new CustomEvent('shown.bs.modal', { bubbles: true }));
    };
    Modal.prototype.hide = function () {
        if (!this.el) return;
        this.el.classList.remove('show');
        this.el.style.display = 'none';
        this.el.setAttribute('aria-hidden', 'true');
        this.el.removeAttribute('aria-modal');
        if (backdropEl) backdropEl.classList.remove('show');
        document.body.classList.remove('tw-modal-open');
        this.el.dispatchEvent(new CustomEvent('hidden.bs.modal', { bubbles: true }));
    };
    Modal.prototype.toggle = function () {
        if (!this.el) return;
        if (this.el.classList.contains('show')) this.hide();
        else this.show();
    };
    Modal.getInstance = function (el) {
        el = typeof el === 'string' ? qs(el) : el;
        return el && el._twModal ? el._twModal : null;
    };
    Modal.getOrCreateInstance = function (el) {
        el = typeof el === 'string' ? qs(el) : el;
        if (!el) return null;
        if (!el._twModal) el._twModal = new Modal(el);
        return el._twModal;
    };

    /* ── Tab ──────────────────────────────────────────────── */
    function activateTab(trigger) {
        var targetSel = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
        if (!targetSel || targetSel === '#') return;
        var pane = qs(targetSel);
        if (!pane) return;
        var list = trigger.closest('[role="tablist"], .nav-tabs, .nav');
        if (list) {
            qsa('[data-bs-toggle="tab"], [data-bs-toggle="pill"]', list).forEach(function (t) {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
        }
        trigger.classList.add('active');
        trigger.setAttribute('aria-selected', 'true');
        var parent = pane.parentElement;
        if (parent) {
            qsa('.tab-pane', parent).forEach(function (p) {
                p.classList.remove('show', 'active');
            });
        }
        pane.classList.add('show', 'active');
    }

    /* ── Dropdown ─────────────────────────────────────────── */
    function closeAllDropdowns(except) {
        qsa('.dropdown-menu.show').forEach(function (menu) {
            if (except && menu === except) return;
            menu.classList.remove('show');
            var parent = menu.closest('.dropdown');
            if (parent) {
                var btn = qs('[data-bs-toggle="dropdown"]', parent);
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        });
    }
    function toggleDropdown(trigger) {
        var parent = trigger.closest('.dropdown') || trigger.parentElement;
        if (!parent) return;
        var menu = qs('.dropdown-menu', parent);
        if (!menu) return;
        var willShow = !menu.classList.contains('show');
        closeAllDropdowns(willShow ? menu : null);
        if (willShow) {
            menu.classList.add('show');
            trigger.setAttribute('aria-expanded', 'true');
        } else {
            menu.classList.remove('show');
            trigger.setAttribute('aria-expanded', 'false');
        }
    }

    /* ── Global bootstrap shim ────────────────────────────── */
    window.bootstrap = window.bootstrap || {};
    window.bootstrap.Collapse = Collapse;
    window.bootstrap.Modal = Modal;

    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-bs-toggle], [data-bs-dismiss]');
        if (!t) {
            if (!e.target.closest('.dropdown-menu')) closeAllDropdowns();
            return;
        }

        var dismiss = t.getAttribute('data-bs-dismiss');
        if (dismiss === 'modal') {
            e.preventDefault();
            var modal = t.closest('.modal');
            if (modal) Modal.getOrCreateInstance(modal).hide();
            return;
        }
        if (dismiss === 'offcanvas') {
            e.preventDefault();
            var panel = t.closest('.offcanvas, .sidebar, .offcanvas-md');
            if (panel) panel.classList.remove('show');
            return;
        }

        var toggle = t.getAttribute('data-bs-toggle');
        var targetSel = t.getAttribute('data-bs-target') || t.getAttribute('href');
        if (!toggle) return;

        if (toggle === 'modal' && targetSel) {
            e.preventDefault();
            var modalEl = qs(targetSel);
            if (modalEl) Modal.getOrCreateInstance(modalEl).show();
            return;
        }
        if (toggle === 'collapse' && targetSel) {
            e.preventDefault();
            var colEl = qs(targetSel);
            if (colEl) Collapse.getOrCreateInstance(colEl).toggle();
            return;
        }
        if (toggle === 'dropdown') {
            e.preventDefault();
            e.stopPropagation();
            toggleDropdown(t);
            return;
        }
        if ((toggle === 'tab' || toggle === 'pill') && targetSel) {
            e.preventDefault();
            activateTab(t);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeAllDropdowns();
        qsa('.modal.show').forEach(function (m) {
            Modal.getOrCreateInstance(m).hide();
        });
    });
})(window, document);
