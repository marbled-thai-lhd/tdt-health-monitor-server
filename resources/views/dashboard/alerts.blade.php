@extends('layouts.app')

@section('title', 'Alerts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1">Alert Management</h2>
        <p class="text-muted mb-0">Monitor and resolve system alerts</p>
    </div>
    <div>
        <button class="btn btn-outline-primary me-2">
            <i class="fas fa-download me-1"></i>
            Export
        </button>
        <button class="btn btn-success">
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
                    <!-- Add server options here -->
                </select>
            </div>
            <div class="col-md-3">
                <label for="severity" class="form-label">Severity</label>
                <select name="severity" id="severity" class="form-select">
                    <option value="">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="warning">Warning</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="unresolved">Unresolved</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>
                        Filter
                    </button>
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
                                    <span class="badge bg-{{ $alert->severity === 'critical' ? 'danger' : 'warning' }}">
                                        {{ ucfirst($alert->severity) }}
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        {{ $alert->created_at->format('M d, H:i') }}
                                        <div class="text-muted">{{ $alert->created_at->diffForHumans() }}</div>
                                    </small>
                                </td>
                                <td>
                                    @if($alert->resolved_at)
                                        <span class="badge bg-success">Resolved</span>
                                        <small class="d-block text-muted">
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
                                            onclick="showAlertDetail({{ $alert->id }})">
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

function showAlertDetail(alertId) {
    // This would typically make an AJAX call to get alert details
    document.getElementById('alertDetailContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    // Simulate loading alert details
    setTimeout(() => {
        document.getElementById('alertDetailContent').innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Alert ID:</strong> ${alertId}<br>
                    <strong>Type:</strong> Supervisor Process Down<br>
                    <strong>Severity:</strong> <span class="badge bg-danger">Critical</span><br>
                    <strong>Created:</strong> 2 hours ago
                </div>
                <div class="col-md-6">
                    <strong>Server:</strong> web-server-01<br>
                    <strong>Status:</strong> <span class="badge bg-danger">Unresolved</span><br>
                    <strong>Last Updated:</strong> 2 hours ago
                </div>
            </div>
            <hr>
            <div>
                <strong>Message:</strong><br>
                <div class="bg-light p-3 rounded mt-2">
                    Supervisor process 'laravel-worker:laravel-worker_00' is not running.
                    Process may have crashed or been manually stopped.
                </div>
            </div>
            <hr>
            <div>
                <strong>Additional Data:</strong><br>
                <pre class="bg-light p-3 rounded mt-2"><code>{
    "process_name": "laravel-worker:laravel-worker_00",
    "expected_state": "RUNNING",
    "actual_state": "STOPPED",
    "exit_code": 1,
    "last_start": "2024-01-15 10:30:15"
}</code></pre>
            </div>
        `;
    }, 500);
}
</script>
@endpush
