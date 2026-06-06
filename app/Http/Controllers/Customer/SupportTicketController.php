<?php

namespace App\Http\Controllers\Customer;

use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTicketRequest;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $request->validated('message'),
            'is_internal_note' => false,
        ]);

        return redirect()->route('customer.support.show', $ticket)
            ->with('success', 'Your support request has been submitted.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->isOwnedBy($request->user()), 404);

        // Only non-internal messages are ever loaded for the customer.
        $ticket->load(['publicMessages.author']);

        return view('customer.support.show', ['ticket' => $ticket]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->isOwnedBy($request->user()), 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_internal_note' => false,
        ]);

        // A customer reply re-opens a resolved/closed ticket.
        if ($ticket->status->isClosed()) {
            $ticket->update(['status' => SupportTicketStatus::Open->value, 'closed_at' => null]);
        } else {
            $ticket->update(['status' => SupportTicketStatus::Open->value]);
        }

        return back()->with('success', 'Your reply has been sent.');
    }
}
