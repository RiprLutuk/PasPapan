@extends('errors::layout')

@section('title', __('Payment Required'))

@section('content')
    @include('errors.partials.page', [
        'status' => '402',
        'tone' => 'primary',
        'eyebrow' => __('Feature access is limited'),
        'titleText' => __('Payment or plan upgrade is required'),
        'summary' => __('This feature is not available under the current access level. Please contact the administrator or upgrade the plan before trying again.'),
        'details' => [
            __('Some enterprise-only pages require an active plan.'),
            __('Your account may not have permission for billing-restricted features.'),
        ],
        'primaryAction' => [
            'label' => __('Return to Dashboard'),
            'href' => url('/'),
        ],
        'secondaryAction' => [
            'label' => __('Contact Administrator'),
            'href' => url('/'),
        ],
    ])
@endsection
