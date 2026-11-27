/**
 * Global Flatpickr initializer.
 * Targets:
 *   .js-datepicker  → date only (display d-m-Y, value Y-m-d)
 *   .js-datetimepicker → date+time (display d-m-Y H:i, value Y-m-d H:i)
 *   .js-timepicker → time only (display h:i K, value H:i)
 *   .js-monthpicker → month only (display F Y, value Y-m)
 *
 * Note: altInput must NOT keep .js-datepicker / .js-datetimepicker / .js-timepicker / .js-monthpicker
 * or modal re-init will attach Flatpickr to the visible alt and leave the named
 * original input empty (Laravel then reports the field as required).
 */
(function (window) {
    'use strict';

    if (typeof flatpickr === 'undefined') {
        return;
    }

    var PICKER_CLASSES = ['js-datepicker', 'js-datetimepicker', 'js-timepicker', 'js-monthpicker'];

    function resolveMinDate(el) {
        var raw = (el.getAttribute('data-min-date') || '').trim().toLowerCase();
        if (!raw) return undefined;
        if (raw === 'today' || raw === 'now') return 'today';
        return el.getAttribute('data-min-date');
    }

    function resolveMaxDate(el) {
        var minAge = parseInt(el.getAttribute('data-min-age') || '', 10);
        if (!isNaN(minAge) && minAge > 0) {
            var d = new Date();
            d.setHours(0, 0, 0, 0);
            d.setFullYear(d.getFullYear() - minAge);
            return d;
        }
        var raw = (el.getAttribute('data-max-date') || '').trim().toLowerCase();
        if (!raw) return undefined;
        if (raw === 'today' || raw === 'now') return 'today';
        // Support "-18y" / "18y-ago" style caps (no future + min age)
        var ageMatch = raw.match(/^(\d+)\s*y(?:ears?)?(?:-ago)?$/i) || raw.match(/^-(\d+)y$/i);
        if (ageMatch) {
            var years = parseInt(ageMatch[1], 10);
            var cap = new Date();
            cap.setHours(0, 0, 0, 0);
            cap.setFullYear(cap.getFullYear() - years);
            return cap;
        }
        return el.getAttribute('data-max-date');
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
        var maxDate = resolveMaxDate(el);
        var opts = {
            allowInput: false,
            clickOpens: true,
            disableMobile: true,
            animate: true,
            altInput: true,
            altInputClass: altInputClassFor(el),
            // Keep picker above Bootstrap / tw modals (10650)
            static: false,
            appendTo: document.body,
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
        if (maxDate !== undefined) {
            opts.maxDate = maxDate;
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
            // Submit 24h H:i for Laravel date_format:H:i; show 12h in the UI
            dateFormat: 'H:i',
            altFormat: 'h:i K',
            defaultDate: el.value || null,
            minuteIncrement: step
        }));
    }

    function initMonth(el) {
        if (!el || el._flatpickr) return el._flatpickr;
        if (typeof monthSelectPlugin === 'undefined') {
            console.warn('monthSelectPlugin missing — cannot init .js-monthpicker');
            return null;
        }
        var shorthand = el.getAttribute('data-month-shorthand') === '1';
        return flatpickr(el, Object.assign(baseOptions(el), {
            dateFormat: 'Y-m',
            altFormat: shorthand ? 'M Y' : 'F Y',
            defaultDate: el.value || null,
            plugins: [
                new monthSelectPlugin({
                    shorthand: shorthand,
                    dateFormat: 'Y-m',
                    altFormat: shorthand ? 'M Y' : 'F Y'
                })
            ]
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
        if (el.classList.contains('js-monthpicker')) {
            initMonth(el);
        } else if (el.classList.contains('js-timepicker')) {
            initTime(el);
        } else if (el.classList.contains('js-datetimepicker')) {
            initDateTime(el);
        } else if (el.classList.contains('js-datepicker')) {
            initDate(el);
        }
    }

    function initAll(root) {
        var scope = root || document;
        scope.querySelectorAll(
            'input.js-datepicker, input.js-datetimepicker, input.js-timepicker, input.js-monthpicker'
        ).forEach(initOne);
    }

    window.IsarvaDatepicker = {
        init: initAll,
        initOne: initOne,
        initDate: initDate,
        initDateTime: initDateTime,
        initTime: initTime,
        initMonth: initMonth
    };

    document.addEventListener('DOMContentLoaded', function () {
        initAll(document);
    });

    document.addEventListener('shown.bs.modal', function (e) {
        initAll(e.target);
    });
})(window);
