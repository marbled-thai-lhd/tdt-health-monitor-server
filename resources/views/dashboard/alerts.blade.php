@extends('layouts.app')

@section('title', 'Alerts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1">Alert Management</h2>
        <p class="text-muted mb-0">Monitor and resolve system alerts</p>
    </div>
    <div>
        <a href="{{ route('dashboard.alerts.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
           class="btn btn-outline-primary me-2">
            <i class="fas fa-download me-1"></i>
            Export
        </a>
        <button class="btn btn-success" onclick="resolveAllAlerts()">
            <i class="fas fa-check-double me-1"></i>
            Resolve All
        </button>
    </div>
</div>

<!-- Alert Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card card-stat border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <h5 class="card-title text-muted mb-1">Total Alerts</h5>
                    <h3 class="mb-0">{{ $alertStats['total_alerts'] ?? 0 }}</h3>
                </div>
                <div class="text-info">
                    <i class="fas fa-bell fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card card-stat border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <h5 class="card-title text-muted mb-1">Critical</h5>
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
                    <h5 class="card-title text-muted mb-1">Warning</h5>
                    <h3 class="mb-0 text-warning">{{ $alertStats['warning_count'] ?? 0 }}</h3>
                </div>
                <div class="text-warning">
                    <i class="fas fa-exclamation-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card card-stat border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <h5 class="card-title text-muted mb-1">Resolved Today</h5>
                    <h3 class="mb-0 text-success">{{ $alertStats['resolved_today'] ?? 0 }}</h3>
                </div>
                <div class="text-success">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="server" class="form-label">Server</label>
                <select name="server" id="server" class="form-select">
                    <option value="">All Servers</option>
                    @foreach($servers as $server)
                        <option value="{{ $server->id }}" {{ request('server') == $server->id ? 'selected' : '' }}>
                            {{ $server->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="severity" class="form-label">Severity</label>
                <select name="severity" id="severity" class="form-select">
                    <option value="">All Severities</option>
                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>High</option>
                    <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Warning</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="unresolved" {{ request('status') == 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-filter me-1"></i>
                        Filter
                    </button>
                    <a href="{{ route('dashboard.alerts') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Alerts List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            Alert List
        </h5>
    </div>
    <div class="card-body p-0">
        @if($alerts && $alerts->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Server</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Severity</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input alert-checkbox"
                                           value="{{ $alert->id }}">
                                </td>
                                <td>
                                    <a href="{{ route('dashboard.server-detail', $alert->server) }}"
                                       class="text-decoration-none">
                                        <i class="fas fa-server me-1"></i>
                                        {{ $alert->server->name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $alert->type }}</span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 300px;"
                                         title="{{ $alert->message }}">
                                        {{ $alert->message }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $alert->severity === 'critical' ? 'danger' : ($alert->severity === 'high' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($alert->severity) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="local-time-short" data-utc="{{ $alert->created_at->toISOString() }}">
                                        {{ $alert->created_at->format('M d, H:i') }}
                                    </small>
                                    <div class="text-muted local-time-relative" data-utc="{{ $alert->created_at->toISOString() }}">
                                        {{ $alert->created_at->diffForHumans() }}
                                    </div>
                                </td>
                                <td>
                                    @if($alert->resolved_at)
                                        <span class="badge bg-success">Resolved</span>
                                        <small class="d-block text-muted local-time-relative" data-utc="{{ $alert->resolved_at->toISOString() }}">
                                            {{ $alert->resolved_at->diffForHumans() }}
                                        </small>
                                    @else
                                        <span class="badge bg-danger">Unresolved</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$alert->resolved_at)
                                        <form method="POST"
                                              action="{{ route('dashboard.resolve-alert', $alert) }}"
                                              class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Are you sure you want to resolve this alert?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <button class="btn btn-sm btn-outline-secondary ms-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#alertDetailModal"
                                            onclick="showAlertDetail('{{ $alert->id }}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white">
                {{ $alerts->links() }}
            </div>
        @else
            <div class="p-5 text-center text-muted">
                <i class="fas fa-check-circle fa-4x mb-3"></i>
                <h4 class="mb-3">No alerts found</h4>
                <p class="mb-0">All systems are running smoothly!</p>
            </div>
        @endif
    </div>
</div>

<!-- Alert Detail Modal -->
<div class="modal fade" id="alertDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alert Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="alertDetailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="resolveModalBtn">Resolve Alert</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Convert UTC times to local timezone
    convertTimesToLocal();

    // Select All functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const alertCheckboxes = document.querySelectorAll('.alert-checkbox');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            alertCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Update select all when individual checkboxes change
    alertCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.alert-checkbox:checked').length;
            const totalCount = alertCheckboxes.length;

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = checkedCount === totalCount;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < totalCount;
            }
        });
    });
});

