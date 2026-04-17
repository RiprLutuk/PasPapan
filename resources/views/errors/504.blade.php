@extends('errors::layout')

@section('title', __('Gateway Timeout'))

@section('content')
    @include('errors.partials.page', [
        'status' => '504',
        'tone' => 'slate',
        'eyebrow' => __('The server waited too long'),
        'titleText' => __('The response timed out before completion'),
        'summary' => __('One of the backend services did not answer in time. Please wait a short moment, then retry the same page once.'),
        'details' => [
            __('The request may succeed again when the service recovers.'),
            __('If the issue repeats, try again from the dashboard after a short delay.'),
        ],
        'primaryAction' => [
            'label' => __('Retry Current Page'),
            'href' => url()->current(),
        ],
        'secondaryAction' => [
            'label' => __('Return Home'),
            'href' => url('/'),
        ],
    ])
@endsection
