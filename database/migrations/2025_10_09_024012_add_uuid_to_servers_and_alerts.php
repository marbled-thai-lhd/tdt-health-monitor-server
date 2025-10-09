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
        // Add UUID to servers table
        Schema::table('servers', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->index('uuid');
        });

        // Add UUID to alerts table  
        Schema::table('alerts', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropIndex(['servers_uuid_unique']);
            $table->dropColumn('uuid');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['alerts_uuid_unique']);
            $table->dropColumn('uuid');
        });
    }
};
