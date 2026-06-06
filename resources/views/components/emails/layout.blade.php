@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Inter,Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                    <tr>
                        <td style="background:#0b1b33;border-radius:14px 14px 0 0;padding:24px 32px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:800;">Planetic<span style="color:#2563eb;">Web</span></span>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;padding:32px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;border-radius:0 0 14px 14px;border:1px solid #e2e8f0;border-top:0;padding:24px 32px;color:#64748b;font-size:13px;">
                            <p style="margin:0 0 8px;">Planetic Web · planeticweb.com</p>
                            <p style="margin:0;">Need help? Email <a href="mailto:{{ config('billing.support_email') }}" style="color:#2563eb;">{{ config('billing.support_email') }}</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
