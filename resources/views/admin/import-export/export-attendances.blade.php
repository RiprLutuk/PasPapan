<table>
  <thead>
    <tr>
      <th>#</th>
      <th>{{ __('Date') }}</th>
      <th>{{ __('Name') }}</th>
      <th>{{ __('NIP') }}</th>
      <th>{{ __('Time In') }}</th>
      <th>{{ __('Time Out') }}</th>
      <th>{{ __('Shift') }}</th>
      <th>{{ __('Barcode Id') }}</th>
      <th>{{ __('Coordinates') }}</th>
      <th>{{ __('Status') }}</th>
      <th>{{ __('Note') }}</th>
      <th>{{ __('Attachment') }}</th>
      <th>{{ __('Created At') }}</th>
      <th>{{ __('Updated At') }}</th>

      <th>{{ __('User Id') }}</th>
      <th>{{ __('Shift Id') }}</th>
      <th>{{ __('Raw Status') }}</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($attendances as $attendance)
      <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $attendance->date?->format('Y-m-d') }}</td>
        <td>{{ $attendance->user?->name }}</td>
        <td data-type="s">{{ $attendance->user?->nip }}</td>
        <td>{{ \App\Helpers::format_time($attendance->time_in) }}</td>
        <td>{{ \App\Helpers::format_time($attendance->time_out) }}</td>
        <td>{{ $attendance->shift?->name }}</td>
        <td>{{ $attendance->barcode_id }}</td>
        <td>
            @if($attendance->latitude_in && $attendance->longitude_in)
                <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $attendance->latitude_in }},{{ $attendance->longitude_in }}" target="_blank" rel="noopener noreferrer">{{ __('IN') }}</a>
            @endif
            @if($attendance->latitude_out && $attendance->longitude_out)
                {{ ($attendance->latitude_in ? ' | ' : '') }}
                <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $attendance->latitude_out }},{{ $attendance->longitude_out }}" target="_blank" rel="noopener noreferrer">{{ __('OUT') }}</a>
            @endif
        </td>
        <td>{{ __($attendance->status) }}</td>
        <td>{{ $attendance->note }}</td>
        <td>
            @if(is_array($attendance->attachment_url))
                @foreach($attendance->attachment_url as $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ __('Link') }} {{ $loop->iteration }}</a><br>
                @endforeach
            @elseif($attendance->attachment_url)
                {{ str_starts_with($attendance->attachment_url, 'http') ? $attendance->attachment_url : url($attendance->attachment_url) }}
            @else
                - 
            @endif
        </td>
        <td>{{ $attendance->created_at }}</td>
        <td>{{ $attendance->updated_at }}</td>

        <td>{{ $attendance->user_id }}</td>
        <td>{{ $attendance->shift_id }}</td>
        <td>{{ $attendance->status }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
