<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Klinik Bustari — Login Code</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f8fafc; padding:20px; margin:0;">
    <div style="max-width:520px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden;">
        <div style="background:linear-gradient(135deg,#1e3a8a,#059669); color:#fff; padding:24px; text-align:center;">
            <h1 style="margin:0; font-size:20px;">Klinik Bustari Admin</h1>
            <p style="margin:6px 0 0; font-size:12px; opacity:.85;">WhatsApp AI Agent Portal</p>
        </div>
        <div style="padding:32px 24px;">
            <p style="color:#374151; margin:0 0 16px;">Assalamualaikum,</p>
            <p style="color:#374151; margin:0 0 24px;">Kod login sekali guna anda:</p>
            <div style="background:#f1f5f9; border:2px dashed #94a3b8; border-radius:12px; padding:24px; text-align:center; margin-bottom:24px;">
                <div style="font-size:36px; font-weight:800; letter-spacing:12px; color:#059669; font-family:'Courier New', monospace;">
                    {{ $code }}
                </div>
            </div>
            <p style="color:#64748b; font-size:13px; margin:0 0 8px;">
                Kod ini sah selama <strong>{{ $ttlMinutes }} minit</strong>.
            </p>
            <p style="color:#64748b; font-size:13px; margin:0;">
                Kalau anda tidak minta login, abaikan mesej ini.
            </p>
        </div>
        <div style="background:#f8fafc; padding:16px; text-align:center; color:#94a3b8; font-size:11px; border-top:1px solid #e2e8f0;">
            Klinik Bustari · Petra Jaya, Kuching
        </div>
    </div>
</body>
</html>
