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
        // Populate UUIDs for existing servers
        DB::table('servers')->whereNull('uuid')->get()->each(function ($server) {
            DB::table('servers')
                ->where('id', $server->id)
                ->update(['uuid' => \Illuminate\Support\Str::uuid()->toString()]);
        });

        // Populate UUIDs for existing alerts
        DB::table('alerts')->whereNull('uuid')->get()->each(function ($alert) {
            DB::table('alerts')
                ->where('id', $alert->id)
                ->update(['uuid' => \Illuminate\Support\Str::uuid()->toString()]);
        });

        // Populate UUIDs and status columns for existing health reports
        DB::table('health_reports')->whereNull('uuid')->get()->each(function ($report) {
            $supervisorData = json_decode($report->supervisor_data, true);
            $cronData = json_decode($report->cron_data, true);
            $queueData = json_decode($report->queue_data, true);
            $backupData = json_decode($report->backup_data, true);

            $updates = [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'supervisor_status' => $this->normalizeStatus($supervisorData['status'] ?? 'unknown'),
                'cron_status' => $this->normalizeStatus($cronData['status'] ?? 'unknown'),
                'queue_status' => $this->normalizeStatus($queueData['status'] ?? 'unknown'),
                'backup_status' => $this->normalizeStatus($backupData['status'] ?? 'unknown'),
            ];

            DB::table('health_reports')
                ->where('id', $report->id)
                ->update($updates);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse since we're just populating data
    }

    /**
     * Normalize status values to consistent format
     */
    private function normalizeStatus(?string $status): string
    {
        return match($status) {
            'healthy', 'ok', 'good', 'running' => 'ok',
            'unhealthy', 'error', 'failed', 'critical', 'timeout' => 'error',
            'warning', 'degraded' => 'warning',
            'offline', 'stopped', 'no_processes' => 'offline',
            default => $status ?? 'unknown'
        };
    }
};
