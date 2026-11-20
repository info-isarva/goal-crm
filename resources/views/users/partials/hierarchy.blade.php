@php
    $companyName = $company->name ?? config('app.name', 'ISARVA CRM');
    $countInSubtree = function ($unit) use (&$countInSubtree, $usersByOrgUnit) {
        $n = ($usersByOrgUnit->get($unit->id) ?? collect())->count();
        foreach ($unit->children as $child) {
            $n += $countInSubtree($child);
        }
        return $n;
    };

    $hierarchyPayload = $hierarchyUsers->map(function ($u) use ($roles) {
        $parts = preg_split('/\s+/', trim((string) $u->name)) ?: [];
        $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
        $roleLabel = ((int) $u->crm_role_type === 0)
            ? 'Super Admin'
            : ($roles[$u->crm_role_type] ?? strtoupper($u->job_role ?? 'User'));

        return [
            'id' => (int) $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role_id' => (int) ($u->crm_role_type ?? 0),
            'role' => $roleLabel,
            'org_id' => $u->goal_org_unit_id ? (int) $u->goal_org_unit_id : null,
            'org_name' => optional($u->orgUnit)->name,
            'reports_to' => $u->reports_to_user_id ? (int) $u->reports_to_user_id : null,
            'reports' => (int) ($u->direct_reports_count ?? 0),
            'initials' => $initials ?: 'U',
            'status' => (int) ($u->status ?? 1),
        ];
    })->values();
@endphp

