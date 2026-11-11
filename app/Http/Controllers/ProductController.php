<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_product_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $products = Product::with(['category', 'owner', 'taxRate'])->orderBy('product_name')->get();
        return view('products.index', compact('products'));
    }

    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_product_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $categories = ProductCategory::orderBy('category_name')->get();
        $taxRates = TaxRate::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $currentUser = auth()->user();
        return view('products.create', compact('categories', 'taxRates', 'users', 'currentUser'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_product_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'product_name' => 'required|string|max:150',
            'product_code' => 'required|string|max:150|unique:products,product_code',
            'product_type' => 'required|in:default,product',
            'product_owner_id' => 'required|exists:users,id',
            'product_status' => 'boolean',
            'product_category_id' => 'required|exists:product_categories,id',
            'unit_price' => 'required|numeric',
            'commission_rate' => 'nullable|numeric',
            'tax_status' => 'boolean',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'product_description' => 'nullable|string',
            'project_detail' => 'nullable|string',
        ], [
            'product_code.unique' => 'The product code has already been taken. Please use a different code.',
        ]);
        $validated['created_by'] = auth()->id();
        $validated['created_at'] = now();
        $validated['updated_by'] = auth()->id();
        $validated['tax_status'] = $request->boolean('tax_status');
        $taxRateId = $validated['tax_status'] ? ($validated['tax_rate_id'] ?? null) : null;
        unset($validated['tax_rate_id']);
        $product = Product::create($validated);
        if ($taxRateId) {
            \DB::table('product_tax')->insert([
                'product_id' => $product->id,
                'tax_id' => $taxRateId,
            ]);
        }
        return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_product_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $product = Product::findOrFail($id);
        $categories = ProductCategory::orderBy('category_name')->get();
        $taxRates = TaxRate::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        // Fetch the tax id from product_tax table
        $productTax = \DB::table('product_tax')->where('product_id', $product->id)->first();
        $product->tax_rate_id = $productTax ? $productTax->tax_id : null;
        return view('products.edit', compact('product', 'categories', 'taxRates', 'users'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_product_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'product_name' => 'required|string|max:150',
            'product_code' => 'required|string|max:150|unique:products,product_code,' . $id,
            'product_type' => 'required|in:default,product',
            'product_owner_id' => 'required|exists:users,id',
            'product_status' => 'boolean',
            'product_category_id' => 'required|exists:product_categories,id',
            'unit_price' => 'required|numeric',
            'commission_rate' => 'nullable|numeric',
            'tax_status' => 'boolean',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'product_description' => 'nullable|string',
            'project_detail' => 'nullable|string',
        ], [
            'product_code.unique' => 'The product code has already been taken. Please use a different code.',
        ]);
        $validated['updated_by'] = auth()->id();
        $validated['updated_at'] = now();
        $validated['tax_status'] = $request->boolean('tax_status');
        $taxRateId = $validated['tax_status'] ? ($validated['tax_rate_id'] ?? null) : null;
        unset($validated['tax_rate_id']);
        $product->update($validated);
        // Update product_tax table
        \DB::table('product_tax')->where('product_id', $product->id)->delete();
        if ($taxRateId) {
            \DB::table('product_tax')->insert([
                'product_id' => $product->id,
                'tax_id' => $taxRateId
            ]);
        }
        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_product_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $product = Product::findOrFail($id);
        $product->deleted_by = auth()->id();
        $product->deleted_at = now();
        $product->save();
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully!');
    }

    public function uploadEditorImage(Request $request)
    {
        if (
            !auth()->user()->hasCrmPermission('create_crm_product_guard')
            && !auth()->user()->hasCrmPermission('edit_crm_product_guard')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        $file = $request->file('file');
        $dir = 'product-editor/' . auth()->id();
        $path = $file->store($dir, 'public');

        return response()->json([
            'location' => asset('storage/' . $path),
        ]);
    }
}
