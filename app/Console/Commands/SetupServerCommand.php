<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:setup-server
                            {name : The server name}
                            {ip : The server IP address}
                            {--api-key= : Custom API key (will be generated if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup a new server for monitoring';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $ip = $this->argument('ip');
        $apiKey = $this->option('api-key') ?: Str::random(64);

        // Validate IP address
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error('Invalid IP address provided.');
            return self::FAILURE;
        }

        // Check if server already exists
        if (Server::where('name', $name)->exists()) {
            $this->error("Server with name '{$name}' already exists.");
            return self::FAILURE;
        }

        if (Server::where('ip_address', $ip)->exists()) {
            $this->error("Server with IP '{$ip}' already exists.");
            return self::FAILURE;
        }

        // Create server
        $server = Server::create([
            'name' => $name,
            'ip_address' => $ip,
            'api_key' => $apiKey,
            'status' => 'active',
        ]);

        $this->info("Server '{$name}' created successfully!");
        $this->newLine();

        $this->table(['Field', 'Value'], [
            ['ID', $server->id],
            ['Name', $server->name],
            ['IP Address', $server->ip_address],
            ['API Key', $server->api_key],
            ['Status', $server->status],
        ]);

        $this->newLine();
        $this->info('Add the following environment variables to your Laravel application:');
        $this->line("HEALTH_MONITOR_ENABLED=true");
        $this->line("HEALTH_MONITOR_URL=" . config('app.url') . "/api/health/report");
        $this->line("HEALTH_MONITOR_API_KEY={$apiKey}");
        $this->line("HEALTH_MONITOR_SERVER_NAME={$name}");
        $this->line("HEALTH_MONITOR_SERVER_IP={$ip}");
        $this->newLine();
        $this->comment('Optional: Separate URL for backup notifications:');
        $this->line("# HEALTH_MONITOR_BACKUP_URL=" . config('app.url') . "/api/health/backup-notification");

        return self::SUCCESS;
    }
}
