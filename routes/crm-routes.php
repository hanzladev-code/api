<?php

use App\Http\Controllers\CRM\Authenticated\DomainsController;
use App\Http\Controllers\CRM\Authenticated\NetworksController;
use App\Http\Controllers\CRM\Authenticated\OfferController;
use App\Http\Controllers\CRM\Authenticated\StaffController;
use App\Http\Controllers\CRM\Authenticated\TrackersController;
use App\Http\Controllers\CRM\AUTHENTICATION\AuthenticatedSessionController;
use App\Http\Controllers\CRM\AUTHENTICATION\RegisterController;
use App\Http\Controllers\OptionsController;
use App\Http\Controllers\UtmSourcesController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'crm'], function () {
    Route::group(['middleware' => ['guest']], function () {
        Route::post('/login', [AuthenticatedSessionController::class, 'login']);
        Route::post('/check-credentials', [AuthenticatedSessionController::class, 'checkCredentials']);
        Route::post('/register', [RegisterController::class, 'store']);
        Route::get('/options/{key}', [OptionsController::class, 'show']);
    });

    Route::group(['middleware' => ['auth:sanctum']], function () {
        // Routes accessible to all authenticated users
        Route::get('/me', [AuthenticatedSessionController::class, 'me']);
        Route::get('/sidebar', [AuthenticatedSessionController::class, 'sidebar']);
        Route::post('/logout', [AuthenticatedSessionController::class, 'logout']);
        Route::get('/authenticated', function () {
            return 'Hello World authenticated';
        });

        // Routes that require specific permissions
        Route::apiResource('/options', OptionsController::class)->except(['show']);

        Route::apiResource('/domains', DomainsController::class);

        Route::apiResource('/trackers', TrackersController::class);

        Route::apiResource('/networks', NetworksController::class);

        Route::apiResource('/users', StaffController::class);

        Route::apiResource('/offers', OfferController::class);

        Route::apiResource('/utm-sources', UtmSourcesController::class);
    });
});
