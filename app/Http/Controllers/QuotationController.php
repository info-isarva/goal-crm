<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuotationRequest;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationSetting;
use App\Models\QuotationEmailTemplate;
use App\Models\QuotationEmailSendLog;
use App\Models\QuotationWhatsappSendLog;
use App\Models\QuotationTemplate;
use App\Models\User;
use App\Models\WhatsappSetting;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use App\Mail\QuotationSentMail;
use App\Models\EmailSetting;
use App\Services\EmailComposeSettingsService;
use App\Services\EmailService;
use App\Services\GoogleGmailService;
use App\Services\WhatsappService;
use App\Services\QuotationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    public function index()
    {
        /** Permission check */
        if (!auth()->user()->hasCrmPermission('manage_crm_quotations_guard')) {
            abort(403);
        }
        $quotations = Quotation::query();
        $this->applyQuotationVisibilityScope($quotations);

        // Filter by status
        if (request()->filled('status')) {
            $quotations->where('status', request('status'));
        }

        // Filter by subject or quotation number
        if (request()->filled('search')) {
            $quotations->where(function ($query) {
                $query->where('quote_no', 'like', '%' . request('search') . '%')
                    ->orWhere('subject', 'like', '%' . request('search') . '%');
            });
        }

        $quotations = $quotations->with(['deal', 'organization', 'people'])->latest()->paginate(10);

        $whatsappSetting = \App\Models\WhatsappSetting::getSettings();
        $whatsappEnabled = (bool) ($whatsappSetting && $whatsappSetting->exists && $whatsappSetting->is_active
            && !empty($whatsappSetting->whatsapp_access_token));

        $emailSetting = EmailSetting::where('is_active', true)->first();
        $emailEnabled = (bool) ($emailSetting && !empty($emailSetting->mail_from_address) && !empty(auth()->user()->email));
        $company = Company::first();

        return view('quotations.index', compact('quotations', 'whatsappEnabled', 'emailEnabled', 'emailSetting', 'whatsappSetting', 'company'));
    }

    public function create(Request $request)
    {
        /** Permission check */
        if (!auth()->user()->hasCrmPermission('create_crm_quotations_guard')) {
            abort(403);
        }
        $products = Product::with('taxRate')->orderBy('product_name')->get();
        $organizations = Organization::all();
        $users = \App\Models\User::all();
        $taxRates = \App\Models\TaxRate::all();
        $stages = \App\Models\Stage::orderBy('order_by')->orderBy('order_by')->get();
        $currentUser = auth()->user();
        $countries = [
            'India', 'United Arab Emirates', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Singapore', 'Japan', 'China', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman', 'Switzerland', 'Malaysia', 'New Zealand', 'South Africa', 'Other'
        ];

        $prefillDeal = null;
        if ($request->filled('deal_id')) {
            $prefillDeal = \App\Models\Deal::with(['organization', 'person'])
                ->find($request->input('deal_id'));
        }

        app(QuotationTemplateService::class)->ensureAllBuiltInTemplatesExist(auth()->id());
        $quotationTemplates = QuotationTemplate::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'is_default', 'version']);

        $defaultQuotationTemplateId = (int) (
            optional($quotationTemplates->firstWhere('is_default', true))->id
            ?? optional($quotationTemplates->first())->id
        );

        $selectedQuotationTemplateId = (int) old('quotation_template_id', $defaultQuotationTemplateId);
        $selectedQuotationTemplate = $quotationTemplates->firstWhere('id', $selectedQuotationTemplateId)
            ?? $quotationTemplates->first();

        $nextProposalVersion = 1;
        if ($prefillDeal) {
            $nextProposalVersion = $this->nextProposalVersionForDeal((int) $prefillDeal->id);
        }
        $nextProposalVersion = (int) old('proposal_version', $nextProposalVersion);
        $defaultTermsConditions = QuotationSetting::defaultTermsConditions();

        return view('quotations.create', compact(
            'products',
            'organizations',
            'users',
            'stages',
            'countries',
            'taxRates',
            'currentUser',
            'prefillDeal',
            'quotationTemplates',
            'defaultQuotationTemplateId',
            'selectedQuotationTemplate',
            'selectedQuotationTemplateId',
            'nextProposalVersion',
            'defaultTermsConditions'
        ));
    }

    public function edit(Quotation $quotation)
    {
        /* edit permission check */
        if (!auth()->user()->hasCrmPermission('edit_crm_quotations_guard')) {
            abort(403);
        }
        $this->authorizeQuotationAccess($quotation);
        $products = Product::with('taxRate')->orderBy('product_name')->get();
        $quotation->load('items.product');
        $quotation->load('addresses');
        $quotation->load('quotationTemplate');


        $organizations = Organization::all();
        $users = \App\Models\User::all();
        $taxRates = \App\Models\TaxRate::all();
        $stages = \App\Models\Stage::orderBy('order_by')->orderBy('order_by')->get();
        $countries = [
            'India', 'United Arab Emirates', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Singapore', 'Japan', 'China', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman', 'Switzerland', 'Malaysia', 'New Zealand', 'South Africa', 'Other'
        ];

        app(QuotationTemplateService::class)->ensureAllBuiltInTemplatesExist(auth()->id());
        $quotationTemplates = QuotationTemplate::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'is_default', 'version']);

        $selectedQuotationTemplateId = (int) old(
            'quotation_template_id',
            $quotation->quotation_template_id
                ?? optional($quotationTemplates->firstWhere('is_default', true))->id
                ?? optional($quotationTemplates->first())->id
        );
        $selectedQuotationTemplate = $quotationTemplates->firstWhere('id', $selectedQuotationTemplateId)
            ?? $quotationTemplates->first();

        return view('quotations.edit', compact(
            'quotation',
            'products',
            'users',
            'organizations',
            'stages',
            'countries',
            'taxRates',
            'quotationTemplates',
            'selectedQuotationTemplate',
            'selectedQuotationTemplateId'
        ));
    }

    public function update($id, Request $request)
    {
        /** edit permission check */
        if (!auth()->user()->hasCrmPermission('edit_crm_quotations_guard')) {
            abort(403);
        }
        $quotation = Quotation::findOrFail($id);
        $this->authorizeQuotationAccess($quotation);

        //Validation logic can be added here as needed
        $validated = $request->validate([
            'people_id' => 'required',
            'organization_id' => 'required',
            'deal_id' => 'required',
            'subject' => 'required|string|max:255',
            'quotation_title' => 'nullable|string|max:255',
            'quotation_product_type' => 'required|in:default,product',
            'currency_code' => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'project_detail' => 'nullable|string',
            'show_bank_details' => 'nullable|boolean',
            'proposal_version' => 'nullable|integer|min:1',
            'quotation_owner' => 'required|exists:users,id',
            'valid_until' => 'required|date',
            'status' => 'required|in:Draft,Negotiation,Delivered,On Hold,Confirmed,Closed Won,Closed Lost',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax' => 'nullable|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'overall_discount' => 'nullable|numeric|min:0',
            'overall_discount_percent' => 'nullable|numeric|min:0|max:100',
            'overall_tax' => 'nullable|numeric|min:0',
            'adjustment' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',

            'billing_flat' => 'nullable|string|max:255',
            'billing_street' => 'required|string|max:255',
            'billing_city' => 'required|string|max:255',
            'billing_state' => 'required|string|max:255',
            'billing_country' => 'required|string|max:255',
            'billing_zip' => 'nullable|max:20',

            'enable_shipping_address' => 'nullable|boolean',
            'shipping_flat' => 'nullable|string|max:255',
            'shipping_street' => ($request->boolean('enable_shipping_address') ? 'required' : 'nullable') . '|string|max:255',
            'shipping_city' => ($request->boolean('enable_shipping_address') ? 'required' : 'nullable') . '|string|max:255',
            'shipping_state' => ($request->boolean('enable_shipping_address') ? 'required' : 'nullable') . '|string|max:255',
            'shipping_country' => ($request->boolean('enable_shipping_address') ? 'required' : 'nullable') . '|string|max:255',
            'shipping_zip' => 'nullable|max:20',

            'quotation_template_id' => 'nullable|exists:quotation_templates,id',
        ]);
        

        $organization_id = Organization::where('name', $validated['organization_id'])->first()->id;
        $validated['organization_id'] = $organization_id;
        $full_name = $validated['people_id'];

        $nameParts = explode(' ', trim($full_name));

        $first_name = $nameParts[0] ?? '';
        $last_name  = $nameParts[1] ?? null;

        $query = Person::where('first_name', $first_name)
            ->where('organization_id', $organization_id);

        if (!empty($last_name)) {
            $query->where('last_name', $last_name);
        } else {
            $query->whereNull('last_name'); // if DB column is NULL
        }

        $person = $query->first();

        $people_id = $person ? $person->id : null;
        $validated['people_id'] = $request->get('person_id'); //$people_id;
        $deal_id = \App\Models\Deal::where('title', $validated['deal_id'])->where('organization_id', $organization_id)->first()->id;
        $validated['deal_id'] = $deal_id;

        $proposalVersion = (int) ($validated['proposal_version'] ?? 0);
        if ($proposalVersion < 1 || (int) $quotation->deal_id !== (int) $deal_id) {
            $proposalVersion = $this->nextProposalVersionForDeal((int) $deal_id, (int) $quotation->id);
        }

        $quotation->update([
            'subject' => $validated['subject'],
            'quotation_title' => trim((string) ($validated['quotation_title'] ?? '')) ?: null,
            'quotation_product_type' => $validated['quotation_product_type'] ?? 'default',
            'currency_code' => $validated['currency_code'] ?? 'INR',
            'currency_symbol' => $validated['currency_symbol'] ?? '₹',
            'organization_id' => $validated['organization_id'],
            'people_id' => $validated['people_id'],
            'deal_id' => $validated['deal_id'],
            'proposal_version' => $proposalVersion,
            'valid_until' => $validated['valid_until'],
            'status' => $validated['status'],
            'owner_id' => $validated['quotation_owner'],
            'description' => $this->sanitizeRichText($validated['description'] ?? null),
            'terms_conditions' => $this->sanitizeRichText($validated['terms_conditions'] ?? null),
            'project_detail' => $this->sanitizeRichText($validated['project_detail'] ?? null),
            'show_bank_details' => $request->boolean('show_bank_details'),
            'subtotal' => $validated['subtotal'],
            'discount' => $validated['overall_discount'] ?? 0,
            'discount_percent' => $validated['overall_discount_percent'] ?? 0,
            'tax' => $validated['overall_tax'] ?? 0,
            'adjustment' => $validated['adjustment'] ?? 0,
            'grand_total' => $validated['grand_total'],
            'updated_by' => auth()->id(),
        ]);

        // Allow changing quotation template on edit; refresh PDF snapshot from selected template.
        $templateService = app(QuotationTemplateService::class);
        $resolved = $templateService->resolveTemplateForQuotation(
            !empty($validated['quotation_template_id'])
                ? (int) $validated['quotation_template_id']
                : ($quotation->quotation_template_id ? (int) $quotation->quotation_template_id : null)
        );
        if ($resolved) {
            $quotation->update([
                'quotation_template_id' => $resolved['template']->id,
                'quotation_template_version_id' => $resolved['version']->id,
                'template_html_snapshot' => $resolved['version']->html_content,
                'template_css_snapshot' => $resolved['version']->css_content,
                'template_version_number' => $resolved['version']->version,
            ]);
        }

        $quotation->items()->delete();
        foreach ($validated['items'] as $item) {
            $quotation->items()->create(
                [
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'amount' => $item['amount'],
                    'discount' => $item['discount'] ?? 0,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'total' => $item['total'],
                ]
            );
        }

        // Update billing address & shipping address
        $billingAddress = $quotation->addresses()->where('address_type', 'billing')->first();
        if ($billingAddress) {
            $billingAddress->update([
                'flat_no' => $validated['billing_flat'],
                'address_line1' => $validated['billing_street'],
                'city' => $validated['billing_city'],
                'state' => $validated['billing_state'],
                'country' => $validated['billing_country'],
                'pincode' => $validated['billing_zip'],
            ]);
        } else {
            $quotation->addresses()->create([
                'quotation_id' => $quotation->id,
                'address_type' => 'billing',
                'flat_no' => $validated['billing_flat'],
                'address_line1' => $validated['billing_street'],
                'city' => $validated['billing_city'],
                'state' => $validated['billing_state'],
                'country' => $validated['billing_country'],
                'pincode' => $validated['billing_zip'],
            ]);
        }

        $shippingAddress = $quotation->addresses()->where('address_type', 'shipping')->first();
        if ($request->boolean('enable_shipping_address')) {
            $shippingData = [
                'flat_no' => $validated['shipping_flat'] ?? null,
                'address_line1' => $validated['shipping_street'] ?? null,
                'city' => $validated['shipping_city'] ?? null,
                'state' => $validated['shipping_state'] ?? null,
                'country' => $validated['shipping_country'] ?? null,
                'pincode' => $validated['shipping_zip'] ?? null,
            ];
        if ($shippingAddress) {
                $shippingAddress->update($shippingData);
        } else {
                $quotation->addresses()->create(array_merge([
                'quotation_id' => $quotation->id,
                'address_type' => 'shipping',
                ], $shippingData));
            }
        } elseif ($shippingAddress) {
            $shippingAddress->delete();
        }

        return redirect()->route('quotations.index')->with('success', 'Quotation updated successfully.');
    }

    public function destroy(Quotation $quotation)
    {
        /** delete permission check */
        if (!auth()->user()->hasCrmPermission('delete_crm_quotations_guard')) {
            abort(403);
        }
        $this->authorizeQuotationAccess($quotation);
        //soft delete the quotation and its items
        $quotation->items()->delete();
        $quotation->addresses()->delete();
        $quotation->delete();

        return redirect()->route('quotations.index')->with('success', 'Quotation deleted successfully.');
    }

    public function store(Request $request)
    {
        /** create permission check */
        if (!auth()->user()->hasCrmPermission('create_crm_quotations_guard')) {
            abort(403);
        }
        //Validation logic can be added here as needed
        $validated = $request->validate([
            'people_id' => 'required',
            'organization_id' => 'required',
            'deal_id' => 'required',
            'subject' => 'required|string|max:255',
            'quotation_title' => 'nullable|string|max:255',
            'quotation_product_type' => 'required|in:default,product',
            'currency_code' => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'project_detail' => 'nullable|string',
            'show_bank_details' => 'nullable|boolean',
            'proposal_version' => 'nullable|integer|min:1',
            'quotation_owner' => 'required|exists:users,id',
            'valid_until' => 'required|date',
            'status' => 'required|in:Draft,Negotiation,Delivered,On Hold,Confirmed,Closed Won,Closed Lost',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax' => 'nullable|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'overall_discount' => 'nullable|numeric|min:0',
            'overall_discount_percent' => 'nullable|numeric|min:0|max:100',
            'overall_tax' => 'nullable|numeric|min:0',
            'adjustment' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',

            'billing_flat' => 'nullable|string|max:255',
            'billing_street' => 'required|string|max:255',
            'billing_city' => 'required|string|max:255',
            'billing_state' => 'required|string|max:255',
            'billing_country' => 'required|string|max:255',
            'billing_zip' => 'required|max:20',

            'enable_shipping_address' => 'nullable|boolean',
            'shipping_flat' => 'nullable|string|max:255',
            'shipping_street' => ($request->boolean('enable_shipping_address') ? 'required' : 'nullable') . '|string|max:255',
            'shipping_city' => ($request->boolean('enable_shipping_address') ? 'required' : 'nullable') . '|string|max:255',
            'shipping_state' => ($request->boolean('enable_shipping_address') ? 'required' : 'nullable') . '|string|max:255',
            'shipping_country' => ($request->boolean('enable_shipping_address') ? 'required' : 'nullable') . '|string|max:255',
            'shipping_zip' => 'nullable|max:20',
            'quotation_template_id' => 'nullable|exists:quotation_templates,id',
        ]);

        $templateService = app(QuotationTemplateService::class);
        $resolvedTemplate = $templateService->resolveTemplateForQuotation(
            !empty($validated['quotation_template_id']) ? (int) $validated['quotation_template_id'] : null
        );

        if (!$resolvedTemplate) {
            return redirect()->back()->withInput()->withErrors([
                'quotation_template_id' => 'No active default quotation template is configured. Please ask a Super Admin to create and set a default template under Settings → Quotation Templates.',
            ]);
        }

        $organization_id = Organization::where('name', $validated['organization_id'])->first()->id;
        $validated['organization_id'] = $organization_id;
        $full_name = $validated['people_id'];

        $nameParts = explode(' ', trim($full_name));

        $first_name = $nameParts[0] ?? '';
        $last_name  = $nameParts[1] ?? null;

        $query = Person::where('first_name', $first_name)
            ->where('organization_id', $organization_id);

        if (!empty($last_name)) {
            $query->where('last_name', $last_name);
        } else {
            $query->whereNull('last_name'); // if DB column is NULL
        }

        $person = $query->first();

        $people_id = $person ? $person->id : null;
        $validated['people_id'] = $request->get('person_id'); //$people_id;
        $deal_id = \App\Models\Deal::where('title', $validated['deal_id'])->where('organization_id', $organization_id)->first()->id;
        $validated['deal_id'] = $deal_id;
        // Generate unique quote number & Cross check with DB to avoid duplicates
        do {
            $quote_no = 'Q-' . strtoupper(uniqid());
        } while (Quotation::where('quote_no', $quote_no)->exists());

        $proposalVersion = (int) ($validated['proposal_version'] ?? 0);
        if ($proposalVersion < 1) {
            $proposalVersion = $this->nextProposalVersionForDeal((int) $deal_id);
        }

        $data = [
            'quote_no' => $quote_no,
            'subject' => $validated['subject'],
            'quotation_title' => trim((string) ($validated['quotation_title'] ?? '')) ?: null,
            'quotation_product_type' => $validated['quotation_product_type'] ?? 'default',
            'currency_code' => $validated['currency_code'] ?? 'INR',
            'currency_symbol' => $validated['currency_symbol'] ?? '₹',
            'organization_id' => $validated['organization_id'],
            'people_id' => $validated['people_id'],
            'deal_id' => $validated['deal_id'],
            'proposal_version' => $proposalVersion,
            'valid_until' => $validated['valid_until'],
            'status' => $validated['status'],
            'owner_id' => $validated['quotation_owner'],
            'description' => $this->sanitizeRichText($validated['description'] ?? null),
            'terms_conditions' => $this->sanitizeRichText($validated['terms_conditions'] ?? null),
            'project_detail' => $this->sanitizeRichText($validated['project_detail'] ?? null),
            'show_bank_details' => $request->boolean('show_bank_details'),
            'quotation_template_id' => $resolvedTemplate['template']->id,
            'quotation_template_version_id' => $resolvedTemplate['version']->id,
            'template_html_snapshot' => $resolvedTemplate['version']->html_content,
            'template_css_snapshot' => $resolvedTemplate['version']->css_content,
            'template_version_number' => $resolvedTemplate['version']->version,
            'subtotal' => $validated['subtotal'],
            'discount' => $validated['overall_discount'] ?? 0,
            'discount_percent' => $validated['overall_discount_percent'] ?? 0,
            'tax' => $validated['overall_tax'] ?? 0,
            'adjustment' => $validated['adjustment'] ?? 0,
            'grand_total' => $validated['grand_total'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];
         $quotation = Quotation::create($data);
        if(!$quotation) {
            return redirect()->back()->withInput()->withErrors(['error' => 'Failed to create quotation. Please try again.']);
        }

        $deals = \App\Models\Deal::find($deal_id);
        if ($deals) {
            $proposalStageId = \App\Models\Stage::proposalPriceQuoteId();
            if ($proposalStageId) {
                $deals->update(['stage_id' => $proposalStageId]);
            }
            \App\Models\StageHistory::create([
                'deal_id' => $deals->id,
                'stage_name' => \App\Models\Stage::nameById($proposalStageId) ?? 'Proposal/Price Quote',
                'amount' => $deals->amount,
                'probability' => $deals->probability,
                'close_date' => $deals->close_date,
                'modified_time' => now(),
                'modified_by' => auth()->id(),
            ]);
        }
        foreach ($validated['items'] as $item) {
            $quotation->items()->create(
                [
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'amount' => $item['amount'],
                    'discount' => $item['discount'] ?? 0,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'total' => $item['total'],
                ]
            );
        }

        // Create billing address
        $quotation->addresses()->create([
            'quotation_id' => $quotation->id,
            'address_type' => 'billing',
            'flat_no' => $validated['billing_flat'],
            'address_line1' => $validated['billing_street'],
            'city' => $validated['billing_city'],
            'state' => $validated['billing_state'],
            'country' => $validated['billing_country'],
            'pincode' => $validated['billing_zip'],
        ]);
        // Create shipping address only when enabled
        if ($request->boolean('enable_shipping_address')) {
        $quotation->addresses()->create([
            'quotation_id' => $quotation->id,
            'address_type' => 'shipping',
                'flat_no' => $validated['shipping_flat'] ?? null,
                'address_line1' => $validated['shipping_street'] ?? null,
                'city' => $validated['shipping_city'] ?? null,
                'state' => $validated['shipping_state'] ?? null,
                'country' => $validated['shipping_country'] ?? null,
                'pincode' => $validated['shipping_zip'] ?? null,
            ]);
        }

        return redirect()->route('quotations.index')->with('success', 'Quotation created successfully.');

    }

    public function show($id)
    {
        /** view   */
        if (!auth()->user()->hasCrmPermission('view_crm_quotations_guard')) {
            abort(403);
        }
        $quotation = Quotation::with(
            'items.product',
            'organization',
            'people',
            'deal',
            'owner',
            'addresses'
        )->findOrFail($id);
        $this->authorizeQuotationAccess($quotation);

        $visibleSenderIds = $this->visibleTeamUserIds();
        $quotation->load([
            'whatsappSendLogs' => function ($q) use ($visibleSenderIds) {
                if ($visibleSenderIds !== null) {
                    $q->whereIn('sent_by', $visibleSenderIds);
                }
                $q->with('sender')->latest();
            },
            'emailSendLogs' => function ($q) use ($visibleSenderIds) {
                if ($visibleSenderIds !== null) {
                    $q->whereIn('sent_by', $visibleSenderIds);
                }
                $q->with('sender')->latest();
            },
        ]);
        $company = Company::first();

        $whatsappSetting = \App\Models\WhatsappSetting::getSettings();
        $whatsappEnabled = (bool) ($whatsappSetting && $whatsappSetting->exists && $whatsappSetting->is_active
            && !empty($whatsappSetting->whatsapp_access_token));

        $emailSetting = EmailSetting::where('is_active', true)->first();
        $emailEnabled = (bool) ($emailSetting && !empty($emailSetting->mail_from_address) && !empty(auth()->user()->email));

        $emailTemplates = QuotationEmailTemplate::active()->orderByDesc('is_default')->orderBy('name')->get();
        $defaultEmailTemplate = $emailTemplates->firstWhere('is_default', true) ?? $emailTemplates->first();
        $emailTemplatePayloads = [];
        $composeSettings = app(EmailComposeSettingsService::class);
        foreach ($emailTemplates as $template) {
            $rendered = $template->renderForQuotation($quotation, $company);
            $rendered['body'] = $composeSettings->prepareQuotationBody(auth()->user(), $rendered['body']);
            $emailTemplatePayloads[$template->id] = $rendered;
        }

        return view('quotations.show', compact(
            'quotation',
            'whatsappEnabled',
            'emailEnabled',
            'emailSetting',
            'company',
            'whatsappSetting',
            'emailTemplates',
            'defaultEmailTemplate',
            'emailTemplatePayloads'
        ));
    }

    public function whatsappSendLogs(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_quotations_guard')) {
            abort(403);
        }

        $logs = QuotationWhatsappSendLog::query()
            ->with(['quotation', 'sender']);
        $this->applySendLogVisibilityScope($logs);
        $logs = $logs
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('recipient', 'like', $term)
                        ->orWhereHas('quotation', fn ($q2) => $q2->where('quote_no', 'like', $term));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('quotations.whatsapp_send_logs', compact('logs'));
    }

    public function emailSendLogs(Request $request)
    {
        if (!auth()->user()->hasCrmPermission('manage_crm_quotations_guard')) {
            abort(403);
        }

        $logs = QuotationEmailSendLog::query()
            ->with(['quotation', 'sender']);
        $this->applySendLogVisibilityScope($logs);
        $logs = $logs
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('recipient', 'like', $term)
                        ->orWhere('subject', 'like', $term)
                        ->orWhereHas('quotation', fn ($q2) => $q2->where('quote_no', 'like', $term));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('quotations.email_send_logs', compact('logs'));
    }

    public function generatePdf($id)
    {
        if (!auth()->user()->hasCrmPermission('download_pdf_crm_quotations_guard')) {
            abort(403);
        }
        $quotation = Quotation::with(['items.product', 'addresses'])->findOrFail($id);
        $this->authorizeQuotationAccess($quotation);
        $company = Company::first(); // Assuming you have a single company record. Adjust as needed.

        // Generate PDF using Puppeteer
        $viewContent = view('quotations.invoice', compact('quotation', 'company'))->render();
        $tempHtmlPath = storage_path('app/temp_' . Str::random(16) . '.html');
        file_put_contents($tempHtmlPath, $viewContent);

        $puppeteerScript = base_path('scripts/generate_pdf.js');
        // Generate a dynamic file name based on the quote number
        $quoteNumber = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string) ($quotation->quote_no ?? 'default'));
        $outputPdfPath = storage_path("app/quote_{$quoteNumber}.pdf");

        // Log Puppeteer output for debugging
        exec(sprintf('node %s %s %s 2>&1', escapeshellarg($puppeteerScript), escapeshellarg($tempHtmlPath), escapeshellarg($outputPdfPath)), $output, $returnVar);
        @unlink($tempHtmlPath);
        if ($returnVar !== 0) {
            \Log::error('Puppeteer Output: ' . implode("\n", $output));
            throw new \Exception('Failed to generate PDF using Puppeteer.');
        }

        return response()->download($outputPdfPath)->deleteFileAfterSend(true);
    }


    // public function downloadQuotation($id)
    // {
    //     $quotation = Quotation::with(['items.product', 'organization', 'addresses'])->findOrFail($id);
    //     $company = Company::first();

    //     // Initialize TCPDF with updated settings
    //     $pdf = new \TCPDF();
    //     $pdf->SetCreator(PDF_CREATOR);
    //     $pdf->SetAuthor($company->name ?? 'Company Name');
    //     $pdf->SetTitle('Quotation');
    //     $pdf->SetSubject('Quotation PDF');
    //     $pdf->SetKeywords('Quotation, PDF, TCPDF');

    //     // Set margins and add a page
    //     $pdf->SetMargins(10, 10, 10);
    //     $pdf->AddPage();

    //     // Render the HTML content for the PDF
    //     $html = view('pdf.quotation_tcpdf', compact('quotation', 'company'))->render();

    //     // Write the HTML content to the PDF
    //     $pdf->writeHTML($html, true, false, true, false, '');

    //     // Output the PDF to the browser
    //     return $pdf->Output('quotation_' . $quotation->quote_no . '.pdf', 'I');
    // }

    public function downloadQuotation($id)
    {
        if (!auth()->user()->hasCrmPermission('download_pdf_crm_quotations_guard')) {
            abort(403);
        }
        $quotation = Quotation::with(['items.product', 'organization', 'addresses'])->findOrFail($id);
        $this->authorizeQuotationAccess($quotation);
        $company = Company::first();

        $built = $this->buildQuotationPdf($quotation, $company);

        return response($built['binary'], 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $built['filename'] . '"',
        ]);
    }

    /**
     * Send quotation via WhatsApp template (with PDF header) to entered number.
     * Uses the same PDF as /quotations/download/{id}.
     */
    public function sendWhatsapp(Request $request, $id)
    {
        if (!auth()->user()->canSendQuotationWhatsApp()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'whatsapp_number' => 'required|string|max:30',
        ]);

        $quotation = Quotation::with(['people', 'organization', 'owner', 'items.product', 'addresses'])
            ->findOrFail($id);
        $this->authorizeQuotationAccess($quotation);

        $company = Company::first();
        $contactName = trim(optional($quotation->people)->full_name ?? optional($quotation->people)->first_name ?? 'Customer');
        $companyName = $company->name ?? config('app.name', 'CRM');
        $quoteNo = $quotation->quote_no ?? ('Q-' . $quotation->id);
        $clientName = optional($quotation->organization)->name ?? $contactName;
        $amount = format_email_money(
            $quotation->grand_total ?? 0,
            trim((string) ($quotation->currency_symbol ?? '')) ?: (trim((string) (optional($company)->currency_symbol ?? '')) ?: '₹'),
            trim((string) ($quotation->currency_code ?? '')) ?: (trim((string) (optional($company)->currency_code ?? '')) ?: 'INR')
        );
        $senderName = optional($quotation->owner)->name
            ?? optional(auth()->user())->name
            ?? $companyName;

        $messageData = WhatsappSetting::quotationMessageVariables($quotation, $company, auth()->user());
        $bodyParams = $messageData['body_params'];

        try {
            // Same PDF engine/content as quotations/download/{id}
            $pdfMeta = $this->storeQuotationPdfForWhatsapp($quotation, $company);
        } catch (\Throwable $e) {
            \Log::error('Quotation WhatsApp PDF generation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'Failed to generate quotation PDF. Please try again.',
            ], 500);
        }

        $service = new WhatsappService();
        $result = $service->sendQuotationTemplateMessage(
            $validated['whatsapp_number'],
            $bodyParams,
            $pdfMeta['url'],
            $pdfMeta['filename'],
            null,
            null,
            $pdfMeta['path']
        );

        if ($result['success']) {
            $whatsappSetting = WhatsappSetting::getSettings();
            $this->recordQuotationWhatsappSendLog($quotation, [
                'status' => 'sent',
                'recipient' => $validated['whatsapp_number'],
                'message_preview' => WhatsappSetting::renderQuotationMessageFormat(
                    $whatsappSetting->quotation_message_format ?? '',
                    $messageData['variables']
                ),
                'meta' => [
                    'quote_no' => $quoteNo,
                    'pdf_filename' => $pdfMeta['filename'],
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quotation sent successfully via WhatsApp.',
                'data'    => $result['data'] ?? [],
                'pdf'     => [
                    'filename' => $pdfMeta['filename'],
                    'url'      => $pdfMeta['url'],
                ],
            ]);
        }

        $this->recordQuotationWhatsappSendLog($quotation, [
            'status' => 'failed',
            'recipient' => $validated['whatsapp_number'],
            'error_message' => $result['error'] ?? 'Failed to send WhatsApp message.',
            'meta' => [
                'quote_no' => $quoteNo,
                'details' => $result['details'] ?? null,
            ],
        ]);

        return response()->json([
            'success'  => false,
            'disabled' => $result['disabled'] ?? false,
            'error'    => $result['error'] ?? 'Failed to send WhatsApp message.',
            'details'  => $result['details'] ?? null,
        ], 422);
    }

    /**
     * Send quotation via email with PDF attachment and custom HTML body.
     */
    public function sendEmail(Request $request, $id)
    {
        if (!auth()->user()->canSendQuotationEmail()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $emailSetting = EmailSetting::where('is_active', true)->first();
        if (!$emailSetting || empty($emailSetting->mail_from_address)) {
            return response()->json([
                'success'  => false,
                'disabled' => true,
                'error'    => 'Email integration is disabled. Configure and enable Email Settings first.',
            ], 422);
        }

        $validated = $request->validate([
            'to'          => 'required|string|max:2000',
            'cc'          => 'nullable|string|max:2000',
            'bcc'         => 'nullable|string|max:2000',
            'subject'     => 'required|string|max:255',
            'body'        => 'required|string|max:50000',
            'template_id' => 'nullable|integer|exists:quotation_email_templates,id',
        ]);

        $toList = $this->parseEmailList($validated['to']);
        if (empty($toList)) {
            return response()->json([
                'success' => false,
                'error'   => 'Please enter at least one valid recipient email address.',
            ], 422);
        }

        $ccList = $this->parseEmailList($validated['cc'] ?? '');
        $bccList = $this->parseEmailList($validated['bcc'] ?? '');

        $quotation = Quotation::with(['people', 'organization', 'owner', 'items.product', 'addresses'])
            ->findOrFail($id);
        $this->authorizeQuotationAccess($quotation);
        $company = Company::first();
        $emailTemplate = !empty($validated['template_id'])
            ? QuotationEmailTemplate::active()->find((int) $validated['template_id'])
            : null;

        try {
            $built = $this->buildQuotationPdf($quotation, $company);
        } catch (\Throwable $e) {
            Log::error('Quotation email PDF generation failed', [
                'quotation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Failed to generate quotation PDF. Please try again.',
            ], 500);
        }

        EmailService::applyConfiguration();

        $sender = $this->quotationMailSender($emailSetting);
        if (empty($sender['reply_to'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Your account does not have an email address. Update your profile before sending mail.',
            ], 422);
        }

        try {
            $mail = Mail::to($toList);
            if (!empty($ccList)) {
                $mail->cc($ccList);
            }
            if (!empty($bccList)) {
                $mail->bcc($bccList);
            }

            $logoPath = QuotationEmailTemplate::resolveCompanyLogoPath($company);
            $emailBody = QuotationEmailTemplate::sanitizeEmailBodyHtml($validated['body']);
            $emailBody = QuotationEmailTemplate::embedEmailLogos($emailBody, $company);
            $emailBody = app(EmailComposeSettingsService::class)->finalizeOutgoingHtml(auth()->user(), $emailBody);

            $mail->send(new QuotationSentMail(
                $validated['subject'],
                $emailBody,
                $built['binary'],
                $built['filename'],
                $sender['address'],
                $sender['name'],
                $sender['reply_to'],
                $logoPath,
                QuotationEmailTemplate::resolveCompanyLogoMime($logoPath),
            ));
        } catch (\Throwable $e) {
            Log::error('Quotation email send failed', [
                'quotation_id' => $id,
                'from' => $sender['address'] ?? null,
                'reply_to' => $sender['reply_to'] ?? null,
                'error' => $e->getMessage(),
            ]);

            $this->recordQuotationEmailSendLog($quotation, [
                'status' => 'failed',
                'recipient' => implode(', ', $toList),
                'cc' => implode(', ', $ccList),
                'bcc' => implode(', ', $bccList),
                'subject' => $validated['subject'],
                'from_address' => $sender['address'] ?? null,
                'error_message' => $e->getMessage() ?: 'Failed to send email.',
                'meta' => [
                    'email_template_id' => $emailTemplate?->id,
                    'email_template_name' => $emailTemplate?->name,
                ],
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage() ?: 'Failed to send email. Check Email Settings.',
            ], 422);
        }

        $this->recordQuotationEmailSendLog($quotation, [
            'status' => 'sent',
            'recipient' => implode(', ', $toList),
            'cc' => implode(', ', $ccList) ?: null,
            'bcc' => implode(', ', $bccList) ?: null,
            'subject' => $validated['subject'],
            'from_address' => $sender['address'],
            'reply_to' => $sender['reply_to'],
            'message_preview' => Str::limit(trim(strip_tags($validated['body'])), 500),
            'pdf_filename' => $built['filename'],
            'meta' => [
                'reply_to' => $sender['reply_to'],
                'pdf_filename' => $built['filename'],
                'email_template_id' => $emailTemplate?->id,
                'email_template_name' => $emailTemplate?->name,
            ],
        ]);

        Log::info('Quotation email sent', [
            'quotation_id' => $id,
            'to' => $toList,
            'from' => $sender['address'],
            'reply_to' => $sender['reply_to'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quotation email sent successfully. If the recipient does not see it, ask them to check spam.',
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function parseEmailList(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $parts = preg_split('/[,;]+/', $value) ?: [];

        return array_values(array_unique(array_filter(array_map(function ($email) {
            $email = trim($email);
            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }, $parts))));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function recordQuotationWhatsappSendLog(Quotation $quotation, array $payload): void
    {
        try {
            $meta = $payload['meta'] ?? [];
            QuotationWhatsappSendLog::create([
                'quotation_id'    => $quotation->id,
                'status'          => $payload['status'] ?? 'sent',
                'recipient'       => $payload['recipient'] ?? null,
                'sent_by'         => auth()->id(),
                'message_preview' => $payload['message_preview'] ?? null,
                'pdf_filename'    => $payload['pdf_filename'] ?? ($meta['pdf_filename'] ?? null),
                'error_message'   => $payload['error_message'] ?? null,
                'meta'            => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write quotation WhatsApp send log', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function recordQuotationEmailSendLog(Quotation $quotation, array $payload): void
    {
        try {
            $meta = $payload['meta'] ?? [];
            QuotationEmailSendLog::create([
                'quotation_id'    => $quotation->id,
                'status'          => $payload['status'] ?? 'sent',
                'recipient'       => $payload['recipient'] ?? null,
                'cc'              => $payload['cc'] ?? null,
                'bcc'             => $payload['bcc'] ?? null,
                'subject'         => $payload['subject'] ?? null,
                'from_address'    => $payload['from_address'] ?? null,
                'reply_to'        => $payload['reply_to'] ?? ($meta['reply_to'] ?? null),
                'sent_by'         => auth()->id(),
                'message_preview' => $payload['message_preview'] ?? null,
                'pdf_filename'    => $payload['pdf_filename'] ?? ($meta['pdf_filename'] ?? null),
                'error_message'   => $payload['error_message'] ?? null,
                'meta'            => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write quotation email send log', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Public temporary PDF endpoint for WhatsApp providers to download the document.
     */
    public function whatsappPdf(string $token)
    {
        $cacheKey = 'wa_quote_pdf_' . $token;
        $meta = Cache::get($cacheKey);

        if (!$meta || empty($meta['path']) || !is_file($meta['path'])) {
            abort(404, 'Quotation PDF link expired or not found.');
        }

        $filename = $meta['filename'] ?? 'quotation.pdf';

        return response()->file($meta['path'], [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Build the same PDF used by /quotations/download/{id}.
     * Build the same PDF used by /quotations/download/{id} via mPDF.
     *
     * @return array{binary: string, filename: string}
     */
    protected function buildQuotationPdf(Quotation $quotation, $company): array
    {
        $quotation->loadMissing(['items.product', 'organization', 'addresses', 'people', 'owner', 'deal']);
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $quotation->quote_no ?? ('quote_' . $quotation->id)) . '.pdf';

        $templateService = app(QuotationTemplateService::class);
        $customHtml = $templateService->renderQuotationHtml($quotation, $company);

        return $templateService->generatePdfFromHtml($customHtml, $filename);
    }

    /**
     * Generate the download-route PDF to disk and return path + public URL for WhatsApp.
     *
     * @return array{url: string, filename: string, path: string, token: string}
     */
    protected function storeQuotationPdfForWhatsapp(Quotation $quotation, $company): array
    {
        $built = $this->buildQuotationPdf($quotation, $company);
        $filename = $built['filename'];
        $token = Str::random(40);
        $storageName = $token . '_' . $filename;

        $dir = storage_path('app/public/whatsapp-quotations');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $publicDir = public_path('whatsapp-quotations');
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        $path = $dir . DIRECTORY_SEPARATOR . $storageName;
        $publicPath = $publicDir . DIRECTORY_SEPARATOR . $storageName;

        file_put_contents($path, $built['binary']);
        @file_put_contents($publicPath, $built['binary']);

        Cache::put('wa_quote_pdf_' . $token, [
            'quotation_id' => $quotation->id,
            'path'         => $path,
            'filename'     => $filename,
        ], now()->addHours(6));

        // Use public APP_URL when set (Isarva/Meta must fetch PDF from internet)
        $appUrl = rtrim((string) config('app.url'), '/');
        if (!empty($appUrl) && !preg_match('#^https?://(localhost|127\.0\.0\.1)#i', $appUrl)) {
            $url = $appUrl . '/whatsapp-quotations/' . rawurlencode($storageName);
        } else {
            $url = url('/whatsapp-quotations/' . rawurlencode($storageName));
        }

        return [
            'url'      => $url,
            'filename' => $filename,
            'path'     => $path,
            'token'    => $token,
        ];
    }

    public function emailComposeData(Request $request, $id)
    {
        if (!auth()->user()->canSendQuotationEmail()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $quotation = Quotation::with(['people', 'organization', 'owner'])->findOrFail($id);
        $emailSetting = EmailSetting::where('is_active', true)->first();
        if (!$emailSetting || empty($emailSetting->mail_from_address)) {
            return response()->json([
                'success'  => false,
                'disabled' => true,
                'error'    => 'Email integration is disabled. Configure and enable Email Settings first.',
            ], 422);
        }

        $sender = $this->quotationMailSender($emailSetting);
        if (empty($sender['reply_to'])) {
            return response()->json([
                'success'  => false,
                'disabled' => true,
                'error'    => 'Your account does not have an email address. Update your profile before sending mail.',
            ], 422);
        }

        $company = Company::first();
        $template = $this->resolveQuotationEmailTemplate($request->query('template_id'));
        $payload = $this->quotationEmailComposeDefaults($quotation, $emailSetting, $company, $template);
        $payload['success'] = true;
        $payload['quotation_id'] = $quotation->id;
        $payload['send_url'] = route('quotations.send_email', $quotation->id);
        $payload['templates'] = QuotationEmailTemplate::active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default'])
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'is_default' => (bool) $item->is_default,
            ])
            ->values()
            ->all();

        return response()->json($payload);
    }

    /**
     * Gmail compose data for quotation (separate from SMTP send email).
     */
    public function gmailComposeData(Request $request, $id, GoogleGmailService $gmailService)
    {
        if (!auth()->user()->canSendQuotationEmail()) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
            }
            abort(403);
        }

        $user = auth()->user();
        if (empty($user->email)) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Your account does not have an email address. Update your profile first.',
                ], 422);
            }

            return redirect()->route('quotations.show', $id)->with(
                'error',
                'Your account does not have an email address. Update your profile first.'
            );
        }

        $quotation = Quotation::with(['people', 'organization', 'owner'])->findOrFail($id);
        $this->authorizeQuotationAccess($quotation);

        // Browser visit — show quotation page with Gmail compose modal (not raw JSON).
        if (!$request->ajax() && !$request->expectsJson()) {
            return redirect()->route('quotations.show', $quotation->id)->with('open_gmail', true);
        }

        $company = Company::first();
        $template = $this->resolveQuotationEmailTemplate($request->query('template_id'));
        $defaults = $this->quotationEmailComposeDefaults($quotation, null, $company, $template);

        return response()->json([
            'success'          => true,
            'quotation_id'     => $quotation->id,
            'to_email'         => $defaults['to_email'],
            'to_name'          => $defaults['to_name'],
            'to_label'         => $defaults['to_label'],
            'subject'          => $defaults['subject'],
            'body'             => $defaults['body'],
            'pdf_filename'     => $defaults['pdf_filename'],
            'modal_title'      => $quotation->quote_no,
            'template_id'      => $template?->id,
            'templates'        => QuotationEmailTemplate::active()
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'is_default'])
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'is_default' => (bool) $item->is_default,
                ])
                ->values()
                ->all(),
            'send_url'         => route('quotations.send_gmail', $quotation->id),
            'connect_url'      => route('google.gmail.connect', [
                'return' => route('quotations.show', $quotation->id) . '?open_gmail=1',
            ]),
            'gmail_connected'  => $gmailService->userHasGmailAccess($user),
            'from_address'     => $user->email,
            'from_name'        => trim((string) ($user->name ?? '')) ?: config('app.name'),
            'crm_login_email'  => $user->email,
        ]);
    }

    /**
     * Send quotation via user's connected Gmail account (separate from SMTP send email).
     */
    public function sendGmail(Request $request, $id, GoogleGmailService $gmailService)
    {
        if (!auth()->user()->canSendQuotationEmail()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $user = auth()->user();
        if (!$gmailService->userHasGmailAccess($user)) {
            return response()->json([
                'success'       => false,
                'needs_connect' => true,
                'connect_url'   => route('google.gmail.connect', [
                    'return' => route('quotations.show', $id) . '?open_gmail=1',
                ]),
                'error'         => 'Connect your Gmail account before sending. Use the same email as your CRM login (' . $user->email . ').',
            ], 422);
        }

        $validated = $request->validate([
            'to'          => 'required|string|max:2000',
            'cc'          => 'nullable|string|max:2000',
            'bcc'         => 'nullable|string|max:2000',
            'subject'     => 'required|string|max:255',
            'body'        => 'required|string|max:50000',
            'template_id' => 'nullable|integer|exists:quotation_email_templates,id',
        ]);

        $toList = $this->parseEmailList($validated['to']);
        if (empty($toList)) {
            return response()->json([
                'success' => false,
                'error'   => 'Please enter at least one valid recipient email address.',
            ], 422);
        }

        $ccList = $this->parseEmailList($validated['cc'] ?? '');
        $bccList = $this->parseEmailList($validated['bcc'] ?? '');

        $quotation = Quotation::with(['people', 'organization', 'owner', 'items.product', 'addresses'])
            ->findOrFail($id);
        $this->authorizeQuotationAccess($quotation);
        $company = Company::first();
        $emailTemplate = !empty($validated['template_id'])
            ? QuotationEmailTemplate::active()->find((int) $validated['template_id'])
            : null;

        try {
            $built = $this->buildQuotationPdf($quotation, $company);
        } catch (\Throwable $e) {
            Log::error('Quotation Gmail PDF generation failed', [
                'quotation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Failed to generate quotation PDF. Please try again.',
            ], 500);
        }

        $htmlBody = QuotationEmailTemplate::sanitizeEmailBodyHtml($validated['body']);
        $htmlBody = QuotationEmailTemplate::embedEmailLogos($htmlBody, $company);
        if (strip_tags($htmlBody) === $htmlBody) {
            $htmlBody = nl2br(e($htmlBody));
        }
        $htmlBody = app(EmailComposeSettingsService::class)->finalizeOutgoingHtml($user, $htmlBody);

        $gmailAttachments = [[
            'filename' => $built['filename'],
            'mime'     => 'application/pdf',
            'content'  => $built['binary'],
        ]];

        $logoPath = QuotationEmailTemplate::resolveCompanyLogoPath($company);
        if ($logoPath && is_file($logoPath) && str_contains($htmlBody, QuotationEmailTemplate::EMAIL_LOGO_CID_SRC)) {
            $logoBinary = @file_get_contents($logoPath);
            if ($logoBinary !== false && $logoBinary !== '') {
                $gmailAttachments[] = [
                    'filename'   => basename($logoPath),
                    'mime'       => QuotationEmailTemplate::resolveCompanyLogoMime($logoPath) ?: 'image/png',
                    'content'    => $logoBinary,
                    'content_id' => QuotationEmailTemplate::EMAIL_LOGO_CID,
                    'inline'     => true,
                ];
            }
        }

        try {
            $gmailService->sendMessage(
                $user,
                $toList,
                $ccList,
                $bccList,
                $validated['subject'],
                $htmlBody,
                $gmailAttachments
            );
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Failed to send via Gmail.';
            $needsReconnect = str_contains(strtolower($message), 'insufficient')
                || str_contains(strtolower($message), 'permission')
                || str_contains(strtolower($message), 'gmail send permission')
                || str_contains(strtolower($message), 'reconnect');

            if ($needsReconnect) {
                try {
                    $gmailService->clearUserGmailTokens($user);
                } catch (\Throwable $ignored) {
                }

                return response()->json([
                    'success'       => false,
                    'needs_connect' => true,
                    'connect_url'   => route('google.gmail.connect', [
                        'return' => route('quotations.show', $id) . '?open_gmail=1',
                    ]),
                    'error'         => 'Gmail permission is missing or expired. Please reconnect Gmail and allow “Send email on your behalf”.',
                ], 422);
            }

            Log::error('Quotation Gmail send failed', [
                'quotation_id' => $id,
                'from' => $user->email,
                'error' => $message,
            ]);

            $this->recordQuotationEmailSendLog($quotation, [
                'status' => 'failed',
                'recipient' => implode(', ', $toList),
                'cc' => implode(', ', $ccList),
                'bcc' => implode(', ', $bccList),
                'subject' => $validated['subject'],
                'from_address' => $user->email,
                'error_message' => $e->getMessage() ?: 'Failed to send via Gmail.',
                'meta' => [
                    'channel' => 'gmail',
                    'email_template_id' => $emailTemplate?->id,
                    'email_template_name' => $emailTemplate?->name,
                ],
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage() ?: 'Failed to send via Gmail.',
            ], 422);
        }

        $this->recordQuotationEmailSendLog($quotation, [
            'status' => 'sent',
            'recipient' => implode(', ', $toList),
            'cc' => implode(', ', $ccList) ?: null,
            'bcc' => implode(', ', $bccList) ?: null,
            'subject' => $validated['subject'],
            'from_address' => $user->email,
            'reply_to' => $user->email,
            'message_preview' => Str::limit(trim(strip_tags($validated['body'])), 500),
            'pdf_filename' => $built['filename'],
            'meta' => [
                'channel' => 'gmail',
                'pdf_filename' => $built['filename'],
                'email_template_id' => $emailTemplate?->id,
                'email_template_name' => $emailTemplate?->name,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quotation sent via Gmail successfully.',
        ]);
    }

    protected function resolveQuotationEmailTemplate($templateId = null): ?QuotationEmailTemplate
    {
        if ($templateId) {
            return QuotationEmailTemplate::active()->find((int) $templateId);
        }

        return QuotationEmailTemplate::getDefault();
    }

    protected function quotationEmailComposeDefaults(
        Quotation $quotation,
        ?EmailSetting $emailSetting = null,
        ?Company $company = null,
        ?QuotationEmailTemplate $template = null
    ): array {
        $emailSetting = $emailSetting ?? EmailSetting::where('is_active', true)->first();
        $company = $company ?? Company::first();

        $defaultToEmail = optional($quotation->people)->email ?? '';
        $defaultToName = trim(
            (optional($quotation->people)->first_name ?? '') . ' ' .
            (optional($quotation->people)->last_name ?? '')
        );
        $fromName = optional($emailSetting)->mail_from_name ?? config('mail.from.name');
        $pdfFilename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $quotation->quote_no ?? ('quote_' . $quotation->id)) . '.pdf';
        $template = $template ?? QuotationEmailTemplate::getDefault();

        if ($template) {
            $rendered = $template->renderForQuotation($quotation, $company);
            $defaultSubject = $rendered['subject'];
            $defaultBody = $rendered['body'];
        } else {
            $fromName = optional($emailSetting)->mail_from_name ?? config('mail.from.name');
            $defaultSubject = 'Quotation' . (!empty($quotation->subject) ? ' — ' . $quotation->subject : ' — ' . $quotation->quote_no);
            $senderName = optional($quotation->owner)->name ?? optional(auth()->user())->name ?? ($fromName ?: 'CRM');
            $contactLabel = $defaultToName !== '' ? $defaultToName : ($defaultToEmail ?: 'Customer');
            $defaultBody = '<p>Dear ' . e($contactLabel) . ',</p>'
                . '<p>Greetings from <strong>' . e($company->name ?? config('app.name')) . '</strong>.</p>'
                . '<p>Please find attached Quotation No. <strong>' . e($quotation->quote_no) . '</strong>'
                . (optional($quotation->organization)->name ? ' for <strong>' . e(optional($quotation->organization)->name) . '</strong>' : '')
                . '. Total amount: <strong>' . e(format_email_money(
                    $quotation->grand_total ?? 0,
                    trim((string) ($quotation->currency_symbol ?? '')) ?: (trim((string) (optional($company)->currency_symbol ?? '')) ?: '₹'),
                    trim((string) ($quotation->currency_code ?? '')) ?: (trim((string) (optional($company)->currency_code ?? '')) ?: 'INR')
                )) . '</strong>.</p>'
                . '<p>Kindly review and share your confirmation at your earliest convenience.</p>'
                . '<p>Best regards,<br>' . e($senderName) . '</p>';
        }

        $toLabel = $defaultToName !== '' ? $defaultToName . ' (' . $defaultToEmail . ')' : $defaultToEmail;
        $sender = $this->quotationMailSender($emailSetting);

        $compose = app(EmailComposeSettingsService::class);
        $defaultBody = $compose->prepareQuotationBody(auth()->user(), $defaultBody);

        return [
            'to_email'       => $defaultToEmail,
            'to_name'        => $defaultToName,
            'to_label'       => $toLabel,
            'subject'        => $defaultSubject,
            'body'           => $defaultBody,
            'pdf_filename'   => $pdfFilename,
            'modal_title'    => $quotation->quote_no,
            'template_id'    => $template?->id,
            'from_address'   => $sender['display_address'],
            'editor_style'   => $compose->editorContentStyle(auth()->user()),
            'from_name'      => $sender['name'],
            'reply_to'       => $sender['reply_to'],
            'transport_from' => $sender['address'],
        ];
    }

    /**
     * @return array{address: string, name: string, reply_to: string, display_address: string}
     */
    protected function quotationMailSender(?EmailSetting $emailSetting = null): array
    {
        $user = auth()->user();
        $emailSetting = $emailSetting ?? EmailSetting::where('is_active', true)->first();

        $userEmail = trim((string) ($user->email ?? ''));
        $userName = trim((string) ($user->name ?? '')) ?: config('app.name');
        $verifiedAddress = trim((string) ($emailSetting->mail_from_address ?? ''));
        $useUserAsFrom = $userEmail !== '' && $verifiedAddress !== '' && (
            strcasecmp($userEmail, $verifiedAddress) === 0
            || $this->emailsShareDomain($userEmail, $verifiedAddress)
        );

        $transportFrom = $useUserAsFrom ? $userEmail : ($verifiedAddress ?: $userEmail);

        return [
            'address' => $transportFrom,
            'name' => $userName,
            'reply_to' => $userEmail ?: $verifiedAddress,
            'display_address' => $userEmail ?: $transportFrom,
        ];
    }

    protected function emailsShareDomain(string $emailA, string $emailB): bool
    {
        $domainA = strtolower((string) substr(strrchr($emailA, '@') ?: '', 1));
        $domainB = strtolower((string) substr(strrchr($emailB, '@') ?: '', 1));

        return $domainA !== '' && $domainA === $domainB;
    }

    public function productMeta(Product $product)
    {
        if (
            !auth()->user()->hasCrmPermission('create_crm_quotations_guard')
            && !auth()->user()->hasCrmPermission('edit_crm_quotations_guard')
        ) {
            abort(403);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->product_name,
            'unit_price' => (float) $product->unit_price,
            'product_type' => $product->product_type ?? 'default',
            'project_detail' => $product->project_detail,
        ]);
    }

    /**
     * Live HTML preview of selected quotation template using wizard form values.
     *
     * @deprecated Use QuotationTemplateController::wizardDraftPreview
     */
    public function wizardTemplatePreview(Request $request)
    {
        return app(\App\Http\Controllers\QuotationTemplateController::class)->wizardDraftPreview($request);
    }

    public function uploadEditorImage(Request $request)
    {
        if (
            !auth()->user()->hasCrmPermission('create_crm_quotations_guard')
            && !auth()->user()->hasCrmPermission('edit_crm_quotations_guard')
        ) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        $file = $request->file('file');
        $dir = 'quotation-editor/' . auth()->id();
        $path = $file->store($dir, 'public');

        return response()->json([
            'location' => asset('storage/' . $path),
        ]);
    }

    /**
     * Next proposal version for a deal (AJAX).
     */
    public function nextProposalVersion(Request $request)
    {
        $dealId = (int) $request->get('deal_id', 0);
        $dealTitle = trim((string) $request->get('deal_title', ''));
        $orgName = trim((string) $request->get('organization', ''));
        $excludeId = $request->filled('exclude_quotation_id')
            ? (int) $request->get('exclude_quotation_id')
            : null;

        if ($dealId <= 0 && $dealTitle !== '') {
            $query = \App\Models\Deal::query()->where('title', $dealTitle);
            if ($orgName !== '') {
                $org = Organization::where('name', $orgName)->first();
                if ($org) {
                    $query->where('organization_id', $org->id);
                }
            }
            $dealId = (int) optional($query->first())->id;
        }

        if ($dealId <= 0) {
            return response()->json(['proposal_version' => 1, 'label' => 'v1']);
        }

        $version = $this->nextProposalVersionForDeal($dealId, $excludeId);

        return response()->json([
            'proposal_version' => $version,
            'label' => 'v' . $version,
        ]);
    }

    /**
     * Visible user IDs for the current role.
     * null = all users (super admin / admin).
     *
     * @return \Illuminate\Support\Collection<int, int>|null
     */
    protected function visibleTeamUserIds()
    {
        $user = auth()->user();
        $role = (int) ($user->crm_role_type ?? -1);

        // Super Admin (0) / Admin (1): all
        if ($role === 0 || $role === 1) {
            return null;
        }

        // Manager (2): self + assigned subordinates
        if ($role === 2) {
            return User::where('assign_manager', $user->id)
                ->pluck('id')
                ->push($user->id)
                ->unique()
                ->values();
        }

        // Employee / others: self only
        return collect([(int) $user->id]);
    }

    /**
     * Scope quotations: own (created/owned); manager team; admin all.
     */
    protected function applyQuotationVisibilityScope(Builder $query): Builder
    {
        $visibleIds = $this->visibleTeamUserIds();
        if ($visibleIds === null) {
            return $query;
        }

        return $query->where(function ($q) use ($visibleIds) {
            $q->whereIn('created_by', $visibleIds)
                ->orWhereIn('owner_id', $visibleIds);
        });
    }

    /**
     * Scope email/WhatsApp send logs by who sent them.
     */
    protected function applySendLogVisibilityScope(Builder $query): Builder
    {
        $visibleIds = $this->visibleTeamUserIds();
        if ($visibleIds === null) {
            return $query;
        }

        return $query->whereIn('sent_by', $visibleIds);
    }

    /**
     * Abort if the current user cannot access this quotation.
     */
    protected function authorizeQuotationAccess(Quotation $quotation): void
    {
        $visibleIds = $this->visibleTeamUserIds();
        if ($visibleIds === null) {
            return;
        }

        $createdBy = (int) ($quotation->created_by ?? 0);
        $ownerId = (int) ($quotation->owner_id ?? 0);

        if ($visibleIds->contains($createdBy) || $visibleIds->contains($ownerId)) {
            return;
        }

        abort(403, 'You do not have access to this quotation.');
    }

    protected function nextProposalVersionForDeal(int $dealId, ?int $excludeQuotationId = null): int
    {
        $query = Quotation::query()->where('deal_id', $dealId);
        if ($excludeQuotationId) {
            $query->where('id', '!=', $excludeQuotationId);
        }

        return max(1, (int) $query->max('proposal_version') + 1);
    }

    protected function sanitizeRichText(?string $html): ?string
    {
        $html = trim((string) ($html ?? ''));
        if ($html === '' || trim(strip_tags($html)) === '') {
            return null;
        }

        return app(QuotationTemplateService::class)->sanitizeRichEditorHtml($html);
    }
}
