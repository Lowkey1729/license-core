<?php

use App\Enums\LicenseActionEnum;
use App\Enums\LicenseStatusEnum;
use App\Helpers\BrandApiKeyAESEncryption;
use App\Models\Brand;
use App\Models\BrandApiKey;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Notifications\NewLicenseKeyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->brand = Brand::create([
        'name' => 'Test Brand',
        'slug' => 'test_brand',
    ]);

    $this->apiKey = 'test_api_key';

    BrandApiKey::create([
        'brand_id' => $this->brand->id,
        'api_key' => $this->apiKey,
    ]);


    $this->headers = ['X-BRAND-API-KEY' => $this->apiKey];

});

describe('Provision Licenses', function () {

    test('it provisions a license, sends email, and returns 201', function () {
        Notification::fake();

        Product::create([
            'brand_id' => $this->brand->id,
            'slug' => 'pro_pack',
            'name' => 'Pro Pack',
        ]);

        $payload = [
            'customer_email' => $email = 'customer1@gmail.com',
            'products' => [
                [
                    'product_slug' => 'pro_pack',
                    'max_seats' => 5,
                    'expires_at' => now()->addYear()->format("Y-m-d")
                ]
            ]
        ];

        $response = $this->postJson(route("brand.licenses.store"), $payload, $this->headers);

        $response->assertStatus(201);

        $data = $response->json('data');

        expect($data)
            ->toHaveKey('licenseKey')
            ->toHaveKey('customerEmail')
            ->toHaveKey('productNames');

        $this->assertDatabaseHas('license_keys', [
            'customer_email' => $email,
            'brand_id' => $this->brand->id
        ]);

        Notification::assertSentOnDemand(
            NewLicenseKeyNotification::class,
            fn($n, $ch, $notifiable) => $notifiable->routes['mail'] === $email
        );
    });

    test('it fails with 400 if attempting to provision too many products', function () {
        $products = array_fill(0, 11, ['product_slug' => 'dummy-slug']);

        $this->postJson(route("brand.licenses.store"), [
            'customer_email' => 'customer1@gmail.com',
            'products' => $products
        ], $this->headers)
            ->assertStatus(400)
            ->assertJson([
                'status' => 'failed',
                'message' => 'You are not allowed to provision licenses for more than 10 products at the same time'
            ]);
    });

    test('it fails with 404 if a product slug does not exist', function () {
        $this->postJson(route("brand.licenses.store"), [
            'customer_email' => 'typo@test.com',
            'products' => [['product_slug' => 'non-existent-product']]
        ], $this->headers)
            ->assertStatus(404);
    });
});

describe('Update License Lifecycle', function () {

    $createLicense = function (Brand $brand, string $status = LicenseStatusEnum::Active->value) {
        $key = LicenseKey::create([
            'brand_id' => $brand->id,
            'key' => 'key_' . uniqid(),
            'customer_email' => 'customer@example.com'
        ]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'slug' => 'prod_' . uniqid(),
            'name' => 'Product',
        ]);

        return License::create([
            'status' => $status,
            'expires_at' => now()->addYear(),
            'license_key_id' => $key->id,
            'product_id' => $product->id,
            'max_seats' => 5,
        ]);
    };

    test('it can suspend a license', function () use ($createLicense) {
        $license = $createLicense($this->brand);

        $this->patchJson(
            route("brand.licenses.update", ["id" => $license->id]),
            ['action' => LicenseActionEnum::Suspend->value],
            $this->headers
        )->assertStatus(200);

        expect($license->refresh()->status)->toBe(LicenseStatusEnum::Suspended->value);
    });

    test('it validates the action payload', function () use ($createLicense) {
        $license = $createLicense($this->brand);

        $this->patchJson(
            route("brand.licenses.update", ["id" => $license->id]),
            ['action' => 'invalid_action_verb'],
            $this->headers
        )->assertStatus(422);
    });

    test('it returns 404 when trying to update another brands license', function () use ($createLicense) {
        $otherBrand = Brand::create(['name' => 'Brand 2', 'slug' => 'brand_2']);

        $otherLicense = $createLicense($otherBrand);

        $this->patchJson(
            route("brand.licenses.update", ["id" => $otherLicense->id]),
            ['action' => LicenseActionEnum::Suspend->value],
            $this->headers
        )->assertStatus(404);
    });

    test('it renews a license with expiry date', function () use ($createLicense) {
        $license = $createLicense($this->brand);
        $newDate = now()->addYears(2)->toDateTimeString();

        $this->patchJson(
            route("brand.licenses.update", ["id" => $license->id]),
            [
                'action' => LicenseActionEnum::Renew->value,
                'expires_at' => $newDate
            ],
            $this->headers
        )->assertStatus(200);

        expect($license->refresh())
            ->status->toBe(LicenseStatusEnum::Active->value)
            ->expires_at->toDateTimeString()->toBe($newDate);
    });
});

describe('Fetch Licenses', function () {

    test('it returns a paginated list of licenses', function () {
        $key = LicenseKey::create([
            'brand_id' => $this->brand->id,
            'key' => 'test_key',
            'customer_email' => "customer@gmail.com"
        ]);

        $product = Product::create([
            'brand_id' => $this->brand->id,
            'slug' => 'pro_pack',
            'name' => 'Pro Pack',
        ]);

        License::create([
            'status' => LicenseStatusEnum::Active->value,
            'expires_at' => now()->addYear(),
            'license_key_id' => $key->id,
            'product_id' => $product->id,
            'max_seats' => 5,
        ]);

        $response = $this->getJson(route("brand.licenses.index"), $this->headers)
            ->assertStatus(200);

        $data = $response->json('data');

        expect($data)
            ->toHaveKey("data")
            ->toHaveKey("links")
            ->toHaveKey("prev_page_url")
            ->toHaveKey("next_page_url");
    });

    test('it filters by email', function () {

        $targetKey = LicenseKey::create([
            'brand_id' => $this->brand->id,
            'key' => 'target_key',
            'customer_email' => "target@gmail.com"
        ]);

        LicenseKey::create([
            'brand_id' => $this->brand->id,
            'key' => 'noise_key',
            'customer_email' => "other@gmail.com"
        ]);

        $product = Product::create([
            'brand_id' => $this->brand->id,
            'slug' => 'pro_pack',
            'name' => 'Pro Pack',
        ]);

        License::create([
            'status' => LicenseStatusEnum::Active->value,
            'license_key_id' => $targetKey->id,
            'product_id' => $product->id,
            'max_seats' => 1,
        ]);

        $response = $this->getJson(
            route("brand.licenses.index", ["email" => $targetKey->customer_email]),
            $this->headers
        );

        $response->assertStatus(200);

        $data = $response->json('data.data');

        expect($data)->toHaveCount(1)
            ->and($data[0])->toHaveKey('license_key.customer_email', $targetKey->customer_email);
    });
});
