@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
@endphp

<x-report-shell
    title="Sales by Source"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
>
    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Lead Source</th>
                    <th>Account Name</th>
                    <th>Deal Name</th>
                    <th>Closing Date</th>
                    <th>Stage</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotal = 0; @endphp
                @forelse($grouped as $source => $deals)
                    @php $count = $deals->count(); $i = 0; $groupSum = 0; @endphp
                    @foreach($deals as $deal)
                        @php $groupSum += is_numeric($deal->amount) ? (float)$deal->amount : 0; $grandTotal += is_numeric($deal->amount) ? (float)$deal->amount : 0; @endphp
                        <tr>
                            @if($i == 0)
                                <td rowspan="{{ $count }}" data-label="Lead Source"><strong>{{ $source }}</strong> <small>({{ $count }})</small></td>
                            @endif
                            <td data-label="Account Name">{{ $deal->organization ? $deal->organization->name : '-' }}</td>
                            <td data-label="Deal Name"><a href="{{ url('/deals/'.$deal->id) }}" target="_blank">{{ $deal->title ?? '-' }}</a></td>
                            <td data-label="Closing Date">{{ $deal->close_date ? \Carbon\Carbon::parse($deal->close_date)->format('d/m/Y') : '-' }}</td>
                            <td data-label="Stage">{{ $deal->stage_name ?? '-' }}</td>
                            <td data-label="Amount" class="text-end">@if(is_numeric($deal->amount)) @money($deal->amount) @else - @endif</td>
                        </tr>
                        @php $i++; @endphp
                    @endforeach
                    <tr>
                        <td colspan="5" class="text-end" data-label="Subtotal"><strong style="color:#6b21a8;">Sum @money($groupSum)</strong></td>
                        <td class="text-end" data-label="Amount"><strong style="color:#6b21a8;">Sum @money($groupSum)</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="report-empty">No deals found for selected filters.</td></tr>
                @endforelse
                @if($grouped->isNotEmpty())
                <tr>
                    <td colspan="5" data-label="Grand Total"><strong>Grand Total</strong></td>
                    <td class="text-end" data-label="Amount"><strong style="color:#6b21a8;">Sum @money($grandTotal)</strong></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</x-report-shell>
@endsection
