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
        Schema::create('servers', function (Blueprint $table) {
            $table->uuid('id')->unique()->primary();
            $table->string('name')->unique();
            $table->string('ip_address')->nullable();
            $table->string('base_url')->nullable();
            $table->text('description')->nullable();
            $table->enum('environment', ['production', 'staging', 'development', 'testing'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('api_key')->unique();
            $table->string('status')->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'last_seen_at']);
            $table->index(['is_active', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
