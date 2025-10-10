<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Alert;
use App\Services\HealthReportService;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
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
     * Show main dashboard
     */
    public function index(): View
    {
        $statistics = $this->healthReportService->getServerStatistics();
        $alertStats = $this->alertService->getAlertStatistics();
        $recentAlerts = $this->alertService->getRecentAlerts(5);
        $healthSummary = $this->healthReportService->getHealthSummary();

        return view('dashboard.index', compact(
            'statistics',
            'alertStats',
            'recentAlerts',
            'healthSummary'
        ));
    }

    /**
     * Show servers list
     */
    public function servers(): View
    {
        $servers = Server::with(['latestHealthReport', 'unresolvedAlerts'])
            ->orderBy('name')
            ->get();

        return view('dashboard.servers', compact('servers'));
    }

    /**
     * Show create server form
     */
    public function createServer(): View
    {
        return view('dashboard.servers.create');
    }

    /**
     * Store new server
     */
    public function storeServer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:servers,name',
            'ip_address' => 'nullable|ip',
            'base_url' => 'required|url|max:255',
            'description' => 'nullable|string|max:1000',
            'environment' => 'nullable|in:production,staging,development,testing',
            'api_key' => 'required|string|min:16|unique:servers,api_key',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['status'] = 'offline'; // Default status

        $server = Server::create($validated);

        return redirect()->route('dashboard.servers.setup', $server)
            ->with('success', 'Server created successfully! Follow the setup instructions below.');
    }

    /**
     * Show setup instructions for a server
     */
    public function setupServer(Server $server): View
    {
        return view('dashboard.servers.setup', compact('server'));
    }

    /**
     * Show server detail
     */
    public function serverDetail(Server $server): View
    {
        $server->load(['healthReports' => function ($query) {
            $query->latest()->limit(20);
        }, 'alerts' => function ($query) {
            $query->latest()->limit(10);
        }]);

        $healthTimeline = $this->healthReportService->getServerHealthTimeline($server, 24);

        return view('dashboard.server-detail', compact('server', 'healthTimeline'));
    }

    /**
     * Show alerts
     */
    public function alerts(Request $request): View
    {
        $query = Alert::with('server');

        // Apply filters based on request parameters
        if ($request->filled('server')) {
            $query->where('server_id', $request->input('server'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'unresolved') {
                $query->unresolved();
            } elseif ($request->input('status') === 'resolved') {
                $query->resolved();
            }
            // If status is any other value (or empty string), show all alerts
        }
        // If no status parameter at all, default to unresolved for initial page load
        else if (!$request->has('status')) {
            $query->unresolved();
        }
        // If status parameter exists but is empty (All Status selected), show all alerts

        $alerts = $query->orderByDesc('created_at')->paginate(20);

        // Preserve query parameters in pagination links
        $alerts->appends($request->query());

        $alertStats = $this->alertService->getAlertStatistics();

        // Get all servers for filter dropdown
        $servers = Server::orderBy('name')->get();

        return view('dashboard.alerts', compact('alerts', 'alertStats', 'servers'));
    }

    /**
     * Resolve an alert
     */
    public function resolveAlert(Request $request, Alert $alert)
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        $alert->resolve($request->input('resolution_notes'));

        return $request->input('json') ? [
            'success' => true,
            'message' => 'Alert resolved successfully.'
        ] : redirect()->route('dashboard.alerts')->with('success', 'Alert resolved successfully.');
    }

    /**
     * Get alert details for modal
     */
    public function getAlertDetail(Alert $alert)
    {
        $alert->load('server');

        return response()->json([
            'success' => true,
            'alert' => [
                'id' => $alert->id,
                'type' => $alert->type,
                'severity' => $alert->severity,
                'message' => $alert->message,
                'data' => $alert->data,
                'created_at' => $alert->created_at->toISOString(),
                'resolved_at' => $alert->resolved_at?->toISOString(),
                'resolution_notes' => $alert->resolution_notes,
                'server' => [
                    'id' => $alert->server->id,
                    'name' => $alert->server->name,
                    'ip_address' => $alert->server->ip_address,
                ]
            ]
        ]);
    }

    /**
     * Export alerts to CSV
     */
    public function exportAlerts(Request $request)
    {
        $query = Alert::with('server');
        
        // Apply same filters as alerts() method
        if ($request->filled('server')) {
            $query->where('server_id', $request->input('server'));
        }
        
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }
        
        if ($request->filled('status')) {
            if ($request->input('status') === 'unresolved') {
                $query->unresolved();
            } elseif ($request->input('status') === 'resolved') {
                $query->resolved();
            }
        } else {
            // Default to all alerts for export
            // Don't filter by status
        }
        
        $alerts = $query->orderByDesc('created_at')->get();
        
        $filename = 'alerts_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($alerts) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Alert ID',
                'Server Name', 
                'Server IP',
                'Type',
                'Severity',
                'Message',
                'Status',
                'Created At',
                'Resolved At',
                'Resolution Notes'
            ]);
            
            // CSV Data
            foreach ($alerts as $alert) {
                fputcsv($file, [
                    $alert->id,
                    $alert->server->name,
                    $alert->server->ip_address,
                    $alert->type,
                    $alert->severity,
                    $alert->message,
                    $alert->resolved_at ? 'Resolved' : 'Unresolved',
                    $alert->created_at->format('Y-m-d H:i:s'),
                    $alert->resolved_at ? $alert->resolved_at->format('Y-m-d H:i:s') : '',
                    $alert->resolution_notes ?? ''
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Resolve all unresolved alerts
     */
    public function resolveAllAlerts(Request $request)
    {
        $query = Alert::unresolved();
        
        // Apply same filters as alerts() method if provided
        if ($request->filled('server')) {
            $query->where('server_id', $request->input('server'));
        }
        
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }
        
        $unresolvedAlerts = $query->get();
        $resolvedCount = 0;
        
        foreach ($unresolvedAlerts as $alert) {
            $alert->resolve('Bulk resolved by administrator');
            $resolvedCount++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "Successfully resolved {$resolvedCount} alert(s).",
            'resolved_count' => $resolvedCount
        ]);
    }

    /**
     * Force a health check for a specific server
     */
    public function forceHealthCheck(Server $server)
    {
        try {
            // Create a force check request to the monitored server
            $client = new \GuzzleHttp\Client(['timeout' => 60]);

            // The monitored server should have an endpoint to trigger immediate health check
            $forceCheckUrl = $server->base_url . '/health-monitor/force-check';

            $response = $client->post($forceCheckUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $server->api_key,
                ],
                'json' => [
                    'timestamp' => time(),
                    'force_check' => true
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return response()->json([
                    'success' => true,
                    'message' => 'Health check triggered successfully. Report should arrive within 1-2 minutes.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger health check on server.'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Force health check failed', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to server: ' . $e->getMessage()
            ], 500);
        }
    }
}
