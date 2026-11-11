<div class="hub-page-skeleton pipeline-skeleton" id="dealsPipelineSkeleton" role="status" aria-label="Loading deals pipeline" aria-hidden="false">
    <div class="pipeline-skeleton-inner">
        <div class="pipeline-skeleton-header">
            <div class="hub-skeleton-block hub-skeleton-line title"></div>
            <div class="pipeline-skeleton-actions">
                <div class="hub-skeleton-block hub-skeleton-pill"></div>
                <div class="hub-skeleton-block hub-skeleton-pill"></div>
            </div>
        </div>

        <div class="pipeline-skeleton-filters">
            @for ($i = 0; $i < 5; $i++)
            <div class="hub-skeleton-block pipeline-skeleton-filter"></div>
            @endfor
        </div>

        <div class="pipeline-skeleton-board">
            @for ($col = 0; $col < 6; $col++)
            <div class="pipeline-skeleton-column">
                <div class="pipeline-skeleton-column-head">
                    <div class="hub-skeleton-block hub-skeleton-line medium"></div>
                    <div class="hub-skeleton-block pipeline-skeleton-badge"></div>
                </div>
                <div class="pipeline-skeleton-column-body">
                    @for ($card = 0; $card < 5; $card++)
                    <div class="pipeline-skeleton-card">
                        <div class="hub-skeleton-block hub-skeleton-line medium mb-2"></div>
                        <div class="hub-skeleton-block hub-skeleton-line short mb-2"></div>
                        <div class="hub-skeleton-block hub-skeleton-line short mb-2"></div>
                        <div class="hub-skeleton-block hub-skeleton-line short"></div>
                    </div>
                    @endfor
                    <div class="pipeline-skeleton-column-fill"></div>
                </div>
            </div>
            @endfor
        </div>
    </div>
</div>
