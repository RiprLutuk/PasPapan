<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Date</th>
      <th>Name</th>
      <th>NIP</th>
      <th>Time In</th>
      <th>Time Out</th>
      <th>Shift</th>
      <th>Barcode Id</th>
      <th>Coordinates</th>
      <th>Status</th>
      <th>Note</th>
      <th>Attachment</th>
      <th>Created At</th>
      <th>Updated At</th>

      <th>User Id</th>
      <th>Shift Id</th>
      <th>Raw Status</th>
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
                <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $attendance->latitude_in }},{{ $attendance->longitude_in }}" target="_blank">IN</a>
            @endif
            @if($attendance->latitude_out && $attendance->longitude_out)
                {{ ($attendance->latitude_in ? ' | ' : '') }}
                <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $attendance->latitude_out }},{{ $attendance->longitude_out }}" target="_blank">OUT</a>
            @endif
        </td>
        <td>{{ __($attendance->status) }}</td>
        <td>{{ $attendance->note }}</td>
        <td>{{ $attendance->attachment_url ? (str_starts_with($attendance->attachment_url, 'http') ? $attendance->attachment_url : url($attendance->attachment_url)) : '-' }}</td>
        <td>{{ $attendance->created_at }}</td>
        <td>{{ $attendance->updated_at }}</td>

        <td>{{ $attendance->user_id }}</td>
        <td>{{ $attendance->shift_id }}</td>
        <td>{{ $attendance->status }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
