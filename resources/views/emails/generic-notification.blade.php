@php($delivery = $delivery ?? null)
<!doctype html>
<html lang="id">
<body style="margin:0;background:#f4f8f3;color:#17301e;font-family:Arial,sans-serif;padding:24px;">
    <main style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #d9e8d8;border-radius:14px;overflow:hidden;">
        <div style="height:5px;background:#477c3a;"></div>
        <div style="padding:28px;">
            <p style="margin:0 0 8px;color:#477c3a;font-size:12px;font-weight:700;letter-spacing:.08em;">NOTIFIKASI PASPAPAN</p>
            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#17301e;">{{ $delivery->subject }}</h1>
            <p style="margin:0 0 14px;font-size:15px;line-height:1.65;white-space:pre-line;color:#405b47;">Halo {{ $delivery->recipient_name ?: 'Rekan Kerja' }},

{{ $delivery->body }}</p>
            @if(filled($delivery->action_url))
                <p style="margin:22px 0 0;"><a href="{{ $delivery->action_url }}" style="display:inline-block;background:#477c3a;border-radius:8px;color:#fff;font-size:14px;font-weight:700;padding:12px 18px;text-decoration:none;">Buka PasPapan</a></p>
            @endif
        </div>
    </main>
</body>
</html>
