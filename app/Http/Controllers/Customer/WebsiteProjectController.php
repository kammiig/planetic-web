<?php

namespace App\Http\Controllers\Customer;

use App\Enums\WebsiteProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteProjectIntakeRequest;
use App\Mail\ProjectMeetingMail;
use App\Mail\ProjectMessageMail;
use App\Models\WebsiteProject;
use App\Models\WebsiteProjectAsset;
use App\Models\WebsiteProjectMessage;
use App\Models\WebsiteProjectMessageAttachment;
use App\Services\Notifications\AdminNotifier;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteProjectController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.projects.index', [
            'projects' => $request->user()->websiteProjects()->with(['order.items', 'order.hostingAccount', 'domain'])->latest()->get(),
        ]);
    }

    public function show(Request $request, WebsiteProject $project): View
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $project->load([
            'assets', 'domain', 'hostingAccount', 'order.items', 'order.hostingAccount',
            'publicMessages.author', 'publicMessages.attachments',
            'meetings.requester',
        ]);

        return view('customer.projects.show', ['project' => $project]);
    }

    public function storeIntake(WebsiteProjectIntakeRequest $request, WebsiteProject $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $project->fill([
            'business_name' => $request->validated('business_name'),
            'business_description' => $request->validated('business_description'),
            'industry' => $request->validated('industry'),
            'pages_required' => $request->validated('pages_required', []),
            'brand_colours' => $request->validated('brand_colours'),
            'reference_websites' => $request->referenceWebsites(),
            'special_requirements' => $request->validated('special_requirements'),
            'content_received' => true,
            'status' => WebsiteProjectStatus::ContentReceived->value,
        ]);

        if ($request->hasFile('logo')) {
            $this->storeAsset($project, $request->file('logo'), 'logo');
            $project->logo_received = true;
        }

        foreach ($request->file('files', []) as $file) {
            $this->storeAsset($project, $file, $this->fileTypeFor($file->getClientMimeType()));
        }

        $project->save();

        return redirect()->route('customer.projects.show', $project)
            ->with('success', 'Thanks! Your project details have been submitted — our team will get started.');
    }

    /** Post a message into the project workspace, with optional attachments. */
    public function storeMessage(Request $request, WebsiteProject $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'extensions:jpg,jpeg,png,pdf,doc,docx,txt,zip', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt,zip'],
        ], [
            'attachments.*.max' => 'Each attachment must be 10 MB or smaller.',
            'attachments.*.extensions' => 'Allowed file types: jpg, png, pdf, doc, docx, txt, zip.',
            'attachments.*.mimes' => 'Allowed file types: jpg, png, pdf, doc, docx, txt, zip.',
        ]);

        $message = $project->messages()->create([
            'user_id' => $request->user()->id,
            'is_from_staff' => false,
            'is_internal_note' => false,
            'body' => $validated['body'],
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store("website-projects/{$project->id}/messages", 'local');
            $message->attachments()->create([
                'website_project_id' => $project->id,
                'user_id' => $request->user()->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize() ?: 0,
            ]);
        }

        // Notify the team a customer replied (best-effort; never blocks).
        app(AdminNotifier::class)->alert(
            'New customer message on '.$project->project_number,
            'A customer posted a message in their website project workspace.',
            array_filter([
                'Project' => $project->project_number,
                'Customer' => $request->user()->email,
                'Message' => \Illuminate\Support\Str::limit($validated['body'], 160),
            ]),
            url('/admin/website-projects/'.$project->id.'/edit'),
            'Open project',
        );

        return back()->with('success', 'Your message has been sent to the team.');
    }

    /** Customer requests a revision during the revision window. */
    public function requestRevision(Request $request, WebsiteProject $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        if (! $project->canRequestRevision()) {
            return back()->with('error', $project->revisionWindowHasEnded()
                ? 'Your revision period has ended. Contact us if you still need changes and we can reopen it.'
                : 'Revisions can be requested once your website has been delivered for review.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $project->messages()->create([
            'user_id' => $request->user()->id,
            'is_from_staff' => false,
            'is_internal_note' => false,
            'body' => "Revision requested:\n\n".$validated['body'],
        ]);

        $project->update([
            'status' => WebsiteProjectStatus::RevisionsInProgress->value,
            'revisions_used' => $project->revisions_used + 1,
        ]);

        app(AdminNotifier::class)->alert(
            'Revision requested on '.$project->project_number,
            'The customer has requested a revision within their revision window.',
            array_filter([
                'Project' => $project->project_number,
                'Customer' => $request->user()->email,
                'Revision #' => (string) $project->revisions_used,
                'Details' => \Illuminate\Support\Str::limit($validated['body'], 200),
            ]),
            url('/admin/website-projects/'.$project->id.'/edit'),
            'Open project',
        );

        return back()->with('success', 'Your revision request has been sent — our team will get on it.');
    }

    /** Customer proposes a meeting time with the developer. */
    public function requestMeeting(Request $request, WebsiteProject $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $validated = $request->validate([
            'proposed_at' => ['required', 'date', 'after:+1 hour'],
            'topic' => ['nullable', 'string', 'max:160'],
            'duration_minutes' => ['nullable', 'integer', 'in:15,30,45,60'],
        ], [
            'proposed_at.after' => 'Please choose a time at least an hour from now.',
        ]);

        $meeting = $project->meetings()->create([
            'requested_by' => $request->user()->id,
            'status' => 'requested',
            'topic' => $validated['topic'] ?? null,
            'proposed_at' => $validated['proposed_at'],
            'duration_minutes' => $validated['duration_minutes'] ?? 30,
        ]);

        // Email the customer a "requested" acknowledgement + alert the team.
        app(NotificationService::class)->send($request->user(), new ProjectMeetingMail($meeting, 'requested'), 'project_meeting');
        app(AdminNotifier::class)->alert(
            'Meeting requested on '.$project->project_number,
            'A customer proposed a meeting time. Confirm or reschedule it from the admin panel.',
            array_filter([
                'Project' => $project->project_number,
                'Customer' => $request->user()->email,
                'Proposed time' => $meeting->proposed_at->format('l j M Y, g:i A'),
                'Topic' => $meeting->topic,
            ]),
            url('/admin/website-projects/'.$project->id.'/edit'),
            'Confirm meeting',
        );

        return back()->with('success', 'Your meeting request has been sent. We will confirm a time and send a calendar invite.');
    }

    public function downloadAsset(Request $request, WebsiteProject $project, WebsiteProjectAsset $asset): StreamedResponse
    {
        // Files are private and served only through this authenticated route.
        abort_unless($project->isOwnedBy($request->user()), 404);
        abort_unless($asset->website_project_id === $project->id && $asset->isOwnedBy($request->user()), 404);

        return Storage::disk('local')->download($asset->stored_path, $asset->original_filename);
    }

    public function downloadMessageAttachment(Request $request, WebsiteProject $project, WebsiteProjectMessageAttachment $attachment): StreamedResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);
        abort_unless($attachment->website_project_id === $project->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    private function storeAsset(WebsiteProject $project, $file, string $type): void
    {
        // Stored outside the public web root; never directly guessable.
        $path = $file->store("website-projects/{$project->id}", 'local');

        $project->assets()->create([
            'user_id' => $project->user_id,
            'file_type' => $type,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    private function fileTypeFor(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            $mime === 'application/pdf', str_contains($mime, 'word'), $mime === 'text/plain' => 'content_document',
            default => 'other',
        };
    }
}
