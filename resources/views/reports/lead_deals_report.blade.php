@extends('layouts.app')

@section('content')
@php
    $period = $period ?? 'this_month';
    $type = $type ?? 'lead';
    $countLabel = $type === 'deal' ? 'Total Deals' : 'Total Leads';
    $selectedColumns = $selectedColumns ?? ['name', 'company', 'lead_source', 'product_services', 'amount'];
    $availableColumns = $availableColumns ?? [];
    $totalCount = $totalCount ?? ($records->count() ?? 0);
    $totalAmount = $totalAmount ?? 0;
    $colCount = max(count($selectedColumns), 1);
    $stageId = $stageId ?? null;
    $stages = $stages ?? collect();
    $columnOptions = $availableColumns;
    if (!isset($columnOptions['stage'])) {
        $columnOptions['stage'] = 'Stage';
    }
    $exportUrl = route('reports.lead_deals_excel', array_merge(
        request()->only('period', 'start_date', 'end_date', 'type', 'category', 'lead_source', 'owner', 'stage'),
        ['columns' => $selectedColumns]
    ));
@endphp

<style>
.toolbar-field-extra select[multiple] {
    min-height: 38px;
    min-width: 220px;
}
</style>

<x-report-shell
    title="Lead Deals Report"
    :show-date-range="true"
    :period="$period"
    :start="$start ?? null"
    :end="$end ?? null"
    :form-action="route('reports.lead_deals')"
    :export-url="$exportUrl"
>
    <x-slot name="metaAsideLeft">
        <div class="report-meta-stat">
            <span class="label">{{ $countLabel }}</span>
            <span class="value">{{ number_format($totalCount) }}</span>
        </div>
    </x-slot>

    <x-slot name="metaAsideRight">
        <div class="report-meta-stat amount">
            <span class="label">Total Amount</span>
            <span class="value">{{ \App\Helpers\MoneyFormatter::formatIndian($totalAmount) }}</span>
        </div>
    </x-slot>

    <x-slot name="toolbar">
        <div class="toolbar-field">
            <label for="type">Type</label>
            <select name="type" id="type" class="form-select">
                <option value="lead" {{ $type === 'lead' ? 'selected' : '' }}>Leads</option>
                <option value="deal" {{ $type === 'deal' ? 'selected' : '' }}>Deals</option>
            </select>
        </div>
        <div class="toolbar-field" id="stageField" style="{{ $type === 'deal' ? '' : 'display:none;' }}">
            <label for="stage">Stage</label>
            <select name="stage" id="stage" class="form-select" {{ $type === 'deal' ? '' : 'disabled' }}>
                <option value="">All Stages</option>
                @foreach($stages as $stage)
                    <option value="{{ $stage->id }}" {{ (string) ($stageId ?? '') === (string) $stage->id ? 'selected' : '' }}>
                        {{ $stage->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-field">
            <label for="category">Product/Services</label>
            <select name="category" id="category" class="form-select">
                <option value="">All Product/Services</option>
                @foreach($categories ?? [] as $category)
                    <option value="{{ $category->id }}" {{ (string) ($categoryId ?? '') === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->category_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-field">
            <label for="lead_source">Lead Source</label>
            <select name="lead_source" id="lead_source" class="form-select">
                <option value="">All</option>
                @foreach($leadSources ?? [] as $source)
                    <option value="{{ $source->id }}" {{ (string) ($leadSourceId ?? '') === (string) $source->id ? 'selected' : '' }}>
                        {{ $source->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-field">
            <label for="owner">Owner</label>
            <select name="owner" id="owner" class="form-select">
                <option value="">All</option>
                @foreach($owners ?? [] as $owner)
                    <option value="{{ $owner->id }}" {{ (string) ($ownerId ?? '') === (string) $owner->id ? 'selected' : '' }}>
                        {{ $owner->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-field toolbar-field-extra">
            <label for="columns">Columns</label>
            <select name="columns[]" id="columns" class="form-select" multiple size="1">
                @foreach($columnOptions as $fieldKey => $fieldLabel)
                    @php $dealOnly = $fieldKey === 'stage'; @endphp
                    <option value="{{ $fieldKey }}"
                        {{ in_array($fieldKey, $selectedColumns, true) ? 'selected' : '' }}
                        @if($dealOnly) data-deal-only="1" @endif
                        @if($dealOnly && $type !== 'deal') hidden disabled @endif>
                        {{ $fieldLabel }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    @foreach($selectedColumns as $field)
                        <th>{{ $availableColumns[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($records as $row)
                    <tr>
                        @foreach($selectedColumns as $field)
                            @php $label = $availableColumns[$field] ?? $field; @endphp
                            @if($field === 'name')
                                <td data-label="{{ $label }}">
                                    <a href="{{ $row->show_url }}" target="_blank">{{ $row->name ?: '-' }}</a>
                                    @if(!empty($row->title) && $row->title !== $row->name)
                                        <div class="text-muted small">{{ $row->title }}</div>
                                    @endif
                                </td>
                            @elseif($field === 'product_services')
                                <td data-label="{{ $label }}">
                                    @if(!empty($row->category_names))
                                        {{ implode(', ', $row->category_names) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            @elseif($field === 'amount')
                                <td data-label="{{ $label }}" class="text-end">
                                    @if(is_numeric($row->amount))
                                        {{ \App\Helpers\MoneyFormatter::formatIndian($row->amount) }}
                                    @else
                                        {{ $row->amount ?: '-' }}
                                    @endif
                                </td>
                            @else
                                <td data-label="{{ $label }}">{{ $row->{$field} ?? '-' }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colCount }}" class="report-empty">
                            No {{ $type === 'deal' ? 'deals' : 'leads' }} found for selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-report-shell>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var typeSelect = document.getElementById('type');
    var stageField = document.getElementById('stageField');
    var stageSelect = document.getElementById('stage');
    var columns = document.getElementById('columns');

    function syncDealOnlyFields() {
        var isDeal = typeSelect && typeSelect.value === 'deal';
        if (stageField && stageSelect) {
            stageField.style.display = isDeal ? '' : 'none';
            stageSelect.disabled = !isDeal;
            if (!isDeal) stageSelect.value = '';
        }
        if (columns) {
            columns.querySelectorAll('option[data-deal-only]').forEach(function (opt) {
                opt.hidden = !isDeal;
                opt.disabled = !isDeal;
                if (!isDeal) opt.selected = false;
            });
        }
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', syncDealOnlyFields);
        syncDealOnlyFields();
    }

    if (!columns) return;
    columns.addEventListener('focus', function () {
        columns.size = Math.min(columns.options.length, 8);
    });
    columns.addEventListener('blur', function () {
        columns.size = 1;
    });
});
</script>
@endsection
