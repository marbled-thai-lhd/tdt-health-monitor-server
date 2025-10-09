<?php

namespace App\Services;

use App\Models\Server;
use App\Models\HealthReport;
use Carbon\Carbon;

class HealthReportService
{
    /**
     * Process incoming health report
     */
    public function processHealthReport(Server $server, array $reportData, array $metadata = []): HealthReport
    {
        // Extract and normalize status values
        $supervisorStatus = $this->normalizeStatus($reportData['supervisor']['status'] ?? null);
        $cronStatus = $this->normalizeStatus($reportData['cron']['status'] ?? null);
        $queueStatus = $this->normalizeStatus($reportData['queues']['status'] ?? null);
        $systemStatus = $this->normalizeStatus($metadata['status'] ?? null);

        // Calculate overall status from individual statuses
        $overallStatus = $this->calculateOverallStatusFromComponents([
            $supervisorStatus,
            $cronStatus,
            $queueStatus,
            $systemStatus
        ]);

        // Create health report with separate status columns
        $healthReport = HealthReport::create([
            'server_id' => $server->id,
            'server_uuid' => $server->uuid,
            'report_type' => 'health_check',
            'supervisor_data' => $reportData['supervisor'] ?? null,
            'supervisor_status' => $supervisorStatus,
            'cron_data' => $reportData['cron'] ?? null,
            'cron_status' => $cronStatus,
            'queue_data' => $reportData['queues'] ?? null,
            'queue_status' => $queueStatus,
            'metadata' => array_merge($metadata, [
                'server_ip' => $reportData['server_ip'] ?? null,
                'received_at' => now()->toISOString(),
            ]),
            'system_status' => $systemStatus,
            'overall_status' => $overallStatus,
            'reported_at' => Carbon::parse($reportData['timestamp']),
        ]);

        return $healthReport;
    }

    /**
     * Process backup notification
     */
    public function processBackupNotification(Server $server, array $backupInfo): HealthReport
    {
        $backupStatus = $this->normalizeStatus($this->getBackupStatus($backupInfo));

        return HealthReport::create([
            'server_id' => $server->id,
            'server_uuid' => $server->uuid,
            'report_type' => 'backup_notification',
            'backup_data' => $backupInfo,
            'backup_status' => $backupStatus,
            'metadata' => [
                'received_at' => now()->toISOString(),
            ],
            'overall_status' => $backupStatus,
            'reported_at' => now(),
        ]);
    }

