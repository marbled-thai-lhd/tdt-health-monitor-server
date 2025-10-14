@extends('layouts.app')

@section('title', 'Server Setup Instructions')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <!-- Success Message -->
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">Server Created Successfully!</h5>
                    <p class="mb-0">{{ $server->name }} has been added to your monitoring system.</p>
                </div>
            </div>
        </div>

        <!-- Server Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-server text-primary me-2"></i>
                    Server Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Server Name:</dt>
                            <dd class="col-sm-8"><code>{{ $server->name }}</code></dd>

                            <dt class="col-sm-4">Environment:</dt>
                            <dd class="col-sm-8">
                                @if($server->environment)
                                    <span class="badge bg-{{ $server->environment === 'production' ? 'danger' : ($server->environment === 'staging' ? 'warning' : 'info') }}">
                                        {{ ucfirst($server->environment) }}
                                    </span>
                                @else
                                    <span class="text-muted">Not specified</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">API Key:</dt>
                            <dd class="col-sm-8">
                                <div class="input-group">
                                    <input type="password" class="form-control form-control-sm" id="apiKeyField" value="{{ $server->api_key }}" readonly>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="toggleApiKey()">
                                        <i class="fas fa-eye" id="eyeIcon"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="copyApiKey()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </dd>

                            <dt class="col-sm-4">Monitoring URL:</dt>
                            <dd class="col-sm-8"><code>{{ url('/api/health-report') }}</code></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup Instructions -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-cogs text-success me-2"></i>
                    Setup Instructions
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Follow these steps on your target server ({{ $server->name }}) to enable monitoring.
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <span class="badge bg-primary me-2">1</span>
                            Install Package
                        </h6>
                        <p>Install the health monitor package via Composer:</p>
                        <div class="position-relative">
                            <pre class="bg-light p-3 rounded"><code>composer require tdt/health-monitor</code></pre>
                            <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2"
                                    onclick="copyToClipboard('composer require tdt/health-monitor')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>

                        <h6 class="text-primary mb-3 mt-4">
                            <span class="badge bg-primary me-2">2</span>
                            Publish Configuration
                        </h6>
                        <p>Publish the package configuration:</p>
                        <div class="position-relative">
                            <pre class="bg-light p-3 rounded"><code>php artisan vendor:publish --provider="TDT\HealthMonitor\HealthMonitorServiceProvider" --tag="health-monitor-config"</code></pre>
                            <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2"
                                    onclick="copyToClipboard('php artisan vendor:publish --provider=&quot;TDT\\HealthMonitor\\HealthMonitorServiceProvider&quot; --tag=&quot;health-monitor-config&quot;')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <span class="badge bg-primary me-2">3</span>
                            Configure Environment
                        </h6>
                        <p>Add these variables to your <code>.env</code> file:</p>
                        <div class="position-relative">
                            <pre class="bg-light p-3 rounded" style="font-size: 0.8rem;"><code># Health Monitor Basic Configuration
HEALTH_MONITOR_ENABLED=true
HEALTH_MONITOR_URL={{ url('/api/health-report') }}
HEALTH_MONITOR_API_KEY={{ $server->api_key }}
HEALTH_MONITOR_SERVER_NAME={{ $server->name }}
HEALTH_MONITOR_INTERVAL=5
HEALTH_MONITOR_TIMEOUT=30
HEALTH_MONITOR_RETRY_ATTEMPTS=3

# Optional: Separate URL for backup notifications
# If not set, backup notifications will use the main HEALTH_MONITOR_URL
HEALTH_MONITOR_BACKUP_URL={{ url('/api/backup-notification') }}

# System Monitoring Configuration
CRON_USER={{ config('health-monitor.cron.user', 'ec2-user') }}
SUPERVISOR_CONFIG_PATH=/etc/supervisor/conf.d
SUPERVISOR_SOCKET_PATH=/var/run/supervisor.sock

# Queue Health Check Configuration
QUEUE_HEALTH_CHECK_ENABLED=true
QUEUE_HEALTH_CHECK_QUEUES=default,emails,notifications
QUEUE_HEALTH_CHECK_TIMEOUT=30

