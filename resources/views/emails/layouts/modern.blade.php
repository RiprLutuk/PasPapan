<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 30px;
            text-align: center;
        }
        .logo-text {
            color: #ffffff;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
        }
        .content {
            padding: 40px 30px;
            color: #374151;
            line-height: 1.6;
        }
        .content h1 {
            color: #111827;
            font-size: 22px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .content p {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 16px;
            margin-top: 10px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        }
        .info-table {
            width: 100%;
            background-color: #f9fafb;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            border: 1px solid #f3f4f6;
        }
        .info-row {
            margin-bottom: 10px;
            display: block;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
            display: inline-block;
            width: 100px;
        }
        .info-value {
            color: #111827;
            font-weight: 500;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                {{-- Fallback to App Name if Logo is complex --}}
                <a href="{{ config('app.url') }}" class="logo-text">
                    {{ config('app.name', 'PasPapan') }}
                </a>
            </div>
            
            <div class="content">
                {{ $slot }}
            </div>
            
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
                @if(isset($supportEmail))
                 <p>{{ __('Support') }}: <a href="mailto:{{ $supportEmail }}" style="color: #4f46e5; text-decoration: none;">{{ $supportEmail }}</a></p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
