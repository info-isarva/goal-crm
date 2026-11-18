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
    function portalModalToBody(el) {
        if (!el || el.parentElement === document.body) return;
        document.body.appendChild(el);
    }
    function portalAllModals() {
        qsa('.modal').forEach(portalModalToBody);
    }
    function Modal(el, opts) {
        this.el = typeof el === 'string' ? qs(el) : el;
        this.opts = opts || {};
        if (this.el) {
            this.el._twModal = this;
            portalModalToBody(this.el);
        }
    }
    Modal.prototype.show = function (relatedTarget) {
        if (!this.el) return;
        var showEv = new Event('show.bs.modal', { bubbles: true, cancelable: true });
        showEv.relatedTarget = relatedTarget || null;
        this.el.dispatchEvent(showEv);
        if (showEv.defaultPrevented) return;
        portalModalToBody(this.el);
        ensureBackdrop().classList.add('show');
        if (backdropEl) document.body.insertBefore(backdropEl, this.el);
        this.el.classList.add('show');
        this.el.style.display = 'block';
        this.el.style.zIndex = '10650';
        this.el.scrollTop = 0;
        this.el.removeAttribute('aria-hidden');
        this.el.setAttribute('aria-modal', 'true');
        document.body.classList.add('tw-modal-open');
        var shownEv = new Event('shown.bs.modal', { bubbles: true });
        shownEv.relatedTarget = relatedTarget || null;
        this.el.dispatchEvent(shownEv);
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
    function resetDropdownMenu(menu) {
        if (!menu) return;
        menu.classList.remove('show');
        menu.style.position = '';
        menu.style.top = '';
        menu.style.left = '';
        menu.style.right = '';
        menu.style.bottom = '';
        menu.style.zIndex = '';
        menu.style.minWidth = '';
        menu.style.maxWidth = '';
        menu.style.maxHeight = '';
        menu.style.overflowY = '';
        if (menu._twDropdownHome && menu.parentElement === document.body) {
            menu._twDropdownHome.appendChild(menu);
        }
        menu._twDropdownHome = null;
        if (menu._twDropdownTrigger) {
            menu._twDropdownTrigger.setAttribute('aria-expanded', 'false');
            menu._twDropdownTrigger = null;
        }
    }
    function positionDropdownMenu(trigger, menu) {
        // Portal to body + fixed coords so overflow:hidden table cards cannot clip it.
        if (menu.parentElement !== document.body) {
            menu._twDropdownHome = menu.parentElement;
            document.body.appendChild(menu);
        }
        menu._twDropdownTrigger = trigger;
        menu.style.position = 'fixed';
        menu.style.zIndex = '1080';
        menu.style.right = 'auto';
        menu.style.bottom = 'auto';
        menu.style.maxWidth = 'min(280px, calc(100vw - 16px))';
        menu.style.minWidth = Math.max(trigger.offsetWidth, 200) + 'px';
        menu.style.maxHeight = 'min(70vh, 360px)';
        menu.style.overflowY = 'auto';
        menu.classList.add('show');

        var rect = trigger.getBoundingClientRect();
        var mw = menu.offsetWidth || 220;
        var mh = menu.offsetHeight || 160;
        var gap = 6;
        var left = menu.classList.contains('dropdown-menu-end')
            ? (rect.right - mw)
            : rect.left;
        left = Math.max(8, Math.min(left, window.innerWidth - mw - 8));
        var top = rect.bottom + gap;
        if (top + mh > window.innerHeight - 8 && rect.top - gap - mh > 8) {
            top = rect.top - gap - mh;
        }
        menu.style.left = left + 'px';
        menu.style.top = Math.max(8, top) + 'px';
    }
    function closeAllDropdowns(except) {
        qsa('.dropdown-menu.show').forEach(function (menu) {
            if (except && menu === except) return;
            resetDropdownMenu(menu);
        });
    }
    function resolveDropdownMenu(trigger) {
        if (trigger._twMenu && document.body.contains(trigger._twMenu)) {
            return trigger._twMenu;
        }
        var parent = trigger.closest('.dropdown') || trigger.parentElement;
        if (!parent) return null;
        var menu = qs('.dropdown-menu', parent);
        if (menu) trigger._twMenu = menu;
        return menu;
    }
    function toggleDropdown(trigger) {
        var menu = resolveDropdownMenu(trigger);
        if (!menu) return;
        var willShow = !menu.classList.contains('show');
        closeAllDropdowns(willShow ? menu : null);
        if (willShow) {
            positionDropdownMenu(trigger, menu);
            trigger.setAttribute('aria-expanded', 'true');
        } else {
            resetDropdownMenu(menu);
            trigger.setAttribute('aria-expanded', 'false');
        }
    }
    window.addEventListener('resize', function () { closeAllDropdowns(); });
    window.addEventListener('scroll', function (e) {
        if (e.target && e.target.closest && e.target.closest('.dropdown-menu')) return;
        closeAllDropdowns();
    }, true);

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
            closeAllDropdowns();
            var modalEl = qs(targetSel);
            if (modalEl) Modal.getOrCreateInstance(modalEl).show(t);
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', portalAllModals);
    } else {
        portalAllModals();
    }
})(window, document);
