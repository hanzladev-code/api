<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\RoutePermissionController;
use App\Http\Controllers\TrackOfferController;

// Protected routes
Route::group(['prefix' => 'crm'], function () {
    Route::middleware('auth:sanctum')->group(function () {
        // Routes management
        Route::get('/routes', [RouteController::class, 'index']);
        Route::get('/routes/tree', [RouteController::class, 'tree']);
        Route::get('/routes/permissions', [RouteController::class, 'permissions']);
        Route::get('/routes/{route}', [RouteController::class, 'show']);
        Route::post('/routes', [RouteController::class, 'store']);
        Route::put('/routes/{route}', [RouteController::class, 'update']);
        Route::delete('/routes/{route}', [RouteController::class, 'destroy']);
        Route::post('/routes/bulk-update', [RouteController::class, 'bulkUpdate']);

        // Roles management
        Route::apiResource('/roles', RoleController::class);

        // Permissions management
        Route::apiResource('/permissions', PermissionController::class);

        // Role-Permission management
        Route::get('/role-permissions', [RolePermissionController::class, 'index']);
        Route::get('/role-permissions/{id}', [RolePermissionController::class, 'show']);
        Route::post('/role-permissions', [RolePermissionController::class, 'store']);
        Route::put('/role-permissions/{id}', [RolePermissionController::class, 'update']);
        Route::delete('/role-permissions/{id}', [RolePermissionController::class, 'destroy']);
        Route::post('/role-permissions/assign-multiple', [RolePermissionController::class, 'assignMultiple']);
        Route::post('/role-permissions/sync', [RolePermissionController::class, 'syncPermissions']);

        // User-Permission management
        Route::get('/user-permissions', [UserPermissionController::class, 'index']);
        Route::get('/user-permissions/{id}', [UserPermissionController::class, 'show']);
        Route::post('/user-permissions', [UserPermissionController::class, 'store']);
        Route::put('/user-permissions/{id}', [UserPermissionController::class, 'update']);
        Route::delete('/user-permissions/{id}', [UserPermissionController::class, 'destroy']);
        Route::post('/user-permissions/assign-multiple', [UserPermissionController::class, 'assignMultiple']);
        Route::post('/user-permissions/sync', [UserPermissionController::class, 'syncPermissions']);
        Route::get('/users/{userId}/permissions', [UserPermissionController::class, 'getUserPermissions']);

        // Route-Permission management
        Route::get('/route-permissions', [RoutePermissionController::class, 'index']);
        Route::get('/route-permissions/tree', [RoutePermissionController::class, 'getRouteTree']);
        Route::get('/route-permissions/available', [RoutePermissionController::class, 'getAvailablePermissions']);
        Route::put('/route-permissions/{routeId}', [RoutePermissionController::class, 'updateRoutePermission']);
        Route::post('/route-permissions/batch', [RoutePermissionController::class, 'batchUpdatePermissions']);

        // Offer tracking and statistics (authenticated)
        Route::get('/offers/{offerId}/stats', [TrackOfferController::class, 'offerStats']);
        Route::get('/clicks', [TrackOfferController::class, 'index']);
        Route::get('/clicks/{clickId}', [TrackOfferController::class, 'show']);

        Route::get('/permissions/verify/{permission}', [PermissionController::class, 'verifyPermission']);
    });
});
