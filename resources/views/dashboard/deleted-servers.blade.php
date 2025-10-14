@extends('layouts.app')

@section('title', 'Server Archives')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="fas fa-archive text-muted me-2"></i>
        Server Archives
    </h2>
    <div>
        <a href="{{ route('dashboard.servers') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>
            Back to Active Servers
        </a>
    </div>
</div>

@if($deletedServers->count() > 0)
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>{{ $deletedServers->count() }}</strong> archived server(s) found. These servers have been deleted but can be restored. All related data (health reports, alerts) is preserved.
    </div>

    <div class="row">
        @foreach($deletedServers as $server)
            <div class="col-md-6 mb-4">
                <div class="card border-warning h-100">
                    <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-server text-warning me-2"></i>
                            <div>
                                <h6 class="card-title mb-0">{{ $server->name }}</h6>
                                <small class="text-muted">{{ $server->ip_address ?? 'No IP set' }}</small>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item text-success" href="#"
                                       onclick="restoreServer('{{ $server->id }}', '{{ $server->name }}'); return false;">
                                        <i class="fas fa-undo me-2"></i>
                                        Restore
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#"
                                       onclick="permanentDeleteServer('{{ $server->id }}', '{{ $server->name }}'); return false;">
                                        <i class="fas fa-trash-alt me-2"></i>
                                        Delete Permanently
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border-end">
                                    <div class="h5 mb-0 text-muted">
                                        {{ $server->healthReports()->count() }}
                                    </div>
                                    <small class="text-muted">Health Reports</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border-end">
                                    <div class="h5 mb-0 text-muted">
                                        {{ $server->alerts()->count() }}
                                    </div>
                                    <small class="text-muted">Alerts</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="h5 mb-0 text-warning">
                                    <i class="fas fa-archive"></i>
                                </div>
                                <small class="text-muted">Archived</small>
                            </div>
                        </div>

                        @if($server->description)
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    {{ $server->description }}
                                </small>
                            </div>
                        @endif

                        @if($server->environment)
                            <div class="mt-2">
                                <span class="badge bg-{{ $server->environment === 'production' ? 'danger' : ($server->environment === 'staging' ? 'warning' : 'info') }}">
                                    {{ ucfirst($server->environment) }}
                                </span>
                            </div>
                        @endif

                        <div class="mt-3 pt-3 border-top">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Deleted: {{ $server->deleted_at->format('M j, Y \a\t g:i A') }}
                                <br>
                                <i class="fas fa-calendar me-1"></i>
                                Last seen: {{ $server->last_seen_at ? $server->last_seen_at->format('M j, Y \a\t g:i A') : 'Never' }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-5">
        <i class="fas fa-archive fa-4x text-muted mb-3"></i>
        <h4 class="text-muted mb-3">No archived servers</h4>
        <p class="text-muted mb-4">Deleted servers will appear here and can be restored if needed.</p>
        <a href="{{ route('dashboard.servers') }}" class="btn btn-primary">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Active Servers
        </a>
    </div>
@endif
@endsection

@push('scripts')
<script>
function restoreServer(serverId, serverName) {
    if (!confirm(`Are you sure you want to restore server "${serverName}"? This will make it active again and resume monitoring.`)) {
        return;
    }

    const button = event.target.closest('a');
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Restoring...';
    button.classList.add('disabled');

    fetch(`/dashboard/servers/${serverId}/restore`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);

            // Remove server card with animation
            const serverCard = button.closest('.col-md-6');
            serverCard.style.transition = 'all 0.3s ease';
            serverCard.style.opacity = '0';
            serverCard.style.transform = 'translateY(-20px)';

            setTimeout(() => {
                serverCard.remove();

                // Check if no servers left
                const remainingServers = document.querySelectorAll('.col-md-6').length;
                if (remainingServers === 0) {
                    location.reload();
                }
            }, 300);
        } else {
            throw new Error(data.message || 'Failed to restore server');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to restore server: ' + error.message);

        // Restore button state
        button.innerHTML = originalContent;
        button.classList.remove('disabled');
    });
}

function permanentDeleteServer(serverId, serverName) {
    if (!confirm(`⚠️ PERMANENT DELETE WARNING ⚠️\n\nAre you sure you want to permanently delete server "${serverName}"?\n\nThis will:\n• Delete the server permanently\n• Delete ALL health reports\n• Delete ALL alerts\n• This action CANNOT be undone\n\nType "DELETE" to confirm:`)) {
        return;
    }

    const confirmation = prompt(`To confirm permanent deletion of "${serverName}", please type: DELETE`);
    if (confirmation !== 'DELETE') {
        alert('Deletion cancelled. You must type "DELETE" exactly to confirm.');
        return;
    }

    const button = event.target.closest('a');
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
    button.classList.add('disabled');

    fetch(`/dashboard/servers/${serverId}/force-delete`, {
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
            showAlert('warning', data.message);

            // Remove server card with animation
            const serverCard = button.closest('.col-md-6');
            serverCard.style.transition = 'all 0.3s ease';
            serverCard.style.opacity = '0';
            serverCard.style.transform = 'translateY(-20px)';

            setTimeout(() => {
                serverCard.remove();

                // Check if no servers left
                const remainingServers = document.querySelectorAll('.col-md-6').length;
                if (remainingServers === 0) {
                    location.reload();
                }
            }, 300);
        } else {
            throw new Error(data.message || 'Failed to delete server');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Failed to delete server: ' + error.message);

        // Restore button state
        button.innerHTML = originalContent;
        button.classList.remove('disabled');
    });
}

function showAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid') || document.body;
    container.insertBefore(alert, container.firstChild);
}
</script>
@endpush
