<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>User Reports PDF</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; }
        h2 { color: #2c3e50; margin-bottom: 10px; }
        .card { border: 1px solid #ccc; margin-bottom: 20px; border-radius: 6px; }
        .card-header { background: #f5f5f5; font-weight: bold; padding: 8px 12px; border-bottom: 1px solid #eee; }
        .card-body { padding: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #e9ecef; }
        .badge { background: #17a2b8; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 12px; }
        .heading { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="heading mb-4">
    <h2>User Reports</h2>
    <h3><strong>Starting from </strong> {{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y')  }} &nbsp; <strong>to </strong> {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }} </h3>
    </div>
    @if(request('user_id') || request('from_date') || request('to_date'))
        @foreach($results as $row)
            <div class="card">
                <div class="card-header">
                    {{ $row['user']->name }}
                </div>
                <div class="card-body">
                    <h5>Leads <span class="badge">{{ count($row['leads']) }}</span></h5>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Title</th>
                                <th>Company Name</th>
                                <th>Converted to Deal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($row['leads'] as $lead)
                                <tr>
                                    <td>{{ $lead->created_at ? \Carbon\Carbon::parse($lead->created_at)->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $lead->title }}</td>
                                    <td>{{ $lead->organization ? $lead->organization->name : '-' }}</td>
                                    <td>{{ $lead->converted_at ? 'Yes' : 'No' }}</td>
                                    <td>{{ $lead->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No leads found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <h5>Deals <span class="badge">{{ count($row['deals']) }}</span></h5>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Title</th>
                                <th>Company Name</th>
                                <th>Expected Close Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($row['deals'] as $deal)
                                <tr>
                                    <td>{{ $deal->created_at ? \Carbon\Carbon::parse($deal->created_at)->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $deal->title }}</td>
                                    <td>{{ $deal->organization ? $deal->organization->name : '-' }}</td>
                                    <td>{{ $deal->close_date ? \Carbon\Carbon::parse($deal->close_date)->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $deal->stage_name }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">No deals found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
</body>
</html>
