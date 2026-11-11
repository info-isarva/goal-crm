<?php

namespace App\Http\Controllers;

use App\Models\WebForm;
use App\Models\WebFormSubmission;
use App\Services\WebFormLeadService;
use App\Support\WebFormFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Public (no login) endpoints used by websites and landing pages.
 */
class PublicWebFormController extends Controller
{
    public function show(string $token)
    {
        $form = WebForm::where('token', $token)->first();

        return response()
            ->view('web_forms.public', ['form' => $form, 'token' => $token])
            ->header('Content-Security-Policy', 'frame-ancestors *');
    }

    public function script(string $token)
    {
        $form = WebForm::where('token', $token)->first();

        $config = [
            'token' => $token,
            'active' => (bool) ($form && $form->is_active),
            'submitUrl' => route('webforms.public.submit', $token),
        ];
        if ($form && $form->is_active) {
            $config += [
                'heading' => (string) $form->heading,
                'intro' => (string) $form->intro_text,
                'button' => $form->submit_button_text ?: 'Submit',
                'success' => (string) $form->success_message,
                'redirect' => (string) $form->redirect_url,
                'color' => $form->theme_color ?: '#5b3cc4',
                'fields' => WebFormFields::publicConfig($form->fields ?? []),
            ];
        }

        $js = view('web_forms.embed_js', [
            'configJson' => json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
        ])->render();

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function preflight()
    {
        return response('', 204, $this->corsHeaders(request()));
    }

    public function submit(Request $request, string $token, WebFormLeadService $service)
    {
        $ajax = $request->boolean('_ajax');
        $form = WebForm::where('token', $token)->first();

        if (!$form || !$form->is_active) {
            return $this->fail($request, $ajax, 'This form is no longer accepting submissions.', 404);
        }

        // Bots fill every field, including the hidden one; pretend success so they don't retry.
        if (trim((string) $request->input('_hp_website', '')) !== '') {
            return $this->succeed($request, $ajax, $form);
        }

        $sourceHost = $this->sourceHost($request);
        if (!$form->allowsHost($sourceHost)) {
            return $this->fail($request, $ajax, 'Submissions from this website are not allowed.', 403);
        }

        $fields = WebFormFields::publicConfig($form->fields ?? []);
        $rules = [];
        $labels = [];
        foreach ($fields as $field) {
            $rule = [$field['required'] ? 'required' : 'nullable', 'string', 'max:' . $field['max']];
            if ($field['type'] === 'email') {
                $rule[] = 'email';
            } elseif ($field['type'] === 'tel') {
                $rule[] = 'regex:/^[0-9+()\-\s.]{6,20}$/';
            } elseif ($field['type'] === 'select') {
                $rule[] = 'in:' . implode(',', array_column($field['options'] ?? [], 'value'));
            }
            $rules[$field['key']] = $rule;
            $labels[$field['key']] = $field['label'];
        }

        $validator = Validator::make($request->only(array_keys($rules)), $rules, [
            'required' => ':attribute is required.',
            'email' => 'Please enter a valid email address.',
            'regex' => 'Please enter a valid phone number.',
            'in' => 'Please choose a valid option.',
        ], $labels);

        if ($validator->fails()) {
            if ($ajax) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Please correct the highlighted fields.',
                    'errors' => $validator->errors()->toArray(),
                ], 422, $this->corsHeaders($request));
            }

            return $this->fail($request, $ajax, implode(' ', $validator->errors()->all()), 422);
        }

        $data = array_map(fn ($v) => is_string($v) ? trim(strip_tags($v)) : $v, $validator->validated());
        $pageUrl = Str::limit((string) ($request->input('_page') ?: $request->headers->get('referer', '')), 500, '');

        $submission = WebFormSubmission::create([
            'web_form_id' => $form->id,
            'payload' => $data,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'page_url' => $pageUrl ?: null,
        ]);

        try {
            $lead = $service->createLead($form, $data);
            $submission->update(['status' => 'created', 'lead_id' => $lead->id]);
            $form->increment('submissions_count');
        } catch (\Throwable $e) {
            Log::error('Web form lead creation failed', ['web_form_id' => $form->id, 'error' => $e->getMessage()]);
            $submission->update(['status' => 'failed', 'error_message' => Str::limit($e->getMessage(), 1000, '')]);

            return $this->fail($request, $ajax, 'Sorry, we could not submit your details right now. Please try again later.', 500);
        }

        return $this->succeed($request, $ajax, $form);
    }

    protected function succeed(Request $request, bool $ajax, WebForm $form)
    {
        if ($ajax) {
            return response()->json([
                'ok' => true,
                'message' => $form->success_message ?: 'Thank you!',
                'redirect' => $form->redirect_url ?: null,
            ], 200, $this->corsHeaders($request));
        }

        if ($form->redirect_url) {
            return redirect()->away($form->redirect_url);
        }

        return response()->view('web_forms.public_result', [
            'form' => $form,
            'success' => true,
            'message' => $form->success_message ?: 'Thank you!',
        ]);
    }

    protected function fail(Request $request, bool $ajax, string $message, int $status)
    {
        if ($ajax) {
            return response()->json(['ok' => false, 'message' => $message], $status, $this->corsHeaders($request));
        }

        return response()->view('web_forms.public_result', [
            'form' => null,
            'success' => false,
            'message' => $message,
        ], $status);
    }

    protected function sourceHost(Request $request): ?string
    {
        foreach ([$request->headers->get('origin'), $request->input('_page'), $request->headers->get('referer')] as $url) {
            $host = $url ? parse_url((string) $url, PHP_URL_HOST) : null;
            if ($host) {
                return strtolower($host);
            }
        }

        return null;
    }

    protected function corsHeaders(Request $request): array
    {
        return [
            'Access-Control-Allow-Origin' => $request->headers->get('origin') ?: '*',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Vary' => 'Origin',
        ];
    }
}
