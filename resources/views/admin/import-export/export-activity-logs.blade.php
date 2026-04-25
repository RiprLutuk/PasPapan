<table>
    <thead>
        <tr>
            <th>{{ __('Date') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Action') }}</th>
            <th>{{ __('Description') }}</th>
            <th>{{ __('IP Address') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at }}</td>
                <td>{{ $log->user->name ?? __('System') }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->description }}</td>
                <td>{{ $log->ip_address }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
