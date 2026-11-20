<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:dejavusans,sans-serif;font-size:9pt;color:#0f172a}
        .brand{background:#0f766e;color:#fff;padding:10px 12px;margin:0 0 12px;border-radius:6px}
        .brand h1{margin:0;font-size:14pt}
        .brand p{margin:3px 0 0;font-size:8pt;color:#ccfbf1}
        table{width:100%;border-collapse:collapse}
        th{background:#0f766e;color:#fff;font-size:7.5pt;padding:6px;text-align:left;border:1px solid #0d9488}
        td{font-size:7.8pt;padding:5px;border:1px solid #e2e8f0}
        tr:nth-child(even) td{background:#f8fafc}
    </style>
</head>
<body>
    <div class="brand">
        <h1>PMS Queue Export</h1>
        <p>Generated {{ $generatedAt->format('d M Y H:i') }} · {{ $rows->count() }} record(s)</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Case</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Branch</th>
                <th>Disb. Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Assigned</th>
                <th>Next FU</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $item)
                <tr>
                    <td>{{ $item->loanCase?->case_no }}</td>
                    <td>{{ $item->loanCase?->customer_name }}</td>
                    <td>{{ $item->loanCase?->product }}</td>
                    <td>{{ $item->loanCase?->orgUnit?->name }}</td>
                    <td>{{ optional($item->disbursement?->disbursed_on)->format('d M Y') }}</td>
                    <td>{{ number_format((float) ($item->disbursement?->amount ?? 0), 2) }}</td>
                    <td>{{ $item->statusLabel() }}</td>
                    <td>{{ $item->assignee?->name }}</td>
                    <td>{{ optional($item->follow_up_at)->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No records.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
