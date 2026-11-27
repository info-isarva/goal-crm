<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Models\GoalBank;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GoalBankController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeMasters();

        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'active' => 'nullable|in:1,0',
        ]);

        $query = GoalBank::query()->orderBy('sort_order')->orderBy('name');

        if (! empty($filters['q'])) {
            $q = trim($filters['q']);
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('code', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%');
            });
        }

        if (array_key_exists('active', $filters) && $filters['active'] !== null && $filters['active'] !== '') {
            $query->where('is_active', $filters['active'] === '1');
        }

        $banks = $query->paginate(25)->withQueryString();
        $kpis = [
            'total' => GoalBank::query()->count(),
            'active' => GoalBank::query()->where('is_active', true)->count(),
            'inactive' => GoalBank::query()->where('is_active', false)->count(),
        ];

        return view('goal.masters.banks', compact('banks', 'kpis', 'filters'));
    }

    public function store(Request $request)
    {
        $this->authorizeMasters();

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:40',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $name = trim($data['name']);
        $code = Str::upper(trim($data['code']));

        $existing = GoalBank::withTrashed()
            ->where(function ($q) use ($name, $code) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhereRaw('UPPER(code) = ?', [$code]);
            })
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill([
                'name' => $name,
                'code' => $code,
                'description' => $data['description'] ?? $existing->description,
                'sort_order' => $data['sort_order'] ?? $existing->sort_order,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            ])->save();

            return redirect()
                ->route('goal.masters.banks.index')
                ->with('success', 'Bank restored / updated.');
        }

        $maxSort = (int) GoalBank::query()->max('sort_order');
        GoalBank::create([
            'name' => $name,
            'code' => $code,
            'slug' => Str::slug($name),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? ($maxSort + 10),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ]);

        return redirect()
            ->route('goal.masters.banks.index')
            ->with('success', 'Bank added.');
    }

    public function update(Request $request, GoalBank $bank)
    {
        $this->authorizeMasters();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('goal_banks', 'name')->ignore($bank->id),
            ],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('goal_banks', 'code')->ignore($bank->id),
            ],
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $bank->fill([
            'name' => trim($data['name']),
            'code' => Str::upper(trim($data['code'])),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? $bank->sort_order,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $bank->is_active,
        ])->save();

        return redirect()
            ->route('goal.masters.banks.index')
            ->with('success', 'Bank updated.');
    }

    /**
     * AJAX search for Recommendation Suggested Bank (name or code).
     */
    public function search(Request $request)
    {
        $user = auth()->user();
        $allowed = (int) ($user->crm_role_type ?? -1) === 0
            || $user->hasCrmPermission('edit_case_workspace_guard')
            || $user->hasCrmPermission('manage_banks_guard')
            || $user->hasCrmPermission('view_loan_cases_guard');

        if (! $allowed) {
            abort(403);
        }

        $term = trim((string) $request->query('q', ''));

        return response()->json([
            'ok' => true,
            'results' => GoalBank::searchResults($term),
        ]);
    }

    protected function authorizeMasters(): void
    {
        $user = auth()->user();
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return;
        }
        if ($user->hasCrmPermission('manage_banks_guard')) {
            return;
        }
        abort(403, 'You do not have permission to manage banks.');
    }
}
