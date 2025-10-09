<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\HealthReport;
use App\Models\Alert;
use App\Services\HealthReportService;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HealthReportController extends Controller
{
    protected HealthReportService $healthReportService;
    protected AlertService $alertService;

    public function __construct(
        HealthReportService $healthReportService,
        AlertService $alertService
    ) {
        $this->healthReportService = $healthReportService;
        $this->alertService = $alertService;
    }

    /**
     * Receive health report from monitored servers
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = $this->validateHealthReport($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Authenticate the request
            $server = $this->authenticateRequest($request);
            if (!$server) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed'
                ], 401);
            }

            // Process the health report
            $reportData = $request->input('report', []);
            $metadata = $request->input('metadata', []);

            $healthReport = $this->healthReportService->processHealthReport(
                $server,
                $reportData,
                $metadata
            );

            // Generate alerts if necessary
            $this->alertService->processHealthReportAlerts($healthReport);

            // Update server status
            $server->updateStatus($healthReport->overall_status);

            Log::info('Health report processed', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'overall_status' => $healthReport->overall_status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Health report received successfully',
                'report_id' => $healthReport->id
            ]);

        } catch (\Exception $e) {
            Log::error('Health report processing failed', [
                'error' => $e->getMessage(),
                'server_name' => $request->header('X-Server-Name'),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Receive backup notification from monitored servers
     */
    public function backupNotification(Request $request): JsonResponse
    {
        try {
            // Validate backup notification
            $validator = $this->validateBackupNotification($request);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Authenticate the request
            $server = $this->authenticateRequest($request);
            if (!$server) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed'
                ], 401);
            }

            // Process backup notification
            $backupInfo = $request->input('backup_info', []);

            $healthReport = $this->healthReportService->processBackupNotification(
                $server,
                $backupInfo
            );

            // Check for backup failures and create alerts
            if (!empty($backupInfo['upload_error']) || ($backupInfo['uploaded'] ?? false) === false) {
                Alert::createBackupFailedAlert($server, $backupInfo);
            }

            Log::info('Backup notification processed', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'backup_success' => $backupInfo['uploaded'] ?? false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Backup notification received successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Backup notification processing failed', [
                'error' => $e->getMessage(),
                'server_name' => $request->header('X-Server-Name')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate health report request
     */
    protected function validateHealthReport(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'report' => 'required|array',
            'report.server_name' => 'required|string|max:255',
            'report.server_ip' => 'required|ip',
            'report.timestamp' => 'required|date',
            'report.supervisor' => 'sometimes|array',
            'report.cron' => 'sometimes|array',
            'report.queues' => 'sometimes|array',
            'metadata' => 'sometimes|array',
        ]);
    }

    /**
     * Validate backup notification request
     */
    protected function validateBackupNotification(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'type' => 'required|string|in:backup_notification',
            'server_name' => 'required|string|max:255',
            'server_ip' => 'required|ip',
            'backup_info' => 'required|array',
            'backup_info.file_path' => 'sometimes|string',
            'backup_info.file_size' => 'sometimes|integer',
            'backup_info.duration' => 'sometimes|numeric',
            'backup_info.uploaded' => 'sometimes|boolean',
            'timestamp' => 'required|date',
        ]);
    }

    /**
     * Authenticate request using HMAC
     */
    protected function authenticateRequest(Request $request): ?Server
    {
        $serverName = $request->header('X-Server-Name');
        $authHeader = $request->header('Authorization');

        if (!$serverName || !$authHeader) {
            return null;
        }

        // Extract Bearer token
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);

        // Find server
        $server = Server::where('name', $serverName)->first();
        if (!$server || !$server->api_key) {
            return null;
        }

        // Validate HMAC token (timestamp is extracted from token)
        if (!$this->validateHmacToken($token, $serverName, $server->api_key)) {
            return null;
        }

        return $server;
    }

    /**
     * Validate HMAC token
     */
    protected function validateHmacToken(string $token, string $serverName, string $apiKey): bool
    {
        try {
            $decodedToken = base64_decode($token);
            [$tokenTimestamp, $signature] = explode('.', $decodedToken, 2);

            $timestamp = (int)$tokenTimestamp;

            // Check timestamp to prevent replay attacks (allow 5 minutes window)
            if (abs(time() - $timestamp) > 300) {
                return false;
            }
            $payload = "{$serverName}:{$timestamp}";
            $expectedSignature = hash_hmac('sha256', $payload, $apiKey);
            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Force health check for a specific server
     */
    public function forceCheck(Server $server): JsonResponse
    {
        try {
            // Check if server has force check URL configured
            if (empty($server->force_check_url)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Force check URL not configured for this server'
                ], 400);
            }

            // Make HTTP request to server's force check endpoint
            $client = new \GuzzleHttp\Client(['timeout' => 30]);

            try {
                $response = $client->post($server->force_check_url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'trigger' => 'manual',
                        'timestamp' => now()->toISOString(),
                    ]
                ]);

                $responseData = json_decode($response->getBody()->getContents(), true);

                // Log the force check request
                Log::info('Force check initiated', [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'response_status' => $response->getStatusCode(),
                    'response_data' => $responseData
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Force check initiated successfully',
                    'server' => $server->name,
                    'response' => $responseData
                ]);

            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error('Force check request failed', [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'error' => $e->getMessage(),
                    'url' => $server->force_check_url
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to server for force check',
                    'error' => $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Force check failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Force check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
