@php
    $children = $byManager->get((int) $user->id, collect())->values();
    $showChildren = $children->isNotEmpty() && ($depth ?? 0) < ($maxDepth ?? 4);
    $isRoot = ((int) ($depth ?? 0) === 0);
@endphp
<li class="uh-org-item {{ $showChildren ? 'has-kids' : '' }} {{ $isRoot ? 'is-root' : '' }}"
    data-name="{{ strtolower($user->name) }}"
    data-role="{{ $user->crm_role_type }}"
    data-org="{{ $user->goal_org_unit_id }}">
    <div class="uh-node-wrap">
        @unless($isRoot)
            <span class="uh-in-arrow" aria-hidden="true"></span>
        @endunless
        <div class="uh-node-card {{ $isRoot ? 'is-root-card' : '' }}">
            <div class="uh-avatar">{{ $initials($user->name) }}<span class="uh-online"></span></div>
            <div class="uh-node-body">
                <div class="uh-node-name">{{ $user->name }}</div>
                <div class="uh-node-role">{{ $roleName($user) }}</div>
                <div class="uh-node-meta"><i class="bi bi-people-fill"></i> {{ (int) $user->direct_reports_count }} Users</div>
            </div>
        </div>
        @if($showChildren)
            <span class="uh-out-node" aria-hidden="true"></span>
        @endif
    </div>
    @if($showChildren)
        <ul class="uh-org">
            @foreach($children as $child)
                @include('users.partials.hierarchy_chart_node', [
                    'user' => $child,
                    'byManager' => $byManager,
                    'roleName' => $roleName,
                    'initials' => $initials,
                    'depth' => ($depth ?? 0) + 1,
                    'maxDepth' => $maxDepth ?? 4,
                ])
            @endforeach
        </ul>
    @endif
</li>
