<script>
(function () {
    const form = document.getElementById('reportFilterForm');
    const periodInput = document.getElementById('monthPeriodInput');
    const fromYearInput = document.getElementById('fromYearInput');
    const fromMonthInput = document.getElementById('fromMonthInput');
    const toYearInput = document.getElementById('toYearInput');
    const toMonthInput = document.getElementById('toMonthInput');
    const trigger = document.getElementById('mrpTrigger');
    const triggerLabel = document.getElementById('mrpTriggerLabel');
    const panel = document.getElementById('mrpPanel');
    const customPane = document.getElementById('mrpCustomPane');
    const fromMonthSel = document.getElementById('mrpFromMonth');
    const fromYearSel = document.getElementById('mrpFromYear');
    const toMonthSel = document.getElementById('mrpToMonth');
    const toYearSel = document.getElementById('mrpToYear');
    const rangeText = document.getElementById('mrpRangeText');
    const pickerRoot = document.getElementById('monthRangePicker');
    if (!form || !pickerRoot || !periodInput || !fromYearInput || !fromMonthInput || !toYearInput || !toMonthInput) return;

    const presets = document.querySelectorAll('#mrpPresets .drp-preset');
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    function monthKey(y, m) {
        return (parseInt(y, 10) * 12) + parseInt(m, 10);
    }
    function formatRange(fy, fm, ty, tm) {
        const from = (monthNames[fm - 1] || '') + ' ' + fy;
        const to = (monthNames[tm - 1] || '') + ' ' + ty;
        return from + ' - ' + to;
    }
    function syncSelectsFromInputs() {
        fromMonthSel.value = String(fromMonthInput.value);
        fromYearSel.value = String(fromYearInput.value);
        toMonthSel.value = String(toMonthInput.value);
        toYearSel.value = String(toYearInput.value);
        rangeText.textContent = formatRange(
            fromYearInput.value, fromMonthInput.value,
            toYearInput.value, toMonthInput.value
        );
    }
    function updateRangeTextFromSelects() {
        rangeText.textContent = formatRange(
            fromYearSel.value, fromMonthSel.value,
            toYearSel.value, toMonthSel.value
        );
    }
    function openPanel() {
        panel.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
    }
    function closePanel() {
        panel.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
        syncSelectsFromInputs();
        syncCustomVisibility(periodInput.value === 'custom');
        presets.forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.period === periodInput.value);
        });
    }
    function syncCustomVisibility(show) {
        customPane.classList.toggle('show', !!show);
    }

    trigger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (panel.classList.contains('open')) closePanel();
        else {
            openPanel();
            if (periodInput.value === 'custom') {
                syncCustomVisibility(true);
                syncSelectsFromInputs();
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
            periodInput.value = period;

            if (period === 'custom') {
                syncCustomVisibility(true);
                if (!fromMonthInput.value || !toMonthInput.value) {
                    const now = new Date();
                    fromYearInput.value = now.getFullYear();
                    fromMonthInput.value = now.getMonth() + 1;
                    toYearInput.value = now.getFullYear();
                    toMonthInput.value = now.getMonth() + 1;
                }
                syncSelectsFromInputs();
                return;
            }

            fromYearInput.value = '';
            fromMonthInput.value = '';
            toYearInput.value = '';
            toMonthInput.value = '';
            syncCustomVisibility(false);
            triggerLabel.textContent = btn.textContent.trim();
            closePanel();
            form.submit();
        });
    });

    [fromMonthSel, fromYearSel, toMonthSel, toYearSel].forEach(function (el) {
        el.addEventListener('change', updateRangeTextFromSelects);
        el.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    document.getElementById('mrpCancel').addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closePanel();
    });

    document.getElementById('mrpApply').addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        let fy = parseInt(fromYearSel.value, 10);
        let fm = parseInt(fromMonthSel.value, 10);
        let ty = parseInt(toYearSel.value, 10);
        let tm = parseInt(toMonthSel.value, 10);
        if (!fy || !fm || !ty || !tm) return;
        if (monthKey(fy, fm) > monthKey(ty, tm)) {
            const tmpY = fy, tmpM = fm;
            fy = ty; fm = tm;
            ty = tmpY; tm = tmpM;
        }
        periodInput.value = 'custom';
        fromYearInput.value = fy;
        fromMonthInput.value = fm;
        toYearInput.value = ty;
        toMonthInput.value = tm;
        triggerLabel.textContent = formatRange(fy, fm, ty, tm);
        closePanel();
        form.submit();
    });

    document.addEventListener('click', function (e) {
        if (!panel.classList.contains('open')) return;
        if (pickerRoot.contains(e.target)) return;
        closePanel();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel.classList.contains('open')) closePanel();
    });
})();
</script>