# Database Backup Configuration
DB_BACKUP_ENABLED=true
DB_BACKUP_SCHEDULE="0 2 * * *"
DB_BACKUP_RETENTION_DAYS=30

# AWS S3 Configuration for Backups
DB_BACKUP_S3_BUCKET=your-backup-bucket
AWS_DEFAULT_REGION=ap-northeast-1
DB_BACKUP_S3_PATH=database-backups
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key

# Client-Side Logging Configuration
HEALTH_MONITOR_LOGGING_ENABLED=true

# Health Check Logging
HEALTH_LOG_ENABLED=true
HEALTH_LOG_DAILY=true
HEALTH_LOG_DAILY_DAYS=30
HEALTH_LOG_LEVEL=info

# Database Backup Logging
BACKUP_LOG_ENABLED=true
BACKUP_LOG_DAILY=true
BACKUP_LOG_DAILY_DAYS=30
BACKUP_LOG_LEVEL=info</code></pre>
                            <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2"
                                    onclick="copyEnvConfig()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>

                        <div class="alert alert-info mt-3">
                            <strong><i class="fas fa-info-circle me-1"></i>Configuration Notes:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>CRON_USER:</strong> Set to your system user (check with <code>whoami</code>)</li>
                                <li><strong>SUPERVISOR_CONFIG_PATH:</strong> Adjust based on your system's supervisor configuration</li>
                                <li><strong>SUPERVISOR_SOCKET_PATH:</strong> Auto-detected if not specified</li>
                                <li><strong>QUEUE_HEALTH_CHECK_QUEUES:</strong> List queues to monitor (comma-separated)</li>
                                <li><strong>AWS_DEFAULT_REGION:</strong> Used for both S3 backup storage and other AWS services</li>
                                <li><strong>Client Logging:</strong> Creates daily rotating log files in <code>storage/logs/</code> directory</li>
                                <li><strong>Log Retention:</strong> Set different retention periods for health checks vs backups</li>
                            </ul>
                        </div>

                        <h6 class="text-primary mb-3 mt-4">
                            <span class="badge bg-primary me-2">4</span>
                            Setup Scheduler
                        </h6>
                        <p>Run the setup command:</p>
                        <div class="position-relative">
                            <pre class="bg-light p-3 rounded"><code>php artisan health:setup-schedule</code></pre>
                            <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2"
                                    onclick="copyToClipboard('php artisan health:setup-schedule')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>

                        <div class="alert alert-info mt-3">
                            <strong><i class="fas fa-shield-alt me-1"></i>Security Note:</strong>
                            <p class="mb-0 mt-2">All communication between your server and the monitoring dashboard uses HMAC-SHA256 authentication with timestamp validation to prevent replay attacks. The API key is never sent in plain text.</p>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="text-success mb-3">
                    <span class="badge bg-success me-2">5</span>
                    Test Setup
                </h6>
                <p>Verify the setup by running a manual health check:</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="position-relative">
                            <pre class="bg-light p-3 rounded"><code>php artisan health:check --force</code></pre>
                            <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2"
                                    onclick="copyToClipboard('php artisan health:check --force')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info py-2">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                You should see a successful health report sent to the monitoring server.
                            </small>
                        </div>
                    </div>
                </div>

                <h6 class="text-warning mb-3 mt-4">
                    <span class="badge bg-warning me-2">6</span>
                    Verify Logging (Optional)
                </h6>
                <p>Check that client-side logging is working correctly:</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="position-relative">
                            <pre class="bg-light p-3 rounded"><code># Check health check logs
tail -f storage/logs/health-check-$(date +%Y-%m-%d).log

# Check database backup logs
tail -f storage/logs/database-backup-$(date +%Y-%m-%d).log

