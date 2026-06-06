<?php

namespace App\Mail;

use App\Models\HostingAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HostingReactivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public HostingAccount $account) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your hosting is active again');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.hosting-reactivated', with: ['account' => $this->account]);
    }
}
