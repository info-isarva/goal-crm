<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>New Task</title>
	<style>
		body { font-family: Arial, Helvetica, sans-serif; background:#f6f8fb; color:#333; margin:0; padding:0; }
		.email-wrapper { width:100%; padding:30px 16px; }
		.email-container { max-width:600px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.08); }
		.email-header { background:#0b63d1; color:#fff; padding:18px 24px; }
		.email-body { padding:24px; }
		.btn { display:inline-block; background:#0b63d1; color:#fff !important; padding:10px 18px; text-decoration:none; border-radius:6px; }
		.meta { color:#6b7280; font-size:13px; }
		.footer { padding:16px 24px; font-size:12px; color:#9aa0a6; background:#fbfdff; }
		a.related-link { color:#0b63d1; font-weight:600; text-decoration:underline; }
	</style>
</head>
<body>
	<div class="email-wrapper">
		<div class="email-container">
			<div class="email-header">
				<strong>{{ config('app.name') }}</strong>
			</div>
			<div class="email-body">
				<h2 style="margin-top:0;">New Task Assigned</h2>
				<p class="meta">Hi {{ $user->name }},</p>
				<p style="font-size:15px;">A new task <strong>"{{ $task->name }}"</strong> has been created.</p>

				@if(!empty($task->description))
					<p><strong>Description:</strong><br>{{ $task->description }}</p>
				@endif

				<table width="100%" style="margin:16px 0;border-collapse:collapse;">
					<tr>
						<td style="padding:8px 0;"><strong>Due At</strong></td>
						<td style="padding:8px 0; text-align:right;">{{ \Carbon\Carbon::parse($task->due_at)->format('d M Y, H:i') }}</td>
					</tr>
					<tr>
						<td style="padding:8px 0;"><strong>Priority</strong></td>
						<td style="padding:8px 0; text-align:right;">{{ ucfirst($task->priority) }}</td>
					</tr>
					@if(!empty($task->status))
					<tr>
						<td style="padding:8px 0;"><strong>Status</strong></td>
						<td style="padding:8px 0; text-align:right;">{{ $task->status }}</td>
					</tr>
					@endif
				</table>

				@if(!empty($related))
					<hr style="border:none;border-top:1px solid #eef2f7;margin:18px 0;">
					<p style="margin:0 0 8px 0;">
						<strong>{{ $related['label'] }}:</strong>
						<a class="related-link" href="{{ $related['url'] }}">{{ $related['name'] }}</a>
					</p>
					<p style="margin-top:18px;">
						<a class="btn" href="{{ $related['url'] }}">Open {{ $related['label'] }}</a>
					</p>
				@endif
			</div>
			<div class="footer">
				<div>{{ config('app.name') }} — Task notification</div>
			</div>
		</div>
	</div>
</body>
</html>
