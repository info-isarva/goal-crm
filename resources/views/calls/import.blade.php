@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="card shadow-sm">
        <div class="card-header border-bottom-1">
            <h4 class="fw-bold text-primary">
                <i class="bi bi-list"></i> Import Call Logs (Excel)
            </h4>
        </div>
        <div class="card-body text-center">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="my-4">
                <i class="bi bi-calendar-x display-4 text-muted"></i>
                <h5 class="fw-bold">No Call Logs Imported</h5>
                <p class="text-muted">You haven’t imported any call logs yet. Ready to get started?</p>
            </div>

            <a href="{{ asset('files/sample_call_logs.xlsx') }}" class="btn btn-info mb-3">
                <i class="bi bi-download"></i> Download Sample Excel Sheet
            </a>

            <form action="{{ route('calllogs.import') }}" method="POST" enctype="multipart/form-data" class="mt-3">
                @csrf
                <div class="mb-4">
                    <label for="import_file" class="form-label fw-bold">Select Excel/CSV File</label>
                    <input type="file" name="import_file" id="import_file" class="form-control" accept=".xlsx,.csv" required>
                </div>

                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-success me-2 btn-padding">
                         <i class="bi bi-upload"></i> Upload & Import
                        
                    </button>
                    <a href="{{ route('calllogs.index') }}" class="btn btn-secondary btn-padding ms-2">
                        <i class="bi bi-arrow-left"></i> Back to Call Logs
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Required Columns</h5>
        </div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">Name</li>
                <li class="list-group-item">Company Name</li>
                <li class="list-group-item">Mobile Number</li>
                <li class="list-group-item">Requirement</li>
                <li class="list-group-item">Call Status</li>
                <li class="list-group-item">Lead Status</li>
            </ul>
        </div>
    </div>
</div>
@endsection
