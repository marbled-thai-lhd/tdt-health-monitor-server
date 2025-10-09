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
        Schema::table('health_reports', function (Blueprint $table) {
            // Add UUID column
            $table->uuid('uuid')->unique()->after('id');
            
            // Add separate status columns
            $table->string('supervisor_status')->default('unknown')->after('supervisor_data');
            $table->string('cron_status')->default('unknown')->after('cron_data');
            $table->string('queue_status')->default('unknown')->after('queue_data');
            $table->string('backup_status')->default('unknown')->after('backup_data');
            
            // Add indexes for better performance
            $table->index('uuid');
            $table->index(['supervisor_status', 'cron_status'], 'hr_status_idx_1');
            $table->index(['queue_status', 'backup_status'], 'hr_status_idx_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_reports', function (Blueprint $table) {
            $table->dropIndex(['health_reports_uuid_unique']);
            $table->dropIndex(['hr_status_idx_1']);
            $table->dropIndex(['hr_status_idx_2']);
            $table->dropColumn(['uuid', 'supervisor_status', 'cron_status', 'queue_status', 'backup_status']);
        });
    }
};
