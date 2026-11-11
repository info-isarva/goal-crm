<?php

namespace App\Http\Controllers;

use App\Models\ProductCategoryType;
use Illuminate\Http\Request;

class ProductCategoryTypeController extends Controller
{
    protected function allow(string $permission): void
    {
        $user = auth()->user();
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return;
        }
        if (
            !$user->hasCrmPermission($permission)
            && !$user->hasCrmPermission('manage_crm_product_category_guard')
        ) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->allow('manage_crm_product_category_guard');

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        $types = ProductCategoryType::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', (int) $status === 1);
            })
            ->ordered()
            ->paginate(20)
            ->appends($request->query());

        return view('product_category_types.index', compact('types'));
    }

    public function create()
    {
        $this->allow('create_crm_product_category_guard');

        return view('product_category_types.create');
    }

    public function store(Request $request)
    {
        $this->allow('create_crm_product_category_guard');

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_category_types,name',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        ProductCategoryType::create([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('product_category_types.index')
            ->with('success', 'Product category type created successfully!');
    }

    public function edit($id)
    {
        $this->allow('edit_crm_product_category_guard');

        $type = ProductCategoryType::findOrFail($id);

        return view('product_category_types.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        $this->allow('edit_crm_product_category_guard');

        $type = ProductCategoryType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_category_types,name,' . $type->id,
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $type->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('product_category_types.index')
            ->with('success', 'Product category type updated successfully!');
    }

    public function destroy($id)
    {
        $this->allow('delete_crm_product_category_guard');

        $type = ProductCategoryType::findOrFail($id);
        $type->delete();

        return redirect()
            ->route('product_category_types.index')
            ->with('success', 'Product category type deleted successfully!');
    }
}
