@extends('errors::layout')

@section('title', __('Unauthorized'))

@section('content')
    @include('errors.partials.page', [
        'status' => '401',
        'tone' => 'slate',
        'eyebrow' => __('Authentication is required'),
        'titleText' => __('You need to sign in before continuing'),
        'summary' => __('The page you requested requires a valid session. Please sign in again and then repeat the action you were trying to do.'),
        'details' => [
            __('Your session may have expired.'),
            __('The page may only be available for authenticated users.'),
            __('Signing in again usually resolves this issue.'),
        ],
        'primaryAction' => [
            'label' => __('Log In'),
            'href' => route('login'),
        ],
        'secondaryAction' => [
            'label' => __('Go Home'),
            'href' => url('/'),
        ],
    ])
@endsection
