<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\ProductCategoryType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    /**
     * Active types from product_category_types (plus current value when editing legacy rows).
     */
    protected function typeOptions(?string $includeType = null): Collection
    {
        $types = ProductCategoryType::query()
            ->active()
            ->ordered()
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values();

        if ($includeType !== null && $includeType !== '' && !$types->contains($includeType)) {
            $types->push($includeType);
        }

        return $types;
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_product_category_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $type = trim((string) $request->query('type', ''));

        $query = ProductCategory::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('category_name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('type', 'like', '%' . $search . '%');
                });
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                $q->where('status', (int) $status);
            })
            ->when($type !== '', function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->orderBy('category_name');

        $categories = $query->paginate(15)->appends($request->query());
        $types = $this->typeOptions();

        return view('product_categories.index', compact('categories', 'types'));
    }

    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_product_category_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $types = $this->typeOptions();

        return view('product_categories.create', compact('types'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_product_category_guard')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->input('type') === '') {
            $request->merge(['type' => null]);
        }

        $allowedTypes = $this->typeOptions()->all();

        $validated = $request->validate([
            'category_name' => [
                'required', 'string', 'max:150',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'description' => 'nullable|string',
            'type' => ['nullable', 'string', 'max:50', Rule::in($allowedTypes)],
            'status' => 'required|boolean',
        ], [
            'category_name.regex' => 'Product Category must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'type.in' => 'Please select a valid type.',
        ]);
        $validated['created_by'] = auth()->id();
        $validated['created_at'] = now();
        ProductCategory::create($validated);
        return redirect()->route('product_categories.index')->with('success', 'Product category created successfully!');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_product_category_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $category = ProductCategory::findOrFail($id);
        $types = $this->typeOptions($category->type);

        return view('product_categories.edit', compact('category', 'types'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_product_category_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $category = ProductCategory::findOrFail($id);

        if ($request->input('type') === '') {
            $request->merge(['type' => null]);
        }

        $allowedTypes = $this->typeOptions($category->type)->all();

        $validated = $request->validate([
            'category_name' => [
                'required', 'string', 'max:150',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'description' => 'nullable|string',
            'type' => ['nullable', 'string', 'max:50', Rule::in($allowedTypes)],
            'status' => 'required|boolean',
        ], [
            'category_name.regex' => 'Product Category must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'type.in' => 'Please select a valid type.',
        ]);
        $validated['updated_by'] = auth()->id();
        $validated['updated_at'] = now();
        $category->update($validated);
        return redirect()->route('product_categories.index')->with('success', 'Product category updated successfully!');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_product_category_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $category = ProductCategory::findOrFail($id);
        $category->deleted_by = auth()->id();
        $category->deleted_at = now();
        $category->save();
        $category->delete();
        return redirect()->route('product_categories.index')->with('success', 'Product category deleted successfully!');
    }
}
