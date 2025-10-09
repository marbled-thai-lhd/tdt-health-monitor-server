<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->uuid('server_uuid')->nullable()->after('server_id');
            $table->index('server_uuid');
        });

        // Populate server_uuid from existing server_id relationships
        DB::statement('
            UPDATE alerts a
            JOIN servers s ON a.server_id = s.id
            SET a.server_uuid = s.uuid
        ');

        // Make server_uuid non-nullable
        Schema::table('alerts', function (Blueprint $table) {
            $table->uuid('server_uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['server_uuid']);
            $table->dropColumn('server_uuid');
        });
    }
};
