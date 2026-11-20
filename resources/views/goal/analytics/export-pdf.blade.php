<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:dejavusans,sans-serif;font-size:11pt;color:#0f172a}
        h1{font-size:18pt;margin:0 0 4px}
        .muted{color:#64748b;font-size:9pt;margin:0 0 14px}
        table{width:100%;border-collapse:collapse;margin:0 0 16px}
        th,td{border:1px solid #e2e8f0;padding:7px 8px;text-align:left;font-size:9.5pt}
        th{background:#f1f5f9;font-weight:700;color:#475569}
        .meta td{border:0;padding:3px 0}
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="muted">
        Analytics Dashboard · Generated {{ $generatedAt->format('d M Y H:i') }}
        · For {{ $owner->name }} · Scope: {{ $dash['scope_label'] ?? '—' }}
    </p>

    <table class="meta">
        <tr><td><strong>Section:</strong> {{ $sectionLabel }}</td></tr>
        <tr><td><strong>Period:</strong> {{ ($dash['filters']['date_from'] ?? '') }} to {{ ($dash['filters']['date_to'] ?? '') }}</td></tr>
    </table>

    <h3 style="font-size:12pt;margin:0 0 8px;">KPI Summary</h3>
    <table>
        <thead>
            <tr><th>Metric</th><th>Value</th><th>Delta %</th></tr>
        </thead>
        <tbody>
            @forelse(($dash['kpis'] ?? []) as $kpi)
                <tr>
                    <td>{{ $kpi['label'] ?? '' }}</td>
                    <td>{{ $kpi['value'] ?? '' }}</td>
                    <td>{{ $kpi['delta'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No KPI data for this scope.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 style="font-size:12pt;margin:0 0 8px;">Loan Funnel</h3>
    <table>
        <thead>
            <tr><th>Stage</th><th>Count</th><th>From Prev %</th></tr>
        </thead>
        <tbody>
            @forelse(($dash['funnel']['stages'] ?? []) as $st)
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

    <h3 style="font-size:12pt;margin:0 0 8px;">Branch Performance</h3>
    <table>
        <thead>
            <tr><th>Branch</th><th>Disbursement (₹ Lac)</th></tr>
        </thead>
        <tbody>
            @php
                $labels = $dash['branches']['labels'] ?? [];
                $vals = $dash['branches']['disbursement_lac'] ?? [];
            @endphp
            @forelse($labels as $i => $name)
                <tr>
                    <td>{{ $name }}</td>
                    <td>{{ number_format((float) ($vals[$i] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No branch data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="muted">
        This export uses the same role-based data permissions as the Analytics Dashboard.
    </p>
</body>
</html>
