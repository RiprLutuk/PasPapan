@component('emails.layouts.modern', ['title' => $companyName ?? config('app.name'), 'eyebrow' => __('Notification'), 'message' => $message ?? null])

# {{ $greeting ?? __('Hello, Admin!') }}

@foreach (($introLines ?? []) as $line)
{!! \Illuminate\Support\Str::markdown($line) !!}
@endforeach

@if (!empty($details))
<div class="email-section">
    <table class="email-data-table" role="presentation">
        @foreach($details as $label => $value)
        <tr>
            <td class="email-data-label">{{ $label }}</td>
            <td class="email-data-value">{{ $value }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif

@if (!empty($actionText) && !empty($actionUrl))
<div style="text-align: center; margin-top: 24px; margin-bottom: 24px;">
    <a href="{{ $actionUrl }}" class="button" target="_blank" rel="noopener">{{ $actionText }}</a>
</div>
@endif

@foreach (($outroLines ?? []) as $line)
{{ $line }}
@endforeach

@if (!empty($actionText) && !empty($actionUrl))
<div class="subcopy">
    <p>@lang('If you\'re having trouble clicking the ":actionText" button, copy and paste the URL below into your web browser:', ['actionText' => $actionText])</p>
    <p><a href="{{ $actionUrl }}">{{ $actionUrl }}</a></p>
</div>
@endif

@endcomponent
