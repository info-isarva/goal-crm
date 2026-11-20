<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:dejavusans,sans-serif;font-size:9pt;color:#0f172a}
        .brand{background:#0f766e;color:#fff;padding:12px 14px;margin:0 0 10px}
        .brand h1{margin:0;font-size:14pt;font-weight:700}
        .brand p{margin:4px 0 0;font-size:8pt;color:#ccfbf1}
        .filters{margin:0 0 10px;font-size:7.5pt;color:#475569}
        .filters span{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:2px 6px;margin:0 4px 4px 0}
        .kpis{width:100%;margin:0 0 12px;border-collapse:collapse}
        .kpis td{border:1px solid #e2e8f0;padding:8px 10px;background:#f8fafc;width:20%}
        .kpis .lbl{font-size:7pt;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
        .kpis .val{font-size:11pt;font-weight:800;margin-top:2px;white-space:nowrap}
        table.data{width:100%;border-collapse:collapse}
        table.data th{background:#0f766e;color:#fff;font-size:7.5pt;padding:6px;text-align:left;border:1px solid #0d9488}
        table.data td{font-size:8pt;padding:5px;border:1px solid #e2e8f0;vertical-align:top}
        table.data tr:nth-child(even) td{background:#f8fafc}
        .amt{text-align:right;font-weight:700;white-space:nowrap}
        .neg{color:#b91c1c}
        .empty{text-align:center;color:#94a3b8;padding:16px}
        .type{font-size:7.5pt;font-weight:700}
        .foot{margin-top:10px;font-size:7.5pt;color:#64748b}
    </style>
</head>
<body>
    <div class="brand">
        <h1>Revenue Ledger Export</h1>
        <p>Generated {{ $generatedAt->format('d M Y H:i') }} · {{ count($rows) }} entr{{ count($rows) === 1 ? 'y' : 'ies' }}</p>
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
                <div class="lbl">Entries</div>
                <div class="val">{{ number_format($summary['total'] ?? 0) }}</div>
            </td>
            <td>
                <div class="lbl">Recognized</div>
                <div class="val">₹ {{ number_format((float) ($summary['recognized'] ?? 0), 2) }}</div>
            </td>
            <td>
                <div class="lbl">Adjustments</div>
                <div class="val">₹ {{ number_format((float) ($summary['adjustments'] ?? 0), 2) }}</div>
            </td>
            <td>
                <div class="lbl">Reversals</div>
                <div class="val">₹ {{ number_format((float) ($summary['reversals'] ?? 0), 2) }}</div>
            </td>
            <td>
                <div class="lbl">Net ledger</div>
                <div class="val">₹ {{ number_format((float) ($summary['net'] ?? 0), 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>When</th>
                <th>Case</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Org unit</th>
                <th>Amount</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['when'] }}</td>
                    <td>{{ $r['case_no'] }}</td>
                    <td>{{ $r['customer'] }}</td>
                    <td class="type">{{ $r['type'] }}</td>
                    <td>{{ $r['org'] }}</td>
                    <td class="amt {{ ((float) $r['amount_raw'] < 0) ? 'neg' : '' }}">{{ $r['amount'] }}</td>
                    <td>{{ $r['note'] }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="7">No revenue entries for this filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="foot">Amounts reflect the current filter set. CSV / Excel exports include the same rows.</div>
</body>
</html>
