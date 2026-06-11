<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic operational alert for the admin inbox (new website project sold,
 * provisioning failure, registrar/WHM/Cloudflare errors, …). Keeps secrets
 * out: callers pass display-safe lines only.
 */
class AdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $details  label => value rows
     */
    public function __construct(
        public string $alertSubject,
        public string $intro,
        public array $details = [],
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Planetic Web] '.$this->alertSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-alert', with: [
            'alertSubject' => $this->alertSubject,
            'intro' => $this->intro,
            'details' => $this->details,
            'actionUrl' => $this->actionUrl,
            'actionLabel' => $this->actionLabel,
        ]);
    }
}
