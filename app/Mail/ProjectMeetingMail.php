<?php

namespace App\Mail;

use App\Models\WebsiteProjectMeeting;
use App\Support\IcsBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Meeting request / confirmation / reschedule notice for a project workspace,
 * with a .ics invite attached so the recipient can add it to any calendar
 * (Google / Outlook / Apple) — no calendar API integration required.
 */
class ProjectMeetingMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param 'requested'|'confirmed'|'rescheduled'|'cancelled' $event */
    public function __construct(
        public WebsiteProjectMeeting $meeting,
        public string $event,
        public bool $toStaff = false,
    ) {}

    public function envelope(): Envelope
    {
        $project = $this->meeting->project;

        $subject = match ($this->event) {
            'confirmed' => 'Your project meeting is confirmed — '.$project->project_number,
            'rescheduled' => 'Your project meeting has been rescheduled — '.$project->project_number,
            'cancelled' => 'Your project meeting was cancelled — '.$project->project_number,
            default => 'New meeting request for your website project '.$project->project_number,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.project-meeting', with: [
            'meeting' => $this->meeting,
            'event' => $this->event,
            'toStaff' => $this->toStaff,
        ]);
    }

    public function attachments(): array
    {
        // Cancelled meetings don't ship an invite.
        if ($this->event === 'cancelled') {
            return [];
        }

        $meeting = $this->meeting;
        $ics = IcsBuilder::event(
            uid: 'meeting-'.$meeting->id.'@planeticweb.com',
            title: 'Planetic Web — '.($meeting->topic ?: 'Project meeting ('.$meeting->project->project_number.')'),
            start: $meeting->effectiveTime(),
            durationMinutes: $meeting->duration_minutes,
            description: $meeting->notes,
            url: $meeting->meeting_url,
        );

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $ics, 'meeting.ics')
                ->withMime('text/calendar'),
        ];
    }
}
