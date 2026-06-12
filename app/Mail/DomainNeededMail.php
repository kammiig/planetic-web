<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Action-required notice for orders placed with "I'll decide my domain later":
 * payment is confirmed and the project is underway, but hosting setup waits
 * until the customer tells us their domain.
 */
class DomainNeededMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Action needed — choose the domain for your order '.$this->order->order_number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.domain-needed', with: [
            'order' => $this->order,
        ]);
    }
}
