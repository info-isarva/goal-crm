<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Lead Created</title>
</head>
<body>
    <h2>New Lead Created</h2>

    <p>A new lead has been created in the CRM.</p>

    <p><strong>Title:</strong> {{ $lead->title ?? 'N/A' }}</p>
    <p><strong>Description:</strong> {{ $lead->description ?? 'N/A' }}</p>
    <p><strong>Status:</strong> {{ $lead->status ?? 'N/A' }}</p>
    <p><strong>Lead ID:</strong> {{ $lead->id ?? 'N/A' }}</p>
    <p>If you need more deatils, go to <a href="{{ url('/leads/' . $lead->id) }}">Lead Details</a>.</p>

    <p>Regards,<br>CRM System</p>
</body>
</html>
