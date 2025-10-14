<?php

namespace App\Services;

use App\Models\Server;
use App\Models\HealthReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

        // Update server status and last_seen_at based on health report
        $this->updateServerStatus($server, $overallStatus);

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

        // Group reports by hour to reduce noise and get better visualization
        $groupedReports = $reports->groupBy(function ($report) {
            return $report->reported_at->format('Y-m-d H:00:00');
        });

        $timeline = [];

        // Generate hourly timeline for the last 24 hours
        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('Y-m-d H:00:00');
            $hourReports = $groupedReports->get($hour, collect());

            if ($hourReports->count() > 0) {
                // Get the most recent status for this hour
                $latestReport = $hourReports->last();
                $status = $latestReport->overall_status;
            } else {
                // If no reports for this hour, assume offline
                $status = 'offline';
            }

            $timeline[] = [
                'timestamp' => now()->subHours($i)->toISOString(),
                'status' => $status,
            ];
        }

        return $timeline;
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

    /**
     * Update server status based on health report
     */
    protected function updateServerStatus(Server $server, string $overallStatus): void
    {
        try {
            // Update last_seen_at to current time
            $server->last_seen_at = now();

            // Update server status based on overall health status
            $server->status = $overallStatus;

            $server->save();

            Log::info('Server status updated', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'new_status' => $overallStatus,
                'last_seen_at' => $server->last_seen_at
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update server status', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'new_status' => $overallStatus,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark servers as offline if they haven't sent reports recently
     */
    public function markOfflineServers(): int
    {
        $threshold = config('monitoring.offline_threshold', 10); // minutes
        $cutoffTime = now()->subMinutes($threshold);

        $offlineCount = Server::where('status', '!=', 'offline')
            ->where(function ($query) use ($cutoffTime) {
                $query->whereNull('last_seen_at')
                      ->orWhere('last_seen_at', '<', $cutoffTime);
            })
            ->update(['status' => 'offline']);

        return $offlineCount;
    }
}
