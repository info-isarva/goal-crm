@php
    $kpis = $dash['kpis'] ?? [];
    $kpiChunks = array_chunk($kpis, 3);
    $stages = $dash['funnel']['stages'] ?? [];
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
        .kpi-delta{font-size:7.5pt;color:#64748b;margin-top:2px}
        .kpi-bar{height:3px;width:100%;margin-top:6px;border-radius:2px;background:#0f766e}
        .data-table{width:100%;border-collapse:collapse;margin:0 0 8px 0}
        .data-table th{
            background:#0f766e;color:#ffffff;font-size:7.5pt;font-weight:700;
            text-transform:uppercase;letter-spacing:.02em;padding:7px 6px;border:1px solid #0d9488;
            text-align:left
        }
        .data-table td{
            font-size:8pt;padding:6px;border:1px solid #e2e8f0;vertical-align:middle;color:#1e293b
        }
        .data-table tr:nth-child(even) td{background:#f8fafc}
        .footer{color:#64748b;font-size:7.5pt;margin-top:14px}
    </style>
</head>
<body>
    <div class="brand-bar">
        <div class="brand-title">{{ $schedule->name }}</div>
        <p class="brand-sub">
            {{ $schedule->typeLabel() }} · Generated {{ $generatedAt->format('d M Y H:i') }}
            · For {{ $owner->name }} · Scope: {{ $dash['scope_label'] ?? '—' }}
        </p>
    </div>

    <table class="meta-box">
        <tr>
            <td style="width:33%;">
                <div class="meta-label">Frequency</div>
                <div class="meta-value">{{ $schedule->frequencyLabel() }}</div>
            </td>
            <td style="width:33%;">
                <div class="meta-label">Format</div>
                <div class="meta-value">{{ $schedule->formatLabel() }}</div>
            </td>
            <td style="width:34%;">
                <div class="meta-label">Owner</div>
                <div class="meta-value">{{ $owner->name }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">KPI Summary</div>
    @forelse($kpiChunks as $chunk)
        <table class="kpi-wrap">
            <tr>
                @foreach($chunk as $kpi)
                    <td class="kpi-card" width="{{ (int) floor(100 / max(count($chunk), 1)) }}%">
                        <div class="kpi-label">{{ $kpi['label'] ?? '' }}</div>
                        <div class="kpi-value">{{ $kpi['value'] ?? '' }}</div>
                        <div class="kpi-delta">Delta: {{ $kpi['delta'] ?? '—' }}%</div>
                        <div class="kpi-bar"></div>
                    </td>
                @endforeach
                @for($i = count($chunk); $i < 3; $i++)
                    <td width="33%"></td>
                @endfor
            </tr>
        </table>
    @empty
        <p style="color:#64748b;font-size:8pt;">No KPI data for this scope.</p>
    @endforelse

    <div class="section-title">Loan Funnel</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Stage</th>
                <th>Count</th>
                <th>From Prev %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stages as $st)
                <tr>
                    <td>{{ $st['label'] ?? '' }}</td>
                    <td>{{ number_format($st['count'] ?? 0) }}</td>
                    <td>{{ number_format($st['from_prev_pct'] ?? 0, 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="3">No funnel data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">
        This report uses the same role-based data permissions as the Analytics Dashboard.
        Unauthorized branch or financial data is never included.
    </p>
</body>
</html>
