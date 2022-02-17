<?php

use App\Http\Controllers\V1\TagController;
use App\Http\Controllers\V1\OfficeController;
use App\Http\Controllers\V1\OfficeImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function() {
    // Tags...
    Route::get('/tags', TagController::class)->name('tags.index');

    // Offices...
    Route::apiResource('offices', OfficeController::class);

    // OfficeImages
    Route::apiResource('offices.images', OfficeImageController::class)->only(['store', 'destroy']);
});
