@extends('layouts.app')

@section('title', 'Add New Server')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus text-primary me-2"></i>
                        Add New Server
                    </h5>
                    <a href="{{ route('dashboard.servers') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Servers
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dashboard.servers.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Server Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="e.g., web-server-01"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Choose a unique, descriptive name for your server
                            </small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="ip_address" class="form-label">IP Address</label>
                            <input type="text"
                                   class="form-control @error('ip_address') is-invalid @enderror"
                                   id="ip_address"
                                   name="ip_address"
                                   value="{{ old('ip_address') }}"
                                   placeholder="e.g., 192.168.1.10">
                            @error('ip_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Optional: Server's IP address for reference
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="base_url" class="form-label">Base URL <span class="text-danger">*</span></label>
                        <input type="url"
                               class="form-control @error('base_url') is-invalid @enderror"
                               id="base_url"
                               name="base_url"
                               value="{{ old('base_url') }}"
                               placeholder="e.g., https://myserver.com or http://192.168.1.10:8080"
                               required>
                        @error('base_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            The base URL where the server can be accessed (required for force health checks)
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  placeholder="Brief description of this server's purpose...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="environment" class="form-label">Environment</label>
                            <select class="form-select @error('environment') is-invalid @enderror"
                                    id="environment"
                                    name="environment">
                                <option value="">Select Environment</option>
                                <option value="production" {{ old('environment') === 'production' ? 'selected' : '' }}>Production</option>
                                <option value="staging" {{ old('environment') === 'staging' ? 'selected' : '' }}>Staging</option>
                                <option value="development" {{ old('environment') === 'development' ? 'selected' : '' }}>Development</option>
                                <option value="testing" {{ old('environment') === 'testing' ? 'selected' : '' }}>Testing</option>
                            </select>
                            @error('environment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="api_key" class="form-label">API Key <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control @error('api_key') is-invalid @enderror"
                                       id="api_key"
                                       name="api_key"
                                       value="{{ old('api_key') ?: \Str::random(32) }}"
                                       required>
                                <button type="button" class="btn btn-outline-secondary" onclick="generateApiKey()">
                                    <i class="fas fa-sync"></i>
                                </button>
                                @error('api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                This key will be used by the server to authenticate with the monitoring system
                            </small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active (Enable monitoring for this server)
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Next steps:</strong> After creating the server, you'll need to install and configure the health monitor package on the target server using the generated API key.
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('dashboard.servers') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Create Server
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function generateApiKey() {
    // Generate a random 32-character API key
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < 32; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('api_key').value = result;
}
</script>
@endpush
