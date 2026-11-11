@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'quotationTemplatesPageSkeleton',
        'columns' => 5,
        'rows' => 10,
        'filterFields' => 1,
    ])

    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-0">Quotation Templates</h4>
                <small class="text-muted">Manage HTML/CSS layouts used when generating quotation PDFs</small>
            </div>
            @if(!empty($canCreate))
            <a href="{{ route('quotation_templates.create') }}" class="btn btn-custom btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> New Template
            </a>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="custom-display">
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Template</th>
                            <th class="text-center">Version</th>
                            <th>Status</th>
                            <th>Default</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            @php
                                $hasAnyAction = !empty($canEdit)
                                    || !empty($canPreview)
                                    || !empty($canDuplicate)
                                    || (!empty($canSetDefault) && !$template->is_default)
                                    || !empty($canDeactivate)
                                    || !empty($canDelete);
                            @endphp
                            <tr>
                                <td>
                                    @if($hasAnyAction)
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            @if(!empty($canEdit))
                                            <li><a class="dropdown-item" href="{{ route('quotation_templates.edit', $template) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                            @endif
                                            @if(!empty($canPreview))
                                            <li><a class="dropdown-item" href="{{ route('quotation_templates.preview', $template) }}" target="_blank"><i class="bi bi-eye me-2"></i>Preview</a></li>
                                            @endif
                                            @if(!empty($canDuplicate))
                                            <li>
                                                <form action="{{ route('quotation_templates.duplicate', $template) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-files me-2"></i>Duplicate</button>
                                                </form>
                                            </li>
                                            @endif
                                            @if(!empty($canSetDefault) && !$template->is_default)
                                            <li>
                                                <form action="{{ route('quotation_templates.set_default', $template) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-star me-2"></i>Set as Default</button>
                                                </form>
                                            </li>
                                            @endif
                                            @if(!empty($canDeactivate))
                                            <li>
                                                <form action="{{ route('quotation_templates.toggle_status', $template) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-toggle-{{ $template->isActive() ? 'on' : 'off' }} me-2"></i>
                                                        {{ $template->isActive() ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </li>
                                            @endif
                                            @if(!empty($canDelete))
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                @if($template->is_default)
                                                    <button type="button" class="dropdown-item text-danger" disabled title="Set another template as default first">
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        class="dropdown-item text-danger js-delete-template"
                                                        data-url="{{ route('quotation_templates.destroy', $template) }}"
                                                        data-name="{{ $template->name }}"
                                                        data-used="{{ (int) $template->quotations_count }}">
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </button>
                                                @endif
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $template->name }}</div>
                                    @if($template->description)
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($template->description, 80) }}</small>
                                    @endif
                                    <div><small class="text-muted">Used by {{ $template->quotations_count }} quotation(s)</small></div>
                                </td>
                                <td class="text-center">{{ $template->version }}</td>
                                <td>
                                    @if($template->isActive())
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($template->is_default)
                                        <span class="badge bg-warning text-dark">Yes</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td>{{ optional($template->updated_at)->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No quotation templates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $templates->links() }}</div>
        </div>
    </div>
</div>

{{-- Hidden delete form submitted after SweetAlert confirm --}}
<form id="qt-delete-form" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteForm = document.getElementById('qt-delete-form');

    document.querySelectorAll('.js-delete-template').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const url = btn.getAttribute('data-url');
            const name = btn.getAttribute('data-name') || 'this template';
            const used = parseInt(btn.getAttribute('data-used') || '0', 10) || 0;

            let text = 'This cannot be undone.';
            if (used > 0) {
                text = 'Used by ' + used + ' quotation(s). Those quotations will keep their saved PDF snapshot, but this template will be removed.';
            }

            const fire = (typeof Swal !== 'undefined' && Swal.fire)
                ? Swal.fire({
                    title: 'Delete template?',
                    html: '<strong>' + name.replace(/</g, '&lt;') + '</strong><br><span class="text-muted">' + text + '</span>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                })
                : Promise.resolve({ isConfirmed: window.confirm('Delete "' + name + '"?\n' + text) });

            fire.then(function (result) {
                if (!result.isConfirmed || !deleteForm || !url) {
                    return;
                }
                deleteForm.setAttribute('action', url);
                deleteForm.submit();
            });
        });
    });
});
</script>
@endpush
