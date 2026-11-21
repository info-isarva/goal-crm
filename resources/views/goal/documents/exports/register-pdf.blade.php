<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Document Register</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #0f172a; }
        h1 { font-size: 13pt; margin: 0 0 4px; }
        .meta { font-size: 8.5pt; color: #64748b; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #ecfdf5; color: #134e4a; font-weight: 700; font-size: 8pt; }
        td { font-size: 7.5pt; }
        .empty { text-align: center; color: #94a3b8; padding: 16px; }
    </style>
</head>
<body>
    <h1>Document checklist register</h1>
    <div class="meta">
        Exported on {{ ($generatedAt ?? now())->format('d-m-Y H:i') }}
        · {{ count($rows) }} item{{ count($rows) === 1 ? '' : 's' }}
    </div>
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
                        <td>{{ $cell !== null && $cell !== '' ? $cell : '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ count($headings) }}">No documents found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
