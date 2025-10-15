<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotification extends Mailable
{
    use SerializesModels;

    public Alert $alert;

    /**
     * Create a new message instance.
     */
    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->alert->severity) {
            'critical' => '🔴 CRITICAL ALERT',
            'high' => '🟠 HIGH ALERT',
            'medium' => '🟡 MEDIUM ALERT',
            'low' => '🔵 LOW ALERT',
            default => '⚠️ ALERT',
        };

        return new Envelope(
            subject: "{$subject}: {$this->alert->title}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.alert-notification',
            with: [
                'alert' => $this->alert,
                'server' => $this->alert->server,
                'dashboardUrl' => config('app.url'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
