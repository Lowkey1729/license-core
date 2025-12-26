<?php

namespace App\Services;

use App\Enums\ActorTypeEnum;
use App\Enums\EventEnum;
use App\Exceptions\LicenseException;
use App\Helpers\LicenseKeyAESEncryption;
use App\Models\Activation;
use App\Models\LicenseKey;

readonly class ProductLicenseService
{
    public function __construct(
        private LicenseKeyAESEncryption $licenseKeyAES
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws LicenseException
     */
    public function activate(array $data): void
    {
        $licenseKey = $this->getLicenseKey($data);

        $license = $licenseKey->licenses->first();

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

        auditLog(
            event: EventEnum::Created,
            action: "New product activation: {$activation->id} created}",
            actorType: ActorTypeEnum::Product,
            objectType: Activation::class,
            objectId: $activation->id,
            metadata: collect($data)->except(['license_key'])->toArray()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws LicenseException
     */
    public function deactivate(array $data): void
    {
        $licenseKey = $this->getLicenseKey($data);

        $license = $licenseKey->licenses->first();

        $activation = Activation::query()
            ->where('license_id', $license->id)
            ->where('fingerprint', $data['fingerprint'])
            ->first();

        if (! $activation) {
            throw new LicenseException('Activation not found', 404);
        }

        $activation->delete();

        auditLog(
            event: EventEnum::Deleted,
            action: "Activation: {$activation->id} deleted}",
            actorType: ActorTypeEnum::Product,
            objectType: Activation::class,
            objectId: $activation->id,
            metadata: collect($data)->except(['license_key'])->toArray()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws LicenseException
     */
    public function checkStatus(array $data): array
    {
        $licenseKey = $this->getLicenseKey($data);

        $licenseKey->load(['licenses.product', 'licenses' => function ($query) {
            $query->withCount('activations');
        }]);

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
            }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws LicenseException
     */
    protected function getLicenseKey(array $data): LicenseKey
    {
        $data['license_key'] = str_replace('-', '', $data['license_key']);

        $licenseKey = LicenseKey::query()
            ->where('key', $this->licenseKeyAES->encrypt($data['license_key']))
            ->forProduct($data['product_slug'] ?? null)
            ->with([
                'licenses' => fn ($q) => $q->forProduct($data['product_slug'] ?? null),
                'licenses.product',
            ])
            ->when(isset($data['product_slug']), function ($query) use ($data) {
                $query->whereRelation('licenses.product', 'slug', $data['product_slug']);
            })
            ->first();

        if (! $licenseKey) {
            throw new LicenseException('Invalid license key or no license found for this product', 403);
        }

        return $licenseKey;
    }
}
