<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $success ? 'Thank you' : 'Submission failed' }}</title>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f6fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 16px;
            box-sizing: border-box;
        }
        .box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 32px 28px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 18px rgba(15, 23, 42, .06);
        }
        .icon {
            width: 56px; height: 56px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 28px; color: #fff; margin-bottom: 14px;
        }
        p { margin: 0 0 18px; color: #374151; line-height: 1.6; }
        a { color: #2563eb; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon" style="background: {{ $success ? ($form->theme_color ?? '#16a34a') : '#dc2626' }};">{{ $success ? '✓' : '!' }}</div>
        <p>{{ $message }}</p>
        <a href="javascript:history.back()">&laquo; Go back</a>
    </div>
</body>
</html>
