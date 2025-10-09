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
        // Add missing columns to health_reports
        Schema::table('health_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('health_reports', 'server_uuid')) {
                $table->uuid('server_uuid')->nullable()->after('server_id');
                $table->index('server_uuid');
            }
            if (!Schema::hasColumn('health_reports', 'system_status')) {
                $table->string('system_status', 20)->nullable()->after('backup_status');
            }
        });

        // Populate server_uuid for health_reports
        DB::statement('
            UPDATE health_reports hr
            JOIN servers s ON hr.server_id = s.id
            SET hr.server_uuid = s.uuid
            WHERE hr.server_uuid IS NULL
        ');

        // Populate status columns from JSON data if they are null
        $healthReports = DB::table('health_reports')
            ->whereNull('supervisor_status')
            ->orWhereNull('cron_status')
            ->orWhereNull('queue_status')
            ->orWhereNull('backup_status')
            ->get();

        foreach ($healthReports as $report) {
            $supervisorData = json_decode($report->supervisor_data, true);
            $cronData = json_decode($report->cron_data, true);
            $queueData = json_decode($report->queue_data, true);
            $backupData = json_decode($report->backup_data, true);
            $metadata = json_decode($report->metadata, true);

            $updates = [];

            if (!$report->supervisor_status) {
                $updates['supervisor_status'] = $this->normalizeStatus($supervisorData['status'] ?? null);
            }
            if (!$report->cron_status) {
                $updates['cron_status'] = $this->normalizeStatus($cronData['status'] ?? null);
            }
            if (!$report->queue_status) {
                $updates['queue_status'] = $this->normalizeStatus($queueData['status'] ?? null);
            }
            if (!$report->backup_status) {
                $updates['backup_status'] = $this->normalizeStatus($backupData['status'] ?? null);
            }
            if (!$report->system_status) {
                $updates['system_status'] = $this->normalizeStatus($metadata['status'] ?? null);
            }

            if (!empty($updates)) {
                DB::table('health_reports')
                    ->where('id', $report->id)
                    ->update($updates);
            }
        }

        // Make server_uuid non-nullable for health_reports
        Schema::table('health_reports', function (Blueprint $table) {
            if (Schema::hasColumn('health_reports', 'server_uuid')) {
                $table->uuid('server_uuid')->nullable(false)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_reports', function (Blueprint $table) {
            if (Schema::hasColumn('health_reports', 'server_uuid')) {
                $table->dropIndex(['server_uuid']);
                $table->dropColumn('server_uuid');
            }
            if (Schema::hasColumn('health_reports', 'system_status')) {
                $table->dropColumn('system_status');
            }
        });
    }

    /**
     * Normalize status values to consistent format
     */
    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        return match($status) {
            'healthy', 'ok', 'good', 'running' => 'ok',
            'unhealthy', 'error', 'failed', 'critical', 'timeout' => 'error',
            'warning', 'degraded' => 'warning',
            'offline', 'stopped', 'no_processes' => 'offline',
            default => $status
        };
    }
};
