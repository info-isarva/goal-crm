<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Visits &amp; Follow-ups</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 10pt; color: #0f172a; }
        h1 { font-size: 14pt; margin: 0 0 4px; }
        .meta { font-size: 9pt; color: #64748b; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #ecfdf5; color: #065f46; font-weight: 700; font-size: 9pt; }
        td { font-size: 8.5pt; }
        .empty { text-align: center; color: #94a3b8; padding: 18px; }
    </style>
</head>
<body>
    <h1>Customer Visits &amp; Follow-ups</h1>
    <div class="meta">Exported on {{ now()->format('d-m-Y H:i') }} · {{ count($rows) }} record(s)</div>
    <table>
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
                        <td>{{ $cell ?: '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($headings) }}">No visit / follow-up tasks found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
