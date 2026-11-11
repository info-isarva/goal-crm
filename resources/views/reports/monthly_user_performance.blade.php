@extends('layouts.app')

@section('content')
@php
    $fyMonths = [];
    if (!empty($start) && !empty($end)) {
        try {
            $p = \Carbon\Carbon::parse($start)->startOfMonth();
            $endP = \Carbon\Carbon::parse($end)->startOfMonth();
            while ($p->lte($endP)) {
                $fyMonths[] = $p->format('Y-n');
                $p->addMonth();
            }
        } catch (Exception $e) {
            $fyMonths = [];
        }
    }
@endphp

<x-report-shell
    title="Monthly User Performance Report"
    :show-date-range="false"
    :export-url="null"
    :show-run-button="true"
>
    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="year">Year</label>
            <select name="year" id="year" class="form-select">
                @for($y = date('Y')-2; $y <= date('Y')+1; $y++)
                    @php
                        $yearHasFyMonths = false;
                        if (!empty($fyMonths)) {
                            foreach ($fyMonths as $fm) {
                                list($fyY, $fyM) = explode('-', $fm);
                                if ((int)$fyY === (int)$y) { $yearHasFyMonths = true; break; }
                            }
                        }
                    @endphp
                    <option value="{{ $y }}" {{ ($year ?? date('Y')) == $y ? 'selected' : '' }} @if(!empty($fyMonths) && !$yearHasFyMonths) disabled class="text-muted" @endif>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="toolbar-field">
            <label for="month">Month</label>
            <select name="month" id="month" class="form-select">
                @for($m = 1; $m <= 12; $m++)
                    @php
                        $isInFy = true;
                        if (!empty($fyMonths)) {
                            if (!empty($year)) {
                                $isInFy = in_array($year . '-' . $m, $fyMonths);
                            } else {
                                $found = false;
                                foreach ($fyMonths as $fm) {
                                    list($fyY, $fyM) = explode('-', $fm);
                                    if ((int)$fyM === (int)$m) { $found = true; break; }
                                }
                                $isInFy = $found;
                            }
                        }
                    @endphp
                    <option value="{{ $m }}" {{ ($month ?? date('n')) == $m ? 'selected' : '' }} @if(!empty($fyMonths) && !$isInFy) disabled class="text-muted" @endif>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
        </div>
        <div class="toolbar-field">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-select">
                <option value="">All</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ ($user_id ?? '') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Sales Target</th>
                    <th>Achieved Sales</th>
                    <th>Remaining Target</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $row)
                    <tr>
                        <td data-label="User">{{ $row['user_name'] }}</td>
                        <td data-label="Year">{{ $row['year'] }}</td>
                        <td data-label="Month">{{ \Carbon\Carbon::create()->month($row['month'])->format('F') }}</td>
                        <td data-label="Sales Target">{{ $currency_symbol }} {{ number_format($row['sales_target'], 2) }}</td>
                        <td data-label="Achieved Sales">{{ $currency_symbol }} {{ number_format($row['achieved_sales'], 2) }}</td>
                        <td data-label="Remaining Target">{{ $currency_symbol }} {{ number_format($row['remaining_target'], 2) }}</td>
                        <td data-label="Progress">
                            <div style="min-width:120px;">
                                @php
                                    $progress = $row['progress'];
                                    $barColor = 'bg-danger';
                                    if ($progress > 85) {
                                        $barColor = 'bg-success';
                                    } elseif ($progress > 25) {
                                        $barColor = 'bg-primary';
                                    }
                                @endphp
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                        {{ $progress }}%
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="report-empty">Data Not Available this Financial  Year</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('reports.partials.pagination', ['paginator' => null, 'noun' => 'results'])
</x-report-shell>
@endsection
