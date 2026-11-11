@php
    $skeletonId = $skeletonId ?? 'crmPageSkeleton';
    $columns = (int) ($columns ?? 9);
    $rows = (int) ($rows ?? 10);
    $filterFields = (int) ($filterFields ?? 6);
    $gridCols = '32px ' . str_repeat('minmax(72px, 1fr) ', max(1, $columns - 1));
@endphp
<div class="hub-page-skeleton crm-list-skeleton" id="{{ $skeletonId }}" role="status" aria-label="Loading list" aria-hidden="false">
    <div class="hub-skeleton-shell crm-list-skeleton-card">
        <div class="crm-skeleton-header">
            <div class="hub-skeleton-block hub-skeleton-line title"></div>
            <div class="crm-skeleton-actions">
                <div class="hub-skeleton-block hub-skeleton-pill"></div>
                <div class="hub-skeleton-block hub-skeleton-pill"></div>
                <div class="hub-skeleton-block hub-skeleton-pill"></div>
            </div>
        </div>
        <div class="crm-skeleton-body">
            <div class="crm-skeleton-filters">
                @for ($i = 0; $i < $filterFields; $i++)
                <div class="crm-skeleton-filter-field">
                    <div class="hub-skeleton-block hub-skeleton-line short mb-2"></div>
                    <div class="hub-skeleton-block crm-skeleton-input"></div>
                </div>
                @endfor
            </div>

            <div class="crm-skeleton-table">
                <div class="crm-skeleton-table-head" style="grid-template-columns: {{ $gridCols }};">
                    @for ($c = 0; $c < $columns; $c++)
                    <div class="hub-skeleton-block hub-skeleton-line {{ $c === 0 ? 'short' : 'medium' }}"></div>
                    @endfor
                </div>
                @for ($r = 0; $r < $rows; $r++)
                <div class="crm-skeleton-table-row" style="grid-template-columns: {{ $gridCols }};">
                    @for ($c = 0; $c < $columns; $c++)
                    <div class="hub-skeleton-block hub-skeleton-line {{ $c % 3 === 0 ? 'short' : 'medium' }}"></div>
                    @endfor
                </div>
                @endfor
            </div>

            <div class="crm-skeleton-footer">
                <div class="hub-skeleton-block hub-skeleton-line subtitle"></div>
                <div class="crm-skeleton-pagination">
                    <div class="hub-skeleton-block hub-skeleton-pill"></div>
                    <div class="hub-skeleton-block hub-skeleton-pill"></div>
                </div>
            </div>
        </div>
    </div>
</div>
