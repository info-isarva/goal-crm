<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GoalLeadSourceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeMasters();

        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'trashed' => 'nullable|in:1,0',
        ]);

        $query = LeadSource::query()->orderBy('name');

        if (($filters['trashed'] ?? '') === '1') {
            $query->onlyTrashed();
        }

        if (! empty($filters['q'])) {
            $q = trim($filters['q']);
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%');
            });
        }

        $sources = $query->paginate(25)->withQueryString();
        $kpis = [
            'total' => LeadSource::withTrashed()->count(),
            'active' => LeadSource::query()->count(),
            'inactive' => LeadSource::onlyTrashed()->count(),
        ];

        return view('goal.masters.sources', compact('sources', 'kpis', 'filters'));
    }

    public function store(Request $request)
    {
        $this->authorizeMasters();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z][A-Za-z0-9 .\'\-]*$/',
                Rule::unique('lead_sources', 'name')->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string|max:255',
        ], [
            'name.regex' => 'Name must start with a letter.',
            'name.unique' => 'Source name already exists.',
        ]);

        $name = trim($data['name']);
        $existing = LeadSource::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill([
                'name' => $name,
                'description' => $data['description'] ?? $existing->description,
            ])->save();

            return redirect()
                ->route('goal.masters.sources.index')
                ->with('success', 'Source restored / updated.');
        }

        LeadSource::create([
            'name' => $name,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()
            ->route('goal.masters.sources.index')
            ->with('success', 'Source added.');
    }

    public function update(Request $request, LeadSource $source)
    {
        $this->authorizeMasters();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z][A-Za-z0-9 .\'\-]*$/',
                Rule::unique('lead_sources', 'name')->ignore($source->id)->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ], [
            'name.regex' => 'Name must start with a letter.',
            'name.unique' => 'Source name already exists.',
        ]);

        $source->fill([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
        ])->save();

        $wantActive = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;

        if (! $wantActive && ! $source->trashed()) {
            $source->delete();
        } elseif ($wantActive && $source->trashed()) {
            $source->restore();
        }

        return redirect()
            ->route('goal.masters.sources.index')
            ->with('success', 'Source updated.');
    }

    public function restore(int $source)
    {
        $this->authorizeMasters();

        $row = LeadSource::onlyTrashed()->findOrFail($source);
        $row->restore();

        return redirect()
            ->route('goal.masters.sources.index')
            ->with('success', 'Source restored.');
    }

    protected function authorizeMasters(): void
    {
        $user = auth()->user();
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return;
        }
        if ($user->hasCrmPermission('manage_sources_guard')
            || $user->hasCrmPermission('manage_crm_lead_source_guard')) {
            return;
        }
        abort(403, 'You do not have permission to manage sources.');
    }
}
