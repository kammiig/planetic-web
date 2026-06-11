@extends('layouts.customer')

@section('title', $ticket->ticket_number)
@section('page-title', 'Support ticket')

@section('content')
    <a href="{{ route('customer.support.index') }}" class="text-sm font-medium text-primary-600 hover:underline">← Back to support</a>

    <div class="mt-4 card-dash">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold">{{ $ticket->subject }}</h2>
                <p class="text-sm text-slate-500">{{ $ticket->ticket_number }} · {{ ucfirst($ticket->category ?? 'general') }}</p>
            </div>
            <x-status-badge :status="$ticket->status" />
        </div>
    </div>

    {{-- Conversation (public messages only — internal notes are never loaded) --}}
    <div class="mt-6 space-y-4">
        @foreach ($ticket->publicMessages as $message)
            @php $isStaff = $message->author && $message->author->is_admin; @endphp
            <div class="card-dash {{ $isStaff ? 'border-l-4 border-primary-500' : '' }}">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-semibold">{{ $isStaff ? 'Planetic Web Support' : ($message->author->name ?? 'You') }}</span>
                    <span class="text-slate-400">{{ $message->created_at->diffForHumans() }}</span>
                </div>
                <p class="mt-2 whitespace-pre-line text-slate-700">{{ $message->message }}</p>

                @if ($message->attachments->isNotEmpty())
                    <ul class="mt-3 flex flex-wrap gap-2" aria-label="Attachments">
                        @foreach ($message->attachments as $attachment)
                            <li>
                                <a href="{{ route('customer.support.attachments.download', [$ticket, $attachment]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-[10px] border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-primary-300 hover:text-primary-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                    {{ $attachment->original_name }}
                                    <span class="text-xs text-slate-400">({{ $attachment->humanSize() }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Reply --}}
    <form method="POST" action="{{ route('customer.support.reply', $ticket) }}" enctype="multipart/form-data" class="mt-6 card" novalidate>
        @csrf
        <label for="reply" class="label">Add a reply</label>
        <textarea id="reply" name="message" rows="4" required class="input @error('message') input-error @enderror">{{ old('message') }}</textarea>
        @error('message')<p class="field-error" role="alert">{{ $message }}</p>@enderror

        <div class="mt-4">
            <label for="reply_attachments" class="label">Attachments (optional)</label>
            <input id="reply_attachments" type="file" name="attachments[]" multiple
                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip"
                   class="block w-full cursor-pointer rounded-[10px] border border-slate-300 text-sm text-slate-600 file:mr-3 file:cursor-pointer file:rounded-l-[9px] file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
                   aria-describedby="reply_attachments_help">
            <p id="reply_attachments_help" class="help-text">Up to 5 files, 10&nbsp;MB each. Allowed: jpg, png, pdf, doc, docx, txt, zip.</p>
            @error('attachments')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            @error('attachments.*')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary mt-4">Send reply</button>
    </form>
@endsection
