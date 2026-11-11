@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Close Financial Year</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                Current Fin.Year <strong id="current-fy">@if(isset($current) && $current) {{ $current->from_date->format('d/m/Y') }} - {{ $current->to_date->format('d/m/Y') }} @else N/A @endif</strong>
            </div>

            <div class="mb-3">
                <div class="list-group">
                    <div class="list-group-item d-flex align-items-start">
                        <div class="me-3"><span class="badge bg-primary">1</span></div>
                        <div class="flex-fill">
                            <h6>Backing up data</h6>
                            <div id="backup-status" class="text-muted">Not started</div>
                        </div>
                    </div>
                    <div class="list-group-item d-flex align-items-start">
                        <div class="me-3"><span class="badge bg-secondary">2</span></div>
                        <div class="flex-fill">
                            <h6>Create new financial year</h6>
                            <div id="create-status" class="text-muted">Waiting for backup</div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                @if(isset($canClose) && $canClose)
                    <button id="start-close-btn" class="btn btn-custom">Start Close Financial Year</button>
                    <a href="{{ route('company.edit') }}" class="btn btn-link">Cancel</a>
                @else
                    <button id="start-close-btn" class="btn btn-custom" disabled>Start Close Financial Year</button>
                    <a href="{{ route('company.edit') }}" class="btn btn-link">Cancel</a>
                    @if(isset($current) && $current)
                        <div class="mt-2 text-muted">Closing is allowed only after the financial year end date: <strong>{{ $current->to_date->format('d/m/Y') }}</strong></div>
                    @endif
                @endif
                
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('start-close-btn').addEventListener('click', function(){
    var btn = this;
    btn.disabled = true;
    document.getElementById('backup-status').innerText = 'Running...';

    fetch("{{ route('company.close_year.backup') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(function(res){
        return res.json();
    }).then(function(json){
        if(json.success){
            document.getElementById('backup-status').innerText = 'Backup completed: ' + json.file;
            document.getElementById('create-status').innerText = 'Creating next financial year...';
            return fetch("{{ route('company.close_year.create') }}", { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
        }
        throw new Error(json.error || 'Unknown backup error');
    }).then(function(res){
        return res.json();
    }).then(function(json){
        if(json.success){
            document.getElementById('create-status').innerText = 'Next financial year created: ' + json.fin_key;
            btn.disabled = false;
        } else {
            document.getElementById('create-status').innerText = 'Failed: ' + (json.error || 'Unknown');
            btn.disabled = false;
        }
    }).catch(function(err){
        document.getElementById('backup-status').innerText = 'Failed: ' + err.message;
        btn.disabled = false;
    });
});
</script>

@endsection
