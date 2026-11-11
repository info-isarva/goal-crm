@extends('layouts.app')

@section('content')
@php
    $canCreateWf = auth()->user()->hasCrmPermission('create_crm_web_forms_guard') || auth()->user()->hasCrmPermission('manage_crm_web_forms_guard');
    $canEditWf = auth()->user()->hasCrmPermission('edit_crm_web_forms_guard') || auth()->user()->hasCrmPermission('manage_crm_web_forms_guard');
    $canDeleteWf = auth()->user()->hasCrmPermission('delete_crm_web_forms_guard') || auth()->user()->hasCrmPermission('manage_crm_web_forms_guard');
    $canViewWf = auth()->user()->hasCrmPermission('view_crm_web_forms_guard') || auth()->user()->hasCrmPermission('manage_crm_web_forms_guard');
@endphp
<div class="container-fluid p-4">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h4 class="mb-1 fw-bold">Web Forms</h4>
                    <small class="text-muted">Build lead capture forms, paste the generated script into your website or landing page, and every submission becomes a lead.</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('settings.index') }}" class="btn btn-light btn-sm">&laquo; Back to Settings</a>
                    @if($canCreateWf)
                        <a href="{{ route('web_forms.create') }}" class="btn btn-custom btn-sm"><i class="bi bi-plus-lg me-1"></i>New Web Form</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if($forms->isEmpty())
                <div class="text-center py-5 px-3">
                    <div class="mb-3" style="font-size: 2.5rem; color: #5b3cc4;"><i class="bi bi-ui-checks-grid"></i></div>
                    <h6 class="fw-bold mb-1">No web forms yet</h6>
                    <p class="text-muted small mb-3">Create a form, choose the fields, and copy the embed code to your website.</p>
                    @if($canCreateWf)
                        <a href="{{ route('web_forms.create') }}" class="btn btn-custom btn-sm"><i class="bi bi-plus-lg me-1"></i>Create your first web form</a>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Form Name</th>
                                <th>Lead Source</th>
                                <th>Lead Owner</th>
                                <th class="text-center">Fields</th>
                                <th class="text-center">Submissions</th>
                                <th>Status</th>
                                <th class="text-nowrap">Created</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($forms as $form)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold">{{ $form->name }}</div>
                                        @if($form->heading)
                                            <small class="text-muted">{{ $form->heading }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $form->leadSource->name ?? '—' }}</td>
                                    <td>{{ $form->owner->name ?? '—' }}</td>
                                    <td class="text-center">{{ count($form->fields ?? []) }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('web_forms.submissions', $form) }}" class="text-decoration-none fw-semibold">{{ number_format($form->submissions_count) }}</a>
                                    </td>
                                    <td>
                                        @if($form->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ optional($form->created_at)->format('d M Y') }}</td>
                                    <td class="text-end pe-4 text-nowrap">
                                        @if($canViewWf)
                                            <a href="{{ route('web_forms.embed', $form) }}" class="btn btn-sm btn-outline-primary" title="Get embed code"><i class="bi bi-code-slash me-1"></i>Embed Code</a>
                                        @endif
                                        @if($canEditWf)
                                            <a href="{{ route('web_forms.edit', $form) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                            <form action="{{ route('web_forms.toggle', $form) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $form->is_active ? 'warning' : 'success' }}" title="{{ $form->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i class="bi {{ $form->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($canDeleteWf)
                                            <form action="{{ route('web_forms.destroy', $form) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this web form? Websites using its code will stop showing it.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
