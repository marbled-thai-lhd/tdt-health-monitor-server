<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Server;
use App\Mail\AlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestAlertEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alert:test-email {email? : Email address to send test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test alert email to verify email configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Preparing test alert email...');

        // Get test email from argument or use env
        $testEmail = $this->argument('email');
        if (!$testEmail) {
            $adminEmails = $this->getAdminEmails();
            if (empty($adminEmails)) {
                $this->error('No admin emails configured. Please set ALERT_ADMIN_EMAILS in .env');
                return 1;
            }
            $testEmail = $adminEmails[0];
        }

        // Validate email
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: {$testEmail}");
            return 1;
        }

        // Get or create a test server
        $server = Server::first();
        if (!$server) {
            $this->error('No servers found in database. Please add a server first.');
            return 1;
        }

        // Create a test alert (but don't save it to avoid triggering observer)
        $alert = new Alert([
            'id' => '00000000-0000-0000-0000-000000000000',
            'server_id' => $server->id,
            'type' => 'test',
            'severity' => 'medium',
            'title' => 'Test Alert Email',
            'message' => 'This is a test alert email to verify your email configuration is working correctly.',
            'data' => [
                'test' => true,
                'sent_at' => now()->toISOString(),
            ],
            'resolved' => false,
            'created_at' => now(),
        ]);

        // Set the server relationship
        $alert->setRelation('server', $server);

        try {
            $this->info("Sending test email to: {$testEmail}");

            Mail::to($testEmail)->send(new AlertNotification($alert));

            // Show mail configuration
            $this->newLine();
            $this->info('Current Mail Configuration:');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['MAIL_MAILER', config('mail.default')],
                    ['MAIL_HOST', config('mail.mailers.smtp.host')],
                    ['MAIL_PORT', config('mail.mailers.smtp.port')],
                    ['MAIL_FROM', config('mail.from.address')],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send test email');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get admin emails from environment variable
     */
    protected function getAdminEmails(): array
    {
        $emailsString = env('ALERT_ADMIN_EMAILS', '');

        if (empty($emailsString)) {
            return [];
        }

        $emails = array_filter(
            array_map('trim', explode(',', $emailsString)),
            fn($email) => !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)
        );

        return array_values($emails);
    }
}
