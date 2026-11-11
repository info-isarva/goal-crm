<?php

namespace App\Http\Controllers;

use App\Models\MainCategory;
use Illuminate\Http\Request;

class MainCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_main_category_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        $categories = MainCategory::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('category_name', 'like', '%' . $search . '%');
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('status', (int) $status);
            })
            ->orderBy('category_name')
            ->paginate(15)
            ->appends($request->query());

        return view('main_categories.index', compact('categories'));
    }

    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_main_category_guard')) {
            abort(403, 'Unauthorized action.');
        }

        return view('main_categories.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_main_category_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:150',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
                'unique:main_categories,category_name',
            ],
            'status' => 'required|boolean',
        ], [
            'category_name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'category_name.unique' => 'Main category name already exists.',
        ]);

        MainCategory::create($validated);

        return redirect()->route('main_categories.index')->with('success', 'Main category created successfully!');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_main_category_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $category = MainCategory::findOrFail($id);
        return view('main_categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_main_category_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $category = MainCategory::findOrFail($id);

        $validated = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:150',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
                'unique:main_categories,category_name,' . $category->id,
            ],
            'status' => 'required|boolean',
        ], [
            'category_name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'category_name.unique' => 'Main category name already exists.',
        ]);

        $category->update($validated);

        return redirect()->route('main_categories.index')->with('success', 'Main category updated successfully!');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_main_category_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $category = MainCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('main_categories.index')->with('success', 'Main category deleted successfully!');
    }
}