function convertTimesToLocal() {
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

function showAlertDetail(alertId) {
    // Show loading spinner
    document.getElementById('alertDetailContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    // Fetch real alert data
    fetch(`/dashboard/alerts/${alertId}/detail`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const alert = data.alert;
                const createdDate = new Date(alert.created_at);
                const resolvedDate = alert.resolved_at ? new Date(alert.resolved_at) : null;

                // Format severity badge color
                let severityBadge;
                switch(alert.severity) {
                    case 'critical':
                        severityBadge = '<span class="badge bg-danger">Critical</span>';
                        break;
                    case 'high':
                        severityBadge = '<span class="badge bg-danger">High</span>';
                        break;
                    case 'warning':
                        severityBadge = '<span class="badge bg-warning">Warning</span>';
                        break;
                    default:
                        severityBadge = '<span class="badge bg-secondary">' + alert.severity + '</span>';
                }

                // Format status badge
                const statusBadge = resolvedDate
                    ? '<span class="badge bg-success">Resolved</span>'
                    : '<span class="badge bg-danger">Unresolved</span>';

                document.getElementById('alertDetailContent').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Alert ID:</strong> ${alert.id}<br>
                            <strong>Type:</strong> ${alert.type}<br>
                            <strong>Severity:</strong> ${severityBadge}<br>
                            <strong>Created:</strong> ${createdDate.toLocaleString('en-US', {
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            })}
                        </div>
                        <div class="col-md-6">
                            <strong>Server:</strong> ${alert.server.name}<br>
                            <strong>IP Address:</strong> ${alert.server.ip_address || 'N/A'}<br>
                            <strong>Status:</strong> ${statusBadge}<br>
                            <strong>Last Updated:</strong> ${resolvedDate ?
                                resolvedDate.toLocaleString('en-US', {
                                    year: 'numeric',
                                    month: '2-digit',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: false
                                }) : 'Not resolved'
                            }
                        </div>
                    </div>
                    <hr>
                    <div>
                        <strong>Message:</strong><br>
                        <div class="bg-light p-3 rounded mt-2">
                            ${alert.message || 'No message available'}
                        </div>
                    </div>
                    ${alert.data ? `
                        <hr>
                        <div>
                            <strong>Additional Data:</strong><br>
                            <pre class="bg-light p-3 rounded mt-2"><code>${JSON.stringify(alert.data, null, 2)}</code></pre>
                        </div>
                    ` : ''}
                    ${alert.resolution_notes ? `
                        <hr>
                        <div>
                            <strong>Resolution Notes:</strong><br>
                            <div class="bg-light p-3 rounded mt-2">
                                ${alert.resolution_notes}
                            </div>
                        </div>
                    ` : ''}
                `;

                // Update the resolve button in modal footer
                const resolveBtn = document.getElementById('resolveModalBtn');
                if (resolvedDate) {
                    resolveBtn.style.display = 'none';
                } else {
                    resolveBtn.style.display = 'inline-block';
                    resolveBtn.onclick = () => resolveAlertFromModal(alert.id);
                }

            } else {
                document.getElementById('alertDetailContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load alert details.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching alert details:', error);
            document.getElementById('alertDetailContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading alert details. Please try again.
                </div>
            `;
        });
}

function resolveAlertFromModal(alertId) {
    if (confirm('Are you sure you want to resolve this alert?')) {
        const resolveBtn = document.getElementById('resolveModalBtn');
        const originalText = resolveBtn.innerHTML;

        // Show loading state
        resolveBtn.disabled = true;
        resolveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Resolving...';

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
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('alertDetailModal'));
                modal.hide();

                // Refresh page to show updated data
                location.reload();
            } else {
                alert('Failed to resolve alert: ' + (data.message || 'Unknown error'));
                resolveBtn.disabled = false;
                resolveBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error resolving alert:', error);
            alert('Failed to resolve alert. Please try again.');
            resolveBtn.disabled = false;
            resolveBtn.innerHTML = originalText;
        });
    }
}

function resolveAllAlerts() {
    if (confirm('Are you sure you want to resolve ALL unresolved alerts? This action cannot be undone.')) {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;

        // Show loading state
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Resolving...';

        // Build query string to preserve current filters
        const urlParams = new URLSearchParams(window.location.search);
        const queryString = urlParams.toString();

        fetch(`/dashboard/alerts/resolve-all${queryString ? '?' + queryString : ''}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Refresh page to show updated data
                location.reload();
            } else {
                alert('Failed to resolve alerts: ' + (data.message || 'Unknown error'));
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error resolving alerts:', error);
            alert('Failed to resolve alerts. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
}
</script>
@endpush
