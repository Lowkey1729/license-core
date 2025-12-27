<?php

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
        Schema::create('activations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_id');
            $table->string('fingerprint', 100);
            $table->string('platform_info', 100)->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->unique(['license_id', 'fingerprint', 'deleted_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activations');
    }
};
