@extends('layouts.app')

@section('content')
<div class="container-fluid p-4 reports-list-page hub-page-wrap">
    @include('partials.skeletons.reports_list')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="dropdown">
            <button class="btn btn-light border dropdown-toggle fw-semibold" type="button" id="reportsFolderBtn"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <span id="reportsFolderLabel">All Reports</span>
            </button>
            <ul class="dropdown-menu shadow-sm reports-folder-menu" aria-labelledby="reportsFolderBtn" style="min-width:260px;">
                <li class="px-3 py-2">
                    <input type="text" class="form-control form-control-sm" id="folderSearchInput" placeholder="Search Folder">
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <button type="button" class="dropdown-item d-flex justify-content-between align-items-center folder-filter-item active"
                            data-filter="all">
                        <span>All Reports</span>
                        <i class="bi bi-check2 folder-check"></i>
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item folder-filter-item" data-filter="favorites">
                        Favorites
                    </button>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li class="px-3 pt-1 pb-1 text-muted small text-uppercase">Folders</li>
                @foreach($folders as $folder)
                <li>
                    <button type="button" class="dropdown-item folder-filter-item folder-name-item"
                            data-filter="{{ $folder }}">
                        {{ $folder }}
                    </button>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="d-flex align-items-center gap-2 flex-grow-1 justify-content-end" style="max-width:520px;">
            <div class="input-group" style="max-width:320px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control" id="reportsSearchInput"
                       placeholder="Search All Reports" value="{{ $search }}">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 reports-table">
                <thead class="table-light">
                    <tr>
                        <th style="width:56px;"></th>
                        <th>Report Name</th>
                        <th>Description</th>
                        <th style="width:180px;">Folder</th>
                        <th style="width:140px;">Last Accessed Date</th>
                        <th style="width:120px;">Created By</th>
                    </tr>
                </thead>
                <tbody id="reportsTableBody">
                    @forelse($reports as $report)
                    <tr class="report-row"
                        data-id="{{ $report['id'] }}"
                        data-name="{{ strtolower($report['name']) }}"
                        data-description="{{ strtolower($report['description']) }}"
                        data-folder="{{ $report['folder'] }}">
                        <td>
                            <button type="button" class="btn btn-link p-0 text-secondary favorite-toggle"
                                    data-id="{{ $report['id'] }}" title="Favorite">
                                <i class="bi bi-star"></i>
                            </button>
                        </td>
                        <td>
                            <a href="{{ $report['url'] }}" class="text-decoration-none fw-semibold report-link"
                               data-id="{{ $report['id'] }}">
                                {{ $report['name'] }}
                            </a>
                        </td>
                        <td class="text-muted">{{ $report['description'] }}</td>
                        <td>{{ $report['folder'] }}</td>
                        <td class="last-accessed text-muted">-</td>
                        <td class="text-muted">-</td>
                    </tr>
                    @empty
                    <tr id="reportsEmptyRow">
                        <td colspan="6" class="text-center text-muted py-4">No reports available for your permissions.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="reportsNoMatch" class="text-center text-muted py-4 d-none">No reports match your search.</div>
    </div>
</div>

<style>
.reports-list-page .reports-table th {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    white-space: nowrap;
}
.reports-list-page .reports-table td {
    font-size: 13px;
    vertical-align: middle;
}
.reports-list-page .report-link {
    color: #2563eb;
}
.reports-list-page .report-link:hover {
    color: #1d4ed8;
    text-decoration: underline !important;
}
.reports-list-page .favorite-toggle.is-favorite {
    color: #f59e0b !important;
}
.reports-list-page .favorite-toggle.is-favorite .bi-star::before {
    content: "\f586"; /* bi-star-fill */
}
.reports-list-page .folder-filter-item .folder-check {
    display: none;
}
.reports-list-page .folder-filter-item.active .folder-check {
    display: inline-block;
    color: #2563eb;
}
.reports-list-page .reports-folder-menu .dropdown-item {
    font-size: 13px;
}
</style>

