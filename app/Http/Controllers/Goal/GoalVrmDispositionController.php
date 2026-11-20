<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Goal\Concerns\AuthorizesGoal;
use App\Models\GoalVrmDisposition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GoalVrmDispositionController extends Controller
{
    use AuthorizesGoal;

    public function index(Request $request)
    {
        $this->authorizeMasters();

        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'active' => 'nullable|in:1,0',
        ]);

        $query = GoalVrmDisposition::query()->orderBy('sort_order')->orderBy('name');

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
            });
        }

        if (array_key_exists('active', $filters) && $filters['active'] !== null && $filters['active'] !== '') {
            $query->where('is_active', $filters['active'] === '1');
        }

        $dispositions = $query->paginate(25)->withQueryString();
        $kpis = [
            'total' => GoalVrmDisposition::query()->count(),
            'active' => GoalVrmDisposition::query()->where('is_active', true)->count(),
            'creates_lead' => GoalVrmDisposition::query()->where('creates_lead', true)->where('is_active', true)->count(),
        ];

        return view('goal.masters.dispositions', compact('dispositions', 'kpis', 'filters'));
    }

    public function store(Request $request)
    {
        $this->authorizeMasters();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'creates_lead' => 'nullable|boolean',
            'maps_to_lead_type' => 'nullable|in:immediate,future',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $name = trim($data['name']);
        $existing = GoalVrmDisposition::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill([
                'name' => $name,
                'creates_lead' => (bool) ($data['creates_lead'] ?? false),
                'maps_to_lead_type' => $data['maps_to_lead_type'] ?? null,
                'sort_order' => $data['sort_order'] ?? $existing->sort_order,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            ])->save();

            return redirect()
                ->route('goal.masters.dispositions.index')
                ->with('success', 'Disposition restored / updated.');
        }

        $maxSort = (int) GoalVrmDisposition::query()->max('sort_order');
        GoalVrmDisposition::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'creates_lead' => (bool) ($data['creates_lead'] ?? false),
            'maps_to_lead_type' => $data['maps_to_lead_type'] ?? null,
            'sort_order' => $data['sort_order'] ?? ($maxSort + 10),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ]);

        return redirect()
            ->route('goal.masters.dispositions.index')
            ->with('success', 'Disposition added.');
    }

    public function update(Request $request, GoalVrmDisposition $disposition)
    {
        $this->authorizeMasters();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('goal_vrm_dispositions', 'name')->ignore($disposition->id),
            ],
            'creates_lead' => 'nullable|boolean',
            'maps_to_lead_type' => 'nullable|in:immediate,future',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $disposition->fill([
            'name' => trim($data['name']),
            'creates_lead' => (bool) ($data['creates_lead'] ?? false),
            'maps_to_lead_type' => $data['maps_to_lead_type'] ?? null,
            'sort_order' => $data['sort_order'] ?? $disposition->sort_order,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $disposition->is_active,
        ])->save();

        return redirect()
            ->route('goal.masters.dispositions.index')
            ->with('success', 'Disposition updated.');
    }

    protected function authorizeMasters(): void
    {
        $user = auth()->user();
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return;
        }
        if ($user->hasCrmPermission('manage_dispositions_guard')) {
            return;
        }
        abort(403, 'You do not have permission to manage dispositions.');
    }
}
