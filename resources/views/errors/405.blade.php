@extends('errors::layout')

@section('title', __('Method Not Allowed'))

@section('content')
    @include('errors.partials.page', [
        'status' => '405',
        'tone' => 'amber',
        'eyebrow' => __('Unexpected request method'),
        'titleText' => __('This action is not allowed for the current page'),
        'summary' => __('The request reached the correct address, but the action used is not supported here. This often happens after an expired form or repeated submission.'),
        'details' => [
            __('Reload the page before sending the form again.'),
            __('Use the original button or menu path instead of resubmitting an old link.'),
        ],
        'primaryAction' => [
            'label' => __('Return to Dashboard'),
            'href' => url('/'),
        ],
        'secondaryAction' => [
            'label' => __('Open Login'),
            'href' => route('login'),
        ],
    ])
@endsection
