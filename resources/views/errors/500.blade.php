@extends('errors::layout')

@section('title', __('Server Error'))

@section('content')
    @include('errors.partials.page', [
        'status' => '500',
        'tone' => 'red',
        'eyebrow' => __('Unexpected server issue'),
        'titleText' => __('The system ran into an internal error'),
        'summary' => __('Something failed while processing the request. This page is safe to leave, and retrying once after a short moment is usually enough.'),
        'details' => [
            __('The request reached the server but could not be completed.'),
            __('If this keeps happening, note the page and contact the administrator.'),
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
