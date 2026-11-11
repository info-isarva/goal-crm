<!DOCTYPE html>
<html>
<head>
    <title>Meeting Reminder</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background:#f6f8fb; color:#333; margin:0; padding:0; }
        .email-wrapper { width:100%; padding:30px 16px; }
        .email-container { max-width:600px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.08); }
        .email-header { background:#0b63d1; color:#fff; padding:18px 24px; }
        .email-body { padding:24px; }
        .btn { display:inline-block; background:#0b63d1; color:#fff !important; padding:10px 18px; text-decoration:none; border-radius:6px; }
        a.related-link { color:#0b63d1; font-weight:600; text-decoration:underline; }
        .footer { padding:16px 24px; font-size:12px; color:#9aa0a6; background:#fbfdff; }
        ul { padding-left: 20px; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <strong>{{ config('app.name') }}</strong>
            </div>
            <div class="email-body">
                <h2 style="margin-top:0;">Meeting Reminder</h2>
                <p>Dear {{ $user->name }},</p>
                <p>This is a reminder for your upcoming meeting:</p>
                <ul>
                    <li><strong>Title:</strong> {{ $meeting->name }}</li>
                    <li><strong>Description:</strong> {{ $meeting->description }}</li>
                    <li><strong>Start Time:</strong> {{ \Carbon\Carbon::parse($meeting->start_at)->format('F j, Y g:i A') }}</li>
                    <li><strong>Location:</strong> {{ $meeting->location }}</li>
                    @if(!empty($related))
                        <li>
                            <strong>{{ $related['label'] }}:</strong>
                            <a class="related-link" href="{{ $related['url'] }}">{{ $related['name'] }}</a>
                        </li>
                    @endif
                </ul>
                @if(!empty($meeting->meet_link))
                    <p>Meeting Link: <a href="{{ $meeting->meet_link }}">{{ $meeting->meet_link }}</a></p>
                @endif
                @if(!empty($related))
                    <p style="margin-top:18px;">
                        <a class="btn" href="{{ $related['url'] }}">Open {{ $related['label'] }}</a>
                    </p>
                @endif
                <p>Thank you.</p>
            </div>
            <div class="footer">
                <div>{{ config('app.name') }} — Meeting reminder</div>
            </div>
        </div>
    </div>
</body>
</html>
