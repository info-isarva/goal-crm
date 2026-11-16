<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalOrgUnit;
use Illuminate\Http\Request;

class GoalOrgUnitController extends Controller
{
    use AuthorizesGoal;

    public function index()
    {
        $this->requireGoalPermission('manage_org_units_guard');
        $units = GoalOrgUnit::query()->with('parent')->orderBy('type')->orderBy('name')->get();
        return view('goal.org_units.index', compact('units'));
    }

    public function create()
    {
        $this->requireGoalPermission('manage_org_units_guard');
        $parents = GoalOrgUnit::query()->orderBy('name')->get();
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
        GoalOrgUnit::create($data);
        return redirect()->route('goal.org-units.index')->with('success', 'Org unit created.');
    }

    public function edit(GoalOrgUnit $org_unit)
    {
        $this->requireGoalPermission('manage_org_units_guard');
        $parents = GoalOrgUnit::query()->where('id', '!=', $org_unit->id)->orderBy('name')->get();
        return view('goal.org_units.edit', ['unit' => $org_unit, 'parents' => $parents]);
    }

    public function update(Request $request, GoalOrgUnit $org_unit)
    {
        $this->requireGoalPermission('manage_org_units_guard');
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50|unique:goal_org_units,code,' . $org_unit->id,
            'type' => 'required|in:region,cluster,branch',
            'parent_id' => 'nullable|exists:goal_org_units,id',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $org_unit->update($data);
        return redirect()->route('goal.org-units.index')->with('success', 'Org unit updated.');
    }
}
