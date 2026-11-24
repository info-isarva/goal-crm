@php
    $filters = $filters ?? ['q' => '', 'crm_role_type' => '', 'goal_org_unit_id' => '', 'status' => '', 'two_fa' => '', 'per_page' => 10, 'sort' => 'name', 'dir' => 'asc'];
    $listStats = $listStats ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'two_fa' => 0];
    $branchOptions = $branchOptions ?? collect();
    $isGoalTenant = $isGoalTenant ?? \App\Services\Goal\GoalDashboardService::userUsesGoalDashboard(auth()->user());
    $canEdit = auth()->user()->hasCrmPermission('edit_crm_user_guard');
    $canDelete = auth()->user()->hasCrmPermission('delete_crm_user_guard');
    $canCreate = auth()->user()->hasCrmPermission('create_crm_user_guard')
        && (auth()->user()->isSuperAdmin() || empty($maxUsers) || ($userCount ?? 0) < $maxUsers);

    // Soft pastel bg + darker initials (mockup style)
    $avatarPalette = [
        ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
        ['bg' => '#ede9fe', 'fg' => '#6d28d9'],
        ['bg' => '#ccfbf1', 'fg' => '#0f766e'],
        ['bg' => '#dcfce7', 'fg' => '#15803d'],
        ['bg' => '#ffedd5', 'fg' => '#c2410c'],
        ['bg' => '#fce7f3', 'fg' => '#be185d'],
        ['bg' => '#e0e7ff', 'fg' => '#3730a3'],
        ['bg' => '#fef3c7', 'fg' => '#b45309'],
    ];
    $roleBadge = function ($roleId, $roles) {
        $name = ((int) $roleId === 0) ? 'Super Admin' : ($roles[$roleId] ?? 'Unknown');
        $map = [
            0 => 'us-role--dark',
            4 => 'us-role--md',
            5 => 'us-role--ceo',
            6 => 'us-role--rd',
            7 => 'us-role--cluster',
            8 => 'us-role--bm',
            9 => 'us-role--vrm',
            10 => 'us-role--jsm',
            11 => 'us-role--sm',
            12 => 'us-role--ssm',
            13 => 'us-role--pms',
            14 => 'us-role--accounts',
        ];
        $class = $map[(int) $roleId] ?? 'us-role--default';

        return [$name, $class];
    };
    $sortUrl = function ($col) use ($filters) {
        $dir = ($filters['sort'] === $col && $filters['dir'] === 'asc') ? 'desc' : 'asc';

        return request()->fullUrlWithQuery(array_merge($filters, ['sort' => $col, 'dir' => $dir, 'page' => 1]));
    };
    $initials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $a = strtoupper(substr($parts[0] ?? 'U', 0, 1));
        $b = strtoupper(substr($parts[1] ?? '', 0, 1));

        return $a.$b ?: 'U';
    };
@endphp

