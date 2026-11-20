<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:dejavusans,sans-serif;font-size:7pt;color:#0f172a}
        .brand{background:#0f766e;color:#fff;padding:10px 12px;margin:0 0 8px}
        .brand h1{margin:0;font-size:12pt;font-weight:700}
        .brand p{margin:3px 0 0;font-size:7.5pt;color:#ccfbf1}
        .filters{margin:0 0 8px;font-size:7pt;color:#475569}
        .filters span{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:3px;padding:1px 5px;margin:0 3px 3px 0}
        .kpis{width:100%;margin:0 0 8px;border-collapse:collapse}
        .kpis td{border:1px solid #e2e8f0;padding:6px 8px;background:#f8fafc;width:20%}
        .kpis .lbl{font-size:6.5pt;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
        .kpis .val{font-size:9pt;font-weight:800;margin-top:1px;white-space:nowrap}
        table.data{width:100%;border-collapse:collapse}
        table.data th{
            background:#0f766e;color:#fff;font-size:5.6pt;padding:3px 2px;text-align:left;
            border:1px solid #0d9488;white-space:nowrap;
        }
        table.data td{font-size:5.8pt;padding:2px 2px;border:1px solid #e2e8f0;vertical-align:top}
        table.data tr:nth-child(even) td{background:#f8fafc}
        .empty{text-align:center;color:#94a3b8;padding:14px;font-size:8pt}
        .foot{margin-top:8px;font-size:7pt;color:#64748b}
    </style>
</head>
<body>
    <div class="brand">
        <h1>Branch MIS — Full commercial sheet</h1>
        <p>Generated {{ $generatedAt->format('d M Y H:i') }} · {{ count($rows) }} case(s) · all export columns</p>
    </div>

    @if(!empty($filterLabels))
        <div class="filters">
            @foreach($filterLabels as $label)
                <span>{{ $label }}</span>
            @endforeach
        </div>
    @endif

    <table class="kpis">
        <tr>
            <td>
                <div class="lbl">Cases</div>
                <div class="val">{{ number_format($summary['cases'] ?? count($rows)) }}</div>
            </td>
            <td>
                <div class="lbl">Disbursed</div>
                <div class="val">₹ {{ number_format((float) ($summary['disb'] ?? 0), 2) }}</div>
            </td>
            <td>
                <div class="lbl">Payin</div>
                <div class="val">₹ {{ number_format((float) ($summary['payin'] ?? 0), 2) }}</div>
            </td>
            <td>
                <div class="lbl">Payout</div>
                <div class="val">₹ {{ number_format((float) ($summary['payout'] ?? 0), 2) }}</div>
            </td>
            <td>
                <div class="lbl">Net revenue</div>
                <div class="val">₹ {{ number_format((float) ($summary['net'] ?? 0), 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ ($cell === null || $cell === '') ? '—' : $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ max(1, count($headings)) }}">No Branch MIS rows for this filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="foot">Same columns as CSV / Excel. Scrollable on-screen Branch MIS table matches this sheet.</div>
</body>
</html>
