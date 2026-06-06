<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RenewalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customerName,
        public string $serviceName,
        public string $renewalDate,
        public ?float $amount,
        public int $daysBefore,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Renewal reminder · '.$this->serviceName);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.renewal-reminder', with: [
            'customerName' => $this->customerName,
            'serviceName' => $this->serviceName,
            'renewalDate' => $this->renewalDate,
            'amount' => $this->amount,
            'daysBefore' => $this->daysBefore,
        ]);
    }
}
