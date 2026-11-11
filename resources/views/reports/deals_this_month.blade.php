@extends('layouts.app')

@section('content')
<x-report-shell
    title="Deals Closing this Month"
    :show-date-range="false"
    :export-url="null"
    :show-run-button="true"
    :form-action="route('reports.deals_this_month')"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="">All Users</option>
                @foreach($usersList as $owner)
                    <option value="{{ $owner->id }}" {{ (string) ($filterUserId ?? '') === (string) $owner->id ? 'selected' : '' }}>
                        {{ $owner->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-field">
            <label for="stage">Stage</label>
            <select name="stage" id="stage" class="form-select">
                <option value="">All Stages</option>
                @foreach($stages as $stageName)
                    <option value="{{ $stageName }}" {{ ($stageFilter ?? '') === $stageName ? 'selected' : '' }}>
                        {{ $stageName }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Deal Name</th>
                    <th>Account Name</th>
                    <th>Deal Owner</th>
                    <th>Stage</th>
                    <th>Probability (%)</th>
                    <th>Closing Date</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($historicalSelected) && $historicalSelected)
                    <tr><td colspan="6" class="report-empty">Unable to show deals for a historical financial year selection.</td></tr>
                @else
                    @forelse($deals as $deal)
                    <tr>
                        <td data-label="Deal Name"><a href="{{ route('deals.show', $deal->id) }}" class="text-primary text-decoration-none">{{ $deal->title }}</a></td>
                        <td data-label="Account Name">{{ $deal->organization->name ?? '-' }}</td>
                        <td data-label="Deal Owner">{{ $deal->owner->name ?? '-' }}</td>
                        <td data-label="Stage">
                            @php
                                $stage = $deal->stage_name ?? '-';
                                $stageColors = [
                                    'Open' => 'bg-primary',
                                    'Closed Won' => 'bg-success-subtle text-success',
                                    'Closed Lost' => 'bg-danger-subtle text-danger',
                                ];
                                $color = $stageColors[$stage] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $color }}">{{ $stage }}</span>
                        </td>
                        <td data-label="Probability">{{ $deal->probability ?? '-' }}</td>
                        <td data-label="Closing Date">{{ $deal->close_date ? \Carbon\Carbon::parse($deal->close_date)->format('d/m/Y') : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="report-empty">No deals found for the selected filters.</td></tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>

    @include('reports.partials.pagination', ['paginator' => $deals ?? null, 'noun' => 'deals'])
</x-report-shell>
@endsection
