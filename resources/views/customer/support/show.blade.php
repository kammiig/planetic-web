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
            </div>
        @endforeach
    </div>

    {{-- Reply --}}
    <form method="POST" action="{{ route('customer.support.reply', $ticket) }}" class="mt-6 card" novalidate>
        @csrf
        <label for="reply" class="label">Add a reply</label>
        <textarea id="reply" name="message" rows="4" required class="input @error('message') input-error @enderror">{{ old('message') }}</textarea>
        @error('message')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        <button type="submit" class="btn-primary mt-4">Send reply</button>
    </form>
@endsection
