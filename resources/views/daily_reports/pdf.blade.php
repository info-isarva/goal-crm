<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #222; }
        .title-block { text-align: center; margin-bottom: 8px; }
        .title-block .company { font-size: 9pt; color: #64748b; letter-spacing: 0.5px; }
        .title-block .title { font-size: 15pt; font-weight: bold; color: #1f4e78; margin-top: 2px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta td { font-size: 8.5pt; color: #475569; padding: 5px 8px; background: #f1f5f9; border: 1px solid #e2e8f0; }
        .meta td strong { color: #111827; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            background: #2e75b6; color: #ffffff; font-weight: bold; font-size: 8.5pt;
            padding: 6px 5px; border: 1px solid #1f4e78; text-align: center;
        }
        table.data td { padding: 5px; border: 1px solid #cbd5e1; font-size: 8.5pt; vertical-align: top; }
        table.data tr.even td { background: #f7fafd; }
        table.data td.num { text-align: center; }
        table.data td.activity { width: 30%; }
        table.data tr.total td { background: #ddebf7; font-weight: bold; border-top: 2px solid #2e75b6; }
        .status-submitted { color: #15803d; font-weight: bold; }
        .status-draft { color: #b45309; font-weight: bold; }
        .empty { text-align: center; color: #64748b; padding: 14px; }
    </style>
</head>
<body>
    <htmlpagefooter name="footer">
        <table width="100%" style="font-size: 7.5pt; color: #94a3b8;">
            <tr>
                <td>Generated on {{ $meta['generated_on'] }}{{ $meta['generated_by'] ? ' by ' . $meta['generated_by'] : '' }}</td>
                <td style="text-align: right;">Page {PAGENO} of {nbpg}</td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="footer" value="on" />

    <div class="title-block">
        <div class="company">{{ strtoupper($meta['company']) }}</div>
        <div class="title">{{ $meta['title'] }}</div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Period:</strong> {{ $meta['period_label'] }} ({{ $meta['range_text'] }})</td>
            <td><strong>User:</strong> {{ $meta['user_label'] }}</td>
            <td><strong>Records:</strong> {{ $meta['record_count'] }}</td>
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
            @php
                $colCount = count($headings);
                $countFrom = 3;
                $countTo = 3 + $outreachColumnCount;
            @endphp
            @forelse($rows as $i => $row)
                <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
                    @foreach($row as $c => $value)
                        @if($c === 0 || ($c >= $countFrom && $c <= $countTo))
                            <td class="num">{{ $value }}</td>
                        @elseif($c === $colCount - 2)
                            <td class="activity">{{ $value }}</td>
                        @elseif($c === $colCount - 1)
                            <td class="num {{ $value === 'Draft' ? 'status-draft' : 'status-submitted' }}">{{ $value }}</td>
                        @else
                            <td>{{ $value }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ $colCount }}" class="empty">No daily reports found for the selected filters.</td></tr>
            @endforelse
            @if($totalsRow)
                <tr class="total">
                    @foreach($totalsRow as $c => $value)
                        <td class="{{ ($c >= $countFrom && $c <= $countTo) ? 'num' : '' }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
