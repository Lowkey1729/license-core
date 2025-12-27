<?php

use App\Enums\LicenseStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_key_id');
            $table->foreignUuid('product_id');
            $table->string('status', 25)->default(LicenseStatusEnum::Active->value);
            $table->integer('max_seats')->default(1);
            $table->timestamp('expires_at')->nullable();

            $table->unique(['license_key_id', 'product_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
