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
    <div>
        <button class="btn btn-outline-secondary me-2">
            <i class="fas fa-edit me-1"></i>
            Edit Server
        </button>
        <button class="btn btn-primary">
            <i class="fas fa-sync me-1"></i>
            Force Check
        </button>
    </div>
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
                                    {{ $server->last_seen_at->format('M d, Y H:i:s') }}
                                    <small class="text-muted d-block">{{ $server->last_seen_at->diffForHumans() }}</small>
                                @else
                                    Never
                                @endif
                            </dd>

                            <dt class="col-sm-4">Created:</dt>
                            <dd class="col-sm-8">{{ $server->created_at->format('M d, Y') }}</dd>

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
@if($server->healthReports->first())
    @php $latestReport = $server->healthReports->first(); @endphp
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-stethoscope text-success me-2"></i>
                        Latest Health Report
                        <small class="text-muted">{{ $latestReport->created_at->diffForHumans() }}</small>
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
                                    <span class="badge status-badge status-{{ $latestReport->supervisor_status }} ms-auto">
                                        {{ ucfirst($latestReport->supervisor_status) }}
                                    </span>
                                </div>
                                @if($latestReport->supervisor_data)
                                    @php $supervisorData = json_decode($latestReport->supervisor_data, true); @endphp
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
                                    <span class="badge status-badge status-{{ $latestReport->cron_status }} ms-auto">
                                        {{ ucfirst($latestReport->cron_status) }}
                                    </span>
                                </div>
                                @if($latestReport->cron_data)
                                    @php $cronData = json_decode($latestReport->cron_data, true); @endphp
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
                                    <span class="badge status-badge status-{{ $latestReport->queue_status }} ms-auto">
                                        {{ ucfirst($latestReport->queue_status) }}
                                    </span>
                                </div>
                                @if($latestReport->queue_data)
                                    @php $queueData = json_decode($latestReport->queue_data, true); @endphp
                                    <small class="text-muted">
                                        {{ $queueData['healthy_queues'] ?? 0 }}/{{ $queueData['total_queues'] ?? 0 }} healthy
                                    </small>
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
                @if($server->healthReports->count() > 0)
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
                                @foreach($server->healthReports as $report)
                                    <tr>
                                        <td>
                                            <small>{{ $report->created_at->format('M d H:i') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-{{ $report->supervisor_status }}">
                                                {{ ucfirst($report->supervisor_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-{{ $report->cron_status }}">
                                                {{ ucfirst($report->cron_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-{{ $report->queue_status }}">
                                                {{ ucfirst($report->queue_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-{{ $report->overall_status }}">
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
                                    <small class="text-muted">
                                        {{ $alert->created_at->diffForHumans() }}
                                    </small>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="badge bg-{{ $alert->severity === 'critical' ? 'danger' : 'warning' }} mb-1">
                                        {{ ucfirst($alert->severity) }}
                                    </span>
                                    @if(!$alert->resolved_at)
                                        <button class="btn btn-sm btn-outline-success"
                                                onclick="resolveAlert({{ $alert->id }})">
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Health Timeline Chart
    const ctx = document.getElementById('healthTimeline').getContext('2d');
    const timelineData = @json($healthTimeline ?? []);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: timelineData.map(item => {
                const date = new Date(item.time);
                return date.getHours().toString().padStart(2, '0') + ':' +
                       date.getMinutes().toString().padStart(2, '0');
            }),
            datasets: [{
                label: 'Health Score',
                data: timelineData.map(item => item.score),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                tension: 0.4
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
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Health: ' + context.parsed.y + '%';
                        }
                    }
                }
            }
        }
    });
});

function resolveAlert(alertId) {
    if (confirm('Are you sure you want to resolve this alert?')) {
        fetch(`/dashboard/alerts/${alertId}/resolve`, {
            method: 'POST',
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
</script>
@endpush
