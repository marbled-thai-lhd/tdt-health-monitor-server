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
        // Calculate overall status from report data
        $overallStatus = $this->calculateOverallStatus($reportData);

        // Create health report
        $healthReport = HealthReport::create([
            'server_id' => $server->id,
            'report_type' => 'health_check',
            'supervisor_data' => $reportData['supervisor'] ?? null,
            'cron_data' => $reportData['cron'] ?? null,
            'queue_data' => $reportData['queues'] ?? null,
            'metadata' => array_merge($metadata, [
                'server_ip' => $reportData['server_ip'] ?? null,
                'received_at' => now()->toISOString(),
            ]),
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
        return HealthReport::create([
            'server_id' => $server->id,
            'report_type' => 'backup_notification',
            'backup_data' => $backupInfo,
            'metadata' => [
                'received_at' => now()->toISOString(),
            ],
            'overall_status' => $this->getBackupStatus($backupInfo),
            'reported_at' => now(),
        ]);
    }

    /**
     * Calculate overall status from individual components
     */
    protected function calculateOverallStatus(array $reportData): string
    {
        $issues = [];

        // Check supervisor issues
        if (isset($reportData['supervisor'])) {
            $supervisorData = $reportData['supervisor'];
            if ($supervisorData['status'] !== 'ok') {
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
        if (isset($reportData['cron']) && $reportData['cron']['status'] !== 'ok') {
            $issues[] = 'cron';
        }

        // Check queue issues
        if (isset($reportData['queues'])) {
            $queueData = $reportData['queues'];
            if ($queueData['status'] !== 'healthy') {
                $issues[] = 'queue';
            }
        }

        // Determine overall status
        if (empty($issues)) {
            return 'healthy';
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

        return 'healthy';
    }

    /**
     * Get server statistics
     */
    public function getServerStatistics(): array
    {
        $totalServers = Server::count();
        $activeServers = Server::active()->count();
        $offlineServers = Server::offline()->count();
        $serversWithIssues = Server::withIssues()->count();

        // Recent reports statistics
        $recentReports = HealthReport::healthChecks()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $healthyReports = HealthReport::healthChecks()
            ->where('created_at', '>=', now()->subHours(24))
            ->where('overall_status', 'healthy')
            ->count();

        return [
            'servers' => [
                'total' => $totalServers,
                'active' => $activeServers,
                'offline' => $offlineServers,
                'with_issues' => $serversWithIssues,
                'health_percentage' => $totalServers > 0 ? round(($activeServers / $totalServers) * 100, 1) : 0,
            ],
            'reports_24h' => [
                'total' => $recentReports,
                'healthy' => $healthyReports,
                'health_percentage' => $recentReports > 0 ? round(($healthyReports / $recentReports) * 100, 1) : 0,
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
