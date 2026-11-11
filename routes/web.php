<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\GoogleController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\TaskReportController;
use App\Http\Controllers\Reports\UserActivityReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\GoogleCalendarController;

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('/google/gmail/callback', [\App\Http\Controllers\GoogleGmailController::class, 'callback'])->name('google.gmail.callback');
Route::get('/validate-google-token', [GoogleController::class, 'validateGoogleToken'])->middleware('auth')->name('google.validateToken');
Route::get('auth/g', [GoogleController::class, 'redirectToG'])->middleware('auth')->name('google.redirectToG'); // Google Calendar consent (signed-in users only)


Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return view('auth.login');
});

// Temporary signed PDF URL for WhatsApp providers (no auth — Meta/WACRM must fetch the file)
Route::get('/whatsapp-quotation-pdf/{token}', [QuotationController::class, 'whatsappPdf'])
    ->middleware('signed')
    ->name('quotations.whatsapp.pdf');

// Public web form endpoints used by external websites / landing pages (no auth, CSRF exempt)
Route::get('/webforms/{token}', [\App\Http\Controllers\PublicWebFormController::class, 'show'])
    ->where('token', '[a-z0-9]{32}')->name('webforms.public.show');
Route::get('/webforms/{token}/embed.js', [\App\Http\Controllers\PublicWebFormController::class, 'script'])
    ->where('token', '[a-z0-9]{32}')->name('webforms.public.embed');
Route::post('/webforms/{token}/submit', [\App\Http\Controllers\PublicWebFormController::class, 'submit'])
    ->where('token', '[a-z0-9]{32}')->middleware('throttle:20,1')->name('webforms.public.submit');
Route::options('/webforms/{token}/submit', [\App\Http\Controllers\PublicWebFormController::class, 'preflight'])
    ->where('token', '[a-z0-9]{32}');

// Social Ads lead webhooks called by Meta, Google Ads and third-party tools (no auth, CSRF exempt)
Route::prefix('social-ads/webhook')->name('social_ads.webhook.')->middleware('throttle:300,1')->group(function () {
    Route::get('/meta', [\App\Http\Controllers\SocialAdsWebhookController::class, 'metaVerify'])->name('meta');
    Route::post('/meta', [\App\Http\Controllers\SocialAdsWebhookController::class, 'metaReceive']);
    Route::post('/google', [\App\Http\Controllers\SocialAdsWebhookController::class, 'google'])->name('google');
    Route::post('/generic/{token}', [\App\Http\Controllers\SocialAdsWebhookController::class, 'generic'])
        ->where('token', '[a-z0-9]{40}')->name('generic');
});


Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth',  'compliance.consent'])->name('dashboard');

