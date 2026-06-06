<?php

namespace App\Mail;

use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProvisioningCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?Domain $domain = null,
        public ?HostingAccount $hosting = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Planetic Web services are ready');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.provisioning-completed', with: [
            'order' => $this->order,
            'domain' => $this->domain,
            'hosting' => $this->hosting,
        ]);
    }
}
