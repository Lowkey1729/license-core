<?php

use App\Http\Controllers\BrandLicenseController;
use App\Http\Controllers\ProductLicenseController;
use App\Http\Middleware\BrandApiKeyAuth;

Route::prefix('v1')->group(function () {
    Route::prefix('brand')->middleware(BrandApiKeyAuth::class)->group(function () {
        Route::get('licenses', [BrandLicenseController::class, 'index'])
            ->name('brand.licenses.index');
        Route::post('licenses', [BrandLicenseController::class, 'store'])
            ->name('brand.licenses.store');
        Route::patch('licenses/{id}', [BrandLicenseController::class, 'update'])
            ->name('brand.licenses.update');
    });

    Route::prefix('product/licenses')->group(function () {
        Route::post('activate', [ProductLicenseController::class, 'activate'])
            ->name('product.licenses.activate');
        Route::post('deactivate', [ProductLicenseController::class, 'deactivate'])
            ->name('product.licenses.deactivate');
        Route::get('check', [ProductLicenseController::class, 'check'])
            ->name('product.licenses.deactivate');
    });
});
