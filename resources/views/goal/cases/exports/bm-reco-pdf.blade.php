@php
    $app = data_get($br, 'profile.applicant', []) ?: [];
    $co = data_get($br, 'profile.co_applicant', []) ?: [];
    $btRows = data_get($loans, 'bt_source', []);
    if (is_array($btRows) && isset($btRows['loan_type'])) {
        $btRows = [$btRows];
    }
    $btRows = is_array($btRows) ? array_values(array_filter($btRows, 'is_array')) : [];
    $loanRows = data_get($loans, 'rows', []);
    $loanRows = is_array($loanRows) ? array_values(array_filter($loanRows, 'is_array')) : [];
    $contacts = data_get($br, 'contacts', []) ?: [];
    $money = function ($v) {
        if ($v === null || $v === '') return '—';
        if (!is_numeric($v)) return (string) $v;
        return '₹ '.number_format((float) $v, 0);
    };
    $txt = fn ($v) => ($v === null || $v === '') ? '—' : (string) $v;
    $equifaxReceived = ! empty(data_get($br, 'equifax.received'))
        || filled(data_get($br, 'equifax.recorded_at'))
        || filled(data_get($br, 'equifax.path'))
        || filled(data_get($br, 'equifax.file_name'))
        || filled(data_get($br, 'equifax.score'))
        || filled(data_get($br, 'equifax.notes'));
    $equifaxScore = data_get($br, 'equifax.score');
    $equifaxNotes = data_get($br, 'equifax.notes');
    $equifaxFile = data_get($br, 'equifax.file_name');
    $titleName = strtoupper((string) ($app['name'] ?? $case->customer_name ?? 'CUSTOMER'));
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BM Reco — {{ $case->case_no }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 10pt; color: #0f172a; line-height: 1.35; }
        h1 { font-size: 13pt; margin: 0 0 2px; color: #0f172a; }
        h2 { font-size: 10.5pt; margin: 14px 0 6px; color: #1e3a8a; text-transform: uppercase; letter-spacing: .03em; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        .meta { font-size: 8.5pt; color: #64748b; margin-bottom: 10px; }
        .letter { margin-bottom: 10px; }
        .letter p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; vertical-align: top; font-size: 9pt; }
        th { background: #eff6ff; color: #1e3a8a; font-weight: 700; }
        .label { width: 28%; color: #475569; font-weight: 600; background: #f8fafc; }
        .kv td { border: 0; padding: 2px 0; }
        .kv .k { width: 32%; color: #64748b; }
        .block { margin: 4px 0 8px; padding: 6px 8px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .foot { margin-top: 14px; font-size: 8.5pt; color: #64748b; }
        .badge { display: inline-block; padding: 1px 6px; border: 1px solid #86efac; background: #ecfdf5; color: #047857; font-size: 8pt; font-weight: 700; }
    </style>
</head>
<body>
    <h1>Goal Corporation — Proposal Brief (BM Reco)</h1>
    <div class="meta">
        Case {{ $case->case_no }}
        · {{ $case->orgUnit?->name ?: '—' }}
        · Exported {{ $exportedAt->format('d-m-Y H:i') }}
        @if($equifaxReceived)
            · <span class="badge">Equifax received</span>
        @endif
    </div>

    <div class="letter">
        <p>Dear Sir,</p>
        <p>Please find the below Proposal brief summary of <strong>{{ $titleName }}</strong>@if($equifaxReceived). Equifax / credit bureau check recorded.@else.@endif</p>
    </div>

    @if($equifaxReceived && ($equifaxScore || $equifaxNotes || $equifaxFile))
        <h2>Equifax / Credit Bureau</h2>
        <table>
            <tbody>
                @if($equifaxScore)
                    <tr><td class="label">Score / Reference</td><td>{{ $txt($equifaxScore) }}</td></tr>
                @endif
                @if($equifaxFile)
                    <tr><td class="label">Report file</td><td>{{ $txt($equifaxFile) }}</td></tr>
                @endif
                @if($equifaxNotes)
                    <tr><td class="label">Bureau data / notes</td><td>{{ $txt($equifaxNotes) }}</td></tr>
                @endif
            </tbody>
        </table>
    @endif

    <h2>Borrower Profile</h2>
    <table>
        <thead>
            <tr>
                <th>Field</th>
                <th>Applicant</th>
                <th>Co-Applicant</th>
            </tr>
        </thead>
        <tbody>
            @foreach([
                ['name', 'Name'],
                ['age', 'Age'],
                ['relationship', 'Relationship'],
                ['profession', 'Profession'],
                ['gross_income', 'Gross Income'],
                ['net_income', 'Net Income'],
                ['pan', 'PAN NO'],
                ['dob', 'DOB'],
            ] as [$key, $label])
                <tr>
                    <td class="label">{{ $label }}</td>
                    <td>{{ in_array($key, ['gross_income', 'net_income'], true) ? $money($app[$key] ?? null) : $txt($app[$key] ?? null) }}</td>
                    <td>{{ in_array($key, ['gross_income', 'net_income'], true) ? $money($co[$key] ?? null) : $txt($co[$key] ?? null) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Requirement</h2>
    <table class="kv">
        <tr><td class="k">Requirement</td><td>{{ $txt($br['requirement'] ?? $case->product) }}</td></tr>
        <tr><td class="k">Amount</td><td>{{ $money($br['amount'] ?? $br['recommended_amount'] ?? $case->loan_amount) }}</td></tr>
        <tr><td class="k">Eligibility</td><td>{{ $txt($br['eligibility'] ?? null) }}</td></tr>
        <tr><td class="k">Suggested Bank</td><td>{{ $txt($br['suggested_bank'] ?? $case->lender_name) }}</td></tr>
    </table>

    @if($btRows !== [])
        <h2>Loan Type (BT / Source)</h2>
        <table>
            <thead>
                <tr>
                    <th>Loan Type</th>
                    <th>Loan Amt</th>
                    <th>ROI</th>
                    <th>Tenor</th>
                    <th>EMI</th>
                </tr>
            </thead>
            <tbody>
                @foreach($btRows as $row)
                    <tr>
                        <td>{{ $txt($row['loan_type'] ?? null) }}</td>
                        <td>{{ $money($row['loan_amt'] ?? null) }}</td>
                        <td>{{ $txt($row['roi'] ?? null) }}</td>
                        <td>{{ $txt($row['tenor'] ?? null) }}</td>
                        <td>{{ $money($row['emi'] ?? null) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Existing Loans — Applicant / Co-Applicant</h2>
    <table>
        <thead>
            <tr>
                <th>Party</th>
                <th>Type</th>
                <th>Bank</th>
                <th>Sanction</th>
                <th>Outstanding</th>
                <th>EMI</th>
                <th>Paid</th>
                <th>Pending</th>
                <th>Bounces</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loanRows as $row)
                <tr>
                    <td>{{ $txt($row['party'] ?? null) }}</td>
                    <td>{{ $txt($row['type'] ?? null) }}</td>
                    <td>{{ $txt($row['bank'] ?? null) }}</td>
                    <td>{{ $money($row['sanction'] ?? null) }}</td>
                    <td>{{ $money($row['outstanding'] ?? null) }}</td>
                    <td>{{ $money($row['emi'] ?? null) }}</td>
                    <td>{{ $txt($row['paid'] ?? null) }}</td>
                    <td>{{ $txt($row['pending'] ?? null) }}</td>
                    <td>{{ $txt($row['bounces'] ?? null) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;color:#94a3b8">No existing loan rows</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>App &amp; Co-App Background</h2>
    <div class="block">{{ $txt($br['background'] ?? null) }}</div>

    <h2>Collateral Details</h2>
    <table class="kv">
        <tr><td class="k">Type of Collateral</td><td>{{ $txt($col['type'] ?? null) }}</td></tr>
        <tr><td class="k">Property Address</td><td>{{ $txt($col['address'] ?? null) }}</td></tr>
        <tr><td class="k">LA/BU Area</td><td>{{ $txt($col['area'] ?? null) }}</td></tr>
        <tr><td class="k">Construction Stage</td><td>{{ $txt($col['stage'] ?? null) }}</td></tr>
        <tr><td class="k">Type of Khata</td><td>{{ $txt($col['khata'] ?? null) }}</td></tr>
    </table>

    <h2>Mitigates</h2>
    <div class="block">{{ $txt($br['mitigants'] ?? null) }}</div>

    <h2>Deviation</h2>
    <div class="block">{{ $txt($br['deviation'] ?? 'NA') }}</div>

    @if(!empty($br['remarks']))
        <h2>Remarks</h2>
        <div class="block">{{ $txt($br['remarks']) }}</div>
    @endif

    <h2>Contacts</h2>
    <table>
        <thead>
            <tr>
                <th>Role</th>
                <th>Name</th>
                <th>Contact</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>SM</td><td>{{ $txt($contacts['sm_name'] ?? $case->owner?->name) }}</td><td>{{ $txt($contacts['sm_mobile'] ?? $case->owner?->mobile) }}</td></tr>
            <tr><td>JSM</td><td>{{ $txt($contacts['asm_name'] ?? null) }}</td><td>{{ $txt($contacts['asm_mobile'] ?? null) }}</td></tr>
            <tr><td>SSM</td><td>{{ $txt($contacts['rsm_name'] ?? null) }}</td><td>{{ $txt($contacts['rsm_mobile'] ?? null) }}</td></tr>
            <tr><td>BM</td><td>{{ $txt($contacts['bm_name'] ?? null) }}</td><td>{{ $txt($contacts['bm_mobile'] ?? null) }}</td></tr>
        </tbody>
    </table>

    <div class="foot">
        Recommended by: {{ $txt($br['by'] ?? $br['recommended_by'] ?? null) }}
        · Date: {{ $txt($br['date'] ?? null) }}
        · Goal Loan CRM
    </div>
</body>
</html>
