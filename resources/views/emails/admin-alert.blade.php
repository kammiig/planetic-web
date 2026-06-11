<x-emails.layout :title="$alertSubject">
    <h1 style="margin:0 0 8px;font-size:22px;">{{ $alertSubject }}</h1>
    <p style="margin:0 0 16px;color:#334155;">{{ $intro }}</p>

    @if (! empty($details))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border-collapse:collapse;">
            @foreach ($details as $label => $value)
                <tr>
                    <td style="padding:6px 12px 6px 0;color:#64748b;font-size:14px;white-space:nowrap;vertical-align:top;">{{ $label }}</td>
                    <td style="padding:6px 0;color:#0f172a;font-size:14px;font-weight:600;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($actionUrl)
        <x-emails.button :url="$actionUrl">{{ $actionLabel ?? 'Open admin panel' }}</x-emails.button>
    @endif
</x-emails.layout>
