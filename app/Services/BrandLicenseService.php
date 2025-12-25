<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActorTypeEnum;
use App\Enums\EventEnum;
use App\Enums\LicenseActionEnum;
use App\Enums\LicenseStatusEnum;
use App\Exceptions\InvalidLicenseActionException;
use App\Helpers\LicenseKeyGenerator;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

readonly class BrandLicenseService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws \Throwable
     */
    public function provision(Brand $brand, array $data): void
    {
        $product = Product::query()
            ->where('brand_id', $brand->id)
            ->where('slug', $data['product_slug'])
            ->firstOrFail();

        DB::transaction(function () use ($brand, $data, $product) {

            $licenseKey = LicenseKey::query()->firstOrCreate([
                'customer_email' => $data['customer_email'],
                'brand_id' => $brand->id,
            ],
                [
                    'key' => (new LicenseKeyGenerator)->handle(),
                ]);

            $license = License::query()->firstOrCreate([
                'license_key_id' => $licenseKey->id,
                'product_id' => $product->id,
            ],
                [
                    'status' => LicenseStatusEnum::Active->value,
                    'expires_at' => $data['expires_at'],
                    'max_seats' => $data['max_seats'],
                ]);

            auditLog(
                event: EventEnum::Created,
                action: "License {$license->id} created",
                actorType: ActorTypeEnum::Brand,
                actorId: $brand->id,
                objectType: License::class,
                objectId: $license->id,
                dispatchAfterCommit: true,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
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
            LicenseActionEnum::Renew->value => function (License $license, array $data) {
                $license->expires_at = $data['expires_at'];
                $license->status = LicenseStatusEnum::Active->value;
            },
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
                $query->whereRelation('licenseKey', 'email', $data['email']);
            })
            ->paginate();
    }
}
