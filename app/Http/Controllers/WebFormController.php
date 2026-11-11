<?php

namespace App\Http\Controllers;

use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\WebForm;
use App\Services\WebFormLeadService;
use App\Support\WebFormFields;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebFormController extends Controller
{
    public static function userCanManage($user): bool
    {
        if (!$user) {
            return false;
        }
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return true;
        }
        if (!\App\Models\Company::current()->isWebFormsEnabled()) {
            return false;
        }
        return $user->hasCrmPermission('manage_crm_web_forms_guard')
            || $user->hasCrmPermission('view_crm_web_forms_guard')
            || $user->hasCrmPermission('create_crm_web_forms_guard')
            || $user->hasCrmPermission('edit_crm_web_forms_guard')
            || $user->hasCrmPermission('delete_crm_web_forms_guard');
    }

    protected function authorizeWebForm(string $action): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized action.');
        }
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return;
        }
        if (!\App\Models\Company::current()->isWebFormsEnabled()) {
            abort(403, 'Web Forms module is currently disabled.');
        }
        if ($user->hasCrmPermission('manage_crm_web_forms_guard')) {
            return;
        }

        $guardMap = [
            'view' => 'view_crm_web_forms_guard',
            'create' => 'create_crm_web_forms_guard',
            'edit' => 'edit_crm_web_forms_guard',
            'delete' => 'delete_crm_web_forms_guard',
        ];

        $guard = $guardMap[$action] ?? null;
        if (!$guard || !$user->hasCrmPermission($guard)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $this->authorizeWebForm('view');

        $forms = WebForm::with(['owner:id,name', 'leadSource:id,name'])
            ->orderByDesc('id')
            ->get();

        return view('web_forms.index', compact('forms'));
    }

    public function create()
    {
        $this->authorizeWebForm('create');

        $form = new WebForm([
            'name' => '',
            'heading' => 'Contact Us',
            'intro_text' => 'Fill in your details and our team will get back to you shortly.',
            'fields' => WebFormFields::defaults(),
            'submit_button_text' => 'Submit',
            'success_message' => 'Thank you! We have received your details and will contact you soon.',
            'theme_color' => '#5b3cc4',
            'lead_source_id' => LeadSource::whereRaw('LOWER(name) LIKE ?', ['%website%'])->value('id'),
            'lead_status' => WebFormLeadService::defaultStatus(),
            'lead_priority' => 'normal',
            'owner_id' => auth()->id(),
            'is_active' => true,
        ]);

        return view('web_forms.form', $this->formViewData($form));
    }

    public function store(Request $request)
    {
        $this->authorizeWebForm('create');

        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $form = WebForm::create($data);

        return redirect()->route('web_forms.embed', $form)
            ->with('success', 'Web form created. Copy the code below and paste it into your website.');
    }

    public function edit(WebForm $webForm)
    {
        $this->authorizeWebForm('edit');

        return view('web_forms.form', $this->formViewData($webForm));
    }

    public function update(Request $request, WebForm $webForm)
    {
        $this->authorizeWebForm('edit');

        $data = $this->validated($request);
        $data['updated_by'] = auth()->id();
        $webForm->update($data);

        return redirect()->route('web_forms.index')->with('success', 'Web form updated. Websites using it pick up the changes automatically.');
    }

    public function destroy(WebForm $webForm)
    {
        $this->authorizeWebForm('delete');

        $webForm->delete();

        return redirect()->route('web_forms.index')->with('success', 'Web form deleted. Its embed code will stop working.');
    }

    public function toggle(WebForm $webForm)
    {
        $this->authorizeWebForm('edit');

        $webForm->update(['is_active' => !$webForm->is_active, 'updated_by' => auth()->id()]);

        return back()->with('success', $webForm->is_active ? 'Web form activated.' : 'Web form deactivated. Websites will show it as unavailable.');
    }

    public function regenerateToken(WebForm $webForm)
    {
        $this->authorizeWebForm('edit');

        $webForm->update(['token' => WebForm::generateToken(), 'updated_by' => auth()->id()]);

        return redirect()->route('web_forms.embed', $webForm)
            ->with('success', 'New embed code generated. Replace the old code on your website; the old code no longer works.');
    }

    public function embed(WebForm $webForm)
    {
        $this->authorizeWebForm('view');

        $fields = WebFormFields::publicConfig($webForm->fields ?? []);

        return view('web_forms.embed', [
            'form' => $webForm,
            'fields' => $fields,
            'scriptSnippet' => $this->scriptSnippet($webForm),
            'iframeSnippet' => $this->iframeSnippet($webForm),
            'htmlSnippet' => $this->htmlSnippet($webForm, $fields),
        ]);
    }

    public function submissions(Request $request, WebForm $webForm)
    {
        $this->authorizeWebForm('view');

        $perPage = in_array((int) $request->get('per_page'), [25, 50, 100], true) ? (int) $request->get('per_page') : 25;
        $submissions = $webForm->submissions()
            ->with('lead:id,title')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('web_forms.submissions', [
            'form' => $webForm,
            'submissions' => $submissions,
            'perPage' => $perPage,
            'fieldLabels' => collect($webForm->fields ?? [])->pluck('label', 'key')->all(),
        ]);
    }

    protected function formViewData(WebForm $form): array
    {
        $savedFields = $form->fields;
        $oldFields = old('fields');
        if (is_array($oldFields)) {
            $savedFields = array_values(array_filter($oldFields, fn ($f) => !empty($f['enabled'])));
        }

        return [
            'form' => $form,
            'fieldRows' => WebFormFields::editorRows($savedFields),
            'leadSources' => LeadSource::orderBy('name')->get(['id', 'name']),
            'leadStatuses' => LeadStatus::orderBy('id')->get(['id', 'name']),
            'users' => User::where('status', 1)->orderBy('name')->get(['id', 'name']),
            'categories' => ProductCategory::where('status', 1)->orderBy('category_name')->get(['id', 'category_name']),
        ];
    }

    protected function validated(Request $request): array
    {
        $catalog = WebFormFields::catalog();

        $request->validate([
            'name' => 'required|string|max:150',
            'heading' => 'nullable|string|max:150',
            'intro_text' => 'nullable|string|max:500',
            'submit_button_text' => 'required|string|max:50',
            'success_message' => 'required|string|max:500',
            'redirect_url' => 'nullable|url|max:500',
            'theme_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'lead_source_id' => 'required|exists:lead_sources,id',
            'lead_status' => 'required|exists:leads_status,name',
            'lead_priority' => ['required', Rule::in(['high', 'normal', 'low'])],
            'owner_id' => 'required|exists:users,id',
            'default_category_ids' => 'nullable|array',
            'default_category_ids.*' => 'integer|exists:product_categories,id',
            'allowed_domains' => 'nullable|string|max:1000',
            'fields' => 'required|array',
            'fields.*.key' => ['required', Rule::in(array_keys($catalog))],
            'fields.*.label' => 'nullable|string|max:100',
            'fields.*.placeholder' => 'nullable|string|max:150',
        ], [
            'lead_source_id.required' => 'Please select the lead source for leads from this form.',
            'owner_id.required' => 'Please select who should own the leads from this form.',
            'theme_color.regex' => 'Theme color must be a hex color such as #5b3cc4.',
        ]);

        $fields = [];
        foreach ($request->input('fields', []) as $field) {
            $key = $field['key'] ?? null;
            $locked = !empty($catalog[$key]['locked']);
            if (!$locked && empty($field['enabled'])) {
                continue;
            }
            $fields[] = [
                'key' => $key,
                'label' => trim((string) ($field['label'] ?? '')) ?: $catalog[$key]['label'],
                'required' => $locked || !empty($field['required']),
                'placeholder' => trim((string) ($field['placeholder'] ?? '')),
            ];
        }

        $keys = array_column($fields, 'key');
        if (!in_array('email', $keys, true) && !in_array('mobile', $keys, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fields' => 'Enable at least Email or Mobile so your team can contact the visitor.',
            ]);
        }

        return [
            'name' => $request->input('name'),
            'heading' => $request->input('heading'),
            'intro_text' => $request->input('intro_text'),
            'fields' => $fields,
            'submit_button_text' => $request->input('submit_button_text'),
            'success_message' => $request->input('success_message'),
            'redirect_url' => $request->input('redirect_url'),
            'theme_color' => strtolower($request->input('theme_color')),
            'lead_source_id' => (int) $request->input('lead_source_id'),
            'lead_status' => $request->input('lead_status'),
            'lead_priority' => $request->input('lead_priority'),
            'owner_id' => (int) $request->input('owner_id'),
            'default_category_ids' => implode(',', array_map('intval', $request->input('default_category_ids', []))) ?: null,
            'allowed_domains' => implode(', ', (new WebForm(['allowed_domains' => $request->input('allowed_domains')]))->allowedDomainList()) ?: null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    protected function scriptSnippet(WebForm $form): string
    {
        return '<div id="crm-webform-' . $form->token . '"></div>' . "\n"
            . '<script src="' . $form->embedScriptUrl() . '" async></script>';
    }

    protected function iframeSnippet(WebForm $form): string
    {
        return '<iframe src="' . $form->hostedUrl() . '" style="width:100%;max-width:640px;height:760px;border:0;" title="' . e($form->heading ?: $form->name) . '" loading="lazy"></iframe>';
    }

    /**
     * Plain HTML form for sites that cannot run external scripts (posts directly, then shows the thank-you page or redirect).
     */
    protected function htmlSnippet(WebForm $form, array $fields): string
    {
        $lines = ['<form action="' . $form->submitUrl() . '" method="POST">'];
        foreach ($fields as $field) {
            $name = e($field['key']);
            $label = e($field['label']) . ($field['required'] ? ' *' : '');
            $required = $field['required'] ? ' required' : '';
            $placeholder = $field['placeholder'] !== '' ? ' placeholder="' . e($field['placeholder']) . '"' : '';
            $lines[] = '  <p>';
            $lines[] = '    <label for="wf-' . $name . '">' . $label . '</label><br>';
            if ($field['type'] === 'textarea') {
                $lines[] = '    <textarea id="wf-' . $name . '" name="' . $name . '" rows="4" maxlength="' . $field['max'] . '"' . $placeholder . $required . '></textarea>';
            } elseif ($field['type'] === 'select') {
                $lines[] = '    <select id="wf-' . $name . '" name="' . $name . '"' . $required . '>';
                $lines[] = '      <option value="">Select</option>';
                foreach ($field['options'] ?? [] as $option) {
                    $lines[] = '      <option value="' . e($option['value']) . '">' . e($option['label']) . '</option>';
                }
                $lines[] = '    </select>';
            } else {
                $lines[] = '    <input type="' . $field['type'] . '" id="wf-' . $name . '" name="' . $name . '" maxlength="' . $field['max'] . '"' . $placeholder . $required . '>';
            }
            $lines[] = '  </p>';
        }
        $lines[] = '  <input type="text" name="_hp_website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">';
        $lines[] = '  <button type="submit">' . e($form->submit_button_text ?: 'Submit') . '</button>';
        $lines[] = '</form>';

        return implode("\n", $lines);
    }
}
