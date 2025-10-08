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
                            <pre class="bg-light p-3 rounded" style="font-size: 0.85rem;"><code>HEALTH_MONITOR_ENABLED=true
HEALTH_MONITOR_URL={{ url('/api/health-report') }}
HEALTH_MONITOR_API_KEY={{ $server->api_key }}
HEALTH_MONITOR_SERVER_NAME={{ $server->name }}

# Optional: Backup notification URL (if different)
HEALTH_MONITOR_BACKUP_NOTIFICATION_URL={{ url('/api/backup-notification') }}

# Database backup settings (if needed)
DB_BACKUP_ENABLED=true
DB_BACKUP_S3_BUCKET=your-backup-bucket
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key</code></pre>
                            <button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2" 
                                    onclick="copyEnvConfig()">
                                <i class="fas fa-copy"></i>
                            </button>
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
    const envConfig = `HEALTH_MONITOR_ENABLED=true
HEALTH_MONITOR_URL={{ url('/api/health-report') }}
HEALTH_MONITOR_API_KEY={{ $server->api_key }}
HEALTH_MONITOR_SERVER_NAME={{ $server->name }}

# Optional: Backup notification URL (if different)
HEALTH_MONITOR_BACKUP_NOTIFICATION_URL={{ url('/api/backup-notification') }}

# Database backup settings (if needed)
DB_BACKUP_ENABLED=true
DB_BACKUP_S3_BUCKET=your-backup-bucket
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key`;

    navigator.clipboard.writeText(envConfig);
    
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