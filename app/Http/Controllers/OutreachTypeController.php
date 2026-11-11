<?php

namespace App\Http\Controllers;

use App\Models\OutreachType;
use Illuminate\Http\Request;

class OutreachTypeController extends Controller
{
    protected function allow(string $permission): void
    {
        $user = auth()->user();
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return;
        }
        if (!$user->hasCrmPermission($permission) && !$user->hasCrmPermission('manage_crm_outreach_type_guard')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->allow('manage_crm_outreach_type_guard');

        $search = trim((string) $request->query('search', ''));

        $outreachTypes = OutreachType::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->ordered()
            ->paginate(20)
            ->appends($request->query());

        return view('outreach_types.index', compact('outreachTypes'));
    }

    public function create()
    {
        $this->allow('create_crm_outreach_type_guard');

        return view('outreach_types.create');
    }

    public function store(Request $request)
    {
        $this->allow('create_crm_outreach_type_guard');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:outreach_types,name',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        OutreachType::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('outreach_types.index')->with('success', 'Outreach type created successfully!');
    }

    public function edit($id)
    {
        $this->allow('edit_crm_outreach_type_guard');

        $outreachType = OutreachType::findOrFail($id);

        return view('outreach_types.edit', compact('outreachType'));
    }

    public function update(Request $request, $id)
    {
        $this->allow('edit_crm_outreach_type_guard');

        $outreachType = OutreachType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:outreach_types,name,' . $outreachType->id,
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $outreachType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('outreach_types.index')->with('success', 'Outreach type updated successfully!');
    }

    public function destroy($id)
    {
        $this->allow('delete_crm_outreach_type_guard');

        $outreachType = OutreachType::findOrFail($id);
        $outreachType->delete();

        return redirect()->route('outreach_types.index')->with('success', 'Outreach type deleted successfully!');
    }
}
