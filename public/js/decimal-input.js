/**
 * Restrict text inputs to numbers + optional single decimal.
 * Usage: <input type="text" class="js-decimal" inputmode="decimal">
 * Optional: data-decimals="2" (default unlimited fractional digits while typing)
 */
(function () {
    'use strict';

    function sanitize(value, maxDecimals) {
        value = String(value || '');
        value = value.replace(/[^\d.]/g, '');

        var firstDot = value.indexOf('.');
        if (firstDot !== -1) {
            value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
        }

        if (typeof maxDecimals === 'number' && maxDecimals >= 0 && firstDot !== -1) {
            var parts = value.split('.');
            value = parts[0] + '.' + (parts[1] || '').slice(0, maxDecimals);
        }

        return value;
    }

    function bind(el) {
        if (!el || el.dataset.decimalBound === '1') return;
        el.dataset.decimalBound = '1';

        if (!el.getAttribute('inputmode')) {
            el.setAttribute('inputmode', 'decimal');
        }
        if (!el.getAttribute('autocomplete')) {
            el.setAttribute('autocomplete', 'off');
        }

        var maxDecimalsAttr = el.getAttribute('data-decimals');
        var maxDecimals = maxDecimalsAttr !== null && maxDecimalsAttr !== ''
            ? parseInt(maxDecimalsAttr, 10)
            : null;

        el.addEventListener('input', function () {
            var next = sanitize(el.value, Number.isFinite(maxDecimals) ? maxDecimals : null);
            if (el.value !== next) {
                var start = el.selectionStart;
                var diff = el.value.length - next.length;
                el.value = next;
                if (typeof start === 'number') {
                    var pos = Math.max(0, start - diff);
                    try { el.setSelectionRange(pos, pos); } catch (e) {}
                }
            }
        });

        el.addEventListener('blur', function () {
            var v = el.value.trim();
            if (v === '' || v === '.') {
                return;
            }
            if (v.startsWith('.')) {
                v = '0' + v;
            }
            if (v.endsWith('.')) {
                v = v.slice(0, -1);
            }
            el.value = sanitize(v, Number.isFinite(maxDecimals) ? maxDecimals : null);
        });

        el.addEventListener('keydown', function (e) {
            // Allow control shortcuts and navigation
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            var allowedKeys = [
                'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End'
            ];
            if (allowedKeys.indexOf(e.key) !== -1) return;
            if (e.key === '.' || e.key === 'Decimal') {
                if (String(el.value).indexOf('.') !== -1) {
                    e.preventDefault();
                }
                return;
            }
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });

        el.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text') || '';
            var next = sanitize(text, Number.isFinite(maxDecimals) ? maxDecimals : null);
            var start = el.selectionStart || 0;
            var end = el.selectionEnd || 0;
            var merged = el.value.slice(0, start) + next + el.value.slice(end);
            el.value = sanitize(merged, Number.isFinite(maxDecimals) ? maxDecimals : null);
            el.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    function init(root) {
        (root || document).querySelectorAll('input.js-decimal').forEach(bind);
    }

    document.addEventListener('DOMContentLoaded', function () {
        init(document);
    });

    document.addEventListener('shown.bs.modal', function (e) {
        init(e.target || document);
    });

    window.DecimalInput = { init: init, sanitize: sanitize };
})();
