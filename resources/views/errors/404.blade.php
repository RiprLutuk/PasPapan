@extends('errors::layout')

@section('title', __('Page Not Found'))

@section('content')
    @include('errors.partials.page', [
        'status' => '404',
        'tone' => 'red',
        'eyebrow' => __('Destination unavailable'),
        'titleText' => __('The page could not be found'),
        'summary' => __('The address may be incorrect, the page may have been moved, or the menu item may no longer be available.'),
        'details' => [
            __('Check the address in the browser and try again.'),
            __('Return to the dashboard and reopen the menu from there.'),
        ],
        'primaryAction' => [
            'label' => __('Go Home'),
            'href' => url('/'),
        ],
        'secondaryAction' => [
            'label' => __('Open Notifications'),
            'href' => route('notifications'),
        ],
    ])
@endsection
