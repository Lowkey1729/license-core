<?php

namespace App\Providers;

use App\Helpers\BrandApiKeyAESEncryption;
use App\Helpers\LicenseKeyAESEncryption;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BrandApiKeyAESEncryption::class, function () {
            return new BrandApiKeyAESEncryption(
                config('brand.aes.secret'),
                config('brand.aes.iv'),
            );
        });

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
