<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalOrgUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalOrgUnitController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->requireGoalPermission('manage_org_units_guard');

        $filters = $this->validatedFilters($request);
        $all = GoalOrgUnit::query()->with(['parent', 'children'])->get();
        $kpis = [
            'total' => $all->count(),
            'regions' => $all->where('type', GoalOrgUnit::TYPE_REGION)->count(),
            'clusters' => $all->where('type', GoalOrgUnit::TYPE_CLUSTER)->count(),
            'branches' => $all->where('type', GoalOrgUnit::TYPE_BRANCH)->count(),
            'active' => $all->where('is_active', true)->count(),
        ];

        $units = $this->filterUnits($all, $filters);
        $units = $this->orderAsTree($units);

        return view('goal.org_units.index', compact('units', 'kpis', 'filters'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->requireGoalPermission('manage_org_units_guard');

        $filters = $this->validatedFilters($request);
        $all = GoalOrgUnit::query()->with('parent')->get();
        $units = $this->orderAsTree($this->filterUnits($all, $filters));
        $filename = 'org-hierarchy-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($units) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Name', 'Code', 'Type', 'Parent', 'Active', 'Children']);

            foreach ($units as $unit) {
                fputcsv($out, [
                    $unit->name,
                    $unit->code,
                    $unit->type,
                    $unit->parent?->name,
                    $unit->is_active ? 'Yes' : 'No',
                    $unit->children_count ?? $unit->children->count(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create()
    {
        $this->requireGoalPermission('manage_org_units_guard');
        $parents = GoalOrgUnit::query()->orderBy('type')->orderBy('name')->get();

        return view('goal.org_units.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $this->requireGoalPermission('manage_org_units_guard');
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50|unique:goal_org_units,code',
            'type' => 'required|in:region,cluster,branch',
            'parent_id' => 'nullable|exists:goal_org_units,id',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $this->assertValidHierarchy($data['type'], $parentId);
        if ($parentId) {
            $parent = GoalOrgUnit::query()->find($parentId);
            if ($parent && (! $parent->is_active || $parent->hasInactiveAncestor())) {
                $data['is_active'] = false;
            }
        }
        GoalOrgUnit::create($data);

        return redirect()->route('goal.org-units.index')->with('success', 'Org unit created.');
    }

    public function edit(GoalOrgUnit $org_unit)
    {
        $this->requireGoalPermission('manage_org_units_guard');
        $parents = GoalOrgUnit::query()
            ->where('id', '!=', $org_unit->id)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('goal.org_units.edit', ['unit' => $org_unit, 'parents' => $parents]);
    }

    public function update(Request $request, GoalOrgUnit $org_unit)
    {
        $this->requireGoalPermission('manage_org_units_guard');
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50|unique:goal_org_units,code,'.$org_unit->id,
            'type' => 'required|in:region,cluster,branch',
            'parent_id' => 'nullable|exists:goal_org_units,id',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $this->assertValidHierarchy(
            $data['type'],
            $parentId,
            (int) $org_unit->id
        );

        if ($parentId) {
            $parent = GoalOrgUnit::query()->find($parentId);
            if ($parent && (! $parent->is_active || $parent->hasInactiveAncestor())) {
                $data['is_active'] = false;
            }
        }

        $wasActive = (bool) $org_unit->is_active;
        $willBeInactive = ! (bool) $data['is_active'];

        if ($wasActive && $willBeInactive) {
            $activeChildren = GoalOrgUnit::query()
                ->whereIn('id', $org_unit->childDescendantIds())
                ->where('is_active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get(['id', 'name', 'type']);

            if ($activeChildren->isNotEmpty()) {
                $names = $activeChildren
                    ->map(fn (GoalOrgUnit $c) => $c->name.' ('.ucfirst($c->type).')')
                    ->implode(', ');

                throw ValidationException::withMessages([
                    'is_active' => 'Cannot inactivate this unit while child units are still active: '.$names.'. Inactivate the child units first.',
                ]);
            }
        }

        $org_unit->update($data);

        return redirect()->route('goal.org-units.index')->with('success', 'Org unit updated.');
    }

    /**
     * Region has no parent; Cluster under Region; Branch under Cluster (or Region).
     * Reject self-parent and cycles on update.
     */
    private function assertValidHierarchy(string $type, ?int $parentId, ?int $selfId = null): void
    {
        if ($selfId && $parentId && $selfId === $parentId) {
            throw ValidationException::withMessages(['parent_id' => 'An org unit cannot be its own parent.']);
        }

        if ($type === GoalOrgUnit::TYPE_REGION) {
            if ($parentId) {
                throw ValidationException::withMessages(['parent_id' => 'Regions cannot have a parent.']);
            }

            return;
        }

        if (! $parentId) {
            throw ValidationException::withMessages([
                'parent_id' => $type === GoalOrgUnit::TYPE_CLUSTER
                    ? 'Clusters must sit under a Region.'
                    : 'Branches must sit under a Cluster (or Region).',
            ]);
        }

        $parent = GoalOrgUnit::query()->find($parentId);
        if (! $parent) {
            throw ValidationException::withMessages(['parent_id' => 'Selected parent was not found.']);
        }

        if ($type === GoalOrgUnit::TYPE_CLUSTER && $parent->type !== GoalOrgUnit::TYPE_REGION) {
            throw ValidationException::withMessages(['parent_id' => 'Cluster parent must be a Region.']);
        }

        if ($type === GoalOrgUnit::TYPE_BRANCH && ! in_array($parent->type, [GoalOrgUnit::TYPE_CLUSTER, GoalOrgUnit::TYPE_REGION], true)) {
            throw ValidationException::withMessages(['parent_id' => 'Branch parent must be a Cluster or Region.']);
        }

        if ($selfId) {
            $descendants = GoalOrgUnit::query()->find($selfId)?->descendantIds() ?? [$selfId];
            if (in_array($parentId, array_map('intval', $descendants), true)) {
                throw ValidationException::withMessages(['parent_id' => 'Cannot set a descendant as parent (cycle).']);
            }
        }
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:100',
            'type' => 'nullable|in:region,cluster,branch',
            'active' => 'nullable|in:1,0,yes,no',
        ]);
    }

    private function filterUnits(Collection $units, array $filters): Collection
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $type = $filters['type'] ?? null;
        $active = $filters['active'] ?? null;

        return $units
            ->when($q !== '', function (Collection $c) use ($q) {
                $needle = mb_strtolower($q);

                return $c->filter(function (GoalOrgUnit $u) use ($needle) {
                    return str_contains(mb_strtolower((string) $u->name), $needle)
                        || str_contains(mb_strtolower((string) $u->code), $needle)
                        || str_contains(mb_strtolower((string) ($u->parent?->name ?? '')), $needle);
                });
            })
            ->when($type, fn (Collection $c) => $c->where('type', $type))
            ->when($active !== null && $active !== '', function (Collection $c) use ($active) {
                $want = in_array($active, ['1', 'yes'], true);

                return $c->where('is_active', $want);
            })
            ->values();
    }

    /**
     * Depth-first: roots → children, preserving Region → Cluster → Branch reading order.
     */
    private function orderAsTree(Collection $units): Collection
    {
        if ($units->isEmpty()) {
            return $units;
        }

        $byParent = $units->groupBy(fn (GoalOrgUnit $u) => $u->parent_id ?: 0);
        $ids = $units->pluck('id')->all();
        $ordered = collect();

        $walk = function ($parentId, int $depth) use (&$walk, $byParent, &$ordered) {
            $children = ($byParent->get($parentId) ?? collect())
                ->sortBy([
                    [fn (GoalOrgUnit $u) => match ($u->type) {
                        GoalOrgUnit::TYPE_REGION => 0,
                        GoalOrgUnit::TYPE_CLUSTER => 1,
                        default => 2,
                    }, 'asc'],
                    ['name', 'asc'],
                ])
                ->values();

            foreach ($children as $child) {
                $child->setAttribute('tree_depth', $depth);
                $child->setAttribute('children_count', $child->children->count());
                $ordered->push($child);
                $walk($child->id, $depth + 1);
            }
        };

        // Roots that appear in the filtered set (parent missing or null)
        $roots = $units->filter(fn (GoalOrgUnit $u) => ! $u->parent_id || ! in_array($u->parent_id, $ids, true))
            ->sortBy([
                [fn (GoalOrgUnit $u) => match ($u->type) {
                    GoalOrgUnit::TYPE_REGION => 0,
                    GoalOrgUnit::TYPE_CLUSTER => 1,
                    default => 2,
                }, 'asc'],
                ['name', 'asc'],
            ]);

        foreach ($roots as $root) {
            $root->setAttribute('tree_depth', 0);
            $root->setAttribute('children_count', $root->children->count());
            $ordered->push($root);
            $walk($root->id, 1);
        }

        // Deduplicate if a unit was both root and visited (shouldn't happen, but safe)
        return $ordered->unique('id')->values();
    }
}
