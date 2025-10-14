@extends('layouts.app')

@section('title', 'Servers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1">Server Management</h2>
        <p class="text-muted mb-0">Monitor and manage all your servers</p>
    </div>
    <div>
        <a href="{{ route('dashboard.servers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Add Server
        </a>
    </div>
</div>

@if($servers && $servers->count() > 0)
    <div class="row">
        @foreach($servers as $server)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card server-card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-server text-primary me-2"></i>
                            {{ $server->name }}
                        </h5>
                        <span class="badge status-badge status-{{ $server->status ?? 'offline' }}">
                            {{ ucfirst($server->status ?? 'offline') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">IP Address</small>
                                <strong>{{ $server->ip_address ?? 'N/A' }}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Last Seen</small>
                                <strong>
                                    @if($server->last_seen_at)
                                        {{ $server->last_seen_at->diffForHumans() }}
                                    @else
                                        Never
                                    @endif
                                </strong>
                            </div>
                        </div>

                        @if($server->latestHealthReport)
                            <div class="mb-3">
                                <small class="text-muted d-block mb-2">Latest Health Report</small>
                                <div class="row">
                                    <div class="col-4 text-center">
                                        <div class="small text-muted">Supervisor</div>
                                        <i class="fas fa-{{ $server->latestHealthReport->supervisor_status === 'ok' ? 'check-circle text-success' : 'times-circle text-danger' }}"></i>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="small text-muted">Cron</div>
                                        <i class="fas fa-{{ $server->latestHealthReport->cron_status === 'ok' ? 'check-circle text-success' : 'times-circle text-danger' }}"></i>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="small text-muted">Queue</div>
                                        <i class="fas fa-{{ $server->latestHealthReport->queue_status === 'ok' ? 'check-circle text-success' : 'times-circle text-danger' }}"></i>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($server->unresolvedAlerts && $server->unresolvedAlerts->count() > 0)
                            <div class="alert alert-warning py-2 mb-3">
                                <small>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $server->unresolvedAlerts->count() }} unresolved alert(s)
                                </small>
                            </div>
                        @endif

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('dashboard.server-detail', $server) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>
                                View Details
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('dashboard.servers.edit', $server) }}">
                                            <i class="fas fa-edit me-2"></i>
                                            Edit
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger"
                                           href="#"
                                           onclick="deleteServer('{{ $server->id }}', '{{ $server->name }}'); return false;">
                                            <i class="fas fa-trash me-2"></i>
                                            Delete
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-5">
        <i class="fas fa-server fa-4x text-muted mb-3"></i>
        <h4 class="text-muted mb-3">No servers found</h4>
        <p class="text-muted mb-4">Get started by adding your first server to monitor.</p>
        <a href="{{ route('dashboard.servers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Add Your First Server
        </a>
    </div>
@endif

@push('scripts')
<script>
function deleteServer(serverId, serverName) {
    // Show confirmation dialog
    if (!confirm(`Are you sure you want to delete server "${serverName}"? This will archive the server and all its data. You can restore it later from the Archives if needed.`)) {
        return;
    }

    // Show loading state
    const deleteButton = event.target.closest('a');
    const originalContent = deleteButton.innerHTML;
    deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
    deleteButton.classList.add('disabled');

    // Make AJAX request
    fetch(`/dashboard/servers/${serverId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            // Insert alert at top of page
            const container = document.querySelector('.container-fluid');
            container.insertBefore(alert, container.firstChild);

            // Remove server card with animation
            const serverCard = deleteButton.closest('.col-md-6');
            serverCard.style.transition = 'all 0.3s ease';
            serverCard.style.opacity = '0';
            serverCard.style.transform = 'translateY(-20px)';

            setTimeout(() => {
                serverCard.remove();

                // Check if no servers left
                const remainingServers = document.querySelectorAll('.col-md-6').length;
                if (remainingServers === 0) {
                    location.reload(); // Reload to show "No servers found" message
                }
            }, 300);
        } else {
            throw new Error(data.message || 'Failed to delete server');
        }
    })
    .catch(error => {
        console.error('Error:', error);

        // Show error message
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            Failed to delete server: ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insert alert at top of page
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alert, container.firstChild);

        // Restore button state
        deleteButton.innerHTML = originalContent;
        deleteButton.classList.remove('disabled');
    });
}
</script>
@endpush

@endsection
