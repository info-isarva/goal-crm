<div class="hub-page-skeleton settings-hub-skeleton" id="settingsPageSkeleton" role="status" aria-label="Loading settings" aria-hidden="false">
    <div class="hub-skeleton-shell settings-skeleton-shell">
        <div class="hub-skeleton-topbar">
            <div>
                <div class="hub-skeleton-block hub-skeleton-line title mb-2"></div>
                <div class="hub-skeleton-block hub-skeleton-line subtitle"></div>
            </div>
            <div class="hub-skeleton-block hub-skeleton-search"></div>
        </div>
        <div class="hub-skeleton-body">
            <div class="hub-skeleton-sidebar">
                @for ($i = 0; $i < 8; $i++)
                <div class="hub-skeleton-block hub-skeleton-nav-item"></div>
                @endfor
            </div>
            <div class="hub-skeleton-main">
                @for ($section = 0; $section < 4; $section++)
                <div class="{{ $section > 0 ? 'mt-4' : '' }}">
                    <div class="hub-skeleton-block hub-skeleton-section-title mb-3"></div>
                    <div class="hub-skeleton-list">
                        @for ($row = 0; $row < 5; $row++)
                        <div class="hub-skeleton-row">
                            <div class="hub-skeleton-block hub-skeleton-icon"></div>
                            <div class="hub-skeleton-row-copy">
                                <div class="hub-skeleton-block hub-skeleton-line medium"></div>
                                <div class="hub-skeleton-block hub-skeleton-line short"></div>
                            </div>
                            <div class="hub-skeleton-block hub-skeleton-arrow"></div>
                        </div>
                        @endfor
                    </div>
                </div>
                @endfor
                <div class="settings-skeleton-fill"></div>
            </div>
        </div>
    </div>
</div>
