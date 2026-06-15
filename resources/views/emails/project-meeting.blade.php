<x-emails.layout title="Project meeting">
    @php
        $heading = match ($event) {
            'confirmed' => 'Your meeting is confirmed',
            'rescheduled' => 'Your meeting has been rescheduled',
            'cancelled' => 'Your meeting was cancelled',
            default => 'New meeting request',
        };
    @endphp
    <h1 style="margin:0 0 8px;font-size:22px;">{{ $heading }}</h1>
    <p style="margin:0 0 16px;color:#334155;">
        Website project <strong>{{ $meeting->project->project_number }}</strong>
        ({{ $meeting->project->business_name ?? 'Your website' }})
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border-collapse:collapse;">
        <tr>
            <td style="padding:6px 12px 6px 0;color:#64748b;font-size:14px;white-space:nowrap;">When</td>
            <td style="padding:6px 0;color:#0f172a;font-size:14px;font-weight:600;">{{ $meeting->effectiveTime()->format('l j M Y, g:i A') }} ({{ $meeting->duration_minutes }} min)</td>
        </tr>
        @if ($meeting->topic)
            <tr><td style="padding:6px 12px 6px 0;color:#64748b;font-size:14px;">Topic</td><td style="padding:6px 0;color:#0f172a;font-size:14px;font-weight:600;">{{ $meeting->topic }}</td></tr>
        @endif
        @if ($meeting->meeting_url)
            <tr><td style="padding:6px 12px 6px 0;color:#64748b;font-size:14px;">Link</td><td style="padding:6px 0;font-size:14px;"><a href="{{ $meeting->meeting_url }}">{{ $meeting->meeting_url }}</a></td></tr>
        @endif
    </table>

    @if ($event === 'requested' && ! $toStaff)
        <p style="margin:0 0 16px;color:#334155;">We'll confirm a time shortly and send you a calendar invite.</p>
    @elseif ($event !== 'cancelled')
        <p style="margin:0 0 16px;color:#334155;">A calendar invite (<strong>meeting.ics</strong>) is attached — open it to add this to your calendar.</p>
    @endif

    @if ($toStaff)
        <x-emails.button url="{{ url('/admin/website-projects/'.$meeting->website_project_id.'/edit') }}">Manage in admin</x-emails.button>
    @else
        <x-emails.button url="{{ url('/dashboard/website-projects/'.$meeting->website_project_id) }}">View in your dashboard</x-emails.button>
    @endif
</x-emails.layout>
