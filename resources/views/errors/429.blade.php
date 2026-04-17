@extends('errors::layout')

@section('title', __('Too Many Requests'))

@section('content')
    @include('errors.partials.page', [
        'status' => '429',
        'tone' => 'blue',
        'eyebrow' => __('Too many requests in a short time'),
        'titleText' => __('Please wait before trying again'),
        'summary' => __('The system temporarily slowed down repeated requests to protect stability. Wait a moment, then retry the page once.'),
        'details' => [
            __('Avoid repeatedly refreshing the same page.'),
            __('Wait a short moment before trying again.'),
        ],
        'primaryAction' => [
            'label' => __('Return Home'),
            'href' => url('/'),
        ],
        'secondaryAction' => [
            'label' => __('Open Notifications'),
            'href' => route('notifications'),
        ],
    ])
@endsection
