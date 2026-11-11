<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SettingsController extends Controller
{
    /**
     * Settings hub page (opened from sidebar Settings menu).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $settings = collect($this->settingsCatalog())
            ->filter(function (array $item) use ($user) {
                if (!empty($item['admin_only']) && (int) ($user->crm_role_type ?? -1) !== 0) {
                    return false;
                }

                if (!empty($item['callable']) && is_callable($item['callable'])) {
                    return (bool) call_user_func($item['callable'], $user);
                }

                $permission = $item['permission'] ?? null;
                if (!$permission) {
                    return true;
                }

                if (is_array($permission)) {
                    foreach ($permission as $guard) {
                        if ($user->hasCrmPermission($guard)) {
                            return true;
                        }
                    }

                    return false;
                }

                return $user->hasCrmPermission($permission);
            })
            ->map(function (array $item) {
                $item['url'] = route($item['route']);
                unset($item['permission'], $item['route'], $item['admin_only'], $item['callable']);

                return $item;
            })
            ->values();

        $categories = $settings->pluck('category')->unique()->values();
        $search = trim((string) $request->get('q', ''));
        $category = $request->get('category', 'all');

        return view('settings.index', compact('settings', 'categories', 'search', 'category'));
    }

    /**
     * Built-in settings catalog for the Settings hub page.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function settingsCatalog(): array
    {
        return [
            [
                'id' => 'personal_settings',
                'name' => 'Personal Settings',
                'description' => 'Update your profile, contact details, and two-factor authentication.',
                'category' => 'Personal',
                'icon' => 'bi-person-circle',
                'icon_color' => '#2563eb',
                'route' => 'profile.edit',
            ],
            [
                'id' => 'change_password',
                'name' => 'Change Password',
                'description' => 'Update your login password and keep your account secure.',
                'category' => 'Personal',
                'icon' => 'bi-lock-fill',
                'icon_color' => '#7c3aed',
                'route' => 'password.edit',
            ],
            [
                'id' => 'company_settings',
                'name' => 'Company Settings',
                'description' => 'Manage company profile, logo, address, and bank details.',
                'category' => 'Organization',
                'icon' => 'bi-building',
                'icon_color' => '#0d9488',
                'route' => 'company.edit',
            ],
            [
                'id' => 'close_financial_year',
                'name' => 'Close Financial Year',
                'description' => 'Close the current financial year and prepare for the next period.',
                'category' => 'Organization',
                'icon' => 'bi-calendar-event',
                'icon_color' => '#ea580c',
                'route' => 'company.close_year.page',
                'permission' => 'crm_close_financial_year_guard',
            ],
            [
                'id' => 'user_settings',
                'name' => 'User Settings',
                'description' => 'Add, edit, and manage CRM users and their access.',
                'category' => 'Users & Permissions',
                'icon' => 'bi-people',
                'icon_color' => '#2563eb',
                'route' => 'users.index',
                'callable' => function ($user) {
                    return ($user->hasCrmPermission('create_crm_user_guard')
                        || $user->hasCrmPermission('edit_crm_user_guard')
                        || $user->hasCrmPermission('delete_crm_user_guard')
                        || $user->hasCrmPermission('manage_crm_user_guard'))
                        && $user->hasCrmPermission('manage_crm_user_guard');
                },
            ],
            [
                'id' => 'access_control',
                'name' => 'Access Control',
                'description' => 'Set user create limit and which modules Admins can assign to other users.',
                'category' => 'Users & Permissions',
                'icon' => 'bi-shield-lock',
                'icon_color' => '#b91c1c',
                'route' => 'access.settings.edit',
                'admin_only' => true,
            ],
            [
                'id' => 'weekly_target_alert',
                'name' => 'Weekly Target Alert Timings',
                'description' => 'Set morning, afternoon and evening times for weekly target reminder popups.',
                'category' => 'Users & Permissions',
                'icon' => 'bi-alarm',
                'icon_color' => '#ea580c',
                'route' => 'weekly_target_alert.settings.edit',
                'callable' => function ($user) {
                    return in_array((int) ($user->crm_role_type ?? -1), [0, 1], true);
                },
            ],
            [
                'id' => 'data_export',
                'name' => 'Export Data',
                'description' => 'Export CRM modules (Leads, Deals, Contacts, etc.) to Excel or CSV.',
                'category' => 'Data & Catalog',
                'icon' => 'bi-download',
                'icon_color' => '#4f46e5',
                'route' => 'data_export.index',
                'callable' => function ($user) {
                    return in_array((int) ($user->crm_role_type ?? -1), [0, 1], true);
                },
            ],
            [
                'id' => 'roles',
                'name' => 'Roles',
                'description' => 'Define user roles and control what each role can access.',
                'category' => 'Users & Permissions',
                'icon' => 'bi-person-badge',
                'icon_color' => '#7c3aed',
                'route' => 'roles.index',
                'permission' => 'manage_crm_role_guard',
            ],
            [
                'id' => 'permissions',
                'name' => 'Permissions',
                'description' => 'Configure granular permissions for CRM modules and actions.',
                'category' => 'Users & Permissions',
                'icon' => 'bi-shield-lock',
                'icon_color' => '#dc2626',
                'route' => 'permissions.index',
                'permission' => 'manage_crm_permission_guard',
            ],
            [
                'id' => 'whatsapp_settings',
                'name' => 'WhatsApp Settings',
                'description' => 'Connect WhatsApp and configure quotation message templates.',
                'category' => 'Channels & Integrations',
                'icon' => 'bi-whatsapp',
                'icon_color' => '#16a34a',
                'route' => 'whatsapp.settings.edit',
                'admin_only' => true,
            ],
            [
                'id' => 'email_settings',
                'name' => 'Email Settings',
                'description' => 'Configure SMTP or Sendmail for CRM notifications and system emails.',
                'category' => 'Channels & Integrations',
                'icon' => 'bi-envelope',
                'icon_color' => '#2563eb',
                'route' => 'email.settings.edit',
                'admin_only' => true,
            ],
            [
                'id' => 'email_compose_settings',
                'name' => 'Email Configuration',
                'description' => 'Compose font, size, and email signature applied to all outgoing emails.',
                'category' => 'Channels & Integrations',
                'icon' => 'bi-fonts',
                'icon_color' => '#7c3aed',
                'route' => 'email.compose.settings.edit',
            ],
            [
                'id' => 'web_forms',
                'name' => 'Web Forms',
                'description' => 'Build lead capture forms and embed them on your website or landing pages.',
                'category' => 'Channels & Integrations',
                'icon' => 'bi-ui-checks-grid',
                'icon_color' => '#5b3cc4',
                'route' => 'web_forms.index',
                'callable' => function ($user) {
                    return \App\Http\Controllers\WebFormController::userCanManage($user);
                },
            ],
            [
                'id' => 'social_ads',
                'name' => 'Social Ads',
                'description' => 'Import leads automatically from Facebook, Instagram, Google Ads and LinkedIn lead forms.',
                'category' => 'Channels & Integrations',
                'icon' => 'bi-megaphone',
                'icon_color' => '#1877f2',
                'route' => 'social_ads.index',
                'callable' => function ($user) {
                    return \App\Http\Controllers\SocialAdsController::userCanManage($user);
                },
            ],
            [
                'id' => 'gmail_settings',
                'name' => 'Gmail Account Setup',
                'description' => 'Connect your Gmail account to send quotations directly from CRM.',
                'category' => 'Channels & Integrations',
                'icon' => 'bi-google',
                'icon_color' => '#ea4335',
                'route' => 'gmail.settings.edit',
                'callable' => function ($user) {
                    return $user->canSendQuotationEmail();
                },
            ],
            [
                'id' => 'quotation_terms_settings',
                'name' => 'Quotation Terms & Conditions',
                'description' => 'Set default terms and conditions pre-filled on new quotations.',
                'category' => 'Sales & Documents',
                'icon' => 'bi-file-text',
                'icon_color' => '#0d9488',
                'route' => 'quotation.settings.edit',
                'callable' => function ($user) {
                    return (int) ($user->crm_role_type ?? -1) === 0
                        || $user->hasCrmPermission('manage_crm_quotation_terms_guard');
                },
            ],
            [
                'id' => 'quotation_email_templates',
                'name' => 'Quotation Email Templates',
                'description' => 'Design email subject and body templates for sending quotations.',
                'category' => 'Sales & Documents',
                'icon' => 'bi-envelope-paper',
                'icon_color' => '#2563eb',
                'route' => 'quotation_email_templates.index',
                'callable' => function ($user) {
                    return (int) ($user->crm_role_type ?? -1) === 0
                        || $user->hasCrmPermission('manage_crm_quotation_email_templates_guard');
                },
            ],
            [
                'id' => 'quotation_templates',
                'name' => 'Quotation Templates',
                'description' => 'Design and manage PDF quotation templates for your business.',
                'category' => 'Sales & Documents',
                'icon' => 'bi-file-earmark-richtext',
                'icon_color' => '#2563eb',
                'route' => 'quotation_templates.index',
                'callable' => function ($user) {
                    return \App\Http\Controllers\QuotationTemplateController::userCanAccessTemplates($user);
                },
            ],
            [
                'id' => 'tax_rates',
                'name' => 'Tax Rates',
                'description' => 'Manage tax rates applied to products and quotations.',
                'category' => 'Data & Catalog',
                'icon' => 'bi-percent',
                'icon_color' => '#ea580c',
                'route' => 'tax_rates.index',
                'permission' => 'manage_crm_tax_guard',
            ],
            [
                'id' => 'product_categories',
                'name' => 'Product Categories',
                'description' => 'Organize products into categories for deals and quotations.',
                'category' => 'Data & Catalog',
                'icon' => 'bi-tags',
                'icon_color' => '#0d9488',
                'route' => 'product_categories.index',
                'permission' => 'manage_crm_product_category_guard',
            ],
            [
                'id' => 'product_category_types',
                'name' => 'Product Category Types',
                'description' => 'Manage type options used on Product Categories (Products, Services, etc.).',
                'category' => 'Data & Catalog',
                'icon' => 'bi-list-ul',
                'icon_color' => '#0f766e',
                'route' => 'product_category_types.index',
                'permission' => 'manage_crm_product_category_guard',
            ],
            [
                'id' => 'main_categories',
                'name' => 'Main Categories',
                'description' => 'Configure main categories used for leads and deals.',
                'category' => 'Data & Catalog',
                'icon' => 'bi-diagram-3',
                'icon_color' => '#7c3aed',
                'route' => 'main_categories.index',
                'permission' => 'manage_crm_main_category_guard',
            ],
            [
                'id' => 'lead_sources',
                'name' => 'Lead Sources',
                'description' => 'Define lead sources for tracking and reporting.',
                'category' => 'Data & Catalog',
                'icon' => 'bi-signpost-split',
                'icon_color' => '#2563eb',
                'route' => 'lead_sources.index',
                'permission' => 'manage_crm_lead_source_guard',
            ],
            [
                'id' => 'outreach_types',
                'name' => 'Outreach Types',
                'description' => 'Manage outreach options used in Daily Report (Prospect, LinkedIn, Mail, etc.).',
                'category' => 'Data & Catalog',
                'icon' => 'bi-broadcast',
                'icon_color' => '#0891b2',
                'route' => 'outreach_types.index',
                'permission' => 'manage_crm_outreach_type_guard',
            ],
            [
                'id' => 'backup_data',
                'name' => 'Backup Data',
                'description' => 'Download a backup of your CRM database.',
                'category' => 'System',
                'icon' => 'bi-database',
                'icon_color' => '#475569',
                'route' => 'backup',
                'permission' => 'crm_backup_guard',
            ],
            [
                'id' => 'audit_logs',
                'name' => 'Audit Logs',
                'description' => 'Review user activity, changes, and system events across the CRM.',
                'category' => 'Logs',
                'icon' => 'bi-activity',
                'icon_color' => '#6366f1',
                'route' => 'activity-logs.index',
                'permission' => 'view_crm_activity_log_guard',
                'callable' => function ($user) {
                    $roleType = (int) ($user->crm_role_type ?? -1);

                    return $roleType === 0 || $roleType === 1;
                },
            ],
            [
                'id' => 'email_send_logs',
                'name' => 'Email Send Logs',
                'description' => 'View quotation email delivery history, status, and recipients.',
                'category' => 'Logs',
                'icon' => 'bi-envelope-check',
                'icon_color' => '#2563eb',
                'route' => 'quotations.email_send_logs',
                'permission' => 'manage_crm_quotations_guard',
            ],
            [
                'id' => 'whatsapp_send_logs',
                'name' => 'WhatsApp Send Logs',
                'description' => 'View quotation WhatsApp message delivery history and status.',
                'category' => 'Logs',
                'icon' => 'bi-whatsapp',
                'icon_color' => '#16a34a',
                'route' => 'quotations.whatsapp_send_logs',
                'permission' => 'manage_crm_quotations_guard',
            ],
        ];
    }

    /**
     * Route names that belong to the Settings area (for back navigation).
     *
     * @return array<string, string>
     */
    public static function pageTitles(): array
    {
        return [
            'profile.edit' => 'Personal Settings',
            'company.edit' => 'Company Settings',
            'company.close_year.page' => 'Close Financial Year',
            'whatsapp.settings.edit' => 'WhatsApp Settings',
            'email.settings.edit' => 'Email Settings',
            'gmail.settings.edit' => 'Gmail Account Setup',
            'email.compose.settings.edit' => 'Email Configuration',
            'quotation.settings.edit' => 'Quotation Terms & Conditions',
            'quotation_email_templates.index' => 'Quotation Email Templates',
            'quotation_email_templates.create' => 'Create Email Template',
            'quotation_email_templates.edit' => 'Edit Email Template',
            'quotation_templates.index' => 'Quotation Templates',
            'quotation_templates.create' => 'Create Quotation Template',
            'quotation_templates.edit' => 'Edit Quotation Template',
            'users.index' => 'User Settings',
            'users.create' => 'Add User',
            'users.edit' => 'Edit User',
            'access.settings.edit' => 'Access Control',
            'weekly_target_alert.settings.edit' => 'Weekly Target Alert Timings',
            'data_export.index' => 'Export Data',
            'web_forms.index' => 'Web Forms',
            'web_forms.create' => 'New Web Form',
            'web_forms.edit' => 'Edit Web Form',
            'web_forms.embed' => 'Web Form Embed Code',
            'web_forms.submissions' => 'Web Form Submissions',
            'social_ads.index' => 'Social Ads',
            'social_ads.leads' => 'Social Ads Leads Log',
            'social_ads.meta' => 'Facebook & Instagram Lead Ads',
            'social_ads.google' => 'Google Ads Lead Forms',
            'social_ads.linkedin' => 'LinkedIn Lead Gen Forms',
            'social_ads.generic' => 'Generic Lead Webhooks',
            'social_ads.forms.edit' => 'Ad Form Settings',
            'permissions.index' => 'Permissions',
            'roles.index' => 'Roles',
            'tax_rates.index' => 'Tax Rates',
            'product_categories.index' => 'Product Categories',
            'product_categories.create' => 'Add Product Category',
            'product_categories.edit' => 'Edit Product Category',
            'product_category_types.index' => 'Product Category Types',
            'product_category_types.create' => 'Add Product Category Type',
            'product_category_types.edit' => 'Edit Product Category Type',
            'main_categories.index' => 'Main Categories',
            'main_categories.create' => 'Add Main Category',
            'main_categories.edit' => 'Edit Main Category',
            'lead_sources.index' => 'Lead Sources',
            'outreach_types.index' => 'Outreach Types',
            'outreach_types.create' => 'Add Outreach Type',
            'outreach_types.edit' => 'Edit Outreach Type',
            'password.edit' => 'Change Password',
            'backup' => 'Backup Data',
            'activity-logs.index' => 'Audit Logs',
            'activity-logs.show' => 'Audit Log Details',
            'quotations.email_send_logs' => 'Email Send Logs',
            'quotations.whatsapp_send_logs' => 'WhatsApp Send Logs',
        ];
    }

    /**
     * Whether the current route is a settings sub-page (not the hub).
     */
    public static function isSettingsSubPage(?string $routeName = null): bool
    {
        $routeName = $routeName ?? Route::currentRouteName();
        if (!$routeName || $routeName === 'settings.index') {
            return false;
        }

        $prefixes = [
            'profile.',
            'company.edit',
            'company.close_year.',
            'whatsapp.settings.',
            'email.settings.',
            'gmail.settings.',
            'email.compose.settings.',
            'quotation.settings.',
            'quotation_email_templates.',
            'quotation_templates.',
            'users.',
            'access.settings.',
            'weekly_target_alert.settings.',
            'data_export.',
            'web_forms.',
            'social_ads.',
            'permissions.',
            'roles.',
            'tax_rates.',
            'product_categories.',
            'product_category_types.',
            'main_categories.',
            'lead_sources.',
            'outreach_types.',
            'password.',
            'backup',
            'activity-logs.',
            'quotations.email_send_logs',
            'quotations.whatsapp_send_logs',
        ];

        foreach ($prefixes as $prefix) {
            if (in_array($prefix, ['company.edit', 'backup', 'quotations.email_send_logs', 'quotations.whatsapp_send_logs'], true)) {
                if ($routeName === $prefix) {
                    return true;
                }
                continue;
            }

            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
