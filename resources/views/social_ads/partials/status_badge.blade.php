@php
    $map = [
        'created' => ['sa-badge-on', 'Lead Created'],
        'failed' => ['sa-badge-err', 'Failed'],
        'skipped' => ['sa-badge-warn', 'Skipped'],
        'test' => ['sa-badge-off', 'Test Lead'],
        'pending' => ['sa-badge-off', 'Pending'],
    ];
    [$cls, $text] = $map[$status] ?? ['sa-badge-off', ucfirst((string) $status)];
@endphp
<span class="badge {{ $cls }}" @if(!empty($title)) title="{{ $title }}" @endif>{{ $text }}</span>
