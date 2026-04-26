@php
    use Illuminate\Support\Carbon;
    $selectedDate = Carbon::parse($date ?? ($week ?? $month))->settings(['formatFunction' => 'translatedFormat']);
    $showUserDetail = !$month || $week || $date; // is week or day filter
    $isPerDayFilter = isset($date);
    $datesWithoutWeekend = '';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PasPapan | {{ $date ?? ($week ?? $month) }}</title>
    <style>
        /* Payslip Header Styles */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-left {
            text-align: left;
            vertical-align: middle;
            width: 70%;
        }

        .header-right {
            text-align: right;
            vertical-align: middle;
            width: 30%;
        }

        .company-info-table {
            width: 100%;
        }

        .logo-cell {
            width: 60px;
            vertical-align: middle;
            padding-right: 15px;
        }

        .text-cell {
            vertical-align: middle;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #111;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .company-address {
            font-size: 10px;
            color: #666;
            margin: 2px 0 0 0;
            line-height: 1.2;
        }

        /* Stamp Style */
        .stamp-box {
            border: 3px solid #15803d;
            /* Green border */
            padding: 5px 10px;
            display: inline-block;
            text-align: center;
            border-radius: 6px;
            transform: rotate(-6deg);
            color: #15803d;
            opacity: 0.9;
        }

        .stamp-title {
            font-size: 14px;
            font-weight: 900;
            color: #15803d;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stamp-subtitle {
            font-size: 9px;
            color: #166534;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            font-weight: bold;
        }

        /* Fit to Page Optimization */
        @page {
            margin: 10px 20px;
        }

        body {
            margin: 10px;
            padding: 10px;
        }

        #table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
            page-break-inside: auto;
        }

        #table th,
        #table td {
            border: 1px solid #666;
            /* Darker border for print clarity */
            padding: 3px 2px;
            /* Tight padding */
            font-size: 9px;
            /* Smaller font to fit 31 days */
            text-align: center;
        }

        /* Left align Name column */
        #table td:nth-child(2) {
            text-align: left;
            padding-left: 5px;
        }

        #table th {
            background-color: #f2f2f2;
            text-transform: uppercase;
            font-weight: bold;
            color: #333;
            font-size: 8px;
            /* Even smaller headers */
        }

        #table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>

    @if (!($isExcel ?? false))
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <table class="company-info-table">
                        <tr>
                            <td class="logo-cell">
                                @if (file_exists(public_path('images/icons/logo.png')))
                                    <img src="{{ public_path('images/icons/logo.png') }}"
                                        alt="{{ \App\Models\Setting::getValue('app.company_name', config('app.name')) }}"
                                        style="height: 45px; width: auto;">
                                @endif
                            </td>
                            <td class="text-cell">
                                <h1 class="company-name">
                                    {{ \App\Models\Setting::getValue('app.company_name', config('app.name')) }}</h1>
                                <p class="company-address">
                                    {{ \App\Models\Setting::getValue('app.company_address', '123 Business Rd, Jakarta, Indonesia') }}
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="header-right">
                    {{-- Stamp Removed by User Request --}}
                </td>
            </tr>
        </table>

        <!-- Filter Info -->
        <div style="font-size: 11px; margin-bottom: 10px; color: #555;">
            <table style="width: 100%">
                <tr>
                    <td width="50%">
                        @if ($division)
                            <strong>{{ __('Division') }}:</strong> {{ App\Models\Division::find($division)->name }} <br>
                        @endif
                        @if ($jobTitle)
                            <strong>{{ __('Job Title') }}:</strong> {{ App\Models\JobTitle::find($jobTitle)->name }}
                        @endif
                    </td>
                    <td width="50%" style="text-align: right;">
                        @if ($month)
                            {{ __('Period') }}: {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}
                        @elseif ($week)
                            {{ __('Period') }}: {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <table id="table">
        <thead>
            <tr>
                <th scope="col" style="padding: 0px">
                    {{ __('No.') }}
                </th>
                <th scope="col">
                    {{ $showUserDetail ? __('Name') : __('Name') . '/' . __('Date') }}
                </th>
                @if ($showUserDetail)
                    <th scope="col">
                        {{ __('NIP') }}
                    </th>
                    <th scope="col">
                        {{ __('Division') }}
                    </th>
                    <th scope="col">
                        {{ __('Job Title') }}
                    </th>
                    @if ($isPerDayFilter)
                        <th scope="col">
                            {{ __('Shift') }}
                        </th>
                        <th scope="col">
                            {{ __('Time In') }}
                        </th>
                        <th scope="col">
                            {{ __('Time Out') }}
                        </th>
                        <th scope="col">
                            {{ __('Note') }}
                        </th>
                        <th scope="col">
                            {{ __('Maps') }}
                        </th>
                    @endif
                @endif
                @foreach ($dates as $date)
                    <th scope="col" style="padding: 0px 2px; font-size: 14px">
                        @if ($isPerDayFilter)
                            {{ __('Status') }}
                        @elseif (!$month)
                            {{ $date->format('d/m') }}
                        @else
                            {{ $date->format('d') }}
                        @endif
                    </th>
                @endforeach
                @if (!$isPerDayFilter)
                    @foreach (['H', 'T', 'I', 'S', 'A'] as $_st)
                        <th scope="col">
                            {{ __($_st) }}
                        </th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $employee)
                @php
                    $attendances = $employee->attendances;
                    $attendance = $employee->attendances->isEmpty() ? null : $employee->attendances->first();
                @endphp
                <tr style="font-size: 12px">
                    <td style="text-align: center; vertical-align: middle; padding: 0px">
                        {{ $loop->iteration }}
                    </td>
                    <td>
                        {{ $employee->name }}
                    </td>
                    @if ($showUserDetail)
                        <td>
                            {{ $employee->nip }}
                        </td>
                        <td>
                            {{ $employee->division?->name ?? '-' }}
                        </td>
                        <td>
                            {{ $employee->jobTitle?->name ?? '-' }}
                        </td>
                        @if ($isPerDayFilter)
                            <td>
                                {{ $attendance['shift'] ?? '-' }}
                            </td>
                            <td>
                                {{ $attendance['time_in'] ?? '-' }}
                            </td>
                            <td>
                                {{ $attendance['time_out'] ?? '-' }}
                            </td>
                            <td>
                                {{ $attendance['note'] ?? '-' }}
                            </td>
                            <td style="text-align: center;">
                                @if (($attendance['lat'] ?? null) && ($attendance['lng'] ?? null))
                                    <a href="https://www.google.com/maps?q={{ $attendance['lat'] }},{{ $attendance['lng'] }}"
                                        target="_blank" rel="noopener noreferrer">{{ __('View') }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                    @endif
                    @php
                        $presentCount = 0;
                        $lateCount = 0;
                        $excusedCount = 0;
                        $sickCount = 0;
                        $absentCount = 0;
                    @endphp
                    @foreach ($dates as $date)
                        @php
                            $isWeekend = $date->isWeekend();
                            $status = ($attendances->firstWhere(
                                fn($v, $k) => \Carbon\Carbon::parse($v['date'])->isSameDay($date),
                            ) ?? [
                                'status' => $isWeekend || !$date->isPast() ? '-' : 'absent',
                            ])['status'];
                            switch ($status) {
                                case 'present':
                                    $shortStatus = 'H';
                                    $presentCount++;
                                    break;
                                case 'late':
                                    $shortStatus = 'T';
                                    $lateCount++;
                                    break;
                                case 'excused':
                                    $shortStatus = 'I';
                                    $excusedCount++;
                                    break;
                                case 'sick':
                                    $shortStatus = 'S';
                                    $sickCount++;
                                    break;
                                case 'absent':
                                    $shortStatus = 'A';
                                    $absentCount++;
                                    break;
                                default:
                                    $shortStatus = '-';
                                    break;
                            }
                        @endphp
                        <td style="padding: 0px; text-align: center;">
                            {{ $isPerDayFilter ? __($status) : $shortStatus }}
                        </td>
                    @endforeach

                    @if (!$isPerDayFilter)
                        @foreach ([$presentCount, $lateCount, $excusedCount, $sickCount, $absentCount] as $statusCount)
                            <td style=" text-align: center;">
                                {{ $statusCount }}
                            </td>
                        @endforeach
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
    @if ($employees->isEmpty())
        <div style="text-align: center; margin-top: 20px">
            Tidak ada data
        </div>
    @endif
</body>

</html>
