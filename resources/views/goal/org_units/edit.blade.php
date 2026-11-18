@extends('layouts.app')

@push('styles')
@include('goal.partials.pd_hub_styles')
@endpush

@section('content')
<div class="container-fluid p-4 pd-page">
    <div class="pd-hero">
        <div>
            <ul class="pd-crumbs">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li class="sep">›</li>
                <li><a href="{{ route('goal.org-units.index') }}">Org Hierarchy</a></li>
                <li class="sep">›</li>
                <li>Edit</li>
            </ul>
            <h1 class="pd-title">Edit Org Unit</h1>
            <p class="pd-sub">{{ $unit->name }} · {{ ucfirst($unit->type) }}</p>
        </div>
        <div class="pd-hero-actions">
            <a href="{{ route('goal.org-units.index') }}" class="pd-link-btn"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="pd-card">
        <div class="pd-card-head">
            <h5>Unit details</h5>
            <span class="pd-pill {{ $unit->is_active ? 'ok' : 'slate' }}">{{ $unit->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="p-3 p-md-4">
            <form method="POST" action="{{ route('goal.org-units.update', $unit) }}">
                @csrf
                @method('PUT')
                @include('goal.org_units._form', ['unit' => $unit])
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <button type="submit" class="pd-btn"><i class="bi bi-check-lg"></i> Update</button>
                    <a href="{{ route('goal.org-units.index') }}" class="pd-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
