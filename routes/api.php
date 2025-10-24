<?php

use App\Http\Controllers\FilesController;
use App\Http\Controllers\TrackOfferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('/files', FilesController::class);


Route::get('/get-meta-data', [TrackOfferController::class, 'getMetaData']); 
Route::group(['middleware' => ['guest']], function () {
    // Tracking and conversion endpoints (no authentication required)
    Route::get('/track-offer', [TrackOfferController::class, 'store']);
    Route::post('/conversion', [TrackOfferController::class, 'conversion']);
});

include 'crm-routes.php';
include 'routes.php';
