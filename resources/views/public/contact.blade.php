@extends('layouts.public')

@section('title', 'Contact Us — UK Website Design & Hosting Support')
@section('meta_description', 'Get in touch with Planetic Web for help with domains, hosting, DNS or your bespoke website project. Friendly, UK-based support.')

@section('content')
    <section class="container-px py-16">
        <div class="mx-auto grid max-w-5xl gap-10 lg:grid-cols-2">
            <div>
                <h1 class="text-3xl font-bold">Get in touch</h1>
                <p class="mt-3 text-slate-600">Questions about domains, hosting or a new website? Send us a message and we'll get back to you.</p>

                <dl class="mt-8 space-y-4 text-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                        <div>
                            <dt class="font-semibold text-slate-900">Support</dt>
                            <dd><a class="text-primary-600 hover:underline" href="mailto:{{ config('billing.support_email') }}">{{ config('billing.support_email') }}</a></dd>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                        <div>
                            <dt class="font-semibold text-slate-900">Billing</dt>
                            <dd><a class="text-primary-600 hover:underline" href="mailto:{{ config('billing.billing_email') }}">{{ config('billing.billing_email') }}</a></dd>
                        </div>
                    </div>
                </dl>
            </div>

            <div class="card">
                <form method="POST" action="{{ route('contact.store') }}" class="space-y-4" novalidate>
                    @csrf
                    <x-field name="name" label="Your name" autocomplete="name" :required="true" />
                    <x-field name="email" label="Email address" type="email" autocomplete="email" :required="true" />
                    <x-field name="subject" label="Subject" />
                    <div>
                        <label for="message" class="label">Message <span class="required-mark" aria-hidden="true">*</span><span class="sr-only">(required)</span></label>
                        <textarea id="message" name="message" rows="5" required
                                  class="input @error('message') input-error @enderror"
                                  @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
                        @error('message')
                            <p id="message-error" class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-primary w-full">Send message</button>
                </form>
            </div>
        </div>
    </section>
@endsection
