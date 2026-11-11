@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
@endphp

<x-report-shell
    title="Open Deals Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :export-url="null"
    :show-run-button="true"
    :form-action="route('reports.open_deals')"
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
                <option value="">All Open Stages</option>
                @foreach($stages as $stageName)
                    <option value="{{ $stageName }}" {{ ($stageFilter ?? '') === $stageName ? 'selected' : '' }}>
                        {{ $stageName }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    @if($deals->isEmpty())
        <div class="report-content-pad">
            <div class="alert alert-info mb-0">No deals found for the selected filters.</div>
        </div>
    @else
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Deal Owner</th>
                        <th>Closing Date</th>
                        <th>Stage</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deals as $deal)
                    <tr>
                        <td data-label="#">{{ ($deals->firstItem() ?? 0) + $loop->index }}</td>
                        <td data-label="Title"><a class="text-decoration-none" href="{{ route('deals.show', $deal->id) }}">{{ $deal->title }}</a></td>
                        <td data-label="Company Name">{{ $deal->organization->name ?? '-' }}</td>
                        <td data-label="Contact Person">{{ $deal->person ? ($deal->person->first_name . ' ' . $deal->person->last_name) : '-' }}</td>
                        <td data-label="Deal Owner">{{ $deal->owner->name ?? '-' }}</td>
                        <td data-label="Closing Date">{{ $deal->close_date ? \Carbon\Carbon::parse($deal->close_date)->format('d-m-Y') : '-' }}</td>
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
                        <td data-label="Amount">{{ $deal->amount ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @include('reports.partials.pagination', ['paginator' => $deals ?? null, 'noun' => 'deals'])
</x-report-shell>
@endsection
