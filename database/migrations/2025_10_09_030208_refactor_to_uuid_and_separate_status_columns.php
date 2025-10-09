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
        // Step 1: Add UUID columns to all tables
        Schema::table('servers', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->index('uuid');
        });

        Schema::table('health_reports', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->uuid('server_uuid')->nullable()->after('server_id');

            // Add separate status columns
            $table->string('supervisor_status', 20)->nullable()->after('supervisor_data');
            $table->string('cron_status', 20)->nullable()->after('cron_data');
            $table->string('queue_status', 20)->nullable()->after('queue_data');
            $table->string('backup_status', 20)->nullable()->after('backup_data');
            $table->string('system_status', 20)->nullable()->after('metadata');

            $table->index('uuid');
            $table->index('server_uuid');
            $table->index(['supervisor_status', 'cron_status', 'queue_status', 'backup_status']);
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->uuid('server_uuid')->nullable()->after('server_id');
            $table->index('uuid');
            $table->index('server_uuid');
        });

        // Step 2: Populate UUID values
        DB::table('servers')->whereNull('uuid')->update([
            'uuid' => DB::raw('(SELECT UUID())')
        ]);

        DB::table('health_reports')->whereNull('uuid')->update([
            'uuid' => DB::raw('(SELECT UUID())')
        ]);

        DB::table('alerts')->whereNull('uuid')->update([
            'uuid' => DB::raw('(SELECT UUID())')
        ]);

        // Step 3: Populate server_uuid in related tables
        DB::statement('
            UPDATE health_reports hr
            JOIN servers s ON hr.server_id = s.id
            SET hr.server_uuid = s.uuid
        ');

        DB::statement('
            UPDATE alerts a
            JOIN servers s ON a.server_id = s.id
            SET a.server_uuid = s.uuid
        ');

        // Step 4: Populate status columns from JSON data
        $healthReports = DB::table('health_reports')->get();
        foreach ($healthReports as $report) {
            $supervisorData = json_decode($report->supervisor_data, true);
            $cronData = json_decode($report->cron_data, true);
            $queueData = json_decode($report->queue_data, true);
            $backupData = json_decode($report->backup_data, true);
            $metadata = json_decode($report->metadata, true);

            $updates = [
                'supervisor_status' => $this->normalizeStatus($supervisorData['status'] ?? null),
                'cron_status' => $this->normalizeStatus($cronData['status'] ?? null),
                'queue_status' => $this->normalizeStatus($queueData['status'] ?? null),
                'backup_status' => $this->normalizeStatus($backupData['status'] ?? null),
                'system_status' => $this->normalizeStatus($metadata['status'] ?? null),
            ];

            DB::table('health_reports')
                ->where('id', $report->id)
                ->update($updates);
        }

        // Step 5: Make UUID columns non-nullable and unique
        Schema::table('servers', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->unique('uuid');
        });

        Schema::table('health_reports', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->uuid('server_uuid')->nullable(false)->change();
            $table->unique('uuid');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->uuid('server_uuid')->nullable(false)->change();
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropIndex(['server_uuid']);
            $table->dropColumn(['uuid', 'server_uuid']);
        });

        Schema::table('health_reports', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropIndex(['server_uuid']);
            $table->dropIndex(['supervisor_status', 'cron_status', 'queue_status', 'backup_status']);
            $table->dropColumn([
                'uuid',
                'server_uuid',
                'supervisor_status',
                'cron_status',
                'queue_status',
                'backup_status',
                'system_status'
            ]);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
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
