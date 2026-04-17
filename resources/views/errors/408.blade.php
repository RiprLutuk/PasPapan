@extends('errors::layout')

@section('title', __('Request Timeout'))

@section('content')
    @include('errors.partials.page', [
        'status' => '408',
        'tone' => 'slate',
        'eyebrow' => __('The request took too long'),
        'titleText' => __('The server stopped waiting for a response'),
        'summary' => __('The connection may have been unstable or the request took longer than expected. You can retry the same page once the network is stable.'),
        'details' => [
            __('Check your internet connection.'),
            __('Retry the request after a short moment.'),
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
