@php
    $f = $dash['filters'] ?? [];
    $section = $f['section'] ?? 'overview';
    $targetTab = $f['target_tab'] ?? 'employee';
    $show = function (array $for) use ($section) {
        return $section === 'overview' || in_array($section, $for, true);
    };
    $kpiTone = [
        'blue' => 't-blue', 'teal' => 't-teal', 'amber' => 't-amber',
        'green' => 't-green', 'purple' => 't-purple', 'red' => 't-red',
    ];
    $showFunnel = $show(['leads', 'processing']);
    $showTrend = $show(['leads', 'disbursement', 'revenue']);
    $showBranch = $show(['disbursement', 'performance']);
    $showTargets = $show(['performance']);
    $showStatus = $show(['processing']);
    $showAlerts = $show(['processing', 'revenue']);
    $stages = $dash['funnel']['stages'] ?? [];
    $branchLabels = $dash['branches']['labels'] ?? [];
    $branchIds = $dash['branches']['ids'] ?? [];
    $branchClusters = $dash['branches']['cluster_ids'] ?? [];
    $branchDisb = $dash['branches']['disbursement_lac'] ?? [];
    $branchMax = max(1, (float) (count($branchDisb) ? max($branchDisb) : 1));
    $activeBranch = (string) ($dash['branches']['active_branch'] ?? '');
    $drillBase = $dash['branches']['drill_base'] ?? route('goal.analytics');
    $gaTrend = $dash['trend'] ?? ['labels' => [], 'disbursement_cr' => [], 'revenue_lac' => []];
    $gaCaseStatus = $dash['case_status'] ?? ['total' => 0, 'items' => []];
@endphp

@if(!empty($dash['load_error']))
    <div class="alert alert-warning">{{ $dash['load_error'] }}</div>
@endif

