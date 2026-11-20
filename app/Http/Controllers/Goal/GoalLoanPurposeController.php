<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Models\GoalLoanPurpose;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GoalLoanPurposeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeMasters();

        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'active' => 'nullable|in:1,0',
        ]);

        $query = GoalLoanPurpose::query()->orderBy('sort_order')->orderBy('name');

        if (! empty($filters['q'])) {
            $q = trim($filters['q']);
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%');
            });
        }

        if (array_key_exists('active', $filters) && $filters['active'] !== null && $filters['active'] !== '') {
            $query->where('is_active', $filters['active'] === '1');
        }

        $purposes = $query->paginate(25)->withQueryString();
        $kpis = [
            'total' => GoalLoanPurpose::query()->count(),
            'active' => GoalLoanPurpose::query()->where('is_active', true)->count(),
            'inactive' => GoalLoanPurpose::query()->where('is_active', false)->count(),
        ];

        return view('goal.masters.purposes', compact('purposes', 'kpis', 'filters'));
    }

    public function store(Request $request)
    {
        $this->authorizeMasters();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $name = trim($data['name']);
        $existing = GoalLoanPurpose::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill([
                'name' => $name,
                'description' => $data['description'] ?? $existing->description,
                'sort_order' => $data['sort_order'] ?? $existing->sort_order,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            ])->save();

            return redirect()
                ->route('goal.masters.purposes.index')
                ->with('success', 'Purpose restored / updated.');
        }

        $maxSort = (int) GoalLoanPurpose::query()->max('sort_order');
        GoalLoanPurpose::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? ($maxSort + 10),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ]);

        return redirect()
            ->route('goal.masters.purposes.index')
            ->with('success', 'Purpose added.');
    }

    public function update(Request $request, GoalLoanPurpose $purpose)
    {
        $this->authorizeMasters();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('goal_loan_purposes', 'name')->ignore($purpose->id),
            ],
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $purpose->fill([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? $purpose->sort_order,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $purpose->is_active,
        ])->save();

        return redirect()
            ->route('goal.masters.purposes.index')
            ->with('success', 'Purpose updated.');
    }

    protected function authorizeMasters(): void
    {
        $user = auth()->user();
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return;
        }
        if ($user->hasCrmPermission('manage_purposes_guard')) {
            return;
        }
        abort(403, 'You do not have permission to manage purposes.');
    }
}
