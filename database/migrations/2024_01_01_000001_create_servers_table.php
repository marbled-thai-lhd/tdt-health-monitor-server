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
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->string('ip_address')->nullable();
            $table->string('base_url')->nullable();
            $table->text('description')->nullable();
            $table->enum('environment', ['production', 'staging', 'development', 'testing'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('api_key')->unique();
            $table->enum('status', ['ok', 'warning', 'error', 'offline'])->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('uuid');
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
