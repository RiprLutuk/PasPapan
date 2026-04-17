@extends('errors::layout')

@section('title', __('Payload Too Large'))

@section('content')
    @include('errors.partials.page', [
        'status' => '413',
        'tone' => 'amber',
        'eyebrow' => __('Upload limit reached'),
        'titleText' => __('The file is larger than the allowed limit'),
        'summary' => __('The upload could not be processed because the selected file is too large for the server configuration.'),
        'details' => [
            __('Try compressing the file before uploading again.'),
            __('Use a smaller image or document if possible.'),
        ],
        'primaryAction' => [
            'label' => __('Open Home'),
            'href' => url('/'),
        ],
        'secondaryAction' => [
            'label' => __('Open Login'),
            'href' => route('login'),
        ],
    ])
@endsection
