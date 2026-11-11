<?php
use App\Http\Controllers\Api\Mobile\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadApiController;

use App\Http\Controllers\Api\Mobile\LoginController;

use App\Http\Controllers\Api\Mobile\GoogleLoginController;

use App\Http\Controllers\Api\Mobile\OrganizationController;
use App\Http\Controllers\Api\Mobile\ContactPersonController;
use App\Http\Controllers\Api\Mobile\CompanyOwnerController;

// Website lead capture API: requires the LEAD_API_KEY shared secret (X-Api-Key header)
Route::middleware([\App\Http\Middleware\VerifyLeadApiKey::class, 'throttle:30,1'])->group(function () {
    Route::post('/leads', [LeadApiController::class, 'store']);
    Route::post('/leads/check-duplicate', [LeadApiController::class, 'checkDuplicate']);
});

Route::get('/product-categories/{type?}', [LeadApiController::class, 'product_categories'])->middleware('throttle:60,1');

// Mobile App Login API
Route::post('/mobile/login', [LoginController::class, 'login'])->middleware('throttle:5,1');


// Mobile App Google Login API
Route::post('/mobile/google-login', [GoogleLoginController::class, 'login'])->middleware('throttle:10,1');


Route::middleware('auth:sanctum')->group(function () {
    
    // Mobile App Contact Person List API (requires token)
    Route::get('/mobile/organizations/{organization}/contacts', [ContactPersonController::class, 'index']);
    // Mobile App Company Owner List API (requires token)
    Route::get('/mobile/organizations/{organization}/owners', [CompanyOwnerController::class, 'index']);

    Route::get('/mobile/other/lead-sources', [App\Http\Controllers\Api\Mobile\OtherController::class, 'leadSourceList']);
    Route::get('/mobile/other/lead-statuses', [App\Http\Controllers\Api\Mobile\OtherController::class, 'leadStatusList']);
    Route::get('/mobile/other/priorities', [App\Http\Controllers\Api\Mobile\OtherController::class, 'priorityList']);
    Route::get('/mobile/other/categories', [App\Http\Controllers\Api\Mobile\OtherController::class, 'categoryList']);
    Route::get('/mobile/other/users', [App\Http\Controllers\Api\Mobile\OtherController::class, 'userList']);
    Route::get('/mobile/other/stages', [App\Http\Controllers\Api\Mobile\OtherController::class, 'dealStageList']);
    Route::get('/mobile/other/user-permissions', [App\Http\Controllers\Api\Mobile\OtherController::class, 'userPermissions']);
    Route::get('/mobile/other/main-categories', [App\Http\Controllers\Api\Mobile\OtherController::class, 'mainCategoryList']);

    // Mobile App Leads APIs (requires token)
    Route::get('/mobile/leads', [App\Http\Controllers\Api\Mobile\LeadsController::class, 'index']);
    Route::get('/mobile/leads/{lead}', [App\Http\Controllers\Api\Mobile\LeadsController::class, 'show']);
    Route::post('/mobile/leads', [App\Http\Controllers\Api\Mobile\LeadsController::class, 'store']);
    Route::post('/mobile/leads/{lead}', [App\Http\Controllers\Api\Mobile\LeadsController::class, 'update']);
    Route::delete('/mobile/leads/{lead}', [App\Http\Controllers\Api\Mobile\LeadsController::class, 'destroy']);
    
    // Mobile App Deals APIs (requires token)
    Route::get('/mobile/deals', [App\Http\Controllers\Api\Mobile\DealsController::class, 'index']);
    Route::get('/mobile/deals/{deal}', [App\Http\Controllers\Api\Mobile\DealsController::class, 'show']);
    Route::post('/mobile/deals', [App\Http\Controllers\Api\Mobile\DealsController::class, 'store']);
    Route::post('/mobile/deals/{deal}', [App\Http\Controllers\Api\Mobile\DealsController::class, 'update']);
    Route::delete('/mobile/deals/{deal}', [App\Http\Controllers\Api\Mobile\DealsController::class, 'destroy']);
    Route::post('/mobile/deals/store-from-lead/{lead}', [App\Http\Controllers\Api\Mobile\DealsController::class, 'storeFromLead']);
    Route::post('/mobile/deals/{deal}/switch-stage', [App\Http\Controllers\Api\Mobile\DealsController::class, 'switchStage']);

    // Mobile App Organizations APIs (requires token)
    Route::get('/mobile/organizations', [OrganizationController::class, 'index']);
    Route::get('/mobile/organizations/autocomplete', [OrganizationController::class, 'autocomplete']);

    Route::post('/mobile/organizations/company-create', [OrganizationController::class, 'CompanyCreate']);

    Route::post('/mobile/organizations', [OrganizationController::class, 'store']);
    Route::get('/mobile/organizations/{organization}', [OrganizationController::class, 'show']);
    Route::post('/mobile/organizations/{organization}', [OrganizationController::class, 'update']);
    Route::delete('/mobile/organizations/{organization}', [OrganizationController::class, 'destroy']);

    

    // Mobile App Contact Persons APIs (requires token)
    Route::get('/mobile/contacts', [ContactPersonController::class, 'listAllContacts']);
     Route::get('/mobile/contacts/autocomplete', [ContactPersonController::class, 'autocomplete']);
    Route::get('/mobile/contacts/autosearch', [ContactPersonController::class, 'autosearch']);
    Route::get('/mobile/contacts/{contact}', [ContactPersonController::class, 'show']);
    Route::post('/mobile/contacts', [ContactPersonController::class, 'store']);

    Route::post('/mobile/contacts/createPerson', [ContactPersonController::class, 'ajaxCreate']);

    Route::post('/mobile/contacts/{contact}', [ContactPersonController::class, 'update']);
    Route::delete('/mobile/contacts/{contact}', [ContactPersonController::class, 'destroy']);
   

    // Mobile App Tasks APIs (requires token)
    Route::get('/mobile/tasks', [App\Http\Controllers\Api\Mobile\TaskController::class, 'index']);
    Route::post('/mobile/tasks/create', [App\Http\Controllers\Api\Mobile\TaskController::class, 'store']);
    Route::post('/mobile/tasks/{id}/mark-as-completed', [App\Http\Controllers\Api\Mobile\TaskController::class, 'markAsCompleted']);
    Route::delete('/mobile/tasks/delete/{id}', [App\Http\Controllers\Api\Mobile\TaskController::class, 'destroy']);
    Route::get('/mobile/tasks/{id}', [App\Http\Controllers\Api\Mobile\TaskController::class, 'show']);
    Route::post('/mobile/tasks/update/{id}', [App\Http\Controllers\Api\Mobile\TaskController::class, 'update']);


    // Mobile App Report APIs (requires token)
    Route::get('/mobile/reports/leads', [ReportController::class, 'leadReport']);
    Route::get('/mobile/reports/deals', [ReportController::class, 'dealReport']);
    Route::get('/mobile/reports/deals/monthly-revenue', [ReportController::class, 'revenueReport']);
    Route::get('/mobile/reports/converted-leads', [ReportController::class, 'convertedLeadsReport']);
    Route::get('/mobile/reports/user-performance', [ReportController::class, 'userPerformanceReport']);
    Route::get('/mobile/reports/weekly-activity', [ReportController::class, 'weeklyActivityReport']);

    //mobile App Dashbaord APIs (requires token)
    Route::get('/mobile/dashboard', [App\Http\Controllers\Api\Mobile\DashboardController::class, 'index']);

    //Activty APIs
    Route::post('/mobile/notes', [App\Http\Controllers\Api\Mobile\NotesController::class, 'store']);
    Route::post('/mobile/notes/{note}', [App\Http\Controllers\Api\Mobile\NotesController::class, 'update']);
    Route::delete('/mobile/notes/{note}', [App\Http\Controllers\Api\Mobile\NotesController::class, 'destroy']);

    Route::post('/mobile/calls', [App\Http\Controllers\Api\Mobile\CallController::class, 'store']);
    Route::post('/mobile/calls/{call}', [App\Http\Controllers\Api\Mobile\CallController::class, 'update']);
    Route::delete('/mobile/calls/{call}', [App\Http\Controllers\Api\Mobile\CallController::class, 'destroy']);
    
    Route::get('/mobile/files/{file}/download', [App\Http\Controllers\Api\Mobile\FileDownloadController::class, 'download'])->name('mobile.files.download');

    Route::post('/mobile/meetings', [App\Http\Controllers\Api\Mobile\MeetingController::class, 'store']);
    Route::post('/mobile/meetings/{meeting}', [App\Http\Controllers\Api\Mobile\MeetingController::class, 'update']);
    Route::delete('/mobile/meetings/{meeting}', [App\Http\Controllers\Api\Mobile\MeetingController::class, 'destroy']);

    Route::get('/mobile/profile', [App\Http\Controllers\Api\Mobile\ProfileController::class, 'show']);

    Route::get('/mobile/contacts/{contact}/callactivities', [App\Http\Controllers\Api\Mobile\CallController::class, 'contactCallHistory']);
    Route::get('/mobile/callLogs', [App\Http\Controllers\Api\Mobile\CallController::class, 'callLogsList']);
    Route::post('/mobile/callLogs', [App\Http\Controllers\Api\Mobile\CallController::class, 'createCallLog']);

    // Route::get('/mobile/push-notifications', [App\Http\Controllers\Api\Mobile\NotificationController::class, 'index']);
    Route::post('/mobile/save-token', [App\Http\Controllers\Api\Mobile\NotificationController::class, 'saveToken']);

    Route::get('/mobile/notifications', [App\Http\Controllers\Api\Mobile\NotificationController::class, 'listNotifications']);
    Route::get('/mobile/notifications/mark-read/{id}', [App\Http\Controllers\Api\Mobile\NotificationController::class, 'clickNotification']);
    Route::post('/mobile/profile/change-password', [App\Http\Controllers\Api\Mobile\ProfileController::class, 'changePassword']);
    
});