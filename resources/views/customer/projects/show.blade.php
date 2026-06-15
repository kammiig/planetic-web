@extends('layouts.customer')

@section('title', $project->business_name ?? 'Website project')
@section('page-title', 'Website project')

@php
    $needsIntake = in_array($project->status, [\App\Enums\WebsiteProjectStatus::OrderReceived, \App\Enums\WebsiteProjectStatus::InformationRequired], true);
    $pageOptions = ['Home', 'About', 'Services', 'Products', 'Portfolio', 'Blog', 'Contact', 'FAQ', 'Pricing', 'Testimonials'];
@endphp

@section('content')
    <a href="{{ route('customer.projects.index') }}" class="text-sm font-medium text-primary-600 hover:underline">← Back to projects</a>

    <div class="mt-4 card-dash">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold">{{ $project->business_name ?? 'Your website project' }}</h2>
                <p class="text-sm text-slate-500">{{ $project->project_number }}</p>
            </div>
            <x-status-badge :status="$project->status" />
        </div>
        @if ($action = $project->status->customerNextAction())
            <div class="alert alert-info mt-4">{{ $action }}</div>
        @endif

        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-slate-500">Domain</dt>
                <dd class="font-medium">{{ $project->domain?->domain_name ?? $project->order?->primaryDomainName() ?? 'Waiting for your domain' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Hosting</dt>
                <dd class="font-medium">
                    @if ($hosting = $project->order?->hostingAccount)
                        {{ $hosting->status->customerLabel() }}
                    @else
                        Included — set up after your domain
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">Order</dt>
                <dd class="font-medium">{{ $project->order?->order_number ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    @php
        $orderNeedsDomain = $project->order
            && $project->order->isPaid()
            && blank($project->order->primaryDomainName())
            && ! $project->domain;
    @endphp
    @if ($orderNeedsDomain)
        @include('customer.partials.add-domain-form', ['order' => $project->order])
    @endif

    @if ($needsIntake)
        {{-- Intake form --}}
        <form method="POST" action="{{ route('customer.projects.intake', $project) }}" enctype="multipart/form-data" class="mt-6 card space-y-5" novalidate>
            @csrf
            <h2 class="text-lg font-bold">Tell us about your website</h2>

            <x-field name="business_name" label="Business name" :value="$project->business_name" :required="true" />

            <div>
                <label for="business_description" class="label">Business description</label>
                <textarea id="business_description" name="business_description" rows="3" class="input">{{ old('business_description', $project->business_description) }}</textarea>
                <p class="help-text">A short description of what your business does.</p>
            </div>

            <x-field name="industry" label="Industry" :value="$project->industry" />

            <fieldset>
                <legend class="label">Pages you need</legend>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($pageOptions as $page)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="pages_required[]" value="{{ $page }}" class="h-5 w-5 rounded border-slate-300 text-primary-500 focus:ring-primary-500"
                                   @checked(in_array($page, old('pages_required', $project->pages_required ?? [])))>
                            {{ $page }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <x-field name="brand_colours" label="Brand colours" :value="$project->brand_colours" placeholder="e.g. Navy blue and white" />

            <div>
                <label for="reference_websites" class="label">Reference websites</label>
                <textarea id="reference_websites" name="reference_websites" rows="2" class="input" placeholder="One URL per line">{{ old('reference_websites', collect($project->reference_websites)->implode("\n")) }}</textarea>
                <p class="help-text">Websites you like, one per line.</p>
            </div>

            <div>
                <label for="special_requirements" class="label">Special requirements</label>
                <textarea id="special_requirements" name="special_requirements" rows="3" class="input">{{ old('special_requirements', $project->special_requirements) }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="logo" class="label">Logo</label>
                    <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp,.pdf" class="input">
                    @error('logo')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="files" class="label">Images &amp; content files</label>
                    <input type="file" id="files" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt" class="input">
                    @error('files.*')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    <p class="help-text">JPG, PNG, WEBP, PDF, DOC, DOCX or TXT. Max 10 MB each.</p>
                </div>
            </div>

            <button type="submit" class="btn-primary">Submit project details</button>
        </form>
    @else
        {{-- Submitted summary --}}
        <div class="mt-6 card-dash">
            <h2 class="text-lg font-bold">Your submitted details</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-slate-500">Business</dt><dd class="font-medium">{{ $project->business_name }}</dd></div>
                @if ($project->business_description)<div><dt class="text-slate-500">Description</dt><dd>{{ $project->business_description }}</dd></div>@endif
                @if ($project->pages_required)<div><dt class="text-slate-500">Pages</dt><dd>{{ implode(', ', $project->pages_required) }}</dd></div>@endif
            </dl>

            @if ($project->assets->isNotEmpty())
                <h3 class="mt-6 text-sm font-semibold text-slate-700">Uploaded files</h3>
                <ul class="mt-2 divide-y divide-slate-200">
                    @foreach ($project->assets as $asset)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span>{{ $asset->original_filename }} <span class="text-slate-400">({{ $asset->humanFileSize() }})</span></span>
                            <a href="{{ route('customer.projects.assets.download', [$project, $asset]) }}" class="font-medium text-primary-600 hover:underline">Download</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- ===================== Delivery / revision ===================== --}}
    @php $windowEnds = $project->revisionWindowEndsAt(); @endphp
    @if ($project->delivered_at || in_array($project->status, [\App\Enums\WebsiteProjectStatus::Delivered, \App\Enums\WebsiteProjectStatus::ReviewRequired, \App\Enums\WebsiteProjectStatus::RevisionsInProgress], true))
        <div class="mt-6 card-dash">
            <h2 class="text-lg font-bold">Delivery &amp; revisions</h2>

            @if ($project->canRequestRevision())
                <div class="alert alert-info mt-3">
                    Your website is ready for review. You can request changes until
                    <strong>{{ $windowEnds?->format('j M Y') }}</strong>
                    ({{ $windowEnds?->diffForHumans() }}).
                    @if ($project->revisions_used > 0)<span class="block text-sm">Revisions requested so far: {{ $project->revisions_used }}.</span>@endif
                </div>

                <form method="POST" action="{{ route('customer.projects.revision', $project) }}" class="mt-4 space-y-3" novalidate>
                    @csrf
                    <label for="revision_body" class="label">Request a revision</label>
                    <textarea id="revision_body" name="body" rows="3" required class="input @error('body') input-error @enderror" placeholder="Describe the changes you'd like…">{{ old('body') }}</textarea>
                    @error('body')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    <button type="submit" class="btn-primary">Request revision</button>
                </form>
            @elseif ($project->revisionWindowHasEnded())
                <div class="alert alert-warning mt-3">
                    Your revision period ended on <strong>{{ $windowEnds?->format('j M Y') }}</strong>.
                    Need more changes? <a href="{{ route('customer.support.index') }}" class="font-semibold underline">Contact support</a> and we can reopen it.
                </div>
            @else
                <p class="mt-3 text-sm text-slate-600">We'll let you know the moment your website is delivered for review.</p>
            @endif
        </div>
    @endif

    {{-- ===================== Project conversation ===================== --}}
    <div class="mt-6 card-dash">
        <h2 class="text-lg font-bold">Project messages</h2>
        <p class="text-sm text-slate-500">Chat with your developer about your website.</p>

        <div class="mt-4 space-y-4">
            @forelse ($project->publicMessages as $message)
                @php $staff = $message->is_from_staff; @endphp
                <div class="rounded-[12px] border p-4 {{ $staff ? 'border-primary-200 bg-primary-50/40' : 'border-slate-200' }}">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold">{{ $staff ? 'Planetic Web team' : ($message->author->name ?? 'You') }}</span>
                        <span class="text-slate-400">{{ $message->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-slate-700">{{ $message->body }}</p>
                    @if ($message->attachments->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($message->attachments as $attachment)
                                <li>
                                    <a href="{{ route('customer.projects.messages.download', [$project, $attachment]) }}"
                                       class="inline-flex items-center gap-1.5 rounded-[10px] border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:border-primary-300 hover:text-primary-600">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                        {{ $attachment->original_name }} <span class="text-xs text-slate-400">({{ $attachment->humanSize() }})</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No messages yet — start the conversation below.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('customer.projects.messages.store', $project) }}" enctype="multipart/form-data" class="mt-5 space-y-3" novalidate>
            @csrf
            <label for="message_body" class="label">Add a message</label>
            <textarea id="message_body" name="body" rows="3" required class="input @error('body') input-error @enderror">{{ old('body') }}</textarea>
            @error('body')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            <div>
                <label for="message_attachments" class="label">Attachments (optional)</label>
                <input id="message_attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip"
                       class="block w-full cursor-pointer rounded-[10px] border border-slate-300 text-sm text-slate-600 file:mr-3 file:cursor-pointer file:rounded-l-[9px] file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                <p class="help-text">Up to 5 files, 10&nbsp;MB each. jpg, png, pdf, doc, docx, txt, zip.</p>
                @error('attachments.*')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary">Send message</button>
        </form>
    </div>

    {{-- ===================== Meetings ===================== --}}
    <div class="mt-6 card-dash" x-data="{ open: {{ $errors->has('proposed_at') ? 'true' : 'false' }} }">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">Meetings</h2>
                <p class="text-sm text-slate-500">Schedule a call with your developer.</p>
            </div>
            <button type="button" @click="open = !open" class="btn-secondary btn-sm">Request a meeting</button>
        </div>

        @if ($project->meetings->isNotEmpty())
            <ul class="mt-4 divide-y divide-slate-200">
                @foreach ($project->meetings->sortByDesc('proposed_at') as $meeting)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <div>
                            <p class="font-medium text-slate-900">{{ $meeting->effectiveTime()->format('l j M Y, g:i A') }} <span class="text-sm text-slate-400">({{ $meeting->duration_minutes }} min)</span></p>
                            @if ($meeting->topic)<p class="text-sm text-slate-500">{{ $meeting->topic }}</p>@endif
                            @if ($meeting->meeting_url && $meeting->isConfirmed())<a href="{{ $meeting->meeting_url }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-600 hover:underline">Join link</a>@endif
                        </div>
                        <span class="badge {{ $meeting->isConfirmed() ? 'badge-success' : 'badge-warning' }}">{{ $meeting->statusLabel() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <form x-show="open" x-cloak method="POST" action="{{ route('customer.projects.meeting', $project) }}" class="mt-4 space-y-3" novalidate>
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="proposed_at" class="label">Preferred date &amp; time <span class="required-mark">*</span></label>
                    <input type="datetime-local" id="proposed_at" name="proposed_at" required class="input @error('proposed_at') input-error @enderror" value="{{ old('proposed_at') }}">
                    @error('proposed_at')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="duration_minutes" class="label">Duration</label>
                    <select id="duration_minutes" name="duration_minutes" class="input">
                        @foreach ([15, 30, 45, 60] as $mins)
                            <option value="{{ $mins }}" @selected(old('duration_minutes', 30) == $mins)>{{ $mins }} minutes</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-field name="topic" label="What would you like to discuss? (optional)" :value="old('topic')" />
            <button type="submit" class="btn-primary">Send meeting request</button>
            <p class="help-text">We'll confirm a time and email you a calendar invite (.ics) you can add to Google Calendar, Outlook or Apple Calendar.</p>
        </form>
    </div>
@endsection
