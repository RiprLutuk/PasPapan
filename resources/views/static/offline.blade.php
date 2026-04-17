<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url={{ url('/offline.html') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Offline') }} | {{ config('app.name') }}</title>
</head>
<body>
    <p>{{ __('Redirecting to the offline page...') }}</p>
</body>
</html>
