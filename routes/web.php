<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AdvancedChurchModuleController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\OtpChallengeController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\ChurchController;
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
use App\Http\Controllers\PublicContributionController;
use App\Http\Controllers\PublicFlowController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SolutionModuleController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WorkspaceContextController;
use App\Support\Rbac;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('otp', [OtpChallengeController::class, 'create'])->name('otp.create');
    Route::post('otp', [OtpChallengeController::class, 'store'])->middleware('throttle:otp')->name('otp.store');
});

Route::get('public/eglises/{church}/don', [PublicFlowController::class, 'donation'])->name('public.donation');
Route::get('public/eglises/{church}/visiteur', [PublicFlowController::class, 'visitor'])->name('public.visitor');
Route::get('public/evenements/{event}', [PublicFlowController::class, 'event'])->name('public.event');
// SEC-20 : formulaires publics non authentifies -> limitation de debit.
Route::middleware('throttle:public-form')->group(function () {
    Route::post('public/eglises/{church}/don', [PublicFlowController::class, 'storeDonation'])->name('public.donation.store');
    Route::post('public/eglises/{church}/visiteur', [PublicFlowController::class, 'storeVisitor'])->name('public.visitor.store');
    Route::post('public/evenements/{event}', [PublicFlowController::class, 'storeEvent'])->name('public.event.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('workspace/switch', [WorkspaceContextController::class, 'update'])->name('workspace.switch');

    // Le tableau de bord (KPI scopes par perimetre) reste accessible a tout
    // utilisateur authentifie ; le detail affiche depend du role.
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware('workspace:communaute')->group(function () {
        Route::middleware('permission:'.Rbac::PLATFORM_TENANTS)->group(function () {
            Route::resource('communautes', CommunityController::class)->only(['index', 'store']);
        });
        Route::middleware('permission:'.Rbac::CHURCHES_MANAGE)->group(function () {
            Route::resource('eglises', ChurchController::class)->only(['index', 'store']);
        });
        Route::middleware('permission:'.Rbac::USERS_MANAGE)->group(function () {
            Route::resource('utilisateurs', UserManagementController::class)->only(['index', 'store']);
        });
        Route::middleware('permission:'.Rbac::ROLES_MANAGE)->group(function () {
            Route::get('roles-permissions', [RolePermissionController::class, 'index'])->name('roles-permissions.index');
            Route::post('roles-permissions/roles', [RolePermissionController::class, 'storeRole'])->name('roles-permissions.roles.store');
            Route::post('roles-permissions/permissions', [RolePermissionController::class, 'storePermission'])->name('roles-permissions.permissions.store');
            Route::put('roles-permissions/roles/{role}/permissions', [RolePermissionController::class, 'syncRolePermissions'])->name('roles-permissions.roles.permissions');
        });
        Route::middleware('permission:'.Rbac::AUDIT_VIEW)->group(function () {
            Route::get('journal-audit', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });
    });

    Route::middleware('workspace:eglise')->group(function () {
        Route::post('offline/sync', [OfflineSyncController::class, 'store'])
            ->middleware('permission:'.Rbac::OFFLINE_SYNC)
            ->name('offline.sync');

        Route::middleware('permission:'.Rbac::MEMBERS_MANAGE)->group(function () {
            Route::resource('membres', MemberController::class)->only(['index', 'store']);
            Route::patch('membres/{member}/statut', [MemberController::class, 'promote'])->name('membres.promote');
        });

        Route::get('comptabilite', [AccountingController::class, 'index'])
            ->middleware('permission:'.Rbac::REPORTS_VIEW)
            ->name('accounting.index');
        Route::post('comptabilite/collectes', [AccountingController::class, 'collection'])
            ->middleware('permission:'.Rbac::CONTRIBUTIONS_RECORD)
            ->name('accounting.collection');
        Route::post('comptabilite/ecritures', [AccountingController::class, 'manualEntry'])
            ->middleware('permission:'.Rbac::ACCOUNTING_POST)
            ->name('accounting.manual-entry');

        // SEC-27 : file d'attente des contributions publiques a valider.
        Route::middleware('permission:'.Rbac::CONTRIBUTIONS_RECORD)->group(function () {
            Route::get('contributions-publiques', [PublicContributionController::class, 'index'])->name('public-contributions.index');
            Route::post('contributions-publiques/{publicContribution}/valider', [PublicContributionController::class, 'approve'])->name('public-contributions.approve');
            Route::post('contributions-publiques/{publicContribution}/rejeter', [PublicContributionController::class, 'reject'])->name('public-contributions.reject');
        });

        Route::get('plan-comptable', [ChartOfAccountController::class, 'index'])
            ->middleware('permission:'.Rbac::REPORTS_VIEW)
            ->name('plan-comptable.index');
        Route::middleware('permission:'.Rbac::PLATFORM_CHART_OF_ACCOUNTS)->group(function () {
            Route::post('plan-comptable', [ChartOfAccountController::class, 'store'])->name('plan-comptable.store');
            Route::put('plan-comptable/{planComptable}', [ChartOfAccountController::class, 'update'])->name('plan-comptable.update');
            Route::delete('plan-comptable/{planComptable}', [ChartOfAccountController::class, 'destroy'])->name('plan-comptable.destroy');
        });

        Route::middleware('permission:'.Rbac::REPORTS_VIEW)->group(function () {
            Route::get('rapports/balance.pdf', [ReportController::class, 'trialBalancePdf'])->name('reports.trial-balance.pdf');
            Route::get('rapports/balance.xlsx', [ReportController::class, 'trialBalanceExcel'])->name('reports.trial-balance.excel');
            Route::get('rapports/etats-ohada.pdf', [ReportController::class, 'financialStatementsPdf'])->name('reports.financial-statements.pdf');
            Route::get('rapports/etats-ohada.xlsx', [ReportController::class, 'financialStatementsExcel'])->name('reports.financial-statements.excel');
        });

        Route::middleware('permission:'.Rbac::SERVICES_MANAGE)->group(function () {
            Route::resource('services', ChurchServiceController::class)->only(['index', 'store']);
            Route::resource('groupes', MinistryGroupController::class)->only(['index', 'store']);
            Route::resource('evenements', ChurchEventController::class)->only(['index', 'store']);
        });

        Route::get('budgets', [BudgetController::class, 'index'])
            ->middleware('permission:'.Rbac::BUDGET_MONITOR)
            ->name('budgets.index');
        Route::post('budgets', [BudgetController::class, 'store'])
            ->middleware('permission:'.Rbac::BUDGET_CONFIGURE)
            ->name('budgets.store');

        Route::get('depenses', [ExpenseController::class, 'index'])
            ->middleware('permission:'.Rbac::BUDGET_MONITOR)
            ->name('depenses.index');
        Route::post('depenses', [ExpenseController::class, 'store'])
            ->middleware('permission:'.Rbac::REQUISITION_CREATE)
            ->name('depenses.store');

        Route::middleware('permission:'.Rbac::MEMBERS_MANAGE)->group(function () {
            foreach (['visiteurs', 'convertis', 'enfants', 'volontaires', 'formations', 'sermons-media', 'incidents'] as $module) {
                Route::get($module, [PastoralModuleController::class, 'index'])->defaults('module', $module)->name("pastoral.{$module}.index");
                Route::post($module, [PastoralModuleController::class, 'store'])->defaults('module', $module)->name("pastoral.{$module}.store");
            }
            Route::post('enfants/{child}/check-in', [PastoralModuleController::class, 'childCheckIn'])->name('children.check-in');
            Route::post('enfants/{child}/check-out', [PastoralModuleController::class, 'childCheckOut'])->name('children.check-out');
        });

        Route::middleware('permission:'.Rbac::SERVICES_MANAGE)->group(function () {
            foreach (['communications', 'demandes-service', 'reservations-locaux', 'patrimoine', 'conseils-reunions', 'promesses-dons', 'sondages', 'temoignages'] as $module) {
                Route::get($module, [EngagementAdminModuleController::class, 'index'])->defaults('module', $module)->name("operations.{$module}.index");
                Route::post($module, [EngagementAdminModuleController::class, 'store'])->defaults('module', $module)->name("operations.{$module}.store");
            }
        });

        foreach (AdvancedChurchModuleController::modules() as $module) {
            $permission = AdvancedChurchModuleController::permissionFor($module);
            Route::get($module, [AdvancedChurchModuleController::class, 'index'])
                ->defaults('module', $module)
                ->middleware('permission:'.$permission)
                ->name("advanced.{$module}.index");
            Route::post($module, [AdvancedChurchModuleController::class, 'store'])
                ->defaults('module', $module)
                ->middleware('permission:'.$permission)
                ->name("advanced.{$module}.store");
        }
        Route::post('fournisseurs/{vendorBill}/payer', [AdvancedChurchModuleController::class, 'payVendorBill'])
            ->middleware('permission:'.Rbac::ACCOUNTING_POST)
            ->name('vendor-bills.pay');
        Route::post('paie/{payrollRun}/payer', [AdvancedChurchModuleController::class, 'payPayrollRun'])
            ->middleware('permission:'.Rbac::ACCOUNTING_POST)
            ->name('payroll-runs.pay');
        Route::post('counseling/{counselingCase}/planifier', [AdvancedChurchModuleController::class, 'scheduleCounselingFollowUp'])
            ->middleware('permission:'.Rbac::MEMBERS_MANAGE)
            ->name('counseling.schedule');
        Route::post('counseling/{counselingCase}/cloturer', [AdvancedChurchModuleController::class, 'closeCounselingCase'])
            ->middleware('permission:'.Rbac::MEMBERS_MANAGE)
            ->name('counseling.close');

        Route::get('solutions', [SolutionModuleController::class, 'index'])->name('solutions.index');
        Route::patch('solutions/{solution}', [SolutionModuleController::class, 'update'])
            ->middleware('permission:'.Rbac::ROLES_MANAGE)
            ->name('solutions.update');
    });
});
