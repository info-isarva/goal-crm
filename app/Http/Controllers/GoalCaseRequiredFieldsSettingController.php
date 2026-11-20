<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Goal\GoalCaseRequiredFields;
use Illuminate\Http\Request;

class GoalCaseRequiredFieldsSettingController extends Controller
{
    protected function ensureSuperAdmin(): void
    {
        if ((int) (auth()->user()->crm_role_type ?? -1) !== 0) {
            abort(403, 'Only Super Admin can manage case required fields.');
        }
    }

    public function edit()
    {
        $this->ensureSuperAdmin();

        $company = Company::query()->first() ?? new Company();
        $map = GoalCaseRequiredFields::map($company);
        $catalog = GoalCaseRequiredFields::catalog();

        $groups = [];
        foreach ($catalog as $key => $meta) {
            $groups[$meta['group']][$key] = $meta;
        }

        return view('settings.goal_case_required_fields', [
            'company' => $company,
            'map' => $map,
            'groups' => $groups,
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureSuperAdmin();

        $allowed = array_keys(GoalCaseRequiredFields::catalog());
        $incoming = $request->input('required', []);
        if (! is_array($incoming)) {
            $incoming = [];
        }
        $incoming = array_intersect_key($incoming, array_flip($allowed));

        $normalized = GoalCaseRequiredFields::normalizeFromRequest($incoming);

        $company = Company::query()->first();
        if (! $company) {
            $company = new Company();
            $company->name = config('app.name', 'CRM');
        }

        $company->goal_case_required_fields = $normalized;
        $company->save();

        return redirect()
            ->route('goal_case_required_fields.settings.edit')
            ->with('success', 'Loan Case required fields saved.');
    }
}
