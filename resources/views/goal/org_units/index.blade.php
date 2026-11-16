@extends('layouts.app')
@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Org Hierarchy</h4>
        <a href="{{ route('goal.org-units.create') }}" class="btn btn-custom btn-sm">Add unit</a>
    </div>
    <div class="card"><div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Name</th><th>Code</th><th>Type</th><th>Parent</th><th>Active</th><th></th></tr></thead>
            <tbody>
            @forelse($units as $unit)
                <tr>
                    <td>{{ $unit->name }}</td>
                    <td><code>{{ $unit->code }}</code></td>
                    <td class="text-capitalize">{{ $unit->type }}</td>
                    <td>{{ $unit->parent?->name ?? '—' }}</td>
                    <td>{{ $unit->is_active ? 'Yes' : 'No' }}</td>
                    <td><a href="{{ route('goal.org-units.edit', $unit) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted p-3">No org units yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
