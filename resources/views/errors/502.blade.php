@extends('errors::layout')

@section('title', __('Bad Gateway'))

@section('content')
    @include('errors.partials.page', [
        'status' => '502',
        'tone' => 'red',
        'eyebrow' => __('Upstream service problem'),
        'titleText' => __('The system received an invalid response'),
        'summary' => __('A service behind the application did not respond correctly. This usually resolves itself, so you can retry after a short delay.'),
        'details' => [
            __('The issue is usually temporary and server-side.'),
            __('Refreshing too often is not recommended while waiting for recovery.'),
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