<div id="usersListView" class="us-manage">
    <div class="us-page-head">
        <div class="us-page-brand">
            <div class="us-page-icon" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
            <div>
                <h1 class="us-page-title">User Settings</h1>
                <p class="us-page-sub">
                    @if(\App\Services\Goal\GoalDashboardService::userUsesGoalDashboard(auth()->user()))
                        Manage CRM users, roles and authentication. Monthly targets are set in Targets.
                    @else
                        Manage CRM users, roles, authentication and sales targets.
                    @endif
                </p>
            </div>
        </div>
    </div>

    @php
        $kpiBaseQuery = array_filter([
            'q' => $filters['q'] ?? null,
            'crm_role_type' => $filters['crm_role_type'] ?? null,
            'goal_org_unit_id' => $filters['goal_org_unit_id'] ?? null,
            'per_page' => $filters['per_page'] ?? 10,
            'sort' => $filters['sort'] ?? 'name',
            'dir' => $filters['dir'] ?? 'asc',
        ], fn ($v) => $v !== null && $v !== '');
        $kpiUrl = function (array $extra) use ($kpiBaseQuery) {
            $merged = array_merge($kpiBaseQuery, $extra);
            return route('users.index', array_filter($merged, fn ($v) => $v !== null && $v !== ''));
        };
        $wavePath = 'M0 48 C18 40 28 58 45 46 C62 34 72 52 88 42 C100 34 110 38 120 30 L120 70 L0 70 Z';
    @endphp

    <div class="us-kpis">
        <a href="{{ $kpiUrl([]) }}" class="us-kpi us-kpi--teal">
            <div class="us-kpi-icon"><i class="bi bi-people-fill"></i></div>
            <div class="us-kpi-body">
                <div class="us-kpi-label">Total Users</div>
                <div class="us-kpi-value">{{ $listStats['total'] }}</div>
            </div>
            <span class="us-kpi-wave" aria-hidden="true">
                <svg viewBox="0 0 120 70" preserveAspectRatio="none"><defs>
                    <linearGradient id="usWTeal" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#99f6e4"/><stop offset="100%" stop-color="#009B80"/></linearGradient>
                </defs><path fill="url(#usWTeal)" d="{{ $wavePath }}"/></svg>
            </span>
        </a>
        <a href="{{ $kpiUrl(['status' => 1]) }}" class="us-kpi us-kpi--green">
            <div class="us-kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="us-kpi-body">
                <div class="us-kpi-label">Active Users</div>
                <div class="us-kpi-value">{{ $listStats['active'] }}</div>
            </div>
            <span class="us-kpi-wave" aria-hidden="true">
                <svg viewBox="0 0 120 70" preserveAspectRatio="none"><defs>
                    <linearGradient id="usWGreen" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#6ee7b7"/><stop offset="100%" stop-color="#10b981"/></linearGradient>
                </defs><path fill="url(#usWGreen)" d="{{ $wavePath }}"/></svg>
            </span>
        </a>
        <a href="{{ $kpiUrl(['status' => 0]) }}" class="us-kpi us-kpi--amber">
            <div class="us-kpi-icon"><i class="bi bi-pause-circle-fill"></i></div>
            <div class="us-kpi-body">
                <div class="us-kpi-label">Inactive Users</div>
                <div class="us-kpi-value">{{ $listStats['inactive'] }}</div>
            </div>
            <span class="us-kpi-wave" aria-hidden="true">
                <svg viewBox="0 0 120 70" preserveAspectRatio="none"><defs>
                    <linearGradient id="usWAmber" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#fdba74"/><stop offset="100%" stop-color="#f59e0b"/></linearGradient>
                </defs><path fill="url(#usWAmber)" d="{{ $wavePath }}"/></svg>
            </span>
        </a>
        <a href="{{ $kpiUrl(['two_fa' => 1]) }}" class="us-kpi us-kpi--sky">
            <div class="us-kpi-icon"><i class="bi bi-shield-fill-check"></i></div>
            <div class="us-kpi-body">
                <div class="us-kpi-label">2FA Enabled</div>
                <div class="us-kpi-value">{{ $listStats['two_fa'] }}</div>
            </div>
            <span class="us-kpi-wave" aria-hidden="true">
                <svg viewBox="0 0 120 70" preserveAspectRatio="none"><defs>
                    <linearGradient id="usWSky" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#93c5fd"/><stop offset="100%" stop-color="#3b82f6"/></linearGradient>
                </defs><path fill="url(#usWSky)" d="{{ $wavePath }}"/></svg>
            </span>
        </a>
    </div>

    <div class="us-control-card">
        <div class="us-control-top">
            <div class="users-view-toggle" role="group" aria-label="Users view mode">
                <button type="button" class="uv-btn is-active users-view-list-btn" data-view="list">
                    <i class="bi bi-list-ul"></i> List View
                </button>
                <button type="button" class="uv-btn users-view-hierarchy-btn" data-view="hierarchy">
                    <i class="bi bi-diagram-3"></i> Hierarchy
                </button>
            </div>
            <a href="{{ route('users.create') }}"
               class="us-btn us-btn--primary us-btn--create {{ $canCreate ? '' : 'disabled' }}">
                <i class="bi bi-plus-lg"></i> Create User
            </a>
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="us-filters" id="usersFilterForm">
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <input type="hidden" name="dir" value="{{ $filters['dir'] }}">
            <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">

            <div class="us-filters-row">
                <div class="us-filter-field us-filter-field--search">
                    <label class="us-filter-label" for="usFilterQ">Search</label>
                    <div class="us-filter-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="usFilterQ" name="q" value="{{ $filters['q'] }}" placeholder="Name, email or mobile..." autocomplete="off">
                    </div>
                </div>
                <div class="us-filter-field">
                    <label class="us-filter-label" for="usFilterRole">CRM Role</label>
                    <select id="usFilterRole" name="crm_role_type" class="form-select js-select2 us-filter-select"
                            data-placeholder="All roles" data-allow-clear="true" data-width="100%">
                        <option value="">All roles</option>
                        @foreach($roles as $id => $name)
                            <option value="{{ $id }}" @selected((string) $filters['crm_role_type'] === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="us-filter-field">
                    <label class="us-filter-label" for="usFilterBranch">Branch</label>
                    <select id="usFilterBranch" name="goal_org_unit_id" class="form-select js-select2 us-filter-select"
                            data-placeholder="All branches" data-allow-clear="true" data-width="100%">
                        <option value="">All branches</option>
                        @foreach($branchOptions as $branch)
                            <option value="{{ $branch->id }}" @selected((string) ($filters['goal_org_unit_id'] ?? '') === (string) $branch->id)>
                                {{ $branch->name }}@if($branch->code) ({{ $branch->code }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="us-filter-field">
                    <label class="us-filter-label" for="usFilterStatus">Status</label>
                    <select id="usFilterStatus" name="status" class="form-select js-select2 us-filter-select"
                            data-placeholder="All" data-allow-clear="true" data-search="false" data-width="100%">
                        <option value="">All</option>
                        <option value="1" @selected((string) $filters['status'] === '1')>Active</option>
                        <option value="0" @selected((string) $filters['status'] === '0')>Inactive</option>
                    </select>
                </div>
                <div class="us-filter-field">
                    <label class="us-filter-label" for="usFilter2fa">2FA</label>
                    <select id="usFilter2fa" name="two_fa" class="form-select js-select2 us-filter-select"
                            data-placeholder="All" data-allow-clear="true" data-search="false" data-width="100%">
                        <option value="">All</option>
                        <option value="1" @selected((string) $filters['two_fa'] === '1')>Enabled</option>
                        <option value="0" @selected((string) $filters['two_fa'] === '0')>Disabled</option>
                    </select>
                </div>
                <div class="us-filter-actions">
                    <button type="submit" class="us-btn us-btn--primary">
                        <i class="bi bi-funnel-fill"></i> Apply
                    </button>
                    <a href="{{ route('users.index') }}" class="us-btn us-btn--outline">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                    <div class="dropdown">
                        <button type="button" class="us-btn us-btn--outline dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li>
                                <a class="dropdown-item" href="{{ route('users.export', array_merge(request()->query(), ['format' => 'csv'])) }}">
                                    <i class="bi bi-file-earmark-spreadsheet me-2"></i> Export Excel (CSV)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('users.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
                                    <i class="bi bi-file-earmark-pdf me-2"></i> Export PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="us-table-card">
        <div class="us-table-head">
            <div class="us-table-head-left">
                <span class="us-table-ico" aria-hidden="true"><i class="bi bi-table"></i></span>
                <h3 class="us-table-title">
                    Users List
                    <span class="us-table-count">({{ $users->total() }})</span>
                </h3>
            </div>
            @if(!empty($maxUsers))
                <span class="us-seats">Seats: {{ $userCount ?? 0 }} / {{ $maxUsers }}</span>
            @endif
        </div>

        <div class="table-responsive us-table-wrap">
            <table class="table align-middle mb-0 us-table" id="usersManageTable">
                <thead>
                    <tr>
                        <th class="col-check">
                            <input type="checkbox" class="form-check-input" id="usSelectAll" title="Select page">
                        </th>
                        <th class="col-num">#</th>
                        <th class="col-name">
                            <a class="us-sort" href="{{ $sortUrl('name') }}">Name
                                @if($filters['sort']==='name')<i class="bi bi-caret-{{ $filters['dir']==='asc' ? 'up' : 'down' }}-fill"></i>@endif
                            </a>
                        </th>
                        <th class="col-email">
                            <a class="us-sort" href="{{ $sortUrl('email') }}">Email
                                @if($filters['sort']==='email')<i class="bi bi-caret-{{ $filters['dir']==='asc' ? 'up' : 'down' }}-fill"></i>@endif
                            </a>
                        </th>
                        <th class="col-branch">Branch</th>
                        <th class="col-role">
                            <a class="us-sort" href="{{ $sortUrl('crm_role_type') }}">CRM Role
                                @if($filters['sort']==='crm_role_type')<i class="bi bi-caret-{{ $filters['dir']==='asc' ? 'up' : 'down' }}-fill"></i>@endif
                            </a>
                        </th>
                        @unless($isGoalTenant)
                        <th class="col-target">
                            <a class="us-sort" href="{{ $sortUrl('sales_target') }}">Sales Target (₹)
                                @if($filters['sort']==='sales_target')<i class="bi bi-caret-{{ $filters['dir']==='asc' ? 'up' : 'down' }}-fill"></i>@endif
                            </a>
                        </th>
                        @endunless
                        <th class="col-2fa">2FA</th>
                        <th class="col-status">
                            <a class="us-sort" href="{{ $sortUrl('status') }}">Status
                                @if($filters['sort']==='status')<i class="bi bi-caret-{{ $filters['dir']==='asc' ? 'up' : 'down' }}-fill"></i>@endif
                            </a>
                        </th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            [$roleName, $roleClass] = $roleBadge($user->crm_role_type, $roles);
                            $avatar = $avatarPalette[$user->id % count($avatarPalette)];
                            $ini = $initials($user->name);
                            $isActive = (int) $user->status === 1;
                            $twoFaOn = (bool) $user->{"2fa_enabled"};
                            $userPayload = base64_encode(json_encode([
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'mobile' => $user->mobile,
                                'role' => $roleName,
                                'role_class' => $roleClass,
                                'status' => $isActive ? 'Active' : 'Inactive',
                                'two_fa' => $twoFaOn ? 'Enabled' : 'Disabled',
                                'sales_target' => $isGoalTenant ? null : number_format((float) ($user->sales_target ?? 0), 2),
                                'job_role' => $user->job_role,
                                'org' => optional($user->orgUnit)->name,
                                'initials' => $ini,
                                'avatar_bg' => $avatar['bg'],
                                'avatar_fg' => $avatar['fg'],
                            ], JSON_UNESCAPED_UNICODE));
                        @endphp
                        <tr>
                            <td class="col-check" data-label="">
                                <input type="checkbox" class="form-check-input us-row-check" value="{{ $user->id }}">
                            </td>
                            <td class="col-num" data-label="#">{{ $users->firstItem() + $loop->index }}</td>
                            <td class="col-name" data-label="Name">
                                <div class="us-name-cell">
                                    <span class="us-avatar" style="background:{{ $avatar['bg'] }};color:{{ $avatar['fg'] }}">{{ $ini }}</span>
                                    @if ((int) $user->crm_role_type !== 0 && $canEdit)
                                        <a href="{{ route('users.edit', $user->id) }}" class="us-name-link">{{ $user->name }}</a>
                                    @else
                                        <span class="us-name-text">{{ $user->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="col-email" data-label="Email">{{ $user->email }}</td>
                            <td class="col-branch" data-label="Branch">
                                @if(optional($user->orgUnit)->name)
                                    <span class="us-branch">{{ $user->orgUnit->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="col-role" data-label="CRM Role">
                                <span class="us-role {{ $roleClass }}">{{ $roleName }}</span>
                            </td>
                            @unless($isGoalTenant)
                            <td class="col-target" data-label="Sales Target">
                                {{ number_format((float) ($user->sales_target ?? 0), 2) }}
                            </td>
                            @endunless
                            <td class="col-2fa" data-label="2FA">
                                <form method="POST" action="{{ route('users.2fa.update', $user->id) }}" class="us-2fa-form" data-user-name="{{ $user->name }}" data-enabled="{{ $twoFaOn ? '1' : '0' }}">
                                    @csrf
                                    <div class="form-check form-switch us-2fa-switch mb-0">
                                        <input class="form-check-input us-2fa-toggle" type="checkbox" name="2fa_enabled" value="1"
                                            {{ $twoFaOn ? 'checked' : '' }}
                                            @disabled(!$canEdit || (int) $user->crm_role_type === 0)>
                                        <label class="form-check-label">{{ $twoFaOn ? 'Enabled' : 'Disabled' }}</label>
                                    </div>
                                </form>
                            </td>
                            <td class="col-status" data-label="Status">
                                @if($isActive)
                                    <span class="us-status us-status--active">Active</span>
                                @else
                                    <span class="us-status us-status--inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="col-actions" data-label="Actions">
                                <div class="us-actions">
                                    <button type="button" class="us-abtn us-abtn--view us-view-btn" title="View Details" data-user="{{ $userPayload }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if ((int) $user->crm_role_type !== 0)
                                        <a href="{{ route('users.edit', $user->id) }}"
                                           class="us-abtn us-abtn--edit {{ $canEdit ? '' : 'disabled' }}" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <div class="dropdown">
                                            <button class="us-abtn us-abtn--more" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item us-view-btn" data-user="{{ $userPayload }}">
                                                        <i class="bi bi-eye"></i> View Details
                                                    </button>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ $canEdit ? '' : 'disabled' }}" href="{{ route('users.edit', $user->id) }}">
                                                        <i class="bi bi-pencil"></i> Edit User
                                                    </a>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ route('users.password.reset', $user->id) }}" class="us-reset-form">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item {{ $canEdit ? '' : 'disabled' }}" @disabled(!$canEdit)>
                                                            <i class="bi bi-key"></i> Reset Password
                                                        </button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('users.status.update', $user->id) }}" class="us-status-form">
                                                        @csrf
                                                        <input type="hidden" name="status" value="{{ $isActive ? 0 : 1 }}">
                                                        <button type="submit"
                                                            class="dropdown-item {{ $isActive ? 'text-danger' : 'text-success' }} {{ $canEdit ? '' : 'disabled' }}"
                                                            @disabled(!$canEdit || (int) $user->id === (int) auth()->id())>
                                                            <i class="bi {{ $isActive ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                                                            {{ $isActive ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                </li>
                                                @if($canDelete)
                                                    <li>
                                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="delete-user-btn dropdown-item text-danger"
                                                                data-user-name="{{ $user->name }}">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isGoalTenant ? 9 : 10 }}" class="text-center py-5">
                                <div class="us-empty">
                                    <i class="bi bi-people"></i>
                                    <div>No users match your filters.</div>
                                    <a href="{{ route('users.index') }}" class="us-btn us-btn--ghost mt-2">Reset filters</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->total() > 0)
            <div class="us-table-foot">
                <div class="us-showing">
                    Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
                </div>
                <div class="us-foot-right">
                    <form method="GET" action="{{ route('users.index') }}" class="us-rows-label" id="usRowsForm">
                        @foreach(request()->except(['per_page', 'page']) as $key => $val)
                            @if(is_array($val))
                                @foreach($val as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                            @endif
                        @endforeach
                        <span>Rows per page:</span>
                        <select name="per_page" onchange="this.form.submit()" aria-label="Rows per page">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected((int) $filters['per_page'] === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                    <div class="us-pager">
                        {{ $users->onEachSide(1)->links('vendor.pagination.users') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- View Details Modal --}}
<div class="modal fade" id="usUserDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content us-detail-modal">
            <div class="modal-header us-detail-modal__head">
                <div class="us-detail-modal__title-wrap">
                    <span class="us-detail-modal__badge"><i class="bi bi-person-vcard"></i></span>
                    <div>
                        <h5 class="modal-title us-detail-modal__title">User Details</h5>
                        <p class="us-detail-modal__sub mb-0">Profile, role and account status</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body us-detail-modal__body" id="usUserDetailBody"></div>
            <div class="modal-footer us-detail-modal__foot">
                <button type="button" class="us-btn us-btn--ghost" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Close
                </button>
                <a href="#" class="us-btn us-btn--primary" id="usUserDetailEdit">
                    <i class="bi bi-pencil"></i> Edit User
                </a>
            </div>
        </div>
    </div>
</div>
