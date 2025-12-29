<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\BrandApiKey;
use App\Models\Product;
use Illuminate\Console\Command;
use Random\RandomException;
use Str;

class ConfigureApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configure-app';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the command for use';

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $brands = [];

    /**
     * @var array<string, mixed>
     */
    protected array $products = [];

    /**
     * Execute the console command.
     *
     * @throws RandomException
     */
    public function handle(): int
    {
        $this->flushOutPreviousRecords();

        $this->buildItems();

        $this->populateBrands();
        $this->populateProducts();
        $this->generateAPIKeys();

        return 0;
    }

    protected function populateBrands(): void
    {
        Brand::insert($this->brands);

        $this->info('✅ Brands populated successfully ('.count($this->brands).')');
    }

    protected function populateProducts(): void
    {
        $count = 0;

        foreach ($this->products as $products) {
            Product::insert($products);
            $count += count($products);
        }

        $this->info("✅ Products populated successfully ({$count})");
    }

    private function flushOutPreviousRecords(): void
    {
        $this->warn('🧹 Cleaning existing records...');

        Brand::truncate();
        Product::truncate();
        BrandApiKey::truncate();

        $this->info('🗑️  Existing brands and products removed');
    }

    protected function buildItems(): void
    {
        $this->info('🚀 Initializing seed payloads...');
        $this->line('────────────────────────────────────────');

        $now = now();

        $this->info('📦 Preparing brands...');
        $this->brands = [
            [
                'id' => $id1 = newUniqueId(),
                'name' => $name = 'WP Rocket',
                'slug' => Str::slug($name, '_'),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => $id2 = newUniqueId(),
                'name' => $name = 'Rank Math',
                'slug' => Str::slug($name, '_'),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'id' => $id3 = newUniqueId(),
                'name' => $name = 'Imagify',
                'slug' => Str::slug($name, '_'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->line('   → 3 brands prepared');

        $this->info('📦 Preparing products...');
        $this->products = [
            $id1 => [
                [
                    'id' => newUniqueId(),
                    'name' => $productName = 'WP Rocket Core Plugin',
                    'slug' => Str::slug($productName, '_'),
                    'brand_id' => $id1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => newUniqueId(),
                    'name' => $productName = 'RocketCDN',
                    'slug' => Str::slug($productName, '_'),
                    'brand_id' => $id1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],

                [
                    'id' => newUniqueId(),
                    'name' => $productName = 'Advanced Caching Features',
                    'slug' => Str::slug($productName, '_'),
                    'brand_id' => $id1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],

            $id2 => [
                [
                    'id' => newUniqueId(),
                    'name' => $productName = 'Rank Math SEO Plugin',
                    'slug' => Str::slug($productName, '_'),
                    'brand_id' => $id2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => newUniqueId(),
                    'name' => $productName = 'Content AI',
                    'slug' => Str::slug($productName, '_'),
                    'brand_id' => $id2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => newUniqueId(),
                    'name' => $productName = 'Advanced Schema Builder',
                    'slug' => Str::slug($productName, '_'),
                    'brand_id' => $id2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],

            $id3 => [
                [
                    'id' => newUniqueId(),
                    'name' => $productName = 'Imagify Core Plugin',
                    'slug' => Str::slug($productName, '_'),
                    'brand_id' => $id3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => newUniqueId(),
                    'name' => $productName = 'Imagify Smart Compression',
                    'slug' => Str::slug($productName, '_'),
                    'brand_id' => $id3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => newUniqueId(),
                    'name' => $productName = 'WebP & AVIF Conversion',
                    'slug' => Str::slug($productName, '_'),
                    'brand_id' => $id3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
        ];

        $this->line('   → 9 products prepared');

        $this->line('────────────────────────────────────────');
    }

    /**
     * @throws RandomException
     */
    private function generateAPIKeys(): void
    {
        $apiKeys = [];

        foreach ($this->brands as $brand) {
            $apiKey = bin2hex(random_bytes(30));

            BrandApiKey::query()->create([
                'brand_id' => $brand['id'],
                'api_key' => hash('sha256', $apiKey),
                'expires_at' => null,
            ]);

            $apiKeys[] = [
                'brand' => $brand['name'],
                'api_key' => $apiKey,
            ];
        }

        $this->info("\n🎉 X-BRAND-API-KEY Keys Generated Successfully! 🎉\n");
        $this->warn('⚠️  Please copy and store this key securely now. You will NOT be able to view it again.');

        $headers = ['Brand', 'API Key'];
        $rows = array_map(fn ($item) => [$item['brand'], $item['api_key']], $apiKeys);

        $this->table($headers, $rows);
    }
}
