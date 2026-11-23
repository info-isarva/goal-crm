@php
    $active = $active ?? '';
    $q = request()->only('period', 'metric');
@endphp
<nav class="gt-subnav" aria-label="Targets sections">
    <a href="{{ route('goal.targets.index', $q) }}" class="{{ $active === 'dashboard' ? 'is-active' : '' }}">
        <i class="bi bi-grid"></i> Dashboard
    </a>
    <a href="{{ route('goal.targets.summary', $q) }}" class="{{ $active === 'summary' ? 'is-active' : '' }}">
        <i class="bi bi-clipboard-data"></i> Target Summary
    </a>
    <a href="{{ route('goal.targets.achievement', $q) }}" class="{{ $active === 'achievement' ? 'is-active' : '' }}">
        <i class="bi bi-graph-up-arrow"></i> Achievement
    </a>
    <a href="{{ route('goal.targets.carry-forward', $q) }}" class="{{ $active === 'carry' ? 'is-active' : '' }}">
        <i class="bi bi-arrow-repeat"></i> Shortfall Carry Forward
    </a>
    @if(!empty($canManage))
        <a href="{{ route('goal.targets.set', $q) }}" class="{{ $active === 'set' ? 'is-active' : '' }}">
            <i class="bi bi-bullseye"></i> Set Targets
        </a>
    @endif
</nav>
