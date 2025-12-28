<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health/ready', function () {
    try {
        DB::connection()->getPdo();
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'not_ready',
        ], 503);
    }

    return response()->json([
        'status' => 'ready',
    ]);
});

Route::get('/health/dependencies', function () {
    $checks = [];

    $checks['mysql'] = rescue(fn () => DB::connection('mysql')->getPdo(), false) ? 'ok' : 'failed';
    $checks['mongo'] = rescue(fn () => DB::connection('mongodb')->table('audit_log')->limit(1)->get(), false) ? 'ok' : 'failed';
    $checks['redis'] = rescue(fn () => Redis::ping(), false) ? 'ok' : 'failed';

    return response()->json([
        'status' => in_array('failed', $checks, true) ? 'degraded' : 'healthy',
        'services' => $checks,
        'timestamp' => now()->toIso8601String(),
    ]);
});
