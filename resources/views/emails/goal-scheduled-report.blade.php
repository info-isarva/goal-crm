@php
    $kpis = $dash['kpis'] ?? [];
    $stages = $dash['funnel']['stages'] ?? [];
    $mis = $dash['mis_report'] ?? null;
    $misColumns = $mis['columns'] ?? [];
    $misRows = array_slice($mis['rows'] ?? [], 0, 25);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $schedule->name }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td style="background:#0f766e;padding:16px 20px;">
                            <div style="font-size:18px;font-weight:700;color:#ffffff;line-height:1.3;">{{ $schedule->name }}</div>
                            <div style="font-size:12px;color:#ccfbf1;margin-top:4px;">
                                {{ $schedule->typeLabel() }} · Generated {{ now()->format('d M Y, h:i A') }}
                                · Scope: {{ $scopeLabel }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 20px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                                <tr>
                                    <td style="padding:10px 12px;font-size:12px;width:33%;">
                                        <div style="color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;">Frequency</div>
                                        <div style="font-weight:700;margin-top:2px;">{{ $schedule->frequencyLabel() }}</div>
                                    </td>
                                    <td style="padding:10px 12px;font-size:12px;width:33%;">
                                        <div style="color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;">Format</div>
                                        <div style="font-weight:700;margin-top:2px;">{{ $schedule->formatLabel() }}</div>
                                    </td>
                                    <td style="padding:10px 12px;font-size:12px;width:33%;">
                                        <div style="color:#64748b;font-size:10px;font-weight:700;text-transform:uppercase;">Attachment</div>
                                        <div style="font-weight:700;margin-top:2px;">Included below &amp; attached</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if(count($kpis))
                    <tr>
                        <td style="padding:8px 20px 4px;">
                            <div style="font-size:14px;font-weight:700;color:#0f172a;border-bottom:2px solid #0f766e;padding-bottom:6px;margin-bottom:10px;">KPI Summary</div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;">
                                <tr>
                                    <th align="left" style="background:#0f766e;color:#ffffff;font-size:11px;padding:8px 10px;border:1px solid #0d9488;">Metric</th>
                                    <th align="left" style="background:#0f766e;color:#ffffff;font-size:11px;padding:8px 10px;border:1px solid #0d9488;">Value</th>
                                    <th align="left" style="background:#0f766e;color:#ffffff;font-size:11px;padding:8px 10px;border:1px solid #0d9488;">Delta %</th>
                                </tr>
                                @foreach($kpis as $i => $kpi)
                                    <tr>
                                        <td style="font-size:12px;padding:8px 10px;border:1px solid #e2e8f0;background:{{ $i % 2 ? '#f8fafc' : '#ffffff' }};">{{ $kpi['label'] ?? '' }}</td>
                                        <td style="font-size:12px;padding:8px 10px;border:1px solid #e2e8f0;font-weight:700;background:{{ $i % 2 ? '#f8fafc' : '#ffffff' }};">{{ $kpi['value'] ?? '' }}</td>
                                        <td style="font-size:12px;padding:8px 10px;border:1px solid #e2e8f0;background:{{ $i % 2 ? '#f8fafc' : '#ffffff' }};">{{ $kpi['delta'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    @if(count($stages))
                    <tr>
                        <td style="padding:16px 20px 4px;">
                            <div style="font-size:14px;font-weight:700;color:#0f172a;border-bottom:2px solid #0f766e;padding-bottom:6px;margin-bottom:10px;">Loan Funnel</div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;">
                                <tr>
                                    <th align="left" style="background:#0f766e;color:#ffffff;font-size:11px;padding:8px 10px;border:1px solid #0d9488;">Stage</th>
                                    <th align="left" style="background:#0f766e;color:#ffffff;font-size:11px;padding:8px 10px;border:1px solid #0d9488;">Count</th>
                                    <th align="left" style="background:#0f766e;color:#ffffff;font-size:11px;padding:8px 10px;border:1px solid #0d9488;">From Prev %</th>
                                </tr>
                                @foreach($stages as $i => $st)
                                    <tr>
                                        <td style="font-size:12px;padding:8px 10px;border:1px solid #e2e8f0;background:{{ $i % 2 ? '#f8fafc' : '#ffffff' }};">{{ $st['label'] ?? '' }}</td>
                                        <td style="font-size:12px;padding:8px 10px;border:1px solid #e2e8f0;font-weight:700;background:{{ $i % 2 ? '#f8fafc' : '#ffffff' }};">{{ number_format($st['count'] ?? 0) }}</td>
                                        <td style="font-size:12px;padding:8px 10px;border:1px solid #e2e8f0;background:{{ $i % 2 ? '#f8fafc' : '#ffffff' }};">{{ number_format($st['from_prev_pct'] ?? 0, 1) }}%</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endif

                    @if($mis && count($misColumns) && count($misRows))
                    <tr>
                        <td style="padding:16px 20px 4px;">
                            <div style="font-size:14px;font-weight:700;color:#0f172a;border-bottom:2px solid #0f766e;padding-bottom:6px;margin-bottom:10px;">{{ $mis['title'] ?? 'Report Data' }}</div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e2e8f0;">
                                <tr>
                                    @foreach($misColumns as $col)
                                        <th align="left" style="background:#0f766e;color:#ffffff;font-size:10px;padding:7px 8px;border:1px solid #0d9488;">{{ $col['label'] ?? ($col['key'] ?? '') }}</th>
                                    @endforeach
                                </tr>
                                @foreach($misRows as $ri => $row)
                                    <tr>
                                        @foreach($misColumns as $col)
                                            @php $key = $col['key'] ?? ''; @endphp
                                            <td style="font-size:11px;padding:7px 8px;border:1px solid #e2e8f0;background:{{ $ri % 2 ? '#f8fafc' : '#ffffff' }};">{{ is_array($row) ? ($row[$key] ?? '') : '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                            @if(count($mis['rows'] ?? []) > 25)
                                <div style="font-size:11px;color:#64748b;margin-top:8px;">Showing first 25 rows. Full data is in the attachment.</div>
                            @endif
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td style="padding:18px 20px 20px;">
                            <div style="font-size:12px;color:#64748b;line-height:1.5;">
                                Full {{ $schedule->formatLabel() }} report is attached to this email.
                                Data uses the same role-based permissions as the Analytics Dashboard.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
