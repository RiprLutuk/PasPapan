@extends('errors::layout')

@section('title', __('Page Expired'))

@section('content')
    @include('errors.partials.page', [
        'status' => '419',
        'tone' => 'amber',
        'eyebrow' => __('Session ended'),
        'titleText' => __('Your session has expired'),
        'summary' => __('This usually happens after a period of inactivity or when the page stays open for too long before a form is submitted.'),
        'details' => [
            __('Reload the page to request a fresh session token.'),
            __('If needed, sign in again before continuing.'),
        ],
        'primaryAction' => [
            'label' => __('Reload Current Page'),
            'href' => url()->current(),
        ],
        'secondaryAction' => [
            'label' => __('Log In Again'),
            'href' => route('login'),
        ],
    ])
@endsection
