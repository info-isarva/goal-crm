<?php

namespace App\Http\Controllers;

use App\Models\TaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_tax_guard')) {
            abort(403, 'Unauthorized action.');
        }
    $taxRates = TaxRate::orderBy('name')->paginate(15);
    return view('tax_rates.index', compact('taxRates'));
    }

    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_tax_guard')) {
            abort(403, 'Unauthorized action.');
        }
        return view('tax_rates.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_tax_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'rate' => [
                'required',
                'numeric',
                'between:1,100',
            ],
            'type' => 'required|in:percentage,fixed',
            'country' => 'nullable|string|max:100',
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'rate.between' => 'Rate must be a number between 1 and 100.',
        ]);
        $validated['created_by'] = auth()->id();
        $validated['created_at'] = now();
        TaxRate::create($validated);
        return redirect()->route('tax_rates.index')->with('success', 'Tax rate created successfully!');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_tax_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $taxRate = TaxRate::findOrFail($id);
        return view('tax_rates.edit', compact('taxRate'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_tax_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $taxRate = TaxRate::findOrFail($id);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
            ],
            'rate' => [
                'required',
                'numeric',
                'between:1,100',
            ],
            'type' => 'required|in:percentage,fixed',
            'country' => 'nullable|string|max:100',
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'rate.between' => 'Rate must be a number between 1 and 100.',
        ]);
        $validated['updated_by'] = auth()->id();
        $validated['updated_at'] = now();
        $taxRate->update($validated);
        return redirect()->route('tax_rates.index')->with('success', 'Tax rate updated successfully!');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_tax_guard')) {
            abort(403, 'Unauthorized action.');
        }
        $taxRate = TaxRate::findOrFail($id);
        $taxRate->delete();
        return redirect()->route('tax_rates.index')->with('success', 'Tax rate deleted successfully!');
    }
}
