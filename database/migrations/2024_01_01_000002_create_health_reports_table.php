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
        Schema::create('health_reports', function (Blueprint $table) {
            $table->uuid('id')->unique()->primary();
            $table->uuid('server_id');
            $table->enum('report_type', ['health_check', 'backup_notification'])->default('health_check');
            $table->json('supervisor_data')->nullable();
            $table->string('supervisor_status', 20)->nullable();
            $table->json('cron_data')->nullable();
            $table->string('cron_status', 20)->nullable();
            $table->json('queue_data')->nullable();
            $table->string('queue_status', 20)->nullable();
            $table->json('backup_data')->nullable();
            $table->string('backup_status', 20)->nullable();
            $table->string('system_status', 20)->nullable();
            $table->json('metadata')->nullable();
            $table->string('overall_status', 20)->default('unknown');
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->index(['server_id', 'reported_at']);
            $table->index(['overall_status', 'reported_at']);
            $table->index('report_type');
            $table->index(['supervisor_status', 'cron_status'], 'hr_status_idx');
            $table->index(['queue_status', 'backup_status'], 'hr_queue_backup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_reports');
    }
};
