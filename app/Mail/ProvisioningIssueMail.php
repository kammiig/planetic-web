<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Customer-safe "we're finishing your setup manually" notice, sent when a
 * provisioning step needs human attention. Never includes technical errors.
 */
class ProvisioningIssueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your order '.$this->order->order_number.' — our team is completing your setup');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.provisioning-issue', with: [
            'order' => $this->order,
        ]);
    }
}
