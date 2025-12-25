<?php

use App\Http\Controllers\LicenseController;
use App\Http\Middleware\BrandApiKeyAuth;

Route::prefix('v1')->group(function () {
    Route::prefix('brand')->middleware(BrandApiKeyAuth::class)->group(function () {
        Route::post('licenses', [LicenseController::class, 'store']);
        Route::put("licenses/{id}", [LicenseController::class, 'update']);
    });
});
