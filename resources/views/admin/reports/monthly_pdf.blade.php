<!DOCTYPE html>
<html>
<head>
    <title>{{ __('Monthly Attendance Report') }} - {{ $month }} {{ $year }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .title { font-size: 16pt; font-weight: bold; }
        .subtitle { font-size: 12pt; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #eee; }
        .status-present { color: green; }
        .status-late { color: orange; }
        .status-absent { color: red; }
        .page-break { page-break-after: always; }
        .summary { margin-top: 20px; page-break-inside: avoid; }
    </style>
</head>
<body>
    @foreach($attendances as $userId => $userAttendances)
        @php $user = $userAttendances->first()->user; @endphp
        
        <div class="header">
            <div class="title">{{ __('Monthly Attendance Report') }}</div>
            <div class="subtitle">{{ $month }} {{ $year }}</div>
        </div>

        <div style="margin-bottom: 15px;">
            <strong>{{ __('Name') }}:</strong> {{ $user->name ?? __('N/A') }} <br>
            <strong>{{ __('NIP') }}:</strong> {{ $user->nip ?? '-' }} <br>
            <strong>{{ __('Division') }}:</strong> {{ $user->division->name ?? '-' }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Time In') }}</th>
                    <th>{{ __('Time Out') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Shift') }}</th>
                    <th>{{ __('Note') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($userAttendances as $attendance)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d M Y') }}</td>
                        <td>{{ $attendance->time_in ?? '-' }}</td>
                        <td>{{ $attendance->time_out ?? '-' }}</td>
                        <td>
                            <span class="status-{{ $attendance->status }}">
                                {{ __(ucfirst($attendance->status)) }}
                            </span>
                        </td>
                        <td>{{ $attendance->shift->name ?? '-' }}</td>
                        <td>{{ $attendance->note ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <h3>{{ __('Summary') }}</h3>
            <p>
                <strong>{{ __('Present') }}:</strong> {{ $userAttendances->where('status', 'present')->count() }} |
                <strong>{{ __('Late') }}:</strong> {{ $userAttendances->where('status', 'late')->count() }} |
                <strong>{{ __('Sick/Excused') }}:</strong> {{ $userAttendances->whereIn('status', ['sick', 'excused'])->count() }} |
                <strong>{{ __('Absent') }}:</strong> {{ $userAttendances->where('status', 'absent')->count() }}
            </p>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
