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
        Schema::table('servers', function (Blueprint $table) {
            $table->text('description')->nullable()->after('ip_address');
            $table->enum('environment', ['production', 'staging', 'development', 'testing'])->nullable()->after('description');
            $table->boolean('is_active')->default(true)->after('environment');

            // Update api_key to be required and unique
            $table->string('api_key')->nullable(false)->unique()->change();

            // Update status enum to include more statuses
            $table->enum('status', ['healthy', 'warning', 'critical', 'offline'])->default('offline')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['description', 'environment', 'is_active']);

            // Revert api_key to nullable
            $table->string('api_key')->nullable()->change();

            // Revert status enum
            $table->enum('status', ['active', 'inactive', 'warning', 'error'])->default('active')->change();
        });
    }
};
