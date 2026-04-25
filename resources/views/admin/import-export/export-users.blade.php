<table>
  <thead>
    <tr>
      <th>#</th>
      <th>{{ __('NIP') }}</th>
      <th>{{ __('Name') }}</th>
      <th>{{ __('Email') }}</th>
      <th>{{ __('Group') }}</th>
      <th>{{ __('Phone') }}</th>
      <th>{{ __('Gender') }}</th>
      <th>{{ __('Basic Salary') }}</th>
      <th>{{ __('Hourly Rate') }}</th>
      <th>{{ __('Division') }}</th>
      <th>{{ __('Job Title') }}</th>
      <th>{{ __('Education') }}</th>
      <th>{{ __('Birth Date') }}</th>
      <th>{{ __('Birth Place') }}</th>
      <th>{{ __('Address') }}</th>
      <th>{{ __('City') }}</th>
      <th>{{ __('Created At') }}</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($users as $user)
      <tr>
        <td>{{ $loop->iteration }}</td>
        <td data-type="s">{{ $user->nip }}</td>
        <td>{{ $user->name }}</td>
        <td>{{ $user->email }}</td>
        <td>{{ $user->group }}</td>
        <td data-type="s">{{ $user->phone }}</td>
        <td>{{ $user->gender }}</td>
        <td>{{ $user->basic_salary }}</td>
        <td>{{ $user->hourly_rate }}</td>
        <td>{{ $user->division?->name }}</td>
        <td>{{ $user->jobTitle?->name }}</td>
        <td>{{ $user->education?->name }}</td>
        <td>{{ $user->birth_date?->format('Y-m-d') }}</td>
        <td>{{ $user->birth_place }}</td>
        <td>{{ $user->address }}</td>
        <td>{{ $user->city }}</td>
        <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
