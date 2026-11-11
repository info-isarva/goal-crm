<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $form ? ($form->heading ?: $form->name) : 'Form unavailable' }}</title>
    <style>
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
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
