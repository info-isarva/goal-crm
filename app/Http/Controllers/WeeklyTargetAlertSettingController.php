<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class WeeklyTargetAlertSettingController extends Controller
{
    protected function ensureAdmin(): void
    {
        if (!in_array((int) (auth()->user()->crm_role_type ?? -1), [0, 1], true)) {
            abort(403, 'Only Admin can manage weekly target alert timings.');
        }
    }

    public function edit()
    {
        $this->ensureAdmin();

        $company = Company::query()->first() ?? new Company();

        return view('settings.weekly_target_alert', [
            'company' => $company,
            'enabled' => old('weekly_target_alert_enabled', $company->isWeeklyTargetAlertEnabled()),
            'morning' => old('weekly_target_alert_morning', $company->weekly_target_alert_morning ?: '09:00'),
            'afternoon' => old('weekly_target_alert_afternoon', $company->weekly_target_alert_afternoon ?: '14:00'),
            'evening' => old('weekly_target_alert_evening', $company->weekly_target_alert_evening ?: '18:00'),
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'weekly_target_alert_enabled' => 'nullable|boolean',
            'weekly_target_alert_morning' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'weekly_target_alert_afternoon' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'weekly_target_alert_evening' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ], [
            'weekly_target_alert_morning.regex' => 'Morning time must be HH:MM (24-hour).',
            'weekly_target_alert_afternoon.regex' => 'Afternoon time must be HH:MM (24-hour).',
            'weekly_target_alert_evening.regex' => 'Evening time must be HH:MM (24-hour).',
        ]);

        $morning = $validated['weekly_target_alert_morning'];
        $afternoon = $validated['weekly_target_alert_afternoon'];
        $evening = $validated['weekly_target_alert_evening'];

        if ($morning >= $afternoon || $afternoon >= $evening) {
            return back()
                ->withInput()
                ->withErrors([
                    'weekly_target_alert_afternoon' => 'Set times in order: Morning < Afternoon < Evening.',
                ]);
        }

        $company = Company::query()->first();
        if (!$company) {
            $company = new Company();
            $company->name = config('app.name', 'CRM');
        }

        $company->weekly_target_alert_enabled = $request->boolean('weekly_target_alert_enabled');
        $company->weekly_target_alert_morning = $morning;
        $company->weekly_target_alert_afternoon = $afternoon;
        $company->weekly_target_alert_evening = $evening;
        $company->save();

        return redirect()
            ->route('weekly_target_alert.settings.edit')
            ->with('success', 'Weekly target alert timings saved successfully.');
    }
}
