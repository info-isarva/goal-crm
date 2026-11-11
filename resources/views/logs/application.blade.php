@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow p-4">
        <h2 class="mb-4">Application Logs</h2>
        <pre style="background:#222;color:#f9c74f;padding:1rem;border-radius:8px;max-height:600px;overflow:auto;font-size:0.95rem;">
{{ $logs }}
        </pre>
    </div>
</div>
@endsection
