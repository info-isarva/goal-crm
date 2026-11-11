@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/leads.css') }}">
<div class="container-fluid p-4 hub-page-wrap hub-page-wrap--crm">
    @include('partials.skeletons.crm_list', [
        'skeletonId' => 'quotationEmailTemplatesPageSkeleton',
        'columns' => 5,
        'rows' => 10,
        'filterFields' => 1,
    ])

    <div class="card mt-0">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-0">Quotation Email Templates</h4>
                <small class="text-muted">Design email subject and body — Standard, Professional, Isarva Professional, Minimal, or Corporate formats</small>
            </div>
            <a href="{{ route('quotation_email_templates.create') }}" class="btn btn-custom btn-sm">
                <i class="bi bi-plus-lg"></i> New Template
            </a>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="table-responsive" style="overflow:visible;">
                <table class="table table-bordered table-hover align-middle mb-0" style="overflow:visible;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Template</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Default</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td style="overflow:visible; position:relative;">
                                    <div class="dropdown">
                                        <button class="btn btn-link p-0 text-dark" type="button"
                                            id="emailTemplateActions{{ $template->id }}"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start shadow rounded-3 py-2"
                                            aria-labelledby="emailTemplateActions{{ $template->id }}">
                                            <li>
                                                <a class="dropdown-item px-4 py-2" href="{{ route('quotation_email_templates.edit', $template) }}">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                            </li>
                                            @if(!$template->is_default)
                                                <li>
                                                    <form action="{{ route('quotation_email_templates.set_default', $template) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item px-4 py-2">
                                                            <i class="bi bi-star me-2"></i>Set Default
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                            <li>
                                                <form action="{{ route('quotation_email_templates.toggle_status', $template) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item px-4 py-2">
                                                        <i class="bi bi-toggle-{{ $template->is_active ? 'on' : 'off' }} me-2"></i>
                                                        {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </li>
                                            @if(!$template->is_default)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('quotation_email_templates.destroy', $template) }}" method="POST"
                                                        onsubmit="return confirm('Delete this email template?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item px-4 py-2 text-danger">
                                                            <i class="bi bi-trash me-2"></i>Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $template->name }}</div>
                                    @if($template->description)
                                        <div class="small text-muted">{{ $template->description }}</div>
                                    @endif
                                </td>
                                <td class="small">{{ $template->subject }}</td>
                                <td>
                                    @if($template->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($template->is_default)
                                        <span class="badge bg-primary">Default</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $template->updated_at?->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No email templates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $templates->links() }}</div>
        </div>
    </div>
</div>
@endsection
