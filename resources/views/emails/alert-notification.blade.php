<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Notification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #e0e0e0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .severity-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .severity-critical {
            background-color: #dc3545;
            color: white;
        }
        .severity-high {
            background-color: #fd7e14;
            color: white;
        }
        .severity-medium {
            background-color: #ffc107;
            color: #000;
        }
        .severity-low {
            background-color: #0dcaf0;
            color: #000;
        }
        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin: 10px 0;
        }
        .alert-details {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .detail-row {
            margin: 12px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .detail-value {
            color: #2c3e50;
            font-size: 14px;
        }
        .message-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .data-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="severity-badge severity-{{ $alert->severity }}">
                {{ strtoupper($alert->severity) }} SEVERITY
            </span>
            <h1>{{ $alert->title }}</h1>
        </div>

        <div class="alert-details">
            <div class="detail-row">
                <div class="detail-label">Alert Type</div>
                <div class="detail-value">{{ $alert->getTypeDisplayName() }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Server</div>
                <div class="detail-value">
                    <strong>{{ $server->name }}</strong>
                    @if($server->ip_address)
                        <br>
                        <small style="color: #6c757d;">{{ $server->ip_address }}</small>
                    @endif
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Time</div>
                <div class="detail-value">{{ $alert->created_at ? $alert->created_at->format('Y-m-d H:i:s') : 'N/A' }} UTC</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    @if($alert->resolved)
                        <span style="color: #28a745;">✓ Resolved</span>
                    @else
                        <span style="color: #dc3545;">⚠ Unresolved</span>
                    @endif
                </div>
            </div>
        </div>

        @if($alert->message)
        <div class="message-box">
            <strong>Message:</strong><br>
            {{ $alert->message }}
        </div>
        @endif

        @if($alert->data && count($alert->data) > 0)
        <div class="data-box">
            <strong>Additional Data:</strong>
            <pre style="margin: 10px 0 0 0; white-space: pre-wrap; word-wrap: break-word;">{{ json_encode($alert->data, JSON_PRETTY_PRINT) }}</pre>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ $dashboardUrl }}/dashboard/alerts/{{ $alert->id }}/detail" class="button">
                View in Dashboard
            </a>
        </div>

        <div class="footer">
            <p>
                This is an automated notification from your Health Monitoring System.<br>
                Please do not reply to this email.
            </p>
            <p style="margin-top: 10px;">
                <a href="{{ $dashboardUrl }}" style="color: #007bff; text-decoration: none;">
                    Go to Dashboard
                </a>
            </p>
        </div>
    </div>
</body>
</html>
