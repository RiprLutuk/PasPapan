@props(['url', 'message' => null])
<tr>
<td class="header">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td class="header-content" align="center" style="text-align: center;">
                <a href="{{ $url }}" style="text-decoration: none;">
                    <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                            @if (trim($slot) === 'Laravel')
                                <td>
                                    <img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo" style="height: 32px; width: auto; vertical-align: middle; margin-right: 12px;">
                                </td>
                            @else
                                @php
                                    $logoSrc = \App\Support\MailBranding::logoMailSource($message ?? null);
                                    $companyName = \App\Support\MailBranding::companyName();
                                @endphp
                                <td style="vertical-align: middle; padding-right: 14px;">
                                    <img src="{{ $logoSrc }}" class="logo" alt="{{ $companyName }}" width="40" height="40" style="height: 40px; width: 40px; border-radius: 14px; vertical-align: middle; display: block; object-fit: cover; border: 1px solid rgba(87, 148, 74, 0.15);">
                                </td>
                                <td style="vertical-align: middle; text-align: left;">
                                    <span class="header-title" style="font-size: 19px; font-weight: 800; color: #163020; font-family: 'Figtree', sans-serif; line-height: 1.2; display: block;">
                                        {!! $slot !!}
                                    </span>
                                </td>
                            @endif
                        </tr>
                    </table>
                </a>
            </td>
        </tr>
    </table>
</td>
</tr>
