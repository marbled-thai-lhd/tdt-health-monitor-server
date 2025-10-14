<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    // How long before a server is considered offline (in minutes)
    'offline_threshold' => env('MONITORING_OFFLINE_THRESHOLD', 10),

    // Email addresses to receive alert notifications
    'alert_emails' => array_filter(explode(',', env('MONITORING_ALERT_EMAILS', ''))),

    // Cleanup settings
    'cleanup' => [
        // Days to keep health reports
        'health_reports_retention_days' => env('MONITORING_HEALTH_REPORTS_RETENTION', 30),

        // Days to keep resolved alerts
        'resolved_alerts_retention_days' => env('MONITORING_RESOLVED_ALERTS_RETENTION', 90),
    ],

    // Dashboard settings
    'dashboard' => [
        'refresh_interval' => env('MONITORING_DASHBOARD_REFRESH', 30), // seconds
        'items_per_page' => env('MONITORING_ITEMS_PER_PAGE', 20),
    ],

    // Alert thresholds
    'thresholds' => [
        'queue_timeout' => env('MONITORING_QUEUE_TIMEOUT_THRESHOLD', 30), // seconds
        'supervisor_restart_threshold' => env('MONITORING_SUPERVISOR_RESTART_THRESHOLD', 3), // restarts per hour
        'backup_failure_threshold' => env('MONITORING_BACKUP_FAILURE_THRESHOLD', 2), // consecutive failures
    ]
];
