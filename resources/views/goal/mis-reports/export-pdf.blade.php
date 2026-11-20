@php
    $columns = $report['columns'] ?? [];
    $rows = array_slice($report['rows'] ?? [], 0, 400);
    $kpis = $report['kpis'] ?? [];
    $badgeKeys = ['status', 'priority', 'type', 'outcome', 'metric', 'achievement', 'conversion', 'approval_rate'];
    $toneMap = [
        'blue' => ['#dbeafe', '#1d4ed8'],
        'teal' => ['#ccfbf1', '#0f766e'],
        'amber' => ['#fef3c7', '#b45309'],
        'green' => ['#dcfce7', '#15803d'],
        'indigo' => ['#e0e7ff', '#4338ca'],
        'rose' => ['#ffe4e6', '#be123c'],
        'orange' => ['#ffedd5', '#c2410c'],
        'slate' => ['#f1f5f9', '#475569'],
        'purple' => ['#f3e8ff', '#7e22ce'],
    ];
    $badgeTone = function ($raw, $key = '') {
        $rawStr = trim((string) $raw);
        $v = strtolower($rawStr);
        if ($v === '' || $v === '—' || $v === '-') {
            return ['#f1f5f9', '#475569'];
        }
        if (in_array($key, ['achievement', 'conversion', 'approval_rate'], true) || str_ends_with($v, '%')) {
            $n = (float) preg_replace('/[^0-9.\-]/', '', $rawStr);
            if ($n >= 100) {
                return ['#dcfce7', '#15803d'];
            }
            if ($n >= 70) {
                return ['#ccfbf1', '#0f766e'];
            }
            if ($n >= 40) {
                return ['#fef3c7', '#b45309'];
            }

            return ['#ffe4e6', '#be123c'];
        }
        if (str_contains($v, 'disburs') || str_contains($v, 'complet') || str_contains($v, 'paid') || str_contains($v, 'verified') || str_contains($v, 'converted') || str_contains($v, 'sanction') || str_contains($v, 'interested') || str_contains($v, 'recognized') || str_contains($v, 'eligible') || $v === 'high' || str_contains($v, 'active')) {
            return ['#dcfce7', '#15803d'];
        }
        if (str_contains($v, 'reject') || str_contains($v, 'lost') || str_contains($v, 'missed') || str_contains($v, 'hold') || str_contains($v, 'overdue') || str_contains($v, 'revers') || $v === 'critical' || str_contains($v, 'cancel')) {
            return ['#ffe4e6', '#be123c'];
        }
        if (str_contains($v, 'query') || str_contains($v, 'pending') || str_contains($v, 'draft') || str_contains($v, 'open') || str_contains($v, 'unalloc') || str_contains($v, 'adjust') || $v === 'medium' || str_contains($v, 'process') || str_contains($v, 'partial')) {
            return ['#fef3c7', '#b45309'];
        }
        if (str_contains($v, 'login') || str_contains($v, 'allocat') || str_contains($v, 'new') || str_contains($v, 'visit') || str_contains($v, 'call') || str_contains($v, 'follow') || $v === 'low') {
            return ['#dbeafe', '#1d4ed8'];
        }
        if (str_contains($v, 'pms') || str_contains($v, 'ready') || str_contains($v, 'schedule')) {
            return ['#ccfbf1', '#0f766e'];
        }

        return ['#f1f5f9', '#475569'];
    };
    $kpiChunks = array_chunk($kpis, 4);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:dejavusans,sans-serif;font-size:9pt;color:#0f172a;line-height:1.35}
        .brand-bar{
            background:#0f766e;color:#ffffff;padding:12px 14px;margin:0 0 12px 0;
            border-radius:6px
        }
        .brand-title{font-size:15pt;font-weight:700;margin:0 0 3px 0;color:#ffffff}
        .brand-sub{font-size:8pt;margin:0;color:#ccfbf1}
        .meta-box{
            width:100%;border-collapse:collapse;margin:0 0 12px 0;
            background:#f8fafc;border:1px solid #e2e8f0
        }
        .meta-box td{border:0;padding:7px 10px;font-size:8.5pt;vertical-align:top}
        .meta-label{color:#64748b;font-size:7.5pt;font-weight:700;text-transform:uppercase;letter-spacing:.03em}
        .meta-value{color:#0f172a;font-weight:700;margin-top:2px}
        .section-title{
            font-size:11pt;font-weight:700;color:#0f172a;margin:14px 0 8px 0;
            padding-bottom:4px;border-bottom:2px solid #0f766e
        }
        .kpi-wrap{width:100%;border-collapse:separate;border-spacing:6px;margin:0 0 6px 0}
        .kpi-card{
            border:1px solid #e2e8f0;border-radius:5px;padding:8px 10px;
            background:#ffffff;vertical-align:top
        }
        .kpi-label{font-size:7.2pt;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
        .kpi-value{font-size:12pt;font-weight:700;color:#0f172a;margin-top:3px}
        .kpi-bar{height:3px;width:100%;margin-top:6px;border-radius:2px}
        .data-table{width:100%;border-collapse:collapse;margin:0 0 8px 0}
        .data-table th{
            background:#0f766e;color:#ffffff;font-size:7.5pt;font-weight:700;
            text-transform:uppercase;letter-spacing:.02em;padding:7px 6px;border:1px solid #0d9488;
            text-align:left
        }
        .data-table td{
            font-size:7.8pt;padding:6px;border:1px solid #e2e8f0;vertical-align:middle;color:#1e293b
        }
        .data-table tr:nth-child(even) td{background:#f8fafc}
        .badge{
            display:inline-block;padding:2px 7px;border-radius:10px;
            font-size:7pt;font-weight:700;line-height:1.2
        }
        .footer-note{font-size:7.5pt;color:#94a3b8;margin-top:10px;border-top:1px solid #e2e8f0;padding-top:6px}
        .empty{text-align:center;color:#64748b;padding:16px;font-weight:600}
    </style>
</head>
<body>
    <htmlpagefooter name="misFooter">
        <table width="100%" style="border-top:1px solid #e2e8f0;padding-top:4px;font-size:7.5pt;color:#94a3b8">
            <tr>
                <td width="60%">Goal Corporation · MIS Report · Confidential</td>
                <td width="40%" align="right">Page {PAGENO} of {nbpg}</td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="misFooter" value="on" />

    <div class="brand-bar">
        <div class="brand-title">{{ $report['title'] ?? $title }}</div>
        <div class="brand-sub">
            MIS Report · Generated {{ $generatedAt->format('d M Y H:i') }}
            · For {{ $owner->name }}
            · Scope: {{ $report['scope_label'] ?? '—' }}
        </div>
    </div>

    <table class="meta-box">
        <tr>
            <td width="34%">
                <div class="meta-label">Period</div>
                <div class="meta-value">{{ $report['period_label'] ?? '—' }}</div>
            </td>
            <td width="22%">
                <div class="meta-label">Financial Year</div>
                <div class="meta-value">{{ !empty($report['fy']) ? $report['fy'] : 'Current' }}</div>
            </td>
            <td width="22%">
                <div class="meta-label">Category</div>
                <div class="meta-value">{{ ucfirst((string) ($report['group'] ?? 'Report')) }}</div>
            </td>
            <td width="22%">
                <div class="meta-label">Records</div>
                <div class="meta-value">{{ number_format(count($report['rows'] ?? [])) }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="border-top:1px solid #e2e8f0">
                <div class="meta-label">Description</div>
                <div class="meta-value" style="font-weight:600;color:#334155">{{ $report['description'] ?? '' }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">KPI Summary</div>
    @forelse($kpiChunks as $chunk)
        <table class="kpi-wrap">
            <tr>
                @foreach($chunk as $kpi)
                    @php
                        $tone = $kpi['tone'] ?? 'slate';
                        $colors = $toneMap[$tone] ?? $toneMap['slate'];
                    @endphp
                    <td class="kpi-card" width="{{ round(100 / max(count($chunk), 1)) }}%">
                        <div class="kpi-label">{{ $kpi['label'] ?? '' }}</div>
                        <div class="kpi-value">{{ $kpi['value'] ?? '—' }}</div>
                        <div class="kpi-bar" style="background:{{ $colors[1] }}"></div>
                    </td>
                @endforeach
                @for($i = count($chunk); $i < 4; $i++)
                    <td width="{{ round(100 / 4) }}%"></td>
                @endfor
            </tr>
        </table>
    @empty
        <div class="empty">No KPI data for this report.</div>
    @endforelse

    <div class="section-title">Detailed Records</div>
    <table class="data-table">
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col['label'] ?? '' }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $col)
                        @php
                            $ck = $col['key'] ?? '';
                            $val = $row[$ck] ?? '—';
                            $isBadge = in_array($ck, $badgeKeys, true);
                        @endphp
                        <td>
                            @if($isBadge)
                                @php [$bg, $fg] = $badgeTone($val, $ck); @endphp
                                <span class="badge" style="background:{{ $bg }};color:{{ $fg }}">{{ $val }}</span>
                            @else
                                {{ $val }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(1, count($columns)) }}" class="empty">No records for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Role-scoped data · Filters applied · Amounts and statuses reconcile to live CRM modules.
        @if(count($report['rows'] ?? []) > 400)
            Showing first 400 of {{ number_format(count($report['rows'] ?? [])) }} records.
        @endif
    </div>
</body>
</html>
