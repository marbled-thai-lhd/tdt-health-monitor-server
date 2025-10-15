<?php

namespace App\Observers;

use App\Models\Alert;
use App\Mail\AlertNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AlertObserver
{
    /**
     * Handle the Alert "created" event.
     * Send email notification to admin emails when a new alert is created.
     */
    public function created(Alert $alert): void
    {
        // Get admin emails from config
        $adminEmails = $this->getAdminEmails();

        if (empty($adminEmails)) {
            Log::warning('No admin emails configured for alert notifications');
            return;
        }

        // Load the server relationship
        $alert->load('server');

        try {
            // Send email to all admin emails
            foreach ($adminEmails as $email) {
                Mail::to(trim($email))->send(new AlertNotification($alert));
            }

            Log::info('Alert email notifications sent', [
                'alert_id' => $alert->id,
                'alert_type' => $alert->type,
                'severity' => $alert->severity,
                'recipients_count' => count($adminEmails),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send alert email notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get admin emails from environment variable
     * Emails should be comma-separated
     */
    protected function getAdminEmails(): array
    {
        $emailsString = env('ALERT_ADMIN_EMAILS', '');

        if (empty($emailsString)) {
            return [];
        }

        // Split by comma and filter empty values
        $emails = array_filter(
            array_map('trim', explode(',', $emailsString)),
            fn($email) => !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)
        );

        return array_values($emails);
    }

    /**
     * Handle the Alert "updated" event.
     */
    public function updated(Alert $alert): void
    {
        //
    }

    /**
     * Handle the Alert "deleted" event.
     */
    public function deleted(Alert $alert): void
    {
        //
    }

    /**
     * Handle the Alert "restored" event.
     */
    public function restored(Alert $alert): void
    {
        //
    }

    /**
     * Handle the Alert "force deleted" event.
     */
    public function forceDeleted(Alert $alert): void
    {
        //
    }
}
