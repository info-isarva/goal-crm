{{-- Sidebar Reports: single link to reports list page --}}
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('reports.*') || request()->routeIs('task-reports.*') || request()->routeIs('analytics.*') ? 'active' : '' }}"
       href="{{ route('reports.index') }}">
        <i class="bi bi-bar-chart-fill"></i>
        <span class="nav-label">Reports</span>
    </a>
</li>
