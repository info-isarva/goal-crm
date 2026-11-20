<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:dejavusans,sans-serif;font-size:8.5pt;color:#0f172a}
        .brand{background:#0f766e;color:#fff;padding:12px 14px;margin:0 0 10px}
        .brand h1{margin:0;font-size:14pt;font-weight:700}
        .brand p{margin:4px 0 0;font-size:8pt;color:#ccfbf1}
        .filters{margin:0 0 10px;font-size:7.5pt;color:#475569}
        .filters span{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:2px 6px;margin:0 4px 4px 0}
        .kpis{width:100%;margin:0 0 12px;border-collapse:collapse}
        .kpis td{border:1px solid #e2e8f0;padding:8px 10px;background:#f8fafc;width:20%}
        .kpis .lbl{font-size:7pt;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
        .kpis .val{font-size:10pt;font-weight:800;margin-top:2px;white-space:nowrap}
        table.data{width:100%;border-collapse:collapse}
        table.data th{background:#0f766e;color:#fff;font-size:7pt;padding:5px 4px;text-align:left;border:1px solid #0d9488}
        table.data td{font-size:7.2pt;padding:4px;border:1px solid #e2e8f0;vertical-align:top}
        table.data tr:nth-child(even) td{background:#f8fafc}
        .amt{text-align:right;font-weight:700;white-space:nowrap}
        .empty{text-align:center;color:#94a3b8;padding:16px}
        .foot{margin-top:10px;font-size:7.5pt;color:#64748b}
    </style>
</head>
<body>
    <div class="brand">
        <h1>Branch MIS Export</h1>
        <p>Generated {{ $generatedAt->format('d M Y H:i') }} · {{ count($rows) }} case(s) · landscape summary</p>
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
                <th>Case</th>
                <th>Customer</th>
                <th>Month</th>
                <th>Location</th>
                <th>Bank</th>
                <th>Status</th>
                <th>OTC/PDD</th>
                <th>Disb Amt</th>
                <th>Payin</th>
                <th>Payout</th>
                <th>Net Revenue</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['case_no'] }}</td>
                    <td>{{ $r['customer'] }}</td>
                    <td>{{ $r['month'] }}</td>
                    <td>{{ $r['location'] }}</td>
                    <td>{{ $r['bank'] }}</td>
                    <td>{{ $r['status'] }}</td>
                    <td>{{ $r['otc'] }}</td>
                    <td class="amt">{{ $r['disb_amt'] }}</td>
                    <td class="amt">{{ $r['payin_amt'] }}</td>
                    <td class="amt">{{ $r['payout_amt'] }}</td>
                    <td class="amt">{{ $r['net_revenue'] }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="11">No Branch MIS rows for this filter.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="foot">Full column set is available via CSV / Excel export. PDF shows key commercial columns for readability.</div>
</body>
</html>
