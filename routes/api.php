<?php

use App\Http\Controllers\BrandLicenseController;
use App\Http\Controllers\ProductLicenseController;
use App\Http\Middleware\BrandApiKeyAuth;
use Illuminate\Support\Facades\Redis;

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
            ->middleware('throttle:2,1')
            ->name('product.licenses.activate');
        Route::post('deactivate', [ProductLicenseController::class, 'deactivate'])
            ->middleware('throttle:2,1')
            ->name('product.licenses.deactivate');
        Route::get('check', [ProductLicenseController::class, 'check'])
            ->name('product.licenses.check');
    });
});

Route::get('health', function () {

    try {
        DB::connection('mysql')->getPdo();
        $mysqlDBStatus = 'ok';
    } catch (\Exception $e) {
        $mysqlDBStatus = 'failed';
    }

    try {
        DB::connection('mysql')->getPdo();
        $mongoDBStatus = 'ok';
    } catch (\Exception $e) {
        $mongoDBStatus = 'failed';
    }

    try {
        Redis::ping();
        $redisStatus = 'ok';
    } catch (\Exception $e) {
        $redisStatus = 'failed';
    }

    $status = ($mysqlDBStatus === 'ok' && $mongoDBStatus === 'ok' && $redisStatus === 'ok') ? 200 : 500;

    return response()->json([
        'status' => $status === 200 ? 'healthy' : 'unhealthy',
        'services' => [
            'mysqlDB' => $mysqlDBStatus,
            'mongoDB' => $mongoDBStatus,
            'redis' => $redisStatus,
        ],
        'timestamp' => now()->toIso8601String(),
    ], $status);
});
