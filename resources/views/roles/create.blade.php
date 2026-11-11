@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Create role</h4>
                    <a href="{{ route('roles.index') }}" class="btn btn-light">&laquo; Back to roles</a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('roles.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" >
                                     @error('name')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="description" name="description" value="{{ old('description') }}">
                                </div>
                            </div>
                            
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div id="permissions-list">
                                    <div class="row g-3 permission-grid">
                                    @foreach($groupedPermissions as $parentName => $group)
                                        <div class="col-12 col-md-3">
                                            <div class="permission-card h-100 p-3 border-1 rounded-3 bg-white">
                                                <div class="fw-bold text-primary mb-2">{{ $parentName }}</div>
                                                <div class="row">
                                                    @foreach($group as $permission)
                                                    <div class="form-check form-switch ms-3 mb-1">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}">
                                                        <label class="form-check-label" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                                        <span class="text-muted small ms-2">{{ $permission->description }}</span>
                                                    </div>
                                                @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    </div>
                                    <!-- @foreach($groupedPermissions as $parentName => $group)
                                        @if(count($group) > 0)
                                            <div class="mb-2">
                                                <div class="fw-bold text-primary mb-1">{{ $parentName }}</div>
                                                @foreach($group as $permission)
                                                    <div class="form-check form-switch ms-3 mb-1">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}">
                                                        <label class="form-check-label" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                                        <span class="text-muted small ms-2">{{ $permission->description }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach -->
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-custom mt-3">Create Role</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
