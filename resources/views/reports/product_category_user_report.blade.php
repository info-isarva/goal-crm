@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
@endphp

<x-report-shell
    title="Product Category Wise User Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="category">Category</label>
            <select id="category" name="category" class="form-select">
                <option value="">All</option>
                @foreach(App\Models\ProductCategory::all() as $category)
                    <option value="{{ $category->id }}" {{ (string) ($categoryId ?? '') === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->category_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select id="user_id" name="user_id" class="form-select">
                <option value="">All Users</option>
                @foreach($filterUsers ?? [] as $user)
                    <option value="{{ $user->id }}" {{ (string) ($userId ?? '') === (string) $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Product Category</th>
                    <th>User</th>
                    <th>Lead Count</th>
                    <th>Deal Count</th>
                    <th>Deal Won</th>
                    <th>Deal Lost</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $category => $users)
                    @php $rowspan = count($users); @endphp
                    @foreach ($users as $index => $user)
                        <tr>
                            @if ($index === 0)
                                <td class="d-md-table-cell align-middle" rowspan="{{ $rowspan }}" data-label="Product Category"><span class="fw-bold">{{ $category }}</span></td>
                            @endif
                            <td data-label="User">{{ $user['user'] }}</td>
                            <td data-label="Lead Count">
                                @if($user['lead_count'] == 0)
                                    {{ $user['lead_count'] }}
                                @else
                                    <a href="#" class="count-link" data-url="/leads/list/{{ $user['user_id'] }}?category={{ $user['category_id'] }}">{{ $user['lead_count'] }}</a>
                                @endif
                            </td>
                            <td data-label="Deal Count">
                                @if ($user['deal_count'] == 0)
                                    {{ $user['deal_count'] }}
                                @else
                                    <a href="#" class="count-link" data-url="/deals/list/{{ $user['user_id'] }}?category={{ $user['category_id'] }}">{{ $user['deal_count'] }}</a>
                                @endif
                            </td>
                            <td data-label="Deal Won">
                                @if ($user['deal_won'] == 0)
                                    {{ $user['deal_won'] }}
                                @else
                                    <a href="#" class="count-link" data-url="/deals/list/{{ $user['user_id'] }}?category={{ $user['category_id'] }}&stage=Closed Won">{{ $user['deal_won'] }}</a>
                                @endif
                            </td>
                            <td data-label="Deal Lost">
                                @if ($user['deal_lost'] == 0)
                                    {{ $user['deal_lost'] }}
                                @else
                                    <a href="#" class="count-link" data-url="/deals/list/{{ $user['user_id'] }}?category={{ $user['category_id'] }}&stage=Closed Lost">{{ $user['deal_lost'] }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</x-report-shell>

<div class="modal fade" id="dataModal" tabindex="-1" aria-labelledby="dataModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 1500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dataModalLabel">Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.count-link').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            const url = this.getAttribute('data-url');
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load data');
                    return response.text();
                })
                .then(html => {
                    const modal = document.getElementById('dataModal');
                    modal.querySelector('.modal-body').innerHTML = html;
                    new bootstrap.Modal(modal).show();
                })
                .catch(error => {
                    const modal = document.getElementById('dataModal');
                    modal.querySelector('.modal-body').innerHTML = '<p class="text-danger">' + error.message + '</p>';
                    new bootstrap.Modal(modal).show();
                });
        });
    });
});
</script>
@endpush
