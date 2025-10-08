<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HealthReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'report_type',
        'supervisor_data',
        'cron_data',
        'queue_data',
        'backup_data',
        'metadata',
        'overall_status',
        'reported_at',
    ];

    protected $casts = [
        'supervisor_data' => 'array',
        'cron_data' => 'array',
        'queue_data' => 'array',
        'backup_data' => 'array',
        'metadata' => 'array',
        'reported_at' => 'datetime',
    ];

    /**
     * Get the server that owns this report
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Check if this is a health check report
     */
    public function isHealthCheck(): bool
    {
        return $this->report_type === 'health_check';
    }

    /**
     * Check if this is a backup notification
     */
    public function isBackupNotification(): bool
    {
        return $this->report_type === 'backup_notification';
    }

    /**
     * Get supervisor process issues
     */
    public function getSupervisorIssues(): array
    {
        if (!$this->supervisor_data || $this->supervisor_data['status'] === 'ok') {
            return [];
        }

        $issues = [];

        if (isset($this->supervisor_data['processes'])) {
            foreach ($this->supervisor_data['processes'] as $process) {
                if ($process['status'] !== 'RUNNING') {
                    $issues[] = [
                        'type' => 'process_not_running',
                        'process' => $process['name'],
                        'status' => $process['status'],
                        'severity' => 'high'
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Get queue health issues
     */
    public function getQueueIssues(): array
    {
        if (!$this->queue_data || $this->queue_data['status'] === 'healthy') {
            return [];
        }

        $issues = [];

        if (isset($this->queue_data['queues'])) {
            foreach ($this->queue_data['queues'] as $queueName => $queueData) {
                if ($queueData['status'] !== 'healthy') {
                    $severity = $queueData['status'] === 'timeout' ? 'high' : 'medium';
                    $issues[] = [
                        'type' => 'queue_unhealthy',
                        'queue' => $queueName,
                        'status' => $queueData['status'],
                        'severity' => $severity,
                        'message' => $queueData['message'] ?? 'Queue health check failed'
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Get cron issues
     */
    public function getCronIssues(): array
    {
        if (!$this->cron_data || $this->cron_data['status'] === 'ok') {
            return [];
        }

        return [
            [
                'type' => 'cron_error',
                'message' => $this->cron_data['message'] ?? 'Cron check failed',
                'severity' => 'medium'
            ]
        ];
    }

    /**
     * Get all issues from this report
     */
    public function getAllIssues(): array
    {
        return array_merge(
            $this->getSupervisorIssues(),
            $this->getQueueIssues(),
            $this->getCronIssues()
        );
    }

    /**
     * Calculate overall status from individual components
     */
    public function calculateOverallStatus(): string
    {
        $issues = $this->getAllIssues();

        if (empty($issues)) {
            return 'healthy';
        }

        $hasHighSeverity = collect($issues)->contains('severity', 'high');
        $hasCriticalSeverity = collect($issues)->contains('severity', 'critical');

        if ($hasCriticalSeverity) {
            return 'error';
        } elseif ($hasHighSeverity) {
            return 'error';
        } else {
            return 'warning';
        }
    }

    /**
     * Scope for health check reports
     */
    public function scopeHealthChecks($query)
    {
        return $query->where('report_type', 'health_check');
    }

    /**
     * Scope for backup notifications
     */
    public function scopeBackupNotifications($query)
    {
        return $query->where('report_type', 'backup_notification');
    }

    /**
     * Scope for reports with issues
     */
    public function scopeWithIssues($query)
    {
        return $query->whereIn('overall_status', ['warning', 'error']);
    }
}
