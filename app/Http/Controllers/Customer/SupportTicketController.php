<?php

namespace App\Http\Controllers\Customer;

use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.support.index', [
            'tickets' => $request->user()->supportTickets()->latest()->paginate(15),
        ]);
    }

    public function store(SupportTicketRequest $request): RedirectResponse
    {
        $ticket = $request->user()->supportTickets()->create([
            'ticket_number' => 'TMP-'.uniqid(),
            'subject' => $request->validated('subject'),
            'category' => $request->validated('category'),
            'status' => SupportTicketStatus::Open->value,
        ]);

        $ticket->update(['ticket_number' => 'TCK-'.(10000 + $ticket->id)]);

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $request->validated('message'),
            'is_internal_note' => false,
        ]);

        $this->storeAttachments($request, $ticket, $message);

        return redirect()->route('customer.support.show', $ticket)
            ->with('success', 'Your support request has been submitted.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->isOwnedBy($request->user()), 404);

        // Only non-internal messages are ever loaded for the customer.
        $ticket->load(['publicMessages.author', 'publicMessages.attachments']);

        return view('customer.support.show', ['ticket' => $ticket]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->isOwnedBy($request->user()), 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            ...SupportTicketRequest::attachmentRules(),
        ], (new SupportTicketRequest)->messages());

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_internal_note' => false,
        ]);

        $this->storeAttachments($request, $ticket, $message);

        // A customer reply re-opens a resolved/closed ticket.
        if ($ticket->status->isClosed()) {
            $ticket->update(['status' => SupportTicketStatus::Open->value, 'closed_at' => null]);
        } else {
            $ticket->update(['status' => SupportTicketStatus::Open->value]);
        }

        return back()->with('success', 'Your reply has been sent.');
    }

    /**
     * Download an attachment. Private disk + ownership check: only the ticket's
     * owner ever reaches the file, and the stored (hashed) path is never exposed.
     */
    public function downloadAttachment(Request $request, SupportTicket $ticket, SupportTicketAttachment $attachment): StreamedResponse
    {
        abort_unless($ticket->isOwnedBy($request->user()), 404);
        abort_unless($attachment->support_ticket_id === $ticket->id, 404);

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    /**
     * Persist validated uploads to the PRIVATE local disk (hashed filenames,
     * outside the web root) and link them to the ticket + message.
     */
    private function storeAttachments(Request $request, SupportTicket $ticket, SupportTicketMessage $message): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('support-attachments/'.$ticket->id, 'local');

            $ticket->attachments()->create([
                'support_ticket_message_id' => $message->id,
                'user_id' => $request->user()->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize() ?: 0,
            ]);
        }
    }
}
