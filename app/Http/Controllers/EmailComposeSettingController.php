<?php

namespace App\Http\Controllers;

use App\Services\EmailComposeSettingsService;
use App\Services\GoogleGmailService;
use Illuminate\Http\Request;

class EmailComposeSettingController extends Controller
{
    public function edit(EmailComposeSettingsService $composeSettings, GoogleGmailService $gmailService)
    {
        $user = auth()->user();

        $connected = $gmailService->userHasGmailAccess($user);
        $fromLabel = trim(($user->name ?? '') . ' <' . ($user->email ?? '') . '>');

        return view('settings.email_compose', [
            'user' => $user,
            'fontFamilies' => $composeSettings->fontFamilies(),
            'fontSizes' => $composeSettings->fontSizes(),
            'fontFamily' => $composeSettings->fontFamily($user),
            'fontSize' => $composeSettings->fontSize($user),
            'signatureName' => $composeSettings->signatureName($user),
            'signatureHtml' => $composeSettings->signatureHtml($user),
            'fromLabel' => $fromLabel,
            'gmailConnected' => $connected,
            'editorContentStyle' => $composeSettings->editorContentStyle($user),
        ]);
    }

    public function update(Request $request, EmailComposeSettingsService $composeSettings)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'email_compose_font_family' => 'required|string|max:100|in:' . implode(',', $composeSettings->fontFamilies()),
            'email_compose_font_size' => 'required|integer|in:' . implode(',', $composeSettings->fontSizes()),
            'email_signature_name' => 'nullable|string|max:100',
            'email_signature_html' => 'nullable|string|max:20000',
        ]);

        $signatureHtml = trim((string) ($validated['email_signature_html'] ?? ''));
        // Keep empty signature as null; strip empty editor shells
        if ($signatureHtml === '' || trim(strip_tags($signatureHtml)) === '') {
            $signatureHtml = null;
        }

        $user->update([
            'email_compose_font_family' => $validated['email_compose_font_family'],
            'email_compose_font_size' => (int) $validated['email_compose_font_size'],
            'email_signature_name' => trim((string) ($validated['email_signature_name'] ?? '')) ?: null,
            'email_signature_html' => $signatureHtml,
        ]);

        return redirect()
            ->route('email.compose.settings.edit')
            ->with('success', 'Compose settings saved. Font and signature will apply to all outgoing emails.');
    }
}
