@extends('layouts.app')

@section('title', $server->name . ' - Server Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.servers') }}">Servers</a></li>
                <li class="breadcrumb-item active">{{ $server->name }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center">
            <h2 class="h4 mb-0 me-3">{{ $server->name }}</h2>
            <span class="badge status-badge status-{{ $server->status ?? 'offline' }} fs-6">
                {{ ucfirst($server->status ?? 'offline') }}
            </span>
        </div>
    </div>
    @if(session('user.role') === 'admin')
    <div>
        <a href="{{ route('dashboard.servers.edit', $server) }}" class="btn btn-outline-secondary me-2">
            <i class="fas fa-edit me-1"></i>
            Edit Server
        </a>
        <button class="btn btn-primary" onclick="forceHealthCheck('{{ $server->id }}')">
            <i class="fas fa-sync me-1"></i>
            Force Check
        </button>
    </div>
    @endif
</div>

<!-- Server Info -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    Server Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Server Name:</dt>
                            <dd class="col-sm-8">{{ $server->name }}</dd>

                            <dt class="col-sm-4">IP Address:</dt>
                            <dd class="col-sm-8">{{ $server->ip_address ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
                                <span class="badge status-badge status-{{ $server->status ?? 'offline' }}">
                                    {{ ucfirst($server->status ?? 'offline') }}
                                </span>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Last Seen:</dt>
                            <dd class="col-sm-8">
                                @if($server->last_seen_at)
                                    <span class="local-time" data-utc="{{ $server->last_seen_at->toISOString() }}">
                                        {{ $server->last_seen_at->format('M d, Y H:i:s') }} UTC
                                    </span>
                                    <small class="text-muted d-block">{{ $server->last_seen_at->diffForHumans() }}</small>
                                @else
                                    Never
                                @endif
                            </dd>

                            <dt class="col-sm-4">Created:</dt>
                            <dd class="col-sm-8">
                                <span class="local-date" data-utc="{{ $server->created_at->toISOString() }}">
                                    {{ $server->created_at->format('M d, Y') }}
                                </span>
                            </dd>

                            <dt class="col-sm-4">Reports:</dt>
                            <dd class="col-sm-8">{{ $server->healthReports->count() }} total</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Health Timeline (24h)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="healthTimeline" style="height: 200px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Latest Health Report -->
@if($server->healthCheckReports->first())
    @php $latestReport = $server->healthCheckReports->first(); @endphp
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-stethoscope text-success me-2"></i>
                        Latest Health Report
                        <small class="text-muted local-time-relative" data-utc="{{ $latestReport->created_at->toISOString() }}">
                            {{ $latestReport->created_at->diffForHumans() }}
                        </small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Supervisor Status -->
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-cogs fa-lg text-primary me-2"></i>
                                    <h6 class="mb-0">Supervisor</h6>
                                    <span class="badge status-badge status-{{ $latestReport->supervisor_status }} ms-auto"
                                          title="Supervisor processes: {{ $latestReport->supervisor_status }}">
                                        {{ ucfirst($latestReport->supervisor_status) }}
                                    </span>
                                </div>
                                @if($latestReport->supervisor_data)
                                    @php $supervisorData = $latestReport->supervisor_data; @endphp
                                    <small class="text-muted">
                                        {{ $supervisorData['total_processes'] ?? 0 }} processes,
                                        {{ $supervisorData['running_processes'] ?? 0 }} running
                                    </small>
                                @endif
                            </div>
                        </div>

                        <!-- Cron Status -->
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-clock fa-lg text-warning me-2"></i>
                                    <h6 class="mb-0">Cron Jobs</h6>
                                    <span class="badge status-badge status-{{ $latestReport->cron_status }} ms-auto"
                                          title="Cron job monitoring: {{ $latestReport->cron_status }}">
                                        {{ ucfirst($latestReport->cron_status) }}
                                    </span>
                                </div>
                                @if($latestReport->cron_data)
                                    @php $cronData = $latestReport->cron_data; @endphp
                                    <small class="text-muted">
                                        {{ count($cronData['jobs'] ?? []) }} jobs configured
                                    </small>
                                @endif
                            </div>
                        </div>

                        <!-- Queue Status -->
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-list fa-lg text-info me-2"></i>
                                    <h6 class="mb-0">Queues</h6>
                                    <span class="badge status-badge status-{{ $latestReport->queue_status }} ms-auto"
                                          title="Queue health check: {{ $latestReport->queue_status }}">
                                        {{ ucfirst($latestReport->queue_status) }}
                                    </span>
                                </div>
                                @if($latestReport->queue_data)
                                    @php $queueData = $latestReport->queue_data; @endphp
                                    <small class="text-muted">
                                        {{ $queueData['ok_queues'] ?? 0 }}/{{ $queueData['total_queues'] ?? 0 }} ok
                                    </small>
                                    @if(in_array($latestReport->queue_status, ['timeout', 'error']) && isset($queueData['queues']) && is_array($queueData['queues']))
                                        @foreach($queueData['queues'] as $queueName => $queueResult)
                                            @if(isset($queueResult['status']) && in_array($queueResult['status'], ['timeout', 'error']))
                                                @php
                                                    $pendingCount = $queueResult['pending_jobs'] ?? $queueResult['queue_size'] ?? 0;
                                                    $statusIcon = $queueResult['status'] === 'timeout' ? 'hourglass-half' : 'exclamation-triangle';
                                                @endphp
                                                <small class="d-block mt-1 text-danger">
                                                    <i class="fas fa-{{ $statusIcon }} me-1"></i>
                                                    <strong>{{ $queueName }}:</strong> {{ ucfirst($queueResult['status']) }}
                                                    @if($pendingCount > 0)
                                                        ({{ $pendingCount }} pending)
                                                    @endif
                                                </small>
                                            @endif
                                        @endforeach
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Recent Reports and Alerts -->
<div class="row">
    <!-- Recent Health Reports -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history text-secondary me-2"></i>
                    Recent Health Reports
                </h5>
            </div>
            <div class="card-body p-0">
                @if($server->healthCheckReports->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Supervisor</th>
                                    <th>Cron</th>
                                    <th>Queue</th>
                                    <th>Overall</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($server->healthCheckReports as $report)
                                    <tr>
                                        <td>
                                            <small class="local-time-short" data-utc="{{ $report->created_at->toISOString() }}">
                                                {{ $report->created_at->format('M d H:i') }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-{{ $report->supervisor_status }}"
                                                  title="Supervisor {{ $report->supervisor_status }}">
                                                {{ ucfirst($report->supervisor_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-{{ $report->cron_status }}"
                                                  title="Cron jobs {{ $report->cron_status }}">
                                                {{ ucfirst($report->cron_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-{{ $report->queue_status }}"
                                                  title="Queue system {{ $report->queue_status }}">
                                                {{ ucfirst($report->queue_status) }}
                                            </span>
                                            @if(in_array($report->queue_status, ['timeout', 'error']) && $report->queue_data && isset($report->queue_data['queues']) && is_array($report->queue_data['queues']))
                                                @foreach($report->queue_data['queues'] as $queueName => $queueResult)
                                                    @if(isset($queueResult['status']) && in_array($queueResult['status'], ['timeout', 'error']))
                                                        @php
                                                            $pendingCount = $queueResult['pending_jobs'] ?? $queueResult['queue_size'] ?? 0;
                                                            $statusIcon = $queueResult['status'] === 'timeout' ? 'hourglass-half' : 'exclamation-triangle';
                                                        @endphp
                                                        <small class="d-block text-muted mt-1">
                                                            <i class="fas fa-{{ $statusIcon }}"></i>
                                                            <strong>{{ $queueName }}:</strong>
                                                            @if($pendingCount > 0)
                                                                {{ $pendingCount }} pending
                                                            @else
                                                                {{ ucfirst($queueResult['status']) }}
                                                            @endif
                                                        </small>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-{{ $report->overall_status }}"
                                                  title="Overall system {{ $report->overall_status }}">
                                                {{ ucfirst($report->overall_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No health reports available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bell text-warning me-2"></i>
                    Recent Alerts
                </h5>
            </div>
            <div class="card-body p-0">
                @if($server->alerts->count() > 0)
                    @foreach($server->alerts as $alert)
                        <div class="alert-item p-3 border-bottom alert-{{ $alert->severity }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $alert->type }}</h6>
                                    <p class="mb-1 text-muted small">{{ $alert->message }}</p>
                                    <small class="text-muted local-time-relative" data-utc="{{ $alert->created_at->toISOString() }}">
                                        {{ $alert->created_at->diffForHumans() }}
                                    </small>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="badge bg-{{ $alert->severity === 'critical' ? 'danger' : 'warning' }} mb-1">
                                        {{ ucfirst($alert->severity) }}
                                    </span>
                                    @if(!$alert->resolved_at)
                                        <button class="btn btn-sm btn-outline-success"
                                                onclick="resolveAlert('{{ $alert->id }}')">
                                            Resolve
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p class="mb-0">No alerts</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Backup Files -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-archive text-primary me-2"></i>
                    Backup Files
                </h5>
            </div>
            <div class="card-body p-0">
                @if($server->backupReports->count() > 0)
                    @php
                        $allBackupFiles = collect();
                        foreach($server->backupReports as $report) {
                            if (isset($report->backup_data)) {
                                $backupData = $report->backup_data;

                                // Extract filename from s3_path or file_path
                                $filename = 'Unknown';
                                if (isset($backupData['s3_path'])) {
                                    $filename = basename($backupData['s3_path']);
                                } elseif (isset($backupData['file_path'])) {
                                    $filename = basename($backupData['file_path']);
                                }

                                $allBackupFiles->push([
                                    'filename' => $filename,
                                    'size' => $backupData['file_size'] ?? 0,
                                    'created_at' => $report->created_at,
                                    'download_url' => $backupData['s3_url'] ?? null,
                                    'type' => $backupData['uploaded'] ? 'S3 Backup' : 'Local Backup',
                                    'duration' => $backupData['duration'] ?? 0,
                                    'bucket' => $backupData['s3_bucket'] ?? null
                                ]);
                            }
                        }
                        $allBackupFiles = $allBackupFiles->sortByDesc('created_at');
                    @endphp

                    @if($allBackupFiles->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Filename</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Duration</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allBackupFiles->take(20) as $file)
                                        <tr>
                                            <td>
                                                <i class="fas fa-{{ $file['download_url'] ? 'cloud' : 'file-archive' }} text-primary me-2"></i>
                                                <strong>{{ $file['filename'] }}</strong>
                                                @if($file['bucket'])
                                                    <br><small class="text-muted">{{ $file['bucket'] }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $file['download_url'] ? 'success' : 'secondary' }}">
                                                    {{ $file['type'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>
                                                    {{ $file['size'] > 0 ? number_format($file['size'] / 1024 / 1024, 2) . ' MB' : 'Unknown' }}
                                                </strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $file['duration'] > 0 ? number_format($file['duration'], 2) . 's' : '-' }}
                                                </small>
                                            </td>
                                            <td>
                                                <small class="local-time-short" data-utc="{{ $file['created_at']->toISOString() }}">
                                                    {{ $file['created_at']->format('M d, H:i') }}
                                                </small>
                                                <div class="local-time-relative text-muted small" data-utc="{{ $file['created_at']->toISOString() }}">
                                                    {{ $file['created_at']->diffForHumans() }}
                                                </div>
                                            </td>
                                            <td>
                                                @if($file['download_url'])
                                                    <a href="{{ $file['download_url'] }}"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Download {{ $file['filename'] }}"
                                                       target="_blank">
                                                        <i class="fas fa-download me-1"></i>
                                                        Download
                                                    </a>
                                                @else
                                                    <span class="text-muted small">Local only</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($allBackupFiles->count() > 20)
                            <div class="card-footer text-center">
                                <small class="text-muted">
                                    Showing 20 of {{ $allBackupFiles->count() }} backup files
                                </small>
                            </div>
                        @endif
                    @else
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-archive fa-2x mb-2"></i>
                            <p class="mb-0">No backup files found</p>
                        </div>
                    @endif
                @else
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-archive fa-2x mb-2"></i>
                        <p class="mb-0">No backup reports available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Health Timeline Chart
    const ctx = document.getElementById('healthTimeline').getContext('2d');
    const timelineData = @json($healthTimeline ?? []);

    // Convert status to score and format timeline data
    const processedData = timelineData.map(item => {
        // Convert status to health score
        let score;
        switch(item.status) {
            case 'ok':
                score = 100;
                break;
            case 'warning':
                score = 60;
                break;
            case 'error':
                score = 20;
                break;
            default:
                score = 0;
        }

        // Parse timestamp and convert to local timezone
        const utcDate = new Date(item.timestamp);
        const localDate = new Date(utcDate.getTime() + (utcDate.getTimezoneOffset() * 60000));

        return {
            time: new Date(item.timestamp), // Keep original for proper timezone handling
            score: score,
            status: item.status
        };
    });

    // If no data, show placeholder
    if (processedData.length === 0) {
        // Generate placeholder data for the last 24 hours
        const now = new Date();
        for (let i = 23; i >= 0; i--) {
            const time = new Date(now.getTime() - (i * 60 * 60 * 1000));
            processedData.push({
                time: time,
                score: 0,
                status: 'offline'
            });
        }
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: processedData.map(item => {
                const date = item.time;
                return date.getHours().toString().padStart(2, '0') + ':' +
                       date.getMinutes().toString().padStart(2, '0');
            }),
            datasets: [{
                label: 'Health Score',
                data: processedData.map(item => item.score),
                borderColor: function(context) {
                    const status = processedData[context.dataIndex]?.status;
                    switch(status) {
                        case 'ok': return '#28a745';
                        case 'warning': return '#ffc107';
                        case 'error': return '#dc3545';
                        default: return '#6c757d';
                    }
                },
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: function(context) {
                    const status = processedData[context.dataIndex]?.status;
                    switch(status) {
                        case 'ok': return '#28a745';
                        case 'warning': return '#ffc107';
                        case 'error': return '#dc3545';
                        default: return '#6c757d';
                    }
                },
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    grid: {
                        display: true
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const dataPoint = processedData[context.dataIndex];
                            return `Health: ${context.parsed.y}% (${dataPoint.status})`;
                        },
                        title: function(context) {
                            const dataPoint = processedData[context[0].dataIndex];
                            // Format time according to browser's locale and timezone
                            return dataPoint.time.toLocaleString('en-US', {
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: false
                            });
                        }
                    }
                }
            }
        }
    });

    // Convert UTC times to local timezone
    convertTimesToLocal();
});

function convertTimesToLocal() {
    // Convert local-time elements (full datetime)
    document.querySelectorAll('.local-time').forEach(function(element) {
        const utcTime = element.getAttribute('data-utc');
        if (utcTime) {
            const localDate = new Date(utcTime);
            element.textContent = localDate.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
        }
    });

    // Convert local-date elements (date only)
    document.querySelectorAll('.local-date').forEach(function(element) {
        const utcTime = element.getAttribute('data-utc');
        if (utcTime) {
            const localDate = new Date(utcTime);
            element.textContent = localDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }
    });

    // Convert local-time-short elements (short format for tables)
    document.querySelectorAll('.local-time-short').forEach(function(element) {
        const utcTime = element.getAttribute('data-utc');
        if (utcTime) {
            const localDate = new Date(utcTime);
            element.textContent = localDate.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit'
            }) + ' ' + localDate.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        }
    });

    // Update relative time elements
    document.querySelectorAll('.local-time-relative').forEach(function(element) {
        const utcTime = element.getAttribute('data-utc');
        if (utcTime) {
            const date = new Date(utcTime);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);

            let relativeText;
            if (diffInSeconds < 60) {
                relativeText = diffInSeconds + ' seconds ago';
            } else if (diffInSeconds < 3600) {
                relativeText = Math.floor(diffInSeconds / 60) + ' minutes ago';
            } else if (diffInSeconds < 86400) {
                relativeText = Math.floor(diffInSeconds / 3600) + ' hours ago';
            } else {
                relativeText = Math.floor(diffInSeconds / 86400) + ' days ago';
            }

            element.textContent = relativeText;
        }
    });

    // Update relative times every minute
    setTimeout(function() {
        document.querySelectorAll('.local-time-relative').forEach(function(element) {
            const utcTime = element.getAttribute('data-utc');
            if (utcTime) {
                const date = new Date(utcTime);
                const now = new Date();
                const diffInSeconds = Math.floor((now - date) / 1000);

                let relativeText;
                if (diffInSeconds < 60) {
                    relativeText = diffInSeconds + ' seconds ago';
                } else if (diffInSeconds < 3600) {
                    relativeText = Math.floor(diffInSeconds / 60) + ' minutes ago';
                } else if (diffInSeconds < 86400) {
                    relativeText = Math.floor(diffInSeconds / 3600) + ' hours ago';
                } else {
                    relativeText = Math.floor(diffInSeconds / 86400) + ' days ago';
                }

                element.textContent = relativeText;
            }
        });
    }, 60000); // Update every minute
}

function resolveAlert(alertId) {
    if (confirm('Are you sure you want to resolve this alert?')) {
        fetch(`/dashboard/alerts/${alertId}/resolve`, {
            method: 'POST',
            body: JSON.stringify({ json: true }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to resolve alert');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to resolve alert');
        });
    }
}

function forceHealthCheck(serverId) {
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;

    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Checking...';

    fetch(`/dashboard/servers/${serverId}/force-check`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            button.innerHTML = '<i class="fas fa-check me-1"></i>Triggered!';
            button.className = 'btn btn-success';

            // Show notification
            alert(data.message);

            // Reset button after 3 seconds
            setTimeout(() => {
                button.disabled = false;
                button.innerHTML = originalContent;
                button.className = 'btn btn-primary';
            }, 3000);

            // Optionally reload page after 5 seconds to show new data
            setTimeout(() => {
                location.reload();
            }, 5000);
        } else {
            alert('Error: ' + data.message);
            button.disabled = false;
            button.innerHTML = originalContent;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to trigger health check. Please try again.');
        button.disabled = false;
        button.innerHTML = originalContent;
    });
}
</script>
@endpush
