@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="card mt-3">
        <form method="POST" action="{{ route('permissions.update', $permission->id) }}">
            @csrf
            @method('PUT')
            <div class="card-header">
                <h4 class="mb-0">Edit Permission</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $permission->name) }}" >
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="guard_name" class="form-label">Guard Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="guard_name" name="guard_name" value="{{ old('guard_name', $permission->guard_name) }}"  readonly>
                    @error('guard_name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="parent_id" class="form-label">Parent Permission  <span class="text-danger">*</span></label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="">None</option>
                        @if($permission->parent_id)
                            <option value="{{ $permission->parent_id }}" selected>{{ $permission->parentPermission->name }}</option>
                        @endif
                    </select>
                    @error('parent_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2">{{ old('description', $permission->description) }}</textarea>
                    @error('description')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="crm_permission" class="form-label">CRM Permission</label>
                    <input type="checkbox" id="crm_permission" name="crm_permission" value="1" {{ old('crm_permission', $permission->crm_permission) ? 'checked' : '' }}> Yes
                </div>
            </div>
            <!-- Modal for adding new parent permission (outside form/container for reliability) -->
            <div class="modal fade" id="addParentModal" tabindex="-1" aria-labelledby="addParentModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addParentModalLabel">Add Parent Permission</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="parent_name" class="form-label">Parent Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="parent_name" name="parent_name">
                                <div id="parentNameError" class="text-danger small mt-1 d-none"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveParentBtn">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var dropdownParent = document.body;
                if (window.jQuery && $('#parent_id').length) {
                    $('#parent_id').select2({
                        width: '100%',
                        placeholder: 'Select or search parent permission',
                        allowClear: true,
                        dropdownParent: $(dropdownParent),
                        ajax: {
                            url: '{{ route('parent-permissions.autocomplete') }}',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return { q: params.term };
                            },
                            processResults: function(data) {
                                var results = data.results || [];
                                return { results: results };
                            },
                            cache: true
                        },
                        templateResult: function(data) {
                            return data.text;
                        }
                    });
                    $('#parent_id').on('select2:open', function() {
                        // Remove any previous footer
                        $('.select2-footer-parent').remove();
                        // Add footer after results
                        setTimeout(function() {
                            var $results = $('.select2-results');
                            if ($results.length) {
                                var $footer = $('<div class="select2-footer-parent px-3 py-2 text-primary" style="cursor:pointer;border-top:1px solid #eee;">+ New Parent Permission</div>');
                                $footer.on('mousedown', function(e) {
                                    e.preventDefault();
                                    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addParentModal'));
                                    modal.show();
                                    $('#parent_id').select2('close');
                                });
                                $results.append($footer);
                            }
                        }, 0);
                    });
                }
                const nameInput = document.getElementById('name');
                const guardInput = document.getElementById('guard_name');
                nameInput.addEventListener('input', function() {
                    let val = nameInput.value.trim().toLowerCase().replace(/\s+/g, '_');
                    guardInput.value = val ? val + '_guard' : '';
                });
                // AJAX for adding new parent permission
                document.getElementById('saveParentBtn').addEventListener('click', function() {
                    const parentName = document.getElementById('parent_name').value.trim();
                    const errorDiv = document.getElementById('parentNameError');
                    errorDiv.classList.add('d-none');
                    if (!parentName) {
                        errorDiv.textContent = 'Parent name is required.';
                        errorDiv.classList.remove('d-none');
                        return;
                    }
                    fetch("{{ route('parent-permissions.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ name: parentName })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.parent) {
                            // Add new option to dropdown
                            var newOption = new Option(data.parent.name, data.parent.id, true, true);
                            $('#parent_id').append(newOption).trigger('change');
                            // Close modal
                            document.getElementById('parent_name').value = '';
                            var modal = bootstrap.Modal.getInstance(document.getElementById('addParentModal'));
                            modal.hide();
                        } else {
                            errorDiv.textContent = data.message || 'Error adding parent permission.';
                            errorDiv.classList.remove('d-none');
                        }
                    })
                    .catch(() => {
                        errorDiv.textContent = 'Error adding parent permission.';
                        errorDiv.classList.remove('d-none');
                    });
                });
            });
            </script>
            <div class="card-footer text-end">
                <a href="{{ route('permissions.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Permission</button>
            </div>
        </form>
    </div>
</div>
@endsection
