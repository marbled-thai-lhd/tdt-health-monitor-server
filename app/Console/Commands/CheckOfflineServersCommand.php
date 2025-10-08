<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOfflineServersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:check-offline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for offline servers and create alerts';

    /**
     * Execute the console command.
     */
    public function handle(AlertService $alertService): int
    {
        $this->info('Checking for offline servers...');

        try {
            // Check for offline servers and create alerts
            $alertService->checkOfflineServers();

            // Resolve offline alerts for servers that came back online
            $alertService->resolveOfflineAlerts();

            // Auto-resolve stale alerts
            $alertService->autoResolveStaleAlerts();

            $this->info('Offline server check completed successfully.');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Offline server check failed: ' . $e->getMessage());
            Log::error('Offline server check failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}
