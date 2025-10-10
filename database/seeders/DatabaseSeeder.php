<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Server::create([
            'id' => '1bde9dd1-2166-493b-8db1-c23f8d9d3d6e',
            'name' => 'aripla-local',
            'ip_address' => '127.0.0.1',
            'base_url' => 'http://127.0.0.1:8002',
            'description' => null,
            'environment' => 'development',
            'is_active' => 1,
            'api_key' => 'uT4LTGeZ2HrC17BZEtSqIBN1k8KgxyWB',
            'status' => 'offline',
            'last_seen_at' => null,
            'metadata' => null,
            'created_at' => '2025-10-09 03:45:37',
            'updated_at' => '2025-10-09 03:45:37',
        ]);
    }
}
