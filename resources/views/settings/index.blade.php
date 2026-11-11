@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/settings-hub.css') }}?v={{ filemtime(public_path('css/settings-hub.css')) }}">
@endpush

@section('content')
@php
    $categoryIcons = [
        'Personal' => 'bi-person',
        'Organization' => 'bi-building',
        'Users & Permissions' => 'bi-people',
        'Channels & Integrations' => 'bi-plug',
        'Sales & Documents' => 'bi-file-earmark-text',
        'Data & Catalog' => 'bi-collection',
        'Logs' => 'bi-journal-text',
        'System' => 'bi-hdd-stack',
    ];
    $grouped = $settings->groupBy('category');
@endphp

<div class="container-fluid settings-hub-page hub-page-wrap">
    @include('partials.skeletons.settings_hub')
    <div class="settings-hub-shell hub-page-content">
        <div class="settings-hub-topbar">
            <div>
                <h1 class="settings-hub-topbar-title">Settings</h1>
                <p class="settings-hub-topbar-subtitle">Manage your profile, organization, users, integrations, and system preferences.</p>
            </div>
            <div class="settings-search-wrap">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" id="settingsSearchInput"
                           placeholder="Search settings..." value="{{ $search }}" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="settings-hub-body">
            <aside class="settings-hub-sidebar">
                <p class="settings-sidebar-label">Categories</p>
                <nav class="nav flex-column settings-category-nav" id="settingsCategoryNav">
                    <button type="button"
                            class="category-filter {{ $category === 'all' ? 'active' : '' }}"
                            data-category="all">
                        <i class="bi bi-grid"></i>
                        <span class="category-label">All Settings</span>
                        <span class="badge rounded-pill">{{ $settings->count() }}</span>
                    </button>
                    @foreach($categories as $cat)
                    <button type="button"
                            class="category-filter {{ $category === $cat ? 'active' : '' }}"
                            data-category="{{ $cat }}">
                        <i class="bi {{ $categoryIcons[$cat] ?? 'bi-folder2' }}"></i>
                        <span class="category-label">{{ $cat }}</span>
                        <span class="badge rounded-pill">{{ $settings->where('category', $cat)->count() }}</span>
                    </button>
                    @endforeach
                </nav>
            </aside>

            <div class="settings-hub-main">
                @forelse($grouped as $categoryName => $items)
                <section class="settings-category-section" data-category="{{ $categoryName }}">
                    <div class="settings-section-head">
                        <h2 class="settings-section-title">{{ $categoryName }}</h2>
                        <span class="settings-section-count">{{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}</span>
                    </div>

                    <div class="settings-items-list">
                        @foreach($items as $item)
                        <a href="{{ $item['url'] }}"
                           class="settings-item settings-item-row"
                           data-name="{{ strtolower($item['name']) }}"
                           data-description="{{ strtolower($item['description']) }}"
                           data-category="{{ $categoryName }}">
                            <div class="settings-item-icon" style="--icon-color: {{ $item['icon_color'] }};">
                                <i class="bi {{ $item['icon'] }}"></i>
                            </div>
                            <div class="settings-item-body">
                                <p class="settings-item-title">{{ $item['name'] }}</p>
                                <p class="settings-item-desc">{{ $item['description'] }}</p>
                            </div>
                            <span class="settings-item-arrow" aria-hidden="true">
                                <i class="bi bi-chevron-right"></i>
                            </span>
                        </a>
                        @endforeach
                    </div>
                </section>
                @empty
                <div class="settings-empty-state">
                    <i class="bi bi-gear"></i>
                    <p class="mb-0">No settings are available for your account.</p>
                </div>
                @endforelse

                <div id="settingsNoMatch" class="settings-empty-state d-none">
                    <i class="bi bi-search"></i>
                    <p class="mb-0">No settings match your search.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var currentCategory = @json($category ?: 'all');
    var searchInput = document.getElementById('settingsSearchInput');
    var noMatch = document.getElementById('settingsNoMatch');

    function setActiveCategory(category) {
        currentCategory = category || 'all';
        document.querySelectorAll('.category-filter').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-category') === currentCategory);
        });
    }

    function filterSettings() {
        var q = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
        var visibleSections = 0;

        document.querySelectorAll('.settings-category-section').forEach(function (section) {
            var sectionCategory = section.getAttribute('data-category') || '';
            var sectionVisible = currentCategory === 'all' || currentCategory === sectionCategory;
            var sectionHasVisible = false;

            section.querySelectorAll('.settings-item-row').forEach(function (row) {
                var name = row.getAttribute('data-name') || '';
                var desc = row.getAttribute('data-description') || '';
                var cat = (row.getAttribute('data-category') || '').toLowerCase();
                var matchSearch = !q || name.indexOf(q) !== -1 || desc.indexOf(q) !== -1 || cat.indexOf(q) !== -1;
                var show = sectionVisible && matchSearch;
                row.style.display = show ? '' : 'none';
                if (show) sectionHasVisible = true;
            });

            section.style.display = sectionHasVisible ? '' : 'none';
            if (sectionHasVisible) visibleSections++;
        });

        if (noMatch) {
            var total = document.querySelectorAll('.settings-item-row').length;
            noMatch.classList.toggle('d-none', visibleSections > 0 || total === 0);
        }
    }

    document.querySelectorAll('.category-filter').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setActiveCategory(btn.getAttribute('data-category'));
            filterSettings();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', filterSettings);
    }

    setActiveCategory(currentCategory);
    filterSettings();
})();
</script>
@endsection
