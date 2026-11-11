@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
@endphp

<x-report-shell
    title="Lost Deals Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :export-url="null"
    :show-run-button="true"
    :form-action="route('reports.lost_deals')"
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
            <label for="lead_source">Lead Source</label>
            <select name="lead_source" id="lead_source" class="form-select">
                <option value="">All Lead Sources</option>
                @foreach($leadSources as $source)
                    <option value="{{ $source->id }}" {{ (string) ($leadSourceId ?? '') === (string) $source->id ? 'selected' : '' }}>
                        {{ $source->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    @if($deals->isEmpty())
        <div class="report-content-pad">
            <div class="alert alert-info mb-0">No lost deals found for the selected filters.</div>
        </div>
    @else
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Company Name</th>
                        <th>Title</th>
                        <th>Deal Owner</th>
                        <th>Lead Source</th>
                        <th>Stage</th>
                        <th>Created Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deals as $deal)
                    <tr>
                        <td data-label="#">{{ ($deals->firstItem() ?? 0) + $loop->index }}</td>
                        <td data-label="Company Name">
                            @if($deal->organization)
                                <a href="{{ route('organizations.show', $deal->organization->id) }}">{{ $deal->organization->name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td data-label="Title"><a class="text-decoration-none" href="{{ route('deals.show', $deal->id) }}">{{ $deal->title }}</a></td>
                        <td data-label="Deal Owner">{{ $deal->owner->name ?? '-' }}</td>
                        <td data-label="Lead Source">{{ $deal->dealSource->name ?? '-' }}</td>
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
                        <td data-label="Created Time">{{ $deal->created_at ? \Carbon\Carbon::parse($deal->created_at)->format('d-m-Y H:i') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @include('reports.partials.pagination', ['paginator' => $deals ?? null, 'noun' => 'deals'])
</x-report-shell>
@endsection
