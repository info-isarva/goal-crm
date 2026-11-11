<?php

namespace App\Http\Controllers;

use App\Models\LeadSource;
use Illuminate\Http\Request;

class LeadSourceController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_lead_source_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $search = trim((string) $request->query('search', ''));
        $descriptionFilter = $request->query('description_filter', '');

        $leadSources = LeadSource::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($descriptionFilter === 'with', function ($query) {
                $query->whereNotNull('description')->where('description', '!=', '');
            })
            ->when($descriptionFilter === 'without', function ($query) {
                $query->where(function ($inner) {
                    $inner->whereNull('description')->orWhere('description', '');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->appends($request->query());

        return view('lead_sources.index', compact('leadSources'));
    }

    public function create()
    {
        if (!auth()->user()->hasCrmPermission('create_crm_lead_source_guard')) {
            abort(403, 'Unauthorized action.');
        }

        return view('lead_sources.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('create_crm_lead_source_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
                'unique:lead_sources,name',
            ],
            'description' => 'nullable|string|max:255',
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'name.unique' => 'Lead source name already exists.',
        ]);

        LeadSource::create($validated);

        return redirect()->route('lead_sources.index')->with('success', 'Lead source created successfully!');
    }

    public function edit($id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_lead_source_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $leadSource = LeadSource::findOrFail($id);
        return view('lead_sources.edit', compact('leadSource'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasCrmPermission('edit_crm_lead_source_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $leadSource = LeadSource::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z][A-Za-z .\'-]*$/',
                'unique:lead_sources,name,' . $leadSource->id,
            ],
            'description' => 'nullable|string|max:255',
        ], [
            'name.regex' => 'Name must start with a letter and may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'name.unique' => 'Lead source name already exists.',
        ]);

        $leadSource->update($validated);

        return redirect()->route('lead_sources.index')->with('success', 'Lead source updated successfully!');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasCrmPermission('delete_crm_lead_source_guard')) {
            abort(403, 'Unauthorized action.');
        }

        $leadSource = LeadSource::findOrFail($id);
        $leadSource->delete();

        return redirect()->route('lead_sources.index')->with('success', 'Lead source deleted successfully!');
    }
}
