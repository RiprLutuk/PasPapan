<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $payroll->month }}/{{ $payroll->year }}</title>
    <style>
        /* Security Background Pattern */
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 12px; 
            color: #333; 
            line-height: 1.5;
            background-color: #fff;
            /* Subtle diagonal security lines */
            background-image: repeating-linear-gradient(
                135deg,
                #fff,
                #fff 10px,
                #f3f4f6 10px,
                #f3f4f6 11px
            );
        }
        
        /* Modern Header */
        .header-table { width: 100%; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 30px; margin-top: -30px; }
        .header-left { text-align: left; vertical-align: middle; width: 70%; }
        .header-right { text-align: right; vertical-align: middle; width: 30%; }

        /* Logo & Company Side-by-Side */
        .company-info-table { width: 100%; }
        .logo-cell { width: 60px; vertical-align: middle; padding-right: 15px; }
        .text-cell { vertical-align: middle; }
        
        .company-name { font-size: 16px; font-weight: bold; color: #111; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2; }
        .company-address { font-size: 10px; color: #666; margin: 2px 0 0 0; line-height: 1.2; }

        /* Stamp Style */
        .stamp-box { 
            border: 3px solid #15803d; /* Green border */
            padding: 8px 15px; 
            display: inline-block; 
            text-align: center;
            border-radius: 6px;
            transform: rotate(-8deg); /* More tilt */
            color: #15803d; /* Green text */
            opacity: 0.9;
        }
        .stamp-title { font-size: 20px; font-weight: 900; color: #15803d; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
        .stamp-subtitle { font-size: 9px; color: #166534; margin: 2px 0 0 0; text-transform: uppercase; font-weight: bold; }

        /* Crisp Info Table */
        .info-table { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
        .info-table td { padding: 3px 0; vertical-align: top; }
        .label-col { width: 15%; font-weight: bold; color: #555; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
        .colon-col { width: 2%; text-align: center; color: #555; font-weight: bold; font-size: 10px; }
        .value-col { width: 33%; color: #111; font-weight: 600; font-size: 11px; }
        
        /* Modern Data Table */
        .salary-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .salary-table th { color: #888; text-transform: uppercase; font-size: 9px; letter-spacing: 1px; padding: 8px 0; border-bottom: 1px solid #eee; }
        .salary-table td { padding: 5px 0; color: #333; font-size: 11px; border-bottom: 1px solid #f9f9f9; }
        
        .section-header { font-size: 10px; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 1.5px; padding-top: 10px; padding-bottom: 2px; }
        
        .total-row td { font-weight: bold; color: #111; padding-top: 8px; padding-bottom: 8px; border-top: 1px solid #eee; }
        
        .net-salary-box { 
            background-color: #f0fdf4; /* Green-50 */
            border: 1px solid #dcfce7; /* Green-100 */
            color: #166534; /* Green-800 */
            padding: 12px 15px; 
            margin-top: 15px; 
            border-radius: 8px;
        }
        .net-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
        .net-value { font-size: 18px; font-weight: 900; float: right; }

        .text-right { text-align: right; }
        .text-red { color: #dc2626; }
        .text-green { color: #166534; }

        /* Footer & Signatures */
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #ccc; padding-top: 10px; }
        
        .signature-section { margin-top: 50px; page-break-inside: avoid; }
        .signature-table { width: 100%; table-layout: fixed; }
        .signature-cell-left { text-align: center; vertical-align: top; width: 50%; padding-right: 20px; }
        .signature-cell-right { text-align: center; vertical-align: top; width: 50%; padding-left: 20px; }
        .signature-line { border-top: 1px solid #ddd; width: 60%; margin: 60px auto 10px auto; }
        .signature-name { font-weight: bold; font-size: 12px; color: #111; }
        .signature-role { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Confidential Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: #d1d5db; /* Gray-300 */
            opacity: 0.3;
            z-index: -1000;
            pointer-events: none;
            font-weight: 900;
            letter-spacing: 10px;
            width: 100%;
            text-align: center;
            white-space: nowrap;
        }

        .confidential-note {
            margin-top: 40px;
            font-size: 7px;
            color: #9ca3af; /* Gray-400 */
            text-align: center;
            font-style: italic;
            line-height: 1.4;
            max-width: 90%;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>

    <div class="watermark">{{ __('Confidential') }}</div>

    <table class="header-table">
        <tr>
            <td class="header-left">
                <table class="company-info-table">
                    <tr>
                        <td class="logo-cell">
                            <img src="{{ public_path('images/icons/logo.png') }}" style="height: 50px; width: auto;">
                        </td>
                        <td class="text-cell">
                            <h1 class="company-name">{{ \App\Models\Setting::getValue('app.company_name', config('app.name')) }}</h1>
                            <p class="company-address">{{ \App\Models\Setting::getValue('app.company_address', '123 Business Rd, Jakarta, Indonesia') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="header-right">
                <div class="stamp-box">
                    <div class="stamp-title">{{ __('Payslip') }}</div>
                    <div class="stamp-subtitle">{{ DateTime::createFromFormat('!m', $payroll->month)->format('F') }} {{ $payroll->year }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <!-- Left Column Data -->
            <td width="55%" style="vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="label-col" style="width: 25%;">{{ __('NIP') }}</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $payroll->user->nip ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">{{ __('Name') }}</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $payroll->user->name }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">{{ __('Department') }}</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $payroll->user->division->name ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            
            <!-- Right Column Data (Aligned Right) -->
            <td width="45%" style="vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="label-col" style="width: 30%; padding-left: 20px;">{{ __('Generated On') }}</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $payroll->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="label-col" style="padding-left: 20px;">{{ __('Status') }}</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ ucfirst($payroll->status) }}</td>
                    </tr>
                    <tr>
                        <td class="label-col" style="padding-left: 20px;">{{ __('Job Title') }}</td>
                        <td class="colon-col">:</td>
                        <td class="value-col">{{ $payroll->user->jobTitle->name ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="salary-table">
        <thead>
            <tr>
                <th width="70%" style="text-align: left;">{{ __('Description') }}</th>
                <th width="30%" class="text-right">{{ __('Amount') }} (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <!-- EARNINGS SECTION -->
            <tr><td colspan="2" class="section-header">{{ __('Earnings') }}</td></tr>
            
            <tr>
                <td>{{ __('Basic Salary') }}</td>
                <td class="text-right">{{ number_format($payroll->basic_salary, 0, ',', '.') }}</td>
            </tr>
            
            @if(is_array($payroll->allowances))
                @foreach($payroll->allowances as $name => $amount)
                    <tr>
                        <td>{{ $name }}</td>
                        <td class="text-right">{{ number_format($amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif

            <tr class="total-row">
                <td style="padding-left: 10px;">{{ __('Total Earnings') }}</td>
                <td class="text-right text-green">{{ number_format($payroll->gross_salary ?? ($payroll->basic_salary + $payroll->total_allowance), 0, ',', '.') }}</td>
            </tr>

            <!-- DEDUCTIONS SECTION -->
            <tr><td colspan="2" class="section-header" style="color: #ef4444;">{{ __('Deductions') }}</td></tr> <!-- Red-500 -->

            @if(is_array($payroll->deductions))
                @foreach($payroll->deductions as $name => $amount)
                    <tr>
                        <td>{{ $name }}</td>
                        <td class="text-right text-red">-{{ number_format($amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif

            <tr class="total-row">
                <td style="padding-left: 10px;">{{ __('Total Deductions') }}</td>
                <td class="text-right text-red">-{{ number_format($payroll->total_deductions ?? $payroll->total_deduction, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- NET PAY CARD -->
    <div class="net-salary-box">
        <span class="net-label">{{ __('Net Salary (Take Home Pay)') }}</span>
        <span class="net-value">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
    </div>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td class="signature-cell-left">
                    <div class="signature-role">{{ __('Approved By') }}</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ __('Manager / HR') }}</div>
                </td>
                <td class="signature-cell-right">
                    <div class="signature-role">{{ __('Received By') }}</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $payroll->user->name }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div style="margin-bottom: 5px;">{{ __('Generated by :app. This is a computer-generated document and may not require a physical signature.', ['app' => config('app.name')]) }}</div>
        
        <div class="confidential-note" style="margin-top: 5px;">
            {{ __('Please note that the contents of this statement should be treated with absolute confidentiality except where disclosure is required for tax, legal, or regulatory purposes. Any breach of this confidentiality obligation may result in disciplinary action.') }}
        </div>
    </div>

</body>
</html>
