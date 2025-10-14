<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Health Monitor') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 0.25rem;
            margin: 0.25rem 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: help;
            transition: all 0.2s ease;
        }
        .status-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .status-ok {
            background-color: #28a745;
            box-shadow: 0 0 0 1px rgba(40, 167, 69, 0.2);
        }
        .status-warning {
            background-color: #ffc107;
            color: #000;
            box-shadow: 0 0 0 1px rgba(255, 193, 7, 0.2);
        }
        .status-error {
            background-color: #dc3545;
            box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.2);
        }
        .status-critical {
            background-color: #dc3545;
            box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.2);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.2); }
            50% { box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.3); }
            100% { box-shadow: 0 0 0 1px rgba(220, 53, 69, 0.2); }
        }
        .status-offline,
        .status-no_processes {
            background-color: #6c757d;
            box-shadow: 0 0 0 1px rgba(108, 117, 125, 0.2);
        }

        .card-stat {
            transition: transform 0.2s;
        }
        .card-stat:hover {
            transform: translateY(-2px);
        }

        .table-responsive {
            border-radius: 0.375rem;
        }

        .alert-item {
            border-left: 4px solid transparent;
        }
        .alert-item.alert-critical {
            border-left-color: #dc3545;
        }
        .alert-item.alert-warning {
            border-left-color: #ffc107;
        }

        .server-card {
            transition: all 0.2s;
            border: 1px solid #dee2e6;
        }
        .server-card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .metric-chart {
            height: 200px;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-3">
                        <i class="fas fa-heartbeat text-danger"></i>
                        Health Monitor
                    </h5>
                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}"
                           href="{{ route('dashboard.index') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('dashboard.servers*') ? 'active' : '' }}"
                           href="{{ route('dashboard.servers') }}">
                            <i class="fas fa-server me-2"></i>
                            Servers
                        </a>
                        <a class="nav-link {{ request()->routeIs('dashboard.alerts*') ? 'active' : '' }}"
                           href="{{ route('dashboard.alerts') }}">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Alerts
                        </a>
                        <a class="nav-link {{ request()->routeIs('dashboard.servers.archived') ? 'active' : '' }}"
                           href="{{ route('dashboard.servers.archived') }}">
                            <i class="fas fa-archive me-2"></i>
                            Archives
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">@yield('title', 'Dashboard')</h1>
                        <div class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <span class="local-time-header" data-utc="{{ now()->toISOString() }}">
                                {{ now()->format('M d, Y H:i') }}
                            </span>
                        </div>
                    </div>

                    <!-- Alerts -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Content -->
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global timezone conversion script -->
    <script>
        function updateHeaderTime() {
            const now = new Date();
            const timeElement = document.querySelector('.local-time-header');
            if (timeElement) {
                timeElement.textContent = now.toLocaleDateString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric'
                }) + ' ' + now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Update header time immediately
            updateHeaderTime();

            // Update header time every minute
            setInterval(updateHeaderTime, 60000);
        });
    </script>

    @stack('scripts')
</body>
</html>
