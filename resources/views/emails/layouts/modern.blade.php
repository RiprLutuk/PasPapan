@php
    $companyName = \App\Support\MailBranding::companyName();
    $homeUrl = config('app.url', url('/'));
    $emailTitle = $title ?? $companyName;
    $logoPath = \App\Support\MailBranding::logoPath();
    $logoUrl = is_file($logoPath) && isset($message) && is_object($message) && method_exists($message, 'embed')
        ? $message->embed($logoPath)
        : url('images/icons/logo.jpeg');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>{{ $emailTitle }}</title>
    <style type="text/css">
        body,
        body *:not(html):not(style):not(br):not(tr):not(code) {
            box-sizing: border-box;
            font-family: Figtree, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            position: relative;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100%;
            -webkit-text-size-adjust: none;
            background-color: #f5faf4;
            color: #163020;
        }

        a {
            color: #57944a;
            text-decoration: none;
        }

        p,
        ul,
        ol,
        blockquote {
            margin: 0 0 16px;
            color: #466351;
            font-size: 15px;
            line-height: 1.75;
            text-align: left;
        }

        h1 {
            margin: 0 0 18px;
            color: #163020;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.03em;
            text-align: left;
        }

        h2 {
            margin: 28px 0 12px;
            color: #163020;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.35;
            text-align: left;
        }

        h3 {
            margin: 20px 0 10px;
            color: #163020;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.45;
            text-align: left;
        }

        .wrapper {
            width: 100%;
            background:
                radial-gradient(circle at top left, rgba(87, 148, 74, 0.10), transparent 32%),
                #f5faf4;
            margin: 0;
            padding: 32px 12px;
        }

        .content {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .brand {
            width: 100%;
            margin-bottom: 22px;
        }

        .brand-card {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .brand-link {
            display: inline-block;
            text-decoration: none;
        }

        .brand-mark {
            height: 42px;
            width: 42px;
            border-radius: 14px;
            display: block;
            object-fit: cover;
            border: 1px solid rgba(87, 148, 74, 0.15);
            box-shadow: 0 16px 28px -24px rgba(34, 64, 41, 0.45);
        }

        .brand-name {
            color: #163020;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.02em;
            padding-left: 12px;
        }

        .body {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .inner-body {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 24px;
            background: #ffffff;
            border: 1px solid rgba(87, 148, 74, 0.14);
            box-shadow: 0 26px 60px -38px rgba(34, 64, 41, 0.32);
            overflow: hidden;
        }

        .body-accent {
            height: 6px;
            background: linear-gradient(90deg, #6ab45b 0%, #57944a 50%, #44733a 100%);
        }

        .content-cell {
            padding: 34px 36px 30px;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 16px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #e2f0df;
            color: #44733a;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .email-section {
            margin: 22px 0 0;
            padding: 18px 20px;
            border-radius: 18px;
            background: #f8fcf7;
            border: 1px solid #d5ead1;
        }

        .email-section--subtle {
            background: #ffffff;
            border-style: dashed;
        }

        .email-data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .email-data-table td {
            padding: 9px 0;
            vertical-align: top;
            border-bottom: 1px solid #edf5ea;
            font-size: 14px;
            line-height: 1.65;
            word-break: break-word;
        }

        .email-data-table tr:last-child td {
            border-bottom: 0;
        }

        .email-data-label {
            width: 150px;
            padding-right: 16px !important;
            color: #5d7766;
            font-weight: 700;
        }

        .email-data-value {
            color: #163020;
            font-weight: 600;
        }

        .email-note {
            margin: 18px 0 0;
            padding: 16px 18px;
            border-radius: 16px;
            border-left: 4px solid #57944a;
            background: #f8fcf7;
            color: #355340;
            font-size: 14px;
            line-height: 1.7;
        }

        .email-note--warning {
            border-left-color: #b7791f;
            background: #fff9eb;
            color: #744210;
        }

        .email-steps {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .email-step-row td {
            padding: 10px 0;
            vertical-align: top;
        }

        .email-step-badge {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-block;
            background: linear-gradient(135deg, #6ab45b 0%, #44733a 100%);
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            line-height: 34px;
            text-align: center;
        }

        .email-step-copy {
            padding-left: 14px !important;
        }

        .email-step-title {
            color: #163020;
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .email-step-text {
            color: #466351;
            font-size: 13px;
            line-height: 1.65;
            margin: 0;
        }

        .button {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 999px;
            background: linear-gradient(135deg, #6ab45b 0%, #44733a 100%);
            color: #ffffff !important;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            line-height: 1.15;
            text-transform: uppercase;
            box-shadow: 0 14px 28px -22px rgba(68, 115, 58, 0.8);
            text-decoration: none;
            white-space: nowrap;
            word-break: keep-all;
        }

        .subcopy {
            border-top: 1px solid #e2efe0;
            margin-top: 26px;
            padding-top: 18px;
        }

        .subcopy p {
            margin-bottom: 10px;
            color: #5d7766;
            font-size: 12px;
            line-height: 1.65;
        }

        .footer {
            width: 100%;
        }

        .footer-card {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .footer p {
            margin: 18px 0 0;
            color: #6b7f71;
            font-size: 12px;
            line-height: 1.7;
            text-align: center;
        }

        @media only screen and (max-width: 620px) {
            .wrapper {
                padding: 20px 10px;
            }

            .content-cell {
                padding: 28px 22px 24px;
            }

            .email-data-label,
            .email-data-value,
            .email-data-table td {
                display: block;
                width: 100%;
            }

            .email-data-label {
                padding-bottom: 2px !important;
            }
        }
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="brand" align="center">
                            <table class="brand-card" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="left">
                                        <a href="{{ $homeUrl }}" class="brand-link" target="_blank" rel="noopener">
                                            <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                <tr>
                                                    <td>
                                                        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="brand-mark" width="42" height="42" style="display: block;">
                                                    </td>
                                                    <td class="brand-name">
                                                        {{ $companyName }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="body" align="center">
                            <table class="inner-body" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="body-accent"></td>
                                </tr>
                                <tr>
                                    <td class="content-cell">
                                        @if (!empty($eyebrow))
                                            <div class="eyebrow">{{ $eyebrow }}</div>
                                        @endif

                                        {!! Illuminate\Mail\Markdown::parse($slot) !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="footer" align="center">
                            <table class="footer-card" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="center">
                                        <p>
                                            © {{ date('Y') }} {{ $companyName }}. {{ __('All rights reserved.') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
