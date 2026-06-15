<?php

namespace App\Mail;

use App\Models\WebsiteProject;
use App\Models\WebsiteProjectMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the other party that a new message was posted in a project
 * workspace. The message body is summarised, not quoted in full, so private
 * detail stays in the authenticated dashboard.
 */
class ProjectMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public WebsiteProject $project,
        public WebsiteProjectMessage $message,
        public bool $toStaff = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New message on your website project '.$this->project->project_number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.project-message', with: [
            'project' => $this->project,
            'message' => $this->message,
            'toStaff' => $this->toStaff,
        ]);
    }
}
