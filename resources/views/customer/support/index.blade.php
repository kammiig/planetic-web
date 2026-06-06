@extends('layouts.customer')

@section('title', 'Support')
@section('page-title', 'Support')

@section('content')
    <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Your support tickets</h2>
            <button type="button" @click="open = !open" class="btn-primary btn-sm">New ticket</button>
        </div>

        {{-- New ticket form --}}
        <div x-show="open" x-collapse x-cloak class="card mt-4">
            <form method="POST" action="{{ route('customer.support.store') }}" class="space-y-4" novalidate>
                @csrf
                <x-field name="subject" label="Subject" :required="true" />
                <div>
                    <label for="category" class="label">Category</label>
                    <select id="category" name="category" class="input">
                        @foreach (['general' => 'General', 'billing' => 'Billing', 'domain' => 'Domain', 'hosting' => 'Hosting', 'website' => 'Website', 'technical' => 'Technical'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="message" class="label">Message <span class="required-mark" aria-hidden="true">*</span></label>
                    <textarea id="message" name="message" rows="4" required class="input @error('message') input-error @enderror">{{ old('message') }}</textarea>
                    @error('message')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-primary">Submit ticket</button>
            </form>
        </div>
    </div>

    @if ($tickets->isEmpty())
        <div class="card mt-6 text-center text-slate-500">
            You have no support tickets. Need help? Create a support request above.
        </div>
    @else
        <div class="table-wrap mt-6">
            <table class="table-base">
                <caption class="sr-only">Your support tickets</caption>
                <thead>
                    <tr><th scope="col">Ticket</th><th scope="col">Subject</th><th scope="col">Status</th><th scope="col">Updated</th><th scope="col"><span class="sr-only">View</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td class="font-semibold">{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td><x-status-badge :status="$ticket->status" /></td>
                            <td>{{ $ticket->updated_at->diffForHumans() }}</td>
                            <td class="text-right"><a href="{{ route('customer.support.show', $ticket) }}" class="btn-secondary btn-sm">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tickets->links() }}</div>
    @endif
@endsection
