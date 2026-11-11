<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Quotation;
use App\Models\QuotationTemplate;
use App\Models\User;
use App\Services\QuotationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationTemplateController extends Controller
{
    protected QuotationTemplateService $service;

    public function __construct(QuotationTemplateService $service)
    {
        $this->service = $service;
    }

    /**
     * Guards used for Quotation Templates module access.
     *
     * @return list<string>
     */
    public static function accessGuards(): array
    {
        return [
            'manage_crm_quotation_templates_guard',
            'create_crm_quotation_templates_guard',
            'edit_crm_quotation_templates_guard',
            'delete_crm_quotation_templates_guard',
            'preview_crm_quotation_templates_guard',
            'duplicate_crm_quotation_templates_guard',
            'set_default_crm_quotation_templates_guard',
            'deactivate_crm_quotation_templates_guard',
        ];
    }

    public static function userCanAccessTemplates(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Super Admin always has access to the module list.
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return true;
        }

        foreach (self::accessGuards() as $guard) {
            if ($user->hasCrmPermission($guard)) {
                return true;
            }
        }

        return false;
    }

    protected function userHasTemplatePermission(string $guard): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Super Admin always allowed.
        if ((int) ($user->crm_role_type ?? -1) === 0) {
            return true;
        }

        // Each action requires its own assigned permission (Manage does not auto-grant actions).
        return $user->hasCrmPermission($guard);
    }

    protected function denyAccess(string $message): void
    {
        if (request()->expectsJson() || request()->ajax()) {
            abort(403, $message);
        }

        $fallback = self::userCanAccessTemplates(auth()->user())
            ? route('quotation_templates.index')
            : route('settings.index');

        $previous = url()->previous();
        $target = ($previous && $previous !== url()->current()) ? $previous : $fallback;

        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            redirect()->to($target)->with('error', $message)
        );
    }

    protected function authorizeTemplatePermission(string $guard, string $message = 'Permission denied. You do not have access for this action.'): void
    {
        if (!$this->userHasTemplatePermission($guard)) {
            $this->denyAccess($message);
        }
    }

    protected function authorizeAnyTemplateAccess(): void
    {
        if (!self::userCanAccessTemplates(auth()->user())) {
            $this->denyAccess('Permission denied. Quotation Templates access is not assigned to your account.');
        }
    }

    public function index()
    {
        $this->authorizeAnyTemplateAccess();
        $this->service->ensureAllBuiltInTemplatesExist(auth()->id());

        $templates = QuotationTemplate::query()
            ->with('createdBy')
            ->withCount('quotations')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15);

        $canCreate = $this->userHasTemplatePermission('create_crm_quotation_templates_guard');
        $canEdit = $this->userHasTemplatePermission('edit_crm_quotation_templates_guard');
        $canDelete = $this->userHasTemplatePermission('delete_crm_quotation_templates_guard');
        $canPreview = $this->userHasTemplatePermission('preview_crm_quotation_templates_guard');
        $canDuplicate = $this->userHasTemplatePermission('duplicate_crm_quotation_templates_guard');
        $canSetDefault = $this->userHasTemplatePermission('set_default_crm_quotation_templates_guard');
        $canDeactivate = $this->userHasTemplatePermission('deactivate_crm_quotation_templates_guard');

        return view('quotation_templates.index', compact(
            'templates',
            'canCreate',
            'canEdit',
            'canDelete',
            'canPreview',
            'canDuplicate',
            'canSetDefault',
            'canDeactivate'
        ));
    }

    public function create()
    {
        $this->authorizeTemplatePermission(
            'create_crm_quotation_templates_guard',
            'Permission denied. You do not have Create Quotation Templates access.'
        );

        $placeholderGroups = QuotationTemplateService::placeholderGroups();
        $html = QuotationTemplateService::defaultHtml();
        $css = QuotationTemplateService::defaultCss();
        $canPreview = $this->userHasTemplatePermission('preview_crm_quotation_templates_guard');
        $canSetDefault = $this->userHasTemplatePermission('set_default_crm_quotation_templates_guard');
        $canDeactivate = $this->userHasTemplatePermission('deactivate_crm_quotation_templates_guard');

        return view('quotation_templates.form', [
            'template' => null,
            'placeholderGroups' => $placeholderGroups,
            'html' => $html,
            'css' => $css,
            'mode' => 'create',
            'canPreview' => $canPreview,
            'canSetDefault' => $canSetDefault,
            'canDeactivate' => $canDeactivate,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeTemplatePermission(
            'create_crm_quotation_templates_guard',
            'Permission denied. You do not have Create Quotation Templates access.'
        );

        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:quotation_templates,name',
            'description' => 'nullable|string|max:1000',
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'is_default' => 'nullable|boolean',
        ]);

        $canSetDefault = $this->userHasTemplatePermission('set_default_crm_quotation_templates_guard');
        $canDeactivate = $this->userHasTemplatePermission('deactivate_crm_quotation_templates_guard');
        $isDefault = $canSetDefault && !empty($validated['is_default']);
        $status = $canDeactivate ? $validated['status'] : 'active';
        if ($isDefault) {
            $status = 'active';
        }

        $template = DB::transaction(function () use ($validated, $isDefault, $status) {
            if ($isDefault) {
                QuotationTemplate::query()->where('is_default', true)->update(['is_default' => false]);
            }

            $template = QuotationTemplate::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'html_content' => $this->service->sanitizeHtml($validated['html_content']),
                'css_content' => $this->service->sanitizeCss($validated['css_content'] ?? ''),
                'status' => $status,
                'is_default' => $isDefault,
                'version' => 1,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->service->createVersion($template);

            return $template;
        });

        return redirect()
            ->route('quotation_templates.edit', $template)
            ->with('success', 'Quotation template created successfully.');
    }

    public function edit(QuotationTemplate $quotation_template)
    {
        $this->authorizeTemplatePermission(
            'edit_crm_quotation_templates_guard',
            'Permission denied. You do not have Edit Quotation Templates access.'
        );

        $placeholderGroups = QuotationTemplateService::placeholderGroups();
        $canPreview = $this->userHasTemplatePermission('preview_crm_quotation_templates_guard');
        $canSetDefault = $this->userHasTemplatePermission('set_default_crm_quotation_templates_guard');
        $canDeactivate = $this->userHasTemplatePermission('deactivate_crm_quotation_templates_guard');

        return view('quotation_templates.form', [
            'template' => $quotation_template,
            'placeholderGroups' => $placeholderGroups,
            'html' => $quotation_template->html_content,
            'css' => $quotation_template->css_content,
            'mode' => 'edit',
            'canPreview' => $canPreview,
            'canSetDefault' => $canSetDefault,
            'canDeactivate' => $canDeactivate,
        ]);
    }

    public function update(Request $request, QuotationTemplate $quotation_template)
    {
        $this->authorizeTemplatePermission(
            'edit_crm_quotation_templates_guard',
            'Permission denied. You do not have Edit Quotation Templates access.'
        );

        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:quotation_templates,name,' . $quotation_template->id,
            'description' => 'nullable|string|max:1000',
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'is_default' => 'nullable|boolean',
        ]);

        $canSetDefault = $this->userHasTemplatePermission('set_default_crm_quotation_templates_guard');
        $canDeactivate = $this->userHasTemplatePermission('deactivate_crm_quotation_templates_guard');

        DB::transaction(function () use ($validated, $quotation_template, $canSetDefault, $canDeactivate) {
            $html = $this->service->sanitizeHtml($validated['html_content']);
            $css = $this->service->sanitizeCss($validated['css_content'] ?? '');
            $contentChanged = $html !== $quotation_template->html_content
                || $css !== (string) $quotation_template->css_content;

            $isDefault = $quotation_template->is_default;
            if ($canSetDefault) {
                $isDefault = !empty($validated['is_default']);
                if ($isDefault) {
                    QuotationTemplate::query()
                        ->where('id', '!=', $quotation_template->id)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                }
            }

            $status = $quotation_template->status;
            if ($canDeactivate) {
                $status = $validated['status'];
            }
            if ($isDefault) {
                $status = 'active';
            }

            $version = (int) $quotation_template->version;
            if ($contentChanged) {
                $version++;
            }

            $quotation_template->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'html_content' => $html,
                'css_content' => $css,
                'status' => $status,
                'is_default' => $isDefault,
                'version' => $version,
                'updated_by' => auth()->id(),
            ]);

            if ($contentChanged) {
                $this->service->createVersion($quotation_template->fresh());
            }
        });

        return redirect()
            ->route('quotation_templates.edit', $quotation_template)
            ->with('success', 'Quotation template updated successfully.');
    }

    public function destroy(QuotationTemplate $quotation_template)
    {
        $this->authorizeTemplatePermission(
            'delete_crm_quotation_templates_guard',
            'Permission denied. You do not have Delete Quotation Templates access.'
        );

        if ($quotation_template->is_default) {
            return redirect()
                ->route('quotation_templates.index')
                ->with('error', 'Cannot delete the default template. Set another template as default first.');
        }

        try {
            DB::transaction(function () use ($quotation_template) {
                // Soft delete so built-in seeders do not recreate it on the next page load.
                $quotation_template->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('quotation_templates.index')
                ->with('error', 'Unable to delete this template. Please try again.');
        }

        return redirect()
            ->route('quotation_templates.index')
            ->with('success', 'Quotation template deleted.');
    }

    public function duplicate(QuotationTemplate $quotation_template)
    {
        $this->authorizeTemplatePermission(
            'duplicate_crm_quotation_templates_guard',
            'Permission denied. You do not have Duplicate Quotation Templates access.'
        );

        $copy = DB::transaction(function () use ($quotation_template) {
            $baseName = $quotation_template->name . ' (Copy)';
            $name = $baseName;
            $i = 2;
            while (QuotationTemplate::where('name', $name)->exists()) {
                $name = $baseName . ' ' . $i;
                $i++;
            }

            $copy = QuotationTemplate::create([
                'name' => $name,
                'description' => $quotation_template->description,
                'html_content' => $quotation_template->html_content,
                'css_content' => $quotation_template->css_content,
                'status' => 'active',
                'is_default' => false,
                'version' => 1,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->service->createVersion($copy);

            return $copy;
        });

        if ($this->userHasTemplatePermission('edit_crm_quotation_templates_guard')) {
            return redirect()
                ->route('quotation_templates.edit', $copy)
                ->with('success', 'Template duplicated successfully.');
        }

        return redirect()
            ->route('quotation_templates.index')
            ->with('success', 'Template duplicated successfully.');
    }

    public function toggleStatus(QuotationTemplate $quotation_template)
    {
        $this->authorizeTemplatePermission(
            'deactivate_crm_quotation_templates_guard',
            'Permission denied. You do not have Activate/Deactivate Quotation Templates access.'
        );

        if ($quotation_template->is_default && $quotation_template->isActive()) {
            return redirect()
                ->route('quotation_templates.index')
                ->with('error', 'Cannot deactivate the default template. Set another default first.');
        }

        $quotation_template->update([
            'status' => $quotation_template->isActive() ? 'inactive' : 'active',
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('quotation_templates.index')
            ->with('success', 'Template status updated.');
    }

    public function setDefault(QuotationTemplate $quotation_template)
    {
        $this->authorizeTemplatePermission(
            'set_default_crm_quotation_templates_guard',
            'Permission denied. You do not have Set Default Quotation Template access.'
        );

        $this->service->setAsDefault($quotation_template);

        return redirect()
            ->route('quotation_templates.index')
            ->with('success', '"' . $quotation_template->name . '" is now the default template.');
    }

    public function preview(Request $request, ?QuotationTemplate $quotation_template = null)
    {
        $this->authorizeTemplatePermission(
            'preview_crm_quotation_templates_guard',
            'Permission denied. You do not have Preview Quotation Templates access.'
        );

        $html = (string) $request->input('html_content', $quotation_template?->html_content ?? '');
        $css = (string) $request->input('css_content', $quotation_template?->css_content ?? '');

        $document = $this->service->renderPreviewHtml($html, $css);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'html' => $document]);
        }

        return response($document)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function previewPost(Request $request)
    {
        if ($request->boolean('wizard_draft') || ($request->filled('quotation_template_id') && !$request->filled('html_content'))) {
            $document = $this->buildWizardDraftPreviewDocument($request);
            if ($document === null) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active quotation template is configured.',
                ], 422);
            }

            return response()->json(['success' => true, 'html' => $document]);
        }

        $this->authorizeTemplatePermission(
            'preview_crm_quotation_templates_guard',
            'Permission denied. You do not have Preview Quotation Templates access.'
        );

        $validated = $request->validate([
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
        ]);

        $document = $this->service->renderPreviewHtml(
            $validated['html_content'],
            $validated['css_content'] ?? ''
        );

        return response()->json(['success' => true, 'html' => $document]);
    }

    /**
     * Lightweight HTML preview for quotation create/edit template picker.
     * Available to users who can create/edit quotations (not only Super Admin).
     */
    public function pickerPreview(QuotationTemplate $quotation_template)
    {
        $user = auth()->user();
        if (
            !$user
            || (
                (int) $user->crm_role_type !== 0
                && !$user->hasCrmPermission('create_crm_quotations_guard')
                && !$user->hasCrmPermission('edit_crm_quotations_guard')
                && !$user->hasCrmPermission('view_crm_quotations_guard')
            )
        ) {
            abort(403);
        }

        if (!$quotation_template->isActive() && (int) $user->crm_role_type !== 0) {
            abort(404);
        }

        $document = $this->service->renderPreviewHtml(
            (string) ($quotation_template->html_content ?? ''),
            (string) ($quotation_template->css_content ?? '')
        );

        return response($document)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Live HTML preview of selected quotation template using create/edit wizard form values.
     */
    public function wizardDraftPreview(Request $request)
    {
        $document = $this->buildWizardDraftPreviewDocument($request);
        if ($document === null) {
            return response(
                '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;padding:24px;color:#b91c1c;">No active quotation template is configured.</body></html>',
                422
            )->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return response($document)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * @return string|null Rendered HTML document, or null when no template is available.
     */
    protected function buildWizardDraftPreviewDocument(Request $request): ?string
    {
        $user = auth()->user();
        if (
            !$user
            || (
                (int) $user->crm_role_type !== 0
                && !$user->hasCrmPermission('create_crm_quotations_guard')
                && !$user->hasCrmPermission('edit_crm_quotations_guard')
            )
        ) {
            abort(403);
        }

        $templateId = (int) $request->input('quotation_template_id', 0);
        $resolved = $this->service->resolveTemplateForQuotation($templateId > 0 ? $templateId : null);
        if (!$resolved) {
            return null;
        }

        $template = $resolved['template'];
        $version = $resolved['version'] ?? null;
        $html = (string) ($version->html_content ?? $template->html_content ?? '');
        $css = (string) ($version->css_content ?? $template->css_content ?? '');

        $data = $this->service->buildDataFromWizardDraft($request->all(), Company::first());

        return $this->service->renderPreviewHtml($html, $css, $data);
    }
}
