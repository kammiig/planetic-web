<?php

namespace App\Mail;

use App\Models\HostingAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HostingSuspendedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public HostingAccount $account) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your hosting has been suspended');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.hosting-suspended', with: ['account' => $this->account]);
    }
}
