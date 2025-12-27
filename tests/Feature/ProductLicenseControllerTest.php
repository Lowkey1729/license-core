<?php

use App\Enums\EventEnum;
use App\Enums\LicenseStatusEnum;
use App\Models\Activation;
use App\Models\Brand;
use App\Models\License;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->brand = Brand::create([
        'name' => 'Test Brand',
        'slug' => 'test_brand',
    ]);

    $this->product = Product::create([
        'brand_id' => $this->brand->id,
        'slug' => $this->product_slug = 'pro_pack',
        'name' => 'Pro Pack',
    ]);

    $this->rawKey = 'LICENSE123KEY';

    $this->licenseKey = LicenseKey::create([
        'brand_id' => $this->brand->id,
        'key' => $this->rawKey,
        'customer_email' => $this->customerEmail = 'customer@gmail.com',
    ]);
});

describe('License Activation', function () {

    test('it successfully activates a license', function () {

        License::query()->create([
            'license_key_id' => $this->licenseKey->id,
            'product_id' => $this->product->id,
            'status' => LicenseStatusEnum::Active->value,
            'max_seats' => 5,
            'expires_at' => now()->addYear(),
        ]);

        $payload = [
            'license_key' => $this->rawKey,
            'product_slug' => $this->product_slug,
            'fingerprint' => $fingerprint = 'device-hwid-001',
            'platform_info' => $platformInfo = 'Windows 11',
        ];

        $response = $this->postJson(route('product.licenses.activate'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activations', [
            'fingerprint' => $fingerprint,
            'platform_info' => $platformInfo,
        ]);

        $this->assertDatabaseHas('audit_logs', ['event' => EventEnum::Created->value], 'mongodb');
    });

    test('it fails with 403 if the license is suspended', function () {
        License::query()->create([
            'license_key_id' => $this->licenseKey->id,
            'product_id' => $this->product->id,
            'status' => LicenseStatusEnum::Suspended->value,
        ]);

        $response = $this->postJson(route('product.licenses.activate'), [
            'license_key' => $this->rawKey,
            'product_slug' => $this->product_slug,
            'fingerprint' => 'device-hwid-001',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'License is suspended']);
    });

    test('it fails with 403 if the device is already activated', function () {

        $license = License::query()->create([
            'license_key_id' => $this->licenseKey->id,
            'product_id' => $this->product->id,
            'status' => LicenseStatusEnum::Active->value,
        ]);

        Activation::query()->create([
            'license_id' => $license->id,
            'fingerprint' => $fingerprint = 'duplicate-hwid',
        ]);

        $response = $this->postJson(route('product.licenses.activate'), [
            'license_key' => $this->rawKey,
            'product_slug' => $this->product_slug,
            'fingerprint' => $fingerprint,
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'This platform is already activated for this product']);
    });

    test('it fails with 409 if max seats are reached', function () {
        $license = License::query()->create([
            'license_key_id' => $this->licenseKey->id,
            'product_id' => $this->product->id,
            'status' => LicenseStatusEnum::Active->value,
            'max_seats' => 2,
        ]);

        Activation::query()->insert([
            [
                'license_id' => $license->id,
                'fingerprint' => 'pixel1',
                'id' => newUniqueId(),
            ],
            [
                'license_id' => $license->id,
                'fingerprint' => 'pixel2',
                'id' => newUniqueId(),
            ],
        ]);

        $response = $this->postJson(route('product.licenses.activate'), [
            'license_key' => $this->rawKey,
            'product_slug' => $this->product_slug,
            'fingerprint' => 'pixel3',
        ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'You have reached the maximum number of activations for this license']);
    });

    test('it fails with 403 if license key is invalid or product mismatch', function () {
        License::query()->create([
            'license_key_id' => $this->licenseKey->id,
            'product_id' => $this->product->id,
            'status' => LicenseStatusEnum::Active->value,
        ]);

        $response = $this->postJson(route('product.licenses.activate'), [
            'license_key' => $this->rawKey,
            'product_slug' => 'other-tool', // Mismatch
            'fingerprint' => 'hwid-1',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Invalid license key or no license found for this product']);
    });
});

describe('License Deactivation', function () {

    test('it successfully deactivates a device', function () {
        $license = License::query()->create([
            'license_key_id' => $this->licenseKey->id,
            'product_id' => $this->product->id,
            'status' => LicenseStatusEnum::Active->value,
        ]);

        $activation = Activation::query()->create([
            'license_id' => $license->id,
            'fingerprint' => $fingerprint = 'device-to-remove',
        ]);

        $response = $this->postJson(route('product.licenses.deactivate'), [
            'license_key' => $this->rawKey,
            'product_slug' => $this->product_slug,
            'fingerprint' => $fingerprint,
        ]);

        $response->assertStatus(200);

        $this->assertSoftDeleted('activations', ['id' => $activation->id]);
    });

    test('it fails with 404 if activation not found', function () {
        License::query()->create([
            'license_key_id' => $this->licenseKey->id,
            'product_id' => $this->product->id,
            'status' => LicenseStatusEnum::Active->value,
        ]);

        $response = $this->postJson(route('product.licenses.deactivate'), [
            'license_key' => $this->rawKey,
            'product_slug' => $this->product_slug,
            'fingerprint' => 'ghost-device',
        ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Activation not found']);
    });
});

describe('Check License Status', function () {

    test('it returns correct license details and seat counts', function () {
        $license = License::query()->create([
            'license_key_id' => $this->licenseKey->id,
            'product_id' => $this->product->id,
            'status' => LicenseStatusEnum::Active->value,
            'max_seats' => 2,
        ]);

        Activation::query()->insert([
            [
                'license_id' => $license->id,
                'fingerprint' => 'pixel1',
                'id' => newUniqueId(),
            ],
            [
                'license_id' => $license->id,
                'fingerprint' => 'pixel2',
                'id' => newUniqueId(),
            ],
        ]);

        $response = $this->getJson(
            uri: route('product.licenses.check', ['license_key' => $this->rawKey])
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'License checked successfully.',
                'data' => [
                    'customer' => $this->customerEmail,
                    'activations' => [
                        [
                            'product' => $this->product->name,
                            'status' => 'active',
                            'max_seats' => 2,
                            'seats_used' => 2,
                            'seats_left' => 0,
                            'expires_at' => null,
                            'is_valid' => true,
                            'slug' => $this->product_slug,
                        ],
                    ],
                ],
            ]);
    });
});
