<?php

namespace App\Services;

use App\Models\Server;
use App\Models\HealthReport;
use App\Models\Alert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * Process health report and generate alerts
     */
    public function processHealthReportAlerts(HealthReport $healthReport): void
    {
        $server = $healthReport->server;

        // Get all issues from the report
        $allIssues = $healthReport->getAllIssues();

        if (empty($allIssues)) {
            return;
        }

        // Group issues by type
        $supervisorIssues = array_filter($allIssues, fn($issue) => $issue['type'] === 'process_not_running');
        $queueIssues = array_filter($allIssues, fn($issue) => $issue['type'] === 'queue_unhealthy');
        $cronIssues = array_filter($allIssues, fn($issue) => $issue['type'] === 'cron_error');

        // Create alerts for supervisor issues
        if (!empty($supervisorIssues)) {
            $this->createSupervisorAlert($server, $supervisorIssues);
        }

        // Create alerts for queue issues
        if (!empty($queueIssues)) {
            $this->createQueueAlert($server, $queueIssues);
        }

        // Create alerts for cron issues
        if (!empty($cronIssues)) {
            $this->createCronAlert($server, $cronIssues[0]['message']);
        }
    }

    /**
     * Check for offline servers and create alerts
     */
    public function checkOfflineServers(): void
    {
        $offlineServers = Server::offline()->get();

        foreach ($offlineServers as $server) {
            // Check if we already have an unresolved offline alert for this server
            $existingAlert = Alert::where('server_id', $server->id)
                ->where('type', 'server_offline')
                ->where('resolved', false)
                ->first();

            if (!$existingAlert) {
                $alert = Alert::createServerOfflineAlert($server);
                $this->sendAlertNotification($alert);
            }
        }
    }

    /**
     * Resolve offline alerts for servers that came back online
     */
    public function resolveOfflineAlerts(): void
    {
        // Get servers that are now online but have unresolved offline alerts
        $onlineServers = Server::where('status', '!=', 'error')
            ->whereHas('alerts', function ($query) {
                $query->where('type', 'server_offline')
                      ->where('resolved', false);
            })
            ->get();

        foreach ($onlineServers as $server) {
            $offlineAlerts = $server->alerts()
                ->where('type', 'server_offline')
                ->where('resolved', false)
                ->get();

            foreach ($offlineAlerts as $alert) {
                $alert->resolve('Server came back online');
                Log::info('Resolved offline alert', [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'alert_id' => $alert->id
                ]);
            }
        }
    }

    /**
     * Create supervisor alert if not already exists
     */
    protected function createSupervisorAlert(Server $server, array $issues): void
    {
        $processNames = collect($issues)->pluck('process')->unique()->values()->toArray();

        // Check for existing unresolved supervisor alert with same processes
        $existingAlert = Alert::where('server_id', $server->id)
            ->where('type', 'supervisor_issue')
            ->where('resolved', false)
            ->where('data->issues', $issues)
            ->first();

        if (!$existingAlert) {
            $alert = Alert::createSupervisorAlert($server, $issues);
            $this->sendAlertNotification($alert);
        }
    }

    /**
     * Create queue alert if not already exists
     */
    protected function createQueueAlert(Server $server, array $issues): void
    {
        $queueNames = collect($issues)->pluck('queue')->unique()->values()->toArray();

        // Check for existing unresolved queue alert
        $existingAlert = Alert::where('server_id', $server->id)
            ->where('type', 'queue_issue')
            ->where('resolved', false)
            ->whereJsonContains('data->issues', $issues)
            ->first();

        if (!$existingAlert) {
            $alert = Alert::createQueueAlert($server, $issues);
            $this->sendAlertNotification($alert);
        }
    }

    /**
     * Create cron alert if not already exists
     */
    protected function createCronAlert(Server $server, string $message): void
    {
        // Check for existing unresolved cron alert
        $existingAlert = Alert::where('server_id', $server->id)
            ->where('type', 'cron_issue')
            ->where('resolved', false)
            ->where('created_at', '>=', now()->subHours(1)) // Only check last hour
            ->first();

        if (!$existingAlert) {
            $alert = Alert::createCronAlert($server, $message);
            $this->sendAlertNotification($alert);
        }
    }

    /**
     * Send alert notification via email
     */
    protected function sendAlertNotification(Alert $alert): void
    {
        try {
            $recipients = config('monitoring.alert_emails', []);

            if (empty($recipients)) {
                Log::warning('No alert email recipients configured');
                return;
            }

            // For now, just log the alert - you can implement actual email sending
            Log::info('Alert notification', [
                'alert_id' => $alert->id,
                'server_name' => $alert->server->name,
                'type' => $alert->type,
                'severity' => $alert->severity,
                'title' => $alert->title,
                'message' => $alert->message,
                'recipients' => $recipients
            ]);

            // TODO: Implement actual email sending
            // Mail::to($recipients)->send(new AlertNotification($alert));

        } catch (\Exception $e) {
            Log::error('Failed to send alert notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Auto-resolve stale alerts
     */
    public function autoResolveStaleAlerts(): void
    {
        $staleThreshold = now()->subHours(24);

        // Resolve supervisor and queue alerts that are older than 24 hours
        // if the latest health report shows no issues
        $staleAlerts = Alert::unresolved()
            ->whereIn('type', ['supervisor_issue', 'queue_issue'])
            ->where('created_at', '<', $staleThreshold)
            ->with('server.healthReports')
            ->get();

        foreach ($staleAlerts as $alert) {
            $latestReport = $alert->server->latestHealthReport;

            if ($latestReport && $latestReport->overall_status === 'ok') {
                $alert->resolve('Auto-resolved: issue no longer present in latest reports');

                Log::info('Auto-resolved stale alert', [
                    'alert_id' => $alert->id,
                    'server_name' => $alert->server->name,
                    'alert_type' => $alert->type
                ]);
            }
        }
    }

    /**
     * Get alert statistics
     */
    public function getAlertStatistics(): array
    {
        $total = Alert::count();
        $unresolved = Alert::unresolved()->count();
        $critical = Alert::unresolved()->critical()->count();
        $high = Alert::unresolved()->high()->count();
        $recent = Alert::recent(24)->count();

        return [
            'total_alerts' => $total,
            'unresolved_alerts' => $unresolved,
            'critical_alerts' => $critical,
            'high_severity_alerts' => $high,
            'recent_alerts_24h' => $recent,
            'resolution_rate' => $total > 0 ? round((($total - $unresolved) / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get recent alerts for dashboard
     */
    public function getRecentAlerts(int $limit = 10): array
    {
        return Alert::with('server')
            ->unresolved()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'server_name' => $alert->server->name,
                    'type' => $alert->type,
                    'type_display' => $alert->getTypeDisplayName(),
                    'severity' => $alert->severity,
                    'severity_color' => $alert->getSeverityColor(),
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'created_at' => $alert->created_at->toISOString(),
                    'age' => $alert->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }
}
