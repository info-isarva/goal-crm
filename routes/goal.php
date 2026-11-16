<?php

use App\Http\Controllers\Goal\GoalAccountsController;
use App\Http\Controllers\Goal\GoalAllocationController;
use App\Http\Controllers\Goal\GoalAnalyticsController;
use App\Http\Controllers\Goal\GoalCaseWorkspaceController;
use App\Http\Controllers\Goal\GoalLoanCaseController;
use App\Http\Controllers\Goal\GoalLoanLeadController;
use App\Http\Controllers\Goal\GoalMisController;
use App\Http\Controllers\Goal\GoalOrgUnitController;
use App\Http\Controllers\Goal\GoalPayoutController;
use App\Http\Controllers\Goal\GoalPmsController;
use App\Http\Controllers\Goal\GoalTargetController;
use App\Http\Controllers\Goal\GoalVrmController;
use Illuminate\Support\Facades\Route;

Route::prefix('goal')->name('goal.')->group(function () {
    Route::get('analytics', [GoalAnalyticsController::class, 'index'])->name('analytics');

    Route::get('org-units', [GoalOrgUnitController::class, 'index'])->name('org-units.index');
    Route::get('org-units/create', [GoalOrgUnitController::class, 'create'])->name('org-units.create');
    Route::post('org-units', [GoalOrgUnitController::class, 'store'])->name('org-units.store');
    Route::get('org-units/{org_unit}/edit', [GoalOrgUnitController::class, 'edit'])->name('org-units.edit');
    Route::put('org-units/{org_unit}', [GoalOrgUnitController::class, 'update'])->name('org-units.update');

    Route::get('vrm', [GoalVrmController::class, 'index'])->name('vrm.index');
    Route::post('vrm', [GoalVrmController::class, 'store'])->name('vrm.store');
    Route::post('vrm/products/quick', [GoalVrmController::class, 'storeQuickProduct'])->name('vrm.products.quick');
    Route::get('vrm/calls', [GoalVrmController::class, 'calls'])->name('vrm.calls');
    Route::get('vrm/calls/export', [GoalVrmController::class, 'exportCalls'])->name('vrm.calls.export');
    Route::get('vrm/calls/import', [GoalVrmController::class, 'importForm'])->name('vrm.calls.import');
    Route::post('vrm/calls/import', [GoalVrmController::class, 'import'])->name('vrm.calls.import.store');
    Route::get('vrm/calls/{log}', [GoalVrmController::class, 'showCall'])->name('vrm.calls.show');
    Route::put('vrm/calls/{log}', [GoalVrmController::class, 'updateCall'])->name('vrm.calls.update');
    Route::delete('vrm/calls/{log}', [GoalVrmController::class, 'destroyCall'])->name('vrm.calls.destroy');

    Route::get('leads', [GoalLoanLeadController::class, 'index'])->name('leads.index');
    Route::get('leads/create', [GoalLoanLeadController::class, 'create'])->name('leads.create');
    Route::post('leads', [GoalLoanLeadController::class, 'store'])->name('leads.store');
    Route::get('leads/{lead}', [GoalLoanLeadController::class, 'show'])->name('leads.show');
    Route::put('leads/{lead}', [GoalLoanLeadController::class, 'update'])->name('leads.update');
    Route::post('leads/{lead}/notes', [GoalLoanLeadController::class, 'addNote'])->name('leads.notes');
    Route::delete('leads/{lead}/notes/{index}', [GoalLoanLeadController::class, 'deleteNote'])->name('leads.notes.destroy');
    Route::post('leads/{lead}/reschedule', [GoalLoanLeadController::class, 'reschedule'])->name('leads.reschedule');
    Route::post('leads/{lead}/visit', [GoalLoanLeadController::class, 'logVisit'])->name('leads.visit');
    Route::post('leads/{lead}/convert', [GoalLoanLeadController::class, 'convert'])->name('leads.convert');
    Route::post('leads/{lead}/transition', [GoalLoanLeadController::class, 'transition'])->name('leads.transition');
    Route::post('leads/{lead}/loan-cases', [GoalLoanCaseController::class, 'storeFromLead'])->name('leads.loan-cases.store');

    Route::get('allocation', [GoalAllocationController::class, 'index'])->name('allocation.index');
    Route::post('allocation/bulk', [GoalAllocationController::class, 'assignBulk'])->name('allocation.bulk');
    Route::get('allocation/{lead}/history', [GoalAllocationController::class, 'history'])->name('allocation.history');
    Route::post('allocation/{lead}', [GoalAllocationController::class, 'assign'])->name('allocation.assign');

    Route::get('cases', [GoalLoanCaseController::class, 'index'])->name('cases.index');
    Route::get('cases/create', [GoalLoanCaseController::class, 'create'])->name('cases.create');
    Route::get('cases/lead-lookup', [GoalLoanCaseController::class, 'leadLookup'])->name('cases.lead-lookup');
    Route::get('cases/product-documents', [GoalLoanCaseController::class, 'productDocuments'])->name('cases.product-documents');
    Route::post('cases', [GoalLoanCaseController::class, 'store'])->name('cases.store');
    Route::get('cases/{case}', [GoalLoanCaseController::class, 'show'])->name('cases.show');
    Route::post('cases/{case}/workspace', [GoalLoanCaseController::class, 'updateWorkspace'])->name('cases.workspace');
    Route::post('cases/{case}/transition', [GoalLoanCaseController::class, 'transition'])->name('cases.transition');
    Route::post('cases/{case}/tasks', [GoalLoanCaseController::class, 'storeTask'])->name('cases.tasks');
    Route::post('cases/{case}/reassign', [GoalCaseWorkspaceController::class, 'reassign'])->name('cases.reassign');
    Route::post('cases/{case}/documents', [GoalCaseWorkspaceController::class, 'uploadDocument'])->name('cases.documents.store');
    Route::post('cases/{case}/documents/verify', [GoalCaseWorkspaceController::class, 'verifyDocument'])->name('cases.documents.verify');
    Route::post('cases/{case}/bank-applications', [GoalCaseWorkspaceController::class, 'storeApplication'])->name('cases.bank-applications.store');
    Route::patch('bank-applications/{application}/status', [GoalCaseWorkspaceController::class, 'updateApplicationStatus'])->name('bank-applications.status');
    Route::post('bank-applications/{application}/queries', [GoalCaseWorkspaceController::class, 'storeQuery'])->name('bank-applications.queries.store');
    Route::post('bank-queries/{query}/resolve', [GoalCaseWorkspaceController::class, 'resolveQuery'])->name('bank-queries.resolve');
    Route::post('bank-applications/{application}/sanctions', [GoalCaseWorkspaceController::class, 'storeSanction'])->name('bank-applications.sanctions.store');
    Route::post('cases/{case}/disbursements', [GoalCaseWorkspaceController::class, 'storeDisbursement'])->name('cases.disbursements.store');
    Route::post('disbursements/{disbursement}/verify', [GoalCaseWorkspaceController::class, 'verifyDisbursement'])->name('disbursements.verify');
    Route::post('cases/{case}/pms-transfer', [GoalCaseWorkspaceController::class, 'transferPms'])->name('cases.pms-transfer');

    Route::get('pms', [GoalPmsController::class, 'index'])->name('pms.index');
    Route::post('pms/{item}/verify', [GoalPmsController::class, 'verify'])->name('pms.verify');

    Route::get('accounts', [GoalAccountsController::class, 'index'])->name('accounts.index');
    Route::post('accounts/{item}/report', [GoalAccountsController::class, 'updateReport'])->name('accounts.report');

    Route::get('payouts', [GoalPayoutController::class, 'index'])->name('payouts.index');
    Route::post('payouts/{payout}/receipt', [GoalPayoutController::class, 'recordReceipt'])->name('payouts.receipt');
    Route::post('payouts/{payout}/exception', [GoalPayoutController::class, 'markException'])->name('payouts.exception');

    Route::get('targets', [GoalTargetController::class, 'index'])->name('targets.index');
    Route::post('targets', [GoalTargetController::class, 'store'])->name('targets.store');
    Route::put('targets/{target}', [GoalTargetController::class, 'update'])->name('targets.update');

    Route::get('branch-mis', [GoalMisController::class, 'branchMis'])->name('branch-mis');
    Route::get('revenue-ledger', [GoalMisController::class, 'revenueLedger'])->name('revenue-ledger');
});
