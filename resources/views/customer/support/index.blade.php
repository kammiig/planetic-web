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
            <form method="POST" action="{{ route('customer.support.store') }}" enctype="multipart/form-data" class="space-y-4" novalidate>
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
                <div>
                    <label for="attachments" class="label">Attachments (optional)</label>
                    <input id="attachments" type="file" name="attachments[]" multiple
                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip"
                           class="block w-full cursor-pointer rounded-[10px] border border-slate-300 text-sm text-slate-600 file:mr-3 file:cursor-pointer file:rounded-l-[9px] file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
                           aria-describedby="attachments_help">
                    <p id="attachments_help" class="help-text">Up to 5 files, 10&nbsp;MB each. Allowed: jpg, png, pdf, doc, docx, txt, zip.</p>
                    @error('attachments')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    @error('attachments.*')<p class="field-error" role="alert">{{ $message }}</p>@enderror
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
