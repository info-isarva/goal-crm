@extends('layouts.app')
@section('content')
<div class="container-fluid p-4">
    <div class="card"><div class="card-header"><h5 class="mb-0">Edit Org Unit</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('goal.org-units.update', $unit) }}">@csrf @method('PUT')
            @include('goal.org_units._form', ['unit' => $unit])
            <button class="btn btn-custom">Update</button>
            <a href="{{ route('goal.org-units.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div></div>
</div>
@endsection
