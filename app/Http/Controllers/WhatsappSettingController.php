<?php

namespace App\Http\Controllers;

use App\Models\WhatsappSetting;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class WhatsappSettingController extends Controller
{
    /**
     * Display the WhatsApp settings view (Super Admin only).
     */
    public function edit()
    {
        $user = auth()->user();
        if (!$user || $user->crm_role_type !== 0) {
            abort(403, 'Unauthorized access. Only Super Admin can access WhatsApp Settings.');
        }

        $canEdit = true;
        $isarvaSetting = WhatsappSetting::getSettings('isarva');
        $otherSetting  = WhatsappSetting::getSettings('other');

        // Active provider is 'other' if otherSetting exists and is active, else 'isarva'
        $activeProvider = ($otherSetting->exists && $otherSetting->is_active) ? 'other' : 'isarva';

        $setting = ($activeProvider === 'other') ? $otherSetting : $isarvaSetting;

        return view('settings.whatsapp', compact('isarvaSetting', 'otherSetting', 'activeProvider', 'setting', 'canEdit'));
    }

    /**
     * Update WhatsApp integration settings (Super Admin only).
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->crm_role_type !== 0) {
            abort(403, 'Unauthorized action. Only Super Admin can modify WhatsApp Settings.');
        }

        $validated = $request->validate([
            'provider_type'            => 'required|in:isarva,other',
            'whatsapp_access_token'    => 'nullable|string',
            'whatsapp_phone_number_id' => 'nullable|string|max:100',
            'whatsapp_api_url'         => 'required|url|max:255',
            'whatsapp_api_version'     => 'nullable|string|max:50',
            'whatsapp_template_id'     => 'nullable|string|max:150',
            'quotation_template_id'    => 'nullable|string|max:150',
            'quotation_language_code'  => 'nullable|string|max:20',
            'quotation_message_format' => 'nullable|string|max:2000',
            'whatsapp_language_code'   => 'nullable|string|max:20',
            'is_active'                => 'nullable|boolean',
        ]);

        $provider = $validated['provider_type'];
        $isActive = $request->has('is_active') ? 1 : 0;

        // Find or create record for the selected provider_type
        $setting = WhatsappSetting::firstOrNew(['provider_type' => $provider]);
        $setting->whatsapp_access_token    = $validated['whatsapp_access_token'];
        $setting->whatsapp_phone_number_id = $validated['whatsapp_phone_number_id'];
        $setting->whatsapp_api_url         = $validated['whatsapp_api_url'];
        $setting->whatsapp_api_version     = $validated['whatsapp_api_version'] ?? ($provider === 'other' ? 'v20.0' : 'v20.0');
        $setting->whatsapp_template_id     = $validated['whatsapp_template_id'];
        $setting->quotation_template_id    = $validated['quotation_template_id'] ?? null;
        $setting->quotation_language_code  = $validated['quotation_language_code'] ?? ($validated['whatsapp_language_code'] ?? 'en_US');
        $setting->quotation_message_format = $validated['quotation_message_format'] ?? null;
        $setting->whatsapp_language_code   = $validated['whatsapp_language_code'] ?? 'en_US';
        $setting->is_active                = $isActive;
        $setting->save();

        // Deactivate the other provider setting record
        $otherProvider = ($provider === 'isarva') ? 'other' : 'isarva';
        WhatsappSetting::where('provider_type', $otherProvider)->update(['is_active' => 0]);

        return redirect()->route('whatsapp.settings.edit')->with('success', ucfirst($provider) . ' WhatsApp settings saved successfully.');
    }

    /**
     * Send a test WhatsApp message (Super Admin only).
     */
    public function sendTest(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->crm_role_type !== 0) {
            return response()->json(['success' => false, 'error' => 'Unauthorized action. Only Super Admin can test WhatsApp API.'], 403);
        }

        $request->validate([
            'test_phone_number' => 'required|string|max:30',
            'test_params'       => 'nullable|string',
        ]);

        $testParams = [];
        if ($request->filled('test_params')) {
            $testParams = array_map('trim', explode(',', $request->input('test_params')));
        }

        $service = new WhatsappService();
        $result = $service->sendTestMessage($request->input('test_phone_number'), $testParams);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Test message dispatched successfully via WhatsApp API!',
                'data' => $result['data'] ?? [],
            ]);
        }

        return response()->json([
            'success'  => false,
            'disabled' => $result['disabled'] ?? false,
            'error'    => $result['error'] ?? 'Failed to send test message.',
            'details'  => $result['details'] ?? null,
        ], 422);
    }

    /**
     * Verify current user's login password before revealing WhatsApp token (Super Admin only).
     */
    public function verifyPassword(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->crm_role_type !== 0) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'error'   => 'Incorrect password. Unable to view token.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password verified.',
        ]);
    }
}
