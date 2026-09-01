<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AdvancedChurchModuleController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\OtpChallengeController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChurchController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\ChurchEventController;
use App\Http\Controllers\ChurchServiceController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EngagementAdminModuleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MinistryGroupController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\PastoralModuleController;
use App\Http\Controllers\PublicFlowController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SolutionModuleController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WorkspaceContextController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('otp', [OtpChallengeController::class, 'create'])->name('otp.create');
    Route::post('otp', [OtpChallengeController::class, 'store'])->name('otp.store');
});

Route::get('public/eglises/{church}/don', [PublicFlowController::class, 'donation'])->name('public.donation');
Route::post('public/eglises/{church}/don', [PublicFlowController::class, 'storeDonation'])->name('public.donation.store');
Route::get('public/eglises/{church}/visiteur', [PublicFlowController::class, 'visitor'])->name('public.visitor');
Route::post('public/eglises/{church}/visiteur', [PublicFlowController::class, 'storeVisitor'])->name('public.visitor.store');
Route::get('public/evenements/{event}', [PublicFlowController::class, 'event'])->name('public.event');
Route::post('public/evenements/{event}', [PublicFlowController::class, 'storeEvent'])->name('public.event.store');

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('workspace/switch', [WorkspaceContextController::class, 'update'])->name('workspace.switch');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware('workspace:communaute')->group(function () {
        Route::resource('communautes', CommunityController::class)->only(['index', 'store']);
        Route::resource('eglises', ChurchController::class)->only(['index', 'store']);
        Route::resource('utilisateurs', UserManagementController::class)->only(['index', 'store']);
        Route::get('roles-permissions', [RolePermissionController::class, 'index'])->name('roles-permissions.index');
        Route::post('roles-permissions/roles', [RolePermissionController::class, 'storeRole'])->name('roles-permissions.roles.store');
        Route::post('roles-permissions/permissions', [RolePermissionController::class, 'storePermission'])->name('roles-permissions.permissions.store');
        Route::put('roles-permissions/roles/{role}/permissions', [RolePermissionController::class, 'syncRolePermissions'])->name('roles-permissions.roles.permissions');
    });

    Route::middleware('workspace:eglise')->group(function () {
        Route::post('offline/sync', [OfflineSyncController::class, 'store'])->name('offline.sync');
        Route::resource('membres', MemberController::class)->only(['index', 'store']);
        Route::patch('membres/{member}/statut', [MemberController::class, 'promote'])->name('membres.promote');
        Route::get('comptabilite', [AccountingController::class, 'index'])->name('accounting.index');
        Route::post('comptabilite/collectes', [AccountingController::class, 'collection'])->name('accounting.collection');
        Route::post('comptabilite/ecritures', [AccountingController::class, 'manualEntry'])->name('accounting.manual-entry');
        Route::resource('plan-comptable', ChartOfAccountController::class)->parameters(['plan-comptable' => 'planComptable'])->only(['index', 'store', 'update', 'destroy']);
        Route::get('rapports/balance.pdf', [ReportController::class, 'trialBalancePdf'])->name('reports.trial-balance.pdf');
        Route::get('rapports/balance.xlsx', [ReportController::class, 'trialBalanceExcel'])->name('reports.trial-balance.excel');
        Route::get('rapports/etats-ohada.pdf', [ReportController::class, 'financialStatementsPdf'])->name('reports.financial-statements.pdf');
        Route::get('rapports/etats-ohada.xlsx', [ReportController::class, 'financialStatementsExcel'])->name('reports.financial-statements.excel');
        Route::resource('services', ChurchServiceController::class)->only(['index', 'store']);
        Route::resource('groupes', MinistryGroupController::class)->only(['index', 'store']);
        Route::resource('evenements', ChurchEventController::class)->only(['index', 'store']);
        Route::resource('budgets', BudgetController::class)->only(['index', 'store']);
        Route::resource('depenses', ExpenseController::class)->only(['index', 'store']);
        foreach (['visiteurs', 'convertis', 'enfants', 'volontaires', 'formations', 'sermons-media', 'incidents'] as $module) {
            Route::get($module, [PastoralModuleController::class, 'index'])->defaults('module', $module)->name("pastoral.{$module}.index");
            Route::post($module, [PastoralModuleController::class, 'store'])->defaults('module', $module)->name("pastoral.{$module}.store");
        }
        Route::post('enfants/{child}/check-in', [PastoralModuleController::class, 'childCheckIn'])->name('children.check-in');
        Route::post('enfants/{child}/check-out', [PastoralModuleController::class, 'childCheckOut'])->name('children.check-out');
        foreach (['communications', 'demandes-service', 'reservations-locaux', 'patrimoine', 'conseils-reunions', 'promesses-dons', 'sondages', 'temoignages'] as $module) {
            Route::get($module, [EngagementAdminModuleController::class, 'index'])->defaults('module', $module)->name("operations.{$module}.index");
            Route::post($module, [EngagementAdminModuleController::class, 'store'])->defaults('module', $module)->name("operations.{$module}.store");
        }
        foreach (AdvancedChurchModuleController::modules() as $module) {
            Route::get($module, [AdvancedChurchModuleController::class, 'index'])->defaults('module', $module)->name("advanced.{$module}.index");
            Route::post($module, [AdvancedChurchModuleController::class, 'store'])->defaults('module', $module)->name("advanced.{$module}.store");
        }
        Route::post('fournisseurs/{vendorBill}/payer', [AdvancedChurchModuleController::class, 'payVendorBill'])->name('vendor-bills.pay');
        Route::post('paie/{payrollRun}/payer', [AdvancedChurchModuleController::class, 'payPayrollRun'])->name('payroll-runs.pay');
        Route::post('counseling/{counselingCase}/planifier', [AdvancedChurchModuleController::class, 'scheduleCounselingFollowUp'])->name('counseling.schedule');
        Route::post('counseling/{counselingCase}/cloturer', [AdvancedChurchModuleController::class, 'closeCounselingCase'])->name('counseling.close');
        Route::get('solutions', [SolutionModuleController::class, 'index'])->name('solutions.index');
        Route::patch('solutions/{solution}', [SolutionModuleController::class, 'update'])->name('solutions.update');
    });
});
