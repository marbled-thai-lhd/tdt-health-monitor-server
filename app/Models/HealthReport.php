<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class HealthReport extends Model
{
    use HasFactory;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'report_type',
        'supervisor_data',
        'supervisor_status',
        'cron_data',
        'cron_status',
        'queue_data',
        'queue_status',
        'backup_data',
        'backup_status',
        'metadata',
        'system_status',
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
     * Boot the model and generate UUID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Get the server that owns this report
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id', 'id');
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
     * Get supervisor status (from separate column, normalized during migration)
     */
    public function getSupervisorStatusAttribute(): string
    {
        return $this->attributes['supervisor_status'] ?? 'unknown';
    }

    /**
     * Get cron status (from separate column, normalized during migration)
     */
    public function getCronStatusAttribute(): string
    {
        return $this->attributes['cron_status'] ?? 'unknown';
    }

    /**
     * Get queue status (from separate column, normalized during migration)
     */
    public function getQueueStatusAttribute(): string
    {
        return $this->attributes['queue_status'] ?? 'unknown';
    }

    /**
     * Get backup status (from separate column, normalized during migration)
     */
    public function getBackupStatusAttribute(): string
    {
        return $this->attributes['backup_status'] ?? 'unknown';
    }

    /**
     * Get system status (from separate column, normalized during migration)
     */
    public function getSystemStatusAttribute(): string
    {
        return $this->attributes['system_status'] ?? 'unknown';
    }

    /**
     * Get overall status (from separate column or calculate from components)
     */
    public function getOverallStatusAttribute(): string
    {
        // If overall_status is set, use it
        if (!empty($this->attributes['overall_status'])) {
            return $this->normalizeStatus($this->attributes['overall_status']);
        }

        // Otherwise calculate from component statuses
        $statuses = [
            $this->supervisor_status,
            $this->cron_status,
            $this->queue_status,
            $this->backup_status,
            $this->system_status
        ];

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
    private function normalizeStatus(?string $status): string
    {
        return match($status) {
            'healthy', 'ok', 'good', 'running' => 'ok',
            'unhealthy', 'error', 'failed', 'critical', 'timeout' => 'error',
            'warning', 'degraded' => 'warning',
            'offline', 'stopped', 'no_processes' => 'error',
            default => $status ?? 'unknown'
        };
    }    /**
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
        if (!$this->queue_data || in_array($this->queue_data['status'], ['ok', 'healthy'])) {
            return [];
        }

        $issues = [];

        if (isset($this->queue_data['queues'])) {
            foreach ($this->queue_data['queues'] as $queueName => $queueData) {
                if (!in_array($queueData['status'], ['ok', 'healthy'])) {
                    $severity = in_array($queueData['status'], ['timeout', 'error', 'failed']) ? 'high' : 'medium';
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
            return 'ok';
        }

        $hasHighSeverity = collect($issues)->contains('severity', 'high');
        $hasCriticalSeverity = collect($issues)->contains('severity', 'critical');

        if ($hasCriticalSeverity || $hasHighSeverity) {
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
