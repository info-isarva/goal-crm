/**
 * Global searchable Select2 initializer.
 * Targets: select.js-select2 / select.select2 / select.form-select
 * Skips: compact/per-page, data-select2-skip, .no-select2, already initialized
 */
(function (window, $) {
    'use strict';

    if (!$ || !$.fn || !$.fn.select2) {
        return;
    }

    var SELECTOR = [
        'select.js-select2',
        'select.select2',
        'select.form-select',
        'select.cw-select'
    ].join(', ');

    var SKIP_SELECTOR = [
        '.no-select2',
        '.form-select-compact',
        '.form-select-sm',
        '[data-select2-skip]',
        '.select2-hidden-accessible',
        '.dataTables_length select',
        '.per-page-wrap select',
        'select[size]',
        'select[multiple]:not(.js-select2):not(.select2)'
    ].join(', ');

    function resolveParent($el) {
        // Filters/tables → body (above KPI cards). Modals/offcanvas keep local parent.
        var $modal = $el.closest('.modal');
        if ($modal.length) {
            return $modal;
        }
        var $offcanvas = $el.closest('.offcanvas');
        if ($offcanvas.length) {
            return $offcanvas;
        }
        return $(document.body);
    }

    function shouldSkip($el) {
        if (!$el.is('select')) return true;
        if ($el.is(SKIP_SELECTOR)) return true;
        if ($el.closest('.per-page-wrap, .dataTables_length').length) return true;
        if ($el.data('select2-skip')) return true;
        if ($el.hasClass('select2-hidden-accessible')) return true;
        return false;
    }

    function buildOptions($el) {
        var placeholder = $el.data('placeholder') || $el.attr('placeholder') || $el.find('option[value=""]').first().text() || '';
        var allowClear = $el.data('allow-clear');
        if (typeof allowClear === 'undefined') {
            allowClear = $el.find('option[value=""]').length > 0;
        }

        var optionCount = $el.find('option').length;
        var searchMode = $el.data('search');
        var minSearch;
        if (searchMode === false || searchMode === 'false') {
            minSearch = Infinity;
        } else if (searchMode === true || searchMode === 'true') {
            minSearch = 0;
        } else {
            // Short lists (Lead Type, Status, etc.) — no search box, match mockup
            minSearch = optionCount <= 12 ? Infinity : 0;
        }

        var opts = {
            width: '100%',
            placeholder: placeholder || undefined,
            allowClear: !!allowClear,
            dropdownParent: resolveParent($el),
            minimumResultsForSearch: minSearch
        };

        if ($el.prop('multiple')) {
            opts.closeOnSelect = $el.data('close-on-select') === true;
        }

        return opts;
    }

    function initOne(el) {
        var $el = $(el);
        if (shouldSkip($el)) {
            return;
        }
        $el.select2(buildOptions($el));
    }

    function initAll(root) {
        var $root = root ? $(root) : $(document);
        $root.find(SELECTOR).addBack(SELECTOR).each(function () {
            initOne(this);
        });
    }

    function refresh($el) {
        if (!$el || !$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.trigger('change.select2');
        }
    }

    window.IsarvaSelect2 = {
        init: initAll,
        initOne: initOne,
        refresh: refresh
    };

    $(function () {
        initAll(document);
    });

    // Re-init when Bootstrap modal opens (dynamic content / first paint)
    $(document).on('shown.bs.modal', '.modal', function () {
        initAll(this);
    });

    // Ensure open panels keep Select2's measured width + sit above page chrome
    $(document).on('select2:open', function () {
        window.setTimeout(function () {
            var $open = $('body > .select2-container--open').last();
            if (!$open.length) {
                $open = $('.select2-container--open').last();
            }
            if (!$open.length) return;
            $open.css('z-index', 10050);
            var $drop = $open.find('.select2-dropdown');
            if ($drop.length) {
                $drop.css({
                    'z-index': 10051,
                    'background': '#fff',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }
        }, 0);
    });
})(window, window.jQuery);
