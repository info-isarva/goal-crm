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

    // Containers that use transform/overflow — Select2 must attach inside them.
    var PARENT_SELECTOR = [
        '.modal',
        '.offcanvas',
        '.ll-status-drawer',
        '.ll-lead-modal',
        '.ld-modal',
        '.tw-modal',
        '[data-select2-parent]'
    ].join(', ');

    function resolveParent($el) {
        var explicit = $el.attr('data-dropdown-parent') || $el.data('dropdownParent');
        if (explicit) {
            var $explicit = $(explicit);
            if ($explicit.length) {
                return $explicit.first();
            }
        }

        var $parent = $el.closest(PARENT_SELECTOR);
        if ($parent.length) {
            return $parent.first();
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

    function buildOptions($el, parentOverride) {
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

        var $parent = parentOverride ? $(parentOverride) : resolveParent($el);
        if (!$parent || !$parent.length) {
            $parent = $(document.body);
        }

        var opts = {
            width: '100%',
            placeholder: placeholder || undefined,
            allowClear: !!allowClear,
            dropdownParent: $parent,
            minimumResultsForSearch: minSearch
        };

        if ($el.prop('multiple')) {
            opts.closeOnSelect = $el.data('close-on-select') === true;
        }

        return opts;
    }

    function initOne(el, parentOverride) {
        var $el = $(el);
        if (shouldSkip($el)) {
            return;
        }
        $el.select2(buildOptions($el, parentOverride));
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
        refresh: refresh,
        resolveParent: resolveParent
    };

    $(function () {
        initAll(document);
    });

    // Re-init when Bootstrap modal opens (dynamic content / first paint)
    $(document).on('shown.bs.modal', '.modal', function () {
        initAll(this);
    });

    // Keep page scroll stable when Select2 focuses the results panel
    // (otherwise the sticky top header / page hero scroll out of view).
    var lockedScrollY = null;

    function restoreScroll() {
        if (lockedScrollY === null) return;
        var y = lockedScrollY;
        if ((window.scrollY || window.pageYOffset || 0) !== y) {
            window.scrollTo(0, y);
        }
    }

    // Ensure open panels keep Select2's measured width + sit above page chrome
    $(document).on('select2:opening', function () {
        lockedScrollY = window.scrollY || window.pageYOffset || 0;
    });

    $(document).on('select2:open', function (e) {
        restoreScroll();
        window.requestAnimationFrame(restoreScroll);
        window.setTimeout(function () {
            restoreScroll();
            var $select = $(e.target);
            var inst = $select.data('select2');
            // Re-anchor after scroll restore so the menu isn't left floating with a gap.
            if (inst && inst.dropdown && typeof inst.dropdown._positionDropdown === 'function') {
                try {
                    inst.dropdown._positionDropdown();
                } catch (err) { /* ignore */ }
            }
            var $container = $select.next('.select2-container');
            var $open = $container.filter('.select2-container--open');
            if (!$open.length) {
                $open = $select.closest(PARENT_SELECTOR).find('> .select2-container--open').last();
            }
            if (!$open.length) {
                $open = $('body > .select2-container--open').last();
            }
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

    $(document).on('select2:closing select2:close', function () {
        restoreScroll();
        lockedScrollY = null;
    });

    // Label click must open the Select2 UI (not the hidden native select).
    $(document).on('click', 'label[for]', function (e) {
        var id = this.getAttribute('for');
        if (!id) return;
        var el = document.getElementById(id);
        if (!el || el.tagName !== 'SELECT') return;
        var $el = $(el);
        if (!$el.hasClass('select2-hidden-accessible')) return;
        e.preventDefault();
        $el.select2('open');
    });
})(window, window.jQuery);