@if($show(['leads','processing','disbursement','revenue','performance']))
    <div class="pd-kpi-row">
        @forelse(($dash['kpis'] ?? []) as $kpi)
            @php
                $d = $kpi['delta'] ?? null;
                $deltaClass = $d === null ? 'flat' : ($d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat'));
                $deltaIcon = $d === null || $d == 0 ? 'bi-dash' : ($d > 0 ? 'bi-arrow-up' : 'bi-arrow-down');
            @endphp
            <a class="ga-kpi {{ $kpiTone[$kpi['tone'] ?? 'blue'] ?? 't-blue' }}" href="{{ $kpi['href'] ?? '#' }}">
                <span class="ga-kpi-ico"><i class="bi {{ $kpi['icon'] ?? 'bi-graph-up' }}"></i></span>
                <div class="ga-kpi-main">
                    <div class="ga-kpi-value">{{ $kpi['value'] }}</div>
                    <div class="ga-kpi-label">{{ $kpi['label'] }}</div>
                </div>
                <div class="ga-kpi-delta {{ $deltaClass }}">
                    <i class="bi {{ $deltaIcon }}"></i>
                    @if($d !== null)
                        <span>{{ $d > 0 ? '+' : '' }}{{ $d }}%</span>
                        <span class="vs">vs previous period</span>
                    @else
                        <span>—</span>
                        <span class="vs">vs previous period</span>
                    @endif
                </div>
                <div class="ga-kpi-wave" aria-hidden="true">
                    <svg viewBox="0 0 140 90" preserveAspectRatio="none">
                        <path class="w0" d="M0 58 C28 28 52 72 78 46 C98 28 118 34 140 22 V90 H0 Z"/>
                        <path class="w1" d="M0 68 C30 42 54 78 82 56 C104 40 122 48 140 36 V90 H0 Z"/>
                        <path class="w2" d="M0 78 C34 58 58 84 88 68 C110 56 126 62 140 52 V90 H0 Z"/>
                    </svg>
                </div>
            </a>
        @empty
            <div class="empty-soft" style="grid-column:1/-1">No KPI data for this scope.</div>
        @endforelse
    </div>
@endif

@if($showFunnel || $showTrend || $showBranch)
<div class="row g-3 mb-3">
    @if($showFunnel)
    <div class="col-xl-4 col-lg-6">
        <div class="panel">
            <div class="panel-h">
                <div class="panel-h-left">
                    <span class="panel-ico"><i class="bi bi-funnel-fill"></i></span>
                    <div>
                        <h5>Loan Funnel</h5>
                        <div class="sub">Lead to disbursement conversion and stage-wise performance.</div>
                    </div>
                </div>
                <span class="conv-badge">Overall Conversion: {{ number_format($dash['funnel']['overall_conversion'] ?? 0, 1) }}%</span>
            </div>
            <div class="panel-b">
                @if(count($stages))
                    <div class="funnel-layout">
                        <div class="funnel-viz">
                            @foreach($stages as $i => $st)
                                <a class="funnel-seg s{{ $i + 1 }}" href="{{ $st['href'] ?? '#' }}"
                                   style="background:{{ $st['color'] ?? '#1d4ed8' }}">{{ $st['label'] }}</a>
                            @endforeach
                        </div>
                        <div class="funnel-meta">
                            @foreach($stages as $st)
                                <a class="funnel-meta-row" href="{{ $st['href'] ?? '#' }}">
                                    <span class="cnt">{{ number_format($st['count']) }}</span>
                                    <span class="pct {{ ((float)($st['from_prev_pct'] ?? 0) <= 0 ? 'is-zero' : '') }}">{{ number_format($st['from_prev_pct'], 1) }}%</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty-soft">No funnel data for selected filters.</div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($showTrend)
    <div class="col-xl-4 col-lg-6">
        <div class="panel">
            <div class="panel-h">
                <div class="panel-h-left">
                    <span class="panel-ico"><i class="bi bi-graph-up"></i></span>
                    <div>
                        <h5>Disbursement &amp; Revenue Trend</h5>
                        <div class="sub">Monthly trend of disbursement and revenue.</div>
                    </div>
                </div>
                <div class="chart-legend">
                    <span><i style="background:#0d9488"></i> Disbursement (₹ Cr)</span>
                    <span><i style="background:#2563eb"></i> Revenue (₹ Lac)</span>
                </div>
            </div>
            <div class="panel-b">
                <div class="chart-box"><canvas id="gaTrendChart"></canvas></div>
            </div>
        </div>
    </div>
    @endif

    @if($showBranch)
    <div class="col-xl-4 col-lg-12">
        <div class="panel is-branch">
            <div class="panel-h">
                <div class="panel-h-left">
                    <span class="panel-ico"><i class="bi bi-geo-alt-fill"></i></span>
                    <div>
                        <h5>Branch Performance</h5>
                        <div class="sub">Top branches by disbursement amount.</div>
                    </div>
                </div>
            </div>
            <div class="panel-b">
                @if(count($branchLabels))
                    <div class="branch-list">
                        @foreach($branchLabels as $i => $name)
                            @php
                                $bid = $branchIds[$i] ?? null;
                                $cid = $branchClusters[$i] ?? null;
                                $val = (float) ($branchDisb[$i] ?? 0);
                                $pct = $val > 0 ? min(100, max(6, round(($val / $branchMax) * 100))) : 0;
                                $url = $drillBase;
                                if ($activeBranch !== '' && (string) $bid === $activeBranch && $cid) {
                                    $url .= (str_contains($url, '?') ? '&' : '?') . 'cluster=' . urlencode((string) $cid);
                                } elseif ($bid) {
                                    $url .= (str_contains($url, '?') ? '&' : '?') . 'branch=' . urlencode((string) $bid);
                                }
                            @endphp
                            <a class="branch-row" href="{{ $url }}">
                                <span class="nm">{{ $name }}</span>
                                <span class="branch-bar"><span class="{{ $pct <= 0 ? 'is-empty' : '' }}" style="width:{{ $pct }}%"></span></span>
                                <span class="amt">₹ {{ number_format($val, 1) }}L</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-soft">No branch disbursement data.</div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endif

@if($showTargets || $showStatus || $showAlerts)
<div class="row g-3 mb-3 row-eq align-items-stretch">
    @if($showTargets)
    <div class="col-xl-4 col-lg-6">
        <div class="panel">
            <div class="panel-h">
                <div class="panel-h-left">
                    <span class="panel-ico"><i class="bi bi-trophy-fill"></i></span>
                    <div>
                        <h5>Target Achievement</h5>
                        <div class="sub">Target vs achievement by employee, branch, cluster and region.</div>
                    </div>
                </div>
            </div>
            <div class="panel-b" id="gaTargetPanel">
                @php
                    $tgtDims = ['employee'=>'Employee','branch'=>'Branch','cluster'=>'Cluster','region'=>'Region'];
                @endphp
                <div class="tgt-tabs" role="tablist">
                    @foreach($tgtDims as $key => $label)
                        <button type="button"
                                class="tgt-tab {{ $targetTab === $key ? 'active' : '' }}"
                                data-target-tab="{{ $key }}"
                                role="tab"
                                aria-selected="{{ $targetTab === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
                @foreach($tgtDims as $key => $label)
                    @php $rows = $dash['targets'][$key] ?? []; @endphp
                    <div class="tgt-pane {{ $targetTab === $key ? 'is-active' : '' }}" data-target-pane="{{ $key }}" role="tabpanel">
                        <div class="table-responsive">
                            <table class="tgt-table">
                                <thead>
                                    <tr>
                                        <th>{{ $label }}</th>
                                        <th>Target (₹)</th>
                                        <th>Achieved (₹)</th>
                                        <th>Achievement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rows as $row)
                                        <tr>
                                            <td class="name">{{ $row['name'] }}</td>
                                            <td class="amt">{{ $row['target'] }}</td>
                                            <td class="amt">{{ $row['achieved'] }}</td>
                                            <td>
                                                <div class="tgt-ach">
                                                    <span class="pct">{{ (int)$row['percent'] }}%</span>
                                                    <div class="prog"><span style="width:{{ min(100, max(0, (int)$row['percent'])) }}%"></span></div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="empty-soft">No targets for this period / dimension.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($showStatus)
    <div class="col-xl-4 col-lg-6">
        <div class="panel">
            <div class="panel-h">
                <div class="panel-h-left">
                    <span class="panel-ico"><i class="bi bi-pie-chart-fill"></i></span>
                    <div>
                        <h5>Case Status Distribution</h5>
                        <div class="sub">Current status of loan cases.</div>
                    </div>
                </div>
            </div>
            <div class="panel-b">
                @php $cs = $dash['case_status'] ?? ['total'=>0,'items'=>[]]; @endphp
                <div class="status-grid">
                    <div class="donut-wrap">
                        <canvas id="gaStatusChart"></canvas>
                        <div class="donut-center">
                            <div class="num">{{ number_format($cs['total'] ?? 0) }}</div>
                            <div class="txt">Cases</div>
                        </div>
                    </div>
                    <div class="legend-list">
                        @forelse(($cs['items'] ?? []) as $item)
                            <a class="legend-item" href="{{ $item['href'] ?? '#' }}">
                                <span class="left">
                                    <span class="dot" style="background:{{ $item['color'] }}"></span>
                                    <span class="name">{{ $item['label'] }}</span>
                                </span>
                                <span class="val">{{ number_format($item['percent'], 1) }}% <span class="cnt">({{ number_format($item['count']) }})</span></span>
                            </a>
                        @empty
                            <div class="empty-soft">No case status data.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($showAlerts)
    <div class="col-xl-4 col-lg-12">
        <div class="panel">
            <div class="panel-h">
                <div class="panel-h-left">
                    <span class="panel-ico"><i class="bi bi-bell-fill"></i></span>
                    <div>
                        <h5>Operational Alerts</h5>
                        <div class="sub">Key actions that need immediate attention.</div>
                    </div>
                </div>
                <a class="view-all" href="{{ route('goal.allocation.index') }}">View All →</a>
            </div>
            <div class="panel-b">
                <div class="alert-list">
                    @forelse(($dash['alerts'] ?? []) as $al)
                        <a class="alert-item" href="{{ $al['href'] ?? '#' }}">
                            <span class="ico"><i class="bi {{ $al['icon'] ?? 'bi-exclamation-circle' }}"></i></span>
                            <span class="lbl">{{ $al['label'] }}</span>
                            <span class="cnt">{{ number_format($al['count']) }}</span>
                            <span class="prio {{ $al['tone'] ?? 'warning' }}">{{ $al['priority'] ?? '' }}</span>
                        </a>
                    @empty
                        <div class="empty-soft">No operational alerts.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<div id="gaChartData" hidden>@json(['trend' => $gaTrend, 'case_status' => $gaCaseStatus])</div>
