<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Alert extends Model
{
    use HasFactory;
    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'type',
        'severity',
        'title',
        'message',
        'data',
        'resolved',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'data' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
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
     * Get the server that owns this alert
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id', 'id');
    }

    /**
     * Mark alert as resolved
     */
    public function resolve(?string $notes = null): void
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Check if alert is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Get severity level as integer for sorting
     */
    public function getSeverityLevel(): int
    {
        return match($this->severity) {
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    /**
     * Get severity color for UI
     */
    public function getSeverityColor(): string
    {
        return match($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get alert type display name
     */
    public function getTypeDisplayName(): string
    {
        return match($this->type) {
            'server_offline' => 'Server Offline',
            'supervisor_issue' => 'Supervisor Issue',
            'cron_issue' => 'Cron Issue',
            'queue_issue' => 'Queue Issue',
            'backup_failed' => 'Backup Failed',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Create server offline alert
     */
    public static function createServerOfflineAlert(Server $server): self
    {
        return self::create([
            'server_id' => $server->id,
            'type' => 'server_offline',
            'severity' => 'critical',
            'title' => "Server '{$server->name}' is offline",
            'message' => "Server has not reported for more than the configured threshold.",
            'data' => [
                'last_seen_at' => $server->last_seen_at?->toISOString(),
                'threshold_minutes' => config('monitoring.offline_threshold', 10),
            ]
        ]);
    }

    /**
     * Create supervisor issue alert
     */
    public static function createSupervisorAlert(Server $server, array $issues): self
    {
        $processNames = collect($issues)->pluck('process')->join(', ');

        return self::create([
            'server_id' => $server->id,
            'type' => 'supervisor_issue',
            'severity' => 'high',
            'title' => "Supervisor processes not running on '{$server->name}'",
            'message' => "The following processes are not running: {$processNames}",
            'data' => ['issues' => $issues]
        ]);
    }

    /**
     * Create queue issue alert
     */
    public static function createQueueAlert(Server $server, array $issues): self
    {
        $queueNames = collect($issues)->pluck('queue')->join(', ');

        return self::create([
            'server_id' => $server->id,
            'type' => 'queue_issue',
            'severity' => 'high',
            'title' => "Queue health issues on '{$server->name}'",
            'message' => "The following queues have issues: {$queueNames}",
            'data' => ['issues' => $issues]
        ]);
    }

    /**
     * Create cron issue alert
     */
    public static function createCronAlert(Server $server, string $message): self
    {
        return self::create([
            'server_id' => $server->id,
            'type' => 'cron_issue',
            'severity' => 'medium',
            'title' => "Cron monitoring issue on '{$server->name}'",
            'message' => $message,
        ]);
    }

    /**
     * Create backup failed alert
     */
    public static function createBackupFailedAlert(Server $server, array $backupData): self
    {
        return self::create([
            'server_id' => $server->id,
            'type' => 'backup_failed',
            'severity' => 'high',
            'title' => "Database backup failed on '{$server->name}'",
            'message' => "Database backup process failed. Check server logs for details.",
            'data' => $backupData
        ]);
    }

    /**
     * Scope for unresolved alerts
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope for resolved alerts
     */
    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    /**
     * Scope for critical alerts
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope for high severity alerts
     */
    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    /**
     * Scope for recent alerts
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
