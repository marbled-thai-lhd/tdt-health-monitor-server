@extends('layouts.app')

@section('title', 'Edit Server - ' . $server->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.servers') }}">Servers</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.server-detail', $server) }}">{{ $server->name }}</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
        <h2 class="h4 mb-0">Edit Server</h2>
        <p class="text-muted mb-0">Update server configuration and settings</p>
    </div>
    <div>
        <a href="{{ route('dashboard.server-detail', $server) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Back to Details
        </a>
    </div>
</div>

<!-- Success Message -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Error Messages -->
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Edit Form -->
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Server Configuration
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dashboard.servers.update', $server) }}">
                    @csrf
                    @method('PUT')

                    <!-- Server Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-server me-1"></i>
                            Server Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $server->name) }}"
                               required
                               placeholder="Enter server name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">A unique name to identify this server in the dashboard</div>
                    </div>

                    <!-- IP Address -->
                    <div class="mb-3">
                        <label for="ip_address" class="form-label">
                            <i class="fas fa-globe me-1"></i>
                            IP Address
                        </label>
                        <input type="text"
                               class="form-control @error('ip_address') is-invalid @enderror"
                               id="ip_address"
                               name="ip_address"
                               value="{{ old('ip_address', $server->ip_address) }}"
                               placeholder="192.168.1.100">
                        @error('ip_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Optional IP address for server identification</div>
                    </div>

                    <!-- Base URL -->
                    <div class="mb-3">
                        <label for="base_url" class="form-label">
                            <i class="fas fa-link me-1"></i>
                            Base URL <span class="text-danger">*</span>
                        </label>
                        <input type="url"
                               class="form-control @error('base_url') is-invalid @enderror"
                               id="base_url"
                               name="base_url"
                               value="{{ old('base_url', $server->base_url) }}"
                               required
                               placeholder="https://your-server.com">
                        @error('base_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">The base URL where your application is running</div>
                    </div>

                    <!-- Environment -->
                    <div class="mb-3">
                        <label for="environment" class="form-label">
                            <i class="fas fa-cog me-1"></i>
                            Environment
                        </label>
                        <select class="form-select @error('environment') is-invalid @enderror"
                                id="environment"
                                name="environment">
                            <option value="">Select Environment</option>
                            <option value="production" {{ old('environment', $server->environment) === 'production' ? 'selected' : '' }}>Production</option>
                            <option value="staging" {{ old('environment', $server->environment) === 'staging' ? 'selected' : '' }}>Staging</option>
                            <option value="development" {{ old('environment', $server->environment) === 'development' ? 'selected' : '' }}>Development</option>
                            <option value="testing" {{ old('environment', $server->environment) === 'testing' ? 'selected' : '' }}>Testing</option>
                        </select>
                        @error('environment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Environment type for better organization</div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            <i class="fas fa-file-text me-1"></i>
                            Description
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  placeholder="Optional description about this server">{{ old('description', $server->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Optional description to help identify the server's purpose</div>
                    </div>

                    <!-- Active Status -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   {{ old('is_active', $server->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-toggle-on me-1"></i>
                                Active Server
                            </label>
                        </div>
                        <div class="form-text">Inactive servers will not receive health check requests</div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('dashboard.server-detail', $server) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Update Server
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Server Info Panel -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    Current Server Info
                </h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt class="mb-1">ID:</dt>
                    <dd class="mb-3">
                        <code class="small">{{ $server->id }}</code>
                    </dd>

                    <dt class="mb-1">API Key:</dt>
                    <dd class="mb-3">
                        <div class="input-group">
                            <input type="text"
                                   class="form-control form-control-sm"
                                   value="{{ $server->api_key }}"
                                   id="apiKey"
                                   readonly>
                            <button class="btn btn-outline-secondary btn-sm"
                                    type="button"
                                    onclick="copyToClipboard('apiKey')"
                                    title="Copy API Key">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted">Used for health report authentication</small>
                    </dd>

                    <dt class="mb-1">Status:</dt>
                    <dd class="mb-3">
                        <span class="badge status-badge status-{{ $server->status ?? 'offline' }}">
                            {{ ucfirst($server->status ?? 'offline') }}
                        </span>
                    </dd>

                    <dt class="mb-1">Created:</dt>
                    <dd class="mb-3">
                        <span class="local-date" data-utc="{{ $server->created_at->toISOString() }}">
                            {{ $server->created_at->format('M d, Y') }}
                        </span>
                    </dd>

                    <dt class="mb-1">Last Seen:</dt>
                    <dd class="mb-0">
                        @if($server->last_seen_at)
                            <span class="local-time-relative" data-utc="{{ $server->last_seen_at->toISOString() }}">
                                {{ $server->last_seen_at->diffForHumans() }}
                            </span>
                        @else
                            <span class="text-muted">Never</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Important Notes
                </h5>
            </div>
            <div class="card-body">
                <ul class="mb-0 small">
                    <li class="mb-2">
                        <strong>API Key:</strong> Cannot be changed after creation for security reasons
                    </li>
                    <li class="mb-2">
                        <strong>Base URL:</strong> Make sure it's accessible from this monitoring server
                    </li>
                    <li class="mb-0">
                        <strong>Deactivating:</strong> Will stop all health checks for this server
                    </li>
                </ul>
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
});

function convertTimesToLocal() {
    // Convert local-date elements (date only)
    document.querySelectorAll('.local-date').forEach(function(element) {
        const utcTime = element.getAttribute('data-utc');
        if (utcTime) {
            const localDate = new Date(utcTime);
            element.textContent = localDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
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
}

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999); // For mobile devices

    navigator.clipboard.writeText(element.value).then(function() {
        // Show success feedback
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-secondary');

        setTimeout(function() {
            button.innerHTML = originalContent;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    }).catch(function(err) {
        console.error('Failed to copy: ', err);
        alert('Failed to copy to clipboard');
    });
}
</script>
@endpush