<script>
(function () {
    var storageKeyFav = 'crm_report_favorites';
    var storageKeyAccess = 'crm_report_last_accessed';
    var currentFilter = @json($filter ?: 'all');
    var searchInput = document.getElementById('reportsSearchInput');
    var folderLabel = document.getElementById('reportsFolderLabel');
    var folderSearch = document.getElementById('folderSearchInput');
    var noMatch = document.getElementById('reportsNoMatch');

    function readJson(key, fallback) {
        try {
            var raw = localStorage.getItem(key);
            return raw ? JSON.parse(raw) : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function writeJson(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    }

    function favorites() {
        return readJson(storageKeyFav, []);
    }

    function setFavorites(list) {
        writeJson(storageKeyFav, list);
    }

    function lastAccessedMap() {
        return readJson(storageKeyAccess, {});
    }

    function relativeTime(ts) {
        if (!ts) return '-';
        var diff = Date.now() - Number(ts);
        if (diff < 0) diff = 0;
        var sec = Math.floor(diff / 1000);
        if (sec < 45) return 'Now';
        var min = Math.floor(sec / 60);
        if (min < 60) return min + ' min ago';
        var hr = Math.floor(min / 60);
        if (hr < 24) return hr + ' hr ago';
        var day = Math.floor(hr / 24);
        return day + ' day' + (day > 1 ? 's' : '') + ' ago';
    }

    function applyFavoritesUi() {
        var fav = favorites();
        document.querySelectorAll('.favorite-toggle').forEach(function (btn) {
            var id = btn.getAttribute('data-id');
            var on = fav.indexOf(id) !== -1;
            btn.classList.toggle('is-favorite', on);
            btn.querySelector('i').className = on ? 'bi bi-star-fill' : 'bi bi-star';
        });
    }

    function applyLastAccessedUi() {
        var map = lastAccessedMap();
        document.querySelectorAll('.report-row').forEach(function (row) {
            var id = row.getAttribute('data-id');
            var cell = row.querySelector('.last-accessed');
            if (cell) cell.textContent = relativeTime(map[id]);
        });
    }

    function setActiveFolderItem(filter) {
        document.querySelectorAll('.folder-filter-item').forEach(function (item) {
            var active = item.getAttribute('data-filter') === filter;
            item.classList.toggle('active', active);
        });
        if (filter === 'all') folderLabel.textContent = 'All Reports';
        else if (filter === 'favorites') folderLabel.textContent = 'Favorites';
        else folderLabel.textContent = filter;
    }

    function filterRows() {
        var q = (searchInput.value || '').trim().toLowerCase();
        var fav = favorites();
        var visible = 0;
        document.querySelectorAll('.report-row').forEach(function (row) {
            var name = row.getAttribute('data-name') || '';
            var desc = row.getAttribute('data-description') || '';
            var folder = row.getAttribute('data-folder') || '';
            var id = row.getAttribute('data-id');
            var matchSearch = !q || name.indexOf(q) !== -1 || desc.indexOf(q) !== -1 || folder.toLowerCase().indexOf(q) !== -1;
            var matchFolder = true;
            if (currentFilter === 'favorites') {
                matchFolder = fav.indexOf(id) !== -1;
            } else if (currentFilter !== 'all') {
                matchFolder = folder === currentFilter;
            }
            var show = matchSearch && matchFolder;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noMatch) {
            noMatch.classList.toggle('d-none', visible > 0 || document.querySelectorAll('.report-row').length === 0);
        }
    }

    document.querySelectorAll('.folder-filter-item').forEach(function (item) {
        item.addEventListener('click', function () {
            currentFilter = item.getAttribute('data-filter') || 'all';
            setActiveFolderItem(currentFilter);
            filterRows();
        });
    });

    if (folderSearch) {
        folderSearch.addEventListener('input', function () {
            var q = (folderSearch.value || '').trim().toLowerCase();
            document.querySelectorAll('.folder-name-item').forEach(function (item) {
                var text = (item.textContent || '').trim().toLowerCase();
                item.parentElement.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterRows);
    }

    document.querySelectorAll('.favorite-toggle').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var id = btn.getAttribute('data-id');
            var list = favorites();
            var idx = list.indexOf(id);
            if (idx === -1) list.push(id);
            else list.splice(idx, 1);
            setFavorites(list);
            applyFavoritesUi();
            if (currentFilter === 'favorites') filterRows();
        });
    });

    document.querySelectorAll('.report-link').forEach(function (link) {
        link.addEventListener('click', function () {
            var id = link.getAttribute('data-id');
            var map = lastAccessedMap();
            map[id] = Date.now();
            writeJson(storageKeyAccess, map);
        });
    });

    setActiveFolderItem(currentFilter);
    applyFavoritesUi();
    applyLastAccessedUi();
    filterRows();
})();
</script>
@endsection
