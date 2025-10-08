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

        <!-- Setup Instructions -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-book text-info me-2"></i>
                    Setup Instructions
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-3">After creating the server, follow these steps to set up monitoring:</p>

                <div class="accordion" id="setupAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                                <span class="badge bg-primary me-2">1</span>
                                Install the Health Monitor Package
                            </button>
                        </h2>
                        <div id="step1" class="accordion-collapse collapse show" data-bs-parent="#setupAccordion">
                            <div class="accordion-body">
                                <p>On your target server, install the health monitor package:</p>
                                <pre class="bg-light p-3 rounded"><code>composer require tdt/health-monitor</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                                <span class="badge bg-primary me-2">2</span>
                                Configure Environment Variables
                            </button>
                        </h2>
                        <div id="step2" class="accordion-collapse collapse" data-bs-parent="#setupAccordion">
                            <div class="accordion-body">
                                <p>Add these variables to your server's <code>.env</code> file:</p>
                                <pre class="bg-light p-3 rounded"><code>HEALTH_MONITOR_ENABLED=true
HEALTH_MONITOR_URL={{ url('/api/health-report') }}
HEALTH_MONITOR_API_KEY=[API_KEY_FROM_STEP_3]
HEALTH_MONITOR_SERVER_NAME=[SERVER_NAME_FROM_STEP_3]</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                                <span class="badge bg-primary me-2">3</span>
                                Setup Cron Schedule
                            </button>
                        </h2>
                        <div id="step3" class="accordion-collapse collapse" data-bs-parent="#setupAccordion">
                            <div class="accordion-body">
                                <p>Run the setup command and follow the instructions:</p>
                                <pre class="bg-light p-3 rounded"><code>php artisan health:setup-schedule</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
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
