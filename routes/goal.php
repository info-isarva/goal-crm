<?php

use App\Http\Controllers\Goal\GoalAccountsController;
use App\Http\Controllers\Goal\GoalAllocationController;
use App\Http\Controllers\Goal\GoalAnalyticsController;
use App\Http\Controllers\Goal\GoalScheduledReportController;
use App\Http\Controllers\Goal\GoalCaseWorkspaceController;
use App\Http\Controllers\Goal\GoalBankProcessingController;
use App\Http\Controllers\Goal\GoalDocumentsController;
use App\Http\Controllers\Goal\GoalSanctionDisbursementController;
use App\Http\Controllers\Goal\GoalLoanCaseController;
use App\Http\Controllers\Goal\GoalLoanLeadController;
use App\Http\Controllers\Goal\GoalMisController;
use App\Http\Controllers\Goal\GoalMisReportController;
use App\Http\Controllers\Goal\GoalOrgUnitController;
use App\Http\Controllers\Goal\GoalPayoutController;
use App\Http\Controllers\Goal\GoalPmsController;
use App\Http\Controllers\Goal\GoalTargetController;
use App\Http\Controllers\Goal\GoalVisitsController;
use App\Http\Controllers\Goal\GoalVrmController;
use App\Http\Controllers\Goal\GoalLeadSourceController;
use App\Http\Controllers\Goal\GoalLoanPurposeController;
use App\Http\Controllers\Goal\GoalVrmDispositionController;
use Illuminate\Support\Facades\Route;

