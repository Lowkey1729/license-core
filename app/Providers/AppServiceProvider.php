<?php

namespace App\Providers;

use App\Helpers\LicenseKeyAESEncryption;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LicenseKeyAESEncryption::class, function () {
            return new LicenseKeyAESEncryption(
                config('licenses.aes.secret'),
                config('licenses.aes.iv'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
