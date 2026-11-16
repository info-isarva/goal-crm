<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $form ? ($form->heading ?: $form->name) : 'Form unavailable' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f6fa;
            padding: 24px 16px;
        }
        .wf-wrap { max-width: 640px; margin: 0 auto; }
        body.wf-embedded { background: transparent; padding: 8px; }
    </style>
</head>
<body>
    <div class="wf-wrap">
        <div id="crm-webform-{{ $token }}"></div>
    </div>
    <script>
        if (window.self !== window.top) { document.body.className = 'wf-embedded'; }
    </script>
    <script src="{{ route('webforms.public.embed', $token) }}"></script>
</body>
</html>
