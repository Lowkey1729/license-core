<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActorTypeEnum;
use App\Enums\EventEnum;
use App\Exceptions\LicenseException;
use App\Helpers\LicenseKeyAESEncryption;
use App\Models\Activation;
use App\Models\LicenseKey;
use Cache;
use DB;

readonly class ProductLicenseService
{
    public function __construct(
        private readonly LicenseKeyAESEncryption $licenseKeyAES
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws LicenseException|\Throwable
     */
    public function activate(array $data): void
    {
        DB::transaction(function () use ($data) {
            $licenseKey = $this->getLicenseKey($data);

            $license = $licenseKey->licenses()
                ->lockForUpdate()
                ->first();

            if (! $license) {
                throw new LicenseException('No license found for this key', 404);
            }

            if (! $license->isValid()) {
                throw new LicenseException("License is {$license->status}", 403);
            }

            $existingActivation = Activation::query()
                ->where('license_id', $license->id)
                ->where('fingerprint', $data['fingerprint'])
                ->first();

            if ($existingActivation) {
                throw new LicenseException('This platform is already activated for this product', 403);
            }

            if ($license->activations()->count() >= $license->max_seats) {
                throw new LicenseException(
                    'You have reached the maximum number of activations for this license',
                    409
                );
            }

            $activation = $license->activations()->create([
                'fingerprint' => $data['fingerprint'],
                'platform_info' => $data['platform_info'] ?? null,
            ]);

            $cacheKey = $this->getCacheKey($data);
            Cache::forget($cacheKey);

            auditLog(
                event: EventEnum::Created,
                action: "New product activation: {$activation->id} created}",
                actorType: ActorTypeEnum::Product,
                objectType: Activation::class,
                objectId: $activation->id,
                metadata: collect($data)->except(['license_key'])->toArray()
            );

        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws LicenseException
     * @throws \Throwable
     */
    public function deactivate(array $data): void
    {
        DB::transaction(function () use ($data) {
            $licenseKey = $this->getLicenseKey($data);

            $license = $licenseKey->licenses()
                ->lockForUpdate()
                ->first();

            if (! $license) {
                throw new LicenseException('License not found', 404);
            }

            $activation = Activation::query()
                ->where('license_id', $license->id)
                ->where('fingerprint', $data['fingerprint'])
                ->first();

            if (! $activation) {
                throw new LicenseException('Activation not found', 404);
            }

            $activation->delete();

            $cacheKey = $this->getCacheKey($data);
            Cache::forget($cacheKey);

            auditLog(
                event: EventEnum::Deleted,
                action: "Activation: {$activation->id} deleted}",
                actorType: ActorTypeEnum::Product,
                objectType: Activation::class,
                objectId: $activation->id,
                metadata: collect($data)->except(['license_key'])->toArray()
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws LicenseException
     */
    public function checkStatus(array $data): array
    {
        $cacheKey = $this->getCacheKey($data);

        return Cache::remember($cacheKey, 1200, function () use ($data) {
            $licenseKey = $this->getLicenseKey(data: $data, eagerLoad: true);

            return [
                'customer' => $licenseKey->customer_email,
                'activations' => $licenseKey->licenses->map(function ($license) {
                    $seatsUsed = $license->activations_count;

                    return [
                        'product' => $license->product->name,
                        'slug' => $license->product->slug,
                        'is_valid' => $license->isValid(),
                        'status' => $license->status,
                        'expires_at' => $license->expires_at,
                        'max_seats' => $license->max_seats,
                        'seats_used' => $seatsUsed,
                        'seats_left' => $license->max_seats - $seatsUsed,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws LicenseException
     */
    protected function getLicenseKey(array $data, bool $eagerLoad = false): LicenseKey
    {
        // if a key like XXXX-XXXX-XXXX-XX is passed, normalize it to XXXXXXXXXXXXXX
        $data['license_key'] = str_replace('-', '', $data['license_key']);

        $query = LicenseKey::query()
            ->where('key', $this->licenseKeyAES->encrypt($data['license_key']))
            ->whereProduct($data['product_slug'] ?? null);

        if ($eagerLoad) {
            $query->with([
                'licenses' => fn ($q) => $q->withCount('activations')->whereProduct($data['product_slug'] ?? null),
                'licenses.product',
            ]);
        }

        $licenseKey = $query->first();

        if (! $licenseKey) {
            throw new LicenseException('Invalid license key or no license found for this product', 403);
        }

        return $licenseKey;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function getCacheKey(array $data): string
    {
        return sprintf(
            '%s:%s',
            'license_status',
            hash('sha256', $data['license_key']),
        );
    }
}
