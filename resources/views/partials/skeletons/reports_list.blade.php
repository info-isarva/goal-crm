<div class="hub-page-skeleton reports-hub-skeleton" id="reportsPageSkeleton" role="status" aria-label="Loading reports" aria-hidden="false">
    <div class="reports-skeleton-inner">
        <div class="hub-skeleton-toolbar">
            <div class="hub-skeleton-block hub-skeleton-pill"></div>
            <div class="hub-skeleton-block hub-skeleton-search"></div>
        </div>
        <div class="hub-skeleton-table">
            <div class="hub-skeleton-table-head">
                <div class="hub-skeleton-block hub-skeleton-star"></div>
                <div class="hub-skeleton-block hub-skeleton-line medium"></div>
                <div class="hub-skeleton-block hub-skeleton-line medium"></div>
                <div class="hub-skeleton-block hub-skeleton-line short"></div>
                <div class="hub-skeleton-block hub-skeleton-line short"></div>
                <div class="hub-skeleton-block hub-skeleton-line short"></div>
            </div>
            @for ($i = 0; $i < 14; $i++)
            <div class="hub-skeleton-table-row">
                <div class="hub-skeleton-block hub-skeleton-star"></div>
                <div class="hub-skeleton-block hub-skeleton-line medium"></div>
                <div class="hub-skeleton-block hub-skeleton-line short"></div>
                <div class="hub-skeleton-block hub-skeleton-line short"></div>
                <div class="hub-skeleton-block hub-skeleton-line short"></div>
                <div class="hub-skeleton-block hub-skeleton-line short"></div>
            </div>
            @endfor
            <div class="reports-skeleton-table-fill"></div>
        </div>
    </div>
</div>
