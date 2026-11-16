@php
    $count = $countInSubtree($unit);
    $hasChildren = $unit->children->isNotEmpty();
    $collectOrgIds = function ($u) use (&$collectOrgIds) {
        $ids = [(int) $u->id];
        foreach ($u->children as $child) {
            $ids = array_merge($ids, $collectOrgIds($child));
        }
        return $ids;
    };
    $orgIds = implode(',', $collectOrgIds($unit));
@endphp
<li class="uh-tree-item {{ $hasChildren ? 'is-open' : '' }}" data-org="{{ $unit->id }}">
    <button type="button" class="uh-tree-row" data-org="{{ $unit->id }}" data-org-ids="{{ $orgIds }}">
        @if($hasChildren)
            <i class="bi bi-caret-down-fill uh-caret"></i>
        @else
            <span class="uh-caret-spacer"></span>
        @endif
        <i class="bi bi-folder-fill uh-folder"></i>
        <span>{{ $unit->name }} (<span class="uh-tree-count" data-org-ids="{{ $orgIds }}">{{ $count }}</span>)</span>
    </button>
    @if($hasChildren)
        <ul class="uh-tree-children">
            @foreach($unit->children as $child)
                @include('users.partials.hierarchy_tree_node', [
                    'unit' => $child,
                    'countInSubtree' => $countInSubtree,
                    'depth' => ($depth ?? 1) + 1,
                ])
            @endforeach
        </ul>
    @endif
</li>