Route::middleware(['auth', 'compliance.consent'])->group(function () {
    // AJAX autocomplete / autofill for organization and customer pickers
    Route::get('/organizations/autocomplete', [\App\Http\Controllers\OrganizationController::class, 'autocomplete'])->name('organizations.autocomplete');
    Route::get('/organizations/details', [\App\Http\Controllers\OrganizationController::class, 'details'])->name('organizations.details');
    Route::get('/customers/autocomplete', [\App\Http\Controllers\CustomerController::class, 'autocomplete'])->name('customers.autocomplete');
    Route::get('/customers/details', [\App\Http\Controllers\CustomerController::class, 'details'])->name('customers.details');

    // Interactive Analytics Dashboard
    Route::get('/analytics-dashboard', [\App\Http\Controllers\AnalyticsDashboardController::class, 'index'])->name('analytics.dashboard');
    Route::get('/analytics-dashboard/data', [\App\Http\Controllers\AnalyticsDashboardController::class, 'getWidgetData'])->name('analytics.widget_data');
    Route::post('/analytics-dashboard/save', [\App\Http\Controllers\AnalyticsDashboardController::class, 'saveDashboards'])->name('analytics.save_dashboards');
    Route::post('/analytics-dashboard/clone', [\App\Http\Controllers\AnalyticsDashboardController::class, 'cloneDashboard'])->name('analytics.clone_dashboard');

    // Call Logs
    Route::resource('calllogs', App\Http\Controllers\CallLogController::class)->except(['show']);
    // Show single call log (constrain to numeric id so static paths like /calllogs/import are not captured)
    Route::get('/calllogs/{calllog}', [App\Http\Controllers\CallLogController::class, 'show'])->whereNumber('calllog')->name('calllogs.show');
    Route::get('/calllogs/import', function () {
        return view('calls.import'); })->name('calllogs.import.page');
    Route::post('/calllogs/import', [\App\Http\Controllers\CallLogController::class, 'import'])->name('calllogs.import');
    // Export call logs to Excel (current filters applied)
    Route::get('/calllogs/excel', [\App\Http\Controllers\CallLogController::class, 'exportExcel'])->name('calllogs.excel');
    Route::get('calllogs/report', [\App\Http\Controllers\CallLogController::class, 'report'])->name('calllogs.report');
    Route::get('calllogs/report/excel', [\App\Http\Controllers\CallLogController::class, 'exportCalls'])->name('calllogs.report.excel');

    // Compliance Consent Form routes
    Route::get('/compliance-consent', [\App\Http\Controllers\ComplianceConsentController::class, 'showForm'])->name('compliance.consent.form');
    Route::post('/compliance-consent', [\App\Http\Controllers\ComplianceConsentController::class, 'submitForm'])->name('compliance.consent.submit');

    // Lead Files
    Route::post('/leads/files', [\App\Http\Controllers\LeadFileController::class, 'store'])->name('leads.files.store');
    Route::delete('/leads/files/{file}', [\App\Http\Controllers\LeadFileController::class, 'destroy'])->name('leads.files.destroy');
    Route::get('lead-files/{file}/download', [App\Http\Controllers\LeadFileController::class, 'download'])->name('lead-files.download');

    // Deal Files
    Route::post('/deals/files', [\App\Http\Controllers\DealFileController::class, 'store'])->name('deals.files.store');
    Route::delete('/deals/files/{file}', [\App\Http\Controllers\DealFileController::class, 'destroy'])->name('deals.files.destroy');
    Route::get('deal-files/{file}/download', [App\Http\Controllers\DealFileController::class, 'download'])->name('deal-files.download');
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 2FA enable/disable
    Route::post('/profile/2fa', [ProfileController::class, 'update2FA'])->name('profile.2fa.update');


    // Export organizations (respect current filters)
    Route::get('/organizations/excel', [\App\Http\Controllers\OrganizationController::class, 'exportExcel'])->name('organizations.excel');
    Route::resource('organizations', App\Http\Controllers\OrganizationController::class);
    // User CRUD routes
    Route::resource('users', App\Http\Controllers\UserController::class);
    // Admin: Update 2FA status for user
    Route::post('/users/{user}/2fa', [App\Http\Controllers\UserController::class, 'update2FA'])->name('users.2fa.update');

    Route::get('/organizations/{id}/people', [\App\Http\Controllers\PersonController::class, 'listForOrganization'])->name('organizations.people');
    // AJAX route to add organization to person (modal)

    Route::post('/organizations/ajax-create', [App\Http\Controllers\OrganizationController::class, 'ajaxCreate']);
    Route::post('/industries/ajax-create', [App\Http\Controllers\IndustryController::class, 'ajaxCreate'])->name('industries.ajaxCreate');

    // Export customers to Excel (respect current filters)
    Route::get('/customers/excel', [\App\Http\Controllers\CustomerController::class, 'exportExcel'])->name('customers.excel');
    Route::resource('customers', App\Http\Controllers\CustomerController::class);
    Route::get('/customers/{id}/people', [\App\Http\Controllers\PersonController::class, 'listForCustomer'])->name('customers.people');
    Route::post('/customers/ajax-create', [App\Http\Controllers\CustomerController::class, 'ajaxCreate']);

    // AJAX route for people autocomplete (filtered by organization)
    Route::get('/people/autocomplete', [\App\Http\Controllers\PersonController::class, 'autocomplete'])->name('people.autocomplete');
    // AJAX route for people details (autofill)
    Route::get('/people/details', [App\Http\Controllers\PersonController::class, 'details']);
    // Export people to Excel (respect current filters)
    Route::get('/people/excel', [\App\Http\Controllers\PersonController::class, 'exportExcel'])->name('people.excel');
    Route::get('/people/email-recipients', [\App\Http\Controllers\CrmEntityGmailController::class, 'recipientSearch'])->name('people.email_recipients');
    Route::get('/gmail/email-recipients', [\App\Http\Controllers\CrmEntityGmailController::class, 'recipientSearch'])->name('gmail.email_recipients');
    Route::post('/people/savePeople', [\App\Http\Controllers\PersonController::class, 'savePeople'])->name('people.save');
    Route::delete('/people/deleteContact/{id}', [\App\Http\Controllers\PersonController::class, 'deleteContact'])->name('people.delete');
    Route::post('/people/ajax-create', [App\Http\Controllers\PersonController::class, 'ajaxCreate']);
    Route::post('/people/{person}/add-organization', [\App\Http\Controllers\PersonController::class, 'addOrganization'])->name('people.addOrganization');
    Route::delete('/people/{person}/organizations/{organization}', [\App\Http\Controllers\PersonController::class, 'removeOrganization'])->name('people.removeOrganization');
    // Resource routes last so specific paths above match first
    Route::resource('people', App\Http\Controllers\PersonController::class)->whereNumber('person');
    //Leads Related Routes
    Route::get('/leads/{id}/gmail-compose', [\App\Http\Controllers\CrmEntityGmailController::class, 'leadCompose'])->name('leads.gmail_compose');
    Route::post('/leads/{id}/send-gmail', [\App\Http\Controllers\CrmEntityGmailController::class, 'leadSend'])->name('leads.send_gmail');
    Route::get('/deals/{id}/gmail-compose', [\App\Http\Controllers\CrmEntityGmailController::class, 'dealCompose'])->name('deals.gmail_compose');
    Route::post('/deals/{id}/send-gmail', [\App\Http\Controllers\CrmEntityGmailController::class, 'dealSend'])->name('deals.send_gmail');

    Route::resource('leads', App\Http\Controllers\LeadController::class);

    // Add route for converting lead to deal
    Route::get('/leads/{lead}/convert', [App\Http\Controllers\DealController::class, 'createFromLead'])->name('leads.convert');
    Route::post('/deals/from-lead/{lead}', [App\Http\Controllers\DealController::class, 'storeFromLead'])->name('deals.storeFromLead');
    Route::post('/deals/{deal}/move-to-stage', [App\Http\Controllers\DealController::class, 'updateStage'])->name('deals.moveToStage');
    // Tax Rates Resource Routes
    Route::resource('tax_rates', App\Http\Controllers\TaxRateController::class);

    // Product Categories Resource Routes
    Route::resource('product_categories', App\Http\Controllers\ProductCategoryController::class);
    Route::resource('product_category_types', App\Http\Controllers\ProductCategoryTypeController::class);

    // Main Categories Resource Routes
    Route::resource('main_categories', App\Http\Controllers\MainCategoryController::class);

    // Lead Sources Resource Routes
    Route::resource('lead_sources', App\Http\Controllers\LeadSourceController::class);

    // Outreach Types (admin master for Daily Report)
    Route::resource('outreach_types', App\Http\Controllers\OutreachTypeController::class);

    // Daily Report entry module (export routes must come before the resource's {daily_report} routes)
    Route::get('daily_reports/export/excel', [App\Http\Controllers\DailyReportController::class, 'exportExcel'])->name('daily_reports.export_excel');
    Route::get('daily_reports/export/pdf', [App\Http\Controllers\DailyReportController::class, 'exportPdf'])->name('daily_reports.export_pdf');
    Route::resource('daily_reports', App\Http\Controllers\DailyReportController::class);

    // Product Resource Routes
    Route::post('/products/editor/upload-image', [App\Http\Controllers\ProductController::class, 'uploadEditorImage'])->name('products.editor.upload_image');
    Route::resource('products', App\Http\Controllers\ProductController::class);

    // Deal CRUD routes
    // AJAX: check duplicate deal by title + organization + person
    Route::get('/deals/check-duplicate', [App\Http\Controllers\DealController::class, 'checkDuplicate']);
    Route::resource('deals', App\Http\Controllers\DealController::class);
    Route::get('/deals/{id}/convertshow', [App\Http\Controllers\DealController::class, 'showConvertDeals'])->name('deals.convertshow');
    Route::post('/deals/{deal}/won', [App\Http\Controllers\DealController::class, 'markWon'])->name('deals.won');
    Route::post('/deals/{deal}/lost', [App\Http\Controllers\DealController::class, 'markLost'])->name('deals.lost');
    Route::post('/deals/{deal}/reopen', [App\Http\Controllers\DealController::class, 'reopen'])->name('deals.reopen');

    //Deals Pipeline View and Stage Update
    Route::get('/deals/pipeline', [App\Http\Controllers\DealController::class, 'pipeline'])->name('deals.pipeline');
    Route::post('/deals/update-stage', [App\Http\Controllers\DealController::class, 'updateStage'])->name('deals.updateStage');

    Route::get('/deals/get-deal-details', [App\Http\Controllers\DealController::class, 'getDealDetails'])->name('deals.get_details');

    // Deal stage history
    Route::get('/deals/{deal}/stage-history', [App\Http\Controllers\DealController::class, 'stageHistory'])->name('deals.stageHistory');
    // AJAX autocomplete for parent permissions
    Route::get('/deals/search/autocomplete', [App\Http\Controllers\DealController::class, 'autoComplete'])->name('deals.autocomplete');

    Route::get('/contact/autocomplete', [App\Http\Controllers\PersonController::class, 'autoCompletePerson'])->name('contact.autocomplete');

    // Notes
    Route::post('/notes', [App\Http\Controllers\NoteController::class, 'store'])->name('notes.store');
    Route::put('/notes/{note}', [App\Http\Controllers\NoteActionsController::class, 'update'])->name('notes.update');
    Route::post('/notes/{note}/pin', [App\Http\Controllers\NoteActionsController::class, 'pin'])->name('notes.pin');
    Route::post('/notes/{note}/unpin', [App\Http\Controllers\NoteActionsController::class, 'unpin'])->name('notes.unpin');
    Route::delete('/notes/{note}', [App\Http\Controllers\NoteActionsController::class, 'destroy'])->name('notes.destroy');


    // Tasks
    Route::post('/tasks', [App\Http\Controllers\TaskController::class, 'store'])->name('tasks.store');
    // Task actions
    Route::put('/tasks/{task}', [App\Http\Controllers\TaskActionsController::class, 'update'])->name('tasks.update');
    Route::post('/tasks/{task}/complete', [App\Http\Controllers\TaskActionsController::class, 'complete'])->name('tasks.complete');
    Route::post('/tasks/{id}/incomplete', [TaskController::class, 'incomplete'])->name('tasks.incomplete');
    Route::delete('/tasks/{task}', [App\Http\Controllers\TaskActionsController::class, 'destroy'])->name('tasks.destroy');
    // Route to show a task and redirect to its related lead or deal
    Route::get('/tasks/{task}/show', [\App\Http\Controllers\TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{id}/edit', [TaskController::class, 'edit'])->name('tasks.edit');

    // AJAX route for task name autocomplete
    Route::get('/tasks/autocomplete', [\App\Http\Controllers\TaskController::class, 'autocomplete'])->name('tasks.autocomplete');

    // Calls
    Route::post('/calls', [App\Http\Controllers\CallController::class, 'store'])->name('calls.store');
    // Call actions
    Route::put('/calls/{call}', [App\Http\Controllers\CallActionsController::class, 'update'])->name('calls.update');
    Route::delete('/calls/{call}', [App\Http\Controllers\CallActionsController::class, 'destroy'])->name('calls.destroy');


    // Meetings
    Route::post('/meetings', [App\Http\Controllers\MeetingController::class, 'store'])->name('meetings.store');
    Route::get('/meetings/{meeting}', [App\Http\Controllers\MeetingController::class, 'show'])->name('meetings.show');
    Route::put('/meetings/{meeting}', [App\Http\Controllers\MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('/meetings/{meeting}', [App\Http\Controllers\MeetingController::class, 'destroy'])->name('meetings.destroy');
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');


    Route::post('/meetings/save', [App\Http\Controllers\MeetingController::class, 'meetingStore'])->name('meetings.meetingStore');
    Route::post('/meetings/update', [App\Http\Controllers\MeetingController::class, 'meetingUpdate'])->name('meetings.meetingUpdate');
    Route::post('/meetings/add-participants', [App\Http\Controllers\MeetingController::class, 'addParticipants'])->name('meetings.addParticipants');
    // Route to delete a participant from a meeting
    Route::delete('/meetings/{meeting}/participants/{participant}', [MeetingController::class, 'removeParticipant'])->name('meetings.participants.destroy');
    // Permission CRUD routes
    Route::resource('permissions', App\Http\Controllers\PermissionController::class);
    // Role CRUD routes
    Route::resource('roles', App\Http\Controllers\RoleController::class);
    // AJAX route for parent permission creation
    Route::post('/parent-permissions', [App\Http\Controllers\ParentPermissionController::class, 'store'])->name('parent-permissions.store');
    // AJAX autocomplete for parent permissions
    Route::get('/parent-permissions/autocomplete', [App\Http\Controllers\ParentPermissionController::class, 'autocomplete'])->name('parent-permissions.autocomplete');

    // Reports
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    // Export today's leads report to Excel
    Route::get('/reports/today-leads/excel', [\App\Http\Controllers\ReportController::class, 'exportTodayLeads'])->name('reports.today_leads_excel');
    Route::get('/reports/today-leads', [\App\Http\Controllers\ReportController::class, 'todayLeads'])->name('reports.today_leads');
    // Today's Closed Won Deals export (must be before the page route)
    Route::get('/reports/today-closed-won-deals/excel', [\App\Http\Controllers\ReportController::class, 'exportTodayClosedWonDeals'])->name('reports.today_closed_won_deals_excel');
    // Today's Closed Won Deals
    Route::get('/reports/today-closed-won-deals', [\App\Http\Controllers\ReportController::class, 'todayClosedWonDeals'])->name('reports.today_closed_won_deals');
    // Export converted leads report
    Route::get('/reports/converted-leads/excel', [\App\Http\Controllers\ReportController::class, 'exportConvertedLeads'])->name('reports.converted_leads_excel');
    Route::get('/reports/converted-leads', [\App\Http\Controllers\ReportController::class, 'convertedLeads'])->name('reports.converted_leads');
    // Export Leads by Status (respect selected status and FY)
    Route::get('/reports/leads-by-status/excel', [\App\Http\Controllers\ReportController::class, 'exportLeadsByStatus'])->name('reports.leads_by_status_excel');
    Route::get('/reports/leads-by-status', [\App\Http\Controllers\ReportController::class, 'leadsByStatus'])->name('reports.leads_by_status');
    // Leads by Source (custom report)
    // Export leads by source (respect start_date/end_date and FY)
    Route::get('/reports/leads-by-source/excel', [\App\Http\Controllers\ReportController::class, 'exportLeadsBySource'])->name('reports.leads_by_source_excel');
    Route::get('/reports/leads-by-source', [\App\Http\Controllers\ReportController::class, 'leadsBySource'])->name('reports.leads_by_source_custom');
    // Deals by Source (custom report)
    Route::get('/reports/deals-by-source', [\App\Http\Controllers\ReportController::class, 'dealsBySource'])->name('reports.deals_by_source_custom');
    // Open Deals Report
    Route::get('/reports/open-deals', [\App\Http\Controllers\ReportController::class, 'openDeals'])->name('reports.open_deals');
    // Lost Deals Report
    Route::get('/reports/lost-deals', [\App\Http\Controllers\ReportController::class, 'lostDeals'])->name('reports.lost_deals');
    // Deals Closing This Month Report
    Route::get('/reports/deals-this-month', [\App\Http\Controllers\ReportController::class, 'dealsThisMonth'])->name('reports.deals_this_month');

    Route::get('/reports/leads', [\App\Http\Controllers\ReportController::class, 'leads'])->name('reports.leads');
    Route::get('/reports/deals', [\App\Http\Controllers\ReportController::class, 'deals'])->name('reports.deals');
    Route::get('/reports/revenue-by-month', [\App\Http\Controllers\ReportController::class, 'revenueByMonth'])->name('reports.revenue_by_month');

    // User-wise leads & deals report
    Route::get('/reports/user-wise', [\App\Http\Controllers\UserReportController::class, 'index'])->name('reports.user_wise');
    // AJAX: Get leads list for user
    Route::get('/reports/user-leads/{user}', [\App\Http\Controllers\UserReportController::class, 'userLeads']);
    // AJAX: Get lead details
    Route::get('/reports/lead-details/{lead}', [\App\Http\Controllers\UserReportController::class, 'leadDetails']);

    // AJAX: Get converted leads list for user
    Route::get('/reports/converted-leads/{user}', [\App\Http\Controllers\UserReportController::class, 'convertedLeads']);
    // AJAX: Get deals list for user
    Route::get('/reports/user-deals/{user}', [\App\Http\Controllers\UserReportController::class, 'userDeals']);
    Route::get('/reports/user-reports', [App\Http\Controllers\UserReportController::class, 'userReports'])->name('reports.user_reports');
    Route::get('/reports/user-reports/pdf', [App\Http\Controllers\UserReportController::class, 'userReportsPdf'])->name('reports.user_reports_pdf');

    // Monthly User Report
    Route::get('/reports/monthly-user-report', [App\Http\Controllers\UserReportController::class, 'monthlyUserReport'])->name('reports.monthly_user_report');

    Route::get('/company', [\App\Http\Controllers\CompanyController::class, 'edit'])->name('company.edit');
    Route::post('/company', [\App\Http\Controllers\CompanyController::class, 'update'])->name('company.update');
    
    // WhatsApp Integration Settings
    Route::get('/access-settings', [\App\Http\Controllers\AccessSettingController::class, 'edit'])->name('access.settings.edit');
    Route::post('/access-settings', [\App\Http\Controllers\AccessSettingController::class, 'update'])->name('access.settings.update');

    Route::get('/weekly-target-alert-settings', [\App\Http\Controllers\WeeklyTargetAlertSettingController::class, 'edit'])->name('weekly_target_alert.settings.edit');
    Route::post('/weekly-target-alert-settings', [\App\Http\Controllers\WeeklyTargetAlertSettingController::class, 'update'])->name('weekly_target_alert.settings.update');

    Route::get('/data-export', [\App\Http\Controllers\DataExportController::class, 'index'])->name('data_export.index');
    Route::post('/data-export', [\App\Http\Controllers\DataExportController::class, 'export'])->name('data_export.download');

    // Web Forms (website / landing page lead capture)
    Route::get('/web-forms/{webForm}/embed', [\App\Http\Controllers\WebFormController::class, 'embed'])->name('web_forms.embed');
    Route::get('/web-forms/{webForm}/submissions', [\App\Http\Controllers\WebFormController::class, 'submissions'])->name('web_forms.submissions');
    Route::post('/web-forms/{webForm}/toggle', [\App\Http\Controllers\WebFormController::class, 'toggle'])->name('web_forms.toggle');
    Route::post('/web-forms/{webForm}/regenerate', [\App\Http\Controllers\WebFormController::class, 'regenerateToken'])->name('web_forms.regenerate');
    Route::resource('web-forms', \App\Http\Controllers\WebFormController::class)
        ->except(['show'])
        ->parameters(['web-forms' => 'webForm'])
        ->names('web_forms');

    // Social Ads (Meta / Google Ads / LinkedIn / generic webhook lead capture)
    Route::prefix('social-ads')->name('social_ads.')->group(function () {
        $sa = \App\Http\Controllers\SocialAdsController::class;
        $conn = \App\Http\Controllers\SocialAdsConnectController::class;

        Route::get('/', [$sa, 'index'])->name('index');
        Route::get('/leads', [$sa, 'leads'])->name('leads');
        Route::post('/leads/retry-failed', [$sa, 'retryFailed'])->name('leads.retry_failed');
        Route::post('/leads/{socialAdLead}/retry', [$sa, 'retry'])->name('leads.retry');

        Route::get('/meta', [$sa, 'meta'])->name('meta');
        Route::post('/meta/credentials', [$conn, 'metaSaveCredentials'])->name('meta.credentials');
        Route::get('/meta/connect', [$conn, 'metaConnect'])->name('meta.connect');
        Route::get('/meta/callback', [$conn, 'metaCallback'])->name('meta.callback');
        Route::post('/meta/refresh-pages', [$conn, 'metaRefreshPages'])->name('meta.refresh_pages');
        Route::post('/meta/register-webhook', [$conn, 'metaRegisterWebhook'])->name('meta.register_webhook');
        Route::post('/meta/disconnect', [$conn, 'metaDisconnect'])->name('meta.disconnect');

        Route::get('/google', [$sa, 'google'])->name('google');
        Route::post('/google/regenerate-key', [$sa, 'googleRegenerateKey'])->name('google.regenerate_key');

        Route::get('/linkedin', [$sa, 'linkedin'])->name('linkedin');
        Route::post('/linkedin/credentials', [$conn, 'linkedinSaveCredentials'])->name('linkedin.credentials');
        Route::get('/linkedin/connect', [$conn, 'linkedinConnect'])->name('linkedin.connect');
        Route::get('/linkedin/callback', [$conn, 'linkedinCallback'])->name('linkedin.callback');
        Route::post('/linkedin/refresh-accounts', [$conn, 'linkedinRefreshAccounts'])->name('linkedin.refresh_accounts');
        Route::post('/linkedin/disconnect', [$conn, 'linkedinDisconnect'])->name('linkedin.disconnect');

        Route::get('/generic', [$sa, 'generic'])->name('generic');
        Route::post('/generic', [$sa, 'genericStore'])->name('generic.store');

        Route::post('/defaults/{platform}', [$sa, 'saveDefaults'])->whereIn('platform', ['meta', 'google', 'linkedin', 'generic'])->name('defaults');

        Route::post('/accounts/{socialAdAccount}/subscribe', [$conn, 'accountSubscribe'])->name('accounts.subscribe');
        Route::post('/accounts/{socialAdAccount}/unsubscribe', [$conn, 'accountUnsubscribe'])->name('accounts.unsubscribe');
        Route::post('/accounts/{socialAdAccount}/sync-forms', [$conn, 'accountSyncForms'])->name('accounts.sync_forms');
        Route::post('/accounts/{socialAdAccount}/sync-leads', [$conn, 'accountSyncLeads'])->name('accounts.sync_leads');

        Route::get('/forms/{socialAdForm}/edit', [$sa, 'formEdit'])->name('forms.edit');
        Route::put('/forms/{socialAdForm}', [$sa, 'formUpdate'])->name('forms.update');
        Route::delete('/forms/{socialAdForm}', [$sa, 'formDestroy'])->name('forms.destroy');
        Route::post('/forms/{socialAdForm}/toggle', [$sa, 'formToggle'])->name('forms.toggle');
        Route::post('/forms/{socialAdForm}/sync', [$sa, 'formSync'])->name('forms.sync');
        Route::post('/forms/{socialAdForm}/regenerate-token', [$sa, 'formRegenerateToken'])->name('forms.regenerate_token');
    });

    Route::get('/whatsapp-settings', [\App\Http\Controllers\WhatsappSettingController::class, 'edit'])->name('whatsapp.settings.edit');
    Route::post('/whatsapp-settings', [\App\Http\Controllers\WhatsappSettingController::class, 'update'])->name('whatsapp.settings.update');
    Route::post('/whatsapp-settings/test', [\App\Http\Controllers\WhatsappSettingController::class, 'sendTest'])->name('whatsapp.settings.test');
    Route::post('/whatsapp-settings/verify-password', [\App\Http\Controllers\WhatsappSettingController::class, 'verifyPassword'])->name('whatsapp.settings.verify_password');

    // Email Integration Settings
    Route::get('/email-settings', [\App\Http\Controllers\EmailSettingController::class, 'edit'])->name('email.settings.edit');
    Route::post('/email-settings', [\App\Http\Controllers\EmailSettingController::class, 'update'])->name('email.settings.update');
    Route::post('/email-settings/test', [\App\Http\Controllers\EmailSettingController::class, 'sendTest'])->name('email.settings.test');
    Route::post('/email-settings/verify-password', [\App\Http\Controllers\EmailSettingController::class, 'verifyPassword'])->name('email.settings.verify_password');

    Route::get('/gmail-settings', [\App\Http\Controllers\GmailSettingController::class, 'edit'])->name('gmail.settings.edit');
    Route::post('/gmail-settings/disconnect', [\App\Http\Controllers\GmailSettingController::class, 'disconnect'])->name('gmail.settings.disconnect');
    Route::post('/gmail-settings/test', [\App\Http\Controllers\GmailSettingController::class, 'sendTest'])->name('gmail.settings.test');
    Route::get('/email-configuration', [\App\Http\Controllers\EmailComposeSettingController::class, 'edit'])->name('email.compose.settings.edit');
    Route::post('/email-configuration', [\App\Http\Controllers\EmailComposeSettingController::class, 'update'])->name('email.compose.settings.update');

    // Quotation default terms (Super Admin)
    Route::get('/quotation-settings/terms', [\App\Http\Controllers\QuotationSettingController::class, 'edit'])->name('quotation.settings.edit');
    Route::post('/quotation-settings/terms', [\App\Http\Controllers\QuotationSettingController::class, 'update'])->name('quotation.settings.update');

    // Quotation Templates (Super Admin)
    Route::get('/quotation-templates', [\App\Http\Controllers\QuotationTemplateController::class, 'index'])->name('quotation_templates.index');
    Route::get('/quotation-templates/create', [\App\Http\Controllers\QuotationTemplateController::class, 'create'])->name('quotation_templates.create');
    Route::post('/quotation-templates', [\App\Http\Controllers\QuotationTemplateController::class, 'store'])->name('quotation_templates.store');
    Route::post('/quotation-templates/preview', [\App\Http\Controllers\QuotationTemplateController::class, 'previewPost'])->name('quotation_templates.preview_post');
    Route::post('/quotation-templates/wizard-draft-preview', [\App\Http\Controllers\QuotationTemplateController::class, 'wizardDraftPreview'])->name('quotation_templates.wizard_draft_preview');
    Route::get('/quotation-templates/{quotation_template}/edit', [\App\Http\Controllers\QuotationTemplateController::class, 'edit'])->name('quotation_templates.edit');
    Route::put('/quotation-templates/{quotation_template}', [\App\Http\Controllers\QuotationTemplateController::class, 'update'])->name('quotation_templates.update');
    Route::delete('/quotation-templates/{quotation_template}', [\App\Http\Controllers\QuotationTemplateController::class, 'destroy'])->name('quotation_templates.destroy');
    Route::get('/quotation-templates/{quotation_template}/preview', [\App\Http\Controllers\QuotationTemplateController::class, 'preview'])->name('quotation_templates.preview');
    Route::get('/quotation-templates/{quotation_template}/picker-preview', [\App\Http\Controllers\QuotationTemplateController::class, 'pickerPreview'])->name('quotation_templates.picker_preview');
    Route::post('/quotation-templates/{quotation_template}/duplicate', [\App\Http\Controllers\QuotationTemplateController::class, 'duplicate'])->name('quotation_templates.duplicate');
    Route::post('/quotation-templates/{quotation_template}/toggle-status', [\App\Http\Controllers\QuotationTemplateController::class, 'toggleStatus'])->name('quotation_templates.toggle_status');
    Route::post('/quotation-templates/{quotation_template}/set-default', [\App\Http\Controllers\QuotationTemplateController::class, 'setDefault'])->name('quotation_templates.set_default');

    Route::get('/quotation-email-templates', [\App\Http\Controllers\QuotationEmailTemplateController::class, 'index'])->name('quotation_email_templates.index');
    Route::get('/quotation-email-templates/create', [\App\Http\Controllers\QuotationEmailTemplateController::class, 'create'])->name('quotation_email_templates.create');
    Route::post('/quotation-email-templates', [\App\Http\Controllers\QuotationEmailTemplateController::class, 'store'])->name('quotation_email_templates.store');
    Route::get('/quotation-email-templates/{quotation_email_template}/edit', [\App\Http\Controllers\QuotationEmailTemplateController::class, 'edit'])->name('quotation_email_templates.edit');
    Route::put('/quotation-email-templates/{quotation_email_template}', [\App\Http\Controllers\QuotationEmailTemplateController::class, 'update'])->name('quotation_email_templates.update');
    Route::delete('/quotation-email-templates/{quotation_email_template}', [\App\Http\Controllers\QuotationEmailTemplateController::class, 'destroy'])->name('quotation_email_templates.destroy');
    Route::post('/quotation-email-templates/{quotation_email_template}/set-default', [\App\Http\Controllers\QuotationEmailTemplateController::class, 'setDefault'])->name('quotation_email_templates.set_default');
    Route::post('/quotation-email-templates/{quotation_email_template}/toggle-status', [\App\Http\Controllers\QuotationEmailTemplateController::class, 'toggleStatus'])->name('quotation_email_templates.toggle_status');

    Route::post('/company/close-financial-year', [\App\Http\Controllers\CompanyController::class, 'closeFinancialYear'])->name('company.close_financial_year');
    // Close financial year UI + AJAX
    Route::get('/company/close-year', [\App\Http\Controllers\CompanyController::class, 'showCloseYearPage'])->name('company.close_year.page');
    Route::post('/company/close-year/backup', [\App\Http\Controllers\CompanyController::class, 'ajaxBackup'])->name('company.close_year.backup');
    Route::post('/company/close-year/create', [\App\Http\Controllers\CompanyController::class, 'ajaxCreateNextYear'])->name('company.close_year.create');
    // Select financial year (store in session)
    Route::post('/financial-year/select', [\App\Http\Controllers\CompanyController::class, 'selectFinancialYear'])->name('financial_year.select');
    // Database backup (admins only)
    Route::get('/backup', [\App\Http\Controllers\BackupController::class, 'backup'])->name('backup');

    // Export Monthly User Report to Excel
    Route::get('/reports/monthly-user-report/excel', [App\Http\Controllers\UserReportController::class, 'exportMonthlyUserReportExcel'])->name('reports.monthly_user_report_excel');

    // Monthly User Calls Modal (AJAX)
    Route::get('/reports/user-calls/{user}', [App\Http\Controllers\UserReportController::class, 'userCallsModal']);

    // User Daily Report
    Route::get('/reports/user-daily-report', [App\Http\Controllers\UserReportController::class, 'userDailyReport'])->name('reports.user_daily_report');

    // Daily Sales & Marketing Report (all users activity matrix)
    Route::get('/reports/sales-marketing-daily', [App\Http\Controllers\SalesMarketingDailyReportController::class, 'index'])->name('reports.sales_marketing_daily');
    Route::get('/reports/sales-marketing-daily/excel', [App\Http\Controllers\SalesMarketingDailyReportController::class, 'excel'])->name('reports.sales_marketing_daily_excel');

    // Consolidated Sales Report
    Route::get('/reports/consolidated-sales', [\App\Http\Controllers\ConsolidatedSalesReportController::class, 'index'])->name('reports.consolidated_sales');
    Route::get('/reports/consolidated-sales/excel', [\App\Http\Controllers\ConsolidatedSalesReportController::class, 'excel'])->name('reports.consolidated_sales_excel');

    // Export Daily User Report to Excel
    Route::get('/reports/user-daily-report/excel', [\App\Http\Controllers\UserReportController::class, 'exportDailyUserReportExcel'])->name('reports.user_daily_report_excel');

    // Monthly User Performance Report
    Route::get('/reports/monthly-user-performance', [\App\Http\Controllers\UserReportController::class, 'monthlyUserPerformanceReport'])->name('reports.monthly_user_performance');

     // Monthly Target Report
    Route::get('/reports/monthly-target-report', [\App\Http\Controllers\UserReportController::class, 'monthlyTargetReport'])->name('reports.monthly_target_report');
    
    // Route for displaying analytics reports
    Route::get('/reports/analytics-reports', [\App\Http\Controllers\UserReportController::class, 'displayAnalyticsReports'])->name('analytics.reports');

    Route::get('/reports/product-category-user-report', [\App\Http\Controllers\UserReportController::class, 'productCategoryUserReport'])->name('reports.product_category_user_report');
    
    Route::get('/reports/month-lead-comparison', [\App\Http\Controllers\ReportController::class, 'monthLeadComparisonReport'])->name('reports.month_lead_comparison');
    
    
    // Dashboard user sales widget and history
    Route::get('/dashboard/user-sales-widget', [\App\Http\Controllers\DashboardController::class, 'userSalesWidget'])->name('dashboard.user_sales_widget');
    Route::get('/dashboard/user-sales-history', [\App\Http\Controllers\DashboardController::class, 'userSalesHistory'])->name('dashboard.user_sales_history');

    // Employee targets dashboard (admin only)
    Route::get('/dashboard/employee-targets', [\App\Http\Controllers\DashboardController::class, 'employeeTargetsDashboard'])->name('dashboard.employee_targets');

    // Notifications: mark read endpoint for AJAX
    Route::post('/notifications/mark-read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.markRead');
    // Notifications list page
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    // AJAX endpoint to fetch recent notifications and unread count
    Route::get('/notifications/recent', [\App\Http\Controllers\NotificationController::class, 'recent'])->name('notifications.recent');
    Route::get('/notifications/weekly-target-alert', [\App\Http\Controllers\NotificationController::class, 'weeklyTargetAlert'])->name('notifications.weekly_target_alert');

    // Task Reminder Reports Route
    Route::get('/task-reports', [TaskReportController::class, 'index'])->name('task-reports.index');
    Route::get('/reports/task', [\App\Http\Controllers\ReportController::class, 'taskReport'])->name('reports.task');

    // Weekly Activity Report (leads/deals with Notes, Meetings, Tasks)
    Route::get('/reports/weekly-activity', [\App\Http\Controllers\ReportController::class, 'weeklyActivityReport'])->name('reports.weekly_activity');
    Route::get('/reports/weekly-activity/excel', [\App\Http\Controllers\ReportController::class, 'exportWeeklyActivity'])->name('reports.weekly_activity_excel');

    // User Activity Report
    Route::get('/reports/user-activity', [UserActivityReportController::class, 'index'])->name('reports.user_activity');

    // Route to fetch leads list for a user and category
    Route::get('/leads/list/{user}', [\App\Http\Controllers\UserReportController::class, 'fetchLeadsList']);

    // Route to fetch deals list for a user and category
    Route::get('/deals/list/{user}', [\App\Http\Controllers\UserReportController::class, 'fetchDealsList']);

    // Quotation routes
    Route::get('/quotations/products/{product}/meta', [QuotationController::class, 'productMeta'])->name('quotations.product_meta');
    Route::get('/quotations/meta/next-proposal-version', [QuotationController::class, 'nextProposalVersion'])->name('quotations.next_proposal_version');
    Route::post('/quotations/wizard-template-preview', [QuotationController::class, 'wizardTemplatePreview'])->name('quotations.wizard_template_preview');
    Route::get('/quotations/send-logs/whatsapp', [QuotationController::class, 'whatsappSendLogs'])->name('quotations.whatsapp_send_logs');
    Route::get('/quotations/send-logs/email', [QuotationController::class, 'emailSendLogs'])->name('quotations.email_send_logs');
    Route::post('/quotations/editor/upload-image', [QuotationController::class, 'uploadEditorImage'])->name('quotations.editor.upload_image');
    Route::resource('quotations', App\Http\Controllers\QuotationController::class);
    // Add a custom route for AJAX deal creation
    Route::post('/deals/ajax-create', [App\Http\Controllers\DealController::class, 'ajaxCreate'])->name('deals.ajaxCreate');
    Route::get('/quotations/{id}/pdf', [QuotationController::class, 'generatePdf'])->name('quotations.pdf');
    Route::get('/quotations/download/{id}', [QuotationController::class, 'downloadQuotation'])->name('quotations.download');
    Route::post('/quotations/{id}/send-whatsapp', [QuotationController::class, 'sendWhatsapp'])->name('quotations.send_whatsapp');
    Route::post('/quotations/{id}/send-email', [QuotationController::class, 'sendEmail'])->name('quotations.send_email');
    Route::get('/quotations/{id}/email-compose', [QuotationController::class, 'emailComposeData'])->name('quotations.email_compose');
    Route::get('/quotations/{id}/gmail-compose', [QuotationController::class, 'gmailComposeData'])->name('quotations.gmail_compose');
    Route::post('/quotations/{id}/send-gmail', [QuotationController::class, 'sendGmail'])->name('quotations.send_gmail');

    Route::get('/google/gmail/connect', [\App\Http\Controllers\GoogleGmailController::class, 'connect'])->name('google.gmail.connect');
    Route::get('/google/gmail/status', [\App\Http\Controllers\GoogleGmailController::class, 'status'])->name('google.gmail.status');

    Route::get('/calendar', [GoogleCalendarController::class, 'calendar'])->name('calendar.events');

    Route::get('/events', [MeetingController::class, 'fetchMeetings']);
  
  	Route::get('/reports/lead-deals-report', [\App\Http\Controllers\ReportController::class, 'lead_deals_reports'])->name('reports.lead_deals');
    Route::get('/reports/lead-deals-report/excel', [\App\Http\Controllers\ReportController::class, 'exportLeadDealsReport'])->name('reports.lead_deals_excel');

});

// Application logs viewer (admin only)
Route::middleware(['auth'])->group(function () {
    Route::get('/logs/application', [LogController::class, 'application'])->name('logs.application');
});

// Activity logs viewer (admin only)
Route::middleware(['auth'])->group(function () {
    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/{activityLog}', [\App\Http\Controllers\ActivityLogController::class, 'show'])->name('activity-logs.show');
});


// Email verification routes (must be outside auth middleware)
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::get('/email/verified/success', [EmailVerificationController::class, 'success'])->name('verification.success');


// Email-based 2FA routes
Route::get('/2fa/email', [App\Http\Controllers\Auth\Email2FAController::class, 'showForm'])->name('auth.2fa.verify');
Route::post('/2fa/email', [App\Http\Controllers\Auth\Email2FAController::class, 'verify'])->middleware('throttle:10,1')->name('auth.2fa.verify.post');


require __DIR__ . '/auth.php';

// Internal endpoints (token-protected, minimal middleware) -------------------------------------------------
// POST /internal/run-reminders
// Use header 'X-INTERNAL-RUN-TOKEN' or query param '?token=...'
Route::middleware(['web'])->group(function () {
    Route::post('/internal/run-reminders', [\App\Http\Controllers\InternalController::class, 'runReminders'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);
});





