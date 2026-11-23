<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:dejavusans,sans-serif;font-size:9pt;color:#0f172a}
        .hdr{border-bottom:2px solid #059669;padding:0 0 10px;margin:0 0 12px}
        .hdr .co{font-size:13pt;font-weight:800;color:#0f172a;margin:0}
        .hdr .prod{font-size:8pt;color:#64748b;margin:2px 0 0}
        .hdr .badge{display:inline-block;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;border-radius:4px;padding:2px 8px;font-size:7.5pt;font-weight:700;margin-top:6px}
        .title{font-size:12pt;font-weight:800;margin:0 0 4px;color:#0f172a;text-transform:uppercase;letter-spacing:.02em}
        .meta{font-size:8pt;color:#475569;margin:0 0 10px}
        .filters{margin:0 0 10px}
        .filters span{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:2px 6px;margin:0 4px 4px 0;font-size:7.5pt;color:#475569}
        .kpis{width:100%;border-collapse:collapse;margin:0 0 12px}
        .kpis td{border:1px solid #e2e8f0;background:#f8fafc;padding:8px 10px;width:25%}
        .kpis .lbl{font-size:7pt;font-weight:700;color:#64748b;text-transform:uppercase}
        .kpis .val{font-size:11pt;font-weight:800;margin-top:2px;white-space:nowrap}
        h3{font-size:9.5pt;margin:12px 0 6px;color:#0f172a}
        table.data{width:100%;border-collapse:collapse}
        table.data th{background:#059669;color:#fff;font-size:7.5pt;padding:6px;text-align:left;border:1px solid #047857}
        table.data td{font-size:7.5pt;padding:5px;border:1px solid #e2e8f0;vertical-align:top}
        table.data tr:nth-child(even) td{background:#f8fafc}
        .amt{text-align:right;white-space:nowrap;font-weight:700}
        .empty{text-align:center;color:#94a3b8;padding:18px}
        .foot{margin-top:14px;font-size:7.5pt;color:#64748b;border-top:1px solid #e2e8f0;padding-top:8px}
        .bars{width:100%;border-collapse:collapse;margin:0 0 10px}
        .bars td{padding:3px 4px;font-size:7.5pt}
        .bar-bg{background:#e2e8f0;height:8px;border-radius:4px}
        .bar-fill{background:#059669;height:8px;border-radius:4px}
    </style>
</head>
<body>
@php
    $meta = $meta ?? [];
    $kpis = $kpis ?? [];
    $columns = $columns ?? [];
    $rows = $rows ?? [];
    $fmtInr = function ($n) {
        return '₹ '.number_format((float) $n, 2);
    };
@endphp

<div class="hdr">
    <div class="co">{{ $meta['company'] ?? 'Goal Corporation' }}</div>
    <div class="prod">{{ $meta['product'] ?? 'Goal Loan CRM' }} · Official Report</div>
    <div class="badge">{{ $meta['report_id'] ?? '' }}</div>
</div>

<div class="title">{{ $meta['title'] ?? 'Revenue Report' }}</div>
<div class="meta">
    Financial Year {{ $meta['fy_label'] ?? '—' }}
    @if(!empty($meta['period_label'])) | {{ $meta['period_label'] }}@endif
</div>

@if(!empty($meta['filter_labels']))
    <div class="filters">
        @foreach($meta['filter_labels'] as $label)
            <span>{{ $label }}</span>
        @endforeach
    </div>
@endif

@if(!empty($kpis))
    <table class="kpis">
        <tr>
            @foreach(array_slice($kpis, 0, 4, true) as $label => $val)
                <td>
                    <div class="lbl">{{ str_replace('_', ' ', (string) $label) }}</div>
                    <div class="val">
                        @if(is_numeric($val) && !in_array($label, ['entries','employees','branches','clusters','regions','targets','count','matched','missing','disbursements','cases'], true))
                            {{ $fmtInr($val) }}
                        @else
                            {{ is_numeric($val) ? number_format((float)$val) : $val }}
                        @endif
                    </div>
                </td>
            @endforeach
        </tr>
    </table>
@endif

@if(!empty($charts['monthly']))
    <h3>Monthly Revenue Trend</h3>
    @php $maxNet = max(1, ...array_map(fn($m) => (float)($m['net'] ?? 0), $charts['monthly'])); @endphp
    <table class="bars">
        @foreach($charts['monthly'] as $m)
            @php $pct = min(100, round(((float)($m['net'] ?? 0) / $maxNet) * 100)); @endphp
            <tr>
                <td width="18%">{{ $m['label'] }}</td>
                <td width="62%"><div class="bar-bg"><div class="bar-fill" style="width:{{ $pct }}%"></div></div></td>
                <td width="20%" class="amt">{{ $fmtInr($m['net'] ?? 0) }}</td>
            </tr>
        @endforeach
    </table>
@endif

@foreach(($sections ?? [['title' => null, 'rows' => $rows]]) as $section)
    @if(!empty($section['title']))
        <h3>{{ $section['title'] }}</h3>
    @elseif(($meta['report_key'] ?? '') === 'summary')
        <h3>Branch-wise Revenue Summary</h3>
    @endif
    <table class="data">
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse(($section['rows'] ?? $rows) as $row)
                <tr>
                    @foreach($row as $i => $cell)
                        <td class="{{ $i > 0 && is_numeric(str_replace([',','.'], '', (string)$cell)) ? 'amt' : '' }}">{{ $cell === '' || $cell === null ? '—' : $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max(1, count($columns)) }}" class="empty">No records for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endforeach

<div class="foot">
    Generated by: {{ $meta['generated_by'] ?? '—' }}
    &nbsp;|&nbsp; Generated on: {{ $meta['generated_at'] ?? '—' }}
    &nbsp;|&nbsp; Report ID: {{ $meta['report_id'] ?? '—' }}
    &nbsp;|&nbsp; {{ $meta['company'] ?? 'Goal Corporation' }} | {{ $meta['title'] ?? 'Revenue Report' }}
</div>
</body>
</html>
