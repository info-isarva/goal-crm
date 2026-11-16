/**
 * Global Flatpickr initializer.
 * Targets:
 *   .js-datepicker  → date only (display d-m-Y, value Y-m-d)
 *   .js-datetimepicker → date+time (display d-m-Y H:i, value Y-m-d H:i)
 *   .js-timepicker → time only (display h:i K, value H:i)
 *
 * Note: altInput must NOT keep .js-datepicker / .js-datetimepicker / .js-timepicker
 * or modal re-init will attach Flatpickr to the visible alt and leave the named
 * original input empty (Laravel then reports the field as required).
 */
(function (window) {
    'use strict';

    if (typeof flatpickr === 'undefined') {
        return;
    }

    var PICKER_CLASSES = ['js-datepicker', 'js-datetimepicker', 'js-timepicker'];

    function resolveMinDate(el) {
        var raw = (el.getAttribute('data-min-date') || '').trim().toLowerCase();
        if (!raw) return undefined;
        if (raw === 'today' || raw === 'now') return 'today';
        return el.getAttribute('data-min-date');
    }

    function altInputClassFor(el) {
        var classes = (el.className || 'form-control form-control-sm').split(/\s+/).filter(function (c) {
            return c && PICKER_CLASSES.indexOf(c) === -1;
        });
        if (classes.indexOf('flatpickr-alt') === -1) {
            classes.push('flatpickr-alt');
        }
        return classes.join(' ');
    }

    function baseOptions(el) {
        var minDate = resolveMinDate(el);
        var opts = {
            allowInput: false,
            clickOpens: true,
            disableMobile: true,
            animate: true,
            altInput: true,
            altInputClass: altInputClassFor(el),
            onReady: function (selectedDates, dateStr, instance) {
                if (instance.altInput) {
                    instance.altInput.setAttribute(
                        'placeholder',
                        el.getAttribute('placeholder') || el.getAttribute('data-placeholder') || ''
                    );
                    // Never treat alt as a picker target
                    PICKER_CLASSES.forEach(function (c) {
                        instance.altInput.classList.remove(c);
                    });
                    instance.altInput.classList.add('flatpickr-alt');
                    if (el.disabled) instance.altInput.disabled = true;
                    if (el.readOnly) instance.altInput.readOnly = true;
                    if (el.required) {
                        instance.altInput.required = true;
                        el.required = false;
                    }
                }
            },
            onChange: function (selectedDates, dateStr, instance) {
                var changeEvt = new Event('change', { bubbles: true });
                el.dispatchEvent(changeEvt);
                if (typeof window.jQuery !== 'undefined') {
                    window.jQuery(el).trigger('change');
                }
            }
        };
        if (minDate !== undefined) {
            opts.minDate = minDate;
        }
        return opts;
    }

    function initDate(el) {
        if (!el || el._flatpickr) return el._flatpickr;
        return flatpickr(el, Object.assign(baseOptions(el), {
            dateFormat: 'Y-m-d',
            altFormat: 'd-m-Y',
            defaultDate: el.value || null
        }));
    }

    function initDateTime(el) {
        if (!el || el._flatpickr) return el._flatpickr;
        return flatpickr(el, Object.assign(baseOptions(el), {
            enableTime: true,
            time_24hr: true,
            dateFormat: 'Y-m-d H:i',
            altFormat: 'd-m-Y H:i',
            defaultDate: el.value || null
        }));
    }

    function initTime(el) {
        if (!el || el._flatpickr) return el._flatpickr;
        var step = parseInt(el.getAttribute('data-minute-increment') || '15', 10) || 15;
        return flatpickr(el, Object.assign(baseOptions(el), {
            enableTime: true,
            noCalendar: true,
            time_24hr: false,
            dateFormat: 'h:i K',
            altFormat: 'h:i K',
            defaultDate: el.value || null,
            minuteIncrement: step
        }));
    }

    function shouldSkip(el) {
        if (!el || el._flatpickr) return true;
        if (el.classList.contains('flatpickr-alt')) return true;
        if (el.getAttribute('data-fp-omit') === '1') return true;
        return false;
    }

    function initOne(el) {
        if (shouldSkip(el)) return;
        if (el.classList.contains('js-timepicker')) {
            initTime(el);
        } else if (el.classList.contains('js-datetimepicker')) {
            initDateTime(el);
        } else if (el.classList.contains('js-datepicker')) {
            initDate(el);
        }
    }

    function initAll(root) {
        var scope = root || document;
        scope.querySelectorAll('input.js-datepicker, input.js-datetimepicker, input.js-timepicker').forEach(initOne);
    }

    window.IsarvaDatepicker = {
        init: initAll,
        initOne: initOne,
        initDate: initDate,
        initDateTime: initDateTime,
        initTime: initTime
    };

    document.addEventListener('DOMContentLoaded', function () {
        initAll(document);
    });

    document.addEventListener('shown.bs.modal', function (e) {
        initAll(e.target);
    });
})(window);
