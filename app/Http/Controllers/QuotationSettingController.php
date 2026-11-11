<?php

namespace App\Http\Controllers;

use App\Models\QuotationSetting;
use App\Services\QuotationTemplateService;
use Illuminate\Http\Request;

class QuotationSettingController extends Controller
{
    protected function authorizeManage(): void
    {
        $user = auth()->user();
        if (
            !$user
            || (
                (int) $user->crm_role_type !== 0
                && !$user->hasCrmPermission('manage_crm_quotation_terms_guard')
            )
        ) {
            abort(403, 'Unauthorized. Quotation Terms & Conditions requires Manage Quotation Terms permission.');
        }
    }

    public function edit()
    {
        $this->authorizeManage();

        $setting = QuotationSetting::current();
        $canEdit = true;

        return view('settings.quotation_terms', compact('setting', 'canEdit'));
    }

    public function update(Request $request)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'default_terms_conditions' => 'nullable|string',
        ]);

        $html = trim((string) ($validated['default_terms_conditions'] ?? ''));
        if ($html !== '' && trim(strip_tags($html)) === '') {
            $html = '';
        } elseif ($html !== '') {
            $html = app(QuotationTemplateService::class)->sanitizeRichEditorHtml($html);
        }

        $setting = QuotationSetting::current();
        $setting->default_terms_conditions = $html !== '' ? $html : null;
        $setting->save();

        return redirect()
            ->route('quotation.settings.edit')
            ->with('success', 'Default quotation terms & conditions saved successfully.');
    }
}