<script type="application/json" id="uhUsersJson">{!! json_encode($hierarchyPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}</script>
<div class="uh-wrap" id="usersHierarchyView" hidden data-company="{{ $companyName }}">
    <div class="uh-header">
        <div class="uh-header-left">
            <div class="uh-brand">
                <div class="uh-brand-icon" aria-hidden="true">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <div>
                    <h2 class="uh-title">Hierarchy Users</h2>
                    <p class="uh-sub">View users in organizational hierarchy with reporting structure.</p>
                </div>
            </div>
            <div class="users-view-toggle" role="group" aria-label="Users view mode">
                <button type="button" class="uv-btn users-view-list-btn" data-view="list">
                    <i class="bi bi-list-ul me-1"></i> List View
                </button>
                <button type="button" class="uv-btn is-active users-view-hierarchy-btn" data-view="hierarchy">
                    <i class="bi bi-diagram-3 me-1"></i> Hierarchy
                </button>
            </div>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-custom btn-sm @if(!auth()->user()->hasCrmPermission('create_crm_user_guard')) disabled @endif">
            <i class="bi bi-plus-lg"></i> Create User
        </a>
    </div>

    <div class="uh-stats">
        <div class="uh-stat uh-stat--teal">
            <div class="uh-stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="uh-stat-text">
                <div class="uh-stat-label">Total Users</div>
                <div class="uh-stat-value" id="uhStatTotal">{{ $hierarchyStats['total'] }}</div>
            </div>
            <span class="uh-stat-wave" aria-hidden="true"></span>
        </div>
        <div class="uh-stat uh-stat--teal">
            <div class="uh-stat-icon"><i class="bi bi-people"></i></div>
            <div class="uh-stat-text">
                <div class="uh-stat-label">Top Level Users</div>
                <div class="uh-stat-value" id="uhStatTop">{{ $hierarchyStats['top_level'] }}</div>
            </div>
            <span class="uh-stat-wave" aria-hidden="true"></span>
        </div>
        <div class="uh-stat uh-stat--amber">
            <div class="uh-stat-icon"><i class="bi bi-person-workspace"></i></div>
            <div class="uh-stat-text">
                <div class="uh-stat-label">Reporting Managers</div>
                <div class="uh-stat-value" id="uhStatManagers">{{ $hierarchyStats['managers'] }}</div>
            </div>
            <span class="uh-stat-wave" aria-hidden="true"></span>
        </div>
        <div class="uh-stat uh-stat--sky">
            <div class="uh-stat-icon"><i class="bi bi-diagram-3-fill"></i></div>
            <div class="uh-stat-text">
                <div class="uh-stat-label">Hierarchy Levels</div>
                <div class="uh-stat-value" id="uhStatLevels">{{ $hierarchyStats['levels'] }}</div>
            </div>
            <span class="uh-stat-wave" aria-hidden="true"></span>
        </div>
    </div>

    <div class="uh-toolbar">
        <div class="uh-search">
            <i class="bi bi-search"></i>
            <input type="text" id="uhSearch" placeholder="Search user, role or org unit..." autocomplete="off">
        </div>
        <select id="uhFilterRole" class="uh-select">
            <option value="">CRM Role: All</option>
            @foreach(($hierarchyRoles ?? $roles) as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
        <select id="uhFilterOrg" class="uh-select">
            <option value="">Org Unit: All</option>
            @foreach(($orgUnits ?? []) as $root)
                @php
                    $optIds = function ($u) use (&$optIds) {
                        $ids = [(int) $u->id];
                        foreach ($u->children as $c) { $ids = array_merge($ids, $optIds($c)); }
                        return $ids;
                    };
                @endphp
                <option value="{{ $root->id }}" data-org-ids="{{ implode(',', $optIds($root)) }}">{{ $root->name }}</option>
                @foreach($root->children as $child)
                    <option value="{{ $child->id }}" data-org-ids="{{ implode(',', $optIds($child)) }}">— {{ $child->name }}</option>
                    @foreach($child->children as $grand)
                        <option value="{{ $grand->id }}" data-org-ids="{{ implode(',', $optIds($grand)) }}">—— {{ $grand->name }}</option>
                        @foreach($grand->children as $gg)
                            <option value="{{ $gg->id }}" data-org-ids="{{ implode(',', $optIds($gg)) }}">——— {{ $gg->name }}</option>
                        @endforeach
                    @endforeach
                @endforeach
            @endforeach
        </select>
        <div class="uh-mode-toggle" role="group" aria-label="Hierarchy mode">
            <button type="button" class="uh-mode is-active" data-mode="tree">Tree View</button>
            <button type="button" class="uh-mode" data-mode="chart">Chart View</button>
        </div>
        <button type="button" class="uh-btn" id="uhExpandAll" title="Expand or collapse all folders in Organization Hierarchy">
            <i class="bi bi-arrows-collapse"></i> <span class="uh-expand-label">Collapse All</span>
        </button>
        <button type="button" class="uh-btn" id="uhExport"><i class="bi bi-download"></i> Export</button>
    </div>

    <div class="uh-main" id="uhMain">
        <aside class="uh-tree-panel" id="uhTreePanel">
            <div class="uh-panel-title">Organization Hierarchy</div>
            <ul class="uh-tree" id="uhOrgTree">
                <li class="uh-tree-item is-open" data-org="">
                    <button type="button" class="uh-tree-row is-active" data-org="" data-org-ids="">
                        <i class="bi bi-caret-down-fill uh-caret"></i>
                        <i class="bi bi-folder-fill uh-folder"></i>
                        <span>{{ $companyName }} (<span id="uhTreeRootCount">{{ $hierarchyStats['total'] }}</span>)</span>
                    </button>
                    <ul class="uh-tree-children">
                        @forelse($orgUnits as $unit)
                            @include('users.partials.hierarchy_tree_node', [
                                'unit' => $unit,
                                'countInSubtree' => $countInSubtree,
                                'depth' => 1,
                            ])
                        @empty
                            <li class="uh-tree-empty text-muted px-3 py-2">No org units yet.</li>
                        @endforelse
                    </ul>
                </li>
            </ul>
        </aside>

        <section class="uh-chart-panel">
            <div class="uh-panel-title">Organization Chart</div>
            <div class="uh-chart-scroll" id="uhChartScroll">
                <div class="uh-chart" id="uhChart"></div>
            </div>
            <div class="uh-zoom">
                <button type="button" id="uhZoomOut" title="Zoom out"><i class="bi bi-dash-lg"></i></button>
                <span id="uhZoomLabel">100%</span>
                <button type="button" id="uhZoomIn" title="Zoom in"><i class="bi bi-plus-lg"></i></button>
            </div>

            <aside class="uh-user-drawer" id="uhUserDrawer" hidden aria-hidden="true">
                <div class="uh-drawer-head">
                    <div class="uh-drawer-who">
                        <div class="uh-avatar uh-drawer-avatar" id="uhDrawerAvatar">U</div>
                        <div>
                            <div class="uh-drawer-name" id="uhDrawerName">—</div>
                            <div class="uh-drawer-role" id="uhDrawerRole">—</div>
                        </div>
                    </div>
                    <button type="button" class="uh-drawer-close" id="uhDrawerClose" title="Close">&times;</button>
                </div>
                <div class="uh-drawer-meta" id="uhDrawerMeta"></div>
                <div class="uh-drawer-tabs">
                    <button type="button" class="uh-drawer-tab is-active" data-tab="direct">Direct reports</button>
                    <button type="button" class="uh-drawer-tab" data-tab="all">All under</button>
                </div>
                <div class="uh-drawer-list" id="uhDrawerList"></div>
            </aside>
        </section>
    </div>
</div>
