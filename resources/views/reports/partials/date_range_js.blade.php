<script>
(function () {
    const form = document.getElementById('reportFilterForm');
    const periodInput = document.getElementById('periodInput');
    const startInput = document.getElementById('startDateInput');
    const endInput = document.getElementById('endDateInput');
    const trigger = document.getElementById('drpTrigger');
    const triggerLabel = document.getElementById('drpTriggerLabel');
    const panel = document.getElementById('drpPanel');
    const calendarPane = document.getElementById('drpCalendarPane');
    const startDisplay = document.getElementById('drpStartDisplay');
    const endDisplay = document.getElementById('drpEndDisplay');
    const startWrap = document.getElementById('drpStartWrap');
    const endWrap = document.getElementById('drpEndWrap');
    const rangeText = document.getElementById('drpRangeText');
    const leftCal = document.getElementById('drpCalLeft');
    const rightCal = document.getElementById('drpCalRight');
    const pickerRoot = document.getElementById('dateRangePicker');
    if (!form || !pickerRoot || !periodInput || !startInput || !endInput) return;

    const presets = document.querySelectorAll('.drp-preset');
    const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const weekdays = ['Su','Mo','Tu','We','Th','Fr','Sa'];

    let draftStart = startInput.value || null;
    let draftEnd = endInput.value || null;
    let picking = 'start';
    let panelLocked = false;

    let leftMonth = draftStart ? new Date(draftStart + 'T00:00:00') : new Date();
    leftMonth = new Date(leftMonth.getFullYear(), leftMonth.getMonth(), 1);
    let rightMonth = new Date(leftMonth.getFullYear(), leftMonth.getMonth() + 1, 1);

    function pad(n) { return String(n).padStart(2, '0'); }
    function fmt(d) {
        if (!d) return '';
        if (typeof d === 'string') return d;
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }
    function parseDate(s) {
        if (!s) return null;
        const p = s.split('-');
        return new Date(+p[0], +p[1] - 1, +p[2]);
    }
    function sameDay(a, b) {
        return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }
    function between(d, a, b) {
        if (!a || !b) return false;
        const t = d.getTime(), x = a.getTime(), y = b.getTime();
        return t > Math.min(x, y) && t < Math.max(x, y);
    }

    function openPanel() {
        panel.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
    }
    function closePanel() {
        panel.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
        draftStart = startInput.value || null;
        draftEnd = endInput.value || null;
        picking = 'start';
        updateDisplays();
        syncCalendarVisibility(periodInput.value === 'custom');
        presets.forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.period === periodInput.value);
        });
    }
    function syncCalendarVisibility(show) {
        calendarPane.classList.toggle('show', !!show);
    }
    function updateActiveField() {
        startWrap.classList.toggle('active-field', picking === 'start');
        endWrap.classList.toggle('active-field', picking === 'end');
    }
    function updateDisplays() {
        startDisplay.value = draftStart || '';
        endDisplay.value = draftEnd || '';
        if (draftStart && draftEnd) rangeText.textContent = draftStart + ' - ' + draftEnd;
        else if (draftStart) rangeText.textContent = draftStart + ' - ';
        else rangeText.textContent = '';
        updateActiveField();
        renderCalendars();
    }

    function renderMonth(container, monthDate, side) {
        const y = monthDate.getFullYear();
        const m = monthDate.getMonth();
        const startDow = new Date(y, m, 1).getDay();
        const daysInMonth = new Date(y, m + 1, 0).getDate();
        let yearOpts = '';
        const cy = new Date().getFullYear();
        for (let yy = cy - 10; yy <= cy + 2; yy++) {
            yearOpts += '<option value="' + yy + '"' + (yy === y ? ' selected' : '') + '>' + yy + '</option>';
        }
        let monthOpts = '';
        monthNames.forEach(function (name, idx) {
            monthOpts += '<option value="' + idx + '"' + (idx === m ? ' selected' : '') + '>' + name + '</option>';
        });
        let html = '<div class="drp-cal-header">'
            + '<button type="button" class="drp-nav-btn" data-nav="' + side + '-prev"><i class="bi bi-chevron-left"></i></button>'
            + '<div class="d-flex gap-1">'
            + '<select data-side="' + side + '" data-type="month">' + monthOpts + '</select>'
            + '<select data-side="' + side + '" data-type="year">' + yearOpts + '</select>'
            + '</div>'
            + '<button type="button" class="drp-nav-btn" data-nav="' + side + '-next"><i class="bi bi-chevron-right"></i></button>'
            + '</div><div class="drp-weekdays">';
        weekdays.forEach(function (w) { html += '<span>' + w + '</span>'; });
        html += '</div><div class="drp-days">';
        const s = parseDate(draftStart);
        const e = parseDate(draftEnd);
        for (let i = 0; i < startDow; i++) html += '<span class="drp-day empty"></span>';
        for (let day = 1; day <= daysInMonth; day++) {
            const d = new Date(y, m, day);
            const ds = fmt(d);
            let cls = 'drp-day';
            if (s && sameDay(d, s)) cls += ' start';
            if (e && sameDay(d, e)) cls += ' end';
            if (s && e && between(d, s, e)) cls += ' in-range';
            html += '<button type="button" class="' + cls + '" data-date="' + ds + '">' + day + '</button>';
        }
        html += '</div>';
        container.innerHTML = html;
    }

    function renderCalendars() {
        if (rightMonth.getFullYear() < leftMonth.getFullYear()
            || (rightMonth.getFullYear() === leftMonth.getFullYear() && rightMonth.getMonth() <= leftMonth.getMonth())) {
            rightMonth = new Date(leftMonth.getFullYear(), leftMonth.getMonth() + 1, 1);
        }
        renderMonth(leftCal, leftMonth, 'left');
        renderMonth(rightCal, rightMonth, 'right');
    }

    function applyPreset(period) {
        periodInput.value = period;
        if (period !== 'custom') {
            startInput.value = '';
            endInput.value = '';
        }
    }

    trigger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (panel.classList.contains('open')) closePanel();
        else {
            openPanel();
            if (periodInput.value === 'custom') {
                syncCalendarVisibility(true);
                updateDisplays();
            }
        }
    });

    presets.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const period = btn.dataset.period;
            presets.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            applyPreset(period);
            if (period === 'custom') {
                syncCalendarVisibility(true);
                if (!draftStart || !draftEnd) {
                    const now = new Date();
                    draftStart = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
                    draftEnd = null;
                    leftMonth = new Date(now.getFullYear(), now.getMonth(), 1);
                    rightMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1);
                }
                picking = draftEnd ? 'start' : (draftStart ? 'end' : 'start');
                updateDisplays();
                return;
            }
            syncCalendarVisibility(false);
            triggerLabel.textContent = btn.textContent.trim();
            closePanel();
            if (form) form.submit();
        });
    });

    document.getElementById('drpCancel').addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closePanel();
    });
    document.getElementById('drpApply').addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!draftStart || !draftEnd) return;
        if (draftStart > draftEnd) {
            const tmp = draftStart;
            draftStart = draftEnd;
            draftEnd = tmp;
        }
        startInput.value = draftStart;
        endInput.value = draftEnd;
        periodInput.value = 'custom';
        triggerLabel.textContent = draftStart + ' - ' + draftEnd;
        closePanel();
        if (form) form.submit();
    });

    function handlePanelInteraction(e) {
        e.stopPropagation();
        panelLocked = true;
        setTimeout(function () { panelLocked = false; }, 0);
    }
    panel.addEventListener('mousedown', handlePanelInteraction);
    panel.addEventListener('click', handlePanelInteraction);

    function onDaySelect(date) {
        if (!date) return;
        if (picking === 'start' || (draftStart && draftEnd)) {
            draftStart = date;
            draftEnd = null;
            picking = 'end';
        } else {
            draftEnd = date;
            if (draftStart > draftEnd) {
                const tmp = draftStart;
                draftStart = draftEnd;
                draftEnd = tmp;
            }
            picking = 'start';
        }
        updateDisplays();
    }

    function onCalClick(e) {
        e.preventDefault();
        e.stopPropagation();
        const nav = e.target.closest('[data-nav]');
        if (nav) {
            const dir = nav.getAttribute('data-nav');
            if (dir === 'left-prev') leftMonth = new Date(leftMonth.getFullYear(), leftMonth.getMonth() - 1, 1);
            if (dir === 'left-next') leftMonth = new Date(leftMonth.getFullYear(), leftMonth.getMonth() + 1, 1);
            if (dir === 'right-prev') rightMonth = new Date(rightMonth.getFullYear(), rightMonth.getMonth() - 1, 1);
            if (dir === 'right-next') rightMonth = new Date(rightMonth.getFullYear(), rightMonth.getMonth() + 1, 1);
            renderCalendars();
            return;
        }
        const sel = e.target.closest('select[data-side]');
        if (sel) {
            const side = sel.getAttribute('data-side');
            const type = sel.getAttribute('data-type');
            const base = side === 'left' ? leftMonth : rightMonth;
            const y = type === 'year' ? +sel.value : base.getFullYear();
            const m = type === 'month' ? +sel.value : base.getMonth();
            if (side === 'left') leftMonth = new Date(y, m, 1);
            else rightMonth = new Date(y, m, 1);
            renderCalendars();
            return;
        }
        const dayBtn = e.target.closest('.drp-day[data-date]');
        if (dayBtn) onDaySelect(dayBtn.getAttribute('data-date'));
    }

    leftCal.addEventListener('mousedown', onCalClick);
    rightCal.addEventListener('mousedown', onCalClick);
    leftCal.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); });
    rightCal.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); });
    leftCal.addEventListener('change', onCalClick);
    rightCal.addEventListener('change', onCalClick);

    startWrap.addEventListener('click', function (e) { e.stopPropagation(); picking = 'start'; updateActiveField(); });
    endWrap.addEventListener('click', function (e) { e.stopPropagation(); picking = 'end'; updateActiveField(); });

    document.addEventListener('mousedown', function (e) {
        if (!panel.classList.contains('open') || panelLocked) return;
        if (!pickerRoot.contains(e.target)) closePanel();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel.classList.contains('open')) closePanel();
    });

    form.addEventListener('submit', function () {
        if (periodInput.value === 'custom') {
            startInput.value = draftStart || startInput.value;
            endInput.value = draftEnd || endInput.value;
        }
    });

    updateDisplays();
})();
</script>