Route::prefix('goal')->name('goal.')->group(function () {
    Route::get('analytics', [GoalAnalyticsController::class, 'index'])->name('analytics');
    Route::get('analytics/export', [GoalAnalyticsController::class, 'export'])->name('analytics.export');
    Route::get('mis-reports', [GoalAnalyticsController::class, 'misReports'])->name('mis-reports');
    Route::get('mis-reports/{report}', [GoalMisReportController::class, 'show'])->name('mis-reports.show');
    Route::get('mis-reports/{report}/export', [GoalMisReportController::class, 'export'])->name('mis-reports.export');

    Route::get('scheduled-reports', [GoalScheduledReportController::class, 'index'])->name('scheduled-reports.index');
    Route::post('scheduled-reports', [GoalScheduledReportController::class, 'store'])->name('scheduled-reports.store');
    Route::put('scheduled-reports/{scheduled_report}', [GoalScheduledReportController::class, 'update'])->name('scheduled-reports.update');
    Route::post('scheduled-reports/{scheduled_report}/pause', [GoalScheduledReportController::class, 'pause'])->name('scheduled-reports.pause');
    Route::post('scheduled-reports/{scheduled_report}/resume', [GoalScheduledReportController::class, 'resume'])->name('scheduled-reports.resume');
    Route::post('scheduled-reports/{scheduled_report}/run', [GoalScheduledReportController::class, 'runNow'])->name('scheduled-reports.run');
    Route::delete('scheduled-reports/{scheduled_report}', [GoalScheduledReportController::class, 'destroy'])->name('scheduled-reports.destroy');

    Route::get('org-units', [GoalOrgUnitController::class, 'index'])->name('org-units.index');
    Route::get('org-units/export', [GoalOrgUnitController::class, 'export'])->name('org-units.export');
    Route::get('org-units/create', [GoalOrgUnitController::class, 'create'])->name('org-units.create');
    Route::post('org-units', [GoalOrgUnitController::class, 'store'])->name('org-units.store');
    Route::get('org-units/{org_unit}/edit', [GoalOrgUnitController::class, 'edit'])->name('org-units.edit');
    Route::put('org-units/{org_unit}', [GoalOrgUnitController::class, 'update'])->name('org-units.update');

    Route::get('vrm', [GoalVrmController::class, 'index'])->name('vrm.index');
    Route::post('vrm', [GoalVrmController::class, 'store'])->name('vrm.store');
    Route::post('vrm/products/quick', [GoalVrmController::class, 'storeQuickProduct'])->name('vrm.products.quick');
    Route::post('vrm/dispositions/quick', [GoalVrmController::class, 'storeQuickDisposition'])->name('vrm.dispositions.quick');
    Route::put('vrm/dispositions/{disposition}', [GoalVrmController::class, 'updateDisposition'])->name('vrm.dispositions.update');

    Route::get('masters/dispositions', [GoalVrmDispositionController::class, 'index'])->name('masters.dispositions.index');
    Route::post('masters/dispositions', [GoalVrmDispositionController::class, 'store'])->name('masters.dispositions.store');
    Route::put('masters/dispositions/{disposition}', [GoalVrmDispositionController::class, 'update'])->name('masters.dispositions.update');

    Route::get('masters/sources', [GoalLeadSourceController::class, 'index'])->name('masters.sources.index');
    Route::post('masters/sources', [GoalLeadSourceController::class, 'store'])->name('masters.sources.store');
    Route::put('masters/sources/{source}', [GoalLeadSourceController::class, 'update'])->name('masters.sources.update');
    Route::post('masters/sources/{source}/restore', [GoalLeadSourceController::class, 'restore'])->name('masters.sources.restore');

    Route::get('masters/purposes', [GoalLoanPurposeController::class, 'index'])->name('masters.purposes.index');
    Route::post('masters/purposes', [GoalLoanPurposeController::class, 'store'])->name('masters.purposes.store');
    Route::put('masters/purposes/{purpose}', [GoalLoanPurposeController::class, 'update'])->name('masters.purposes.update');
    Route::get('vrm/calls', [GoalVrmController::class, 'calls'])->name('vrm.calls');
    Route::get('vrm/calls/export', [GoalVrmController::class, 'exportCalls'])->name('vrm.calls.export');
    Route::get('vrm/calls/import', [GoalVrmController::class, 'importForm'])->name('vrm.calls.import');
    Route::post('vrm/calls/import', [GoalVrmController::class, 'import'])->name('vrm.calls.import.store');
    Route::get('vrm/calls/{log}', [GoalVrmController::class, 'showCall'])->name('vrm.calls.show');
    Route::put('vrm/calls/{log}', [GoalVrmController::class, 'updateCall'])->name('vrm.calls.update');
    Route::delete('vrm/calls/{log}', [GoalVrmController::class, 'destroyCall'])->name('vrm.calls.destroy');

    Route::get('leads', [GoalLoanLeadController::class, 'index'])->name('leads.index');
    Route::get('leads/create', [GoalLoanLeadController::class, 'create'])->name('leads.create');
    Route::get('leads/party-matches', [GoalLoanLeadController::class, 'partyMatches'])->name('leads.party-matches');
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
    Route::get('allocation/export', [GoalAllocationController::class, 'export'])->name('allocation.export');
    Route::post('allocation/bulk', [GoalAllocationController::class, 'assignBulk'])->name('allocation.bulk');
    Route::get('allocation/{lead}/history', [GoalAllocationController::class, 'history'])->name('allocation.history');
    Route::post('allocation/{lead}', [GoalAllocationController::class, 'assign'])->name('allocation.assign');

    Route::get('documents', [GoalDocumentsController::class, 'index'])->name('documents.index');
    Route::get('documents/export', [GoalDocumentsController::class, 'export'])->name('documents.export');
    Route::post('documents/upload', [GoalDocumentsController::class, 'upload'])->name('documents.upload');
    Route::get('documents/{case}/file', [GoalDocumentsController::class, 'file'])->name('documents.file');
    Route::post('documents/{case}/verify', [GoalDocumentsController::class, 'verify'])->name('documents.verify');

    Route::get('bank-processing', [GoalBankProcessingController::class, 'index'])->name('bank-processing.index');
    Route::get('bank-processing/export', [GoalBankProcessingController::class, 'export'])->name('bank-processing.export');
    Route::post('bank-processing/applications', [GoalBankProcessingController::class, 'storeApplication'])->name('bank-processing.applications.store');
    Route::post('bank-processing/applications/{application}/queries', [GoalBankProcessingController::class, 'storeQuery'])->name('bank-processing.queries.store');

    Route::get('sanction-disbursement', [GoalSanctionDisbursementController::class, 'index'])->name('sanction-disbursement.index');

    Route::get('visits', [GoalVisitsController::class, 'index'])->name('visits.index');
    Route::get('visits/export', [GoalVisitsController::class, 'export'])->name('visits.export');
    Route::post('visits', [GoalVisitsController::class, 'store'])->name('visits.store');
    Route::put('visits/{task}', [GoalVisitsController::class, 'update'])->name('visits.update');
    Route::post('visits/{task}/complete', [GoalVisitsController::class, 'complete'])->name('visits.complete');

    Route::get('cases', [GoalLoanCaseController::class, 'index'])->name('cases.index');
    Route::get('cases/export', [GoalLoanCaseController::class, 'export'])->name('cases.export');
    Route::get('cases/create', [GoalLoanCaseController::class, 'create'])->name('cases.create');
    Route::get('cases/lead-lookup', [GoalLoanCaseController::class, 'leadLookup'])->name('cases.lead-lookup');
    Route::get('cases/product-documents', [GoalLoanCaseController::class, 'productDocuments'])->name('cases.product-documents');
    Route::post('cases', [GoalLoanCaseController::class, 'store'])->name('cases.store');
    Route::get('cases/{case}', [GoalLoanCaseController::class, 'show'])->name('cases.show');
    Route::post('cases/{case}/workspace', [GoalLoanCaseController::class, 'updateWorkspace'])->name('cases.workspace');
    Route::post('cases/{case}/transition', [GoalLoanCaseController::class, 'transition'])->name('cases.transition');
    Route::post('cases/{case}/tasks', [GoalLoanCaseController::class, 'storeTask'])->name('cases.tasks');
    Route::post('cases/{case}/reassign', [GoalCaseWorkspaceController::class, 'reassign'])->name('cases.reassign');
    Route::get('cases/{case}/bm-reco/export', [GoalCaseWorkspaceController::class, 'exportBmReco'])->name('cases.bm-reco.export');
    Route::post('cases/{case}/bm-reco/equifax', [GoalCaseWorkspaceController::class, 'uploadBmRecoEquifax'])->name('cases.bm-reco.equifax');
    Route::get('cases/{case}/bm-reco/equifax', [GoalCaseWorkspaceController::class, 'downloadBmRecoEquifax'])->name('cases.bm-reco.equifax.download');
    Route::delete('cases/{case}/bm-reco/equifax', [GoalCaseWorkspaceController::class, 'removeBmRecoEquifax'])->name('cases.bm-reco.equifax.remove');
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
    Route::get('pms/export', [GoalPmsController::class, 'export'])->name('pms.export');
    Route::get('pms/{item}', [GoalPmsController::class, 'show'])->name('pms.show');
    Route::post('pms/{item}/start', [GoalPmsController::class, 'start'])->name('pms.start');
    Route::post('pms/{item}/verify', [GoalPmsController::class, 'verify'])->name('pms.verify');
    Route::post('pms/{item}/reassign', [GoalPmsController::class, 'reassign'])->name('pms.reassign');
    Route::post('pms/{item}/followups', [GoalPmsController::class, 'storeFollowup'])->name('pms.followups.store');
    Route::post('pms/followups/{followup}/complete', [GoalPmsController::class, 'completeFollowup'])->name('pms.followups.complete');
    Route::post('pms/followups/{followup}/cancel', [GoalPmsController::class, 'cancelFollowup'])->name('pms.followups.cancel');
    Route::post('pms/followups/{followup}/reschedule', [GoalPmsController::class, 'rescheduleFollowup'])->name('pms.followups.reschedule');

    Route::get('accounts', [GoalAccountsController::class, 'index'])->name('accounts.index');
    Route::get('accounts/export', [GoalAccountsController::class, 'export'])->name('accounts.export');
    Route::get('accounts/{item}', [GoalAccountsController::class, 'show'])->name('accounts.show');
    Route::post('accounts/{item}/report', [GoalAccountsController::class, 'updateReport'])->name('accounts.report');
    Route::post('accounts/{item}/submit', [GoalAccountsController::class, 'submit'])->name('accounts.submit');
    Route::post('accounts/{item}/approve', [GoalAccountsController::class, 'approve'])->name('accounts.approve');
    Route::post('accounts/{item}/receipts', [GoalAccountsController::class, 'storeReceipt'])->name('accounts.receipts.store');

    Route::get('payouts', [GoalPayoutController::class, 'index'])->name('payouts.index');
    Route::get('payouts/export', [GoalPayoutController::class, 'export'])->name('payouts.export');
    Route::get('payouts/{payout}', [GoalPayoutController::class, 'show'])->name('payouts.show');
    Route::post('payouts/{payout}/receipt', [GoalPayoutController::class, 'recordReceipt'])->name('payouts.receipt');
    Route::post('payouts/{payout}/exception', [GoalPayoutController::class, 'markException'])->name('payouts.exception');
    Route::post('payouts/{payout}/release-hold', [GoalPayoutController::class, 'releaseHold'])->name('payouts.release-hold');

    Route::get('targets', [GoalTargetController::class, 'index'])->name('targets.index');
    Route::get('targets/export', [GoalTargetController::class, 'export'])->name('targets.export');
    Route::post('targets', [GoalTargetController::class, 'store'])->name('targets.store');
    Route::put('targets/{target}', [GoalTargetController::class, 'update'])->name('targets.update');

    Route::get('branch-mis', [GoalMisController::class, 'branchMis'])->name('branch-mis');
    Route::get('branch-mis/export', [GoalMisController::class, 'exportBranchMis'])->name('branch-mis.export');
    Route::get('revenue-ledger', [GoalMisController::class, 'revenueLedger'])->name('revenue-ledger');
    Route::get('revenue-ledger/export', [GoalMisController::class, 'exportRevenueLedger'])->name('revenue-ledger.export');
    Route::post('revenue-ledger/adjust', [GoalMisController::class, 'adjustRevenue'])->name('revenue-ledger.adjust');
});
