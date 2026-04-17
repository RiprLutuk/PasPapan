@extends('errors::layout')

@section('title', __('Access Denied'))

@section('content')
    @include('errors.partials.page', [
        'status' => '403',
        'tone' => 'amber',
        'eyebrow' => __('Permission required'),
        'titleText' => __('You do not have access to this page'),
        'summary' => __($exception->getMessage() ?: 'Your current role does not have permission to open this page or complete this action.'),
        'details' => [
            __('The page may be limited to administrators or supervisors.'),
            __('If you believe you should have access, ask the administrator to review your role permissions.'),
        ],
        'primaryAction' => [
            'label' => __('Return to Dashboard'),
            'href' => url('/'),
        ],
        'secondaryAction' => [
            'label' => __('Open Notifications'),
            'href' => route('notifications'),
        ],
    ])
@endsection
