@component('emails.layouts.modern', ['title' => __('New Leave Request'), 'eyebrow' => __('Approval Needed'), 'message' => $message ?? null])

# {{ __('New Leave Request') }}

{{ __('Hello Admin,') }}

{{ __('There is a new leave request that requires your attention.') }}

<div class="email-section">
    <table class="email-data-table" role="presentation">
        <tr>
            <td class="email-data-label">{{ __('Employee') }}</td>
            <td class="email-data-value">{{ $userName }}</td>
        </tr>
        <tr>
            <td class="email-data-label">{{ __('Type') }}</td>
            <td class="email-data-value">{{ __('' . $leaveType) }}</td>
        </tr>
        <tr>
            <td class="email-data-label">{{ __('Date') }}</td>
            <td class="email-data-value">
                {{ $dateDisplay }}
                <span style="font-size: 12px; color: #5d7766; font-weight: 500;">({{ $daysInfo }})</span>
            </td>
        </tr>
        <tr>
            <td class="email-data-label">{{ __('Reason') }}</td>
            <td class="email-data-value">{{ $reason }}</td>
        </tr>
    </table>
</div>

<div style="text-align: center;">
    <a href="{{ $url }}" class="button">{{ __('View Request') }}</a>
</div>

{{ __('Please login to approve or reject this request.') }}

@endcomponent
