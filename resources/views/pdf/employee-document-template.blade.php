@php
    $preview = $preview ?? false;
    $logoPng = public_path('images/icons/logo.png');
    $logoJpeg = public_path('images/icons/logo.jpeg');
    $logoPath = file_exists($logoPng) ? $logoPng : (file_exists($logoJpeg) ? $logoJpeg : null);
    $companyAddress = \App\Models\Setting::getValue('app.company_address', '');
    $companyPhone = \App\Models\Setting::getValue('app.company_phone', '');
    $companyWebsite = \App\Models\Setting::getValue('app.company_website', '');
    $supportContact = \App\Models\Setting::getValue('app.support_contact', config('mail.from.address'));
    $documentMeta = $documentMeta ?? [];
    $contactLines = collect([
        $companyPhone ? __('Telp/HP: :value', ['value' => $companyPhone]) : null,
        $supportContact ? __('Kontak: :value', ['value' => $supportContact]) : null,
        $companyWebsite ? __('Website: :value', ['value' => $companyWebsite]) : null,
    ])->filter()->values();
@endphp

@unless ($preview)
    <!doctype html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <title>{{ $companyName }} - {{ __('Employee Document') }}</title>
@endunless

    <style>
        @page {
            margin: 34px 54px 68px 54px;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.62;
            margin: 0;
        }

        .employee-document-preview {
            background: #1f2937;
            overflow-x: auto;
            padding: 18px;
        }

        .employee-document-preview .employee-document-page {
            background: #ffffff;
            box-sizing: border-box;
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.62;
            margin: 0 auto;
            min-height: 1123px;
            padding: 34px 54px 68px;
            position: relative;
            width: 794px;
        }

        .employee-document-page {
            position: relative;
        }

        .page-accent {
            position: absolute;
            z-index: 0;
        }

        .top-corner-navy {
            border-left: 118px solid transparent;
            border-top: 118px solid #083344;
            height: 0;
            right: 0;
            top: 0;
            width: 0;
        }

        .top-corner-brand {
            border-left: 88px solid transparent;
            border-top: 88px solid #06b6d4;
            height: 0;
            right: 0;
            top: 0;
            width: 0;
        }

        .top-corner-primary {
            border-left: 58px solid transparent;
            border-top: 58px solid #6ab45b;
            height: 0;
            right: 0;
            top: 0;
            width: 0;
        }

        .top-rule-primary,
        .top-rule-brand,
        .bottom-rule-primary,
        .bottom-rule-brand {
            height: 2px;
            position: absolute;
            width: 110px;
            z-index: 0;
        }

        .top-rule-primary {
            background: #6ab45b;
            right: 92px;
            top: 14px;
        }

        .top-rule-brand {
            background: #06b6d4;
            right: 80px;
            top: 22px;
            width: 138px;
        }

        .bottom-corner-navy {
            border-bottom: 112px solid #083344;
            border-right: 112px solid transparent;
            bottom: 0;
            height: 0;
            left: 0;
            width: 0;
        }

        .bottom-corner-primary {
            border-bottom: 82px solid #6ab45b;
            border-right: 82px solid transparent;
            bottom: 0;
            height: 0;
            left: 0;
            width: 0;
        }

        .bottom-corner-brand {
            border-bottom: 52px solid #06b6d4;
            border-right: 52px solid transparent;
            bottom: 0;
            height: 0;
            left: 0;
            width: 0;
        }

        .bottom-rule-primary {
            background: #6ab45b;
            bottom: 64px;
            left: 72px;
            width: 96px;
        }

        .bottom-rule-brand {
            background: #06b6d4;
            bottom: 74px;
            left: 82px;
            width: 130px;
        }

        .letterhead,
        .meta-table,
        .document-body,
        .footer {
            position: relative;
            z-index: 1;
        }

        .letterhead {
            border-bottom: 1.4px solid #31542a;
            margin: 0 0 20px;
            padding-bottom: 11px;
            width: 100%;
        }

        .letterhead,
        .letterhead td {
            border: 0;
        }

        .logo-cell {
            padding: 0 12px 0 0;
            vertical-align: middle;
            width: 52px;
        }

        .company-cell {
            padding: 0;
            vertical-align: middle;
        }

        .contact-cell {
            color: #4b5563;
            font-size: 8.8px;
            line-height: 1.4;
            padding: 0 0 0 14px;
            text-align: right;
            vertical-align: middle;
            width: 190px;
        }

        .company-name {
            color: #111827;
            font-size: 15.5px;
            font-weight: 700;
            letter-spacing: .01em;
            margin: 0 0 2px;
            text-transform: uppercase;
        }

        .company-address {
            color: #4b5563;
            font-size: 9.2px;
            line-height: 1.35;
            margin: 0;
        }

        .company-mark {
            color: #57944a;
            font-size: 8.2px;
            font-weight: 700;
            letter-spacing: .18em;
            margin: 2px 0 0;
            text-transform: uppercase;
        }

        .meta-table {
            margin: 0 0 24px;
            width: 62%;
        }

        .meta-table,
        .meta-table td {
            border: 0;
        }

        .meta-label {
            color: #31542a;
            font-size: 10.5px;
            font-weight: 700;
            padding: 0 8px 4px 0;
            width: 64px;
        }

        .meta-separator {
            color: #31542a;
            font-size: 10.5px;
            padding: 0 8px 4px 0;
            width: 8px;
        }

        .meta-value {
            color: #111827;
            font-size: 10.5px;
            padding: 0 0 4px;
        }

        h1, h2, h3, h4 {
            margin: 0 0 12px;
        }

        .document-body {
            margin-top: 8px;
        }

        .document-body::after {
            clear: both;
            content: "";
            display: table;
        }

        h2 {
            font-size: 16.5px;
            letter-spacing: .025em;
            line-height: 1.35;
            margin: 10px 0 24px;
            text-transform: uppercase;
        }

        p {
            margin: 0 0 11px;
        }

        table {
            border-collapse: collapse;
            margin: 13px 0;
            width: 100%;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        .footer {
            position: fixed;
            right: 0;
            bottom: -46px;
            left: 0;
            border-top: 1px solid #badcb3;
            color: #6b7280;
            font-size: 10px;
            height: 32px;
            line-height: 1.35;
            padding-top: 8px;
        }

        .employee-document-preview .footer {
            position: absolute;
            bottom: 34px;
            left: 54px;
            right: 54px;
        }
    </style>

@unless ($preview)
    </head>
    <body>
@endunless

    <div class="{{ $preview ? 'employee-document-preview' : 'employee-document-pdf' }}">
        <div class="employee-document-page">
            <div class="page-accent top-corner-navy"></div>
            <div class="page-accent top-corner-brand"></div>
            <div class="page-accent top-corner-primary"></div>
            <div class="page-accent top-rule-primary"></div>
            <div class="page-accent top-rule-brand"></div>
            <div class="page-accent bottom-corner-navy"></div>
            <div class="page-accent bottom-corner-primary"></div>
            <div class="page-accent bottom-corner-brand"></div>
            <div class="page-accent bottom-rule-primary"></div>
            <div class="page-accent bottom-rule-brand"></div>

            <table class="letterhead">
                <tr>
                    @if ($logoPath)
                        <td class="logo-cell">
                            <img src="{{ $logoPath }}" style="height: 42px; width: auto;">
                        </td>
                    @endif
                    <td class="company-cell">
                        <h1 class="company-name">{{ $companyName }}</h1>
                        @if ($companyAddress)
                            <p class="company-address">{{ $companyAddress }}</p>
                        @endif
                        <p class="company-mark">{{ __('Enterprise Workforce System') }}</p>
                    </td>
                    @if ($contactLines->isNotEmpty())
                        <td class="contact-cell">
                            @foreach ($contactLines as $line)
                                <div>{{ $line }}</div>
                            @endforeach
                        </td>
                    @endif
                </tr>
            </table>

            @if ($documentMeta)
                <table class="meta-table">
                    @foreach ($documentMeta as $label => $value)
                        @if (filled($value))
                            <tr>
                                <td class="meta-label">{{ $label }}</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">{{ $value }}</td>
                            </tr>
                        @endif
                    @endforeach
                </table>
            @endif

            <div class="document-body">
                {!! $body !!}
            </div>

            <div class="footer">
                @if ($footer)
                    {!! $footer !!}
                @else
                    {{ __('Generated by :app. This is a computer-generated document and may not require a physical signature.', ['app' => $companyName]) }}
                @endif
            </div>
        </div>
    </div>

@unless ($preview)
    </body>
    </html>
@endunless
