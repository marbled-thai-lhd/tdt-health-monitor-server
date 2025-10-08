<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Alert;
use App\Services\HealthReportService;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
    public function alerts(): View
    {
        $alerts = Alert::with('server')
            ->unresolved()
            ->orderByDesc('created_at')
            ->paginate(20);

        $alertStats = $this->alertService->getAlertStatistics();

        return view('dashboard.alerts', compact('alerts', 'alertStats'));
    }

    /**
     * Resolve an alert
     */
    public function resolveAlert(Request $request, Alert $alert): RedirectResponse
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        $alert->resolve($request->input('resolution_notes'));

        return back()->with('success', 'Alert resolved successfully.');
    }
}
