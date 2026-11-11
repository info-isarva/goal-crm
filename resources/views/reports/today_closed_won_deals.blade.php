@extends('layouts.app')

@section('content')
@php
    $exportUrl = route('reports.today_closed_won_deals_excel', request()->except('page'));
@endphp

<x-report-shell
    title="Today's Sales"
    :show-date-range="false"
    :export-url="$exportUrl"
    :show-run-button="false"
>
    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Deal Name</th>
                    <th>Account Name</th>
                    <th>Deal Owner</th>
                    <th>Lead Source</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($historicalSelected) && $historicalSelected)
                    <tr><td colspan="5" class="report-empty">Today's closed won sales are not available for a historical financial year selection.</td></tr>
                @else
                    @forelse($deals as $deal)
                    <tr>
                        <td data-label="Deal Name"><a href="{{ url('/deals/'.$deal->id) }}">{{ $deal->title ?? '-' }}</a></td>
                        <td data-label="Account Name">{{ $deal->organization ? $deal->organization->name : '-' }}</td>
                        <td data-label="Deal Owner">{{ $deal->owner ? $deal->owner->name : '-' }}</td>
                        <td data-label="Lead Source">{{ $deal->dealSource ? $deal->dealSource->name : '-' }}</td>
                        <td data-label="Amount" class="text-end">{{ is_numeric($deal->amount) ? \App\Helpers\MoneyFormatter::format($deal->amount) : ($deal->amount ?: '-') }}</td>
                    </tr>
                    @empty
                        <tr><td colspan="5" class="report-empty">No closed won sales for today.</td></tr>
                    @endforelse
                @endif
                <tr>
                    <td colspan="4"><strong>Grand Total</strong></td>
                    <td class="text-end"><a href="#" class="text-primary">Sum {{ is_numeric($total) ? \App\Helpers\MoneyFormatter::format($total) : \App\Helpers\MoneyFormatter::format(0) }}</a></td>
                </tr>
            </tbody>
        </table>
    </div>

    @include('reports.partials.pagination', ['paginator' => $deals ?? null, 'noun' => 'deals'])
</x-report-shell>
@endsection
