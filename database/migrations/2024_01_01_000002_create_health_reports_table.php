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
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->enum('report_type', ['health_check', 'backup_notification'])->default('health_check');
            $table->json('supervisor_data')->nullable();
            $table->json('cron_data')->nullable();
            $table->json('queue_data')->nullable();
            $table->json('backup_data')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('overall_status', ['healthy', 'warning', 'error'])->default('healthy');
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->index(['server_id', 'reported_at']);
            $table->index(['overall_status', 'reported_at']);
            $table->index('report_type');
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
