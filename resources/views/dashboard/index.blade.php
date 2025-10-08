@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-3">
        <div class="card card-stat border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <h5 class="card-title text-muted mb-1">Total Servers</h5>
                    <h3 class="mb-0">{{ $statistics['total_servers'] ?? 0 }}</h3>
                </div>
                <div class="text-primary">
                    <i class="fas fa-server fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card card-stat border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <h5 class="card-title text-muted mb-1">Healthy Servers</h5>
                    <h3 class="mb-0 text-success">{{ $statistics['healthy_servers'] ?? 0 }}</h3>
                </div>
                <div class="text-success">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card card-stat border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <h5 class="card-title text-muted mb-1">Critical Alerts</h5>
                    <h3 class="mb-0 text-danger">{{ $alertStats['critical_count'] ?? 0 }}</h3>
                </div>
                <div class="text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card card-stat border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <h5 class="card-title text-muted mb-1">Offline Servers</h5>
                    <h3 class="mb-0 text-secondary">{{ $statistics['offline_servers'] ?? 0 }}</h3>
                </div>
                <div class="text-secondary">
                    <i class="fas fa-times-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Health Summary Chart -->
    <div class="col-md-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Server Health Overview
                </h5>
            </div>
            <div class="card-body">
                <canvas id="healthChart" class="metric-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bell text-warning me-2"></i>
                    Recent Alerts
                </h5>
                <a href="{{ route('dashboard.alerts') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentAlerts && $recentAlerts->count() > 0)
                    @foreach($recentAlerts as $alert)
                        <div class="alert-item p-3 border-bottom alert-{{ $alert->severity }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $alert->server->name }}</h6>
                                    <p class="mb-1 text-muted small">{{ $alert->message }}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $alert->created_at->diffForHumans() }}
                                    </small>
                                </div>
                                <span class="badge bg-{{ $alert->severity === 'critical' ? 'danger' : 'warning' }}">
                                    {{ ucfirst($alert->severity) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p class="mb-0">No recent alerts</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Server Status Summary -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-server text-info me-2"></i>
                    Server Status Summary
                </h5>
                <a href="{{ route('dashboard.servers') }}" class="btn btn-sm btn-outline-primary">View Details</a>
            </div>
            <div class="card-body">
                @if(isset($healthSummary) && count($healthSummary) > 0)
                    <div class="row">
                        @foreach($healthSummary->take(6) as $server)
                            <div class="col-md-4 col-lg-2 mb-3">
                                <div class="server-card card h-100">
                                    <div class="card-body text-center p-3">
                                        <i class="fas fa-server fa-2x mb-2 text-{{ $server->status === 'healthy' ? 'success' : ($server->status === 'offline' ? 'secondary' : 'warning') }}"></i>
                                        <h6 class="card-title mb-1">{{ $server->name }}</h6>
                                        <span class="badge status-badge status-{{ $server->status }}">
                                            {{ ucfirst($server->status) }}
                                        </span>
                                        @if($server->last_seen_at)
                                            <small class="d-block text-muted mt-2">
                                                {{ $server->last_seen_at->diffForHumans() }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-server fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No servers found</h5>
                        <p class="text-muted">Configure your first server to start monitoring.</p>
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
    // Health Chart
    const ctx = document.getElementById('healthChart').getContext('2d');
    const healthData = @json($healthSummary ?? []);

    const statusCounts = {
        healthy: 0,
        warning: 0,
        critical: 0,
        offline: 0
    };

    healthData.forEach(server => {
        if (statusCounts.hasOwnProperty(server.status)) {
            statusCounts[server.status]++;
        }
    });

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Healthy', 'Warning', 'Critical', 'Offline'],
            datasets: [{
                data: [
                    statusCounts.healthy,
                    statusCounts.warning,
                    statusCounts.critical,
                    statusCounts.offline
                ],
                backgroundColor: [
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6c757d'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.parsed / total) * 100);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
