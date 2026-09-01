<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ChurchCentralAdvancedModuleApiController;
use App\Http\Controllers\Api\ChurchCentralApiController;
use App\Http\Controllers\Api\ChurchCentralCrudApiController;
use App\Http\Controllers\Api\ChurchCentralGenericModuleApiController;
use App\Http\Controllers\Api\MediaUploadController;
use App\Http\Controllers\OfflineSyncController;
use App\Support\Rbac;
use Illuminate\Support\Facades\Route;

Route::post('auth/token', [ApiAuthController::class, 'token']);

Route::middleware('auth:sanctum')->group(function () {
    // Contexte utilisateur et referentiels : lecture ouverte a tout jeton.
    Route::get('me', [ChurchCentralApiController::class, 'me']);
    Route::get('churches', [ChurchCentralApiController::class, 'churches']);
    Route::get('solutions', [ChurchCentralApiController::class, 'solutions']);
    Route::get('media/offline-manifest', [ChurchCentralApiController::class, 'offlineMediaManifest']);

    Route::middleware('permission:'.Rbac::MEMBERS_MANAGE)->group(function () {
        Route::get('members', [ChurchCentralApiController::class, 'members']);
        Route::post('members', [ChurchCentralCrudApiController::class, 'storeMember']);
        Route::put('members/{member}', [ChurchCentralCrudApiController::class, 'updateMember']);
    });

    Route::middleware('permission:'.Rbac::SERVICES_MANAGE)->group(function () {
        Route::post('services', [ChurchCentralCrudApiController::class, 'storeService']);
        Route::put('services/{service}', [ChurchCentralCrudApiController::class, 'updateService']);
        Route::post('groups', [ChurchCentralCrudApiController::class, 'storeGroup']);
        Route::put('groups/{group}', [ChurchCentralCrudApiController::class, 'updateGroup']);
        Route::post('events', [ChurchCentralCrudApiController::class, 'storeEvent']);
        Route::put('events/{event}', [ChurchCentralCrudApiController::class, 'updateEvent']);
    });

    Route::middleware('permission:'.Rbac::BUDGET_CONFIGURE)->group(function () {
        Route::post('budgets', [ChurchCentralCrudApiController::class, 'storeBudget']);
        Route::put('budgets/{budget}', [ChurchCentralCrudApiController::class, 'updateBudget']);
    });

    Route::middleware('permission:'.Rbac::REQUISITION_CREATE)->group(function () {
        Route::post('expenses', [ChurchCentralCrudApiController::class, 'storeExpense']);
        Route::put('expenses/{expense}', [ChurchCentralCrudApiController::class, 'updateExpense']);
    });

    // Modules pastoraux / administratifs : Secretaire (members.manage + services.manage).
    Route::middleware('permission:'.Rbac::MEMBERS_MANAGE.'|'.Rbac::SERVICES_MANAGE)->group(function () {
        Route::get('{family}/{module}', [ChurchCentralGenericModuleApiController::class, 'index'])
            ->whereIn('family', ['pastoral', 'administration']);
        Route::post('{family}/{module}', [ChurchCentralGenericModuleApiController::class, 'store'])
            ->whereIn('family', ['pastoral', 'administration']);
        Route::put('{family}/{module}/{id}', [ChurchCentralGenericModuleApiController::class, 'update'])
            ->whereIn('family', ['pastoral', 'administration']);
    });

    // Modules avances : garde large au niveau route, permission fine par module
    // appliquee dans le controleur (AdvancedChurchModuleController::permissionFor).
    Route::middleware('permission:'.Rbac::ACCOUNTING_POST.'|'.Rbac::BANK_RECONCILE.'|'.Rbac::SERVICES_MANAGE)->group(function () {
        Route::get('advanced/{module}', [ChurchCentralAdvancedModuleApiController::class, 'index']);
        Route::post('advanced/{module}', [ChurchCentralAdvancedModuleApiController::class, 'store']);
        Route::put('advanced/{module}/{id}', [ChurchCentralAdvancedModuleApiController::class, 'update']);
    });

    Route::get('accounting/entries', [ChurchCentralApiController::class, 'journalEntries'])
        ->middleware('permission:'.Rbac::REPORTS_VIEW);

    Route::middleware('permission:'.Rbac::SERVICES_MANAGE)->group(function () {
        Route::post('media/uploads', [MediaUploadController::class, 'initiate']);
        Route::get('media/uploads/{upload}', [MediaUploadController::class, 'show']);
        Route::post('media/uploads/{upload}/chunks', [MediaUploadController::class, 'chunk']);
        Route::post('media/uploads/{upload}/complete', [MediaUploadController::class, 'complete']);
    });

    Route::post('offline/sync', [OfflineSyncController::class, 'store'])
        ->middleware('permission:'.Rbac::OFFLINE_SYNC);

    Route::post('auth/logout', [ApiAuthController::class, 'logout']);
});
