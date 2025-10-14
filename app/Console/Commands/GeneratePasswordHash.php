<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GeneratePasswordHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:generate-hash {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a password hash for .env configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $password = $this->argument('password');

        if (!$password) {
            $password = $this->secret('Enter password to hash');
        }

        if (empty($password)) {
            $this->error('Password cannot be empty');
            return 1;
        }

        $hash = Hash::make($password);

        $this->newLine();
        $this->info('Password hash generated successfully!');
        $this->newLine();
        $this->line('Add this to your .env file:');
        $this->newLine();
        $this->line($hash);
        $this->newLine();
        $this->comment('Example:');
        $this->comment('ADMIN_PASSWORD_HASH="' . $hash . '"');
        $this->newLine();

        return 0;
    }
}
