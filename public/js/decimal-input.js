/**
 * Restrict text inputs to numbers + optional decimal, with Indian comma grouping.
 * Usage: <input type="text" class="js-decimal" inputmode="decimal" data-decimals="2">
 * Displays: 2,00,000.00  |  Stores/typing: commas added automatically.
 */
(function () {
    'use strict';

    function digitCountBefore(str, index) {
        var count = 0;
        var limit = Math.min(index, str.length);
        for (var i = 0; i < limit; i++) {
            if (/\d/.test(str.charAt(i))) count++;
        }
        return count;
    }

    function indexAfterDigits(str, digitCount) {
        if (digitCount <= 0) return 0;
        var seen = 0;
        for (var i = 0; i < str.length; i++) {
            if (/\d/.test(str.charAt(i))) {
                seen++;
                if (seen >= digitCount) return i + 1;
            }
        }
        return str.length;
    }

    function sanitizeRaw(value, maxDecimals) {
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

    function formatIndianInt(intPart) {
        intPart = String(intPart || '0').replace(/^0+(?=\d)/, '');
        if (intPart === '') intPart = '0';
        var last3 = intPart.slice(-3);
        var rest = intPart.slice(0, -3);
        if (rest) {
            rest = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',');
            return rest + ',' + last3;
        }
        return last3;
    }

    function formatIndian(raw, maxDecimals, opts) {
        opts = opts || {};
        raw = sanitizeRaw(raw, maxDecimals);
        if (raw === '' || raw === '.') return raw === '.' ? '0.' : '';

        var negative = false;
        if (raw.charAt(0) === '-') {
            negative = true;
            raw = raw.slice(1);
        }

        var hasDot = raw.indexOf('.') !== -1;
        var parts = raw.split('.');
        var intPart = parts[0] || '0';
        var decPart = parts[1] || '';

        var formatted = formatIndianInt(intPart);
        if (hasDot || (opts.forceDecimals && typeof maxDecimals === 'number' && maxDecimals > 0)) {
            if (opts.forceDecimals && typeof maxDecimals === 'number' && maxDecimals > 0) {
                while (decPart.length < maxDecimals) decPart += '0';
                if (maxDecimals >= 0) decPart = decPart.slice(0, maxDecimals);
            }
            formatted += '.' + decPart;
        }

        return (negative ? '-' : '') + formatted;
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

        function applyFormat(forceDecimals) {
            var prev = el.value;
            var caret = el.selectionStart;
            var digitsBefore = typeof caret === 'number' ? digitCountBefore(prev, caret) : null;
            var next = formatIndian(prev, Number.isFinite(maxDecimals) ? maxDecimals : null, {
                forceDecimals: !!forceDecimals
            });
            if (prev === next) return;
            el.value = next;
            if (digitsBefore !== null && document.activeElement === el) {
                var pos = indexAfterDigits(next, digitsBefore);
                try { el.setSelectionRange(pos, pos); } catch (e) {}
            }
        }

        // Format existing server-rendered value on bind
        if (String(el.value || '').trim() !== '') {
            applyFormat(true);
        }

        el.addEventListener('input', function () {
            applyFormat(false);
        });

        el.addEventListener('blur', function () {
            var v = el.value.trim();
            if (v === '' || v === '.' || v === '0.') {
                if (v === '.' || v === '0.') el.value = '0';
                return;
            }
            applyFormat(true);
        });

        el.addEventListener('keydown', function (e) {
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            var allowedKeys = [
                'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End'
            ];
            if (allowedKeys.indexOf(e.key) !== -1) return;
            if (e.key === '.' || e.key === 'Decimal') {
                if (sanitizeRaw(el.value, null).indexOf('.') !== -1) {
                    e.preventDefault();
                }
                return;
            }
            if (e.key === ',') {
                e.preventDefault();
                return;
            }
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });

        el.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text') || '';
            var start = el.selectionStart || 0;
            var end = el.selectionEnd || 0;
            var merged = el.value.slice(0, start) + text + el.value.slice(end);
            el.value = formatIndian(merged, Number.isFinite(maxDecimals) ? maxDecimals : null, {});
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

    window.DecimalInput = {
        init: init,
        sanitize: sanitizeRaw,
        formatIndian: formatIndian
    };
})();
