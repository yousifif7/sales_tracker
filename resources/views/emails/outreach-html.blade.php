<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Outreach' }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f6;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:18px 28px;background:#0f172a;border-bottom:3px solid #0ea5e9;">
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#7dd3fc;font-weight:700;">
                                FieldLine
                            </div>
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.4;color:#e2e8f0;margin-top:4px;">
                                White-label security control room
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.65;color:#1f2937;">
                            {!! $bodyHtml !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 28px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding-top:18px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.55;color:#4b5563;">
                                        <div style="font-size:15px;font-weight:700;color:#0f172a;">{{ $signatureName }}</div>
                                        <div style="margin-top:2px;color:#64748b;">{{ $signatureTitle }}</div>
                                        @if (filled($signatureWebsite))
                                            <div style="margin-top:10px;">
                                                <a href="{{ $signatureWebsite }}" style="color:#0284c7;text-decoration:none;font-weight:600;">{{ $signatureWebsite }}</a>
                                            </div>
                                        @endif
                                        @if (filled($signatureEmail))
                                            <div style="margin-top:2px;">
                                                <a href="mailto:{{ $signatureEmail }}" style="color:#0284c7;text-decoration:none;">{{ $signatureEmail }}</a>
                                            </div>
                                        @endif
                                        <div style="margin-top:12px;font-size:12px;color:#9ca3af;">
                                            Reply to this email if useful.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if (filled($trackingPixelUrl ?? null))
        <img src="{{ $trackingPixelUrl }}" width="1" height="1" alt="" style="display:none;width:1px;height:1px;border:0;" />
    @endif
</body>
</html>
