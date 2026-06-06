@props(['url'])
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
    <tr>
        <td style="border-radius:10px;background:#2563eb;">
            <a href="{{ $url }}" style="display:inline-block;padding:12px 24px;color:#ffffff;font-weight:600;font-size:16px;text-decoration:none;border-radius:10px;">{{ $slot }}</a>
        </td>
    </tr>
</table>
