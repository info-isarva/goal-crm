<?php

namespace App\Http\Controllers;

use App\Models\QuotationEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationEmailTemplateController extends Controller
{
    protected function authorizeManage(): void
    {
        $user = auth()->user();
        if (
            !$user
            || (
                (int) $user->crm_role_type !== 0
                && !$user->hasCrmPermission('manage_crm_quotation_email_templates_guard')
            )
        ) {
            abort(403, 'Unauthorized. Quotation Email Templates requires Manage Quotation Email Templates permission.');
        }
    }

    public function index()
    {
        $this->authorizeManage();

        $templates = QuotationEmailTemplate::query()
            ->with('createdBy')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15);

        return view('quotation_email_templates.index', compact('templates'));
    }

    public function create()
    {
        $this->authorizeManage();

        return view('quotation_email_templates.form', [
            'template' => new QuotationEmailTemplate([
                'subject' => QuotationEmailTemplate::designPresets()['professional']['subject'],
                'body' => QuotationEmailTemplate::professionalCardBody(),
                'is_active' => true,
            ]),
            'placeholders' => QuotationEmailTemplate::placeholders(),
            'designPresets' => QuotationEmailTemplate::designPresets(),
            'sampleVariables' => QuotationEmailTemplate::sampleVariables(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage();
        $validated = $this->validateTemplate($request);

        DB::transaction(function () use ($validated) {
            if (!empty($validated['is_default'])) {
                QuotationEmailTemplate::query()->update(['is_default' => false]);
            }

            QuotationEmailTemplate::create([
                ...$validated,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()->route('quotation_email_templates.index')
            ->with('success', 'Email template created successfully.');
    }

    public function edit(QuotationEmailTemplate $quotation_email_template)
    {
        $this->authorizeManage();

        $quotation_email_template->body = QuotationEmailTemplate::sanitizeEmailBodyHtml(
            (string) $quotation_email_template->body
        );

        return view('quotation_email_templates.form', [
            'template' => $quotation_email_template,
            'placeholders' => QuotationEmailTemplate::placeholders(),
            'designPresets' => QuotationEmailTemplate::designPresets(),
            'sampleVariables' => QuotationEmailTemplate::sampleVariables(),
        ]);
    }

    public function update(Request $request, QuotationEmailTemplate $quotation_email_template)
    {
        $this->authorizeManage();
        $validated = $this->validateTemplate($request);

        DB::transaction(function () use ($validated, $quotation_email_template) {
            if (!empty($validated['is_default'])) {
                QuotationEmailTemplate::query()
                    ->where('id', '!=', $quotation_email_template->id)
                    ->update(['is_default' => false]);
            }

            $quotation_email_template->update([
                ...$validated,
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()->route('quotation_email_templates.index')
            ->with('success', 'Email template updated successfully.');
    }

    public function destroy(QuotationEmailTemplate $quotation_email_template)
    {
        $this->authorizeManage();

        if ($quotation_email_template->is_default && QuotationEmailTemplate::count() > 1) {
            return back()->with('error', 'Set another template as default before deleting this one.');
        }

        $quotation_email_template->delete();

        if (QuotationEmailTemplate::where('is_default', true)->doesntExist()) {
            QuotationEmailTemplate::query()->orderBy('id')->first()?->update(['is_default' => true]);
        }

        return redirect()->route('quotation_email_templates.index')
            ->with('success', 'Email template deleted successfully.');
    }

    public function setDefault(QuotationEmailTemplate $quotation_email_template)
    {
        $this->authorizeManage();

        DB::transaction(function () use ($quotation_email_template) {
            QuotationEmailTemplate::query()->update(['is_default' => false]);
            $quotation_email_template->update([
                'is_default' => true,
                'is_active' => true,
                'updated_by' => auth()->id(),
            ]);
        });

        return back()->with('success', 'Default email template updated.');
    }

    public function toggleStatus(QuotationEmailTemplate $quotation_email_template)
    {
        $this->authorizeManage();

        if ($quotation_email_template->is_default && $quotation_email_template->is_active) {
            return back()->with('error', 'Default template cannot be deactivated.');
        }

        $quotation_email_template->update([
            'is_active' => !$quotation_email_template->is_active,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Template status updated.');
    }

    protected function validateTemplate(Request $request): array
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'subject'     => 'required|string|max:255',
            'body'        => 'required|string|max:50000',
            'is_default'  => 'nullable|boolean',
            'is_active'   => 'nullable|boolean',
        ]) + [
            'is_default' => $request->boolean('is_default'),
            'is_active'  => $request->boolean('is_active', true),
        ];

        $validated['body'] = QuotationEmailTemplate::sanitizeEmailBodyHtml($validated['body']);

        return $validated;
    }

    protected function defaultBody(): string
    {
        return QuotationEmailTemplate::professionalCardBody();
    }
}
