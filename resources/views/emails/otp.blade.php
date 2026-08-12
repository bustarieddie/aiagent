<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kod Login Klinik Bustari</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:420px;background:#ffffff;border-radius:16px;border:1px solid #e5e7eb;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px 8px;text-align:center;">
                            <div style="font-size:32px;line-height:1;">💬</div>
                            <h1 style="margin:12px 0 2px;font-size:20px;color:#111827;">Klinik Bustari</h1>
                            <p style="margin:0;font-size:13px;color:#6b7280;">WhatsApp AI Agent Admin</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px 8px;text-align:center;">
                            <p style="margin:0 0 12px;font-size:14px;color:#374151;">Kod login sekali guna anda:</p>
                            <div style="display:inline-block;padding:12px 24px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;font-size:32px;letter-spacing:10px;font-weight:bold;color:#047857;font-family:'Courier New',monospace;">
                                {{ $code }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 32px 28px;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#6b7280;">
                                Sah selama {{ $ttl }} minit. Jangan kongsi kod ini dengan sesiapa.
                            </p>
                            <p style="margin:12px 0 0;font-size:11px;color:#9ca3af;">
                                Jika anda tidak meminta kod ini, abaikan e-mel ini.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
