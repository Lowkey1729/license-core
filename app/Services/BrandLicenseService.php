<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActorTypeEnum;
use App\Enums\EventEnum;
use App\Enums\LicenseActionEnum;
use App\Enums\LicenseStatusEnum;
use App\Exceptions\InvalidLicenseActionException;
use App\Exceptions\ProvisionLicenseException;
use App\Helpers\LicenseKeyGenerator;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Notifications\NewLicenseKeyNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Notification;

readonly class BrandLicenseService
{
    private const int MAX_ALLOWABLE_PRODUCTS = 10;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws \Throwable
     */
    public function provision(Brand $brand, array $data): array
    {
        if (count($data['products']) > self::MAX_ALLOWABLE_PRODUCTS) {
            throw new ProvisionLicenseException(
                sprintf(
                    'You are not allowed to provision licenses for more than %s products at the same time',
                    self::MAX_ALLOWABLE_PRODUCTS
                )
            );
        }

        $licenseKey = LicenseKey::query()->firstOrCreate([
            'customer_email' => $data['customer_email'],
            'brand_id' => $brand->id,
        ],
            [
                'key' => (new LicenseKeyGenerator)->handle(),
            ]);

        $productNames = $this->createLicense($brand, $licenseKey, $data);

        Notification::route('mail', $data['customer_email'])
            ->notify(new NewLicenseKeyNotification(
                licenseKey: $licenseKey->key,
                customerEmail: $data['customer_email'],
                productNames: $productNames,
                brandName: $brand->name,
            ));

        return [
            'licenseKey' => formatKey($licenseKey->key),
            'customerEmail' => $licenseKey->customer_email,
            'productNames' => $productNames,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     *
     * @throws \Throwable
     */
    private function createLicense(Brand $brand, LicenseKey $licenseKey, array $data): array
    {
        $productNames = [];

        DB::transaction(function () use ($brand, $data, $licenseKey, &$productNames) {
            $products = $data['products'];
            foreach ($products as $_product) {

                $product = Product::query()
                    ->where('brand_id', $brand->id)
                    ->where('slug', $_product['product_slug'])
                    ->first();

                if (! $product) {
                    throw new ProvisionLicenseException(
                        "No product found with slug '{$_product['product_slug']}'",
                        404
                    );
                }

                $productNames[] = $product->name;

                License::query()->updateOrCreate([
                    'license_key_id' => $licenseKey->id,
                    'product_id' => $product->id,
                ],
                    [
                        'status' => LicenseStatusEnum::Active->value,
                        'expires_at' => $_product['expires_at'] ?? null,
                        'max_seats' => $_product['max_seats'] ?? 1,
                    ]);

            }

            auditLog(
                event: EventEnum::Created,
                action: "LicenseKey({$licenseKey->id}) provisioned to Customer: {$data['customer_email']}",
                actorType: ActorTypeEnum::Brand,
                actorId: $brand->id,
                objectType: LicenseKey::class,
                objectId: $licenseKey->id,
                metadata: $data,
                dispatchAfterCommit: true,
            );
        });

        return $productNames;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidLicenseActionException
     */
    public function updateLicenseStatus(Brand $brand, string $id, array $data): void
    {
        $license = License::query()
            ->whereRelation('licenseKey', 'brand_id', $brand->id)
            ->where('id', $id)
            ->firstOrFail();

        match ($data['action']) {
            LicenseActionEnum::Suspend->value => $license->status = LicenseStatusEnum::Suspended->value,
            LicenseActionEnum::Resume->value => $license->status = LicenseStatusEnum::Active->value,
            LicenseActionEnum::Cancel->value => $license->status = LicenseStatusEnum::Cancelled->value,
            LicenseActionEnum::Renew->value => (function () use ($license, $data) {
                $license->expires_at = $data['expires_at'];
                $license->status = LicenseStatusEnum::Active->value;
            })(),
            default => throw new InvalidLicenseActionException("License action '{$data['action']}' is not supported."),
        };

        $license->save();

        auditLog(
            event: EventEnum::Updated,
            action: "License {$license->id} updated",
            actorType: ActorTypeEnum::Brand,
            actorId: $brand->id,
            objectType: License::class,
            objectId: $license->id,
            metadata: $data
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return LengthAwarePaginator<int, License>
     */
    public function fetchLicenses(Brand $brand, array $data): LengthAwarePaginator
    {
        return License::query()
            ->whereRelation('licenseKey', 'brand_id', $brand->id)
            ->when(isset($data['email']), function (Builder $query) use ($data) {
                $query->whereRelation('licenseKey', 'customer_email', $data['email']);
            })
            ->paginate();
    }
}
