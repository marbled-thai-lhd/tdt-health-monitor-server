<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'base_url',
        'api_key',
        'status',
        'last_seen_at',
        'metadata',
        'description',
        'environment',
        'is_active',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get health reports for this server
     */
    public function healthReports(): HasMany
    {
        return $this->hasMany(HealthReport::class);
    }

    /**
     * Get alerts for this server
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get the latest health report relationship
     */
    public function latestHealthReport()
    {
        return $this->hasOne(HealthReport::class)
                   ->where('report_type', 'health_check')
                   ->latest('reported_at');
    }

    /**
     * Get unresolved alerts
     */
    public function unresolvedAlerts(): HasMany
    {
        return $this->alerts()->where('resolved', false);
    }

    /**
     * Check if server is considered offline
     */
    public function isOffline(): bool
    {
        if (!$this->last_seen_at) {
            return true;
        }

        $threshold = config('monitoring.offline_threshold', 10); // minutes
        return $this->last_seen_at->diffInMinutes(now()) > $threshold;
    }

    /**
     * Update server status based on latest report
     */
    public function updateStatus(string $overallStatus): void
    {
        $statusMap = [
            'healthy' => 'healthy',  // healthy -> healthy
            'warning' => 'warning',  // warning -> warning  
            'error' => 'critical',   // error -> critical
        ];

        $this->update([
            'status' => $statusMap[$overallStatus] ?? 'critical',
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Scope for active servers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'healthy');
    }

    /**
     * Scope for servers with issues
     */
    public function scopeWithIssues($query)
    {
        return $query->whereIn('status', ['warning', 'critical']);
    }

    /**
     * Scope for offline servers
     */
    public function scopeOffline($query)
    {
        $threshold = config('monitoring.offline_threshold', 10);
        return $query->where(function ($query) use ($threshold) {
            $query->whereNull('last_seen_at')
                  ->orWhere('last_seen_at', '<', now()->subMinutes($threshold));
        });
    }
}
