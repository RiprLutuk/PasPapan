<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Performance Appraisal Report') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #1a1a2e; line-height: 1.5; }
        .page { padding: 30px 40px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #4338ca; padding-bottom: 15px; margin-bottom: 20px; }
        .header-left h1 { font-size: 18px; color: #4338ca; margin-bottom: 2px; }
        .header-left p { font-size: 10px; color: #6b7280; }
        .header-right { text-align: right; font-size: 10px; color: #6b7280; }
        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 35%; padding: 4px 8px; font-weight: bold; color: #374151; background: #f3f4f6; border: 1px solid #e5e7eb; }
        .info-value { display: table-cell; width: 65%; padding: 4px 8px; border: 1px solid #e5e7eb; }
        .section-title { font-size: 13px; font-weight: bold; color: #4338ca; margin: 20px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; }
        table.kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.kpi-table th { background: #4338ca; color: white; text-align: left; padding: 6px 10px; font-size: 10px; text-transform: uppercase; }
        table.kpi-table td { padding: 6px 10px; border: 1px solid #e5e7eb; font-size: 10px; }
        table.kpi-table tr:nth-child(even) { background: #f9fafb; }
        .score-box { display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: bold; color: white; }
        .score-green { background: #059669; }
        .score-yellow { background: #d97706; }
        .score-red { background: #dc2626; }
        .final-score-card { background: #eef2ff; border: 2px solid #4338ca; border-radius: 8px; padding: 15px; text-align: center; margin: 20px 0; }
        .final-score-card .label { font-size: 12px; color: #4338ca; font-weight: bold; }
        .final-score-card .score { font-size: 36px; font-weight: bold; color: #4338ca; }
        .final-score-card .grade { font-size: 14px; color: #6366f1; }
        .notes-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; margin: 10px 0; font-style: italic; color: #4b5563; font-size: 10px; }
        .signature-area { margin-top: 40px; display: table; width: 100%; }
        .sig-col { display: table-cell; width: 33.33%; text-align: center; padding: 10px; }
        .sig-line { border-top: 1px solid #374151; width: 150px; margin: 40px auto 5px; }
        .sig-name { font-weight: bold; font-size: 10px; }
        .sig-role { font-size: 9px; color: #6b7280; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .calibration-badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .cal-approved { background: #d1fae5; color: #065f46; }
        .cal-pending { background: #fef3c7; color: #92400e; }
        .cal-rejected { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>{{ $companyName }}</h1>
                <p>{{ __('Performance Appraisal Report') }}</p>
            </div>
            <div class="header-right">
                <p><strong>{{ __('Document ID') }}:</strong> APR-{{ str_pad($appraisal->id, 5, '0', STR_PAD_LEFT) }}</p>
                <p><strong>{{ __('Generated') }}:</strong> {{ now()->format('d M Y H:i') }}</p>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="section-title">{{ __('Employee Information') }}</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">{{ __('Employee Name') }}</div>
                <div class="info-value">{{ $appraisal->user->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('NIP / Employee ID') }}</div>
                <div class="info-value">{{ $appraisal->user->nip ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('Department') }}</div>
                <div class="info-value">{{ $appraisal->user->division->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('Position') }}</div>
                <div class="info-value">{{ $appraisal->user->jobTitle->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('Appraisal Period') }}</div>
                <div class="info-value">{{ date('F', mktime(0, 0, 0, $appraisal->period_month, 10)) }} {{ $appraisal->period_year }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('Status') }}</div>
                <div class="info-value">{{ ucwords(str_replace('_', ' ', $appraisal->status)) }}</div>
            </div>
            @if($appraisal->calibration_status)
            <div class="info-row">
                <div class="info-label">{{ __('Calibration') }}</div>
                <div class="info-value">
                    <span class="calibration-badge {{ $appraisal->calibration_status === 'approved' ? 'cal-approved' : ($appraisal->calibration_status === 'rejected' ? 'cal-rejected' : 'cal-pending') }}">
                        {{ ucfirst($appraisal->calibration_status) }}
                    </span>
                    @if($appraisal->calibrator)
                        {{ __('by') }} {{ $appraisal->calibrator->name }}
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Attendance Score -->
        <div class="section-title">{{ __('Attendance Score') }} ({{ __('Weight') }}: 30%)</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">{{ __('System Attendance Score') }}</div>
                <div class="info-value">
                    <span class="score-box {{ $appraisal->attendance_score >= 80 ? 'score-green' : ($appraisal->attendance_score >= 60 ? 'score-yellow' : 'score-red') }}">
                        {{ $appraisal->attendance_score }} / 100
                    </span>
                </div>
            </div>
        </div>

        <!-- KPI Matrices -->
        <div class="section-title">{{ __('KPI Performance Evaluation') }} ({{ __('Weight') }}: 70%)</div>
        <table class="kpi-table">
            <thead>
                <tr>
                    <th style="width: 30%">{{ __('KPI Indicator') }}</th>
                    <th style="width: 12%">{{ __('Weight') }}</th>
                    <th style="width: 12%">{{ __('Self Score') }}</th>
                    <th style="width: 12%">{{ __('Manager Score') }}</th>
                    <th style="width: 34%">{{ __('Comments') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($appraisal->evaluations as $eval)
                <tr>
                    <td><strong>{{ $eval->kpiTemplate->name ?? '-' }}</strong></td>
                    <td>{{ $eval->kpiTemplate->weight ?? 0 }}%</td>
                    <td>
                        @if($eval->self_score)
                            <span class="score-box {{ $eval->self_score >= 80 ? 'score-green' : ($eval->self_score >= 60 ? 'score-yellow' : 'score-red') }}">{{ $eval->self_score }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($eval->manager_score)
                            <span class="score-box {{ $eval->manager_score >= 80 ? 'score-green' : ($eval->manager_score >= 60 ? 'score-yellow' : 'score-red') }}">{{ $eval->manager_score }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $eval->comments ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Final Score -->
        <div class="final-score-card">
            <div class="label">{{ __('Final Weighted Score') }}</div>
            <div class="score">{{ $appraisal->final_score ?? __('N/A') }}</div>
            <div class="grade">
                @if($appraisal->final_score)
                    @if($appraisal->final_score >= 90) {{ __('Grade') }}: A ({{ __('Outstanding') }})
                    @elseif($appraisal->final_score >= 80) {{ __('Grade') }}: B ({{ __('Exceeds Expectations') }})
                    @elseif($appraisal->final_score >= 70) {{ __('Grade') }}: C ({{ __('Meets Expectations') }})
                    @elseif($appraisal->final_score >= 60) {{ __('Grade') }}: D ({{ __('Needs Improvement') }})
                    @else {{ __('Grade') }}: E ({{ __('Below Expectations') }})
                    @endif
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if($appraisal->notes)
        <div class="section-title">{{ __('Manager Notes') }}</div>
        <div class="notes-box">{{ $appraisal->notes }}</div>
        @endif

        @if($appraisal->calibration_notes)
        <div class="section-title">{{ __('Calibration Notes') }} ({{ __('HR Director') }})</div>
        <div class="notes-box">{{ $appraisal->calibration_notes }}</div>
        @endif

        <!-- 1-on-1 Session -->
        @if($appraisal->meeting_date)
        <div class="section-title">1-on-1 Session</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">{{ __('Meeting Date') }}</div>
                <div class="info-value">{{ $appraisal->meeting_date->format('d M Y') }}</div>
            </div>
            @if($appraisal->meeting_link)
            <div class="info-row">
                <div class="info-label">{{ __('Virtual Meeting Link') }}</div>
                <div class="info-value">{{ $appraisal->meeting_link }}</div>
            </div>
            @endif
        </div>
        @endif

        <!-- Signature Area -->
        <div class="signature-area">
            <div class="sig-col">
                <div class="sig-line"></div>
                <div class="sig-name">{{ $appraisal->user->name }}</div>
                <div class="sig-role">{{ __('Employee') }}</div>
                @if($appraisal->employee_acknowledgement)
                <div style="color: #059669; font-size: 9px; margin-top: 3px;">✓ {{ __('Acknowledged') }}</div>
                @endif
            </div>
            <div class="sig-col">
                <div class="sig-line"></div>
                <div class="sig-name">{{ $appraisal->evaluator->name ?? '-' }}</div>
                <div class="sig-role">{{ __('Direct Manager') }}</div>
            </div>
            <div class="sig-col">
                <div class="sig-line"></div>
                <div class="sig-name">{{ $appraisal->calibrator->name ?? '-' }}</div>
                <div class="sig-role">{{ __('HR Director / Calibrator') }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            {{ __('This document is system-generated by :company HR System.', ['company' => $companyName]) }}
            {{ __('Document ID') }}: APR-{{ str_pad($appraisal->id, 5, '0', STR_PAD_LEFT) }} &bull;
            {{ __('Printed') }}: {{ now()->format('d M Y H:i') }}
        </div>
    </div>
</body>
</html>
