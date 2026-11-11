<!DOCTYPE html>
<html>
<head>
    <title>Meeting Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: #007bff;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .email-body {
            padding: 20px;
        }
        .email-footer {
            background: #f1f1f1;
            color: #555;
            text-align: center;
            padding: 10px;
            font-size: 12px;
        }
        ul {
            padding-left: 20px;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: #ffffff !important;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 8px;
        }
        a.related-link {
            color: #007bff;
            font-weight: 600;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Meeting Reminder</h1>
        </div>
        <div class="email-body">
            <p>Dear {{ $hostName ?? 'Host' }},</p>
            <p>This is a reminder for your upcoming meeting. Here are the details:</p>
            <ul>
                <li><strong>Title:</strong> {{ is_array($meeting) ? ($meeting['name'] ?? '') : ($meeting->name ?? '') }}</li>
                <li><strong>Description:</strong> {{ is_array($meeting) ? ($meeting['description'] ?? '') : ($meeting->description ?? '') }}</li>
                <li><strong>Start Time:</strong> {{ \Carbon\Carbon::parse(is_array($meeting) ? $meeting['start_at'] : $meeting->start_at)->format('F j, Y g:i A') }}</li>
                <li><strong>End Time:</strong> {{ \Carbon\Carbon::parse(is_array($meeting) ? $meeting['finish_at'] : $meeting->finish_at)->format('F j, Y g:i A') }}</li>
                <li><strong>Location:</strong> {{ is_array($meeting) ? ($meeting['location'] ?? '') : ($meeting->location ?? '') }}</li>
                @if(!empty($related))
                    <li>
                        <strong>{{ $related['label'] }}:</strong>
                        <a class="related-link" href="{{ $related['url'] }}">{{ $related['name'] }}</a>
                    </li>
                @endif
            </ul>
            @php
                $meetLink = is_array($meeting) ? ($meeting['meet_link'] ?? null) : ($meeting->meet_link ?? null);
            @endphp
            @if($meetLink)
                <p>Meeting Link: <a href="{{ $meetLink }}">{{ $meetLink }}</a></p>
            @endif
            @if(!empty($related))
                <p>
                    <a class="btn" href="{{ $related['url'] }}">Open {{ $related['label'] }}</a>
                </p>
            @endif
            <p>Thank you.</p>
        </div>
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