        /**
     * Calculate overall status from component statuses
     */
    private function calculateOverallStatusFromComponents(array $statuses): string
    {
        // Filter out null/empty statuses
        $statuses = array_filter($statuses, fn($status) => !empty($status));

        if (empty($statuses)) {
            return 'unknown';
        }

        // Priority: error > warning > ok
        if (in_array('error', $statuses)) {
            return 'error';
        }
        if (in_array('warning', $statuses)) {
            return 'warning';
        }
        if (in_array('ok', $statuses)) {
            return 'ok';
        }

        return 'unknown';
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
            'offline', 'stopped', 'no_processes' => 'error',
            default => $status
        };
    }

    /**
     * Calculate overall status from report data (legacy method)
     */
    public function calculateOverallStatus(array $reportData): string
    {
        $issues = [];

        // Check supervisor issues
        if (isset($reportData['supervisor'])) {
            $supervisorData = $reportData['supervisor'];
            $normalizedStatus = $this->normalizeStatus($supervisorData['status'] ?? 'unknown');

            if ($normalizedStatus !== 'ok') {
                $issues[] = 'supervisor';
            } elseif (isset($supervisorData['processes'])) {
                foreach ($supervisorData['processes'] as $process) {
                    if ($process['status'] !== 'RUNNING') {
                        $issues[] = 'supervisor';
                        break;
                    }
                }
            }
        }

        // Check cron issues
        if (isset($reportData['cron'])) {
            $normalizedStatus = $this->normalizeStatus($reportData['cron']['status'] ?? 'unknown');
            if ($normalizedStatus !== 'ok') {
                $issues[] = 'cron';
            }
        }

        // Check queue issues
        if (isset($reportData['queues'])) {
            $queueData = $reportData['queues'];
            $normalizedStatus = $this->normalizeStatus($queueData['status'] ?? 'unknown');
            if ($normalizedStatus !== 'ok') {
                $issues[] = 'queue';
            }
        }

        // Determine overall status
        if (empty($issues)) {
            return 'ok';
        }

        // If there are supervisor or queue issues, it's an error
        if (in_array('supervisor', $issues) || in_array('queue', $issues)) {
            return 'error';
        }

        // Otherwise it's a warning (cron issues are less critical)
        return 'warning';
    }

    /**
     * Get backup status from backup info
     */
    protected function getBackupStatus(array $backupInfo): string
    {
        if (isset($backupInfo['upload_error']) || ($backupInfo['uploaded'] ?? false) === false) {
            return 'error';
        }

        return 'ok';
    }

    /**
     * Get server statistics
     */
    public function getServerStatistics(): array
    {
        $totalServers = Server::count();
        $activeServers = Server::active()->count();
        $okServers = Server::where('status', 'ok')->count();
        $offlineServers = Server::offline()->count();
        $serversWithIssues = Server::withIssues()->count();

        // Recent reports statistics
        $recentReports = HealthReport::healthChecks()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $okReports = HealthReport::healthChecks()
            ->where('created_at', '>=', now()->subHours(24))
            ->where('overall_status', 'ok')
            ->count();

        return [
            'servers' => [
                'total' => $totalServers,
                'active' => $activeServers,
                'ok_servers' => $okServers,
                'offline' => $offlineServers,
                'with_issues' => $serversWithIssues,
                'health_percentage' => $totalServers > 0 ? round(($activeServers / $totalServers) * 100, 1) : 0,
            ],
            'reports_24h' => [
                'total' => $recentReports,
                'ok' => $okReports,
                'health_percentage' => $recentReports > 0 ? round(($okReports / $recentReports) * 100, 1) : 0,
            ]
        ];
    }

    /**
     * Get server health timeline
     */
    public function getServerHealthTimeline(Server $server, int $hours = 24): array
    {
        $reports = $server->healthReports()
            ->healthChecks()
            ->where('reported_at', '>=', now()->subHours($hours))
            ->orderBy('reported_at')
            ->get(['overall_status', 'reported_at']);

        return $reports->map(function ($report) {
            return [
                'timestamp' => $report->reported_at->toISOString(),
                'status' => $report->overall_status,
            ];
        })->toArray();
    }

    /**
     * Get health summary for all servers
     */
    public function getHealthSummary(): array
    {
        $servers = Server::with(['latestHealthReport', 'unresolvedAlerts'])
            ->get()
            ->map(function ($server) {
                $latestReport = $server->latestHealthReport;

                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'ip_address' => $server->ip_address,
                    'status' => $server->status,
                    'last_seen_at' => $server->last_seen_at?->toISOString(),
                    'is_offline' => $server->isOffline(),
                    'latest_report' => $latestReport ? [
                        'overall_status' => $latestReport->overall_status,
                        'reported_at' => $latestReport->reported_at->toISOString(),
                        'supervisor_status' => $latestReport->supervisor_data['status'] ?? 'unknown',
                        'cron_status' => $latestReport->cron_data['status'] ?? 'unknown',
                        'queue_status' => $latestReport->queue_data['status'] ?? 'unknown',
                    ] : null,
                    'unresolved_alerts_count' => $server->unresolvedAlerts()->count(),
                ];
            });

        return $servers->toArray();
    }

    /**
     * Clean up old health reports
     */
    public function cleanupOldReports(int $retentionDays = 30): int
    {
        $cutoffDate = now()->subDays($retentionDays);

        return HealthReport::where('created_at', '<', $cutoffDate)->delete();
    }
}