# List all health monitor log files
ls -la storage/logs/*health* storage/logs/*backup*</code></pre>
                            <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2"
                                    onclick="copyLogCommands()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-success py-2">
                            <small>
                                <i class="fas fa-chart-line me-1"></i>
                                <strong>Daily logs include:</strong> Execution time, command options, component status, success/error details with JSON formatting.
                            </small>
                        </div>

                        <div class="alert alert-info py-2 mt-2">
                            <small>
                                <i class="fas fa-clock me-1"></i>
                                <strong>Log Rotation:</strong> Files are created daily and automatically cleaned up based on retention settings (30 days for health checks, configurable for backups).
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center">
            <a href="{{ route('dashboard.server-detail', $server) }}" class="btn btn-primary me-2">
                <i class="fas fa-eye me-1"></i>
                View Server Details
            </a>
            <a href="{{ route('dashboard.servers') }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-list me-1"></i>
                Back to Servers
            </a>
            <a href="{{ route('dashboard.servers.create') }}" class="btn btn-outline-primary">
                <i class="fas fa-plus me-1"></i>
                Add Another Server
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleApiKey() {
    const field = document.getElementById('apiKeyField');
    const icon = document.getElementById('eyeIcon');

    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function copyApiKey() {
    const field = document.getElementById('apiKeyField');
    field.select();
    field.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(field.value);

    // Show success feedback
    const button = event.target.closest('button');
    const originalIcon = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(() => {
        button.innerHTML = originalIcon;
    }, 2000);
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);

    // Show success feedback
    const button = event.target.closest('button');
    const originalIcon = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(() => {
        button.innerHTML = originalIcon;
    }, 2000);
}

function copyEnvConfig() {
    const envConfig = `# Health Monitor Basic Configuration
HEALTH_MONITOR_ENABLED=true
HEALTH_MONITOR_URL={{ url('/api/health-report') }}
HEALTH_MONITOR_API_KEY={{ $server->api_key }}
HEALTH_MONITOR_SERVER_NAME={{ $server->name }}
HEALTH_MONITOR_INTERVAL=5
HEALTH_MONITOR_TIMEOUT=30
HEALTH_MONITOR_RETRY_ATTEMPTS=3

# Optional: Separate URL for backup notifications
# If not set, backup notifications will use the main HEALTH_MONITOR_URL
HEALTH_MONITOR_BACKUP_URL={{ url('/api/backup-notification') }}

# System Monitoring Configuration
CRON_USER={{ config('health-monitor.cron.user', 'ec2-user') }}
SUPERVISOR_CONFIG_PATH=/etc/supervisor/conf.d
SUPERVISOR_SOCKET_PATH=/var/run/supervisor.sock

# Queue Health Check Configuration
QUEUE_HEALTH_CHECK_ENABLED=true
QUEUE_HEALTH_CHECK_QUEUES=default,emails,notifications
QUEUE_HEALTH_CHECK_TIMEOUT=30

# Database Backup Configuration
DB_BACKUP_ENABLED=true
DB_BACKUP_SCHEDULE="0 2 * * *"
DB_BACKUP_RETENTION_DAYS=30

# AWS S3 Configuration for Backups
DB_BACKUP_S3_BUCKET=your-backup-bucket
AWS_DEFAULT_REGION=ap-northeast-1
DB_BACKUP_S3_PATH=database-backups
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key

# Client-Side Logging Configuration
HEALTH_MONITOR_LOGGING_ENABLED=true

# Health Check Logging
HEALTH_LOG_ENABLED=true
HEALTH_LOG_DAILY=true
HEALTH_LOG_DAILY_DAYS=30
HEALTH_LOG_LEVEL=info

# Database Backup Logging
BACKUP_LOG_ENABLED=true
BACKUP_LOG_DAILY=true
BACKUP_LOG_DAILY_DAYS=30
BACKUP_LOG_LEVEL=info`;

    navigator.clipboard.writeText(envConfig);

    // Show success feedback
    const button = event.target.closest('button');
    const originalIcon = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(() => {
        button.innerHTML = originalIcon;
    }, 2000);
}

function copyLogCommands() {
    const logCommands = `# Check health check logs
tail -f storage/logs/health-check-$(date +%Y-%m-%d).log

# Check database backup logs
tail -f storage/logs/database-backup-$(date +%Y-%m-%d).log

# List all health monitor log files
ls -la storage/logs/*health* storage/logs/*backup*`;

    navigator.clipboard.writeText(logCommands);

    // Show success feedback
    const button = event.target.closest('button');
    const originalIcon = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(() => {
        button.innerHTML = originalIcon;
    }, 2000);
}
</script>
@endpush
