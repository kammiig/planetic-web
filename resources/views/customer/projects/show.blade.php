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
@endsection
