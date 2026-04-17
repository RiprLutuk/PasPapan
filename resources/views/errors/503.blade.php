@extends('errors::layout')

@section('title', __('Service Unavailable'))

@section('content')
    @include('errors.partials.page', [
        'status' => '503',
        'tone' => 'blue',
        'eyebrow' => __('Service temporarily unavailable'),
        'titleText' => __('The application is currently under maintenance'),
        'summary' => __('The system is temporarily unavailable while updates or maintenance work are in progress. Please come back again shortly.'),
        'details' => [
            __('This page usually returns automatically once maintenance is complete.'),
            __('No action is needed from your side other than checking again later.'),
        ],
        'primaryAction' => [
            'label' => __('Check Again'),
            'href' => url()->current(),
        ],
        'secondaryAction' => [
            'label' => __('Return Home'),
            'href' => url('/'),
        ],
    ])
@endsection
