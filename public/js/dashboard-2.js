document.addEventListener('DOMContentLoaded', function () {
    // Period loader: center of page on change, hide once data is loaded
    var periodSelect = document.getElementById('dashboardPeriod');
    var periodForm = document.getElementById('dashboardPeriodForm');
    var periodLoader = document.getElementById('dashboardPeriodLoader');
    var pageSkeleton = document.getElementById('dashboardPageSkeleton');
    var periodDropdownItems = document.getElementById('dashboardPeriodDropdownItems');
    var activePeriodName = document.getElementById('activeDashboardPeriodName');

    function ensureLoaderOnBody() {
        if (periodLoader && periodLoader.parentElement !== document.body) {
            document.body.appendChild(periodLoader);
        }
    }

    function showPeriodLoader() {
        ensureLoaderOnBody();
        if (!periodLoader) return;
        periodLoader.classList.add('is-active');
        periodLoader.setAttribute('aria-hidden', 'false');
        periodLoader.setAttribute('aria-busy', 'true');
        document.body.classList.add('dashboard-period-loading');
        if (periodSelect && periodSelect.tagName === 'SELECT') periodSelect.disabled = true;
    }

    function hidePeriodLoader() {
        try { sessionStorage.removeItem('dashboardPeriodLoading'); } catch (e) {}
        if (!periodLoader) return;
        periodLoader.classList.remove('is-active');
        periodLoader.setAttribute('aria-hidden', 'true');
        periodLoader.setAttribute('aria-busy', 'false');
        document.body.classList.remove('dashboard-period-loading');
        if (periodSelect && periodSelect.tagName === 'SELECT') periodSelect.disabled = false;
    }

    // Hide loader once dashboard data/DOM is ready
    function finishPeriodLoading() {
        hidePeriodLoader();
        if (pageSkeleton) {
            pageSkeleton.style.transition = 'opacity 180ms ease';
            pageSkeleton.style.opacity = '0';
            window.setTimeout(function () {
                pageSkeleton.remove();
            }, 180);
        }
    }
    // Allow charts/layout to paint, then hide
    requestAnimationFrame(function () {
        setTimeout(finishPeriodLoading, 200);
    });
    window.addEventListener('load', finishPeriodLoading);
    window.addEventListener('pageshow', finishPeriodLoading);

    if (periodSelect && periodForm && periodDropdownItems) {
        periodDropdownItems.addEventListener('click', function (event) {
            var option = event.target.closest('[data-period-value]');
            if (!option) return;
            event.preventDefault();
            periodSelect.value = option.dataset.periodValue;
            if (activePeriodName) activePeriodName.textContent = option.dataset.periodLabel;
            try { sessionStorage.setItem('dashboardPeriodLoading', '1'); } catch (e) {}
            showPeriodLoader();
            periodForm.submit();
        });
    }

    var chartLabels = window.chartLabels || [];
    var leadsCounts = window.leadsAnalyticsSeries || [];
    var dealsCounts = window.dealsAnalyticsSeries || [];
    var periodLabel = window.dashboardPeriodLabel || 'selected period';

    // Fallback for older month-keyed payloads
    if (!chartLabels.length) {
        var leadsData = window.leadsAnalytics || {};
        var dealsData = window.dealsAnalytics || {};
        var now = new Date();
        chartLabels = [];
        leadsCounts = [];
        dealsCounts = [];
        for (let i = 5; i >= 0; i--) {
            let d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            chartLabels.push(d.toLocaleString('default', { month: 'short', year: '2-digit' }));
            let m = (now.getMonth() - i + 12) % 12 + 1;
            leadsCounts.push(leadsData[m] || 0);
            dealsCounts.push(dealsData[m] || 0);
        }
    }

    // Combined Line Chart (Leads vs Deals)
    var combinedEl = document.getElementById('combinedChart');
    var combinedChart = null;
    if (combinedEl) {
        try {
            var fixedCombinedH = parseInt(combinedEl.getAttribute('data-height')) || 320;
            combinedEl.style.height = fixedCombinedH + 'px';
        } catch (e) { /* ignore */ }
        var ctxCombined = combinedEl.getContext('2d');
        combinedChart = new Chart(ctxCombined, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Leads',
                        data: leadsCounts,
                        borderColor: 'rgba(21, 129, 201, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: chartLabels.length > 40 ? 0 : 4
                    },
                    {
                        label: 'Deals',
                        data: dealsCounts,
                        borderColor: 'rgba(211, 18, 60, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.06)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: chartLabels.length > 40 ? 0 : 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                devicePixelRatio: window.devicePixelRatio || 1,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            autoSkip: true,
                            maxTicksLimit: 12
                        }
                    }
                }
            }
        });
    }
    
    // Leads by Source Pie Chart
    var leadsBySource = window.leadsBySource || {};
    var leadsBySourceEl = document.getElementById('leadsBySourceChart');
    var leadsBySourceChart = null;
    if (leadsBySourceEl) {
        var labels = Object.keys(leadsBySource);
        try {
            var fixedH = parseInt(leadsBySourceEl.getAttribute('data-height')) || 320;
            leadsBySourceEl.style.height = fixedH + 'px';
        } catch (e) { /* ignore */ }

        var data = Object.values(leadsBySource);
        var defaultPalette = [
            '#0a88dbff', '#eb103fff', '#e0ac26ff', '#108f8fff', '#7451b9ff', '#f38517ff', '#adaeb1ff', '#d314b3ff'
        ];
        var leadSourceColors = window.leadSourceColors || {};
        var colors = labels.map(function(lab, idx) {
            if (leadSourceColors && leadSourceColors[lab]) return leadSourceColors[lab];
            if (defaultPalette[idx]) return defaultPalette[idx];
            var hash = 0;
            for (var i = 0; i < lab.length; i++) { hash = lab.charCodeAt(i) + ((hash << 5) - hash); }
            var c = (hash & 0x00FFFFFF).toString(16).toUpperCase();
            return '#' + '00000'.substring(0, 6 - c.length) + c;
        });
        var total = data.reduce(function(acc, v) { return acc + (parseInt(v) || 0); }, 0);
        if (!labels.length || total === 0) {
            var card = leadsBySourceEl.closest('.card');
            if (card) {
                leadsBySourceEl.style.display = 'none';
                var msg = document.createElement('div');
                msg.className = 'text-muted text-center small py-4';
                msg.innerText = 'No lead source data for ' + periodLabel + '.';
                card.appendChild(msg);
            }
        } else {
                leadsBySourceChart = new Chart(leadsBySourceEl.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{ data: data, backgroundColor: colors.slice(0, labels.length) }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        devicePixelRatio: window.devicePixelRatio || 1,
                        cutout: '60%',
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        var v = context.parsed || 0;
                                        var total = context.chart.data.datasets[0].data.reduce(function(sum, n) { return sum + (parseInt(n) || 0); }, 0);
                                        var pct = total ? Math.round((v / total) * 1000) / 10 : 0;
                                        return context.label + ': ' + v + ' (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
        }
    }

    // View toggle behavior (Dashboard <-> Analytics)
    var viewDashBtn = document.getElementById('viewDashBtn');
    var viewAnalyticsBtn = document.getElementById('viewAnalyticsBtn');
    var dashboardView = document.getElementById('dashboardView');
    var analyticsView = document.getElementById('analyticsView');
    if (viewDashBtn && viewAnalyticsBtn && dashboardView && analyticsView) {
        function showDashboard() {
            dashboardView.style.display = '';
            analyticsView.style.display = 'none';
            viewDashBtn.classList.add('active');
            viewAnalyticsBtn.classList.remove('active');
        }
        function showAnalytics() {
            dashboardView.style.display = 'none';
            analyticsView.style.display = '';
            viewDashBtn.classList.remove('active');
            viewAnalyticsBtn.classList.add('active');
            try { if (combinedChart && typeof combinedChart.resize === 'function') combinedChart.resize(); } catch (e) {}
            try { if (leadsBySourceChart && typeof leadsBySourceChart.resize === 'function') leadsBySourceChart.resize(); } catch (e) {}
        }
        viewDashBtn.addEventListener('click', showDashboard);
        viewAnalyticsBtn.addEventListener('click', showAnalytics);
        showDashboard();
    }

    // Hero clock (date + time) - update every second
    function updateHeroClock() {
        var dateEl = document.getElementById('heroDate');
        var timeEl = document.getElementById('heroTime');
        if (!dateEl || !timeEl) return;
        var now = new Date();
        var dateStr = now.toLocaleDateString(undefined, { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric' });
        var timeStr = now.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        dateEl.textContent = dateStr;
        timeEl.textContent = timeStr;
    }
    updateHeroClock();
    setInterval(updateHeroClock, 1000);
});
