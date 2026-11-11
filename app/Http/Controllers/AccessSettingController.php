<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;

class AccessSettingController extends Controller
{
    protected function ensureSuperAdmin(): void
    {
        if ((int) (auth()->user()->crm_role_type ?? -1) !== 0) {
            abort(403, 'Only Super Admin can manage access settings.');
        }
    }

    public function edit()
    {
        $this->ensureSuperAdmin();

        $company = Company::query()->first() ?? new Company();
        $permissions = Permission::with('parentPermission')->orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy(function ($item) {
            return $item->parentPermission ? $item->parentPermission->name : 'Other';
        });

        $selectedGuards = old(
            'admin_assignable_permissions',
            $company->adminAssignableGuards() ?? []
        );

        $userCount = User::countNonSuperAdmins();
        $maxUsers = $company->max_users;
        $emailSendEnabled = old('email_send_enabled', $company->isEmailSendEnabled());
        $whatsappSendEnabled = old('whatsapp_send_enabled', $company->isWhatsappSendEnabled());
        $webFormsEnabled = old('web_forms_enabled', $company->isWebFormsEnabled());
        $socialAdsEnabled = old('social_ads_enabled', $company->isSocialAdsEnabled());

        $sendPermissionGuards = [
            'send_email_crm_quotations_guard',
            'send_whatsapp_crm_quotations_guard',
        ];

        return view('settings.access', compact(
            'company',
            'groupedPermissions',
            'selectedGuards',
            'userCount',
            'maxUsers',
            'emailSendEnabled',
            'whatsappSendEnabled',
            'webFormsEnabled',
            'socialAdsEnabled',
            'sendPermissionGuards'
        ));
    }

    public function update(Request $request)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'max_users' => 'nullable|integer|min:1|max:100000',
            'email_send_enabled' => 'nullable|boolean',
            'whatsapp_send_enabled' => 'nullable|boolean',
            'web_forms_enabled' => 'nullable|boolean',
            'social_ads_enabled' => 'nullable|boolean',
            'admin_assignable_permissions' => 'nullable|array',
            'admin_assignable_permissions.*' => 'string|exists:permissions,guard_name',
        ], [
            'max_users.min' => 'User create limit must be at least 1.',
            'admin_assignable_permissions.*.exists' => 'One or more selected permissions are invalid.',
        ]);

        $company = Company::query()->first();
        if (!$company) {
            $company = new Company();
            $company->name = config('app.name', 'CRM');
        }

        $webFormsEnabled = $request->boolean('web_forms_enabled');
        $socialAdsEnabled = $request->boolean('social_ads_enabled');

        $company->max_users = $validated['max_users'] ?? null;
        $company->email_send_enabled = $request->boolean('email_send_enabled');
        $company->whatsapp_send_enabled = $request->boolean('whatsapp_send_enabled');
        $company->web_forms_enabled = $webFormsEnabled;
        $company->social_ads_enabled = $socialAdsEnabled;

        $assignable = $validated['admin_assignable_permissions'] ?? [];
        if (!$webFormsEnabled) {
            $assignable = array_values(array_filter($assignable, fn ($g) => !Permission::isWebFormsGuard($g)));
        } else {
            $hasWf = array_filter($assignable, fn ($g) => Permission::isWebFormsGuard($g));
            if (empty($hasWf)) {
                $assignable = array_merge($assignable, Permission::webFormsGuards());
            }
        }

        if (!$socialAdsEnabled) {
            $assignable = array_values(array_filter($assignable, fn ($g) => !Permission::isSocialAdsGuard($g)));
        } else {
            $hasSa = array_filter($assignable, fn ($g) => Permission::isSocialAdsGuard($g));
            if (empty($hasSa)) {
                $assignable = array_merge($assignable, Permission::socialAdsGuards());
            }
        }

        $company->admin_assignable_permissions = array_values(array_unique($assignable));
        $company->save();

        return redirect()
            ->route('access.settings.edit')
            ->with('success', 'Access settings saved successfully.');
    }
}
